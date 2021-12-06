<?php
/**
 * ACF "CiviCRM City Field" Class.
 *
 * Provides a "CiviCRM City Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM City Field.
 *
 * A class that encapsulates a "CiviCRM City Field" Custom ACF Field in ACF 5+.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Address_City_Field extends acf_field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $cpt The Advanced Custom Fields object.
	 */
	public $acf;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $name The Field Type name.
	 */
	public $name = 'civicrm_address_city';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $label The Field Type label.
	 */
	public $label = '';

	/**
	 * Field Type category.
	 *
	 * Choose between the following categories:
	 *
	 * basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
	 *
	 * @since 0.4
	 * @access public
	 * @var string $label The Field Type category.
	 */
	public $category = 'CiviCRM';

	/**
	 * Field Type defaults.
	 *
	 * Array of default settings which are merged into the Field object.
	 * These are used later in settings.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $defaults The Field Type defaults.
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $settings The Field Type settings.
	 */
	public $settings = [
		'version' => CIVICRM_WP_PROFILE_SYNC_VERSION,
		'url' => CIVICRM_WP_PROFILE_SYNC_URL,
		'path' => CIVICRM_WP_PROFILE_SYNC_PATH,
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
	 * @since 0.4
	 * @access public
	 * @var array $l10n The Field Type translations.
	 */
	public $l10n = [];



	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf = $parent->acf;
		$this->civicrm = $this->acf_loader->civicrm;

		// Define label.
		$this->label = __( 'CiviCRM Address: City (Read Only)', 'civicrm-wp-profile-sync' );

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
	 * @since 0.4
	 *
	 * @param array $field The Field being edited.
	 */
	public function render_field_settings( $field ) {

		// Get Locations.
		$location_types = $this->plugin->civicrm->address->location_types_get();

		// Init choices.
		$choices = [];

		// Build Location Types choices array for dropdown.
		foreach ( $location_types as $location_type ) {
			$choices[$location_type['id']] = esc_attr( $location_type['display_name'] );
		}

		// Define Primary setting Field.
		$primary = [
			'label' => __( 'CiviCRM Primary Address', 'civicrm-wp-profile-sync' ),
			'name' => 'city_is_primary',
			'type' => 'true_false',
			'instructions' => __( 'Sync with the CiviCRM Primary Address.', 'civicrm-wp-profile-sync' ),
			'ui' => 0,
			'default_value' => 0,
			'required' => 0,
		];

		// Now add it.
		acf_render_field_setting( $field, $primary );

		// Define Location Type setting Field.
		$type = [
			'label' => __( 'CiviCRM Location Type', 'civicrm-wp-profile-sync' ),
			'name' => 'city_location_type_id',
			'type' => 'select',
			'instructions' => __( 'Choose the Location Type of the CiviCRM Address that this ACF Field should sync with.', 'civicrm-wp-profile-sync' ),
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'required' => 0,
			'return_format' => 'value',
			'conditional_logic' => [
				[
					[
						'field' => 'city_is_primary',
						'operator' => '==',
						'value' => 0,
					],
				],
			],
			'choices' => $choices,
		];

		// Now add it.
		acf_render_field_setting( $field, $type );

	}



	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a simple text Field.
		$field['type'] = 'text';
		$field['readonly'] = 1;
		$field['allow_null'] = 0;
		$field['prepend'] = '';
		$field['append'] = '';
		$field['step'] = '';

		// Populate Field.
		if ( ! empty( $field['value'] ) ) {

			// Ensure value is cast as a string.
			$city = (string) $field['value'];

			// Apply City to Field.
			$field['value'] = $city;

		}

		// Render.
		acf_render_field( $field );

	}



	/**
	 * This filter is applied to the $value after it is loaded from the database.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The value found in the database.
	 * @param integer|string $post_id The ACF "Post ID" from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function load_value( $value, $post_id, $field ) {

		// Assign City for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_city( $value, $post_id, $field );
		}

		// --<
		return $value;

	}



	/**
	 * This filter is applied to the $value before it is saved in the database.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function update_value( $value, $post_id, $field ) {

		// Assign City for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_city( $value, $post_id, $field );
		}

		// --<
		return $value;

	}



	/**
	 * Get the City for this Contact.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function get_city( $value, $post_id, $field ) {

		// Get Contact ID for this ACF "Post ID".
		$contact_id = $this->acf->field->query_contact_id( $post_id );

		// Overwrite if we get a value.
		if ( $contact_id !== false ) {

			// Get this Contact's Addresses.
			$addresses = $this->plugin->civicrm->address->addresses_get_by_contact_id( $contact_id );

			// Init City.
			$city = false;

			// Does this Field sync with the Primary Address?
			if ( ! empty( $field['city_is_primary'] ) ) {

				// Assign City from the Primary Address.
				foreach ( $addresses as $address ) {
					if ( ! empty( $address->is_primary ) ) {
						$city = $address->city;
						break;
					}
				}

			// We need a Location Type.
			} elseif ( ! empty( $field['city_location_type_id'] ) ) {

				// Assign City from the type of Address.
				foreach ( $addresses as $address ) {
					if ( $address->location_type_id == $field['city_location_type_id'] ) {
						$city = $address->city;
						break;
					}
				}

			}

			// Overwrite if we get a value.
			if ( $city !== false ) {
				$value = $city;
			}

		}

		// --<
		return $value;

	}



	/**
	 * This filter is applied to the value after it is loaded from the database
	 * and before it is returned to the template.
	 *
	 * @since 0.4
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
	 * @since 0.4
	 *
	 * @param bool $valid The validation status based on the value and the Field's required setting.
	 * @param mixed $value The $_POST value.
	 * @param array $field The Field array holding all the Field options.
	 * @param string $input The corresponding input name for $_POST value.
	 * @return string|bool $valid False if not valid, or string for error message.
	public function validate_value( $valid, $value, $field, $input ) {

		// Basic usage.
		if ( $value < $field['custom_minimum_setting'] ) {
			$valid = false;
		}

		// Advanced usage.
		if ( $value < $field['custom_minimum_setting'] ) {
			$valid = __( 'The value is too little!', 'civicrm-wp-profile-sync' ),
		}

		// --<
		return $valid;

	}
	 */



	/**
	 * This action is fired after a value has been deleted from the database.
	 *
	 * Please note that saving a blank value is treated as an update, not a delete.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The Post ID from which the value was deleted.
	 * @param string $key The meta key which the value was deleted.
	public function delete_value( $post_id, $key ) {

	}
	 */



	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	public function load_field( $field ) {

		// --<
		return $field;

	}
	 */



	/**
	 * This filter is applied to the Field before it is saved to the database.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	public function update_field( $field ) {

		// --<
		return $field;

	}
	 */



	/**
	 * This action is fired after a Field is deleted from the database.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field array holding all the Field options.
	public function delete_field( $field ) {

	}
	 */



} // Class ends.



