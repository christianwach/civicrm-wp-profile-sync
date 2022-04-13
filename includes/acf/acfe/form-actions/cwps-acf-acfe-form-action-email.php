<?php
/**
 * "CiviCRM Email" ACFE Form Action Class.
 *
 * Handles the "CiviCRM Email" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync "CiviCRM Email" ACFE Form Action Class.
 *
 * A class that handles the "CiviCRM Email" ACFE Form Action.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Email extends CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Base {

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
	public $action_name = 'cwps_email';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_key The prefix for the Field Key.
	 */
	public $field_key = 'field_cwps_civicrm_email_action_';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_name The prefix for the Field Name.
	 */
	public $field_name = 'cwps_civicrm_email_action_';



	/**
	 * Constructor.
	 *
	 * @since 0.5
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
		$this->action_label = __( 'CiviCRM Email action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->alias_placeholder = __( 'CiviCRM Email', 'civicrm-wp-profile-sync' );

		// Init parent.
		parent::__construct();

	}



	/**
	 * Configure this object.
	 *
	 * @since 0.5
	 */
	public function configure() {

		// Declare the mapped Email Fields with translatable titles.
		$this->mapped_email_fields = [
			'subject' => __( 'Subject', 'civicrm-wp-profile-sync' ),
			'from_name' => __( 'From Name', 'civicrm-wp-profile-sync' ),
			'from_email' => __( 'From Email', 'civicrm-wp-profile-sync' ),
			'alternative_receiver_address' => __( 'Alternative Receiver Address', 'civicrm-wp-profile-sync' ),
			'cc' => __( 'Carbon Copy', 'civicrm-wp-profile-sync' ),
			'bcc' => __( 'Blind Carbon Copy', 'civicrm-wp-profile-sync' ),
			//'extra_data' => __( 'Extra Data', 'civicrm-wp-profile-sync' ),
			//'from_email_option' => __( 'From Email Option', 'civicrm-wp-profile-sync' ),
		];

		// Populate mapping Fields.
		foreach ( $this->mapped_email_fields as $name => $title ) {
			$this->mapping_field_filters_add( $name );
		}

		// Declare the Email Contact Fields with translatable titles.
		$this->contact_fields = [
			'contact_id' => __( 'Recipient CiviCRM Contact', 'civicrm-wp-profile-sync' ),
		];

		// Handle Contact Fields.
		foreach ( $this->contact_fields as $name => $title ) {

			// Populate mapping Fields.
			$this->mapping_field_filters_add( $name );

			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( $this->field_name . 'ref_' . $name );

			// Pre-load with "Generic" values.
			//$filter = 'acf/prepare_field/name=' . $this->field_name . 'map_' . $name;
			//add_filter( $filter, [ $this, 'prepare_choices' ], 5 );

		}

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$this->js_model_case_reference_field_add( $this->field_name . 'email_case_id' );
		}

		// CiviCRM Email Conditional Field.
		$this->mapping_field_filters_add( 'civicrm_email_conditional' );

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
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function validation( $form, $current_post_id, $action ) {

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Validate the Email data.
		$valid = $this->form_email_validate( $form, $current_post_id, $action );
		if ( ! $valid ) {
			return;
		}

		// TODO: Check other Email Entities.

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

		// Populate Email data array.
		$email = $this->form_email_data( $form, $current_post_id, $action );

		// Send the Email with the data from the Form.
		$args['email'] = $this->form_email_save( $email );

		// If we get an Email.
		if ( $args['email'] !== false ) {

			// Maybe save the Email ID if there is one.
			if ( ! empty( $args['email']['id'] ) ) {
				$args['id'] = $args['email']['id'];
			}

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

		// Define Template Field.
		$template_field = [
			'key' => $this->field_key . 'template',
			'label' => __( 'Message Template', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'template',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $this->civicrm->email->template_options_get(),
		];

		// Define "Disable Smarty" Field.
		$smarty_field = [
			'key' => $this->field_key . 'disable_smarty',
			'label' => __( 'Disable Smarty', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'disable_smarty',
			'type' => 'true_false',
			'instructions' => __( 'Disable Smarty. Normal CiviMail tokens are still supported. By default Smarty is enabled if configured by CIVICRM_MAIL_SMARTY.', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'message' => '',
			'default_value' => 0,
			'ui' => 1,
			'ui_on_text' => '',
			'ui_off_text' => '',
		];

		// Define "Create Activity" Field.
		$create_activity_field = [
			'key' => $this->field_key . 'create_activity',
			'label' => __( 'Create Activity', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'create_activity',
			'type' => 'true_false',
			'instructions' => __( 'Usually an Email Activity is created when an Email is sent.', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'message' => '',
			'default_value' => 1,
			'ui' => 1,
			'ui_on_text' => '',
			'ui_off_text' => '',
		];

		// Define "Activity Details" Field.
		$activity_details_field = [
			'key' => $this->field_key . 'activity_details',
			'label' => __( 'Activity Details', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'activity_details',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => 'html,text',
			'placeholder' => '',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => [
				'html,text' => __( 'HTML and Text versions of the body', 'civicrm-wp-profile-sync' ),
				'tplName' => __( 'Just the name of the message template', 'civicrm-wp-profile-sync' ),
				'html' => __( 'Just the HTML version of the body', 'civicrm-wp-profile-sync' ),
				'text' => __( 'Just the text version of the body', 'civicrm-wp-profile-sync' ),
			],
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'create_activity',
						'operator' => '==',
						'value' => 1,
					],
				],
			],
		];

		// Init Fields.
		$fields = [
			$template_field,
			$smarty_field,
			$create_activity_field,
			$activity_details_field,
		];

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {

			$fields[] = [
				'key' => $this->field_key . 'email_case_id',
				'label' => __( 'Case', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'email_case_id',
				'type' => 'cwps_acfe_case_action_ref',
				'instructions' => __( 'Select a Case Action in this Form.', 'civicrm-wp-profile-sync' ),
				'required' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-instruction-placement' => 'field',
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
		$code = 'civicrm_email_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$conditional = $this->mapping_field_get( $code, $label );
		$conditional['placeholder'] = __( 'Always send', 'civicrm-wp-profile-sync' );
		$conditional['wrapper']['data-instruction-placement'] = 'field';
		$conditional['instructions'] = __( 'To send the Email only when a Form Field is populated (e.g. "First Name") link this to the Form Field. To send the Email only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );
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

		// Build Email Details Accordion.
		$mapping_email_accordion = $this->tab_mapping_accordion_email_add();

		// Combine Sub-Fields.
		$fields = array_merge(
			$mapping_tab_header,
			$mapping_contacts_accordion,
			$mapping_email_accordion
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

		// "Recipient Contact Reference" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contacts_open',
			'label' => __( 'Recipient Contact Reference', 'civicrm-wp-profile-sync' ),
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
		foreach ( $this->contact_fields as $name => $title ) {

			// Bundle them into a container group.
			$contact_group_field = [
				'key' => $this->field_key . 'contact_group_' . $name,
				'label' => $title,
				'name' => $this->field_name . 'contact_group_' . $name,
				'type' => 'group',
				/* translators: %s: The name of the Field */
				'instructions' => sprintf( __( 'Use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $title ),
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
				'key' => $this->field_key . 'ref_' . $name,
				'label' => __( 'CiviCRM Contact Action', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'ref_' . $name,
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
							'field' => $this->field_key . 'map_' . $name,
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'cid_' . $name,
							'operator' => '==empty',
						],
					],
				],
			];

			// Define Contact ID Field.
			$cid_field = [
				'key' => $this->field_key . 'cid_' . $name,
				'label' => __( 'CiviCRM Contact ID', 'civicrm-wp-profile-sync' ),
				'name' => $this->field_name . 'cid_' . $name,
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
							'field' => $this->field_key . 'ref_' . $name,
							'operator' => '==empty',
						],
						[
							'field' => $this->field_key . 'map_' . $name,
							'operator' => '==empty',
						],
					],
				],
			];

			// Add Contact ID Field.
			$contact_group_field['sub_fields'][] = $cid_field;

			// Define Custom Contact Reference Field.
			$title = sprintf( __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' ), $title );
			$mapping_field = $this->mapping_field_get( $name, $title );
			$mapping_field['instructions'] = __( 'Define a custom Contact Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field' => $this->field_key . 'ref_' . $name,
						'operator' => '==empty',
					],
					[
						'field' => $this->field_key . 'cid_' . $name,
						'operator' => '==empty',
					],
				],
			];

			// Add Custom Contact Reference Field.
			$contact_group_field['sub_fields'][] = $mapping_field;

			// Add Contact Reference Group.
			$fields[] = $contact_group_field;

		}

		// "Recipient Contact Reference" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_contacts_close',
			'label' => __( 'Recipient Contact Reference', 'civicrm-wp-profile-sync' ),
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
	 * Defines the Fields in the "Email Fields" Accordion.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_accordion_email_add() {

		// Init return.
		$fields = [];

		// "Email Fields" Accordion wrapper open.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_email_open',
			'label' => __( 'Email Fields', 'civicrm-wp-profile-sync' ),
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
		foreach ( $this->mapped_email_fields as $name => $title ) {
			$fields[] = $this->mapping_field_get( $name, $title );
		}

		// "Email Fields" Accordion wrapper close.
		$fields[] = [
			'key' => $this->field_key . 'mapping_accordion_email_close',
			'label' => __( 'Email Fields', 'civicrm-wp-profile-sync' ),
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
	 * Builds Email data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Email data.
	 */
	public function form_email_data( $form, $current_post_id, $action ) {

		// Build Fields array.
		$fields = [];
		foreach ( $this->mapped_email_fields as $name => $title ) {
			$fields[ $name ] = get_sub_field( $this->field_key . 'map_' . $name );
		}

		// Populate data array with values of mapped Fields.
		$data = acfe_form_map_vs_fields( $fields, $fields, $current_post_id, $form );

		// Get the Action variables.
		$data['template_id'] = get_sub_field( $this->field_key . 'template' );
		$data['disable_smarty'] = get_sub_field( $this->field_key . 'disable_smarty' );
		$data['create_activity'] = get_sub_field( $this->field_key . 'create_activity' );
		$data['activity_details'] = get_sub_field( $this->field_key . 'activity_details' );

		// Get the Email Contacts.
		foreach ( $this->contact_fields as $name => $title ) {

			// Get Group Field.
			$contact_group_field = get_sub_field( $this->field_key . 'contact_group_' . $name );

			// Check Action Reference Field.
			$contact_id = false;
			if ( ! empty( $contact_group_field[ $this->field_name . 'ref_' . $name ] ) ) {
				$action_name = $contact_group_field[ $this->field_name . 'ref_' . $name ];
				$contact_id = $this->form_contact_id_get_mapped( $action_name );
			}

			// Check Contact ID Field.
			if ( $contact_id === false ) {
				if ( ! empty( $contact_group_field[ $this->field_name . 'cid_' . $name ] ) ) {
					$contact_id = $contact_group_field[ $this->field_name . 'cid_' . $name ];
				}
			}

			// Check mapped Field.
			if ( $contact_id === false ) {
				if ( ! empty( $contact_group_field[ $this->field_name . 'map_' . $name ] ) ) {
					$reference = [ $name => $contact_group_field[ $this->field_name . 'map_' . $name ] ];
					$reference = acfe_form_map_vs_fields( $reference, $reference, $current_post_id, $form );
					if ( ! empty( $reference[ $name ] ) && is_numeric( $reference[ $name ] ) ) {
						$contact_id = $reference[ $name ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $contact_id ) && is_numeric( $contact_id ) ) {
				$data[ $name ] = $contact_id;
			}

		}

		// Add the Case if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$action_name = get_sub_field( $this->field_key . 'activity_case_id' );
			$data['case_id'] = $this->form_case_id_get_mapped( $action_name );
		}

		// Get Email Conditional Reference.
		$data['civicrm_email_conditional_ref'] = get_sub_field( $this->field_key . 'map_civicrm_email_conditional' );
		$conditionals = [ $data['civicrm_email_conditional_ref'] ];

		// Populate array with mapped Conditional Field values.
		$conditionals = acfe_form_map_vs_fields( $conditionals, $conditionals, $current_post_id, $form );

		// Save Email Conditional.
		$data['civicrm_email_conditional'] = array_pop( $conditionals );

		// --<
		return $data;

	}



	/**
	 * Validates the Email data array from mapped Fields.
	 *
	 * @since 0.5.2
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Email can be saved, false otherwise.
	 */
	public function form_email_validate( $form, $current_post_id, $action ) {

		// Get the Email.
		$email = $this->form_email_data( $form, $current_post_id, $action );

		// Skip if the Email Conditional Reference Field has a value.
		if ( ! empty( $email['civicrm_email_conditional_ref'] ) ) {
			// And the Email Conditional Field has no value.
			if ( empty( $email['civicrm_email_conditional'] ) ) {
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
		// Reject the submission if there is no Contact ID.
		if ( empty( $email['contact_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				// / * translators: %s The name of the Form Action * /
				__( 'A Contact ID is required to send an Email in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}
		*/

		// Reject the submission if the Template ID is missing.
		if ( empty( $email['template_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				/* translators: %s The name of the Form Action */
				__( 'A Template ID is required to send an Email in "%s".', 'civicrm-wp-profile-sync' ),
				$action
			) );
			return false;
		}

		// Valid.
		return true;

	}



	/**
	 * Sends the CiviCRM Email given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $email_data The array of Email data.
	 * @return array|bool $email The Email data array, or false on failure.
	 */
	public function form_email_save( $email_data ) {

		// Init return.
		$email = false;

		// Skip if the Email Conditional Reference Field has a value.
		if ( ! empty( $email_data['civicrm_email_conditional_ref'] ) ) {
			// And the Email Conditional Field has no value.
			if ( empty( $email_data['civicrm_email_conditional'] ) ) {
				return $email;
			}
		}

		// Add Custom Field data if present.
		if ( ! empty( $custom_data ) ) {
			$email_data += $custom_data;
		}

		// Unset Email Conditionals.
		if ( isset( $email_data['civicrm_email_conditional'] ) ) {
			unset( $email_data['civicrm_email_conditional'] );
		}
		if ( isset( $email_data['civicrm_email_conditional_ref'] ) ) {
			unset( $email_data['civicrm_email_conditional_ref'] );
		}

		// Strip out empty Fields.
		$email_data = $this->form_data_prepare( $email_data );

		// Sanity checks.
		if ( empty( $email_data['contact_id'] ) || empty( $email_data['template_id'] ) ) {
			return $email;
		}

		// Send the Email.
		$result = $this->civicrm->email->email_send( $email_data );

		// Bail on failure.
		if ( $result === false ) {
			return $email;
		}

		// --<
		return $result;

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

		// We need an Action Name.
		if ( empty( $action_name ) ) {
			return $case_id;
		}

		// Get the Case data for that Action.
		$related_case = acfe_form_get_action( $action_name, 'case' );
		if ( empty( $related_case['id'] ) ) {
			return $case_id;
		}

		// Assign return.
		$case_id = (int) $related_case['id'];

		// --<
		return $case_id;

	}



} // Class ends.



