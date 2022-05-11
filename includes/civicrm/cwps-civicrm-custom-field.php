<?php
/**
 * CiviCRM Custom Field compatibility Class.
 *
 * Handles CiviCRM Custom Field integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Custom Field compatibility Class.
 *
 * This class provides CiviCRM Custom Field integration.
 *
 * @since 0.5
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Custom_Field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;



	/**
	 * Initialises this object.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->plugin = $parent->plugin;
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



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Custom Field data for a given ID.
	 *
	 * This is called on a per-Field basis. If it ends up slowing things down
	 * too much, an alternative would be to query *all* Custom Fields, stash
	 * that data set, then query it locally for each subsequent request.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param string|integer $field_id The numeric ID of the Custom Field.
	 * @return array|bool $field An array of Custom Field data, or false on failure.
	 */
	public function get_by_id( $field_id ) {

		// Init return.
		$field = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Build params to get Custom Group data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $field_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'CustomField', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $field;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $field;
		}

		// The result set should contain only one item.
		$field = array_pop( $result['values'] );

		// --<
		return $field;

	}



	/**
	 * Get the CiviCRM Custom Field data for a given Custom Group ID.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param integer $custom_group_id The numeric ID of the Custom Group.
	 * @return array $fields An array of Custom Field data.
	 */
	public function get_by_group_id( $custom_group_id ) {

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Build params to get Custom Group data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'custom_group_id' => $custom_group_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'CustomField', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $fields;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $fields;
		}

		// The result set is what we want.
		$fields = $result['values'];

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the values for a given CiviCRM Contact ID and set of Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact to query.
	 * @param array $custom_field_ids The Custom Field IDs to query.
	 * @return array $contact_data An array of Contact data.
	 */
	public function values_get_by_contact_id( $contact_id, $custom_field_ids = [] ) {

		// Init return.
		$contact_data = [];

		// Bail if we have no Custom Field IDs.
		if ( empty( $custom_field_ids ) ) {
			return $contact_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_data;
		}

		// Format codes.
		$codes = [];
		foreach ( $custom_field_ids as $custom_field_id ) {
			$codes[] = 'custom_' . $custom_field_id;
		}

		// Define params to get queried Contact.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $contact_id,
			'return' => $codes,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_data;
		}

		// Overwrite return.
		foreach ( $result['values'] as $item ) {
			foreach ( $item as $key => $value ) {
				if ( substr( $key, 0, 7 ) == 'custom_' ) {
					$index = (int) str_replace( 'custom_', '', $key );
					$contact_data[ $index ] = $value;
				}
			}
		}

		// Maybe filter here?

		// --<
		return $contact_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Custom Fields for a given CiviCRM Contact.
	 *
	 * @since 0.4
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_contact( $contact ) {

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Get Contact Type hierarchy.
		$hierarchy = $this->civicrm->contact_type->hierarchy_get_for_contact( $contact );

		// Get separated array of Contact Types.
		$contact_types = $this->civicrm->contact_type->hierarchy_separate( $hierarchy );

		// Check each Contact Type in turn.
		foreach ( $contact_types as $contact_type ) {

			// Call the method for the Contact Type.
			$fields_for_contact_type = $this->get_for_contact_type( $contact_type['type'], $contact_type['subtype'] );

			// Add to return array.
			$custom_fields = $custom_fields + $fields_for_contact_type;

		}

		// --<
		return $custom_fields;

	}



	/**
	 * Gets the Custom Fields for all CiviCRM Contacts.
	 *
	 * The returned array is keyed by the "name" of the Custom Group. To fetch
	 * the Custom Group data as well, use the method with the same name located
	 * in the Custom Group class.
	 *
	 * CiviCRM has a special setting for "extends" called "Contact" that allows
	 * Custom Fields to be attached to any Contact Type.
	 *
	 * This should not be confused with "get_for_all_contact_types" which gets
	 * the Custom Fields for all top level CiviCRM Contact Types - and prepends
	 * the results of this query.
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

		// Construct params to get Fields for all Contacts.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'extends' => 'Contact',
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0, // No limit.
				],
			],
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Add the Custom Fields from the chained API data.
			foreach ( $result['values'] as $key => $value ) {
				foreach ( $value['api.CustomField.get']['values'] as $subkey => $item ) {
					$custom_fields[ $value['title'] ][] = $item;
				}
			}

		}

		// --<
		return $custom_fields;

	}



	/**
	 * Gets all the Custom Fields for all CiviCRM Contact Types/Subtypes.
	 *
	 * The returned array is keyed by the "name" of the Custom Group. To fetch
	 * the Custom Group data as well, use the method with the same name located
	 * in the Custom Group class.
	 *
	 * Prepends the Custom Fields for all CiviCRM Contacts.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_all_contact_types() {

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Start with the Custom Fields for all Contact Types.
		$custom_fields = $this->get_for_contacts();

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
				],
			],
			'extends' => [
				'IN' => $this->plugin->civicrm->contact_type->types_get_top_level(),
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Add the Custom Fields from the chained API data.
			foreach ( $result['values'] as $key => $value ) {
				foreach ( $value['api.CustomField.get']['values'] as $subkey => $item ) {
					$custom_fields[ $value['title'] ][] = $item;
				}
			}

		}

		// --<
		return $custom_fields;

	}



	/**
	 * Gets the Custom Fields for a CiviCRM Contact Type/Sub-type.
	 *
	 * The returned array is keyed by the "name" of the Custom Group. To fetch
	 * the Custom Group data as well, use the method with the same name located
	 * in the Custom Group class.
	 *
	 * Prepends the Custom Fields for all CiviCRM Contacts.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param string $type The Contact Type that the Option Group applies to.
	 * @param string $subtype The Contact Sub-type that the Option Group applies to.
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_contact_type( $type = '', $subtype = '' ) {

		// Maybe set a key for the Sub-type.
		$index = $subtype;
		if ( empty( $subtype ) ) {
			$index = 'none';
		}

		// Only do this once per Type.
		static $pseudocache;
		if ( isset( $pseudocache[ $type ][ $index ] ) ) {
			return $pseudocache[ $type ][ $index ];
		}

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Start with the Custom Fields for all Contact Types.
		$custom_fields = $this->get_for_contacts();

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'extends' => $type,
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0, // No limit.
				],
			],
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Append to return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// We only need the results from the chained API data.
			foreach ( $result['values'] as $key => $value ) {

				// Skip adding if it extends a sibling Sub-type.
				if ( ! empty( $subtype ) && ! empty( $value['extends_entity_column_value'] ) ) {
					if ( ! in_array( $subtype, $value['extends_entity_column_value'] ) ) {
						continue;
					}
				}

				// Add the Custom Fields.
				foreach ( $value['api.CustomField.get']['values'] as $subkey => $item ) {
					$custom_fields[ $value['title'] ][] = $item;
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $type ][ $index ] ) ) {
			$pseudocache[ $type ][ $index ] = $custom_fields;
		}

		// --<
		return $custom_fields;

	}



	/**
	 * Get the Custom Fields for a CiviCRM Entity Type and Subtype.
	 *
	 * There's a discussion to be had about whether or not to include Custom Groups
	 * for a Sub-type or not. The code in this method can return data specific to
	 * the Sub-type, but it's presumably desirable to include all Custom Groups
	 * that apply to an Entity Type.
	 *
	 * There's also a slight weakness in this code, in that the returned array is
	 * keyed by the "title" of the Custom Group. It is possible (though unlikely)
	 * that two Custom Groups may have the same "title", in which case the Custom
	 * Fields will be grouped together in the "CiviCRM Field" dropdown. The unique
	 * element is the Custom Group's "name" property, but then we would have to
	 * retrieve the "title" somewhere else - as it stands, the return array has
	 * all the data required to build the select, so I'm leaving it as is for now.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param string $type The Entity Type that the Option Group applies to.
	 * @param string $subtype The Entity Sub-type that the Option Group applies to.
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_entity_type( $type = '', $subtype = '' ) {

		// Maybe set a key for the subtype.
		$index = $subtype;
		if ( empty( $subtype ) ) {
			$index = 'none';
		}

		// Only do this once per Entity Type.
		static $pseudocache = [];
		if ( isset( $pseudocache[ $type ][ $index ] ) ) {
			return $pseudocache[ $type ][ $index ];
		}

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Start with the Custom Fields for all Contact Types.
		if ( in_array( $type, $this->plugin->civicrm->contact_type->types_get_top_level() ) ) {
			$custom_fields = $this->get_for_contacts();
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'extends' => $type,
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0, // No limit.
				],
			],
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Append to return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// We only need the results from the chained API data.
			foreach ( $result['values'] as $key => $value ) {

				// Skip adding if it extends a sibling Sub-type.
				if ( ! empty( $subtype ) && ! empty( $value['extends_entity_column_value'] ) ) {
					if ( ! in_array( $subtype, $value['extends_entity_column_value'] ) ) {
						continue;
					}
				}

				// Add the Custom Fields.
				foreach ( $value['api.CustomField.get']['values'] as $subkey => $item ) {
					$custom_fields[ $value['title'] ][] = $item;
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $type ][ $index ] ) ) {
			$pseudocache[ $type ][ $index ] = $custom_fields;
		}

		// --<
		return $custom_fields;

	}



} // Class ends.



