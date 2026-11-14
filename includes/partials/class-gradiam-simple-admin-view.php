<?php
/**
 * The simplified administration itself: short menu, start screen instead of the
 * dashboard, clean toolbar, no foreign notices, support contact in the footer.
 *
 * It is applied to users with the marker capability who are not administrators,
 * and to administrators who switched on "Simple view" in the toolbar (preview).
 * Menu hiding is for clarity only; access is controlled by the role's capabilities.
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
 * Simplified admin.
 */
class Gradiam_Simple_Admin_View {

	/**
	 * Start screen slug.
	 */
	const START_SLUG = 'gradiam-simple-admin';

	/**
	 * User meta: administrator preview switched on.
	 */
	const PREVIEW_META = 'gradiam_simple_admin_preview';

	/**
	 * Plugin version (assets).
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Top-level menu before filtering.
	 *
	 * @var array|null
	 */
	private static $full_menu = null;

	/**
	 * Initialize the class.
	 *
	 * @param string $version Plugin version.
	 */
	public function __construct( $version ) {
		$this->version = $version;
	}

	// -------------------------------------------------------------------------
	// Who gets the simple view
	// -------------------------------------------------------------------------

	/**
	 * Real client: has the marker capability and is not an administrator.
	 *
	 * @param WP_User|null $user User, current user by default.
	 * @return bool
	 */
	public static function is_client( $user = null ) {
		$user = $user instanceof WP_User ? $user : wp_get_current_user();

		return $user->exists() && $user->has_cap( Gradiam_Simple_Admin_Role::MARKER ) && ! $user->has_cap( 'manage_options' );
	}

	/**
	 * Administrator with "Simple view" switched on.
	 *
	 * @return bool
	 */
	public static function is_preview() {
		return current_user_can( 'manage_options' ) && (bool) get_user_meta( get_current_user_id(), self::PREVIEW_META, true );
	}

	/**
	 * Show the simplified admin to the current user.
	 *
	 * @return bool
	 */
	public static function is_simple() {
		return self::is_client() || self::is_preview();
	}

	/**
	 * Start screen URL.
	 *
	 * @return string
	 */
	public static function start_url() {
		return admin_url( 'admin.php?page=' . self::START_SLUG );
	}

	// -------------------------------------------------------------------------
	// Menu
	// -------------------------------------------------------------------------

	/**
	 * Register the start screen. Hooked to admin_menu (early).
	 */
	public function add_start_page() {
		add_menu_page(
			__( 'Start', 'gradiam-simple-admin' ),
			__( 'Start', 'gradiam-simple-admin' ),
			'read',
			self::START_SLUG,
			array( $this, 'render_start' ),
			'dashicons-screenoptions',
			1
		);
	}

	/**
	 * Remember the full menu, then shorten it for the simple view.
	 * Hooked to admin_menu with a very late priority (after all plugins).
	 */
	public function filter_menu() {
		global $menu;

		self::$full_menu = is_array( $menu ) ? $menu : array();

		if ( ! self::is_simple() ) {
			// Others keep the dashboard; the start screen stays reachable by URL.
			remove_menu_page( self::START_SLUG );
			return;
		}

		$allowed = Gradiam_Simple_Admin_Settings::allowed_menu();

		foreach ( (array) $menu as $position => $item ) {
			$slug = isset( $item[2] ) ? (string) $item[2] : '';
			if ( ! in_array( $slug, $allowed, true ) ) {
				unset( $menu[ $position ] );
			}
		}
	}

