<?php
/**
 * CiviCRM Case Type Class.
 *
 * Handles CiviCRM Case Type functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Case Type Class.
 *
 * A class that encapsulates CiviCRM Case Type functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Case_Type {

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

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		//$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for a "Case Type" by ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $case_type_id The numeric ID of the Case Type.
	 * @return array|bool $case_type An array of Case Type data, or false on failure.
	 */
	public function get_by_id( $case_type_id ) {

		// Init return.
		$case_type = false;

		// Bail if we have no Case Type ID.
		if ( empty( $case_type_id ) ) {
			return $case_type;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $case_type;
		}

		// Define params to get queried Case.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $case_type_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CaseType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $case_type;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $case_type;
		}

		// The result set should contain only one item.
		$case_type = array_pop( $result['values'] );

		// --<
		return $case_type;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the number of Cases for a given CiviCRM Case Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $case_type_id The ID of the CiviCRM Case Type.
	 * @return integer $count The number of Cases of that Type.
	 */
	public function case_count( $case_type_id ) {

		// Sanity check.
		if ( empty( $case_type_id ) ) {
			return 0;
		}

		// Params to query Cases.
		$params = [
			'version' => 3,
			'case_type_id' => $case_type_id,
			'return' => [
				'id',
			],
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Case', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'case_type_id' => $case_type_id,
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
	 * Get the Case Type that is mapped to a Post Type.
	 *
	 * @since 0.5
	 *
	 * @param string $post_type_name The name of Post Type.
	 * @return integer|bool $case_type_id The numeric ID of the Case Type, or false if not mapped.
	 */
	public function id_get_for_post_type( $post_type_name ) {

		// Init return.
		$case_type_id = false;

		// Get mappings and flip.
		$mappings = $this->acf_loader->mapping->mappings_for_case_types_get();
		$mappings = array_flip( $mappings );

		// Overwrite the Case Type ID if there is a value.
		if ( isset( $mappings[ $post_type_name ] ) ) {
			$case_type_id = $mappings[ $post_type_name ];
		}

		// --<
		return $case_type_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Case Types as choices for an ACF "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param string $field The Field Type to get the Options for.
	 * @return array $choices The choices array.
	 */
	public function choices_get( $field = 'case_type_id' ) {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache[ $field ] ) ) {
			return $pseudocache[ $field ];
		}

		// Init return.
		$choices = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $choices;
		}

		// Define params to get queried Case Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Add the Field.
		$params['field'] = $field;

		// Call the API.
		$result = civicrm_api( 'Case', 'getoptions', $params );

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
		if ( ! isset( $pseudocache[ $field ] ) ) {
			$pseudocache[ $field ] = $choices;
		}

		// --<
		return $choices;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all Case Types.
	 *
	 * @since 0.5
	 *
	 * @return array $case_types The array of Case Types.
	 */
	public function get_all() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$case_types = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $case_types;
		}

		// Define params to get queried Case Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CaseType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $case_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $case_types;
		}

		// The formatted result set is what we're after.
		foreach ( $result['values'] as $choice ) {
			$case_types[ $choice['id'] ] = $choice['title'];
		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $case_types;
		}

		// --<
		return $case_types;

	}



	/**
	 * Get all Case Types that are mapped to Post Types.
	 *
	 * @since 0.5
	 *
	 * @return array $case_types The array of mapped Case Types.
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
		$case_types = [];

		// Get mapping array.
		$mappings = $this->acf_loader->mapping->mappings_for_case_types_get();

		// Bail on empty.
		if ( empty( $mappings ) ) {
			return $case_types;
		}

		// Get all Case Type IDs.
		$case_type_ids = array_keys( $mappings );

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $case_types;
		}

		// Define params to get queried Case Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => [ 'IN' => $case_type_ids ],
			'options' => [
				'sort' => 'title',
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CaseType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $case_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $case_types;
		}

		// The result set is what we're after.
		$case_types = $result['values'];

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $case_types;
		}
		*/

		// --<
		return $case_types;

	}



	/**
	 * Check if a Case Type is mapped to a Post Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $case_type_id The numeric ID of the Case Type.
	 * @return string|bool $is_linked The name of the Post Type, or false otherwise.
	 */
	public function is_mapped_to_post_type( $case_type_id ) {

		// Assume not.
		$is_mapped = false;

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_case_types_get();

		// Check presence in mappings.
		if ( isset( $mapped_post_types[ $case_type_id ] ) ) {
			$is_mapped = $mapped_post_types[ $case_type_id ];
		}

		// --<
		return $is_mapped;

	}



} // Class ends.



