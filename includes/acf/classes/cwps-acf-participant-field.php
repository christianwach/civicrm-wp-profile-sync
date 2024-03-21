<?php
/**
 * CiviCRM Participant Field Class.
 *
 * Handles CiviCRM Participant Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Participant Field Class.
 *
 * A class that encapsulates CiviCRM Participant Field functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_Field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Built-in Participant Fields.
	 *
	 * These are mapped to their corresponding ACF Field types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $participant_fields = [
		'contact_id' => [
			'civicrm_contact',
			'civicrm_contact_existing_new',
		],
		'event_id' => [
			'civicrm_event',
			'civicrm_event_group',
		],
		'status_id' => 'select',
		'register_date' => 'date_time_picker',
		'source' => 'text',
	];

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
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
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

		// Intercept Post created, updated (or synced) from Participant events.
		add_action( 'cwps/acf/post/participant/created', [ $this, 'post_edited' ], 10 );
		add_action( 'cwps/acf/post/participant/edited', [ $this, 'post_edited' ], 10 );
		add_action( 'cwps/acf/post/participant/sync', [ $this, 'participant_sync_to_post' ], 10 );

		// Maybe sync the various Participant "Date" Fields to ACF Fields attached to the WordPress Post.
		add_action( 'cwps/acf/participant/acf_fields_saved', [ $this, 'maybe_sync_fields' ], 10 );

		// Some Participant "Text" Fields need their own validation.
		add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

		// Listen for queries from our ACF Field class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 30, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'date_time_picker_settings_modify' ], 10, 2 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Validate the content of a Field.
	 *
	 * Some Participant Fields require validation.
	 *
	 * @since 0.5
	 *
	 * @param bool   $valid The existing valid status.
	 * @param mixed  $value The value of the Field.
	 * @param array  $field The Field data array.
	 * @param string $input The input element's name attribute.
	 * @return string|bool $valid A string to display a custom error message, boolean otherwise.
	 */
	public function value_validate( $valid, $value, $field, $input ) {

		// Bail if it's not required and is empty.
		if ( $field['required'] == '0' && empty( $value ) ) {
			return $valid;
		}

		// Get the mapped Participant Field name if present.
		$participant_field_name = $this->civicrm->participant->participant_field_name_get( $field );
		if ( $participant_field_name === false ) {
			return $valid;
		}

		/*
		// Validate depending on the Field name.
		switch ( $participant_field_name ) {

			case 'duration' :
				// Must be an integer.
				if ( ! ctype_digit( $value ) ) {
					$valid = __( 'Must be an integer.', 'civicrm-wp-profile-sync' );
				}
				break;

		}
		*/

		// --<
		return $valid;

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a Post has been updated from a Participant via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Participant Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Participant and WordPress Post params.
	 */
	public function participant_sync_to_post( $args ) {

		// Re-use Post Edited method.
		$this->post_edited( $args );

	}

	/**
	 * Intercept when a Post has been updated from a Participant via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Participant Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Participant and WordPress Post params.
	 */
	public function post_edited( $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Participant Fields.
		if ( empty( $acf_fields['participant'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['participant'] as $selector => $participant_field ) {

			// Skip if it's not a public Participant Field.
			if ( ! array_key_exists( $participant_field, $this->participant_fields ) ) {
				continue;
			}

			// Does the mapped Participant Field exist?
			if ( isset( $args['objectRef']->$participant_field ) ) {

				// Modify value for ACF prior to update.
				$value = $this->value_get_for_acf(
					$args['objectRef']->$participant_field,
					$participant_field,
					$selector,
					$args['post_id']
				);

				// Update it.
				$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

			}

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the value of a Participant Field, formatted for ACF.
	 *
	 * @since 0.5
	 *
	 * @param mixed          $value The Participant Field value.
	 * @param array          $name The Participant Field name.
	 * @param string         $selector The ACF Field selector.
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

		// Get the ACF type for this Participant Field.
		$type = $this->get_acf_type( $name );

		// Are there multiple possible Fields.
		if ( is_array( $type ) ) {

			// Query the Field setting for the actual type.
			$acf_setting = get_field_object( $selector, $post_id );

			/*
			 * Override type.
			 *
			 * This works because we define the Local Field "name" value as the
			 * same as its "type" value when composing the Field Group for the
			 * built-in Participant CPT.
			 *
			 * When the Field is added to a Field Group via the UI, the Field
			 * Object is populated. This is not the case when added via code to
			 * a Local Field Group.
			 *
			 * TODO: Try and find out why this is.
			 */
			$type = isset( $acf_setting['type'] ) ? $acf_setting['type'] : $selector;

		}

		// Convert CiviCRM value to ACF value by Participant Field.
		switch ( $type ) {

			// Used by "Status ID".
			case 'select':
			case 'checkbox':

				// Convert if the value has the special CiviCRM array-like format.
				if ( is_string( $value ) && false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
					$value = CRM_Utils_Array::explodePadded( $value );
				}

				break;

			// Used by "Register Date".
			case 'date_picker':
			case 'date_time_picker':

				// Get Field setting.
				$acf_setting = get_field_object( $selector, $post_id );

				// Date Picker test.
				if ( $acf_setting['type'] == 'date_picker' ) {

					// Participant edit passes a Y-m-d format, so test for that.
					$datetime = DateTime::createFromFormat( 'Y-m-d', $value );

					// Participant create passes a different format, so test for that.
					if ( $datetime === false ) {
						$datetime = DateTime::createFromFormat( 'YmdHis', $value );
					}

					// Convert to ACF format.
					$value = $datetime->format( 'Ymd' );

				// Date & Time Picker test.
				} elseif ( $acf_setting['type'] == 'date_time_picker' ) {

					// Participant edit passes a YmdHis format, so test for that.
					$datetime = DateTime::createFromFormat( 'YmdHis', $value );

					// Participant API passes a different format, so test for that.
					if ( $datetime === false ) {
						$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
					}

					// Convert to ACF format.
					$value = $datetime->format( 'Y-m-d H:i:s' );

				}

				break;

			// Used by "Contact Existing/New".
			case 'civicrm_contact_existing_new':

				// Convert the value to the Field's array format.
				$value = $this->acf_loader->acf->field_type->contact_group->prepare_input( $value );

				break;

			// Used by "Event Group".
			case 'civicrm_event_group':

				// Convert the value to the Field's array format.
				$value = $this->acf_loader->acf->field_type->event_group->prepare_input( $value );

				break;

		}

		// TODO: Filter here?

		// --<
		return $value;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the "select" options for a given CiviCRM Participant Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Participant Field.
	 * @return array $options The array of Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// We only have a few to account for.

		// Status ID.
		if ( $name == 'status_id' ) {
			$statuses = $this->statuses_get();
			if ( ! empty( $statuses ) ) {
				$options = [];
				foreach ( $statuses as $status ) {
					$options[ $status['id'] ] = $status['label'];
				}
			}
		}

		// Participant Role ID.
		if ( $name == 'role_id' ) {
			$option_group_id = $this->civicrm->participant_role->option_group_id_get();
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group_id );
			}
		}

		// --<
		return $options;

	}

	// -------------------------------------------------------------------------

	/**
	 * Geta the data for all active Participant Statuses.
	 *
	 * @since 0.5
	 *
	 * @return array $options The array of Participant Status data.
	 */
	public function statuses_get() {

		// Init return.
		$options = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $options;
		}

		// Define query params.
		$params = [
			'version' => 3,
			'is_active' => 1,
			'options' => [
				'limit' => 0,
				'sort' => 'weight ASC',
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'ParticipantStatusType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $options;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $options;
		}

		// We want the result set.
		$options = $result['values'];

		// --<
		return $options;

	}

	/**
	 * Gets the data for a given Participant Status.
	 *
	 * @since 0.5
	 *
	 * @param integer $status_id The numeric ID of the Participant Status.
	 * @return array $options The array of Participant Status data.
	 */
	public function status_get_by_id( $status_id ) {

		// Init return.
		$options = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $options;
		}

		// Define query params.
		$params = [
			'version' => 3,
			'id' => $status_id,
			'options' => [
				'limit' => 0,
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'ParticipantStatusType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $options;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $options;
		}

		// The result set should contain only one item.
		$options = array_pop( $result['values'] );

		// --<
		return $options;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Participant Fields for an ACF Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $participant_fields The array of Participant Fields.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$participant_fields = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $participant_fields;
		}

		// Bail if this is not a Participant Field Group.
		$is_participant_field_group = $this->civicrm->participant->is_participant_field_group( $field_group );
		if ( $is_participant_field_group === false ) {
			return $participant_fields;
		}

		// TODO: Do we need this loop?

		// Loop through the Post Types.
		foreach ( $is_participant_field_group as $post_type_name ) {

			// Get public Fields of this type.
			$participant_fields_for_type = $this->data_get( $field['type'], 'public' );

			// Merge with return array.
			$participant_fields = array_merge( $participant_fields, $participant_fields_for_type );

		}

		// --<
		return $participant_fields;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Participant Field options for a given Field ID.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Field.
	 * @return array $field The array of Field data.
	 */
	public function get_by_name( $name ) {

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
			'action' => 'get',
		];

		// Call the API.
		$result = civicrm_api( 'Participant', 'getfield', $params );

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
	 * Get the core Fields for a CiviCRM Participant.
	 *
	 * @since 0.5
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

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Participant', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Participant Fields array.
				$public_fields = [];
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->participant_fields ) ) {
						$public_fields[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				foreach ( $public_fields as $key => $value ) {
					if ( is_array( $this->participant_fields[ $value['name'] ] ) ) {
						if ( in_array( $field_type, $this->participant_fields[ $value['name'] ] ) ) {
							$fields[] = $value;
						}
					} else {
						if ( $field_type == $this->participant_fields[ $value['name'] ] ) {
							$fields[] = $value;
						}
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
	 * Get the core Fields for CiviCRM Participants.
	 *
	 * @since 0.5
	 *
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function data_get_filtered( $filter = 'none' ) {

		// Only do this once per filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ] ) ) {
			return $pseudocache[ $filter ];
		}

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Participant', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Participant Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->participant_fields ) ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ] ) ) {
			$pseudocache[ $filter ] = $fields;
		}

		// --<
		return $fields;

	}

	/**
	 * Get the public Fields for CiviCRM Participants.
	 *
	 * @since 0.5
	 *
	 * @return array $public_fields The array of CiviCRM Fields.
	 */
	public function get_public_fields() {

		// Init return.
		$public_fields = [];

		// Get the public Fields for CiviCRM Participants.
		$public_fields = $this->data_get_filtered( 'public' );

		// --<
		return $public_fields;

	}

	/**
	 * Get the Fields for an ACF Field and mapped to a CiviCRM Participant.
	 *
	 * Unused.
	 *
	 * @since 0.5
	 *
	 * @param string $type The type of ACF Field.
	 * @return array $fields The array of Field names.
	 */
	public function get_by_acf_type( $type = '' ) {

		// Init return.
		$participant_fields = [];

		// Skip all but those mapped to the type of ACF Field.
		foreach ( $this->participant_fields as $key => $value ) {
			if ( $type == $value ) {
				$participant_fields[ $key ] = $value;
			}
		}

		// --<
		return $participant_fields;

	}

	/**
	 * Get the ACF Field Type for a Participant Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Participant Field.
	 * @return array|string|bool $type The ACF Field Type (or array of Field Types).
	 */
	public function get_acf_type( $name = '' ) {

		// Init return.
		$type = false;

		// If the key exists, return the value - which is the ACF Type.
		if ( array_key_exists( $name, $this->participant_fields ) ) {
			$type = $this->participant_fields[ $name ];
		}

		// --<
		return $type;

	}

	// -------------------------------------------------------------------------

	/**
	 * Modify the Settings of an ACF "Select" Field.
	 *
	 * @since 0.5
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

		// Get the mapped Participant Field name if present.
		$participant_field_name = $this->civicrm->participant->participant_field_name_get( $field );
		if ( $participant_field_name === false ) {
			return $field;
		}

		// Get keyed array of settings.
		$field['choices'] = $this->options_get( $participant_field_name );

		// --<
		return $field;

	}

	/**
	 * Modify the Settings of an ACF "Date Time Picker" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function date_time_picker_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'date_time_picker' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Participant Field name if present.
		$participant_field_name = $this->civicrm->participant->participant_field_name_get( $field );
		if ( $participant_field_name === false ) {
			return $field;
		}

		/*
		// Try and get CiviCRM format.
		//$civicrm_format = $this->date_time_format_get( $participant_field_name );
		*/

		// Set just the "Display Format" attribute.
		$field['display_format'] = 'Y-m-d H:i:s';

		// --<
		return $field;

	}

	/**
	 * Get the CiviCRM "DateTime format" for a given CiviCRM Participant Field.
	 *
	 * There is such a horrible mismatch between CiviCRM datetime formats and
	 * PHP datetime formats that I've given up trying to translate them.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Participant Field.
	 * @return string $format The DateTime format.
	 */
	public function date_time_format_get( $name ) {

		// Init return.
		$format = '';

		// We only have a few to account for.
		$date_fields = [ 'register_date' ];

		// If it's one of our Fields.
		if ( in_array( $name, $date_fields ) ) {

			// Get the "Participant Date Time" preference.
			$format = CRM_Utils_Date::getDateFormat( 'activityDateTime' );

			// Override if we get the default.
			$config = CRM_Core_Config::singleton();
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			if ( $config->dateInputFormat == $format ) {
				$format = '';
			}

		}

		// If it's empty, fall back to a sensible CiviCRM-formatted setting.
		if ( empty( $format ) ) {
			$format = 'yy-mm-dd';
		}

		// --<
		return $format;

	}

	// -------------------------------------------------------------------------

	/**
	 * Maybe sync the Participant "Date" Fields to the ACF Fields on a WordPress Post.
	 *
	 * Participant Fields to maintain sync with:
	 *
	 * * The ACF "Registered Date" Field
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function maybe_sync_fields( $args ) {

		// Bail if there's no Participant ID.
		if ( empty( $args['participant_id'] ) ) {
			return;
		}

		// Get the full Participant data.
		$participant = $this->civicrm->participant->get_by_id( $args['participant_id'] );

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $args['post']->ID ) ) {
			return;
		}

		// Let's make an array of params.
		$params = [
			'op' => 'edit',
			'objectName' => 'Participant',
			'objectId' => $args['participant_id'],
			'objectRef' => (object) $participant,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_remove();

		// Update the Post.
		$this->acf_loader->post->participant_edited( $params );

		// Reinstate WordPress callbacks.
		$this->acf_loader->mapper->hooks_wordpress_add();

	}

}
