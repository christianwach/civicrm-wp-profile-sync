<?php
/**
 * BuddyBoss Compatibility Class.
 *
 * Handles BuddyBoss compatibility.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync BuddyBoss Class.
 *
 * A class that encapsulates BuddyBoss functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_BuddyBoss {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * BuddyPress Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $bp_loader;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $bp_loader The BuddyPress Loader object.
	 */
	public function __construct( $bp_loader ) {

		// Store references to objects.
		$this->plugin = $bp_loader->plugin;
		$this->bp_loader = $bp_loader;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/buddypress/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.5
		 */
		do_action( 'cwps/buddypress/buddyboss/loaded' );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Hook into CiviCRM-to-WordPress Contact sync process.
		add_action( 'cwps/civicrm/contact/contact_sync/post', [ $this, 'contact_synced' ], 20 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Trigger the sync process to populate the core BuddyBoss xProfile Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_synced( $args ) {

		// Check if our setting allows Nickname sync.
		$nickname_sync = $this->plugin->admin->setting_get( 'user_profile_nickname_sync', 1 );

		// Prevent sync if not.
		if ( $nickname_sync !== 1 ) {
			add_filter( 'bp_xprofile_nickname_field_id', [ $this, 'nickname_sync_prevent' ] );
		}

		// Trigger the sync process.
		bp_xprofile_sync_bp_profile( $args['user_id'] );

		// Reinstate normal operation.
		if ( $nickname_sync !== 1 ) {
			remove_filter( 'bp_xprofile_nickname_field_id', [ $this, 'nickname_sync_prevent' ] );
		}

	}

	/**
	 * Prevents Nickname sync to BuddyBoss.
	 *
	 * @since 0.5
	 *
	 * @param integer $field_id The numeric ID of the BuddyBoss Nickname Field.
	 * @return integer Set to "0" to prevent Nickname sync.
	 */
	public function nickname_sync_prevent( $field_id ) {
		return 0;
	}

}
