<?php
/**
 * ACFE "CiviCRM Country Field" Class.
 *
 * Provides a "CiviCRM Country Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Country Field.
 *
 * A class that encapsulates a "CiviCRM Country Field" Custom ACF Field in ACF 5+.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_Country extends acf_field {

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
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The Advanced Custom Fields object.
	 */
	public $acf;

	/**
	 * ACF Extended object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The Advanced Custom Fields object.
	 */
	public $acfe;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $name The Field Type name.
	 */
	public $name = 'cwps_acfe_address_country';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.5
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
	 * @since 0.5
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
	 * @since 0.5
	 * @access public
	 * @var array $defaults The Field Type defaults.
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.5
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
	 * @since 0.5
	 * @access public
	 * @var array $l10n The Field Type translations.
	 */
	public $l10n = [];



	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf = $parent->acf_loader->acf;
		$this->acfe = $parent;
		$this->civicrm = $this->acf_loader->civicrm;

		// Define label.
		$this->label = __( 'CiviCRM Address: Country', 'civicrm-wp-profile-sync' );

		// Define category.
		$this->category = __( 'CiviCRM ACFE Forms only', 'civicrm-wp-profile-sync' );

		// Define translations.
		$this->l10n = [];

		// Call parent.
		parent::__construct();

	}



	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a "select" Field.
		$field['type'] = 'select';

		// Render.
		acf_render_field( $field );

	}



	/**
	 * This filter is applied to the $value after it is loaded from the database.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The value found in the database.
	 * @param integer|string $post_id The ACF "Post ID" from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	public function load_value( $value, $post_id, $field ) {

		// Assign Country for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_country( $value, $post_id, $field );
		}

		// --<
		return $value;

	}
	 */



	/**
	 * This filter is applied to the $value before it is saved in the database.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	public function update_value( $value, $post_id, $field ) {

		// Assign Country for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_country( $value, $post_id, $field );
		}

		// --<
		return $value;

	}
	 */



	/**
	 * This filter is applied to the value after it is loaded from the database
	 * and before it is returned to the template.
	 *
	 * @since 0.5
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
	 * @since 0.5
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
	 * @since 0.5
	 *
	 * @param integer $post_id The Post ID from which the value was deleted.
	 * @param string $key The meta key which the value was deleted.
	public function delete_value( $post_id, $key ) {

	}
	 */



	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function load_field( $field ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		$field['allow_null'] = 1;
		$field['multiple'] = 0;
		$field['ui'] = 1;
		$field['ajax'] = 0;
		$field['return_format'] = 'value';
		$field['choices'] = CRM_Core_PseudoConstant::country();
		$field['default_value'] = $config->defaultContactCountry;

		// --<
		return $field;

	}



	/**
	 * This filter is applied to the Field before it is saved to the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	public function update_field( $field ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		$field['allow_null'] = 0;
		$field['multiple'] = 0;
		$field['ui'] = 1;
		$field['ajax'] = 0;
		$field['return_format'] = 'value';
		$field['choices'] = CRM_Core_PseudoConstant::country();
		$field['default_value'] = $config->defaultContactCountry;

		// --<
		return $field;

	}
	 */



	/**
	 * This action is fired after a Field is deleted from the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	public function delete_field( $field ) {

	}
	 */



	/**
	 * This method is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.5
	 */
	public function input_admin_enqueue_scripts() {

		// Enqueue our JavaScript.
		wp_enqueue_script(
			'acf-input-' . $this->name,
			plugins_url( 'assets/js/acf/acfe/fields/civicrm-address-country-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION // Version.
		);

	}



} // Class ends.



