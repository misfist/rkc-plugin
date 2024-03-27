<?php
/**
 * AdminSettings
 *
 * @since   1.0.0
 * @package Site_Functionality
 */
namespace Site_Functionality\App\Admin;

use Site_Functionality\Common\Abstracts\Base;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Admin_Settings extends Base {

	public $data = array(
		'option_name'  => 'business_info', // $id
		'option_group' => 'general', // $page
		'section'      => 'business_info',
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
		\add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Register Settings
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_setting/
	 * @see https://developer.wordpress.org/reference/functions/add_settings_field/
	 *
	 * @return void
	 */
	public function register_settings() : void {

		register_setting(
			$this->data['option_group'],
			$this->data['option_name'],
			array(
				'show_in_rest' => true,
			)
		);

		add_settings_section(
			$this->data['section'],
			esc_html__( 'Business Information', 'site-functionality' ),
			array( $this, 'render_section' ),
			$this->data['option_group'],
			array()
		);

		add_settings_field(
			'business_name',
			esc_html__( 'Name', 'site-functionality' ),
			array( $this, 'render_business_name_field' ),
			$this->data['option_group'],
			$this->data['section']
		);

		add_settings_field(
			'business_address',
			esc_html__( 'Address', 'site-functionality' ),
			array( $this, 'render_business_address_field' ),
			$this->data['option_group'],
			$this->data['section']
		);

	}

	public function render_section() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'site-functionality' ) );
		}

		do_settings_sections( $this->data['section'] );
	}

	/**
	 * Render field
	 *
	 * @return void
	 */
	public function render_business_name_field() : void {
		$options = get_option( $this->data['option_name'] );

		$value = isset( $options['business_name'] ) ? $options['business_name'] : '';

		echo '<input type="text" name="business_info[business_name]" class="regular-text business_name_field" placeholder="' . esc_attr__( '', 'site-functionality' ) . '" value="' . esc_attr( $value ) . '">';
		echo '<p class="description">' . __( 'Legal business name.', 'site-functionality' ) . '</p>';
	}

	/**
	 * Render field
	 *
	 * @return void
	 */
	public function render_business_address_field() : void {
		$options = get_option( $this->data['option_name'] );

		$value = isset( $options['business_address'] ) ? $options['business_address'] : '';

		echo '<textarea name="business_info[business_address]" class="regular-text business_address_field" placeholder="' . esc_attr__( '14938 Camden Ave, San Jose CA 95124', 'site-functionality' ) . '">' . $value . '</textarea>';
		echo '<p class="description">' . __( 'Legal business address.', 'site-functionality' ) . '</p>';
	}


}
