<?php
/**
 * "CiviCRM Email" ACFE Form Action Class.
 *
 * Handles the "CiviCRM Email" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync "CiviCRM Email" ACFE Form Action Class.
 *
 * A class that handles the "CiviCRM Email" ACFE Form Action.
 *
 * @since 0.7.0
 */
class CWPS_ACF_ACFE_Form_Action_Email extends CWPS_ACF_ACFE_Form_Action_Base {

	/**
	 * Form Action Name.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $name = 'cwps_email';

	/**
	 * Mapped Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $mapped_email_fields;

	/**
	 * Email Contact Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $contact_fields;

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
		$this->civicrm    = $this->acf_loader->civicrm;

		// Label this Form Action.
		$this->title = __( 'CiviCRM Email action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->name_placeholder = __( 'CiviCRM Email', 'civicrm-wp-profile-sync' );

		// Declare the mapped Email Fields with translatable titles.
		$this->mapped_email_fields = [
			'subject'                      => __( 'Subject', 'civicrm-wp-profile-sync' ),
			'from_name'                    => __( 'From Name', 'civicrm-wp-profile-sync' ),
			'from_email'                   => __( 'From Email', 'civicrm-wp-profile-sync' ),
			'alternative_receiver_address' => __( 'Alternative Receiver Address', 'civicrm-wp-profile-sync' ),
			'cc'                           => __( 'Carbon Copy', 'civicrm-wp-profile-sync' ),
			'bcc'                          => __( 'Blind Carbon Copy', 'civicrm-wp-profile-sync' ),
			// 'extra_data'                   => __( 'Extra Data', 'civicrm-wp-profile-sync' ),
			// 'from_email_option'            => __( 'From Email Option', 'civicrm-wp-profile-sync' ),
		];

		// Declare the Email Contact Fields with translatable titles.
		$this->contact_fields = [
			'contact_id' => __( 'Recipient CiviCRM Contact', 'civicrm-wp-profile-sync' ),
		];

		// Add Contact Action Reference Field to ACF Model.
		foreach ( $this->contact_fields as $name => $title ) {
			$this->js_model_contact_reference_field_add( 'ref_' . $name );
		}

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$this->js_model_case_reference_field_add( 'case_id' );
		}

		// Declare core Fields for this Form Action.
		$this->item = [
			'action'   => $this->name,
			'name'     => '',
			'email'    => [
				'from_name'                    => '',
				'from_email'                   => '',
				'alternative_receiver_address' => '',
				'cc'                           => '',
				'bcc'                          => '',
				'subject'                      => '',
			],
			'settings' => [
				'contact_id'       => '',
				'template_id'      => '',
				'case_id'          => '',
				'smarty_disable'   => false,
				'activity_create'  => false,
				'activity_details' => false,
			],
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

		// Email data.
		$keys = array_keys( $this->mapped_email_fields );
		foreach ( $keys as $key ) {
			if ( acf_maybe_get( $action['email'], $key ) ) {
				$action[ $key ] = $action['email'][ $key ];
			}
		}

		// Load the Action variables.
		$action['template_id']      = $action['settings']['template_id'];
		$action['smarty_disable']   = $action['settings']['smarty_disable'];
		$action['activity_create']  = $action['settings']['activity_create'];
		$action['activity_details'] = $action['settings']['activity_details'];

		// Load the Email Contacts.
		foreach ( $this->contact_fields as $name => $title ) {
			$action[ 'contact_group_' . $name ] = $action['settings'][ $name ];
		}

		// Load the Case if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$action['case_id'] = $action['settings']['case_id'];
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

		// Save Email data.
		$keys = array_keys( $this->mapped_email_fields );
		foreach ( $keys as $key ) {
			if ( acf_maybe_get( $action, $key ) ) {
				$save['email'][ $key ] = $action[ $key ];
			}
		}

		// Save Action variables.
		$save['settings']['template_id']      = $action['template_id'];
		$save['settings']['smarty_disable']   = $action['smarty_disable'];
		$save['settings']['activity_create']  = $action['activity_create'];
		$save['settings']['activity_details'] = $action['activity_details'];

		// Save Email Contacts.
		foreach ( $this->contact_fields as $name => $title ) {
			$save['settings'][ $name ] = $action[ 'contact_group_' . $name ];
		}

		// Save Case ID if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$save['settings']['case_id'] = $action['case_id'];
		}

		// --<
		return $save;

	}

	/**
	 * Performs validation when the Form the Action is attached to is submitted.
	 *
	 * @since 0.7.0
	 *
	 * @param array $form The array of Form data.
	 * @param array $action The array of Action data.
	 */
	public function validate_action( $form, $action ) {

		// Skip if the Contact Conditional Reference Field has a value.
		$this->form_conditional_populate( [ 'action' => &$action ] );
		if ( ! $this->form_conditional_check( [ 'action' => $action ] ) ) {
			return;
		}

		// Validate the Email data.
		$valid = $this->form_email_validate( $form, $action );
		if ( ! $valid ) {
			return;
		}

		// TODO: Check other Email Entities.

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

		// Populate Conditional Reference and value.
		$this->form_conditional_populate( [ 'action' => &$action ] );

		// Init result array to save for this Action.
		$result = $this->item;

		// Always add action name.
		$result['form_action'] = $this->name;
		$result['name']        = $action['name'];

		// Populate Email data array.
		$email = $this->form_email_data( $form, $action );

		// Check Conditional.
		if ( $this->form_conditional_check( $action ) ) {

			// Send the Email with the data from the Form.
			$result['email'] = $this->form_email_save( $email );

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

		// Define Template Field.
		$template_field = [
			'key'               => $this->field_key . 'template_id',
			'label'             => __( 'Message Template', 'civicrm-wp-profile-sync' ),
			'name'              => 'template_id',
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
			'choices'           => $this->civicrm->email->template_options_get(),
		];

		// Define "Disable Smarty" Field.
		$smarty_field = [
			'key'               => $this->field_key . 'smarty_disable',
			'label'             => __( 'Disable Smarty', 'civicrm-wp-profile-sync' ),
			'name'              => 'smarty_disable',
			'type'              => 'true_false',
			'instructions'      => __( 'Disable Smarty. Normal CiviMail tokens are still supported. By default Smarty is enabled if configured by CIVICRM_MAIL_SMARTY.', 'civicrm-wp-profile-sync' ),
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

		// Define "Create Activity" Field.
		$activity_create_field = [
			'key'               => $this->field_key . 'activity_create',
			'label'             => __( 'Create Activity', 'civicrm-wp-profile-sync' ),
			'name'              => 'activity_create',
			'type'              => 'true_false',
			'instructions'      => __( 'Usually an Email Activity is created when an Email is sent.', 'civicrm-wp-profile-sync' ),
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
			'default_value'     => 1,
			'ui'                => 1,
			'ui_on_text'        => '',
			'ui_off_text'       => '',
		];

		// Define "Activity Details" Field.
		$activity_details_field = [
			'key'               => $this->field_key . 'activity_details',
			'label'             => __( 'Activity Details', 'civicrm-wp-profile-sync' ),
			'name'              => 'activity_details',
			'type'              => 'select',
			'instructions'      => '',
			'required'          => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions'  => '',
			'default_value'     => 'html,text',
			'placeholder'       => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'return_format'     => 'value',
			'choices'           => [
				'html,text' => __( 'HTML and Text versions of the body', 'civicrm-wp-profile-sync' ),
				'tplName'   => __( 'Just the name of the message template', 'civicrm-wp-profile-sync' ),
				'html'      => __( 'Just the HTML version of the body', 'civicrm-wp-profile-sync' ),
				'text'      => __( 'Just the text version of the body', 'civicrm-wp-profile-sync' ),
			],
			'conditional_logic' => [
				[
					[
						'field'    => $this->field_key . 'activity_create',
						'operator' => '==',
						'value'    => 1,
					],
				],
			],
		];

		// Init Fields.
		$fields = [
			$template_field,
			$smarty_field,
			$activity_create_field,
			$activity_details_field,
		];

		// Add Case Field if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {

			$fields[] = [
				'key'              => $this->field_key . 'case_id',
				'label'            => __( 'Case', 'civicrm-wp-profile-sync' ),
				'name'             => 'case_id',
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

		}

		// Configure Conditional Field.
		$args = [
			'placeholder'  => __( 'Always send', 'civicrm-wp-profile-sync' ),
			'instructions' => __( 'To send the Email only when a Form Field is populated (e.g. "First Name") link this to the Form Field. To send the Email only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' ),
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
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_contacts_add() {

		// Init return.
		$fields = [];

		// "Recipient Contact Reference" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_contacts_open',
			'label'             => __( 'Recipient Contact Reference', 'civicrm-wp-profile-sync' ),
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
		foreach ( $this->contact_fields as $name => $title ) {

			// Bundle them into a container group.
			$contact_group_field = [
				'key'          => $this->field_key . 'contact_group_' . $name,
				'label'        => $title,
				'name'         => 'contact_group_' . $name,
				'type'         => 'group',
				/* translators: %s: The name of the Field */
				'instructions' => sprintf( __( 'Use one Field to identify the %s.', 'civicrm-wp-profile-sync' ), $title ),
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
				'key'               => $this->field_key . 'ref_' . $name,
				'label'             => __( 'CiviCRM Contact Action', 'civicrm-wp-profile-sync' ),
				'name'              => 'ref_' . $name,
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
							'field'    => $this->field_key . $name,
							'operator' => '==empty',
						],
						[
							'field'    => $this->field_key . 'cid_' . $name,
							'operator' => '==empty',
						],
					],
				],
			];

			// Define Contact ID Field.
			$cid_field = [
				'key'               => $this->field_key . 'cid_' . $name,
				'label'             => __( 'CiviCRM Contact ID', 'civicrm-wp-profile-sync' ),
				'name'              => 'cid_' . $name,
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
							'field'    => $this->field_key . 'ref_' . $name,
							'operator' => '==empty',
						],
						[
							'field'    => $this->field_key . $name,
							'operator' => '==empty',
						],
					],
				],
			];

