<?php
/**
 * ACFE Form Action Base Class.
 *
 * Holds methods common to CiviCRM Profile Sync ACFE Form Action classes.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync "Base" ACFE Form Action Class.
 *
 * A class that is extended by CiviCRM Profile Sync ACFE Form Action classes.
 *
 * @since 0.7.0
 */
class CWPS_ACF_ACFE_Form_Action_Base extends acfe_module_form_action {

	/**
	 * Plugin object.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * ACF Extended object.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var object
	 */
	public $acfe;

	/**
	 * ACFE Form object.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var object
	 */
	public $form;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Form Action Name.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $name = '';

	/**
	 * Form Action Name Placeholder.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $name_placeholder = '';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $field_key = 'field_';

	/**
	 * Conditional Field "code".
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $conditional_code = 'conditional';

	/**
	 * Files to examine for possible deletion.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var integer
	 */
	public $file_fields_empty;

	/**
	 * Context for parsing ACFE tags when displaying data in Forms.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $context_display = [
		'context'  => 'display',
		'unformat' => 'wysiwyg',
	];

	/**
	 * Context for parsing ACFE tags when reading data from Form submissions.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $context_save = [
		'context' => 'save',
		'format'  => false,
		'return'  => 'raw',
	];

	/**
	 * Constructor.
	 *
	 * @since 0.7.0
	 */
	public function __construct() {

		// Declare Conditional Field for this Form Action.
		$this->item[ $this->conditional_code ] = '';

		// Init parent.
		parent::__construct();

	}

	/**
	 * Maybe skip the Action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.7.0
	 *
	 * @param array $form The array of Form data.
	 * @param array $action The array of Action data.
	 * @return bool $prepare The net result of the set of filters.
	 */
	protected function make_skip( $form, $action ) {

		// Get the Form name.
		$form_name = acf_maybe_get( $form, 'name' );

		// Assume we're good to go.
		$prepare = true;

		/**
		 * Allow others to prevent Form Action.
		 *
		 * Returning false for any of these filters will skip the Action.
		 *
		 * @since 0.7.0
		 *
		 * @param bool  $prepare True by default so that the Form Action goes ahead.
		 * @param array $form The array of Form data.
		 * @param array $action The array of Action data.
		 */
		$filter  = 'acfe/form/v3/skip/' . $this->name;
		$prepare = apply_filters( $filter, $prepare, $form, $action );
		$prepare = apply_filters( $filter . '/form=' . $form_name, $prepare, $form, $action );
		if ( ! empty( $action['name'] ) ) {
			$prepare = apply_filters( $filter . '/action=' . $action['name'], $prepare, $form, $action );
		}

		// --<
		return $prepare;

	}

	/**
	 * Gets the result of an Action name.
	 *
	 * @since 0.7.0
	 *
	 * @param string $action_name The name of the Action.
	 * @param string $key The key of the desired data in the Action result.
	 * @return mixed $data The result of the Action name.
	 */
	public function get_action_output( $action_name = null, $key = null ) {

		// Init return.
		$data = null;

		// Safely get all Action results.
		$actions = acf_get_form_data( 'acfe/form/actions' );
		$actions = acf_get_array( $actions );

		// When no Action name is passed, return all data.
		if ( is_null( $action_name ) ) {
			return $actions;
		}

		// Retrieve the desired data.
		if ( array_key_exists( $action_name, $actions ) ) {
			$data = $actions[ $action_name ];
			if ( ! empty( $key ) && array_key_exists( $key, $actions[ $action_name ] ) ) {
				$data = $actions[ $action_name ][ $key ];
			}
		}

		// --<
		return $data;

	}

	/**
	 * Saves the result of the Action for use by subsequent Actions.
	 *
	 * @since 0.7.0
	 *
	 * @param array $action The name of the Action.
	 * @param array $data The result of the Action.
	 */
	public function load_action_save( $action, $data ) {

		// Update array of Action results.
		$this->set_action_output( $data, $action );

	}

