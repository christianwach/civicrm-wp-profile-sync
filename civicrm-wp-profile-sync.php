<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM Profile Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-profile-sync
Description: Keeps entities in CiviCRM in sync with their equivalents in WordPress.
Author: Christian Wach
Version: 0.4
Author URI: https://haystack.co.uk
Text Domain: civicrm-wp-profile-sync
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// Set plugin version here.
define( 'CIVICRM_WP_PROFILE_SYNC_VERSION', '0.4' );

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

		// Only do this once.
		static $done;
		if ( isset( $done ) AND $done === true ) {
			return;
		}

		// Load our class files.
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/admin/cwps-admin.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/wordpress/cwps-wp.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/cai/cwps-cai.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/cwps-acf-loader.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/mapper/cwps-mapper.php';

		// We're done.
		$done = true;

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Only do this once.
		static $done;
		if ( isset( $done ) AND $done === true ) {
			return;
		}

		// Initialise objects.
		$this->admin = new CiviCRM_WP_Profile_Sync_Admin();
		$this->wp = new CiviCRM_WP_Profile_Sync_WordPress();
		$this->civicrm = new CiviCRM_WP_Profile_Sync_CiviCRM();
		$this->bp = new CiviCRM_WP_Profile_Sync_BuddyPress();
		$this->cai = new CiviCRM_WP_Profile_Sync_CAI();
		$this->acf = new CiviCRM_WP_Profile_Sync_ACF_Loader();
		$this->mapper = new CiviCRM_WP_Profile_Sync_Mapper();

		// Store references.
		$this->admin->set_references( $this );
		$this->wp->set_references( $this );
		$this->civicrm->set_references( $this );
		$this->bp->set_references( $this );
		$this->cai->set_references( $this );
		$this->acf->set_references( $this );
		$this->mapper->set_references( $this );

		// We're done.
		$done = true;

	}



	// -------------------------------------------------------------------------



	/**
	 * Add BuddyPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_bp_add() {

		// Pass requests to BuddyPress object.
		$this->bp->register_mapper_hooks();

	}



	/**
	 * Remove BuddyPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_bp_remove() {

		// Pass requests to BuddyPress object.
		$this->bp->unregister_mapper_hooks();

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

		// Pass requests to WordPress object.
		$this->wp->register_mapper_hooks();

	}



	/**
	 * Remove WordPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_wp_remove() {

		// Pass requests to WordPress object.
		$this->wp->unregister_mapper_hooks();

	}



	/**
	 * Add CiviCRM sync hooks.
	 *
	 * Syncs data to a WordPress User when a CiviCRM Contact is updated.
	 *
	 * @since 0.1
	 */
	public function hooks_civicrm_add() {

		// Pass requests to CiviCRM object.
		$this->civicrm->register_mapper_hooks();

	}



	/**
	 * Remove CiviCRM sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_civicrm_remove() {

		// Pass requests to CiviCRM object.
		$this->civicrm->unregister_mapper_hooks();

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



