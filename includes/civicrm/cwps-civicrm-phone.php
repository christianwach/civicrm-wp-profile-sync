<?php
/**
 * CiviCRM Phone Class.
 *
 * Handles CiviCRM Phone functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Phone Class.
 *
 * A class that encapsulates CiviCRM Phone functionality.
 *
 * @since 0.5
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Phone {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->plugin;
		$this->civicrm = $parent;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/civicrm/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5.2
	 */
	public function register_hooks() {

	}



	/**
	 * Unregister hooks.
	 *
	 * @since 0.5.2
	 */
	public function unregister_hooks() {

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for a Phone Record.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param integer $phone_id The numeric ID of the Phone Record.
	 * @return array $phone The array of Phone Record data, or empty if none.
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
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
	 * Get the data for a Contact's Phone Records by Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer $location_type_id The numeric ID of the Phone Location Type.
	 * @param integer $phone_type_id The numeric ID of the Phone Type.
	 * @return array $phones The array of Phone Record data, or empty if none.
	 */
	public function phones_get_by_type( $contact_id, $location_type_id, $phone_type_id ) {

		// Init return.
		$phones = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phones;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'location_type_id' => $location_type_id,
			'phone_type_id' => $phone_type_id,
		];

		// Get Phone Record details via API.
		$result = civicrm_api( 'Phone', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $phones;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $phones;
		}

		// We want the result set.
		foreach ( $result['values'] as $value ) {
			$phones[] = (object) $value;
		}

		// --<
		return $phones;

	}



	/**
	 * Get the Phone Records for a given Contact ID.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
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
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
	 * Update a CiviCRM Contact's Phone Record.
	 *
	 * If you want to "create" a Phone Record, do not pass $data['id'] in. The
	 * presence of an ID will cause an update to that Phone Record.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $data The Phone data to update the Contact with.
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

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
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
	 * @since 0.5 Moved to this class.
	 *
	 * @param integer $phone_id The numeric ID of the Phone Record.
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
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
	 * Get the Phone Types that are defined in CiviCRM.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
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



	// -------------------------------------------------------------------------



	/**
	 * Get the Phone Field options for a given Field Name.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Field.
	 * @return array $field The array of Field data.
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
		$result = civicrm_api( 'Phone', 'getfield', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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



} // Class ends.



