<?php
/**
 * CiviCRM Event Type Class.
 *
 * Handles CiviCRM Event Type functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Event Type Class.
 *
 * A class that encapsulates CiviCRM Event Type functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Event_Type {

	/**
	 * Plugin object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;



	/**
	 * Constructor.
	 *
	 * @since 0.5.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5.4
	 */
	public function register_hooks() {

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the numeric ID of the "Event Types" Option Group.
	 *
	 * @since 0.5.4
	 *
	 * @return integer|bool $option_group_id The ID of the Option Group, or false on failure.
	 */
	public function option_group_id_get() {

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
	 * @since 0.5.4
	 *
	 * @param integer $event_type_id The numeric ID of the Event Type.
	 * @return array|bool $event_type An array of Event Type data, or false on failure.
	 */
	public function get_by_id( $event_type_id ) {

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

		// Define params to get queried Event.
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
	 * @since 0.5.4
	 *
	 * @param integer $event_type_value The numeric value of the Event Type.
	 * @return array|bool $event_type An array of Event Type data, or false on failure.
	 */
	public function get_by_value( $event_type_value ) {

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

		// Define params to get queried Event.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'value' => $event_type_value,
			'option_group_id' => $this->option_group_id_get(),
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
	 * Get the number of Events for a given CiviCRM Event Type.
	 *
	 * @since 0.5.4
	 *
	 * @param integer $event_type_id The ID of the CiviCRM Event Type.
	 * @return integer $count The number of Events of that Type.
	 */
	public function event_count( $event_type_id ) {

		// Sanity check.
		if ( empty( $event_type_id ) ) {
			return 0;
		}

		// Params to query Events.
		$params = [
			'version' => 3,
			'event_type_id' => $event_type_id,
			'return' => [
				'id',
			],
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Event', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'event_type_id' => $event_type_id,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return empty( $result['count'] ) ? 0 : (int) $result['count'];

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Event Type that is mapped to a Post Type.
	 *
	 * @since 0.5.4
	 *
	 * @param string $post_type_name The name of Post Type.
	 * @return integer|bool $event_type_id The numeric ID of the Event Type, or false if not mapped.
	 */
	public function id_get_for_post_type( $post_type_name ) {

		// Init return.
		$event_type_id = false;

		// Get mappings and flip.
		$mappings = $this->acf_loader->mapping->mappings_for_event_types_get();
		$mappings = array_flip( $mappings );

		// Overwrite the Event Type ID if there is a value.
		if ( isset( $mappings[ $post_type_name ] ) ) {
			$event_type_id = $mappings[ $post_type_name ];
		}

		// --<
		return $event_type_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Event Types as choices for an ACF "Select" Field.
	 *
	 * @since 0.5.4
	 *
	 * @return array $choices The choices array.
	 */
	public function choices_get() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$choices = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $choices;
		}

		// Define params to get queried Event Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'field' => 'event_type_id',
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Event', 'getoptions', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $choices;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $choices;
		}

		// The formatted result set is what we're after.
		foreach ( $result['values'] as $choice ) {
			$choices[ (int) $choice['key'] ] = $choice['value'];
		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $choices;
		}

		// --<
		return $choices;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all Event Types.
	 *
	 * @since 0.5.4
	 *
	 * @return array $event_types The array of Event Types.
	 */
	public function get_all() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$event_types = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $event_types;
		}

		// Define params to get queried Event Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'option_group_id' => $this->option_group_id_get(),
			'options' => [
				'sort' => 'weight',
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $event_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $event_types;
		}

		// The result set is what we're after.
		$event_types = $result['values'];

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $event_types;
		}

		// --<
		return $event_types;

	}



	/**
	 * Get all Event Types that are mapped to Post Types.
	 *
	 * @since 0.5.4
	 *
	 * @return array $event_types The array of mapped Event Types.
	 */
	public function get_mapped() {

		/*
		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}
		*/

		// Init return.
		$event_types = [];

		// Get mapping array.
		$mappings = $this->acf_loader->mapping->mappings_for_event_types_get();

		// Bail on empty.
		if ( empty( $mappings ) ) {
			return $event_types;
		}

		// Get all Event Type IDs.
		$event_type_ids = array_keys( $mappings );

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $event_types;
		}

		// Define params to get queried Event Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'value' => [ 'IN' => $event_type_ids ],
			'option_group_id' => $this->option_group_id_get(),
			'options' => [
				'sort' => 'label',
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $event_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $event_types;
		}

		// The result set is what we're after.
		$event_types = $result['values'];

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $event_types;
		}
		*/

		// --<
		return $event_types;

	}



	/**
	 * Check if an Event Type is mapped to a Post Type.
	 *
	 * @since 0.5.4
	 *
	 * @param integer $event_type_id The numeric ID of the Event Type.
	 * @return string|bool $is_linked The name of the Post Type, or false otherwise.
	 */
	public function is_mapped_to_post_type( $event_type_id ) {

		// Assume not.
		$is_mapped = false;

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_event_types_get();

		// Check presence in mappings.
		if ( isset( $mapped_post_types[ $event_type_id ] ) ) {
			$is_mapped = $mapped_post_types[ $event_type_id ];
		}

		// --<
		return $is_mapped;

	}



} // Class ends.



