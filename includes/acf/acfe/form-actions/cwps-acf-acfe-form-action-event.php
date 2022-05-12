<?php
/**
 * "Event" ACFE Form Action Class.
 *
 * Handles the "Event" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync "Event" ACFE Form Action Class.
 *
 * A class that handles the "Event" ACFE Form Action.
 *
 * @since 0.5.4
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Event extends CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Base {

	/**
	 * Plugin object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $acf The parent object.
	 */
	public $acfe;

	/**
	 * ACFE Form object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $form The ACFE Form object.
	 */
	public $form;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Form Action Name.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string $action_name The unique name of the Form Action.
	 */
	public $action_name = 'cwps_event';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string $field_key The prefix for the Field Key.
	 */
	public $field_key = 'field_cwps_event_action_';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string $field_name The prefix for the Field Name.
	 */
	public $field_name = 'cwps_event_action_';

	/**
	 * Event Contact Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $contact_fields The Event Contact Fields.
	 */
	public $contact_fields = [
		'created_id' => 'civicrm_contact',
	];

	/**
	 * Public Event Fields to ignore.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $event_fields_to_ignore The Public Event Fields to ignore.
	 */
	public $event_fields_to_ignore = [
		'event_type_id' => 'select',
		'default_role_id' => 'select',
		'participant_listing_id' => 'select',
		'campaign_id' => 'select',
		'is_show_location' => 'true_false',
	];

	/**
	 * Location Fields to ignore.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $location_fields_to_ignore The Location Fields to ignore.
	 */
	public $location_fields_to_ignore = [
		'is_show_location' => 'true_false',
	];

	/**
	 * Location Phone Fields to ignore.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $phone_fields_to_ignore The Location Phone Fields to ignore.
	 */
	public $phone_fields_to_ignore = [
		'phone_type_id' => 'select',
	];

	/**
	 * Registration Fields to ignore.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $registration_fields_to_ignore The Registration Fields to ignore.
	 */
	public $registration_fields_to_ignore = [
		'is_online_registration' => 'true_false',
	];

	/**
	 * Registration Confirmation Screen Fields to ignore.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $registration_email_fields_to_ignore The Registration Confirmation Screen Fields to ignore.
	 */
	public $confirm_fields_to_ignore = [
		'is_confirm_enabled' => 'true_false',
	];

	/**
	 * Registration Confirmation Email Fields to ignore.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array $registration_email_fields_to_ignore The Registration Confirmation Email Fields to ignore.
	 */
	public $registration_email_fields_to_ignore = [
		'is_email_confirm' => 'true_false',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.5.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acfe = $parent->acfe;
		$this->form = $parent;
		$this->civicrm = $this->acf_loader->civicrm;

		// Label this Form Action.
		$this->action_label = __( 'CiviCRM Event action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->alias_placeholder = __( 'CiviCRM Event', 'civicrm-wp-profile-sync' );

		// Register hooks.
		$this->register_hooks();

		// Init parent.
		parent::__construct();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5.4
	 */
	public function register_hooks() {

	}



	/**
	 * Configure this object.
	 *
	 * @since 0.5.4
	 */
	public function configure() {

		// Get the public Event Fields.
		$this->public_event_fields = $this->civicrm->event_field->get_public_fields( 'create' );

		// Populate public mapping Fields.
		foreach ( $this->public_event_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->event_fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Get the Event Settings Fields.
		$this->settings_fields = $this->civicrm->event_field->get_settings_fields( 'create' );

		// Populate Event Settings mapping Fields.
		foreach ( $this->settings_fields as $field ) {
			$this->mapping_field_filters_add( $field['name'] );
		}

		// Handle Contact Fields.
		foreach ( $this->contact_fields as $name => $field_type ) {

			// Populate mapping Fields.
			$field = $this->civicrm->event_field->get_by_name( $name );
			$this->mapping_field_filters_add( $field['name'] );

			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( $this->field_name . 'ref_' . $field['name'] );

			// Also build array of data for CiviCRM Fields.
			$this->fields_for_contacts[] = $field;

			// Pre-load with "Generic" values.
			//$filter = 'acf/prepare_field/name=' . $this->field_name . 'map_' . $field['name'];
			//add_filter( $filter, [ $this, 'prepare_choices' ], 5 );

		}

		// ---------------------------------------------------------------------

		// Populate Event Location Settings mapping Fields.
		$this->mapping_field_filters_add( 'is_show_location' );
		$this->mapping_field_filters_add( 'existing_location' );

		// Get the Event Location Address Fields.
		$this->event_location_address_fields = $this->civicrm->event_location->get_address_fields( 'create' );

		// Populate Event Location Address mapping Fields.
		foreach ( $this->event_location_address_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->location_fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Get the Custom Fields for all Addresses.
		$this->address_custom_fields = $this->plugin->civicrm->custom_group->get_for_addresses();

		// Populate Address mapping Fields.
		foreach ( $this->address_custom_fields as $key => $custom_group ) {
			if ( ! empty( $custom_group['api.CustomField.get']['values'] ) ) {
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$this->mapping_field_filters_add( 'custom_' . $custom_field['id'] );
				}
			}
		}

		// Email Conditional Field.
		$this->mapping_field_filters_add( 'address_conditional' );

		// Get the Event Location Email Fields.
		$this->event_location_email_fields = $this->civicrm->event_location->get_email_fields( 'create' );

		// Populate Event Location Email mapping Fields.
		foreach ( $this->event_location_email_fields as $field ) {
			$this->mapping_field_filters_add( $field['name'] );
		}

		// Email Conditional Field.
		$this->mapping_field_filters_add( 'email_fields_conditional' );

		// Get the Event Location Phone Fields.
		$this->event_location_phone_fields = $this->civicrm->event_location->get_phone_fields( 'create' );

		// Populate Event Location Phone mapping Fields.
		foreach ( $this->event_location_phone_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->phone_fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Get Phone Types.
		$this->phone_types = $this->plugin->civicrm->phone->phone_types_get();

		// Phone Conditional Field.
		$this->mapping_field_filters_add( 'phone_fields_conditional' );

		// ---------------------------------------------------------------------

		// Get the Event Registration Fields.
		$this->event_registration_fields = $this->civicrm->event_registration->get_settings_fields();

		// Populate Event Registration mapping Fields.
		foreach ( $this->event_registration_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Get the Event Registration Screen Fields.
		$this->event_registration_screen_fields = $this->civicrm->event_registration->get_register_screen_fields();

		// Populate Event Registration Screen mapping Fields.
		foreach ( $this->event_registration_screen_fields as $field ) {
			$this->mapping_field_filters_add( $field['name'] );
		}

		// Get the Event Registration Confirmation Screen Fields.
		$this->event_confirm_screen_fields = $this->civicrm->event_registration->get_confirm_screen_fields();

		// Populate Event Registration Confirmation Screen mapping Fields.
		foreach ( $this->event_confirm_screen_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->confirm_fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Get the Event Registration Thank You Screen Fields.
		$this->event_thankyou_screen_fields = $this->civicrm->event_registration->get_thankyou_screen_fields();

		// Populate Event Registration Thank You Screen mapping Fields.
		foreach ( $this->event_thankyou_screen_fields as $field ) {
			$this->mapping_field_filters_add( $field['name'] );
		}

		// Get the Event Registration Confirmation Email Fields.
		$this->event_confirmation_email_fields = $this->civicrm->event_registration->get_confirmation_email_fields();

		// Populate Event Registration Confirmation Email mapping Fields.
		foreach ( $this->event_confirmation_email_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_email_fields_to_ignore ) ) {
				$this->mapping_field_filters_add( $field['name'] );
			}
		}

		// Populate Event Registration Settings mapping Fields.
		$this->mapping_field_filters_add( 'is_online_registration' );
		$this->mapping_field_filters_add( 'is_confirm_enabled' );
		$this->mapping_field_filters_add( 'is_email_confirm' );
		$this->mapping_field_filters_add( 'custom_pre_id' );
		$this->mapping_field_filters_add( 'custom_post_id' );

		// ---------------------------------------------------------------------

		// Get the Custom Groups and Fields.
		$this->custom_fields = $this->plugin->civicrm->custom_group->get_for_entity_type( 'Event', '', true );
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

		// Event Conditional Field.
		$this->mapping_field_filters_add( 'event_conditional' );

	}



	/**
	 * Pre-load mapping Fields with "Generic" choices.
	 *
	 * Not used but leaving this here for future use.
	 *
	 * @since 0.5.4
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
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function validation( $form, $current_post_id, $action ) {

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Validate the Event data.
		$valid = $this->form_event_validate( $form, $current_post_id, $action );
		if ( ! $valid ) {
			return;
		}

		// Validate the Event Registration data.
		$valid = $this->form_registration_validate( $form, $current_post_id, $action );
		if ( ! $valid ) {
			return;
		}

		// Validate the Event Location data.
		$valid = $this->form_locblock_validate( $form, $current_post_id, $action );
		if ( ! $valid ) {
			return;
		}

	}



	/**
	 * Performs the action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function make( $form, $current_post_id, $action ) {

		// Bail if a filter has overridden the action.
		if ( false === $this->make_skip( $form, $current_post_id, $action ) ) {
			return;
		}

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Init array to save for this Action.
		$args = [
			'form_action' => $this->action_name,
			'id' => false,
		];

		// Populate Event, Registration and Custom Field data arrays.
		$event = $this->form_event_data( $form, $current_post_id, $action );
		$registration = $this->form_registration_data( $form, $current_post_id, $action );
		$custom_fields = $this->form_event_custom_data( $form, $current_post_id, $action );

		// First save the LocBlock with the data from the Form.
		$locblock_data = $this->form_locblock_data( $form, $current_post_id, $action );
		$args['location'] = $this->form_locblock_save( $locblock_data );

		// Save the Event with the data from the Form.
		$args['event'] = $this->form_event_save( $event, $custom_fields, $args['location'], $registration );

		// If we get an Event.
		if ( $args['event'] !== false ) {

			// Post-process Custom Fields now that we have an Event.
			$this->form_event_custom_post_process( $form, $current_post_id, $action, $args['event'] );

			// Maybe enable Registration.
			$args['profiles'] = $this->form_registration_save( $args['event'], $registration );

			// Save the Event ID for backwards compatibility.
			$args['id'] = $args['event']['id'];

		}

		// Save the results of this Action for later use.
		$this->make_action_save( $action, $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines additional Fields for the "Action" Tab.
	 *
	 * @since 0.5.4
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_action_append() {

		// Add Conditional Field.
		$code = 'event_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$conditional = $this->mapping_field_get( $code, $label );
		$conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$conditional['wrapper']['data-instruction-placement'] = 'field';
		$conditional['instructions'] = __( 'To add the Event only when a Form Field is populated (e.g. "Title") link this to the Form Field. To add the Event only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );
		$fields[] = $conditional;

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines the "Mapping" Tab.
	 *
	 * @since 0.5.4
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_add() {

		// Get Tab Header.
		$mapping_tab_header = $this->tab_mapping_header();

		// Build Contacts Accordion.
		$mapping_settings_accordion = $this->tab_mapping_accordion_settings_add();

		// Build Event Details Accordion.
		$mapping_event_accordion = $this->tab_mapping_accordion_event_add();

		// Build Custom Fields Accordion.
		$mapping_custom_accordion = $this->tab_mapping_accordion_custom_add();

		// Build Event Location Accordion.
		$mapping_location_accordion = $this->tab_mapping_accordion_location_add();

		// Build Event Registration Accordion.
		$mapping_registration_accordion = $this->tab_mapping_accordion_registration_add();

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$mapping_settings_accordion,
			$mapping_event_accordion,
			$mapping_custom_accordion,
			$mapping_location_accordion,
			$mapping_registration_accordion
		);

		// --<
		return $fields;

	}



	/**
	 * Defines the Fields in the "Contacts" Accordion.
	 *
	 * @since 0.5.4
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_settings_add() {

		// Init return.
		$fields = [];

		// "Event Settings" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_settings_open',
			'label' => __( 'Event Settings', 'civicrm-wp-profile-sync' ),
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

		// Add Settings Fields.
		foreach ( $this->settings_fields as $field ) {

			// Retrieve the choices.
			switch ( $field['name'] ) {
				case 'event_type_id':
					$choices = $this->civicrm->event_type->choices_get();
					break;
				case 'default_role_id':
					$choices = $this->civicrm->participant_role->choices_get();
					break;
				case 'participant_listing_id':
					$choices = [ 'disabled' => __( 'Disabled', 'civicrm-wp-profile-sync' ) ];
					$choices += $this->civicrm->event_field->options_get( 'participant_listing_id' );
					break;
				case 'campaign_id':
					// Skip Campaign Field if the CiviCampaign component is not active.
					$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
					if ( ! $campaign_active ) {
						continue 2;
					}
					$choices = [ 'none' => __( 'None', 'civicrm-wp-profile-sync' ) ];
					$choices = [ '' => __( 'Select', 'civicrm-wp-profile-sync' ) ];
					$choices += $this->civicrm->campaign->choices_get();
					break;
			}

			// Define Setting Field.
			$args = [
				'field_name' => $field['name'],
				'field_title' => $field['title'],
				'choices' => $choices,
			];

			// Add Settings Group.
			$fields[] = $this->form_setting_group_get( $args );

		}

		// Add Contact Reference Fields.
		foreach ( $this->fields_for_contacts as $field ) {

			// Bundle them into a container group.
			$contact_group_field = [
				'key' => $this->field_key . 'contact_group_' . $field['name'],
				'label' => $field['title'],
				'name' => $this->field_name . 'contact_group_' . $field['name'],
				'type' => 'group',
				/* translators: %s: The name of the Field */
				'instructions' => sprintf( __( 'Use one Field to identify the %s. Defaults to logged-in Contact.', 'civicrm-wp-profile-sync' ), $field['title'] ),
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
			$title = __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' );
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

		// "Event Settings" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_settings_close',
			'label' => __( 'Event Settings', 'civicrm-wp-profile-sync' ),
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
	 * Defines the Fields in the "Event Fields" Accordion.
	 *
	 * @since 0.5.4
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_event_add() {

		// Init return.
		$fields = [];

		// "Event Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_event_open',
			'label' => __( 'Event Fields', 'civicrm-wp-profile-sync' ),
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
		foreach ( $this->public_event_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->event_fields_to_ignore ) ) {
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Event Fields" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_event_close',
			'label' => __( 'Event Fields', 'civicrm-wp-profile-sync' ),
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
	 * @since 0.5.4
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
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'existing_location',
						'operator' => '==empty',
					],
				],
			],
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
		foreach ( $this->custom_fields as $key => $custom_group ) {

			// Skip if there are no Custom Fields.
			if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
				continue;
			}

			// Get the Event Type ID.
			$event_type_ids = [];
			if ( ! empty( $custom_group['extends_entity_column_value'] ) ) {
				$event_type_ids = $custom_group['extends_entity_column_value'];
			}

			// Init conditional logic.
			$conditional_logic = [];

			// Add Sub-types as OR conditionals if present.
			if ( ! empty( $event_type_ids ) ) {
				foreach ( $event_type_ids as $event_type_id ) {

					$event_type = [
						'field' => $this->field_key . 'event_types',
						'operator' => '==contains',
						'value' => $event_type_id,
					];

					$conditional_logic[] = [
						$event_type,
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

		// "Custom Fields" Accordion wrapper close.
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



	/**
	 * Defines the "Location" Accordion.
	 *
	 * @since 0.5.4
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_location_add() {

		// Init return.
		$fields = [];

		// "Event Location" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_location_open',
			'label' => __( 'Event Location', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// Define "Show Location" setting Field.
		$args = [
			'field_name' => 'is_show_location',
			'field_title' => __( 'Show Location', 'civicrm-wp-profile-sync' ),
			'extra' => __( 'Disable to make the Location available to Event Administrators only.', 'civicrm-wp-profile-sync' ),
			'choices' => [
				'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
				'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add "Show Location" Group.
		$fields[] = $this->form_setting_group_get( $args );

		// ---------------------------------------------------------------------

		// Define "Existing Location" setting Field.
		$args = [
			'field_name' => 'existing_location',
			'field_title' => __( 'Existing Location', 'civicrm-wp-profile-sync' ),
			'extra' => __( 'You cannot map a new Location if you choose an existing one.', 'civicrm-wp-profile-sync' ),
			'choices' => $this->civicrm->event_location->get_all(),
			'lazy_load' => 1,
		];

		// Add "Existing Location" Group.
		$fields[] = $this->form_setting_group_get( $args );

		// ---------------------------------------------------------------------

		// Bundle the "New Location" Fields into a container group.
		$location_group_field = [
			'key' => $this->field_key . 'location_group',
			'label' => __( 'New Location', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'location_group',
			'type' => 'group',
			'instructions' => '',
			'instruction_placement' => 'field',
			'required' => 0,
			'layout' => 'block',
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'value_existing_location',
						'operator' => '==empty',
					],
				],
			],
		];

		// Init sub Fields array.
		$sub_fields = [];

		// ---------------------------------------------------------------------

		// "Address" Accordion wrapper open.
		$sub_fields[] = [
			'key' => $this->field_key . 'address_fields_open',
			'label' => __( 'Address', 'civicrm-wp-profile-sync' ),
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

		// Add "Address Mapping" Fields.
		foreach ( $this->event_location_address_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->location_fields_to_ignore ) ) {
				$sub_fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// Build Conditional Field.
		$code = 'address_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$address_fields_conditional = $this->mapping_field_get( $code, $label );
		$address_fields_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$address_fields_conditional['instructions'] = __( 'To add the Address to the Location only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add to Sub-fields.
		$sub_fields[] = $address_fields_conditional;

		// "Address" Accordion wrapper close.
		$sub_fields[] = [
			'key' => $this->field_key . 'address_fields_close',
			'label' => __( 'Address', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// Maybe add Custom Fields Accordion to Sub-fields.
		if ( ! empty( $this->address_custom_fields ) ) {

			// "Custom Fields" Accordion wrapper open.
			$sub_fields[] = [
				'key' => $this->field_key . 'address_custom_open',
				'label' => __( 'Address Custom Fields', 'civicrm-wp-profile-sync' ),
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
			foreach ( $this->address_custom_fields as $key => $custom_group ) {

				// Skip if there are no Custom Fields.
				if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
					continue;
				}

				// Add "Map" Fields for the Custom Fields.
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$code = 'custom_' . $custom_field['id'];
					$sub_fields[] = $this->mapping_field_get( $code, $custom_field['label'] );
				}

			}

			// "Custom Fields" Accordion wrapper close.
			$sub_fields[] = [
				'key' => $this->field_key . 'address_custom_close',
				'label' => __( 'Address Custom Fields', 'civicrm-wp-profile-sync' ),
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

		}

		// ---------------------------------------------------------------------

		// "Emails" Accordion wrapper open.
		$sub_fields[] = [
			'key' => $this->field_key . 'email_fields_open',
			'label' => __( 'Email Addresses', 'civicrm-wp-profile-sync' ),
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

		// Define the Emails Repeater Field.
		$email_fields_repeater = [
			'key' => $this->field_key . 'email_fields_repeater',
			//'label' => __( 'Email Addresses', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'email_fields_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_email',
			'min' => 0,
			'max' => 2,
			'layout' => 'block',
			'button_label' => __( 'Add Email', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$email_fields_repeater_sub_fields = [];

		// Add "Email Mapping" Fields.
		foreach ( $this->event_location_email_fields as $field ) {
			$email_fields_repeater_sub_fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
		}

		// Build Conditional Field.
		$code = 'email_fields_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$email_fields_conditional = $this->mapping_field_get( $code, $label );
		$email_fields_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$email_fields_conditional['instructions'] = __( 'To add the Email to the Location only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Conditional Field to Repeater's Sub-Fields.
		$email_fields_repeater_sub_fields[] = $email_fields_conditional;

		// Add to Repeater.
		$email_fields_repeater['sub_fields'] = $email_fields_repeater_sub_fields;

		// Add Repeater to Sub-fields.
		$sub_fields[] = $email_fields_repeater;

		// "Emails" Accordion wrapper close.
		$sub_fields[] = [
			'key' => $this->field_key . 'email_fields_close',
			'label' => __( 'Emails', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Phone Numbers" Accordion wrapper open.
		$sub_fields[] = [
			'key' => $this->field_key . 'phone_fields_open',
			'label' => __( 'Phone Numbers', 'civicrm-wp-profile-sync' ),
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

		// Define the Phones Repeater Field.
		$phone_fields_repeater = [
			'key' => $this->field_key . 'phone_fields_repeater',
			//'label' => __( 'Phone Numbers', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'phone_fields_repeater',
			'type' => 'repeater',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'acfe_repeater_stylised_button' => 0,
			'collapsed' => $this->field_key . 'map_phone',
			'min' => 0,
			'max' => 2,
			'layout' => 'block',
			'button_label' => __( 'Add Phone', 'civicrm-wp-profile-sync' ),
			'sub_fields' => [],
		];

		// Init Sub-Fields.
		$phone_fields_repeater_sub_fields = [];

		// ---------------------------------------------------------------------

		// Define "Phone Type" setting Field.
		$args = [
			'field_name' => 'phone_type_id',
			'field_title' => __( 'Phone Type', 'civicrm-wp-profile-sync' ),
			'choices' => $this->phone_types,
		];

		// Add "Phone Type" Group.
		$phone_fields_repeater_sub_fields[] = $this->form_setting_group_get( $args );

		// ---------------------------------------------------------------------

		// Add "Phone Mapping" Fields.
		foreach ( $this->event_location_phone_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->phone_fields_to_ignore ) ) {
				$phone_fields_repeater_sub_fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// Assign code and label.
		$code = 'phone_fields_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );

		$phone_fields_conditional = $this->mapping_field_get( $code, $label );
		$phone_fields_conditional['placeholder'] = __( 'Always add', 'civicrm-wp-profile-sync' );
		$phone_fields_conditional['instructions'] = __( 'To add the Phone to the Location only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Field to Repeater's Sub-Fields.
		$phone_fields_repeater_sub_fields[] = $phone_fields_conditional;

		// Add to Repeater.
		$phone_fields_repeater['sub_fields'] = $phone_fields_repeater_sub_fields;

		// Add Repeater to Sub-fields.
		$sub_fields[] = $phone_fields_repeater;

		// "Phone Numbers" Accordion wrapper close.
		$sub_fields[] = [
			'key' => $this->field_key . 'phone_fields_close',
			'label' => __( 'Phone Numbers', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// Add the Sub-fields.
		$location_group_field['sub_fields'] = $sub_fields;

		// Add the Field.
		$fields[] = $location_group_field;

		// ---------------------------------------------------------------------

		// "Location" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_location_close',
			'label' => __( 'Location', 'civicrm-wp-profile-sync' ),
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
	 * Defines the "Registration" Accordion.
	 *
	 * @since 0.5.4
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_registration_add() {

		// Init return.
		$fields = [];

		// "Event Registration" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_registration_open',
			'label' => __( 'Event Registration', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// Define "Online Registration" setting Field.
		$args = [
			'field_name' => 'is_online_registration',
			'field_title' => __( 'Allow Online Registration', 'civicrm-wp-profile-sync' ),
			'choices' => [
				'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
				'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add "Online Registration" Group.
		$fields[] = $this->form_setting_group_get( $args );

		// ---------------------------------------------------------------------

		// "Online Registration Configuration" container group.
		$container_group_field = [
			'key' => $this->field_key . 'registration_settings',
			'label' => __( 'Online Registration Configuration', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'registration_settings',
			'type' => 'group',
			'instructions' => '',
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'required' => 0,
			'layout' => 'block',
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'value_is_online_registration',
						'operator' => '!=',
						'value' => '0',
					],
				],
			],
		];

		// Init Sub-fields.
		$container_group_field['sub_fields'] = [];

		// ---------------------------------------------------------------------

		// "Components" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_registration_components_open',
			'label' => __( 'Enable Components', 'civicrm-wp-profile-sync' ),
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

		// Define "Confirmation Screen" setting Field.
		$args = [
			'field_name' => 'is_confirm_enabled',
			'field_title' => __( 'Enable Confirmation Screen', 'civicrm-wp-profile-sync' ),
			'choices' => [
				'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
				'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add "Confirmation Screen" Group.
		$container_group_field['sub_fields'][] = $this->form_setting_group_get( $args );

		// Define "Confirmation Email" setting Field.
		$args = [
			'field_name' => 'is_email_confirm',
			'field_title' => __( 'Send Confirmation Email', 'civicrm-wp-profile-sync' ),
			'choices' => [
				'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
				'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add "Confirmation Email" Group.
		$container_group_field['sub_fields'][] = $this->form_setting_group_get( $args );

		// "Components" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_registration_components_close',
			'label' => __( 'Enable Components', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Settings" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_registration_settings_open',
			'label' => __( 'Settings', 'civicrm-wp-profile-sync' ),
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

		// Add "Event Registration Mapping" Fields.
		foreach ( $this->event_registration_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_fields_to_ignore ) ) {
				$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Settings" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_registration_settings_close',
			'label' => __( 'Settings', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Registration Screen" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_registration_screen_settings_open',
			'label' => __( 'Registration Screen', 'civicrm-wp-profile-sync' ),
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

		// Add "Event Registration Screen Mapping" Fields.
		foreach ( $this->event_registration_screen_fields as $field ) {
			$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
		}

		// Define "Include Profile Top" setting Field.
		$args = [
			'field_name' => 'custom_pre_id',
			'field_title' => __( 'Include Profile (top of page)', 'civicrm-wp-profile-sync' ),
			'choices' => $this->civicrm->event_registration->profiles_options_get(),
		];

		// Add "Include Profile Top" Group.
		$container_group_field['sub_fields'][] = $this->form_setting_group_get( $args );

		// Define "Include Profile Bottom" setting Field.
		$args = [
			'field_name' => 'custom_post_id',
			'field_title' => __( 'Include Profile (bottom of page)', 'civicrm-wp-profile-sync' ),
			'choices' => $this->civicrm->event_registration->profiles_options_get(),
		];

		// Add "Include Profile Bottom" Group.
		$container_group_field['sub_fields'][] = $this->form_setting_group_get( $args );

		// "Registration Screen" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_registration_screen_settings_close',
			'label' => __( 'Registration Screen', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Confirmation Screen" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_confirm_screen_settings_open',
			'label' => __( 'Confirmation Screen', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'value_is_confirm_enabled',
						'operator' => '!=',
						'value' => '0',
					],
				],
			],
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

		// Add "Confirmation Screen Mapping" Fields.
		foreach ( $this->event_confirm_screen_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->confirm_fields_to_ignore ) ) {
				$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Confirmation Screen" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_confirm_screen_settings_close',
			'label' => __( 'Confirmation Screen', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'value_is_confirm_enabled',
						'operator' => '!=',
						'value' => '0',
					],
				],
			],
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

		// ---------------------------------------------------------------------

		// "Thank You Screen" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_thankyou_screen_settings_open',
			'label' => __( 'Thank You Screen', 'civicrm-wp-profile-sync' ),
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

		// Add "Thank You Screen Mapping" Fields.
		foreach ( $this->event_thankyou_screen_fields as $field ) {
			$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
		}

		// "Thank You Screen" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_thankyou_screen_settings_close',
			'label' => __( 'Thank You Screen', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Confirmation Email" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_confirmation_email_settings_open',
			'label' => __( 'Confirmation Email', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'value_is_email_confirm',
						'operator' => '!=',
						'value' => '0',
					],
				],
			],
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

		// Add "Confirmation Email Mapping" Fields.
		foreach ( $this->event_confirmation_email_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_email_fields_to_ignore ) ) {
				$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// TODO

		// "Confirmation Email" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key' => $this->field_key . 'mapping_accordion_confirmation_email_settings_close',
			'label' => __( 'Confirmation Email', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'accordion',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'value_is_email_confirm',
						'operator' => '!=',
						'value' => '0',
					],
				],
			],
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

		// Add Group to Fields.
		$fields[] = $container_group_field;

		// ---------------------------------------------------------------------

		// "Registration" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_registration_close',
			'label' => __( 'Event Registration', 'civicrm-wp-profile-sync' ),
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
	 * Builds Event data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Event data.
	 */
	public function form_event_data( $form, $current_post_id, $action ) {

		// Build Fields array.
		$fields = [];
		foreach ( $this->public_event_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->event_fields_to_ignore ) ) {
				$fields[ $field['name'] ] = get_sub_field( $this->field_key . 'map_' . $field['name'] );
			}
		}

		// Populate data array with values of mapped Fields.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Get the Event Settings.
		foreach ( $this->settings_fields as $field ) {

			// Skip Campaign Field if the CiviCampaign component is not active.
			if ( $field['name'] === 'campaign_id' ) {
				$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
				if ( ! $campaign_active ) {
					continue;
				}
			}

			// Get Setting value.
			$setting_value = $this->form_setting_value_get( $field['name'], $form, $current_post_id, $action );

			// Participant Listing Field needs special handling.
			if ( $field['name'] === 'participant_listing_id' && $setting_value === 'disabled' ) {
				$setting_value = '';
			}

			// Assign to data.
			$data[ $field['name'] ] = $setting_value;

		}

		// Get the Event Contacts.
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

		// Get Event Conditional Reference.
		$data['event_conditional_ref'] = get_sub_field( $this->field_key . 'map_event_conditional' );
		$conditionals = [ $data['event_conditional_ref'] ];

		// Populate array with mapped Conditional Field values.
		$conditionals = acfe_form_map_vs_fields( $conditionals, $conditionals, $current_post_id, $form );

		// Save Event Conditional.
		$data['event_conditional'] = array_pop( $conditionals );

		// --<
		return $data;

	}



	/**
	 * Validates the Event data array from mapped Fields.
	 *
	 * @@since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Event can be saved, false otherwise.
	 */
	public function form_event_validate( $form, $current_post_id, $action ) {

		// Get the Event.
		$event = $this->form_event_data( $form, $current_post_id, $action );

		// Skip if the Event Conditional Reference Field has a value.
		if ( ! empty( $event['event_conditional_ref'] ) ) {
			// And the Event Conditional Field has no value.
			if ( empty( $event['event_conditional'] ) ) {
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
		// Reject the submission if there is no Creator Contact ID.
		if ( empty( $event['creator_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				// / * translators: %s The name of the Form Action * /
				__( 'A Contact ID is required to create an Event in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}
		*/

		// Reject the submission if the Event Type ID is missing.
		if ( empty( $event['event_type_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'An Event Type ID is required to create an Event in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Reject the submission if the Event Title is missing.
		if ( empty( $event['title'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'A title is required to create an Event in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Reject the submission if the Event Start Date is missing.
		if ( empty( $event['start_date'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'A start date is required to create an Event in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Valid.
		return true;

	}



	/**
	 * Saves the CiviCRM Event given data from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $event_data The array of Event data.
	 * @param array $custom_data The array of Custom Field data.
	 * @param array $locblock_data The array of LocBlock data.
	 * @param array $registration The array of Registration data.
	 * @return array|bool $event The Event data array, or false on failure.
	 */
	public function form_event_save( $event_data, $custom_data, $locblock_data, $registration ) {

		// Init return.
		$event = false;

		// Skip if the Event Conditional Reference Field has a value.
		if ( ! empty( $event_data['event_conditional_ref'] ) ) {
			// And the Event Conditional Field has no value.
			if ( empty( $event_data['event_conditional'] ) ) {
				return $event;
			}
		}

		// Bail if the Event can't be created.
		if ( empty( $event_data['event_type_id'] ) ) {
			return $event;
		}

		// Add Custom Field data if present.
		if ( ! empty( $custom_data ) ) {
			$event_data += $custom_data;
		}

		// Add LocBlock ID if present.
		if ( ! empty( $locblock_data['id'] ) ) {
			$event_data['loc_block_id'] = $locblock_data['id'];
		}

		// Add "Show Location" to Event data.
		$event_data['is_show_location'] = ! empty( $locblock_data['is_show_location'] ) ? 1 : 0;

		// Add "Allow Online Registration" to Event data.
		$event_data['is_online_registration'] = ! empty( $registration['is_online_registration'] ) ? 1 : 0;

		// Maybe add Registration data - but skip Profile Fields.
		if ( ! empty( $event_data['is_online_registration'] ) ) {
			$profile_fields = $this->civicrm->event_registration->profile_fields_get();
			foreach ( $registration as $key => $value ) {
				if ( ! array_key_exists( $key, $profile_fields ) ) {
					$event_data[ $key ] = $value;
				}
			}
		}

		// Unset Event Conditionals.
		if ( isset( $event_data['event_conditional'] ) ) {
			unset( $event_data['event_conditional'] );
		}
		if ( isset( $event_data['event_conditional_ref'] ) ) {
			unset( $event_data['event_conditional_ref'] );
		}

		// Strip out empty Fields.
		$event_data = $this->form_data_prepare( $event_data );

		/*
		 * Event "Is Public" defaults to "1" but is not present in the API return
		 * values when not explicitly set. This causes, for example, CEO to create
		 *  a "Private" Event in Event Organiser. We define it here when not set.
		 */
		if ( ! isset( $event_data['is_public'] ) ) {
			$event_data['is_public'] = 1;
		} else {
			$event_data['is_public'] = 0;
		}

		// Event "Is Confirm Enabled" defaults to "1" when not explicitly set.
		if ( ! isset( $event_data['is_confirm_enabled'] ) ) {
			$event_data['is_confirm_enabled'] = 0;
		} else {
			$event_data['is_confirm_enabled'] = 1;
		}

		// Create the Event.
		$result = $this->civicrm->event->create( $event_data );

		// Bail on failure.
		if ( $result === false ) {
			return $event;
		}

		// Get the full Event data.
		$event = $this->civicrm->event->get_by_id( $result['id'] );

		// --<
		return $event;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Custom Field data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_event_custom_data( $form, $current_post_id, $action ) {

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
			foreach ( $custom_group_field as $field ) {

				// Get mapped Fields.
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {

					// Add to mapped Fields array.
					$code = 'custom_' . $custom_field['id'];
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
						'field' => $field_ref,
						'selector' => $selector,
						'settings' => $settings,
					];
				}

				// Get the processed value (the Attachment ID).
				$attachment_id = (int) $data[ $code ];

				// Build an args array.
				$args = [
					'selector' => $selector,
					'post_id' => $current_post_id,
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
	 * Processes Custom Fields once an Event has been established.
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
	 * @@since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @param array $event The array of Event data.
	 * @return array $data The array of Custom Fields data.
	 */
	public function form_event_custom_post_process( $form, $current_post_id, $action, $event ) {

		// Bail if we have no post-process array.
		if ( empty( $this->file_fields_empty ) ) {
			return;
		}

		// Bail if we have no Event ID.
		if ( empty( $event['id'] ) ) {
			return;
		}

		// Get the array of Custom Field IDs.
		$custom_field_ids = array_keys( $this->file_fields_empty );
		array_walk( $custom_field_ids, function( &$item ) {
			$item = (int) trim( str_replace( 'custom_', '', $item ) );
		} );

		// Get the corresponding values.
		$values = $this->civicrm->custom_field->values_get_by_event_id( $event['id'], $custom_field_ids );
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
				'entity_id' => $event['id'],
				'custom_field_id' => $custom_field_id,
			];

			// Hand off to Attachment class.
			$this->civicrm->attachment->fields_clear( (int) $file_id, $data['settings'], $args );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds LocBlock data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $locblock_data The array of LocBlock data.
	 */
	public function form_locblock_data( $form, $current_post_id, $action ) {

		// Init return.
		$locblock_data = [];

		// Get "Show Location" value.
		$field_name = 'is_show_location';
		$setting_value = $this->form_setting_value_get( $field_name, $form, $current_post_id, $action );
		$locblock_data[ $field_name ] = $setting_value;

		// Get "Existing Location" value.
		$field_name = 'existing_location';
		$existing_location_id = $this->form_setting_value_get( $field_name, $form, $current_post_id, $action );

		// No need to go further if there's an existing Location.
		if ( ! empty( $existing_location_id ) ) {
			$locblock_data['id'] = (int) $existing_location_id;
			return $locblock_data;
		}

		// Get the data that comprises the LocBlock.
		$address_data = $this->form_locblock_address_data( $form, $current_post_id, $action );
		$email_data = $this->form_locblock_email_data( $form, $current_post_id, $action );
		$phone_data = $this->form_locblock_phone_data( $form, $current_post_id, $action );

		// Maybe add Address.
		if ( ! empty( $address_data ) ) {
			$address = $this->form_locblock_address_add( $address_data );
			if ( $address !== false ) {
				$locblock_data['address'] = $address;
			}
		}

		// Maybe add Email(s).
		if ( ! empty( $email_data ) ) {
			$emails = $this->form_locblock_email_add( $email_data );
			if ( $emails !== false ) {
				foreach ( $emails as $index => $email ) {
					if ( ! empty( $email ) ) {
						$locblock_data[ $index ] = $email;
					}
				}
			}
		}

		// Maybe add Phone(s).
		if ( ! empty( $phone_data ) ) {
			$phones = $this->form_locblock_phone_add( $phone_data );
			if ( $phones !== false ) {
				foreach ( $phones as $index => $phone ) {
					if ( ! empty( $phone ) ) {
						$locblock_data[ $index ] = $phone;
					}
				}
			}
		}

		// --<
		return $locblock_data;

	}



	/**
	 * Validates the Event LocBlock data array from mapped Fields.
	 *
	 * @@since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the LocBlock can be saved, false otherwise.
	 */
	public function form_locblock_validate( $form, $current_post_id, $action ) {

		// Get the Event LocBlock data.
		$data = $this->form_locblock_data( $form, $current_post_id, $action );

		// Skip if Event LocBlock ID is set.
		if ( ! empty( $data['id'] ) ) {
			return true;
		}

		// Check Address.
		if ( ! empty( $data['address'] ) ) {
			$valid = $this->form_locblock_address_validate( $data['address'], $action );
			if ( ! $valid ) {
				return false;
			}
		}

		// Check Email.
		if ( ! empty( $data['email'] ) ) {
			$valid = $this->form_locblock_email_validate( $data['email'], $action );
			if ( ! $valid ) {
				return false;
			}
		}

		// Check Email 2.
		if ( ! empty( $data['email_2'] ) ) {
			$valid = $this->form_locblock_email_validate( $data['email_2'], $action );
			if ( ! $valid ) {
				return false;
			}
		}

		// Check Phone.
		if ( ! empty( $data['phone'] ) ) {
			$valid = $this->form_locblock_phone_validate( $data['phone'], $action );
			if ( ! $valid ) {
				return false;
			}
		}

		// Check Phone 2.
		if ( ! empty( $data['phone_2'] ) ) {
			$valid = $this->form_locblock_phone_validate( $data['phone_2'], $action );
			if ( ! $valid ) {
				return false;
			}
		}

		// Valid.
		return true;

	}



	/**
	 * Saves the CiviCRM LocBlock given data from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $locblock_data The array of LocBlock data.
	 * @return array|bool $locblock The array of LocBlock data, or false on failure.
	 */
	public function form_locblock_save( $locblock_data ) {

		// Init return.
		$locblock = false;

		// Bail if there's no LocBlock data.
		if ( empty( $locblock_data ) ) {
			return $locblock;
		}

		// Strip out empty Fields.
		$locblock_data = $this->form_data_prepare( $locblock_data );

		// If there's an existing LocBlock ID.
		if ( ! empty( $locblock_data['id'] ) ) {

			// Get the LocBlock data.
			$existing = $this->civicrm->event_location->get_by_id( $locblock_data['id'] );

			// Return retrieved LocBlock on success.
			if ( ! empty( $existing['id'] ) ) {
				return $existing;
			}

			// Fallback in case of failure.
			return $locblock_data;

		}

		/*
		// Add in empty Fields when requested.
		if ( ! empty( $locblock_data['is_override'] ) ) {
			foreach ( $this->event_location_locblock_fields as $locblock_field ) {
				if ( ! array_key_exists( $locblock_field['name'], $locblock_data ) ) {
					$locblock_data[ $locblock_field['name'] ] = '';
				}
			}
		}
		*/

		// Create the LocBlock.
		$result = $this->civicrm->event_location->create( $locblock_data );
		if ( $result === false ) {
			return $locblock;
		}

		// Use the API return value.
		$locblock = $result;

		// --<
		return $locblock;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Address data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $address_data The array of Address data.
	 */
	public function form_locblock_address_data( $form, $current_post_id, $action ) {

		// Init return.
		$address_data = [];

		// Get the Location Group Field.
		$location_group_field = get_sub_field( $this->field_key . 'location_group' );

		// Build Fields array.
		$fields = [];
		foreach ( $this->event_location_address_fields as $field ) {
			$fields[ $field['name'] ] = $location_group_field[ $this->field_name . 'map_' . $field['name'] ];
		}

		// Maybe add Custom Fields.
		$custom_fields = $this->form_locblock_address_custom_data( $location_group_field );
		if ( ! empty( $custom_fields ) ) {
			$fields += $custom_fields;
		}

		// Populate array with mapped Field values.
		$address_data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Get Address Conditional Reference.
		$address_data['address_conditional_ref'] = get_sub_field( $this->field_key . 'map_address_conditional' );
		$conditionals = [ $address_data['address_conditional_ref'] ];

		// Populate array with mapped Conditional Field values.
		$conditionals = acfe_form_map_vs_fields( $conditionals, $conditionals, $current_post_id, $form );

		// Save Address Conditional.
		$address_data['address_conditional'] = array_pop( $conditionals );

		// --<
		return $address_data;

	}



	/**
	 * Validates the Event LocBlock Address data array from mapped Fields.
	 *
	 * @@since 0.5.4
	 *
	 * @param array $data The array of Event LocBlock Address data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the LocBlock Address can be saved, false otherwise.
	 */
	public function form_locblock_address_validate( $data, $action ) {

		// Skip if the Address Conditional Reference Field has a value.
		if ( ! empty( $data['address_conditional_ref'] ) ) {
			// And the Address Conditional Field has no value.
			if ( empty( $data['address_conditional'] ) ) {
				return true;
			}
		}

		// CiviCRM Event Organiser requires a "Street Address".
		if ( defined( 'CIVICRM_WP_EVENT_ORGANISER_VERSION' ) ) {
			if ( empty( $data['street_address'] ) ) {
				acfe_add_validation_error( '', sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A Street Address is required in "%s".', 'civicrm-wp-profile-sync' ),
					$action
				) );
				return false;
			}
		}

		// CiviCRM Addresses seem not to require data.
		return true;

	}



	/**
	 * Builds Address Custom Field data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Location Group Field.
	 * @return array $fields The array of Custom Fields data.
	 */
	public function form_locblock_address_custom_data( $field ) {

		// Init return.
		$fields = [];

		// Build data array.
		foreach ( $this->address_custom_fields as $key => $custom_group ) {

			// Get mapped Fields.
			foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
				$code = 'custom_' . $custom_field['id'];
				$fields[ $code ] = $field[ $this->field_name . 'map_' . $code ];
			}

		}

		// --<
		return $fields;

	}



	/**
	 * Adds the CiviCRM Address given data from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $address_data The array of Address data.
	 * @return array|bool $addresses The array of Address data, or false on failure.
	 */
	public function form_locblock_address_add( $address_data ) {

		// Init return.
		$address = false;

		// Bail if there's no Address data.
		if ( empty( $address_data ) ) {
			return $address;
		}

		// Strip out empty Fields.
		$address_data = $this->form_data_prepare( $address_data );

		// Only skip if the Address Conditional Reference Field has a value.
		if ( ! empty( $address_data['address_conditional_ref'] ) ) {
			// And the Address Conditional Field has a value.
			if ( empty( $address_data['address_conditional'] ) ) {
				return $address;
			}
		}

		/*
		// Add in empty Fields when requested.
		if ( ! empty( $address_data['is_override'] ) ) {
			foreach ( $this->event_location_address_fields as $address_field ) {
				if ( ! array_key_exists( $address_field['name'], $address_data ) ) {
					$address_data[ $address_field['name'] ] = '';
				}
			}
		}
		*/

		// Remove Conditional Fields.
		unset( $address_data['address_conditional'] );
		unset( $address_data['address_conditional_ref'] );

		// --<
		return $address_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Email data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $email_data The array of Email data.
	 */
	public function form_locblock_email_data( $form, $current_post_id, $action ) {

		// Init return.
		$email_data = [
			'email' => [],
			'email_2' => [],
		];

		// Get the Location Group Field.
		$location_group_field = get_sub_field( $this->field_key . 'location_group' );

		// Get the Email Repeater Field.
		$email_repeater = $location_group_field[ $this->field_name . 'email_fields_repeater' ];

		// Skip it if it's empty.
		if ( empty( $email_repeater ) ) {
			return $email_data;
		}

		// Init key.
		$key = 'email';

		// Loop through the Action Fields.
		foreach ( $email_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Get mapped Fields.
			foreach ( $this->event_location_email_fields as $email_field ) {
				$fields[ $email_field['name'] ] = $field[ $this->field_name . 'map_' . $email_field['name'] ];
			}

			// Get Email Conditional.
			$fields['email_conditional'] = $field[ $this->field_name . 'map_email_fields_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Email Conditional Reference.
			$fields['email_conditional_ref'] = $field[ $this->field_name . 'map_email_fields_conditional' ];

			// Add the data.
			$email_data[ $key ] = $fields;

			// The second item needs a suffix.
			$key = 'email_2';

		}

		// --<
		return $email_data;

	}



	/**
	 * Validates the Event LocBlock Email data array from mapped Fields.
	 *
	 * @@since 0.5.4
	 *
	 * @param array $data The array of Event LocBlock Email data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the LocBlock Address can be saved, false otherwise.
	 */
	public function form_locblock_email_validate( $data, $action ) {

		// Skip it if there's no data.
		if ( empty( $data ) ) {
			return true;
		}

		// Skip if the Conditional Reference Field has a value.
		if ( ! empty( $data['email_conditional_ref'] ) ) {
			// And the Conditional Field has no value.
			if ( empty( $data['email_conditional'] ) ) {
				return true;
			}
		}

		// Reject if there's an invalid Email.
		if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'An invalid Email was found in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Valid.
		return true;

	}



	/**
	 * Adds the CiviCRM Email(s) given data from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $email_data The array of Email data.
	 * @return array|bool $emails The array of Emails, or false on failure.
	 */
	public function form_locblock_email_add( $email_data ) {

		// Init return.
		$emails = false;

		// Bail if there's no Email data.
		if ( empty( $email_data ) ) {
			return $emails;
		}

		// Handle each nested Action in turn.
		foreach ( $email_data as $key => $email ) {

			// Strip out empty Fields.
			$email = $this->form_data_prepare( $email );

			// Only skip if the Email Conditional Reference Field has a value.
			if ( ! empty( $email['email_conditional_ref'] ) ) {
				// And the Email Conditional Field has a value.
				if ( empty( $email['email_conditional'] ) ) {
					continue;
				}
			}

			// Skip if there is no Email Address to save.
			if ( empty( $email['email'] ) ) {
				continue;
			}

			// Remove Conditional Fields.
			unset( $email['email_conditional'] );
			unset( $email['email_conditional_ref'] );

			// Add the Email data.
			$emails[ $key ] = $email;

		}

		// --<
		return $emails;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Phone data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $phone_data The array of Phone data.
	 */
	public function form_locblock_phone_data( $form, $current_post_id, $action ) {

		// Init return.
		$phone_data = [
			'phone' => [],
			'phone_2' => [],
		];

		// Get the Location Group Field.
		$location_group_field = get_sub_field( $this->field_key . 'location_group' );

		// Get the Phone Repeater Field.
		$phone_repeater = $location_group_field[ $this->field_name . 'phone_fields_repeater' ];

		// Skip it if it's empty.
		if ( empty( $phone_repeater ) ) {
			return $phone_data;
		}

		// Init key.
		$key = 'phone';

		// Loop through the Action Fields.
		foreach ( $phone_repeater as $field ) {

			// Init Fields.
			$fields = [];

			// Get "Phone Type" value.
			$field_name = 'phone_type_id';
			$setting_value = $this->form_setting_value_get( $field_name, $form, $current_post_id, $action, $field );
			$fields[ $field_name ] = $setting_value;

			// Get mapped Fields.
			foreach ( $this->event_location_phone_fields as $phone_field ) {
			if ( ! array_key_exists( $phone_field['name'], $this->phone_fields_to_ignore ) ) {
					$fields[ $phone_field['name'] ] = $field[ $this->field_name . 'map_' . $phone_field['name'] ];
				}
			}

			// Get Phone Conditional.
			$fields['phone_conditional'] = $field[ $this->field_name . 'map_phone_fields_conditional' ];

			// Populate array with mapped Field values.
			$fields = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

			// Save Phone Conditional Reference.
			$fields['phone_conditional_ref'] = $field[ $this->field_name . 'map_phone_fields_conditional' ];

			// Add the data.
			$phone_data[ $key ] = $fields;

			// Update key for second item.
			$key = 'phone_2';

		}

		// --<
		return $phone_data;

	}



	/**
	 * Validates the Event LocBlock Phone data array from mapped Fields.
	 *
	 * @@since 0.5.4
	 *
	 * @param array $data The array of Event LocBlock Phone data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the LocBlock Address can be saved, false otherwise.
	 */
	public function form_locblock_phone_validate( $data, $action ) {

		// Skip it if there's no data.
		if ( empty( $data ) ) {
			return true;
		}

		// Skip if the Conditional Reference Field has a value.
		if ( ! empty( $data['phone_conditional_ref'] ) ) {
			// And the Conditional Field has no value.
			if ( empty( $data['phone_conditional'] ) ) {
				return true;
			}
		}

		// Reject if there's no Phone Number.
		if ( empty( $data['phone'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'A valid Phone Number is required in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Valid.
		return true;

	}



	/**
	 * Adds the CiviCRM Phone(s) given data from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $phone_data The array of Phone data.
	 * @return array|bool $phones The array of Phones, or false on failure.
	 */
	public function form_locblock_phone_add( $phone_data ) {

		// Init return.
		$phones = false;

		// Bail if there's no Phone data.
		if ( empty( $phone_data ) ) {
			return $phones;
		}

		// Handle each nested Action in turn.
		foreach ( $phone_data as $key => $phone ) {

			// Strip out empty Fields.
			$phone = $this->form_data_prepare( $phone );

			// Only skip if the Phone Conditional Reference Field has a value.
			if ( ! empty( $phone['phone_conditional_ref'] ) ) {
				// And the Phone Conditional Field has a value.
				if ( empty( $phone['phone_conditional'] ) ) {
					continue;
				}
			}

			// Skip if there is no Phone Address to save.
			if ( empty( $phone['phone'] ) ) {
				continue;
			}

			// Remove Conditional Fields.
			unset( $phone['phone_conditional'] );
			unset( $phone['phone_conditional_ref'] );

			// Add the Phone data.
			$phones[ $key ] = $phone;

		}

		// --<
		return $phones;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Registration data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Registration data.
	 */
	public function form_registration_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Get "Online Registration" value.
		$field_name = 'is_online_registration';
		$setting_value = $this->form_setting_value_get( $field_name, $form, $current_post_id, $action );
		$data[ $field_name ] = $setting_value;

		// No need to go further if "Online Registration" is disabled.
		if ( empty( $setting_value ) ) {
			$data[ $field_name ] = 0;
			return $data;
		}

		// Get the data that is always present for Registration.
		$settings_data = $this->form_registration_settings_data( $form, $current_post_id, $action );
		$register_data = $this->form_registration_screen_data( $form, $current_post_id, $action );
		$confirm_data = $this->form_registration_confirmation_screen_data( $form, $current_post_id, $action );
		$thankyou_data = $this->form_registration_thankyou_screen_data( $form, $current_post_id, $action );
		$email_data = $this->form_registration_confirmation_email_data( $form, $current_post_id, $action );

		// Maybe add Settings.
		if ( ! empty( $settings_data ) ) {
			foreach ( $settings_data as $key => $value ) {
				$data[ $key ] = $value;
			}
		}

		// Maybe add Registration Screen data.
		if ( ! empty( $register_data ) ) {
			foreach ( $register_data as $key => $value ) {
				$data[ $key ] = $value;
			}
		}

		// Maybe add Confirmation Screen data.
		if ( ! empty( $confirm_data ) ) {
			foreach ( $confirm_data as $key => $value ) {
				$data[ $key ] = $value;
			}
		}

		// Maybe add Thank You Screen data.
		if ( ! empty( $thankyou_data ) ) {
			foreach ( $thankyou_data as $key => $value ) {
				$data[ $key ] = $value;
			}
		}

		// Maybe add Confirmation Email data.
		if ( ! empty( $email_data ) ) {
			foreach ( $email_data as $key => $value ) {
				$data[ $key ] = $value;
			}
		}

		// --<
		return $data;

	}



	/**
	 * Validates the Event Registration data array from mapped Fields.
	 *
	 * @@since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Event Registration can be enabled, false otherwise.
	 */
	public function form_registration_validate( $form, $current_post_id, $action ) {

		// Get the Event Registration data.
		$data = $this->form_registration_data( $form, $current_post_id, $action );

		// Skip if Event Registration is not enabled.
		if ( empty( $data['is_online_registration'] ) ) {
			return true;
		}

		// Reject the submission if no Profile has been selected.
		if ( empty( $data['custom_pre_id'] ) && empty( $data['custom_post_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'A Profile is required to enable Online Registration in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Check Confirmation Email.
		$valid = $this->form_registration_confirmation_email_validate( $data, $action );
		if ( ! $valid ) {
			return false;
		}

		// Valid.
		return true;

	}



	/**
	 * Saves the Event Registration Profile given data from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $event The array of CiviCRM Event data.
	 * @param array $data The array of Registration data.
	 * @return array|bool $profiles The array of Profile data, or false on failure.
	 */
	public function form_registration_save( $event, $data ) {

		// Init formatted return.
		$profiles = [
			'top' => false,
			'bottom' => false,
		];

		// Bail if there's no Event ID.
		if ( empty( $event['id'] ) ) {
			return $profiles;
		}

		// Bail if Event Registration is not enabled.
		if ( empty( $event['is_online_registration'] ) ) {
			return $profiles;
		}

		// Bail if there's no Registration data.
		if ( empty( $data ) ) {
			return $profiles;
		}

		// Strip out empty Fields.
		$data = $this->form_data_prepare( $data );

		// Bail if there's no Profile data.
		if ( empty( $data['custom_pre_id'] ) && empty( $data['custom_post_id'] ) ) {
			return $profiles;
		}

		// Maybe create the Top Profile.
		if ( ! empty( $data['custom_pre_id'] ) ) {
			$result = $this->civicrm->event_registration->profile_create( $event, $data['custom_pre_id'] );
			if ( $result !== false ) {
				$profiles['top'] = $result;
			}
		}

		// Maybe create the Bottom Profile.
		if ( ! empty( $data['custom_post_id'] ) ) {
			$result = $this->civicrm->event_registration->profile_create( $event, $data['custom_post_id'] );
			if ( $result !== false ) {
				$profiles['bottom'] = $result;
			}
		}

		// --<
		return $profiles;

	}



	/**
	 * Builds Event Registration Settings data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Settings data.
	 */
	public function form_registration_settings_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = get_sub_field( $this->field_key . 'registration_settings' );

		// Build Fields array.
		$fields = [];
		foreach ( $this->event_registration_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_fields_to_ignore ) ) {
				$fields[ $field['name'] ] = $group_field[ $this->field_name . 'map_' . $field['name'] ];
			}
		}

		// Populate array with mapped Field values.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// --<
		return $data;

	}



	/**
	 * Builds Event Registration Screen data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Registration Screen data.
	 */
	public function form_registration_screen_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = get_sub_field( $this->field_key . 'registration_settings' );

		// Build Fields array.
		$fields = [];
		foreach ( $this->event_registration_screen_fields as $field ) {
			$fields[ $field['name'] ] = $group_field[ $this->field_name . 'map_' . $field['name'] ];
		}

		// Populate array with mapped Field values.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Get "Include Profile Top" value.
		$field_name = 'custom_pre_id';
		$setting_value = $this->form_setting_value_get( $field_name, $form, $current_post_id, $action, $group_field );
		$data[ $field_name ] = $setting_value;

		// Get "Include Profile Bottom" value.
		$field_name = 'custom_post_id';
		$setting_value = $this->form_setting_value_get( $field_name, $form, $current_post_id, $action, $group_field );
		$data[ $field_name ] = $setting_value;

		// Set some defaults.
		if ( empty( $data['registration_link_text'] ) ) {
			$data['registration_link_text'] = __( 'Register Now', 'civicrm-wp-profile-sync' );
		}

		// --<
		return $data;

	}



	/**
	 * Builds Event Registration Confirmation Screen data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Confirmation Screen data.
	 */
	public function form_registration_confirmation_screen_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = get_sub_field( $this->field_key . 'registration_settings' );

		// Build Fields array.
		$fields = [];
		foreach ( $this->event_confirm_screen_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->confirm_fields_to_ignore ) ) {
				$fields[ $field['name'] ] = $group_field[ $this->field_name . 'map_' . $field['name'] ];
			}
		}

		// Populate array with mapped Field values.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Add the "Confirmation Screen Enabled" Field.
		$setting_value = $this->form_setting_value_get( 'is_confirm_enabled', $form, $current_post_id, $action, $group_field );
		$data['is_confirm_enabled'] = $setting_value;

		// --<
		return $data;

	}



	/**
	 * Builds Event Registration Thank You Screen data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Thank You Screen data.
	 */
	public function form_registration_thankyou_screen_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = get_sub_field( $this->field_key . 'registration_settings' );

		// Build Fields array.
		$fields = [];
		foreach ( $this->event_thankyou_screen_fields as $field ) {
			$fields[ $field['name'] ] = $group_field[ $this->field_name . 'map_' . $field['name'] ];
		}

		// Populate array with mapped Field values.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// --<
		return $data;

	}



	/**
	 * Builds Event Registration Confirmation Email data array from mapped Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Confirmation Email data.
	 */
	public function form_registration_confirmation_email_data( $form, $current_post_id, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = get_sub_field( $this->field_key . 'registration_settings' );

		// Build Fields array.
		$fields = [];
		foreach ( $this->event_confirmation_email_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_email_fields_to_ignore ) ) {
				$fields[ $field['name'] ] = $group_field[ $this->field_name . 'map_' . $field['name'] ];
			}
		}

		// Populate array with mapped Field values.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Add the "Confirmation Email Enabled" Field.
		$setting_value = $this->form_setting_value_get( 'is_email_confirm', $form, $current_post_id, $action, $group_field );
		$data['is_email_confirm'] = $setting_value;

		// --<
		return $data;

	}



	/**
	 * Validates the Event Registration Confirmation Email data array from mapped Fields.
	 *
	 * @@since 0.5.4
	 *
	 * @param array $data The array of Event Registration Confirmation Email data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Confirmation Email can be sent, false otherwise.
	 */
	public function form_registration_confirmation_email_validate( $data, $action ) {

		// Skip if Confirmation Email is disabled.
		if ( empty( $data['is_email_confirm'] ) ) {
			return true;
		}

		// Reject the submission if no "Confirm From Name" has been selected.
		if ( empty( $data['confirm_from_name'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'A "From Name" is required to send a Confirmation Email in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Reject the submission if no "Confirm From Email" has been selected.
		if ( empty( $data['confirm_from_email'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'A "From Email" is required to send a Confirmation Email in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Valid.
		return true;

	}



	// -------------------------------------------------------------------------



	/**
	 * Finds the linked Contact ID when it has been mapped.
	 *
	 * @since 0.5.4
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



} // Class ends.



