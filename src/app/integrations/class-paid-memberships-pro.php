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
			'lesson-tag',
			'event-categories',
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

		\add_filter( 'pmpro_has_membership_access_filter', array( $this, 'course_membership_access' ), 15, 4 );
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
			$user_level = pmpro_getMembershipLevelForUser( $user->ID );

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

		$terms = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );

		$restricted_terms = get_post_terms_with_levels( $terms, $post->ID );

		if ( ! empty( $restricted_terms ) ) {
			$restricted_terms_ids = array_map( 'intval', wp_list_pluck( $restricted_terms, 'id' ) );
			$user_level           = pmpro_getMembershipLevelForUser( $user->ID );

			$has_access = $user_level && in_array( (int) $user_level->subscription_id, $restricted_terms_ids );
		} elseif ( empty( $post_membership_levels ) ) {
			$has_access = true;
		}
		var_dump( '$post_membership_levels', $post_membership_levels );

		return $has_access;
	}

}
