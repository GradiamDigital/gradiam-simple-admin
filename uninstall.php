<?php
/**
 * Uninstall: remove the role (its users become subscribers), settings and preview flags.
 *
 * @package Gradiam_Simple_Admin
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/partials/class-gradiam-simple-admin-role.php';

Gradiam_Simple_Admin_Role::uninstall();

delete_option( 'gradiam_simple_admin_settings' );
delete_metadata( 'user', 0, 'gradiam_simple_admin_preview', '', true );
