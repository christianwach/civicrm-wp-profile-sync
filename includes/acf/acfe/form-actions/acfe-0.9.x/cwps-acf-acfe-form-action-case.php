<?php
/**
 * "Case" ACFE Form Action Class.
 *
 * Handles the "Case" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync "Case" ACFE Form Action Class.
 *
 * A class that handles the "Case" ACFE Form Action.
 *
 * @since 0.7.0
 */
class CWPS_ACF_ACFE_Form_Action_Case extends CWPS_ACF_ACFE_Form_Action_Base {

	/**
	 * Form Action Name.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $name = 'cwps_case';

	/**
	 * Data transient key.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var string
	 */
	private $transient_key = 'cwps_acf_acfe_form_action_case';

	/**
	 * Public Case Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $public_case_fields;

	/**
	 * Fields for Contacts.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_for_contacts;

	/**
	 * Custom Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $custom_fields;

	/**
	 * Custom Field IDs.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $custom_field_ids;

	/**
	 * Case Type choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $case_type_choices;

	/**
	 * Default Case Status.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $case_status_default;

	/**
	 * Case Status choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $case_status_choices;

	/**
	 * Default Case Encounter Medium.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $case_encounter_medium;

	/**
	 * Case Encounter Medium choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $case_encounter_choices;

	/**
	 * Public Case Fields to add.
	 *
	 * These are not mapped for Post Type Sync, so need to be added.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_to_add = [
		'details' => 'wysiwyg',
		'subject' => 'text',
	];

	/**
	 * Public Case Fields to ignore.
	 *
	 * These are mapped for Post Type Sync, but need special handling.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_to_ignore = [
		'contact_id' => 'civicrm_contact',
		'creator_id' => 'civicrm_contact',
		// 'manager_id' => 'civicrm_contact',
	];

	/**
	 * Case Contact Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $contact_fields = [
		'contact_id' => 'civicrm_contact',
		'creator_id' => 'civicrm_contact',
		// 'manager_id' => 'civicrm_contact',
	];

	/**
	 * Constructor.
	 *
	 * @since 0.7.0
	 */
	public function __construct() {

		// Store references to objects.
		$this->plugin     = civicrm_wp_profile_sync();
		$this->acf_loader = $this->plugin->acf;
		$this->acfe       = $this->acf_loader->acfe;
		$this->form       = $this->acfe->form;
		$this->civicrm    = $this->acf_loader->civicrm;

		// Label this Form Action.
		$this->title = __( 'CiviCRM Case action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->name_placeholder = __( 'CiviCRM Case', 'civicrm-wp-profile-sync' );

		// Declare core Fields for this Form Action.
		$this->item = [
			'action'      => $this->name,
			'name'        => '',
			'id'          => false,
			'case'        => [
				'id'           => false,
				'case_type_id' => '',
				'contact_id'   => false,
				'creator_id'   => false,
				'manager_id'   => false,
			],
			'settings'    => [
				'case_status_id'    => '',
				'case_medium_id'    => '',
				'dismiss_if_exists' => false,
				'dismiss_message'   => '',
			],
			'conditional' => '',
		];

		// Init parent.
		parent::__construct();

	}

	/**
	 * Prepares data when the Form Action is loaded.
	 *
	 * @since 0.7.0
	 *
	 * @param array $action The array of Form Action data.
	 * @return array $action The array of data to save.
	 */
	public function prepare_load_action( $action ) {

		// Load the Action variables.
		$action['dismiss_if_exists'] = $action['settings']['dismiss_if_exists'];
		$action['dismiss_message']   = $action['settings']['dismiss_message'];
		$action['case_status_id']    = $action['settings']['case_status_id'];
		$action['case_medium_id']    = $action['settings']['case_medium_id'];

		// Load Entity data.
		foreach ( $this->public_case_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$action[ $field['name'] ] = $action['case'][ $field['name'] ];
			}
		}

		// Load additional Entity data.
		$action['case_type_id'] = $action['case']['case_type_id'];

		// Load Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			$action[ 'custom_group_' . $custom_group['id'] ] = $action['case'][ 'custom_group_' . $custom_group['id'] ];
		}

		// Load the Contacts.
		foreach ( $this->fields_for_contacts as $field ) {
			$action[ 'contact_group_' . $field['name'] ] = $action['case'][ $field['name'] ];
		}

