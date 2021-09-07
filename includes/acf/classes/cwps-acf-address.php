<?php
/**
 * CiviCRM Address Class.
 *
 * Handles CiviCRM Address functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Address Class.
 *
 * A class that encapsulates CiviCRM Address functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Address {

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
	 * "CiviCRM Field" field value prefix in the ACF Field data.
	 *
	 * This distinguishes Address Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $address_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $address_field_prefix = 'caiaddress_';

	/**
	 * Public Address Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $address_fields The array of public Address Fields.
	 */
	public $address_fields = [
		'is_primary' => 'true_false',
		'is_billing' => 'true_false',
		'street_address' => 'text',
		'supplemental_address_1' => 'text',
		'supplemental_address_2' => 'text',
		'supplemental_address_3' => 'text',
		'city' => 'text',
		'county_id' => 'select',
		'state_province_id' => 'select',
		'country_id' => 'select',
		'postal_code' => 'text',
		'geo_code_1' => 'text',
		'geo_code_2' => 'text',
		'name' => 'text',
	];

	/**
	 * Address Fields to remove for the Bypass Location Rule.
	 *
	 * These are mapped for Past Type Sync, but need to be removed for the
	 * Bypass Location Rule because they are handled by custom ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $bypass_fields The Address Fields to remove for the Bypass Location Rule.
	 */
	public $bypass_fields_to_remove = [
		'county_id' => 'select',
		'state_province_id' => 'select',
		'country_id' => 'select',
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

		// Listen for queries from the ACF Bypass class.
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

		// Listen for queries from our ACF Field Group class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'text_settings_modify' ], 10, 2 );

		// Some Address "Text" Fields need their own validation.
		//add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a Country by its numeric ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $country_id The numeric ID of the Country.
	 * @return array $country The array of Country data.
	 */
	public function country_get_by_id( $country_id ) {

		// Init return.
		$country = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $country;
		}

		// Params to get the Address Type.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $country_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Country', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $country;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $country;
		}

 		// The result set should contain only one item.
		$country = array_pop( $result['values'] );

		// --<
		return $country;

	}



	/**
	 * Get a Country by its "short name".
	 *
	 * @since 0.4
	 *
	 * @param string $country_short The "short name" of the Country.
	 * @return array $country The array of Country data, empty on failure.
	 */
	public function country_get_by_short( $country_short ) {

		// Init return.
		$country = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $country;
		}

		// Params to get the Address Type.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'iso_code' => $country_short,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Country', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $country;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $country;
		}

 		// The result set should contain only one item.
		$country = array_pop( $result['values'] );

		// --<
		return $country;

	}



	/**
	 * Get a State/Province by its numeric ID.
	 *
	 * @since 0.4
	 *
	 * @return array $state_provinces The array of State/Province data.
	 */
	public function state_provinces_get() {

		// Init return.
		$state_provinces = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $state_provinces;
		}

		// Use CiviCRM Core method.
		$state_provinces = CRM_Core_PseudoConstant::stateProvince();

		// --<
		return $state_provinces;

	}



	/**
	 * Get a State/Province by its numeric ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $state_province_id The numeric ID of the State/Province.
	 * @return array $state_province The array of State/Province data.
	 */
	public function state_province_get_by_id( $state_province_id ) {

		// Init return.
		$state_province = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $state_province;
		}

		// Params to get the Address Type.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'state_province_id' => $state_province_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'StateProvince', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $state_province;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $state_province;
		}

 		// The result set should contain only one item.
		$state_province = array_pop( $result['values'] );

		// --<
		return $state_province;

	}



	/**
	 * Get a State/Province by its "short name".
	 *
	 * @since 0.4
	 *
	 * @param string $abbreviation The short name of the State/Province.
	 * @return array $state_province The array of State/Province data.
	 */
	public function state_province_get_by_short( $abbreviation ) {

		// Init return.
		$state_province = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $state_province;
		}

		// Params to get the Address Type.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'abbreviation' => $abbreviation,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'StateProvince', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $state_province;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $state_province;
		}

 		// The result set should contain only one item.
		$state_province = array_pop( $result['values'] );

		// --<
		return $state_province;

	}



	/**
	 * Get the data for Shared Addresses.
	 *
	 * @since 0.4
	 *
	 * @param integer $address_id The numeric ID of the Address.
	 * @param array $shared The array of Shared Address data.
	 */
	public function addresses_shared_get_by_id( $address_id ) {

		// Init return.
		$shared = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $shared;
		}

		// Construct params to find Shared Addresses.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'master_id' => $address_id,
			'options' => [ 'limit' => 0 ],
		];

		// Get Shared Addresses via API.
		$result = civicrm_api( 'Address', 'get', $params );

		// Bail on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			return $shared;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $shared;
		}

 		// Return the result set as an array of objects.
 		foreach( $result['values'] AS $item ) {
			$shared[] = (object) $item;
		}

		// --<
		return $shared;

	}



	/**
	 * Get the data for an Address.
	 *
	 * @since 0.4
	 *
	 * @param integer $address_id The numeric ID of the Address.
	 * @param object|boolean $address The Address data object, or false if none.
	 */
	public function address_get_by_id( $address_id ) {

		// Init return.
		$address = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $address;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $address_id,
		];

		// Get Address details via API.
		$result = civicrm_api( 'Address', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $address;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $address;
		}

 		// The result set should contain only one item.
		$address = (object) array_pop( $result['values'] );

		// --<
		return $address;

	}



	/**
	 * Get the data for a Contact's Address by Location Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer $location_type_id The numeric ID of the Address Location Type.
	 * @param object $address The array of Address data, or empty if none.
	 */
	public function address_get_by_location( $contact_id, $location_type_id ) {

		// Init return.
		$address = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $address;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'location_type_id' => $location_type_id,
		];

		// Get Address details via API.
		$result = civicrm_api( 'Address', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $address;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $address;
		}

 		// The result set should contain only one item.
		$address = (object) array_pop( $result['values'] );

		// --<
		return $address;

	}



	/**
	 * Get the Addresses for a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $addresses The array of data for the Addresses, or empty if none.
	 */
	public function addresses_get_by_contact_id( $contact_id ) {

		// Init return.
		$addresses = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $addresses;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
		];

		// Get Address details via API.
		$result = civicrm_api( 'Address', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $addresses;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $addresses;
		}

 		// Return the result set as an array of objects.
 		foreach( $result['values'] AS $item ) {
			$addresses[] = (object) $item;
		}

		// --<
		return $addresses;

	}



	/**
	 * Get the Primary Address for a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $address The Address data object, or false if none.
	 */
	public function address_get_primary_by_contact_id( $contact_id ) {

		// Init return.
		$address = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $addresses;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'is_primary' => 1,
			'contact_id' => $contact_id,
		];

		// Get Address details via API.
		$result = civicrm_api( 'Address', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $address;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $address;
		}

 		// The result set should contain only one item.
		$address = (object) array_pop( $result['values'] );

		// --<
		return $address;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a Location Type by its numeric ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $location_type_id The numeric ID of the Location Type.
	 * @return array $location_type The array of Location Type data.
	 */
	public function location_type_get_by_id( $location_type_id ) {

		// Init return.
		$location_type = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $location_type;
		}

		// Params to get the Address Type.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $location_type_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'LocationType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $location_type;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $location_type;
		}

 		// The result set should contain only one item.
		$location_type = array_pop( $result['values'] );

		// --<
		return $location_type;

	}



	/**
	 * Get the Location Types that are defined in CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @return array $location_types The array of possible Location Types.
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



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact's Address Record.
	 *
	 * If you want to "create" an Address Record, do not pass $data['id'] in. The
	 * presence of an ID will cause an update to that Address Record.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $data The Address data to update the Contact with.
	 * @return array|boolean $address The array of Address Record data, or false on failure.
	 */
	public function update( $contact_id, $data ) {

		// Init return.
		$address = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $address;
		}

		// Define params to create new Address Record.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Address', 'create', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $address;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $address;
		}

		// The result set should contain only one item.
		$address = array_pop( $result['values'] );

		// --<
		return $address;

	}



	/**
	 * Delete an Address Record in CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param integer $address_id The numeric ID of the Address Record.
	 * @return boolean $success True if successfully deleted, or false on failure.
	 */
	public function delete( $address_id ) {

		// Init return.
		$success = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Define params to delete this Address Record.
		$params = [
			'version' => 3,
			'id' => $address_id,
		];

		// Call the API.
		$result = civicrm_api( 'Address', 'delete', $params );

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



	/**
	 * Update a CiviCRM Contact's Address Record.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $data The Address data to save.
	 * @return array|boolean $address The array of Address data, or false on failure.
	 */
	public function address_record_update( $contact_id, $data ) {

		// Init return.
		$address = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $address;
		}

		// Get the current Address for this Location Type.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'location_type_id' => $data['location_type_id'],
		];

		// Call the CiviCRM API.
		$existing_address = civicrm_api( 'Address', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $existing_address['is_error'] ) AND $existing_address['is_error'] == 1 ) {
			return $address;
		}

		// Update the Address if there is an existing one.
		if ( ! empty( $existing_address['values'] ) ) {
			$existing = array_pop( $existing_address['values'] );
			$data['id'] = $existing['id'];
		}

		// Go ahead and update.
		$address = $this->update( $contact_id, $data );

		// --<
		return $address;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Address Fields.
	 *
	 * @since 0.5
	 *
	 * @param string $field_type The type of ACF Field.
	 * @param string $filter The token by which to filter the array of fields.
	 * @return array $fields The array of field names.
	 */
	public function civicrm_fields_get( $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[$filter] ) ) {
			return $pseudocache[$filter];
		}

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Address', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 AND ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Address Fields array.
				foreach ( $result['values'] AS $key => $value ) {
					if ( array_key_exists( $value['name'], $this->address_fields ) ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$filter] ) ) {
			$pseudocache[$filter] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Address Field options for a given Field ID.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the field.
	 * @return array $field The array of field data.
	 */
	public function get_by_name( $name ) {

		// Init return.
		$field = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'name' => $name,
			'action' => 'get',
		];

		// Call the API.
		$result = civicrm_api( 'Address', 'getfield', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $field;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $field;
		}

		// The result set is the item.
		$field = $result['values'];

		// --<
		return $field;

	}



	/**
	 * Get the mapped Address Field name if present.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing field data array.
	 * @return string|boolean $address_field_name The name of the Address Field, or false if none.
	 */
	public function address_field_name_get( $field ) {

		// Init return.
		$address_field_name = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Set the mapped Address Field name if present.
		if ( isset( $field[$acf_field_key] ) ) {
			if ( false !== strpos( $field[$acf_field_key], $this->address_field_prefix ) ) {
				$address_field_name = (string) str_replace( $this->address_field_prefix, '', $field[$acf_field_key] );
			}
		}

		/**
		 * Filter the Address Field name.
		 *
		 * @since 0.5
		 *
		 * @param integer $address_field_name The existing Address Field name.
		 * @param array $field The array of ACF Field data.
		 * @return integer $address_field_name The modified Address Field name.
		 */
		$address_field_name = apply_filters( 'cwps/acf/civicrm/address/address_field/name', $address_field_name, $field );

		// --<
		return $address_field_name;

	}



	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The existing Setting Field choices array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param array $entity_array The Entity and ID array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_bypass_settings_choices( $choices, $field, $field_group, $entity_array ) {

		// Pass if a Contact Entity is not present.
		if ( ! array_key_exists( 'contact', $entity_array ) ) {
			return $choices;
		}

		// Get the public fields on the Entity for this Field Type.
		$public_fields = $this->civicrm_fields_get( 'public' );
		$fields_for_entity = [];
		foreach ( $public_fields AS $key => $value ) {
			if ( $field['type'] == $this->address_fields[$value['name']] ) {
				// Skip the ones that are not needed in ACFE Forms.
				if ( ! array_key_exists( $value['name'], $this->bypass_fields_to_remove ) ) {
					$fields_for_entity[] = $value;
				}
			}
		}

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->civicrm->custom_field->get_for_entity_type( 'Address', '' );

		/**
		 * Filter the Custom Fields.
		 *
		 * @since 0.5
		 *
		 * @param array The initially empty array of filtered Custom Fields.
		 * @param array $custom_fields The CiviCRM Custom Fields array.
		 * @param array $field The ACF Field data array.
		 */
		$filtered_fields = apply_filters( 'cwps/acf/query_settings/custom_fields_filter', [], $custom_fields, $field );

		// Pass if not populated.
		if ( empty( $fields_for_entity ) AND empty( $filtered_fields ) ) {
			return $choices;
		}

		// Build Address Field choices array for dropdown.
		if ( ! empty( $fields_for_entity ) ) {
			$address_fields_label = esc_attr__( 'Address Fields', 'civicrm-wp-profile-sync' );
			foreach( $fields_for_entity AS $address_field ) {
				$choices[$address_fields_label][$this->address_field_prefix . $address_field['name']] = $address_field['title'];
			}
		}

		// Build Custom Field choices array for dropdown.
		if ( ! empty( $filtered_fields ) ) {
			$custom_field_prefix = $this->civicrm->custom_field_prefix();
			foreach( $filtered_fields AS $custom_group_name => $custom_group ) {
				$custom_fields_label = esc_attr( $custom_group_name );
				foreach( $custom_group AS $custom_field ) {
					$choices[$custom_fields_label][$custom_field_prefix . $custom_field['id']] = $custom_field['label'];
				}
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/address/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



	/**
	 * Modify the Settings of an ACF "Text" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function text_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'text' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->acf_loader->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) OR empty( $field[$key] ) ) {
			return $field;
		}

		// Get the mapped Address Field name if present.
		$address_field_name = $this->address_field_name_get( $field );
		if ( $address_field_name === false ) {
			return $field;
		}

		// Get Address Field data.
		$field_data = $this->get_by_name( $address_field_name );

		// Set the "maxlength" attribute.
		if ( ! empty( $field_data['maxlength'] ) ) {
			$field['maxlength'] = $field_data['maxlength'];
		}

		// --<
		return $field;

	}



	/**
	 * Validate the content of a Field.
	 *
	 * Some Address Fields require validation.
	 *
	 * @since 0.5
	 *
	 * @param boolean $valid The existing valid status.
	 * @param mixed $value The value of the Field.
	 * @param array $field The Field data array.
	 * @param string $input The input element's name attribute.
	 * @return string|boolean $valid A string to display a custom error message, boolean otherwise.
	 */
	public function value_validate( $valid, $value, $field, $input ) {

		// Bail if it's not required and is empty.
		if ( $field['required'] == '0' AND empty( $value ) ) {
			return $valid;
		}

		// Get the mapped Address Field name if present.
		$address_field_name = $this->address_field_name_get( $field );
		if ( $address_field_name === false ) {
			return $valid;
		}

		// Validate depending on the field name.
		switch ( $address_field_name ) {

			case 'duration' :
				// Must be an integer.
				if ( ! ctype_digit( $value ) ) {
					$valid = __( 'Must be an integer.', 'civicrm-wp-profile-sync' );
				}
				break;

		}

		// --<
		return $valid;

	}



} // Class ends.



