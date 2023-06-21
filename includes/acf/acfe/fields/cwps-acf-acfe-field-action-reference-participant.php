<?php
/**
 * ACFE "Form Action Reference" Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * ACFE Form "Participant Action Reference" Class.
 *
 * A class that encapsulates a "Participant Action Reference" Custom ACF Field in ACF 5+.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Participant_Action_Ref extends acf_field {

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
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * ACF Extended object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acfe;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $name = 'cwps_acfe_participant_action_ref';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.5
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
	 * @since 0.5
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
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.5
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
	 * var message = acf._e( 'civicrm_participant', 'error' );
	 *
	 * @since 0.5
	 * @access public
	 * @var array
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
		$this->civicrm = $this->acf_loader->civicrm;
		$this->acf = $this->acf_loader->acf;
		$this->acfe = $parent;

		// Define label.
		$this->label = __( 'CiviCRM Form Action Reference: Participant', 'civicrm-wp-profile-sync' );

		// Define category.
		$this->category = __( 'CiviCRM ACFE Forms only', 'civicrm-wp-profile-sync' );

		// Define translations.
		$this->l10n = [
			// Example message.
			'error' => __( 'Error! Please enter a higher value.', 'civicrm-wp-profile-sync' ),
		];

		// Call parent.
		parent::__construct();

		// Remove this Field from the list of available Fields.
		add_filter( 'acf/get_field_types', [ $this, 'remove_field_type' ], 100, 1 );

	}

	/**
	 * Removes this Field Type from the list of available Field Types.
	 *
	 * @since 0.5
	 *
	 * @param array $groups The Field being rendered.
	 */
	public function remove_field_type( $groups ) {

		// Bail if the "CiviCRM" group is missing.
		if ( empty( $groups[ $this->category ] ) ) {
			return $groups;
		}

		// Remove this Field Type.
		if ( isset( $groups[ $this->category ][ $this->name ] ) ) {
			unset( $groups[ $this->category ][ $this->name ] );
		}

		// --<
		return $groups;

	}

	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a select.
		$field['type'] = 'select';
		$field['ui'] = 0;
		$field['ajax'] = 0;
		$field['allow_null'] = 1;
		$field['multiple'] = 0;
		$field['required'] = 0;
		$field['conditional_logic'] = 0;

		// Init choices array.
		$field['choices'] = [];

		// Overwrite with Field value.
		if ( ! empty( $field['value'] ) ) {
			$field['choices'][ $field['value'] ] = $field['value'];
		}

		// Render.
		acf_render_field( $field );

	}

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
			plugins_url( 'assets/js/acf/acfe/fields/cwps-field-action-reference-participant.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			true
		);

	}

}
