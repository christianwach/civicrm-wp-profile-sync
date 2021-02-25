<?php
/**
 * CiviCRM Contact compatibility Class.
 *
 * Handles CiviCRM Contact integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM WordPress Profile Sync CiviCRM Contact compatibility Class.
 *
 * This class provides CiviCRM Contact integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Contact {

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
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * Top-level Contact Types which can be mapped.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $top_level_types The top level CiviCRM Contact Types.
	 */
	public $top_level_types = [
		'Individual',
		'Household',
		'Organization',
	];



	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 */
	public function __construct() {

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/civicrm/loaded', [ $this, 'initialise' ] );

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

		// Store CiviCRM object reference.
		$this->civicrm = $parent;

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
	 * Register CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper callbacks.
		$this->register_mapper_hooks();

		// Disable the CiviCRM-WordPress plugin's attempts to sync.
		add_action( 'init', [ $this, 'hooks_core_remove' ], 100 );

		// Listen for User sync.
		add_action( 'cwps/wordpress/user_sync', [ $this, 'name_update' ], 10 );
		add_action( 'cwps/wordpress/user_sync', [ $this, 'nickname_update' ], 10 );

		// Add "CiviCRM Profile" link to admin menu.
		add_action( 'admin_bar_menu', [ $this, 'menu_link_add' ], 1 );

	}



	/**
	 * Unregister CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Unregister Mapper callbacks.
		$this->unregister_mapper_hooks();

		// Remove all other callbacks.
		remove_action( 'init', [ $this, 'hooks_core_remove' ], 100 );
		remove_action( 'cwps/wordpress/user_sync', [ $this, 'name_update' ], 10 );

	}



	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Intercept Contact updates in CiviCRM.
		//add_action( 'cwps/mapper/contact_pre_edit', [ $this, 'contact_pre' ], 10, 4 );
		add_action( 'cwps/mapper/contact_edited', [ $this, 'contact_edited' ], 10 );

	}



	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all CiviCRM callbacks.
		//remove_action( 'cwps/mapper/contact_pre_edit', [ $this, 'contact_pre' ], 10 );
		remove_action( 'cwps/mapper/contact_edited', [ $this, 'contact_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Add back CiviCRM's callbacks.
	 *
	 * This method undoes the removal of the callbacks below.
	 *
	 * @see self::hooks_core_remove()
	 *
	 * @since 0.3.1
	 * @since 0.4 Moved to this class.
	 */
	public function hooks_core_add() {

		// Get CiviCRM instance.
		$civicrm = civi_wp();

		// Do we have the old-style plugin structure?
		if ( method_exists( $civicrm, 'update_user' ) ) {

			// Re-add previous CiviCRM plugin filters.
			add_action( 'user_register', [ $civicrm, 'update_user' ] );
			add_action( 'profile_update', [ $civicrm, 'update_user' ] );

		} else {

			// Re-add current CiviCRM plugin filters.
			add_action( 'user_register', [ $civicrm->users, 'update_user' ] );
			add_action( 'profile_update', [ $civicrm->users, 'update_user' ] );

		}

		/**
		 * Let other plugins know that we're adding user actions.
		 *
		 * @since 0.3.1
		 */
		do_action( 'civicrm_wp_profile_sync_hooks_core_added' );

	}



	/**
	 * Remove CiviCRM's callbacks.
	 *
	 * These may cause recursive updates when creating or editing a WordPress
	 * user. This doesn't seem to have been necessary in the past, but seems
	 * to be causing trouble when newer versions of BuddyPress and CiviCRM are
	 * active.
	 *
	 * @since 0.3.1
	 * @since 0.4 Moved to this class.
	 */
	public function hooks_core_remove() {

		// Get CiviCRM instance.
		$civicrm = civi_wp();

		// Do we have the old-style plugin structure?
		if ( method_exists( $civicrm, 'update_user' ) ) {

			// Remove previous CiviCRM plugin filters.
			remove_action( 'user_register', [ $civicrm, 'update_user' ] );
			remove_action( 'profile_update', [ $civicrm, 'update_user' ] );

		} else {

			// Remove current CiviCRM plugin filters.
			remove_action( 'user_register', [ $civicrm->users, 'update_user' ] );
			remove_action( 'profile_update', [ $civicrm->users, 'update_user' ] );

		}

		/**
		 * Let other plugins know that we're removing CiviCRM's callbacks.
		 *
		 * @since 0.3.1
		 */
		do_action( 'civicrm_wp_profile_sync_hooks_core_removed' );

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires when a CiviCRM Contact is edited, but prior to any operations taking place.
	 *
	 * This is used as a means by which to discover the direction of the update, because
	 * if the update is initiated from the WordPress side, this callback will have been
	 * unhooked and will not be called.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_pre( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Individual' ) {
			return;
		}

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->plugin->hooks_wp_remove();
		$this->plugin->hooks_bp_remove();

	}



	/**
	 * Update a WordPress User when a CiviCRM Contact is edited.
	 *
	 * @since 0.1
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_edited( $args ) {

		// Grab the Contact.
		$contact = $args['objectRef'];

		// Get the WordPress User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $args['objectId'] );

		// Bail if we didn't get one.
		if ( $user_id === false ) {
			return;
		}

		// Should this Contact be synced?
		if ( ! $this->contact_should_be_synced( $contact, $user_id ) ) {
			return;
		}

		// Remove CiviCRM's own callbacks.
		$this->hooks_core_remove();

		// Add User ID to the array of CiviCRM params.
		$args['user_id'] = $user_id;

		/**
		 * Allow plugins to know that the sync process is starting.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/civicrm/contact/contact_sync/pre', $args );

		 // TODO: It seems overkill to update the User twice here.

		 // Update the WordPress User.
		 $this->plugin->wp->user->name_update( $args );
		 $this->plugin->wp->user->nickname_update( $args );

		/**
		 * Allow plugins to hook into the sync process.
		 *
		 * Deprecated: Use "cwps/civicrm/contact/contact_sync" instead.
		 *
		 * @since 0.2.4
		 *
		 * @param integer $objectId The ID of the CiviCRM Contact.
		 * @param object $contact The CiviCRM Contact object.
		 * @param integer $user_id The ID of the WordPress User.
		 */
		do_action( 'civicrm_wp_profile_sync_civi_contact_synced', $args['objectId'], $contact, $user_id );

		/**
		 * Allow plugins to hook into the sync process.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/civicrm/contact/contact_sync', $args );

		// Add back CiviCRM's own callbacks.
		$this->hooks_core_add();

		/**
		 * Broadcast that a CiviCRM Contact has been synced.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/civicrm/contact/contact_sync/post', $args );

	}



	/**
	 * Update a WordPress User when a CiviCRM Contact is edited.
	 *
	 * @since 0.1
	 *
	 * @param WP_User $user The WordPress User object.
	 * @return object|bool $ufmatch The array of CiviCRM UFMatch data, or false on failure.
	 */
	public function contact_sync( $user ) {

		// Init return.
		$ufmatch = false;

		// Sanity check.
		if ( ! ( $user instanceof WP_User ) ) {
			return $ufmatch;
		}

		// Init CiviCRM.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $ufmatch;
		}

		// Get the current UFMatch entry.
		$entry = $this->plugin->mapper->ufmatch->entry_get_by_user_id( $user->ID );

		// Should this User be synced?
		if ( ! $this->plugin->wp->user->user_should_be_synced( $user, $entry ) ) {
			return $ufmatch;
		}

		// Make sure UFMatch file is loaded.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Create Contact if none exists - returns the CiviCRM UFMatch object.
		$ufmatch = CRM_Core_BAO_UFMatch::synchronizeUFMatch(
			$user, // User object.
			$user->ID, // WordPress User ID.
			$user->user_email, // Unique identifier.
			'WordPress', // CMS.
			null, // Status (anything but null will return a success boolean).
			'Individual' // Contact Type.
		);

		// Bail if we don't get one for some reason.
		if ( ! isset( $ufmatch->contact_id ) ) {
			return false;
		}

		// --<
		return $ufmatch;

	}



	/**
	 * Check if a CiviCRM Contact should by synced.
	 *
	 * @since 0.3
	 *
	 * @param object $contact The CiviCRM Contact object.
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @return bool $should_be_synced Whether or not the Contact should be synced.
	 */
	public function contact_should_be_synced( $contact, $user_id ) {

		// Assume Contact should be synced.
		$should_be_synced = true;

		/**
		 * Let other plugins override whether a CiviCRM Contact should be synced.
		 *
		 * Deprecated: use "cwps/contact/should_be_synced" instead.
		 *
		 * @since 0.3
		 *
		 * @param bool $should_be_synced True if the Contact should be synced, false otherwise.
		 * @param object $contact The CiviCRM Contact object.
		 * @param integer $user_id The numeric ID of the WordPress User.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		$should_be_synced = apply_filters( 'civicrm_wp_profile_sync_contact_should_be_synced', $should_be_synced, $contact, $user_id );

		/**
		 * Let other plugins override whether a CiviCRM Contact should be synced.
		 *
		 * @since 0.4
		 *
		 * @param bool $should_be_synced True if the Contact should be synced, false otherwise.
		 * @param object $contact The CiviCRM Contact object.
		 * @param integer $user_id The numeric ID of the WordPress User.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'cwps/contact/should_be_synced', $should_be_synced, $contact, $user_id );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact's "First Name" and "Last Name".
	 *
	 * @since 0.1
	 * @since 0.4 Params reduced to single array.
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function name_update( $args ) {

		// Grab User and Contact.
		$user = $args['user'];
		$contact = $args['ufmatch'];

		// Should this Contact name be synced?
		if ( ! $this->name_should_be_synced( $user, $contact ) ) {
			return;
		}

		// Update the CiviCRM Contact "First Name" and "Last Name".
		$params = [
			'version' => 3,
			'id' => $contact->contact_id,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Contact', 'create', $params );

        // Log something on failure.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			$e = new \Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'Could not update the name of the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
		}

	}



	/**
	 * Check if a CiviCRM Contact's name should by synced.
	 *
	 * @since 0.3
	 *
	 * @param object $user The WordPress User object.
	 * @param object $contact The CiviCRM Contact object.
	 * @return bool $should_be_synced Whether or not the Contact's name should be synced.
	 */
	public function name_should_be_synced( $user, $contact ) {

		// Assume a Contact's name should be synced.
		$should_be_synced = true;

		/**
		 * Let other plugins override whether a CiviCRM Contact's name should be synced.
		 *
		 * Deprecated: use "cwps/contact/name/should_be_synced" instead.
		 *
		 * @since 0.3
		 *
		 * @param bool $should_be_synced True if the Contact's name should be synced, false otherwise.
		 * @param object $user The WordPress User object.
		 * @param object $contact The CiviCRM Contact object.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		$should_be_synced = apply_filters( 'civicrm_wp_profile_sync_contact_name_should_be_synced', $should_be_synced, $user, $contact );

		/**
		 * Let other plugins override whether a CiviCRM Contact's name should be synced.
		 *
		 * @since 0.4
		 *
		 * @param bool $should_be_synced True if the Contact's name should be synced, false otherwise.
		 * @param object $user The WordPress User object.
		 * @param object $contact The CiviCRM Contact object.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'cwps/contact/name/should_be_synced', $should_be_synced, $user, $contact );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact's "Nickname".
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function nickname_update( $args ) {

		// Grab User and Contact.
		$user = $args['user'];
		$contact = $args['ufmatch'];

		// Should this Contact nickname be synced?
		if ( ! $this->nickname_should_be_synced( $user, $contact ) ) {
			return;
		}

		// Update the CiviCRM Contact "First Name" and "Last Name".
		$params = [
			'version' => 3,
			'id' => $contact->contact_id,
			'nick_name' => $user->nickname,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Contact', 'create', $params );

        // Log something on failure.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			$e = new \Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'Could not update the nickname of the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
		}

	}



	/**
	 * Check if a CiviCRM Contact's "Nickname" should by synced.
	 *
	 * @since 0.4
	 *
	 * @param object $user The WordPress User object.
	 * @param object $contact The CiviCRM Contact object.
	 * @return bool $should_be_synced Whether or not the Contact's "Nickname" should be synced.
	 */
	public function nickname_should_be_synced( $user, $contact ) {

		// Assume a Contact's "Nickname" should be synced.
		$should_be_synced = true;

		/**
		 * Let other plugins override whether a CiviCRM Contact's "Nickname" should be synced.
		 *
		 * @since 0.4
		 *
		 * @param bool $should_be_synced True if the Contact's "Nickname" should be synced, false otherwise.
		 * @param object $user The WordPress User object.
		 * @param object $contact The CiviCRM Contact object.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'cwps/contact/nickname/should_be_synced', $should_be_synced, $user, $contact );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a Contact's Details.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array|bool $contact The array of Contact data, or false if none.
	 */
	public function get_by_id( $contact_id ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $contact_id,
		];

		// Get Contact details via API.
		$contact = civicrm_api( 'Contact', 'getsingle', $params );

		// Log and bail on failure.
		if ( isset( $contact['is_error'] ) AND $contact['is_error'] == '1' ) {
			$e = new \Exception;
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_id' => $contact_id,
				'params' => $params,
				'contact' => $contact,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $contact;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all top-level CiviCRM Contact Types.
	 *
	 * @since 0.4
	 *
	 * @return array $top_level_types The top level CiviCRM Contact Types.
	 */
	public function types_get_top_level() {

		// --<
		return $this->top_level_types;

	}



	// -------------------------------------------------------------------------



	/**
	 * Add a link to the Contact View screen to the WordPress admin menu.
	 *
	 * @since 0.4
	 */
	public function menu_link_add() {

		// Access WordPress admin bar.
		global $wp_admin_bar;

		// Get current user.
		$user = wp_get_current_user();

	    // Sanity check.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Does this User have a Contact?
		$contact = $this->plugin->mapper->ufmatch->contact_get_by_user_id( $user->ID );

		// Bail if there isn't one.
		if ( $contact === false ) {
			return;
		}

		// Check permission to view this Contact.
		if ( ! $this->user_can_view( $contact['id'] ) ) {
			return;
		}

		// Get the link to the Contact.
		$link = $this->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact['id'] );

		// Add menu item.
		$wp_admin_bar->add_node( [
			'parent' => 'user-actions',
			'id'     => 'civicrm-profile',
			'title'  => __( 'CiviCRM Profile', 'civicrm-wp-profile-sync' ),
			'href'   => $link,
		] );


	}



	/**
	 * Check with CiviCRM that this Contact can be viewed.
	 *
	 * @since 0.4
	 *
	 * @param int $contact_id The CiviCRM Contact ID to check.
	 * @return bool $permitted True if allowed, false otherwise.
	 */
	public function user_can_view( $contact_id ) {

		// Deny by default.
		$permitted = false;

		// Always deny if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $permitted;
		}

		// Check with CiviCRM that this Contact can be viewed.
		if ( CRM_Contact_BAO_Contact_Permission::allow( $contact_id, CRM_Core_Permission::VIEW ) ) {
			$permitted = true;
		}

		/**
		 * Return permission but allow overrides.
		 *
		 * @since 0.3
		 *
		 * @param bool $permitted True if allowed, false otherwise.
		 * @param int $contact_id The CiviCRM Contact ID.
		 * @return bool $permitted True if allowed, false otherwise.
		 */
		return apply_filters( 'cwps/contact/user_can_view', $permitted, $contact_id );

	}



} // Class ends.



