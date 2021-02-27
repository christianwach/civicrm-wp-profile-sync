<?php
/**
 * CiviCRM Phone Class.
 *
 * Handles CiviCRM Phone functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Phone Class.
 *
 * A class that encapsulates CiviCRM Phone Record functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Phone extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * ACF Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $fields_handled The array of ACF Fields which must be handled separately.
	 */
	public $fields_handled = [
		'civicrm_phone',
	];

	/**
	 * Shortcode object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $shortcode The Shortcode object.
	 */
	public $shortcode;



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to parent.
		$this->civicrm = $parent;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

		// Init parent.
		parent::__construct();

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/acf/civicrm/phone/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include Shortcode class file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/shortcodes/cwps-shortcode-phone.php';

	}



	/**
	 * Set up the child objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Init Phone Shortcode object.
		$this->shortcode = new CiviCRM_Profile_Sync_ACF_Shortcode_Phone( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add any Phone Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post created from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

		// Maybe sync the Phone Record "Phone ID" to the ACF Subfields.
		add_action( 'cwps/acf/civicrm/phone/created', [ $this, 'maybe_sync_phone_id' ], 10, 2 );

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Listen for events from our Mapper that require Phone updates.
		add_action( 'cwps/acf/mapper/phone/created', [ $this, 'phone_edited' ], 10 );
		add_action( 'cwps/acf/mapper/phone/edited', [ $this, 'phone_edited' ], 10 );
		add_action( 'cwps/acf/mapper/phone/delete/pre', [ $this, 'phone_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/phone/deleted', [ $this, 'phone_deleted' ], 10 );

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/phone/created', [ $this, 'phone_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/edited', [ $this, 'phone_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/delete/pre', [ $this, 'phone_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/deleted', [ $this, 'phone_deleted' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact's Fields with data from ACF Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool $success True if updates were successful, or false on failure.
	 */
	public function fields_handled_update( $args ) {

		// Init success.
		$success = true;

		// Bail if we have no field data to save.
		if ( empty( $args['fields'] ) ) {
			return $success;
		}

		// Loop through the field data.
		foreach( $args['fields'] AS $field => $value ) {

			// Get the field settings.
			$settings = get_field_object( $field, $args['post_id'] );

			// Maybe update a Phone Record.
			$success = $this->field_handled_update( $field, $value, $args['contact']['id'], $settings, $args );

		}

		// --<
		return $success;

	}



	/**
	 * Update a CiviCRM Contact's Field with data from an ACF Field.
	 *
	 * These Fields require special handling because they are not part
	 * of the core Contact data.
	 *
	 * @since 0.4
	 *
	 * @param str $field The ACF Field selector.
	 * @param mixed $value The ACF Field value.
	 * @param int $contact_id The numeric ID of the Contact.
	 * @param array $settings The ACF Field settings.
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function field_handled_update( $field, $value, $contact_id, $settings, $args ) {

		// Skip if it's not an ACF Field Type that this class handles.
		if ( ! in_array( $settings['type'], $this->fields_handled ) ) {
			return true;
		}

		// Update the Phone Records.
		$success = $this->phones_update( $value, $contact_id, $field, $args );

		// --<
		return $success;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for a Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param int $phone_id The numeric ID of the Phone Record.
	 * @param array $phone The array of Phone Record data, or empty if none.
	 */
	public function phone_get_by_id( $phone_id ) {

		// Init return.
		$phone = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phone;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $phone_id,
		];

		// Get Phone Record details via API.
		$result = civicrm_api( 'Phone', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $phone;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $phone;
		}

 		// The result set should contain only one item.
		$phone = array_pop( $result['values'] );

		// --<
		return $phone;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Phone Records for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $phone_data The array of Phone Record data for the CiviCRM Contact.
	 */
	public function phones_get_for_contact( $contact_id ) {

		// Init return.
		$phone_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $phone_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phone_data;
		}

		// Define params to get queried Phone Records.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Phone', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $phone_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $phone_data;
		}

		// The result set it what we want.
		$phone_data = $result['values'];

		// --<
		return $phone_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a Post is been synced from a Contact.
	 *
	 * Sync any associated ACF Fields mapped to Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get all Phone Records for this Contact.
		$data = $this->phones_get_for_contact( $args['objectId'] );

		// Bail if there are no Phone Record Fields.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Phone Record Fields.
		if ( empty( $acf_fields['phone'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach( $acf_fields['phone'] AS $selector => $phone_field ) {

			// Init Field value.
			$value = [];

			// Let's look at each Phone in turn.
			foreach( $data AS $phone ) {

				// Convert to ACF Phone data.
				$acf_phone = $this->prepare_from_civicrm( $phone );

				// Add to Field value.
				$value[] = $acf_phone;

			}

			// Now update Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

		}

	}



	/**
	 * Update all of a CiviCRM Contact's Phone Records.
	 *
	 * @since 0.4
	 *
	 * @param array $values The array of Phone Record arrays to update the Contact with.
	 * @param int $contact_id The numeric ID of the Contact.
	 * @param str $selector The ACF Field selector.
	 * @param array $args The array of WordPress params.
	 * @return array|bool $phones The array of Phone Record data, or false on failure.
	 */
	public function phones_update( $values, $contact_id, $selector, $args = [] ) {

		// Init return.
		$phones = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phones;
		}

		// Get the current Phone Records.
		$current = $this->phones_get_for_contact( $contact_id );

		// If there are no existing Phone Records.
		if ( empty( $current ) ) {

			// Create a Phone Record from each value.
			foreach( $values AS $key => $value ) {

				// Build required data.
				$phone_data = $this->prepare_from_field( $value );

				// Okay, let's do it.
				$phone = $this->update( $contact_id, $phone_data );

				// Add to return array.
				$phones[] = $phone;

				// Make an array of our params.
				$params = [
					'key' => $key,
					'value' => $value,
					'phone' => $phone,
					'contact_id' => $contact_id,
					'selector' => $selector,
				];

				/**
				 * Broadcast that a Phone Record has been created.
				 *
				 * We use this internally to update the ACF Field with the Phone ID.
				 *
				 * @since 0.4
				 *
				 * @param array $params The Phone data.
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/civicrm/phone/created', $params, $args );

			}

			// No need to go any further.
			return $phones;

		}

		// We have existing Phone Records.
		$actions = [
			'create' => [],
			'update' => [],
			'delete' => [],
		];

		// Let's look at each ACF Record and check its Phone ID.
		foreach( $values AS $key => $value ) {

			// New Records have no Phone ID.
			if ( empty( $value['field_phone_id'] ) ) {
				$actions['create'][$key] = $value;
				continue;
			}

			// Records to update have a Phone ID.
			if ( ! empty( $value['field_phone_id'] ) ) {
				$actions['update'][$key] = $value;
				continue;
			}

		}

		// Grab the ACF Phone ID values.
		$acf_phone_ids = wp_list_pluck( $values, 'field_phone_id' );

		// Sanitise array contents.
		array_walk( $acf_phone_ids, function( &$item ) {
			$item = (int) trim( $item );
		} );

		// Records to delete are missing from the ACF data.
		foreach( $current AS $current_phone ) {
			if ( ! in_array( $current_phone['id'], $acf_phone_ids ) ) {
				$actions['delete'][] = $current_phone['id'];
				continue;
			}
		}

		// Create CiviCRM Phone Records.
		foreach( $actions['create'] AS $key => $value ) {

			// Build required data.
			$phone_data = $this->prepare_from_field( $value );

			// Okay, let's do it.
			$phone = $this->update( $contact_id, $phone_data );

			// Add to return array.
			$phones[] = $phone;

			// Make an array of our params.
			$params = [
				'key' => $key,
				'value' => $value,
				'phone' => $phone,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Phone Record has been created.
			 *
			 * We use this internally to update the ACF Field with the Phone ID.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Phone data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/phone/created', $params, $args );

		}

		// Update CiviCRM Phone Records.
		foreach( $actions['update'] AS $key => $value ) {

			// Build required data.
			$phone_data = $this->prepare_from_field( $value, $value['field_phone_id'] );

			// Okay, let's do it.
			$phone = $this->update( $contact_id, $phone_data );

			// Add to return array.
			$phones[] = $phone;

			// Make an array of our params.
			$params = [
				'key' => $key,
				'value' => $value,
				'phone' => $phone,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Phone Record has been updated.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Phone data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/phone/updated', $params, $args );

		}

		// Delete CiviCRM Phone Records.
		foreach( $actions['delete'] AS $phone_id ) {

			// Okay, let's do it.
			$phone = $this->delete( $phone_id );

			// Make an array of our params.
			$params = [
				'phone_id' => $phone_id,
				'phone' => $phone,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Phone Record has been deleted.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Phone data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/phone/deleted', $params, $args );

		}

	}



	/**
	 * Prepare the CiviCRM Phone Record data from an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $value The array of Phone Record data in the ACF Field.
	 * @param int $phone_id The numeric ID of the Phone Record (or null if new).
	 * @return array $phone_data The CiviCRM Phone Record data.
	 */
	public function prepare_from_field( $value, $phone_id = null ) {

		// Init required data.
		$phone_data = [];

		// Maybe add the Phone ID.
		if ( ! empty( $phone_id ) ) {
			$phone_data['id'] = $phone_id;
		}

		// Convert ACF data to CiviCRM data.
		$phone_data['is_primary'] = empty( $value['field_phone_primary'] ) ? '0' : '1';
		$phone_data['location_type_id'] = (int) $value['field_phone_location'];
		$phone_data['phone_type_id'] = (int) $value['field_phone_type'];
		$phone_data['phone'] = trim( $value['field_phone_number'] );
		$phone_data['phone_ext'] = trim( $value['field_phone_extension'] );

		// --<
		return $phone_data;

	}



	/**
	 * Prepare the ACF Field data from a CiviCRM Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param array $value The array of Phone Record data in CiviCRM.
	 * @return array $phone_data The ACF Phone data.
	 */
	public function prepare_from_civicrm( $value ) {

		// Init required data.
		$phone_data = [];

		// Maybe cast as an object.
		if ( ! is_object( $value ) ) {
			$value = (object) $value;
		}

		// Init optional Phone Extension.
		$phone_ext = empty( $value->phone_ext ) ? '' : $value->phone_ext;

		// Convert CiviCRM data to ACF data.
		$phone_data['field_phone_number'] = trim( $value->phone );
		$phone_data['field_phone_extension'] = $this->acf_loader->civicrm->denullify( $phone_ext );
		$phone_data['field_phone_location'] = (int) $value->location_type_id;
		$phone_data['field_phone_type'] = (int) $value->phone_type_id;
		$phone_data['field_phone_primary'] = empty( $value->is_primary ) ? '0' : '1';
		$phone_data['field_phone_id'] = (int) $value->id;

		// --<
		return $phone_data;

	}



	/**
	 * Update a CiviCRM Contact's Phone Record.
	 *
	 * If you want to "create" a Phone Record, do not pass $data['id'] in. The
	 * presence of an ID will cause an update to that Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param int $contact_id The numeric ID of the Contact.
	 * @param str $data The Phone data to update the Contact with.
	 * @return array|bool $phone The array of Phone Record data, or false on failure.
	 */
	public function update( $contact_id, $data ) {

		// Init return.
		$phone = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phone;
		}

		// Define params to create new Phone Record.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Phone', 'create', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $phone;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $phone;
		}

		// The result set should contain only one item.
		$phone = array_pop( $result['values'] );

		// --<
		return $phone;

	}



	/**
	 * Delete a Phone Record in CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param int $phone_id The numeric ID of the Phone Record.
	 * @return bool $success True if successfully deleted, or false on failure.
	 */
	public function delete( $phone_id ) {

		// Init return.
		$success = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Define params to delete this Phone Record.
		$params = [
			'version' => 3,
			'id' => $phone_id,
		];

		// Call the API.
		$result = civicrm_api( 'Phone', 'delete', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $success;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $success;
		}

		// The result set should contain only one item.
		$success = ( $result['values'] == '1' ) ? true : false;

		// --<
		return $success;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Phone Record has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_edited( $args ) {

		// Grab the Phone Record data.
		$civicrm_phone = $args['objectRef'];

		// Bail if this is not a Contact's Phone Record.
		if ( empty( $civicrm_phone->contact_id ) ) {
			return;
		}

		// Process the Phone Record.
		$this->phone_process( $civicrm_phone, $args );

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

		// Always clear properties if set previously.
		if ( isset( $this->phone_pre ) ) {
			unset( $this->phone_pre );
		}

		// We just need the Phone ID.
		$phone_id = (int) $args['objectId'];

		// Grab the Phone Record data from the database.
		$phone_pre = $this->phone_get_by_id( $phone_id );

		// Maybe cast previous Phone Record data as object and stash in a property.
		if ( ! is_object( $phone_pre ) ) {
			$this->phone_pre = (object) $phone_pre;
		} else {
			$this->phone_pre = $phone_pre;
		}

	}



	/**
	 * A CiviCRM Phone Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_deleted( $args ) {

		// Bail if we don't have a pre-delete Phone Record.
		if ( ! isset( $this->phone_pre ) ) {
			return;
		}

		// We just need the Phone ID.
		$phone_id = (int) $args['objectId'];

		// Sanity check.
		if ( $phone_id != $this->phone_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Phone Record.
		if ( empty( $this->phone_pre->contact_id ) ) {
			return;
		}

		// Process the Phone Record.
		$this->phone_process( $this->phone_pre, $args );

	}



	/**
	 * Process a CiviCRM Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param object $phone The CiviCRM Phone Record object.
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_process( $phone, $args ) {

		// Convert to ACF Phone data.
		$acf_phone = $this->prepare_from_civicrm( $phone );

		// Get the Contact data.
		$contact = $this->acf_loader->civicrm->contact->get_by_id( $phone->contact_id );

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Test if any of this Contact's Contact Types is mapped to a Post Type.
		$post_types = $this->acf_loader->civicrm->contact->is_mapped( $contact, 'create' );
		if ( $post_types !== false ) {

			// Handle each Post Type in turn.
			foreach( $post_types AS $post_type ) {

				// Get the Post ID for this Contact.
				$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $contact, $post_type );

				// Skip if not mapped or Post doesn't yet exist.
				if ( $post_id === false ) {
					continue;
				}

				// Exclude "reverse" edits when a Post is the originator.
				if ( $entity['entity'] === 'post' AND $post_id == $entity['id'] ) {
					continue;
				}

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $phone, $acf_phone, $args );

			}

		}

		/**
		 * Broadcast that a Phone ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $phone The CiviCRM Phone Record object.
		 * @param array $acf_phone The ACF Phone Record array.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/phone/updated', $contact, $phone, $acf_phone, $args );

	}



	/**
	 * Update Phone ACF Fields on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param int|str $post_id The ACF "Post ID".
	 * @param object $phone The CiviCRM Phone Record object.
	 * @param array $acf_phone The ACF Phone Record array.
	 * @param array $args The array of CiviCRM params.
	 */
	public function fields_update( $post_id, $phone, $acf_phone, $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Phone Record Fields.
		if ( empty( $acf_fields['phone'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach( $acf_fields['phone'] AS $selector => $phone_field ) {

			// Get existing Field value.
			$existing = get_field( $selector, $post_id );

			// Before applying edit, make some checks.
			if ( $args['op'] == 'edit' ) {

				// If there is no existing Field value, treat as a 'create' op.
				if ( empty( $existing ) ) {
					$args['op'] = 'create';
				} else {

					// Grab the ACF Phone ID values.
					$acf_phone_ids = wp_list_pluck( $existing, 'field_phone_id' );

					// Sanitise array contents.
					array_walk( $acf_phone_ids, function( &$item ) {
						$item = (int) trim( $item );
					} );

					// If the ID is missing, treat as a 'create' op.
					if ( ! in_array( $phone->id, $acf_phone_ids ) ) {
						$args['op'] = 'create';
					}

				}

			}

			// Process array record.
			switch( $args['op'] ) {

				case 'create' :

					// Make sure no other Phone is Primary if this one is.
					if ( $acf_phone['field_phone_primary'] == '1' AND ! empty( $existing ) ) {
						foreach( $existing AS $key => $record ) {
							$existing[$key]['field_phone_primary'] = '0';
						}
					}

					// Add array record.
					$existing[] = $acf_phone;

					break;

				case 'edit' :

					// Make sure no other Phone is Primary if this one is.
					if ( $acf_phone['field_phone_primary'] == '1' ) {
						foreach( $existing AS $key => $record ) {
							$existing[$key]['field_phone_primary'] = '0';
						}
					}

					// Overwrite array record.
					foreach( $existing AS $key => $record ) {
						if ( $phone->id == $record['field_phone_id'] ) {
							$existing[$key] = $acf_phone;
							break;
						}
					}

					break;

				case 'delete' :

					// Remove array record.
					foreach( $existing AS $key => $record ) {
						if ( $phone->id == $record['field_phone_id'] ) {
							unset( $existing[$key] );
							break;
						}
					}

					break;

			}

			// Now update Field.
			$this->acf_loader->acf->field->value_update( $selector, $existing, $post_id );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Phone Locations that are defined in CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @return array $location_types The array of possible Phone Locations.
	 */
	public function location_types_get() {

		// Init return.
		$location_types = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $location_types;
		}

		// Params to get all Location Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'options' => [
				'limit' => 0,
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'LocationType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $location_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $location_types;
		}

		// Assign results to return.
		$location_types = $result['values'];

		// --<
		return $location_types;

	}



	/**
	 * Get the Phone Locations that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $location_types The array of possible Phone Locations.
	 */
	public function location_types_get_for_acf_field( $field ) {

		// Init return.
		$location_types = [];

		// Get field group for this field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no field group.
		if ( empty( $field_group ) ) {
			return $location_types;
		}

		// Get Location Types.
		$location_types = $this->location_types_get();

		/**
		 * Filter the retrieved Location Types.
		 *
		 * @since 0.4
		 *
		 * @param array $location_types The retrieved array of Location Types.
		 * @param array $field The ACF Field data array.
		 * @return array $location_types The modified array of Location Types.
		 */
		$location_types = apply_filters(
			'cwps/acf/phone/location_types/get_for_acf_field',
			$location_types, $field
		);

		// --<
		return $location_types;

	}



	/**
	 * Get the Phone Types that are defined in CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @return array $phone_types The array of possible Phone Types.
	 */
	public function phone_types_get() {

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$phone_types = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phone_types;
		}

		// Get the Phone Types array.
		$phone_type_ids = CRM_Core_PseudoConstant::get( 'CRM_Core_DAO_Phone', 'phone_type_id' );

		// Bail if there are no results.
		if ( empty( $phone_type_ids ) ) {
			return $phone_types;
		}

		// Assign to return.
		$phone_types = $phone_type_ids;

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $phone_types;
		}

		// --<
		return $phone_types;

	}



	/**
	 * Get the Phone Types that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $location_types The array of possible Phone Types.
	 */
	public function phone_types_get_for_acf_field( $field ) {

		// Init return.
		$phone_types = [];

		// Get field group for this field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no field group.
		if ( empty( $field_group ) ) {
			return $phone_types;
		}

		// Get the Phone Types array.
		$phone_type_ids = $this->phone_types_get();

		// Bail if there are no results.
		if ( empty( $phone_type_ids ) ) {
			return $phone_types;
		}

		// Assign to return.
		$phone_types = $phone_type_ids;

		// --<
		return $phone_types;

	}



	/**
	 * Add any Phone Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param int|str $post_id The ACF "Post ID".
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Add if it has a reference to a Phone Field.
		if ( ! empty( $field['type'] ) AND $field['type'] == 'civicrm_phone' ) {
			$acf_fields['phone'][$field['name']] = $field['type'];
		}

		// --<
		return $acf_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Sync the CiviCRM "Phone ID" to the ACF Fields on a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param array $params The Phone data.
	 * @param array $args The array of WordPress params.
	 */
	public function maybe_sync_phone_id( $params, $args ) {

		// Get Entity reference.
		$entity = $this->acf_loader->acf->field->entity_type_get( $args['post_id'] );

		// Check permissions if it's a Post.
		if ( $entity === 'post' ) {
			if ( ! current_user_can( 'edit_post', $args['post_id'] ) ) {
				return;
			}
		}

		// Check permissions if it's a User.
		if ( $entity === 'user' ) {
			if ( ! current_user_can( 'edit_user', $args['user_id'] ) ) {
				return;
			}
		}

		// Maybe cast Phone as an object.
		if ( ! is_object( $params['phone'] ) ) {
			$params['phone'] = (object) $params['phone'];
		}

		// Get existing Field value.
		$existing = get_field( $params['selector'], $args['post_id'] );

		// Add Phone ID and overwrite array element.
		if ( ! empty( $existing[$params['key']] ) ) {
			$params['value']['field_phone_id'] = $params['phone']->id;
			$existing[$params['key']] = $params['value'];
		}

		// Now update Field.
		$this->acf_loader->acf->field->value_update( $params['selector'], $existing, $args['post_id'] );

	}



} // Class ends.




