<?php
/**
 * CiviCRM Website compatibility Class.
 *
 * Handles CiviCRM Website integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Website compatibility Class.
 *
 * This class provides CiviCRM Website integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Website {

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
	 * Register CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper callbacks.
		$this->register_mapper_hooks();

		// Listen for User sync.
		add_action( 'cwps/wordpress/user_sync', [ $this, 'website_update' ], 10 );

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
		remove_action( 'cwps/wordpress/user_sync', [ $this, 'website_update' ], 10 );

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

		// Intercept Website updates in CiviCRM.
		add_action( 'cwps/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//add_action( 'cwps/mapper/website/delete/pre', [ $this, 'website_pre_delete' ], 10 );
		add_action( 'cwps/mapper/website/created', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/mapper/website/deleted', [ $this, 'website_deleted' ], 10 );

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
		remove_action( 'cwps/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//remove_action( 'cwps/mapper/website/delete/pre', [ $this, 'website_pre_delete' ], 10 );
		remove_action( 'cwps/mapper/website/created', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/mapper/website/deleted', [ $this, 'website_deleted' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Fires when a CiviCRM Contact's Website is about to be edited.
	 *
	 * We need to check if an existing Website's Website Type is being changed,
	 * so we store the previous Website record here for comparison later.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_edit( $args ) {

		// We need a Contact ID in the edited Website.
		$website = $args['objectRef'];
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Always clear properties if set previously.
		if ( isset( $this->pre_edit ) ) {
			unset( $this->pre_edit );
		}

		// Grab the previous Website data from the database.
		$this->pre_edit = (object) $this->get_by_id( $website->id );

	}

	/**
	 * Updates a WordPress User when a CiviCRM Contact's Website is edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_edited( $args ) {

		// Bail if we have no Contact ID.
		if ( ! isset( $args['objectRef']->contact_id ) ) {
			return;
		}

		// Bail if we have no Website Type ID.
		if ( ! isset( $args['objectRef']->website_type_id ) ) {
			return;
		}

		// Which Website Type is the synced Website Type?
		$website_type_id = $this->plugin->admin->setting_get( 'user_profile_website_type', 0 );
		if ( empty( $website_type_id ) ) {
			return;
		}

		// Grab edited CiviCRM Website object.
		if ( ! is_object( $args['objectRef'] ) ) {
			$website = (object) $args['objectRef'];
		} else {
			$website = $args['objectRef'];
		}

		// Assume unchanged.
		$unchanged = true;
		$was_user_type = false;
		$now_user_type = false;

		// Check previous Website Type if there is one.
		if ( ! empty( $this->pre_edit ) && (int) $this->pre_edit->id === (int) $website->id ) {

			// If it used to be the synced Website Type.
			if ( (int) $website_type_id === (int) $this->pre_edit->website_type_id ) {

				// Check if it no longer is.
				if ( (int) $website_type_id !== (int) $website->website_type_id ) {
					$was_user_type = true;
					$unchanged = false;
				}

			} else {

				// Check if it now is.
				if ( (int) $website_type_id === (int) $website->website_type_id ) {
					$now_user_type = true;
					$unchanged = false;
				}

			}

		}

		// Bail if this is not the synced Website Type ID.
		if ( $unchanged && (int) $website->website_type_id !== (int) $website_type_id ) {
			return;
		}

		// Get the WordPress User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $website->contact_id );
		if ( empty( $user_id ) ) {
			return;
		}

		// Add User ID to args.
		$args['user_id'] = $user_id;

		/*
		 * When there is a change in edited Website's Website Type:
		 *
		 * If it is now the synced Website Type, edit as normal. Changes *away*
		 * from other Website Types are handled elsewhere.
		 *
		 * If it is no longer the synced Website Type, clear the URL.
		 */
		if ( $unchanged === false && $was_user_type === true ) {

			// Only apply "used to be" if an "is now" has not happened.
			if ( empty( $this->skip_updates ) ) {

				// Let's make a new object so we don't overwrite the Website object.
				$changed = new stdClass();
				$changed->id = $website->id;
				$changed->contact_id = $website->contact_id;
				$changed->website_type_id = $website->website_type_id;
				$changed->url = '';

				// Build new args.
				$changed_args = [
					'op' => $args['op'],
					'objectName' => $args['objectName'],
					'objectId' => $args['objectId'],
					'objectRef' => $changed,
					'user_id' => $user_id,
				];

				// Now update the WordPress User's Website.
				$this->plugin->wp->user->website_update( $changed_args );

			}

		} else {

			// Update the WordPress User's Website.
			$this->plugin->wp->user->website_update( $args );

			/*
			 * If this is an "is now" change, save it because we never want to
			 * override with an empty "used to be" value.
			 */
			if ( $now_user_type === true ) {
				$this->skip_updates = true;
			}

		}

		/**
		 * Broadcast that a CiviCRM Contact's Website has been synced.
		 *
		 * @since 0.2.4
		 *
		 * @param integer $user_id The ID of the WordPress User.
		 * @param integer $objectId The ID of the CiviCRM Website.
		 * @param object $website The CiviCRM Website object.
		 */
		do_action( 'civicrm_wp_profile_sync_website_synced', $user_id, $args['objectId'], $website );

	}

	/**
	 * Fires when a CiviCRM Contact's Website is about to be deleted.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_delete( $args ) {

		// Always clear property if set previously.
		if ( isset( $this->pre_delete ) ) {
			unset( $this->pre_delete );
		}

		// Bail if no Website ID.
		if ( empty( $args['objectId'] ) ) {
			return;
		}

		// We need a Contact ID in the deleted Website.
		$website = (object) $this->get_by_id( $args['objectId'] );
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Store the previous Website data for later.
		$this->pre_delete = $website;

	}

	/**
	 * Clears a WordPress User's Website when a CiviCRM Contact's Website is deleted.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_deleted( $args ) {

		// Get the full existing Website data.
		$website = $args['objectRef'];
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Which Website Type is the synced Website Type?
		$website_type_id = $this->plugin->admin->setting_get( 'user_profile_website_type', 0 );
		if ( empty( $website_type_id ) ) {
			return;
		}

		// Get the WordPress User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $website->contact_id );
		if ( empty( $user_id ) ) {
			return;
		}

		// Only delete if an "is now" has not happened.
		if ( ! empty( $this->skip_updates ) ) {
			return;
		}

		// Clear the URL.
		$website->url = '';

		// Build new args.
		$changed_args = [
			'op' => $args['op'],
			'objectName' => $args['objectName'],
			'objectId' => $args['objectId'],
			'objectRef' => $website,
			'user_id' => $user_id,
		];

		// Update the WordPress User's Website.
		$this->plugin->wp->user->website_update( $changed_args );

	}

	// -------------------------------------------------------------------------

	/**
	 * Updates a CiviCRM Contact's Website with the WordPress User's Website.
	 *
	 * @since 0.1
	 * @since 0.4 Params reduced to single array.
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_update( $args ) {

		// Which Website Type should we sync?
		$website_type_id = $this->plugin->admin->setting_get( 'user_profile_website_type', 0 );
		if ( empty( $website_type_id ) ) {
			return;
		}

		// Sanity check WordPress User.
		$user = $args['user'];
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Sanity check CiviCRM Contact.
		$contact = $args['ufmatch'];
		if ( empty( $contact->contact_id ) ) {
			return;
		}

		// Update the Website.
		$website = $this->update_for_contact( $website_type_id, $contact->contact_id, $user->user_url );

	}

	/**
	 * Updates a CiviCRM Contact's Website.
	 *
	 * @since 0.4
	 * @since 0.5.2 Renamed.
	 *
	 * @param integer $website_type_id The numeric ID of the Website Type.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $value The Website URL to update the Contact with.
	 * @return array|bool $website The array of Website data, or false on failure.
	 */
	public function update_for_contact( $website_type_id, $contact_id, $value ) {

		// Init return.
		$website = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website;
		}

		// Get the current Website.
		$existing = $this->get_by_type( $contact_id, $website_type_id );

		// Bail if there are no existing Websites and there is no incoming value.
		if ( empty( $existing ) && empty( $value ) ) {
			return $website;
		}

		// Create a new Website if there are no results and there is an incoming value.
		if ( empty( $existing ) && ! empty( $value ) ) {

			// Define params to create new Website.
			$params = [
				'website_type_id' => $website_type_id,
				'contact_id' => $contact_id,
				'url' => $value,
			];

			// Create it.
			return $this->create( $params );

		}

		// Bail if it hasn't changed.
		if ( ! empty( $existing['url'] ) && $existing['url'] === $value ) {
			return $existing;
		}

		// If there is an incoming value, update.
		if ( ! empty( $value ) ) {

			// Define params to update this Website.
			$params = [
				'id' => $existing['id'],
				'website_type_id' => $website_type_id,
				'contact_id' => $contact_id,
				'url' => $value,
			];

			// Update it.
			return $this->update( $params );

		}

		// Delete it.
		$this->delete( $existing['id'] );

		// Always return false.
		return false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Creates a CiviCRM Website record.
	 *
	 * @since 0.5.2
	 *
	 * @param array $website The array of CiviCRM Website data.
	 * @return array|bool $website_data The array of Website data, or false on failure.
	 */
	public function create( $website ) {

		// Init return.
		$website_data = false;

		// Try and initialise CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website_data;
		}

		// Build params to create Website.
		$params = [
			'version' => 3,
		] + $website;

		// Call the CiviCRM API.
		$result = civicrm_api( 'Website', 'create', $params );

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
			return $website;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $website_data;
		}

		// The result set should contain only one item.
		$website_data = array_pop( $result['values'] );

		// --<
		return $website_data;

	}

	/**
	 * Updates a CiviCRM Website record.
	 *
	 * This is an alias of `self::website_create()` except that we expect an ID
	 * to have been set in the Website data.
	 *
	 * @since 0.5.2
	 *
	 * @param array $website The array of CiviCRM ACL data.
	 * @return array|bool The array of Website data from the CiviCRM API, or false on failure.
	 */
	public function update( $website ) {

		// Log and bail if there's no ID.
		if ( empty( $website['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'An ID must be present to edit a Website.', 'civicrm-wp-profile-sync' ),
				'website' => $website,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Pass through.
		return $this->create( $website );

	}

	/**
	 * Deletes a CiviCRM Website record.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $website_id The numeric ID of the CiviCRM Website.
	 * @return bool $success True if the operation was successful, false on failure.
	 */
	public function delete( $website_id ) {

		// Init as failure.
		$success = false;

		// Try and initialise CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Log and bail if there's no Website ID.
		if ( empty( $website_id ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'An ID must be present to delete a Website.', 'civicrm-wp-profile-sync' ),
				'backtrace' => $trace,
			], true ) );
			return $success;
		}

		// Build params to delete Website.
		$params = [
			'version' => 3,
			'id' => $website_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Website', 'delete', $params );

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
	 * Get the data for a Website.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $website_id The numeric ID of the Website.
	 * @return array $website The array of Website data, or empty if none.
	 */
	public function get_by_id( $website_id ) {

		// Init return.
		$website = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $website_id,
		];

		// Get Website details via API.
		$result = civicrm_api( 'Website', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $website;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $website;
		}

		// The result set should contain only one item.
		$website = array_pop( $result['values'] );

		// --<
		return $website;

	}

	/**
	 * Get CiviCRM Contact's Website by Website Type.
	 *
	 * @since 0.4
	 *
	 * @param object|array|integer $contact The CiviCRM Contact reference.
	 * @param integer $website_type_id The numeric ID of the Website Type.
	 * @return object|bool $website The CiviCRM Website data, or false on failure.
	 */
	public function get_by_type( $contact, $website_type_id ) {

		// Init return.
		$website = false;

		// Grab ID from incoming Contact data.
		$contact_id = false;
		if ( is_object( $contact ) ) {
			$contact_id = $contact->contact_id;
		} elseif ( is_array( $contact ) ) {
			$contact_id = $contact['contact_id'];
		} elseif ( is_numeric( $contact ) ) {
			$contact_id = (int) $contact;
		}

		// Bail if there's no Contact ID.
		if ( empty( $contact_id ) ) {
			return $website;
		}

		// Get the current Website.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'website_type_id' => $website_type_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Website', 'get', $params );

		// Bail on failure.
		if ( ! empty( $result['is_error'] ) ) {
			return $website;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $website;
		}

		// The result set should contain only one item.
		$website = array_pop( $result['values'] );

		// --<
		return $website;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Website Types.
	 *
	 * @since 0.4
	 *
	 * @return array $website_types The array of possible Website Types.
	 */
	public function types_get() {

		// Init return.
		$website_types = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website_types;
		}

		// Get the Website Types array.
		$website_type_ids = CRM_Core_PseudoConstant::get( 'CRM_Core_DAO_Website', 'website_type_id' );

		// Bail if there are no results.
		if ( empty( $website_type_ids ) ) {
			return $website_types;
		}

		// Assign to return.
		$website_types = $website_type_ids;

		// --<
		return $website_types;

	}

	/**
	 * Get the Website Types.
	 *
	 * @since 0.4
	 *
	 * @return array $options The array of possible Website Types.
	 */
	public function types_options_get() {

		// Init return.
		$options = [];

		// Get Website Types.
		$website_types = $this->types_get();

		// Bail if there are none.
		if ( empty( $website_types ) ) {
			return $options;
		}

		// Add to return array keyed by ID.
		foreach ( $website_types as $website_type_id => $website_type_name ) {
			$options[ $website_type_id ] = esc_attr( $website_type_name );
		}

		// --<
		return $options;

	}

	/**
	 * Gets the URL of the CiviCRM "Website Types Options" admin page.
	 *
	 * @since 0.5.2
	 *
	 * @return string $url The URL of the CiviCRM "Website Type Options" page.
	 */
	public function types_options_get_link() {

		// Init safe return value.
		$url = '#';

		// Get the CiviCRM Option Group.
		$option_group = $this->plugin->civicrm->option_group_get( 'website_type' );
		if ( empty( $option_group ) ) {
			return $url;
		}

		// Build CiviCRM "query" and get URL.
		$query = 'gid=' . $option_group['id'] . 'reset=1';
		$url = $this->plugin->civicrm->get_link( 'civicrm/admin/options', $query );

		// --<
		return $url;

	}

}
