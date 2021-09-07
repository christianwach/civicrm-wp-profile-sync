<?php
/**
 * ACF "CiviCRM Contact ID Field" Class.
 *
 * Provides a "CiviCRM Contact ID Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Contact ID Field.
 *
 * A class that encapsulates a "CiviCRM Contact ID Field" Custom ACF Field in ACF 5+.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_ID_Field extends acf_field {

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
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $name The Field Type name.
	 */
	public $name = 'civicrm_contact_id';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a field type.
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
	 * Array of default settings which are merged into the field object.
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

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to parent.
		$this->acf = $parent->acf;

		// Define label.
		$this->label = __( 'CiviCRM Contact ID (Read Only)', 'civicrm-wp-profile-sync' );

		// Define category.
		$this->category = __( 'CiviCRM Post Type Sync', 'civicrm-wp-profile-sync' );

		// Define translations.
		$this->l10n = [];

		// Call parent.
		parent::__construct();

	}



	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a simple number field.
		$field['type'] = 'number';
		$field['readonly'] = 1;
		$field['allow_null'] = 0;
		$field['prepend'] = '';
		$field['append'] = '';
		$field['step'] = '';

		// Populate field.
		if ( ! empty( $field['value'] ) ) {

			// Cast value to an integer.
			$contact_id = (int) $field['value'];

			// Apply Contact ID to field.
			$field['value'] = $contact_id;

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
	 * @param array $field The field array holding all the field options.
	 * @return mixed $value The modified value.
	 */
	public function load_value( $value, $post_id, $field ) {

		// Assign Contact ID for this Field if empty.
		if ( empty( $value ) ) {

			// Get Contact ID for this ACF "Post ID".
			$contact_id = $this->acf->field->query_contact_id( $post_id );

			// Overwrite if we get a value.
			if ( $contact_id !== false ) {
				$value = $contact_id;
			}

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
	 * @param array $field The field array holding all the field options.
	 * @return mixed $value The modified value.
	 */
	public function update_value( $value, $post_id, $field ) {

		// Assign Contact ID for this Field if empty.
		if ( empty( $value ) ) {

			// Get Contact ID for this ACF "Post ID".
			$contact_id = $this->acf->field->query_contact_id( $post_id );

			// Overwrite if we get a value.
			if ( $contact_id !== false ) {
				$value = $contact_id;
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
	 * @param array $field The field array holding all the field options.
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
	 * All values are validated regardless of the field's required setting.
	 * This allows you to validate and return messages to the user if the value
	 * is not correct.
	 *
	 * @since 0.4
	 *
	 * @param bool $valid The validation status based on the value and the field's required setting.
	 * @param mixed $value The $_POST value.
	 * @param array $field The field array holding all the field options.
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
	 * @param array $field The field array holding all the field options.
	 * @return array $field The modified field data.
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
	 * @param array $field The field array holding all the field options.
	 * @return array $field The modified field data.
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
	 * @param array $field The field array holding all the field options.
	public function delete_field( $field ) {

	}
	 */



} // Class ends.



