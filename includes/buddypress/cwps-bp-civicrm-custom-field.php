<?php
/**
 * BuddyPress CiviCRM Custom Field Class.
 *
 * Handles BuddyPress CiviCRM Custom Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress CiviCRM Custom Field Class.
 *
 * A class that encapsulates BuddyPress CiviCRM Custom Field functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_CiviCRM_Custom_Field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * BuddyPress Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $bp_loader The BuddyPress Loader object.
	 */
	public $bp_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * BuddyPress xProfile object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $xprofile The BuddyPress xProfile object.
	 */
	public $xprofile;

	/**
	 * CiviCRM Custom Field data types that can have "Select", "Radio" and
	 * "CheckBox" HTML subtypes.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $data_types The data types that can have "Select", "Radio"
	 *                        and "CheckBox" HTML subtypes.
	 */
	public $data_types = [
		'String',
		'Int',
		'Float',
		'Money',
		'Country',
		'StateProvince',
	];

	/**
	 * All CiviCRM Custom Fields that are of type "Select".
	 *
	 * @since 0.5
	 * @access public
	 * @var array $data_types The Custom Fields that are of type "Select".
	 */
	public $select_types = [
		'Select',
		'Multi-Select',
		'Autocomplete-Select',
		'Select Country',
		'Multi-Select Country',
		'Select State/Province',
		'Multi-Select State/Province',
	];

	/**
	 * "CiviCRM Field" Field value prefix in the BuddyPress Field data.
	 *
	 * This distinguishes Custom Fields from Contact Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $custom_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $custom_field_prefix = 'cwps_custom_';


	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $xprofile The BuddyPress xProfile object.
	 */
	public function __construct( $xprofile ) {

		// Store references to objects.
		$this->plugin = $xprofile->bp_loader->plugin;
		$this->bp_loader = $xprofile->bp_loader;
		$this->civicrm = $this->plugin->civicrm;
		$this->xprofile = $xprofile;

		// Init when the BuddyPress Field object is loaded.
		add_action( 'cwps/buddypress/field/loaded', [ $this, 'initialise' ] );

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

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/bp/field/query_setting_choices', [ $this, 'query_setting_choices' ], 100, 4 );

		// Filter the "CiviCRM Field" select to include only Custom Fields of the right type on the "Edit Field" sceen.
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'checkbox_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'select_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'multiselect_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'radio_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'date_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'text_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'textarea_settings_filter' ], 10, 3 );
		add_filter( 'cwps/bp/query_settings/custom_fields_filter', [ $this, 'url_settings_filter' ], 10, 3 );

		// Filter the xProfile Field options when saving on the "Edit Field" screen.
		add_filter( 'cwps/bp/field/query_options', [ $this, 'checkbox_settings_get' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'select_settings_get' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'multiselect_settings_get' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'radio_settings_get' ], 10, 3 );

		// TODO: Filter the xProfile Field settings when saving on the "Edit Field" screen.
		//add_filter( 'cwps/bp/field/query_options', [ $this, 'date_settings_get' ], 10, 3 );
		//add_filter( 'cwps/bp/field/query_options', [ $this, 'text_settings_get' ], 10, 3 );

		// Intercept when the content of a set of CiviCRM Custom Fields has been updated.
		add_action( 'cwps/mapper/custom_edited', [ $this, 'custom_edited' ], 10 );

		return;



		// Intercept Post synced from Contact events.
		add_action( 'cwps/bp/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Returns the Contact Field choices for a Setting Field from when found.
	 *
	 * Contact Fields only differ for the top level Contact Types.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The existing array of choices for the Setting Field.
	 * @param string $field_type The BuddyPress Field Type.
	 * @param string $entity_type The CiviCRM Entity Type.
	 * @param array $entity_type_data The array of Entity Type data.
	 * @return array $choices The modified array of choices for the Setting Field.
	 */
	public function query_setting_choices( $choices, $field_type, $entity_type, $entity_type_data ) {

		// Bail if there's something amiss.
		if ( empty( $entity_type ) ||  empty( $field_type ) ) {
			return $choices;
		}

		// Get Custom Fields for the "Contact" Entity Type.
		if ( $entity_type === 'Contact' ) {

			// We need Contact Type data.
			if ( empty( $entity_type_data ) ) {
				return $choices;
			}

			// Get the "name" of the Contact Type.
			$name = $entity_type_data['name'];
			$subtype_name = '';

			// Alter names if this is a Sub-type.
			if ( ! empty( $entity_type_data['parent_id'] ) ) {
				$parent_type = $this->civicrm->contact->type_get_by_id( $entity_type_data['parent_id'] );
				$name = $parent_type['name'];
				$subtype_name = $entity_type_data['name'];
			}

			// Get the Custom Fields for this Contact Type.
			$custom_fields = $this->plugin->civicrm->custom_field->get_for_contact_type( $name, $subtype_name );

		} else {

			// Get Custom Fields for other Entity Types.
			$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( $entity_type, '' );

		}

		/**
		 * Filter the Custom Fields.
		 *
		 * @since 0.5
		 *
		 * @param array The initially empty array of filtered Custom Fields.
		 * @param array $custom_fields The CiviCRM Custom Fields array.
		 * @param string $field_type The BuddyPress Field Type.
		 */
		$filtered_fields = apply_filters( 'cwps/bp/query_settings/custom_fields_filter', [], $custom_fields, $field_type );

		// Build Custom Field choices array for dropdown.
		if ( ! empty( $filtered_fields ) ) {
			foreach ( $filtered_fields as $custom_field_label => $custom_fields ) {
				foreach ( $custom_fields as $custom_field ) {
					$choices[$custom_field_label][$this->custom_field_prefix . $custom_field['id']] = $custom_field['label'];
				}
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			//'choices' => $choices,
			'field_type' => $field_type,
			'contact_type' => $contact_type,
			//'custom_fields' => $custom_fields,
			'filtered_fields' => $filtered_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/bp/custom_field/choices', $choices );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'choices' => $choices,
		], true ) );
		*/

		// Return populated array.
		return $choices;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the mapped Custom Field ID.
	 *
	 * @since 0.5
	 *
	 * @param string $value The value of the BuddyPress Field setting.
	 * @return integer|bool $custom_field_id The mapped Custom Field ID or false if not present.
	 */
	public function id_get( $value ) {

		// Init return.
		$custom_field_id = false;

		// Bail if our prefix isn't there.
		if ( false === strpos( $value, $this->custom_field_prefix ) ) {
			return $custom_field_id;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = (int) str_replace( $this->custom_field_prefix, '', $value );

		// --<
		return $custom_field_id;

	}



	/**
	 * Get the mapped Custom Field ID for a given BuddyPress Field.
	 *
	 * @since 0.5
	 *
	 * @param object $field The xProfile Field object.
	 * @return integer|bool $custom_field_id The numeric ID of the Custom Field, or false if none.
	 */
	public function id_get_by_field( $field ) {

		// Init return.
		$custom_field_id = false;

		// Get the BuddyPress CiviCRM Field value.
		$bp_field_value = $this->xprofile->get_mapping_data( $field, 'value' );

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $bp_field_value );

		/**
		 * Filter the Custom Field ID.
		 *
		 * @since 0.5
		 *
		 * @param integer $custom_field_id The existing Custom Field ID.
		 * @param object $field The xProfile Field object.
		 */
		$custom_field_id = apply_filters( 'cwps/bp/custom_field/id_get', $custom_field_id, $field );

		// --<
		return $custom_field_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Update BuddyPress Fields when a set of CiviCRM Custom Fields has been updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function custom_edited( $args ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Init User IDs.
		$user_id = false;

		/**
		 * Query for the User ID that this set of Custom Fields is mapped to.
		 *
		 * This filter sends out a request for other classes to respond with a
		 * User ID if they detect that the set of Custom Fields maps to an
		 * Entity Type that they are responsible for.
		 *
		 * Internally, this is used by:
		 *
		 * - BuddyPress CiviCRM Contact
		 * - BuddyPress CiviCRM Address
		 *
		 * More classes to follow as sync for those Entities is built.
		 *
		 * @since 0.5
		 *
		 * @param bool $user_id False, since we're asking for the User ID.
		 * @param array $args The array of CiviCRM Custom Fields params.
		 */
		$user_id = apply_filters( 'cwps/bp/query_user_id', $user_id, $args );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'user_id' => $user_id,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if we can't find a User ID.
		if ( $user_id === false ) {
			return;
		}

		// Get the BuddyPress Fields for this User.
		$bp_fields = $this->xprofile->fields_get_for_user( $user_id );

		// Bail if we don't find any Fields.
		if ( empty( $bp_fields ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'bp_fields' => $bp_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Filter out Fields not mapped to a CiviCRM Custom Field.
		$bp_fields_mapped = [];
		foreach ( $bp_fields as $bp_field ) {
			$bp_field_mapping = $bp_field['field_meta']['value'];
			$custom_field_id = $this->id_get( $bp_field_mapping );
			if ( $custom_field_id === false ) {
				continue;
			}
			$bp_field['custom_field_id'] = $custom_field_id;
			$bp_fields_mapped[] = $bp_field;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'bp_fields_mapped' => $bp_fields_mapped,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if we don't have any left.
		if ( empty( $bp_fields_mapped ) ) {
			return;
		}

		// Build a reference array for Custom Fields.
		$custom_fields = [];
		foreach ( $args['custom_fields'] as $key => $field ) {
			$custom_fields[$key] = $field['custom_field_id'];
		}

		// Let's look at each BuddyPress Field in turn.
		foreach ( $bp_fields_mapped as $bp_field ) {

			// Skip if it isn't mapped to an edited Custom Field.
			if ( ! in_array( $bp_field['custom_field_id'], $custom_fields ) ) {
				continue;
			}

			// Get the corresponding Custom Field.
			$args_key = array_search( $bp_field['custom_field_id'], $custom_fields );
			$field = $args['custom_fields'][$args_key];

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'field' => $field,
				//'backtrace' => $trace,
			], true ) );
			*/

			// Modify values for BuddyPress prior to update.
			$value = $this->value_get_for_bp( $field['value'], $field, $bp_field );

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'value' => $value,
				//'args' => $args,
				//'backtrace' => $trace,
			], true ) );
			*/

			// Okay, go ahead and save the value to the xProfile Field.
			$result = xprofile_set_field_data( $bp_field['field_id'], $user_id, $value );

		}

		// Add the User ID to the params.
		$args['user_id'] = $user_id;

		/**
		 * Broadcast that a set of CiviCRM Custom Fields may have been updated.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/bp/civicrm/custom_field/custom_edited', $args );

	}



	/**
	 * Get the value of a Custom Field, formatted for BuddyPress.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The CiviCRM Custom Field value.
	 * @param array $field The CiviCRM Custom Field data.
	 * @param $params The array of BuddyPress Field params.
	 * @return mixed $value The value formatted for BuddyPress.
	 */
	public function value_get_for_bp( $value, $field, $params ) {

		// Bail if empty.
		if ( empty( $value ) ) {
			return $value;
		}

		// Convert CiviCRM value to BuddyPress value by Field Type.
		switch( $field['type'] ) {

			// Used by "CheckBox" and others.
			case 'String' :
			case 'Country' :
			case 'StateProvince' :

				// Convert if the value has the special CiviCRM array-like format.
				if ( is_string( $value ) ) {
					if ( false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
						$value = CRM_Utils_Array::explodePadded( $value );
					}
				}

				break;

			// Contact Reference Fields may return the Contact's "sort_name".
			case 'ContactReference' :

				// Test for a numeric value.
				if ( ! is_numeric( $value ) ) {

					/*
					 * This definitely happens when Contact Reference Fields are
					 * attached to Events - when retrieving the Event from the
					 * CiviCRM API, the Custom Field values are helpfully added
					 * to the returned data. However, the value in "custom_N" is
					 * the Contact's "sort_name". The numeric ID is also returned,
					 * but this is added under the key "custom_N_id" instead.
					 */

					/*
					$e = new \Exception();
					$trace = $e->getTraceAsString();
					error_log( print_r( [
						'method' => __METHOD__,
						'value' => $value,
						'field' => $field,
						'selector' => $selector,
						'post_id' => $post_id,
						//'backtrace' => $trace,
					], true ) );
					*/

				}

				break;

			// Used by "Date Select" and  "Date Time Select".
			case 'Timestamp' :

				// Custom Fields use a YmdHis format, so try that.
				$datetime = DateTime::createFromFormat( 'YmdHis', $value );

				// Convert to BuddyPress format which cannot have "H:m:s".
				if ( $datetime !== false ) {
					$value = $datetime->format( 'Y-m-d' ) . ' 00:00:00';
				}

				break;

			// Used by "Note" and maybe others.
			case 'Memo' :

				// At minimum needs an unautop.
				$value = $this->unautop( $value );

				break;

		}

		// --<
		return $value;

	}



	/**
	 * Get the "date format" for a given CiviCRM Custom Field ID.
	 *
	 * @since 0.5
	 *
	 * @param array $custom_field_id The numeric ID of the Custom Field.
	 * @return string $format The date format.
	 */
	public function date_format_get_from_civicrm( $custom_field_id ) {

		// Init return.
		$format = '';

		// Bail if there is no Custom Field ID.
		if ( empty( $custom_field_id ) ) {
			return $format;
		}

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $format;
		}

		// Bail if it's not Date.
		if ( $field_data['data_type'] !== 'Date' ) {
			return $format;
		}

		// Bail if it's not "Select Date".
		if ( $field_data['html_type'] !== 'Select Date' ) {
			return $format;
		}

		// Bail if the "Time Format" is set.
		if ( isset( $field_data['time_format'] ) ) {
			return $format;
		}

		// Get the mappings.
		$mappings = $this->plugin->mapper->date_mappings;

		// Get the PHP format.
		$format = $mappings[ $field_data['date_format'] ];

		// --<
		return $format;

	}



	/**
	 * Replaces paragraph elements with double line-breaks.
	 *
	 * This is the inverse behavior of the wpautop() function found in WordPress
	 * which converts double line-breaks to paragraphs. Handy when you want to
	 * undo whatever it did.
	 *
	 * Code via Frankie Jarrett on GitHub.
	 *
	 * @link https://gist.github.com/fjarrett/ecddd0ed419bb853e390
	 *
	 * @since 0.5
	 *
	 * @param string $pee
	 * @param bool $br (optional)
	 * @return string
	 */
	public function unautop( $pee, $br = true ) {

		// Match plain <p> tags and their contents (ignore <p> tags with attributes).
		$matches = preg_match_all( '/<(p+)*(?:>(.*)<\/\1>|\s+\/>)/m', $pee, $pees );

		// Bail if no matches.
		if ( ! $matches ) {
			return $pee;
		}

		// Init replacements array.
		$replace = [
			"\n" => '',
			"\r" => '',
		];

		// Maybe add breaks to replacements array.
		if ( $br ) {
			$replace['<br>'] = "\r\n";
			$replace['<br/>'] = "\r\n";
			$replace['<br />'] = "\r\n";
		}

		// Build keyed replacements.
		foreach ( $pees[2] as $i => $tinkle ) {
			$replace[ $pees[0][ $i ] ] = $tinkle . "\r\n\r\n";
		}

		// Do replacements.
		$replaced = str_replace( array_keys( $replace ), array_values( $replace ), $pee );

		// --<
		return rtrim( $replaced );

	}



	// -------------------------------------------------------------------------



	/**
	 * Modify the Options of a BuddyPress "Checkbox" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function checkbox_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $args['value'] );
		if ( empty( $custom_field_id ) ) {
			return $options;
		}

		// Get keyed array of settings.
		$options = $this->checkbox_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Get the choices for the Setting of a "Checkbox" Field.
	 *
	 * @since 0.5
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the Field.
	 */
	public function checkbox_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not "String".
		if ( $field_data['data_type'] !== 'String' ) {
			return $choices;
		}

		// Bail if it's not "CheckBox".
		if ( $field_data['html_type'] !== 'CheckBox' ) {
			return $choices;
		}

		// Get options.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "CheckBox" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function checkbox_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Boolean/Radio.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'String' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'CheckBox' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Modify the Options of a BuddyPress "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function select_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'selectbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $args['value'] );
		if ( empty( $custom_field_id ) ) {
			return $options;
		}

		// Get keyed array of settings.
		$options = $this->select_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Get the choices for the Setting of a "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the Field.
	 */
	public function select_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not a data type that can have a "Select".
		if ( ! in_array( $field_data['data_type'], $this->data_types ) ) {
			return $choices;
		}

		// Bail if it's not a type of "Select".
		if ( ! in_array( $field_data['html_type'], $this->select_types ) ) {
			return $choices;
		}

		// Populate with child options where possible.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// "Country" selects require special handling.
		$country_selects = [ 'Select Country', 'Multi-Select Country' ];
		if ( in_array( $field_data['html_type'], $country_selects ) ) {
			$choices = CRM_Core_PseudoConstant::country();
		}

		// "State/Province" selects also require special handling.
		$state_selects = [ 'Select State/Province', 'Multi-Select State/Province' ];
		if ( in_array( $field_data['html_type'], $state_selects ) ) {
			$choices = CRM_Core_PseudoConstant::stateProvince();
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function select_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'selectbox' !== $field_type ) {
			return $filtered_fields;
		}

		/*
		// BuddyPress has no "Autocomplete-Select".
		if ( $field['ui'] == 1 && $field['ajax'] == 1 ) {

			// Filter Fields to include only Autocomplete-Select.
			$select_types = [ 'Autocomplete-Select' ];

		}
		*/

		// Filter Fields to include only "Select" types.
		$select_types = [ 'Select', 'Select Country', 'Select State/Province' ];

		// Filter Fields to include only those which are compatible.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) && in_array( $custom_field['html_type'], $select_types ) ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Modify the Options of a BuddyPress "Multi Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function multiselect_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'multiselectbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $args['value'] );
		if ( empty( $custom_field_id ) ) {
			return $options;
		}

		// Get keyed array of settings.
		$options = $this->select_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Multi Select Box" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function multiselect_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'multiselectbox' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter Fields to include only "Multi-Select" types.
		$select_types = [ 'Multi-Select', 'Multi-Select Country', 'Multi-Select State/Province' ];

		// Filter Fields to include only those which are compatible.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) && in_array( $custom_field['html_type'], $select_types ) ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Modify the Options of a BuddyPress "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function radio_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'radio' !== $field_type ) {
			return $options;
		}

		// Get the mapped Custom Field ID.
		$custom_field_id = $this->id_get( $args['value'] );
		if ( empty( $custom_field_id ) ) {
			return $options;
		}

		// Get keyed array of settings.
		$options = $this->radio_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Get the choices for the Setting of a "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the Field.
	 */
	public function radio_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not a data type that can have a "Radio" sub-type.
		if ( ! in_array( $field_data['data_type'], $this->data_types ) ) {
			return $choices;
		}

		// Bail if it's not "Radio".
		if ( $field_data['html_type'] !== 'Radio' ) {
			return $choices;
		}

		// Get options.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function radio_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'radio' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter Fields to include only "Radio" HTML types.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Radio' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Date" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function date_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'datebox' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Date/Select Date.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'Date' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Select Date' ) {
						if ( ! isset( $custom_field['time_format'] ) OR $custom_field['time_format'] == '0' ) {
							$filtered_fields[$custom_group_name][] = $custom_field;
						}
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Modify the Options of a BuddyPress "Text" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function text_settings_get( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'textbox' !== $field_type ) {
			return $options;
		}

		// Bail if our prefix isn't there.
		if ( false === strpos( $args['value'], $this->custom_field_prefix ) ) {
			return $options;
		}

		// Get keyed array of settings.
		//$options = $this->radio_choices_get( $custom_field_id );

		// --<
		return $options;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Text" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function text_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'textbox' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter Fields to include only those of HTML type "Text".
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Text' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Textarea" Field.
	 *
	 * Thisis actually a "Rich Text" Field in BuddyPress.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function textarea_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'textarea' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Memo/RichTextEditor.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'Memo' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'RichTextEditor' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "URL" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function url_settings_filter( $filtered_fields, $custom_fields, $field_type ) {

		// Bail early if not our Field Type.
		if ( 'url' !== $field_type ) {
			return $filtered_fields;
		}

		// Filter Fields to include only "Link".
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'Link' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Link' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



} // Class ends.


