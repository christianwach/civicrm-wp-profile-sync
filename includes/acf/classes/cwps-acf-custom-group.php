<?php
/**
 * CiviCRM Custom Group Class.
 *
 * Handles CiviCRM Custom Group functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Custom Group Class.
 *
 * A class that encapsulates CiviCRM Custom Group functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Custom_Group {

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
	 * Get all Custom Groups.
	 *
	 * @since 0.4
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_all() {

		// Init array to build.
		$custom_groups = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_groups;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $custom_groups;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_groups;
		}

 		// The result set is what we want.
		$custom_groups = $result['values'];

		// --<
		return $custom_groups;

	}



	/**
	 * Get all the Custom Fields for all CiviCRM Contact Types/Subtypes.
	 *
	 * @since 0.5
	 *
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_contacts() {

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'options' => [
				'limit' => 0,
			],
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0,
				]
			],
			'extends' => [
				'IN' => [ "Individual", "Organization", "Household" ],
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $custom_fields;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_fields;
		}

 		// The result set is what we want.
		$custom_fields = $result['values'];

		// --<
		return $custom_fields;

	}



	/**
	 * Get all the Custom Fields for all CiviCRM Activity Types.
	 *
	 * @since 0.5
	 *
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_activities() {

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'options' => [
				'limit' => 0,
			],
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0,
				]
			],
			'extends' => 'Activity',
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $custom_fields;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_fields;
		}

 		// The result set is what we want.
		$custom_fields = $result['values'];

		// --<
		return $custom_fields;

	}



	/**
	 * Get all the Custom Fields for all CiviCRM Case Types.
	 *
	 * @since 0.5
	 *
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_cases() {

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'options' => [
				'limit' => 0,
			],
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0,
				]
			],
			'extends' => 'Case',
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $custom_fields;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_fields;
		}

 		// The result set is what we want.
		$custom_fields = $result['values'];

		// --<
		return $custom_fields;

	}



	/**
	 * Get a Custom Group by its ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $custom_group_id The numeric ID of the Custom Group.
	 * @return array $custom_group The array of Custom Group data.
	 */
	public function get_by_id( $custom_group_id ) {

		// Init return.
		$custom_group = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_group;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $custom_group_id,
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $custom_group;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_group;
		}

 		// The result set should contain only one item.
		$custom_group = array_pop( $result['values'] );

		// --<
		return $custom_group;

	}



	/**
	 * Get the Custom Groups for a CiviCRM Entity Type/Subtype.
	 *
	 * @since 0.4
	 *
	 * @param string $type The Entity Type that the Custom Group applies to.
	 * @param string $subtype The Entity Sub-type that the Custom Group applies to.
	 * @param boolean $with_fields Pass "true" to retrieve the Custom Fields as well.
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_entity_type( $type = '', $subtype = '', $with_fields = false ) {

		// Maybe set a key for the subtype.
		$key = $subtype;
		if ( empty( $subtype ) ) {
			$key = 'none';
		}

		// Maybe set a key for the boolean.
		$subkey = 'raw';
		if ( ! empty( $with_fields ) ) {
			$subkey = '$with_fields';
		}

		// Only do this once per Entity Type.
		static $pseudocache;
		if ( isset( $pseudocache[$type][$key][$subkey] ) ) {
			return $pseudocache[$type][$key][$subkey];
		}

		// Init return.
		$custom_groups = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_groups;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'extends' => $type,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// If there's an Entity Sub-type, add that.
		if ( ! empty( $subtype ) ) {
			$params['extends_entity_column_value'] = $subtype;
		}

		// Maybe include Fields query.
		if ( ! empty( $with_fields ) ) {
			$params['api.CustomField.get'] = [
				'is_active' => 1,
				'options' => [
					'limit' => 0, // No limit.
				],
			];
		}

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $custom_groups;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_groups;
		}

 		// The result set is what we want.
		$custom_groups = $result['values'];

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$type][$key][$subkey] ) ) {
			$pseudocache[$type][$key][$subkey] = $custom_groups;
		}

		// --<
		return $custom_groups;

	}



} // Class ends.



