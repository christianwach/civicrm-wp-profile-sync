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

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a Country by its numeric ID.
	 *
	 * @since 0.4
	 *
	 * @param int $country_id The numeric ID of the Country.
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
	 * @param int $country_short The "short name" of the Country.
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
	 * @param int $state_province_id The numeric ID of the State/Province.
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
	 * @param str $abbreviation The short name of the State/Province.
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
	 * @param int $address_id The numeric ID of the Address.
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
	 * @param int $address_id The numeric ID of the Address.
	 * @param object|bool $address The Address data object, or false if none.
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
	 * Get the Addresses for a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param int $contact_id The numeric ID of the Contact.
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
	 * @param int $contact_id The numeric ID of the Contact.
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
	 * @param int $location_type_id The numeric ID of the Location Type.
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
	 * @param int $contact_id The numeric ID of the Contact.
	 * @param str $data The Address data to update the Contact with.
	 * @return array|bool $address The array of Address Record data, or false on failure.
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
	 * @param int $address_id The numeric ID of the Address Record.
	 * @return bool $success True if successfully deleted, or false on failure.
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



} // Class ends.



