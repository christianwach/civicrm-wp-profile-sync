<?php
/**
 * ACF "CiviCRM Yes/No Field" Class.
 *
 * Provides a "CiviCRM Yes/No Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Yes/No Field.
 *
 * A class that encapsulates a "CiviCRM Yes/No" Custom ACF Field in ACF 5+.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Yes_No extends acf_field {

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
	 * Advanced Custom Fields object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $name = 'civicrm_yes_no';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.4
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
	 * @since 0.4
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
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $defaults = [
		'choices' => [],
		'default_value' => '2', // '1' = Yes, '0' = No.
		'allow_null' => 0,
		'return_format' => 'value',
	];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
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
	 * @var array
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
		$this->label = __( 'CiviCRM Yes / No', 'civicrm-wp-profile-sync' );

		// Define category.
		if ( function_exists( 'acfe' ) ) {
			$this->category = __( 'CiviCRM Post Type Sync and ACFE Forms', 'civicrm-wp-profile-sync' );
		} else {
			$this->category = __( 'CiviCRM Post Type Sync', 'civicrm-wp-profile-sync' );
		}

		// Define translations.
		$this->l10n = [
			// Example message.
			'error' => __( 'Error! Please enter a higher value.', 'civicrm-wp-profile-sync' ),
		];

		// Define choices.
		$this->defaults['choices'] = [
			'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
			'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			'2' => __( 'Unknown', 'civicrm-wp-profile-sync' ),
		];

		// Call parent.
		parent::__construct();

		// Listen for queries from our Entity classes.
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'field_settings_filter' ], 10, 3 );

	}

	/**
	 * Filter the Custom Fields for the Setting of a "CiviCRM Contact" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function field_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( $this->name !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only "Yes/No".
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && $custom_field['data_type'] == 'Boolean' ) {
					if ( ! empty( $custom_field['html_type'] ) && $custom_field['html_type'] == 'Radio' ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}

	/**
	 * This method is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.6.6
	 */
	public function input_admin_enqueue_scripts() {

		// Enqueue our JavaScript.
		wp_enqueue_script(
			'acf-input-' . $this->name,
			plugins_url( 'assets/js/acf/fields/civicrm-yes-no-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-pro-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			true
		);

	}

	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a checkbox.
		$field['type'] = 'radio';
		$field['allow_null'] = 0;

		// Define choices.
		$field['choices'] = $this->defaults['choices'];

		// Init list definition.
		$ul = [
			'class' => 'acf-radio-list acf-hl',
			'data-allow_null' => $field['allow_null'],
		];

		// Select value.
		$value = (string) $field['value'];

		// Set checked item flag, override if already saved.
		$checked = $this->defaults['default_value'];
		if ( isset( $field['choices'][ $value ] ) ) {
			$checked = $value;
		}

		// Ensure we have a string.
		$checked = (string) $checked;

		// Hidden input.
		$html = acf_get_hidden_input( [ 'name' => $field['name'] ] );

		// Open list.
		$html .= '<ul ' . acf_esc_attr( $ul ) . '>';

		// Init counter.
		$i = 0;

		// Loop through choices.
		foreach ( $field['choices'] as $value => $label ) {

			// Ensure value is a string.
			$value = (string) $value;

			// Define input attributes.
			$atts = [
				'type' => 'radio',
				'id' => $field['id'],
				'name' => $field['name'],
				'value' => $value,
			];

			// Maybe set checked.
			$class = '';
			if ( $value === $checked ) {
				$atts['checked'] = 'checked';
				$class = ' class="selected"';
			}

			// Bump counter.
			$i++;
			if ( $i > 1 ) {
				$atts['id'] .= '-' . $value;
			}

			// Append radio button.
			$html .= '<li><label' . $class . '><input ' . acf_esc_attr( $atts ) . '/>' . $label . '</label></li>';

		}

		// Close list.
		$html .= '</ul>';

		// Print to screen.
		echo $html;

	}

	/**
	 * This filter is applied to the $value after it is loaded from the database.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function load_value( $value, $post_id, $field ) {

		// Must be single value.
		if ( is_array( $value ) ) {
			$value = array_pop( $value );
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
	 */
	public function format_value( $value, $post_id, $field ) {

		// Format the value.
		$value = acf_get_field_type( 'select' )->format_value( $value, $post_id, $field );

		// --<
		return $value;

	}

}
