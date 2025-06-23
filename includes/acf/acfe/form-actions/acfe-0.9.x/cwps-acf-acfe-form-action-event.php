<?php
/**
 * "Event" ACFE Form Action Class.
 *
 * Handles the "Event" ACFE Form Action.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.7.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync "Event" ACFE Form Action Class.
 *
 * A class that handles the "Event" ACFE Form Action.
 *
 * @since 0.7.0
 */
class CWPS_ACF_ACFE_Form_Action_Event extends CWPS_ACF_ACFE_Form_Action_Base {

	/**
	 * Form Action Name.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $name = 'cwps_event';

	/**
	 * Data transient key.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var string
	 */
	private $transient_key = 'cwps_acf_acfe_form_action_event';

	/**
	 * Public Event Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $public_event_fields;

	/**
	 * Settings Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $settings_fields;

	/**
	 * Fields for Contacts.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $fields_for_contacts;

	/**
	 * Event Location Address Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_location_address_fields;

	/**
	 * Address Custom Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $address_custom_fields;

	/**
	 * Event Location Email Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_location_email_fields;

	/**
	 * Event Location Phone Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_location_phone_fields;

	/**
	 * Phone Types.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $phone_types;

	/**
	 * Event Registration Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_registration_fields;

	/**
	 * Event Registration Screen Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_registration_screen_fields;

	/**
	 * Event Confirm Screen Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_confirm_screen_fields;

	/**
	 * Event Thank You Screen Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_thankyou_screen_fields;

	/**
	 * Event Confirmation Email Fields.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_confirmation_email_fields;

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
	 * Event Location choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_location_choices;

	/**
	 * Event Type choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_type_choices;

	/**
	 * Participant Role choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $participant_role_choices;

	/**
	 * Participant Listing choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $participant_listing_choices;

	/**
	 * Profile choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $profile_choices;

	/**
	 * Campaign choices.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $campaign_choices;

	/**
	 * Event Contact Fields.
	 *
	 * These need special handling in ACFE Forms.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $contact_fields = [
		'created_id' => 'civicrm_contact',
	];

	/**
	 * Public Event Fields to ignore.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $event_fields_to_ignore = [
		'event_type_id'          => 'select',
		'default_role_id'        => 'select',
		'participant_listing_id' => 'select',
		'campaign_id'            => 'select',
		'is_show_location'       => 'true_false',
	];

	/**
	 * Location Fields to ignore.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $location_fields_to_ignore = [
		'is_show_location' => 'true_false',
	];

	/**
	 * Location Phone Fields to ignore.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $phone_fields_to_ignore = [
		'phone_type_id' => 'select',
	];

	/**
	 * Registration Fields to ignore.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $registration_fields_to_ignore = [
		'is_online_registration' => 'true_false',
	];

	/**
	 * Registration Confirmation Screen Fields to ignore.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $confirm_fields_to_ignore = [
		'is_confirm_enabled' => 'true_false',
	];

	/**
	 * Registration Confirmation Email Fields to ignore.
	 *
	 * @since 0.7.0
	 * @access private
	 * @var array
	 */
	private $registration_email_fields_to_ignore = [
		'is_email_confirm' => 'true_false',
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
		$this->title = __( 'CiviCRM Event action', 'civicrm-wp-profile-sync' );

		// Alias Placeholder for this Form Action.
		$this->name_placeholder = __( 'CiviCRM Event', 'civicrm-wp-profile-sync' );

		// Declare core Fields for this Form Action.
		$this->item = [
			'action'       => $this->name,
			'name'         => '',
			'id'           => false,
			'event'        => [
				'id'                     => false,
				'event_type_id'          => '',
				'default_role_id'        => false,
				'participant_listing_id' => false,
				'created_id'             => '',
			],
			'location'     => [],
			'registration' => [],
			'conditional'  => '',
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
		foreach ( $this->public_event_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->event_fields_to_ignore ) ) {
				$action[ $field['name'] ] = $action['event'][ $field['name'] ];
			}
		}

		// Load Settings data.
		foreach ( $this->settings_fields as $field ) {
			// Skip Campaign Field if the CiviCampaign component is not active.
			if ( 'campaign_id' === $field['name'] ) {
				$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
				if ( ! $campaign_active ) {
					continue;
				}
			}
			$action[ 'group_' . $field['name'] ] = $action['event'][ $field['name'] ];
		}

		// Load Contact References.
		foreach ( $this->contact_fields as $name => $title ) {
			$action[ 'contact_group_' . $name ] = $action['event'][ $name ];
		}

