<?php
/**
 * "Activity" ACFE Form Action Class.
 *
 * Handles the "Activity" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync "Activity" ACFE Form Action Class.
 *
 * A class that handles the "Activity" ACFE Form Action.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Activity extends CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Base {

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
	 * ACF Extended object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acfe;

	/**
	 * ACFE Form object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $form;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Form Action Name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $action_name = 'cwps_activity';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $field_key = 'field_cwps_activity_action_';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $field_name = 'cwps_activity_action_';

	/**
	 * Public Activity Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $public_activity_fields;

	/**
	 * Activity Fields for Contacts.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $fields_for_contacts;

	/**
	 * Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $custom_fields;

	/**
	 * Custom Field IDs.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $custom_field_ids;

	/**
	 * Attachment Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $attachment_fields;

	/**
	 * Data transient key.
	 *
	 * @since 0.6.6
	 * @access private
	 * @var string
	 */
	public $transient_key = 'cwps_acf_acfe_form_action_activity';

	/**
	 * Public Activity Fields to add.
	 *
	 * These are not mapped for Post Type Sync, so need to be added.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $fields_to_add = [
		'details' => 'wysiwyg',
		'subject' => 'text',
	];

	/**
	 * Public Activity Fields to ignore.
	 *
	 * These are mapped for Post Type Sync, but need special handling.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $fields_to_ignore = [
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
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $contact_fields = [
		'source_contact_id'   => 'civicrm_contact',
		'target_contact_id'   => 'civicrm_contact',
		'assignee_contact_id' => 'civicrm_contact',
	];

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acfe       = $parent->acfe;
		$this->form       = $parent;
		$this->civicrm    = $this->acf_loader->civicrm;

		// Label this Form Action.
		$this->action_label = __( 'CiviCRM Activity action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->alias_placeholder = __( 'CiviCRM Activity', 'civicrm-wp-profile-sync' );

		// Register hooks.
		$this->register_hooks();

		// Init parent.
		parent::__construct();

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}

	/**
	 * Configure this object.
	 *
	 * @since 0.5
	 */
	public function configure() {

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
		if ( false !== $data && ! empty( $data['public_activity_fields'] ) ) {
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

		// Populate public mapping Fields.
		foreach ( $this->public_activity_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Handle Contact Fields.
		foreach ( $this->contact_fields as $name => $field_type ) {

			// Populate mapping Fields.
			$field = $this->civicrm->activity_field->get_by_name( $name );
			$this->mapping_field_filters_add( $field['name'] );

			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( $this->field_name . 'ref_' . $field['name'] );

			// Also build array of data for CiviCRM Fields.
			$this->fields_for_contacts[] = $field;

			/*
			// Pre-load with "Generic" values.
			$filter = 'acf/prepare_field/name=' . $this->field_name . 'map_' . $field['name'];
			add_filter( $filter, [ $this, 'prepare_choices' ], 5 );
			*/

		}

		// Get the Custom Groups and Fields for all Activity Types from transient if possible.
		if ( false !== $data && ! empty( $data['custom_fields'] ) ) {
			$this->custom_fields = $data['custom_fields'];
		} else {
			$this->custom_fields        = $this->plugin->civicrm->custom_group->get_for_activities();
			$transient['custom_fields'] = $this->custom_fields;
		}

		// Populate mapping Fields.
		$this->custom_field_ids = [];
		foreach ( $this->custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->mapping_field_filters_add( 'custom_' . $custom_field['id'] );
					// Also build Custom Field IDs.
					$this->custom_field_ids[] = (int) $custom_field['id'];
				}
			}
		}

		// Get the public Attachment Fields from transient if possible.
		if ( false !== $data && ! empty( $data['attachment_fields'] ) ) {
			$this->attachment_fields = $data['attachment_fields'];
		} else {
			$this->attachment_fields        = $this->civicrm->attachment->civicrm_fields_get( 'public' );
			$transient['attachment_fields'] = $this->attachment_fields;
		}

		// Populate public mapping Fields.
		foreach ( $this->attachment_fields as $attachment_field ) {
			$this->mapping_field_filters_add( 'attachment_' . $attachment_field['name'] );
		}

		// Attachment File and Conditional Fields.
		$this->mapping_field_filters_add( 'attachment_file' );
		$this->mapping_field_filters_add( 'attachment_conditional' );

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$this->js_model_case_reference_field_add( $this->field_name . 'activity_case_id' );
		}

		// Activity Conditional Field.
		$this->mapping_field_filters_add( 'activity_conditional' );

		// Maybe store Fields in transient.
		if ( false === $data && 1 === $acfe_transients ) {
			$duration = $this->acfe->admin->transient_duration_get();
			set_site_transient( $this->transient_key, $transient, $duration );
		}

	}

	/**
	 * Pre-load mapping Fields with "Generic" choices.
	 *
	 * Not used but leaving this here for future use.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing array of Field data.
	 * @return array $field The modified array of Field data.
	 */
	public function prepare_choices( $field ) {

		// --<
		return $field;

	}

	/**
	 * Performs validation when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array   $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string  $action The customised name of the action.
	 */
	public function validation( $form, $current_post_id, $action ) {

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id   = acf_maybe_get( $form, 'ID' );

		// Validate the Activity data.
		$valid = $this->form_activity_validate( $form, $current_post_id, $action );
		if ( ! $valid ) {
			return;
		}

		// TODO: Check other Activity Entities.

	}

	/**
	 * Performs the action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array   $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string  $action The customised name of the action.
	 */
	public function make( $form, $current_post_id, $action ) {

		// Bail if a filter has overridden the action.
		if ( false === $this->make_skip( $form, $current_post_id, $action ) ) {
			return;
		}

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id   = acf_maybe_get( $form, 'ID' );

		// Init array to save for this Action.
		$args = [
			'form_action' => $this->action_name,
			'id'          => false,
		];

		// Populate Activity and Custom Field data arrays.
		$activity      = $this->form_activity_data( $form, $current_post_id, $action );
		$custom_fields = $this->form_custom_data( $form, $current_post_id, $action );
		$attachments   = $this->form_attachments_data( $form, $current_post_id, $action );

		// Save the Activity with the data from the Form.
		$args['activity'] = $this->form_activity_save( $activity, $custom_fields );

		// If we get an Activity.
		if ( $args['activity'] !== false ) {

			// Post-process Custom Fields now that we have an Activity.
			$this->form_custom_post_process( $form, $current_post_id, $action, $args['activity'] );

			// Save the Attachments with the data from the Form.
			$args['attachments'] = $this->form_attachments_save( $args['activity'], $attachments );

			// Save the Activity ID for backwards compatibility.
			$args['id'] = $args['activity']['id'];

		}

		// Save the results of this Action for later use.
		$this->make_action_save( $action, $args );

	}

	// -------------------------------------------------------------------------

	/**
	 * Defines additional Fields for the "Action" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_action_append() {

		// Define Field.
		$activity_types_field = [
			'key'               => $this->field_key . 'activity_types',
			'label'             => __( 'Activity Type', 'civicrm-wp-profile-sync' ),
			'name'              => $this->field_name . 'activity_types',
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
			'choices'           => $this->civicrm->activity_type->choices_get(),
		];

		// Define Status Field.
		$activity_status_field = [
			'key'               => $this->field_key . 'activity_status_id',
			'label'             => __( 'Activity Status', 'civicrm-wp-profile-sync' ),
			'name'              => $this->field_name . 'activity_status_id',
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
			'choices'           => $this->civicrm->activity_field->options_get( 'status_id' ),
		];

		// Init Fields.
		$fields = [
			$activity_types_field,
			$activity_status_field,
		];

		// Add Campaign Field if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {

			$fields[] = [
				'key'               => $this->field_key . 'activity_campaign_id',
				'label'             => __( 'Campaign', 'civicrm-wp-profile-sync' ),
				'name'              => $this->field_name . 'activity_campaign_id',
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
				'choices'           => $this->civicrm->campaign->choices_get(),
			];

		}

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {

			$fields[] = [
				'key'              => $this->field_key . 'activity_case_id',
				'label'            => __( 'Case', 'civicrm-wp-profile-sync' ),
				'name'             => $this->field_name . 'activity_case_id',
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
				'name'              => $this->field_name . 'activity_case_create',
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

		// Add Conditional Field.
		$code                       = 'activity_conditional';
		$label                      = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$conditional                = $this->mapping_field_get( $code, $label );
		$conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$conditional['wrapper']['data-instruction-placement'] = 'field';
		$conditional['instructions']                          = __( 'To add the Activity only when a Form Field is populated (e.g. "Subject") link this to the Form Field. To add the Activity only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );
		$fields[] = $conditional;

		// --<
		return $fields;

	}

	// -------------------------------------------------------------------------

	/**
	 * Defines the "Mapping" Tab.
	 *
	 * @since 0.5
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
	 * @since 0.5
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
				'name'         => $this->field_name . 'contact_group_' . $field['name'],
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
				'name'              => $this->field_name . 'ref_' . $field['name'],
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
							'field'    => $this->field_key . 'map_' . $field['name'],
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
			$cid_field = [
				'key'               => $this->field_key . 'cid_' . $field['name'],
				'label'             => __( 'CiviCRM Contact ID', 'civicrm-wp-profile-sync' ),
				'name'              => $this->field_name . 'cid_' . $field['name'],
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
							'field'    => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Add Contact ID Field.
			$contact_group_field['sub_fields'][] = $cid_field;

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
	 * @since 0.5
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
	 * @since 0.5
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
						'field'    => $this->field_key . 'activity_types',
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
				'name'                  => $this->field_name . 'custom_group_' . $custom_group['id'],
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
	 * @since 0.5.2
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
			'name'                          => $this->field_name . 'attachment_repeater',
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
			'collapsed'                     => $this->field_key . 'map_attachment_file',
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

		// Assign code and label for "Conditional" Field.
		$code  = 'attachment_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$attachment_conditional                 = $this->mapping_field_get( $code, $label );
		$attachment_conditional['placeholder']  = __( 'Always add', 'civicrm-wp-profile-sync' );
		$attachment_conditional['instructions'] = __( 'To add the Attachment to the Activity only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$sub_fields[] = $attachment_conditional;

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

	// -------------------------------------------------------------------------

	/**
	 * Builds Activity data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array   $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string  $action The customised name of the action.
	 * @return array $data The array of Activity data.
	 */
	public function form_activity_data( $form, $current_post_id, $action ) {

		// Build Fields array.
		$fields = [];
		foreach ( $this->public_activity_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[ $field['name'] ] = get_sub_field( $this->field_key . 'map_' . $field['name'] );
			}
		}

		// Populate data array with values of mapped Fields.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Get the Activity Type & Status.
		$data['activity_type_id'] = get_sub_field( $this->field_key . 'activity_types' );
		$data['status_id']        = get_sub_field( $this->field_key . 'activity_status_id' );

		// Get the Activity Contacts.
		foreach ( $this->fields_for_contacts as $field ) {

			// Get Group Field.
			$contact_group_field = get_sub_field( $this->field_key . 'contact_group_' . $field['name'] );

			// Check Action Reference Field.
			$contact_id = false;
			if ( ! empty( $contact_group_field[ $this->field_name . 'ref_' . $field['name'] ] ) ) {
				$action_name = $contact_group_field[ $this->field_name . 'ref_' . $field['name'] ];
				$contact_id  = $this->form_contact_id_get_mapped( $action_name );
			}

			// Check Contact ID Field.
			if ( $contact_id === false ) {
				if ( ! empty( $contact_group_field[ $this->field_name . 'cid_' . $field['name'] ] ) ) {
					$contact_id = $contact_group_field[ $this->field_name . 'cid_' . $field['name'] ];
				}
			}

			// Check mapped Field.
			if ( $contact_id === false ) {
				if ( ! empty( $contact_group_field[ $this->field_name . 'map_' . $field['name'] ] ) ) {
					$reference = [ $field['name'] => $contact_group_field[ $this->field_name . 'map_' . $field['name'] ] ];
					$reference = acfe_form_map_vs_fields( $reference, $reference, $current_post_id, $form );
					if ( ! empty( $reference[ $field['name'] ] ) && is_numeric( $reference[ $field['name'] ] ) ) {
						$contact_id = $reference[ $field['name'] ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $contact_id ) && is_numeric( $contact_id ) ) {
				$data[ $field['name'] ] = $contact_id;
			}

		}

		// Add the Campaign if the CiviCampaign component is active.
		$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
		if ( $campaign_active ) {
			$data['campaign_id'] = get_sub_field( $this->field_key . 'activity_campaign_id' );
		}

		// Add the Case if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$action_name                  = get_sub_field( $this->field_key . 'activity_case_id' );
			$case_data                    = $this->form_case_get_mapped( $action_name );
			$data['case_id']              = $case_data['case_id'];
			$data['case_skipped']         = $case_data['skipped'];
			$data['case_created']         = $case_data['created'];
			$data['case_activity_create'] = get_sub_field( $this->field_key . 'activity_case_create' );
		}

		// Get Activity Conditional Reference.
		$data['activity_conditional_ref'] = get_sub_field( $this->field_key . 'map_activity_conditional' );
		$conditionals                     = [ $data['activity_conditional_ref'] ];

		// Populate array with mapped Conditional Field values.
		$conditionals = acfe_form_map_vs_fields( $conditionals, $conditionals, $current_post_id, $form );

		// Save Activity Conditional.
		$data['activity_conditional'] = array_pop( $conditionals );

		// --<
		return $data;

	}

	/**
	 * Validates the Activity data array from mapped Fields.
	 *
	 * @since 0.5.2
	 *
	 * @param array   $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string  $action The customised name of the action.
	 * @return bool $valid True if the Activity can be saved, false otherwise.
	 */
	public function form_activity_validate( $form, $current_post_id, $action ) {

		// Get the Activity.
		$activity = $this->form_activity_data( $form, $current_post_id, $action );

		// Skip if the Activity Conditional Reference Field has a value.
		if ( ! empty( $activity['activity_conditional_ref'] ) ) {
			// And the Activity Conditional Field has no value.
			if ( empty( $activity['activity_conditional'] ) ) {
				return true;
			}
		}

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
	 * @since 0.5
	 *
	 * @param array $activity_data The array of Activity data.
	 * @param array $custom_data The array of Custom Field data.
	 * @return array|bool $activity The Activity data array, or false on failure.
	 */
	public function form_activity_save( $activity_data, $custom_data ) {

		// Init return.
		$activity = false;

		// Skip if the Activity Conditional Reference Field has a value.
		if ( ! empty( $activity_data['activity_conditional_ref'] ) ) {
			// And the Activity Conditional Field has no value.
			if ( empty( $activity_data['activity_conditional'] ) ) {
				return $activity;
			}
		}

		// Bail if the Activity can't be created.
		if ( empty( $activity_data['source_contact_id'] ) || empty( $activity_data['activity_type_id'] ) ) {
			return $activity;
		}

		// Add Custom Field data if present.
		if ( ! empty( $custom_data ) ) {
			$activity_data += $custom_data;
		}

		// Unset Activity Conditionals.
		if ( isset( $activity_data['activity_conditional'] ) ) {
			unset( $activity_data['activity_conditional'] );
		}
		if ( isset( $activity_data['activity_conditional_ref'] ) ) {
			unset( $activity_data['activity_conditional_ref'] );
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

		// Bail on failure.
		if ( $result === false ) {
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
	 * @since 0.5
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
		$do_not_notify_for = $this->plugin->civicrm->get_setting( 'do_not_notify_assignees_for' );
		if ( in_array( $activity['activity_type_id'], $do_not_notify_for ) ) {
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

	/**
	 * Finds the linked Contact ID when it has been mapped.
	 *
	 * @since 0.5
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	public function form_contact_id_get_mapped( $action_name ) {

		// Init return.
		$contact_id = false;

		// We need an Action Name.
		if ( empty( $action_name ) ) {
			return $contact_id;
		}

		// Get the Contact data for that Action.
		$related_contact = acfe_form_get_action( $action_name, 'contact' );
		if ( empty( $related_contact['id'] ) ) {
			return $contact_id;
		}

		// Assign return.
		$contact_id = (int) $related_contact['id'];

		// --<
		return $contact_id;

	}

	/**
	 * Finds the linked Case data when it has been mapped.
	 *
	 * @since 0.5
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return array $case The array of data about the linked Case.
	 */
	public function form_case_get_mapped( $action_name ) {

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
		$related_case = acfe_form_get_action( $action_name, 'case' );
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

	// -------------------------------------------------------------------------

	/**
	 * Builds Custom Field data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array   $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string  $action The customised name of the action.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_custom_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Init File Fields tracker.
		$file_fields = [];

		// Build data array.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Fresh Fields array.
			$fields = [];

			// Get Group Field.
			$custom_group_field = get_sub_field( $this->field_key . 'custom_group_' . $custom_group['id'] );
			if ( empty( $custom_group_field ) ) {
				continue;
			}

			// Get mapped Fields.
			foreach ( $custom_group_field as $field ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {

					// Add to mapped Fields array.
					$code            = 'custom_' . $custom_field['id'];
					$fields[ $code ] = $custom_group_field[ $this->field_name . 'map_' . $code ];

					// Track any "File" Custom Fields.
					if ( $custom_field['data_type'] === 'File' ) {
						$file_fields[ $code ] = $custom_field['id'];
					}

				}
			}

			// Populate data array with values of mapped Fields.
			$data += acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		}

		// Post-process data for File Fields.
		if ( ! empty( $file_fields ) ) {
			foreach ( $file_fields as $code => $field_ref ) {

				// Get the ACF Field settings.
				$selector = acfe_form_map_field_value_load( $field_ref, $current_post_id, $form );
				$settings = get_field_object( $selector, $current_post_id );

				// Skip if "CiviCRM only" and no File was uploaded.
				if ( empty( $data[ $code ] ) ) {
					if ( ! empty( $settings['civicrm_file_no_wp'] ) ) {
						continue;
					}
				}

				// Flag for possible deletion if no File was uploaded.
				if ( empty( $data[ $code ] ) ) {
					$this->file_fields_empty[ $code ] = [
						'field'    => $field_ref,
						'selector' => $selector,
						'settings' => $settings,
					];
				}

				// Get the processed value (the Attachment ID).
				$attachment_id = (int) $data[ $code ];

				// Build an args array.
				$args = [
					'selector' => $selector,
					'post_id'  => $current_post_id,
				];

				// Overwrite entry in data array with data for CiviCRM.
				$data[ $code ] = $this->civicrm->attachment->value_get_for_civicrm( $attachment_id, $settings, $args );

				// Maybe delete the WordPress Attachment.
				if ( ! empty( $settings['civicrm_file_no_wp'] ) ) {
					wp_delete_attachment( $attachment_id, true );
				}

			}
		}

		// --<
		return $data;

	}

	/**
	 * Processes Custom Fields once an Activity has been established.
	 *
	 * This is used when a File has been "deleted" and the ACF Field is set not
	 * to delete the WordPress Attachment. In such cases, the ACF "File" Field
	 * may be auto-populated in the Form - so "deleting" it is assumed to mean
	 * that the submitter wishes to delete the WordPress Attachment and the
	 * content of the CiviCRM Custom Field.
	 *
	 * This is only possible because sending an empty value to the API for the
	 * CiviCRM Custom Field will cause the update process to be skipped for
	 * Custom Fields of type "File" - so the previous value will still exist.
	 *
	 * @since 0.5.2
	 *
	 * @param array   $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string  $action The customised name of the action.
	 * @param array   $activity The array of Activity data.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_custom_post_process( $form, $current_post_id, $action, $activity ) {

		// Bail if we have no post-process array.
		if ( empty( $this->file_fields_empty ) ) {
			return;
		}

		// Bail if we have no Activity ID.
		if ( empty( $activity['id'] ) ) {
			return;
		}

		// Get the array of Custom Field IDs.
		$custom_field_ids = array_keys( $this->file_fields_empty );
		array_walk(
			$custom_field_ids,
			function( &$item ) {
				$item = (int) trim( str_replace( 'custom_', '', $item ) );
			}
		);

		// Get the corresponding values.
		$values = $this->civicrm->custom_field->values_get_by_activity_id( $activity['id'], $custom_field_ids );
		if ( empty( $values ) ) {
			return;
		}

		// Handle each "deleted" Field.
		foreach ( $values as $custom_field_id => $file_id ) {

			// Sanity check.
			if ( empty( $this->file_fields_empty[ 'custom_' . $custom_field_id ] ) ) {
				continue;
			}

			// Skip if there's no Custom Field value.
			if ( empty( $file_id ) ) {
				continue;
			}

			// Get the data from the post-process array.
			$data = $this->file_fields_empty[ 'custom_' . $custom_field_id ];

			// Build args.
			$args = [
				'entity_id'       => $activity['id'],
				'custom_field_id' => $custom_field_id,
			];

			// Hand off to Attachment class.
			$this->civicrm->attachment->fields_clear( (int) $file_id, $data['settings'], $args );

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Builds Attachment data array from mapped Fields.
	 *
	 * @since 0.5.2
	 *
	 * @param array   $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string  $action The customised name of the action.
	 * @return array $attachment_data The array of Attachment data.
	 */
	public function form_attachments_data( $form, $current_post_id, $action ) {

		// Init return.
		$attachment_data = [];

		// Get the Attachment Repeater Field.
		$attachment_repeater = get_sub_field( $this->field_key . 'attachment_repeater' );

		// Skip it if it's empty.
		if ( empty( $attachment_repeater ) ) {
			return $attachment_data;
		}

		// Loop through the Action Fields.
		foreach ( $attachment_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Get File Field.
			$fields['file'] = $field[ $this->field_name . 'map_attachment_file' ];

			// Get mapped Fields.
			foreach ( $this->attachment_fields as $attachment_field ) {
				$fields[ $attachment_field['name'] ] = $field[ $this->field_name . 'map_attachment_' . $attachment_field['name'] ];
			}

			// Get Attachment Conditional.
			$fields['attachment_conditional'] = $field[ $this->field_name . 'map_attachment_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Attachment Conditional Reference.
			$fields['attachment_conditional_ref'] = $field[ $this->field_name . 'map_attachment_conditional' ];

			// Add the data.
			$attachment_data[] = $fields;

		}

		// --<
		return $attachment_data;

	}

	/**
	 * Saves the CiviCRM Attachment(s) given data from mapped Fields.
	 *
	 * @since 0.5.2
	 *
	 * @param array $activity The array of Activity data.
	 * @param array $attachment_data The array of Attachment data.
	 * @return array|bool $attachments The array of Attachments, or false on failure.
	 */
	public function form_attachments_save( $activity, $attachment_data ) {

		// Init return.
		$attachments = false;

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

			// Skip if there's no WordPress Attachment ID.
			if ( empty( $attachment['file'] ) ) {
				continue;
			}

			// Only skip if the Attachment Conditional Reference Field has a value.
			if ( ! empty( $attachment['attachment_conditional_ref'] ) ) {
				// And the Attachment Conditional Field has no value.
				if ( empty( $attachment['attachment_conditional'] ) ) {
					continue;
				}
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
			if ( $result === false ) {
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

}
