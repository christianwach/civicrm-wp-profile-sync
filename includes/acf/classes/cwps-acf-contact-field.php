<?php
/**
 * CiviCRM Contact Field Class.
 *
 * Handles CiviCRM Contact Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Contact Field Class.
 *
 * A class that encapsulates CiviCRM Contact Field functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Field {

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
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Contact Fields that all Contact Types have in common.
	 *
	 * These are mapped to their corresponding ACF Field types.
	 *
	 * The "display_name" Field is disabled for now - we need to decide if it
	 * should sync or whether the Post Title always maps to it.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $contact_fields_common = [
		'nick_name' => 'text',
		'image_URL' => 'image',
		'source' => 'text',
		'do_not_email' => 'true_false',
		'do_not_phone' => 'true_false',
		'do_not_mail' => 'true_false',
		'do_not_sms' => 'true_false',
		'do_not_trade' => 'true_false',
		'is_opt_out' => 'true_false',
		'preferred_communication_method' => 'checkbox',
		'preferred_language' => 'select',
		'preferred_mail_format' => 'select',
		'legal_identifier' => 'text',
		'external_identifier' => 'text',
		'communication_style_id' => 'select',
	];

	/**
	 * Contact Fields for Individuals.
	 *
	 * Mapped to their corresponding ACF Field types.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $contact_fields_individual = [
		'prefix_id' => 'select',
		'first_name' => 'text',
		'last_name' => 'text',
		'middle_name' => 'text',
		'suffix_id' => 'select',
		'job_title' => 'text',
		'gender_id' => 'radio',
		'birth_date' => 'date_picker',
		'is_deceased' => 'true_false',
		'deceased_date' => 'date_picker',
		'employer_id' => 'civicrm_contact',
		'formal_title' => 'text',
	];

	/**
	 * Contact Fields for Organisations.
	 *
	 * Mapped to their corresponding ACF Field types.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $contact_fields_organization = [
		'legal_name' => 'text',
		'organization_name' => 'text',
		'sic_code' => 'text',
	];

	/**
	 * Contact Fields for Households.
	 *
	 * Mapped to their corresponding ACF Field types.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $contact_fields_household = [
		'household_name' => 'text',
	];

	/**
	 * Constructor.
	 *
	 * @since 0.4
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

		// Intercept Post created, updated (or synced) from Contact events.
		add_action( 'cwps/acf/post/created', [ $this, 'post_edited' ], 10 );
		add_action( 'cwps/acf/post/edited', [ $this, 'post_edited' ], 10 );
		// Intercept Post-Contact sync event.
		add_action( 'cwps/acf/post/contact/sync', [ $this, 'contact_sync_to_post' ], 10 );

		// Some Contact "Text" Fields need their own validation.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

		// Intercept Contact Image delete.
		add_action( 'civicrm_postSave_civicrm_contact', [ $this, 'image_deleted' ], 10 );
		add_action( 'delete_attachment', [ $this, 'image_attachment_deleted' ], 10 );

		// Listen for queries from our ACF Field Group class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 50, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'checkbox_settings_modify' ], 50, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'radio_settings_modify' ], 50, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'date_picker_settings_modify' ], 50, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'text_settings_modify' ], 50, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'image_settings_modify' ], 50, 2 );

		// TODO: Add hooks to Relationships to detect Employer changes via that route.

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a Post has been updated from a Contact via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Contact Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get Employer ID for this Contact.
		$employer_id = CRM_Core_DAO::getFieldValue( 'CRM_Contact_DAO_Contact', $args['objectId'], 'employer_id' );

		// If we get one, add it.
		if ( ! isset( $args['objectRef']->employer_id ) ) {
			$args['objectRef']->employer_id = $employer_id;
		}

		// Re-use Post Edited method.
		$this->post_edited( $args );

	}

	/**
	 * Intercept when a Post has been updated from a Contact via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Contact Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function post_edited( $args ) {

		// Get originating Entity.
		$originating_entity = $this->acf_loader->mapper->entity_get();

		// Get the Contact Type hierarchy.
		$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_for_contact( $args['objectRef'] );

		// Get the public Contact Fields for the top level type.
		$public_fields = $this->get_public( $hierarchy );

		// Get the ACF Fields for this ACF "Post ID".
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Contact Fields.
		if ( empty( $acf_fields['contact'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['contact'] as $selector => $contact_field ) {

			// Skip if it's not a public Contact Field.
			if ( ! array_key_exists( $contact_field, $public_fields ) ) {
				continue;
			}

			// Does the mapped Contact Field exist?
			if ( isset( $args['objectRef']->$contact_field ) ) {

				// Modify value for ACF prior to update.
				$value = $this->value_get_for_acf(
					$args['objectRef']->$contact_field,
					$contact_field,
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
	 * Get the value of a Contact Field, formatted for ACF.
	 *
	 * @since 0.4
	 *
	 * @param mixed  $value The Contact Field value.
	 * @param array  $name The Contact Field name.
	 * @param string $selector The ACF Field selector.
	 * @param mixed  $post_id The ACF "Post ID".
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

		// Get the ACF type for this Contact Field.
		$type = $this->get_acf_type( $name );

		// Convert CiviCRM value to ACF value by Contact Field.
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

					// Contact edit passes a Y-m-d format, so test for that.
					$datetime = DateTime::createFromFormat( 'Y-m-d', $value );

					// Contact create passes a different format, so test for that.
					if ( $datetime === false ) {
						$datetime = DateTime::createFromFormat( 'YmdHis', $value );
					}

					// Convert to ACF format.
					$value = $datetime->format( 'Ymd' );

				// Date & Time Picker test.
				} elseif ( $acf_setting['type'] == 'date_time_picker' ) {
					$datetime = DateTime::createFromFormat( 'YmdHis', $value );
					$value = $datetime->format( 'Y-m-d H:i:s' );
				}

				break;

			// Used by "Contact Image".
			case 'image':

				// Delegate to method, expect an Attachment ID.
				$value = $this->image_value_get_for_acf( $value, $name, $selector, $post_id );

				break;

		}

		// TODO: Filter here?

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
	 * Get the "date format" for a given CiviCRM Contact Field.
	 *
	 * @since 0.4
	 *
	 * @param string $name The name of the Contact Field.
	 * @return string $format The date format.
	 */
	public function date_format_get( $name ) {

		// Init return.
		$format = '';

		// We only have a few to account for.
		$birth_fields = [ 'birth_date', 'deceased_date' ];

		// "Birth Date" and "Deceased Date" use the same preference.
		if ( in_array( $name, $birth_fields ) ) {
			$format = CRM_Utils_Date::getDateFormat( 'birth' );
		}

		/*
		// If it's empty, fall back on CiviCRM-wide setting.
		if ( empty( $format ) ) {
			// No need yet - `getDateFormat()` already does this.
		}
		*/

		// --<
		return $format;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the "select" options for a given CiviCRM Contact Field.
	 *
	 * @since 0.4
	 *
	 * @param string $name The name of the Contact Field.
	 * @return array $options The array of Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// We only have a few to account for.

		// Individual Prefix.
		if ( $name == 'prefix_id' ) {
			$option_group = $this->plugin->civicrm->option_group_get( 'individual_prefix' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// Individual Suffix.
		if ( $name == 'suffix_id' ) {
			$option_group = $this->plugin->civicrm->option_group_get( 'individual_suffix' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// Gender.
		if ( $name == 'gender_id' ) {
			$option_group = $this->plugin->civicrm->option_group_get( 'gender' );
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
			$option_group = $this->plugin->civicrm->option_group_get( 'communication_style' );
			if ( ! empty( $option_group ) ) {
				$options = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
			}
		}

		// --<
		return $options;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Contact Fields for an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $contact_fields The array of Contact Fields.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$contact_fields = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $contact_fields;
		}

		// Skip if this is not a Contact Field Group.
		$is_contact_field_group = $this->civicrm->contact->is_contact_field_group( $field_group );
		if ( $is_contact_field_group !== false ) {

			// Loop through the Post Types.
			foreach ( $is_contact_field_group as $post_type_name ) {

				// Get the Contact Type ID.
				$contact_type_id = $this->civicrm->contact_type->id_get_for_post_type( $post_type_name );

				// Get Contact Type hierarchy.
				$contact_types = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $contact_type_id );

				// Get public Fields of this type.
				$contact_fields_for_type = $this->data_get( $contact_types['type'], $field['type'], 'public' );

				// Merge with return array.
				$contact_fields = array_merge( $contact_fields, $contact_fields_for_type );

			}

		}

		// Prefix and Suffix cannot be shown for "Multi-Select" or "Autocomplete-Select".
		if ( 'select' === $field['type'] ) {
			if ( $field['multiple'] == 1 || ( $field['ui'] == 1 && $field['ajax'] == 1 ) ) {

				// Re-build Fields without them.
				$filtered_fields = [];
				foreach ( $contact_fields as $contact_field ) {
					if ( $contact_field['name'] == 'prefix_id' || $contact_field['name'] == 'suffix_id' ) {
						continue;
					}
					$filtered_fields[] = $contact_field;
				}
				$contact_fields = $filtered_fields;

			}
		}

		/**
		 * Filter the Contact Fields.
		 *
		 * @since 0.4
		 *
		 * @param array $contact_fields The existing array of Contact Fields.
		 * @param array $field_group The ACF Field Group data array.
		 * @param array $field The ACF Field data array.
		 */
		$contact_fields = apply_filters( 'cwps/acf/civicrm/contact_field/get_for_acf_field', $contact_fields, $field_group, $field );

		// --<
		return $contact_fields;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the core Fields for a CiviCRM Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param array  $contact_type The Contact Type to query.
	 * @param string $field_type The type of ACF Field.
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

				// Skip all but those mapped to the type of ACF Field.
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
	 * Get the core Fields for all CiviCRM Contact Types.
	 *
	 * @since 0.4
	 *
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function data_get_filtered( $filter = 'none' ) {

		// Only do this once per Contact Type, Field Type and filter.
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
			'sequential' => 1,
			'options' => [
				//'sort' => 'title',
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

				// Get the top level Contact Types array.
				$top_level = [];
				$contact_types = $this->plugin->civicrm->contact_type->types_get_all();
				foreach ( $contact_types as $contact_type ) {
					if ( empty( $contact_type['parent_id'] ) ) {
						$top_level[ $contact_type['name'] ] = $contact_type['id'];
					}
				}

				// Skip all but those defined in our Contact Fields arrays.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->contact_fields_individual ) ) {
						$fields[ $top_level['Individual'] ][] = $value;
					}
					if ( array_key_exists( $value['name'], $this->contact_fields_organization ) ) {
						$fields[ $top_level['Organization'] ][] = $value;
					}
					if ( array_key_exists( $value['name'], $this->contact_fields_household ) ) {
						$fields[ $top_level['Household'] ][] = $value;
					}
					if ( array_key_exists( $value['name'], $this->contact_fields_common ) ) {
						$fields['common'][] = $value;
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
	 * Get the Fields for a CiviCRM Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param array $types The Contact Type(s) to query.
	 * @return array $fields The array of Field names.
	 */
	public function get_public( $types = [ 'Individual' ] ) {

		// Init return.
		$contact_fields = [];

		// Check against different Field sets per type.
		if ( in_array( 'Individual', $types ) ) {
			$contact_fields = $this->contact_fields_individual;
		}
		if ( in_array( 'Organization', $types ) ) {
			$contact_fields = $this->contact_fields_organization;
		}
		if ( in_array( 'Household', $types ) ) {
			$contact_fields = $this->contact_fields_household;
		}

		// Combine these with common Fields.
		$contact_fields = array_merge( $contact_fields, $this->contact_fields_common );

		// --<
		return $contact_fields;

	}

	/**
	 * Get the public Fields for all top-level CiviCRM Contact Types.
	 *
	 * @since 0.5
	 *
	 * @return array $public_fields The array of CiviCRM Fields.
	 */
	public function get_public_fields() {

		// Init return.
		$public_fields = [];

		// Get the public Contact Fields for all top level Contact Types.
		$public_fields = $this->data_get_filtered( 'public' );

		// --<
		return $public_fields;

	}

	/**
	 * Get the Fields for an ACF Field and mapped to a CiviCRM Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param array  $types The Contact Type(s) to query.
	 * @param string $type The type of ACF Field.
	 * @return array $fields The array of Field names.
	 */
	public function get_by_acf_type( $types = [ 'Individual' ], $type = '' ) {

		// Init return.
		$contact_fields = [];

		// Get the public Fields defined in this class.
		$public_fields = $this->get_public( $types );

		// Skip all but those mapped to the type of ACF Field.
		foreach ( $public_fields as $key => $value ) {
			if ( $type == $value ) {
				$contact_fields[ $key ] = $value;
			}
		}

		// --<
		return $contact_fields;

	}

	/**
	 * Get the ACF Field Type for a Contact Field.
	 *
	 * @since 0.4
	 *
	 * @param string $name The name of the Contact Field.
	 * @return array $fields The array of Field names.
	 */
	public function get_acf_type( $name = '' ) {

		// Init return.
		$type = false;

		// Combine different arrays.
		$contact_fields = $this->contact_fields_individual +
			$this->contact_fields_organization +
			$this->contact_fields_household +
			$this->contact_fields_common;

		// if the key exists, return the value - which is the ACF Type.
		if ( array_key_exists( $name, $contact_fields ) ) {
			$type = $contact_fields[ $name ];
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

		// Get the mapped Contact Field name if present.
		$contact_field_name = $this->civicrm->contact->contact_field_name_get( $field );
		if ( $contact_field_name === false ) {
			return $field;
		}

		// Get keyed array of options for this Contact Field.
		$field['choices'] = $this->options_get( $contact_field_name );

		// All are optional.
		$field['allow_null'] = 1;

		// Set default "Communication Style".
		if ( $contact_field_name == 'communication_style_id' ) {
			$field['default_value'] = $this->civicrm->option_value_default_get( 'communication_style' );
		}

		// --<
		return $field;

	}

	/**
	 * Modify the Settings of an ACF "Checkbox" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function checkbox_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Contact Field name if present.
		$contact_field_name = $this->civicrm->contact->contact_field_name_get( $field );
		if ( $contact_field_name === false ) {
			return $field;
		}

		// Get keyed array of options for this Contact Field.
		$field['choices'] = $this->options_get( $contact_field_name );

		// All are optional.
		$field['allow_null'] = 1;

		// --<
		return $field;

	}

	/**
	 * Modify the Settings of an ACF "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function radio_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'radio' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Contact Field name if present.
		$contact_field_name = $this->civicrm->contact->contact_field_name_get( $field );
		if ( $contact_field_name === false ) {
			return $field;
		}

		// Get keyed array of options for this Contact Field.
		$field['choices'] = $this->options_get( $contact_field_name );

		// All are optional.
		$field['allow_null'] = 1;

		// --<
		return $field;

	}

	/**
	 * Modify the Settings of an ACF "Date Picker" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function date_picker_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'date_picker' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Contact Field name if present.
		$contact_field_name = $this->civicrm->contact->contact_field_name_get( $field );
		if ( $contact_field_name === false ) {
			return $field;
		}

		// Get Contact Field data.
		$format = $this->date_format_get( $contact_field_name );

		// Get the ACF format.
		$acf_format = $this->acf_loader->mapper->date_mappings[ $format ];

		// Set the date "format" attributes.
		$field['display_format'] = $acf_format;
		$field['return_format'] = $acf_format;

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

		// Get the mapped Contact Field name if present.
		$contact_field_name = $this->civicrm->contact->contact_field_name_get( $field );
		if ( $contact_field_name === false ) {
			return $field;
		}

		// Get Contact Field data.
		$field_data = $this->plugin->civicrm->contact_field->get_by_name( $contact_field_name );

		// Set the "maxlength" attribute.
		if ( ! empty( $field_data['maxlength'] ) ) {
			$field['maxlength'] = $field_data['maxlength'];
		}

		// --<
		return $field;

	}

	/**
	 * Modify the Settings of an "Image" Field as required by a Contact Field.
	 *
	 * The only modification at the moment is to "derestrict" the library that
	 * the Field can access. This is done so that multiple Posts can share the
	 * same Attachment - useful for situations where a Contact has multiple
	 * Contact Types that are mapped to Custom Post Types.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function image_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'image' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Contact Field name if present.
		$contact_field_name = $this->civicrm->contact->contact_field_name_get( $field );
		if ( $contact_field_name === false ) {
			return $field;
		}

		// Set Field source library.
		$field['library'] = 'all';

		// --<
		return $field;

	}

	/**
	 * Get the value of an "Image" Field as required by an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param mixed  $value The Contact Field value (the Image URL).
	 * @param array  $name The Contact Field name.
	 * @param string $selector The ACF Field selector.
	 * @param mixed  $post_id The ACF "Post ID".
	 * @return mixed $value The WordPress Attachment ID, or empty on failure.
	 */
	public function image_value_get_for_acf( $value, $name, $selector, $post_id ) {

		// Grab the raw data (Attachment ID) from the ACF Field.
		$existing = get_field( $selector, $post_id, false );

		// Assume no sync necessary.
		$sync = false;

		// If there's no ACF data.
		if ( empty( $existing ) ) {

			// We're good to sync.
			$sync = true;

		} else {

			// Grab the the full size Image data.
			$full_size = wp_get_attachment_image_url( (int) $existing, 'full' );

			// If the URL has changed.
			if ( ! empty( $full_size ) && $full_size != $value ) {

				// Sync the new image.
				$sync = true;

			} else {

				// The ID is the existing value.
				$attachment_id = (int) $existing;

			}

		}

		// Bail if no sync is necessary.
		if ( $sync === false ) {
			return $attachment_id;
		}

		// Get Contact ID for this ACF "Post ID".
		$contact_id = $this->acf_loader->acf->field->query_contact_id( $post_id );

		// Can't proceed if there's no Contact ID.
		if ( $contact_id === false ) {
			return '';
		}

		// Get full Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );

		/*
		 * Decode the current Image URL.
		 *
		 * We have to do this because Contact Images may have been uploaded
		 * from a Profile embedded via a Shortcode. Since CiviCRM always runs
		 * Contact Image URLs through htmlentities() before saving, the URLs
		 * get "double-encoded" when they are parsed by `redirect_canonical()`
		 * and result in 404s.
		 *
		 * This is only a problem when using Profiles via Shortcodes.
		 *
		 * @see CRM_Contact_BAO_Contact::processImageParams()
		 */
		$url = html_entity_decode( $contact['image_URL'] );

		// Maybe fix the following function.
		add_filter( 'attachment_url_to_postid', [ $this, 'image_url_to_post_id_helper' ], 10, 2 );

		// First check for an existing Attachment ID.
		$possible_id = attachment_url_to_postid( $url );

		// Remove the fix.
		remove_filter( 'attachment_url_to_postid', [ $this, 'image_url_to_post_id_helper' ], 10 );

		// Return early if we find an existing Attachment ID.
		if ( ! empty( $possible_id ) ) {
			$value = (int) $possible_id;
			return $value;
		}

		// Bail if we can't extract the filename.
		if ( false === strpos( $url, 'photo=' ) ) {
			return '';
		}

		// Grab the filename.
		$filename = explode( 'photo=', $url )[1];

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		// Copy the File for WordPress to move.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$tmp_name = $this->civicrm->attachment->file_copy_for_acf( $config->customFileUploadDir . $filename );

		// Find the name of the new File.
		$name = pathinfo( $tmp_name, PATHINFO_BASENAME );

		// Find the mime type of the File.
		$mime_type = wp_check_filetype( $tmp_name );

		// Find the filesize in bytes.
		$size = filesize( $tmp_name );

		/*
		 * Normally this is used to store an error should the upload fail.
		 * Since we aren't actually building an instance of $_FILES, we can
		 * default to zero instead.
		 */
		$error = 0;

		// Create an array that mimics $_FILES.
		$files = [
			'name' => $name,
			'type' => $mime_type,
			'tmp_name' => $tmp_name,
			'error' => $error,
			'size' => $size,
		];

		// Only assign to a Post if the ACF "Post ID" is numeric.
		if ( ! is_numeric( $post_id ) ) {
			$target_post_id = null;
		} else {
			$target_post_id = $post_id;
		}

		// Possibly include the required files.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Transfer the CiviCRM File to WordPress and grab ID.
		$attachment_id = media_handle_sideload( $files, $target_post_id );

		// Handle sideload errors.
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $files['tmp_name'] );
			return '';
		}

		// Grab the the full size Image data.
		$url = wp_get_attachment_image_url( (int) $attachment_id, 'full' );

		// Remove all internal CiviCRM hooks.
		$this->acf_loader->mapper->hooks_civicrm_remove();

		/**
		 * Broadcast that we're about to reverse-sync to a Contact.
		 *
		 * @since 0.4
		 *
		 * @param $contact_data The array of Contact data.
		 */
		do_action( 'cwps/acf/contact_field/reverse_sync/pre' );

		// Bare-bones data.
		$contact_data = [
			'id' => $contact_id,
			'image_URL' => $url,
		];

		// Save the Attachment URL back to the Contact.
		$result = $this->plugin->civicrm->contact->update( $contact_data );

		/**
		 * Broadcast that we have reverse-synced to a Contact.
		 *
		 * @since 0.4
		 *
		 * @param $contact_data The array of Contact data.
		 */
		do_action( 'cwps/acf/contact_field/reverse_sync/post', $contact_data );

		// Restore all internal CiviCRM hooks.
		$this->acf_loader->mapper->hooks_civicrm_add();

		// --<
		return $attachment_id;

	}

	/**
	 * Tries to convert an Attachment URL (for intermediate/edited sized image) into a Post ID.
	 *
	 * Formatted version of the following Gist:
	 *
	 * @see https://gist.github.com/pbiron/d72a5d3b63e7077df767735464b2769c
	 *
	 * Produces incorrect results with the following sequence prior to WordPress 5.3.1:
	 *
	 * 1) Set thumbnail site to 150x150;
	 * 2) Upload foo-150x150.jpg;
	 * 3) Upload foo.jpg;
	 * 4) Call attachment_url_to_post_id( 'https://host/wp-content/uploads/foo-150x150.jpg' )
	 *
	 * @see https://core.trac.wordpress.org/ticket/44095
	 *
	 * Produces incorrect results after the following sequence:
	 *
	 * 1) Set thumbnail site to 150x150;
	 * 2) Upload a 300x300 image foo.jpg;
	 * 3) Edit foo.jpg and scale to 200x200;
	 * 4) Regenerate intermediate sized images
	 *    (e.g. with https://wordpress.org/plugins/regenerate-thumbnails/)
	 * 5) Call attachment_url_to_post_id( 'https://host/wp-content/uploads/foo-150x150.jpg' )
	 *
	 * @see https://core.trac.wordpress.org/ticket/44127
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @param string  $url The URL to resolve.
	 * @return integer The found Post ID, or 0 on failure.
	 */
	public function image_url_to_post_id_helper( $post_id, $url ) {

		global $wpdb;

		// Bail if a Post ID was found.
		if ( $post_id ) {
			return $post_id;
		}

		// Start by setting up a few vars the same way attachment_url_to_postid() does.
		$dir = wp_get_upload_dir();
		$path = $url;

		$site_url = wp_parse_url( $dir['url'] );
		$image_path = wp_parse_url( $path );

		// Force the protocols to match if needed.
		if ( isset( $image_path['scheme'] ) && ( $image_path['scheme'] !== $site_url['scheme'] ) ) {
			$path = str_replace( $image_path['scheme'], $site_url['scheme'], $path );
		}

		if ( 0 === strpos( $path, $dir['baseurl'] . '/' ) ) {
			$path = substr( $path, strlen( $dir['baseurl'] . '/' ) );
		}

		$basename = wp_basename( $path );
		$dirname = dirname( $path );

		/*
		 * The "LIKE" we search for is the serialized form of $basename to reduce
		 * the number of false positives we have to deal with.
		 */
		$sql = $wpdb->prepare(
			"SELECT post_id, meta_key, meta_value FROM $wpdb->postmeta
			 WHERE meta_key IN ( '_wp_attachment_metadata', '_wp_attachment_backup_sizes' ) AND meta_value LIKE %s",
			'%' . serialize( $basename ) . '%'
		);

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $sql );
		foreach ( $results as $row ) {

			if ( '_wp_attachment_metadata' === $row->meta_key ) {

				$meta = maybe_unserialize( $row->meta_value );
				if ( dirname( $meta['file'] ) === $dirname && in_array( $basename, wp_list_pluck( $meta['sizes'], 'file' ) ) ) {
					// URL is for a registered intermediate size.
					$post_id = $row->post_id;
					break;
				}

			} else {

				// See if URL is for a "backup" of an edited image.
				$backup_sizes = maybe_unserialize( $row->meta_value );

				if ( in_array( $basename, wp_list_pluck( $backup_sizes, 'file' ) ) ) {

					/*
					 * URL is possibly for a "backup" of an edited image.
					 * get the meta for the "original" attachment and perform the equivalent
					 * test we did above for '_wp_attachment_metadata' === $row->meta_key
					 */
					$sql = $wpdb->prepare(
						"SELECT meta_value FROM $wpdb->postmeta
						 WHERE post_id = %d AND meta_key = '_wp_attachment_metadata'",
						$row->post_id
					);

					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$meta = maybe_unserialize( $wpdb->get_var( $sql ) );
					if ( isset( $meta['file'] ) && dirname( $meta['file'] ) === $dirname ) {
						// URL is for a "backup" of an edited image.
						$post_id = $row->post_id;
						break;
					}

				}

			}

		}

		// --<
		return $post_id;

	}

	/**
	 * Callback for the Contact postSave hook.
	 *
	 * Since neither "civicrm_pre" nor "civicrm_post" fire when a Contact Image
	 * is deleted via the "Edit Contact" screen, this callback attempts to
	 * identify when this happens and then acts accordingly.
	 *
	 * @since 0.4
	 *
	 * @param object $objectRef The DAO object.
	 */
	public function image_deleted( $objectRef ) {

		// Bail if not Contact save operation.
		if ( ! ( $objectRef instanceof CRM_Contact_BAO_Contact ) ) {
			return;
		}

		// Bail if no Contact ID.
		if ( empty( $objectRef->id ) ) {
			return;
		}

		// Bail if image_URL isn't the string 'null'.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		if ( $objectRef->image_URL !== 'null' ) {
			return;
		}

		// Bail if GET doesn't contain the path we want.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['q'] ) || $_GET['q'] !== 'civicrm/contact/image' ) {
			return;
		}

		// Bail if GET doesn't contain the matching Contact ID.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['cid'] ) || $_GET['cid'] !== $objectRef->id ) {
			return;
		}

		// Bail if GET doesn't contain the delete action.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['action'] ) || $_GET['action'] !== 'delete' ) {
			return;
		}

		// Bail if GET doesn't contain the confirmed flag.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['confirmed'] ) || $_GET['confirmed'] != 1 ) {
			return;
		}

		// Get the full Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $objectRef->id );

		// Bail if something went wrong.
		if ( $contact === false ) {
			return;
		}

		// We need to pass an instance of CRM_Contact_DAO_Contact.
		$object = new CRM_Contact_DAO_Contact();
		$object->id = $objectRef->id;

		// Trigger the sync process via the Mapper.
		$this->acf_loader->mapper->contact_edited( 'edit', $contact['contact_type'], $objectRef->id, $object );

	}

	/**
	 * Fires just before an Attachment is deleted.
	 *
	 * ACF Image Fields store the Attachment ID, so when an Attachment is deleted
	 * (and depending on the return format) nothing bad happens. CiviCRM Contact
	 * Images are stored as URLs - so when the actual file is missing, we get a
	 * 404 and a broken image icon.
	 *
	 * This callback tries to mitigate this by searching for Contacts that have
	 * the Contact Image that's being deleted and triggers the sync process for
	 * those that are found by deleting their Image URL.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the Attachment.
	 */
	public function image_attachment_deleted( $post_id ) {

		// Grab the the full size Image URL.
		$image_url = wp_get_attachment_image_url( $post_id, 'full' );

		// Bail if the Image URL is empty.
		if ( empty( $image_url ) ) {
			return;
		}

		// Search for Contacts.
		$contacts = $this->civicrm->contact->get_by_image( $image_url );

		// Bail if there aren't any.
		if ( empty( $contacts ) ) {
			return;
		}

		// Process all of them.
		foreach ( $contacts as $contact ) {

			// Bare-bones data.
			$contact_data = [
				'id' => $contact['contact_id'],
				'image_URL' => '',
			];

			// Clear the Image URL for the Contact.
			$result = $this->plugin->civicrm->contact->update( $contact_data );

		}

	}

}
