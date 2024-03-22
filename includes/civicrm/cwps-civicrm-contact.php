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
 * CiviCRM Profile Sync CiviCRM Contact compatibility Class.
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
	 * @var object
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
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

		// Store references to objects.
		$this->plugin  = $parent->plugin;
		$this->civicrm = $parent;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Always register plugin hooks.
		add_action( 'cwps/plugin/hooks/civicrm/add', [ $this, 'register_mapper_hooks' ] );
		add_action( 'cwps/plugin/hooks/civicrm/remove', [ $this, 'unregister_mapper_hooks' ] );

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
		remove_action( 'cwps/wordpress/user_sync', [ $this, 'nickname_update' ], 10 );

	}

	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( true === $this->mapper_hooks ) {
			return;
		}

		// Intercept Contact updates in CiviCRM.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_action( 'cwps/mapper/contact/edit/pre', [ $this, 'contact_pre' ], 10, 4 );
		add_action( 'cwps/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );

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
		if ( false === $this->mapper_hooks ) {
			return;
		}

		// Remove all CiviCRM callbacks.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// remove_action( 'cwps/mapper/contact/edit/pre', [ $this, 'contact_pre' ], 10 );
		remove_action( 'cwps/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function contact_pre( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( 'edit' !== $op ) {
			return;
		}

		// Target our object type.
		if ( 'Individual' !== $object_name ) {
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
		if ( false === $user_id ) {
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
		 * @param integer $object_id The ID of the CiviCRM Contact.
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
	 * Update a CiviCRM Contact when a WordPress User is edited.
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
		if ( ! $this->civicrm->is_initialised() ) {
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
	 * Check if a CiviCRM Contact should be synced.
	 *
	 * @since 0.3
	 *
	 * @param object  $contact The CiviCRM Contact object.
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
		$user    = $args['user'];
		$contact = $args['ufmatch'];

		// Should this Contact name be synced?
		if ( ! $this->name_should_be_synced( $user, $contact ) ) {
			return;
		}

		// Init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return;
		}

		/*
		// Are both "First Name" and "Last Name" required?
		if ( empty( $user->first_name ) || empty( $user->last_name ) ) {
			return;
		}
		*/

		// Update the CiviCRM Contact "First Name" and "Last Name".
		$params = [
			'version'    => 3,
			'id'         => $contact->contact_id,
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Contact', 'create', $params );

		// Log something on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'Could not update the name of the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
		}

	}

	/**
	 * Check if a CiviCRM Contact's name should be synced.
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
		$user    = $args['user'];
		$contact = $args['ufmatch'];

		// Should this Contact nickname be synced?
		if ( ! $this->nickname_should_be_synced( $user, $contact ) ) {
			return;
		}

		// Init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return;
		}

		// Update the CiviCRM Contact "First Name" and "Last Name".
		$params = [
			'version'   => 3,
			'id'        => $contact->contact_id,
			'nick_name' => $user->nickname,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Contact', 'create', $params );

		// Log something on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'Could not update the nickname of the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
		}

	}

	/**
	 * Check if a CiviCRM Contact's "Nickname" should be synced.
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

		// Check if our setting allows Nickname sync.
		$nickname_sync = $this->plugin->admin->setting_get( 'user_profile_nickname_sync', 1 );
		if ( 1 !== (int) $nickname_sync ) {
			$should_be_synced = false;
		}

		/**
		 * Let other plugins override whether a CiviCRM Contact's "Nickname" should be synced.
		 *
		 * @since 0.4
		 *
		 * @param bool $should_be_synced True if the Contact's "Nickname" should be synced, false otherwise.
		 * @param object $user The WordPress User object.
		 * @param object $contact The CiviCRM Contact object.
		 */
		return apply_filters( 'cwps/contact/nickname/should_be_synced', $should_be_synced, $user, $contact );

	}

	// -------------------------------------------------------------------------

	/**
	 * Create a CiviCRM Contact for a given set of data.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @return array|bool $contact_data The array Contact data from the CiviCRM API, or false on failure.
	 */
	public function create( $contact ) {

		// Init as failure.
		$contact_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_data;
		}

		// Build params to create Contact.
		$params = [
			'version' => 3,
			'debug'   => 1,
		] + $contact;

		/*
		 * Minimum array to create a Contact:
		 *
		 * $params = [
		 *   'version' => 3,
		 *   'contact_type' => "Individual",
		 *   'contact_sub_type' => "Student",
		 *   'display_name' => "John",
		 * ];
		 *
		 * Updates are triggered by:
		 *
		 * $params['id'] = 255;
		 *
		 * Custom Fields are addressed by ID:
		 *
		 * $params['custom_9'] = "Blah";
		 * $params['custom_7'] = 1;
		 * $params['custom_8'] = 0;
		 *
		 * CiviCRM kindly ignores any Custom Fields which are passed to it that
		 * aren't attached to the Entity. This is of significance when a Field
		 * Group is attached to multiple Post Types (for example) and the Fields
		 * refer to different Entities (e.g. "Parent" and "Student").
		 *
		 * Nice.
		 *
		 * It seems the Custom Field data is actually saved to the Contact - but
		 * it never shows up anywhere. Perhaps needs a deeper look to see what's
		 * going on under the hood in CiviCRM.
		 */

		// Call the API.
		$result = civicrm_api( 'Contact', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return $contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_data;
		}

		// The result set should contain only one item.
		$contact_data = array_pop( $result['values'] );

		// --<
		return $contact_data;

	}

	/**
	 * Update a CiviCRM Contact with a given set of data.
	 *
	 * This is an alias of `self::create()` except that we expect a
	 * Contact ID to have been set in the Contact data.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @return array|bool The array of Contact data from the CiviCRM API, or false on failure.
	 */
	public function update( $contact ) {

		// Log and bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'A numeric ID must be present to update a Contact.', 'civicrm-wp-profile-sync' ),
				'contact'   => $contact,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Pass through.
		return $this->create( $contact );

	}

	/**
	 * Delete a CiviCRM Contact for a given set of data.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @return array|bool $contact_data The array Contact data from the CiviCRM API, or false on failure.
	 */
	public function delete( $contact ) {

		// Init as failure.
		$contact_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_data;
		}

		// Log and bail if there's no Contact ID.
		if ( empty( $contact['id'] ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'A numeric ID must be present to delete a Contact.', 'civicrm-wp-profile-sync' ),
				'contact'   => $contact,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Build params to delete Contact.
		$params = [
			'version' => 3,
		] + $contact;

		// Call the API.
		$result = civicrm_api( 'Contact', 'delete', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_data;
		}

		// The result set should contain only one item.
		$contact_data = array_pop( $result['values'] );

		// --<
		return $contact_data;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Contact data for a given ID.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact to query.
	 * @return array|bool $contact_data An array of Contact data, or false on failure.
	 */
	public function get_by_id( $contact_id ) {

		// Init return.
		$contact_data = false;

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $contact_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_data;
		}

		// Define params to get queried Contact.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => $contact_id,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_data;
		}

		// The result set should contain only one item.
		$contact_data = array_pop( $result['values'] );

		// --<
		return $contact_data;

	}

	/**
	 * Get the CiviCRM Contact data for a set of given IDs.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param array $contact_ids The array of numeric IDs of the CiviCRM Contacts to query.
	 * @return array|bool $contact_data An array of Contact data, or false on failure.
	 */
	public function get_by_ids( $contact_ids = [] ) {

		// Init return.
		$contact_data = false;

		// Bail if we have no Contact IDs.
		if ( empty( $contact_ids ) ) {
			return $contact_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_data;
		}

		// Define params to get queried Contacts.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => [ 'IN' => $contact_ids ],
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_data;
		}

		// --<
		return $result['values'];

	}

	// -------------------------------------------------------------------------

	/**
	 * Get all CiviCRM Contact Types.
	 *
	 * @since 0.5
	 *
	 * @return array $all The flat array CiviCRM Contact Types.
	 */
	public function types_get_all() {

		// Only do this once.
		static $all;
		if ( ! empty( $all ) ) {
			return $all;
		}

		// Init return.
		$all = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $all;
		}

		// Define params to get all Contact Types.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'is_active'  => 1,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $all;
		}

		// Populate return array.
		if ( isset( $result['values'] ) && count( $result['values'] ) > 0 ) {
			$all = $result['values'];
		}

		// --<
		return $all;

	}

	/**
	 * Get a CiviCRM Contact Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_type_id The numeric ID of the CiviCRM Contact Type.
	 * @return array $contact_type The array of CiviCRM Contact Type data.
	 */
	public function type_get_by_id( $contact_type_id ) {

		// Init return.
		$contact_type = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_type;
		}

		// Define params to get all Contact Types.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => $contact_type_id,
			'is_active'  => 1,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $contact_type;
		}

		// Bail if there are none.
		if ( empty( $result['values'] ) ) {
			return $contact_type;
		}

		// Populate return array.
		$contact_type = array_pop( $result['values'] );

		// --<
		return $contact_type;

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
		if ( false === $contact ) {
			return;
		}

		// Check permission to view this Contact.
		if ( ! $this->user_can_view( $contact['id'] ) ) {
			return;
		}

		// Get the link to the Contact.
		$link = $this->plugin->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact['id'] );

		// Add menu item.
		$args = [
			'parent' => 'user-actions',
			'id'     => 'civicrm-profile',
			'title'  => __( 'CiviCRM Profile', 'civicrm-wp-profile-sync' ),
			'href'   => $link,
		];
		$wp_admin_bar->add_node( $args );

	}

	/**
	 * Check with CiviCRM that this Contact can be viewed.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The CiviCRM Contact ID to check.
	 * @return bool $permitted True if allowed, false otherwise.
	 */
	public function user_can_view( $contact_id ) {

		// Deny by default.
		$permitted = false;

		// Always deny if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return $permitted;
		}

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) {
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
		 * @param integer $contact_id The CiviCRM Contact ID.
		 */
		return apply_filters( 'cwps/contact/user_can_view', $permitted, $contact_id );

	}

}