	/**
	 * Saves the result of the Action for use by subsequent Actions.
	 *
	 * @since 0.7.0
	 *
	 * @param array $action The name of the Action.
	 * @param array $data The result of the Action.
	 */
	public function make_action_save( $action, $data ) {

		// Update array of Action results.
		$this->set_action_output( $data, $action );

	}

	/**
	 * Defines the action by adding a layout.
	 *
	 * @since 0.7.0
	 *
	 * @param array $layout The existing layout.
	 * @return array $layout The modified layout.
	 */
	public function register_layout( $layout ) {

		// Build Action Tab.
		$action_tab_fields = $this->tab_action_add();

		// Build Mapping Tab.
		$mapping_tab_fields = $this->tab_mapping_add();

		// Build additional Tabs.
		$relationship_tab_fields = $this->tab_relationship_add();

		// Combine Sub-Fields.
		$layout = array_merge(
			$action_tab_fields,
			$mapping_tab_fields,
			$relationship_tab_fields
		);

		/**
		 * Let the classes that extend this one modify the Sub-Fields.
		 *
		 * @since 0.7.0
		 *
		 * @param array $sub_fields The array of Sub-Fields.
		 */
		$layout = apply_filters( 'cwps/acfe/form/v3/actions/sub_fields', $layout );

		return $layout;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Defines the "Action" Tab.
	 *
	 * These Fields are required to configure the Form Action.
	 *
	 * The ACFE "Action name" Field has a pre-defined format, e.g. it must be
	 * assigned the "acfe_slug" Field Type and have "name" as its "name" and
	 * "field_name" as its "key". Only its "placeholder" attribute needs to be
	 * configured.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	protected function tab_action_add() {

		// Init Fields array.
		$fields = [];

		// "Action" Tab wrapper.
		$fields[] = [
			'key'               => $this->field_key . 'tab_action',
			'label'             => __( 'Action', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'tab',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width'              => '',
				'class'              => '',
				'id'                 => '',
				'data-no-preference' => true,
			],
			'acfe_permissions'  => '',
			'placement'         => 'top',
			'endpoint'          => 0,
		];

		// "Action name" Field.
		$fields[] = [
			'key'               => 'field_name',
			'label'             => __( 'Action name', 'civicrm-wp-profile-sync' ),
			'name'              => 'name',
			'type'              => 'acfe_slug',
			'instructions'      => __( '(Required) Name this action so it can be referenced.', 'civicrm-wp-profile-sync' ),
			'required'          => 1,
			'conditional_logic' => 0,
			'wrapper'           => [
				'width'                      => '',
				'class'                      => '',
				'id'                         => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions'  => '',
			'default_value'     => '',
			'placeholder'       => $this->name_placeholder,
			'prepend'           => '',
			'append'            => '',
			'maxlength'         => '',
		];

		// Add any further Fields.
		$action_extras = $this->tab_action_append();
		if ( ! empty( $action_extras ) ) {
			$fields = array_merge( $fields, $action_extras );
		}

		// --<
		return $fields;

	}

	/**
	 * Defines additional Fields for the "Action" Tab.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	protected function tab_action_append() {
		$fields = [];
		return $fields;
	}

	/**
	 * Defines the "Mapping" Tab.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	protected function tab_mapping_add() {
		$fields = [];
		return $fields;
	}

	/**
	 * Defines the "Mapping" Tab Header.
	 *
	 * @since 0.7.0
	 *
	 * @param string $label The label for this section.
	 * @return array $fields The array of Fields for this section.
	 */
	protected function tab_mapping_header( $label = '' ) {

		// Set a default label.
		if ( empty( $label ) ) {
			$label = __( 'Mapping', 'civicrm-wp-profile-sync' );
		}

		// "Mapping" Tab wrapper.
		$mapping_tab = [
			[
				'key'               => $this->field_key . 'tab_load',
				'label'             => $label,
				'name'              => '',
				'type'              => 'tab',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => [
					'width'              => '',
					'class'              => '',
					'id'                 => '',
					'data-no-preference' => true,
				],
				'acfe_permissions'  => '',
				'placement'         => 'top',
				'endpoint'          => 0,
			],
		];

		// Combine Fields.
		$fields = array_merge(
			$mapping_tab
		);

		// --<
		return $fields;

	}

	/**
	 * Defines the "Relationship" Tab.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	protected function tab_relationship_add() {
		$fields = [];
		return $fields;
	}

	/**
	 * Defines the "Relationship" Tab Header.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	protected function tab_relationship_header() {

		// "Relationship" Tab wrapper.
		$relationship_tab = [
			[
				'key'               => $this->field_key . 'tab_relationship',
				'label'             => __( 'Relationships', 'civicrm-wp-profile-sync' ),
				'name'              => '',
				'type'              => 'tab',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => [
					[
						[
							'field'    => $this->field_key . 'submitting_contact',
							'operator' => '==',
							'value'    => '0',
						],
					],
				],
				'wrapper'           => [
					'width'              => '',
					'class'              => '',
					'id'                 => '',
					'data-no-preference' => true,
				],
				'acfe_permissions'  => '',
				'placement'         => 'top',
				'endpoint'          => 0,
			],
		];

		// Combine Fields.
		$fields = array_merge(
			$relationship_tab
		);

		// --<
		return $fields;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the array that defines a "Map Field" for the "Mapping" Tab.
	 *
	 * @since 0.7.0
	 *
	 * @param string $code The unique code for the Field.
	 * @param string $label The label for the Field.
	 * @param array  $conditional_logic The conditional logic for the Field.
	 * @return array $field The array of Field data.
	 */
	protected function mapping_field_get( $code, $label, $conditional_logic = [] ) {

		// Build the Field array.
		$field = [
			'key'                => $this->field_key . $code,
			'label'              => $label,
			'name'               => $code,
			'type'               => 'select',
			'instructions'       => '',
			'required'           => 0,
			'wrapper'            => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'choices'            => [],
			'default_value'      => [],
			'allow_null'         => 1,
			'multiple'           => 0,
			'ui'                 => 1,
			'return_format'      => 'value',
			'placeholder'        => __( 'Default', 'civicrm-wp-profile-sync' ),
			'ajax'               => 1,
			'search_placeholder' => __( 'Select a field or enter a custom value or template tag.', 'civicrm-wp-profile-sync' ),
			'allow_custom'       => 1,
			'conditional_logic'  => 0,
			'ajax_action'        => 'acfe/form/map_field_ajax',
			'nonce'              => wp_create_nonce( $this->field_key . $code ),
		];

		// Default conditional logic.
		$field['conditional_logic'] = [];

		// Maybe replace with custom conditional logic.
		if ( ! empty( $conditional_logic ) ) {
			$field['conditional_logic'] = $conditional_logic;
		}

		// --<
		return $field;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Adds "Contact Reference Field" actions to the Javascript ACF Model.
	 *
	 * @since 0.7.0
	 *
	 * @param string $field_name The name the Field.
	 */
	public function js_model_contact_reference_field_add( $field_name ) {

		// Add to Javascript ACF Model.
		add_filter(
			'cwps/acf/acfe/form_actions/reference_fields/contact',
			function( $actions ) use ( $field_name ) {
				$actions[ 'new_field/name=' . $field_name ] = 'newContactActionRefField';
				return $actions;
			}
		);

	}

	/**
	 * Adds "Case Reference Field" actions to the Javascript ACF Model.
	 *
	 * @since 0.7.0
	 *
	 * @param string $field_name The name the Field.
	 */
	public function js_model_case_reference_field_add( $field_name ) {

		// Add to Javascript ACF Model.
		add_filter(
			'cwps/acf/acfe/form_actions/reference_fields/case',
			function( $actions ) use ( $field_name ) {
				$actions[ 'new_field/name=' . $field_name ] = 'newCaseActionRefField';
				return $actions;
			}
		);

	}

	/**
	 * Adds "Participant Reference Field" actions to the Javascript ACF Model.
	 *
	 * @since 0.7.0
	 *
	 * @param string $field_name The name the Field.
	 */
	public function js_model_participant_reference_field_add( $field_name ) {

		// Add to Javascript ACF Model.
		add_filter(
			'cwps/acf/acfe/form_actions/reference_fields/participant',
			function( $actions ) use ( $field_name ) {
				$actions[ 'new_field/name=' . $field_name ] = 'newParticipantActionRefField';
				return $actions;
			}
		);

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Loads the data for mapped Fields in an Entity Repeater Field.
	 *
	 * This covers mapping in Fields such as a Contact's "Email", "Website", "Address"
	 * repeaters. Should also work for others.
	 *
	 * @since 0.7.0
	 *
	 * @param array $form The array of Form data.
	 * @param array $args {
	 *     The array of arguments. All arguments are required but may be empty.
	 *
	 *     @type array $fields    The array of Entity Fields that can be mapped.
	 *     @type array $sub_field The array of Field data in the Form Action.
	 *     @type array $entity    The array of CiviCRM Entity data.
	 *     @type array $prefix    The prefix for the CiviCRM Entity.
	 * }
	 * @return array $form The populated array of Form data.
	 */
	protected function form_entity_fields_load( $form, $args ) {

		// Populate the Entity Fields.
		foreach ( $args['fields'] as $field ) {

			// Skip if the Field is not mapped.
			$field_key = $this->is_field_key_tag( $args['sub_field'][ $args['prefix'] . $field['name'] ] );
			if ( ! acf_is_field_key( $field_key ) ) {
				continue;
			}

			// Skip if the Entity does not have the key - e.g. County ID when "null".
			if ( ! array_key_exists( $field['name'], $args['entity'] ) ) {
				continue;
			}

			// Allow (string) "0" as valid data.
			if ( empty( $args['entity'][ $field['name'] ] ) && '0' !== $args['entity'][ $field['name'] ] ) {
				continue;
			}

			// Apply value to map.
			$form['map'][ $field_key ]['value'] = $args['entity'][ $field['name'] ];

		}

		// --<
		return $form;

	}

	/**
	 * Loads the data for mapped Custom Fields in an Entity Repeater Field.
	 *
	 * This covers mapping Form Fields for Custom Fields in Entities such as a Contact's
	 * "Address" repeater. Should also work for others.
	 *
	 * @since 0.7.0
	 *
	 * @param array $form The array of Form data.
	 * @param array $args {
	 *     The array of arguments. All arguments are required but may be empty.
	 *
	 *     @type array $custom_fields The array of Custom Fields in the Custom Group.
	 *     @type array $sub_field     The array of Field data in the Form Action.
	 *     @type array $entity        The array of CiviCRM Entity data.
	 * }
	 * @return array $form The populated array of Form data.
	 */
	protected function form_entity_custom_fields_load( $form, $args ) {

		// Populate the Entity Custom Fields.
		foreach ( $args['custom_fields'] as $custom_field ) {

			// Skip if the Field is not mapped.
			$code      = 'custom_' . $custom_field['id'];
			$field_key = $this->is_field_key_tag( $args['sub_field'][ $code ] );
			if ( ! acf_is_field_key( $field_key ) ) {
				continue;
			}

			// Skip if the Entity does not have the key for some reason.
			if ( ! array_key_exists( $code, $args['entity'] ) ) {
				continue;
			}

			// Allow (string) "0" as valid data.
			if ( empty( $args['entity'][ $code ] ) && '0' !== $args['entity'][ $code ] ) {
				continue;
			}

			// Apply Custom Field value.
			$form['map'][ $field_key ]['value'] = $args['entity'][ $code ];

			// Convert any "File" Custom Fields to WordPress Attachment IDs.
			if ( 'File' === $custom_field['data_type'] && ! empty( $args['entity'][ $code ] ) ) {
				$civicrm_file = $this->civicrm->attachment->file_get_by_id( $args['entity'][ $code ] );
				if ( ! empty( $civicrm_file ) ) {
					$attachment_id = $this->civicrm->attachment->query_by_file( $civicrm_file->uri, 'civicrm' );
					if ( ! empty( $attachment_id ) ) {
						$form['map'][ $field_key ]['value'] = $attachment_id;
					}
				}
			}

		}

		// --<
		return $form;

	}

	/**
	 * Gets the data for mapped Custom Fields in an Entity Repeater Field.
	 *
	 * This covers mapping Custom Fields from Form Fields in Entities such as a Contact
	 * or a Contact's "Address" repeater.
	 *
	 * @since 0.7.0
	 *
	 * @param array $field The currently processed Field.
	 * @param array $args {
	 *     The array of arguments. All arguments are required but may be empty.
	 *
	 *     @type array $custom_groups The array of CiviCRM Custom Groups data.
	 * }
	 *
	 * @return array $data The array of Custom Fields data.
	 */
	protected function form_entity_custom_fields_data( $field, $args ) {

		// Init return.
		$data = [];

		// Init File Fields tracker.
		$file_fields = [];

		// Set ACFE "context". We want to apply tags.
		acfe_add_context( $this->context_save );

		// Build data array.
		foreach ( $args['custom_groups'] as $key => $custom_group ) {

			// Skip if there's no data in the field.
			if ( empty( $field[ 'custom_group_' . $custom_group['id'] ] ) ) {
				continue;
			}

			// Get Group Field.
			$custom_group_field = $field[ 'custom_group_' . $custom_group['id'] ];
			if ( empty( $custom_group_field ) ) {
				continue;
			}

			// Get the Custom Fields array.
			$custom_fields = $custom_group['api.CustomField.get']['values'];
			if ( empty( $custom_fields ) ) {
				continue;
			}

			// Get mapped Fields.
			foreach ( $custom_fields as $custom_field ) {

				// Build CiviCRM APIv3 code.
				$code = 'custom_' . $custom_field['id'];

				// Track any "File" Custom Fields.
				if ( 'File' === $custom_field['data_type'] ) {
					$file_fields[ $code ] = $custom_group_field[ $code ];
				}

				// Populate data array with values of mapped Fields.
				acfe_apply_tags( $custom_group_field[ $code ] );
				$data[ $code ] = $custom_group_field[ $code ];

			}

		}

		// Reset the ACFE "context".
		acfe_delete_context( array_keys( $this->context_save ) );

		// Post-process data for File Fields.
		if ( ! empty( $file_fields ) ) {
			foreach ( $file_fields as $code => $field_ref ) {

				// Get the ACF Field selector.
				$selector = $this->is_field_key_tag( $field_ref );
				if ( ! acf_is_field_key( $selector ) ) {
					continue;
				}

				// Get the ACF Field settings.
				$settings = get_field_object( $selector );

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

				// Build params array.
				$params = [
					'selector' => $selector,
					'post_id'  => 0,
				];

				// Overwrite entry in data array with data for CiviCRM.
				$data[ $code ] = $this->civicrm->attachment->value_get_for_civicrm( $attachment_id, $settings, $params );

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
	 * Processes Custom Fields once an Entity has been established.
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
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param array  $action The array of Action data.
	 * @param array  $entity The array of CiviCRM Entity data.
	 * @param string $entity_type The type of CiviCRM Entity, e.g. "contact", "activity".
	 */
	public function form_entity_custom_fields_post_process( $form, $action, $entity, $entity_type ) {

		// Bail if we have no post-process array.
		if ( empty( $this->file_fields_empty ) ) {
			return;
		}

		// Bail if we have no Entity ID.
		if ( empty( $entity['id'] ) ) {
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

		// Init values as empty.
		$values = [];

		// Get the corresponding values for this Entity.
		switch ( $entity_type ) {

			case 'activity':
				$values = $this->civicrm->custom_field->values_get_by_activity_id( $entity['id'], $custom_field_ids );
				break;

			case 'case':
				$values = $this->civicrm->custom_field->values_get_by_case_id( $entity['id'], $custom_field_ids );
				break;

			case 'contact':
				$values = $this->civicrm->custom_field->values_get_by_contact_id( $entity['id'], $custom_field_ids );
				break;

			case 'event':
				$values = $this->civicrm->custom_field->values_get_by_event_id( $entity['id'], $custom_field_ids );
				break;

			case 'participant':
				$values = $this->civicrm->custom_field->values_get_by_participant_id( $entity['id'], $custom_field_ids );
				break;

		}

		// Bail if there are no values for this Entity.
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
				'entity_id'       => $entity['id'],
				'custom_field_id' => $custom_field_id,
			];

			// Hand off to Attachment class.
			$this->civicrm->attachment->fields_clear( (int) $file_id, $data['settings'], $args );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Finds the linked Contact ID when it has been mapped.
	 *
	 * @since 0.7.0
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false if not found.
	 */
	protected function form_contact_id_get_mapped( $action_name ) {

		// Init return.
		$contact_id = false;

		// We need an Action Name.
		if ( empty( $action_name ) ) {
			return $contact_id;
		}

		// Get the Contact data for that Action.
		$related_contact = $this->get_action_output( $action_name, 'contact' );
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
	 * @since 0.7.0
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return integer|bool $case_id The numeric ID of the Case, or false if not found.
	 */
	protected function form_case_id_get_mapped( $action_name ) {

		// Init return.
		$case_id = false;

		// We need an Action Name.
		if ( empty( $action_name ) ) {
			return $case_id;
		}

		// Get the Case data for that Action.
		$related_case = $this->get_action_output( $action_name, 'case' );
		if ( empty( $related_case['id'] ) ) {
			return $case_id;
		}

		// Assign return.
		$case_id = (int) $related_case['id'];

		// --<
		return $case_id;

	}

	/**
	 * Finds the linked Participant ID when it has been mapped.
	 *
	 * @since 0.7.0
	 *
	 * @param string $action_name The name of the referenced Form Action.
	 * @return integer|bool $participant_id The numeric ID of the Participant, or false if not found.
	 */
	protected function form_participant_id_get_mapped( $action_name ) {

		// Init return.
		$participant_id = false;

		// We need an Action Name.
		if ( empty( $action_name ) ) {
			return $participant_id;
		}

		// Get the Participant data for that Action.
		$related_participant = $this->get_action_output( $action_name, 'participant' );
		if ( empty( $related_participant['id'] ) ) {
			return $participant_id;
		}

		// Assign return.
		$participant_id = (int) $related_participant['id'];

		// --<
		return $participant_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Prepare the data from an ACFE Form.
	 *
	 * @since 0.7.0
	 *
	 * @param array $form_data The array of data from the ACFE Form.
	 * @return array $filtered_data The filtered data.
	 */
	protected function form_data_prepare( $form_data ) {

		// Init filtered data.
		$filtered_data = [];

		// Bail if we have no Form data to save.
		if ( empty( $form_data ) ) {
			return $filtered_data;
		}

		// Populate return array from the Form data.
		foreach ( $form_data as $param => $value ) {

			// Skip if empty but allow (string) "0" as valid data.
			if ( empty( $value ) && '0' !== $value ) {
				continue;
			}

			// Maybe decode entities.
			if ( is_string( $value ) && ! is_numeric( $value ) ) {
				$value = html_entity_decode( $value );
			}

			// Maybe decode entities in arrays.
			if ( is_array( $value ) ) {
				array_walk_recursive(
					$value,
					function( &$item ) {
						if ( is_string( $item ) && ! is_numeric( $item ) ) {
							$item = html_entity_decode( $item );
						}
					}
				);
			}

			// Finally add value to return array.
			$filtered_data[ $param ] = $value;

		}

		// --<
		return $filtered_data;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the Fields for an ACFE Form "Settings" Group.
	 *
	 * @since 0.7.0
	 *
	 * @param array $args The arguments for defining the Field.
	 * @return array $group_field The ACF Group Field.
	 */
	public function form_setting_group_get( $args ) {

		// Build default instructions.
		$instructions = sprintf(
			/* translators: %s: The name of the Field */
			__( 'Use one Field to identify the %s setting.', 'civicrm-wp-profile-sync' ),
			$args['field_title']
		);

		// Maybe add extra text.
		if ( ! empty( $args['extra'] ) ) {
			$instructions = sprintf(
				/* translators: 1: The default instructions, 2: Extra instructions. */
				__( '%1$s %2$s', 'civicrm-wp-profile-sync' ),
				$instructions,
				$args['extra']
			);
		}

		// Maybe set Conditional Logic.
		$conditional_logic = 0;
		if ( ! empty( $args['conditional_logic'] ) ) {
			$conditional_logic = $args['conditional_logic'];
		}

		// Wrap in a container group.
		$group_field = [
			'key'               => $this->field_key . 'group_' . $args['field_name'],
			'label'             => $args['field_title'],
			'name'              => 'group_' . $args['field_name'],
			'type'              => 'group',
			'instructions'      => $instructions,
			'conditional_logic' => $conditional_logic,
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'required'          => 0,
			'layout'            => 'block',
		];

		// Init Sub-fields.
		$group_field['sub_fields'] = [];

		// Define value Field.
		$value_field = [
			'key'               => $this->field_key . 'value_' . $args['field_name'],
			'label'             => $args['field_title'],
			'name'              => 'value_' . $args['field_name'],
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
			'placeholder'       => __( 'Use Mapping Field', 'civicrm-wp-profile-sync' ),
			'allow_null'        => 1,
			'multiple'          => 0,
			'ui'                => 0,
			'return_format'     => 'value',
			'choices'           => $args['choices'],
		];

		// Maybe modify for Lazy Load.
		if ( ! empty( $args['lazy_load'] ) ) {
			$value_field['ui']   = 1;
			$value_field['ajax'] = 1;
		}

		// Add value Field.
		$group_field['sub_fields'][] = $value_field;

		// Define mapping instructions.
		$mapping_instructions = __( 'Choose a mapping for this Setting.', 'civicrm-wp-profile-sync' );
		if ( ! empty( $args['mapping_instructions'] ) ) {
			$mapping_instructions = $args['mapping_instructions'];
		}

		// Define Reference Field.
		/* translators: %s: The name of the Field */
		$title                              = sprintf( __( 'Map %s', 'civicrm-wp-profile-sync' ), $args['field_title'] );
		$mapping_field                      = $this->mapping_field_get( $args['field_name'], $title );
		$mapping_field['instructions']      = $mapping_instructions;
		$mapping_field['conditional_logic'] = [
			[
				[
					'field'    => $this->field_key . 'value_' . $args['field_name'],
					'operator' => '==empty',
				],
			],
		];

		// Add Reference Field.
		$group_field['sub_fields'][] = $mapping_field;

		// --<
		return $group_field;

	}

	/**
	 * Gets the data from an ACFE Form "Settings" Group.
	 *
	 * @since 0.7.0
	 *
	 * @param string $field_name The name of the Field.
	 * @param array  $action The customised name of the action.
	 * @param array  $group The optional nested ACF Group array.
	 * @return mixed $setting_value The setting value, or false if not found.
	 */
	public function form_setting_value_get( $field_name, $action, $group = '' ) {

		// Init value.
		$setting_value = '';

		// Get Group Field.
		if ( empty( $group ) ) {
			$group_field = $action[ $field_name ];
		} else {
			$group_field = $group[ 'group_' . $field_name ];
		}

		// Check Setting Field.
		if ( ! empty( $group_field[ 'value_' . $field_name ] ) ) {
			$setting_value = $group_field[ 'value_' . $field_name ];
		}

		// Check mapped Field.
		if ( '' === $setting_value ) {
			if ( ! empty( $group_field[ $field_name ] ) ) {
				acfe_apply_tags( $group_field[ $field_name ], $this->context_save );
				if ( ! empty( $group_field[ $field_name ] ) && is_numeric( $group_field[ $field_name ] ) ) {
					$setting_value = $group_field[ $field_name ];
				}
			}
		}

		// --<
		return $setting_value;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Adds the Conditional Field given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $args {
	 *     The array of Conditional Field arguments.
	 *
	 *     @type string $label        The Field label.
	 *     @type string $name         The Field name.
	 *     @type string $placeholder  The Field placeholder.
	 *     @type string $instructions The Field instructions.
	 * }
	 * @return array $field The array of Conditional Field data.
	 */
	protected function form_conditional_field_get( $args ) {

		// Populate default label.
		if ( empty( $args['label'] ) ) {
			$args['label'] = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		}

		// Populate default Field name.
		if ( empty( $args['name'] ) ) {
			$args['name'] = $this->conditional_code;
		}

		// Get Conditional Field.
		$field = $this->mapping_field_get( $args['name'], $args['label'] );

		// Always show instructions below.
		$field['wrapper']['data-instruction-placement'] = 'field';

		// Add placeholder and instructions.
		$field['placeholder']  = ! empty( $args['placeholder'] ) ? $args['placeholder'] : '';
		$field['instructions'] = ! empty( $args['instructions'] ) ? $args['instructions'] : '';

		// --<
		return $field;

	}

	/**
	 * Adds the Conditional Field given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $args {
	 *     The array of Conditional Field arguments.
	 *
	 *     @type array $action The array of Action data. Must be passed by reference.
	 *     @type string $key  The "key" for the Field name. Default is the property.
	 * }
	 */
	protected function form_conditional_populate( $args ) {

		// Build the action key.
		$key = empty( $args['key'] ) ? $this->conditional_code : $args['key'];

		// Store Conditional Field reference.
		$args['action'][ $key . '_ref' ] = $args['action'][ $key ];

		// Apply tags to the Conditional Field value.
		acfe_apply_tags( $args['action'][ $key ], $this->context_save );

		// Save a generic conditional result.
		$args['action']['conditional'] = $args['action'][ $key ];

	}

	/**
	 * Checks the Conditional Field given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $args {
	 *     The array of Conditional Field arguments.
	 *
	 *     @type array $action The array of Action data.
	 *     @type string $key  The "key" for the Field name. Default is the property.
	 * }
	 * @return bool $continue True if the Action should continue or false to skip.
	 */
	protected function form_conditional_check( $args ) {

		// Approve by default.
		$continue = true;

		// Build the action key.
		$key = empty( $args['key'] ) ? $this->conditional_code : $args['key'];

		// Skip if the Redirect Conditional Reference Field has a value.
		if ( ! empty( $args['action'][ $key . '_ref' ] ) ) {
			// And the Redirect Conditional Field has no value.
			if ( empty( $args['action'][ $key ] ) ) {
				$continue = false;
			}
		}

		// --<
		return $continue;

	}

	/**
	 * Checks a string to see if it is a Field key.
	 *
	 * @since 0.7.0
	 *
	 * @param string $key The string to query.
	 * @return string|bool $field_key The Field key if found or false otherwise.
	 */
	protected function is_field_key_tag( $key = '' ) {

		// Empty keys and non-strings definitely are not.
		if ( empty( $key ) || ! is_string( $key ) ) {
			return false;
		}

		// Let's extract the Field name.
		preg_match( '/^{field:field_([a-zA-Z0-9_]+)}$/', $key, $matches );

		// Bail if not exactly two entries in the matches array.
		if ( 2 !== count( $matches ) ) {
			return false;
		}

		// Construct return.
		$field_key = 'field_' . array_pop( $matches );

		// --<
		return $field_key;

	}

}