	/**
	 * Parsed top-level menu (before filtering), for the settings screen and the tiles.
	 *
	 * @return array<int,array{slug:string,title:string,cap:string,url:string,dashicon:string,count:int}>
	 */
	public static function full_menu() {
		$items = array();
		$menu  = (array) self::$full_menu;

		// Same order as the rendered menu (WordPress sorts it by position after admin_menu).
		uksort( $menu, 'strnatcasecmp' );

		foreach ( $menu as $item ) {
			if ( empty( $item[2] ) || ( isset( $item[4] ) && false !== strpos( (string) $item[4], 'wp-menu-separator' ) ) ) {
				continue;
			}

			$raw   = (string) $item[0];
			$count = 0;
			if ( preg_match( '/class="[^"]*(?:awaiting-mod|update-plugins)[^"]*"[^>]*>\D*(\d+)/', $raw, $m ) ) {
				$count = (int) $m[1];
			}

			// Title without the count badge.
			$title = trim( wp_strip_all_tags( preg_replace( '/<span.*$/s', '', $raw ) ) );
			if ( '' === $title ) {
				continue;
			}

			$slug     = (string) $item[2];
			$icon     = isset( $item[6] ) ? (string) $item[6] : '';
			$dashicon = 0 === strpos( $icon, 'dashicons-' ) ? $icon : 'dashicons-admin-generic';

			// Core screens use built-in icons through their CSS id.
			$core_icons = array(
				'index.php'               => 'dashicons-dashboard',
				'edit.php'                => 'dashicons-admin-post',
				'upload.php'              => 'dashicons-admin-media',
				'edit.php?post_type=page' => 'dashicons-admin-page',
				'edit-comments.php'       => 'dashicons-admin-comments',
				'themes.php'              => 'dashicons-admin-appearance',
				'plugins.php'             => 'dashicons-admin-plugins',
				'users.php'               => 'dashicons-admin-users',
				'profile.php'             => 'dashicons-admin-users',
				'tools.php'               => 'dashicons-admin-tools',
				'options-general.php'     => 'dashicons-admin-settings',
			);
			if ( isset( $core_icons[ $slug ] ) ) {
				$dashicon = $core_icons[ $slug ];
			}

			$is_page = false === strpos( $slug, '.php' ) || (bool) get_plugin_page_hook( $slug, 'admin.php' );
			$url     = $is_page ? admin_url( 'admin.php?page=' . $slug ) : admin_url( $slug );

			$items[] = array(
				'slug'     => $slug,
				'title'    => $title,
				'cap'      => (string) $item[1],
				'url'      => $url,
				'dashicon' => $dashicon,
				'count'    => $count,
			);
		}

		return $items;
	}

	/**
	 * A client who opens a hidden screen by URL is sent to the start screen.
	 * Only for real clients (an administrator in preview can still go anywhere).
	 * Hooked to current_screen.
	 */
	public function guard_screens() {
		if ( ! self::is_client() || wp_doing_ajax() ) {
			return;
		}

		$parent = $this->screen_menu_slug();
		if ( '' === $parent ) {
			return;
		}

		$known = wp_list_pluck( self::full_menu(), 'slug' );
		if ( in_array( $parent, $known, true ) && ! in_array( $parent, Gradiam_Simple_Admin_Settings::allowed_menu(), true ) ) {
			wp_safe_redirect( self::start_url() );
			exit;
		}
	}

	/**
	 * Top-level menu slug the current screen belongs to.
	 *
	 * @return string
	 */
	private function screen_menu_slug() {
		global $pagenow;

		$screen = get_current_screen();

		// Lists, editors and taxonomies of a post type belong to its menu item.
		if ( $screen && $screen->post_type && in_array( $screen->base, array( 'edit', 'post', 'edit-tags', 'term' ), true ) ) {
			return 'post' === $screen->post_type ? 'edit.php' : 'edit.php?post_type=' . $screen->post_type;
		}

		$parent = (string) get_admin_page_parent();

		return '' !== $parent ? $parent : (string) $pagenow;
	}

	/**
	 * Dashboard → start screen. Hooked to load-index.php.
	 */
	public function redirect_dashboard() {
		if ( self::is_simple() ) {
			wp_safe_redirect( self::start_url() );
			exit;
		}
	}

	/**
	 * After login, clients land on the start screen. Hooked to login_redirect.
	 *
	 * @param string           $redirect_to Destination.
	 * @param string           $requested   Requested destination.
	 * @param WP_User|WP_Error $user        Logged-in user.
	 * @return string
	 */
	public function login_redirect( $redirect_to, $requested, $user ) {
		if ( ! $user instanceof WP_User || ! self::is_client( $user ) ) {
			return $redirect_to;
		}

		if ( '' === (string) $requested || untrailingslashit( $requested ) === untrailingslashit( admin_url() ) ) {
			return self::start_url();
		}

		return $redirect_to;
	}

	// -------------------------------------------------------------------------
	// Start screen
	// -------------------------------------------------------------------------

	/**
	 * Short descriptions of known screens on the tiles.
	 *
	 * @return array<string,string>
	 */
	private function descriptions() {
		$descriptions = array(
			'gradiam-booking'          => __( 'New bookings, payment details, confirmations', 'gradiam-simple-admin' ),
			'chata-filipov-settings'   => __( 'Contact, prices, photos', 'gradiam-simple-admin' ),
			'edit.php?post_type=page'  => __( 'Texts of the pages, including the home page', 'gradiam-simple-admin' ),
			'edit.php?post_type=vylet' => __( 'Trip tips shown on the home page', 'gradiam-simple-admin' ),
			'edit.php'                 => __( 'News and articles', 'gradiam-simple-admin' ),
			'upload.php'               => __( 'Photos and files', 'gradiam-simple-admin' ),
			'profile.php'              => __( 'Your name, e-mail and password', 'gradiam-simple-admin' ),
			'users.php'                => __( 'Your name, e-mail and password', 'gradiam-simple-admin' ),
			'mlang'                    => __( 'Languages and translations', 'gradiam-simple-admin' ),
		);

		/**
		 * Descriptions on the start screen tiles, keyed by menu slug.
		 *
		 * @param array<string,string> $descriptions Descriptions.
		 */
		return (array) apply_filters( 'gradiam_simple_admin_tile_descriptions', $descriptions );
	}

