<?php
/**
 * ACF Extended Admin Class.
 *
 * Handles ACF Extended admin functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.6.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync ACF Extended Admin Class
 *
 * A class that encapsulates ACF Extended Admin functionality.
 *
 * @since 0.6.6
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Admin {

	/**
	 * Plugin object.
	 *
	 * @since 0.6.6
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.6.6
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.6.6
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * ACF object.
	 *
	 * @since 0.6.6
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * ACF Extended object.
	 *
	 * @since 0.6.6
	 * @access public
	 * @var object
	 */
	public $acfe;

	/**
	 * Hook prefix.
	 *
	 * @since 0.6.6
	 * @access public
	 * @var string
	 */
	public $hook_prefix = 'cwps_acfe';

	/**
	 * Constructor.
	 *
	 * @since 0.6.6
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm = $this->acf_loader->civicrm;
		$this->acf = $this->acf_loader->acf;
		$this->acfe = $parent;

		// Init when the ACFE class is loaded.
		add_action( 'cwps/acf/acfe/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.6.6
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.6.6
	 */
	public function register_hooks() {

		// Bail if WordPress Network Admin.
		if ( is_multisite() && is_network_admin() ) {
			return;
		}

		// Bail if not WordPress Admin.
		if ( ! is_admin() ) {
			return;
		}

		// Add our ACF Extended Settings.
		add_filter( 'cwps/settings/defaults', [ $this, 'settings_acfe_defaults' ], 10, 1 );
		add_action( 'cwps/admin/page/settings/meta_boxes/added', [ $this, 'settings_meta_boxes_add' ], 11, 1 );
		add_action( 'cwps/admin/settings/update/pre', [ $this, 'settings_acfe_update' ] );

		// Add scripts to Settings Page.
		add_action( 'cwps/admin/page/parent/admin_menu', [ $this, 'admin_menu' ], 10, 2 );
		add_action( 'cwps/admin/page/settings/admin_menu', [ $this, 'admin_menu' ], 10, 2 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Adds the default ACF Extended Forms Integration settings.
	 *
	 * If you want to filter these values, use the `cwps/settings/defaults` filter
	 * with a priority of 12 or greater.
	 *
	 * @since 0.6.6
	 *
	 * @param array $settings The existing default settings array.
	 * @return array $settings The modified default settings array.
	 */
	public function settings_acfe_defaults( $settings ) {

		// Default ACF Extended Forms Integration "Enabled" to "on".
		$settings['acfe_integration_enabled'] = 1;

		// Default ACF Extended Forms Integration "Transients Cache" to "off".
		$settings['acfe_integration_transients'] = 0;

		// --<
		return $settings;

	}

	/**
	 * Register settings meta boxes.
	 *
	 * @since 0.6.6
	 *
	 * @param string $screen_id The Admin Page Screen ID.
	 */
	public function settings_meta_boxes_add( $screen_id ) {

		// Create ACF Extended Settings metabox.
		add_meta_box(
			'cwps_acfe_integration',
			__( 'ACF Extended Settings', 'civicrm-wp-profile-sync' ),
			[ $this, 'settings_meta_box_acfe_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

	}

	/**
	 * Render ACF Extended Settings meta box on Admin screen.
	 *
	 * @since 0.6.6
	 */
	public function settings_meta_box_acfe_render() {

		// Get settings.
		$acfe_enabled = (int) $this->plugin->admin->setting_get( 'acfe_integration_enabled', 1 );
		$acfe_transients = (int) $this->plugin->admin->setting_get( 'acfe_integration_transients', 0 );

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-admin-settings-acfe.php';

	}

	/**
	 * Updates the Integration Enabled setting value when saved on the Admin screen.
	 *
	 * @since 0.6.6
	 */
	public function settings_acfe_update() {

		// Get Enabled setting. Nonce is checked in admin class.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$acfe_enabled = ! empty( $_POST['cwps_acfe_integration_enabled'] ) ? 1 : 0;
		$this->plugin->admin->setting_set( 'acfe_integration_enabled', $acfe_enabled );

		// Grab previous "Transient Cache" setting.
		$acfe_transients_pre = (int) $this->plugin->admin->setting_get( 'acfe_integration_transients', 0 );

		// Should we enable caching via transients? Nonce is checked in admin class.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$acfe_transients = ! empty( $_POST['cwps_acfe_integration_transients'] ) ? 1 : 0;
		$this->plugin->admin->setting_set( 'acfe_integration_transients', $acfe_transients );

		// Clear the transients any time the setting changes.
		if ( $acfe_transients_pre !== $acfe_transients ) {
			delete_site_transient( 'cwps_acf_acfe_form_action_activity' );
			delete_site_transient( 'cwps_acf_acfe_form_action_case' );
			delete_site_transient( 'cwps_acf_acfe_form_action_contact' );
			delete_site_transient( 'cwps_acf_acfe_form_action_event' );
			delete_site_transient( 'cwps_acf_acfe_form_action_participant' );
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Called when the Settings Page (or its parent) has been added.
	 *
	 * @since 0.6.6
	 *
	 * @param string $handle The handle of the Settings Page.
	 * @param string $slug The slug of the Settings Page.
	 */
	public function admin_menu( $handle, $slug ) {

		// Add scripts and styles.
		add_action( 'admin_print_styles-' . $handle, [ $this, 'admin_styles' ] );
		add_action( 'admin_print_scripts-' . $handle, [ $this, 'admin_scripts' ] );

	}

	/**
	 * Adds styles.
	 *
	 * @since 0.6.6
	 */
	public function admin_styles() {

		// Enqueue our "Settings Page" stylesheet.
		wp_enqueue_style(
			$this->hook_prefix . '-css',
			plugins_url( 'assets/css/acf/acfe/pages/page-settings-acfe.css', CIVICRM_WP_PROFILE_SYNC_FILE ),
			false,
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			'all' // Media.
		);

	}

	/**
	 * Adds scripts.
	 *
	 * @since 0.6.6
	 */
	public function admin_scripts() {

		// Enqueue our "Settings Page" script.
		wp_enqueue_script(
			$this->hook_prefix . '-js',
			plugins_url( 'assets/js/acf/acfe/pages/page-settings-acfe.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'jquery' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			true
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the duration of the Form Action cache transient.
	 *
	 * @since 0.6.6
	 *
	 * @return int $duration The duration of the Form Action cache transient. Default is DAY_IN_SECONDS.
	 */
	public function transient_duration_get() {

		/**
		 * Filter the duration of the Form Action cache transient.
		 *
		 * @see https://codex.wordpress.org/Easier_Expression_of_Time_Constants
		 * @see https://developer.wordpress.org/reference/functions/wp_initial_constants/
		 *
		 * @since 0.6.6
		 *
		 * @param int $duration The duration of the transient.
		 */
		$duration = apply_filters( 'cwps/acf/acfe/form/action/transient/duration', DAY_IN_SECONDS );

		// --<
		return $duration;

	}

}
