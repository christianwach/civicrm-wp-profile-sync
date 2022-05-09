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
	 * Entity identifier.
	 *
	 * This identifier is unique to this "top level" Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string $identifier The unique identifier for this "top level" Entity.
	 */
	public $identifier = 'event';

	/**
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Event Fields from Custom Fields.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string $event_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $event_field_prefix = 'caievent_';



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

		// Listen for queries from the ACF Bypass class.
		//add_filter( 'cwps/acf/bypass/query_settings_field', [ $this, 'query_bypass_settings_field' ], 20, 4 );
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

		// Listen for queries from the ACF Bypass Location Rule class.
		add_filter( 'cwps/acf/bypass/location/query_entities', [ $this, 'query_bypass_entities' ], 20, 2 );

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



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Event for a given set of data.
	 *
	 * @since 0.5.4
	 *
	 * @param array $event The CiviCRM Event data.
	 * @return array|bool $event_data The array Event data from the CiviCRM API, or false on failure.
	 */
	public function create( $event ) {

		// Init as failure.
		$event_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $event_data;
		}

		// Build params to create Event.
		$params = [
			'version' => 3,
		] + $event;

		/*
		 * Minimum array to create an Event:
		 *
		 * $params = [
		 *   'version' => 3,
		 *   'title' => "My Event",
		 *   'start_date' => '2022-05-20 02:00:00',
		 * ];
		 *
		 * Also required is either one of the following:
		 *
		 * * 'event_type_id'
		 * * 'template_id'
		 *
		 * Updates are triggered by:
		 *
		 * $params['id'] = 654;
		 *
		 * Custom Fields are addressed by ID:
		 *
		 * $params['custom_9'] = "Blah";
		 * $params['custom_7'] = 1;
		 * $params['custom_8'] = 0;
		 *
		 * CiviCRM kindly ignores any Custom Fields which are passed to it that
		 * aren't attached to the Entity. This is of significance when a Field
		 * Group is attached to multiple Post Types (for example) and the Fields
		 * refer to different Entities (e.g. "Event" and "Student").
		 *
		 * Nice.
		 */

		// Call the API.
		$result = civicrm_api( 'Event', 'create', $params );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'params' => $params,
			'result' => $result,
			//'backtrace' => $trace,
		], true ) );
		*/

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
	 * Update a CiviCRM Event with a given set of data.
	 *
	 * @since 0.5.4
	 *
	 * @param array $event The CiviCRM Event data.
	 * @return array|bool $event_data The array Event data from the CiviCRM API, or false on failure.
	 */
	public function update( $event ) {

		// Log and bail if there's no Event ID.
		if ( empty( $event['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numeric ID must be present to update an Event.', 'civicrm-wp-profile-sync' ),
				'event' => $event,
				'backtrace' => $trace,
			], true ) );
			return $event_data;
		}

		// Pass through.
		return $this->create( $event );

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
	 * Returns a Setting Field for a Bypass ACF Field Group when found.
	 *
	 * @since 0.5.4
	 *
	 * @param array $setting_field The existing Setting Field array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param array $entity_array The Entity and ID array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_bypass_settings_field( $setting_field, $field, $field_group, $entity_array ) {

		// Pass if not our Entity Type.
		if ( ! array_key_exists( $this->identifier, $entity_array ) ) {
			return $setting_field;
		}

		// Get the public Fields on the Entity for this Field Type.
		$fields_for_entity = $this->civicrm->event_field->data_get( $field['type'], 'public' );

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Event', '' );

		/**
		 * Filter the Custom Fields.
		 *
		 * @since 0.5.4
		 *
		 * @param array The initially empty array of filtered Custom Fields.
		 * @param array $custom_fields The CiviCRM Custom Fields array.
		 * @param array $field The ACF Field data array.
		 */
		$filtered_fields = apply_filters( 'cwps/acf/query_settings/custom_fields_filter', [], $custom_fields, $field );

		// Pass if not populated.
		if ( empty( $fields_for_entity ) && empty( $filtered_fields ) ) {
			return $setting_field;
		}

		// Get the Setting Field.
		$setting_field = $this->acf_field_get( $filtered_fields, $fields_for_entity );

		// Return populated array.
		return $setting_field;

	}



	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
	 *
	 * @since 0.5.4
	 *
	 * @param array $choices The existing Setting Field choices array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param array $entity_array The Entity and ID array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_bypass_settings_choices( $choices, $field, $field_group, $entity_array ) {

		// Pass if not our Entity Type.
		if ( ! array_key_exists( $this->identifier, $entity_array ) ) {
			return $choices;
		}

		// Get the public Fields on the Entity for this Field Type.
		$fields_for_entity = $this->civicrm->event_field->data_get( $field['type'], 'public' );

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Event', '' );

		/**
		 * Filter the Custom Fields.
		 *
		 * @since 0.5.4
		 *
		 * @param array The initially empty array of filtered Custom Fields.
		 * @param array $custom_fields The CiviCRM Custom Fields array.
		 * @param array $field The ACF Field data array.
		 */
		$filtered_fields = apply_filters( 'cwps/acf/query_settings/custom_fields_filter', [], $custom_fields, $field );

		// Pass if not populated.
		if ( empty( $fields_for_entity ) && empty( $filtered_fields ) ) {
			return $choices;
		}

		// Build Event Field choices array for dropdown.
		if ( ! empty( $fields_for_entity ) ) {
			$event_fields_label = esc_attr__( 'Event Fields', 'civicrm-wp-profile-sync' );
			foreach ( $fields_for_entity as $event_field ) {
				$choices[ $event_fields_label ][ $this->event_field_prefix . $event_field['name'] ] = $event_field['title'];
			}
		}

		// Build Custom Field choices array for dropdown.
		if ( ! empty( $filtered_fields ) ) {
			$custom_field_prefix = $this->civicrm->custom_field_prefix();
			foreach ( $filtered_fields as $custom_group_name => $custom_group ) {
				$custom_fields_label = esc_attr( $custom_group_name );
				foreach ( $custom_group as $custom_field ) {
					$choices[ $custom_fields_label ][ $custom_field_prefix . $custom_field['id'] ] = $custom_field['label'];
				}
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5.4
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/event/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



	/**
	 * Appends a nested array of possible values to the Entities array for the
	 * Bypass Location Rule.
	 *
	 * @since 0.5.4
	 *
	 * @param array $entities The existing Entity values array.
	 * @param array $rule The current Location Rule.
	 * @return array $entities The modified Entity values array.
	 */
	public function query_bypass_entities( $entities, $rule = [] ) {

		// Get all Event Types.
		$event_types = $this->civicrm->event_type->get_all();

		// Bail if there are none.
		if ( empty( $event_types ) ) {
			return $entities;
		}

		// Add Option Group and add entries for each Event Type.
		$event_types_title = esc_attr( __( 'Event Types', 'civicrm-wp-profile-sync' ) );
		$entities[ $event_types_title ] = [];
		foreach ( $event_types as $event_type ) {
			$entities[ $event_types_title ][ $this->identifier . '-' . $event_type['value'] ] = $event_type['label'];
		}

		// --<
		return $entities;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets all Event Locations.
	 *
	 * @since 0.5.4
	 *
	 * @return array $locations The array of Event Locations keyed by LocBlock ID.
	 */
	public function locations_get_all() {

		// Init return.
		$locations = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $locations;
		}

		// The fields that we want.
		$fields = [
			'loc_block_id',
			'loc_block_id.address_id.name',
			'loc_block_id.address_id.street_address',
			'loc_block_id.address_id.supplemental_address_1',
			'loc_block_id.address_id.supplemental_address_2',
			'loc_block_id.address_id.supplemental_address_3',
			'loc_block_id.address_id.city',
			'loc_block_id.address_id.state_province_id.name',
		];

		// Build params.
		$params = [
			'version' => 3,
			'check_permissions' => true,
			'return' => $fields,
			'loc_block_id.address_id' => [
				'IS NOT NULL' => 1,
			],
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Event', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $locations;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $locations;
		}

		// Build and format the returned array.
		foreach ( $result['values'] as $location ) {

			$address = '';

			// Add Address elements.
			foreach ( $fields as $field ) {
				if ( $field !== 'loc_block_id' && ! empty( $location[ $field ] ) ) {
					$address .= ( $address ? ' :: ' : '' ) . $location[ $field ];
				}
			}

			// Maybe add to return.
			if ( $address ) {
				$locations[ (int) $location['loc_block_id'] ] = $address;
			}

		}

		// Keep the same order as CiviCRM.
		$locations = CRM_Utils_Array::asort( $locations );

		// --<
		return $locations;

	}



	/**
	 * Gets an Event Location given an ID.
	 *
	 * @since 0.5.4
	 *
	 * @param int $location_id The numeric ID of the Event Location.
	 * @param str $mode Flag to determine the returned data. Defaults to API return data.
	 * @return array|bool $location The array of Event Location data, or false on failure.
	 */
	public function location_get_by_id( $location_id, $mode = '' ) {

		// Init return.
		$location = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $location;
		}

		// TODO: Build logic to get full Event Location data.

		// Build params.
		$params = [
			'version' => 3,
			'id' => $location_id,
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'LocBlock', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $location;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $location;
		}

		// We want what should be the only item.
		$location = array_pop( $result['values'] );

		// --<
		return $location;

	}



	/**
	 * Create a CiviCRM Event Location for a given set of data.
	 *
	 * @since 0.5.4
	 *
	 * @param array $location The CiviCRM Event Location data.
	 * @return array|bool $location_data The array Event Location data from the CiviCRM API, or false on failure.
	 */
	public function location_create( $location ) {

		// Init as failure.
		$location_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $location_data;
		}

		// Build params to create Event Location.
		$params = [
			'version' => 3,
		] + $location;

		/*
		 * Minimum array to create an Event Location:
		 *
		 * $params = [
		 *   'version' => 3,
		 * ];
		 *
		 * Yeah, it's unusual but no params are required.
		 *
		 * Updates are triggered by:
		 *
		 * $params['id'] = 654;
		 */

		// Call the API.
		$result = civicrm_api( 'LocBlock', 'create', $params );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'params' => $params,
			'result' => $result,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $location_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $location_data;
		}

		// The result set should contain only one item.
		$location_data = array_pop( $result['values'] );

		// --<
		return $location_data;

	}



	/**
	 * Update a CiviCRM Event Location with a given set of data.
	 *
	 * @since 0.5.4
	 *
	 * @param array $location The CiviCRM Event Location data.
	 * @return array|bool $location_data The array Event Location data from the CiviCRM API, or false on failure.
	 */
	public function location_update( $location ) {

		// Log and bail if there's no Event Location ID.
		if ( empty( $location['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numeric ID must be present to update an Event Location.', 'civicrm-wp-profile-sync' ),
				'location' => $location,
				'backtrace' => $trace,
			], true ) );
			return $location_data;
		}

		// Pass through.
		return $this->location_create( $location );

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



