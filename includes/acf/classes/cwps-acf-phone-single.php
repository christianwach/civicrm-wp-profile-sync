<?php
/**
 * CiviCRM Single Phone Class.
 *
 * Handles CiviCRM Single Phone functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.6.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Single Phone Class.
 *
 * A class that encapsulates CiviCRM Single Phone functionality.
 *
 * @since 0.6.9
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Phone_Single extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

	/**
	 * Plugin object.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * Contact Fields which must be handled separately.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var array
	 */
	public $fields_handled = [
		'civicrm_phone_single',
	];

	/**
	 * An array of Phone Records prior to delete.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Phone Records in an array and try and match them
	 * up in the post delete hook.
	 *
	 * @since 0.6.9
	 * @access private
	 * @var array
	 */
	private $bridging_array = [];

	/**
	 * Constructor.
	 *
	 * @since 0.6.9
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm    = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

		// Init parent.
		parent::__construct();

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.6.9
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/acf/civicrm/phone_single/loaded' );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.6.9
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add any Single Phone Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post-Contact sync event.
		add_action( 'cwps/acf/post/contact/sync', [ $this, 'contact_sync_to_post' ], 10 );

	}

	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.6.9
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( true === $this->mapper_hooks ) {
			return;
		}

		// Listen for events from our Mapper that require Phone updates.
		add_action( 'cwps/acf/mapper/phone/created', [ $this, 'phone_edited' ], 10 );
		add_action( 'cwps/acf/mapper/phone/edited', [ $this, 'phone_edited' ], 10 );
		add_action( 'cwps/acf/mapper/phone/delete/pre', [ $this, 'phone_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/phone/deleted', [ $this, 'phone_deleted' ], 10 );

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.6.9
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( false === $this->mapper_hooks ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/phone/created', [ $this, 'phone_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/edited', [ $this, 'phone_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/delete/pre', [ $this, 'phone_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/deleted', [ $this, 'phone_deleted' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Update CiviCRM Phone Fields with data from ACF Fields.
	 *
	 * @since 0.6.9
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function fields_handled_update( $args ) {

		// Bail if we have no Field data to save.
		if ( empty( $args['fields'] ) ) {
			return true;
		}

		// Init success.
		$success = true;

		// Loop through the Field data.
		foreach ( $args['fields'] as $field => $value ) {

			// Get the Field settings.
			$settings = get_field_object( $field, $args['post_id'] );
			if ( empty( $settings ) ) {
				continue;
			}

			// Maybe update a Contact Field.
			$this->field_handled_update( $field, $value, $args['contact']['id'], $settings );

		}

		// --<
		return $success;

	}

	/**
	 * Update a CiviCRM Phone Field with data from an ACF Field.
	 *
	 * This Contact Field requires special handling because it is not part
	 * of the core Contact data.
	 *
	 * @since 0.6.9
	 *
	 * @param array   $field The ACF Field data.
	 * @param mixed   $value The ACF Field value.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array   $settings The ACF Field settings.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function field_handled_update( $field, $value, $contact_id, $settings ) {

		// Skip if it's not an ACF Field Type that this class handles.
		if ( ! in_array( $settings['type'], $this->fields_handled, true ) ) {
			return true;
		}

		// Update and return early if mapped to the Primary Phone Record.
		if ( 1 === (int) $settings['phone_is_primary'] ) {
			$this->primary_update( $contact_id, $value );
			return true;
		}

		// The Location Type and Phone Type are in the settings.
		$location_type_id = (int) $settings['phone_location_type_id'];
		$phone_type_id    = (int) $settings['phone_type_id'];

		// Update the Phone Record.
		$success = $this->phone_update( $location_type_id, $phone_type_id, $contact_id, $value );

		// --<
		return $success;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Primary Phone for a given Contact ID.
	 *
	 * @since 0.6.9
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return object|bool $phone The Primary Phone data object, or false on failure.
	 */
	public function primary_get( $contact_id ) {

		// Init return.
		$phone = false;

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $phone;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phone;
		}

		// Define params to get queried Phone Record.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'is_primary' => 1,
			'contact_id' => $contact_id,
		];

		// Call the API.
		$result = civicrm_api( 'Phone', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $phone;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $phone;
		}

		// The result set should contain only one item.
		$phone = (object) array_pop( $result['values'] );

		// --<
		return $phone;

	}

	/**
	 * Update a CiviCRM Contact's Primary Phone.
	 *
	 * @since 0.6.9
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string  $value The phone to update the Contact with.
	 * @return array|bool $phone The array of Phone data, or false on failure.
	 */
	public function primary_update( $contact_id, $value ) {

		// Init return.
		$phone = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phone;
		}

		// Get the current Primary Phone.
		$params = [
			'version'    => 3,
			'contact_id' => $contact_id,
			'is_primary' => 1,
		];

		// Call the CiviCRM API.
		$primary_phone = civicrm_api( 'Phone', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $primary_phone['is_error'] ) && 1 === (int) $primary_phone['is_error'] ) {
			return $phone;
		}

		// Create a Primary Phone if there are no results.
		if ( empty( $primary_phone['values'] ) ) {

			// But only if there is an incoming Phone Record.
			if ( empty( $value ) ) {
				return $phone;
			}

			// Define params to create new Primary Phone.
			$params = [
				'version'    => 3,
				'contact_id' => $contact_id,
				'is_primary' => 1,
				'phone'      => $value,
			];

			// Call the API.
			$result = civicrm_api( 'Phone', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $primary_phone['values'] );

			// Bail if it hasn't changed.
			if ( $existing_data['phone'] == $value ) {
				return $existing_data;
			}

			// If there is an incoming value, update.
			if ( ! empty( $value ) ) {

				// Define params to update this Phone.
				$params = [
					'version'    => 3,
					'id'         => $primary_phone['id'],
					'contact_id' => $contact_id,
					'phone'      => $value,
				];

				// Call the API.
				$result = civicrm_api( 'Phone', 'create', $params );

			} else {

				// Define params to delete this Phone.
				$params = [
					'version' => 3,
					'id'      => $primary_phone['id'],
				];

				// Call the API.
				$result = civicrm_api( 'Phone', 'delete', $params );

				// Bail early.
				return $phone;

			}

		}

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
			return $phone;
		}

		// The result set should contain only one item.
		$phone = array_pop( $result['values'] );

		// --<
		return $phone;

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a Post is been synced from a Contact.
	 *
	 * Sync any associated ACF Fields mapped to Custom Fields.
	 *
	 * @since 0.6.9
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get all Phone Records for this Contact.
		$data = $this->phones_get_for_contact( $args['objectId'] );
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );
		if ( empty( $acf_fields['phone_single'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['phone_single'] as $selector => $phone_field ) {

			// Let's look at each Phone Record in turn.
			foreach ( $data as $phone ) {

				// Cast as object.
				$phone = (object) $phone;

				// If this is mapped to the Primary Phone.
				if ( 'primary' === $phone_field && ! empty( $phone->is_primary ) && 1 === (int) $phone->is_primary ) {
					$this->acf_loader->acf->field->value_update( $selector, $phone->phone, $args['post_id'] );
					continue;
				}

				// Skip if the Location Types don't match.
				if ( $phone_field != $phone->location_type_id ) {
					continue;
				}

				// Update it.
				$this->acf_loader->acf->field->value_update( $selector, $phone->phone, $args['post_id'] );

			}

		}

	}

	/**
	 * Update a CiviCRM Contact's Phone Record.
	 *
	 * @since 0.6.9
	 *
	 * @param integer $location_type_id The numeric ID of the Location Type.
	 * @param integer $phone_type_id The numeric ID of the Phone Type.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string  $value The Phone Number to update the Contact with.
	 * @return array|bool $phone The array of Phone data, or false on failure.
	 */
	public function phone_update( $location_type_id, $phone_type_id, $contact_id, $value ) {

		// Init return.
		$phone = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phone;
		}

		// Get the current Phone for this Location & Pnone Type.
		$params = [
			'version'          => 3,
			'location_type_id' => $location_type_id,
			'phone_type_id'    => $phone_type_id,
			'contact_id'       => $contact_id,
		];

		// Call the CiviCRM API.
		$existing_phone = civicrm_api( 'Phone', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $existing_phone['is_error'] ) && 1 === (int) $existing_phone['is_error'] ) {
			return $phone;
		}

		// Create a new Phone if there are no results.
		if ( empty( $existing_phone['values'] ) ) {

			// Skip if there is no incoming value.
			if ( empty( $value ) ) {
				return $phone;
			}

			// Define params to create new Phone.
			$params = [
				'version'          => 3,
				'location_type_id' => $location_type_id,
				'phone_type_id'    => $phone_type_id,
				'contact_id'       => $contact_id,
				'phone'            => $value,
			];

			// Call the API.
			$result = civicrm_api( 'Phone', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $existing_phone['values'] );

			// Bail if it hasn't changed.
			if ( $existing_data['phone'] === $value ) {
				return $existing_data;
			}

			// If there is an incoming value, update.
			if ( ! empty( $value ) ) {

				// Define params to update this Phone.
				$params = [
					'version'    => 3,
					'id'         => $existing_phone['id'],
					'contact_id' => $contact_id,
					'phone'      => $value,
				];

				// Call the API.
				$result = civicrm_api( 'Phone', 'create', $params );

			} else {

				// Define params to delete this Phone.
				$params = [
					'version' => 3,
					'id'      => $existing_phone['id'],
				];

				// Call the API.
				$result = civicrm_api( 'Phone', 'delete', $params );

				// Bail early.
				return $phone;

			}

		}

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
			return $phone;
		}

		// The result set should contain only one item.
		$phone = array_pop( $result['values'] );

		// --<
		return $phone;

	}

	/**
	 * Update a CiviCRM Contact's Phone Record.
	 *
	 * @since 0.6.9
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array   $data The Phone data to save.
	 * @return array|bool $phone The array of Phone data, or false on failure.
	 */
	public function phone_record_update( $contact_id, $data ) {

		// Init return.
		$phone = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phone;
		}

		// Get the current Phone for this Location Type.
		$params = [
			'version'          => 3,
			'contact_id'       => $contact_id,
			'location_type_id' => $data['location_type_id'],
		];

		// Call the CiviCRM API.
		$existing_phone = civicrm_api( 'Phone', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $existing_phone['is_error'] ) && 1 === (int) $existing_phone['is_error'] ) {
			return $phone;
		}

		// Create a new Phone if there are no results.
		if ( empty( $existing_phone['values'] ) ) {

			// Define params to create new Phone.
			$params = [
				'version'    => 3,
				'contact_id' => $contact_id,
			] + $data;

			// Call the API.
			$result = civicrm_api( 'Phone', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $existing_phone['values'] );

			/*
			// Bail if it hasn't changed.
			if ( $existing_data['phone'] == $value ) {
				return $existing_data;
			}
			*/

			// Define default params to update this Phone.
			$params = [
				'version'    => 3,
				'id'         => $existing_data['id'],
				'contact_id' => $contact_id,
			] + $data;

			// Call the API.
			$result = civicrm_api( 'Phone', 'create', $params );

		}

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
			return $phone;
		}

		// The result set should contain only one item.
		$phone = array_pop( $result['values'] );

		// --<
		return $phone;

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a CiviCRM Phone Record has been updated.
	 *
	 * @since 0.6.9
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_edited( $args ) {

		// Grab the Phone data.
		$phone_data = $args['objectRef'];

		// Bail if this is not a Contact's Phone.
		if ( empty( $phone_data->contact_id ) ) {
			return;
		}

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $phone_data->contact_id );
		if ( false === $contact ) {
			return;
		}

		// Data may be missing for some operations, so get the full Phone Record.
		$phone = $this->plugin->civicrm->phone->phone_get_by_id( $phone_data->id );
		if ( empty( $phone->contact_id ) ) {
			return;
		}

		// Process the Phone Record.
		$this->phone_process( $phone_pre, $args );

	}

	/**
	 * A CiviCRM Contact's Phone Record is about to be deleted.
	 *
	 * Before a Phone Record is deleted, we need to retrieve the Phone Record
	 * because the data passed via "civicrm_post" only contains the ID of the
	 * Phone Record.
	 *
	 * This is not required when creating or editing a Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_pre_delete( $args ) {

		// We just need the Phone ID.
		$phone_id = (int) $args['objectId'];

		// Grab the Phone Record data from the database.
		$phone_pre = $this->plugin->civicrm->phone->phone_get_by_id( $phone_id );

		// Maybe cast previous Phone Record data as object and stash in a property.
		if ( ! is_object( $phone_pre ) ) {
			$phone_pre = (object) $phone_pre;
		}

		// Stash in property array.
		$this->bridging_array[ $phone_id ] = $phone_pre;

	}

	/**
	 * A CiviCRM Phone Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_deleted( $args ) {

		// We just need the Phone ID.
		$phone_id = (int) $args['objectId'];

		// Populate "Previous Phone" if we have it stored.
		$phone_pre = null;
		if ( ! empty( $this->bridging_array[ $phone_id ] ) ) {
			$phone_pre = $this->bridging_array[ $phone_id ];
			unset( $this->bridging_array[ $phone_id ] );
		}

		// Bail if we can't find the previous Phone Record or it doesn't match.
		if ( empty( $phone_pre ) || $phone_id !== (int) $phone_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Phone Record.
		if ( empty( $phone_pre->contact_id ) ) {
			return;
		}

		// Process the Phone Record.
		$this->phone_process( $phone_pre, $args );

	}

	/**
	 * Process a CiviCRM Phone Record.
	 *
	 * @since 0.6.9
	 *
	 * @param object $phone The CiviCRM Phone Record object.
	 * @param array  $args The array of CiviCRM params.
	 */
	public function phone_process( $phone, $args ) {

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $phone->contact_id );

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Test if any of this Contact's Contact Types is mapped to a Post Type.
		$post_types = $this->civicrm->contact->is_mapped( $contact, 'create' );
		if ( false !== $post_types ) {

			// Handle each Post Type in turn.
			foreach ( $post_types as $post_type ) {

				// Get the Post ID for this Contact.
				$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );

				// Skip if not mapped or Post doesn't yet exist.
				if ( false === $post_id ) {
					continue;
				}

				// Exclude "reverse" edits when a Post is the originator.
				if ( 'post' === $entity['entity'] && $post_id == $entity['id'] ) {
					continue;
				}

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $phone );

			}

		}

		/**
		 * Broadcast that a Phone ACF Field may have been edited.
		 *
		 * @since 0.6.9
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $phone The CiviCRM Phone Record object.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/phone_single/updated', $contact, $phone, $args );

	}

	/**
	 * Update a Phone ACF Field on an Entity mapped to a Contact ID.
	 *
	 * @since 0.6.9
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object         $phone The CiviCRM Phone object.
	 */
	public function fields_update( $post_id, $phone ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Phone Fields.
		if ( empty( $acf_fields['phone_single'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['phone_single'] as $selector => $phone_field ) {

			// If this is mapped to the Primary Phone.
			if ( 'primary' === $phone_field ) {
				$primary = $this->plugin->civicrm->denullify( $phone->is_primary );
				if ( ! empty( $primary ) ) {
					$this->acf_loader->acf->field->value_update( $selector, $phone->phone, $post_id );
				}
				continue;
			}

			// We need to test for a combination of Phone Type and Location Type.

			// Skip if the Location & Phone Types don't match.
			if ( (int) $phone_field['location_type_id'] !== (int) $phone->location_type_id ) {
				if ( (int) $phone_field['phone_type_id'] !== (int) $phone->phone_type_id ) {
					continue;
				}
			}

			// Update it.
			$this->acf_loader->acf->field->value_update( $selector, $phone->phone, $post_id );

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Add any Single Phone Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array   $acf_fields The existing ACF Fields array.
	 * @param array   $field The ACF Field.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Add if it has a reference to a Phone Field.
		if ( ! empty( $field['type'] ) && 'civicrm_phone_single' === $field['type'] ) {
			if ( 1 === $field['phone_is_primary'] ) {
				$acf_fields['phone_single'][ $field['name'] ] = 'primary';
			} else {
				$acf_fields['phone_single'][ $field['name'] ] = [
					'location_type_id' => $field['phone_location_type_id'],
					'phone_type_id'    => $field['phone_type_id'],
				];
			}
		}

		// --<
		return $acf_fields;

	}

}
