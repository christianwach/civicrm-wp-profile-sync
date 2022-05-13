<?php
/**
 * CiviCRM Custom Group compatibility Class.
 *
 * Handles CiviCRM Custom Group integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Custom Group compatibility Class.
 *
 * This class provides CiviCRM Custom Group integration.
 *
 * @since 0.5
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Custom_Group {

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
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_hooks The Mapper hooks registered flag.
	 */
	public $mapper_hooks = false;

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

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Always register plugin hooks.
		add_action( 'cwps/plugin/hooks/civicrm/add', [ $this, 'register_mapper_hooks' ] );
		add_action( 'cwps/plugin/hooks/civicrm/remove', [ $this, 'unregister_mapper_hooks' ] );

		// Always register Mapper callbacks.
		$this->register_mapper_hooks();

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.5
	 */
	public function unregister_hooks() {

		// Unregister Mapper callbacks.
		$this->unregister_mapper_hooks();

	}

	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.5
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
			return;
		}

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.5
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_hooks === false ) {
			return;
		}

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get all Custom Groups.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
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
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
	 * @since 0.5 Moved to this class.
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
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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

	// -------------------------------------------------------------------------

	/**
	 * Gets the Custom Groups for all CiviCRM Contacts.
	 *
	 * The returned array contains Custom Group data from the CiviCRM API. To
	 * fetch the Custom Fields keyed by the "name" of the Custom Group (e.g. for
	 * populating ACF Field "Choices") use the method with the same name located
	 * in the Custom Field class.
	 *
	 * CiviCRM has a special setting for "extends" called "Contact" that allows
	 * Custom Groups to be attached to any Contact Type.
	 *
	 * This should not be confused with "get_for_all_contact_types" which gets
	 * the Custom Groups for all top level CiviCRM Contact Types - and prepends
	 * the results of this query.
	 *
	 * @since 0.5
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_contacts() {

		// Init array to build.
		$custom_groups = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_groups;
		}

		// Construct params to get Groups for all Contacts.
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

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
	 * Gets all the Custom Groups for all CiviCRM Contact Types/Subtypes.
	 *
	 * The returned array contains Custom Group data from the CiviCRM API. To
	 * fetch the Custom Fields keyed by the "name" of the Custom Group (e.g. for
	 * populating ACF Field "Choices") use the method with the same name located
	 * in the Custom Field class.
	 *
	 * Prepends the Custom Groups for all CiviCRM Contacts.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_all_contact_types() {

		// Init array to build.
		$custom_groups = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_groups;
		}

		// Start with the Custom Groups for all Contact Types.
		$custom_groups = $this->get_for_contacts();

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

		// Append the Custom Groups if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {
			foreach ( $result['values'] as $key => $value ) {
				$custom_groups[] = $value;
			}
		}

		// --<
		return $custom_groups;

	}

	/**
	 * Get all the Custom Groups for all CiviCRM Activity Types.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_activities() {

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
			'extends' => 'Activity',
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
	 * Get all the Custom Groups for all CiviCRM Case Types.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_cases() {

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
			'extends' => 'Case',
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
	 * Get all the Custom Groups for all CiviCRM Participant Types.
	 *
	 * @since 0.5
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_participants() {

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
			'is_active' => 1,
			'options' => [
				'limit' => 0,
				'sort' => 'weight',
			],
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0,
				],
			],
			'extends' => 'Participant',
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
	 * Get all the Custom Groups for all CiviCRM Relationship Types.
	 *
	 * @since 0.5.1
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_relationships() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

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
			'extends' => 'Relationship',
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $custom_groups;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_groups;
		}

		// The result set is what we want.
		$custom_groups = $result['values'];

		// Set "cache".
		$pseudocache = $custom_groups;

		// --<
		return $custom_groups;

	}

	/**
	 * Get all the Custom Groups for CiviCRM Addresses.
	 *
	 * Custom Fields can only be added to all Addresses, there is no option to
	 * add them to different Location Types.
	 *
	 * @since 0.5.1
	 *
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_addresses() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

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
			'extends' => 'Address',
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $custom_groups;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $custom_groups;
		}

		// The result set is what we want.
		$custom_groups = $result['values'];

		// Set "cache".
		$pseudocache = $custom_groups;

		// --<
		return $custom_groups;

	}

	/**
	 * Get the Custom Groups for a CiviCRM Entity Type/Sub-type.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param string $type The Entity Type that the Custom Group applies to.
	 * @param string $subtype The Entity Sub-type that the Custom Group applies to.
	 * @param bool $with_fields Pass "true" to retrieve the Custom Fields as well.
	 * @return array $custom_groups The array of Custom Groups.
	 */
	public function get_for_entity_type( $type = '', $subtype = '', $with_fields = false ) {

		// Maybe set a key for the Sub-type.
		$key = $subtype;
		if ( empty( $subtype ) ) {
			$key = 'none';
		}

		// Maybe set a key for the boolean.
		$subkey = 'raw';
		if ( ! empty( $with_fields ) ) {
			$subkey = 'with_fields';
		}

		// Only do this once per Entity Type.
		static $pseudocache;
		if ( isset( $pseudocache[ $type ][ $key ][ $subkey ] ) ) {
			return $pseudocache[ $type ][ $key ][ $subkey ];
		}

		// Init return.
		$custom_groups = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_groups;
		}

		// Start with the Custom Groups for all Contact Types.
		if ( in_array( $type, $this->plugin->civicrm->contact_type->types_get_top_level() ) ) {
			$custom_groups = $this->get_for_contacts();
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

		// Maybe include Fields query.
		if ( ! empty( $with_fields ) ) {
			$params['api.CustomField.get'] = [
				'is_active' => 1,
				'options' => [
					'limit' => 0, // No limit.
				],
			];
		}

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Append the Custom Groups if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {
			foreach ( $result['values'] as $key => $value ) {

				// Skip adding if it extends a sibling Sub-type.
				if ( ! empty( $subtype ) && ! empty( $value['extends_entity_column_value'] ) ) {
					if ( ! in_array( $subtype, $value['extends_entity_column_value'] ) ) {
						continue;
					}
				}

				// Okay to add.
				$custom_groups[] = $value;

			}
		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $type ][ $key ][ $subkey ] ) ) {
			$pseudocache[ $type ][ $key ][ $subkey ] = $custom_groups;
		}

		// --<
		return $custom_groups;

	}

}
