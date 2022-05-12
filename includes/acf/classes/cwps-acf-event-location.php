<?php
/**
 * CiviCRM Event Location Class.
 *
 * Handles CiviCRM Event Location functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Event Location Class.
 *
 * A class that encapsulates CiviCRM Event Location functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Event_Location {

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
	 * Settings Fields.
	 *
	 * These Fields are attached to the Event Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $settings_fields The Settings Fields.
	 */
	public $settings_fields = [
		'is_show_location' => 'true_false',
		'loc_block_id' => 'select',
	];

	/**
	 * Address Fields.
	 *
	 * These Fields are attached to the Address Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $address_fields The Address Fields.
	 */
	public $address_fields = [
		'street_address' => 'text',
		'supplemental_address_1' => 'text',
		'supplemental_address_2' => 'text',
		'supplemental_address_3' => 'text',
		'city' => 'text',
		'county_id' => 'select',
		'state_province_id' => 'select',
		'country_id' => 'select',
		'postal_code' => 'text',
		'geo_code_1' => 'text',
		'geo_code_2' => 'text',
		'name' => 'text',
	];

	/**
	 * Email Fields.
	 *
	 * These Fields are attached to the Email Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $email_fields The array of Email Fields.
	 */
	public $email_fields = [
		'email' => 'email',
	];

	/**
	 * Phone Fields.
	 *
	 * These Fields are attached to the Phone Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $phone_fields The array of Phone Fields.
	 */
	public $phone_fields = [
		'phone' => 'text',
		'phone_ext' => 'text',
		'phone_type_id' => 'select',
	];



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

		// Some Event Location "Text" Fields need their own validation.
		//add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

		// Listen for queries from our ACF Field class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 20, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'text_settings_modify' ], 20, 2 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Getter for the public Event Fields.
	 *
	 * Filters out the Campaign ID when the CiviCampaign Component is not active.
	 *
	 * @since 0.5.4
	 */
	public function public_fields_get() {

		// Only do this once.
		static $done;
		if ( isset( $done ) ) {
			return $done;
		}

		// Build array of all Fields.
		$done = $this->settings_fields;
		$done += $this->address_fields;
		$done += $this->email_fields;
		$done += $this->phone_fields;

		// --<
		return $done;

	}



	// -------------------------------------------------------------------------



	/**
	 * Validate the content of a Field.
	 *
	 * Some Event Location Fields require validation.
	 *
	 * @since 0.5.4
	 *
	 * @param bool $valid The existing valid status.
	 * @param mixed $value The value of the Field.
	 * @param array $field The Field data array.
	 * @param string $input The input element's name attribute.
	 * @return string|bool $valid A string to display a custom error message, boolean otherwise.
	 */
	public function value_validate( $valid, $value, $field, $input ) {

		// Bail if it's not required and is empty.
		if ( $field['required'] == '0' && empty( $value ) ) {
			return $valid;
		}

		// Get the mapped Field name if present.
		$event_field_name = $this->civicrm->event->event_field_name_get( $field );
		if ( $event_field_name === false ) {
			return $valid;
		}

		// Validate depending on the Field name.
		switch ( $event_field_name ) {

			case 'duration':
				// Must be an integer.
				if ( ! ctype_digit( $value ) ) {
					$valid = __( 'Must be an integer.', 'civicrm-wp-profile-sync' );
				}
				break;

		}

		// --<
		return $valid;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the value of an Event Location Field, formatted for ACF.
	 *
	 * @since 0.5.4
	 *
	 * @param mixed $value The Field value.
	 * @param array $name The Field name.
	 * @param string $selector The ACF Field selector.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return mixed $value The formatted Field value.
	 */
	public function value_get_for_acf( $value, $name, $selector, $post_id ) {

		// Bail if empty.
		if ( empty( $value ) ) {
			return $value;
		}

		// Bail if value is (string) 'null' which CiviCRM uses for some reason.
		if ( $value == 'null' ) {
			return '';
		}

		// Get the ACF type for this Field.
		$type = $this->get_acf_type( $name );

		// Convert CiviCRM value to ACF value by Field.
		switch ( $type ) {

			// Unused at present.
			case 'select':
			case 'checkbox':

				// Convert if the value has the special CiviCRM array-like format.
				if ( false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
					$value = CRM_Utils_Array::explodePadded( $value );
				}

				break;

			// Used by "Birth Date" and "Deceased Date".
			case 'date_picker':
			case 'date_time_picker':

				// Get Field setting.
				$acf_setting = get_field_object( $selector, $post_id );

				// Date Picker test.
				if ( $acf_setting['type'] == 'date_picker' ) {

					// Event edit passes a Y-m-d format, so test for that.
					$datetime = DateTime::createFromFormat( 'Y-m-d', $value );

					// Event create passes a different format, so test for that.
					if ( $datetime === false ) {
						$datetime = DateTime::createFromFormat( 'YmdHis', $value );
					}

					// Convert to ACF format.
					$value = $datetime->format( 'Ymd' );

				// Date & Time Picker test.
				} elseif ( $acf_setting['type'] == 'date_time_picker' ) {

					// Event edit passes a YmdHis format, so test for that.
					$datetime = DateTime::createFromFormat( 'YmdHis', $value );

					// Event API passes a different format, so test for that.
					if ( $datetime === false ) {
						$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
					}

					// Convert to ACF format.
					$value = $datetime->format( 'Y-m-d H:i:s' );

				}

				break;

		}

		// TODO: Filter here?

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the "select" options for a given CiviCRM Event Location Field.
	 *
	 * @since 0.5.4
	 *
	 * @param string $name The name of the Field.
	 * @return array $options The array of Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// We only have a few to account for.

		// Location Block ID.
		if ( $name == 'loc_block_id' ) {
			$options = $this->get_all();
		}

		// Phone Type ID.
		if ( $name == 'phone_type_id' ) {
			$options = $this->plugin->civicrm->phone->phone_types_get();
		}

		// --<
		return $options;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets all Event Locations.
	 *
	 * @since 0.5.4
	 *
	 * @return array $locations The array of Event Locations keyed by LocBlock ID.
	 */
	public function get_all() {

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
			if ( ! empty( $address ) ) {
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



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Event Location for a given set of data.
	 *
	 * @since 0.5.4
	 *
	 * @param array $location The CiviCRM Event Location data.
	 * @return array|bool $location_data The array Event Location data from the CiviCRM API, or false on failure.
	 */
	public function create( $location ) {

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
	public function update( $location ) {

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
		return $this->create( $location );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Event Location Fields for an ACF Field.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $event_fields The array of Event Location Fields.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$event_fields = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $event_fields;
		}

		// Bail if this is not an Event Field Group.
		$is_event_field_group = $this->civicrm->event->is_event_field_group( $field_group );
		if ( $is_event_field_group === false ) {
			return $event_fields;
		}

		// TODO: Do we need this loop?

		// Loop through the Post Types.
		foreach ( $is_event_field_group as $post_type_name ) {

			// Get public Fields.
			$event_fields_for_type = $this->data_get( $field['type'], 'public' );

			// Merge with return array.
			$event_fields = array_merge( $event_fields, $event_fields_for_type );

		}

		// --<
		return $event_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Event Location Field options for a given Field ID.
	 *
	 * @since 0.5.4
	 *
	 * @param string $name The name of the Field.
	 * @param string $action The name of the Action.
	 * @return array $field The array of Field data.
	 */
	public function get_by_name( $name, $action = 'get' ) {

		// Init return.
		$field = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'name' => $name,
			'action' => $action,
		];

		// Call the API.
		$result = civicrm_api( 'Event', 'getfield', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $field;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $field;
		}

		// The result set is the item.
		$field = $result['values'];

		// --<
		return $field;

	}



	/**
	 * Get the Event Location Fields for an ACF Field Type.
	 *
	 * @since 0.5.4
	 *
	 * @param string $field_type The type of ACF Field.
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function data_get( $field_type = '', $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			return $pseudocache[ $filter ][ $field_type ];
		}

		// Get all Fields.
		$fields = $this->civicrm->event_field->data_get_by_action();

		// Check for filter.
		if ( $filter !== 'none' ) {

			// Check settings filter.
			if ( $filter == 'settings' ) {

				// Skip all but those defined in our Settings Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->settings_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				$fields = [];
				foreach ( $filtered as $key => $value ) {
					if ( $field_type == $this->settings_fields[ $value['name'] ] ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			$pseudocache[ $filter ][ $field_type ] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Address Fields for an ACF Field Type.
	 *
	 * @since 0.5.4
	 *
	 * @param string $field_type The type of ACF Field.
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function data_address_get( $field_type = '', $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			return $pseudocache[ $filter ][ $field_type ];
		}

		// Init return.
		$fields = $this->get_address_fields( 'create' );

		// Check for filter.
		if ( $filter !== 'none' ) {

			// Check public filter.
			if ( $filter == 'public' ) {

				// Skip all but those mapped to the type of ACF Field.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( $field_type == $this->address_fields[ $value['name'] ] ) {
						$filtered[] = $value;
					}
				}

				// Overwrite Fields array.
				$fields = $filtered;

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			$pseudocache[ $filter ][ $field_type ] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Email Fields for an ACF Field Type.
	 *
	 * @since 0.5.4
	 *
	 * @param string $field_type The type of ACF Field.
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function data_email_get( $field_type = '', $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			return $pseudocache[ $filter ][ $field_type ];
		}

		// Init return.
		$fields = $this->get_email_fields( 'create' );

		// Check for filter.
		if ( $filter !== 'none' ) {

			// Check public filter.
			if ( $filter == 'public' ) {

				// Skip all but those mapped to the type of ACF Field.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( $field_type == $this->email_fields[ $value['name'] ] ) {
						$filtered[] = $value;
					}
				}

				// Overwrite Fields array.
				$fields = $filtered;

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			$pseudocache[ $filter ][ $field_type ] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Phone Fields for an ACF Field Type.
	 *
	 * @since 0.5.4
	 *
	 * @param string $field_type The type of ACF Field.
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function data_phone_get( $field_type = '', $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			return $pseudocache[ $filter ][ $field_type ];
		}

		// Init return.
		$fields = $this->get_phone_fields( 'create' );

		// Check for filter.
		if ( $filter !== 'none' ) {

			// Check public filter.
			if ( $filter == 'public' ) {

				// Skip all but those mapped to the type of ACF Field.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( $field_type == $this->phone_fields[ $value['name'] ] ) {
						$filtered[] = $value;
					}
				}

				// Overwrite Fields array.
				$fields = $filtered;

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			$pseudocache[ $filter ][ $field_type ] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Event Location Fields for a given filter and action.
	 *
	 * @since 0.5.4
	 *
	 * @param string $filter The token by which to filter the array of Fields.
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of Field names.
	 */
	public function data_get_filtered( $filter = 'none', $action = '' ) {

		// Maybe set a key for the subtype.
		$index = $action;
		if ( empty( $action ) ) {
			$index = 'all';
		}

		// Only do this once per filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ][ $index ] ) ) {
			return $pseudocache[ $filter ][ $index ];
		}

		// Get all Fields for this action.
		$fields = $this->civicrm->event_field->data_get_by_action( $action );

		// Check for filter.
		if ( $filter !== 'none' ) {

			// Check "public" filter.
			if ( $filter == 'public' ) {

				// Skip all but those defined in our Settings Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->settings_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Maybe order them by our Settings Fields array.
				$fields = [];
				if ( ! empty( $filtered ) ) {
					foreach ( $this->settings_fields as $key => $field_type ) {
						foreach ( $filtered as $value ) {
							if ( $value['name'] === $key ) {
								$fields[] = $value;
								break;
							}
						}
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ][ $index ] ) ) {
			$pseudocache[ $filter ][ $index ] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the public Event Location Fields for a given action.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $public_fields The array of CiviCRM Fields.
	 */
	public function get_public_fields( $action ) {

		// Init return.
		$public_fields = [];

		// Get the public Fields.
		$public_fields = $this->data_get_filtered( 'public', $action );

		// --<
		return $public_fields;

	}



	/**
	 * Get the Event Location Settings Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $settings_fields The array of CiviCRM Fields.
	 */
	public function get_settings_fields( $action ) {

		// Init return.
		$settings_fields = [];

		// Get the Settings Fields.
		$settings_fields = $this->data_get_filtered( 'settings', $action );

		// --<
		return $settings_fields;

	}



	/**
	 * Get the Event Location Address Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of CiviCRM Fields.
	 */
	public function get_address_fields( $action ) {

		// Init return.
		$fields = [];

		// Get all Address Fields.
		$address_fields = $this->civicrm->address->civicrm_fields_get();

		// Skip all but those defined in our Address Fields array.
		$filtered = [];
		foreach ( $address_fields as $key => $value ) {
			if ( array_key_exists( $value['name'], $this->address_fields ) ) {
				$filtered[] = $value;
			}
		}

		// Maybe order them by our Address Fields array.
		if ( ! empty( $filtered ) ) {
			foreach ( $this->address_fields as $key => $field_type ) {
				foreach ( $filtered as $value ) {
					if ( $value['name'] === $key ) {
						$fields[] = $value;
						break;
					}
				}
			}
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Event Location Email Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of CiviCRM Fields.
	 */
	public function get_email_fields( $action ) {

		// Init return.
		$fields = [];

		// Get all Email Fields.
		$email_fields = $this->civicrm->email->civicrm_fields_get();

		// Skip all but those defined in our Location Fields array.
		$filtered = [];
		foreach ( $email_fields as $key => $value ) {
			if ( array_key_exists( $value['name'], $this->email_fields ) ) {
				$filtered[] = $value;
			}
		}

		// Maybe order them by our Location Fields array.
		if ( ! empty( $filtered ) ) {
			foreach ( $this->email_fields as $key => $field_type ) {
				foreach ( $filtered as $value ) {
					if ( $value['name'] === $key ) {
						$fields[] = $value;
						break;
					}
				}
			}
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Event Location Phone Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $phone_fields The array of CiviCRM Fields.
	 */
	public function get_phone_fields( $action ) {

		// Init return.
		$fields = [];

		// Get all Phone Fields.
		$phone_fields = $this->civicrm->phone->civicrm_fields_get();

		// Skip all but those defined in our Location Fields array.
		$filtered = [];
		foreach ( $phone_fields as $key => $value ) {
			if ( array_key_exists( $value['name'], $this->phone_fields ) ) {
				$filtered[] = $value;
			}
		}

		// Maybe order them by our Location Fields array.
		if ( ! empty( $filtered ) ) {
			foreach ( $this->phone_fields as $key => $field_type ) {
				foreach ( $filtered as $value ) {
					if ( $value['name'] === $key ) {
						$fields[] = $value;
						break;
					}
				}
			}
		}

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Fields for an ACF Field and mapped to a CiviCRM Event Type.
	 *
	 * @since 0.5.4
	 *
	 * @param string $type The type of ACF Field.
	 * @return array $fields The array of Field names.
	 */
	public function get_by_acf_type( $type = '' ) {

		// Init return.
		$event_fields = [];

		// Skip all but those mapped to the type of ACF Field.
		foreach ( $this->event_fields as $key => $value ) {
			if ( $type == $value ) {
				$event_fields[ $key ] = $value;
			}
		}

		// --<
		return $event_fields;

	}



	/**
	 * Get the ACF Field Type for an Event Location Field.
	 *
	 * @since 0.5.4
	 *
	 * @param string $name The name of the Field.
	 * @return array $fields The array of Field names.
	 */
	public function get_acf_type( $name = '' ) {

		// Init return.
		$type = false;

		// If the key exists, return the value - which is the ACF Type.
		if ( array_key_exists( $name, $this->event_fields ) ) {
			$type = $this->event_fields[ $name ];
		}

		// --<
		return $type;

	}



	// -------------------------------------------------------------------------



	/**
	 * Modify the Settings of an ACF "Select" Field.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function select_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'select' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Field name if present.
		$event_field_name = $this->civicrm->event->event_field_name_get( $field );
		if ( $event_field_name === false ) {
			return $field;
		}

		// Bail if not one of our Fields. Necessary because prefix is shared.
		if ( ! array_key_exists( $event_field_name, $this->public_fields_get() ) ) {
			return $field;
		}

		// Get keyed array of settings.
		$field['choices'] = $this->options_get( $event_field_name );

		// Set a default for "Location Block ID".
		if ( $event_field_name == 'loc_block_id' ) {
			$field['choices'] = [ '' => __( 'None', 'civicrm-wp-profile-sync' ) ] + $field['choices'];
			$field['default_value'] = '';
			$field['ui'] = 1;
			$field['ajax'] = 1;
		}

		// --<
		return $field;

	}



	/**
	 * Modify the Settings of an ACF "Text" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function text_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'text' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Field name if present.
		$event_field_name = $this->civicrm->event->event_field_name_get( $field );
		if ( $event_field_name === false ) {
			return $field;
		}

		// Bail if not one of our Fields. Necessary because prefix is shared.
		if ( ! array_key_exists( $event_field_name, $this->public_fields_get() ) ) {
			return $field;
		}

		// Maybe get Address Field data.
		if ( array_key_exists( $event_field_name, $this->address_fields ) ) {
			$field_data = $this->plugin->civicrm->address->get_by_name( $event_field_name );
		}

		// Maybe get Phone Field data.
		if ( array_key_exists( $event_field_name, $this->phone_fields ) ) {
			$field_data = $this->plugin->civicrm->phone->get_by_name( $event_field_name );
		}

		// Set the "maxlength" attribute.
		if ( ! empty( $field_data['maxlength'] ) ) {
			$field['maxlength'] = $field_data['maxlength'];
		}

		// --<
		return $field;

	}



} // Class ends.



