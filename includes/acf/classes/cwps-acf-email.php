<?php
/**
 * CiviCRM Email Class.
 *
 * Handles CiviCRM Email functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Email Class.
 *
 * A class that encapsulates CiviCRM Email functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Email extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
	 * "CiviCRM Email" field key in the ACF Field data.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $acf_field_key The key of the "CiviCRM Email" in the ACF Field data.
	 */
	public $acf_field_key = 'field_cacf_civicrm_email';

	/**
	 * Contact Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $fields_handled The array of Contact Fields which must be handled separately.
	 */
	public $fields_handled = [
		'email',
	];



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

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add any Email Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post created from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Listen for events from our Mapper that require Email updates.
		add_action( 'cwps/acf/mapper/email/created', [ $this, 'email_edited' ], 10 );
		add_action( 'cwps/acf/mapper/email/edited', [ $this, 'email_edited' ], 10 );
		add_action( 'cwps/acf/mapper/email/deleted', [ $this, 'email_edited' ], 10 );

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/email/created', [ $this, 'email_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/email/edited', [ $this, 'email_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/email/deleted', [ $this, 'email_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update CiviCRM Email Fields with data from ACF Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return boolean True if updates were successful, or false on failure.
	 */
	public function fields_handled_update( $args ) {

		// Bail if we have no field data to save.
		if ( empty( $args['fields'] ) ) {
			return true;
		}

		// Init success.
		$success = true;

		// Loop through the field data.
		foreach( $args['fields'] AS $field => $value ) {

			// Get the field settings.
			$settings = get_field_object( $field, $args['post_id'] );

			// Maybe update a Contact Field.
			$this->field_handled_update( $field, $value, $args['contact']['id'], $settings );

		}

		// --<
		return $success;

	}



	/**
	 * Update a CiviCRM Email Field with data from an ACF Field.
	 *
	 * This Contact Field requires special handling because it is not part
	 * of the core Contact data.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data.
	 * @param mixed $value The ACF Field value.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $settings The ACF Field settings.
	 * @return boolean True if updates were successful, or false on failure.
	 */
	public function field_handled_update( $field, $value, $contact_id, $settings ) {

		// Skip if it's not a Field that this class handles.
		if ( ! in_array( $settings['type'], $this->fields_handled ) ) {
			return true;
		}

		// Get the "CiviCRM Email" key.
		$email_key = $this->acf_field_key_get();

		// Skip if we don't have a synced Email.
		if ( empty( $settings[$email_key] ) ) {
			return true;
		}

		// Parse value by field type.
		$value = $this->acf_loader->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings );

		// Is this mapped to the Primary Email?
		if ( $settings[$email_key] == 'primary' ) {

			// Update and return early.
			$this->primary_email_update( $contact_id, $value );
			return true;

		}

		// The ID of the Location Type is the setting.
		$location_type_id = absint( $settings[$email_key] );

		// Update the Email.
		$this->email_update( $location_type_id, $contact_id, $value );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Primary Email for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return object|boolean $email The Primary Email data object, or false on failure.
	 */
	public function primary_email_get( $contact_id ) {

		// Init return.
		$email = false;

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $email;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Define params to get queried Email.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_primary' => 1,
			'contact_id' => $contact_id,
		];

		// Call the API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
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
	 * Update a CiviCRM Contact's Primary Email.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $value The email to update the Contact with.
	 * @return array|boolean $email The array of Email data, or false on failure.
	 */
	public function primary_email_update( $contact_id, $value ) {

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
		$primary_email = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $primary_email['is_error'] ) AND $primary_email['is_error'] == 1 ) {
			return $email;
		}

		// Create a Primary Email if there are no results.
		if ( empty( $primary_email['values'] ) ) {

			// Define params to create new Primary Email.
			$params = [
				'version' => 3,
				'contact_id' => $contact_id,
				'is_primary' => 1,
				'email' => $value,
			];

			// Call the API.
			$result = civicrm_api( 'Email', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $primary_email['values'] );

			// Bail if it hasn't changed.
			if ( $existing_data['email'] == $value ) {
				return $existing_data;
			}

			// If there is an incoming value, update.
			if ( ! empty( $value ) ) {

				// Define params to update this Email.
				$params = [
					'version' => 3,
					'id' => $primary_email['id'],
					'contact_id' => $contact_id,
					'email' => $value,
				];

				// Call the API.
				$result = civicrm_api( 'Email', 'create', $params );

			} else {

				// Define params to delete this Email.
				$params = [
					'version' => 3,
					'id' => $primary_email['id'],
				];

				// Call the API.
				$result = civicrm_api( 'Email', 'delete', $params );

				// Bail early.
				return $email;

			}

		}

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = array_pop( $result['values'] );

		// --<
		return $email;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Emails for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $email_data The array of Email data for the CiviCRM Contact.
	 */
	public function emails_get_for_contact( $contact_id ) {

		// Init return.
		$email_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $email_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email_data;
		}

		// Define params to get queried Emails.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $email_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email_data;
		}

		// The result set it what we want.
		$email_data = $result['values'];

		// --<
		return $email_data;

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

		// Get all Emails for this Contact.
		$data = $this->emails_get_for_contact( $args['objectId'] );

		// Bail if there are no Email Fields.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Email Fields.
		if ( empty( $acf_fields['email'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach( $acf_fields['email'] AS $selector => $email_field ) {

			// Let's look at each Email in turn.
			foreach( $data AS $email ) {

				// Cast as object.
				$email = (object) $email;

				// If this is mapped to the Primary Email.
				if ( $email_field == 'primary' AND $email->is_primary == '1' ) {
					$this->acf_loader->acf->field->value_update( $selector, $email->email, $args['post_id'] );
					continue;
				}

				// Skip if the Location Types don't match.
				if ( $email_field != $email->location_type_id ) {
					continue;
				}

				// Update it.
				$this->acf_loader->acf->field->value_update( $selector, $email->email, $args['post_id'] );

			}

		}

	}



	/**
	 * Update a CiviCRM Contact's Email.
	 *
	 * @since 0.4
	 *
	 * @param integer $location_type_id The numeric ID of the Location Type.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $value The Email to update the Contact with.
	 * @return array|boolean $email The array of Email data, or false on failure.
	 */
	public function email_update( $location_type_id, $contact_id, $value ) {

		// Init return.
		$email = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Get the current Email for this Location Type.
		$params = [
			'version' => 3,
			'location_type_id' => $location_type_id,
			'contact_id' => $contact_id,
		];

		// Call the CiviCRM API.
		$existing_email = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $existing_email['is_error'] ) AND $existing_email['is_error'] == 1 ) {
			return $email;
		}

		// Create a new Email if there are no results.
		if ( empty( $existing_email['values'] ) ) {

			// Define params to create new Email.
			$params = [
				'version' => 3,
				'location_type_id' => $location_type_id,
				'contact_id' => $contact_id,
				'email' => $value,
			];

			// Call the API.
			$result = civicrm_api( 'Email', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $existing_email['values'] );

			// Bail if it hasn't changed.
			if ( $existing_data['email'] == $value ) {
				return $existing_data;
			}

			// If there is an incoming value, update.
			if ( ! empty( $value ) ) {

				// Define params to update this Email.
				$params = [
					'version' => 3,
					'id' => $existing_email['id'],
					'contact_id' => $contact_id,
					'email' => $value,
				];

				// Call the API.
				$result = civicrm_api( 'Email', 'create', $params );

			} else {

				// Define params to delete this Email.
				$params = [
					'version' => 3,
					'id' => $existing_email['id'],
				];

				// Call the API.
				$result = civicrm_api( 'Email', 'delete', $params );

				// Bail early.
				return $email;

			}

		}

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = array_pop( $result['values'] );

		// --<
		return $email;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Email has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function email_edited( $args ) {

		// Grab the Email data.
		$email_data = $args['objectRef'];

		// Bail if this is not a Contact's Email.
		if ( empty( $email_data->contact_id ) ) {
			return;
		}

		// Get the Contact data.
		$contact = $this->acf_loader->civicrm->contact->get_by_id( $email_data->contact_id );
		if ( $contact === false ) {
			return;
		}

		// Data may be missing for some operations, so get the full Email record.
		$email = $this->primary_email_get( $email_data->contact_id );
		if ( empty( $email->contact_id ) ) {
			return;
		}

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

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $email );

			}

		}

		/**
		 * Broadcast that an Email ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $email The CiviCRM Email object.
		 */
		do_action( 'cwps/acf/email/updated', $contact, $email );

	}



	/**
	 * Update an Email ACF Field on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object $email The CiviCRM email object.
	 */
	public function fields_update( $post_id, $email ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Email Fields.
		if ( empty( $acf_fields['email'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach( $acf_fields['email'] AS $selector => $email_field ) {

			// If this is mapped to the Primary Email.
			if ( $email_field == 'primary' AND $email->is_primary == '1' ) {
				$this->acf_loader->acf->field->value_update( $selector, $email->email, $post_id );
				continue;
			}

			// Skip if the Location Types don't match.
			if ( $email_field != $email->location_type_id ) {
				continue;
			}

			// Update it.
			$this->acf_loader->acf->field->value_update( $selector, $email->email, $post_id );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Location Types that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $location_types The array of possible Location Types.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$location_types = [];

		// Get field group for this field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no field group.
		if ( empty( $field_group ) ) {
			return $location_types;
		}

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
			'cwps/acf/email/location_types/get_for_acf_field',
			$result['values'], $field
		);

		// --<
		return $location_types;

	}



	/**
	 * Return the "CiviCRM Email" ACF Settings Field.
	 *
	 * @since 0.4
	 *
	 * @param array $location_types The Location Types to populate the ACF Field with.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $location_types = [] ) {

		// Bail if empty.
		if ( empty( $location_types ) ) {
			return;
		}

		// Build choices array for dropdown.
		$choices = [];

		// Prepend "Primary Email" choice for dropdown.
		$specific_email_label = esc_attr__( 'Specific Emails', 'civicrm-wp-profile-sync' );
		$choices[$specific_email_label]['primary'] = esc_attr__( 'Primary Email', 'civicrm-wp-profile-sync' );

		// Build Location Types choices array for dropdown.
		$location_types_label = esc_attr__( 'Location Types', 'civicrm-wp-profile-sync' );
		foreach( $location_types AS $location_type ) {
			$choices[$location_types_label][$location_type['id']] = esc_attr( $location_type['display_name'] );
		}

		// Define field.
		$field = [
			'key' => $this->acf_field_key_get(),
			'label' => __( 'CiviCRM Email', 'civicrm-wp-profile-sync' ),
			'name' => $this->acf_field_key_get(),
			'type' => 'select',
			'instructions' => __( 'Choose the CiviCRM Email that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'required' => 0,
			'return_format' => 'value',
			'parent' => $this->acf_loader->acf->field_group->placeholder_group_get(),
			'choices' => $choices,
		];

		// --<
		return $field;

	}



	/**
	 * Getter method for the "CiviCRM Email" key.
	 *
	 * @since 0.4
	 *
	 * @return string $acf_field_key The key of the "CiviCRM Email" in the ACF Field data.
	 */
	public function acf_field_key_get() {

		// --<
		return $this->acf_field_key;

	}



	/**
	 * Add any Email Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Get the "CiviCRM Email" key.
		$email_key = $this->acf_field_key_get();

		// Add if it has a reference to an Email Field.
		if ( ! empty( $field[$email_key] ) ) {
			$acf_fields['email'][$field['name']] = $field[$email_key];
		}

		// --<
		return $acf_fields;

	}



} // Class ends.



