<?php
/**
 * CiviCRM Address Class.
 *
 * Handles CiviCRM Address functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Address Class.
 *
 * A class that encapsulates CiviCRM Address functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Address {

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
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Address Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $address_field_prefix = 'caiaddress_';

	/**
	 * Public Address Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $address_fields = [
		'is_primary'             => 'true_false',
		'is_billing'             => 'true_false',
		'address_name'           => 'text',
		'street_address'         => 'text',
		'supplemental_address_1' => 'text',
		'supplemental_address_2' => 'text',
		'supplemental_address_3' => 'text',
		'city'                   => 'text',
		'county_id'              => 'select',
		'state_province_id'      => 'select',
		'country_id'             => 'select',
		'postal_code'            => 'text',
		// 'postal_code_suffix' => 'text',
		'geo_code_1'             => 'text',
		'geo_code_2'             => 'text',
		'name'                   => 'text',
	];

	/**
	 * Address Fields to remove for the Bypass Location Rule.
	 *
	 * These are mapped for Post Type Sync, but need to be removed for the
	 * Bypass Location Rule because they are handled by custom ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $bypass_fields_to_remove = [
		'county_id'         => 'select',
		'state_province_id' => 'select',
		'country_id'        => 'select',
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
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm    = $parent;

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

		// Listen for queries from the ACF Bypass class.
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

		// Listen for queries from our ACF Field Group class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'text_settings_modify' ], 10, 2 );

		/*
		// Some Address "Text" Fields need their own validation.
		add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );
		*/

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Address Fields.
	 *
	 * @since 0.5
	 *
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function civicrm_fields_get( $filter = 'none' ) {

		// Only do this once per Field Type and filter.
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
		$result = civicrm_api( 'Address', 'getfields', $params );

		// Override return if we get some.
		if ( empty( $result['is_error'] ) && ! empty( $result['values'] ) ) {

			if ( 'none' === $filter ) {

				// Grab all Fields.
				$fields = $result['values'];

			} elseif ( 'public' === $filter ) {

				// Get the CiviCRM Address Options.
				$address_options = $this->plugin->civicrm->address->settings_get();

				// Skip all but those defined in our public Address Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->address_fields ) ) {
						// The Address Field must also be enabled in CiviCRM.
						if ( ! empty( $address_options[ $key ] ) ) {
							$fields[] = $value;
						}
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
	 * Get the mapped Address Field name if present.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing Field data array.
	 * @return string|bool $address_field_name The name of the Address Field, or false if none.
	 */
	public function address_field_name_get( $field ) {

		// Init return.
		$address_field_name = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Set the mapped Address Field name if present.
		if ( isset( $field[ $acf_field_key ] ) ) {
			if ( false !== strpos( $field[ $acf_field_key ], $this->address_field_prefix ) ) {
				$address_field_name = (string) str_replace( $this->address_field_prefix, '', $field[ $acf_field_key ] );
			}
		}

		/**
		 * Filter the Address Field name.
		 *
		 * @since 0.5
		 *
		 * @param integer $address_field_name The existing Address Field name.
		 * @param array $field The array of ACF Field data.
		 */
		$address_field_name = apply_filters( 'cwps/acf/civicrm/address/address_field/name', $address_field_name, $field );

		// --<
		return $address_field_name;

	}

	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The existing Setting Field choices array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param array $entity_array The Entity and ID array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_bypass_settings_choices( $choices, $field, $field_group, $entity_array ) {

		// Pass if a Contact Entity is not present.
		if ( ! array_key_exists( 'contact', $entity_array ) ) {
			return $choices;
		}

		// Get the public Fields on the Entity for this Field Type.
		$public_fields     = $this->civicrm_fields_get( 'public' );
		$fields_for_entity = [];
		foreach ( $public_fields as $key => $value ) {
			if ( $field['type'] == $this->address_fields[ $value['name'] ] ) {
				// Skip the ones that are not needed in ACFE Forms.
				if ( ! array_key_exists( $value['name'], $this->bypass_fields_to_remove ) ) {
					$fields_for_entity[] = $value;
				}
			}
		}

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Address', '' );

		/**
		 * Filter the Custom Fields.
		 *
		 * @since 0.5
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

		// Build Address Field choices array for dropdown.
		if ( ! empty( $fields_for_entity ) ) {
			$address_fields_label = esc_attr__( 'Address Fields', 'civicrm-wp-profile-sync' );
			foreach ( $fields_for_entity as $address_field ) {
				$choices[ $address_fields_label ][ $this->address_field_prefix . $address_field['name'] ] = $address_field['title'];
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
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/address/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

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

		// Get the mapped Address Field name if present.
		$address_field_name = $this->address_field_name_get( $field );
		if ( false === $address_field_name ) {
			return $field;
		}

		// Get Address Field data.
		$field_data = $this->plugin->civicrm->address->get_by_name( $address_field_name );

		// Set the "maxlength" attribute.
		if ( ! empty( $field_data['maxlength'] ) ) {
			$field['maxlength'] = $field_data['maxlength'];
		}

		// --<
		return $field;

	}

	/**
	 * Validate the content of a Field.
	 *
	 * Some Address Fields require validation.
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
		if ( 0 === (int) $field['required'] && empty( $value ) ) {
			return $valid;
		}

		// Get the mapped Address Field name if present.
		$address_field_name = $this->address_field_name_get( $field );
		if ( false === $address_field_name ) {
			return $valid;
		}

		// Validate depending on the Field name.
		switch ( $address_field_name ) {

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
	// Retained methods to provide backwards compatibility.
	// -------------------------------------------------------------------------

	/**
	 * Get the data for an Address.
	 *
	 * @since 0.4
	 *
	 * @param integer $address_id The numeric ID of the Address.
	 * @return object|bool $address The Address data object, or false if none.
	 */
	public function address_get_by_id( $address_id ) {
		return $this->plugin->civicrm->address->address_get_by_id( $address_id );
	}

	/**
	 * Get the Addresses for a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @return array $addresses The array of data for the Addresses, or empty if none.
	 */
	public function addresses_get_by_contact_id( $contact_id ) {
		return $this->plugin->civicrm->address->addresses_get_by_contact_id( $contact_id );
	}

}
