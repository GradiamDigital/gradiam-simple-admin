<?php
/**
 * Plugin Name:       Gradiam Simple Admin
 * Plugin URI:        https://gradiamdigital.cz
 * Description:       Simplified administration for clients: "Site manager" role, a short menu, a start screen with tiles, no update notices or technical screens.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            GradiamDigital
 * Author URI:        https://gradiamdigital.cz
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gradiam-simple-admin
 * Domain Path:       /languages
 *
 * @package Gradiam_Simple_Admin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current plugin version (SemVer). Keep in sync with "Version" above.
 */
define( 'GRADIAM_SIMPLE_ADMIN_VERSION', '1.0.0' );

define( 'GRADIAM_SIMPLE_ADMIN_FILE', __FILE__ );
define( 'GRADIAM_SIMPLE_ADMIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GRADIAM_SIMPLE_ADMIN_URL', plugin_dir_url( __FILE__ ) );

require_once GRADIAM_SIMPLE_ADMIN_DIR . 'includes/partials/class-gradiam-simple-admin-role.php';

register_activation_hook( __FILE__, array( 'Gradiam_Simple_Admin_Role', 'install' ) );

require_once GRADIAM_SIMPLE_ADMIN_DIR . 'includes/class-gradiam-simple-admin.php';

/**
 * Begins execution of the plugin. Everything is registered through hooks.
 */
function gradiam_simple_admin_run() {
	$plugin = new Gradiam_Simple_Admin();
	$plugin->run();
}
gradiam_simple_admin_run();
