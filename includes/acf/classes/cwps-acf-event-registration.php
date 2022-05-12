<?php
/**
 * CiviCRM Event Registration Class.
 *
 * Handles CiviCRM Event Registration functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Event Registration Class.
 *
 * A class that encapsulates CiviCRM Event Registration functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Event_Registration {

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
		'is_online_registration' => 'true_false',
		'registration_link_text' => 'text',
		'registration_start_date' => 'date_time_picker',
		'registration_end_date' => 'date_time_picker',
		'is_multiple_registrations' => 'true_false',
		'max_additional_participants' => 'select',
		'allow_same_participant_emails' => 'true_false',
		'dedupe_rule_group_id' => 'select',
		'requires_approval' => 'true_false', // Possibly disabled.
		'approval_req_text' => 'textarea', // Possibly disabled.
		'expiration_time' => 'number',
		'allow_selfcancelxfer' => 'true_false',
		'selfcancelxfer_time' => 'number',
	];

	/**
	 * Registration Screen Fields.
	 *
	 * These Fields are attached to the Event Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $registration_screen_fields The Registration Screen Fields.
	 */
	public $registration_screen_fields = [
		'intro_text' => 'wysiwyg',
		'footer_text' => 'wysiwyg',
	];

	/**
	 * Registration Screen Profile Fields.
	 *
	 * These Fields reference the UFGroup Entity.
	 *
	 * We do not support "Additional Participants" yet.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $registration_screen_profiles The Registration Screen Profile Fields.
	 */
	public $registration_screen_profiles = [
		'custom_pre_id' => 'select',
		'custom_post_id' => 'select',
		//'additional_profile_pre_id' => 'select',
		//'additional_profile_post_id' => 'select',
	];

	/**
	 * Confirmation Screen Fields.
	 *
	 * These Fields are attached to the Event Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $confirm_screen_fields The Confirmation Screen Fields.
	 */
	public $confirm_screen_fields = [
		'is_confirm_enabled' => 'true_false',
		'confirm_title' => 'text',
		'confirm_text' => 'wysiwyg',
		'confirm_footer_text' => 'wysiwyg',
	];

	/**
	 * Thank You Screen Fields.
	 *
	 * These Fields are attached to the Event Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $confirm_screen_fields The Thank You Screen Fields.
	 */
	public $thankyou_screen_fields = [
		'thankyou_title' => 'text',
		'thankyou_text' => 'wysiwyg',
		'thankyou_footer_text' => 'wysiwyg',
	];

	/**
	 * Confirmation Email Fields.
	 *
	 * These Fields are attached to the Event Entity.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $registration_fields The Confirmation Email Fields.
	 */
	public $confirmation_email_fields = [
		'is_email_confirm' => 'true_false',
		'confirm_email_text' => 'textarea',
		'confirm_from_name' => 'text',
		'confirm_from_email' => 'email',
		'cc_confirm' => 'email',
		'bcc_confirm' => 'email',
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

		// Some "Text" Fields need their own validation.
		//add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

		// Listen for queries from our ACF Field class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 20, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'text_settings_modify' ], 20, 2 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Getter for the public Event Registration Fields.
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
		$done = $this->settings_fields_get();
		$done += $this->registration_screen_fields;
		$done += $this->registration_screen_profiles;
		$done += $this->confirm_screen_fields;
		$done += $this->thankyou_screen_fields;
		$done += $this->confirmation_email_fields;

		// --<
		return $done;

	}



	/**
	 * Getter for the Settings Fields.
	 *
	 * Removes certain Fields when Participant Statuses are not active.
	 *
	 * @since 0.5.4
	 */
	public function settings_fields_get() {

		// Only do this once.
		static $done;
		if ( isset( $done ) ) {
			return $done;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $this->settings_fields;
		}

		// Use CiviCRM logic to filter out some Fields.
		$participant_statuses = CRM_Event_PseudoConstant::participantStatus();

		// Needs Participant Statuses 8, 9 and 11 to be enabled.
		if (
			in_array( 'Awaiting approval', $participant_statuses ) &&
			in_array( 'Pending from approval', $participant_statuses ) &&
			in_array( 'Rejected', $participant_statuses )
		) {

			// Build array of all Fields.
			$done = $this->settings_fields;

		} else {

			// Build array of filtered Fields.
			$done = $this->settings_fields;
			unset( $done['requires_approval'] );
			unset( $done['approval_req_text'] );

		}

		// --<
		return $done;

	}



	// -------------------------------------------------------------------------



	/**
	 * Validate the content of a Field.
	 *
	 * Some Event Registration Fields require validation.
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
	 * Get the value of an Event Registration Field, formatted for ACF.
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
	 * Get the "select" options for a given CiviCRM Event Registration Field.
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

		// Dedupe Rule.
		if ( $name == 'dedupe_rule_group_id' ) {
			$options = $this->civicrm->contact->dedupe_rules_get( 'Individual' );
		}

		// Max Additional Participants.
		if ( $name == 'max_additional_participants' ) {
			$options = [ 1, 2, 3, 4, 5, 6, 7, 8, 9 ];
		}

		// Custom Profile Fields.
		if ( array_key_exists( $name, $this->registration_screen_profiles ) ) {
			$options = $this->profiles_options_get();
		}

		// --<
		return $options;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Event Registration Fields for an ACF Field.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $event_fields The array of Fields.
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
	 * Get the Event Registration Field options for a given Field ID.
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
	 * Get the Event Registration Fields for an ACF Field Type.
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

			// Check public filter.
			if ( $filter == 'public' ) {

				// Get all public Fields.
				$public_fields = $this->public_fields_get();

				// Skip all but those defined in our public Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $public_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				$fields = [];
				foreach ( $filtered as $key => $value ) {
					if ( $field_type == $public_fields[ $value['name'] ] ) {
						$fields[] = $value;
					}
				}

			// Check "settings" filter.
			} elseif ( $filter == 'settings' ) {

				// Get all settings Fields.
				$settings_fields = $this->settings_fields_get();

				// Skip all but those defined in our Settings Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $settings_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				$fields = [];
				foreach ( $filtered as $key => $value ) {
					if ( $field_type == $settings_fields[ $value['name'] ] ) {
						$fields[] = $value;
					}
				}

			// Check "register" filter.
			} elseif ( $filter == 'register' ) {

				// Skip all but those defined in our Registration Screen Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->registration_screen_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				$fields = [];
				foreach ( $filtered as $key => $value ) {
					if ( $field_type == $this->registration_screen_fields[ $value['name'] ] ) {
						$fields[] = $value;
					}
				}

			// Check "confirm" filter.
			} elseif ( $filter == 'confirm' ) {

				// Skip all but those defined in our Comfirmation Screen Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->confirm_screen_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				$fields = [];
				foreach ( $filtered as $key => $value ) {
					if ( $field_type == $this->confirm_screen_fields[ $value['name'] ] ) {
						$fields[] = $value;
					}
				}

			// Check "thankyou" filter.
			} elseif ( $filter == 'thankyou' ) {

				// Skip all but those defined in our Thank You Screen Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->thankyou_screen_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				$fields = [];
				foreach ( $filtered as $key => $value ) {
					if ( $field_type == $this->thankyou_screen_fields[ $value['name'] ] ) {
						$fields[] = $value;
					}
				}

			// Check "email" filter.
			} elseif ( $filter == 'email' ) {

				// Skip all but those defined in our Thank You Screen Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->confirmation_email_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				$fields = [];
				foreach ( $filtered as $key => $value ) {
					if ( $field_type == $this->confirmation_email_fields[ $value['name'] ] ) {
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
	 * Get the Event Registration Fields for a given filter and action.
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

				// Skip all but those defined in our public Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->public_fields_get() ) ) {
						$filtered[] = $value;
					}
				}

				// Maybe order them by our public Fields array.
				$fields = [];
				if ( ! empty( $filtered ) ) {
					foreach ( $this->public_fields_get() as $key => $field_type ) {
						foreach ( $filtered as $value ) {
							if ( $value['name'] === $key ) {
								$fields[] = $value;
								break;
							}
						}
					}
				}

			// Check "settings" filter.
			} elseif ( $filter == 'settings' ) {

				// Skip all but those defined in our Settings Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->settings_fields_get() ) ) {
						$filtered[] = $value;
					}
				}

				// Maybe order them by our Settings Fields array.
				$fields = [];
				if ( ! empty( $filtered ) ) {
					foreach ( $this->settings_fields_get() as $key => $field_type ) {
						foreach ( $filtered as $value ) {
							if ( $value['name'] === $key ) {
								$fields[] = $value;
								break;
							}
						}
					}
				}

			// Check "register" filter.
			} elseif ( $filter == 'register' ) {

				// Skip all but those defined in our Registration Screen Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->registration_screen_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Maybe order them by our Registration Screen Fields array.
				$fields = [];
				if ( ! empty( $filtered ) ) {
					foreach ( $this->registration_screen_fields as $key => $field_type ) {
						foreach ( $filtered as $value ) {
							if ( $value['name'] === $key ) {
								$fields[] = $value;
								break;
							}
						}
					}
				}

			// Check "confirm" filter.
			} elseif ( $filter == 'confirm' ) {

				// Skip all but those defined in our Confirmation Screen Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->confirm_screen_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Maybe order them by our Confirmation Screen Fields array.
				$fields = [];
				if ( ! empty( $filtered ) ) {
					foreach ( $this->confirm_screen_fields as $key => $field_type ) {
						foreach ( $filtered as $value ) {
							if ( $value['name'] === $key ) {
								$fields[] = $value;
								break;
							}
						}
					}
				}

			// Check "thankyou" filter.
			} elseif ( $filter == 'thankyou' ) {

				// Skip all but those defined in our Thank You Screen Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->thankyou_screen_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Maybe order them by our Thank You Screen Fields array.
				$fields = [];
				if ( ! empty( $filtered ) ) {
					foreach ( $this->thankyou_screen_fields as $key => $field_type ) {
						foreach ( $filtered as $value ) {
							if ( $value['name'] === $key ) {
								$fields[] = $value;
								break;
							}
						}
					}
				}

			// Check "email" filter.
			} elseif ( $filter == 'email' ) {

				// Skip all but those defined in our Confirmation Email Fields array.
				$filtered = [];
				foreach ( $fields as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->confirmation_email_fields ) ) {
						$filtered[] = $value;
					}
				}

				// Maybe order them by our Confirmation Email Fields array.
				$fields = [];
				if ( ! empty( $filtered ) ) {
					foreach ( $this->confirmation_email_fields as $key => $field_type ) {
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
	 * Get the public Event Registration Fields for a given action.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of CiviCRM Fields.
	 */
	public function get_public_fields( $action = 'create' ) {

		// Get the public Fields.
		$fields = $this->data_get_filtered( 'public', $action );

		// --<
		return $fields;

	}



	/**
	 * Get the public Event Registration Settings Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of CiviCRM Fields.
	 */
	public function get_settings_fields( $action = 'create' ) {

		// Get the Settings Fields.
		$fields = $this->data_get_filtered( 'settings', $action );

		// --<
		return $fields;

	}



	/**
	 * Get the Event Registration Screen Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of CiviCRM Fields.
	 */
	public function get_register_screen_fields( $action = 'create' ) {

		// Get the Registration Screen Fields.
		$fields = $this->data_get_filtered( 'register', $action );

		// --<
		return $fields;

	}



	/**
	 * Get the Event Registration Confirmation Screen Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of CiviCRM Fields.
	 */
	public function get_confirm_screen_fields( $action = 'create' ) {

		// Get the Confirmation Screen Fields.
		$fields = $this->data_get_filtered( 'confirm', $action );

		// --<
		return $fields;

	}



	/**
	 * Get the Event Registration Thank You Screen Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of CiviCRM Fields.
	 */
	public function get_thankyou_screen_fields( $action = 'create' ) {

		// Get the Thank You Screen Fields.
		$fields = $this->data_get_filtered( 'thankyou', $action );

		// --<
		return $fields;

	}



	/**
	 * Get the Event Registration Confirmation Email Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param string $action The name of the API action, e.g. 'create'.
	 * @return array $fields The array of CiviCRM Fields.
	 */
	public function get_confirmation_email_fields( $action = 'create' ) {

		// Get the Confirmation Email Fields.
		$fields = $this->data_get_filtered( 'email', $action );

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
	 * Get the ACF Field Type for an Event Registration Field.
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

		// Set a default for "Dedupe Rule".
		if ( $event_field_name == 'dedupe_rule_group_id' ) {
			$field['choices'] = [ '' => __( 'None', 'civicrm-wp-profile-sync' ) ] + $field['choices'];
			$field['default_value'] = '';
		}

		// Set a default for "Profile Fields".
		if ( array_key_exists( $event_field_name, $this->registration_screen_profiles ) ) {
			$field['choices'] = [ '' => __( 'None', 'civicrm-wp-profile-sync' ) ] + $field['choices'];
			$field['default_value'] = '';
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

		// Get Event Field data.
		$field_data = $this->civicrm->event_field->get_by_name( $event_field_name );

		// Set the "maxlength" attribute.
		if ( ! empty( $field_data['maxlength'] ) ) {
			$field['maxlength'] = $field_data['maxlength'];
		}

		// --<
		return $field;

	}



	// -------------------------------------------------------------------------



	/**
	 * Checks if a CiviCRM Event has a Registration Profile.
	 *
	 * We need to specify the "module" because CiviCRM Event can specify an
	 * additional "module" called "CiviEvent_Additional" which refers to Profiles
	 * used for Registrations for additional people. These are specified when
	 * "Register multiple participants" is enabled.
	 *
	 * @since 0.5.4
	 *
	 * @param array $event The array of CiviCRM Event data.
	 * @param string $position The position of the Profile. Pass "top", "bottom" or "any". Default "any".
	 * @return array|bool $profile The CiviCRM UFJoin data, or false otherwise.
	 */
	public function has_profile( $event, $position = 'any' ) {

		// Init return.
		$profile = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $profile;
		}

		// Define query params.
		$params = [
			'version' => 3,
			'entity_table' => 'civicrm_event',
			'module' => 'CiviEvent',
			'entity_id' => $event['id'],
		];

		// Maybe restrict to "top".
		if ( $position === 'top' ) {
			$params['weight'] = 1;
		}

		// Maybe restrict to "bottom".
		if ( $position === 'bottom' ) {
			$params['weight'] = 2;
		}

		// Call the API.
		$result = civicrm_api( 'UFJoin', 'get', $params );

		// Return early if we get an error.
		if ( ! empty( $result['is_error'] ) ) {
			return $profile;
		}

		// Return early if the Event has no profile(s).
		if ( empty( $result['values'] ) ) {
			return $profile;
		}

		// Grab the result set.
		$profile = $result['values'];

		// --<
		return $profile;

	}



	/**
	 * Creates a CiviCRM Event Registration Profile for a given Event.
	 *
	 * @since 0.5.4
	 *
	 * @param array $event The array of CiviCRM Event data.
	 * @param int $profile_id The numeric ID of the CiviCRM Profile.
	 * @param string $position The position of the Profile. Pass "top" or "bottom". Default "top".
	 * @return array|bool $profile CiviCRM Profiles array, or false on failure.
	 */
	public function profile_create( $event, $profile_id = '', $position = 'top' ) {

		// Init return.
		$profile = false;

		// Bail if the Event does not have Online Registration enabled.
		if ( empty( $event['is_online_registration'] ) ) {
			return $profile;
		}

		// Bail if there is no Profile ID.
		if ( empty( $profile_id ) ) {
			return $profile;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $profile;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'module' => 'CiviEvent',
			'entity_table' => 'civicrm_event',
			'entity_id' => $event['id'],
			'uf_group_id' => $profile_id,
			'is_active' => 1,
			'sequential' => 1,
		];

		// Default to "top" but maybe set to "bottom".
		$params['weight'] = 1;
		if ( $position !== 'top' ) {
			$params['weight'] = 2;
		}

		// Call the API.
		$result = civicrm_api( 'UFJoin', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'event' => $event,
				'result' => $result,
				'params' => $params,
				'backtrace' => $trace,
			], true ) );
			return $profile;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $profile;
		}

		// The result set should contain only one item.
		$profile = array_pop( $result['values'] );

		// --<
		return $profile;

	}



	/**
	 * Get all CiviCRM Event Registration Profiles.
	 *
	 * @since 0.5.4
	 *
	 * @return array|bool $result CiviCRM Profiles array, or empty on failure.
	 */
	public function profiles_get() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$profiles = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $profiles;
		}

		// Define params.
		$params = [
			'version' => 3,
		];

		// Get them via API.
		$result = civicrm_api( 'UFGroup', 'get', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'result' => $result,
				'params' => $params,
				'backtrace' => $trace,
			], true ) );
			return $profiles;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $profiles;
		}

		// The result set is what we want.
		$profiles = $result['values'];

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $profiles;
		}

		// --<
		return $profiles;

	}



	/**
	 * Get all CiviCRM Event Registration Profiles formatted for ACF.
	 *
	 * @since 0.5.4
	 *
	 * @return array $options The array of Profile options.
	 */
	public function profiles_options_get() {

		// Init return.
		$options = [];

		// Get all Profiles.
		$profiles = $this->profiles_get();
		if ( empty( $profiles ) ) {
			return $options;
		}

		// Build return array.
		foreach ( $profiles as $profile ) {
			$options[ (int) $profile['id'] ] = esc_html( $profile['title'] );
		}

		// --<
		return $options;

	}



	/**
	 * Getter for the Profile Fields.
	 *
	 * @since 0.5.4
	 */
	public function profile_fields_get() {

		// Return the Registration Screen Profile Fields.
		return $this->registration_screen_profiles;

	}



} // Class ends.



