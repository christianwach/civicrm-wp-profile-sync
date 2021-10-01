<?php
/**
 * ACFE "CiviCRM County Field" Class.
 *
 * Provides a "CiviCRM County Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM County Field.
 *
 * A class that encapsulates a "CiviCRM County Field" Custom ACF Field in ACF 5+.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_County extends acf_field {

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
	public $name = 'cwps_acfe_address_county';

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
		$this->label = __( 'CiviCRM County', 'civicrm-wp-profile-sync' );

		// Define category.
		$this->category = __( 'CiviCRM ACFE Forms', 'civicrm-wp-profile-sync' );

		// Define translations.
		$this->l10n = [];

		// Call parent.
		parent::__construct();

		// Define AJAX callbacks.
		add_action( 'wp_ajax_cwps_get_state_field', [ $this, 'ajax_query' ] );

	}



	/**
	 * Create extra Settings for this Field Type.
	 *
	 * These extra Settings will be visible when editing a Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field being edited.
	 */
	public function render_field_settings( $field ) {

		// Define "Source State/Province" setting Field.
		$country_source = [
			'label' => __( 'Source State/Province', 'civicrm-wp-profile-sync' ),
			'name' => 'state_source',
			'type' => 'radio',
			'instructions' => __( 'The source for the Counties in this Field.', 'civicrm-wp-profile-sync' ),
			'allow_null' => 0,
			'required' => 0,
			'default_value' => 1,
			'layout' => 'vertical',
			'return_format' => 'value',
			'choices' => [
				1 => __( 'The default State/Province in CiviCRM', 'civicrm-wp-profile-sync' ),
				2 => __( 'A CiviCRM State Field', 'civicrm-wp-profile-sync' ),
				3 => __( 'A specific State/Province', 'civicrm-wp-profile-sync' ),
			],
		];

		// Now add it.
		acf_render_field_setting( $field, $country_source );

		// Define "State Field Reference" setting Field.
		$state_ref = [
			'label' => __( 'State/Province Field', 'civicrm-wp-profile-sync' ),
			'name' => 'county_state',
			'type' => 'select',
			'instructions' => __( 'Filter the visible Counties by the selected State/Province Field.', 'civicrm-wp-profile-sync' ),
			'ui' => 1,
			'ajax' => 1,
			'allow_null' => 1,
            'ajax_action' => 'cwps_get_state_field',
            'placeholder' => __( 'Select the State/Province Field', 'civicrm-wp-profile-sync' ),
			'default_value' => 0,
			'required' => 0,
			'conditional_logic' => [ [ [
				'field' => 'state_source',
				'operator' => '==contains',
				'value' => 2,
			] ] ],
		];

		// Add existing choice if present.
		if ( ! empty( $field['county_state'] ) ) {
			$state_field = acf_get_field( $field['county_state'] );
			if( ! empty( $state_field ) ) {
				$label = acf_maybe_get( $state_field, 'label', $state_field['name'] );
				$state_ref['choices'] = [ $field['county_state'] => "{$label} ({$state_field['key']})" ];
			}
		}

		// Now add it.
		acf_render_field_setting( $field, $state_ref );

		// Define "State ID" setting Field.
		$state_id = [
			'label' => __( 'State/Province', 'civicrm-wp-profile-sync' ),
			'name' => 'state_id',
			'type' => 'select',
			'instructions' => __( 'Use the Counties in this State/Province.', 'civicrm-wp-profile-sync' ),
			'allow_null' => 1,
			'ui' => 1,
			'ajax' => 0,
            'placeholder' => __( 'Select the State/Province', 'civicrm-wp-profile-sync' ),
			'default_value' => 0,
			'required' => 0,
			'choices' => CRM_Core_PseudoConstant::stateProvince(),
			'conditional_logic' => [ [ [
				'field' => 'state_source',
				'operator' => '==contains',
				'value' => 3,
			] ] ],
		];

		// Now add it.
		acf_render_field_setting( $field, $state_id );

	}



	/**
	 * AJAX Query callback.
	 *
	 * @since 0.5
	 */
	public function ajax_query() {

		// Validate.
		if ( ! acf_verify_ajax() ) {
			die();
		}

		// Get response.
		$response = $this->ajax_get_response( $_POST );

		// Send results.
		acf_send_ajax_results( $response );

	}



	/**
	 * AJAX Query callback.
	 *
	 * @since 0.5
	 *
	 * @param array $options The options that define the query.
	 * @return array $response The query results.
	 */
	public function ajax_get_response( $options = [] ) {

		// Init response.
		$response = [
			'results' => [],
			'limit' => 25,
		];

		// Init defaults.
		$defaults = [
			'post_id' => 0,
			's' => '',
			'field_key' => '',
			'paged' => 1,
		];

   		// Parse the incoming POST array.
   		$options = acf_parse_args( $options, $defaults );

		// Bail if there's no search string.
		if ( empty( $options['s'] ) ) {
			return $response;
		}

		// Grab the Post ID.
		$post_id = absint( $options['post_id'] );

		// Strip slashes - search may be an integer.
		$search = wp_unslash( (string) $options['s'] );

		// Get the Fields in this Field Group.
		$field_group = acf_get_field_group( $post_id );
		$fields_in_group = acf_get_fields( $field_group );

		// Get the Fields as choices for the select.
		$choices = $this->ajax_get_state_fields( [], $fields_in_group, $field_group );

		// Format and filter the choices for returning.
		$formatted = [];
		foreach ( $choices as $title => $fields ) {
			$title = (string) $title;
			$data = [];
			foreach ( $fields as $key => $label ) {
				$label = (string) $label;
				if ( ! empty( $search ) ) {
					if (
						stripos( strtolower( $label ), $search ) !== false
						OR
						stripos( strtolower( $title ), $search ) !== false
					) {
						$data[] = [ 'id' => $key, 'text' => $label ];
					}
				}
			}
			if ( ! empty( $data ) ) {
				$formatted[] = [ 'text' => $title, 'children' => $data ];
			}
		}

		// Add to the response.
		$response['results'] = $formatted;

  		// --<
		return $response;

	}



	/**
	 * Get the State Fields for the AJAX Query.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The choices for the select.
	 * @param array $fields The array of ACF Fields.
	 * @param array $container The container of ACF Field, e.g. Group or Clone.
	 * @return array $choices The choices for the select.
	 */
	public function ajax_get_state_fields( $choices, $fields, $container = [] ) {

		// Sanity check.
		if ( empty( $fields ) ) {
			return $choices;
		}

		// Look at each Field in turn.
		foreach ( $fields as $field ) {

			// Recurse when there are Sub-fields, e.g. when looking at Groups and Clones.
			if ( acf_maybe_get( $field, 'sub_fields' ) ) {
				$choices = $this->ajax_get_state_fields( $choices, $field['sub_fields'], $field );
				continue;
			}

			// Filter all but Fields of type "CiviCRM State".
			if ( $field['type'] !== 'cwps_acfe_address_state' ) {
				continue;
			}

			// Add to choices.
			$label = acf_maybe_get( $field, 'label', $field['name'] );
			$title = acf_maybe_get( $container, 'label', $container['name'] );
			if ( empty( $title ) ) {
				$title = __( 'Top Level', 'civicrm-wp-profile-sync' );
			}
			$choices[ $title ][ $field['key'] ] = "{$label} ({$field['key']})";

		}

		// --<
		return $choices;

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

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		// Given precedence to the saved value.
		if ( ! empty( $field['value'] ) ) {

			// Add existing choice if present.
			$county = CRM_Core_PseudoConstant::county( $field['value'] );
			if( ! empty( $county ) ) {

				// Try and get the State ID.
				$state_id = $this->plugin->civicrm->address->state_get_for_county( $field['value'] );
				if ( ! empty( $state_id ) ) {
					$field['choices'] = CRM_Core_PseudoConstant::countyForState( $state_id );;
				}

			}

		} elseif ( ! empty( $field['state_id'] ) ) {

			// Add choices from specific State ID if present.
			$field['choices'] = CRM_Core_PseudoConstant::countyForState( $field['state_id'] );

		} else {

			// Add choices from the default State/Province.
			$state_id = $config->defaultContactStateProvince;
			if ( ! empty( $state_id ) ) {
				$field['choices'] = CRM_Core_PseudoConstant::countyForState( $state_id );
			}

		}

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

		// Assign County for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_county( $value, $post_id, $field );
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

		// Assign County for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_county( $value, $post_id, $field );
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

		$field['allow_null'] = 1;
		$field['multiple'] = 0;
		$field['ui'] = 1;
		$field['ajax'] = 0;
		$field['return_format'] = 'value';
		$field['choices'] = [];
		$field['default_value'] = 0;

		// If there's a State Field.
		if ( ! empty( $field['county_state'] ) ) {
			$field['wrapper']['class'] = 'cwps-state-' . $field['county_state'];
		} else {
			$field['wrapper']['class'] = 'cwps-state-none';
		}

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
		$field['choices'] = CRM_Core_PseudoConstant::county();
		$field['default_value'] = $config->defaultContactCounty;

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
			plugins_url( 'assets/js/acf/acfe/fields/civicrm-address-county-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION // Version.
		);

		// Get the Counties keyed by State ID.
		$counties = $this->plugin->civicrm->address->counties_get_for_states();

		// Build data array.
		$vars = [
			'settings' => [
				'counties' => $counties,
			],
		];

		// Localize our script.
		wp_localize_script(
			'acf-input-' . $this->name,
			'CWPS_ACFE_County_Vars',
			$vars
		);

	}



} // Class ends.



