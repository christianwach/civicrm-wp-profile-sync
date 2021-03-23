<?php
/**
 * CiviCRM Contact Type Class.
 *
 * Handles CiviCRM Contact Type functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Contact Type Class.
 *
 * A class that encapsulates CiviCRM Contact Type functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Type {

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
	 * Top-level Contact Types which can be mapped.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $top_level_types The top level CiviCRM Contact Types.
	 */
	public $top_level_types = [
		'Individual',
		'Household',
		'Organization',
	];

	/**
	 * Contact data bridging array.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $bridging_array The Contact data bridging array.
	 */
	public $bridging_array = [];



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
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'register_hooks' ] );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Listen for events from our Mapper that may signal a change of Contact Type.
		add_action( 'cwps/acf/mapper/contact/edit/pre', [ $this, 'contact_edit_pre' ], 10 );
		add_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 9 );

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/contact/edit/pre', [ $this, 'contact_edit_pre' ], 10 );
		remove_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 9 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all top-level CiviCRM contact types.
	 *
	 * @since 0.4
	 *
	 * @return array $top_level_types The top level CiviCRM Contact Types.
	 */
	public function types_get_top_level() {

		// --<
		return $this->top_level_types;

	}



	/**
	 * Get all CiviCRM contact types, nested by parent.
	 *
	 * CiviCRM only allows one level of nesting, so we can parse the results
	 * into a nested array to return.
	 *
	 * @since 0.4
	 *
	 * @return array $nested The nested CiviCRM contact types.
	 */
	public function types_get_nested() {

		// Only do this once.
		static $nested;
		if ( isset( $nested ) ) {
			return $nested;
		}

		// Init return.
		$nested = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $nested;
		}

		// Define params to get all contact types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'options' => [
				'limit' => '0', // No limit.
			],
		];

		// Call API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $nested;
		}

		// Populate contact types array.
		$contact_types = [];
		if ( isset( $result['values'] ) AND count( $result['values'] ) > 0 ) {
			$contact_types = $result['values'];
		}

		// let's get the top level types
		$top_level = [];
		foreach( $contact_types AS $contact_type ) {
			if ( ! isset( $contact_type['parent_id'] ) ) {
				$top_level[] = $contact_type;
			}
		}

		// Build a nested array
		foreach( $top_level AS $item ) {
			$item['children'] = [];
			foreach( $contact_types AS $contact_type ) {
				if ( isset( $contact_type['parent_id'] ) AND $contact_type['parent_id'] == $item['id'] ) {
					$item['children'][] = $contact_type;
				}
			}
			$nested[] = $item;
		}

		// --<
		return $nested;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Contact Type data for a given ID or name.
	 *
	 * @since 0.4
	 *
	 * @param string|integer $contact_type The name of the CiviCRM Contact Type to query.
	 * @param string $mode The param to query by: 'name' or 'id'.
	 * @return array|boolean $contact_type_data An array of Contact Type data, or false on failure.
	 */
	public function get_data( $contact_type, $mode = 'name' ) {

		// Only do this once per Contact Type and mode.
		static $pseudocache;
		if ( isset( $pseudocache[$mode][$contact_type] ) ) {
			return $pseudocache[$mode][$contact_type];
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
			'version' => 3,
			'sequential' => 1,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Add param to query by.
		if ( $mode == 'name' ) {
			$params['name'] = $contact_type;
		} elseif ( $mode == 'id' ) {
			$params['id'] = $contact_type;
		}

		// Call the API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $contact_type_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_type_data;
		}

		// The result set should contain only one item.
		$contact_type_data = array_pop( $result['values'] );

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$mode][$contact_type] ) ) {
			$pseudocache[$mode][$contact_type] = $contact_type_data;
		}

		// --<
		return $contact_type_data;

	}



	// -------------------------------------------------------------------------



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
	 * @return array|boolean $types An array of type and sub-type, or false on failure.
	 */
	public function hierarchy_get( $contact_type, $mode = 'name' ) {

		// Only do this once per Contact Type and mode.
		static $pseudocache;
		if ( isset( $pseudocache[$mode][$contact_type] ) ) {
			return $pseudocache[$mode][$contact_type];
		}

		// Init return.
		$types = false;

		// Get data for the queried Contact Type.
		$contact_type_data = $this->get_data( $contact_type, $mode );

		// Bail if we didn't get any.
		if ( $contact_type_data === false ) {
			return $types;
		}

		// Overwrite with name when passing in an ID.
		if ( $mode == 'id' ) {
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
				'version' => 3,
				'sequential' => 1,
				'id' => $contact_type_data['parent_id'],
			];

			// Call the API.
			$result = civicrm_api( 'ContactType', 'getsingle', $params );

			// Bail if there's an error.
			if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
				return $types;
			}

			// Assign top level type.
			$top_level_type = $result['name'];

		}

		// Clear subtype if identical to type.
		if ( $contact_type_name == $top_level_type ) {
			$contact_subtype = '';
			$contact_type_name = $top_level_type;
		} else {
			$contact_subtype = $contact_type_data['name'];
			$contact_type_name = $top_level_type;
		}

		// Build types.
		$types = [ 'type' => $contact_type_name, 'subtype' => [ $contact_subtype ] ];

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$mode][$contact_type] ) ) {
			$pseudocache[$mode][$contact_type] = $types;
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
					$types = CRM_Utils_Array::explodePadded( $contact['contact_sub_type'] );
					$contact_sub_type = $types;
				} else {
					$contact_sub_type = [ $contact['contact_sub_type'] ];
				}
			}
		}

		// Build types.
		$types = [ 'type' => $contact_type, 'subtype' => $contact_sub_type ];

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
	 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Type::hierarchy_get_by_id()
	 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Type::hierarchy_get_for_contact()
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
			foreach( $hierarchy['subtype'] AS $subtype ) {
				$contact_types[] = [
					'type' => $hierarchy['type'],
					'subtype' => $subtype,
				];
			}
		}

		// --<
		return $contact_types;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the number of Contacts who are of a CiviCRM Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type_id The ID of the CiviCRM Contact Type.
	 * @return integer $count The number of Contacts of that Type.
	 */
	public function contact_count( $contact_type_id ) {

		// Get the hierarchy for the Contact Type ID.
		$hierarchy = $this->hierarchy_get_by_id( $contact_type_id, 'id' );

		// Bail if we didn't get any.
		if ( $hierarchy === false ) {
			return 0;
		}

		// Params to query Contacts.
		$params = [
			'version' => 3,
			'contact_type' => $hierarchy['type'],
			'contact_sub_type' => $hierarchy['subtype'],
			'return' => [
				'id',
			],
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_type_id' => $contact_type_id,
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
	 * Get the Contact Type hierarchy that is mapped to a Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string|boolean $post_type_name The name of Post Type.
	 * @return array $types An associative array populated with parent type and sub-type.
	 */
	public function hierarchy_get_for_post_type( $post_type_name ) {

		// Init return.
		$types = false;

		// Get the mapped Contact Type ID.
		$contact_type_id = $this->id_get_for_post_type( $post_type_name );

		// Bail on failure.
		if ( $contact_type_id === false ) {
			return $types;
		}

		// Get the array of types.
		$types = $this->hierarchy_get_by_id( $contact_type_id );

		// --<
		return $types;

	}



	/**
	 * Get the Contact Type that is mapped to a Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type_name The name of Post Type.
	 * @return integer|boolean $contact_type_id The numeric ID of the Contact Type, or false if not mapped.
	 */
	public function id_get_for_post_type( $post_type_name ) {

		// Init return.
		$contact_type_id = false;

		// Get mappings and flip.
		$mappings = $this->acf_loader->mapping->mappings_for_contact_types_get();
		$mappings = array_flip( $mappings );

		// Overwrite the Contact Type ID if there is a value.
		if ( isset( $mappings[$post_type_name] ) ) {
			$contact_type_id = $mappings[$post_type_name];
		}

		// --<
		return $contact_type_id;

	}



	/**
	 * Get all Contact Types that are mapped to Post Types.
	 *
	 * @since 0.4
	 *
	 * @return array $contact_types The array of mapped Contact Types.
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
		$contact_types = [];

		// Get mapping array.
		$mappings = $this->acf_loader->mapping->mappings_for_contact_types_get();

		// Bail on empty.
		if ( empty( $mappings ) ) {
			return $contact_types;
		}

		// Get all Contact Type IDs.
		$contact_type_ids = array_keys( $mappings );

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_types;
		}

		// Define params to get queried Contact Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => [ 'IN' => $contact_type_ids ],
			'options' => [
				'sort' => 'label',
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $contact_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_types;
		}

		// The result set is what we're after.
		$contact_types = $result['values'];

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $contact_types;
		}
		*/

		// --<
		return $contact_types;

	}



	/**
	 * Check if a Contact Type is mapped to a Post Type.
	 *
	 * @since 0.4
	 *
	 * @param integer|string |array $contact_type The "ID", "name" or "hierarchy" of the Contact Type.
	 * @return string|boolean $is_linked The name of the Post Type, or false otherwise.
	 */
	public function is_mapped_to_post_type( $contact_type ) {

		// Assume not.
		$is_mapped = false;

		// Parse the input when it's an array.
		if ( is_array( $contact_type ) ) {

			// Check if it's a top level Contact Type.
			if ( empty( $contact_type['subtype'] ) ) {
				$contact_type = $contact_type['type'];
			} else {
				$contact_type = $contact_type['subtype'];
			}

		}

		// Parse the input when it's an integer.
		if ( is_numeric( $contact_type ) ) {

			// Assign the numeric ID.
			$contact_type_id = $contact_type = (int) $contact_type;

		}

		// Parse the input when it's a string.
		if ( is_string( $contact_type ) ) {

			// Get data for the queried Contact Type.
			$contact_type_data = $this->get_data( $contact_type, 'name' );

			// Bail if we didn't get any.
			if ( $contact_type_data === false ) {
				return $is_mapped;
			}

			// Assign the numeric ID.
			$contact_type_id = $contact_type_data['id'];

		}

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_contact_types_get();

		// Check presence in mappings.
		if ( isset( $mapped_post_types[$contact_type_id] ) ) {
			$is_mapped = $mapped_post_types[$contact_type_id];
		}

		// --<
		return $is_mapped;

	}



	// -------------------------------------------------------------------------



	/**
	 * When a CiviCRM Contact is about to be updated, get existing data.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_edit_pre( $args ) {

		// Get the full existing Contact data.
		$contact = $this->acf_loader->civicrm->contact->get_by_id( $args['objectId'] );

		// Add to bridge.
		$this->bridging_array[$args['objectId']] = $contact;

	}



	/**
	 * When a CiviCRM Contact has been updated, compare with existing data.
	 *
	 * This method is hooked in before any other Mapper listener so that it can
	 * be queried as to whether a Contact's Contact Types have changed.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_edited( $args ) {

		// Get the previous Contact data.
		$contact_pre = [];
		if ( ! empty( $this->bridging_array[$args['objectId']] ) ) {
			$contact_pre = $this->bridging_array[$args['objectId']];
			unset( $this->bridging_array[$args['objectId']] );
		}

		// Make sure we have arrays.
		if ( empty( $contact_pre['contact_sub_type'] ) ) {
			$contact_pre['contact_sub_type'] = [];
		}
		if ( empty( $args['objectRef']->contact_sub_type ) ) {
			$args['objectRef']->contact_sub_type = [];
		}

		// Find the Contact Types that are missing.
		$types_removed = array_diff( $contact_pre['contact_sub_type'], $args['objectRef']->contact_sub_type );

		// Find the Contact Types that have been added.
		$types_added = array_diff( $args['objectRef']->contact_sub_type, $contact_pre['contact_sub_type'] );

		// Save the diffs in the Contact data.
		$args['objectRef']->subtype_diffs = [
			'removed' => $types_removed,
			'added' => $types_added,
		];

	}



} // Class ends.



