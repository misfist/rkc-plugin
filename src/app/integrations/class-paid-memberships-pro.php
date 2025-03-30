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
		\add_action( 'set_user_role', array( $this, 'set_wp_user_role' ), 10, 3 );

		if( class_exists( '\PMPRO_Roles' ) ) {
			/**
			 *
			 * @see https://www.paidmembershipspro.com/add-ons/approval-process-membership
			 */
			\add_action( 'pmpro_approvals_after_approve_member', array( $this, 'after_approve_member' ), '', 2 );

			\add_action( 'pmpro_approvals_after_deny_member', array( $this, 'after_deny_member' ), '', 2 );

			\add_action( 'pmpro_approvals_after_reset_member', array( $this, 'after_reset_member' ), '', 2 );
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
	public function content_membership_access( $has_access, $post, $user, $post_membership_levels ) : bool {
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
	public function course_membership_access( $has_access, $post, $user, $post_membership_levels ) : bool {
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
	public function event_membership_access( $has_access, $post, $user, $post_membership_levels ) : bool {
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
	public function addon_package_post_types( array $post_types ) : array {
		$post_types = $this->data['post_types'];
		return $post_types;
	}

	/**
	 * Assign a level to role
	 *
	 * @see https://www.paidmembershipspro.com/create-a-plugin-for-pmpro-customizations/
	 *
	 * @param  int    $user_id
	 * @param  string $role
	 * @param  array  $old_roles
	 * @return void
	 */
	public function set_wp_user_role( $user_id, $role, $old_roles ) : void {
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
	public function after_approve_member( $user_id, $level_id ) : void {
		$user = new \WP_User( $user_id );
		if ( $user ) {
			$user->remove_role( 'pending' );
			$user->remove_role( 'subscriber' );

			$roles = \PMPRO_Roles::get_roles_for_level( $level_id );
			if( is_array( $roles ) && ! empty( $roles ) ) {
				foreach( $roles as $role_key => $role_name ) {
					$user->add_role( $role_key );
				}
			}

			$bbp = \bbpress();

			if( function_exists( '\bbp_set_user_role' ) ) {
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
	public function after_deny_member( $user_id, $level_id ) : void {
		$user = new \WP_User( $user_id );
		if ( $user ) {
			$user->set_role( 'subscriber' );

			if( function_exists( '\bbp_set_user_role' ) ) {
				bbp_set_user_role( $user_id, '' );
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
	public function after_reset_member( $user_id, $level_id ) : void {
		$user = new \WP_User( $user_id );
		if ( $user ) {

			$roles = \PMPRO_Roles::get_roles_for_level( $level_id );
			if( is_array( $roles ) && ! empty( $roles ) ) {
				foreach( $roles as $role_key => $role_name ) {
					$user->add_role( $role_key );
				}
			}

			$user->remove_role( 'subscriber' );
			$user->remove_role( 'member' );
			$user->add_role( 'pending' );

			if( function_exists( '\bbp_set_user_role' ) ) {
				bbp_set_user_role( $user_id, 'spectator' );
			}
		}
	}

}
