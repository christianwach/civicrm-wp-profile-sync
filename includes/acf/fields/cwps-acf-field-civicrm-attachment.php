<?php
/**
 * ACF "CiviCRM Attachment Field" Class.
 *
 * Provides a "CiviCRM Attachment Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Attachment Field.
 *
 * A class that encapsulates a "CiviCRM Attachment Field" Custom ACF Field in ACF 5+.
 *
 * @since 0.5.4
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Attachment extends acf_field {

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
	 * @since 0.5.4
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string
	 */
	public $name = 'civicrm_attachment';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
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
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $l10n = [];

	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.5.4
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
		$this->label = __( 'CiviCRM Activity: Attachments', 'civicrm-wp-profile-sync' );

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

		// Maybe remove this Field from the list of available Fields.
		add_filter( 'acf/get_field_types', [ $this, 'remove_field_type' ], 100, 1 );

	}

	/**
	 * Removes this Field Type from the list of available Field Types.
	 *
	 * @since 0.5.4
	 *
	 * @param array $groups The Field being rendered.
	 */
	public function remove_field_type( $groups ) {

		// Bail if the "CiviCRM" group is missing.
		if ( empty( $groups[ $this->category ] ) ) {
			return $groups;
		}

		// Allow if CiviCRM is greater than 5.49.0.
		$version = $this->plugin->civicrm->get_version();
		if ( version_compare( $version, '5.49.0', '>=' ) ) {
			return $groups;
		}

		// Remove this Field Type if less than 5.49.0.
		if ( isset( $groups[ $this->category ][ $this->name ] ) ) {
			unset( $groups[ $this->category ][ $this->name ] );
		}

		// --<
		return $groups;

	}

	/**
	 * Create extra Settings for this Field Type.
	 *
	 * These extra Settings will be visible when editing a Field.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Field being edited.
	 */
	public function render_field_settings( $field ) {

		// Define "File Link" Field.
		$usage = [
			'label' => __( 'File Link', 'civicrm-wp-profile-sync' ),
			'name' => 'file_link',
			'type' => 'select',
			'instructions' => __( 'Choose which File this ACF Field should link to.', 'civicrm-wp-profile-sync' ),
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'required' => 0,
			'return_format' => 'value',
			'choices' => [
				1 => __( 'Use public WordPress File', 'civicrm-wp-profile-sync' ),
				2 => __( 'Use permissioned CiviCRM File', 'civicrm-wp-profile-sync' ),
			],
		];

		// Now add it.
		acf_render_field_setting( $field, $usage );

		// Define "Show Attachment ID" Field.
		$show_attachment_id = [
			'label' => __( 'CiviCRM Attachment ID', 'civicrm-wp-profile-sync' ),
			'name' => 'show_attachment_id',
			'type' => 'true_false',
			'ui' => 1,
			'ui_on_text' => __( 'Show', 'civicrm-wp-profile-sync' ),
			'ui_off_text' => __( 'Hide', 'civicrm-wp-profile-sync' ),
			'default_value' => 0,
			'required' => 0,
		];

		// Now add it.
		acf_render_field_setting( $field, $show_attachment_id );

		// Define "Mime Types" Field.
		$mime_types = [
			'label' => __( 'Allowed file types', 'civicrm-wp-profile-sync' ),
			'name' => 'mime_types',
			'type' => 'text',
			'instructions' => __( 'Comma separated list. Leave blank for all types', 'civicrm-wp-profile-sync' ),
		];

		// Now add it.
		acf_render_field_setting( $field, $mime_types );

	}

	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a "repeater" Field.
		$field['type'] = 'repeater';

		// Render.
		acf_render_field( $field );

	}

	/**
	 * Prepare this Field Type for display.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Field being rendered.
	 */
	public function prepare_field( $field ) {

		// Bail when Attachment ID should be shown.
		if ( ! empty( $field['show_attachment_id'] ) ) {
			return $field;
		}

		// Add hidden class to element.
		$field['wrapper']['class'] .= ' attachment_id_hidden';

		// --<
		return $field;

	}

	/**
	 * This action is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is edited.
	 *
	 * Use this action to add CSS and JavaScript to assist your
	 * render_field_settings() action.
	 *
	 * @since 0.5.4
	public function field_group_admin_enqueue_scripts() {

	}
	 */

	/**
	 * This action is called in the "admin_head" action on the edit screen where
	 * this Field is edited.
	 *
	 * Use this action to add CSS and JavaScript to assist your
	 * render_field_settings() action.
	 *
	 * @since 0.5.4
	public function field_group_admin_head() {

	}
	 */

	/**
	 * This method is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.5.4
	 */
	public function input_admin_enqueue_scripts() {

		// Enqueue our JavaScript.
		wp_enqueue_script(
			'acf-input-' . $this->name,
			plugins_url( 'assets/js/acf/fields/civicrm-attachment-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-pro-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			false
		);

	}

	/**
	 * This method is called in the admin_head action on the edit screen where
	 * this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.5.4
	 */
	public function input_admin_head() {

		echo '
		<style type="text/css">
			/* Hide Repeater column */
			.attachment_id_hidden th[data-key="field_attachment_id"],
			.attachment_id_hidden td.civicrm_attachment_id
			{
				display: none;
			}
		</style>
		';

	}

	/**
	 * This filter is applied to the $value after it is loaded from the database.
	 *
	 * @since 0.5.4
	 *
	 * @param mixed   $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array   $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function load_value( $value, $post_id, $field ) {

		// Make sure we have an array.
		if ( empty( $value ) && ! is_array( $value ) ) {
			$value = [];
		}

		// Strip keys and re-index.
		if ( is_array( $value ) ) {
			$value = array_values( $value );
		}

		// --<
		return $value;

	}

	/**
	 * This filter is applied to the $value before it is saved in the database.
	 *
	 * @since 0.5.4
	 *
	 * @param mixed   $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array   $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function update_value( $value, $post_id, $field ) {

		// Make sure we have an array.
		if ( empty( $value ) && ! is_array( $value ) ) {
			$value = [];
		}

		// --<
		return $value;

	}

	/**
	 * This filter is applied to the value after it is loaded from the database
	 * and before it is returned to the template.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
	 *
	 * @param bool   $valid The validation status based on the value and the Field's required setting.
	 * @param mixed  $value The $_POST value.
	 * @param array  $field The Field array holding all the Field options.
	 * @param string $input The corresponding input name for $_POST value.
	 * @return string|bool $valid False if not valid, or string for error message.
	 */
	public function validate_value( $valid, $value, $field, $input ) {

		// Bail if it's not required and is empty.
		if ( $field['required'] == '0' && empty( $value ) ) {
			return $valid;
		}

		// Grab just the "File" Attachment ID values.
		$attachments = wp_list_pluck( $value, 'field_attachment_file' );

		// Sanitise array contents.
		array_walk(
			$attachments,
			function( &$item ) {
				$item = (string) trim( $item );
			}
		);

		// Check that all "File" Fields are populated.
		if ( in_array( '', $attachments ) ) {
			$valid = __( 'Please upload an Attachment', 'civicrm-wp-profile-sync' );
			return $valid;
		}

		// --<
		return $valid;

	}

	/**
	 * This action is fired after a value has been deleted from the database.
	 *
	 * Please note that saving a blank value is treated as an update, not a delete.
	 *
	 * @since 0.5.4
	 *
	 * @param integer $post_id The Post ID from which the value was deleted.
	 * @param string $key The meta key which the value was deleted.
	public function delete_value( $post_id, $key ) {

	}
	 */

	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function load_field( $field ) {

		// Cast min/max as integer.
		$field['min'] = (int) $field['min'];
		$field['max'] = (int) $field['max'];

		// Init Subfields.
		$sub_fields = [];

		// Maybe append to Field.
		if ( ! empty( $field['sub_fields'] ) ) {

			// Validate Field first.
			foreach ( $field['sub_fields'] as $sub_field ) {
				$sub_fields[] = acf_validate_field( $sub_field );
			}

		}

		// Overwrite subfields.
		$field['sub_fields'] = $sub_fields;

		// --<
		return $field;

	}

	/**
	 * This filter is applied to the Field before it is saved to the database.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function update_field( $field ) {

		// Modify the Field with our settings.
		$field = $this->modify_field( $field );

		// --<
		return $field;

	}

	/**
	 * This action is fired after a Field is deleted from the database.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Field array holding all the Field options.
	public function delete_field( $field ) {

	}
	 */

	/**
	 * Modify the Field with defaults and Subfield definitions.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $subfields The subfield array.
	 */
	public function modify_field( $field ) {

		/*
		 * Set the max value to match the max in CiviCRM.
		 *
		 * @see civicrm/settings/Core.setting.php:396
		 */
		$field['max'] = $this->civicrm->attachment->field_max_attachments_get();
		$field['min'] = 0;

		// Set sensible defaults.
		$field['layout'] = 'table';
		$field['button_label'] = __( 'Add Attachment', 'civicrm-wp-profile-sync' );
		$field['collapsed'] = '';
		$field['prefix'] = '';

		// Set wrapper class.
		$field['wrapper']['class'] = 'civicrm_attachment';

		// Define Attachment "File" subfield.
		$file = [
			'key' => 'field_attachment_file',
			'label' => __( 'File', 'civicrm-wp-profile-sync' ),
			'name' => 'attachment_file',
			'type' => 'file',
			'parent' => $field['key'],
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '30',
				'class' => 'civicrm_attachment_name',
				'id' => '',
			],
			'uploader' => 'basic',
			'min_size' => 0,
			'max_size' => $this->civicrm->attachment->field_max_size_get(),
			'mime_types' => $field['mime_types'],
			'library' => 'all',
			'return_format' => 'array',
		];

		// Define Attachment "Description" Field.
		$description = [
			'key' => 'field_attachment_description',
			'label' => __( 'Description', 'civicrm-wp-profile-sync' ),
			'name' => 'attachment_description',
			'type' => 'text',
			'type' => 'text',
			'parent' => $field['key'],
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '60',
				'class' => '',
				'id' => '',
			],
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '255',
		];

		// Define hidden CiviCRM Attachment ID Field.
		$attachment_id = [
			'readonly' => true,
			'key' => 'field_attachment_id',
			'label' => __( 'CiviCRM ID', 'civicrm-wp-profile-sync' ),
			'name' => 'attachment_id',
			'type' => 'number',
			'parent' => $field['key'],
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '10',
				'class' => 'civicrm_attachment_id',
				'id' => '',
			],
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'min' => '',
			'max' => '',
			'step' => '',
			'prefix' => '',
		];

		// Add Subfields.
		$field['sub_fields'] = [ $file, $description, $attachment_id ];

		// --<
		return $field;

	}

}
