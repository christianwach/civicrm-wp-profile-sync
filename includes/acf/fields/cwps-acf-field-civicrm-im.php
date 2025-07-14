<?php
/**
 * ACF "CiviCRM Instant Messenger Field" Class.
 *
 * Provides a "CiviCRM Instant Messenger Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Instant Messenger Field.
 *
 * A class that encapsulates a "CiviCRM Instant Messenger Field" Custom ACF Field in ACF 5+.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Instant_Messenger extends acf_field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_ACF_Loader
	 */
	public $acf_loader;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF
	 */
	public $acf;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM
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
	public $name = 'civicrm_im';

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
		$this->label = __( 'CiviCRM Instant Messenger: Complete', 'civicrm-wp-profile-sync' );

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

		// Define setting Field.
		$setting = [
			'label'         => __( 'CiviCRM Instant Messenger ID', 'civicrm-wp-profile-sync' ),
			'name'          => 'show_im_id',
			'type'          => 'true_false',
			'ui'            => 1,
			'ui_on_text'    => __( 'Show', 'civicrm-wp-profile-sync' ),
			'ui_off_text'   => __( 'Hide', 'civicrm-wp-profile-sync' ),
			'default_value' => 0,
			'required'      => 0,
		];

		// Now add it.
		acf_render_field_setting( $field, $setting );

	}

	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.4
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
	 * @since 0.4
	 *
	 * @param array $field The Field being rendered.
	 */
	public function prepare_field( $field ) {

		// Bail when IM ID should be shown.
		if ( ! empty( $field['show_im_id'] ) ) {
			return $field;
		}

		// Add hidden class to element.
		$field['wrapper']['class'] .= ' im_id_hidden';

		// --<
		return $field;

	}

	/**
	 * This method is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.4
	 */
	public function input_admin_enqueue_scripts() {

		// Enqueue our JavaScript.
		wp_enqueue_script(
			'acf-input-' . $this->name,
			plugins_url( 'assets/js/acf/fields/civicrm-im-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-pro-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			true
		);

	}

	/**
	 * This method is called in the admin_head action on the edit screen where
	 * this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.4
	 */
	public function input_admin_head() {

		echo '
		<style type="text/css">
			/* Hide Repeater column */
			.im_id_hidden th[data-key="field_im_id"],
			.im_id_hidden td.civicrm_im_id
			{
				display: none;
			}
		</style>
		';

	}

	/**
	 * This filter is applied to the $value after it is loaded from the database.
	 *
	 * @since 0.4
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
	 * @since 0.4
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
	 * This filter is used to perform validation on the value prior to saving.
	 *
	 * All values are validated regardless of the Field's required setting.
	 * This allows you to validate and return messages to the user if the value
	 * is not correct.
	 *
	 * @since 0.4
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

		// Grab just the Primary values.
		$primary_values = wp_list_pluck( $value, 'field_im_primary' );

		// Sanitise array contents.
		array_walk(
			$primary_values,
			function( &$item ) {
				$item = (int) trim( $item );
			}
		);

		// Check that we have a Primary Number.
		if ( ! in_array( 1, $primary_values, true ) ) {
			$valid = __( 'Please select a Primary Instant Messenger', 'civicrm-wp-profile-sync' );
			return $valid;
		}

		// Grab just the Instant Messenger "Name" values.
		$ims = wp_list_pluck( $value, 'field_im_name' );

		// Sanitise array contents.
		array_walk(
			$ims,
			function( &$item ) {
				$item = (string) trim( $item );
			}
		);

		// Check that all "Name" Fields are populated.
		if ( in_array( '', $ims, true ) ) {
			$valid = __( 'Please enter an Instant Messenger', 'civicrm-wp-profile-sync' );
			return $valid;
		}

		// --<
		return $valid;

	}

	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function load_field( $field ) {

		// Cast min/max as integer.
		$field['min'] = (int) $field['min'];
		$field['max'] = (int) $field['max'];

		// Validate Subfields.
		if ( ! empty( $field['sub_fields'] ) ) {
			array_walk(
				$field['sub_fields'],
				function( &$item ) {
					$item = acf_validate_field( $item );
				}
			);
		}

		// --<
		return $field;

	}

	/**
	 * This filter is applied to the Field before it is saved to the database.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function update_field( $field ) {

		// Modify the Field with defaults.
		$field = $this->modify_field( $field );

		// Delete any existing subfields to prevent duplication.
		if ( ! empty( $field['sub_fields'] ) ) {
			foreach ( $field['sub_fields'] as $sub_field ) {
				acf_delete_field( $sub_field['name'] );
			}
		}

		// Add our Subfields.
		$field['sub_fields'] = $this->sub_fields_get( $field );

		// --<
		return $field;

	}

	/**
	 * Deletes any subfields after the Field has been deleted from the database.
	 *
	 * This is more complicated than it ought to be because previous versions of this
	 * Field added an extra set of Sub-fields to the database every time that the
	 * Field Group they were part of was saved. We need to remove them all to retain
	 * the integrity of the Field Group.
	 *
	 * @since 0.7.2
	 *
	 * @param array $field The Field array holding all the Field options.
	 */
	public function delete_field( $field ) {

		// Bail early if no subfields.
		if ( empty( $field['sub_fields'] ) ) {
			return;
		}

		// We need our list of subfields.
		$sub_fields_data = $this->sub_fields_get( $field );

		// Define common query args.
		$args = [
			'posts_per_page'         => -1,
			'post_type'              => 'acf-field',
			'orderby'                => 'menu_order',
			'order'                  => 'ASC',
			'suppress_filters'       => true, // DO NOT allow WPML to modify the query.
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'post_status'            => [ 'publish', 'trash' ],
		];

		// Delete all the subfields.
		foreach ( $sub_fields_data as $sub_field ) {

			// Finalise query.
			$args['name']        = $sub_field['key'];
			$args['post_parent'] = $field['parent'];

			// Skip to next if we don't get any results.
			$acf_posts = get_posts( $args );
			if ( empty( $acf_posts ) ) {
				continue;
			}

			// Delete all the subfields with this name.
			foreach ( $acf_posts as $acf_post ) {

				// Get the Field data.
				$acf_field = (array) acf_maybe_unserialize( $acf_post->post_content );

				// Validate the Field.
				$acf_field = acf_validate_field( $acf_field );

				// Set input prefix.
				$acf_field['prefix'] = 'acf';

				/**
				 * Filters the Field array after it has been loaded.
				 *
				 * @since ACF 5.0.0
				 *
				 * @param array $acf_field The ACF Field array.
				 */
				$acf_field = apply_filters( 'acf/load_field', $acf_field );

				// Delete the Post.
				wp_delete_post( $acf_post->ID, true );

				// Flush Field cache.
				acf_flush_field_cache( $acf_field );

				/**
				 * Fires immediately after a Field has been deleted.
				 *
				 * @since ACF 5.0.0
				 *
				 * @param array $acf_field The ACF Field array.
				 */
				do_action( 'acf/delete_field', $acf_field );

			}

		}

	}

	/**
	 * Modify the Field with defaults.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field array.
	 */
	public function modify_field( $field ) {

		/*
		 * Set the max value to match the max in CiviCRM.
		 *
		 * @see civicrm/templates/CRM/Contact/Form/Inline/IM.tpl:22
		 */
		$field['max'] = 5;
		$field['min'] = 0;

		// Set sensible defaults.
		$field['layout']       = 'table';
		$field['button_label'] = __( 'Add Instant Messenger', 'civicrm-wp-profile-sync' );
		$field['collapsed']    = '';

		// Set wrapper class.
		$field['wrapper']['class'] = 'civicrm_im';

		// --<
		return $field;

	}

	/**
	 * Get the Subfield definitions.
	 *
	 * @since 0.7.2
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $sub_fields The subfield array.
	 */
	public function sub_fields_get( $field ) {

		// Define Instant Messenger "Name" subfield.
		$number = [
			'key'               => 'field_im_name',
			'label'             => __( 'Instant Messenger', 'civicrm-wp-profile-sync' ),
			'name'              => 'im_name',
			'type'              => 'text',
			'parent'            => $field['key'],
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '30',
				'class' => 'civicrm_im_name',
				'id'    => '',
			],
			'default_value'     => '',
			'placeholder'       => '',
			'prepend'           => '',
			'append'            => '',
			'maxlength'         => '',
			'prefix'            => '',
		];

		// Instant Messenger Locations are standard Location Types.
		$location_types = $this->plugin->civicrm->address->location_types_get();

		// Build Location Types choices array for dropdown.
		$locations = [];
		foreach ( $location_types as $location_type ) {
			$locations[ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Get default Location Type.
		$location_type_default = false;
		foreach ( $location_types as $location_type ) {
			if ( ! empty( $location_type['is_default'] ) ) {
				$location_type_default = $location_type['id'];
				break;
			}
		}

		// Define Location Field.
		$location = [
			'key'               => 'field_im_location',
			'label'             => __( 'Location', 'civicrm-wp-profile-sync' ),
			'name'              => 'location',
			'type'              => 'select',
			'parent'            => $field['key'],
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '25',
				'class' => '',
				'id'    => '',
			],
			'choices'           => $locations,
			'default_value'     => $location_type_default,
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'return_format'     => 'value',
			'ajax'              => 0,
			'placeholder'       => '',
			'prefix'            => '',
		];

		// Define Provider Field.
		$provider = [
			'key'               => 'field_im_provider',
			'label'             => __( 'Provider', 'civicrm-wp-profile-sync' ),
			'name'              => 'provider',
			'type'              => 'select',
			'parent'            => $field['key'],
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '25',
				'class' => '',
				'id'    => '',
			],
			'choices'           => $this->civicrm->im->im_providers_get(),
			'default_value'     => false,
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'return_format'     => 'value',
			'ajax'              => 0,
			'placeholder'       => '',
			'prefix'            => '',
		];

		// Define Is Primary Field.
		$primary = [
			'key'               => 'field_im_primary',
			'label'             => __( 'Is Primary', 'civicrm-wp-profile-sync' ),
			'name'              => 'is_primary',
			'type'              => 'radio',
			'parent'            => $field['key'],
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '10',
				'class' => 'civicrm_im_primary',
				'id'    => '',
			],
			'choices'           => [
				1 => __( 'Primary', 'civicrm-wp-profile-sync' ),
			],
			'allow_null'        => 1,
			'other_choice'      => 0,
			'default_value'     => '',
			'layout'            => 'vertical',
			'return_format'     => 'value',
			'save_other_choice' => 0,
			'prefix'            => '',
		];

		// Define hidden CiviCRM Instant Messenger ID Field.
		$im_id = [
			'readonly'          => true,
			'key'               => 'field_im_id',
			'label'             => __( 'CiviCRM ID', 'civicrm-wp-profile-sync' ),
			'name'              => 'civicrm_im_id',
			'type'              => 'number',
			'parent'            => $field['key'],
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '10',
				'class' => 'civicrm_im_id',
				'id'    => '',
			],
			'default_value'     => '',
			'placeholder'       => '',
			'prepend'           => '',
			'append'            => '',
			'min'               => '',
			'max'               => '',
			'step'              => '',
			'prefix'            => '',
		];

		// Add Subfields.
		$sub_fields = [ $number, $location, $provider, $primary, $im_id ];

		// --<
		return $sub_fields;

	}

}
