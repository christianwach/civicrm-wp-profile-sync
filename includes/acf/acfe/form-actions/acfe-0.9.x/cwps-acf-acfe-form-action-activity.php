<?php
/**
 * "Activity" ACFE Form Action Class.
 *
 * Handles the "Activity" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync "Activity" ACFE Form Action Class.
 *
 * A class that handles the "Activity" ACFE Form Action.
 *
 * @since 0.7.0
 */
class CWPS_ACF_ACFE_Form_Action_Activity extends CWPS_ACF_ACFE_Form_Action_Base {

	/**
	 * Form Action Name.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $name = 'cwps_activity';

	/**
	 * Data transient key.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var string
	 */
	private $transient_key = 'cwps_acf_acfe_form_action_activity';

	/**
	 * Public Activity Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $public_activity_fields;

	/**
	 * Activity Fields for Contacts.
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
	 * Attachment Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $attachment_fields;

	/**
	 * Activity Type choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $activity_type_choices;

	/**
	 * Activity Status choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $activity_status_ids;

	/**
	 * Campaign choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $campaign_choices;

	/**
	 * Public Activity Fields to add.
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
	 * Public Activity Fields to ignore.
	 *
	 * These are mapped for Post Type Sync, but need special handling.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_to_ignore = [
		'status_id'           => 'select',
		'source_contact_id'   => 'civicrm_contact',
		'target_contact_id'   => 'civicrm_contact',
		'assignee_contact_id' => 'civicrm_contact',
	];

	/**
	 * Activity Contact Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $contact_fields = [
		'source_contact_id'   => 'civicrm_contact',
		'target_contact_id'   => 'civicrm_contact',
		'assignee_contact_id' => 'civicrm_contact',
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
		$this->title = __( 'CiviCRM Activity action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->name_placeholder = __( 'CiviCRM Activity', 'civicrm-wp-profile-sync' );

		// Declare core Fields for this Form Action.
		$this->item = [
			'action'      => $this->name,
			'name'        => '',
			'id'          => false,
			'activity'    => [
				'id'                  => false,
				'activity_type_id'    => '',
				'source_contact_id'   => false,
				'target_contact_id'   => false,
				'assignee_contact_id' => false,
				'status_id'           => '',
			],
			'attachment'  => [],
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

		// Load Entity data.
		foreach ( $this->public_activity_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$action[ $field['name'] ] = $action['activity'][ $field['name'] ];
			}
		}

		// Load additional Entity data.
		$action['activity_type_id']   = $action['activity']['activity_type_id'];
		$action['activity_status_id'] = $action['activity']['status_id'];

		// Load Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			$action[ 'custom_group_' . $custom_group['id'] ] = $action['activity'][ 'custom_group_' . $custom_group['id'] ];
		}

		// Load associated Entities data.
		$action['attachment_repeater'] = $action['attachment'];

		// Load Campaign ID if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {
			$action['activity_campaign_id'] = $action['activity']['campaign_id'];
		}

		// Load Case ID if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$action['activity_case_id']     = $action['activity']['case_id'];
			$action['activity_case_create'] = $action['activity']['case_create'];
		}

		// Load Contact References.
		foreach ( $this->contact_fields as $name => $title ) {
			$action[ 'contact_group_' . $name ] = $action['activity'][ $name ];
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

		// Save Entity data.
		foreach ( $this->public_activity_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$save['activity'][ $field['name'] ] = $action[ $field['name'] ];
			}
		}

		// Save additional Entity data.
		$save['activity']['activity_type_id'] = $action['activity_type_id'];
		$save['activity']['status_id']        = $action['activity_status_id'];

		// Save Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			$save['activity'][ 'custom_group_' . $custom_group['id'] ] = $action[ 'custom_group_' . $custom_group['id'] ];
		}

		// Save associated Entities data.
		$save['attachment'] = $action['attachment_repeater'];

		// Save Campaign ID if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {
			$save['activity']['campaign_id'] = $action['activity_campaign_id'];
		}

		// Save Case ID if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$save['activity']['case_id']     = $action['activity_case_id'];
			$save['activity']['case_create'] = $action['activity_case_create'];
		}

		// Save Contact References.
		foreach ( $this->contact_fields as $name => $title ) {
			$save['activity'][ $name ] = $action[ 'contact_group_' . $name ];
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

		// Get the public Activity Fields for all top level Activity Types from transient if possible.
		if ( false !== $data && isset( $data['public_activity_fields'] ) ) {
			$this->public_activity_fields = $data['public_activity_fields'];
		} else {

			// Get the public Activity Fields for all top level Activity Types.
			$this->public_activity_fields = $this->civicrm->activity_field->get_public_fields();

			// Prepend the ones that are needed in ACFE Forms (i.e. Subject and Details).
			foreach ( $this->fields_to_add as $name => $field_type ) {
				array_unshift( $this->public_activity_fields, $this->civicrm->activity_field->get_by_name( $name ) );
			}

			$transient['public_activity_fields'] = $this->public_activity_fields;

		}

		// Get Fields for Contacts from transient if possible.
		if ( false !== $data && isset( $data['fields_for_contacts'] ) ) {
			$this->fields_for_contacts = $data['fields_for_contacts'];
		} else {
			foreach ( $this->contact_fields as $name => $field_type ) {
				$field                       = $this->civicrm->activity_field->get_by_name( $name );
				$this->fields_for_contacts[] = $field;
			}
			$transient['fields_for_contacts'] = $this->fields_for_contacts;
		}

		// Handle Contact Fields.
		foreach ( $this->fields_for_contacts as $field ) {

			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( 'ref_' . $field['name'] );

		}

		// Get the Custom Groups and Fields for all Activity Types from transient if possible.
		if ( false !== $data && isset( $data['custom_fields'] ) ) {
			$this->custom_fields = $data['custom_fields'];
		} else {
			$this->custom_fields        = $this->plugin->civicrm->custom_group->get_for_activities();
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

		// Get the public Attachment Fields from transient if possible.
		if ( false !== $data && isset( $data['attachment_fields'] ) ) {
			$this->attachment_fields = $data['attachment_fields'];
		} else {
			$this->attachment_fields        = $this->civicrm->attachment->civicrm_fields_get( 'public' );
			$transient['attachment_fields'] = $this->attachment_fields;
		}

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$this->js_model_case_reference_field_add( 'activity_case_id' );
		}

		// Finally, let's try and cache queries made in tabs.

		// Get Activity Type choices from transient if possible.
		if ( false !== $data && isset( $data['activity_type_choices'] ) ) {
			$this->activity_type_choices = $data['activity_type_choices'];
		} else {
			$this->activity_type_choices        = $this->civicrm->activity_type->choices_get();
			$transient['activity_type_choices'] = $this->activity_type_choices;
		}

		// Get Activity Status choices from transient if possible.
		if ( false !== $data && isset( $data['activity_status_ids'] ) ) {
			$this->activity_status_ids = $data['activity_status_ids'];
		} else {
			$this->activity_status_ids        = $this->civicrm->activity_field->options_get( 'status_id' );
			$transient['activity_status_ids'] = $this->activity_status_ids;
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
	public function validation( $form, $action ) {

		// Skip if the Contact Conditional Reference Field has a value.
		$this->form_conditional_populate( [ 'action' => &$action ] );
		if ( ! $this->form_conditional_check( [ 'action' => $action ] ) ) {
			return;
		}

		// Validate the Activity data.
		$valid = $this->form_activity_validate( $form, $action );
		if ( ! $valid ) {
			return;
		}

		// TODO: Check other Activity Entities.

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

		// Populate Activity data array.
		$activity = $this->form_activity_data( $form, $action );

		// Build Contact Custom Field args.
		$args = [
			'custom_groups' => $this->custom_fields,
		];

		// Get populated Custom Field data array.
		$custom_fields = $this->form_entity_custom_fields_data( $action['activity'], $args );

		// Save the Activity with the data from the Form.
		$result['activity'] = $this->form_activity_save( $activity, $custom_fields );

		// If we get an Activity.
		if ( ! empty( $result['activity'] ) ) {

			// Post-process Custom Fields now that we have an Activity.
			$this->form_entity_custom_fields_post_process( $form, $action, $result['activity'], 'activity' );

			// Get Attachments data array.
			$attachments = $this->form_attachments_data( $form, $action );

			// Save the Attachments with the data from the Form.
			$result['attachments'] = $this->form_attachments_save( $result['activity'], $attachments );

			// Save the Activity ID for backwards compatibility.
			$result['id'] = (int) $result['activity']['id'];

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

		// Define Field.
		$activity_type_field = [
			'key'               => $this->field_key . 'activity_type_id',
			'label'             => __( 'Activity Type', 'civicrm-wp-profile-sync' ),
			'name'              => 'activity_type_id',
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
			'choices'           => $this->activity_type_choices,
		];

		// Define Status Field.
		$activity_status_field = [
			'key'               => $this->field_key . 'activity_status_id',
			'label'             => __( 'Activity Status', 'civicrm-wp-profile-sync' ),
			'name'              => 'activity_status_id',
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
			'choices'           => $this->activity_status_ids,
		];

		// Init Fields.
		$fields = [
			$activity_type_field,
			$activity_status_field,
		];

		// Add Campaign Field if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {

			$fields[] = [
				'key'               => $this->field_key . 'activity_campaign_id',
				'label'             => __( 'Campaign', 'civicrm-wp-profile-sync' ),
				'name'              => 'activity_campaign_id',
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

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {

			$fields[] = [
				'key'              => $this->field_key . 'activity_case_id',
				'label'            => __( 'Case', 'civicrm-wp-profile-sync' ),
				'name'             => 'activity_case_id',
				'type'             => 'cwps_acfe_case_action_ref',
				'instructions'     => __( 'Select a Case Action in this Form.', 'civicrm-wp-profile-sync' ),
				'required'         => 0,
				'wrapper'          => [
					'width'                      => '',
					'class'                      => '',
					'id'                         => '',
					'data-instruction-placement' => 'field',
				],
				'acfe_permissions' => '',
				'default_value'    => '',
				'placeholder'      => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null'       => 1,
				'multiple'         => 0,
				'ui'               => 0,
				'return_format'    => 'value',
				'choices'          => [],
			];

			// Define "Dismiss if exists" Field.
			$fields[] = [
				'key'               => $this->field_key . 'activity_case_create',
				'label'             => __( 'Create Case Activity?', 'civicrm-wp-profile-sync' ),
				'name'              => 'activity_case_create',
				'type'              => 'true_false',
				'instructions'      => __( 'Create a Case Activity even if the Contact already has an existing Case of the selected Type. Useful when you want to add an Activity to a Case.', 'civicrm-wp-profile-sync' ),
				'required'          => 0,
				'conditional_logic' => [
					[
						[
							'field'    => $this->field_key . 'activity_case_id',
							'operator' => '!=empty',
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
				'message'           => '',
				'default_value'     => 0,
				'ui'                => 1,
				'ui_on_text'        => '',
				'ui_off_text'       => '',
			];

		}

		// Configure Conditional Field.
		$args = [
			'placeholder'  => __( 'Always add', 'civicrm-wp-profile-sync' ),
			'instructions' => __( 'To add the Activity only when a Form Field is populated (e.g. "Subject") link this to the Form Field. To add the Activity only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' ),
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
	public function tab_mapping_add() {

		// Get Tab Header.
		$mapping_tab_header = $this->tab_mapping_header();

		// Build Contacts Accordion.
		$mapping_contacts_accordion = $this->tab_mapping_accordion_contacts_add();

		// Build Activity Details Accordion.
		$mapping_activity_accordion = $this->tab_mapping_accordion_activity_add();

		// Build Custom Fields Accordion.
		$mapping_custom_accordion = $this->tab_mapping_accordion_custom_add();

		// Build Attachment Fields Accordion.
		$mapping_attachment_accordion = $this->tab_mapping_accordion_attachment_add();

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$mapping_contacts_accordion,
			$mapping_activity_accordion,
			$mapping_custom_accordion,
			$mapping_attachment_accordion
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

		// "Activity Contacts" Accordion wrapper close.
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
	 * Defines the Fields in the "Activity Fields" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_activity_add() {

		// Init return.
		$fields = [];

		// "Activity Fields" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_activity_open',
			'label'             => __( 'Activity Fields', 'civicrm-wp-profile-sync' ),
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
		foreach ( $this->public_activity_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Activity Fields" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_activity_close',
			'label'             => __( 'Activity Fields', 'civicrm-wp-profile-sync' ),
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

			// Get the Activity Type ID.
			$activity_type_ids = [];
			if ( ! empty( $custom_group['extends_entity_column_value'] ) ) {
				$activity_type_ids = $custom_group['extends_entity_column_value'];
			}

			// Init conditional logic.
			$conditional_logic = [];

			// Add Sub-types as OR conditionals if present.
			if ( ! empty( $activity_type_ids ) ) {
				foreach ( $activity_type_ids as $activity_type_id ) {

					$activity_type = [
						'field'    => $this->field_key . 'activity_type_id',
						'operator' => '==contains',
						'value'    => $activity_type_id,
					];

					$conditional_logic[] = [
						$activity_type,
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

	/**
	 * Defines the "Attachment" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_attachment_add() {

		// Init return.
		$fields = [];

		// "Attachment" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_attachment_open',
			'label'             => __( 'Attachment(s)', 'civicrm-wp-profile-sync' ),
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

		// Define the Attachment Repeater Field.
		$attachment_repeater = [
			'key'                           => $this->field_key . 'attachment_repeater',
			'label'                         => __( 'Attachment Actions', 'civicrm-wp-profile-sync' ),
			'name'                          => 'attachment_repeater',
			'type'                          => 'repeater',
			'instructions'                  => '',
			'required'                      => 0,
			'conditional_logic'             => 0,
			'wrapper'                       => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'acfe_permissions'              => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed'                     => $this->field_key . 'attachment_file',
			'min'                           => 0,
			'max'                           => 3,
			'layout'                        => 'block',
			'button_label'                  => __( 'Add Attachment action', 'civicrm-wp-profile-sync' ),
			'sub_fields'                    => [],
		];

		// Init Sub-Fields.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// First add "File" Field to Repeater's Sub-Fields.
		$code         = 'attachment_file';
		$label        = __( 'File', 'civicrm-wp-profile-sync' );
		$file         = $this->mapping_field_get( $code, $label );
		$sub_fields[] = $file;

		// ---------------------------------------------------------------------

		// Add "Mapping" Fields to Repeater's Sub-Fields.
		foreach ( $this->attachment_fields as $attachment_field ) {
			$sub_fields[] = $this->mapping_field_get( 'attachment_' . $attachment_field['name'], $attachment_field['title'] );
		}

		// ---------------------------------------------------------------------

		// Configure Conditional Field.
		$args = [
			'name'         => 'attachment_conditional',
			'placeholder'  => __( 'Always add', 'civicrm-wp-profile-sync' ),
			'instructions' => __( 'To add the Attachment to the Activity only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' ),
		];

		// Add Conditional Field.
		$sub_fields[] = $this->form_conditional_field_get( $args );

		// ---------------------------------------------------------------------

		// Add to Repeater.
		$attachment_repeater['sub_fields'] = $sub_fields;

		// Add Repeater to Fields.
		$fields[] = $attachment_repeater;

		// "Attachment" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_attachment_close',
			'label'             => __( 'Attachment', 'civicrm-wp-profile-sync' ),
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
	 * Builds Activity data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Activity data.
	 */
	public function form_activity_data( $form, $action ) {

		// Init data array.
		$data = [];

		// Set ACFE "context". We want to apply tags.
		acfe_add_context( $this->context_save );

		// Build Fields array.
		foreach ( $this->public_activity_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				acfe_apply_tags( $action['activity'][ $field['name'] ] );
				$data[ $field['name'] ] = $action['activity'][ $field['name'] ];
			}
		}

