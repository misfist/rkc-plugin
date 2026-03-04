<?php
/**
 * Integrations
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Integrations;

use Site_Functionality\Common\Abstracts\Base;
use function Site_Functionality\get_post_terms_with_levels;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Paid_Memberships_Pro extends Base {

	/**
	 * Data
	 *
	 * @var array
	 */
	public $data = array(
		'restricted_taxonomies' => array(
			'access_level',
			'course-category',
			'event-categories',
		),
		'free_membership_role'  => 7,
		'post_types'            => array(
			'event',
			'course',
			'lesson',
			'module',
			'post',
			'page',
		),
	);

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $settings ) {
		parent::__construct( $settings );
		$this->init();
	}

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		/**
		 * Add metaboxes to taxonomies
		 */
		foreach ( $this->data['restricted_taxonomies'] as $taxonomy ) {
			/**
			 * @see https://developer.wordpress.org/reference/hooks/taxonomy_add_form_fields/
			 */
			\add_action( "{$taxonomy}_add_form_fields", '\pmpro_term_add_form_fields' );
			/**
			 * @see https://developer.wordpress.org/reference/hooks/taxonomy_edit_form_fields/
			 */
			\add_action( "{$taxonomy}_edit_form_fields", '\pmpro_term_edit_form_fields', 10, 2 );
			/**
			 * @see https://developer.wordpress.org/reference/hooks/saved_taxonomy/
			 */
			\add_action( "saved_{$taxonomy}", '\pmpro_term_saved' );

		}

		/**
		 * Save MailChimp Add-on Options
		 *
		 * @since 1.0.3
		 */
		$mailchimp_options = get_option( 'pmpromc_options' );
		if ( ! empty( $mailchimp_options ) && isset( $mailchimp_options['additional_lists'] ) && ! empty( $mailchimp_options['additional_lists'] ) ) {
			$this->data['mailchimp_lists'] = $mailchimp_options['additional_lists'];
		} else {
			$this->data['mailchimp_lists'] = array();
		}

		/**
		 * Filter Checkout Page Link
		 *
		 * @since 1.0.3
		 */
		if ( function_exists( '\pmpromc_options_page' ) && ! empty( $this->data['mailchimp_lists'] ) ) {
			add_filter( 'page_link', array( $this, 'mailchimp_optin_default' ), 11, 3 );
		}

		\add_filter( 'pmpro_has_membership_access_filter', array( $this, 'content_membership_access' ), 15, 4 );

		// \add_filter( 'pmpro_has_membership_access_filter', array( $this, 'course_membership_access' ), 15, 4 );

		// \add_filter( 'pmpro_has_membership_access_filter', array( $this, 'event_membership_access' ), 15, 4 );

		/**
		 * Addon Packages Post Types
		 *
		 * @since 1.0.2
		 *
		 * @link https://www.paidmembershipspro.com/add-ons/pmpro-purchase-access-to-a-single-page/
		 */
		\add_filter( 'pmproap_supported_post_types', array( $this, 'addon_package_post_types' ) );

		/**
		 * @see https://www.paidmembershipspro.com/assign-a-membership-level-to-a-wordpress-user-role/
		 */
		// \add_action( 'set_user_role', array( $this, 'set_wp_user_role' ), 10, 3 );

		if ( class_exists( '\PMPRO_Roles' ) ) {
			/**
			 *
			 * @see https://www.paidmembershipspro.com/add-ons/approval-process-membership
			 */
			\add_action( 'pmpro_approvals_after_approve_member', array( $this, 'after_approve_member' ), '', 2 );

			\add_action( 'pmpro_approvals_after_deny_member', array( $this, 'after_deny_member' ), '', 2 );

			\add_action( 'pmpro_approvals_after_reset_member', array( $this, 'after_reset_member' ), '', 2 );
		}

		/**
		 * Members List
		 */
		add_action( 'pmpro_memberslist_extra_tablenav', array( $this, 'add_members_list_filter_options' ) );
		add_filter( 'pmpro_members_list_sql', array( $this, 'members_list_filter_sql' ) );
		add_filter( 'pmpro_members_list_user', array( $this, 'filter_pending_from_active' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_members_list_scripts' ) );

		if ( class_exists( '\PMPro_Approvals' ) ) {
			/**
			 * @see https://www.paidmembershipspro.com/add-ons/approval-process-membership/
			 *
			 * @since 1.0.4
			 */
			add_filter( 'pmpro_memberslist_extra_cols', array( $this, 'approval_col_header' ) );
			add_action( 'pmpro_manage_memberslist_custom_column', array( $this, 'approval_col_body' ), 10, 3 );
		}
	}

	/**
	 * Filter Course Access
	 * Modify access to course based on term
	 *
	 * @since 1.0.1
	 *
	 * @link https://www.paidmembershipspro.com/hook/pmpro_has_membership_access_filter_post_type/
	 *
	 * @param  bool     $has_access
	 * @param  \WP_Post $post
	 * @param  \WP_User $user
	 * @param  array    $post_membership_levels
	 * @return bool $has_access
	 */
	public function content_membership_access( $has_access, $post, $user, $post_membership_levels ): bool {
		if ( empty( $post ) || empty( $post->ID ) ) {
			return $has_access;
		}

		$post_type = $post->post_type;

		if ( ! in_array( $post_type, $this->data['post_types'] ) ) {
			return $has_access;
		}

		$taxonomies = $this->data['restricted_taxonomies'];

		$terms = wp_get_post_terms( $post->ID, $taxonomies, array( 'fields' => 'ids' ) );

		$restricted_terms = get_post_terms_with_levels( $terms, $post->ID );

		if ( ! empty( $restricted_terms ) ) {
			$restricted_terms_ids = array_map( 'intval', wp_list_pluck( $restricted_terms, 'id' ) );
			$user_level           = pmpro_getMembershipLevelForUser( $user->ID );

			$has_access = $user_level && in_array( (int) $user_level->id, $restricted_terms_ids );
		} elseif ( empty( $post_membership_levels ) ) {
			$has_access = true;
		}

		return $has_access;
	}

	/**
	 * Filter Course Access
	 * Modify access to course based on term
	 *
	 * @link https://www.paidmembershipspro.com/hook/pmpro_has_membership_access_filter_post_type/
	 *
	 * @param  bool     $has_access
	 * @param  \WP_Post $post
	 * @param  \WP_User $user
	 * @param  array    $post_membership_levels
	 * @return bool $has_access
	 */
	public function course_membership_access( $has_access, $post, $user, $post_membership_levels ): bool {
		if ( empty( $post ) || empty( $post->ID ) ) {
			return $has_access;
		}

		$post_type = $post->post_type;

		if ( 'course' !== $post_type ) {
			return $has_access;
		}

		$taxonomies = $this->data['restricted_taxonomies'];

		$terms = wp_get_post_terms( $post->ID, $taxonomies, array( 'fields' => 'ids' ) );

		$restricted_terms = get_post_terms_with_levels( $terms, $post->ID );

		if ( ! empty( $restricted_terms ) ) {
			$restricted_terms_ids = array_map( 'intval', wp_list_pluck( $restricted_terms, 'id' ) );
			$user_level           = pmpro_getMembershipLevelForUser( $user->ID );

			$has_access = $user_level && in_array( (int) $user_level->id, $restricted_terms_ids );
		} elseif ( empty( $post_membership_levels ) ) {
			$has_access = true;
		}

		return $has_access;
	}

	/**
	 * Filter Event Access
	 * Modify access to event based on term
	 *
	 * @link https://www.paidmembershipspro.com/hook/pmpro_has_membership_access_filter_post_type/
	 *
	 * @param  bool     $has_access
	 * @param  \WP_Post $post
	 * @param  \WP_User $user
	 * @param  array    $post_membership_levels
	 * @return bool $has_access
	 */
	public function event_membership_access( $has_access, $post, $user, $post_membership_levels ): bool {
		if ( empty( $post ) || empty( $post->ID ) ) {
			return $has_access;
		}

		$post_type = $post->post_type;

		if ( 'event' !== $post_type ) {
			return $has_access;
		}

		$taxonomies = $this->data['restricted_taxonomies'];

		$terms = wp_get_post_terms( $post->ID, $taxonomies, array( 'fields' => 'ids' ) );

		$restricted_terms = get_post_terms_with_levels( $terms, $post->ID );

		if ( ! empty( $restricted_terms ) ) {
			$restricted_terms_ids = array_map( 'intval', wp_list_pluck( $restricted_terms, 'id' ) );
			$user_level           = pmpro_getMembershipLevelForUser( $user->ID );

			$has_access = $user_level && in_array( (int) $user_level->id, $restricted_terms_ids );
		} elseif ( empty( $post_membership_levels ) ) {
			$has_access = true;
		}

		return $has_access;
	}

	/**
	 * Enable Addon Package for All Post Types
	 *
	 * @link https://www.paidmembershipspro.com/add-ons/pmpro-purchase-access-to-a-single-page/
	 *
	 * @since 1.0.2
	 *
	 * @param array $post_types
	 * @return array
	 */
	public function addon_package_post_types( array $post_types ): array {
		$post_types = $this->data['post_types'];
		return $post_types;
	}

	/**
	 * Modify Checkout Page Link
	 * Append Checkout page link with query args that will select MailChimp list opt-in by default.
	 *
	 * @link https://developer.wordpress.org/reference/hooks/page_link/
	 * @link https://www.paidmembershipspro.com/add-ons/pmpro-mailchimp-integration/
	 * @link https://developer.wordpress.org/reference/functions/add_query_arg/
	 *
	 * @since 1.0.3
	 *
	 * @param string  $link
	 * @param integer $post_id
	 * @param boolean $sample
	 * @return string $link
	 */
	public function mailchimp_optin_default( string $link, int $post_id, bool $sample ): string {
		$checkout_page = (int) get_option( 'pmpro_checkout_page_id' );

		if ( $checkout_page && $checkout_page === $post_id ) {
			$args = array(
				'additional_lists' => $this->data['mailchimp_lists'],
			);
			$link = add_query_arg( $args, $link );
		}
		return $link;
	}

	/**
	 * Assign a level to role
	 * Assign role to admin users
	 *
	 * @see https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
	 *
	 * @param  int    $user_id
	 * @param  string $role
	 * @param  array  $old_roles
	 * @return void
	 */
	public function set_wp_user_role( $user_id, $role, $old_roles ): void {
		$member_roles = array(
			'pmpro_approver',
			'pmpro_membership_manager',
			'administrator',
			'author',
			'editor',
		);
		/**
		 * Free level = 7
		 */
		$free_membership_role = $this->data['free_membership_role'];
		if ( in_array( $role, $member_roles ) ) {
			pmpro_changeMembershipLevel( $free_membership_role, $user_id );
		}
	}

	/**
	 * Update User Roles
	 * If member is approved, update user's roles
	 *
	 * @see https://www.paidmembershipspro.com/add-ons/approval-process-membership
	 *
	 * @param  int $user_id
	 * @param  int $level_id
	 * @return void
	 */
	public function after_approve_member( $user_id, $level_id ): void {
		$user = new \WP_User( $user_id );
		if ( $user ) {
			$user->remove_role( 'pending' );
			$user->remove_role( 'subscriber' );

			$roles = \PMPRO_Roles::get_roles_for_level( $level_id );
			if ( is_array( $roles ) && ! empty( $roles ) ) {
				foreach ( $roles as $role_key => $role_name ) {
					$user->add_role( $role_key );
				}
			}

			$bbp = \bbpress();

			if ( function_exists( '\bbp_set_user_role' ) ) {
				bbp_set_user_role( $user_id, 'participant' );
			}
		}
	}

	/**
	 * Update WP User Role
	 * If member is denied, update user's roles
	 *
	 * @see https://www.paidmembershipspro.com/add-ons/approval-process-membership
	 *
	 * @param  int $user_id
	 * @param  int $level_id
	 * @return void
	 */
	public function after_deny_member( $user_id, $level_id ): void {
		$user = new \WP_User( $user_id );
		if ( $user ) {
			$roles = \PMPRO_Roles::get_roles_for_level( $level_id );
			if ( is_array( $roles ) && ! empty( $roles ) ) {
				foreach ( $roles as $role_key => $role_name ) {
					$user->remove_role( $role_key );
				}
			}

			$user->set_role( 'denied' );

			if ( function_exists( '\bbp_set_user_role' ) ) {
				bbp_set_user_role( $user_id, 'bbp_blocked' );
			}
		}
	}

	/**
	 * Update User Roles
	 * If member is pending, update user's roles
	 *
	 * @see https://www.paidmembershipspro.com/add-ons/approval-process-membership
	 *
	 * @param  int $user_id
	 * @param  int $level_id
	 * @return void
	 */
	public function after_reset_member( $user_id, $level_id ): void {
		$user = new \WP_User( $user_id );
		if ( $user ) {

			$roles = \PMPRO_Roles::get_roles_for_level( $level_id );
			if ( is_array( $roles ) && ! empty( $roles ) ) {
				foreach ( $roles as $role_key => $role_name ) {
					$user->add_role( $role_key );
				}
			}

			$user->remove_role( 'subscriber' );
			$user->remove_role( 'member' );
			$user->add_role( 'pending' );

			if ( function_exists( '\bbp_set_user_role' ) ) {
				bbp_set_user_role( $user_id, 'spectator' );
			}
		}
	}

	/**
	 * Add custom filter options to the members list dropdown.
	 * 
	 * @since 1.0.4
	 *
	 * @param string $which The position of the nav (top or bottom).
	 * @return void
	 */
	public function add_members_list_filter_options( string $which ): void {
		if ( 'top' !== $which ) {
			return;
		}

		$l = isset( $_REQUEST['l'] ) ? sanitize_text_field( $_REQUEST['l'] ) : false;
		?>
			<option value="active" <?php selected( $l, 'active' ); ?>><?php esc_html_e( 'Active Members', 'site-functionality' ); ?></option>
			<option value="paid" <?php selected( $l, 'paid' ); ?>><?php esc_html_e( 'Paid Members', 'site-functionality' ); ?></option>
		<?php
	}

	/**
	 * Filter the members list SQL for custom active and paid filters.
	 * 
	 * @since 1.0.4
	 *
	 * @param string $sql The SQL query.
	 * @return string
	 */
	public function members_list_filter_sql( string $sql ): string {
		$l = isset( $_REQUEST['l'] ) ? sanitize_text_field( $_REQUEST['l'] ) : false;

		if ( 'active' !== $l && 'paid' !== $l ) {
			return $sql;
		}

		global $wpdb;

		// Fix the members list table query which casts our $l value to 0.
		$sql = str_replace( "AND mu.status = 'active' AND mu.membership_id = '0'", "AND mu.status = 'active'", $sql );

		$approval_levels = \PMPro_Approvals::getApprovalLevels();

		$extra = '';

		if ( 'paid' === $l ) {
			$extra .= ' AND ( m.initial_payment > 0 OR m.billing_amount > 0 ) ';
		}

		if ( ! empty( $approval_levels ) ) {
			$extra .= " AND NOT EXISTS (
            SELECT 1 FROM {$wpdb->usermeta} um
            WHERE um.user_id = u.ID
            AND um.meta_key = CONCAT( 'pmpro_approval_', mu.membership_id )
            AND um.meta_value LIKE '%\"pending\"%'
        ) ";
		}

		if ( ! empty( $extra ) ) {
			if ( strpos( $sql, ' GROUP BY' ) !== false ) {
				// Members list table query.
				$sql = str_replace( ' GROUP BY', $extra . ' GROUP BY', $sql );
			} elseif ( strpos( $sql, 'ORDER BY' ) !== false ) {
				// CSV export query.
				$sql = str_replace( 'ORDER BY', $extra . 'ORDER BY', $sql );
			} else {
				// COUNT query — append at the end.
				$sql .= $extra;
			}
		}

		return $sql;
	}

	/**
	 * Remove pending members from the Active and Paid Members list filters.
	 * 
	 * @since 1.0.4
	 *
	 * @param object $user The current user object.
	 * @return object|false The user object or false to exclude.
	 */
	public function filter_pending_from_active( $user ) {
		$l = isset( $_REQUEST['l'] ) ? sanitize_text_field( $_REQUEST['l'] ) : false;

		if ( 'active' !== $l && 'paid' !== $l ) {
			return $user;
		}

		$level_id = isset( $user->membership_id ) ? (int) $user->membership_id : 0;

		if ( empty( $level_id ) ) {
			return $user;
		}

		if ( class_exists( '\PMPro_Approvals' ) && ! \PMPro_Approvals::requiresApproval( $level_id ) ) {
			return $user;
		}

		if ( class_exists( '\PMPro_Approvals' ) && \PMPro_Approvals::isPending( $user->ID, $level_id ) ) {
			return false;
		}

		return $user;
	}

	/**
	 * Enqueue scripts for the members list page.
	 * 
	 * @since 1.0.4
	 *
	 * @param string $hook The current admin page hook.
	 * @return void
	 */
	public function enqueue_members_list_scripts( string $hook ): void {
		if ( 'memberships_page_pmpro-memberslist' !== $hook ) {
			return;
		}

		$l = isset( $_REQUEST['l'] ) ? sanitize_text_field( $_REQUEST['l'] ) : '';

		wp_add_inline_script(
			'jquery',
			sprintf(
				'(function($){
                $(function(){
                    var $select = $("select[name=\'l\']");
                    $select.append(
                        $("<option>", { value: "active", text: %s, selected: %s }),
                        $("<option>", { value: "paid",   text: %s, selected: %s })
                    );
                });
            })(jQuery);',
				wp_json_encode( __( 'Active Members', 'site-functionality' ) ),
				wp_json_encode( 'active' === $l ),
				wp_json_encode( __( 'Paid Members', 'site-functionality' ) ),
				wp_json_encode( 'paid' === $l )
			)
		);
	}

	/**
	 * Add an "Approval Status" column header to the PMPro Members List.
	 * 
	 * @since 1.0.4
	 *
	 * @param array $columns Existing extra columns.
	 * @return array
	 */
	public function approval_col_header( array $columns ): array {
		$columns['approval_status'] = __( 'Approval Status', 'site-functionality' );
		return $columns;
	}

	/**
	 * Output the approval status badge for each member row in the Members List.
	 *
	 * @since 1.0.4
	 *
	 * @param string $column_name The current column name.
	 * @param int    $user_id     The current user's ID.
	 * @param array  $item        The membership data for the current row.
	 * @return void
	 */
	public function approval_col_body( string $column_name, int $user_id, $item ): void {
		if ( 'approval_status' !== $column_name ) {
			return;
		}

		$level_id = isset( $item['membership_id'] ) ? (int) $item['membership_id'] : 0;

		if ( empty( $level_id ) ) {
			return;
		}

		if ( ! \PMPro_Approvals::requiresApproval( $level_id ) ) {
			echo '<span style="color:var(--pmpro--color--almost-black);opacity:0.4;font-size:11px;">' . esc_html__( 'N/A', 'site-functionality' ) . '</span>';
			return;
		}

		if ( \PMPro_Approvals::isApproved( $user_id, $level_id ) ) {
			$status = 'approved';
		} elseif ( \PMPro_Approvals::isPending( $user_id, $level_id ) ) {
			$status = 'pending';
		} else {
			$status = 'denied';
		}

		$labels = array(
			'pending'  => __( 'Pending', 'site-functionality' ),
			'approved' => __( 'Approved', 'site-functionality' ),
			'denied'   => __( 'Denied', 'site-functionality' ),
		);

		$bg_colors = array(
			'pending'  => 'var(--pmpro--color--alert-background)',
			'approved' => 'var(--pmpro--color--success-background)',
			'denied'   => 'var(--pmpro--color--error-background)',
		);

		$text_colors = array(
			'pending'  => 'var(--pmpro--color--alert-text)',
			'approved' => 'var(--pmpro--color--success-text)',
			'denied'   => 'var(--pmpro--color--error-text)',
		);

		$label      = isset( $labels[ $status ] ) ? $labels[ $status ] : esc_html( $status );
		$bg_color   = isset( $bg_colors[ $status ] ) ? $bg_colors[ $status ] : 'var(--pmpro--color--almost-black)';
		$text_color = isset( $text_colors[ $status ] ) ? $text_colors[ $status ] : 'var(--pmpro--color--almost-black)';

		printf(
			'<span style="display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;font-weight:600;color:%s;background:%s;">%s</span>',
			esc_attr( $text_color ),
			esc_attr( $bg_color ),
			esc_html( $label )
		);
	}
}
