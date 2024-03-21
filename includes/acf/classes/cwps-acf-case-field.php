<?php
/**
 * CiviCRM Case Field Class.
 *
 * Handles CiviCRM Case Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Case Field Class.
 *
 * A class that encapsulates CiviCRM Case Field functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Case_Field {

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
	 * Built-in Case Fields.
	 *
	 * These are mapped to their corresponding ACF Field types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $case_fields = [
		'id' => 'number',
		'details' => 'wysiwyg',
		'subject' => 'text',
		'start_date' => 'date_picker',
		'end_date' => 'date_picker',
		'created_date' => 'date_time_picker',
		'modified_date' => 'date_time_picker',
		'status_id' => 'select',
		'medium_id' => 'select',
	];

	/**
	 * Case Fields to add for the Bypass Location Rule.
	 *
	 * These are not mapped for Post Type Sync, but need to be added for the
	 * Bypass Location Rule.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $bypass_fields = [
		'subject' => 'text',
		'details' => 'wysiwyg',
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

		/*
		// Intercept Post created, updated (or synced) from Case events.
		add_action( 'cwps/acf/post/case/created', [ $this, 'post_edited' ], 10 );
		add_action( 'cwps/acf/post/case/edited', [ $this, 'post_edited' ], 10 );
		add_action( 'cwps/acf/post/case/sync', [ $this, 'case_sync_to_post' ], 10 );

		// Maybe sync the various Case "Date" Fields to ACF Fields attached to the WordPress Post.
		add_action( 'cwps/acf/case/acf_fields_saved', [ $this, 'maybe_sync_fields' ], 10 );
		*/

		// Some Case "Text" Fields need their own validation.
		add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

		// Listen for queries from our ACF Field class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 20, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'date_time_picker_settings_modify' ], 20, 2 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Validate the content of a Field.
	 *
	 * Some Case Fields require validation.
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

		// Get the mapped Case Field name if present.
		$case_field_name = $this->civicrm->case->case_field_name_get( $field );
		if ( $case_field_name === false ) {
			return $valid;
		}

		// Validate depending on the Field name.
		switch ( $case_field_name ) {

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
	 * Intercept when a Post has been updated from a Case via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Case Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Case and WordPress Post params.
	 */
	public function case_sync_to_post( $args ) {

		// Re-use Post Edited method.
		$this->post_edited( $args );

	}

	/**
	 * Intercept when a Post has been updated from a Case via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Case Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Case and WordPress Post params.
	 */
	public function post_edited( $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Case Fields.
		if ( empty( $acf_fields['case'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['case'] as $selector => $case_field ) {

			// Skip if it's not a public Case Field.
			if ( ! array_key_exists( $case_field, $this->case_fields ) ) {
				continue;
			}

			// Does the mapped Case Field exist?
			if ( isset( $args['objectRef']->$case_field ) ) {

				// Modify value for ACF prior to update.
				$value = $this->value_get_for_acf(
					$args['objectRef']->$case_field,
					$case_field,
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
	 * Get the value of a Case Field, formatted for ACF.
	 *
	 * @since 0.5
	 *
	 * @param mixed          $value The Case Field value.
	 * @param array          $name The Case Field name.
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

		// Get the ACF type for this Case Field.
		$type = $this->get_acf_type( $name );

		// Convert CiviCRM value to ACF value by Case Field.
		switch ( $type ) {

			// Unused at present.
			case 'select':
			case 'checkbox':

				// Convert if the value has the special CiviCRM array-like format.
				if ( is_string( $value ) && false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
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

					// Case edit passes a Y-m-d format, so test for that.
					$datetime = DateTime::createFromFormat( 'Y-m-d', $value );

					// Case create passes a different format, so test for that.
					if ( $datetime === false ) {
						$datetime = DateTime::createFromFormat( 'YmdHis', $value );
					}

					// Convert to ACF format.
					$value = $datetime->format( 'Ymd' );

				} elseif ( $acf_setting['type'] == 'date_time_picker' ) {

					// Date & Time Picker test.

					// Case edit passes a YmdHis format, so test for that.
					$datetime = DateTime::createFromFormat( 'YmdHis', $value );

					// Case API passes a different format, so test for that.
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
	 * Get the "select" options for a given CiviCRM Case Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Case Field.
	 * @return array $options The array of Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// We only have a few to account for.

		// Case Type ID.
		if ( $name == 'case_type_id' ) {
			$options = $this->civicrm->case_type->choices_get();
		}

		// Case Status ID.
		if ( $name == 'case_status_id' || $name == 'status_id' ) {
			$option_group = $this->plugin->civicrm->option_group_get( 'case_status' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// Medium ID.
		if ( $name == 'case_medium_id' || $name == 'medium_id' ) {
			$options = CRM_Case_PseudoConstant::encounterMedium();
		}

		// --<
		return $options;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Case Fields for an ACF Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $case_fields The array of Case Fields.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$case_fields = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $case_fields;
		}

		// Bail if this is not a Case Field Group.
		$is_case_field_group = $this->civicrm->case->is_case_field_group( $field_group );
		if ( $is_case_field_group === false ) {
			return $case_fields;
		}

		// TODO: Do we need this loop?

		// Loop through the Post Types.
		foreach ( $is_case_field_group as $post_type_name ) {

			// Get public Fields of this type.
			$case_fields_for_type = $this->data_get( $field['type'], 'public' );

			// Merge with return array.
			$case_fields = array_merge( $case_fields, $case_fields_for_type );

		}

		// --<
		return $case_fields;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Case Field options for a given Field ID.
	 *
	 * @since 0.5
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
			'action' => 'get',
		];

		// Add action.
		$params['action'] = $action;

		// Call the API.
		$result = civicrm_api( 'Case', 'getfield', $params );

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
	 * Get the core Fields for a CiviCRM Case Type.
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
			'api_action' => 'create',
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Case', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			if ( $filter == 'none' ) {

				// Grab all Fields.
				$fields = $result['values'];

			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Case Fields array.
				$public_fields = [];
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->case_fields ) ) {
						$public_fields[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				foreach ( $public_fields as $key => $value ) {
					if ( $field_type == $this->case_fields[ $value['name'] ] ) {
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
	 * Get the core Fields for all CiviCRM Case Types.
	 *
	 * @since 0.5
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

		// Maybe add action.
		if ( ! empty( $action ) ) {
			$params['api_action'] = $action;
		}

		// Call the API.
		$result = civicrm_api( 'Case', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			if ( $filter == 'none' ) {

				// Grab all Fields.
				$fields = $result['values'];

			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Case Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->case_fields ) ) {
						$fields[] = $value;
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
	 * Get the public Fields for all CiviCRM Case Types.
	 *
	 * @since 0.5
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $public_fields The array of CiviCRM Fields.
	 */
	public function get_public_fields( $action ) {

		// Init return.
		$public_fields = [];

		// Get the public Fields for all CiviCRM Case Types.
		$public_fields = $this->data_get_filtered( 'public', $action );

		// --<
		return $public_fields;

	}

	/**
	 * Get the Fields for an ACF Field and mapped to a CiviCRM Case Type.
	 *
	 * @since 0.5
	 *
	 * @param string $type The type of ACF Field.
	 * @return array $fields The array of Field names.
	 */
	public function get_by_acf_type( $type = '' ) {

		// Init return.
		$case_fields = [];

		// Skip all but those mapped to the type of ACF Field.
		foreach ( $this->case_fields as $key => $value ) {
			if ( $type == $value ) {
				$case_fields[ $key ] = $value;
			}
		}

		// --<
		return $case_fields;

	}

	/**
	 * Get the ACF Field Type for a Case Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Case Field.
	 * @return array $fields The array of Field names.
	 */
	public function get_acf_type( $name = '' ) {

		// Init return.
		$type = false;

		// If the key exists, return the value - which is the ACF Type.
		if ( array_key_exists( $name, $this->case_fields ) ) {
			$type = $this->case_fields[ $name ];
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

		// Get the mapped Case Field name if present.
		$case_field_name = $this->civicrm->case->case_field_name_get( $field );
		if ( $case_field_name === false ) {
			return $field;
		}

		// Get keyed array of settings.
		$field['choices'] = $this->options_get( $case_field_name );

		// Set a default for "Case Status".
		if ( $case_field_name == 'status_id' || $case_field_name == 'case_status_id' ) {
			$status_id_default = $this->civicrm->option_value_default_get( 'case_status' );
			if ( $status_id_default !== false ) {
				$field['default_value'] = $status_id_default;
			}
		}

		// Set a default for "Activity Medium".
		if ( $case_field_name == 'medium_id' || $case_field_name == 'case_medium_id' ) {
			$medium_id_default = $this->civicrm->option_value_default_get( 'encounter_medium' );
			if ( $medium_id_default !== false ) {
				$field['default_value'] = $medium_id_default;
			}
		}

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

		// Get the mapped Case Field name if present.
		$case_field_name = $this->civicrm->case->case_field_name_get( $field );
		if ( $case_field_name === false ) {
			return $field;
		}

		/*
		// Try and get CiviCRM format.
		$civicrm_format = $this->date_time_format_get( $case_field_name );
		*/

		// Set just the "Display Format" attribute.
		$field['display_format'] = 'Y-m-d H:i:s';

		// --<
		return $field;

	}

	/**
	 * Get the CiviCRM "DateTime format" for a given CiviCRM Case Field.
	 *
	 * There is such a horrible mismatch between CiviCRM datetime formats and
	 * PHP datetime formats that I've given up trying to translate them.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Case Field.
	 * @return string $format The DateTime format.
	 */
	public function date_time_format_get( $name ) {

		// Init return.
		$format = '';

		// We only have a few to account for.
		$date_fields = [ 'created_date', 'modified_date', 'case_date_time' ];

		// If it's one of our Fields.
		if ( in_array( $name, $date_fields ) ) {

			// Get the "Case Date Time" preference.
			$format = CRM_Utils_Date::getDateFormat( 'caseDateTime' );

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
	 * Maybe sync the Case "Date" Fields to the ACF Fields on a WordPress Post.
	 *
	 * Case Fields to maintain sync with:
	 *
	 * * The ACF "Case Date Time" Field
	 * * The ACF "Created Date" Field
	 * * The ACF "Modified Date" Field
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function maybe_sync_fields( $args ) {

		// Bail if there's no Case ID.
		if ( empty( $args['case_id'] ) ) {
			return;
		}

		// Get the full Case data.
		$case = $this->civicrm->case->get_by_id( $args['case_id'] );

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $args['post']->ID ) ) {
			return;
		}

		// Let's make an array of params.
		$params = [
			'op' => 'edit',
			'objectName' => 'Case',
			'objectId' => $args['case_id'],
			'objectRef' => (object) $case,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_remove();

		// Update the Post.
		$this->acf_loader->post->case_edited( $params );

		// Reinstate WordPress callbacks.
		$this->acf_loader->mapper->hooks_wordpress_add();

	}

}
