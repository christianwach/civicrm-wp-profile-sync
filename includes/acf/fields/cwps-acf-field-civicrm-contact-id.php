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
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF plugin version.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var string
	 */
	public $acf_version;

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
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $name = 'civicrm_contact_id';

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
	public $defaults = [];

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
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $l10n = [];

	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.4
	 * @since 0.6.9 Added $version param.
	 *
	 * @param object $parent The parent object reference.
	 * @param string $version The ACF plugin version.
	 */
	public function __construct( $parent, $version ) {

		// Store ACF plugin version.
		$this->acf_version = $version;

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf        = $parent->acf;

		// Define label.
		$this->label = __( 'CiviCRM Contact: Contact ID (Read Only)', 'civicrm-wp-profile-sync' );

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
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a simple "number" Field.
		$field['type']       = 'number';
		$field['readonly']   = 1;
		$field['allow_null'] = 0;
		$field['prepend']    = '';
		$field['append']     = '';
		$field['step']       = '';

		// Populate Field.
		if ( ! empty( $field['value'] ) ) {

			// Cast value to an integer.
			$contact_id = (int) $field['value'];

			// Apply Contact ID to Field.
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
	 * @param mixed          $value The value found in the database.
	 * @param integer|string $post_id The ACF "Post ID" from which the value was loaded.
	 * @param array          $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function load_value( $value, $post_id, $field ) {

		// Assign Contact ID for this Field if empty.
		if ( empty( $value ) ) {

			// Get Contact ID for this ACF "Post ID".
			$contact_id = $this->acf->field->query_contact_id( $post_id );

			// Overwrite if we get a value.
			if ( false !== $contact_id ) {
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
	 * @param mixed   $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array   $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function update_value( $value, $post_id, $field ) {

		// Assign Contact ID for this Field if empty.
		if ( empty( $value ) ) {

			// Get Contact ID for this ACF "Post ID".
			$contact_id = $this->acf->field->query_contact_id( $post_id );

			// Overwrite if we get a value.
			if ( false !== $contact_id ) {
				$value = $contact_id;
			}

		}

		// --<
		return $value;

	}

}
