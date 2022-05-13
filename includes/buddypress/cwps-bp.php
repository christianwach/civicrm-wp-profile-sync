<?php
/**
 * BuddyPress compatibility Class.
 *
 * Handles BuddyPress integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync BuddyPress Class.
 *
 * This class provides BuddyPress integration.
 *
 * Add option to each BuddyPress Profile Field Type to sync directly with the
 * relevant type of CiviCRM Contact Field or Custom Field.
 *
 * Add option to each Custom Field Type to sync directly with the relevant type
 * of BuddyPress Profile Field Field.
 *
 * Sync BuddyPress Member Types to CiviCRM Contact Types.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_BuddyPress {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * BuddyPress reference.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf The BuddyPress plugin reference.
	 */
	public $bp = false;

	/**
	 * BuddyBoss flag.
	 *
	 * @since 0.5
	 * @access public
	 * @var bool $is_buddyboss BuddyBoss flag. True when BuddyBoss is installed.
	 */
	public $is_buddyboss = false;

	/**
	 * BuddyBoss Compatibility object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $buddyboss The BuddyBoss Compatibility object.
	 */
	public $buddyboss;

	/**
	 * BuddyPress xProfile object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $xprofile The BuddyPress xProfile object.
	 */
	public $xprofile;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_hooks The Mapper hooks registered flag.
	 */
	public $mapper_hooks = false;

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

		// Maybe store reference to BuddyPress.
		if ( function_exists( 'buddypress' ) ) {
			$this->bp = buddypress();
		}

		// Bail if BuddyPress isn't detected.
		if ( $this->bp === false ) {
			return;
		}

		// Save BuddyBoss flag.
		if ( defined( 'BP_PLATFORM_VERSION' ) && BP_PLATFORM_VERSION ) {
			$this->is_buddyboss = true;

			// NOTE: bp_hide_last_name().

		}

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Always register plugin hooks.
		add_action( 'cwps/plugin/hooks/bp/add', [ $this, 'register_mapper_hooks' ] );
		add_action( 'cwps/plugin/hooks/bp/remove', [ $this, 'unregister_mapper_hooks' ] );

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/buddypress/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Maybe include BuddyBoss class.
		if ( $this->is_buddyboss === true ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-buddyboss.php';
		}

		// Bail if not developing BuddyPress xProfile sync.
		if ( CIVICRM_WP_PROFILE_SYNC_BUDDYPRESS === false ) {
			return;
		}

		// Include xProfile class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-xprofile.php';

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Maybe init BuddyBoss object.
		if ( $this->is_buddyboss === true ) {
			$this->buddyboss = new CiviCRM_Profile_Sync_BP_BuddyBoss( $this );
		}

		// Bail if not developing BuddyPress xProfile sync.
		if ( CIVICRM_WP_PROFILE_SYNC_BUDDYPRESS === false ) {
			return;
		}

		// Init xProfile objects.
		$this->xprofile = new CiviCRM_Profile_Sync_BP_xProfile( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper callbacks.
		$this->register_mapper_hooks();

		// Listen for special BuddyPress situation.
		add_action( 'cwps/contact/name/should_be_synced', [ $this, 'name_update_allow' ], 1000 );

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Unregister Mapper callbacks.
		$this->unregister_mapper_hooks();

		// Do not listen for special BuddyPress situation.
		remove_action( 'cwps/contact/name/should_be_synced', [ $this, 'name_update_allow' ], 1000 );

	}

	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
			return;
		}

		// Callbacks for new and edited BuddyPress User actions.
		add_action( 'cwps/mapper/bp_xprofile/edited', [ $this, 'user_edited' ], 20 );
		add_action( 'cwps/mapper/bp_signup_user', [ $this, 'user_edited' ], 20 );
		add_action( 'cwps/mapper/bp_activated_user', [ $this, 'user_edited' ], 20 );

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_hooks === false ) {
			return;
		}

		// Remove callbacks for new and edited BuddyPress User actions.
		remove_action( 'cwps/mapper/bp_xprofile/edited', [ $this, 'user_edited' ], 20 );
		remove_action( 'cwps/mapper/bp_signup_user', [ $this, 'user_edited' ], 20 );
		remove_action( 'cwps/mapper/bp_activated_user', [ $this, 'user_edited' ], 20 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept BuddyPress's attempt to sync to WordPress User profile.
	 *
	 * @since 0.1
	 * @since 0.4 Moved to this class.
	 *
	 * @param array $args The array of BuddyPress params.
	 */
	public function user_edited( $args ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
			return;
		}

		// Fetch logged-in User if none set.
		if ( empty( $args['user_id'] ) ) {
			$args['user_id'] = bp_loggedin_user_id();
		}

		// Bail if no User ID found.
		if ( empty( $args['user_id'] ) ) {
			return;
		}

		// Pass to our sync method.
		$this->plugin->wp->user->user_edited( $args );

	}

	/**
	 * Check if a CiviCRM Contact's "First Name" and "Last Name" should be synced.
	 *
	 * @since 0.4
	 *
	 * @param bool $should_be_synced True if the Contact's name should be synced, false otherwise.
	 * @return bool $should_be_synced The modified value of the "should be synced" flag.
	 */
	public function name_update_allow( $should_be_synced ) {

		// Disallow if this is a BuddyPress User's "General Settings" update.
		if ( function_exists( 'bp_is_current_action' ) && bp_is_current_action( 'general' ) ) {
			$should_be_synced = false;
		}

		// --<
		return $should_be_synced;

	}

}
