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
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_entity_type( $type = '', $subtype = '' ) {

		/*
		// Maybe set a key for the subtype.
		$key = $subtype;
		if ( empty( $subtype ) ) {
			$key = 'none';
		}

		// Only do this once per Entity Type.
		static $pseudocache;
		if ( isset( $pseudocache[$type][$key] ) ) {
			return $pseudocache[$type][$key];
		}
		*/

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

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$type][$key] ) ) {
			$pseudocache[$type][$key] = $custom_groups;
		}
		*/

		// --<
		return $custom_groups;

	}



} // Class ends.



