<?php
/**
 * ACF "CiviCRM State Field" Class.
 *
 * Provides a "CiviCRM State Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM State Field.
 *
 * A class that encapsulates a "CiviCRM State Field" Custom ACF Field in ACF 5+.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Address_State_Field extends acf_field {

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
	public $name = 'civicrm_address_state';

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
		$this->label = __( 'CiviCRM Address: State (Read Only)', 'civicrm-wp-profile-sync' );

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

		// Define Primary setting Field.
		$primary = [
			'label'         => __( 'CiviCRM Primary Address', 'civicrm-wp-profile-sync' ),
			'name'          => 'state_is_primary',
			'type'          => 'true_false',
			'instructions'  => __( 'Sync with the CiviCRM Primary Address.', 'civicrm-wp-profile-sync' ),
			'ui'            => 0,
			'default_value' => 0,
			'required'      => 0,
		];

		// Now add it.
		acf_render_field_setting( $field, $primary );

		// Get Locations.
		$location_types = $this->plugin->civicrm->address->location_types_get();

		// Init choices.
		$choices = [];

		// Build Location Types choices array for dropdown.
		foreach ( $location_types as $location_type ) {
			$choices[ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Get default Location Type.
		$location_type_default = false;
		foreach ( $location_types as $location_type ) {
			if ( ! empty( $location_type['is_default'] ) ) {
				$location_type_default = $location_type['id'];
				break;
			}
		}

		// Define Location Type setting Field.
		$type = [
			'label'             => __( 'CiviCRM Location Type', 'civicrm-wp-profile-sync' ),
			'name'              => 'state_location_type_id',
			'type'              => 'select',
			'instructions'      => __( 'Choose the Location Type of the CiviCRM Address that this ACF Field should sync with.', 'civicrm-wp-profile-sync' ),
			'default_value'     => $location_type_default,
			'placeholder'       => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'required'          => 0,
			'return_format'     => 'value',
			'conditional_logic' => [
				[
					[
						'field'    => 'state_is_primary',
						'operator' => '==',
						'value'    => 0,
					],
				],
			],
			'choices'           => $choices,
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
		$field['type']       = 'text';
		$field['readonly']   = 1;
		$field['allow_null'] = 0;
		$field['prepend']    = '';
		$field['append']     = '';
		$field['step']       = '';

		// Populate Field.
		if ( ! empty( $field['value'] ) ) {

			// Ensure value is cast as a string.
			$state = (string) $field['value'];

			// Apply State to Field.
			$field['value'] = $state;

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

		// Assign State for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_state( $value, $post_id, $field );
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

		// Assign State for this Field if empty.
		if ( empty( $value ) ) {
			$value = $this->get_state( $value, $post_id, $field );
		}

		// --<
		return $value;

	}

	/**
	 * Get the State for this Contact.
	 *
	 * @since 0.4
	 *
	 * @param mixed   $value The value found in the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array   $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function get_state( $value, $post_id, $field ) {

		// Get Contact ID for this ACF "Post ID".
		$contact_id = $this->acf->field->query_contact_id( $post_id );

		// Overwrite if we get a value.
		if ( false !== $contact_id ) {

			// Get this Contact's Addresses.
			$addresses = $this->plugin->civicrm->address->addresses_get_by_contact_id( $contact_id );

			// Init State/Province ID.
			$state_id = false;

			if ( ! empty( $field['state_is_primary'] ) ) {

				// Assign State/Province ID from the Primary Address.
				foreach ( $addresses as $address ) {
					if ( ! empty( $address->is_primary ) ) {
						$state_id = (int) $address->state_province_id;
						break;
					}
				}

			} elseif ( ! empty( $field['state_location_type_id'] ) ) {

				// Assign State/Province ID from the Address Location Type.
				foreach ( $addresses as $address ) {
					if ( $address->location_type_id == $field['state_location_type_id'] ) {
						$state_id = (int) $address->state_province_id;
						break;
					}
				}

			}

			// Overwrite if we get a value.
			if ( false !== $state_id ) {
				$state = $this->plugin->civicrm->address->state_province_get_by_id( $state_id );
				$value = $state['name'];
			}

		}

		// --<
		return $value;

	}

}
