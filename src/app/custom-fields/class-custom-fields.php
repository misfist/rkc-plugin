<?php
/**
 * Content Post_Types
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Custom_Fields;

use Site_Functionality\Common\Abstracts\Base;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Custom_Fields extends Base {

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
		add_action( 'acf/init', array( $this, 'acf_settings' ) );
		add_action( 'acf/render_field_general_settings/type=number', array( $this, 'currency_field_setting' ) );
	}

	/**
	 * Add ACF settings
	 * 
	 * @link https://www.advancedcustomfields.com/resources/acf-settings/
	 *
	 * @return void
	 */
	public function acf_settings(): void {
		acf_update_setting( 'l10n_textdomain', 'site-functionality' );
	}

	/**
	 * Add field setting
	 * 
	 * @since 1.0.0.3
	 * 
	 * https://www.advancedcustomfields.com/resources/adding-custom-settings-fields/
	 *
	 * @param  array $field
	 * @return void
	 */
	function currency_field_setting( $field ) : void {
		$args = array(
			'label'        => __( 'Currency', 'site-functionality' ),
			'instructions' => '',
			'name'         => 'is_currency',
			'type'         => 'true_false',
			'ui'           => 1,
		);
		acf_render_field_setting( $field, $args, true );
	}
	
}
