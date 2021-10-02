<?php
/**
 * Admin utility Class.
 *
 * Handles Admin functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Admin utility Class.
 *
 * This class provides Admin functionality.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_Admin {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Multisite Admin object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $multisite The Multisite Admin object.
	 */
	public $multisite;

	/**
	 * The installed version of the plugin.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $plugin_version The plugin version.
	 */
	public $plugin_version;

	/**
	 * Settings data.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $settings The plugin settings data.
	 */
	public $settings = [];

	/**
	 * Upgrade flag.
	 *
	 * @since 0.4
	 * @access public
	 * @var bool $is_upgrade An upgrade flag.
	 */
	public $is_upgrade = false;

	/**
	 * Warning flag.
	 *
	 * @since 0.4
	 * @access public
	 * @var bool $is_upgrade An upgrade flag.
	 */
	public $has_warning = false;

	/**
	 * Parent page reference.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $parent_page The reference to the parent page.
	 */
	public $parent_page;

	/**
	 * Parent page slug.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $parent_page_slug The slug of the parent page.
	 */
	public $parent_page_slug = 'cwps_parent';

	/**
	 * Settings Page reference.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $settings_page The reference to the Settings Page.
	 */
	public $settings_page;

	/**
	 * Settings Page slug.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $settings_page_slug The slug of the Settings Page.
	 */
	public $settings_page_slug = 'cwps_settings';



	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store reference.
		$this->plugin = $parent;

		// Boot when plugin is loaded.
		add_action( 'civicrm_wp_profile_sync_init', [ $this, 'initialise' ] );

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Init settings.
		$this->initialise_settings();

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/admin/loaded' );

	}



	/**
	 * Initialise settings.
	 *
	 * @since 0.4
	 */
	public function initialise_settings() {

		// Assign installed plugin version.
		$this->plugin_version = $this->option_get( 'cwps_version', false );

		// Do upgrade tasks.
		$this->upgrade_tasks();

		// Store version for later reference if there has been a change.
		if ( $this->plugin_version != CIVICRM_WP_PROFILE_SYNC_VERSION ) {
			$this->option_set( 'cwps_version', CIVICRM_WP_PROFILE_SYNC_VERSION );
		}

		// Store default settings if none exist.
		if ( ! $this->option_exists( 'cwps_settings' ) ) {
			$this->option_set( 'cwps_settings', $this->settings_get_defaults() );
		}

		// Load settings array.
		$this->settings = $this->option_get( 'cwps_settings', $this->settings );

		// Settings upgrade tasks.
		$this->upgrade_settings();

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include Multisite Admin class.
		if ( is_multisite() ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/admin/cwps-admin-multisite.php';
		}

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Maybe instantiate Multisite object.
		if ( is_multisite() ) {
			$this->multisite = new CiviCRM_WP_Profile_Sync_Admin_Multisite( $this );
		}

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Maybe show a warning if Settings need updating.
		add_action( 'admin_notices', [ $this, 'upgrade_warning' ] );

		// Add Settings Page to Settings menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 20 );

		// Add our meta boxes.
		add_action( 'add_meta_boxes', [ $this, 'meta_boxes_add' ], 11 );

	}



	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Remove the callbacks registered by this class.
		remove_action( 'admin_notices', [ $this, 'upgrade_warning' ] );
		remove_action( 'admin_menu', [ $this, 'admin_menu' ], 20 );
		remove_action( 'add_meta_boxes', [ $this, 'meta_boxes_add' ], 11 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Do stuff when an upgrade is required.
	 *
	 * @since 0.4
	 */
	public function upgrade_tasks() {

		// If this is a new install (or an upgrade from a version prior to 0.4).
		if ( $this->plugin_version === false ) {
		}

		// If this is an upgrade.
		if ( $this->plugin_version != CIVICRM_WP_PROFILE_SYNC_VERSION ) {
			$this->is_upgrade = true;
		}

		/*
		// For future upgrades, use something like the following.
		if ( version_compare( CIVICRM_WP_PROFILE_SYNC_VERSION, '0.4', '>=' ) ) {
			// Do something
		}
		*/

	}



	/**
	 * Show a warning when a settings upgrade is required.
	 *
	 * @since 0.4
	 */
	public function upgrade_warning() {

		/**
		 * Set access capability but allow overrides.
		 *
		 * @since 0.4
		 *
		 * @param string The default capability for access to Settings Page.
		 * @return string The modified capability for access to Settings Page.
		 */
		$capability = apply_filters( 'cwps/admin/page/settings/cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Get current screen.
		$screen = get_current_screen();

		// Bail if it's not what we expect.
		if ( ! ( $screen instanceof WP_Screen ) ) {
			return;
		}

		// Set website flag.
		$website_type_undefined = false;

		// Overwrite flag if the setting doesn't exist or has no value.
		if (
			! $this->setting_exists( 'user_profile_website_type' ) OR
			$this->setting_get( 'user_profile_website_type', 0 ) === 0
		) {
			$website_type_undefined = true;
		}

		// Set email flag.
		$email_sync_undefined = false;

		// Overwrite flag if the setting doesn't exist or has no value.
		if (
			! $this->setting_exists( 'user_profile_email_sync' ) OR
			$this->setting_get( 'user_profile_email_sync', 2 ) === 2
		) {
			$email_sync_undefined = true;
		}

		// Get our Settings Page screens.
		$settings_screens = $this->page_settings_screens_get();

		// Determine if we are on one of our Settings Pages.
		$is_settings_screen = in_array( $screen->id, $settings_screens );

		// Set message if we are on one of our Settings Pages.
		if ( $is_settings_screen === true ) {

			// If the website setting doesn't exist or has no value.
			if ( $website_type_undefined === true ) {

				echo '<div id="message" class="notice notice-warning">';
				echo '<p>' . __( 'CiviCRM Profile Sync needs to know which Website Type to sync.', 'civicrm-wp-profile-sync' ) . '</p>';
				echo '</div>';

			}

			// If the email setting doesn't exist or has no valid value.
			if ( $email_sync_undefined === true ) {

				echo '<div id="message" class="notice notice-warning">';
				echo '<p>' . __( 'CiviCRM Profile Sync needs to know how to sync the Primary Email.', 'civicrm-wp-profile-sync' ) . '</p>';
				echo '</div>';

			}

		}

		// If either setting has no valid value.
		if ( $website_type_undefined || $email_sync_undefined ) {

			// Set warning flag.
			$this->has_warning = true;

			// Show Admin Notice if not on Settings Page.
			$show_notice = false;
			if ( ! $is_settings_screen ) {
				$show_notice = true;
			}

			/**
			 * Filter whether to show the Admin Notice.
			 *
			 * @since 0.4
			 *
			 * @param bool $show_notice False by default. True if not on Settings Screen.
			 * @param bool $is_settings_screen True if on our Settings Page, or false otherwise.
			 * @param string $screen_id The ID of the current screen.
			 * @return bool $show_notice True if the Admin Notice should be shown, false otherwise.
			 */
			$show_notice = apply_filters( 'cwps/admin/notice/show', $show_notice, $is_settings_screen, $screen->id );

			// If we should show the notice.
			if ( $show_notice ) {

				// Get Settings Page URL for use in Admin Notices.
				$url = $this->page_settings_warning_url_get();

				// Show general "Call to Action".
				$message = sprintf(
					__( 'CiviCRM Profile Sync needs your attention. Please visit the %1$sSettings Page%2$s for details.', 'civicrm-wp-profile-sync' ),
					'<a href="' . $url . '">',
					'</a>'
				);

				// Show it.
				echo '<div id="message" class="notice notice-warning">';
				echo '<p>' . $message . '</p>';
				echo '</div>';

			}

		}

	}



	/**
	 * Do stuff when a settings upgrade is required.
	 *
	 * @since 0.4
	 */
	public function upgrade_settings() {

		// Don't save by default.
		$save = false;

		// "User Profile Website Type" setting may not exist.
		if ( ! $this->setting_exists( 'user_profile_website_type' ) ) {
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'user_profile_website_type', $settings['user_profile_website_type'] );
			$save = true;
		}

		// "User Email Sync" setting may not exist.
		if ( ! $this->setting_exists( 'user_profile_email_sync' ) ) {
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'user_profile_email_sync', $settings['user_profile_email_sync'] );
			$save = true;
		}

		// "User Nickname Sync" setting may not exist.
		if ( ! $this->setting_exists( 'user_profile_nickname_sync' ) ) {
			$settings = $this->settings_get_defaults();
			$this->setting_set( 'user_profile_nickname_sync', $settings['user_profile_nickname_sync'] );
			$save = true;
		}

		// Things to always check on upgrade.
		if ( $this->is_upgrade ) {
			// Add them here.
			//$save = true;
		}

		// Save settings if need be.
		if ( $save === true ) {
			$this->settings_save();
		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Add admin menu item(s) for this plugin.
	 *
	 * @since 0.4
	 */
	public function admin_menu() {

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add parent page to CiviCRM menu.
		$this->parent_page = add_submenu_page(
			'CiviCRM', // Parent slug.
			__( 'CiviCRM Profile Sync: Settings', 'civicrm-wp-profile-sync' ), // Page title.
			__( 'Profile Sync', 'civicrm-wp-profile-sync' ), // Menu title.
			'manage_options', // Required caps.
			$this->parent_page_slug, // Slug name.
			[ $this, 'page_settings' ], // Callback.
			10
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->parent_page, [ $this, 'settings_update_router' ] );

		// Add WordPress scripts and help text.
		add_action( 'admin_head-' . $this->parent_page, [ $this, 'admin_head' ], 50 );

		// Add scripts and styles.
		//add_action( 'admin_print_styles-' . $this->parent_page, [ $this, 'admin_css' ] );
		//add_action( 'admin_print_scripts-' . $this->parent_page, [ $this, 'admin_js' ] );

		// Add Settings Page.
		$this->settings_page = add_submenu_page(
			$this->parent_page_slug, // Parent slug.
			__( 'CiviCRM Profile Sync: Settings', 'civicrm-wp-profile-sync' ), // Page title.
			__( 'Settings', 'civicrm-wp-profile-sync' ), // Menu title.
			'manage_options', // Required caps.
			$this->settings_page_slug, // Slug name.
			[ $this, 'page_settings' ] // Callback.
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->settings_page, [ $this, 'settings_update_router' ] );

		// Add WordPress scripts and help text.
		add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_head' ], 50 );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->settings_page, [ $this, 'admin_menu_highlight' ], 50 );

		// Add scripts and styles
		//add_action( 'admin_print_styles-' . $this->settings_page, [ $this, 'admin_css' ] );
		//add_action( 'admin_print_scripts-' . $this->settings_page, [ $this, 'admin_js' ] );

	}



	/**
	 * Highlight the plugin's parent menu item.
	 *
	 * Regardless of the actual admin screen we are on, we need the parent menu
	 * item to be highlighted so that the appropriate menu is open by default
	 * when the subpage is viewed.
	 *
	 * @since 0.4
	 *
	 * @global string $plugin_page The current plugin page.
	 * @global string $submenu_file The current submenu.
	 */
	public function admin_menu_highlight() {

		global $plugin_page, $submenu_file;

		// Define subpages.
		$subpages = [
		 	$this->settings_page_slug,
		];

		/**
		 * Filter the list of subpages.
		 *
		 * @since 0.4
		 *
		 * @param array $subpages The existing list of subpages.
		 * @return array $subpages The modified list of subpages.
		 */
		$subpages = apply_filters( 'cwps/admin/settings/subpages', $subpages );

		// This tweaks the Settings subnav menu to show only one menu item.
		if ( in_array( $plugin_page, $subpages ) ) {
			$plugin_page = $this->parent_page_slug;
			$submenu_file = $this->parent_page_slug;
		}

	}



	/**
	 * Initialise plugin help.
	 *
	 * @since 0.4
	 */
	public function admin_head() {

		// Enqueue WordPress scripts.
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'dashboard' );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get Settings Page Tab URLs.
	 *
	 * @since 0.4
	 *
	 * @return array $urls The array of Settings Page Tab URLs.
	 */
	public function page_tab_urls_get() {

		// Only calculate once.
		if ( isset( $this->urls ) ) {
			return $this->urls;
		}

		// Init return.
		$this->urls = [];

		// Get Settings Page URL.
		$this->urls['settings'] = menu_page_url( $this->settings_page_slug, false );

		/**
		 * Filter the list of URLs.
		 *
		 * @since 0.4
		 *
		 * @param array $urls The existing list of URLs.
		 * @return array $urls The modified list of URLs.
		 */
		$this->urls = apply_filters( 'cwps/admin/settings/tab_urls', $this->urls );

		// --<
		return $this->urls;

	}



	/**
	 * Show our Settings Page.
	 *
	 * @since 0.4
	 */
	public function page_settings() {

		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get Settings Page Tab URLs.
		$urls = $this->page_tab_urls_get();

		/**
		 * Do not show tabs by default but allow overrides.
		 *
		 * @since 0.4
		 *
		 * @param bool False by default - do not show tabs.
		 * @return bool Modified flag for whether or not to show tabs.
		 */
		$show_tabs = apply_filters( 'cwps/admin/settings/show_tabs', false );

		// Get current screen.
		$screen = get_current_screen();

		/**
		 * Allow meta boxes to be added to this screen.
		 *
		 * The Screen ID to use are:
		 *
		 * * "civicrm_page_cwps_parent"
		 * * "civicrm_page_cwps_settings"
		 *
		 * @since 0.4
		 *
		 * @param string $screen_id The ID of the current screen.
		 */
		do_action( 'add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 == $screen->get_columns() ? '1' : '2' );

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/pages/page-admin-settings.php';

	}



	/**
	 * Get our Settings Page screens.
	 *
	 * @since 0.4
	 *
	 * @return array $settings_screens The array of Settings Page screens.
	 */
	public function page_settings_screens_get() {

		// Define this plugin's Settings Page screen IDs.
		$settings_screens = [
			'civicrm_page_' . $this->parent_page_slug,
			'admin_page_' . $this->settings_page_slug,
		];

		/**
		 * Filter the Settings Page screens.
		 *
		 * @since 0.4
		 *
		 * @param array $settings_screens The default array of Settings Page screens.
		 * @return array $settings_screens The modified array of Settings Page screens.
		 */
		return apply_filters( 'cwps/admin/page/settings/screens', $settings_screens );

	}



	/**
	 * Get the URL of the Settings Page.
	 *
	 * @since 0.4
	 *
	 * @return string $url The URL of the Settings Page.
	 */
	public function page_settings_url_get() {

		// Get Settings Page URL.
		$url = menu_page_url( $this->settings_page_slug, false );

		/**
		 * Filter the Settings Page URL.
		 *
		 * @since 0.4
		 *
		 * @param array $url The default Settings Page URL.
		 * @return array $url The modified Settings Page URL.
		 */
		$url = apply_filters( 'cwps/admin/page/settings/url', $url );

		// --<
		return $url;

	}



	/**
	 * Get the URL for the Settings Page form action attribute.
	 *
	 * This happens to be the same as the Settings Page URL, but need not be.
	 *
	 * @since 0.4
	 *
	 * @return string $submit_url The URL for the Settings Page form action.
	 */
	public function page_settings_submit_url_get() {

		// Get Settings Page submit URL.
		$submit_url = menu_page_url( $this->settings_page_slug, false );

		/**
		 * Filter the Settings Page submit URL.
		 *
		 * @since 0.4
		 *
		 * @param array $submit_url The Settings Page submit URL.
		 * @return array $submit_url The modified Settings Page submit URL.
		 */
		$submit_url = apply_filters( 'cwps/admin/page/settings/submit_url', $submit_url );

		// --<
		return $submit_url;

	}



	/**
	 * Get the URL to the Settings Page in our Admin Notices.
	 *
	 * @since 0.4
	 *
	 * @return string $notice_url The URL to the Settings Page in our Admin Notices.
	 */
	public function page_settings_warning_url_get() {

		// Use default Settings Page URL.
		$notice_url = menu_page_url( $this->settings_page_slug, false );

		/**
		 * Filter the Settings Page URL in Admin Notices.
		 *
		 * @since 0.4
		 *
		 * @param array $notice_url The default Settings Page URL in Admin Notices.
		 * @return array $notice_url The modified Settings Page URL in Admin Notices.
		 */
		$notice_url = apply_filters( 'cwps/admin/notice/url', $notice_url );

		// --<
		return $notice_url;

	}



	// -------------------------------------------------------------------------



	/**
	 * Register meta boxes.
	 *
	 * @since 0.4
	 *
	 * @param string $screen_id The Admin Page Screen ID.
	 */
	public function meta_boxes_add( $screen_id ) {

		// Get our Settings Page screens.
		$settings_screens = $this->page_settings_screens_get();

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $settings_screens ) ) {
			return;
		}

		/**
		 * Set access capability but allow overrides.
		 *
		 * @since 0.4
		 *
		 * @param string The default capability for access to Settings Page.
		 * @return string The modified capability for access to Settings Page.
		 */
		$capability = apply_filters( 'cwps/admin/page/settings/cap', 'manage_options' );

		// Check user permissions.
		if ( ! current_user_can( $capability ) ) {
			return;
		}

		// Create Submit metabox.
		add_meta_box(
			'submitdiv',
			__( 'Settings', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_submit_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Create User Profile Settings metabox.
		add_meta_box(
			'cwps_profile',
			__( 'User Profile Settings', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_user_profile_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

	}



	/**
	 * Render Save Settings meta box on Admin screen.
	 *
	 * @since 0.4
	 */
	public function meta_box_submit_render() {

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-admin-settings-submit.php';

	}



	/**
	 * Render User Profile Settings meta box on Admin screen.
	 *
	 * @since 0.4
	 */
	public function meta_box_user_profile_render() {

		// Get Website Types Options.
		$options = $this->plugin->civicrm->website->types_options_get();

		// Get selected Website Type.
		$website_type_selected = $this->setting_get( 'user_profile_website_type', 0 );

		// Get Email Sync setting.
		$email_sync = (int) $this->setting_get( 'user_profile_email_sync', 2 );

		// Get Nickname Sync setting.
		$nickname_sync = (int) $this->setting_get( 'user_profile_nickname_sync', 1 );

		// Init template vars.
		$email_sync_yes = $email_sync === 1 ? ' selected ="selected"' : '';
		$email_sync_no =  $email_sync === 0 ? ' selected ="selected"' : '';
		$nickname_checked =  $nickname_sync === 1 ? ' checked ="checked"' : '';

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-admin-settings-profile.php';

	}



	// -------------------------------------------------------------------------



	/**
	 * Get default settings for this plugin.
	 *
	 * @since 0.4
	 *
	 * @return array $settings The default values for this plugin.
	 */
	public function settings_get_defaults() {

		// Init return.
		$settings = [];

		// Set an impossible default "User Profile Website Type".
		$settings['user_profile_website_type'] = 0;

		// Set impossible default "User Email Sync" value.
		$settings['user_profile_email_sync'] = 2;

		// Default "User Nickname Sync" to "on" since that's previous behaviour.
		$settings['user_profile_nickname_sync'] = 1;

		/**
		 * Filter default settings.
		 *
		 * @since 0.4
		 *
		 * @param array $settings The array of default settings.
		 * @return array $settings The modified array of default settings.
		 */
		$settings = apply_filters( 'cwps/settings/defaults', $settings );

		// --<
		return $settings;

	}



	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 0.4
	 */
	public function settings_update_router() {

		// Was the "Settings" form submitted?
		if ( isset( $_POST['cwps_save'] ) ) {

			// Update the settings.
			$this->settings_update();

			// Get the Settings Page URL.
			$url = $this->page_settings_url_get();

			// Our array of arguments.
			$args = [ 'updated' => 'true' ];

			// Redirect to our Settings Page.
			wp_safe_redirect( add_query_arg( $args, $url ) );
			exit;

		}

	}



	/**
	 * Update options supplied by our Settings Page.
	 *
	 * @since 0.4
	 */
	public function settings_update() {

		// Check that we trust the source of the data.
		check_admin_referer( 'cwps_settings_action', 'cwps_settings_nonce' );

		// Get User Profile Website Type.
		$website_type = ! empty( $_POST['cwps_website_type_select'] ) ?
						(int) trim( $_POST['cwps_website_type_select'] ) :
						0;

		// Did we set a CiviCRM Website Type?
		if ( $website_type !== 0 ) {
			$this->setting_set( 'user_profile_website_type', $website_type );
		}

		// Get User Profile Email Sync.
		$email_sync = isset( $_POST['cwps_email_sync_select'] ) ?
					  (int) trim( $_POST['cwps_email_sync_select'] ) :
					  2;

		// Did we choose an Email Sync setting?
		if ( $email_sync !== 2 ) {

			// Assign the setting.
			$this->setting_set( 'user_profile_email_sync', $email_sync );

			// The setting in CiviCRM is the logical opposite of ours.
			$civicrm_email_sync = $email_sync === 1 ? false : true;
			$this->plugin->civicrm->email->sync_setting_force( $civicrm_email_sync );

		}

		// Get User Profile Nickname setting.
		$nickname_sync = ! empty( $_POST['cwps_nickname_checkbox'] ) ? 1 : 0;

		// Always set User Profile Nickname sync setting.
		$this->setting_set( 'user_profile_nickname_sync', $nickname_sync );

		/**
		 * Allow plugins to hook into the settings update process.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/admin/settings/update/pre' );

		// Save options.
		$this->settings_save();

		/**
		 * Broadcast that the settings update process is finished.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/admin/settings/update/post' );

	}



	// -------------------------------------------------------------------------



	/**
	 * Save array as option.
	 *
	 * @since 0.4
	 *
	 * @return bool Success or failure.
	 */
	public function settings_save() {

		// Save array as option.
		return $this->option_set( 'cwps_settings', $this->settings );

	}



	/**
	 * Check whether a specified setting exists.
	 *
	 * @since 0.4
	 *
	 * @param string $setting_name The name of the setting.
	 * @return bool Whether or not the setting exists.
	 */
	public function setting_exists( $setting_name = '' ) {

		// Get existence of setting in array.
		return array_key_exists( $setting_name, $this->settings );

	}



	/**
	 * Return a value for a specified setting.
	 *
	 * @since 0.4
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed $default The default value if the setting does not exist.
	 * @return mixed The setting or the default.
	 */
	public function setting_get( $setting_name = '', $default = false ) {

		// Get setting.
		return ( array_key_exists( $setting_name, $this->settings ) ) ? $this->settings[$setting_name] : $default;

	}



	/**
	 * Sets a value for a specified setting.
	 *
	 * @since 0.4
	 *
	 * @param string $setting_name The name of the setting.
	 * @param mixed $value The value of the setting.
	 */
	public function setting_set( $setting_name = '', $value = '' ) {

		// Set setting.
		$this->settings[$setting_name] = $value;

	}



	/**
	 * Deletes a specified setting.
	 *
	 * @since 0.4
	 *
	 * @param string $setting_name The name of the setting.
	 */
	public function setting_delete( $setting_name = '' ) {

		// Unset setting.
		unset( $this->settings[$setting_name] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Test existence of a specified option.
	 *
	 * @since 0.4
	 *
	 * @param string $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function option_exists( $option_name = '' ) {

		// Test by getting option with unlikely default.
		if ( $this->option_get( $option_name, 'fenfgehgefdfdjgrkj' ) == 'fenfgehgefdfdjgrkj' ) {
			return false;
		} else {
			return true;
		}

	}



	/**
	 * Return a value for a specified option.
	 *
	 * @since 0.4
	 *
	 * @param string $option_name The name of the option.
	 * @param string $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function option_get( $option_name = '', $default = false ) {

		// Get option.
		$value = get_site_option( $option_name, $default );

		// --<
		return $value;

	}



	/**
	 * Set a value for a specified option.
	 *
	 * @since 0.4
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully updated.
	 */
	public function option_set( $option_name = '', $value = '' ) {

		// Update option.
		return update_site_option( $option_name, $value );

	}



	/**
	 * Delete a specified option.
	 *
	 * @since 0.4
	 *
	 * @param string $option_name The name of the option.
	 * @return bool $success True if the option was successfully deleted.
	 */
	public function option_delete( $option_name = '' ) {

		// Delete option.
		return delete_site_option( $option_name );

	}



} // Class ends.



