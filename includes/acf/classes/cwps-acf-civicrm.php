<?php
/**
 * CiviCRM Class.
 *
 * Handles general CiviCRM functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Class.
 *
 * A class that encapsulates CiviCRM functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM {

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
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * CiviCRM Contact Type object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $contact_type The CiviCRM Contact Type object.
	 */
	public $contact_type;

	/**
	 * CiviCRM Contact object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $contact The CiviCRM Contact object.
	 */
	public $contact;

	/**
	 * CiviCRM Group object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $group The CiviCRM Group object.
	 */
	public $group;

	/**
	 * CiviCRM Membership object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $membership The CiviCRM Membership object.
	 */
	public $membership;

	/**
	 * CiviCRM Email object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $email The CiviCRM Email object.
	 */
	public $email;

	/**
	 * CiviCRM Website object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $website The CiviCRM Website object.
	 */
	public $website;

	/**
	 * CiviCRM Phone object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $phone The CiviCRM Phone object.
	 */
	public $phone;

	/**
	 * CiviCRM Instant Messenger object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $phone The CiviCRM Instant Messenger object.
	 */
	public $im;

	/**
	 * CiviCRM Note object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $note The CiviCRM Note object.
	 */
	public $note;

	/**
	 * CiviCRM Tag object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $tag The CiviCRM Tag object.
	 */
	public $tag;

	/**
	 * CiviCRM Contact ID object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $contact The CiviCRM Contact ID object.
	 */
	public $contact_id;

	/**
	 * CiviCRM Contact Field object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $contact_field The CiviCRM Contact Field object.
	 */
	public $contact_field;

	/**
	 * CiviCRM Custom Field object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $custom_field The CiviCRM Custom Field object.
	 */
	public $custom_field;

	/**
	 * CiviCRM Relationship object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $relationship The CiviCRM Relationship object.
	 */
	public $relationship;

	/**
	 * CiviCRM Address object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $address The CiviCRM Address object.
	 */
	public $address;

	/**
	 * CiviCRM Addresses object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $addresses The CiviCRM Addresses object.
	 */
	public $addresses;

	/**
	 * CiviCRM City object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $address_city The CiviCRM Address City object.
	 */
	public $address_city;

	/**
	 * CiviCRM State object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $address_state The CiviCRM Address State object.
	 */
	public $address_state;

	/**
	 * CiviCRM Google Map object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $google_map The CiviCRM Google Map object.
	 */
	public $google_map;

	/**
	 * CiviCRM Activity Type object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $activity_type The CiviCRM Activity Type object.
	 */
	public $activity_type;

	/**
	 * CiviCRM Activity object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $activity The CiviCRM Activity object.
	 */
	public $activity;

	/**
	 * CiviCRM Activity Field object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $activity_field The CiviCRM Activity Field object.
	 */
	public $activity_field;

	/**
	 * CiviCRM Event object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $event The CiviCRM Event object.
	 */
	public $event;

	/**
	 * CiviCRM Participant Role object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $participant_role The CiviCRM Participant Role object.
	 */
	public $participant_role;

	/**
	 * CiviCRM Participant object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $participant The CiviCRM Participant object.
	 */
	public $participant;

	/**
	 * CiviCRM Participant Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $participant_field The CiviCRM Participant Field object.
	 */
	public $participant_field;

	/**
	 * CiviCRM Campaign object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $campaign The CiviCRM Campaign object.
	 */
	public $campaign;

	/**
	 * CiviCRM Attachment object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object $attachment The CiviCRM Attachment object.
	 */
	public $attachment;

	/**
	 * "CiviCRM Field" Field key in the ACF Field data.
	 *
	 * This "top level" Field key is common to all CiviCRM Entities. The value
	 * of the Field has a prefix which distiguishes the target Entity.
	 *
	 * @see self::custom_field_prefix()
	 * @see self::contact_field_prefix()
	 * @see self::activity_field_prefix()
	 * @see self::participant_field_prefix()
	 *
	 * @since 0.4
	 * @access public
	 * @var string $acf_field_key The key of the "CiviCRM Field" in the ACF Field data.
	 */
	public $acf_field_key = 'field_cacf_civicrm_custom_field';



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Bail if CiviCRM isn't found.
		if ( ! function_exists( 'civi_wp' ) ) {
			return;
		}

		// Store references to objects.
		$this->plugin = $acf_loader->plugin;
		$this->acf_loader = $acf_loader;

		// Init when this plugin is loaded.
		add_action( 'cwps/acf/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/acf/civicrm/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include top-level Entity class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-contact-type.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-contact-field.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-activity-type.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-activity.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-activity-field.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-case-type.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-case.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-case-field.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-event.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-participant-role.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-participant.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-participant-field.php';

		// Include Standalone class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-custom-field.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-group.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-membership.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-note.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-attachment.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-tag.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-campaign.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-address.php';

		// Include Additional Entity class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-base.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-addresses.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-address-city.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-address-state.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-google-map.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-relationship.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-email.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-website.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-phone.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-im.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-contact-id.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Init Contact Type, Contact and Contact Field objects.
		$this->contact_type = new CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Type( $this );
		$this->contact = new CiviCRM_Profile_Sync_ACF_CiviCRM_Contact( $this );
		$this->contact_field = new CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Field( $this );

		// Init Activity Type, Activity and Activity Field objects.
		$this->activity_type = new CiviCRM_Profile_Sync_ACF_CiviCRM_Activity_Type( $this );
		$this->activity = new CiviCRM_Profile_Sync_ACF_CiviCRM_Activity( $this );
		$this->activity_field = new CiviCRM_Profile_Sync_ACF_CiviCRM_Activity_Field( $this );

		// Init Case Type, Case and Case Field objects.
		$this->case_type = new CiviCRM_Profile_Sync_ACF_CiviCRM_Case_Type( $this );
		$this->case = new CiviCRM_Profile_Sync_ACF_CiviCRM_Case( $this );
		$this->case_field = new CiviCRM_Profile_Sync_ACF_CiviCRM_Case_Field( $this );

		// Init Event, Participant Role, Participant and Participant Field objects.
		$this->event = new CiviCRM_Profile_Sync_ACF_CiviCRM_Event( $this );
		$this->participant = new CiviCRM_Profile_Sync_ACF_CiviCRM_Participant( $this );
		$this->participant_field = new CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_Field( $this );
		$this->participant_role = new CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_Role( $this );

		// Init Standalone objects.
		$this->custom_field = new CiviCRM_Profile_Sync_ACF_CiviCRM_Custom_Field( $this );
		$this->group = new CiviCRM_Profile_Sync_ACF_CiviCRM_Group( $this );
		$this->membership = new CiviCRM_Profile_Sync_ACF_CiviCRM_Membership( $this );
		$this->note = new CiviCRM_Profile_Sync_ACF_CiviCRM_Note( $this );
		$this->attachment = new CiviCRM_Profile_Sync_ACF_CiviCRM_Attachment( $this );
		$this->tag = new CiviCRM_Profile_Sync_ACF_CiviCRM_Tag( $this );
		$this->campaign = new CiviCRM_Profile_Sync_ACF_CiviCRM_Campaign( $this );
		$this->address = new CiviCRM_Profile_Sync_ACF_CiviCRM_Address( $this );

		// Init Additional Entity objects.
		$this->addresses = new CiviCRM_Profile_Sync_ACF_CiviCRM_Addresses( $this );
		$this->address_city = new CiviCRM_Profile_Sync_ACF_CiviCRM_Address_City( $this );
		$this->address_state = new CiviCRM_Profile_Sync_ACF_CiviCRM_Address_State( $this );
		$this->google_map = new CiviCRM_Profile_Sync_ACF_CiviCRM_Google_Map( $this );
		$this->relationship = new CiviCRM_Profile_Sync_ACF_CiviCRM_Relationship( $this );
		$this->email = new CiviCRM_Profile_Sync_ACF_CiviCRM_Email( $this );
		$this->website = new CiviCRM_Profile_Sync_ACF_CiviCRM_Website( $this );
		$this->phone = new CiviCRM_Profile_Sync_ACF_CiviCRM_Phone( $this );
		$this->im = new CiviCRM_Profile_Sync_ACF_CiviCRM_Instant_Messenger( $this );
		$this->contact_id = new CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_ID( $this );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Trace database operations.
		//add_action( 'civicrm_pre', [ $this, 'trace_pre' ], 10, 4 );
		//add_action( 'civicrm_post', [ $this, 'trace_post' ], 10, 4 );

	}



	/**
	 * Initialise CiviCRM if necessary.
	 *
	 * @since 0.4
	 *
	 * @return bool $initialised True if CiviCRM initialised, false otherwise.
	 */
	public function is_initialised() {

		// Init only when CiviCRM is fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) ) {
			return false;
		}
		if ( ! CIVICRM_INSTALLED ) {
			return false;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return false;
		}

		// Try and initialise CiviCRM.
		return civi_wp()->initialize();

	}



	/**
	 * Finds out if a CiviCRM Component is active.
	 *
	 * @since 0.5
	 *
	 * @param string $component The name of the CiviCRM Component, e.g. 'CiviContribute'.
	 * @return bool True if the Component is active, false otherwise.
	 */
	public function is_component_enabled( $component = '' ) {

		// Init return.
		$active = false;

		// Bail if we can't initialise CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $active;
		}

		// Get the Component array. CiviCRM handles caching.
		$components = CRM_Core_Component::getEnabledComponents();

		// Override if Component is active.
		if ( array_key_exists( $component, $components ) ) {
			$active = true;
		}

		// --<
		return $active;

	}



	/**
	 * Check a CiviCRM permission.
	 *
	 * @since 0.4
	 *
	 * @param string $permission The permission string.
	 * @return bool $permitted True if allowed, false otherwise.
	 */
	public function check_permission( $permission ) {

		// Always deny if CiviCRM is not active.
		if ( ! $this->is_initialised() ) {
			return false;
		}

		// Deny by default.
		$permitted = false;

		// Check CiviCRM permissions.
		if ( CRM_Core_Permission::check( $permission ) ) {
			$permitted = true;
		}

		/**
		 * Return permission but allow overrides.
		 *
		 * @since 0.4
		 *
		 * @param bool $permitted True if allowed, false otherwise.
		 * @param string $permission The CiviCRM permission string.
		 */
		return apply_filters( 'cwps/acf/civicrm/permitted', $permitted, $permission );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the values in a given Option Group.
	 *
	 * @since 0.5
	 *
	 * @param integer $option_group_id The numeric ID of the Option Group.
	 * @return array $values The array of values.
	 */
	public function option_values_get( $option_group_id ) {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache[ $option_group_id ] ) ) {
			return $pseudocache[ $option_group_id ];
		}

		// Init return.
		$values = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $values;
		}

		// Define params to get all Participant Roles.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'option_group_id' => $option_group_id,
			'options' => [
				'sort' => 'weight',
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $values;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $values;
		}

		// The result set is what we're after.
		$values = $result['values'];

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $option_group_id ] ) ) {
			$pseudocache[ $option_group_id ] = $values;
		}

		// --<
		return $values;

	}



	/**
	 * Get the default value for a given Option Group name.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Option Group.
	 * @return integer|bool $default The default value, or false if not set.
	 */
	public function option_value_default_get( $name ) {

		// Init return.
		$default = false;

		// Get the Option Group.
		$option_group = $this->plugin->civicrm->option_group_get( $name );
		if ( empty( $option_group ) ) {
			return $default;
		}

		// Get the Option Values for the requested Option Group.
		$option_values = $this->option_values_get( $option_group['id'] );
		if ( empty( $option_values ) ) {
			return $default;
		}

		// Tease out the default if present.
		foreach ( $option_values as $option_value ) {
			if ( ! empty( $option_value['is_default'] ) ) {
				$default = $option_value['value'];
				break;
			}
		}

		// --<
		return $default;

	}



	// -------------------------------------------------------------------------



	/**
	 * Getter method for the "CiviCRM Field" key.
	 *
	 * @since 0.4
	 *
	 * @return string $acf_field_key The key of the "CiviCRM Field" in the ACF Field data.
	 */
	public function acf_field_key_get() {

		// --<
		return $this->acf_field_key;

	}



	/**
	 * Get ACF Field setting prefix that distinguishes Custom Fields from Contact Fields.
	 *
	 * @since 0.4
	 *
	 * @return string $custom_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public function custom_field_prefix() {

		// --<
		return $this->custom_field->custom_field_prefix;

	}



	/**
	 * Get ACF Field setting prefix that distinguishes Contact Fields from Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @return string $contact_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public function contact_field_prefix() {

		// --<
		return $this->contact->contact_field_prefix;

	}



	/**
	 * Get ACF Field setting prefix that distinguishes Activity Fields from Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @return string $activity_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public function activity_field_prefix() {

		// --<
		return $this->activity->activity_field_prefix;

	}



	/**
	 * Get ACF Field setting prefix that distinguishes Participant Fields from Custom Fields.
	 *
	 * @since 0.5
	 *
	 * @return string $participant_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public function participant_field_prefix() {

		// --<
		return $this->participant->participant_field_prefix;

	}



	// -------------------------------------------------------------------------



	/**
	 * Utility for tracing calls to hook_civicrm_pre.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function trace_pre( $op, $objectName, $objectId, $objectRef ) {

		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );

	}



	/**
	 * Utility for tracing calls to hook_civicrm_post.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function trace_post( $op, $objectName, $objectId, $objectRef ) {

		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
			//'backtrace' => $trace,
		], true ) );

	}



} // Class ends.