		// --<
		return $action;

	}

	/**
	 * Prepares data for saving when the Form Action is saved.
	 *
	 * @since 0.7.0
	 *
	 * @param array $action The array of Form Action data.
	 * @return array $save The array of data to save.
	 */
	public function prepare_save_action( $action ) {

		// Init with default array for this Field.
		$save = $this->item;

		// Always add action name.
		$save['name'] = $action['name'];

		// Always save Conditional Field.
		if ( acf_maybe_get( $action, $this->conditional_code ) ) {
			$save['conditional'] = $action[ $this->conditional_code ];
		}

		// Save Action variables.
		$save['settings']['dismiss_if_exists'] = $action['dismiss_if_exists'];
		$save['settings']['dismiss_message']   = $action['dismiss_message'];
		$save['settings']['case_status_id']    = $action['case_status_id'];
		$save['settings']['case_medium_id']    = $action['case_medium_id'];

		// Save Entity data.
		foreach ( $this->public_case_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$save['case'][ $field['name'] ] = $action[ $field['name'] ];
			}
		}

		// Save additional Entity data.
		$save['case']['case_type_id'] = $action['case_type_id'];

		// Save Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			$save['case'][ 'custom_group_' . $custom_group['id'] ] = $action[ 'custom_group_' . $custom_group['id'] ];
		}

		// Save Contact References.
		foreach ( $this->fields_for_contacts as $field ) {
			$save['case'][ $field['name'] ] = $action[ 'contact_group_' . $field['name'] ];
		}

		// --<
		return $save;

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.7.0
	 */
	public function initialize() {

		// Maybe check our transient for cached data.
		$data            = false;
		$acfe_transients = (int) $this->plugin->admin->setting_get( 'acfe_integration_transients', 0 );
		if ( 1 === $acfe_transients ) {
			$data = get_site_transient( $this->transient_key );
		}

		// Init transient data if none found.
		if ( false === $data ) {
			$transient = [];
		}

		// Get the public Case Fields for all Case Types from transient if possible.
		if ( false !== $data && isset( $data['public_case_fields'] ) ) {
			$this->public_case_fields = $data['public_case_fields'];
		} else {

			// Get the public Case Fields for all Case Types.
			$this->public_case_fields = $this->civicrm->case_field->get_public_fields( 'create' );

			/*
			// Prepend the ones that are needed in ACFE Forms (i.e. Subject and Details).
			foreach ( $this->fields_to_add as $name => $field_type ) {
				array_unshift( $this->public_case_fields, $this->civicrm->case_field->get_by_name( $name ) );
			}
			*/

			$transient['public_case_fields'] = $this->public_case_fields;

		}

		// Get Fields for Contacts from transient if possible.
		if ( false !== $data && isset( $data['fields_for_contacts'] ) ) {
			$this->fields_for_contacts = $data['fields_for_contacts'];
		} else {
			foreach ( $this->contact_fields as $name => $field_type ) {
				$field                       = $this->civicrm->case_field->get_by_name( $name, 'create' );
				$this->fields_for_contacts[] = $field;
			}
			$transient['fields_for_contacts'] = $this->fields_for_contacts;
		}

		// Handle Contact Fields.
		foreach ( $this->fields_for_contacts as $field ) {

			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( 'ref_' . $field['name'] );

		}

		// Add "Manager ID" to Contact Fields.
		$field = [
			'name'  => 'manager_id',
			'title' => __( 'Case Manager', 'civicrm-wp-profile-sync' ),
		];
		$this->js_model_contact_reference_field_add( 'ref_' . $field['name'] );
		$this->fields_for_contacts[] = $field;

		// Get the Custom Groups and Fields for all Case Types from transient if possible.
		if ( false !== $data && isset( $data['custom_fields'] ) ) {
			$this->custom_fields = $data['custom_fields'];
		} else {
			$this->custom_fields        = $this->plugin->civicrm->custom_group->get_for_cases();
			$transient['custom_fields'] = $this->custom_fields;
		}

		// Build Custom Field IDs.
		$this->custom_field_ids = [];
		foreach ( $this->custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->custom_field_ids[] = (int) $custom_field['id'];
				}
			}
		}

		// Finally, let's try and cache queries made in tabs.

		// Get Case Type choices from transient if possible.
		if ( false !== $data && isset( $data['case_type_choices'] ) ) {
			$this->case_type_choices = $data['case_type_choices'];
		} else {
			$this->case_type_choices        = $this->civicrm->case_type->choices_get();
			$transient['case_type_choices'] = $this->case_type_choices;
		}

		// Get default Case Status from transient if possible.
		if ( false !== $data && isset( $data['case_status_default'] ) ) {
			$this->case_status_default = $data['case_status_default'];
		} else {
			$this->case_status_default        = $this->civicrm->option_value_default_get( 'case_status' );
			$transient['case_status_default'] = $this->case_status_default;
		}

		// Get Case Status choices from transient if possible.
		if ( false !== $data && isset( $data['case_status_choices'] ) ) {
			$this->case_status_choices = $data['case_status_choices'];
		} else {
			$this->case_status_choices        = $this->civicrm->case_field->options_get( 'case_status_id' );
			$transient['case_status_choices'] = $this->case_status_choices;
		}

		// Get default Case Encounter Medium from transient if possible.
		if ( false !== $data && isset( $data['case_encounter_medium'] ) ) {
			$this->case_encounter_medium = $data['case_encounter_medium'];
		} else {
			$this->case_encounter_medium        = $this->civicrm->option_value_default_get( 'encounter_medium' );
			$transient['case_encounter_medium'] = $this->case_encounter_medium;
		}

		// Get Case Encounter Medium choices from transient if possible.
		if ( false !== $data && isset( $data['case_encounter_choices'] ) ) {
			$this->case_encounter_choices = $data['case_encounter_choices'];
		} else {
			$this->case_encounter_choices        = $this->civicrm->case_field->options_get( 'case_medium_id' );
			$transient['case_encounter_choices'] = $this->case_encounter_choices;
		}

		// Maybe store Fields in transient.
		if ( false === $data && 1 === $acfe_transients ) {
			$duration = $this->acfe->admin->transient_duration_get();
			set_site_transient( $this->transient_key, $transient, $duration );
		}

	}

	/**
	 * Performs validation when the Form the Action is attached to is submitted.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 */
	public function validate_action( $form, $action ) {

		// Skip if the Contact Conditional Reference Field has a value.
		$this->form_conditional_populate( [ 'action' => &$action ] );
		if ( ! $this->form_conditional_check( [ 'action' => $action ] ) ) {
			return;
		}

		// Validate the Case data.
		$valid = $this->form_case_validate( $form, $action );
		if ( ! $valid ) {
			return;
		}

		// TODO: Check other Case Entities.

	}

	/**
	 * Performs the action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.7.0
	 *
	 * @param array $form The array of Form data.
	 * @param array $action The array of Action data.
	 */
	public function make_action( $form, $action ) {

		// Bail if a filter has overridden the action.
		if ( false === $this->make_skip( $form, $action ) ) {
			return;
		}

		// Init result array to save for this Action.
		$result = $this->item;

		// Always add action name.
		$result['form_action'] = $this->name;
		$result['name']        = $action['name'];

		// Bail early if the Conditional Reference Field has a value.
		$this->form_conditional_populate( [ 'action' => &$action ] );
		if ( ! $this->form_conditional_check( [ 'action' => $action ] ) ) {
			// Save the results of this Action for later use.
			$this->make_action_save( $action, $result );
			return;
		}

		// Populate Case, Email, Relationship and Custom Field data arrays.
		$case = $this->form_case_data( $form, $action );

		// Build Contact Custom Field args.
		$args = [
			'custom_groups' => $this->custom_fields,
		];

		// Get populated Custom Field data array.
		$custom_fields = $this->form_entity_custom_fields_data( $action['case'], $args );

		// Save the Case with the data from the Form.
		$result['case'] = $this->form_case_save( $case, $custom_fields );

		// If we get a Case.
		if ( false !== $result['case'] ) {

			// Post-process Custom Fields now that we have a Case.
			$this->form_entity_custom_fields_post_process( $form, $action, $result['case'], 'case' );

			// Save the Case ID for backwards compatibility.
			$result['id'] = (int) $result['case']['id'];

		}

		// Save the results of this Action for later use.
		$this->make_action_save( $action, $result );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Defines additional Fields for the "Action" Tab.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_action_append() {

		// Define Case Type Field.
		$case_type_field = [
			'key'               => $this->field_key . 'case_type_id',
			'label'             => __( 'Case Type', 'civicrm-wp-profile-sync' ),
			'name'              => 'case_type_id',
			'type'              => 'select',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions'  => '',
			'default_value'     => '',
			'placeholder'       => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'return_format'     => 'value',
			'choices'           => $this->case_type_choices,
		];

		// Define Case Status Field.
		$case_status_field = [
			'key'               => $this->field_key . 'case_status_id',
			'label'             => __( 'Case Status', 'civicrm-wp-profile-sync' ),
			'name'              => 'case_status_id',
			'type'              => 'select',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions'  => '',
			'default_value'     => $this->case_status_default,
			'placeholder'       => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'return_format'     => 'value',
			'choices'           => $this->case_status_choices,
		];

		// Define Case "Activity Medium" Field.
		$case_activity_medium_field = [
			'key'               => $this->field_key . 'case_medium_id',
			'label'             => __( 'Activity Medium', 'civicrm-wp-profile-sync' ),
			'name'              => 'case_medium_id',
			'type'              => 'select',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions'  => '',
			'default_value'     => $this->case_encounter_medium,
			'placeholder'       => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'return_format'     => 'value',
			'choices'           => $this->case_encounter_choices,
		];

		// Configure Conditional Field.
		$args = [
			'placeholder'  => __( 'Always add', 'civicrm-wp-profile-sync' ),
			'instructions' => __( 'To add the Case only when a Form Field is populated (e.g. "Subject") link this to the Form Field. To add the Case only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' ),
		];

		// Add Conditional Field.
		$conditional = $this->form_conditional_field_get( $args );

		// Define "Dismiss if exists" Field.
		$dismiss_if_exists_field = [
			'key'               => $this->field_key . 'dismiss_if_exists',
			'label'             => __( 'Skip creating the Case?', 'civicrm-wp-profile-sync' ),
			'name'              => 'dismiss_if_exists',
			'type'              => 'true_false',
			'instructions'      => __( 'Skip creating a Case if the Contact already has a Case of this Type. See "Cheatsheet" for how to reference the "created" and "skipped" variables in this action to make other Form Actions conditional on whether the Case is created or skipped.', 'civicrm-wp-profile-sync' ),
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions'  => '',
			'message'           => '',
			'default_value'     => 0,
			'ui'                => 1,
			'ui_on_text'        => '',
			'ui_off_text'       => '',
		];

		// Define "Dismissed Message" Field.
		$dismiss_message_field = [
			'key'               => $this->field_key . 'dismiss_message',
			'label'             => __( 'Message', 'civicrm-wp-profile-sync' ),
			'name'              => 'dismiss_message',
			'type'              => 'textarea',
			'instructions'      => __( 'Message to display on the Success Page if the Case of this Type already exists. See "Cheatsheet" for how to reference the "dismiss_message" variable in this action.', 'civicrm-wp-profile-sync' ),
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => $this->field_key . 'dismiss_if_exists',
						// 'operator' => '!=empty',
						'operator' => '==',
						'value'    => '1',
					],
				],
			],
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions'  => '',
			'placeholder'       => '',
			'maxlength'         => '',
			'rows'              => 4,
			'new_lines'         => '',
		];

		// Init Fields.
		$fields = [
			$case_type_field,
			$case_status_field,
			$case_activity_medium_field,
			$conditional,
			$dismiss_if_exists_field,
			$dismiss_message_field,
		];

		// --<
		return $fields;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Defines the "Mapping" Tab.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_add() {

		// Get Tab Header.
		$mapping_tab_header = $this->tab_mapping_header();

		// Build Contacts Accordion.
		$mapping_contacts_accordion = $this->tab_mapping_accordion_contacts_add();

		// Build Case Details Accordion.
		$mapping_case_accordion = $this->tab_mapping_accordion_case_add();

		// Build Custom Fields Accordion.
		$mapping_custom_accordion = $this->tab_mapping_accordion_custom_add();

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$mapping_contacts_accordion,
			$mapping_case_accordion,
			$mapping_custom_accordion
		);

		// --<
		return $fields;

	}

	/**
	 * Defines the Fields in the "Contacts" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_contacts_add() {

		// Init return.
		$fields = [];

		// "Contact References" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_contacts_open',
			'label'             => __( 'Contact References', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'acfe_permissions'  => '',
			'open'              => 0,
			'multi_expand'      => 1,
			'endpoint'          => 0,
		];

		// Add Contact Reference Fields.
		foreach ( $this->fields_for_contacts as $field ) {

			// Bundle them into a container group.
			$contact_group_field = [
				'key'          => $this->field_key . 'contact_group_' . $field['name'],
				'label'        => $field['title'],
				'name'         => 'contact_group_' . $field['name'],
				'type'         => 'group',
				/* translators: %s: The name of the Field */
				'instructions' => sprintf( __( 'Use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $field['title'] ),
				'wrapper'      => [
					'width' => '',
					'class' => '',
					'id'    => '',
				],
				'required'     => 0,
				'layout'       => 'block',
			];

			// Define Contact Action Reference Field.
			$contact_group_field['sub_fields'][] = [
				'key'               => $this->field_key . 'ref_' . $field['name'],
				'label'             => __( 'CiviCRM Contact Action', 'civicrm-wp-profile-sync' ),
				'name'              => 'ref_' . $field['name'],
				'type'              => 'cwps_acfe_contact_action_ref',
				'instructions'      => __( 'Select a Contact Action in this Form.', 'civicrm-wp-profile-sync' ),
				'required'          => 0,
				'wrapper'           => [
					'width' => '',
					'class' => '',
					'id'    => '',
				],
				'acfe_permissions'  => '',
				'default_value'     => '',
				'placeholder'       => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null'        => 0,
				'multiple'          => 0,
				'ui'                => 0,
				'return_format'     => 'value',
				'choices'           => [],
				'conditional_logic' => [
					[
						[
							'field'    => $this->field_key . $field['name'],
							'operator' => '==empty',
						],
						[
							'field'    => $this->field_key . 'cid_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Define Contact ID Field.
			$contact_group_field['sub_fields'][] = [
				'key'               => $this->field_key . 'cid_' . $field['name'],
				'label'             => __( 'CiviCRM Contact ID', 'civicrm-wp-profile-sync' ),
				'name'              => 'cid_' . $field['name'],
				'type'              => 'civicrm_contact',
				'instructions'      => __( 'Select a CiviCRM Contact ID from the database.', 'civicrm-wp-profile-sync' ),
				'required'          => 0,
				'wrapper'           => [
					'width' => '',
					'class' => '',
					'id'    => '',
				],
				'acfe_permissions'  => '',
				'default_value'     => '',
				'placeholder'       => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null'        => 0,
				'multiple'          => 0,
				'ui'                => 0,
				'return_format'     => 'value',
				'choices'           => [],
				'conditional_logic' => [
					[
						[
							'field'    => $this->field_key . 'ref_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field'    => $this->field_key . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Define Custom Contact Reference Field.
			$title                              = sprintf( __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' ), $field['title'] );
			$mapping_field                      = $this->mapping_field_get( $field['name'], $title );
			$mapping_field['instructions']      = __( 'Define a custom Contact Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field'    => $this->field_key . 'ref_' . $field['name'],
						'operator' => '==empty',
					],
					[
						'field'    => $this->field_key . 'cid_' . $field['name'],
						'operator' => '==empty',
					],
				],
			];

			// Add Custom Contact Reference Field.
			$contact_group_field['sub_fields'][] = $mapping_field;

			// Add Contact Reference Group.
			$fields[] = $contact_group_field;

		}

		// "Contact References" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_contacts_close',
			'label'             => __( 'Contact References', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'acfe_permissions'  => '',
			'open'              => 0,
			'multi_expand'      => 1,
			'endpoint'          => 1,
		];

		// --<
		return $fields;

	}

	/**
	 * Defines the Fields in the "Case Fields" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_case_add() {

		// Init return.
		$fields = [];

		// "Case Fields" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_case_open',
			'label'             => __( 'Case Fields', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'acfe_permissions'  => '',
			'open'              => 0,
			'multi_expand'      => 1,
			'endpoint'          => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->public_case_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Case Fields" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_case_close',
			'label'             => __( 'Case Fields', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'acfe_permissions'  => '',
			'open'              => 0,
			'multi_expand'      => 1,
			'endpoint'          => 1,
		];

		// --<
		return $fields;

	}

	/**
	 * Defines the Fields in the "Custom Fields" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_custom_add() {

		// Init return.
		$fields = [];

		// "Custom Fields" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_custom_open',
			'label'             => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'acfe_permissions'  => '',
			'open'              => 0,
			'multi_expand'      => 1,
			'endpoint'          => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Skip if there are no Custom Fields.
			if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
				continue;
			}

			// Get the Case Type ID.
			$case_type_ids = [];
			if ( ! empty( $custom_group['extends_entity_column_value'] ) ) {
				$case_type_ids = $custom_group['extends_entity_column_value'];
			}

			// Init conditional logic.
			$conditional_logic = [];

			// Add Sub-types as OR conditionals if present.
			if ( ! empty( $case_type_ids ) ) {
				foreach ( $case_type_ids as $case_type_id ) {

					$case_type = [
						'field'    => $this->field_key . 'case_type_id',
						'operator' => '==contains',
						'value'    => $case_type_id,
					];

					$conditional_logic[] = [
						$case_type,
					];

				}
			}

			// Bundle the Custom Fields into a container group.
			$custom_group_field = [
				'key'                   => $this->field_key . 'custom_group_' . $custom_group['id'],
				'label'                 => $custom_group['title'],
				'name'                  => 'custom_group_' . $custom_group['id'],
				'type'                  => 'group',
				'instructions'          => '',
				'instruction_placement' => 'field',
				'required'              => 0,
				'layout'                => 'block',
				'conditional_logic'     => $conditional_logic,
			];

			// Init sub Fields array.
			$sub_fields = [];

			// Add "Map" Fields for the Custom Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code         = 'custom_' . $custom_field['id'];
				$sub_fields[] = $this->mapping_field_get( $code, $custom_field['label'], $conditional_logic );
			}

			// Add the Sub-fields.
			$custom_group_field['sub_fields'] = $sub_fields;

			// Add the Sub-fields.
			$fields[] = $custom_group_field;

		}

		// "Custom Fields" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_custom_close',
			'label'             => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'acfe_permissions'  => '',
			'open'              => 0,
			'multi_expand'      => 1,
			'endpoint'          => 1,
		];

		// --<
		return $fields;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Builds Case data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Case data.
	 */
	public function form_case_data( $form, $action ) {

		// Init data array.
		$data = [];

		// Set ACFE "context". We want to apply tags.
		acfe_add_context( $this->context_save );

		// Build Fields array.
		foreach ( $this->public_case_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				acfe_apply_tags( $action['case'][ $field['name'] ] );
				$data[ $field['name'] ] = $action['case'][ $field['name'] ];
			}
		}

		// Reset the ACFE "context".
		acfe_delete_context( array_keys( $this->context_save ) );

		// Get the "Dismiss if exists" Field.
		$data['dismiss_if_exists'] = $action['settings']['dismiss_if_exists'];

		// Get the "Dismissed Message" Field.
		$data['dismiss_message'] = $action['settings']['dismiss_message'];

		// Get the Case Type.
		$data['case_type_id'] = $action['case']['case_type_id'];

		// Only set "Case Status" and "Encounter Medium" when there is no mapped Field.
		if ( empty( $data['status_id'] ) ) {
			$data['status_id'] = $action['settings']['case_status_id'];
		}
		if ( empty( $data['medium_id'] ) ) {
			$data['medium_id'] = $action['settings']['case_medium_id'];
		}

		// Get the Case Contacts.
		foreach ( $this->fields_for_contacts as $field ) {

			// Get Group Field.
			$contact_group_field = $action['case'][ $field['name'] ];

			// Check Action Reference Field.
			$contact_id = false;
			if ( ! empty( $contact_group_field[ 'ref_' . $field['name'] ] ) ) {
				$action_name = $contact_group_field[ 'ref_' . $field['name'] ];
				$contact_id  = $this->form_contact_id_get_mapped( $action_name );
			}

			// Check Contact ID Field.
			if ( false === $contact_id ) {
				if ( ! empty( $contact_group_field[ 'cid_' . $field['name'] ] ) ) {
					$contact_id = $contact_group_field[ 'cid_' . $field['name'] ];
				}
			}

			// Check mapped Field.
			if ( false === $contact_id ) {
				if ( ! empty( $contact_group_field[ $field['name'] ] ) ) {
					acfe_apply_tags( $contact_group_field[ $field['name'] ], $this->context_save );
					if ( ! empty( $contact_group_field[ $field['name'] ] ) && is_numeric( $contact_group_field[ $field['name'] ] ) ) {
						$contact_id = $contact_group_field[ $field['name'] ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $contact_id ) && is_numeric( $contact_id ) ) {
				$data[ $field['name'] ] = (int) $contact_id;
			}

		}

		// --<
		return $data;

	}

	/**
	 * Validates the Case data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Case can be saved, false otherwise.
	 */
	public function form_case_validate( $form, $action ) {

		// Get the Case.
		$case = $this->form_case_data( $form, $action );

		/*
		 * We have a problem here because the ACFE Forms Actions query var has
		 * not been populated yet since the "make" actions have not run.
		 *
		 * This means that "$this->get_action_output()" cannot be queried to find
		 * the referenced Contact ID when using an "Action Reference" Field,
		 * even though it will be populated later when the "make" actions run.
		 *
		 * Other methods for defining the Contact ID will still validate, but
		 * we're going to have to exclude this check for now.
		 */

		/*
		// Reject the submission if there is no Contact ID.
		if ( empty( $case['contact_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				// / * translators: %s The name of the Form Action * /
				__( 'A Contact ID is required to create a Case in "%s".', 'civicrm-wp-profile-sync' ),
				$action['name']
			) );
			return false;
		}
		*/

		// Reject the submission if the Case Type ID is missing.
		if ( empty( $case['case_type_id'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A Case Type ID is required to create a Case in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Valid.
		return true;

	}

	/**
	 * Saves the CiviCRM Case given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $case_data The array of Case data.
	 * @param array $custom_data The array of Custom Field data.
	 * @return array|bool $case The Case data array, or false on failure.
	 */
	public function form_case_save( $case_data, $custom_data ) {

		// Init return.
		$case = false;

		// When skipping, get the existing Case and return early if it exists.
		if ( ! empty( $case_data['dismiss_if_exists'] ) ) {
			$case = $this->civicrm->case->get_by_type_and_contact( $case_data['case_type_id'], $case_data['contact_id'] );
			if ( false !== $case ) {

				// Flag that creating the Case has been skipped.
				$case['skipped'] = true;

				// Flag that the Case was not created.
				$case['created'] = false;

				// Add "Dismissed Message" in case it's used on Success Page.
				$case['dismiss_message'] = $case_data['dismiss_message'];

				return $case;

			}
		}

		// Add Custom Field data if present.
		if ( ! empty( $custom_data ) ) {
			$case_data += $custom_data;
		}

		// Strip out empty Fields.
		$case_data = $this->form_data_prepare( $case_data );

		// If a Case ID is set.
		if ( ! empty( $case_data['id'] ) && is_numeric( $case_data['id'] ) ) {

			// Build params to create a "Case Contact".
			$params = [
				'case_id'    => $case_data['id'],
				'contact_id' => $case_data['contact_id'],
			];

			// Add the Case Contact.
			$result = $this->civicrm->case->contact_create( $params );
			if ( false === $result ) {
				return $case;
			}

			// Start result afresh for query below.
			$result = [
				'id' => $case_data['id'],
			];

		} else {

			// Let's create the Case.
			$result = $this->civicrm->case->create( $case_data );
			if ( false === $result ) {
				return $case;
			}

		}

		// Get the full Case data.
		$case = $this->civicrm->case->get_by_id( $result['id'] );

		// Flag that creating the Case has not been skipped.
		$case['skipped'] = false;

		// Flag that the Case was created.
		$case['created'] = true;

		// Add empty "Dismissed Message" in case it's used on Success Page.
		$case['dismiss_message'] = '';

		// Assign the Case Manager if defined.
		$this->civicrm->case->manager_add( $case_data, $case );

		// --<
		return $case;

	}

}