			// Add Contact ID Field.
			$contact_group_field['sub_fields'][] = $cid_field;

			// Define Custom Contact Reference Field.
			$title                              = sprintf( __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' ), $title );
			$mapping_field                      = $this->mapping_field_get( $name, $title );
			$mapping_field['instructions']      = __( 'Define a custom Contact Reference.', 'civicrm-wp-profile-sync' );
			$mapping_field['conditional_logic'] = [
				[
					[
						'field'    => $this->field_key . 'ref_' . $name,
						'operator' => '==empty',
					],
					[
						'field'    => $this->field_key . 'cid_' . $name,
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
			'key'               => $this->field_key . 'mapping_accordion_contacts_close',
			'label'             => __( 'Recipient Contact Reference', 'civicrm-wp-profile-sync' ),
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
	 * Defines the Fields in the "Email Fields" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_email_add() {

		// Init return.
		$fields = [];

		// "Email Fields" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_email_open',
			'label'             => __( 'Email Fields', 'civicrm-wp-profile-sync' ),
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
		foreach ( $this->mapped_email_fields as $name => $title ) {
			$fields[] = $this->mapping_field_get( $name, $title );
		}

		// "Email Fields" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_email_close',
			'label'             => __( 'Email Fields', 'civicrm-wp-profile-sync' ),
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
	 * Builds Email data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $form The array of Form data.
	 * @param array $action The array of Action data.
	 * @return array $data The array of Email data.
	 */
	private function form_email_data( $form, $action ) {

		// Init data array.
		$data = [];

		// Set ACFE "context". We want to apply tags.
		acfe_add_context( [ 'context' => 'display' ] );

		// Mapped Email data.
		$keys = array_keys( $this->mapped_email_fields );
		foreach ( $keys as $key ) {
			acfe_apply_tags( $action['email'][ $key ] );
			$data['email'][ $key ] = $action['email'][ $key ];
		}

		// Get the Action variables.
		$data['settings']['template_id']      = $action['settings']['template_id'];
		$data['settings']['smarty_disable']   = $action['settings']['smarty_disable'];
		$data['settings']['activity_create']  = $action['settings']['activity_create'];
		$data['settings']['activity_details'] = $action['settings']['activity_details'];

		// Get the Email Contacts.
		foreach ( $this->contact_fields as $name => $title ) {

			// Get Group Field.
			$contact_group_field = $action['settings'][ $name ];

			// Check Action Reference Field.
			$contact_id = false;
			if ( ! empty( $contact_group_field[ 'ref_' . $name ] ) ) {
				$action_name = $contact_group_field[ 'ref_' . $name ];
				$contact_id  = $this->form_contact_id_get_mapped( $action_name );
			}

			// Check Contact ID Field.
			if ( false === $contact_id ) {
				if ( ! empty( $contact_group_field[ 'cid_' . $name ] ) ) {
					$contact_id = $contact_group_field[ 'cid_' . $name ];
				}
			}

			// Check mapped Field.
			if ( false === $contact_id ) {
				if ( ! empty( $contact_group_field[ $name ] ) ) {
					acfe_apply_tags( $contact_group_field[ $name ] );
					if ( ! empty( $contact_group_field[ $name ] ) && is_numeric( $contact_group_field[ $name ] ) ) {
						$contact_id = $contact_group_field[ $name ];
					}
				}
			}

			// Assign to data.
			if ( ! empty( $contact_id ) && is_numeric( $contact_id ) ) {
				$data['settings'][ $name ] = (int) $contact_id;
			}

		}

		// Add the Case if the CiviCase component is active.
		$case_active = $this->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			$action_name                 = $action['settings']['case_id'];
			$data['settings']['case_id'] = $this->form_case_id_get_mapped( $action_name );
		}

		// --<
		return $data;

	}

	/**
	 * Validates the Email data array from mapped Fields.
	 *
	 * @since 0.7.0.2
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Email can be saved, false otherwise.
	 */
	private function form_email_validate( $form, $action ) {

		// Get the Email.
		$email = $this->form_email_data( $form, $action );

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
		if ( empty( $email['contact_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				// / * translators: %s The name of the Form Action * /
				__( 'A Contact ID is required to send an Email in "%s".', 'civicrm-wp-profile-sync' ),
				$action['name']
			) );
			return false;
		}
		*/

		// Reject the submission if the Template ID is missing.
		if ( empty( $email['template_id'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A Template ID is required to send an Email in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Valid.
		return true;

	}

	/**
	 * Sends the CiviCRM Email given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $email_data The array of Email data.
	 * @return array|bool $email The Email data array, or false on failure.
	 */
	private function form_email_save( $email_data ) {

		// Init return.
		$email = false;

		// Combine data into single array for CiviCRM API.
		$data = array_merge( $email_data['email'], $email_data['settings'] );

		// Strip out empty Fields.
		$data = $this->form_data_prepare( $data );

		// Sanity checks.
		if ( empty( $data['contact_id'] ) || empty( $data['template_id'] ) ) {
			return $email;
		}

		// Send the Email.
		$result = $this->civicrm->email->email_send( $data );
		if ( false === $result ) {
			return $email;
		}

		// Build return array.
		$email = array_merge( $email_data, [ 'result' => $result ] );

		// --<
		return $email;

	}

}
