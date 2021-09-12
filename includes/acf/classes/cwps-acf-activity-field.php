<?php
/**
 * CiviCRM Activity Field Class.
 *
 * Handles CiviCRM Activity Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Activity Field Class.
 *
 * A class that encapsulates CiviCRM Activity Field functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Activity_Field {

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
	 * Built-in Activity Fields.
	 *
	 * These are mapped to their corresponding ACF Field types.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $activity_fields The public Activity Fields.
	 */
	public $activity_fields = [
		'created_date' => 'date_time_picker',
		'modified_date' => 'date_time_picker',
		'activity_date_time' => 'date_time_picker',
		'status_id' => 'select',
		'priority_id' => 'select',
		'engagement_level' => 'select',
		'duration' => 'text',
		'location' => 'text',
		'source_contact_id' => 'civicrm_activity_creator',
		'target_contact_id' => 'civicrm_activity_target',
		'assignee_contact_id' => 'civicrm_activity_assignee',
	];

	/**
	 * Case Fields to add for the Bypass Location Rule.
	 *
	 * These are not mapped for Past Type Sync, but need to be added for the
	 * Bypass Location Rule.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $bypass_fields The Case Fields to add for the Bypass Location Rule.
	 */
	public $bypass_fields = [
		'subject' => 'text',
		'details' => 'wysiwyg',
	];



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

		// Intercept Post created, updated (or synced) from Activity events.
		add_action( 'cwps/acf/post/activity/created', [ $this, 'post_edited' ], 10 );
		add_action( 'cwps/acf/post/activity/edited', [ $this, 'post_edited' ], 10 );
		add_action( 'cwps/acf/post/activity/sync', [ $this, 'activity_sync_to_post' ], 10 );

		// Maybe sync the various Activity "Date" Fields to ACF Fields attached to the WordPress Post.
		add_action( 'cwps/acf/activity/acf_fields_saved', [ $this, 'maybe_sync_fields' ], 10 );

		// Some Activity "Text" Fields need their own validation.
		add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

		// Listen for queries from our ACF Field class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 20, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'date_time_picker_settings_modify' ], 20, 2 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Validate the content of a Field.
	 *
	 * Some Activity Fields require validation.
	 *
	 * @since 0.4
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

		// Get the mapped Activity Field name if present.
		$activity_field_name = $this->acf_loader->civicrm->activity->activity_field_name_get( $field );
		if ( $activity_field_name === false ) {
			return $valid;
		}

		// Validate depending on the field name.
		switch ( $activity_field_name ) {

			case 'duration' :
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
	 * Intercept when a Post has been updated from an Activity via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Activity Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Activity and WordPress Post params.
	 */
	public function activity_sync_to_post( $args ) {

		// Re-use Post Edited method.
		$this->post_edited( $args );

	}



	/**
	 * Intercept when a Post has been updated from an Activity via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Activity Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Activity and WordPress Post params.
	 */
	public function post_edited( $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Activity Fields.
		if ( empty( $acf_fields['activity'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['activity'] as $selector => $activity_field ) {

			// Skip if it's not a public Activity Field.
			if ( ! array_key_exists( $activity_field, $this->activity_fields ) ) {
				continue;
			}

			// Does the mapped Activity Field exist?
			if ( isset( $args['objectRef']->$activity_field ) ) {

				// Modify value for ACF prior to update.
				$value = $this->value_get_for_acf(
					$args['objectRef']->$activity_field,
					$activity_field,
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
	 * Get the value of an Activity Field, formatted for ACF.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The Activity Field value.
	 * @param array $name The Activity Field name.
	 * @param string $selector The ACF Field selector.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return mixed $value The formatted field value.
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

		// Get the ACF type for this Activity Field.
		$type = $this->get_acf_type( $name );

		// Convert CiviCRM value to ACF value by Activity Field.
		switch( $type ) {

			// Unused at present.
			case 'select' :
			case 'checkbox' :

				// Convert if the value has the special CiviCRM array-like format.
				if ( false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
					$value = CRM_Utils_Array::explodePadded( $value );
				}

				break;

			// Used by "Birth Date" and "Deceased Date".
			case 'date_picker' :
			case 'date_time_picker' :

				// Get field setting.
				$acf_setting = get_field_object( $selector, $post_id );

				// Date Picker test.
				if ( $acf_setting['type'] == 'date_picker' ) {

					// Activity edit passes a Y-m-d format, so test for that.
					$datetime = DateTime::createFromFormat( 'Y-m-d', $value );

					// Activity create passes a different format, so test for that.
					if ( $datetime === false ) {
						$datetime = DateTime::createFromFormat( 'YmdHis', $value );
					}

					// Convert to ACF format.
					$value = $datetime->format( 'Ymd' );

				// Date & Time Picker test.
				} elseif ( $acf_setting['type'] == 'date_time_picker' ) {

					// Activity edit passes a YmdHis format, so test for that.
					$datetime = DateTime::createFromFormat( 'YmdHis', $value );

					// Activity API passes a different format, so test for that.
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
	 * Get the "select" options for a given CiviCRM Activity Field.
	 *
	 * @since 0.4
	 *
	 * @param string $name The name of the Activity Field.
	 * @return array $options The array of field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// We only have a few to account for.

		// Status ID.
		if ( $name == 'status_id' ) {
			$option_group = $this->civicrm->option_group_get( 'activity_status' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// Priority ID.
		if ( $name == 'priority_id' ) {
			$option_group = $this->civicrm->option_group_get( 'priority' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// Engagement Level.
		if ( $name == 'engagement_level' ) {
			$option_group = $this->civicrm->option_group_get( 'engagement_index' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// --<
		return $options;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Activity Fields for an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $activity_fields The array of Activity Fields.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$activity_fields = [];

		// Get field group for this field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no field group.
		if ( empty( $field_group ) ) {
			return $activity_fields;
		}

		// Bail if this is not an Activity Field Group.
		$is_activity_field_group = $this->civicrm->activity->is_activity_field_group( $field_group );
		if ( $is_activity_field_group === false ) {
			return $activity_fields;
		}

		// TODO: Do we need this loop?

		// Loop through the Post Types.
		foreach ( $is_activity_field_group as $post_type_name ) {

			// Get public fields of this type.
			$activity_fields_for_type = $this->data_get( $field['type'], 'public' );

			// Merge with return array.
			$activity_fields = array_merge( $activity_fields, $activity_fields_for_type );

		}

		// --<
		return $activity_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Activity Field options for a given Field ID.
	 *
	 * @since 0.4
	 *
	 * @param string $name The name of the field.
	 * @return array $field The array of field data.
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
		$result = civicrm_api( 'Activity', 'getfield', $params );

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
	 * Get the core Fields for a CiviCRM Activity Type.
	 *
	 * @since 0.4
	 *
	 * @param string $field_type The type of ACF Field.
	 * @param string $filter The token by which to filter the array of fields.
	 * @return array $fields The array of field names.
	 */
	public function data_get( $field_type = '', $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[$filter][$field_type] ) ) {
			return $pseudocache[$filter][$field_type];
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
		$result = civicrm_api( 'Activity', 'getfields', $params );

		// Override return if we get some.
		if (
			$result['is_error'] == 0 AND
			isset( $result['values'] ) AND
			count( $result['values'] ) > 0
		) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Activity Fields array.
				$public_fields = [];
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->activity_fields ) ) {
						$public_fields[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				foreach ( $public_fields as $key => $value ) {
					if ( $field_type == $this->activity_fields[$value['name']] ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$filter][$field_type] ) ) {
			$pseudocache[$filter][$field_type] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the core Fields for all CiviCRM Activity Types.
	 *
	 * @since 0.4
	 *
	 * @param string $filter The token by which to filter the array of fields.
	 * @return array $fields The array of field names.
	 */
	public function data_get_filtered( $filter = 'none' ) {

		// Only do this once per filter.
		static $pseudocache;
		if ( isset( $pseudocache[$filter] ) ) {
			return $pseudocache[$filter];
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
		$result = civicrm_api( 'Activity', 'getfields', $params );

		// Override return if we get some.
		if (
			$result['is_error'] == 0 AND
			isset( $result['values'] ) AND
			count( $result['values'] ) > 0
		) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Activity Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->activity_fields ) ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$filter] ) ) {
			$pseudocache[$filter] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the public Fields for all CiviCRM Activity Types.
	 *
	 * @since 0.5
	 *
	 * @return array $public_fields The array of CiviCRM Fields.
	 */
	public function get_public_fields() {

		// Init return.
		$public_fields = [];

		// Get the public Fields for all CiviCRM Activity Types.
		$public_fields = $this->data_get_filtered( 'public' );

		// --<
		return $public_fields;

	}



	/**
	 * Get the Fields for an ACF Field and mapped to a CiviCRM Activity Type.
	 *
	 * @since 0.4
	 *
	 * @param string $type The type of ACF Field.
	 * @return array $fields The array of field names.
	 */
	public function get_by_acf_type( $type = '' ) {

		// Init return.
		$activity_fields = [];

		// Skip all but those mapped to the type of ACF Field.
		foreach ( $this->activity_fields as $key => $value ) {
			if ( $type == $value ) {
				$activity_fields[$key] = $value;
			}
		}

		// --<
		return $activity_fields;

	}



	/**
	 * Get the ACF Field Type for an Activity Field.
	 *
	 * @since 0.4
	 *
	 * @param string $name The name of the Activity Field.
	 * @return array $fields The array of field names.
	 */
	public function get_acf_type( $name = '' ) {

		// Init return.
		$type = false;

		// If the key exists, return the value - which is the ACF Type.
		if ( array_key_exists( $name, $this->activity_fields ) ) {
			$type = $this->activity_fields[$name];
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
		$key = $this->acf_loader->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[$key] ) ) {
			return $field;
		}

		// Get the mapped Activity Field name if present.
		$activity_field_name = $this->civicrm->activity->activity_field_name_get( $field );
		if ( $activity_field_name === false ) {
			return $field;
		}

		// Get keyed array of settings.
		$field['choices'] = $this->options_get( $activity_field_name );

		// These are all optional.
		$field['allow_null'] = 1;

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
		$key = $this->acf_loader->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[$key] ) ) {
			return $field;
		}

		// Get the mapped Activity Field name if present.
		$activity_field_name = $this->acf_loader->civicrm->activity->activity_field_name_get( $field );
		if ( $activity_field_name === false ) {
			return $field;
		}

		// Try and get CiviCRM format.
		//$civicrm_format = $this->date_time_format_get( $activity_field_name );

		// Set just the "Display Format" attribute.
		$field['display_format'] = 'Y-m-d H:i:s';

		// --<
		return $field;

	}



	/**
	 * Get the CiviCRM "DateTime format" for a given CiviCRM Activity Field.
	 *
	 * There is such a horrible mismatch between CiviCRM datetime formats and
	 * PHP datetime formats that I've given up trying to translate them.
	 *
	 * @since 0.4
	 *
	 * @param string $name The name of the Activity Field.
	 * @return string $format The DateTime format.
	 */
	public function date_time_format_get( $name ) {

		// Init return.
		$format = '';

		// We only have a few to account for.
		$date_fields = [ 'created_date', 'modified_date', 'activity_date_time' ];

		// If it's one of our fields.
		if ( in_array( $name, $date_fields ) ) {

			// Get the "Activity Date Time" preference.
			$format = CRM_Utils_Date::getDateFormat( 'activityDateTime' );

			// Override if we get the default.
			$config = CRM_Core_Config::singleton();
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
	 * Maybe sync the Activity "Date" Fields to the ACF Fields on a WordPress Post.
	 *
	 * Activity Fields to maintain sync with:
	 *
	 * - The ACF "Activity Date Time" Field
	 * - The ACF "Created Date" Field
	 * - The ACF "Modified Date" Field
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function maybe_sync_fields( $args ) {

		// Bail if there's no Activity ID.
		if ( empty( $args['activity_id'] ) ) {
			return;
		}

		// Get the full Activity data.
		$activity = $this->acf_loader->civicrm->activity->get_by_id( $args['activity_id'] );

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $args['post']->ID ) ) {
			return;
		}

		// Let's make an array of params.
		$params = [
			'op' => 'edit',
			'objectName' => 'Activity',
			'objectId' => $args['activity_id'],
			'objectRef' => (object) $activity,
		];

		// Remove WordPress callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_remove();

		// Update the Post.
		$this->acf_loader->post->activity_edited( $params );

		// Reinstate WordPress callbacks.
		$this->acf_loader->mapper->hooks_wordpress_add();

	}



} // Class ends.



