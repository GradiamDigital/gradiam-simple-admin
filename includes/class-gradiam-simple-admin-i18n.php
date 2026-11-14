<?php
/**
 * Define the internationalization functionality.
 *
 * @link       https://gradiamdigital.cz
 * @since      1.0.0
 * @package    Gradiam_Simple_Admin
 * @subpackage Gradiam_Simple_Admin/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Loads the plugin text domain.
 */
class Gradiam_Simple_Admin_I18n {

	/**
	 * Load the plugin text domain. Hooked to init.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'gradiam-simple-admin', false, dirname( plugin_basename( GRADIAM_SIMPLE_ADMIN_FILE ) ) . '/languages' );
	}
}
