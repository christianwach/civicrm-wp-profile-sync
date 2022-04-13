<?php
/**
 * Plugin Name: CiviCRM Profile Sync
 * Plugin URI: https://github.com/christianwach/civicrm-wp-profile-sync
 * GitHub Plugin URI: https://github.com/christianwach/civicrm-wp-profile-sync
 * Description: Keeps a WordPress User profile in sync with a CiviCRM Contact and integrates WordPress and CiviCRM Entities with data synced via Advanced Custom Fields.
 * Author: Christian Wach
 * Version: 0.5.3a
 * Author URI: https://haystack.co.uk
 * Text Domain: civicrm-wp-profile-sync
 * Domain Path: /languages
 * Depends: CiviCRM
 */



// Set plugin version here.
define( 'CIVICRM_WP_PROFILE_SYNC_VERSION', '0.5.3a' );

// Set our bulk operations flag here.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_BULK' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_BULK', false );
}

// Store reference to this file.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_FILE' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_URL' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_URL', plugin_dir_url( CIVICRM_WP_PROFILE_SYNC_FILE ) );
}

// Store PATH to this plugin's directory.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_PATH' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_PATH', plugin_dir_path( CIVICRM_WP_PROFILE_SYNC_FILE ) );
}

// Set BuddyPress development environment.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_BUDDYPRESS' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_BUDDYPRESS', false );
}



/**
 * CiviCRM Profile Sync Class.
 *
 * A class that encapsulates this plugin's functionality.
 *
 * @since 0.1
 */
class CiviCRM_WP_Profile_Sync {

	/**
	 * Admin object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $admin The Admin object.
	 */
	public $admin;

	/**
	 * Mapper object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $mapper The Mapper object.
	 */
	public $mapper;

	/**
	 * WordPress compatibility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $wp The WordPress compatibility object.
	 */
	public $wp;

	/**
	 * CiviCRM compatibility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The CiviCRM compatibility object.
	 */
	public $civicrm;

	/**
	 * BuddyPress compatibility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $bp The BuddyPress compatibility object.
	 */
	public $bp;

	/**
	 * ACF compatibility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf The ACF compatibility object.
	 */
	public $acf;

