<?php
/**
 * "Participant" ACFE Form Action Class.
 *
 * Handles the "Participant" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync "Participant" ACFE Form Action Class.
 *
 * A class that handles the "Participant" ACFE Form Action.
 *
 * @since 0.7.0
 */
class CWPS_ACF_ACFE_Form_Action_Participant extends CWPS_ACF_ACFE_Form_Action_Base {

	/**
	 * Form Action Name.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $name = 'cwps_participant';

	/**
	 * Data transient key.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var string
	 */
	private $transient_key = 'cwps_acf_acfe_form_action_participant';

	/**
	 * Public Participant Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $public_participant_fields;

	/**
	 * Fields for Contacts.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_for_contacts;

	/**
	 * Fields for Participants.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_for_participants;

	/**
	 * Fields for Events.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_for_events;

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
	 * Participant Role choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $participant_role_choices;

	/**
	 * Participant Roles that count towards the total for the Event.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $participant_roles_counted;

	/**
	 * Participant Status choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $participant_status_ids;

	/**
	 * Campaign choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $campaign_choices;

	/**
	 * Event Type choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_type_choices;

	/**
	 * Public Participant Fields to add.
	 *
	 * These are not mapped for Post Type Sync, so need to be added.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_to_add = [
		// 'must_wait' => 'select',
	];

	/**
	 * Public Participant Fields to ignore.
	 *
	 * These are mapped for Post Type Sync, but need special handling.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_to_ignore = [
		'contact_id'    => 'civicrm_contact',
		'event_id'      => 'civicrm_event',
		'status_id'     => 'select',
		'register_date' => 'date_time_picker',
	];

	/**
	 * Participant Contact Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $contact_fields = [
		'contact_id' => 'civicrm_contact',
	];

	/**
	 * Participant Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $participant_fields = [
		'registered_by_id' => 'civicrm_participant',
	];

	/**
	 * Participant Event Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_fields = [
		'event_id' => 'civicrm_event',
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
		$this->title = __( 'CiviCRM Participant action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->name_placeholder = __( 'CiviCRM Participant', 'civicrm-wp-profile-sync' );

		// Declare core Fields for this Form Action.
		$this->item = [
			'action'      => $this->name,
			'name'        => '',
			'id'          => false,
			'participant' => [
				'id'         => false,
				'event_id'   => false,
				'contact_id' => false,
			],
			'settings'    => [
				'add_anyway'    => false,
				'email_receipt' => false,
				'event_type'    => '',
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

		// Load Action variables.
		$action['add_anyway']    = $action['settings']['add_anyway'];
		$action['email_receipt'] = $action['settings']['email_receipt'];
		$action['event_type']    = $action['settings']['event_type'];

		// Load Entity data.
		foreach ( $this->public_participant_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$action[ $field['name'] ] = $action['participant'][ $field['name'] ];
			}
		}

		// Load additional Entity data.
		$action['participant_role_id']   = $action['participant']['role_id'];
		$action['participant_status_id'] = $action['participant']['status_id'];

		// Load Contact References.
		foreach ( $this->contact_fields as $name => $title ) {
			$action[ 'contact_group_' . $name ] = $action['participant'][ $name ];
		}

		// Load Participant References.
		foreach ( $this->participant_fields as $name => $title ) {
			$action[ 'participant_group_' . $name ] = $action['participant'][ $name ];
		}

		// Load Event References.
		foreach ( $this->event_fields as $name => $title ) {
			$action[ 'event_group_' . $name ] = $action['participant'][ $name ];
		}

		// Load Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			if ( array_key_exists( 'custom_group_' . $custom_group['id'], $action['participant'] ) ) {
				$action[ 'custom_group_' . $custom_group['id'] ] = $action['participant'][ 'custom_group_' . $custom_group['id'] ];
			}
		}

		// Load Campaign ID if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {
			$action['participant_campaign_id'] = $action['participant']['campaign_id'];
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
		$save['settings']['add_anyway']    = $action['add_anyway'];
		$save['settings']['email_receipt'] = $action['email_receipt'];
		$save['settings']['event_type']    = $action['event_type'];

		// Save Entity data.
		foreach ( $this->public_participant_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$save['participant'][ $field['name'] ] = $action[ $field['name'] ];
			}
		}

		// Save additional Entity data.
		$save['participant']['role_id']   = $action['participant_role_id'];
		$save['participant']['status_id'] = $action['participant_status_id'];

		// Save Contact References.
		foreach ( $this->contact_fields as $name => $title ) {
			$save['participant'][ $name ] = $action[ 'contact_group_' . $name ];
		}

		// Save Participant References.
		foreach ( $this->participant_fields as $name => $title ) {
			$save['participant'][ $name ] = $action[ 'participant_group_' . $name ];
		}

		// Save Event References.
		foreach ( $this->event_fields as $name => $title ) {
			$save['participant'][ $name ] = $action[ 'event_group_' . $name ];
		}

		// Save Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			$save['participant'][ 'custom_group_' . $custom_group['id'] ] = $action[ 'custom_group_' . $custom_group['id'] ];
		}

		// Save Campaign ID if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {
			$save['participant']['campaign_id'] = $action['participant_campaign_id'];
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

		// Get the public Participant Fields for all Participants from transient if possible.
		if ( false !== $data && isset( $data['public_participant_fields'] ) ) {
			$this->public_participant_fields = $data['public_participant_fields'];
		} else {

			// Get the public Participant Fields for all Participants.
			$this->public_participant_fields = $this->civicrm->participant_field->get_public_fields();

			// Prepend the ones that are needed in ACFE Forms (i.e. Subject and Details).
			if ( ! empty( $this->fields_to_add ) ) {
				foreach ( $this->fields_to_add as $name => $field_type ) {
					array_unshift( $this->public_participant_fields, $this->civicrm->participant_field->get_by_name( $name ) );
				}
			}

			$transient['public_participant_fields'] = $this->public_participant_fields;

		}

		// Get Fields for Contacts from transient if possible.
		if ( false !== $data && isset( $data['fields_for_contacts'] ) ) {
			$this->fields_for_contacts = $data['fields_for_contacts'];
		} else {
			foreach ( $this->contact_fields as $name => $field_type ) {
				$field                       = $this->civicrm->participant_field->get_by_name( $name );
				$this->fields_for_contacts[] = $field;
			}
			$transient['fields_for_contacts'] = $this->fields_for_contacts;
		}

		// Handle Contact Fields.
		foreach ( $this->fields_for_contacts as $field ) {
			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( 'ref_' . $field['name'] );
		}

		// Get Fields for Participants from transient if possible.
		if ( false !== $data && isset( $data['fields_for_participants'] ) ) {
			$this->fields_for_participants = $data['fields_for_participants'];
		} else {
			foreach ( $this->participant_fields as $name => $field_type ) {
				$field                           = $this->civicrm->participant_field->get_by_name( $name );
				$this->fields_for_participants[] = $field;
			}
			$transient['fields_for_participants'] = $this->fields_for_participants;
		}

		// Handle Participant Fields.
		foreach ( $this->fields_for_participants as $field ) {
			// Add Participant Action Reference Field to ACF Model.
			$this->js_model_participant_reference_field_add( 'ref_' . $field['name'] );
		}

		// Get Fields for Events from transient if possible.
		if ( false !== $data && isset( $data['fields_for_events'] ) ) {
			$this->fields_for_events = $data['fields_for_events'];
		} else {
			foreach ( $this->event_fields as $name => $field_type ) {
				$field                     = $this->civicrm->participant_field->get_by_name( $name );
				$this->fields_for_events[] = $field;
			}
			$transient['fields_for_events'] = $this->fields_for_events;
		}

		// Get the Custom Groups and Fields for all Participants from transient if possible.
		if ( false !== $data && isset( $data['custom_fields'] ) ) {
			$this->custom_fields = $data['custom_fields'];
		} else {
			$this->custom_fields        = $this->plugin->civicrm->custom_group->get_for_participants();
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

		// Get Participant Role choices from transient if possible.
		if ( false !== $data && isset( $data['participant_role_choices'] ) ) {
			$this->participant_role_choices = $data['participant_role_choices'];
		} else {
			$this->participant_role_choices        = $this->civicrm->participant_role->choices_get();
			$transient['participant_role_choices'] = $this->participant_role_choices;
		}

		// Get Participant Roles that count towards the total for the Event from transient if possible.
		if ( false !== $data && isset( $data['participant_roles_counted'] ) ) {
			$this->participant_roles_counted = $data['participant_roles_counted'];
		} else {
			$this->participant_roles_counted        = $this->civicrm->participant_role->get_counted();
			$transient['participant_roles_counted'] = $this->participant_roles_counted;
		}

		// Get Participant Status choices from transient if possible.
		if ( false !== $data && isset( $data['participant_status_ids'] ) ) {
			$this->participant_status_ids = $data['participant_status_ids'];
		} else {
			$this->participant_status_ids        = $this->civicrm->participant_field->options_get( 'status_id' );
			$transient['participant_status_ids'] = $this->participant_status_ids;
		}

		// Get Campaign choices from transient if possible.
		if ( $this->civicrm->is_component_enabled( 'CiviCampaign' ) ) {
			if ( false !== $data && isset( $data['campaign_choices'] ) ) {
				$this->campaign_choices = $data['campaign_choices'];
			} else {
				$this->campaign_choices        = $this->civicrm->campaign->choices_get();
				$transient['campaign_choices'] = $this->campaign_choices;
			}
		}

		// Get Event Type choices from transient if possible.
		if ( false !== $data && isset( $data['event_type_choices'] ) ) {
			$this->event_type_choices = $data['event_type_choices'];
		} else {
			$this->event_type_choices        = $this->acf_loader->civicrm->event->types_get_options();
			$transient['event_type_choices'] = $this->event_type_choices;
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

		// Validate the Participant data.
		$valid = $this->form_participant_validate( $form, $action );
		if ( ! $valid ) {
			return;
		}

		// TODO: Check other Participant Entities.

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

		// Populate Participant data array.
		$participant = $this->form_participant_data( $form, $action );

		// Build Contact Custom Field args.
		$args = [
			'custom_groups' => $this->custom_fields,
		];

		// Get populated Custom Field data array.
		$custom_fields = $this->form_entity_custom_fields_data( $action['participant'], $args );

		// Save the Participant with the data from the Form.
		$result['participant'] = $this->form_participant_save( $participant, $custom_fields );

		// If we get a Participant.
		if ( ! empty( $result['participant'] ) ) {

			// Post-process Custom Fields now that we have a Participant.
			$this->form_entity_custom_fields_post_process( $form, $action, $result['participant'], 'participant' );

			// Save the Participant ID for backwards compatibility.
			$result['id'] = (int) $result['participant']['id'];

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
	protected function tab_action_append() {

		// Define Field.
		$participant_role_id_field = [
			'key'               => $this->field_key . 'participant_role_id',
			'label'             => __( 'Participant Role', 'civicrm-wp-profile-sync' ),
			'name'              => 'participant_role_id',
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
			'choices'           => $this->participant_role_choices,
		];

		// Get the Participant Roles that count towards the total for the Event.
		$conditional_logic = [];
		if ( ! empty( $this->participant_roles_counted ) ) {
			foreach ( $this->participant_roles_counted as $role_id => $role_name ) {
				// Add an OR condition for each entry.
				$conditional_logic[] = [
					[
						'field'    => $this->field_key . 'participant_role_id',
						'operator' => '==',
						'value'    => $role_id,
					],
				];
			}
		}

		// Define "Add anyway" Field.
		$participant_add_anyway = [
			'key'               => $this->field_key . 'add_anyway',
			'label'             => __( 'Add when full?', 'civicrm-wp-profile-sync' ),
			'name'              => 'add_anyway',
			'type'              => 'true_false',
			'instructions'      => __( 'The selected Participant Role is included in the "Max Number of Participants" total. Choose whether the Participant should be added even when the "Max Number" has been reached.', 'civicrm-wp-profile-sync' ),
			'required'          => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'conditional_logic' => $conditional_logic,
			'acfe_permissions'  => '',
			'message'           => '',
			'default_value'     => 0,
			'ui'                => 1,
			'ui_on_text'        => '',
			'ui_off_text'       => '',
		];

		// Define "Email receipt?" Field.
		$participant_email_receipt = [
			'key'               => $this->field_key . 'email_receipt',
			'label'             => __( 'Email receipt?', 'civicrm-wp-profile-sync' ),
			'name'              => 'email_receipt',
			'type'              => 'true_false',
			'instructions'      => '',
			'required'          => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'conditional_logic' => 0,
			'acfe_permissions'  => '',
			'message'           => '',
			'default_value'     => 0,
			'ui'                => 1,
			'ui_on_text'        => '',
			'ui_off_text'       => '',
		];

		// Define Status Field.
		$participant_status_field = [
			'key'               => $this->field_key . 'participant_status_id',
			'label'             => __( 'Participant Status', 'civicrm-wp-profile-sync' ),
			'name'              => 'participant_status_id',
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
			'choices'           => $this->participant_status_ids,
		];

		// Init Fields.
		$fields = [
			$participant_role_id_field,
			$participant_add_anyway,
			$participant_email_receipt,
			$participant_status_field,
		];

		// Add Campaign Field if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {

			$fields[] = [
				'key'               => $this->field_key . 'participant_campaign_id',
				'label'             => __( 'Campaign', 'civicrm-wp-profile-sync' ),
				'name'              => 'participant_campaign_id',
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
				'allow_null'        => 1,
				'multiple'          => 0,
				'ui'                => 0,
				'return_format'     => 'value',
				'choices'           => $this->campaign_choices,
			];

		}

		// Configure Conditional Field.
		$args = [
			'placeholder'  => __( 'Always add', 'civicrm-wp-profile-sync' ),
			'instructions' => __( 'To add the Participant only when a Form Field is populated (e.g. "Email") link this to the Form Field. To add the Participant only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' ),
		];

		// Add Conditional Field.
		$fields[] = $this->form_conditional_field_get( $args );

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
	protected function tab_mapping_add() {

		// Get Tab Header.
		$mapping_tab_header = $this->tab_mapping_header();

		// Build Contacts Accordion.
		$mapping_contacts_accordion = $this->tab_mapping_accordion_contacts_add();

		// Build Participants Accordion.
		$mapping_participants_accordion = $this->tab_mapping_accordion_participants_add();

		// Build Event Accordion.
		$mapping_event_accordion = $this->tab_mapping_accordion_event_add();

		// Build Participant Details Accordion.
		$mapping_participant_accordion = $this->tab_mapping_accordion_participant_add();

		// Build Custom Fields Accordion.
		$mapping_custom_accordion = $this->tab_mapping_accordion_custom_add();

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$mapping_contacts_accordion,
			$mapping_participants_accordion,
			$mapping_event_accordion,
			$mapping_participant_accordion,
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
	private function tab_mapping_accordion_contacts_add() {

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
			$title                              = __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' );
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
	 * Defines the Fields in the "Participants" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_participants_add() {

		// Init return.
		$fields = [];

		// "Participant References" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_participants_open',
			'label'             => __( 'Participant References', 'civicrm-wp-profile-sync' ),
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

		// Add Participant Reference Fields.
		foreach ( $this->fields_for_participants as $field ) {

			// Bundle them into a container group.
			$participant_group_field = [
				'key'          => $this->field_key . 'participant_group_' . $field['name'],
				'label'        => $field['title'],
				'name'         => 'participant_group_' . $field['name'],
				'type'         => 'group',
				/* translators: %s: The Field title */
				'instructions' => sprintf( __( 'If the Participant is not the Submitter, use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $field['title'] ),
				'wrapper'      => [
					'width' => '',
					'class' => '',
					'id'    => '',
				],
				'required'     => 0,
				'layout'       => 'block',
			];

			// Define Participant Action Reference Field.
			$participant_group_field['sub_fields'][] = [
				'key'               => $this->field_key . 'ref_' . $field['name'],
				'label'             => __( 'CiviCRM Participant Action', 'civicrm-wp-profile-sync' ),
				'name'              => 'ref_' . $field['name'],
				'type'              => 'cwps_acfe_participant_action_ref',
				'instructions'      => __( 'Select a Participant Action in this Form.', 'civicrm-wp-profile-sync' ),
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

			// Define Participant ID Field.
			$pid_field = [
				'key'               => $this->field_key . 'cid_' . $field['name'],
				'label'             => __( 'CiviCRM Participant ID', 'civicrm-wp-profile-sync' ),
				'name'              => 'cid_' . $field['name'],
				'type'              => 'civicrm_participant',
				'instructions'      => __( 'Select a CiviCRM Participant ID from the database.', 'civicrm-wp-profile-sync' ),
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

			/*
			// Add Participant ID Field.
			$participant_group_field['sub_fields'][] = $pid_field;
			*/

			// Define Custom Participant Reference Field.
			$title                              = __( 'Custom Participant Reference', 'civicrm-wp-profile-sync' );
			$mapping_field                      = $this->mapping_field_get( $field['name'], $title );
			$mapping_field['instructions']      = __( 'Define a custom Participant Reference.', 'civicrm-wp-profile-sync' );
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

			// Add Custom Participant Reference Field.
			$participant_group_field['sub_fields'][] = $mapping_field;

			// Add Participant Reference Group.
			$fields[] = $participant_group_field;

		}

		// "Participant References" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_participants_close',
			'label'             => __( 'Participant References', 'civicrm-wp-profile-sync' ),
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
	 * Defines the Fields in the "Event" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_event_add() {

		// Init return.
		$fields = [];

		// "Event References" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_events_open',
			'label'             => __( 'Event References', 'civicrm-wp-profile-sync' ),
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

		// Add Event Reference Fields.
		foreach ( $this->fields_for_events as $field ) {

			// Define the Group key.
			$group_key = $this->field_key . 'event_group_' . $field['name'];

			// Bundle them into a container group.
			$event_group_field = [
				'key'          => $group_key,
				'label'        => $field['title'],
				'name'         => 'event_group_' . $field['name'],
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

			// Define Event ID Field.
			$event_id_field = [
				'key'               => $this->field_key . 'value_' . $field['name'],
				'label'             => __( 'CiviCRM Event ID', 'civicrm-wp-profile-sync' ),
				'name'              => 'value_' . $field['name'],
				'type'              => 'civicrm_event',
				'instructions'      => __( 'Select a CiviCRM Event ID from the database.', 'civicrm-wp-profile-sync' ),
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
					],
				],
			];

			// Add Event ID Field.
			$event_group_field['sub_fields'][] = $event_id_field;

			// Define Custom Event Reference Field.
			$title                              = __( 'Custom Event Reference', 'civicrm-wp-profile-sync' );
			$mapping_field                      = $this->mapping_field_get( $field['name'], $title );
			$mapping_field['instructions']      = __( 'Define a custom Event Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field'    => $this->field_key . 'value_' . $field['name'],
						'operator' => '==empty',
					],
				],
			];

			// Add Custom Event Reference Field.
			$event_group_field['sub_fields'][] = $mapping_field;

			// Add Event ID Reference Group.
			$fields[] = $event_group_field;

		}