		// Load Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			if ( array_key_exists( 'custom_group_' . $custom_group['id'], $action['event'] ) ) {
				$action[ 'custom_group_' . $custom_group['id'] ] = $action['event'][ 'custom_group_' . $custom_group['id'] ];
			}
		}

		// Load Location Fields.
		$action['group_is_show_location']  = $action['location']['is_show_location'];
		$action['group_existing_location'] = $action['location']['existing_location'];
		$action['location_group']          = $action['location']['new_location'];

		// Load Registration Fields.
		$action['group_is_online_registration'] = $action['registration']['is_online_registration'];
		$action['registration_settings']        = $action['registration']['registration_settings'];

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
		foreach ( $this->public_event_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->event_fields_to_ignore ) ) {
				$save['event'][ $field['name'] ] = $action[ $field['name'] ];
			}
		}

		// Save Settings data.
		foreach ( $this->settings_fields as $field ) {
			// Skip Campaign Field if the CiviCampaign component is not active.
			if ( 'campaign_id' === $field['name'] ) {
				$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
				if ( ! $campaign_active ) {
					continue;
				}
			}
			$save['event'][ $field['name'] ] = $action[ 'group_' . $field['name'] ];
		}

		// Save Contact References.
		foreach ( $this->contact_fields as $name => $title ) {
			$save['event'][ $name ] = $action[ 'contact_group_' . $name ];
		}

		// Save Custom Fields.
		foreach ( $this->custom_fields as $key => $custom_group ) {
			$save['event'][ 'custom_group_' . $custom_group['id'] ] = $action[ 'custom_group_' . $custom_group['id'] ];
		}

		// Save Location Fields.
		$save['location']['is_show_location']  = $action['group_is_show_location'];
		$save['location']['existing_location'] = $action['group_existing_location'];
		$save['location']['new_location']      = $action['location_group'];

		// Save Registration Fields.
		$save['registration']['is_online_registration'] = $action['group_is_online_registration'];
		$save['registration']['registration_settings']  = $action['registration_settings'];

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

		// Get the public Event Fields from transient if possible.
		if ( false !== $data && isset( $data['public_event_fields'] ) ) {
			$this->public_event_fields = $data['public_event_fields'];
		} else {
			// Get the public Event Fields.
			$this->public_event_fields        = $this->civicrm->event_field->get_public_fields( 'create' );
			$transient['public_event_fields'] = $this->public_event_fields;
		}

		// Get the Event Settings Fields from transient if possible.
		if ( false !== $data && isset( $data['settings_fields'] ) ) {
			$this->settings_fields = $data['settings_fields'];
		} else {
			$this->settings_fields        = $this->civicrm->event_field->get_settings_fields( 'create' );
			$transient['settings_fields'] = $this->settings_fields;
		}

		// Get Fields for Contacts from transient if possible.
		if ( false !== $data && isset( $data['fields_for_contacts'] ) ) {
			$this->fields_for_contacts = $data['fields_for_contacts'];
		} else {
			foreach ( $this->contact_fields as $name => $field_type ) {
				$field                       = $this->civicrm->event_field->get_by_name( $name );
				$this->fields_for_contacts[] = $field;
			}
			$transient['fields_for_contacts'] = $this->fields_for_contacts;
		}

		// Handle Contact Fields.
		foreach ( $this->fields_for_contacts as $field ) {
			// Add Contact Action Reference Field to ACF Model.
			$this->js_model_contact_reference_field_add( 'ref_' . $field['name'] );
		}

		// ---------------------------------------------------------------------

		// Get the Event Location Address Fields from transient if possible.
		if ( false !== $data && isset( $data['event_location_address_fields'] ) ) {
			$this->event_location_address_fields = $data['event_location_address_fields'];
		} else {
			$this->event_location_address_fields        = $this->civicrm->event_location->get_address_fields( 'create' );
			$transient['event_location_address_fields'] = $this->event_location_address_fields;
		}

		// Get the Custom Fields for all Addresses from transient if possible.
		if ( false !== $data && isset( $data['address_custom_fields'] ) ) {
			$this->address_custom_fields = $data['address_custom_fields'];
		} else {
			$this->address_custom_fields        = $this->plugin->civicrm->custom_group->get_for_addresses();
			$transient['address_custom_fields'] = $this->address_custom_fields;
		}

		// Get the Event Location Email Fields from transient if possible.
		if ( false !== $data && isset( $data['event_location_email_fields'] ) ) {
			$this->event_location_email_fields = $data['event_location_email_fields'];
		} else {
			$this->event_location_email_fields        = $this->civicrm->event_location->get_email_fields( 'create' );
			$transient['event_location_email_fields'] = $this->event_location_email_fields;
		}

		// Get the Event Location Phone Fields from transient if possible.
		if ( false !== $data && isset( $data['event_location_phone_fields'] ) ) {
			$this->event_location_phone_fields = $data['event_location_phone_fields'];
		} else {
			$this->event_location_phone_fields        = $this->civicrm->event_location->get_phone_fields( 'create' );
			$transient['event_location_phone_fields'] = $this->event_location_phone_fields;
		}

		// Get Phone Types from transient if possible.
		if ( false !== $data && isset( $data['phone_types'] ) ) {
			$this->phone_types = $data['phone_types'];
		} else {
			$this->phone_types        = $this->plugin->civicrm->phone->phone_types_get();
			$transient['phone_types'] = $this->phone_types;
		}

		// ---------------------------------------------------------------------

		// Get the Event Registration Fields from transient if possible.
		if ( false !== $data && isset( $data['event_registration_fields'] ) ) {
			$this->event_registration_fields = $data['event_registration_fields'];
		} else {
			$this->event_registration_fields        = $this->civicrm->event_registration->get_settings_fields();
			$transient['event_registration_fields'] = $this->event_registration_fields;
		}

		// Get the Event Registration Screen Fields from transient if possible.
		if ( false !== $data && isset( $data['event_registration_screen_fields'] ) ) {
			$this->event_registration_screen_fields = $data['event_registration_screen_fields'];
		} else {
			$this->event_registration_screen_fields        = $this->civicrm->event_registration->get_register_screen_fields();
			$transient['event_registration_screen_fields'] = $this->event_registration_screen_fields;
		}

		// Get the Event Registration Confirmation Screen Fields from transient if possible.
		if ( false !== $data && isset( $data['event_confirm_screen_fields'] ) ) {
			$this->event_confirm_screen_fields = $data['event_confirm_screen_fields'];
		} else {
			$this->event_confirm_screen_fields        = $this->civicrm->event_registration->get_confirm_screen_fields();
			$transient['event_confirm_screen_fields'] = $this->event_confirm_screen_fields;
		}

		// Get the Event Registration Thank You Screen Fields from transient if possible.
		if ( false !== $data && isset( $data['event_thankyou_screen_fields'] ) ) {
			$this->event_thankyou_screen_fields = $data['event_thankyou_screen_fields'];
		} else {
			$this->event_thankyou_screen_fields        = $this->civicrm->event_registration->get_thankyou_screen_fields();
			$transient['event_thankyou_screen_fields'] = $this->event_thankyou_screen_fields;
		}

		// Get the Event Registration Confirmation Email Fields from transient if possible.
		if ( false !== $data && isset( $data['event_confirmation_email_fields'] ) ) {
			$this->event_confirmation_email_fields = $data['event_confirmation_email_fields'];
		} else {
			$this->event_confirmation_email_fields        = $this->civicrm->event_registration->get_confirmation_email_fields();
			$transient['event_confirmation_email_fields'] = $this->event_confirmation_email_fields;
		}

		// ---------------------------------------------------------------------

		// Get the Custom Groups and Fields from transient if possible.
		if ( false !== $data && isset( $data['custom_fields'] ) ) {
			$this->custom_fields = $data['custom_fields'];
		} else {
			$this->custom_fields        = $this->plugin->civicrm->custom_group->get_for_entity_type( 'Event', '', true );
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

		// Finally, let's try and cache queries made in tabs.

		// Get Event Type choices from transient if possible.
		if ( false !== $data && isset( $data['event_type_choices'] ) ) {
			$this->event_type_choices = $data['event_type_choices'];
		} else {
			$this->event_type_choices        = $this->civicrm->event_type->choices_get();
			$transient['event_type_choices'] = $this->event_type_choices;
		}

		// Get Event Location choices from transient if possible.
		if ( false !== $data && isset( $data['event_location_choices'] ) ) {
			$this->event_location_choices = $data['event_location_choices'];
		} else {
			$this->event_location_choices        = $this->civicrm->event_location->get_all();
			$transient['event_location_choices'] = $this->event_location_choices;
		}

		// Get Participant Role choices from transient if possible.
		if ( false !== $data && isset( $data['participant_role_choices'] ) ) {
			$this->participant_role_choices = $data['participant_role_choices'];
		} else {
			$this->participant_role_choices        = $this->civicrm->participant_role->choices_get();
			$transient['participant_role_choices'] = $this->participant_role_choices;
		}

		// Get Participant Listing choices from transient if possible.
		if ( false !== $data && isset( $data['participant_listing_choices'] ) ) {
			$this->participant_listing_choices = $data['participant_listing_choices'];
		} else {
			$this->participant_listing_choices        = $this->civicrm->event_field->options_get( 'participant_listing_id' );
			$transient['participant_listing_choices'] = $this->participant_listing_choices;
		}

		// Get Profile choices from transient if possible.
		if ( false !== $data && isset( $data['profile_choices'] ) ) {
			$this->profile_choices = $data['profile_choices'];
		} else {
			$this->profile_choices        = $this->civicrm->event_registration->profiles_options_get();
			$transient['profile_choices'] = $this->profile_choices;
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
	public function validate_action( $form, $action ) {

		// Skip if the Contact Conditional Reference Field has a value.
		$this->form_conditional_populate( [ 'action' => &$action ] );
		if ( ! $this->form_conditional_check( [ 'action' => $action ] ) ) {
			return;
		}

		// Validate the Event data.
		$valid = $this->form_event_validate( $form, $action );
		if ( ! $valid ) {
			return;
		}

		// Validate the Event Registration data.
		$valid = $this->form_registration_validate( $form, $action );
		if ( ! $valid ) {
			return;
		}

		// Validate the Event Location data.
		$valid = $this->form_locblock_validate( $form, $action );
		if ( ! $valid ) {
			return;
		}

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

		// Populate Event and Registration data arrays.
		$event        = $this->form_event_data( $form, $action );
		$registration = $this->form_registration_data( $form, $action );

		// Build Contact Custom Field args.
		$args = [
			'custom_groups' => $this->custom_fields,
		];

		// Get populated Custom Field data array.
		$custom_fields = $this->form_entity_custom_fields_data( $action['event'], $args );

		// First save the LocBlock with the data from the Form.
		$locblock           = $this->form_locblock_data( $form, $action );
		$result['location'] = $this->form_locblock_save( $locblock );

		// Save the Event with the data from the Form.
		$result['event'] = $this->form_event_save( $event, $custom_fields, $result['location'], $registration );

		// If we get an Event.
		if ( ! empty( $result['event'] ) ) {

			// Post-process Custom Fields now that we have an Event.
			$this->form_entity_custom_fields_post_process( $form, $action, $result['event'], 'event' );

			// Maybe enable Registration.
			$result['profiles'] = $this->form_registration_save( $result['event'], $registration );

			// Save the Event ID for backwards compatibility.
			$result['id'] = (int) $result['event']['id'];

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

		// Configure Conditional Field.
		$args = [
			'placeholder'  => __( 'Always add', 'civicrm-wp-profile-sync' ),
			'instructions' => __( 'To add the Event only when a Form Field is populated (e.g. "Title") link this to the Form Field. To add the Event only when more complex conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' ),
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
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_settings_add() {

		// Init return.
		$fields = [];

		// "Event Settings" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_settings_open',
			'label'             => __( 'Event Settings', 'civicrm-wp-profile-sync' ),
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

		// Add Settings Fields.
		foreach ( $this->settings_fields as $field ) {

			// Retrieve the choices.
			switch ( $field['name'] ) {
				case 'event_type_id':
					$choices = $this->event_type_choices;
					break;
				case 'default_role_id':
					$choices = $this->participant_role_choices;
					break;
				case 'participant_listing_id':
					$choices  = [ 'disabled' => __( 'Disabled', 'civicrm-wp-profile-sync' ) ];
					$choices += $this->participant_listing_choices;
					break;
				case 'campaign_id':
					// Skip Campaign Field if the CiviCampaign component is not active.
					$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
					if ( ! $campaign_active ) {
						continue 2;
					}
					$choices  = [ 'none' => __( 'None', 'civicrm-wp-profile-sync' ) ];
					$choices  = [ '' => __( 'Select', 'civicrm-wp-profile-sync' ) ];
					$choices += $this->campaign_choices;
					break;
			}

			// Define Setting Field.
			$args = [
				'field_name'  => $field['name'],
				'field_title' => $field['title'],
				'choices'     => $choices,
			];

			// Add Settings Group.
			$fields[] = $this->form_setting_group_get( $args );

		}

		// Add Contact Reference Fields.
		foreach ( $this->fields_for_contacts as $field ) {

			// Bundle them into a container group.
			$contact_group_field = [
				'key'          => $this->field_key . 'contact_group_' . $field['name'],
				'label'        => $field['title'],
				'name'         => 'contact_group_' . $field['name'],
				'type'         => 'group',
				/* translators: %s: The name of the Field */
				'instructions' => sprintf( __( 'Use one Field to identify the %s. Defaults to logged-in Contact.', 'civicrm-wp-profile-sync' ), $field['title'] ),
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
			$title                              = __( 'Custom Contact Reference', 'civicrm-wp-profile-sync' );
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

		// "Event Settings" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_settings_close',
			'label'             => __( 'Event Settings', 'civicrm-wp-profile-sync' ),
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
	 * Defines the Fields in the "Event Fields" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_event_add() {

		// Init return.
		$fields = [];

		// "Event Fields" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_event_open',
			'label'             => __( 'Event Fields', 'civicrm-wp-profile-sync' ),
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
		foreach ( $this->public_event_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->event_fields_to_ignore ) ) {
				$fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Event Fields" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_event_close',
			'label'             => __( 'Event Fields', 'civicrm-wp-profile-sync' ),
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
	private function tab_mapping_accordion_custom_add() {

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
						'field'    => $this->field_key . 'event_types',
						'operator' => '==contains',
						'value'    => $event_type_id,
					];

					$conditional_logic[] = [
						$event_type,
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
	 * Defines the "Location" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_location_add() {

		// Init return.
		$fields = [];

		// "Event Location" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_location_open',
			'label'             => __( 'Event Location', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// Define "Show Location" setting Field.
		$args = [
			'field_name'  => 'is_show_location',
			'field_title' => __( 'Show Location', 'civicrm-wp-profile-sync' ),
			'extra'       => __( 'Disable to make the Location available to Event Administrators only.', 'civicrm-wp-profile-sync' ),
			'choices'     => [
				'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
				'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add "Show Location" Group.
		$fields[] = $this->form_setting_group_get( $args );

		// ---------------------------------------------------------------------

		// Define "Existing Location" setting Field.
		$args = [
			'field_name'  => 'existing_location',
			'field_title' => __( 'Existing Location', 'civicrm-wp-profile-sync' ),
			'extra'       => __( 'You cannot map a new Location if you choose an existing one.', 'civicrm-wp-profile-sync' ),
			'choices'     => $this->event_location_choices,
			// 'lazy_load'   => 1,
		];

		// Add "Existing Location" Group.
		$fields[] = $this->form_setting_group_get( $args );

		// ---------------------------------------------------------------------

		// Bundle the "New Location" Fields into a container group.
		$location_group_field = [
			'key'                   => $this->field_key . 'location_group',
			'label'                 => __( 'New Location', 'civicrm-wp-profile-sync' ),
			'name'                  => 'location_group',
			'type'                  => 'group',
			'instructions'          => '',
			'instruction_placement' => 'field',
			'required'              => 0,
			'layout'                => 'block',
			'conditional_logic'     => [
				[
					[
						'field'    => $this->field_key . 'value_existing_location',
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
			'key'               => $this->field_key . 'address_fields_open',
			'label'             => __( 'Address', 'civicrm-wp-profile-sync' ),
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

		// Add "Address Mapping" Fields.
		foreach ( $this->event_location_address_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->location_fields_to_ignore ) ) {
				$sub_fields[] = $this->mapping_field_get( 'address_' . $field['name'], $field['title'] );
			}
		}

		// Configure Conditional Field.
		$args = [
			'name'         => 'location_conditional',
			'placeholder'  => __( 'Always add', 'civicrm-wp-profile-sync' ),
			'instructions' => __( 'To add the Address to the Location only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' ),
		];

		// Add Conditional Field.
		$sub_fields[] = $this->form_conditional_field_get( $args );

		// "Address" Accordion wrapper close.
		$sub_fields[] = [
			'key'               => $this->field_key . 'address_fields_close',
			'label'             => __( 'Address', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// Maybe add Custom Fields Accordion to Sub-fields.
		if ( ! empty( $this->address_custom_fields ) ) {

			// "Custom Fields" Accordion wrapper open.
			$sub_fields[] = [
				'key'               => $this->field_key . 'address_custom_open',
				'label'             => __( 'Address Custom Fields', 'civicrm-wp-profile-sync' ),
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
			foreach ( $this->address_custom_fields as $key => $custom_group ) {

				// Skip if there are no Custom Fields.
				if ( empty( $custom_group['api.CustomField.get']['values'] ) ) {
					continue;
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
					'conditional_logic'     => 0,
					'sub_fields'            => [],
				];

				// Add "Map" Fields for the Custom Fields.
				foreach ( $custom_group['api.CustomField.get']['values'] as $custom_field ) {
					$code                               = 'custom_' . $custom_field['id'];
					$custom_group_field['sub_fields'][] = $this->mapping_field_get( $code, $custom_field['label'] );
				}

				// Add the Field.
				$sub_fields[] = $custom_group_field;

			}

			// "Custom Fields" Accordion wrapper close.
			$sub_fields[] = [
				'key'               => $this->field_key . 'address_custom_close',
				'label'             => __( 'Address Custom Fields', 'civicrm-wp-profile-sync' ),
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

		}

		// ---------------------------------------------------------------------

		// "Emails" Accordion wrapper open.
		$sub_fields[] = [
			'key'               => $this->field_key . 'email_fields_open',
			'label'             => __( 'Email Addresses', 'civicrm-wp-profile-sync' ),
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

		// Define the Emails Repeater Field.
		$email_fields_repeater = [
			'key'                           => $this->field_key . 'email_fields_repeater',
			// 'label' => __( 'Email Addresses', 'civicrm-wp-profile-sync' ),
			'name'                          => 'email_fields_repeater',
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
			'collapsed'                     => $this->field_key . 'email',
			'min'                           => 0,
			'max'                           => 2,
			'layout'                        => 'block',
			'button_label'                  => __( 'Add Email', 'civicrm-wp-profile-sync' ),
			'sub_fields'                    => [],
		];

		// Init Sub-Fields.
		$email_fields_repeater_sub_fields = [];

		// Add "Email Mapping" Fields.
		foreach ( $this->event_location_email_fields as $field ) {
			$email_fields_repeater_sub_fields[] = $this->mapping_field_get( $field['name'], $field['title'] );
		}

		// Build Conditional Field.
		$code                                     = 'email_fields_conditional';
		$label                                    = __( 'Conditional On', 'civicrm-wp-profile-sync' );
		$email_fields_conditional                 = $this->mapping_field_get( $code, $label );
		$email_fields_conditional['placeholder']  = __( 'Always add', 'civicrm-wp-profile-sync' );
		$email_fields_conditional['instructions'] = __( 'To add the Email to the Location only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' );

		// Add Conditional Field to Repeater's Sub-Fields.
		$email_fields_repeater_sub_fields[] = $email_fields_conditional;

		// Add to Repeater.
		$email_fields_repeater['sub_fields'] = $email_fields_repeater_sub_fields;

		// Add Repeater to Sub-fields.
		$sub_fields[] = $email_fields_repeater;

		// "Emails" Accordion wrapper close.
		$sub_fields[] = [
			'key'               => $this->field_key . 'email_fields_close',
			'label'             => __( 'Emails', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Phone Numbers" Accordion wrapper open.
		$sub_fields[] = [
			'key'               => $this->field_key . 'phone_fields_open',
			'label'             => __( 'Phone Numbers', 'civicrm-wp-profile-sync' ),
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

		// Define the Phones Repeater Field.
		$phone_fields_repeater = [
			'key'                           => $this->field_key . 'phone_fields_repeater',
			// 'label' => __( 'Phone Numbers', 'civicrm-wp-profile-sync' ),
			'name'                          => 'phone_fields_repeater',
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
			'collapsed'                     => $this->field_key . 'phone',
			'min'                           => 0,
			'max'                           => 2,
			'layout'                        => 'block',
			'button_label'                  => __( 'Add Phone', 'civicrm-wp-profile-sync' ),
			'sub_fields'                    => [],
		];

		// Init Sub-Fields.
		$phone_fields_repeater_sub_fields = [];

		// ---------------------------------------------------------------------

		// Define "Phone Type" setting Field.
		$args = [
			'field_name'  => 'phone_type_id',
			'field_title' => __( 'Phone Type', 'civicrm-wp-profile-sync' ),
			'choices'     => $this->phone_types,
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

		// Configure Conditional Field.
		$args = [
			'name'         => 'phone_fields_conditional',
			'placeholder'  => __( 'Always add', 'civicrm-wp-profile-sync' ),
			'instructions' => __( 'To add the Phone to the Location only when conditions are met, link this to a Hidden Field with value "1" where the conditional logic of that Field shows it when the conditions are met.', 'civicrm-wp-profile-sync' ),
		];

		// Add Field to Repeater's Sub-Fields.
		$phone_fields_repeater_sub_fields[] = $this->form_conditional_field_get( $args );

		// Add to Repeater.
		$phone_fields_repeater['sub_fields'] = $phone_fields_repeater_sub_fields;

		// Add Repeater to Sub-fields.
		$sub_fields[] = $phone_fields_repeater;

		// "Phone Numbers" Accordion wrapper close.
		$sub_fields[] = [
			'key'               => $this->field_key . 'phone_fields_close',
			'label'             => __( 'Phone Numbers', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// Add the Sub-fields.
		$location_group_field['sub_fields'] = $sub_fields;

		// Add the Field.
		$fields[] = $location_group_field;

		// ---------------------------------------------------------------------

		// "Location" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_location_close',
			'label'             => __( 'Location', 'civicrm-wp-profile-sync' ),
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
	 * Defines the "Registration" Accordion.
	 *
	 * @since 0.7.0
	 *
	 * @return array $fields The array of Fields for this section.
	 */
	private function tab_mapping_accordion_registration_add() {

		// Init return.
		$fields = [];

		// "Event Registration" Accordion wrapper open.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_registration_open',
			'label'             => __( 'Event Registration', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// Define "Online Registration" setting Field.
		$args = [
			'field_name'  => 'is_online_registration',
			'field_title' => __( 'Allow Online Registration', 'civicrm-wp-profile-sync' ),
			'choices'     => [
				'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
				'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add "Online Registration" Group.
		$fields[] = $this->form_setting_group_get( $args );

		// ---------------------------------------------------------------------

		// "Online Registration Configuration" container group.
		$container_group_field = [
			'key'               => $this->field_key . 'registration_settings',
			'label'             => __( 'Online Registration Configuration', 'civicrm-wp-profile-sync' ),
			'name'              => 'registration_settings',
			'type'              => 'group',
			'instructions'      => '',
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'required'          => 0,
			'layout'            => 'block',
			'conditional_logic' => [
				[
					[
						'field'    => $this->field_key . 'value_is_online_registration',
						'operator' => '!=',
						'value'    => '0',
					],
				],
			],
		];

		// Init Sub-fields.
		$container_group_field['sub_fields'] = [];

		// ---------------------------------------------------------------------

		// "Components" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_registration_components_open',
			'label'             => __( 'Enable Components', 'civicrm-wp-profile-sync' ),
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

		// Define "Confirmation Screen" setting Field.
		$args = [
			'field_name'  => 'is_confirm_enabled',
			'field_title' => __( 'Enable Confirmation Screen', 'civicrm-wp-profile-sync' ),
			'choices'     => [
				'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
				'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add "Confirmation Screen" Group.
		$container_group_field['sub_fields'][] = $this->form_setting_group_get( $args );

		// Define "Confirmation Email" setting Field.
		$args = [
			'field_name'  => 'is_email_confirm',
			'field_title' => __( 'Send Confirmation Email', 'civicrm-wp-profile-sync' ),
			'choices'     => [
				'1' => __( 'Yes', 'civicrm-wp-profile-sync' ),
				'0' => __( 'No', 'civicrm-wp-profile-sync' ),
			],
		];

		// Add "Confirmation Email" Group.
		$container_group_field['sub_fields'][] = $this->form_setting_group_get( $args );

		// "Components" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_registration_components_close',
			'label'             => __( 'Enable Components', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Settings" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_registration_settings_open',
			'label'             => __( 'Settings', 'civicrm-wp-profile-sync' ),
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

		// Add "Event Registration Mapping" Fields.
		foreach ( $this->event_registration_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_fields_to_ignore ) ) {
				$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Settings" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_registration_settings_close',
			'label'             => __( 'Settings', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Registration Screen" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_registration_screen_settings_open',
			'label'             => __( 'Registration Screen', 'civicrm-wp-profile-sync' ),
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

		// Add "Event Registration Screen Mapping" Fields.
		foreach ( $this->event_registration_screen_fields as $field ) {
			$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
		}

		// Define "Include Profile Top" setting Field.
		$args = [
			'field_name'  => 'custom_pre_id',
			'field_title' => __( 'Include Profile (top of page)', 'civicrm-wp-profile-sync' ),
			'choices'     => $this->profile_choices,
		];

		// Add "Include Profile Top" Group.
		$container_group_field['sub_fields'][] = $this->form_setting_group_get( $args );

		// Define "Include Profile Bottom" setting Field.
		$args = [
			'field_name'  => 'custom_post_id',
			'field_title' => __( 'Include Profile (bottom of page)', 'civicrm-wp-profile-sync' ),
			'choices'     => $this->profile_choices,
		];

		// Add "Include Profile Bottom" Group.
		$container_group_field['sub_fields'][] = $this->form_setting_group_get( $args );

		// "Registration Screen" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_registration_screen_settings_close',
			'label'             => __( 'Registration Screen', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Confirmation Screen" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_confirm_screen_settings_open',
			'label'             => __( 'Confirmation Screen', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => $this->field_key . 'value_is_confirm_enabled',
						'operator' => '!=',
						'value'    => '0',
					],
				],
			],
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

		// Add "Confirmation Screen Mapping" Fields.
		foreach ( $this->event_confirm_screen_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->confirm_fields_to_ignore ) ) {
				$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// "Confirmation Screen" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_confirm_screen_settings_close',
			'label'             => __( 'Confirmation Screen', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => $this->field_key . 'value_is_confirm_enabled',
						'operator' => '!=',
						'value'    => '0',
					],
				],
			],
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

		// ---------------------------------------------------------------------

		// "Thank You Screen" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_thankyou_screen_settings_open',
			'label'             => __( 'Thank You Screen', 'civicrm-wp-profile-sync' ),
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

		// Add "Thank You Screen Mapping" Fields.
		foreach ( $this->event_thankyou_screen_fields as $field ) {
			$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
		}

		// "Thank You Screen" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_thankyou_screen_settings_close',
			'label'             => __( 'Thank You Screen', 'civicrm-wp-profile-sync' ),
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

		// ---------------------------------------------------------------------

		// "Confirmation Email" Accordion wrapper open.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_confirmation_email_settings_open',
			'label'             => __( 'Confirmation Email', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => $this->field_key . 'value_is_email_confirm',
						'operator' => '!=',
						'value'    => '0',
					],
				],
			],
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

		// Add "Confirmation Email Mapping" Fields.
		foreach ( $this->event_confirmation_email_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_email_fields_to_ignore ) ) {
				$container_group_field['sub_fields'][] = $this->mapping_field_get( $field['name'], $field['title'] );
			}
		}

		// TODO: Conditional Field?

		// "Confirmation Email" Accordion wrapper close.
		$container_group_field['sub_fields'][] = [
			'key'               => $this->field_key . 'mapping_accordion_confirmation_email_settings_close',
			'label'             => __( 'Confirmation Email', 'civicrm-wp-profile-sync' ),
			'name'              => '',
			'type'              => 'accordion',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => $this->field_key . 'value_is_email_confirm',
						'operator' => '!=',
						'value'    => '0',
					],
				],
			],
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

		// Add Group to Fields.
		$fields[] = $container_group_field;

		// ---------------------------------------------------------------------

		// "Registration" Accordion wrapper close.
		$fields[] = [
			'key'               => $this->field_key . 'mapping_accordion_registration_close',
			'label'             => __( 'Event Registration', 'civicrm-wp-profile-sync' ),
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
	 * Builds Event data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Event data.
	 */
	private function form_event_data( $form, $action ) {

		// Init data array.
		$data = [];

		// Set ACFE "context". We want to apply tags.
		acfe_add_context( $this->context_save );

		// Build Fields array.
		foreach ( $this->public_event_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->event_fields_to_ignore ) ) {
				acfe_apply_tags( $action['event'][ $field['name'] ] );
				$data[ $field['name'] ] = $action['event'][ $field['name'] ];
			}
		}

		// Reset the ACFE "context".
		acfe_delete_context( array_keys( $this->context_save ) );

		// Get the Event Settings.
		foreach ( $this->settings_fields as $field ) {

			// Skip Campaign Field if the CiviCampaign component is not active.
			if ( 'campaign_id' === $field['name'] ) {
				$campaign_active = $this->civicrm->is_component_enabled( 'CiviCampaign' );
				if ( ! $campaign_active ) {
					continue;
				}
			}

			// Get Setting value.
			$setting_value = $this->form_setting_value_get( $field['name'], $action['event'] );

			// Participant Listing Field needs special handling.
			if ( 'participant_listing_id' === $field['name'] && 'disabled' === $setting_value ) {
				$setting_value = '';
			}

			// Assign to data.
			$data[ $field['name'] ] = $setting_value;

		}

		// Get the Event Contacts.
		foreach ( $this->fields_for_contacts as $field ) {

			// Get Group Field.
			$contact_group_field = $action['event'][ 'contact_group_' . $field['name'] ];

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
				$data[ $field['name'] ] = $contact_id;
			}

		}

		// --<
		return $data;

	}

	/**
	 * Validates the Event data array from mapped Fields.
	 *
	 * @@since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Event can be saved, false otherwise.
	 */
	private function form_event_validate( $form, $action ) {

		// Get the Event.
		$event = $this->form_event_data( $form, $action );

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
		// Reject the submission if there is no Creator Contact ID.
		if ( empty( $event['creator_id'] ) ) {
			acfe_add_validation_error( '', sprintf(
				// / * translators: %s The name of the Form Action * /
				__( 'A Contact ID is required to create an Event in "%s".', 'civicrm-wp-profile-sync' ),
				$action['name']
			) );
			return false;
		}
		*/

		// Reject the submission if the Event Type ID is missing.
		if ( empty( $event['event_type_id'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'An Event Type ID is required to create an Event in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Reject the submission if the Event Title is missing.
		if ( empty( $event['title'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A title is required to create an Event in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Reject the submission if the Event Start Date is missing.
		if ( empty( $event['start_date'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A start date is required to create an Event in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Valid.
		return true;

	}

	/**
	 * Saves the CiviCRM Event given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $event_data The array of Event data.
	 * @param array $custom_data The array of Custom Field data.
	 * @param array $locblock_data The array of LocBlock data.
	 * @param array $registration The array of Registration data.
	 * @return array $event The Event data array, or empty on failure.
	 */
	private function form_event_save( $event_data, $custom_data, $locblock_data, $registration ) {

		// Init return.
		$event = [];

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

		// Strip out empty Fields.
		$event_data = $this->form_data_prepare( $event_data );

		/*
		 * Event "Is Public" defaults to "1" but is not present in the API return
		 * values when not explicitly set. This causes, for example, CEO to create
		 * a "Private" Event in Event Organiser. We define it here when not set.
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
		if ( false === $result ) {
			return $event;
		}

		// Get the full Event data.
		$event = $this->civicrm->event->get_by_id( $result['id'] );

		// --<
		return $event;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Builds LocBlock data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of LocBlock data.
	 */
	private function form_locblock_data( $form, $action ) {

		// Init return.
		$data = [];

		// Get "Show Location" value.
		$field_name          = 'is_show_location';
		$setting_value       = $this->form_setting_value_get( $field_name, $action['location'] );
		$data[ $field_name ] = $setting_value;

		// Get "Existing Location" value.
		$field_name           = 'existing_location';
		$existing_location_id = $this->form_setting_value_get( $field_name, $action['location'] );

		// No need to go further if there's an existing Location.
		if ( ! empty( $existing_location_id ) ) {
			$data['id'] = (int) $existing_location_id;
			return $data;
		}

		// Get the data that comprises the LocBlock.
		$address_data = $this->form_locblock_address_data( $form, $action );
		$email_data   = $this->form_locblock_email_data( $form, $action );
		$phone_data   = $this->form_locblock_phone_data( $form, $action );

		// Maybe add Address.
		if ( ! empty( $address_data ) ) {
			$address = $this->form_locblock_address_add( $address_data );
			if ( ! empty( $address ) ) {
				$data['address'] = $address;
			}
		}

		// Maybe add Email(s).
		if ( ! empty( $email_data ) ) {
			$emails = $this->form_locblock_email_add( $email_data );
			if ( ! empty( $emails ) ) {
				foreach ( $emails as $index => $email ) {
					if ( ! empty( $email ) ) {
						$data[ $index ] = $email;
					}
				}
			}
		}

		// Maybe add Phone(s).
		if ( ! empty( $phone_data ) ) {
			$phones = $this->form_locblock_phone_add( $phone_data );
			if ( ! empty( $phones ) ) {
				foreach ( $phones as $index => $phone ) {
					if ( ! empty( $phone ) ) {
						$data[ $index ] = $phone;
					}
				}
			}
		}

		// --<
		return $data;

	}

	/**
	 * Validates the Event LocBlock data array from mapped Fields.
	 *
	 * @@since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the LocBlock can be saved, false otherwise.
	 */
	private function form_locblock_validate( $form, $action ) {

		// Get the Event LocBlock data.
		$data = $this->form_locblock_data( $form, $action );

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
	 * @since 0.7.0
	 *
	 * @param array $locblock_data The array of LocBlock data.
	 * @return array $data The array of LocBlock data, or empty on failure.
	 */
	private function form_locblock_save( $locblock_data ) {

		// Init return.
		$data = [];

		// Bail if there's no LocBlock data.
		if ( empty( $locblock_data ) ) {
			return $data;
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
		if ( false === $result ) {
			return $data;
		}

		/*
		 * Use the API return value.
		 *
		 * THe API return contains all saved data, including details of all the related
		 * Entities, whereas a simple call to the API only returns the ID and IDs of the
		 * related Entities.
		 *
		 * The Location Address does *not*, however, contain the Custom Fields that have
		 * been saved along with the Address. We could backfill here, but it is probably
		 * simpler to retrieve those when needed.
		 */
		$data = $result;

		// --<
		return $data;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Builds Address data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Address data.
	 */
	private function form_locblock_address_data( $form, $action ) {

		// Init data array.
		$data = [];

		// Define key for this Entity.
		$entity_key = 'location';

		// Skip if the Group is empty.
		if ( empty( $action[ $entity_key ] ) ) {
			return $data;
		}

		// Get the Location Group Field.
		$location_group_field = $action[ $entity_key ]['new_location'];
		if ( empty( $location_group_field ) ) {
			return $data;
		}

		// Set ACFE "context". We want to apply tags.
		acfe_add_context( $this->context_save );

		// Build Fields array.
		foreach ( $this->event_location_address_fields as $field ) {
			acfe_apply_tags( $location_group_field[ 'address_' . $field['name'] ] );
			$data[ $field['name'] ] = $location_group_field[ 'address_' . $field['name'] ];
		}

		// Reset the ACFE "context".
		acfe_delete_context( array_keys( $this->context_save ) );

		// Build Custom Fields args.
		$args = [
			'custom_groups' => $this->address_custom_fields,
		];

		// Maybe add Custom Fields.
		$custom_fields = $this->form_entity_custom_fields_data( $location_group_field, $args );
		if ( ! empty( $custom_fields ) ) {
			$data += $custom_fields;
		}

		// Build Conditional Field args.
		$conditional_args = [
			'action' => &$location_group_field,
			'key'    => $entity_key . '_conditional',
		];

		// Populate Conditional Reference and value.
		$this->form_conditional_populate( $conditional_args );

		// Get Conditional.
		$data[ $entity_key . '_conditional' ] = $location_group_field[ $entity_key . '_conditional' ];

		// Save Conditional Reference.
		$data[ $entity_key . '_conditional_ref' ] = $location_group_field[ $entity_key . '_conditional_ref' ];

		// --<
		return $data;

	}

	/**
	 * Validates the Event LocBlock Address data array from mapped Fields.
	 *
	 * @@since 0.7.0
	 *
	 * @param array  $data The array of Event LocBlock Address data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the LocBlock Address can be saved, false otherwise.
	 */
	private function form_locblock_address_validate( $data, $action ) {

		// Build Conditional Check args.
		$args = [
			'action' => $data,
			'key'    => 'location_conditional',
		];

		// Skip if the Conditional Reference Field says so.
		if ( ! $this->form_conditional_check( $args ) ) {
			return true;
		}

		// CiviCRM Event Organiser requires a "Street Address".
		if ( defined( 'CIVICRM_WP_EVENT_ORGANISER_VERSION' ) ) {
			if ( empty( $data['street_address'] ) ) {
				acfe_add_validation_error(
					'',
					sprintf(
						/* translators: %s The name of the Form Action */
						__( 'A Street Address is required in "%s".', 'civicrm-wp-profile-sync' ),
						$action['name']
					)
				);
				return false;
			}
		}

		// CiviCRM Addresses seem not to require data.
		return true;

	}

	/**
	 * Adds the CiviCRM Address given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $address_data The array of Address data.
	 * @return array $addresses The array of Address data, or empty on failure.
	 */
	private function form_locblock_address_add( $address_data ) {

		// Init return.
		$address = [];

		// Bail if there's no Address data.
		if ( empty( $address_data ) ) {
			return $address;
		}

		// Build Conditional Check args.
		$args = [
			'action' => $address_data,
			'key'    => 'location_conditional',
		];

		// Skip if the Conditional Reference Field says so.
		if ( ! $this->form_conditional_check( $args ) ) {
			return $address;
		}

		// Strip out empty Fields.
		$address_data = $this->form_data_prepare( $address_data );

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
		unset( $address_data['location_conditional'] );
		unset( $address_data['location_conditional_ref'] );

		// --<
		return $address_data;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Builds Email data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Email data.
	 */
	private function form_locblock_email_data( $form, $action ) {

		// Init return.
		$data = [
			'email'   => [],
			'email_2' => [],
		];

		// Get the Location Group Field.
		$location_group_field = $action['location']['new_location'];
		if ( empty( $location_group_field ) ) {
			return $data;
		}

		// Get the Email Repeater Field.
		$email_repeater = $location_group_field['email_fields_repeater'];
		if ( empty( $email_repeater ) ) {
			return $data;
		}

		// Init key.
		$key = 'email';

		// Loop through the Action Fields.
		foreach ( $email_repeater as $field ) {

			// Init Fields.
			$item = [];

			// Set ACFE "context". We want to apply tags.
			acfe_add_context( $this->context_save );

			// Get mapped Fields.
			foreach ( $this->event_location_email_fields as $email_field ) {
				acfe_apply_tags( $field[ $email_field['name'] ] );
				$item[ $email_field['name'] ] = $field[ $email_field['name'] ];
			}

			// Reset the ACFE "context".
			acfe_delete_context( array_keys( $this->context_save ) );

			// Build Conditional Field args.
			$conditional_args = [
				'action' => &$field,
				'key'    => 'email_fields_conditional',
			];

			// Populate Conditional Reference and value.
			$this->form_conditional_populate( $conditional_args );

			// Get Conditional.
			$item['email_fields_conditional'] = $field['email_fields_conditional'];

			// Save Conditional Reference.
			$item['email_fields_conditional_ref'] = $field['email_fields_conditional_ref'];

			// Add the data.
			$data[ $key ] = $item;

			// The second item needs a suffix.
			$key = 'email_2';

		}

		// --<
		return $data;

	}

	/**
	 * Validates the Event LocBlock Email data array from mapped Fields.
	 *
	 * @@since 0.7.0
	 *
	 * @param array  $data The array of Event LocBlock Email data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the LocBlock Address can be saved, false otherwise.
	 */
	private function form_locblock_email_validate( $data, $action ) {

		// Skip it if there's no data.
		if ( empty( $data ) ) {
			return true;
		}

		// Build Conditional Check args.
		$args = [
			'action' => $data,
			'key'    => 'email_fields_conditional',
		];

		// Skip if the Conditional Reference Field says so.
		if ( ! $this->form_conditional_check( $args ) ) {
			return true;
		}

		// Reject if there's an invalid Email.
		if ( ! empty( $data['email'] ) && ! is_email( $data['email'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'An invalid Email was found in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Valid.
		return true;

	}

	/**
	 * Adds the CiviCRM Email(s) given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $email_data The array of Email data.
	 * @return array $emails The array of Emails, or empty on failure.
	 */
	private function form_locblock_email_add( $email_data ) {

		// Init return.
		$emails = [];

		// Bail if there's no Email data.
		if ( empty( $email_data ) ) {
			return $emails;
		}

		// Handle each nested Action in turn.
		foreach ( $email_data as $key => $email ) {

			// Build Conditional Check args.
			$args = [
				'action' => $email,
				'key'    => 'email_fields_conditional',
			];

			// Skip if the Conditional Reference Field says so.
			if ( ! $this->form_conditional_check( $args ) ) {
				continue;
			}

			// Strip out empty Fields.
			$email = $this->form_data_prepare( $email );

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

	// -----------------------------------------------------------------------------------

	/**
	 * Builds Phone data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Phone data.
	 */
	private function form_locblock_phone_data( $form, $action ) {

		// Init return.
		$data = [
			'phone'   => [],
			'phone_2' => [],
		];

		// Get the Location Group Field.
		$location_group_field = $action['location']['new_location'];
		if ( empty( $location_group_field ) ) {
			return $data;
		}

		// Get the Phone Repeater Field.
		$phone_repeater = $location_group_field['phone_fields_repeater'];
		if ( empty( $phone_repeater ) ) {
			return $data;
		}

		// Init key.
		$key = 'phone';

		// Loop through the Action Fields.
		foreach ( $phone_repeater as $field ) {

			// Init Fields.
			$item = [];

			// Get "Phone Type" value.
			$field_name          = 'phone_type_id';
			$setting_value       = $this->form_setting_value_get( $field_name, $action, $field );
			$item[ $field_name ] = $setting_value;

			// Set ACFE "context". We want to apply tags.
			acfe_add_context( $this->context_save );

			// Get mapped Fields.
			foreach ( $this->event_location_phone_fields as $phone_field ) {
				if ( ! array_key_exists( $phone_field['name'], $this->phone_fields_to_ignore ) ) {
					acfe_apply_tags( $field[ $phone_field['name'] ] );
					$item[ $phone_field['name'] ] = $field[ $phone_field['name'] ];
				}
			}

			// Reset the ACFE "context".
			acfe_delete_context( array_keys( $this->context_save ) );

			// Build Conditional Field args.
			$conditional_args = [
				'action' => &$field,
				'key'    => 'phone_fields_conditional',
			];

			// Populate Conditional Reference and value.
			$this->form_conditional_populate( $conditional_args );

			// Get Conditional.
			$item['phone_fields_conditional'] = $field['phone_fields_conditional'];

			// Save Conditional Reference.
			$item['phone_fields_conditional_ref'] = $field['phone_fields_conditional_ref'];

			// Add the data.
			$data[ $key ] = $item;

			// Update key for second item.
			$key = 'phone_2';

		}

		// --<
		return $data;

	}

	/**
	 * Validates the Event LocBlock Phone data array from mapped Fields.
	 *
	 * @@since 0.7.0
	 *
	 * @param array  $data The array of Event LocBlock Phone data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the LocBlock Address can be saved, false otherwise.
	 */
	private function form_locblock_phone_validate( $data, $action ) {

		// Skip it if there's no data.
		if ( empty( $data ) ) {
			return true;
		}

		// Build Conditional Check args.
		$args = [
			'action' => $data,
			'key'    => 'phone_fields_conditional',
		];

		// Skip if the Conditional Reference Field says so.
		if ( ! $this->form_conditional_check( $args ) ) {
			return true;
		}

		// Reject if there's no Phone Number.
		if ( empty( $data['phone'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A valid Phone Number is required in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Valid.
		return true;

	}

	/**
	 * Adds the CiviCRM Phone(s) given data from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array $phone_data The array of Phone data.
	 * @return array $phones The array of Phones, or empty on failure.
	 */
	private function form_locblock_phone_add( $phone_data ) {

		// Init return.
		$phones = [];

		// Bail if there's no Phone data.
		if ( empty( $phone_data ) ) {
			return $phones;
		}

		// Handle each nested Action in turn.
		foreach ( $phone_data as $key => $phone ) {

			// Build Conditional Check args.
			$args = [
				'action' => $phone,
				'key'    => 'phone_fields_conditional',
			];

			// Skip if the Conditional Reference Field says so.
			if ( ! $this->form_conditional_check( $args ) ) {
				continue;
			}

			// Strip out empty Fields.
			$phone = $this->form_data_prepare( $phone );

			// Skip if there is no Phone Address to save.
			if ( empty( $phone['phone'] ) ) {
				continue;
			}

			// Remove Conditional Fields.
			unset( $phone['phone_fields_conditional'] );
			unset( $phone['phone_fields_conditional_ref'] );

			// Add the Phone data.
			$phones[ $key ] = $phone;

		}

		// --<
		return $phones;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Builds Registration data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Registration data.
	 */
	private function form_registration_data( $form, $action ) {

		// Init return.
		$data = [];

		// Get "Online Registration" value.
		$field_name          = 'is_online_registration';
		$setting_value       = $this->form_setting_value_get( $field_name, $action['registration'] );
		$data[ $field_name ] = $setting_value;

		// No need to go further if "Online Registration" is disabled.
		if ( empty( $setting_value ) ) {
			$data[ $field_name ] = 0;
			return $data;
		}

		// Get the data that is always present for Registration.
		$settings_data = $this->form_registration_settings_data( $form, $action );
		$register_data = $this->form_registration_screen_data( $form, $action );
		$confirm_data  = $this->form_registration_confirmation_screen_data( $form, $action );
		$thankyou_data = $this->form_registration_thankyou_screen_data( $form, $action );
		$email_data    = $this->form_registration_confirmation_email_data( $form, $action );

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
	 * @@since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Event Registration can be enabled, false otherwise.
	 */
	private function form_registration_validate( $form, $action ) {

		// Get the Event Registration data.
		$data = $this->form_registration_data( $form, $action );

		// Skip if Event Registration is not enabled.
		if ( empty( $data['is_online_registration'] ) ) {
			return true;
		}

		// Reject the submission if no Profile has been selected.
		if ( empty( $data['custom_pre_id'] ) && empty( $data['custom_post_id'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A Profile is required to enable Online Registration in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
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
	 * @since 0.7.0
	 *
	 * @param array $event The array of CiviCRM Event data.
	 * @param array $data The array of Registration data.
	 * @return array $profiles The array of Profile data, or default on failure.
	 */
	private function form_registration_save( $event, $data ) {

		// Init formatted return.
		$profiles = [
			'top'    => false,
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
			if ( false !== $result ) {
				$profiles['top'] = $result;
			}
		}

		// Maybe create the Bottom Profile.
		if ( ! empty( $data['custom_post_id'] ) ) {
			$result = $this->civicrm->event_registration->profile_create( $event, $data['custom_post_id'] );
			if ( false !== $result ) {
				$profiles['bottom'] = $result;
			}
		}

		// --<
		return $profiles;

	}

	/**
	 * Builds Event Registration Settings data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Settings data.
	 */
	private function form_registration_settings_data( $form, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = $action['registration']['registration_settings'];

		// Build Fields array.
		foreach ( $this->event_registration_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_fields_to_ignore ) ) {
				acfe_apply_tags( $group_field[ $field['name'] ], $this->context_save );
				$data[ $field['name'] ] = $group_field[ $field['name'] ];
			}
		}

		// Set some defaults.
		if ( empty( $data['registration_link_text'] ) ) {
			$data['registration_link_text'] = __( 'Register Now', 'civicrm-wp-profile-sync' );
		}

		// --<
		return $data;

	}

	/**
	 * Builds Event Registration Screen data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Registration Screen data.
	 */
	private function form_registration_screen_data( $form, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = $action['registration']['registration_settings'];

		// Build Fields array.
		foreach ( $this->event_registration_screen_fields as $field ) {
			acfe_apply_tags( $group_field[ $field['name'] ], $this->context_save );
			$data[ $field['name'] ] = $group_field[ $field['name'] ];
		}

		// Get "Include Profile Top" value.
		$field_name          = 'custom_pre_id';
		$setting_value       = $this->form_setting_value_get( $field_name, $action, $group_field );
		$data[ $field_name ] = $setting_value;

		// Get "Include Profile Bottom" value.
		$field_name          = 'custom_post_id';
		$setting_value       = $this->form_setting_value_get( $field_name, $action, $group_field );
		$data[ $field_name ] = $setting_value;

		// --<
		return $data;

	}

	/**
	 * Builds Event Registration Confirmation Screen data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Confirmation Screen data.
	 */
	private function form_registration_confirmation_screen_data( $form, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = $action['registration']['registration_settings'];

		// Build Fields array.
		foreach ( $this->event_confirm_screen_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->confirm_fields_to_ignore ) ) {
				acfe_apply_tags( $group_field[ $field['name'] ], $this->context_save );
				$data[ $field['name'] ] = $group_field[ $field['name'] ];
			}
		}

		// Add the "Confirmation Screen Enabled" Field.
		$setting_value              = $this->form_setting_value_get( 'is_confirm_enabled', $action, $group_field );
		$data['is_confirm_enabled'] = $setting_value;

		// --<
		return $data;

	}

	/**
	 * Builds Event Registration Thank You Screen data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Thank You Screen data.
	 */
	private function form_registration_thankyou_screen_data( $form, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = $action['registration']['registration_settings'];

		// Build Fields array.
		foreach ( $this->event_thankyou_screen_fields as $field ) {
			acfe_apply_tags( $group_field[ $field['name'] ], $this->context_save );
			$data[ $field['name'] ] = $group_field[ $field['name'] ];
		}

		// --<
		return $data;

	}

	/**
	 * Builds Event Registration Confirmation Email data array from mapped Fields.
	 *
	 * @since 0.7.0
	 *
	 * @param array  $form The array of Form data.
	 * @param string $action The customised name of the action.
	 * @return array $data The array of Confirmation Email data.
	 */
	private function form_registration_confirmation_email_data( $form, $action ) {

		// Init return.
		$data = [];

		// Get the Settings Group Field.
		$group_field = $action['registration']['registration_settings'];

		// Build Fields array.
		foreach ( $this->event_confirmation_email_fields as $field ) {
			if ( ! array_key_exists( $field['name'], $this->registration_email_fields_to_ignore ) ) {
				acfe_apply_tags( $group_field[ $field['name'] ], $this->context_save );
				$data[ $field['name'] ] = $group_field[ $field['name'] ];
			}
		}

		// Add the "Confirmation Email Enabled" Field.
		$setting_value            = $this->form_setting_value_get( 'is_email_confirm', $action, $group_field );
		$data['is_email_confirm'] = $setting_value;

		// --<
		return $data;

	}

	/**
	 * Validates the Event Registration Confirmation Email data array from mapped Fields.
	 *
	 * @@since 0.7.0
	 *
	 * @param array  $data The array of Event Registration Confirmation Email data.
	 * @param string $action The customised name of the action.
	 * @return bool $valid True if the Confirmation Email can be sent, false otherwise.
	 */
	private function form_registration_confirmation_email_validate( $data, $action ) {

		// Skip if Confirmation Email is disabled.
		if ( empty( $data['is_email_confirm'] ) ) {
			return true;
		}

		// Reject the submission if no "Confirm From Name" has been selected.
		if ( empty( $data['confirm_from_name'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A "From Name" is required to send a Confirmation Email in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Reject the submission if no "Confirm From Email" has been selected.
		if ( empty( $data['confirm_from_email'] ) ) {
			acfe_add_validation_error(
				'',
				sprintf(
					/* translators: %s The name of the Form Action */
					__( 'A "From Email" is required to send a Confirmation Email in "%s".', 'civicrm-wp-profile-sync' ),
					$action['name']
				)
			);
			return false;
		}

		// Valid.
		return true;

	}

}