		// Reset the ACFE "context".
		acfe_delete_context( array_keys( $this->context_save ) );

		// Get the Activity Type & Status.
		$data['activity_type_id'] = $action['activity']['activity_type_id'];
		$data['status_id']        = $action['activity']['status_id'];

		// Get the Activity Contacts.
		foreach ( $this->fields_for_contacts as $field ) {

			// Get Group Field.
			$contact_group_field = $action['activity'][ $field['name'] ];

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

		// Add the Campaign if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {
			$data['campaign_id'] = $action['activity']['campaign_id'];
		}

		// Add the Case if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$action_name                  = $action['activity']['case_id'];
			$case_data                    = $this->form_case_get_mapped( $action_name );
			$data['case_id']              = $case_data['case_id'];
			$data['case_skipped']         = $case_data['skipped'];
			$data['case_created']         = $case_data['created'];
			$data['case_activity_create'] = $action['activity']['case_create'];
		}

		// --<
		return $data;

	}

	/**
	 * Validates the Activity data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Activity can be saved, false otherwise.
	 */
	public function form_activity_validate( $form, $action ) {

		// Get the Activity.
		$activity = $this->form_activity_data( $form, $action );

		/*
		 * We have a problem here because the ACFE Forms Actions query var has
		 * not been populated yet since the "make" actions have not run.
		 *
		 * This means that "acfe_form_get_action()" cannot be queried to find
		 * the referenced Contact ID when using an "Action Reference" Field,
		 * even though it will be populated later when the "make" actions run.
		 *
		 * Other methods for defining the Contact ID will still validate, but
		 * we're going to have to exclude this check for now.
		 */

		/*
		// Reject the submission if there is no Source Contact ID.
		if ( empty( $activity['source_contact_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				// / * translators: %s The name of the Form Action * /
				__( 'A Contact ID is required to create an Activity in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}
		*/

		// Reject the submission if the Activity Type ID is missing.
		if ( empty( $activity['activity_type_id'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'An Activity Type ID is required to create an Activity in "%s".', 'civicrm-wp-profile-sync' ),
					$action
				)
			);
			return false;
		}

		// Valid.
		return true;

	}

	/**
	 * Saves the CiviCRM Activity given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $activity_data The array of Activity data.
	 * @param array $custom_data The array of Custom Field data.
	 * @return array $activity The Activity data array, or empty on failure.
	 */
	public function form_activity_save( $activity_data, $custom_data ) {

		// Init return.
		$activity = [];

		// Bail if the Activity can't be created.
		if ( empty( $activity_data['source_contact_id'] ) || empty( $activity_data['activity_type_id'] ) ) {
			return $activity;
		}

		// Add Custom Field data if present.
		if ( ! empty( $custom_data ) ) {
			$activity_data += $custom_data;
		}

		// Skip if the Case has been skipped and an Activity should not be created.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			if ( true === $activity_data['case_skipped'] ) {
				if ( empty( $activity_data['case_activity_create'] ) ) {
					return $activity;
				}
			}
		}

		// Strip out empty Fields.
		$activity_data = $this->form_data_prepare( $activity_data );

		// Create the Activity.
		$result = $this->civicrm->activity->create( $activity_data );
		if ( false === $result ) {
			return $activity;
		}

		// Get the full Activity data.
		$activity = $this->civicrm->activity->get_by_id( $result['id'] );

		// Maybe notify Assignee.
		$this->form_activity_notify( $activity );

		// --<
		return $activity;

	}

	/**
	 * Notifies the CiviCRM Activity Assignees.
	 *
	 * @since 0.7.0
	 *
	 * @param array $activity The array of Activity data.
	 */
	public function form_activity_notify( $activity ) {

		// Skip if there are no assignees.
		if ( empty( $activity['assignee_contact_id'] ) ) {
			return;
		}

		// Skip if the CiviCRM setting is not set.
		$assignee_notification = $this->plugin->civicrm->get_setting( 'activity_assignee_notification' );
		if ( ! $assignee_notification ) {
			return;
		}

		// Skip if CiviCRM does not allow it for this Activity Type.
		$do_not_notify_setting = $this->plugin->civicrm->get_setting( 'do_not_notify_assignees_for' );
		$do_not_notify         = array_map( 'intval', $do_not_notify_setting );
		if ( in_array( (int) $activity['activity_type_id'], $do_not_notify, true ) ) {
			return;
		}

		// Get the Contact details of the Assignees.
		$assignee_contacts = CRM_Activity_BAO_ActivityAssignment::getAssigneeNames( [ $activity['id'] ], true, false );

		// Build an associative array of unique Email Addresses.
		$mail_to_contacts = [];
		foreach ( $activity['assignee_contact_id'] as $contact_id ) {
			$mail_to_contacts[ $assignee_contacts[ $contact_id ]['email'] ] = $assignee_contacts[ $contact_id ];
		}

		// Fire off the email.
		$sent = CRM_Activity_BAO_Activity::sendToAssignee( (object) $activity, $mail_to_contacts );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Builds Attachment data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $form The array of Form data.
	 * @param array $field The array of Field data.
	 * @return array $data The array of Attachment data.
	 */
	public function form_attachments_data( $form, $field ) {

		// Init return.
		$data = [];

		// Define key for this Entity.
		$entity_key = 'attachment';

		// Skip if the repeater is empty.
		if ( empty( $field[ $entity_key ] ) ) {
			return $data;
		}

		// Loop through the Action Fields.
		foreach ( $field[ $entity_key ] as $field ) {

			// Init Field item.
			$item = [];

			// Get File Field.
			$item['file'] = $field['attachment_file'];

			// Set ACFE "context". We want to apply tags.
			acfe_add_context( $this->context_save );

			// Get mapped Fields.
			foreach ( $this->attachment_fields as $entity_field ) {
				acfe_apply_tags( $field[ $entity_key . '_' . $entity_field['name'] ] );
				$item[ $entity_field['name'] ] = $field[ $entity_key . '_' . $entity_field['name'] ];
			}

			// Reset the ACFE "context".
			acfe_delete_context( array_keys( $this->context_save ) );

			// Build Conditional Field args.
			$conditional_args = [
				'action' => &$field,
				'key'    => $entity_key . '_conditional',
			];

			// Populate Conditional Reference and value.
			$this->form_conditional_populate( $conditional_args );

			// Get Conditional.
			$item[ $entity_key . '_conditional' ] = $field[ $entity_key . '_conditional' ];

			// Save Conditional Reference.
			$item[ $entity_key . '_conditional_ref' ] = $field[ $entity_key . '_conditional_ref' ];

			// Add the data.
			$data[] = $item;

		}

		// --<
		return $data;

	}

	/**
	 * Saves the CiviCRM Attachment(s) given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $activity The array of Activity data.
	 * @param array $attachment_data The array of Attachment data.
	 * @return array $attachments The array of Attachments, or empty on failure.
	 */
	public function form_attachments_save( $activity, $attachment_data ) {

		// Init return.
		$attachments = [];

		// Bail if there's no Activity ID.
		if ( empty( $activity['id'] ) ) {
			return $attachments;
		}

		// Bail if there's no Attachment data.
		if ( empty( $attachment_data ) ) {
			return $attachments;
		}

		// Handle each nested Action in turn.
		foreach ( $attachment_data as $attachment ) {

			// Strip out empty Fields.
			$attachment = $this->form_data_prepare( $attachment );

			// Build Conditional Check args.
			$args = [
				'action' => $attachment,
				'key'    => 'attachment_conditional',
			];

			// Skip if the Conditional Reference Field says so.
			if ( ! $this->form_conditional_check( $args ) ) {
				continue;
			}

			// Skip if there's no WordPress Attachment ID.
			if ( empty( $attachment['file'] ) ) {
				continue;
			}

			// Cast Attachment ID as integer.
			$attachment_id = (int) $attachment['file'];

			// Get the WordPress File, Filename and Mime Type.
			$file      = get_attached_file( $attachment_id, true );
			$filename  = pathinfo( $file, PATHINFO_BASENAME );
			$mime_type = get_post_mime_type( $attachment_id );

			// Build the API params.
			$params = [
				'entity_id'    => $activity['id'],
				'entity_table' => 'civicrm_activity',
				'name'         => $filename,
				'description'  => $attachment['description'],
				'mime_type'    => $mime_type,
				'options'      => [
					'move-file' => $file,
				],
			];

			// Create the Attachment.
			$result = $this->civicrm->attachment->create( $params );
			if ( false === $result ) {
				continue;
			}

			// Always delete the WordPress Attachment.
			wp_delete_attachment( $attachment_id, true );

			// Get the full Attachment data.
			$attachments[] = $this->civicrm->attachment->get_by_id( $result['id'] );

		}

		// --<
		return $attachments;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Finds the linked Case data when it has been mapped.
	 *
	 * This overrides the method in the Base Class because it returns extra data.
	 *
	 * @since 0.7.0
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return array $case The array of data about the linked Case.
	 */
	protected function form_case_get_mapped( $action_name ) {

		// Init return.
		$case = [
			'case_id' => false,
			'skipped' => false,
			'created' => false,
		];

		// We need an Action Name.
		if ( empty( $action_name ) ) {
			return $case;
		}

		// Get the Case data for that Action.
		$related_case = $this->get_action_output( $action_name, 'case' );
		if ( empty( $related_case['id'] ) ) {
			return $case;
		}

		// Assign to return.
		$case['case_id'] = (int) $related_case['id'];

		// Assign flag if creating the Case has been skipped.
		$skipped = $related_case['skipped'];
		if ( $skipped ) {
			$case['skipped'] = true;
		}

		// Assign flag if the Case has been created.
		$created = $related_case['created'];
		if ( $created ) {
			$case['created'] = true;
		}

		// --<
		return $case;

	}

}
