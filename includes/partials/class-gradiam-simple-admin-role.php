<?php
/**
 * The "Site manager" role and the plugin capabilities.
 *
 * The role can edit content (pages, posts and custom post types with the post
 * capability type, media), manage bookings and the site settings page, but it
 * cannot delete pages, touch plugins, themes, users, updates or WordPress settings.
 *
 * @link       https://gradiamdigital.cz
 * @since      1.0.0
 * @package    Gradiam_Simple_Admin
 * @subpackage Gradiam_Simple_Admin/includes/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role and capabilities.
 */
class Gradiam_Simple_Admin_Role {

	/**
	 * Role slug.
	 */
	const ROLE = 'gradiam_client';

	/**
	 * Marker capability: users who have it (and are not administrators) get the simplified admin.
	 */
	const MARKER = 'gradiam_simple_admin';

	/**
	 * Capability for the site's own settings page (e.g. "Chalet settings").
	 */
	const SITE_SETTINGS = 'gradiam_site_settings';

	/**
	 * Raise when caps() changes: the role is then rebuilt on the next request.
	 */
	const ROLE_VERSION = '1';

	/**
	 * Option that stores the role version present in the database.
	 */
	const VERSION_OPTION = 'gradiam_simple_admin_role_version';

	/**
	 * Capabilities of the role.
	 *
	 * @return array<string,bool>
	 */
	public static function caps() {
		$caps = array(
			'read'                   => true,
			'upload_files'           => true,

			// Posts and custom post types with capability_type "post" (e.g. Výlety).
			'edit_posts'             => true,
			'edit_others_posts'      => true,
			'edit_published_posts'   => true,
			'edit_private_posts'     => true,
			'read_private_posts'     => true,
			'publish_posts'          => true,
			'delete_posts'           => true,
			'delete_others_posts'    => true,
			'delete_published_posts' => true,
			'delete_private_posts'   => true,
			'manage_categories'      => true,

			// Pages: edit and publish, but not delete (the home and booking pages must stay).
			'edit_pages'             => true,
			'edit_others_pages'      => true,
			'edit_published_pages'   => true,
			'edit_private_pages'     => true,
			'read_private_pages'     => true,
			'publish_pages'          => true,

			// Plugin capabilities.
			self::MARKER             => true,
			self::SITE_SETTINGS      => true,
			'manage_gb_bookings'     => true, // Gradiam Booking.
		);

		/**
		 * Capabilities of the "Site manager" role. After changing them from code,
		 * re-save the plugin settings (or reactivate the plugin) to rebuild the role.
		 *
		 * @param array<string,bool> $caps Capabilities.
		 */
		return (array) apply_filters( 'gradiam_simple_admin_role_caps', $caps );
	}

	/**
	 * Create or rebuild the role. Activation hook; also called when the role version changes.
	 */
	public static function install() {
		remove_role( self::ROLE );
		add_role( self::ROLE, 'Site manager', self::caps() );
		update_option( self::VERSION_OPTION, self::ROLE_VERSION, true );
	}

	/**
	 * Rebuild the role after a plugin update that changed it. Hooked to init.
	 */
	public static function maybe_upgrade() {
		if ( get_option( self::VERSION_OPTION ) !== self::ROLE_VERSION || ! get_role( self::ROLE ) ) {
			self::install();
		}
	}

	/**
	 * Translated role name (the database keeps the English one). Hooked to init.
	 */
	public static function translate_name() {
		$roles = wp_roles();
		$name  = _x( 'Site manager', 'User role', 'gradiam-simple-admin' );

		if ( isset( $roles->roles[ self::ROLE ] ) ) {
			$roles->roles[ self::ROLE ]['name'] = $name;
			$roles->role_names[ self::ROLE ]    = $name;
		}
	}

	/**
	 * Administrators always may open the site settings page.
	 * Hooked to user_has_cap.
	 *
	 * @param array $allcaps Capabilities of the user.
	 * @return array
	 */
	public static function grant_admins( $allcaps ) {
		if ( ! empty( $allcaps['manage_options'] ) ) {
			$allcaps[ self::SITE_SETTINGS ] = true;
		}

		return $allcaps;
	}

	/**
	 * Remove the role; its users become subscribers so they keep an account
	 * but lose access to the content. Called from uninstall.php.
	 */
	public static function uninstall() {
		$users = get_users(
			array(
				'role'   => self::ROLE,
				'fields' => 'ID',
			)
		);

		foreach ( $users as $user_id ) {
			$user = new WP_User( (int) $user_id );
			$user->set_role( 'subscriber' );
		}

		remove_role( self::ROLE );
		delete_option( self::VERSION_OPTION );
	}
}
