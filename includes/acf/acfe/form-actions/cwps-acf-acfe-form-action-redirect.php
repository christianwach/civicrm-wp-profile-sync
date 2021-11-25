<?php
/**
 * "CiviCRM Redirect" ACFE Form Action Class.
 *
 * Handles the "CiviCRM Redirect" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync "CiviCRM Redirect" ACFE Form Action Class.
 *
 * A class that handles the "CiviCRM Redirect" ACFE Form Action.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Redirect extends CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Base {

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
	public $action_name = 'redirect_cwps_conditional';

	/**
	 * Field Key Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_key The prefix for the Field Key.
	 */
	public $field_key = 'field_cwps_redirect_conditional_action_';

	/**
	 * Field Name Prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $field_name The prefix for the Field Name.
	 */
	public $field_name = 'cwps_redirect_conditional_action_';



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
		$this->action_label = __( 'Conditional Redirect action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->alias_placeholder = __( 'Conditional Redirect', 'civicrm-wp-profile-sync' );

		// Init parent.
		parent::__construct();

	}



	/**
	 * Configure this object.
	 *
	 * @since 0.5
	 */
	public function configure() {

		// Conditional Field.
		$this->mapping_field_filters_add( 'redirect_conditional' );

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

		// Bail if a filter has overridden the action.
		if ( false === $this->make_skip( $form, $current_post_id, $action ) ) {
			return;
		}

		// Populate Redirect data array.
		$conditional_redirect = $this->form_redirect_data( $form, $current_post_id, $action );

		// Do the Redirect with the data from the Form.
		$conditional_redirect = $this->form_redirect_perform( $conditional_redirect );

		// Save the results of this Action for later use.
		$this->make_action_save( $action, $conditional_redirect );

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

		// Init Fields.
		$fields = [];

		// Add URL Field.
		$fields[] = [
			'key' => $this->field_key . 'redirect_url',
			'label' => __( 'Redirect URL', 'civicrm-wp-profile-sync' ),
			'name' => $this->field_name . 'redirect_url',
			'type' => 'text',
			'instructions' => __( 'The URL to redirect to. See "Cheatsheet" tab for all available template tags.', 'civicrm-wp-profile-sync' ),
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
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		];

		// Add Conditional Field.
		$code = 'redirect_conditional';
		$label = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$conditional = $this->mapping_field_get( $code, $label );
		$conditional['placeholder'] = __( 'Always redirect', 'civicrm-wp-profile-sync' );
		$conditional['wrapper']['data-instruction-placement'] = 'field';
		$conditional['instructions'] = __( 'To redirect only when a Form Field is populated, link this to the Form Field. To redirect only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );
		$fields[] = $conditional;

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Builds Redirect data array from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $form The array of Form data.
	 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Redirect data.
	 */
	public function form_redirect_data( $form, $current_post_id, $action ) {

		// Get some Form details.
		$form_name = acf_maybe_get( $form, 'name' );
		$form_id = acf_maybe_get( $form, 'ID' );

		// Init data array.
		$data = [];

		// Get the Action variables.
        $url = get_sub_field( $this->field_key . 'redirect_url' );
        $url = acfe_form_map_field_value( $url, $current_post_id, $form );

		/**
		 * Filter the Redirect URL.
		 *
		 * @since 0.5
		 *
		 * @param string $url The Redirect URL.
		 * @param array $form The array of Form data.
		 * @param integer $current_post_id The ID of the Post from which the Form has been submitted.
		 * @param string $action The customised name of the Form Action.
		 */
		$filter = 'acfe/form/submit/' . $this->action_name . '/url';
		$url = apply_filters( $filter, $url, $form, $current_post_id, $action );
		$url = apply_filters( $filter . '/form=' . $form_name, $url, $form, $current_post_id, $action );
		if ( ! empty( $action ) ) {
			$url = apply_filters( $filter . '/action=' . $action, $url, $form, $current_post_id, $action );
		}

		// Add trimmed URL to the data array.
		$data['redirect_url'] = trim( $url );

		// Get Redirect Conditional Reference.
		$data['redirect_conditional_ref'] = get_sub_field( $this->field_key . 'map_redirect_conditional' );
		$conditionals = [ $data['redirect_conditional_ref'] ];

		// Populate array with mapped Conditional Field values.
		$conditionals = acfe_form_map_vs_fields( $conditionals, $conditionals, $current_post_id, $form );

		// Save Redirect Conditional.
		$data['redirect_conditional'] = array_pop( $conditionals );

		// --<
		return $data;

	}



	/**
	 * Conditionally performs the redirect given data from mapped Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $redirect_data The array of Redirect data.
	 * @return array|bool $redirect The Redirect data array, or false on failure.
	 */
	public function form_redirect_perform( $redirect_data ) {

		// Init return.
		$redirect = false;

		// Skip if the Redirect Conditional Reference Field has a value.
		if ( ! empty( $redirect_data['redirect_conditional_ref'] ) ) {
			// And the Redirect Conditional Field has no value.
			if ( empty( $redirect_data['redirect_conditional'] ) ) {
				return $redirect_data;
			}
		}

		// Unset Redirect Conditionals.
		if ( isset( $redirect_data['redirect_conditional'] ) ) {
			unset( $redirect_data['redirect_conditional'] );
		}
		if ( isset( $redirect_data['redirect_conditional_ref'] ) ) {
			unset( $redirect_data['redirect_conditional_ref'] );
		}

		// Strip out empty Fields.
		$redirect_data = $this->form_data_prepare( $redirect_data );

		// Sanity check.
		if ( empty( $redirect_data['redirect_url'] ) ) {
			return $redirect;
		}

		// Do the redirect.
		wp_redirect( $redirect_data['redirect_url'] );
		exit;

	}



} // Class ends.