	/**
	 * Start screen.
	 */
	public function render_start() {
		$user     = wp_get_current_user();
		$settings = Gradiam_Simple_Admin_Settings::all();
		$allowed  = Gradiam_Simple_Admin_Settings::allowed_menu();
		$texts    = $this->descriptions();
		$tiles    = array();

		foreach ( self::full_menu() as $item ) {
			if ( self::START_SLUG === $item['slug'] || ! in_array( $item['slug'], $allowed, true ) || ! current_user_can( $item['cap'] ) ) {
				continue;
			}
			$item['text']  = isset( $texts[ $item['slug'] ] ) ? $texts[ $item['slug'] ] : '';
			$item['index'] = count( $tiles );
			$tiles[]       = $item;
		}

		// Menu order, but items with something waiting (e.g. new bookings) first.
		usort(
			$tiles,
			function ( $a, $b ) {
				$waiting = ( $b['count'] > 0 ) <=> ( $a['count'] > 0 );

				return 0 !== $waiting ? $waiting : $a['index'] <=> $b['index'];
			}
		);
		?>
		<div class="wrap gsa-start">
			<h1>
				<?php
				/* translators: %s: first name or display name. */
				printf( esc_html__( 'Hello, %s', 'gradiam-simple-admin' ), esc_html( $user->first_name ? $user->first_name : $user->display_name ) );
				?>
			</h1>
			<p class="gsa-start__lead">
				<?php echo $settings['welcome'] ? nl2br( esc_html( $settings['welcome'] ) ) : esc_html__( 'What would you like to do?', 'gradiam-simple-admin' ); ?>
			</p>

			<?php if ( self::is_preview() ) : ?>
				<p class="gsa-start__preview"><?php esc_html_e( 'Preview: this is what the client sees. Switch it off with "Simple view" in the toolbar.', 'gradiam-simple-admin' ); ?></p>
			<?php endif; ?>

			<ul class="gsa-tiles">
				<?php foreach ( $tiles as $tile ) : ?>
					<li>
						<a class="gsa-tile<?php echo $tile['count'] ? ' gsa-tile--alert' : ''; ?>" href="<?php echo esc_url( $tile['url'] ); ?>">
							<span class="gsa-tile__icon dashicons <?php echo esc_attr( $tile['dashicon'] ); ?>" aria-hidden="true"></span>
							<span class="gsa-tile__title"><?php echo esc_html( $tile['title'] ); ?></span>
							<?php if ( $tile['count'] ) : ?>
								<span class="gsa-tile__count">
									<?php
									/* translators: %d: number of items waiting. */
									echo esc_html( sprintf( _n( '%d waiting', '%d waiting', $tile['count'], 'gradiam-simple-admin' ), $tile['count'] ) );
									?>
								</span>
							<?php endif; ?>
							<?php if ( $tile['text'] ) : ?>
								<span class="gsa-tile__text"><?php echo esc_html( $tile['text'] ); ?></span>
							<?php endif; ?>
						</a>
					</li>
				<?php endforeach; ?>
				<li>
					<a class="gsa-tile gsa-tile--site" href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener">
						<span class="gsa-tile__icon dashicons dashicons-external" aria-hidden="true"></span>
						<span class="gsa-tile__title"><?php esc_html_e( 'View the website', 'gradiam-simple-admin' ); ?></span>
						<span class="gsa-tile__text"><?php esc_html_e( 'Opens in a new tab', 'gradiam-simple-admin' ); ?></span>
					</a>
				</li>
			</ul>

			<?php $this->render_support( 'gsa-support' ); ?>
		</div>
		<?php
	}

