<?php
/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM "Event Group" Reference Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * ACF "CiviCRM Event Group" Class.
 *
 * A class that encapsulates a "CiviCRM Event Group" Custom ACF Field in ACF 5+.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Event_Group extends acf_field {

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The parent object.
	 */
	public $acf;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $name The Field Type name.
	 */
	public $name = 'civicrm_event_group';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a field type.
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
	 * Array of default settings which are merged into the field object.
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
	 * var message = acf._e( 'civicrm_participant', 'error' );
	 *
	 * @since 0.5
	 * @access public
	 * @var array $l10n The Field Type translations.
	 */
	public $l10n = [];

	/**
	 * ACF identifier.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $acf_slug The ACF identifier.
	 */
	public $acf_slug = 'cwps_participant';



	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to ACF object.
		$this->acf = $parent->acf;

		// Store reference to parent.
		$this->acf_type = $parent;

		// Define label.
		$this->label = __( 'CiviCRM Event with Type', 'civicrm-wp-profile-sync' );

		// Define category.
		$this->category = __( 'CiviCRM Post Type Sync', 'civicrm-wp-profile-sync' );

		// Define translations.
		$this->l10n = [
			// Example message.
			'error'	=> __( 'Error! Please enter a higher value.', 'civicrm-wp-profile-sync' ),
		];

		// Call parent.
    	parent::__construct();

		// Register this Field as a Local Field.
		add_action( 'acf/init', [ $this, 'register_field' ] );

		// Force validation.
		add_filter( 'acf/validate_value/type=group', [ $this, 'validate_value' ], 10, 4 );

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
		if ( empty( $groups[$this->category] ) ) {
			return $groups;
		}

		// Remove this Field Type.
		if ( isset( $groups[$this->category][$this->name] ) ) {
			unset( $groups[$this->category][$this->name] );
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

		// Get the ACF Field definition.
		$field = $this->get_field_definition();

		// Render.
		acf_render_field( $field );

	}



	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The field array holding all the field options.
	 * @return array $field The modified field data.
	 */
	public function load_field( $field ) {

		// Init Subfields.
		$sub_fields = [];

		// Maybe append to Field.
		if ( ! empty( $field['sub_fields'] ) ) {

			// Validate field first.
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
	 * @since 0.5
	 *
	 * @param array $field The field array holding all the field options.
	 * @return array $field The modified field data.
	 */
	public function update_field( $field ) {

		// Modify the Field with our settings.
		$field = $this->modify_field( $field );

		// Add Subfields to Field.
		$field['sub_fields'] = $this->get_subfield_definitions();

		// --<
		return $field;

	}



	/**
	 * Modify the Field with defaults and Subfield definitions.
	 *
	 * @since 0.5
	 *
	 * @param array $field The field array holding all the field options.
	 * @return array $subfields The subfield array.
	 */
	public function modify_field( $field ) {

		// Set sensible defaults.
		$field['instruction_placement'] = 'field';
		$field['required'] = 1;
		$field['layout'] = 'block';

		// Use for Participants only.
		// TODO: make available elsewhere?
		$field['field_cacf_civicrm_custom_field'] = 'caiparticipant_event_id';

		// Set wrapper class.
		$field['wrapper']['class'] = 'civicrm_event_group';

		// --<
		return $field;

	}



	/**
	 * Registers the ACF Field.
	 *
	 * This seems to be required in order for AJAX calls in the Contact ID
	 * Sub-field to find a registered Field.
	 *
	 * @since 0.5
	 */
	public function register_field() {

		// Get the ACF Field definition.
		$field = $this->get_field_definition();

		// Add it as a "local field".
		acf_add_local_field( $field );

	}



	/**
	 * Gets the "Existing Contact / New Contact" Field.
	 *
	 * @since 0.5
	 *
	 * @return array $field The ACF Field definition.
	 */
	public function get_field_definition() {

		// Bundle the above Fields into a container group.
		$field = [
			'key' => 'field_' . $this->acf_slug . '_event_group',
			'label' => __ ( 'CiviCRM Event', 'civicrm-wp-profile-sync' ),
			'name' => $this->name,
			'type' => 'group',
			'instructions' => '',
			'instruction_placement' => 'field',
			'required' => 1,
			'layout' => 'block',
			'field_cacf_civicrm_custom_field' => 'caiparticipant_event_id',
		];

		// Add the Sub-fields.
		$field['sub_fields'] = $this->get_subfield_definitions();

		// --<
		return $field;

	}



	/**
	 * Gets the Sub-fields of this Field.
	 *
	 * @since 0.5
	 *
	 * @return array $sub_fields The ACF Sub-field definitions.
	 */
	public function get_subfield_definitions() {

		// Define "Event ID" field.
		$event_id = [
			'key' => 'field_' . $this->acf_slug . '_event_id',
			'label' => __ ( 'Event ID', 'civicrm-wp-profile-sync' ),
			'name' => 'event_id',
			'type' => 'civicrm_event',
			'instructions' => __( 'Select an Event in CiviCRM.', 'civicrm-wp-profile-sync' ),
			'required' => 0,
		];

		// Define "Event Type" field.
		$event_type = [
			'key' => 'field_' . $this->acf_slug . '_event_type',
			'label' => __ ( 'Event Type', 'civicrm-wp-profile-sync' ),
			'name' => 'event_type',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'choices' => $this->acf_loader->civicrm->event->types_get_options(),
			'default_value' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			/*
			'conditional_logic' => [
				[
					[
						'field' => 'field_' . $this->acf_slug . '_event_id',
						'operator' => '==empty',
					],
					[
						'field' => 'field_' . $this->acf_slug . '_event_id',
						'operator' => '!=empty',
					],
				],
			],
			*/
		];

		// Build Sub-fields array.
		$sub_fields = [
			$event_id,
			$event_type,
		];

		// --<
		return $sub_fields;

	}



	/**
	 * Gets the CiviCRM Event ID from this Field.
	 *
	 * @since 0.5
	 *
	 * @param array $value The ACF Field values.
	 * @return integer|bool $event The CiviCRM Event ID, or false on failure.
	 */
	public function prepare_output( $value ) {

		// Let's have an easy prefix.
		$prefix = 'field_' . $this->acf_slug;

		// Grab the Event ID.
		$event_id = $value[$prefix . '_event_id'];
		if ( ! empty( $event_id ) && is_numeric( $event_id ) ) {
			return (int) $event_id;
		}

		// Fallback.
		return false;

	}



	/**
	 * Prepares this Field with data from a CiviCRM Event.
	 *
	 * @since 0.5
	 *
	 * @param integer $event_id The numeric ID of the CiviCRM Event.
	 * @param integer $event_type The "value" of the CiviCRM Event Type.
	 * @return array $field The Field data.
	 */
	public function prepare_input( $event_id, $event_type = '' ) {

		// Let's have an easy prefix.
		$prefix = 'field_' . $this->acf_slug;

		// If Event Type is not specified, find it.
		if ( empty( $event_type ) ) {
			$event = $this->acf_loader->civicrm->event->get_by_id( $event_id );
			if ( ! empty( $event ) ) {
				$event_type = $event['event_type_id'];
			}
		}

		// Rebuild Field.
		$field = [
			$prefix . '_event_id' => $event_id,
			$prefix . '_event_type' => $event_type,
		];

		// --<
		return $field;

	}



	/**
	 * Validates the values of this Field prior to saving.
	 *
	 * @since 0.5
	 *
	 * @param bool $valid The validation status.
	 * @param mixed $value The $_POST value.
	 * @param array $field The field array holding all the field options.
	 * @param string $input The corresponding input name for $_POST value.
	 * @return string|bool $valid False if not valid, or string for error message.
	 */
	public function validate_value( $valid, $value, $field, $input ) {

		// Return early if this isn't our Field.
		if ( $field['key'] !== 'field_' . $this->acf_slug . '_event_group' ) {
			return $valid;
		}

		// Return early if there is an Event ID.
		$event_id = $value['field_' . $this->acf_slug . '_event_id'];
		if ( empty( $event_id ) ) {
			$valid = __( 'Please select an Event', 'civicrm-wp-profile-sync' );
			return $valid;
		}

		// --<
		return $valid;

	}



	/**
	 * This method is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is created.
	 *
	 * @since 0.5
	 */
	public function input_admin_enqueue_scripts() {

		// Enqueue our JavaScript.
		wp_enqueue_script(
			'acf-input-' . $this->name,
			plugins_url( 'assets/js/acf/fields/civicrm-event-group-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION // Version.
		);

		// Init settings and localisation array.
		$vars = [
			'settings' => [
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			],
			'localisation' => [
				//'saving' => __( 'Saving...', 'civicrm' ),
			],
		];

		// Localise the WordPress way.
		wp_localize_script(
			'acf-input-' . $this->name,
			'CWPS_Event_Group_Vars',
			$vars
		);

	}



	/**
	 * This method is called in the admin_head action on the edit screen where
	 * this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.5
	 */
	public function input_admin_head() {

		echo '
		<style type="text/css">
			/* Hide Event Type select */
			.acf-field-cwps-participant-event-type
			{
				display: none;
			}
		</style>
		';

	}

} // Class ends.



