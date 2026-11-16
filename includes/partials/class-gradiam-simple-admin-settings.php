<?php
/**
 * Plugin settings: which menu items the client sees, support contact, welcome text.
 * Screen: Settings → Simple admin (administrators only).
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
 * Settings.
 */
class Gradiam_Simple_Admin_Settings {

	const OPTION    = 'gradiam_simple_admin_settings';
	const PAGE_SLUG = 'gradiam-simple-admin-settings';

	/**
	 * Default values.
	 *
	 * @return array
	 */
	public static function defaults() {
		/**
		 * Top-level menu slugs the client sees by default (the start screen is always shown).
		 *
		 * @param string[] $slugs Menu slugs.
		 */
		$menu = (array) apply_filters(
			'gradiam_simple_admin_default_menu',
			array(
				'gradiam-booking',
				'chata-filipov-settings',
				'edit.php?post_type=page',
				'edit.php?post_type=vylet',
				'upload.php',
				'profile.php',
			)
		);

		return array(
			'menu'          => array_values( array_map( 'strval', $menu ) ),
			'welcome'       => '',
			'support_name'  => 'GradiamDigital',
			'support_email' => '',
			'support_phone' => '',
		);
	}

	/**
	 * All settings merged with the defaults.
	 *
	 * @return array
	 */
	public static function all() {
		$saved = get_option( self::OPTION, array() );

		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * One setting.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();

		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	/**
	 * Menu slugs visible for the client, always including the start screen.
	 *
	 * @return string[]
	 */
	public static function allowed_menu() {
		$menu   = (array) self::get( 'menu' );
		$menu[] = Gradiam_Simple_Admin_View::START_SLUG;

		/**
		 * Top-level menu slugs visible in the simplified admin.
		 *
		 * @param string[] $menu Menu slugs.
		 */
		return array_values( array_unique( (array) apply_filters( 'gradiam_simple_admin_menu', $menu ) ) );
	}

	/**
	 * Register the screen under Settings. Hooked to admin_menu.
	 */
	public function add_page() {
		add_options_page(
			__( 'Simple admin', 'gradiam-simple-admin' ),
			__( 'Simple admin', 'gradiam-simple-admin' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Settings screen.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = self::all();
		$items    = Gradiam_Simple_Admin_View::full_menu();

		// Users without "list_users" (the client) have "Profile" instead of "Users".
		if ( ! in_array( 'profile.php', wp_list_pluck( $items, 'slug' ), true ) ) {
			$items[] = array(
				'slug'     => 'profile.php',
				'title'    => __( 'Profile (users who cannot manage users)', 'gradiam-simple-admin' ),
				'dashicon' => 'dashicons-admin-users',
			);
		}
		$role  = get_role( Gradiam_Simple_Admin_Role::ROLE );
		$users = count_users();
		$count = isset( $users['avail_roles'][ Gradiam_Simple_Admin_Role::ROLE ] ) ? (int) $users['avail_roles'][ Gradiam_Simple_Admin_Role::ROLE ] : 0;
		?>
		<div class="wrap gsa-settings">
			<h1><?php esc_html_e( 'Simple admin', 'gradiam-simple-admin' ); ?></h1>

			<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display only. ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved. The role was rebuilt.', 'gradiam-simple-admin' ); ?></p></div>
			<?php endif; ?>

			<p>
				<?php
				printf(
					/* translators: 1: role name, 2: number of users. */
					esc_html__( 'Users with the role "%1$s" get the simplified administration. Users with this role now: %2$d.', 'gradiam-simple-admin' ),
					esc_html( _x( 'Site manager', 'User role', 'gradiam-simple-admin' ) ),
					(int) $count
				);
				?>
				<a href="<?php echo esc_url( admin_url( 'user-new.php' ) ); ?>"><?php esc_html_e( 'Add a user', 'gradiam-simple-admin' ); ?></a>
				<?php if ( ! $role ) : ?>
					<strong><?php esc_html_e( 'The role does not exist yet; saving the settings creates it.', 'gradiam-simple-admin' ); ?></strong>
				<?php endif; ?>
			</p>
			<p><?php esc_html_e( 'To see the administration as the client sees it, use "Simple view" in the toolbar.', 'gradiam-simple-admin' ); ?></p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="gradiam_simple_admin_save">
				<?php wp_nonce_field( 'gradiam_simple_admin_save' ); ?>

				<h2><?php esc_html_e( 'Menu items for the client', 'gradiam-simple-admin' ); ?></h2>
				<p class="description"><?php esc_html_e( 'The start screen is always shown. An item appears only if the role also has the capability for it.', 'gradiam-simple-admin' ); ?></p>
				<fieldset class="gsa-menu-list">
					<?php foreach ( $items as $item ) : ?>
						<?php
						if ( Gradiam_Simple_Admin_View::START_SLUG === $item['slug'] ) {
							continue;
						}
						?>
						<label>
							<input type="checkbox" name="menu[]" value="<?php echo esc_attr( $item['slug'] ); ?>" <?php checked( in_array( $item['slug'], (array) $settings['menu'], true ) ); ?>>
							<span class="dashicons <?php echo esc_attr( $item['dashicon'] ); ?>" aria-hidden="true"></span>
							<?php echo esc_html( $item['title'] ); ?>
							<code><?php echo esc_html( $item['slug'] ); ?></code>
						</label>
					<?php endforeach; ?>
					<?php
					// Keep saved slugs that are not in the current menu (e.g. a plugin that is off right now).
					$known = wp_list_pluck( $items, 'slug' );
					foreach ( (array) $settings['menu'] as $slug ) :
						if ( in_array( $slug, $known, true ) ) {
							continue;
						}
						?>
						<label class="gsa-missing">
							<input type="checkbox" name="menu[]" value="<?php echo esc_attr( $slug ); ?>" checked>
							<?php esc_html_e( 'Not in the menu now:', 'gradiam-simple-admin' ); ?> <code><?php echo esc_html( $slug ); ?></code>
						</label>
					<?php endforeach; ?>
				</fieldset>

				<h2><?php esc_html_e( 'Start screen', 'gradiam-simple-admin' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="gsa-welcome"><?php esc_html_e( 'Welcome text', 'gradiam-simple-admin' ); ?></label></th>
						<td><textarea id="gsa-welcome" name="welcome" rows="3" class="large-text"><?php echo esc_textarea( $settings['welcome'] ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Optional. Shown under the greeting.', 'gradiam-simple-admin' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><label for="gsa-support-name"><?php esc_html_e( 'Support – name', 'gradiam-simple-admin' ); ?></label></th>
						<td><input id="gsa-support-name" name="support_name" class="regular-text" value="<?php echo esc_attr( $settings['support_name'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gsa-support-email"><?php esc_html_e( 'Support – e-mail', 'gradiam-simple-admin' ); ?></label></th>
						<td><input id="gsa-support-email" type="email" name="support_email" class="regular-text" value="<?php echo esc_attr( $settings['support_email'] ); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="gsa-support-phone"><?php esc_html_e( 'Support – phone', 'gradiam-simple-admin' ); ?></label></th>
						<td><input id="gsa-support-phone" name="support_phone" class="regular-text" value="<?php echo esc_attr( $settings['support_phone'] ); ?>">
						<p class="description"><?php esc_html_e( 'Shown on the start screen and in the footer of the administration.', 'gradiam-simple-admin' ); ?></p></td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save the settings. Hooked to admin_post_gradiam_simple_admin_save.
	 */
	public function save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'gradiam-simple-admin' ), 403 );
		}
		check_admin_referer( 'gradiam_simple_admin_save' );

		$menu = isset( $_POST['menu'] ) ? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['menu'] ) ) : array();

		$settings = array(
			'menu'          => array_values( array_filter( $menu ) ),
			'welcome'       => isset( $_POST['welcome'] ) ? sanitize_textarea_field( wp_unslash( $_POST['welcome'] ) ) : '',
			'support_name'  => isset( $_POST['support_name'] ) ? sanitize_text_field( wp_unslash( $_POST['support_name'] ) ) : '',
			'support_email' => isset( $_POST['support_email'] ) ? sanitize_email( wp_unslash( $_POST['support_email'] ) ) : '',
			'support_phone' => isset( $_POST['support_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['support_phone'] ) ) : '',
		);

		update_option( self::OPTION, $settings, false );

		// Saving also rebuilds the role, so capability changes from code apply at once.
		Gradiam_Simple_Admin_Role::install();

		wp_safe_redirect( add_query_arg( 'updated', '1', admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ) );
		exit;
	}
}
