<?php
/**
 * Admin Class.
 *
 * Handles ACF migration functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync ACF Admin Migrate Class
 *
 * A class that encapsulates ACF migration functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Admin_Migrate {

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * CiviCRM Profile Sync Mappings option key.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $mappings_key The Mappings option key.
	 */
	public $cwps_mappings_key = 'cwps_acf_mappings';

	/**
	 * CiviCRM Profile Sync Mapped items Settings option key.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $settings_key The Settings option key.
	 */
	public $cwps_settings_key = 'cwps_acf_mapping_settings';

	/**
	 * CiviCRM ACF Integration Mappings option key.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $mappings_key The Mappings option key.
	 */
	public $cai_mappings_key = 'civicrm_acf_integration_mappings';

	/**
	 * CiviCRM ACF Integration Mapped items Settings option key.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $settings_key The Settings option key.
	 */
	public $cai_settings_key = 'civicrm_acf_integration_mapping_settings';



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $acf_loader;

		// Init on admin init.
		add_action( 'init', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Is this the back end?
		if ( ! is_admin() ) {
			return;
		}

		// Show a notice.
		add_action( 'admin_notices', [ $this, 'admin_notice' ] );

		// Add menu item(s) to WordPress admin menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 30 );

		// Add our meta boxes.
		add_action( 'add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 1 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Show a notice when CiviCRM ACF Integration is present.
	 *
	 * @since 0.4
	 */
	public function admin_notice() {

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get current screen.
		$screen = get_current_screen();

		// Bail if it's not what we expect.
		if ( ! ( $screen instanceof WP_Screen ) ) {
			return;
		}

		// Bail if we are on our "ACF Integration" page.
		if ( $screen->id == 'admin_page_cwps_acf_sync' ) {
			return;
		}

		// Bail if there is already a warning.
		if ( $this->acf_loader->plugin->admin->has_warning === true ) {
			return;
		}

		// Show general "Call to Action".
		$message = sprintf(
			__( 'CiviCRM ACF Integration has become part of CiviCRM Profile Sync. Please visit the %1$sMigration Page%2$s to switch over.', 'civicrm-wp-profile-sync' ),
			'<a href="' . menu_page_url( 'cwps_acf_sync', false ) . '">',
			'</a>'
		);

		// Show it.
		echo '<div id="message" class="notice notice-warning">';
		echo '<p>' . $message . '</p>';
		echo '</div>';

	}



	// -------------------------------------------------------------------------



	/**
	 * Add our admin page(s) to the WordPress admin menu.
	 *
	 * @since 0.4
	 */
	public function admin_menu() {

		// We must be network admin in Multisite.
		if ( is_multisite() AND ! is_super_admin() ) {
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add our "ACF Integration" page to the CiviCRM menu.
		$this->migrate_page = add_submenu_page(
			'cwps_parent', // Parent slug.
			__( 'CiviCRM Profile Sync', 'civicrm-wp-profile-sync' ),
			__( 'ACF Integration', 'civicrm-wp-profile-sync' ),
			'manage_options',
			'cwps_acf_sync',
			[ $this, 'page_acf_migrate' ]
		);

		// Register our form submit hander.
		add_action( 'load-' . $this->migrate_page, [ $this, 'form_submitted' ] );

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->migrate_page, [ $this->acf_loader->plugin->admin, 'admin_menu_highlight' ], 50 );

		// Add styles and scripts only on our "ACF Integration" page.
		// @see wp-admin/admin-header.php
		add_action( 'admin_head-' . $this->migrate_page, [ $this, 'admin_head' ] );
		add_action( 'admin_print_styles-' . $this->migrate_page, [ $this, 'admin_styles' ] );
		//add_action( 'admin_print_scripts-' . $this->migrate_page, [ $this, 'admin_scripts' ] );

		// Filter the list of single site subpages and add multidomain page.
		add_filter( 'cwps/admin/settings/subpages', [ $this, 'admin_subpages_filter' ] );

		// Filter the list of single site page URLs and add multidomain page URL.
		add_filter( 'cwps/admin/settings/page_urls', [ $this, 'page_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'cwps/admin/settings/show_tabs', [ $this, 'page_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'cwps/admin/settings/nav_tabs', [ $this, 'page_add_tab' ], 10, 2 );

	}



	/**
	 * Add metabox scripts and initialise plugin help.
	 *
	 * @since 0.4
	 */
	public function admin_head() {

		// Enqueue WordPress scripts.
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'dashboard' );

	}



	/**
	 * Enqueue any styles needed by our Migrate page.
	 *
	 * @since 0.4
	 */
	public function admin_styles() {

		// Enqueue CSS.
		wp_enqueue_style(
			'cwps-admin-migrate',
			CIVICRM_WP_PROFILE_SYNC_URL . 'assets/css/acf/pages/cwps-acf-admin-migrate.css',
			null,
			CIVICRM_WP_PROFILE_SYNC_VERSION,
			'all' // Media.
		);

	}



	/**
	 * Append the "ACF Integration" page to Settings page.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * "ACF Integration" subpage.
	 *
	 * @since 0.4
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function admin_subpages_filter( $subpages ) {

		// Add "ACF Integration" page.
		$subpages[] = 'cwps_acf_sync';

		// --<
		return $subpages;

	}



	// -------------------------------------------------------------------------



	/**
	 * Show our "ACF Integration" page.
	 *
	 * @since 0.4
	 */
	public function page_acf_migrate() {

		// We must be network admin in multisite.
		if ( is_multisite() AND ! is_super_admin() ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-wp-profile-sync' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-wp-profile-sync' ) );
		}

		// Get current screen.
		$screen = get_current_screen();

		/**
		 * Allow meta boxes to be added to this screen.
		 *
		 * The Screen ID to use is: "civicrm_page_cwps_acf_sync".
		 *
		 * @since 0.4
		 *
		 * @param str $screen_id The ID of the current screen.
		 */
		do_action( 'add_meta_boxes', $screen->id, null );

		// Grab columns.
		$columns = ( 1 == $screen->get_columns() ? '1' : '2' );

		// Get admin page URLs.
		$urls = $this->acf_loader->plugin->admin->page_get_urls();

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/pages/page-admin-acf-migrate.php';

	}



	/**
	 * Append the "ACF Integration" page URL to the subpage URLs.
	 *
	 * @since 0.4
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_urls_filter( $urls ) {

		// Add multidomain settings page.
		$urls['acf-migrate'] = menu_page_url( 'cwps_acf_sync', false );

		// --<
		return $urls;

	}



	/**
	 * Show subpage tabs on settings pages.
	 *
	 * @since 0.4
	 *
	 * @param bool $show_tabs True if tabs are shown, false otherwise.
	 * @return bool $show_tabs True if tabs are to be shown, false otherwise.
	 */
	public function page_show_tabs( $show_tabs ) {

		// Always show tabs.
		$show_tabs = true;

		// --<
		return $show_tabs;

	}



	/**
	 * Add subpage tab to tabs on settings pages.
	 *
	 * @since 0.4
	 *
	 * @param array $urls The array of subpage URLs.
	 * @param str The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'ACF Integration', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( $active_tab === 'acf-migrate' ) {
			$active = ' nav-tab-active';
		}

		// Render tab.
		echo '<a href="' . $urls['acf-migrate'] . '" class="nav-tab' . $active . '">' . $title . '</a>' . "\n";

	}



	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.4
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function page_submit_url_get() {

		// Sanitise admin page url.
		$target_url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $target_url );

		// Strip flag, if present, and rebuild.
		if ( ! empty( $url_array ) ) {
			$url_raw = str_replace( '&amp;updated=true', '', $url_array[0] );
			$target_url = htmlentities( $url_raw . '&updated=true' );
		}

		// --<
		return $target_url;

	}



	// -------------------------------------------------------------------------



	/**
	 * Register meta boxes.
	 *
	 * @since 0.4
	 *
	 * @param str $screen_id The Admin Page Screen ID.
	 */
	public function meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_cwps_acf_sync',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids ) ) {
			return;
		}

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) {
			return;
		}

		// Init data.
		$data = [];

		// Have we already migrated?
		$data['migrated'] = false;
		if (
			$this->option_exists( $this->cwps_mappings_key )
			AND
			$this->option_exists( $this->cwps_settings_key )
		) {
			$data['migrated'] = true;
		}

		// Only show submit if not migrated.
		if ( $data['migrated'] === false ) {

			// Create Submit metabox.
			add_meta_box(
				'submitdiv',
				__( 'Migrate Settings', 'civicrm-wp-profile-sync' ),
				[ $this, 'meta_box_submit_render' ], // Callback.
				$screen_id, // Screen ID.
				'side', // Column: options are 'normal' and 'side'.
				'core', // Vertical placement: options are 'core', 'high', 'low'.
				$data
			);

		}

		// Init meta box title.
		$title = __( 'Migration Tasks', 'civicrm-wp-profile-sync' );
		if ( $data['migrated'] === true ) {
			$title = __( 'Migration Complete', 'civicrm-wp-profile-sync' );
		}

		// Create "Migrate Info" metabox.
		add_meta_box(
			'cwps_info',
			$title,
			[ $this, 'meta_box_migrate_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core', // Vertical placement: options are 'core', 'high', 'low'.
			$data
		);

	}



	/**
	 * Render Submit meta box on Admin screen.
	 *
	 * @since 0.4
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_submit_render( $unused = NULL, $metabox ) {

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-migrate-submit.php';

	}



	/**
	 * Render "Migrate Settings" meta box on Admin screen.
	 *
	 * @since 0.4
	 *
	 * @param mixed $unused Unused param.
	 * @param array $metabox Array containing id, title, callback, and args elements.
	 */
	public function meta_box_migrate_render( $unused = NULL, $metabox ) {

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-migrate-info.php';

	}



	/**
	 * Perform actions when the form has been submitted.
	 *
	 * @since 0.4
	 */
	public function form_submitted() {

		// Bail if our submit button wasn't clicked.
		if ( empty( $_POST['cwps_migrate_submit'] ) ) {
			return;
		}

		// Let's go.
		$this->form_nonce_check();
		$this->form_migrate_settings();
		$this->form_redirect();

	}



	/**
	 * Migrate the settings.
	 *
	 * @since 0.4
	 */
	private function form_migrate_settings() {

		// Migrate mappings if they exist.
		if ( $this->option_exists( $this->cai_mappings_key ) ) {
			$mappings = $this->option_get( $this->cai_mappings_key );
			$this->option_set( $this->cwps_mappings_key, $mappings );
		}

		// Migrate mapping settings if they exist.
		if ( $this->option_exists( $this->cai_settings_key ) ) {
			$settings = $this->option_get( $this->cai_settings_key );
			$this->option_set( $this->cwps_settings_key, $settings );
		}

	}



	/**
	 * Check the nonce.
	 *
	 * @since 0.4
	 */
	private function form_nonce_check() {

		// Do we trust the source of the data?
		check_admin_referer( 'cwps_migrate_action', 'cwps_migrate_nonce' );

	}



	/**
	 * Redirect to the Settings page with an extra param.
	 *
	 * @since 0.4
	 */
	private function form_redirect() {

		// Our array of arguments.
		$args = [
			'page' => 'cwps_acf_sync',
			'settings-updated' => 'true',
		];

		// Redirect to our admin page.
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );

	}



	// -------------------------------------------------------------------------



	/**
	 * Test existence of a specified option.
	 *
	 * @since 0.4
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function option_exists( $option_name = '' ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_exists()', 'civicrm-wp-profile-sync' ) );
		}

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
	 * @param str $option_name The name of the option.
	 * @param str $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function option_get( $option_name = '', $default = false ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_get()', 'civicrm-wp-profile-sync' ) );
		}

		// Get option.
		$value = get_option( $option_name, $default );

		// --<
		return $value;

	}



	/**
	 * Set a value for a specified option.
	 *
	 * @since 0.4
	 *
	 * @param str $option_name The name of the option.
	 * @param mixed $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully updated.
	 */
	public function option_set( $option_name = '', $value = '' ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_set()', 'civicrm-wp-profile-sync' ) );
		}

		// Update option.
		return update_option( $option_name, $value );

	}



	/**
	 * Delete a specified option.
	 *
	 * @since 0.4
	 *
	 * @param str $option_name The name of the option.
	 * @return bool $success True if the option was successfully deleted.
	 */
	public function option_delete( $option_name = '' ) {

		// Test for empty.
		if ( $option_name == '' ) {
			die( __( 'You must supply an option to option_delete()', 'civicrm-wp-profile-sync' ) );
		}

		// Delete option.
		return delete_option( $option_name );

	}



} // Class ends.