		// Define Event Type Field.
		$event_type_field = [
			'key'               => $this->field_key . 'event_type',
			'label'             => __( 'Event Type', 'civicrm-wp-profile-sync' ),
			'name'              => 'event_type',
			'type'              => 'select',
			'instructions'      => __( 'Choose the Event Type to show its Custom Fields below.', 'civicrm-wp-profile-sync' ),
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'default_value'     => '',
			'choices'           => $this->event_type_choices,
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'return_format'     => 'value',
		];

		// Add Event Type Field.
		$fields[] = $event_type_field;

		// "Event References" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_events_close',
			'label'             => __( 'Event References', 'civicrm-wp-profile-sync' ),
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
	 * Defines the Fields in the "Participant Fields" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_participant_add() {

		// Init return.
		$fields = [];

		// "Participant Fields" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_participant_open',
			'label'             => __( 'Participant Fields', 'civicrm-wp-profile-sync' ),
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
		foreach ( $this->public_participant_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Participant Fields" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_participant_close',
			'label'             => __( 'Participant Fields', 'civicrm-wp-profile-sync' ),
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
	private function tab_mapping_accordion_custom_add() {

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

			// Init conditional logic.
			$conditional_logic = [];

			/*
			 * For Participant: "extends_entity_column_id" means:
			 *
			 * * Missing "extends_entity_column_id": All Participants.
			 * * Missing "extends_entity_column_value": All Participants.
			 *
			 * 1: The VALUE of the 'participant_role'
			 * 2: The ID of the CiviCRM Event
			 * 3: The VALUE of the 'event_type'
			 */

			// Missing "extends_entity_column_id" means All Participants.
			if ( ! empty( $custom_group['extends_entity_column_id'] ) ) {

				// Set conditions for Fields that only show for Participant Roles.
				if ( 1 === (int) $custom_group['extends_entity_column_id'] ) {

					// Get the Participant Role IDs.
					$participant_role_ids = $custom_group['extends_entity_column_value'];

					// Add Roles as OR conditionals if present.
					if ( ! empty( $participant_role_ids ) ) {
						foreach ( $participant_role_ids as $participant_role_id ) {

							$participant_role = [
								'field'    => $this->field_key . 'participant_role_id',
								'operator' => '==contains',
								'value'    => $participant_role_id,
							];

							$conditional_logic[] = [
								$participant_role,
							];

						}
					}

				}

				// Set conditions for Fields that only show for specific Events.
				if ( 2 === (int) $custom_group['extends_entity_column_id'] ) {

					// Get the Event IDs.
					$event_ids = $custom_group['extends_entity_column_value'];

					// Add Events as OR conditionals if present.
					if ( ! empty( $event_ids ) ) {
						foreach ( $event_ids as $event_id ) {

							$event_ref = [
								'field'    => $this->field_key . 'event_id',
								'operator' => '==',
								'value'    => $event_id,
							];

							$event = [
								'field'    => $this->field_key . 'event_group_event_id',
								'operator' => '==',
								'value'    => $event_id,
							];

							$conditional_logic[] = [
								$event_ref,
							];

							$conditional_logic[] = [
								$event,
							];

						}
					}

				}

				// Set conditions for Fields that only show for specific Event Types.
				if ( 3 === (int) $custom_group['extends_entity_column_id'] ) {

					// Get the Event Type IDs.
					$event_type_ids = $custom_group['extends_entity_column_value'];

					// Add Event Types as OR conditionals if present.
					if ( ! empty( $event_type_ids ) ) {
						foreach ( $event_type_ids as $event_type_id ) {

							$event = [
								'field'    => $this->field_key . 'event_type',
								'operator' => '==',
								'value'    => $event_type_id,
							];

							$conditional_logic[] = [
								$event,
							];

						}
					}

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
	 * Builds Participant data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Participant data.
	 */
	private function form_participant_data( $form, $action ) {

		// Init data array.
		$data = [];

		// Set ACFE "context". We want to apply tags.
		acfe_add_context( $this->context_save );

		// Build Fields array.
		foreach ( $this->public_participant_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				acfe_apply_tags( $action['participant'][ $field['name'] ] );
				$data[ $field['name'] ] = $action['participant'][ $field['name'] ];
			}
		}

		// Reset the ACFE "context".
		acfe_delete_context( array_keys( $this->context_save ) );

		// Get the Participant Role & Status.
		$data['role_id']   = $action['participant']['role_id'];
		$data['status_id'] = $action['participant']['status_id'];

		// Get the Action variables.
		$data['add_anyway']    = $action['settings']['add_anyway'];
		$data['email_receipt'] = $action['settings']['email_receipt'];

		// Get the Participant Contacts.
		foreach ( $this->fields_for_contacts as $field ) {

			// Get Group Field.
			$contact_group_field = $action['participant'][ $field['name'] ];

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

		// Get the Participant Participants.
		foreach ( $this->fields_for_participants as $field ) {

			// Get Group Field.
			$participant_group_field = $action['participant'][ $field['name'] ];

			// Check Action Reference Field.
			$participant_id = false;
			if ( ! empty( $participant_group_field[ 'ref_' . $field['name'] ] ) ) {
				$action_name    = $participant_group_field[ 'ref_' . $field['name'] ];
				$participant_id = $this->form_participant_id_get_mapped( $action_name );
			}

			/*
			// Check Participant ID Field.
			if ( false === $participant_id ) {
				if ( ! empty( $participant_group_field[ 'cid_' . $field['name'] ] ) ) {
					$participant_id = $participant_group_field[ 'cid_' . $field['name'] ];
				}
			}
			*/

			// Check mapped Field.
			if ( false === $participant_id ) {
				if ( ! empty( $participant_group_field[ $field['name'] ] ) ) {
					acfe_apply_tags( $participant_group_field[ $field['name'] ], $this->context_save );
					if ( ! empty( $participant_group_field[ $field['name'] ] ) && is_numeric( $participant_group_field[ $field['name'] ] ) ) {
						$participant_id = $participant_group_field[ $field['name'] ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $participant_id ) && is_numeric( $participant_id ) ) {
				$data[ $field['name'] ] = $participant_id;
			}

		}

		// Get the Event.
		foreach ( $this->fields_for_events as $field ) {

			// Get Group Field.
			$event_group_field = $action['participant'][ $field['name'] ];

			// Check Event ID Field.
			$event_id = false;
			if ( ! empty( $event_group_field[ 'value_' . $field['name'] ] ) ) {
				$event_id = $event_group_field[ 'value_' . $field['name'] ];
			}

			// Check mapped Field.
			if ( false === $event_id ) {
				if ( ! empty( $event_group_field[ $field['name'] ] ) ) {

					/*
					 * When validating, ACF Extended won't get the Event ID from an ACF
					 * Field because it filters the Post ID to be "acfe/form/validation".
					 * We need to undo that here to allow template tags to function as
					 * expected for this Field.
					 */
					if ( current_action() === 'acfe/form/validate_cwps_participant' || current_action() === 'acfe/form/make_cwps_participant' ) {
						// Set up the WordPress Post.
						$post_id = acf_maybe_get_POST( '_acf_post_id' );
						global $post;
						// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
						$post = get_post( $post_id );
						setup_postdata( $post );
						// Filter the ACF to return the actual Post ID.
						add_filter( 'acf/pre_load_post_id', [ $this, 'form_validate_post_id' ], 100, 2 );
					}

					// Apply tags now.
					acfe_apply_tags( $event_group_field[ $field['name'] ], $this->context_save );

					// Reset filter and Post if modified.
					if ( current_action() === 'acfe/form/validate_cwps_participant' || current_action() === 'acfe/form/make_cwps_participant' ) {
						remove_filter( 'acf/pre_load_post_id', [ $this, 'form_validate_post_id' ], 100 );
						wp_reset_postdata();
					}

					// Maybe apply value.
					if ( ! empty( $event_group_field[ $field['name'] ] ) && is_numeric( $event_group_field[ $field['name'] ] ) ) {
						$event_id = $event_group_field[ $field['name'] ];
					}

				}
			}

			// Assign to data.
			if ( ! empty( $event_id ) && is_numeric( $event_id ) ) {
				$data[ $field['name'] ] = $event_id;
			}

		}

		// Add the Campaign if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {
			$data['campaign_id'] = $action['participant']['campaign_id'];
		}

		// --<
		return $data;

	}

	/**
	 * Validates the Participant data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Participant can be saved, false otherwise.
	 */
	private function form_participant_validate( $form, $action ) {

		// Get the Participant.
		$participant = $this->form_participant_data( $form, $action );

		// Strip out empty Fields.
		$participant = $this->form_data_prepare( $participant );

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
		if ( empty( $participant['contact_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				// / * translators: %s The name of the Form Action * /
				__( 'A Contact ID is required to create a Participant in "%s".', 'civicrm-wp-profile-sync' ),
				$action['name']
			) );
			return false;
		}
		*/

		// Reject the submission if the Event ID Field is missing.
		if ( empty( $participant['event_id'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'An Event ID is required to create a Participant in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Reject the submission if the Role ID Field is missing.
		if ( empty( $participant['role_id'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A Participant Role ID is required to create a Participant in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Get the Participant Roles that count towards the total for the Event.
		$counted = $this->civicrm->participant_role->get_counted();

		// Make sure the keys are integers.
		$counted_role_ids = array_map( 'intval', array_keys( $counted ) );

		// All's well if the Participant Role is not counted.
		if ( ! in_array( (int) $participant['role_id'], $counted_role_ids, true ) ) {
			return true;
		}

		// All's well if "Add anyway" is on.
		if ( ! empty( $participant['add_anyway'] ) ) {
			return true;
		}

		// Check the status of the Event.
		$is_full = $this->civicrm->event->is_full( $participant['event_id'] );

		// Reject the submission if there's an error.
		if ( false === $is_full ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'Could not check if the Event is full in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// All's well if the Event is not full.
		if ( 0 === $is_full ) {
			return true;
		}

		// Build feedback from Event details.
		$event = $this->civicrm->event->get_by_id( $participant['event_id'] );
		if ( ! empty( $event['title'] ) ) {
			// Set useful message when there are Event details.
			$message = sprintf(
				/* translators: %s The title of the Event */
				__( 'Cannot add Participant because "%s" is full.', 'civicrm-wp-profile-sync' ),
				$event['title']
			);
		} else {
			// Set generic message otherwise.
			$message = sprintf(
				/* translators: %s The name of the Form Action */
				__( 'Cannot add Participant because the Event in "%s" is full.', 'civicrm-wp-profile-sync' ),
				$action['name']
			);
		}

		// Reject the submission.
		acfe_add_validation_error( '', $message );

		// Not valid.
		return false;

	}

	/**
	 * Saves the CiviCRM Participant given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $participant_data The array of Participant data.
	 * @param array $custom_data The array of Custom Field data.
	 * @return array $participant The Participant data array, or empty on failure.
	 */
	private function form_participant_save( $participant_data, $custom_data ) {

		// Init return.
		$participant = [];

		// Add Custom Field data if present.
		if ( ! empty( $custom_data ) ) {
			$participant_data += $custom_data;
		}

		// Strip out empty Fields.
		$participant_data = $this->form_data_prepare( $participant_data );

		/*
		 * Get the Participant Roles that count towards the total for the Event
		 * and make sure the keys are integers.
		 */
		$counted_role_ids = array_map( 'intval', array_keys( $this->participant_roles_counted ) );

		// If the Role is counted, perform the "Add anyway" check.
		if ( in_array( (int) $participant_data['role_id'], $counted_role_ids, true ) ) {

			// If "Add anyway" is on, we can skip this check.
			if ( empty( $participant_data['add_anyway'] ) ) {

				// Check the status of the Event.
				$is_full = $this->civicrm->event->is_full( $participant_data['event_id'] );

				// Bail if there's an error.
				if ( false === $is_full ) {
					return $participant;
				}

				// Bail if the Event is full.
				if ( 1 === $is_full ) {
					return $participant;
				}

			}

		}

		// Unset "Email receipt" param.
		$email_receipt = false;
		if ( isset( $participant_data['email_receipt'] ) ) {
			$email_receipt = true;
			unset( $participant_data['email_receipt'] );
		}

		// Unset "Add anyway" param.
		if ( isset( $participant_data['add_anyway'] ) ) {
			unset( $participant_data['add_anyway'] );
		}

		// Create the Participant.
		$result = $this->civicrm->participant->create( $participant_data );
		if ( false === $result ) {
			return $participant;
		}

		// Get the full Participant data.
		$participant = $this->civicrm->participant->get_by_id( $result['id'] );

		// Maybe email receipt.
		if ( true === $email_receipt ) {

			// Initialise values.
			$values                   = [];
			$values['custom_pre_id']  = '';
			$values['custom_post_id'] = '';

			// The full Event data is needed.
			$event           = $this->civicrm->event->get_by_id( $participant['event_id'] );
			$values['event'] = $event;

			// The full Participant data is needed.
			$values['params'] = $participant;

			// Location data is needed, whether populated or not.
			$params             = [
				'entity_id'    => $event['id'],
				'entity_table' => 'civicrm_event',
			];
			$values['location'] = CRM_Core_BAO_Location::getValues( $params );

			// Okay, go ahead and send.
			$sent = CRM_Event_BAO_Event::sendMail( $participant['contact_id'], $values, $participant['id'] );

		}

		// --<
		return $participant;

	}

	/**
	 * Short-circuits ACF's attempt to find the Post ID.
	 *
	 * @since 0.7.0
	 *
	 * @param integer $preload Null by default.
	 * @param mixed   $post_id The requested Post ID, if supplied.
	 * @return integer $preload The possbily modified Post ID.
	 */
	public function form_validate_post_id( $preload, $post_id ) {

		// Maybe get the Post ID from submission data.
		if ( 'acfe/form/validation' === $preload || 'acfe/form/submit' === $preload ) {
			$post_id = acf_maybe_get_POST( '_acf_post_id' );
			if ( ! empty( $post_id ) ) {
				$preload = (int) $post_id;
			}
		}

		// --<
		return $preload;

	}

}
