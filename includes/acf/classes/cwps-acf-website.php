<?php
/**
 * CiviCRM Website Class.
 *
 * Handles CiviCRM Website functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Website Class.
 *
 * A class that encapsulates CiviCRM Website functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Website extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
	 * "CiviCRM Website" field key in the ACF Field data.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $acf_field_key The key of the "CiviCRM Website" in the ACF Field data.
	 */
	public $acf_field_key = 'field_cacf_civicrm_website';

	/**
	 * Contact Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $fields_handled The array of Contact Fields which must be handled separately.
	 */
	public $fields_handled = [
		'url',
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

		// Add any Website Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Add any Website Fields that are Custom Fields.
		add_filter( 'cwps/acf/contact/custom_field/id_get', [ $this, 'custom_field_id_get' ], 10, 2 );

		// Intercept Post created from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Listen for events from our Mapper that require Website updates.
		add_action( 'cwps/acf/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		add_action( 'cwps/acf/mapper/website/created', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/acf/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/acf/mapper/website/deleted', [ $this, 'website_edited' ], 10 );

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		remove_action( 'cwps/acf/mapper/website/created', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/website/deleted', [ $this, 'website_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact's Fields with data from ACF Fields.
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
	 * Update a CiviCRM Contact's Field with data from an ACF Field.
	 *
	 * These Fields require special handling because they are not part
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

		// Skip if it's not an ACF Field Type that this class handles.
		if ( ! in_array( $settings['type'], $this->fields_handled ) ) {
			return true;
		}

		// Get the "CiviCRM Website" key.
		$website_key = $this->acf_field_key_get();

		// Skip if we don't have a synced Website.
		if ( empty( $settings[$website_key] ) ) {
			return true;
		}

		// Skip if it maps to a Custom Field.
		if ( false !== strpos( $settings[$website_key], $this->civicrm->custom_field_prefix() ) ) {
			return true;
		}

		// Parse value by field type.
		$value = $this->acf_loader->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings );

		// The ID of the Website Type is the setting.
		$website_type_id = $settings[$website_key];

		// Update the Website.
		$this->website_update( $website_type_id, $contact_id, $value );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for a Website.
	 *
	 * @since 0.4
	 *
	 * @param integer $website_id The numeric ID of the Website.
	 * @param array $website The array of Website data, or empty if none.
	 */
	public function website_get_by_id( $website_id ) {

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
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
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
	 * Get the Websites for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $website_data The array of Website data for the CiviCRM Contact.
	 */
	public function websites_get_for_contact( $contact_id ) {

		// Init return.
		$website_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $website_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website_data;
		}

		// Define params to get queried Websites.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Website', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $website_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $website_data;
		}

		// The result set it what we want.
		$website_data = $result['values'];

		// --<
		return $website_data;

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

		// Get all Websites for this Contact.
		$data = $this->websites_get_for_contact( $args['objectId'] );

		// Bail if there are no Website Fields.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Website Fields.
		if ( empty( $acf_fields['website'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach( $acf_fields['website'] AS $selector => $website_field ) {

			// Let's look at each Website in turn.
			foreach( $data AS $website ) {

				// Cast as object.
				$website = (object) $website;

				// Skip if it's a Custom Field.
				if ( false !== strpos( $website_field, $this->civicrm->custom_field_prefix() ) ) {
					continue;
				}

				// Skip if it's not the right Website Type.
				if ( $website_field != $website->website_type_id ) {
					continue;
				}

				// Get the URL safely.
				$url = empty( $website->url ) ? '' : $website->url;

				// Update it.
				$this->acf_loader->acf->field->value_update( $selector, $url, $args['post_id'] );

			}

		}

	}



	/**
	 * Update a CiviCRM Contact's Website.
	 *
	 * @since 0.4
	 *
	 * @param integer $website_type_id The numeric ID of the Website Type.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $value The Website URL to update the Contact with.
	 * @return array|boolean $website The array of Website data, or false on failure.
	 */
	public function website_update( $website_type_id, $contact_id, $value ) {

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



	// -------------------------------------------------------------------------



	/**
	 * A CiviCRM Contact's Website is about to be edited.
	 *
	 * Before a Website is edited, we need to store the previous data so that
	 * we can compare with the data after the edit. If there are changes, then
	 * we will need to update accordingly.
	 *
	 * This is not required for Website creation or deletion.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_edit( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->website_pre ) ) {
			unset( $this->website_pre );
		}

		// Grab Website object.
		$website = $args['objectRef'];

		// We need a Contact ID in the edited Website.
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Grab the previous Website data from the database.
		$this->website_pre = $this->website_get_by_id( $website->id );

	}



	/**
	 * Intercept when a CiviCRM Website has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_edited( $args ) {

		// Grab the Website data.
		$website = $args['objectRef'];

		// Bail if this is not a Contact's Website.
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Get the Contact data.
		$contact = $this->acf_loader->civicrm->contact->get_by_id( $website->contact_id );

		// Test if any of this Contact's Contact Types is mapped.
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
				$this->fields_update( $post_id, $website );

			}

		}

		/**
		 * Broadcast that a Website ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $website The CiviCRM Website object.
		 */
		do_action( 'cwps/acf/website/updated', $contact, $website );

	}



	/**
	 * Update Website ACF Fields on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object $website The CiviCRM Website Record object.
	 */
	public function fields_update( $post_id, $website ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Website Fields.
		if ( empty( $acf_fields['website'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach( $acf_fields['website'] AS $selector => $website_field ) {

			// Skip if it's a Custom Field.
			if ( false !== strpos( $website_field, $this->civicrm->custom_field_prefix() ) ) {
				continue;
			}

			// Skip if it's not the right Website Type.
			if ( $website_field != $website->website_type_id ) {
				continue;
			}

			// Update it.
			$this->acf_loader->acf->field->value_update( $selector, $website->url, $post_id );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Filter the Custom Field ID.
	 *
	 * Some Website Fields may be mapped to CiviCRM Custom Fields. This filter
	 * teases out which ones and, if they are mapped to a Custom Field, returns
	 * their Custom Field ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $custom_field_id The existing Custom Field ID.
	 * @param array $field The array of ACF Field data.
	 * @return integer $custom_field_id The modified Custom Field ID.
	 */
	public function custom_field_id_get( $custom_field_id, $field ) {

		// Get the "CiviCRM Website" key.
		$website_key = $this->acf_field_key_get();

		// Return it if the Field has a reference to a Website Custom Field.
		if ( ! empty( $field[$website_key] ) ) {
			if ( false !== strpos( $field[$website_key], $this->civicrm->custom_field_prefix() ) ) {
				$custom_field_id = absint( str_replace( $this->civicrm->custom_field_prefix(), '', $field[$website_key] ) );
			}
		}

		// --<
		return $custom_field_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Website Types that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $website_types The array of possible Website Types.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$website_types = [];

		// Get field group for this field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no field group.
		if ( empty( $field_group ) ) {
			return $website_types;
		}

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
	 * Return the "CiviCRM Website" ACF Settings Field.
	 *
	 * @since 0.4
	 *
	 * @param array $custom_fields The Custom Fields to populate the ACF Field with.
	 * @param array $website_types The Website Types to populate the ACF Field with.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $custom_fields = [], $website_types = [] ) {

		// Bail if empty.
		if ( empty( $website_types ) ) {
			return;
		}

		// Build choices array for dropdown.
		$choices = [];

		// Build Website Types choices array for dropdown.
		$website_types_label = esc_attr__( 'Contact Website Type', 'civicrm-wp-profile-sync' );
		foreach( $website_types AS $website_type_id => $website_type_name ) {
			$choices[$website_types_label][$website_type_id] = esc_attr( $website_type_name );
		}

		// Build Custom Field choices array for dropdown.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			$custom_fields_label = esc_attr( $custom_group_name );
			foreach( $custom_group AS $custom_field ) {
				$choices[$custom_fields_label][$this->civicrm->custom_field_prefix() . $custom_field['id']] = $custom_field['label'];
			}
		}

		// Define field.
		$field = [
			'key' => $this->acf_field_key_get(),
			'label' => __( 'CiviCRM Website', 'civicrm-wp-profile-sync' ),
			'name' => $this->acf_field_key_get(),
			'type' => 'select',
			'instructions' => __( 'Choose the CiviCRM Website Field that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
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
	 * Getter method for the "CiviCRM Website" key.
	 *
	 * @since 0.4
	 *
	 * @return string $acf_field_key The key of the "CiviCRM Website" in the ACF Field data.
	 */
	public function acf_field_key_get() {

		// --<
		return $this->acf_field_key;

	}



	/**
	 * Add any Website Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Get the "CiviCRM Website" key.
		$website_key = $this->acf_field_key_get();

		// Add if it has a reference to a Website Field.
		if ( ! empty( $field[$website_key] ) ) {
			$acf_fields['website'][$field['name']] = $field[$website_key];
		}

		// --<
		return $acf_fields;

	}



} // Class ends.



