<?php
/**
 * ACF "CiviCRM Phone: Single" Field Class.
 *
 * Provides a "CiviCRM Phone: Single" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.6.9
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync Custom ACF Field Type - "CiviCRM Phone: Single" Field.
 *
 * A class that encapsulates a "CiviCRM Phone: Single" Custom ACF Field in ACF 5+.
 *
 * @since 0.6.9
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Phone_Single extends acf_field {

	/**
	 * Plugin object.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var string
	 */
	public $name = 'civicrm_phone_single';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var string
	 */
	public $label = '';

	/**
	 * Field Type category.
	 *
	 * Choose between the following categories:
	 *
	 * basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
	 *
	 * @since 0.6.9
	 * @access public
	 * @var string
	 */
	public $category = 'CiviCRM';

	/**
	 * Field Type defaults.
	 *
	 * Array of default settings which are merged into the Field object.
	 * These are used later in settings.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var array
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var array
	 */
	public $settings = [
		'version' => CIVICRM_WP_PROFILE_SYNC_VERSION,
		'url'     => CIVICRM_WP_PROFILE_SYNC_URL,
		'path'    => CIVICRM_WP_PROFILE_SYNC_PATH,
	];

	/**
	 * Field Type translations.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Array of strings that are used in JavaScript. This allows JS strings
	 * to be translated in PHP and loaded via:
	 *
	 * var message = acf._e( 'civicrm_contact', 'error' );
	 *
	 * @since 0.6.9
	 * @access public
	 * @var array
	 */
	public $l10n = [];

	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.6.9
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf        = $parent->acf;
		$this->civicrm    = $this->acf_loader->civicrm;

		// Define label.
		$this->label = __( 'CiviCRM Phone: Single', 'civicrm-wp-profile-sync' );

		// Define category.
		if ( function_exists( 'acfe' ) ) {
			$this->category = __( 'CiviCRM Post Type Sync only', 'civicrm-wp-profile-sync' );
		} else {
			$this->category = __( 'CiviCRM Post Type Sync', 'civicrm-wp-profile-sync' );
		}

		// Define translations.
		$this->l10n = [];

		// Call parent.
		parent::__construct();

	}

	/**
	 * Create extra Settings for this Field Type.
	 *
	 * These extra Settings will be visible when editing a Field.
	 *
	 * @since 0.6.9
	 *
	 * @param array $field The Field being edited.
	 */
	public function render_field_settings( $field ) {

		// Define Primary setting Field.
		$primary = [
			'label'         => __( 'CiviCRM Primary Phone', 'civicrm-wp-profile-sync' ),
			'name'          => 'phone_is_primary',
			'type'          => 'true_false',
			'instructions'  => __( 'Sync with the CiviCRM Primary Phone.', 'civicrm-wp-profile-sync' ),
			'ui'            => 0,
			'default_value' => 0,
			'required'      => 0,
		];

		// Now add it.
		acf_render_field_setting( $field, $primary );

		// Phone Locations are standard Location Types.
		$location_types = $this->plugin->civicrm->address->location_types_get();

		// Build Location Types choices array for dropdown.
		$choices = [];
		foreach ( $location_types as $location_type ) {
			$choices[ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Define Location Type setting Field.
		$location_field = [
			'label'             => __( 'CiviCRM Location Type', 'civicrm-wp-profile-sync' ),
			'name'              => 'phone_location_type_id',
			'type'              => 'select',
			'instructions'      => __( 'Choose the Location Type of the CiviCRM Phone that this ACF Field should sync with.', 'civicrm-wp-profile-sync' ),
			'default_value'     => '',
			'placeholder'       => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'required'          => 0,
			'return_format'     => 'value',
			'choices'           => $choices,
			'conditional_logic' => [
				[
					[
						'field'    => 'phone_is_primary',
						'operator' => '==',
						'value'    => 0,
					],
				],
			],
		];

		// Now add it.
		acf_render_field_setting( $field, $location_field );

		// Phone Types are unique to the Phone Entity.
		$phone_types = $this->plugin->civicrm->phone->phone_types_get();

		// Define Location Type setting Field.
		$phone_field = [
			'label'             => __( 'CiviCRM Phone Type', 'civicrm-wp-profile-sync' ),
			'name'              => 'phone_type_id',
			'type'              => 'select',
			'instructions'      => __( 'Choose the Phone Type of the CiviCRM Phone that this ACF Field should sync with.', 'civicrm-wp-profile-sync' ),
			'default_value'     => '',
			'placeholder'       => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'required'          => 0,
			'return_format'     => 'value',
			'choices'           => $phone_types,
			'conditional_logic' => [
				[
					[
						'field'    => 'phone_is_primary',
						'operator' => '==',
						'value'    => 0,
					],
				],
			],
		];

		// Now add it.
		acf_render_field_setting( $field, $phone_field );

	}

	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.6.9
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a simple text Field.
		$field['type']       = 'text';
		$field['prepend']    = '';
		$field['append']     = '';
		$field['step']       = '';

		// Populate Field.
		if ( ! empty( $field['value'] ) ) {

			// Ensure value is cast as a string.
			$phone = (string) $field['value'];

			// Apply Phone to Field.
			$field['value'] = $phone;

		}

		// Render.
		acf_render_field( $field );

	}

	/**
	 * Prepare this Field Type for display.
	 *
	 * @since 0.6.9
	 *
	 * @param array $field The Field being rendered.
	 */
	public function prepare_field( $field ) {

		// --<
		return $field;

	}

	/**
	 * This filter is applied to the $value after it is loaded from the database.
	 *
	 * @since 0.6.9
	 *
	 * @param mixed   $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array   $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function load_value( $value, $post_id, $field ) {

// 		// Assign Phone for this Field if empty.
// 		if ( empty( $value ) ) {
// 			$value = $this->get_phone( $value, $post_id, $field );
// 		}

		// --<
		return $value;

	}

	/**
	 * This filter is applied to the $value before it is saved in the database.
	 *
	 * @since 0.6.9
	 *
	 * @param mixed   $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array   $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function update_value( $value, $post_id, $field ) {

		// Make sure we have a string.
		if ( empty( $value ) ) {
			$value = '';
		}

		// --<
		return $value;

	}

	/**
	 * This filter is applied to the value after it is loaded from the database
	 * and before it is returned to the template.
	 *
	 * @since 0.6.9
	 *
	 * @param mixed $value The value which was loaded from the database.
	 * @param mixed $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	public function format_value( $value, $post_id, $field ) {

		// Bail early if no value.
		if ( empty( $value ) ) {
			return $value;
		}

		// Apply setting.
		if ( $field['font_size'] > 12 ) {

			// format the value
			// $value = 'something';

		}

		// --<
		return $value;

	}
	 */

	/**
	 * This filter is used to perform validation on the value prior to saving.
	 *
	 * All values are validated regardless of the Field's required setting.
	 * This allows you to validate and return messages to the user if the value
	 * is not correct.
	 *
	 * @since 0.6.9
	 *
	 * @param bool   $valid The validation status based on the value and the Field's required setting.
	 * @param mixed  $value The $_POST value.
	 * @param array  $field The Field array holding all the Field options.
	 * @param string $input The corresponding input name for $_POST value.
	 * @return string|bool $valid False if not valid, or string for error message.
	 */
	public function validate_value( $valid, $value, $field, $input ) {

		// Bail if it's not required and is empty.
		if ( 0 === (int) $field['required'] && empty( $value ) ) {
			return $valid;
		}

// 		// Grab just the Primary values.
// 		$primary_values = wp_list_pluck( $value, 'field_phone_primary' );
//
// 		// Sanitise array contents.
// 		array_walk(
// 			$primary_values,
// 			function( &$item ) {
// 				$item = (int) trim( $item );
// 			}
// 		);
//
// 		// Check that we have a Primary Number.
// 		if ( ! in_array( 1, $primary_values, true ) ) {
// 			$valid = __( 'Please select a Primary Number', 'civicrm-wp-profile-sync' );
// 			return $valid;
// 		}
//
// 		// Grab just the Phone Numbers.
// 		$phones = wp_list_pluck( $value, 'field_phone_number' );
//
// 		// Sanitise array contents.
// 		array_walk(
// 			$phones,
// 			function( &$item ) {
// 				$item = (string) trim( $item );
// 			}
// 		);
//
// 		// Check that all Number Fields are populated.
// 		if ( in_array( '', $phones, true ) ) {
// 			$valid = __( 'Please enter a Phone Number', 'civicrm-wp-profile-sync' );
// 			return $valid;
// 		}

		// --<
		return $valid;

	}

	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.6.9
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function load_field( $field ) {

// 		// Init Subfields.
// 		$sub_fields = [];
//
// 		// Maybe append to Field.
// 		if ( ! empty( $field['sub_fields'] ) ) {
//
// 			// Validate Field first.
// 			foreach ( $field['sub_fields'] as $sub_field ) {
// 				$sub_fields[] = acf_validate_field( $sub_field );
// 			}
//
// 		}
//
// 		// Overwrite subfields.
// 		$field['sub_fields'] = $sub_fields;

		// --<
		return $field;

	}

	/**
	 * This filter is applied to the Field before it is saved to the database.
	 *
	 * @since 0.6.9
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function update_field( $field ) {

		// --<
		return $field;

	}

}
