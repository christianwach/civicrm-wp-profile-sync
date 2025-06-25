<?php
/**
 * CiviCRM Contact Type compatibility Class.
 *
 * Handles CiviCRM Contact Type integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Contact Type compatibility Class.
 *
 * This class provides CiviCRM Contact Type integration.
 *
 * @since 0.5
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Contact_Type {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_CiviCRM
	 */
	public $civicrm;

	/**
	 * Top-level Contact Types which can be mapped.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $top_level_types = [
		'Individual',
		'Household',
		'Organization',
	];

	/**
	 * Initialises this object.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin  = $parent->plugin;
		$this->civicrm = $parent;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.5
	 */
	public function initialise() {

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

	// -----------------------------------------------------------------------------------

	/**
	 * Get all top-level CiviCRM Contact Types.
	 *
	 * @since 0.5
	 *
	 * @return array $top_level_types The top level CiviCRM Contact Types.
	 */
	public function types_get_top_level() {

		// --<
		return $this->top_level_types;

	}

	/**
	 * Get all CiviCRM Contact Types.
	 *
	 * @since 0.5
	 *
	 * @return array $all The flat array CiviCRM Contact Types.
	 */
	public function types_get_all() {

		// Only do this once.
		static $all;
		if ( ! empty( $all ) ) {
			return $all;
		}

		// Init return.
		$all = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $all;
		}

		// Define params to get all Contact Types.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'is_active'  => 1,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $all;
		}

		// Populate return array.
		if ( isset( $result['values'] ) && count( $result['values'] ) > 0 ) {
			$all = $result['values'];
		}

		// --<
		return $all;

	}

	/**
	 * Get all CiviCRM Contact Types, nested by parent.
	 *
	 * CiviCRM only allows one level of nesting, so we can parse the results
	 * into a nested array to return.
	 *
	 * @since 0.4
	 *
	 * @return array $nested The nested CiviCRM Contact Types.
	 */
	public function types_get_nested() {

		// Only do this once.
		static $nested;
		if ( ! empty( $nested ) ) {
			return $nested;
		}

		// Init return.
		$nested = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $nested;
		}

		// Define params to get all Contact Types.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'is_active'  => 1,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $nested;
		}

		// Populate Contact Types array.
		$contact_types = [];
		if ( isset( $result['values'] ) && count( $result['values'] ) > 0 ) {
			$contact_types = $result['values'];
		}

		// Let's get the top level types.
		$top_level = [];
		foreach ( $contact_types as $contact_type ) {
			if ( empty( $contact_type['parent_id'] ) ) {
				$top_level[] = $contact_type;
			}
		}

		// Build a nested array.
		foreach ( $top_level as $item ) {
			$item['children'] = [];
			foreach ( $contact_types as $contact_type ) {
				if ( isset( $contact_type['parent_id'] ) && $contact_type['parent_id'] == $item['id'] ) {
					$item['children'][] = $contact_type;
				}
			}
			$nested[] = $item;
		}

		// --<
		return $nested;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Contact Type and Sub-type for a given Contact Type.
	 *
	 * CiviCRM only allows one level of nesting, so we don't need to recurse -
	 * we can simply re-query if there is a 'parent_id'.
	 *
	 * @since 0.4
	 *
	 * @param string $contact_type The name of the CiviCRM Contact Type to query.
	 * @param string $mode The param to query by: 'name' or 'id'.
	 * @return array|bool $types An array of type and sub-type, or false on failure.
	 */
	public function hierarchy_get( $contact_type, $mode = 'name' ) {

		// Only do this once per Contact Type and mode.
		static $pseudocache;
		if ( isset( $pseudocache[ $mode ][ $contact_type ] ) ) {
			return $pseudocache[ $mode ][ $contact_type ];
		}

		// Init return.
		$types = false;

		// Get data for the queried Contact Type.
		$contact_type_data = $this->get_data( $contact_type, $mode );

		// Bail if we didn't get any.
		if ( false === $contact_type_data ) {
			return $types;
		}

		// Overwrite with name when passing in an ID.
		if ( 'id' === $mode ) {
			$contact_type_name = $contact_type_data['name'];
		} else {
			$contact_type_name = $contact_type;
		}

		// Assume it's the top level type.
		$top_level_type = $contact_type_data['name'];

		// If there's a parent ID, re-query.
		if ( ! empty( $contact_type_data['parent_id'] ) ) {

			// Define params to get top-level Contact Type.
			$params = [
				'version'    => 3,
				'sequential' => 1,
				'id'         => $contact_type_data['parent_id'],
			];

			// Call the API.
			// TODO: Perhaps use 'get' instead.
			$result = civicrm_api( 'ContactType', 'getsingle', $params );

			// Bail if there's an error.
			if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
				return $types;
			}

			// Assign top level type.
			$top_level_type = $result['name'];

		}

		// Clear subtype if identical to type.
		if ( $contact_type_name == $top_level_type ) {
			$contact_subtype   = '';
			$contact_type_name = $top_level_type;
		} else {
			$contact_subtype   = $contact_type_data['name'];
			$contact_type_name = $top_level_type;
		}

		// Build types.
		$types = [
			'type'    => $contact_type_name,
			'subtype' => [ $contact_subtype ],
		];

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $mode ][ $contact_type ] ) ) {
			$pseudocache[ $mode ][ $contact_type ] = $types;
		}

		// --<
		return $types;

	}

	/**
	 * Get the CiviCRM Contact Type and Sub-type for a given Contact Type ID.
	 *
	 * @since 0.4
	 *
	 * @param string $contact_type_id The numeric ID of the CiviCRM Contact Type.
	 * @return array $types An associative array populated with parent type and sub-types.
	 */
	public function hierarchy_get_by_id( $contact_type_id ) {

		// Pass through.
		$types = $this->hierarchy_get( $contact_type_id, 'id' );

		// --<
		return $types;

	}

	/**
	 * Get the Contact Type hierarchy for a given a Contact.
	 *
	 * This method assumes that a Contact is of a single sub-type. This may not
	 * be the case.
	 *
	 * @since 0.4
	 *
	 * @param array|obj $contact The Contact data.
	 * @return array $types The array of Contact Type data for the Contact.
	 */
	public function hierarchy_get_for_contact( $contact ) {

		// Maybe cast Contact data as array.
		if ( is_object( $contact ) ) {
			$contact = (array) $contact;
		}

		// Grab the top level Contact Type for this Contact.
		$contact_type = $contact['contact_type'];

		// Find the lowest level Contact Type for this Contact.
		$contact_sub_type = [];
		if ( ! empty( $contact['contact_sub_type'] ) ) {
			if ( is_array( $contact['contact_sub_type'] ) ) {
				$contact_sub_type = $contact['contact_sub_type'];
			} else {
				if ( false !== strpos( $contact['contact_sub_type'], CRM_Core_DAO::VALUE_SEPARATOR ) ) {
					$types            = CRM_Utils_Array::explodePadded( $contact['contact_sub_type'] );
					$contact_sub_type = $types;
				} else {
					$contact_sub_type = [ $contact['contact_sub_type'] ];
				}
			}
		}

		// Build types.
		$types = [
			'type'    => $contact_type,
			'subtype' => array_unique( $contact_sub_type ),
		];

		// --<
		return $types;

	}

	/**
	 * Convert a Contact Type hierarchy into an array separated items.
	 *
	 * A CiviCRM Contact can only have one top-level type, but many possible
	 * sub-types. The existing methods return an array of the form:
	 *
	 * [
	 *   'type' => 'TopLevelName',
	 *   'subtype' => [ 'SubTypeName1', 'SubTypeName2', 'SubTypeName3' ]
	 * ]
	 *
	 * This method rebuilds the array to return an array which looks like:
	 *
	 * [
	 *   [ 'type' => 'TopLevelName', 'subtype' => 'SubTypeName1' ],
	 *   [ 'type' => 'TopLevelName', 'subtype' => 'SubTypeName2' ],
	 *   [ 'type' => 'TopLevelName', 'subtype' => 'SubTypeName3' ],
	 * ]
	 *
	 * This is useful when iterating over Contact Types.
	 *
	 * @see self::hierarchy_get_by_id()
	 * @see self::hierarchy_get_for_contact()
	 *
	 * @since 0.4
	 *
	 * @param array $hierarchy The array of Contact Type data for a Contact.
	 * @return array $contact_types The array of separated Contact Type data.
	 */
	public function hierarchy_separate( $hierarchy ) {

		// Init return.
		$contact_types = [];

		// Build array of Contact Type arrays.
		if ( empty( $hierarchy['subtype'] ) ) {
			$contact_types = [ $hierarchy ];
		} else {
			foreach ( $hierarchy['subtype'] as $subtype ) {
				$contact_types[] = [
					'type'    => $hierarchy['type'],
					'subtype' => $subtype,
				];
			}
		}

		// --<
		return $contact_types;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Contact Type data for a given ID or name.
	 *
	 * @since 0.4
	 *
	 * @param string|integer $contact_type The name or ID of the CiviCRM Contact Type to query.
	 * @param string         $mode The param to query by: 'name' or 'id'.
	 * @return array|bool $contact_type_data An array of Contact Type data, or false on failure.
	 */
	public function get_data( $contact_type, $mode = 'name' ) {

		// Only do this once per Contact Type and mode.
		static $pseudocache;
		if ( isset( $pseudocache[ $mode ][ $contact_type ] ) ) {
			return $pseudocache[ $mode ][ $contact_type ];
		}

		// Init return.
		$contact_type_data = false;

		// Bail if we have no Contact Type.
		if ( empty( $contact_type ) ) {
			return $contact_type_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_type_data;
		}

		// Define params to get queried Contact Type.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Add param to query by.
		if ( 'name' === $mode ) {
			$params['name'] = $contact_type;
		} elseif ( 'id' === $mode ) {
			$params['id'] = $contact_type;
		}

		// Call the API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $contact_type_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_type_data;
		}

		// The result set should contain only one item.
		$contact_type_data = array_pop( $result['values'] );

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $mode ][ $contact_type ] ) ) {
			$pseudocache[ $mode ][ $contact_type ] = $contact_type_data;
		}

		// --<
		return $contact_type_data;

	}

}
