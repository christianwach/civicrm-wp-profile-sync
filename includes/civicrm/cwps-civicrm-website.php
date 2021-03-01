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

		// Intercept Website updates in CiviCRM.
		add_action( 'cwps/mapper/website_pre_edit', [ $this, 'website_pre' ], 10 );
		add_action( 'cwps/mapper/website_edited', [ $this, 'website_edited' ], 10 );

	}



	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all CiviCRM callbacks.
		remove_action( 'cwps/mapper/website_pre_edit', [ $this, 'website_pre' ], 10 );
		remove_action( 'cwps/mapper/website_edited', [ $this, 'website_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a WordPress User when a CiviCRM Contact's Website is edited.
	 *
	 * @since 0.1
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre( $args ) {

		// Bail if we have no Contact ID.
		if ( ! isset( $args['objectRef']->contact_id ) ) {
			return;
		}

		// Get the WordPress User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $args['objectRef']->contact_id );

		// Kick out if we didn't get one.
		if ( empty( $user_id ) ) {
			return;
		}

		// Add User ID to args.
		$args['user_id'] = $user_id;

		// Update the WordPress User's Website.
		$this->plugin->wp->user->website_update( $args );

		/**
		 * Broadcast that a CiviCRM Contact's Website has been synced.
		 *
		 * @since 0.2.4
		 *
		 * @param integer $user_id The ID of the WordPress User.
		 * @param integer $objectId The ID of the CiviCRM Website.
		 * @param object $objectRef The CiviCRM Website object.
		 */
		do_action( 'civicrm_wp_profile_sync_website_synced', $user_id, $args['objectId'], $args['objectRef'] );

	}



	/**
	 * Update a WordPress User when a CiviCRM Contact's Website is edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_edited( $args ) {

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact's Website address.
	 *
	 * @since 0.1
	 * @since 0.4 Params reduced to single array.
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_update( $args ) {

		// Grab User and Contact.
		$user = $args['user'];
		$contact = $args['ufmatch'];

		// Which Website Type should we sync?
		$website_type_id = $this->plugin->admin->setting_get( 'user_profile_website_type', 0 );

		// Bail if we didn't get one.
		if ( empty( $website_type_id ) OR $website_type_id === 0 ) {
			return;
		}

		// Get the current Website.
		$existing = $this->website_get_by_type( $contact, $website_type_id );

		// Create a new Website if there isn't one.
		if ( empty( $existing ) ) {

			// Define params to create new Website.
			$params = [
				'version' => 3,
				'website_type_id' => $website_type_id,
				'contact_id' => $contact->contact_id,
				'url' => $user->user_url,
			];

			// Call the API.
			$result = civicrm_api( 'Website', 'create', $params );

			// Log something on failure.
			if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
				$e = new \Exception;
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					'message' => __( 'Could not create the Website for the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
					'result' => $result,
					'backtrace' => $trace,
				], true ) );
			}

		} else {

			// Bail if it hasn't changed.
			if ( ! empty( $existing->url ) AND $existing->url == $user->user_url ) {
				return;
			}

			// If there is an incoming value, update.
			if ( ! empty( $user->user_url ) ) {

				// Define params to update this Website.
				$params = [
					'version' => 3,
					'id' => $existing->id,
					'contact_id' => $contact->contact_id,
					'website_type_id' => $website_type_id,
					'url' => $user->user_url,
				];

				// Call the API.
				$result = civicrm_api( 'Website', 'create', $params );

				// Log something on failure.
				if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
					$e = new \Exception;
					$trace = $e->getTraceAsString();
					error_log( print_r( [
						'method' => __METHOD__,
						'message' => __( 'Could not update the Website for the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
						'result' => $result,
						'backtrace' => $trace,
					], true ) );
				}

			} else {

				// Define params to delete this Website.
				$params = [
					'version' => 3,
					'id' => $existing->id,
				];

				// Call the API.
				$result = civicrm_api( 'Website', 'delete', $params );

				// Log something on failure.
				if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
					$e = new \Exception;
					$trace = $e->getTraceAsString();
					error_log( print_r( [
						'method' => __METHOD__,
						'message' => __( 'Could not delete the Website for the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
						'result' => $result,
						'website' => $website,
						'backtrace' => $trace,
					], true ) );
				}

				// Bail early.
				return;

			}

		}

	}



	/**
	 * Update a CiviCRM Contact's Website.
	 *
	 * @since 0.4
	 *
	 * @param int $website_type_id The numeric ID of the Website Type.
	 * @param int $contact_id The numeric ID of the Contact.
	 * @param str $value The Website URL to update the Contact with.
	 * @return array|bool $website The array of Website data, or false on failure.
	 */
	public function website_update_acf( $website_type_id, $contact_id, $value ) {

		// Init return.
		$website = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website;
		}

		// Get the current Website for this Website Type.
		$params = [
			'version' => 3,
			'website_type_id' => $website_type_id,
			'contact_id' => $contact_id,
		];

		// Call the CiviCRM API.
		$existing_website = civicrm_api( 'Website', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $existing_website['is_error'] ) AND $existing_website['is_error'] == 1 ) {
			return $website;
		}

		// Create a new Website if there are no results.
		if ( empty( $existing_website['values'] ) ) {

			// Define params to create new Website.
			$params = [
				'version' => 3,
				'website_type_id' => $website_type_id,
				'contact_id' => $contact_id,
				'url' => $value,
			];

			// Call the API.
			$result = civicrm_api( 'Website', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $existing_website['values'] );

			// Bail if it hasn't changed.
			if ( !empty( $existing_data['url'] ) AND $existing_data['url'] == $value ) {
				return $existing_data;
			}

			// If there is an incoming value, update.
			if ( ! empty( $value ) ) {

				// Define params to update this Website.
				$params = [
					'version' => 3,
					'id' => $existing_website['id'],
					'contact_id' => $contact_id,
					'url' => $value,
				];

				// Call the API.
				$result = civicrm_api( 'Website', 'create', $params );

			} else {

				// Define params to delete this Website.
				$params = [
					'version' => 3,
					'id' => $existing_website['id'],
				];

				// Call the API.
				$result = civicrm_api( 'Website', 'delete', $params );

				// Bail early.
				return $website;

			}

		}

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $website;
		}

		// The result set should contain only one item.
		$website = array_pop( $result['values'] );

		// --<
		return $website;

	}



	/**
	 * Create a Website for a CiviCRM Contact.
	 *
	 * @since 0.4
	 *
	 * @param object $contact The CiviCRM Contact data object.
	 * @param int $website_type The numeric ID of the CiviCRM Website Type.
	 * @param int $website_id The numeric ID of the CiviCRM Website.
	 * @return object|bool $website The CiviCRM Website data, or false on failure.
	 */
	public function website_create( $contact, $website_type, $website_id = null ) {

	}



	/**
	 * Edit a Website for a CiviCRM Contact.
	 *
	 * @since 0.4
	 *
	 * @param object $contact The CiviCRM Contact data object.
	 * @param int $website_type The numeric ID of the CiviCRM Website Type.
	 * @param int $website_id The numeric ID of the CiviCRM Website.
	 * @return object|bool $website The CiviCRM Website data, or false on failure.
	 */
	public function website_edit( $contact, $website_type, $website_id ) {

	}



	/**
	 * Delete a Website for a CiviCRM Contact.
	 *
	 * @since 0.4
	 *
	 * @param int $website_id The numeric ID of the CiviCRM Website.
	 * @return object|bool $website The CiviCRM Website data, or false on failure.
	 */
	public function website_delete( $website_id ) {

	}



	/**
	 * Get CiviCRM Contact's Website by Website Type.
	 *
	 * @since 0.4
	 *
	 * @param object $contact The CiviCRM Contact data object.
	 * @param int $website_type The numeric ID of the CiviCRM Website Type.
	 * @return object|bool $website The CiviCRM Website data, or false on failure.
	 */
	public function website_get_by_type( $contact, $website_type ) {

		// Init return.
		$website = false;

		// Get the current Website.
		$params = [
			'version' => 3,
			'contact_id' => $contact->contact_id,
			'website_type_id' => $website_type,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Website', 'get', $params );

		// Bail on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			return $website;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $website;
		}

		// The result set should contain only one item.
		$website = (object) array_pop( $result['values'] );

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
		foreach( $website_types AS $website_type_id => $website_type_name ) {
			$options[$website_type_id] = esc_attr( $website_type_name );
		}

		// --<
		return $options;

	}



} // Class ends.



