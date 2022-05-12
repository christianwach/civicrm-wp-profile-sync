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

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
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
	 * Get the mapped Event Field name if present.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The existing Field data array.
	 * @return string|bool $event_field_name The name of the Event Field, or false if none.
	 */
	public function event_field_name_get( $field ) {

		// Init return.
		$event_field_name = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Set the mapped Event Field name if present.
		if ( isset( $field[ $acf_field_key ] ) ) {
			if ( false !== strpos( $field[ $acf_field_key ], $this->event_field_prefix ) ) {
				$event_field_name = (string) str_replace( $this->event_field_prefix, '', $field[ $acf_field_key ] );
			}
		}

		/**
		 * Filter the Event Field name.
		 *
		 * @since 0.5.4
		 *
		 * @param integer $event_field_name The existing Event Field name.
		 * @param array $field The array of ACF Field data.
		 */
		$event_field_name = apply_filters( 'cwps/acf/civicrm/event/event_field/name', $event_field_name, $field );

		// --<
		return $event_field_name;

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

		// Get the Fields on the Entity for this Field Type.
		$core_fields = $this->civicrm->event_field->data_get( $field['type'], 'public' );

		// Get the Location Fields on the Entity for this Field Type.
		$location_fields = $this->civicrm->event_location->data_get( $field['type'], 'settings' );
		$location_address_fields = $this->civicrm->event_location->data_address_get( $field['type'], 'public' );
		$location_email_fields = $this->civicrm->event_location->data_email_get( $field['type'], 'public' );
		$location_phone_fields = $this->civicrm->event_location->data_phone_get( $field['type'], 'public' );

		// Get the Registration Fields on the Entity for this Field Type.
		$registration_fields = $this->civicrm->event_registration->data_get( $field['type'], 'settings' );
		$registration_screen_fields = $this->civicrm->event_registration->data_get( $field['type'], 'register' );
		$confirmation_screen_fields = $this->civicrm->event_registration->data_get( $field['type'], 'confirm' );
		$thankyou_screen_fields = $this->civicrm->event_registration->data_get( $field['type'], 'thankyou' );
		$email_screen_fields = $this->civicrm->event_registration->data_get( $field['type'], 'email' );

		// Parse the Event Type.
		$event_type = reset( $entity_array[ $this->identifier ] );
		if ( $event_type == '0' ) {
			$event_type = '';
		} else {
			$event_type = (int) $event_type;
		}

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Event', $event_type );

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
		if (
			empty( $core_fields ) &&
			empty( $location_fields ) &&
			empty( $location_address_fields ) &&
			empty( $location_email_fields ) &&
			empty( $location_phone_fields ) &&
			empty( $registration_fields ) &&
			empty( $registration_screen_fields ) &&
			empty( $filtered_fields )
		) {
			return $choices;
		}

		// Build Event Field choices array for dropdown.
		if ( ! empty( $core_fields ) ) {
			$label = esc_attr__( 'Event Fields', 'civicrm-wp-profile-sync' );
			foreach ( $core_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Build Custom Field choices array for dropdown.
		if ( ! empty( $filtered_fields ) ) {
			$custom_field_prefix = $this->civicrm->custom_field_prefix();
			foreach ( $filtered_fields as $custom_group_name => $custom_group ) {
				$label = esc_attr( $custom_group_name );
				foreach ( $custom_group as $custom_field ) {
					$choices[ $label ][ $custom_field_prefix . $custom_field['id'] ] = $custom_field['label'];
				}
			}
		}

		// Build Event Location Field choices array for dropdown.
		if ( ! empty( $location_fields ) ) {
			$label = esc_attr__( 'Event Location Fields', 'civicrm-wp-profile-sync' );
			foreach ( $location_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Build Event Location Address Field choices array for dropdown.
		if ( ! empty( $location_address_fields ) ) {
			$label = esc_attr__( 'Event Location Address Fields', 'civicrm-wp-profile-sync' );
			foreach ( $location_address_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Build Event Location Email Field choices array for dropdown.
		if ( ! empty( $location_email_fields ) ) {
			$label = esc_attr__( 'Event Location Email Fields', 'civicrm-wp-profile-sync' );
			foreach ( $location_email_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Build Event Location Phone Field choices array for dropdown.
		if ( ! empty( $location_phone_fields ) ) {
			$label = esc_attr__( 'Event Location Phone Fields', 'civicrm-wp-profile-sync' );
			foreach ( $location_phone_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Build Event Registration Field choices array for dropdown.
		if ( ! empty( $registration_fields ) ) {
			$label = esc_attr__( 'Event Registration Fields', 'civicrm-wp-profile-sync' );
			foreach ( $registration_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Build Event Registration Screen Field choices array for dropdown.
		if ( ! empty( $registration_screen_fields ) ) {
			$label = esc_attr__( 'Event Registration Screen Fields', 'civicrm-wp-profile-sync' );
			foreach ( $registration_screen_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Maybe add Profile Fields to Event Registration Screen Field choices array.
		if ( $field['type'] === 'select' ) {
			$label = esc_attr__( 'Event Registration Screen Fields', 'civicrm-wp-profile-sync' );
			$title = __( 'Include Profile (top of page)', 'civicrm-wp-profile-sync' );
			$choices[ $label ][ $this->event_field_prefix . 'custom_pre_id' ] = $title;
			$title = __( 'Include Profile (bottom of page)', 'civicrm-wp-profile-sync' );
			$choices[ $label ][ $this->event_field_prefix . 'custom_post_id' ] = $title;
		}

		// Build Event Registration Confirmation Screen Field choices array for dropdown.
		if ( ! empty( $confirmation_screen_fields ) ) {
			$label = esc_attr__( 'Event Registration Confirmation Screen Fields', 'civicrm-wp-profile-sync' );
			foreach ( $confirmation_screen_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Build Event Registration Thank You Screen Field choices array for dropdown.
		if ( ! empty( $thankyou_screen_fields ) ) {
			$label = esc_attr__( 'Event Registration Thank You Screen Fields', 'civicrm-wp-profile-sync' );
			foreach ( $thankyou_screen_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
			}
		}

		// Build Event Registration Confirmation Email Field choices array for dropdown.
		if ( ! empty( $email_screen_fields ) ) {
			$label = esc_attr__( 'Event Registration Confirmation Email Fields', 'civicrm-wp-profile-sync' );
			foreach ( $email_screen_fields as $item ) {
				$choices[ $label ][ $this->event_field_prefix . $item['name'] ] = $item['title'];
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

		// Add an Option Group.
		$event_types_title = esc_attr( __( 'Event Types', 'civicrm-wp-profile-sync' ) );
		$entities[ $event_types_title ] = [];

		// Prepend "All" option.
		$entities[ $event_types_title ][ $this->identifier . '-0' ] = __( 'Any Event Type', 'civicrm-wp-profile-sync' );

		// Add entries for each Event Type.
		foreach ( $event_types as $event_type ) {
			$entities[ $event_types_title ][ $this->identifier . '-' . $event_type['value'] ] = $event_type['label'];
		}

		// --<
		return $entities;

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



