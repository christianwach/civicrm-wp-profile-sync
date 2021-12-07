<?php
/**
 * CiviCRM Activity Type Class.
 *
 * Handles CiviCRM Activity Type functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Activity Type Class.
 *
 * A class that encapsulates CiviCRM Activity Type functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Activity_Type {

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

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
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
	 * Get the numeric ID of the "Activity Types" Option Group.
	 *
	 * @since 0.4
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

		// Define params to get Activity Types Option Group.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'name' => 'activity_type',
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
	 * Get the data for an "Activity Type" by ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_id The numeric ID of the Activity Type.
	 * @return array|bool $activity_type An array of Activity Type data, or false on failure.
	 */
	public function get_by_id( $activity_type_id ) {

		// Init return.
		$activity_type = false;

		// Bail if we have no Activity Type ID.
		if ( empty( $activity_type_id ) ) {
			return $activity_type;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $activity_type;
		}

		// Define params to get queried Activity.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $activity_type_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $activity_type;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $activity_type;
		}

		// The result set should contain only one item.
		$activity_type = array_pop( $result['values'] );

		// --<
		return $activity_type;

	}



	/**
	 * Get the data for an "Activity Type" by value.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_value The numeric value of the Activity Type.
	 * @return array|bool $activity_type An array of Activity Type data, or false on failure.
	 */
	public function get_by_value( $activity_type_value ) {

		// Init return.
		$activity_type = false;

		// Bail if we have no Activity Type value.
		if ( empty( $activity_type_value ) ) {
			return $activity_type;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $activity_type;
		}

		// Define params to get queried Activity.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'value' => $activity_type_value,
			'option_group_id' => $this->option_group_id_get(),
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $activity_type;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $activity_type;
		}

		// The result set should contain only one item.
		$activity_type = array_pop( $result['values'] );

		// --<
		return $activity_type;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the number of Activities for a given CiviCRM Activity Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_id The ID of the CiviCRM Activity Type.
	 * @return integer $count The number of Activities of that Type.
	 */
	public function activity_count( $activity_type_id ) {

		// Sanity check.
		if ( empty( $activity_type_id ) ) {
			return 0;
		}

		// Params to query Activities.
		$params = [
			'version' => 3,
			'activity_type_id' => $activity_type_id,
			'return' => [
				'id',
			],
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Activity', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'activity_type_id' => $activity_type_id,
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
	 * Get the Activity Type that is mapped to a Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type_name The name of Post Type.
	 * @return integer|bool $activity_type_id The numeric ID of the Activity Type, or false if not mapped.
	 */
	public function id_get_for_post_type( $post_type_name ) {

		// Init return.
		$activity_type_id = false;

		// Get mappings and flip.
		$mappings = $this->acf_loader->mapping->mappings_for_activity_types_get();
		$mappings = array_flip( $mappings );

		// Overwrite the Activity Type ID if there is a value.
		if ( isset( $mappings[ $post_type_name ] ) ) {
			$activity_type_id = $mappings[ $post_type_name ];
		}

		// --<
		return $activity_type_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Activity Types as choices for an ACF "Select" Field.
	 *
	 * @since 0.5
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

		// Define params to get queried Activity Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'field' => 'activity_type_id',
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Activity', 'getoptions', $params );

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
			$choices[ $choice['key'] ] = $choice['value'];
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
	 * Get all Activity Types.
	 *
	 * @since 0.5
	 *
	 * @return array $activity_types The array of Activity Types.
	 */
	public function get_all() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$activity_types = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $activity_types;
		}

		// Define params to get queried Activity Types.
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
			return $activity_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $activity_types;
		}

		// The result set is what we're after.
		$activity_types = $result['values'];

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $activity_types;
		}

		// --<
		return $activity_types;

	}



	/**
	 * Get all Activity Types that are mapped to Post Types.
	 *
	 * @since 0.4
	 *
	 * @return array $activity_types The array of mapped Activity Types.
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
		$activity_types = [];

		// Get mapping array.
		$mappings = $this->acf_loader->mapping->mappings_for_activity_types_get();

		// Bail on empty.
		if ( empty( $mappings ) ) {
			return $activity_types;
		}

		// Get all Activity Type IDs.
		$activity_type_ids = array_keys( $mappings );

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $activity_types;
		}

		// Define params to get queried Activity Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'value' => [ 'IN' => $activity_type_ids ],
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
			return $activity_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $activity_types;
		}

		// The result set is what we're after.
		$activity_types = $result['values'];

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $activity_types;
		}
		*/

		// --<
		return $activity_types;

	}



	/**
	 * Check if an Activity Type is mapped to a Post Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_id The numeric ID of the Activity Type.
	 * @return string|bool $is_linked The name of the Post Type, or false otherwise.
	 */
	public function is_mapped_to_post_type( $activity_type_id ) {

		// Assume not.
		$is_mapped = false;

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_activity_types_get();

		// Check presence in mappings.
		if ( isset( $mapped_post_types[ $activity_type_id ] ) ) {
			$is_mapped = $mapped_post_types[ $activity_type_id ];
		}

		// --<
		return $is_mapped;

	}



} // Class ends.