	/**
	 * Support contact box.
	 *
	 * @param string $css_class CSS class.
	 */
	private function render_support( $css_class ) {
		$settings = Gradiam_Simple_Admin_Settings::all();

		if ( ! $settings['support_email'] && ! $settings['support_phone'] ) {
			return;
		}
		?>
		<div class="<?php echo esc_attr( $css_class ); ?>">
			<h2><?php esc_html_e( 'Need help?', 'gradiam-simple-admin' ); ?></h2>
			<p>
				<?php
				/* translators: %s: name of the support company or person. */
				printf( esc_html__( 'The website is looked after by %s.', 'gradiam-simple-admin' ), '<strong>' . esc_html( $settings['support_name'] ) . '</strong>' );
				?>
				<?php if ( $settings['support_email'] ) : ?>
					<br><a href="<?php echo esc_url( 'mailto:' . $settings['support_email'] ); ?>"><?php echo esc_html( $settings['support_email'] ); ?></a>
				<?php endif; ?>
				<?php if ( $settings['support_phone'] ) : ?>
					<br><a href="<?php echo esc_url( 'tel:' . preg_replace( '/[^\d+]/', '', $settings['support_phone'] ) ); ?>"><?php echo esc_html( $settings['support_phone'] ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Toolbar, notices, footer, screens
	// -------------------------------------------------------------------------

	/**
	 * Clean toolbar for the simple view; "Simple view" switch for administrators.
	 * Hooked to admin_bar_menu (late).
	 *
	 * @param WP_Admin_Bar $bar Toolbar.
	 */
	public function admin_bar( $bar ) {
		if ( current_user_can( 'manage_options' ) ) {
			$on = self::is_preview();
			$bar->add_node(
				array(
					'id'    => 'gsa-preview',
					'title' => '<span class="ab-icon dashicons dashicons-visibility" aria-hidden="true"></span>' . esc_html( $on ? __( 'Simple view: on', 'gradiam-simple-admin' ) : __( 'Simple view', 'gradiam-simple-admin' ) ),
					'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=gradiam_simple_admin_preview' ), 'gradiam_simple_admin_preview' ),
					'meta'  => array(
						'class' => $on ? 'gsa-preview-on' : '',
						'title' => __( 'Show the administration as the client sees it', 'gradiam-simple-admin' ),
					),
				)
			);
		}

		if ( ! self::is_simple() ) {
			return;
		}

		/**
		 * Toolbar nodes removed in the simple view.
		 *
		 * @param string[] $nodes Node IDs.
		 */
		$remove = (array) apply_filters(
			'gradiam_simple_admin_toolbar_remove',
			array( 'wp-logo', 'comments', 'new-content', 'updates', 'customize', 'search', 'themes', 'widgets', 'menus', 'dashboard', 'appearance', 'command-palette' )
		);

		foreach ( $remove as $id ) {
			$bar->remove_node( $id );
		}

		if ( is_admin() ) {
			$bar->add_node(
				array(
					'parent' => 'site-name',
					'id'     => 'gsa-start',
					'title'  => __( 'Start', 'gradiam-simple-admin' ),
					'href'   => self::start_url(),
				)
			);
		}
	}

