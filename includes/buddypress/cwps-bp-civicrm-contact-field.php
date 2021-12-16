<?php
/**
 * BuddyPress CiviCRM Contact Field Class.
 *
 * Handles BuddyPress CiviCRM Contact Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress CiviCRM Contact Field Class.
 *
 * A class that encapsulates BuddyPress CiviCRM Contact Field functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_CiviCRM_Contact_Field {

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
	 * Contact Fields that all Contact Types have in common.
	 *
	 * These are mapped to their corresponding BuddyPress Field types.
	 *
	 * The "display_name" Field is disabled for now - we need to decide if it
	 * should sync or whether the Post Title always maps to it.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $contact_fields_common The common public Contact Fields.
	 */
	public $contact_fields_common = [
		'nick_name' => 'textbox',
		'image_URL' => 'image',
		'source' => 'textbox',
		'do_not_email' => 'true_false',
		'do_not_phone' => 'true_false',
		'do_not_mail' => 'true_false',
		'do_not_sms' => 'true_false',
		'do_not_trade' => 'true_false',
		'is_opt_out' => 'true_false',
		'preferred_communication_method' => 'checkbox',
		'preferred_language' => 'selectbox',
		'preferred_mail_format' => 'selectbox',
		'legal_identifier' => 'textbox',
		'external_identifier' => 'textbox',
		'communication_style_id' => 'selectbox',
	];

	/**
	 * Contact Fields for Individuals.
	 *
	 * Mapped to their corresponding BuddyPress Field types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $contact_fields_individual The public Contact Fields for Individuals.
	 */
	public $contact_fields_individual = [
		'prefix_id' => 'selectbox',
		'first_name' => 'textbox',
		'last_name' => 'textbox',
		'middle_name' => 'textbox',
		'suffix_id' => 'selectbox',
		'job_title' => 'textbox',
		'gender_id' => 'radio',
		'birth_date' => 'datebox',
		'is_deceased' => 'true_false',
		'deceased_date' => 'datebox',
		'employer_id' => 'civicrm_contact',
		'formal_title' => 'textbox',
	];

	/**
	 * Contact Fields for Organisations.
	 *
	 * Mapped to their corresponding BuddyPress Field types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $contact_fields_organization The public Contact Fields for Organisations.
	 */
	public $contact_fields_organization = [
		'legal_name' => 'textbox',
		'organization_name' => 'textbox',
		'sic_code' => 'textbox',
	];

	/**
	 * Contact Fields for Households.
	 *
	 * Mapped to their corresponding BuddyPress Field types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $contact_fields_household The public Contact Fields for Households.
	 */
	public $contact_fields_household = [
		'household_name' => 'textbox',
	];

	/**
	 * "CiviCRM Field" Field value prefix in the BuddyPress Field data.
	 *
	 * This distinguishes Contact Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $contact_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $contact_field_prefix = 'cwps_contact_';



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

		// Listen for queries from the BuddyPress Field class.
		add_filter( 'cwps/bp/field/query_setting_choices', [ $this, 'query_setting_choices' ], 10, 4 );

		// Filter the xProfile Field options when saving on the "Edit Field" screen.
		add_filter( 'cwps/bp/field/query_options', [ $this, 'checkbox_settings_modify' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'true_false_settings_modify' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'select_settings_modify' ], 10, 3 );
		//add_filter( 'cwps/bp/field/query_options', [ $this, 'multiselect_settings_modify' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'radio_settings_modify' ], 10, 3 );

		// Append "True/False" mappings to the "Checkbox" xProfile Field Type.
		add_filter( 'cwps/bp/civicrm/contact_field/get_for_bp_field', [ $this, 'true_false_fields_append' ], 10, 3 );

		// Determine if a "Checkbox" Field is a "True/False" Field.
		add_filter( 'cwps/bp/xprofile/value/checkbox/query_type', [ $this, 'true_false_field_query' ], 10, 2 );

		// Filter the xProfile Field settings when saving on the "Edit Field" screen.
		//add_filter( 'cwps/bp/field/post_update', [ $this, 'date_settings_modify' ], 10, 2 );
		//add_filter( 'cwps/bp/field/post_update', [ $this, 'text_settings_modify' ], 10, 2 );

		// Listen for when a Contact Field need syncing to an xProfile Field.
		add_action( 'cwps/buddypress/contact/field/sync', [ $this, 'bp_field_update' ], 10, 2 );

		return;

		// Some Contact "Text" Fields need their own validation.
		//add_filter( 'bp/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

		// Intercept Contact Image delete.
		add_action( 'civicrm_postSave_civicrm_contact', [ $this, 'image_deleted' ], 10 );
		add_action( 'delete_attachment', [ $this, 'image_attachment_deleted' ], 10 );

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
		if ( empty( $entity_type ) || empty( $field_type ) || empty( $entity_type_data ) ) {
			return $choices;
		}

		// Bail if not the "Contact" Entity Type.
		if ( $entity_type !== 'Contact' ) {
			return $choices;
		}

		// Get the Contact Fields for this BuddyPress Field Type.
		$contact_fields = $this->get_for_bp_field_type( $field_type, $entity_type_data );

		// Build Contact Field choices array for dropdown.
		if ( ! empty( $contact_fields ) ) {
			$contact_fields_label = esc_attr__( 'Contact Fields', 'civicrm-wp-profile-sync' );
			foreach ( $contact_fields as $contact_field ) {
				$choices[ $contact_fields_label ][ $this->contact_field_prefix . $contact_field['name'] ] = $contact_field['title'];
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/bp/contact_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



	/**
	 * Get the CiviCRM Contact Fields for a BuddyPress Field Type.
	 *
	 * @since 0.5
	 *
	 * @param string $field_type The BuddyPress Field Type.
	 * @param array $contact_type The array of Contact Type data.
	 * @return array $contact_fields The array of Contact Fields.
	 */
	public function get_for_bp_field_type( $field_type, $contact_type ) {

		// Init return.
		$contact_fields = [];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'field_type' => $field_type,
			'contact_type' => $contact_type,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the "name" of the Contact Type.
		$name = $contact_type['name'];

		// Get the "name" of the top-level Contact Type if this is a Sub-type.
		if ( ! empty( $contact_type['parent_id'] ) ) {
			$parent_type = $this->civicrm->contact->type_get_by_id( $contact_type['parent_id'] );
			$name = $parent_type['name'];
		}

		// Get public Fields of this type.
		$contact_fields = $this->data_get( $name, $field_type, 'public' );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'contact_fields' => $contact_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		/**
		 * Filter the Contact Fields.
		 *
		 * @since 0.5
		 *
		 * @param array $contact_fields The existing array of Contact Fields.
		 * @param string $field_type The BuddyPress Field Type.
		 * @param string $name The name of the top-level Contact Type.
		 */
		$contact_fields = apply_filters( 'cwps/bp/civicrm/contact_field/get_for_bp_field', $contact_fields, $field_type, $name );

		// --<
		return $contact_fields;

	}



	/**
	 * Get the BuddyPress "selectbox" options for a given CiviCRM Contact Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Contact Field.
	 * @return array $options The array of xProfile Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// We only have a few to account for.

		// Individual Prefix.
		if ( $name == 'prefix_id' ) {
			$option_group = $this->civicrm->option_group_get( 'individual_prefix' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// Individual Suffix.
		if ( $name == 'suffix_id' ) {
			$option_group = $this->civicrm->option_group_get( 'individual_suffix' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// Gender.
		if ( $name == 'gender_id' ) {
			$option_group = $this->civicrm->option_group_get( 'gender' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// Preferred Communication Method.
		if ( $name == 'preferred_communication_method' ) {
			$options = CRM_Contact_BAO_Contact::buildOptions( 'preferred_communication_method' );
		}

		// Preferred Language.
		if ( $name == 'preferred_language' ) {
			$options = CRM_Contact_BAO_Contact::buildOptions( 'preferred_language' );
		}

		// Preferred Mail Format.
		if ( $name == 'preferred_mail_format' ) {
			$options = CRM_Core_SelectValues::pmf();
		}

		// Communication Style.
		if ( $name == 'communication_style_id' ) {
			$option_group = $this->civicrm->option_group_get( 'communication_style' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// --<
		return $options;

	}



	/**
	 * Get the "date format" for a given CiviCRM Contact Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Contact Field.
	 * @return string $format The date format.
	 */
	public function date_format_get_from_civicrm( $name ) {

		// Init return.
		$format = '';

		// We only have a few to account for.
		$birth_fields = [ 'birth_date', 'deceased_date' ];

		// "Birth Date" and "Deceased Date" use the same preference.
		if ( in_array( $name, $birth_fields ) ) {
			$format = CRM_Utils_Date::getDateFormat( 'birth' );
		}

		// If it's empty, fall back on CiviCRM-wide setting.
		if ( empty( $format ) ) {
			// No need yet - `getDateFormat()` already does this.
		}

		// Get the mappings.
		$mappings = $this->plugin->mapper->date_mappings;

		// Get the PHP format.
		$format = $mappings[ $format ];

		// --<
		return $format;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the BuddyPress Field Type for a Contact Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Contact Field.
	 * @return string $type The type of BuddyPress Field.
	 */
	public function get_bp_type( $name = '' ) {

		// Init return.
		$type = false;

		// Combine different arrays.
		$contact_fields = $this->contact_fields_individual +
						  $this->contact_fields_organization +
						  $this->contact_fields_household +
						  $this->contact_fields_common;

		// if the key exists, return the value - which is the BuddyPress Type.
		if ( array_key_exists( $name, $contact_fields ) ) {
			$type = $contact_fields[ $name ];
		}

		// --<
		return $type;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the core Contact Fields for a CiviCRM Contact Type.
	 *
	 * @since 0.5
	 *
	 * @param array $contact_type The Contact Type to query.
	 * @param string $field_type The type of BuddyPress Field.
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function data_get( $contact_type = 'Individual', $field_type = '', $filter = 'none' ) {

		// Only do this once per Contact Type, Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ][ $contact_type ][ $field_type ] ) ) {
			return $pseudocache[ $filter ][ $contact_type ][ $field_type ];
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
		$result = civicrm_api( 'Contact', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Init Fields array.
				$contact_fields = [];

				// Check against different Field sets per type.
				if ( $contact_type == 'Individual' ) {
					$contact_fields = $this->contact_fields_individual;
				}
				if ( $contact_type == 'Organization' ) {
					$contact_fields = $this->contact_fields_organization;
				}
				if ( $contact_type == 'Household' ) {
					$contact_fields = $this->contact_fields_household;
				}

				// Combine these with common Fields.
				$contact_fields = array_merge( $contact_fields, $this->contact_fields_common );

				// Skip all but those defined in our Contact Fields arrays.
				$public_fields = [];
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $contact_fields ) ) {
						$public_fields[] = $value;
					}
				}

				// Skip all but those mapped to the type of BuddyPress Field.
				foreach ( $public_fields as $key => $value ) {
					if ( $field_type == $contact_fields[ $value['name'] ] ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ][ $contact_type ][ $field_type ] ) ) {
			$pseudocache[ $filter ][ $contact_type ][ $field_type ] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Gets the mapped Contact Field name.
	 *
	 * @since 0.5
	 *
	 * @param string $value The value of the BuddyPress Field setting.
	 * @return string $name The mapped Contact Field name.
	 */
	public function name_get( $value ) {

		// Init return.
		$name = '';

		// Bail if our prefix isn't there.
		if ( false === strpos( $value, $this->contact_field_prefix ) ) {
			return $name;
		}

		// Get the mapped Contact Field name.
		$name = (string) str_replace( $this->contact_field_prefix, '', $value );

		// --<
		return $name;

	}



	/**
	 * Sync a mapped Contact Field to a BuddyPress xProfile Field.
	 *
	 * @since 0.5
	 *
	 * @param array $params The array of Field params.
	 * @param array $args The array of arguments from the Mapper.
	 */
	public function bp_field_update( $params, $args ) {

		// Bail if something is amiss.
		if ( empty( $params['field_meta']['value'] ) ) {
			return;
		}

		// Try and get the name of the Contact Field.
		$contact_field = $this->name_get( $params['field_meta']['value'] );
		if ( empty( $contact_field ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'contact_field' => $contact_field,
			'params' => $params,
			//'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Does the mapped Contact Field exist?
		if ( ! isset( $args['objectRef']->$contact_field ) ) {
			return;
		}

		// Modify value for BuddyPress prior to update.
		$value = $this->value_get_for_bp( $args['objectRef']->$contact_field, $contact_field, $params );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'value' => $value,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Okay, go ahead and save the value to the xProfile Field.
		$result = $this->xprofile->value_update( $params['field_id'], $args['user_id'], $value );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'result' => $result,
			//'backtrace' => $trace,
		], true ) );
		*/

	}



	/**
	 * Get the value of a Contact Field, formatted for BuddyPress.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The Contact Field value.
	 * @param array $name The Contact Field name.
	 * @param array $params The array of Field params.
	 * @return mixed $value The value formatted for BuddyPress.
	 */
	public function value_get_for_bp( $value, $name, $params ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'value' => $value,
			'name' => $name,
			'params' => $params,
			//'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if value is (string) 'null' which CiviCRM uses for some reason.
		if ( $value == 'null' || $value == 'NULL' ) {
			return '';
		}

		// Get the BuddyPress Field Type for this Contact Field.
		$type = $this->get_bp_type( $name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'type' => $type,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Convert CiviCRM value to BuddyPress value by Field Type.
		switch ( $type ) {

			// Used by "Do not Sms" etc.
			case 'true_false':

				// Clear the value when empty.
				if ( empty( $value ) ) {
					$value = null;
				} else {
					$value = 1;
				}

				break;

			// May not be used.
			case 'checkbox':

				// Convert if the value has the special CiviCRM array-like format.
				if ( is_string( $value ) ) {
					if ( false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
						$value = CRM_Utils_Array::explodePadded( $value );
					}
				}

				break;

			// Used by "Birth Date" and "Deceased Date".
			case 'datebox':

				// Contact edit passes a Y-m-d format, so test for that.
				$datetime = DateTime::createFromFormat( 'Y-m-d', $value );

				// Contact create passes a different format, so test for that.
				if ( $datetime === false ) {
					$datetime = DateTime::createFromFormat( 'YmdHis', $value );
				}

				// Convert to BuddyPress format which cannot have "H:m:s".
				if ( $datetime !== false ) {
					$value = $datetime->format( 'Y-m-d' ) . ' 00:00:00';
				}

				break;

		}

		/**
		 * When submitting the Contact "Edit" form in the CiviCRM back end, the
		 * email address is appended as an array. At other times, it is a string.
		 * We find the first "primary" email entry and use that.
		 */
		if ( $name == 'email' ) {

			// Maybe grab the email from the array.
			if ( is_array( $value ) ) {
				foreach ( $value as $email ) {
					if ( $email->is_primary == '1' ) {
						$value = $email->email;
						break;
					}
				}
			}

		}

		// --<
		return $value;

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
	 * @return array $options The possibly populated array of Options.
	 */
	public function checkbox_settings_modify( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Contact Field name.
		$contact_field_name = $this->name_get( $args['value'] );
		if ( empty( $contact_field_name ) ) {
			return $options;
		}

		// Get keyed array of options for this Contact Field.
		$options = $this->options_get( $contact_field_name );

		// --<
		return $options;

	}



	/**
	 * Modify the Options of a BuddyPress "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function select_settings_modify( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'selectbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Contact Field name.
		$contact_field_name = $this->name_get( $args['value'] );
		if ( empty( $contact_field_name ) ) {
			return $options;
		}

		// Get keyed array of options for this Contact Field.
		$options = $this->options_get( $contact_field_name );

		// --<
		return $options;

	}



	/**
	 * Modify the Options of a BuddyPress "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function radio_settings_modify( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'radio' !== $field_type ) {
			return $options;
		}

		// Get the mapped Contact Field name.
		$contact_field_name = $this->name_get( $args['value'] );
		if ( empty( $contact_field_name ) ) {
			return $options;
		}

		// Get keyed array of options for this Contact Field.
		$options = $this->options_get( $contact_field_name );

		// --<
		return $options;

	}



	/**
	 * Modify the Settings of a BuddyPress "Date" Field.
	 *
	 * @since 0.5
	 *
	 * @param object $field The xProfile Field object.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function date_settings_modify( $field, $args ) {

		// Bail early if not our Field Type.
		if ( 'datebox' !== $field_type ) {
			return;
		}

		// Get the mapped Contact Field name.
		$contact_field_name = $this->plugin->civicrm->contact_field->get_by_name( $args['value'] );
		if ( empty( $contact_field_name ) ) {
			return $options;
		}

		// TODO: This can't be done here directly.
		return $options;

		// Get Contact Field data.
		$format = $this->date_format_get( $contact_field_name );

		// Get the BuddyPress format.
		$bp_format = $this->bp_loader->mapper->date_mappings[ $format ];

		// Set the date "format" attributes.
		$field['display_format'] = $bp_format;
		$field['return_format'] = $bp_format;

	}



	/**
	 * Modify the Settings of a BuddyPress "Text" Field.
	 *
	 * @since 0.5
	 *
	 * @param object $field The xProfile Field object.
	 * @param array $args The array of CiviCRM mapping data.
	 */
	public function text_settings_modify( $field, $args ) {

		// Bail early if not our Field Type.
		if ( 'textbox' !== $field_type ) {
			return;
		}

		// Get the mapped Contact Field name.
		$contact_field_name = $this->name_get( $args['value'] );
		if ( empty( $contact_field_name ) ) {
			return $options;
		}

		// TODO: This can't be done here directly.
		return $options;

		// Get Contact Field data.
		$field_data = $this->plugin->civicrm->contact_field->get_by_name( $contact_field_name );

		// Set the "maxlength" attribute.
		if ( ! empty( $field_data['maxlength'] ) ) {
			$field['maxlength'] = $field_data['maxlength'];
		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Modify the Options of a special case BuddyPress "Checkbox" Field.
	 *
	 * BuddyPress does not have a "True/False" Field, so we use a "Checkbox"
	 * with only a single option.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function true_false_settings_modify( $options, $field_type, $args ) {

		// Bail early if not the "Checkbox" Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $options;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'options' => $options,
			'field_type' => $field_type,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the mapped Contact Field name.
		$contact_field_name = $this->name_get( $args['value'] );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			//'value' => $value,
			'contact_field_name' => $contact_field_name,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( empty( $contact_field_name ) ) {
			return $options;
		}

		// Bail if not a "True/False" Field Type.
		$civicrm_field_type = $this->get_bp_type( $contact_field_name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'civicrm_field_type' => $civicrm_field_type,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( $civicrm_field_type !== 'true_false' ) {
			return $options;
		}

		// Get the full details for the CiviCRM Field.
		$civicrm_field = $this->plugin->civicrm->contact_field->get_by_name( $contact_field_name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'civicrm_field' => $civicrm_field,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Use title for checkbox label.
		$options = [ 1 => $civicrm_field['title'] ];

		// --<
		return $options;

	}



	/**
	 * Filter the Contact Fields for a special case BuddyPress "Checkbox" Field.
	 *
	 * BuddyPress does not have a "True/False" Field, so we use a "Checkbox"
	 * with only a single option.
	 *
	 * @since 0.5
	 *
	 * @param array $contact_fields The existing array of Contact Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @param string $name The name of the top-level Contact Type.
	 * @return array $contact_fields The modified array of Contact Fields.
	 */
	public function true_false_fields_append( $contact_fields, $field_type, $name ) {

		// Bail early if not the "Checkbox" Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $contact_fields;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'contact_fields' => $contact_fields,
			'field_type' => $field_type,
			'name' => $name,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get public Fields of this type.
		$true_false_fields = $this->data_get( $name, 'true_false', 'public' );
		if ( empty( $true_false_fields ) ) {
			return $contact_fields;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'true_false_fields' => $true_false_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Merge with Contact Fields.
		$contact_fields = array_merge( $contact_fields, $true_false_fields );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'contact_fields-FINAL' => $contact_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $contact_fields;

	}



	/**
	 * Checks if a "Checkbox" Field is a "True/False" Field.
	 *
	 * @since 0.5
	 *
	 * @param bool $is_true_false True if "Checkbox" is a "True/False" Field. False by default.
	 * @param array $args The array of arguments.
	 * @return bool $is_true_false True if "Checkbox" is a "True/False" Field. False by default.
	 */
	public function true_false_field_query( $is_true_false, $args ) {

		// Bail early if not the "Contact" Entity Type.
		if ( 'Contact' !== $args['entity_type'] ) {
			return $is_true_false;
		}

		// Bail if not a "Contact Field".
		if ( empty( $args['contact_field_name'] ) ) {
			return $is_true_false;
		}

		// Check if this is a "True/False" Field Type.
		$civicrm_field_type = $this->get_bp_type( $args['contact_field_name'] );
		if ( $civicrm_field_type === 'true_false' ) {
			$is_true_false = true;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'is_true_false' => $is_true_false,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $is_true_false;

	}



} // Class ends.



