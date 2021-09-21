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
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The parent object.
	 */
	public $acfe;

	/**
	 * ACFE Form object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $form The ACFE Form object.
	 */
	public $form;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Form Action Name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $action_name The unique name of the Form Action.
	 */
	public $action_name = 'cwps_activity';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_key The prefix for the Field Key.
	 */
	public $field_key = 'field_cwps_activity_action_';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_name The prefix for the Field Name.
	 */
	public $field_name = 'cwps_activity_action_';

	/**
	 * Public Activity Fields to add.
	 *
	 * These are not mapped for Past Type Sync, so need to be added.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $fields_to_add The Public Activity Fields to add.
	 */
	public $fields_to_add = [
		'details' => 'wysiwyg',
		'subject' => 'text',
	];

	/**
	 * Public Activity Fields to ignore.
	 *
	 * These are mapped for Past Type Sync, but need special handling.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $fields_to_ignore The Public Activity Fields to ignore.
	 */
	public $fields_to_ignore = [
		'status_id' => 'select',
		'source_contact_id' => 'civicrm_contact',
		'target_contact_id' => 'civicrm_contact',
		'assignee_contact_id' => 'civicrm_contact',
	];

	/**
	 * Activity Contact Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $fields_to_ignore The Public Activity Fields to ignore.
	 */
	public $contact_fields = [
		'source_contact_id' => 'civicrm_contact',
		'target_contact_id' => 'civicrm_contact',
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

		// Store reference to plugin object.
		$this->plugin = $parent->acf_loader->plugin;

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to ACFE object.
		$this->acfe = $parent->acfe;

		// Store reference to Form object.
		$this->form = $parent;

		// Store reference to CiviCRM object.
		$this->civicrm = $this->acf_loader->civicrm;

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

		// Get the public Activity Fields for all top level Activity Types.
		$this->public_activity_fields = $this->civicrm->activity_field->get_public_fields();

		// Prepend the ones that are needed in ACFE Forms (i.e. Subject and Details).
		foreach ( $this->fields_to_add as $name => $field_type ) {
			array_unshift( $this->public_activity_fields, $this->civicrm->activity_field->get_by_name( $name ) );
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

			// Pre-load with "Generic" values.
			//$filter = 'acf/prepare_field/name=' . $this->field_name . 'map_' . $field['name'];
			//add_filter( $filter, [ $this, 'prepare_choices' ], 5 );

		}

		// Get the Custom Groups and Fields for all Activity Types.
		$this->custom_fields = $this->civicrm->custom_group->get_for_activities();
		$this->custom_field_ids = [];

		// Populate mapping Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->mapping_field_filters_add( 'custom_' . $custom_field['id'] );
					// Also build Custom Field IDs.
					$this->custom_field_ids[] = (int) $custom_field['id'];
				}
			}
		}

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$this->js_model_case_reference_field_add( $this->field_name . 'activity_case_id' );
		}

		// Activity Conditional Field.
		$this->mapping_field_filters_add( 'activity_conditional' );

	}



	/**
	 * Pre-load mapping Fields with "Generic" choices.
	 *
	 * Not used but leaving this here for future use.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing array of Field data.
	 * @param array $field The modified array of Field data.
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
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function validation( $form, $current_post_id, $action ) {

		/*
		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );
		//acfe_add_validation_error( $selector, $message );
		*/

	}



	/**
	 * Performs the action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function make( $form, $current_post_id, $action ) {

		// Bail a filter has overridden the action.
		if ( false === $this->make_skip( $form, $current_post_id, $action ) ) {
			return;
		}

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Populate Activity, Email, Relationship and Custom Field data arrays.
		$activity = $this->form_activity_data( $form, $current_post_id, $action );
		$custom_fields = $this->form_custom_data( $form, $current_post_id, $action );

		// Save the Activity with the data from the Form.
		$activity = $this->form_activity_save( $activity, $custom_fields );

		// Save the results of this Action for later use.
		$this->make_action_save( $action, $activity );

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
			'key' => $this->field_key . 'activity_types',
			'label' => __( 'Activity Type', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'activity_types',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field'
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->civicrm->activity_type->choices_get(),
		];

		// Define Status Field.
		$activity_status_field = [
			'key' => $this->field_key . 'activity_status_id',
			'label' => __( 'Activity Status', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'activity_status_id',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field'
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->civicrm->activity_field->options_get( 'status_id' ),
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
				'key' => $this->field_key . 'activity_campaign_id',
				'label' => __( 'Campaign', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'activity_campaign_id',
				'type' => 'select',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-instruction-placement' => 'field'
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => '',
				'allow_null' => 1,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => $this->civicrm->campaign->choices_get(),
			];

		}

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {

			$fields[] = [
				'key' => $this->field_key . 'activity_case_id',
				'label' => __( 'Case', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'activity_case_id',
				'type' => 'cwps_acfe_case_action_ref',
				'instructions' => __( 'Select a Case Action in this Form.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-instruction-placement' => 'field'
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 1,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
			];

		}

		// Add Conditional Field.
		$code = 'activity_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$conditional = $this->mapping_field_get( $code, $label );
		$conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$conditional['wrapper']['data-instruction-placement'] = 'field';
		$conditional['instructions'] = __( 'To add the Activity only when a Form Field is populated (e.g. "Subject") link this to the Form Field. To add the Activity only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );
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

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$mapping_contacts_accordion,
			$mapping_activity_accordion,
			$mapping_custom_accordion
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

		// "Activity" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contacts_open',
			'label' => __( 'Contact References', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add Contact Reference Fields.
		foreach ( $this->fields_for_contacts as $field ) {

			// Bundle them into a container group.
			$contact_group_field = [
				'key' => $this->field_key . 'contact_group_' . $field['name'],
				'label' => $field['title'],
				'name' => $this->field_name . 'contact_group_' . $field['name'],
				'type' => 'group',
				'instructions' => sprintf( __( 'Use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $field['title'] ),
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'required' => 0,
				'layout' => 'block',
			];

			// Define Contact Action Reference Field.
			$contact_group_field['sub_fields'][] = [
				'key' => $this->field_key . 'ref_' . $field['name'],
				'label' => __( 'CiviCRM Contact Action', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'ref_' . $field['name'],
				'type' => 'cwps_acfe_contact_action_ref',
				'instructions' => __( 'Select a Contact Action in this Form.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'cid_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Define Contact ID Field.
			$cid_field = [
				'key' => $this->field_key . 'cid_' . $field['name'],
				'label' => __( 'CiviCRM Contact ID', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'cid_' . $field['name'],
				'type' => 'civicrm_contact',
				'instructions' => __( 'Select a CiviCRM Contact ID from the database.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
				],
				'acfe_permissions' => '',
				'default_value' => '',
				'placeholder' => __( 'None', 'civicrm-wp-profile-sync' ),
				'allow_null' => 0,
				'multiple' => 0,
				'ui' => 0,
				'return_format' => 'value',
				'choices' => [],
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'ref_' . $field['name'],
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'map_' . $field['name'],
							'operator' => '==empty',
						],
					],
				],
			];

			// Add Contact ID Field.
			$contact_group_field['sub_fields'][] = $cid_field;

			// Define Custom Contact Reference Field.
			$title = sprintf( __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' ), $field['title'] );
			$mapping_field = $this->mapping_field_get( $field['name'], $title );
			$mapping_field['instructions'] = __( 'Define a custom Contact Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field' => $this->field_key . 'ref_' . $field['name'],
						'operator' => '==empty',
					],
					[
						'field' => $this->field_key . 'cid_' . $field['name'],
						'operator' => '==empty',
					],
				],
			];

			// Add Custom Contact Reference Field.
			$contact_group_field['sub_fields'][] = $mapping_field;

			// Add Contact Reference Group.
			$fields[] = $contact_group_field;

		}

		// "Activity" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contacts_close',
			'label' => __( 'Activity Contacts', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
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

		// "Activity" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_activity_open',
			'label' => __( 'Activity Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Add "Mapping" Fields.
		foreach ( $this->public_activity_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->fields_to_ignore ) ) {
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Activity" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_activity_close',
			'label' => __( 'Activity Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
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
			'key' => $this->field_key . 'mapping_accordion_custom_open',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 0,
		];

		// Get top-level Activity Types.
		$activity_types = $this->civicrm->activity_type->choices_get();

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
						'field' => $this->field_key . 'activity_types',
						'operator' => '==contains',
						'value' => $activity_type_id,
					];

					$conditional_logic[] = [
						$activity_type,
					];

				}
			}

			// Bundle the Custom Fields into a container group.
			$custom_group_field = [
				'key' => $this->field_key . 'custom_group_' . $custom_group['id'],
				'label' => $custom_group['title'],
				'name' => $this->field_name . 'custom_group_' . $custom_group['id'],
				'type' => 'group',
				'instructions' => '',
				'instruction_placement' => 'field',
				'required' => 0,
				'layout' => 'block',
				'conditional_logic' => $conditional_logic,
			];

			// Init sub Fields array.
			$sub_fields = [];

			// Add "Map" Fields for the Custom Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$sub_fields[] = $this->mapping_field_get( $code, $custom_field['label'], $conditional_logic );
			}

			// Add the Sub-fields.
			$custom_group_field['sub_fields'] = $sub_fields;

			// Add the Sub-fields.
			$fields[] = $custom_group_field;

		}

		// "Custom" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_custom_close',
			'label' => __( 'Custom Fields', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'open' => 0,
			'multi_expand' => 1,
			'endpoint' => 1,
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
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
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
		$data['status_id'] = get_sub_field( $this->field_key . 'activity_status_id' );

		// Get the Activity Contacts.
		foreach ( $this->fields_for_contacts as $field ) {

			// Get Group Field.
			$contact_group_field = get_sub_field( $this->field_key . 'contact_group_' . $field['name'] );

			// Check Action Reference Field.
			$contact_id = false;
			if ( ! empty( $contact_group_field[ $this->field_name . 'ref_' . $field['name'] ] ) ) {
				$action_name = $contact_group_field[ $this->field_name . 'ref_' . $field['name'] ];
				$contact_id = $this->form_contact_id_get_mapped( $action_name );
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
			$action_name = get_sub_field( $this->field_key . 'activity_case_id' );
			$data['case_id'] = $this->form_case_id_get_mapped( $action_name );
		}

		// Get Activity Conditional Reference.
		$data['activity_conditional_ref'] = get_sub_field( $this->field_key . 'map_activity_conditional' );
		$conditionals = [ $data['activity_conditional_ref'] ];

		// Populate array with mapped Conditional Field values.
		$conditionals = acfe_form_map_vs_fields( $conditionals, $conditionals, $current_post_id, $form );

		// Save Activity Conditional.
		$data['activity_conditional'] = array_pop( $conditionals );

		// --<
		return $data;

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

		// Add Custom Field data if present.
		if ( ! empty ( $custom_data ) ) {
			$activity_data += $custom_data;
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

		// --<
		return $activity;

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
	 * Finds the linked Case ID when it has been mapped.
	 *
	 * @since 0.5
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return integer|bool $case_id The numeric ID of the Case, or false if not found.
	 */
	public function form_case_id_get_mapped( $action_name ) {

		// Init return.
		$case_id = false;

		// Get the Case ID for that Action.
		$related_case_id = acfe_form_get_action( $action_name, 'id' );
		if ( empty( $related_case_id ) ) {
			return $case_id;
		}

		// Assign return.
		$case_id = (int) $related_case_id;

		// --<
		return $case_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Custom Field data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_custom_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Build data array.
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Fresh Fields array.
			$fields = [];

			// Get Group Field.
			$custom_group_field = get_sub_field( $this->field_key . 'custom_group_' . $custom_group['id'] );
			foreach ( $custom_group_field as $field ) {

				// Get mapped Fields.
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$code = 'custom_' . $custom_field['id'];
					$fields[ $code ] = $custom_group_field[ $this->field_name . 'map_' . $code ];
				}

			}

			// Populate data array with values of mapped Fields.
			$data += acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		}

		// --<
		return $data;

	}



} // Class ends.



