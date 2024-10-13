<?php
/**
 * Helper Functions
 *
 * @since   1.0.0
 *
 * @package   Site_Functionality
 */
namespace Site_Functionality;

/**
 * Get Post Terms that Require Membership
 *
 * @param  array   $terms
 * @param  integer $post_id
 * @return array $terms
 */
function get_post_terms_with_levels( array $terms, int $post_id ) {
	global $wpdb;

	$db_query = "(SELECT m.id, m.name FROM $wpdb->pmpro_memberships_categories mc LEFT JOIN $wpdb->pmpro_membership_levels m ON mc.membership_id = m.id WHERE mc.category_id IN(" . implode( ',', array_map( 'intval', $terms ) ) . ") AND m.id IS NOT NULL) UNION (SELECT m.id, m.name FROM $wpdb->pmpro_memberships_pages mp LEFT JOIN $wpdb->pmpro_membership_levels m ON mp.membership_id = m.id WHERE mp.page_id = '" . esc_sql( $post_id ) . "')";

	return $wpdb->get_results( $db_query );
}
