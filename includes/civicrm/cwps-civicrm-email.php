<?php
/**
 * CiviCRM Email compatibility Class.
 *
 * Handles CiviCRM Email integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Email compatibility Class.
 *
 * This class provides CiviCRM Email integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Email {

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

		// Store plugin reference.
		$this->plugin = $parent->plugin;

		// Store CiviCRM object reference.
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
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper callbacks.
		$this->register_mapper_hooks();

		// Prevent CiviCRM from syncing Primary Email with WordPress User.
		add_action( 'civicrm_postSave_civicrm_setting', [ $this, 'sync_setting_override' ], 10 );

		// Show a notice in CiviCRM's WordPress Settings.
		add_action( 'civicrm/metabox/email_sync/pre', [ $this, 'sync_setting_notice' ], 10 );
		add_action( 'civicrm/metabox/email_sync/post', [ $this, 'sync_setting_js' ], 10 );
		add_filter( 'civicrm/metabox/email_sync/submit/options', [ $this, 'sync_setting_button' ], 10 );

		// Listen for User sync.
		add_action( 'cwps/wordpress/user_sync', [ $this, 'primary_update' ], 10 );

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Unregister Mapper callbacks.
		$this->unregister_mapper_hooks();

		// Remove all other callbacks.
		remove_action( 'civicrm_postSave_civicrm_setting', [ $this, 'sync_setting_override' ], 10 );
		remove_action( 'cwps/wordpress/user_sync', [ $this, 'email_update' ], 10 );

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

		// Intercept Email updates in CiviCRM.
		add_action( 'cwps/mapper/email/edit/pre', [ $this, 'primary_pre' ], 10 );
		add_action( 'cwps/mapper/email/edited', [ $this, 'primary_edited' ], 10 );

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

		// Remove all CiviCRM callbacks.
		remove_action( 'cwps/mapper/email/edit/pre', [ $this, 'primary_pre' ], 10 );
		remove_action( 'cwps/mapper/email/edited', [ $this, 'primary_edited' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the current value of the CiviCRM "Sync CMS Email" setting.
	 *
	 * @since 0.4
	 *
	 * @return bool $value Numeric 1 or 0 depending on the CiviCRM setting, or false otherwise.
	 */
	public function sync_setting_get() {

		// Init return.
		$value = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $value;
		}

		// Get setting value.
		$params = [
			'version' => 3,
			'name' => 'syncCMSEmail',
			'group' => 'CiviCRM Preferences',
		];

		// Return the setting.
		$result = civicrm_api( 'Setting', 'getvalue', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $value;
		}

		// Overwrite value with result.
		$value = $result ? 1 : 0;

		// Return the setting.
		return $value;

	}

	/**
	 * Set the CiviCRM "Sync CMS Email" setting.
	 *
	 * @since 0.4
	 *
	 * @param bool $value The value to apply to the setting.
	 */
	public function sync_setting_set( $value = false ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return;
		}

		// Switch setting to false.
		$params = [
			'version' => 3,
			'syncCMSEmail' => $value,
		];

		// Save the setting.
		$result = civicrm_api( 'Setting', 'create', $params );

	}

	/**
	 * Set the CiviCRM "Sync CMS Email" setting without listening.
	 *
	 * @since 0.4
	 *
	 * @param bool $value The value to apply to the setting.
	 */
	public function sync_setting_force( $value = false ) {

		// Remove our hook.
		remove_action( 'civicrm_postSave_civicrm_setting', [ $this, 'sync_setting_override' ], 10 );

		// Override.
		$this->sync_setting_set( $value );

		// Reinstate our hook.
		add_action( 'civicrm_postSave_civicrm_setting', [ $this, 'sync_setting_override' ], 10 );

	}

	/**
	 * Prevent CiviCRM from syncing Primary Email with WordPress User.
	 *
	 * When CiviCRM settings are saved, this method is called post-save. If the
	 * "Sync CMS Email" setting isn't what we want it to be, override it.
	 *
	 * @since 0.4
	 *
	 * @param obj $dao The CiviCRM database access object.
	 */
	public function sync_setting_override( $dao ) {

		// Bail if not a setting.
		if ( ! ( $dao instanceof CRM_Core_DAO_Setting ) ) {
			return;
		}

		// Bail if not the "Sync CMS Email" setting.
		if ( ! isset( $dao->name ) && $dao->name != 'syncCMSEmail' ) {
			return;
		}

		// Bail if setting is already "off".
		if ( isset( $dao->value ) && '1' != maybe_unserialize( $dao->value ) ) {
			return;
		}

		// Bail if our setting allows CiviCRM to handle Primary Email sync.
		$email_sync = $this->plugin->admin->setting_get( 'user_profile_email_sync', 2 );
		if ( $email_sync !== 1 ) {
			return;
		}

		// Override.
		$this->sync_setting_force( false );

	}

	/**
	 * Show a notice on the CiviCRM Settings "Email Sync" metabox.
	 *
	 * @since 0.4
	 */
	public function sync_setting_notice() {

		// Bail if our setting allows CiviCRM to handle Primary Email sync.
		$email_sync = $this->plugin->admin->setting_get( 'user_profile_email_sync', 2 );
		if ( $email_sync !== 1 ) {
			return;
		}

		// Let people know.
		echo '<div class="notice notice-warning inline" style="background-color: #f7f7f7;">
			<p>' . esc_html__( 'CiviCRM Profile Sync is managing Contact Email &rarr; User Email sync.', 'civicrm-wp-profile-sync' ) . '</p>
		</div>';

	}

	/**
	 * Add some Javascript on the CiviCRM Settings "Email Sync" metabox.
	 *
	 * @since 0.4
	 */
	public function sync_setting_js() {

		// Bail if our setting allows CiviCRM to handle Primary Email sync.
		$email_sync = $this->plugin->admin->setting_get( 'user_profile_email_sync', 2 );
		if ( $email_sync !== 1 ) {
			return;
		}

		// Disable the dropdown.
		echo '<script type="text/javascript">
			jQuery("#sync_email").prop("disabled", true);
		</script>';

	}

	/**
	 * Filter the "Email Sync" submit button attributes.
	 *
	 * @since 0.4
	 *
	 * @param array $options The existing button attributes.
	 * @return array $options The modified button attributes.
	 */
	public function sync_setting_button( $options ) {

		// Bail if our setting allows CiviCRM to handle Primary Email sync.
		$email_sync = $this->plugin->admin->setting_get( 'user_profile_email_sync', 2 );
		if ( $email_sync !== 1 ) {
			return $options;
		}

		// Disable the submit button.
		$options['disabled'] = null;

		// --<
		return $options;

	}

	// -------------------------------------------------------------------------

	/**
	 * Creates a CiviCRM Email record.
	 *
	 * @since 0.6.1
	 *
	 * @param array $email The array of CiviCRM Email data.
	 * @return array|bool $email_data The array of Email data, or false on failure.
	 */
	public function create( $email ) {

		// Init return.
		$email_data = false;

		// Try and initialise CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email_data;
		}

		// Build params to create Email.
		$params = [
			'version' => 3,
		] + $email;

		// Call the CiviCRM API.
		$result = civicrm_api( 'Email', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $email;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email_data;
		}

		// The result set should contain only one item.
		$email_data = array_pop( $result['values'] );

		// --<
		return $email_data;

	}

	/**
	 * Updates a CiviCRM Email record.
	 *
	 * This is an alias of `self::email_create()` except that we expect an ID
	 * to have been set in the Email data.
	 *
	 * @since 0.6.1
	 *
	 * @param array $email The array of CiviCRM ACL data.
	 * @return array|bool The array of Email data from the CiviCRM API, or false on failure.
	 */
	public function update( $email ) {

		// Log and bail if there's no ID.
		if ( empty( $email['id'] ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'An ID must be present to edit an Email.', 'civicrm-wp-profile-sync' ),
				'email' => $email,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Pass through.
		return $this->create( $email );

	}

	/**
	 * Deletes a CiviCRM Email record.
	 *
	 * @since 0.6.1
	 *
	 * @param integer $email_id The numeric ID of the CiviCRM Email.
	 * @return bool $success True if the operation was successful, false on failure.
	 */
	public function delete( $email_id ) {

		// Init as failure.
		$success = false;

		// Try and initialise CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Log and bail if there's no Email ID.
		if ( empty( $email_id ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'An ID must be present to delete an Email.', 'civicrm-wp-profile-sync' ),
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Build params to delete Email.
		$params = [
			'version' => 3,
			'id' => $email_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Email', 'delete', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $success;
		}

		// Success.
		$success = true;

		// --<
		return $success;

	}

	// -------------------------------------------------------------------------

	/**
	 * Listens for when a CiviCRM Contact's Primary Email address is about to be edited.
	 *
	 * @see CRM_Core_BAO_Email::add()
	 *
	 * @since 0.1
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function primary_pre( $args ) {

		// Get full Email record being edited.
		$primary_email = $this->primary_record_get_by_id( $args['objectId'] );

		// Sanity check.
		if ( $primary_email === false ) {
			return;
		}

		// Bail if it isn't the Primary Email.
		if ( $primary_email->is_primary != 1 ) {
			return;
		}

		// Bail if it isn't a Contact Email.
		if ( empty( $primary_email->contact_id ) ) {
			return;
		}

		// If our setting allows CiviCRM to handle Primary Email sync.
		$email_sync = $this->plugin->admin->setting_get( 'user_profile_email_sync', 2 );
		if ( $email_sync !== 1 ) {

			// Remove WordPress and BuddyPress callbacks to prevent recursion.
			$this->plugin->hooks_wp_remove();
			$this->plugin->hooks_bp_remove();

		}

		/**
		 * Fires when a CiviCRM Contact's Primary Email address is about to be
		 * edited.
		 *
		 * The change of email in WordPress causes a notification email to be
		 * sent to the WordPress User. This can be suppressed using this action
		 * as the trigger to do so.
		 *
		 * @since 0.2.7
		 *
		 * @param integer $objectId The ID of the object.
		 * @param object $objectRef The object.
		 */
		do_action( 'civicrm_wp_profile_sync_primary_email_pre_update', $args['objectId'], $args['objectRef'] );

	}

	/**
	 * Listens for when a CiviCRM Contact's Primary Email address has been edited.
	 *
	 * @see CRM_Core_BAO_Email::add()
	 *
	 * @since 0.1
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function primary_edited( $args ) {

		// Bail if our setting allows CiviCRM to handle Primary Email sync.
		$email_sync = $this->plugin->admin->setting_get( 'user_profile_email_sync', 2 );
		if ( $email_sync !== 1 ) {
			return;
		}

		// Get full Email record being edited.
		$primary_email = $this->primary_record_get_by_id( $args['objectId'] );

		// Sanity check.
		if ( $primary_email === false ) {
			return;
		}

		// Bail if it isn't the Primary Email.
		if ( empty( $primary_email->is_primary ) || $primary_email->is_primary != 1 ) {
			return;
		}

		// Bail if it isn't a Contact Email.
		if ( empty( $primary_email->contact_id ) ) {
			return;
		}

		// Get the WordPress User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $primary_email->contact_id );

		// Kick out if we didn't get one.
		if ( empty( $user_id ) ) {
			return;
		}

		// Add User ID to args.
		$args['user_id'] = $user_id;

		// Update the WordPress User's Email.
		$this->plugin->wp->user->email_update( $args );

		/**
		 * Broadcast that a CiviCRM Contact's Email has been synced.
		 *
		 * Used internally by:
		 *
		 * * CiviCRM_WP_Profile_Sync_Mapper_UFMatch::entries_update()
		 *
		 * @since 0.4
		 *
		 * @param integer $user_id The ID of the WordPress User.
		 * @param integer $objectId The ID of the CiviCRM Email.
		 * @param object $objectRef The CiviCRM Email object.
		 */
		do_action( 'civicrm_wp_profile_sync_primary_email_synced', $user_id, $args['objectId'], $args['objectRef'] );

	}

	/**
	 * Update a CiviCRM Contact's Primary Email address.
	 *
	 * This is called when a WordPress User is updated. Users must have an Email
	 * Address, so we do not need to account for it being empty.
	 *
	 * @since 0.1
	 * @since 0.4 Params reduced to single array.
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function primary_update( $args ) {

		// Grab User and Contact.
		$user = $args['user'];
		$contact = $args['ufmatch'];

		// Get Primary Email record.
		$primary_email = $this->primary_record_get_by_contact_id( $contact->contact_id );

		// If there isn't a current Primary Email.
		if ( $primary_email === false ) {

			/*
			 * Construct params to create a Primary Email record.
			 *
			 * When we do not specify a Location Type, the API will create one
			 * with the default Location Type.
			 */
			$params = [
				'contact_id' => $contact->contact_id,
				'email' => $user->user_email,
				'is_primary' => 1,
			];

			// Create it.
			$email = $this->create( $params );

		} else {

			// Only update if the Email has changed.
			if ( $primary_email->email != $user->user_email ) {

				/*
				 * Construct params to update the Email record.
				 *
				 * We need to specify a Location Type so that the API keeps the
				 * existing Location Type rather than reverting to the default
				 * Location Type.
				 */
				$params = [
					'id' => $primary_email->id,
					'location_type_id' => $primary_email->location_type_id,
					'contact_id' => $contact->contact_id,
					'email' => $user->user_email,
					'is_primary' => 1,
				];

				// Update it.
				$email = $this->update( $params );

			}

		}

		// Bail if we have no Email object.
		if ( empty( $email ) ) {
			return;
		}

		// Cast Email as object.
		$email = (object) $email;

		// Prepare params.
		$user_id = $user->ID;
		$email_id = $email->id;

		/**
		 * Fires when a WordPress User's Email has been synced to a CiviCRM
		 * Contact's Primary Email record.
		 *
		 * Used internally by:
		 *
		 * * CiviCRM_WP_Profile_Sync_Mapper_UFMatch::entries_update()
		 *
		 * @since 0.6.1
		 *
		 * @param integer $user_id The ID of the WordPress User.
		 * @param integer $email_id The ID of the CiviCRM Email.
		 * @param object $email The CiviCRM Email object.
		 */
		do_action( 'cwps/civicrm/email/primary_updated', $user_id, $email_id, $email );

	}

	// -------------------------------------------------------------------------

	/**
	 * Get a CiviCRM Email record by its ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $email_id The numeric ID of the CiviCRM Email record.
	 * @return object|bool $email The CiviCRM Email record, or false on failure.
	 */
	public function primary_record_get_by_id( $email_id ) {

		// Init return.
		$email = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Get the current Primary Email.
		$params = [
			'version' => 3,
			'id' => $email_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			return $email;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = (object) array_pop( $result['values'] );

		// --<
		return $email;

	}

	/**
	 * Get a CiviCRM Contact's Primary Email record.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return object|bool $email The CiviCRM Email record, or false on failure.
	 */
	public function primary_record_get_by_contact_id( $contact_id ) {

		// Init return.
		$email = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Get the current Primary Email.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'is_primary' => 1,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			return $email;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = (object) array_pop( $result['values'] );

		// --<
		return $email;

	}

}
