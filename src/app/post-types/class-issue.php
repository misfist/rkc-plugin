<?php
/**
 * Content Post_Types
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Post_Types;

use Site_Functionality\Common\Abstracts\Post_Type;
use Site_Functionality\App\Post_Types\Article;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Issue extends Post_Type {

	/**
	 * Post_Type data
	 */
	public const POST_TYPE = array(
		'id'            => 'issue',
		'slug'          => 'issue',
		'menu'          => 'Issues',
		'title'         => 'Issues',
		'singular'      => 'Issue',
		'menu_icon'     => 'dashicons-media-document',
		'taxonomies'    => array(),
		'has_archive'   => 'print',
		'with_front'    => false,
		'rest_base'     => 'issues',
		'supports'      => array(
			'title',
			'editor',
			'excerpt',
			'author',
			'thumbnail',
			'custom-fields',
			'shadow-terms',
		),
		'menu_position' => 9,
	);

	/**
	 * Init
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();

		// \add_action( 'init', array( $this, 'register_meta' ) );
		\add_action( 'acf/init', array( $this, 'register_fields' ) );
		\add_filter( 'acf/rest/format_value_for_rest/type=date_picker', array( $this, 'format_date_as_year_rest' ), 10, 5 );

	}

	/**
	 * Register Custom Fields
	 *
	 * @return void
	 */
	public function register_fields(): void {
		$fields = array(
			array(
				'key'               => 'field_issue_year',
				'label'             => __( 'Issue Year', 'site-functionality' ),
				'name'              => 'issue_year',
				'aria-label'        => '',
				'type'              => 'date_picker',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'display_format'    => 'Y',
				'return_format'     => 'm/d/Y',
				'first_day'         => 1,
			),
			array(
				'key'               => 'field_issue_volume',
				'label'             => __( 'Issue Volume', 'site-functionality' ),
				'name'              => 'issue_volume',
				'aria-label'        => '',
				'type'              => 'text',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'maxlength'         => '',
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
			),
			array(
				'key'               => 'field_issue_volume_number',
				'label'             => __( 'Issue Volume Number', 'site-functionality' ),
				'name'              => 'issue_volume_number',
				'aria-label'        => '',
				'type'              => 'number',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'min'               => '',
				'max'               => '',
				'placeholder'       => '',
				'step'              => '',
				'prepend'           => '',
				'append'            => '',
			),
			array(
				'key'               => 'field_pages',
				'label'             => __( 'Pages', 'site-functionality' ),
				'name'              => 'pages',
				'aria-label'        => '',
				'type'              => 'number',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => '',
					'id'    => '',
				),
				'default_value'     => '',
				'min'               => '',
				'max'               => '',
				'placeholder'       => '',
				'step'              => '',
				'prepend'           => '',
				'append'            => '',
			),
		);

		$args = array(
			'key'                   => 'group_issue_details',
			'title'                 => __( 'Issue Details', 'site-functionality' ),
			'fields'                => $fields,
			'location'              => array(
				array(
					array(
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => self::POST_TYPE['id'],
					),
				),
			),
			'menu_order'            => 0,
			'position'              => 'side',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => '',
			'show_in_rest'          => 1,
		);

		acf_add_local_field_group( $args );
	}

	/**
	 * Format the field
	 * 
	 * @since 1.0.0.3
	 *
	 * @link https://www.advancedcustomfields.com/resources/wp-rest-api-integration/
	 *
	 * @param  mixed  $value_formatted
	 * @param  int    $post_id
	 * @param  array  $field
	 * @param  mixed  $value
	 * @param  string $format
	 * @return mixed $value_formatted
	 */
	public function format_date_as_year_rest( $value_formatted, $post_id, $field, $value, $format ) {
		$date_format = $field['display_format'];
		if ( $date_format && ! empty( $value ) ) {
			$value_formatted = date( $date_format, strtotime( $value ) );
		}
		return $value_formatted;
	}

	/**
	 * Register Meta
	 *
	 * @return void
	 */
	public function register_meta(): void {}

	/**
	 * Register custom query vars
	 *
	 * @link https://developer.wordpublication.org/reference/hooks/query_vars/
	 *
	 * @param array $vars The array of available query variables
	 */
	public function register_query_vars( $vars ): array {
		return $vars;
	}

}