	/**
	 * Switch the administrator preview. Hooked to admin_post_gradiam_simple_admin_preview.
	 */
	public function toggle_preview() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'gradiam-simple-admin' ), 403 );
		}
		check_admin_referer( 'gradiam_simple_admin_preview' );

		$user_id = get_current_user_id();
		$on      = ! get_user_meta( $user_id, self::PREVIEW_META, true );

		if ( $on ) {
			update_user_meta( $user_id, self::PREVIEW_META, 1 );
			wp_safe_redirect( self::start_url() );
		} else {
			delete_user_meta( $user_id, self::PREVIEW_META );
			wp_safe_redirect( admin_url() );
		}
		exit;
	}

	/**
	 * Remove notices from WordPress core and other plugins (updates, reviews, ads)
	 * in the simple view. Notices of SCF/ACF (e.g. "Options updated"), Polylang
	 * and our own code stay. Hooked to in_admin_header, which runs just before the notices.
	 */
	public function filter_notices() {
		global $wp_filter;

		if ( ! self::is_simple() ) {
			return;
		}

		/**
		 * Regular expression for callbacks (function or class name) whose notices stay.
		 *
		 * @param string $pattern Pattern.
		 */
		$keep = (string) apply_filters( 'gradiam_simple_admin_keep_notices', '/^(acf|scf|gradiam|chata_filipov|pll|polylang)/i' );

		foreach ( array( 'admin_notices', 'all_admin_notices', 'user_admin_notices', 'network_admin_notices' ) as $hook ) {
			if ( empty( $wp_filter[ $hook ] ) || ! $wp_filter[ $hook ] instanceof WP_Hook ) {
				continue;
			}

			foreach ( $wp_filter[ $hook ]->callbacks as $priority => $callbacks ) {
				foreach ( $callbacks as $callback ) {
					$name = $this->callback_name( $callback['function'] );
					if ( ! preg_match( $keep, $name ) ) {
						remove_action( $hook, $callback['function'], $priority );
					}
				}
			}
		}
	}

	/**
	 * Function or class name of a callback.
	 *
	 * @param mixed $callback Callback.
	 * @return string
	 */
	private function callback_name( $callback ) {
		if ( is_string( $callback ) ) {
			return $callback;
		}
		if ( is_array( $callback ) && isset( $callback[0] ) ) {
			return is_object( $callback[0] ) ? get_class( $callback[0] ) : (string) $callback[0];
		}

		return 'closure';
	}

	/**
	 * Support contact instead of "Thank you for creating with WordPress".
	 * Hooked to admin_footer_text.
	 *
	 * @param string $text Footer text.
	 * @return string
	 */
	public function footer_text( $text ) {
		if ( ! self::is_simple() ) {
			return $text;
		}

		$settings = Gradiam_Simple_Admin_Settings::all();
		$parts    = array_filter( array( $settings['support_name'], $settings['support_email'], $settings['support_phone'] ) );

		if ( ! $parts ) {
			return '';
		}

		/* translators: %s: support contact. */
		return esc_html( sprintf( __( 'Website support: %s', 'gradiam-simple-admin' ), implode( ' · ', $parts ) ) );
	}

	/**
	 * No WordPress version in the footer. Hooked to update_footer (late).
	 *
	 * @param string $text Footer text.
	 * @return string
	 */
	public function footer_version( $text ) {
		return self::is_simple() ? '' : $text;
	}

	/**
	 * Body class for the styles. Hooked to admin_body_class.
	 *
	 * @param string $classes Classes.
	 * @return string
	 */
	public function body_class( $classes ) {
		return self::is_simple() ? $classes . ' gsa-simple' : $classes;
	}

	/**
	 * No "Screen options" in the simple view. Hooked to screen_options_show_screen.
	 *
	 * @param bool $show Show.
	 * @return bool
	 */
	public function screen_options( $show ) {
		return self::is_simple() ? false : $show;
	}

	/**
	 * Profile screen without the colour schemes and application passwords.
	 * Hooked to admin_init.
	 */
	public function simplify_profile() {
		if ( self::is_simple() ) {
			remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );
		}
	}

	/**
	 * Application passwords are a developer feature. Hooked to
	 * wp_is_application_passwords_available_for_user.
	 *
	 * @param bool    $available Available.
	 * @param WP_User $user      User.
	 * @return bool
	 */
	public function application_passwords( $available, $user ) {
		return self::is_client( $user ) ? false : $available;
	}

	/**
	 * Block editor without the code editor and the Openverse photo library.
	 * Hooked to block_editor_settings_all.
	 *
	 * @param array $settings Editor settings.
	 * @return array
	 */
	public function editor_settings( $settings ) {
		if ( self::is_client() ) {
			$settings['codeEditingEnabled']           = false;
			$settings['enableOpenverseMediaCategory'] = false;
		}

		return $settings;
	}

	/**
	 * No block directory and remote patterns for clients (they offer
	 * installing plugins and add patterns that do not fit the theme). Hooked to init.
	 */
	public function editor_extras() {
		if ( ! self::is_client() ) {
			return;
		}

		remove_action( 'enqueue_block_editor_assets', 'wp_enqueue_editor_block_directory_assets' );
		add_filter( 'should_load_remote_block_patterns', '__return_false' );
	}

	/**
	 * Styles. Hooked to admin_enqueue_scripts.
	 *
	 * @param string $hook_suffix Current screen.
	 */
	public function enqueue( $hook_suffix ) {
		$own = false !== strpos( $hook_suffix, self::START_SLUG );

		if ( $own || self::is_simple() ) {
			$file = GRADIAM_SIMPLE_ADMIN_DIR . 'admin/css/admin.css';
			wp_enqueue_style( 'gradiam-simple-admin', GRADIAM_SIMPLE_ADMIN_URL . 'admin/css/admin.css', array(), file_exists( $file ) ? (string) filemtime( $file ) : $this->version );
		}
	}

	/**
	 * Toolbar styles (switch highlight) on the front end and in the admin.
	 * Hooked to admin_head and wp_head.
	 */
	public function toolbar_style() {
		if ( ! is_admin_bar_showing() || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		echo '<style>#wpadminbar .gsa-preview-on > .ab-item{background:#d63638;color:#fff}#wpadminbar .gsa-preview-on > .ab-item .ab-icon:before{color:#fff}#wpadminbar #wp-admin-bar-gsa-preview .ab-icon{top:2px}</style>' . "\n";
	}
}
