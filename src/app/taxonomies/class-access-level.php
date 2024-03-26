<?php
/**
 * Taxonomy
 *
 * @since   1.0.0
 *
 * @package   Site_Functionality
 */
namespace Site_Functionality\App\Taxonomies;

use Site_Functionality\Common\Abstracts\Taxonomy;

/**
 * Class Taxonomies
 *
 * @package Site_Functionality\App\Taxonomies
 * @since 1.0.0
 */
class Access_Level extends Taxonomy {

	/**
	 * Taxonomy data
	 */
	public const TAXONOMY = array(
		'id'                => 'access_level',
		'singular_name'     => 'Access Level',
		'name'              => 'Access Levels',
		'menu_name'         => 'Access Levels',
		'plural'            => 'Access Levels',
		'slug'              => 'access',
		'post_types'        => array(
			'event',
			'course',
			'module',
			'post',
			'page',
		),
		'hierarchical'      => true,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
		'show_tagcloud'     => false,
		'show_in_rest'      => true,
		'has_archive'       => false,
		'meta_box_cb'       => 'post_categories_meta_box',
		'rest_base'         => 'access',
	);

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();
	}

	/**
	 * Add rewrite rules
	 *
	 * @link https://developer.wordpress.org/reference/functions/add_rewrite_rule/
	 *
	 * @return void
	 */
	public function rewrite_rules(): void {}
	
}
