<?php
/**
 * CiviCRM Event Class.
 *
 * Handles CiviCRM Event functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Event Class.
 *
 * A class that encapsulates CiviCRM Event functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Event {

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

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'register_hooks' ] );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Add AJAX callback.
		add_action( 'wp_ajax_event_type_get_value', [ $this, 'ajax_event_type_get' ] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Event data for a given set of IDs.
	 *
	 * @since 0.5
	 *
	 * @param array $event_ids The array of numeric IDs of the CiviCRM Events to query.
	 * @return array|bool $event_data An array of Event data, or false on failure.
	 */
	public function get_by_ids( $event_ids = [] ) {

		// Init return.
		$event_data = false;

		// Bail if we have no Event IDs.
		if ( empty( $event_ids ) ) {
			return $event_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $event_data;
		}

		// Define params to get queried Events.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => [ 'IN' => $event_ids ],
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Event', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $event_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $event_data;
		}

		// --<
		return $result['values'];

	}



	/**
	 * Get the CiviCRM Event data for a given ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $event_id The numeric ID of the CiviCRM Event to query.
	 * @return array|bool $event_data An array of Event data, or false on failure.
	 */
	public function get_by_id( $event_id ) {

		// Init return.
		$event_data = false;

		// Bail if we have no Event ID.
		if ( empty( $event_id ) ) {
			return $event_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $event_data;
		}

		// Define params to get queried Event.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $event_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Event', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $event_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $event_data;
		}

		// The result set should contain only one item.
		$event_data = array_pop( $result['values'] );

		// --<
		return $event_data;

	}



	/**
	 * Gets the "is_full" status of a CiviCRM Event.
	 *
	 * @since 0.5
	 *
	 * @param integer $event_id The numeric ID of the CiviCRM Event to query.
	 * @return int|bool $is_full Numeric 1 if the Event is full, 0 if not. False on failure.
	 */
	public function is_full( $event_id ) {

		// Init return.
		$is_full = false;

		// Bail if we have no Event ID.
		if ( empty( $event_id ) ) {
			return $is_full;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $is_full;
		}

		// Define params to get queried Event.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $event_id,
			'return' => [
				'is_full',
			],
			'options' => [
				'limit' => 1,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Event', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $is_full;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $is_full;
		}

		// The result set should contain only one item.
		$event_data = array_pop( $result['values'] );

		// Assign return.
		if ( $event_data['is_full'] ) {
			$is_full = 1;
		} else {
			$is_full = 0;
		}

		// --<
		return $is_full;

	}



	/**
	 * Get the CiviCRM Event data for a given search string.
	 *
	 * @since 0.5
	 *
	 * @param string $search The search string to query.
	 * @return array|bool $event_data An array of Event data, or false on failure.
	 */
	public function get_by_search_string( $search ) {

		// Init return.
		$event_data = false;

		// Bail if we have no Event ID.
		if ( empty( $search ) ) {
			return $event_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $event_data;
		}

		// Define params to get queried Event.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'input' => $search,
			'title' => 'label',
			'search_field' => 'title',
			'label_field' => 'title',
			'options' => [
				'limit' => 25, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Event', 'getlist', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $event_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $event_data;
		}

		// --<
		return $result['values'];

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all CiviCRM Event Types.
	 *
	 * @since 0.5
	 *
	 * @return array|bool $event_types Event Types array, or false on failure.
	 */
	public function types_get() {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Get Option Group ID.
		$option_group_id = $this->type_option_group_id_get();
		if ( $option_group_id === false ) {
			return false;
		}

		// Define params to get items sorted by weight.
		$params = [
			'option_group_id' => $option_group_id,
			'version' => 3,
			'options' => [
				'sort' => 'weight ASC',
			],
		];

		// Get them - descriptions will be present if not null.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if we get an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'result' => $result,
				'params' => $params,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return false;
		}

		// The result set is what we want.
		$event_types = $result['values'];

		// --<
		return $event_types;

	}



	/**
	 * Get all CiviCRM Event Types as a formatted array.
	 *
	 * @since 0.5
	 *
	 * @return array $options The array of Event Types, or empty on failure.
	 */
	public function types_get_options() {

		// Get all the Event Types.
		$event_types = $this->types_get();
		if ( $event_types === false ) {
			return [];
		}

		// Build array.
		$options = [ '' => __( 'None', 'civicrm-wp-profile-sync' ) ];
		foreach ( $event_types as $event_type ) {
			$options[ $event_type['value'] ] = $event_type['label'];
		}

		// --<
		return $options;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the numeric ID of the "Event Types" Option Group.
	 *
	 * @since 0.5
	 *
	 * @return integer|bool $option_group_id The ID of the Option Group, or false on failure.
	 */
	public function type_option_group_id_get() {

		// Only do this once.
		static $option_group_id;
		if ( isset( $option_group_id ) ) {
			return $option_group_id;
		}

		// Init return.
		$option_group_id = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $option_group_id;
		}

		// Define params to get Event Types Option Group.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'name' => 'event_type',
		];

		// Call API.
		$result = civicrm_api( 'OptionGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $option_group_id;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $option_group_id;
		}

		// The result set should contain only one item.
		$option_group = array_pop( $result['values'] );

		// Assign the ID.
		$option_group_id = (int) $option_group['id'];

		// --<
		return $option_group_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for an "Event Type" by ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $event_type_id The numeric ID of the Event Type.
	 * @return array|bool $event_type An array of Event Type data, or false on failure.
	 */
	public function type_get_by_id( $event_type_id ) {

		// Init return.
		$event_type = false;

		// Bail if we have no Event Type ID.
		if ( empty( $event_type_id ) ) {
			return $event_type;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $event_type;
		}

		// Define params to get queried Participant.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $event_type_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $event_type;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $event_type;
		}

		// The result set should contain only one item.
		$event_type = array_pop( $result['values'] );

		// --<
		return $event_type;

	}



	/**
	 * Get the data for an "Event Type" by value.
	 *
	 * @since 0.5
	 *
	 * @param integer $event_type_value The numeric value of the Event Type.
	 * @return array|bool $event_type An array of Event Type data, or false on failure.
	 */
	public function type_get_by_value( $event_type_value ) {

		// Init return.
		$event_type = false;

		// Bail if we have no Event Type value.
		if ( empty( $event_type_value ) ) {
			return $event_type;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $event_type;
		}

		// Define params to get queried Participant.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'value' => $event_type_value,
			'option_group_id' => $this->type_option_group_id_get(),
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $event_type;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $event_type;
		}

		// The result set should contain only one item.
		$event_type = array_pop( $result['values'] );

		// --<
		return $event_type;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Event Type value for an AJAX request.
	 *
	 * @since 0.5
	 */
	public function ajax_event_type_get() {

		// Data response.
		$data = [ 'success' => false ];

		// Get Event ID from POST.
		$event_id = empty( $_POST['value'] ) ? false : (int) trim( $_POST['value'] );
		if ( $event_id === false ) {
			wp_send_json( $data );
		}

		// Get the Event data.
		$event = $this->get_by_id( $event_id );
		if ( $event === false ) {
			wp_send_json( $data );
		}

		// Data response.
		$data = [
			'result' => $event['event_type_id'],
			'success' => true,
		];

		// Return the data.
		wp_send_json( $data );

	}



} // Class ends.



