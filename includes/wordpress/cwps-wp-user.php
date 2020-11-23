<?php
/**
 * WordPress User compatibility Class.
 *
 * Handles WordPress User integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM WordPress Profile Sync WordPress User compatibility Class.
 *
 * This class provides WordPress User integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_WordPress_User {

	/**
	 * Plugin object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $wp The parent object.
	 */
	public $wp;



	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 */
	public function __construct() {

		// Boot when plugin is loaded.
		add_action( 'cwps/wordpress/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function set_references( $parent ) {

		// Store plugin reference.
		$this->plugin = $parent->plugin;

		// Store WordPress object reference.
		$this->wp = $parent;

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper callbacks.
		$this->register_mapper_hooks();

	}



	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Unregister Mapper callbacks.
		$this->unregister_mapper_hooks();

	}



	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Callbacks for new and edited WordPress User actions.
		add_action( 'cwps/mapper/user_registered', [ $this, 'user_edited' ], 10 );
		add_action( 'cwps/mapper/user_edited', [ $this, 'user_edited' ], 10 );

	}



	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove callbacks for new and edited WordPress User actions.
		remove_action( 'cwps/mapper/user_registered', [ $this, 'user_edited' ], 10 );
		remove_action( 'cwps/mapper/user_edited', [ $this, 'user_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Updates a CiviCRM Contact when a WordPress User is edited.
	 *
	 * @since 0.1
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function user_edited( $args ) {

		// Get the User object.
		$user = get_userdata( $args['user_id'] );

		// Bail if we didn't get one.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Add User object to args.
		$args['user'] = $user;

		/**
		 * Allow plugins to know that the sync process is starting.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/wordpress/user_sync/pre', $args );

		// Create Contact if none exists and get the CiviCRM UFMatch object.
		$ufmatch = $this->plugin->civicrm->contact->contact_sync( $args['user'] );

		// Bail if we don't get one for some reason.
		if ( ! isset( $ufmatch->contact_id ) ) {
			return;
		}

		// Add UFMatch object to args.
		$args['ufmatch'] = $ufmatch;

		/**
		 * Broadcast that a WordPress User has been synced.
		 *
		 * Used internally by:
		 *
		 * - CiviCRM_WP_Profile_Sync_CiviCRM_Contact::name_update()
		 * - CiviCRM_WP_Profile_Sync_CiviCRM_Contact::nickname_update()
		 * - CiviCRM_WP_Profile_Sync_CiviCRM_Email::primary_update()
		 * - CiviCRM_WP_Profile_Sync_CiviCRM_Website::website_update()
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress and discovered params.
		 */
		do_action( 'cwps/wordpress/user_sync', $args );

		/**
		 * Allow plugins to hook into the sync process.
		 *
		 * Deprecated, please use "cwps/wordpress/user_sync/post" below.
		 *
		 * @since 0.2.4
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param object $ufmatch The array of CiviCRM UFMatch data.
		 */
		do_action( 'civicrm_wp_profile_sync_wp_user_sync', $user, $ufmatch );

		/**
		 * Broadcast that a WordPress User has been synced.
		 *
		 * Deprecated, please use "cwps/wordpress/user_sync/post" below.
		 *
		 * @since 0.2.4
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param object $ufmatch The array of CiviCRM UFMatch data.
		 */
		do_action( 'civicrm_wp_profile_sync_wp_user_synced', $user, $ufmatch );

		/**
		 * Broadcast that the WordPress User sync process is finished.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress and discovered params.
		 */
		do_action( 'cwps/wordpress/user_sync/post', $args );

	}



	/**
	 * Check if a WordPress User should by synced.
	 *
	 * @since 0.3
	 *
	 * @param object $user The WordPress User object.
	 * @param object $contact The CiviCRM Contact object.
	 * @return bool $should_be_synced Whether or not the User should be synced.
	 */
	public function user_should_be_synced( $user, $contact ) {

		// Assume User should be synced.
		$should_be_synced = true;

		/**
		 * Let other plugins override whether a WordPress User should be synced.
		 *
		 * @since 0.3
		 *
		 * @param bool $should_be_synced True if the User should be synced, false otherwise.
		 * @param object $user The WordPress User object.
		 * @param object $contact The CiviCRM Contact object.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'civicrm_wp_profile_sync_user_should_be_synced', $should_be_synced, $user, $contact );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a WordPress User's "First Name" and "Last Name" fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function name_update( $args ) {

		// Grab User and Contact.
		$user_id = $args['user_id'];
		$contact = $args['objectRef'];

		// Update "First Name".
		update_user_meta( $user_id, 'first_name', $contact->first_name );

		// Update "Last Name".
		update_user_meta( $user_id, 'last_name', $contact->last_name );

		// Compatibility with BP xProfile WordPress User Sync plugin.
		if ( defined( 'BP_XPROFILE_WP_USER_SYNC_VERSION' ) ) {

			// Access plugin object.
			global $bp_xprofile_wordpress_user_sync;

			// Call the relevant sync method.
			$bp_xprofile_wordpress_user_sync->intercept_wp_user_update( $user_id );

		}

	}



	/**
	 * Update a WordPress User's "Nickname" field.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function nickname_update( $args ) {

		// Grab User and Contact.
		$user_id = $args['user_id'];
		$contact = $args['objectRef'];

		// The WordPress User must have a nickname.
		if ( empty( $contact->nick_name ) ) {
			return;
		}

		// Update "Nickname".
		update_user_meta( $user_id, 'nickname', $contact->nick_name );

	}



	/**
	 * Update a WordPress User's Email address.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function email_update( $args ) {

		// Grab User ID and Email.
		$user_id = $args['user_id'];
		$email = $args['objectRef'];

		// Never overwrite with an empty Email address.
		if ( empty( $email->email ) ) {
			return;
		}

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->plugin->hooks_wp_remove();
		$this->plugin->hooks_bp_remove();

		// Build params.
		$params = [
			'ID' => $user_id,
			'user_email' => $email->email,
		];

		// Do the User update.
		wp_update_user( $params );

		// Restore WordPress and BuddyPress callbacks.
		$this->plugin->hooks_wp_add();
		$this->plugin->hooks_bp_add();

	}



	/**
	 * Update a WordPress User's Website URL.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_update( $args ) {

		// Grab User ID and Email.
		$user_id = $args['user_id'];
		$website = $args['objectRef'];

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->plugin->hooks_wp_remove();
		$this->plugin->hooks_bp_remove();

		// Build params.
		$params = [
			'ID' => $user_id,
			'user_url' => $website->url,
		];

		// Do the User update.
		wp_update_user( $params );

		// Restore WordPress and BuddyPress callbacks.
		$this->plugin->hooks_wp_add();
		$this->plugin->hooks_bp_add();

	}



} // Class ends.



