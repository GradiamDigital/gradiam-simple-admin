<?php
/**
 * The core plugin class: loads the dependencies and registers all hooks.
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
 * Core plugin class.
 */
class Gradiam_Simple_Admin {

	/**
	 * Hook loader.
	 *
	 * @var Gradiam_Simple_Admin_Loader
	 */
	protected $loader;

	/**
	 * Plugin identifier.
	 *
	 * @var string
	 */
	protected $plugin_name = 'gradiam-simple-admin';

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	protected $version = GRADIAM_SIMPLE_ADMIN_VERSION;

	/**
	 * Load dependencies and define the hooks.
	 */
	public function __construct() {
		$this->load_dependencies();
		$this->set_locale();
		$this->define_role_hooks();
		$this->define_settings_hooks();
		$this->define_view_hooks();
		$this->define_integration_hooks();
	}

	/**
	 * Load the required files.
	 */
	private function load_dependencies() {
		require_once GRADIAM_SIMPLE_ADMIN_DIR . 'includes/class-gradiam-simple-admin-loader.php';
		require_once GRADIAM_SIMPLE_ADMIN_DIR . 'includes/class-gradiam-simple-admin-i18n.php';
		require_once GRADIAM_SIMPLE_ADMIN_DIR . 'includes/partials/class-gradiam-simple-admin-role.php';
		require_once GRADIAM_SIMPLE_ADMIN_DIR . 'includes/partials/class-gradiam-simple-admin-settings.php';
		require_once GRADIAM_SIMPLE_ADMIN_DIR . 'includes/partials/class-gradiam-simple-admin-view.php';

		$this->loader = new Gradiam_Simple_Admin_Loader();
	}

	/**
	 * Text domain.
	 */
	private function set_locale() {
		$i18n = new Gradiam_Simple_Admin_I18n();
		$this->loader->add_action( 'init', $i18n, 'load_plugin_textdomain' );
	}

	/**
	 * Role: rebuild after updates, translated name, administrators' capabilities.
	 */
	private function define_role_hooks() {
		$this->loader->add_action( 'init', 'Gradiam_Simple_Admin_Role', 'maybe_upgrade' );
		$this->loader->add_action( 'init', 'Gradiam_Simple_Admin_Role', 'translate_name', 20 );
		$this->loader->add_filter( 'user_has_cap', 'Gradiam_Simple_Admin_Role', 'grant_admins' );
	}

	/**
	 * Settings screen.
	 */
	private function define_settings_hooks() {
		$settings = new Gradiam_Simple_Admin_Settings();

		$this->loader->add_action( 'admin_menu', $settings, 'add_page' );
		$this->loader->add_action( 'admin_post_gradiam_simple_admin_save', $settings, 'save' );
	}

	/**
	 * The simplified administration.
	 */
	private function define_view_hooks() {
		$view = new Gradiam_Simple_Admin_View( $this->version );

		$this->loader->add_action( 'admin_menu', $view, 'add_start_page', 5 );
		$this->loader->add_action( 'admin_menu', $view, 'filter_menu', PHP_INT_MAX );
		$this->loader->add_action( 'current_screen', $view, 'guard_screens' );
		$this->loader->add_action( 'load-index.php', $view, 'redirect_dashboard' );
		$this->loader->add_filter( 'login_redirect', $view, 'login_redirect', 20, 3 );

		$this->loader->add_action( 'admin_bar_menu', $view, 'admin_bar', 999 );
		$this->loader->add_action( 'admin_post_gradiam_simple_admin_preview', $view, 'toggle_preview' );
		$this->loader->add_action( 'admin_head', $view, 'toolbar_style' );
		$this->loader->add_action( 'wp_head', $view, 'toolbar_style' );

		$this->loader->add_action( 'in_admin_header', $view, 'filter_notices', PHP_INT_MAX );
		$this->loader->add_filter( 'admin_footer_text', $view, 'footer_text', 20 );
		$this->loader->add_filter( 'update_footer', $view, 'footer_version', 20 );
		$this->loader->add_filter( 'admin_body_class', $view, 'body_class' );
		$this->loader->add_filter( 'screen_options_show_screen', $view, 'screen_options' );
		$this->loader->add_action( 'admin_init', $view, 'simplify_profile' );
		$this->loader->add_filter( 'wp_is_application_passwords_available_for_user', $view, 'application_passwords', 10, 2 );

		$this->loader->add_filter( 'block_editor_settings_all', $view, 'editor_settings' );
		$this->loader->add_action( 'init', $view, 'editor_extras' );

		$this->loader->add_action( 'admin_enqueue_scripts', $view, 'enqueue' );
	}

	/**
	 * Other GradiamDigital code: the Chata Filipov theme opens its "Chalet settings"
	 * page to the capability given by this filter. Gradiam Booking needs no hook:
	 * the role has its capability manage_gb_bookings.
	 */
	private function define_integration_hooks() {
		$this->loader->add_filter( 'chata_filipov_options_capability', $this, 'site_settings_capability' );
	}

	/**
	 * Capability for the theme's settings page.
	 *
	 * @return string
	 */
	public function site_settings_capability() {
		return Gradiam_Simple_Admin_Role::SITE_SETTINGS;
	}

	/**
	 * Register all hooks with WordPress.
	 */
	public function run() {
		$this->loader->run();
	}
}
