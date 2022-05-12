<?php
/**
 * "Contact" ACFE Form Action Class.
 *
 * Holds methods common to CiviCRM Profile Sync ACFE Form Action classes.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync "Base" ACFE Form Action Class.
 *
 * A class that is extended by CiviCRM Profile Sync ACFE Form Action classes.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Base {

	/**
	 * Form Action Name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $action_name The unique name of the Form Action.
	 */
	public $action_name = '';

	/**
	 * Form Action Label.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $action_label The label of the Form Action.
	 */
	public $action_label = '';

	/**
	 * Form Action Alias Placeholder.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $alias_placeholder The alias placeholder for the Form Action.
	 */
	public $alias_placeholder = '';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_key The prefix for the Field Key.
	 */
	public $field_key = '';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_name The prefix for the Field Name.
	 */
	public $field_name = '';



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 */
	public function __construct() {

		// Callback for the "acfe/form/load/..." hook.
		add_filter( 'acfe/form/load/' . $this->action_name, [ $this, 'load' ], 10, 3 );

		// Callback for the "acfe/form/validation/..." hook.
 		add_action( 'acfe/form/validation/' . $this->action_name, [ $this, 'validation' ], 10, 3 );

		// Callback for the "acfe/form/make/..." hook.
		add_action( 'acfe/form/make/' . $this->action_name, [ $this, 'make' ], 10, 3 );

		// Generic callback for ACFE Form Actions hook.
		add_filter( 'acfe/form/actions', [ $this, 'action_add' ] );

	}



	/**
	 * Allow classes to configure themselves prior to the Layout being returned.
	 *
	 * @since 0.5
	 */
	public function configure() {}



	/**
	 * Performs tasks when the Form that the Action is attached to is loaded.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post in which the Form has been embedded.
	 * @param string $action The customised name of the action.
	 */
	public function load( $form, $current_post_id, $action ) {
		return $form;
	}



	/**
	 * Saves the result of the Action for use by subsequent Actions.
	 *
	 * @since 0.5
	 *
	 * @param string $action The name of the Action.
	 * @param array $data The result of the Action.
	 */
	public function load_action_save( $action = '', $data ) {

		// Get the existing array of Action results.
		$actions = get_query_var( 'acfe_form_actions', [] );

		$actions[ $this->action_name ] = $data;
		if ( ! empty( $action ) ) {
			$actions[ $action ] = $data;
		}

		// Update array of Action results.
		set_query_var( 'acfe_form_actions', $actions );

	}



	/**
	 * Performs the Action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 */
	public function make( $form, $current_post_id, $action ) {}



	/**
	 * Maybe skip the Action when the Form the Action is attached to is submitted.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the Form Action.
	 * @return bool $prepare The net result of the set of filters.
	 */
	public function make_skip( $form, $current_post_id, $action ) {

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Assume we're good to go.
		$prepare = true;

		/**
		 * Allow others to prevent Form Action.
		 *
		 * Returning false for any of these filters will skip the Action.
		 *
		 * @since 0.5
		 *
		 * @param bool $prepare True by default so that the Form Action goes ahead.
		 * @param array $form The array of Form data.
		 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
		 * @param string $action The customised name of the Form Action.
		 */
		$filter = 'acfe/form/prepare/' . $this->action_name;
		$prepare = apply_filters( $filter, $prepare, $form, $current_post_id, $action );
		$prepare = apply_filters( $filter . '/form=' . $form_name, $prepare, $form, $current_post_id, $action );
		if ( ! empty( $action ) ) {
			$prepare = apply_filters( $filter . '/action=' . $action, $prepare, $form, $current_post_id, $action );
		}

		// --<
		return $prepare;

	}



	/**
	 * Saves the result of the Action for use by subsequent Actions.
	 *
	 * @since 0.5
	 *
	 * @param string $action The name of the Action.
	 * @param array $data The result of the Action.
	 */
	public function make_action_save( $action = '', $data ) {

		// Get the existing array of Action results.
		$actions = get_query_var( 'acfe_form_actions', [] );

		$actions[ $this->action_name ] = $data;
		if ( ! empty( $action ) ) {
			$actions[ $action ] = $data;
		}

		// Update array of Action results.
		set_query_var( 'acfe_form_actions', $actions );

	}



	/**
	 * Defines the action by adding a layout.
	 *
	 * The "name" value of the layout determines the construction of the
	 * "acfe/form/load/..." and "acfe/form/make/..." actions.
	 *
	 * @since 0.5
	 *
	 * @param array $layouts The existing layouts.
	 * @return array $layouts The modified layouts.
	 */
	public function action_add( $layouts ) {

		// Let the classes that extend this one configure themselves.
		$this->configure();

		// Init our layout.
		$layout = [
			'key' => 'layout_' . $this->action_name,
			'name' => $this->action_name,
			'label' => $this->action_label,
			'display' => 'row',
			'min' => '',
			'max' => '',
		];

		// Build Action Tab.
		$action_tab_fields = $this->tab_action_add();

		// Build Mapping Tab.
		$mapping_tab_fields = $this->tab_mapping_add();

		// Build additional Tabs.
		$relationship_tab_fields = $this->tab_relationship_add();

		// Combine Sub-Fields.
		$sub_fields = array_merge(
			$action_tab_fields,
			$mapping_tab_fields,
			$relationship_tab_fields
		);

		/**
		 * Let the classes that extend this one modify the Sub-Fields.
		 *
		 * @since 0.5
		 *
		 * @param array $sub_fields The array of Sub-Fields.
		 */
		$layout['sub_fields'] = apply_filters( 'cwps/acfe/form/actions/sub_fields', $sub_fields );

		// Add our completed layout to the layouts array.
		$layouts[ 'layout_' . $this->action_name ] = $layout;

		// --<
		return $layouts;

	}



	// -------------------------------------------------------------------------



	/**
	 * Defines the "Action" Tab.
	 *
	 * These Fields are required to configure the Form Action.
	 *
	 * The ACFE "Action name" Field has a pre-defined format, e.g. it must be
	 * assigned the "acfe_slug" Field Type and have "acfe_form_custom_alias" as
	 * its "name". Only its "placeholder" attribute needs to be configured.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_action_add() {

		// Init Fields array.
		$fields = [];

		// "Action" Tab wrapper.
		$fields[] = [
			'key' => $this->field_key . 'tab_action',
			'label' => __( 'Action', 'civicrm-wp-profile-sync' ),
			'name' => '',
			'type' => 'tab',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-no-preference' => true,
			],
			'acfe_permissions' => '',
			'placement' => 'top',
			'endpoint' => 0,
		];

		// "Action name" Field.
		$fields[] = [
			'key' => $this->field_key . 'custom_alias',
			'label' => __( 'Action name', 'civicrm-wp-profile-sync' ),
			'name' => 'acfe_form_custom_alias',
			'type' => 'acfe_slug',
			'instructions' => __( '(Required) Name this action so it can be referenced.', 'civicrm-wp-profile-sync' ),
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => $this->alias_placeholder,
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		];

		// Add any further Fields.
		$action_extras = $this->tab_action_append();
		if ( ! empty( $action_extras ) ) {
			$fields = array_merge(
				$fields,
				$action_extras
			);
		}

		// --<
		return $fields;

	}



	/**
	 * Defines additional Fields for the "Action" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_action_append() {
		$fields = [];
		return $fields;
	}



	/**
	 * Defines the "Mapping" Tab.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_add() {
		$fields = [];
		return $fields;
	}



	/**
	 * Defines the "Mapping" Tab Header.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_mapping_header() {

		// "Mapping" Tab wrapper.
		$mapping_tab = [
			[
				'key' => $this->field_key . 'tab_load',
				'label' => __( 'Mapping', 'civicrm-wp-profile-sync' ),
				'name' => '',
				'type' => 'tab',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-no-preference' => true,
				],
				'acfe_permissions' => '',
				'placement' => 'top',
				'endpoint' => 0,
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
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_relationship_add() {
		$fields = [];
		return $fields;
	}



	/**
	 * Defines the "Relationship" Tab Header.
	 *
	 * @since 0.5
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	public function tab_relationship_header() {

		// "Relationship" Tab wrapper.
		$relationship_tab = [
			[
				'key' => $this->field_key . 'tab_relationship',
				'label' => __( 'Relationships', 'civicrm-wp-profile-sync' ),
				'name' => '',
				'type' => 'tab',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => [
					[
						[
							'field' => $this->field_key . 'submitting_contact',
							'operator' => '==',
							'value' => '0',
						],
					],
				],
				'wrapper' => [
					'width' => '',
					'class' => '',
					'id' => '',
					'data-no-preference' => true,
				],
				'acfe_permissions' => '',
				'placement' => 'top',
				'endpoint' => 0,
			],
		];

		// Combine Fields.
		$fields = array_merge(
			$relationship_tab
		);

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the array that defines a "Map Field" for the "Mapping" Tab.
	 *
	 * @since 0.5
	 *
	 * @param string $code The unique code for the Field.
	 * @param string $label The label for the Field.
	 * @param array $conditional_logic The conditional logic for the Field.
	 * @return array $field The array of Field data.
	 */
	public function mapping_field_get( $code, $label, $conditional_logic = [] ) {

		// Build the Field array.
		$field = [
			'key' => $this->field_key . 'map_' . $code,
			'label' => $label,
			'name' => $this->field_name . 'map_' . $code,
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'acfe_permissions' => '',
			'choices' => [],
			'default_value' => [],
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 1,
			'return_format' => 'value',
			'placeholder' => __( 'Default', 'civicrm-wp-profile-sync' ),
			'ajax' => 0,
			'search_placeholder' => __( 'Enter a custom value or template tag. (See "Cheatsheet" tab)', 'civicrm-wp-profile-sync' ),
			'allow_custom' => 1,
		];

		// Default conditional logic.
		$field['conditional_logic'] = 0;

		// Maybe replace with custom conditional logic.
		if ( ! empty( $conditional_logic ) ) {
			$field['conditional_logic'] = $conditional_logic;
		}

		// --<
		return $field;

	}



	/**
	 * Adds filters that configure "Mapping Fields" when loaded.
	 *
	 * @since 0.5
	 *
	 * @param string $code The unique code for the Field.
	 */
	public function mapping_field_filters_add( $code ) {

		// Grab reference to ACFE Helper object.
		$helpers = acf_get_instance( 'acfe_dynamic_forms_helpers' );

		// Populate mapping Fields.
		add_filter( 'acf/prepare_field/name=' . $this->field_name . 'map_' . $code, [ $helpers, 'map_fields_deep_no_custom' ] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Adds "Contact Reference Field" actions to the Javascript ACF Model.
	 *
	 * @since 0.5
	 *
	 * @param string $field_name The name the Field.
	 */
	public function js_model_contact_reference_field_add( $field_name ) {

		// Add to Javascript ACF Model.
		add_filter( 'cwps/acf/acfe/form_actions/reference_fields/contact', function( $actions ) use ( $field_name ) {
			$actions[ 'new_field/name=' . $field_name ] = 'newContactActionRefField';
			return $actions;
		} );

	}



	/**
	 * Adds "Case Reference Field" actions to the Javascript ACF Model.
	 *
	 * @since 0.5
	 *
	 * @param string $field_name The name the Field.
	 */
	public function js_model_case_reference_field_add( $field_name ) {

		// Add to Javascript ACF Model.
		add_filter( 'cwps/acf/acfe/form_actions/reference_fields/case', function( $actions ) use ( $field_name ) {
			$actions[ 'new_field/name=' . $field_name ] = 'newCaseActionRefField';
			return $actions;
		} );

	}



	/**
	 * Adds "Participant Reference Field" actions to the Javascript ACF Model.
	 *
	 * @since 0.5
	 *
	 * @param string $field_name The name the Field.
	 */
	public function js_model_participant_reference_field_add( $field_name ) {

		// Add to Javascript ACF Model.
		add_filter( 'cwps/acf/acfe/form_actions/reference_fields/participant', function( $actions ) use ( $field_name ) {
			$actions[ 'new_field/name=' . $field_name ] = 'newParticipantActionRefField';
			return $actions;
		} );

	}



	// -------------------------------------------------------------------------



	/**
	 * Prepare the data from an ACFE Form.
	 *
	 * @since 0.5
	 *
	 * @param array $form_data The array of data from the ACFE Form.
	 * @return array $filtered_data The filtered data.
	 */
	public function form_data_prepare( $form_data ) {

		// Init filtered data.
		$filtered_data = [];

		// Bail if we have no Form data to save.
		if ( empty( $form_data ) ) {
			return $filtered_data;
		}

		// Populate return array from the Form data.
		foreach ( $form_data as $param => $value ) {

			// Skip if empty but allow (string) "0" as valid data.
			if ( empty( $value ) && $value !== '0' ) {
				continue;
			}

			// Maybe decode entities.
			if ( is_string( $value ) && ! is_numeric( $value ) ) {
				$value = html_entity_decode( $value );
			}

			// Maybe decode entities in arrays.
			if ( is_array( $value ) ) {
				array_walk_recursive( $value, function( &$item ) {
					if ( is_string( $item ) && ! is_numeric( $item ) ) {
						$item = html_entity_decode( $item );
					}
				} );
			}

			// Finally add value to return array.
			$filtered_data[ $param ] = $value;

		}

		// --<
		return $filtered_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the Fields for an ACFE Form "Settings" Group.
	 *
	 * @since 0.5.4
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

		// Wrap in a container group.
		$group_field = [
			'key' => $this->field_key . $args['field_name'] . '_group_' . $args['field_name'],
			'label' => $args['field_title'],
			'name' => $this->field_name . $args['field_name'] . '_group_' . $args['field_name'],
			'type' => 'group',
			'instructions' => $instructions,
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
			],
			'required' => 0,
			'layout' => 'block',
		];

		// Init Sub-fields.
		$group_field['sub_fields'] = [];

		// Define value Field.
		$value_field = [
			'key' => $this->field_key . 'value_' . $args['field_name'],
			'label' => $args['field_title'],
			'name' => $this->field_name . 'value_' . $args['field_name'],
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [
				[
					[
						'field' => $this->field_key . 'map_' . $args['field_name'],
						'operator' => '==empty',
					],
				],
			],
			'wrapper' => [
				'width' => '',
				'class' => '',
				'id' => '',
				'data-instruction-placement' => 'field',
			],
			'acfe_permissions' => '',
			'default_value' => '',
			'placeholder' => __( 'Use mapping', 'civicrm-wp-profile-sync' ),
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'choices' => $args['choices'],
		];

		// Mmaybe modify for Lazy Load.
		if ( ! empty( $args['lazy_load'] ) ) {
			$value_field['ui'] = 1;
			$value_field['ajax'] = 1;
		}

		// Add value Field.
		$group_field['sub_fields'][] = $value_field;

		// Define Reference Field.
		/* translators: %s: The name of the Field */
		$title = sprintf( __( 'Map %s', 'civicrm-wp-profile-sync' ), $args['field_title'] );
		$mapping_field = $this->mapping_field_get( $args['field_name'], $title );
		$mapping_field['instructions'] = __( 'Choose a mapping for this Setting.', 'civicrm-wp-profile-sync' );
		$mapping_field['conditional_logic'] = [
			[
				[
					'field' => $this->field_key . 'value_' . $args['field_name'],
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
	 * @since 0.5.4
	 *
	 * @param string $field_name The name of the Field.
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return mixed $setting_value The setting value, or false if not found.
	 */
	public function form_setting_value_get( $field_name, $form, $current_post_id, $action, $group = '' ) {

		// Init value.
		$setting_value = '';

		// Get Group Field.
		if ( empty( $group ) ) {
			$group_field = get_sub_field( $this->field_key . $field_name . '_group_' . $field_name );
		} else {
			$group_field = $group[ $this->field_name . $field_name . '_group_' . $field_name ];
		}

		// Check Setting Field.
		if ( ! empty( $group_field[ $this->field_name . 'value_' . $field_name ] ) ) {
			$setting_value = $group_field[ $this->field_name . 'value_' . $field_name ];
		}

		// Check mapped Field.
		if ( $setting_value === '' ) {
			if ( ! empty( $group_field[ $this->field_name . 'map_' . $field_name ] ) ) {
				$reference = [ $field_name => $group_field[ $this->field_name . 'map_' . $field_name ] ];
				$reference = acfe_form_map_vs_fields( $reference, $reference, $current_post_id, $form );
				if ( ! empty( $reference[ $field_name ] ) && is_numeric( $reference[ $field_name ] ) ) {
					$setting_value = $reference[ $field_name ];
				}
			}
		}

		// --<
		return $setting_value;

	}



} // Class ends.



