<?php
/**
 * ACFE Form Class.
 *
 * Handles compatibility with ACFE Forms.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync ACFE Form Class.
 *
 * A class that handles compatibility with ACFE Forms.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Form {

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
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * ACF object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * ACF Extended object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acfe;

	/**
	 * Supported Location Rule name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $rule_name = 'form_civicrm';

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
		$this->civicrm    = $this->acf_loader->civicrm;
		$this->acf        = $this->acf_loader->acf;
		$this->acfe       = $parent;

		// Init when the ACFE class is loaded.
		add_action( 'cwps/acf/acfe/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Listen for queries from the ACF Field Group class.
		add_filter( 'cwps/acf/query_field_group_mapped', [ $this, 'query_field_group_mapped' ], 10, 2 );
		add_filter( 'cwps/acf/field_group/query_supported_rules', [ $this, 'query_supported_rules' ], 10, 4 );

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/acf/query_settings_field', [ $this, 'query_settings_field' ], 200, 3 );

		// Register ACF Location Types.
		add_action( 'acf/init', [ $this, 'register_location_types' ] );

		/*
		 * Check for legacy ACF Extended.
		 *
		 * The Form layer has been completely rewritten in ACF Extended version 0.9 and
		 * so new code has had to be written for compatibility with the new architecture.
		 */
		$new_acfe_forms = version_compare( $this->acfe->acfe_version, '0.9', '>=' );

		/**
		 * Filters the check for legacy ACF Extended.
		 *
		 * @since 0.7.0
		 *
		 * @param bool $new_acfe_forms True if ACF Extended is greater than 0.9, false otherwise.
		 */
		$new_acfe_forms = apply_filters( 'cwps/acf/acfe/form/acfe_version_check', $new_acfe_forms );

		// Register ACFE Form Actions.
		if ( $new_acfe_forms ) {
			add_action( 'acf/include_field_types', [ $this, 'register_form_actions_latest' ], 50 );
		} else {
			add_action( 'acfe/include_form_actions', [ $this, 'register_form_actions_legacy' ], 50 );
		}

		// Add Form Actions Javascript.
		if ( $new_acfe_forms ) {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_form_action_scripts_latest' ] );
		} else {
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_form_action_scripts_legacy' ] );
		}

		// Clear Form Action Query Vars.
		if ( $new_acfe_forms ) {
			add_action( 'acfe/form/submit_success', [ $this, 'form_action_query_vars_clear_latest' ], 1000 );
		} else {
			add_action( 'acfe/form/submit', [ $this, 'form_action_query_vars_clear_legacy' ] );
		}

		// Set a better Form Wrapper class.
		if ( $new_acfe_forms ) {
			add_filter( 'acfe/form/load_form', [ $this, 'form_wrapper' ], 10 );
		} else {
			add_filter( 'acfe/form/load', [ $this, 'form_wrapper' ], 10, 2 );
		}

	}

	/**
	 * Clear the Form Action Query Vars in ACFE 0.9.x+.
	 *
	 * This means we get a fresh set of Query Vars during the load process after
	 * a Form has been submitted.
	 *
	 * @since 0.7.0
	 */
	public function form_action_query_vars_clear_latest() {

		// Clear the array of Action results.
		acf_set_form_data( 'acfe/form/actions', [] );

	}

	/**
	 * Clear the Form Action Query Vars in ACFE 0.8.x.
	 *
	 * This means we get a fresh set of Query Vars during the load process after
	 * a Form has been submitted.
	 *
	 * @since 0.5
	 * @since 0.7.0 Renamed.
	 */
	public function form_action_query_vars_clear_legacy() {

		/*
		// Clear the array of Action results.
		set_query_var( 'acfe_form_actions', [] );
		*/

	}

	/**
	 * Alters the default "Success Wrapper" class.
	 *
	 * @since 0.5
	 *
	 * @param array   $form The ACF Form data array.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $form The modified ACF Form data array.
	 */
	public function form_wrapper( $form, $post_id = 0 ) {

		// Alter the default "Success Wrapper" in ACF Extended 0.9.x.
		if ( ! empty( $form['success']['wrapper'] ) ) {
			if ( '<div id="message" class="updated">%s</div>' === $form['success']['wrapper'] ) {
				$form['success']['wrapper'] = '<div id="message" class="acfe-success">%s</div>';
				return $form;
			}
		}

		// Alter the default "Success Wrapper" in ACF Extended 0.8.x.
		if ( '<div id="message" class="updated">%s</div>' === $form['html_updated_message'] ) {
			$form['html_updated_message'] = '<div id="message" class="acfe-success">%s</div>';
		}

		// --<
		return $form;

	}

	/**
	 * Register Location Types.
	 *
	 * @since 0.5
	 */
	public function register_location_types() {

		// Bail if less than ACF 5.9.0.
		if ( ! function_exists( 'acf_register_location_type' ) ) {
			return;
		}

		// Include Location Rule class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/locations/cwps-acf-acfe-location-bypass.php';

		// Register Location Types with ACF.
		acf_register_location_type( 'CiviCRM_Profile_Sync_ACF_Location_Type_Bypass' );

	}

	/**
	 * Register Form Actions for ACFE version 0.9.x.
	 *
	 * @since 0.7.0
	 */
	public function register_form_actions_latest() {

		// Include Form Action base class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.9.x/cwps-acf-acfe-form-action-base.php';

		// Include Form Action classes.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.9.x/cwps-acf-acfe-form-action-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.9.x/cwps-acf-acfe-form-action-activity.php';

		// Register the Form Actions.
		acfe_register_form_action_type( 'CWPS_ACF_ACFE_Form_Action_Contact' );
		acfe_register_form_action_type( 'CWPS_ACF_ACFE_Form_Action_Activity' );

		// Init Event Actions if the CiviEvent component is active.
		$event_active = $this->plugin->civicrm->is_component_enabled( 'CiviEvent' );
		if ( $event_active ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.9.x/cwps-acf-acfe-form-action-participant.php';
			acfe_register_form_action_type( 'CWPS_ACF_ACFE_Form_Action_Participant' );
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.9.x/cwps-acf-acfe-form-action-event.php';
			acfe_register_form_action_type( 'CWPS_ACF_ACFE_Form_Action_Event' );
		}

		// Init Case Action if the CiviCase component is active.
		$case_active = $this->plugin->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.9.x/cwps-acf-acfe-form-action-case.php';
			acfe_register_form_action_type( 'CWPS_ACF_ACFE_Form_Action_Case' );
		}

		// Init Email Action if the "Email API" Extension is active.
		$email_active = $this->plugin->civicrm->is_extension_enabled( 'org.civicoop.emailapi' );
		if ( $email_active ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.9.x/cwps-acf-acfe-form-action-email.php';
			acfe_register_form_action_type( 'CWPS_ACF_ACFE_Form_Action_Email' );
		}

	}

	/**
	 * Register Form Actions for ACFE version 0.8.x.
	 *
	 * @since 0.5
	 */
	public function register_form_actions_legacy() {

		// Include Form Action base class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.8.x/cwps-acf-acfe-form-action-base.php';

		// Include Form Action classes.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.8.x/cwps-acf-acfe-form-action-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.8.x/cwps-acf-acfe-form-action-activity.php';

		// Init Form Actions.
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Contact( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Activity( $this );

		// Init Event Actions if the CiviEvent component is active.
		$event_active = $this->plugin->civicrm->is_component_enabled( 'CiviEvent' );
		if ( $event_active ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.8.x/cwps-acf-acfe-form-action-participant.php';
			new CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Participant( $this );
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.8.x/cwps-acf-acfe-form-action-event.php';
			new CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Event( $this );
		}

		// Init Case Action if the CiviCase component is active.
		$case_active = $this->plugin->civicrm->is_component_enabled( 'CiviCase' );
		if ( $case_active ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.8.x/cwps-acf-acfe-form-action-case.php';
			new CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Case( $this );
		}

		// Init Email Action if the "Email API" Extension is active.
		$email_active = $this->plugin->civicrm->is_extension_enabled( 'org.civicoop.emailapi' );
		if ( $email_active ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/form-actions/acfe-0.8.x/cwps-acf-acfe-form-action-email.php';
			new CiviCRM_Profile_Sync_ACF_ACFE_Form_Action_Email( $this );
		}

	}

	/**
	 * Enqueue Form Action Javascript for ACFE version 0.9.x.
	 *
	 * @since 0.7.0
	 */
	public function enqueue_form_action_scripts_latest() {

		// Bail if the current screen is not an Edit ACFE Form screen.
		$screen = get_current_screen();
		if ( ! ( $screen instanceof WP_Screen ) ) {
			return;
		}
		if ( 'post' !== $screen->base || 'acfe-form' !== $screen->id ) {
			return;
		}

		// Add JavaScript plus dependencies.
		wp_enqueue_script(
			'cwps-acfe-form-actions',
			plugins_url( 'assets/js/acf/acfe/form-actions/acfe-0.9.x/cwps-form-action-model.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-extended' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			true
		);

		// Init the Contact Reference Field actions array.
		$contact_action_refs = [
			// Contact Actions must always be present.
			'new_field/key=field_cwps_contact_action_name' => 'newContactActionAlias',
			'remove_field/key=field_cwps_contact_action_name' => 'removeContactActionAlias',
		];

		/**
		 * Query Form Action classes to build the Contact Reference Fields ACF Model actions array.
		 *
		 * @since 0.5
		 *
		 * @param array $contact_action_refs The ACF Model actions array to be populated.
		 */
		$contact_actions = apply_filters( 'cwps/acf/acfe/form_actions/reference_fields/contact', $contact_action_refs );

		// Init the Case Reference Field actions array.
		$case_action_refs = [
			// Case Actions must always be present.
			'new_field/key=field_cwps_case_action_name'    => 'newCaseActionAlias',
			'remove_field/key=field_cwps_case_action_name' => 'removeCaseActionAlias',
		];

		/**
		 * Query Form Action classes to build the Case Reference Fields ACF Model actions array.
		 *
		 * @since 0.5
		 *
		 * @param array $case_action_refs The ACF Model actions array to be populated.
		 */
		$case_actions = apply_filters( 'cwps/acf/acfe/form_actions/reference_fields/case', $case_action_refs );

		// Init the Participant Reference Field actions array.
		$participant_action_refs = [
			// Participant Actions must always be present.
			'new_field/key=field_cwps_participant_action_name' => 'newParticipantActionAlias',
			'remove_field/key=field_cwps_participant_action_name' => 'removeParticipantActionAlias',
		];

		/**
		 * Query Form Action classes to build the Participant Reference Fields ACF Model actions array.
		 *
		 * @since 0.5
		 *
		 * @param array $participant_action_refs The ACF Model actions array to be populated.
		 */
		$participant_actions = apply_filters( 'cwps/acf/acfe/form_actions/reference_fields/participant', $participant_action_refs );

		// Build data array.
		$vars = [
			'localisation' => [],
			'settings'     => [
				'contact_actions_reference'     => $contact_actions,
				'case_actions_reference'        => $case_actions,
				'participant_actions_reference' => $participant_actions,
			],
		];

		// Localize our script.
		wp_localize_script(
			'cwps-acfe-form-actions',
			'CWPS_ACFE_Form_Action_Vars',
			$vars
		);

	}

	/**
	 * Enqueue Form Action Javascript for ACFE version 0.9.x.
	 *
	 * @since 0.5
	 */
	public function enqueue_form_action_scripts_legacy() {

		// Bail if the current screen is not an Edit ACFE Form screen.
		$screen = get_current_screen();
		if ( ! ( $screen instanceof WP_Screen ) ) {
			return;
		}
		if ( 'post' !== $screen->base || 'acfe-form' !== $screen->id ) {
			return;
		}

		// Add JavaScript plus dependencies.
		wp_enqueue_script(
			'cwps-acfe-form-actions',
			plugins_url( 'assets/js/acf/acfe/form-actions/acfe-0.8.x/cwps-form-action-model.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-extended' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			true
		);

		// Init the Contact Reference Field actions array.
		$contact_action_refs = [
			// Contact Actions must always be present.
			'new_field/key=field_cwps_contact_action_custom_alias' => 'newContactActionAlias',
			'remove_field/key=field_cwps_contact_action_custom_alias' => 'removeContactActionAlias',
		];

		/**
		 * Query Form Action classes to build the Contact Reference Fields ACF Model actions array.
		 *
		 * @since 0.5
		 *
		 * @param array $contact_action_refs The ACF Model actions array to be populated.
		 */
		$contact_actions = apply_filters( 'cwps/acf/acfe/form_actions/reference_fields/contact', $contact_action_refs );

		// Init the Case Reference Field actions array.
		$case_action_refs = [
			// Case Actions must always be present.
			'new_field/key=field_cwps_case_action_custom_alias' => 'newCaseActionAlias',
			'remove_field/key=field_cwps_case_action_custom_alias' => 'removeCaseActionAlias',
		];

		/**
		 * Query Form Action classes to build the Case Reference Fields ACF Model actions array.
		 *
		 * @since 0.5
		 *
		 * @param array $case_action_refs The ACF Model actions array to be populated.
		 */
		$case_actions = apply_filters( 'cwps/acf/acfe/form_actions/reference_fields/case', $case_action_refs );

		// Init the Participant Reference Field actions array.
		$participant_action_refs = [
			// Participant Actions must always be present.
			'new_field/key=field_cwps_participant_action_custom_alias' => 'newParticipantActionAlias',
			'remove_field/key=field_cwps_participant_action_custom_alias' => 'removeParticipantActionAlias',
		];

		/**
		 * Query Form Action classes to build the Participant Reference Fields ACF Model actions array.
		 *
		 * @since 0.5
		 *
		 * @param array $participant_action_refs The ACF Model actions array to be populated.
		 */
		$participant_actions = apply_filters( 'cwps/acf/acfe/form_actions/reference_fields/participant', $participant_action_refs );

		// Build data array.
		$vars = [
			'localisation' => [],
			'settings'     => [
				'contact_actions_reference'     => $contact_actions,
				'case_actions_reference'        => $case_actions,
				'participant_actions_reference' => $participant_actions,
			],
		];

		// Localize our script.
		wp_localize_script(
			'cwps-acfe-form-actions',
			'CWPS_ACFE_Form_Action_Vars',
			$vars
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Listen for queries from the Field Group class.
	 *
	 * This method responds with a Boolean if it detects that this Field Group
	 * should bypass ACF.
	 *
	 * @since 0.5
	 *
	 * @param bool  $mapped The existing mapping flag.
	 * @param array $field_group The array of ACF Field Group data.
	 * @return bool $mapped True if the Field Group should bypass ACF, or pass through if not.
	 */
	public function query_field_group_mapped( $mapped, $field_group ) {

		// Bail if a Mapping has already been found.
		if ( false !== $mapped ) {
			return $mapped;
		}

		// Bail if this is not a Bypass Field Group.
		$is_bypass_field_group = $this->is_bypass_field_group( $field_group );
		if ( false === $is_bypass_field_group ) {
			return $mapped;
		}

		// --<
		return true;

	}

	/**
	 * Check if this Field Group should bypass ACF.
	 *
	 * @since 0.5
	 *
	 * @param array $field_group The Field Group to check.
	 * @return array|bool The array of Entities if the Field Group should bypass ACF, or false otherwise.
	 */
	public function is_bypass_field_group( $field_group ) {

		// Bail if there's no Field Group ID.
		if ( empty( $field_group['ID'] ) ) {
			return false;
		}

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache[ $field_group['ID'] ] ) ) {
			return $pseudocache[ $field_group['ID'] ];
		}

		// Assume not visible.
		$is_visible = false;

		// Bail if no Location Rules exist.
		if ( ! empty( $field_group['location'] ) ) {

			// We only need the key to test for an ACF Bypass location.
			$params = [
				$this->rule_name => 'foo',
			];

			// Do the check.
			$is_visible = $this->acf_loader->acf->field_group->is_visible( $field_group, $params );

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $field_group['ID'] ] ) ) {
			$pseudocache[ $field_group['ID'] ] = $is_visible;
		}

		// --<
		return $is_visible;

	}

	/**
	 * Listen for queries for supported Location Rules.
	 *
	 * @since 0.5
	 *
	 * @param bool  $supported The existing supported Location Rules status.
	 * @param array $rule The Location Rule.
	 * @param array $params The query params array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return bool $supported The modified supported Location Rules status.
	 */
	public function query_supported_rules( $supported, $rule, $params, $field_group ) {

		// Bail if already supported.
		if ( true === $supported ) {
			return $supported;
		}

		// Test for this Location Rule.
		if ( $rule['param'] == $this->rule_name && ! empty( $params[ $this->rule_name ] ) ) {
			$supported = true;
		}

		// --<
		return $supported;

	}

	// -------------------------------------------------------------------------

	/**
	 * Returns a Setting Field from this Entity when found.
	 *
	 * @since 0.5
	 *
	 * @param array $setting_field The existing Setting Field array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_settings_field( $setting_field, $field, $field_group ) {

		// Pass if conflicting Fields have been found.
		if ( false === $setting_field ) {
			return false;
		}

		// Pass if this is not a Bypass Field Group.
		$is_visible = $this->is_bypass_field_group( $field_group );
		if ( false === $is_visible ) {
			return $setting_field;
		}

		// If already populated, then this is a conflicting Field.
		if ( ! empty( $setting_field ) ) {
			return false;
		}

		// Get the array of Entities and IDs.
		$entity_array = $this->entity_mapping_extract( $field_group['location'] );

		/**
		 * Request an array of Setting Field choices from Entity classes.
		 *
		 * @since 0.5
		 *
		 * @param array The empty default Setting Field choices array.
		 * @param array $field The ACF Field data array.
		 * @param array $field_group The ACF Field Group data array.
		 * @param array $entity_array The array of Entities and IDs.
		 */
		$choices = apply_filters( 'cwps/acf/bypass/query_settings_choices', [], $field, $field_group, $entity_array );

		// Bail if there aren't any.
		if ( empty( $choices ) ) {
			return false;
		}

		// Define Setting Field.
		$setting_field = [
			'key'           => $this->civicrm->acf_field_key_get(),
			'label'         => __( 'CiviCRM Field', 'civicrm-wp-profile-sync' ),
			'name'          => $this->civicrm->acf_field_key_get(),
			'type'          => 'select',
			'instructions'  => __( 'Choose the CiviCRM Field that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
			'default_value' => '',
			'placeholder'   => '',
			'allow_null'    => 1,
			'multiple'      => 0,
			'ui'            => 0,
			'required'      => 0,
			'return_format' => 'value',
			'parent'        => $this->acf->field_group->placeholder_group_get(),
			'choices'       => $choices,
		];

		// Return populated array.
		return $setting_field;

	}

	/**
	 * Returns an array containing the Entities and their IDs.
	 *
	 * @since 0.5
	 *
	 * @param array $location_rules The Location Rules for the Field.
	 * @return array $entity_mapping The array containing the Entities and IDs.
	 */
	public function entity_mapping_extract( $location_rules ) {

		// Init an empty Entity mapping array.
		$entity_mapping = [];

		// The Location Rules outer array is made of "grouos".
		foreach ( $location_rules as $group ) {

			// Skip group if it has no rules.
			if ( empty( $group ) ) {
				continue;
			}

			// The Location Rules inner array is made of "rules".
			foreach ( $group as $rule ) {

				// Is this a Bypass rule?
				if ( $rule['param'] === $this->rule_name ) {

					// Extract the Entity and ID.
					$tmp = explode( '-', $rule['value'] );

					// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
					// $entity_map = [];
					// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
					// $entity_map[] = [ 'entity' => $tmp[0], 'entity_id' => (int) $tmp[1] ];

					// Add to return.
					$entity_mapping[ $tmp[0] ][] = (int) $tmp[1];

				}

			}

		}

		// --<
		return $entity_mapping;

	}

}