	/**
	 * CiviCRM ACF Integration compatibility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $cai The CiviCRM ACF Integration compatibility object.
	 */
	public $cai;



	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Initialise this plugin.
		$this->initialise();

	}



	/**
	 * Initialise this plugin.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && $done === true ) {
			return;
		}

		// Use translation.
		$this->translation();

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		/**
		 * Broadcast that this plugin is active.
		 *
		 * @since 0.2.4
		 */
		do_action( 'civicrm_wp_profile_sync_init' );

		// We're done.
		$done = true;

	}



	/**
	 * Enable translation.
	 *
	 * @since 0.1
	 */
	public function translation() {

		// Load translations.
		load_plugin_textdomain(
			'civicrm-wp-profile-sync', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( CIVICRM_WP_PROFILE_SYNC_FILE ) ) . '/languages/' // Relative path to files.
		);

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Load our class files.
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/admin/cwps-admin.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/wordpress/cwps-wp.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/cai/cwps-cai.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/cwps-acf-loader.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/mapper/cwps-mapper.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->admin = new CiviCRM_WP_Profile_Sync_Admin( $this );
		$this->wp = new CiviCRM_WP_Profile_Sync_WordPress( $this );
		$this->civicrm = new CiviCRM_WP_Profile_Sync_CiviCRM( $this );
		$this->bp = new CiviCRM_WP_Profile_Sync_BuddyPress( $this );
		$this->cai = new CiviCRM_WP_Profile_Sync_CAI( $this );
		$this->acf = new CiviCRM_WP_Profile_Sync_ACF_Loader( $this );
		$this->mapper = new CiviCRM_WP_Profile_Sync_Mapper( $this );

	}



	// -------------------------------------------------------------------------



	/**
	 * Add BuddyPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_bp_add() {

		/**
		 * Broadcast that BuddyPress hooks should be added.
		 *
		 * @since 0.5.2
		 */
		do_action( 'cwps/plugin/hooks/bp/add' );

	}



	/**
	 * Remove BuddyPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_bp_remove() {

		/**
		 * Broadcast that BuddyPress hooks should be removed.
		 *
		 * @since 0.5.2
		 */
		do_action( 'cwps/plugin/hooks/bp/remove' );

	}



	/**
	 * Add WordPress sync hooks.
	 *
	 * Post-processes a CiviCRM Contact when a WordPress User is updated.
	 * Hooked in late to let other plugins go first.
	 *
	 * @since 0.1
	 */
	public function hooks_wp_add() {

		/**
		 * Broadcast that WordPress hooks should be added.
		 *
		 * @since 0.5.2
		 */
		do_action( 'cwps/plugin/hooks/wp/add' );

	}



	/**
	 * Remove WordPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_wp_remove() {

		/**
		 * Broadcast that WordPress hooks should be removed.
		 *
		 * @since 0.5.2
		 */
		do_action( 'cwps/plugin/hooks/wp/remove' );

	}



	/**
	 * Add CiviCRM sync hooks.
	 *
	 * Syncs data to a WordPress User when a CiviCRM Contact is updated.
	 *
	 * @since 0.1
	 */
	public function hooks_civicrm_add() {

		/**
		 * Broadcast that CiviCRM hooks should be added.
		 *
		 * @since 0.5.2
		 */
		do_action( 'cwps/plugin/hooks/civicrm/add' );

	}



	/**
	 * Remove CiviCRM sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_civicrm_remove() {

		/**
		 * Broadcast that CiviCRM hooks should be removed.
		 *
		 * @since 0.5.2
		 */
		do_action( 'cwps/plugin/hooks/civicrm/remove' );

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if this plugin is network activated.
	 *
	 * @since 0.4
	 *
	 * @return bool $is_network_active True if network activated, false otherwise.
	 */
	public function is_network_activated() {

		// Only need to test once.
		static $is_network_active;

		// Have we done this already?
		if ( isset( $is_network_active ) ) {
			return $is_network_active;
		}

		// If not multisite, it cannot be.
		if ( ! is_multisite() ) {
			$is_network_active = false;
			return $is_network_active;
		}

		// Make sure plugin file is included when outside admin.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Get path from 'plugins' directory to this plugin.
		$this_plugin = plugin_basename( CIVICRM_WP_PROFILE_SYNC_FILE );

		// Test if network active.
		$is_network_active = is_plugin_active_for_network( $this_plugin );

		// --<
		return $is_network_active;

	}



	/**
	 * Check if CiviCRM is network activated.
	 *
	 * @since 0.4
	 *
	 * @return bool $civicrm_network_active True if network activated, false otherwise.
	 */
	public function is_civicrm_network_activated() {

		// Only need to test once.
		static $civicrm_network_active;

		// Have we done this already?
		if ( isset( $civicrm_network_active ) ) {
			return $civicrm_network_active;
		}

		// If not multisite, it cannot be.
		if ( ! is_multisite() ) {
			$civicrm_network_active = false;
			return $civicrm_network_active;
		}

		// If CiviCRM's constant is not defined, we'll never know.
		if ( ! defined( 'CIVICRM_PLUGIN_FILE' ) ) {
			$civicrm_network_active = false;
			return $civicrm_network_active;
		}

		// Make sure plugin file is included when outside admin.
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Get path from 'plugins' directory to CiviCRM's directory.
		$civicrm = plugin_basename( CIVICRM_PLUGIN_FILE );

		// Test if network active.
		$civicrm_network_active = is_plugin_active_for_network( $civicrm );

		// --<
		return $civicrm_network_active;

	}



	/**
	 * Check if CiviCRM Admin Utilities is hiding CiviCRM except on main site.
	 *
	 * @since 0.4
	 *
	 * @return bool $civicrm_hidden True if CAU is hiding CiviCRM, false otherwise.
	 */
	public function is_civicrm_main_site_only() {

		// Only need to test once.
		static $civicrm_hidden;

		// Have we done this already?
		if ( isset( $civicrm_hidden ) ) {
			return $civicrm_hidden;
		}

		// If not multisite, it cannot be.
		if ( ! is_multisite() ) {
			$civicrm_hidden = false;
			return $civicrm_hidden;
		}

		// Bail if CiviCRM is not network-activated.
		if ( ! $this->is_civicrm_network_activated() ) {
			$civicrm_hidden = false;
			return $civicrm_hidden;
		}

		// If CAU's constant is not defined, we'll never know.
		if ( ! defined( 'CIVICRM_ADMIN_UTILITIES_VERSION' ) ) {
			$civicrm_hidden = false;
			return $civicrm_hidden;
		}

		// Grab the CAU plugin reference.
		$cau = civicrm_au();

		// Bail if CAU's multisite object is not defined.
		if ( empty( $cau->multisite ) ) {
			$civicrm_hidden = false;
			return $civicrm_hidden;
		}

		// Bail if not hidden.
		if ( $cau->multisite->setting_get( 'main_site_only', '0' ) == '0' ) {
			$civicrm_hidden = false;
			return $civicrm_hidden;
		}

		// CAU is hiding CiviCRM.
		$civicrm_hidden = true;
		return $civicrm_hidden;

	}



} // Class ends.



/**
 * Load plugin if not yet loaded and return reference.
 *
 * @since 0.1
 *
 * @return CiviCRM_WP_Profile_Sync $civicrm_wp_profile_sync The plugin reference.
 */
function civicrm_wp_profile_sync() {

	// Declare as global.
	global $civicrm_wp_profile_sync;

	// Instantiate plugin if not yet instantiated.
	if ( ! isset( $civicrm_wp_profile_sync ) ) {
		$civicrm_wp_profile_sync = new CiviCRM_WP_Profile_Sync();
	}

	// --<
	return $civicrm_wp_profile_sync;

}

// Load only when CiviCRM has loaded.
add_action( 'civicrm_instance_loaded', 'civicrm_wp_profile_sync' );



/**
 * Performs plugin activation tasks.
 *
 * @since 0.5
 */
function civicrm_wp_profile_sync_activate() {

	/**
	 * Broadcast that this plugin has been activated.
	 *
	 * @since 0.5
	 */
	do_action( 'cwps/activated' );

}

// Activation.
register_activation_hook( __FILE__, 'civicrm_wp_profile_sync_activate' );



/**
 * Performs plugin deactivation tasks.
 *
 * @since 0.5
 */
function civicrm_wp_profile_sync_deactivated() {

	/**
	 * Broadcast that this plugin has been deactivated.
	 *
	 * @since 0.5
	 */
	do_action( 'cwps/deactivated' );

}

// Deactivation.
register_deactivation_hook( __FILE__, 'civicrm_wp_profile_sync_deactivated' );

/*
 * Uninstall uses the 'uninstall.php' method.
 * @see https://developer.wordpress.org/reference/functions/register_uninstall_hook/
 */
