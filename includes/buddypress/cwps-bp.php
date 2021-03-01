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
	 * Initialises this object.
	 *
	 * @since 0.4
	 */
	public function __construct() {

		// Boot when plugin is loaded.
		add_action( 'civicrm_wp_profile_sync_init', [ $this, 'initialise' ] );

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function set_references( $parent ) {

		// Store reference.
		$this->plugin = $parent;

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
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper callbacks.
		$this->register_mapper_hooks();

		// Listen for special BuddyPress situation.
		add_action( 'civicrm_wp_profile_sync_contact_name_should_be_synced', [ $this, 'name_update_allow' ], 1000 );

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
		remove_action( 'civicrm_wp_profile_sync_contact_name_should_be_synced', [ $this, 'name_update_allow' ], 1000 );

	}



	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Callbacks for new and edited BuddyPress User actions.
		add_action( 'cwps/mapper/bp_xprofile_edited', [ $this, 'user_edited' ], 20 );
		add_action( 'cwps/mapper/bp_signup_user', [ $this, 'user_edited' ], 20 );
		add_action( 'cwps/mapper/bp_activated_user', [ $this, 'user_edited' ], 20 );

	}



	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove callbacks for new and edited BuddyPress User actions.
		remove_action( 'cwps/mapper/bp_xprofile_edited', [ $this, 'user_edited' ], 20 );
		remove_action( 'cwps/mapper/bp_signup_user', [ $this, 'user_edited' ], 20 );
		remove_action( 'cwps/mapper/bp_activated_user', [ $this, 'user_edited' ], 20 );

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
		if ( ! bp_disable_profile_sync() ) {
			return true;
		}

		// Fetch logged-in User if none set.
		if ( empty( $args['user_id'] ) ) {
			$args['user_id'] = bp_loggedin_user_id();
		}

		// Bail if no User ID found.
		if ( empty( $args['user_id'] ) ) {
			return false;
		}

		// Pass to our sync method.
		$this->plugin->wp->user_edited( $args );

	}



	/**
	 * Check if a CiviCRM Contact's "First Name" and "Last Name" should be synced.
	 *
	 * @since 0.4
	 *
	 * @param bool $should_be_synced True if the Contact's name should be synced, false otherwise.
	 */
	public function name_update_allow( $should_be_synced ) {

		// Disallow if this is a BuddyPress General Settings update.
		if ( function_exists( 'bp_is_current_action' ) AND bp_is_current_action( 'general' ) ) {
			return false;
		}

		// --<
		return $should_be_synced;

	}



} // Class ends.



