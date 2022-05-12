<?php
/**
 * CiviCRM Membership Class.
 *
 * Handles CiviCRM Membership functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Membership Class.
 *
 * A class that encapsulates CiviCRM Membership functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Membership {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * Public Membership Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $membership_fields The array of public Membership Fields.
	 */
	public $membership_fields = [
		//'status_id' => 'select',
		//'campaign_id' => 'select',
		'num_terms' => 'number',
		'source' => 'text',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm = $parent;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all free CiviCRM Membership Types.
	 *
	 * @since 0.5
	 *
	 * @return array $membership_types The array of free CiviCRM Membership Types.
	 */
	public function types_get_free() {

		// Init return.
		$membership_types = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $membership_types;
		}

		// Params to get all free Memberships.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'minimum_fee' => 0,
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'MembershipType', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $membership_types;
		}

		// Assign Membership Types data.
		$membership_types = $result['values'];

		// --<
		return $membership_types;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the current CiviCRM Memberships for a Contact.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer $type_id The numeric ID of the CiviCRM Membership Type.
	 * @param integer $status_id The numeric ID of the CiviCRM Membership Status.
	 * @return array $membership_data The array of Membership data for the CiviCRM Contact.
	 */
	public function get_for_contact( $contact_id, $type_id = 0, $status_id = 0 ) {

		// Init return.
		$membership_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $membership_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $membership_data;
		}

		// Params to query Memberships.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
			'active_only' => 1,
			'options' => [
				'sort' => 'end_date ASC',
			],
		];

		// Add the Membership Type ID if supplied.
		if ( $type_id !== 0 ) {
			$params['membership_type_id'] = $type_id;
		}

		// Add the Membership Status ID if supplied.
		if ( $status_id !== 0 ) {
			$params['status_id'] = $status_id;
		}

		// Call API.
		$result = civicrm_api( 'Membership', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_id' => $contact_id,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $membership_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $membership_data;
		}

		// Assign Memberships data.
		$membership_data = $result['values'];

		// --<
		return $membership_data;

	}



	/**
	 * Checks if a CiviCRM Contact has a current Membership of a given Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer $type_id The numeric ID of the CiviCRM Membership Type.
	 * @return bool $has_current True if the Contact is in the Group, or false otherwise.
	 */
	public function has_current( $contact_id, $type_id ) {

		// Assume not.
		$has_current = false;

		// Get the array of current Memberships.
		$memberships = $this->get_for_contact( $contact_id, $type_id );

		// No result means the Contact does not have a current Membership.
		$has_current = empty( $memberships ) ? false : true;

		// --<
		return $has_current;

	}



	/**
	 * Creates a CiviCRM Membership.
	 *
	 * @since 0.5
	 *
	 * @param array $data The array of CiviCRM Membership params.
	 * @return array|bool $membership The array of CiviCRM Membership data, or false on failure.
	 */
	public function create( $data ) {

		// Init return.
		$membership = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $membership;
		}

		// Define params to create new Membership.
		$params = [
			'version' => 3,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Membership', 'create', $params );

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
			return $membership;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $membership;
		}

		// The result set should contain only one item.
		$membership = array_pop( $result['values'] );

		// --<
		return $membership;

	}



	/**
	 * Update a CiviCRM Membership with a given set of data.
	 *
	 * This is an alias of `self::create()` except that we expect an ID to have
	 * been set in the data.
	 *
	 * @since 0.5
	 *
	 * @param array $data The CiviCRM Membership data.
	 * @return array|bool The array of data from the CiviCRM API, or false on failure.
	 */
	public function update( $data ) {

		// Log and bail if there's no Membership ID.
		if ( empty( $data['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numerical ID must be present to update a Membership.', 'civicrm-wp-profile-sync' ),
				'data' => $data,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Pass through.
		return $this->create( $data );

	}



	/**
	 * Gets the Membership data for a given ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $membership_id The numeric ID of the CiviCRM Membership.
	 * @return array|bool $membership The array of Membership data, or false otherwise.
	 */
	public function get_by_id( $membership_id ) {

		// Assume failure.
		$membership = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $membership;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $membership_id,
		];

		// Get details via API.
		$result = civicrm_api( 'Membership', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $membership;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $membership;
		}

		// The result set should contain only one item.
		$membership = array_pop( $result['values'] );

		// --<
		return $membership;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Membership Fields.
	 *
	 * @since 0.5
	 *
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function civicrm_fields_get( $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ] ) ) {
			return $pseudocache[ $filter ];
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
			'api_action' => 'create',
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Membership', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Membership Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->membership_fields ) ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ] ) ) {
			$pseudocache[ $filter ] = $fields;
		}

		// --<
		return $fields;

	}



} // Class ends.



