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
	 * CiviCRM Custom Group object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $custom_group The CiviCRM Custom Group object.
	 */
	public $custom_group;

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
	 * "CiviCRM Field" field key in the ACF Field data.
	 *
	 * This "top level" field key is common to all CiviCRM Entities. The value
	 * of the field has a prefix which distiguishes the target Entity.
	 *
	 * @see self::custom_field_prefix()
	 * @see self::contact_field_prefix()
	 * @see self::activity_field_prefix()
	 * @see self::participant_field_prefix()
	 *
	 * @since 0.4
	 * @access public
	 * @var str $acf_field_key The key of the "CiviCRM Field" in the ACF Field data.
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

		// Store reference to ACF Loader object.
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
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-custom-group.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-custom-field.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-group.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm-note.php';
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
		$this->custom_group = new CiviCRM_Profile_Sync_ACF_CiviCRM_Custom_Group( $this );
		$this->custom_field = new CiviCRM_Profile_Sync_ACF_CiviCRM_Custom_Field( $this );
		$this->group = new CiviCRM_Profile_Sync_ACF_CiviCRM_Group( $this );
		$this->note = new CiviCRM_Profile_Sync_ACF_CiviCRM_Note( $this );
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
	 * @return boolean $initialised True if CiviCRM initialised, false otherwise.
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
	 * @return boolean $permitted True if allowed, false otherwise.
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
		 * @param boolean $permitted True if allowed, false otherwise.
		 * @param string $permission The CiviCRM permission string.
		 * @return boolean $permitted True if allowed, false otherwise.
		 */
		return apply_filters( 'cwps/acf/civicrm/permitted', $permitted, $permission );

	}



	/**
	 * Get a CiviCRM admin link.
	 *
	 * @since 0.4
	 *
	 * @param string $path The CiviCRM path.
	 * @param string $params The CiviCRM parameters.
	 * @return string $link The URL of the CiviCRM page.
	 */
	public function get_link( $path = '', $params = null ) {

		// Init link.
		$link = '';

		// Init CiviCRM or bail.
		if ( ! $this->is_initialised() ) {
			return $link;
		}

		// Use CiviCRM to construct link.
		$link = CRM_Utils_System::url(
			$path, // Path to the resource.
			$params, // Params to pass to resource.
			true, // Force an absolute link.
			null, // Fragment (#anchor) to append.
			true, // Encode special HTML characters.
			false, // CMS front end.
			true // CMS back end.
		);

		// --<
		return $link;

	}



	/**
	 * Get a CiviCRM Setting.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the CiviCRM Setting.
	 * @return mixed $setting The value of the CiviCRM Setting, or false on failure.
	 */
	public function get_setting( $name ) {

		// Init return.
		$setting = false;

		// Init CiviCRM or bail.
		if ( ! $this->is_initialised() ) {
			return $setting;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'name' => $name,
		];

		// Call the CiviCRM API.
		$setting = civicrm_api( 'Setting', 'getvalue', $params );

		// --<
		return $setting;

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



	// -------------------------------------------------------------------------



	/**
	 * Utility for de-nullifying CiviCRM data.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The existing value.
	 * @return mixed $value The cleaned value.
	 */
	public function denullify( $value ) {

		// Catch inconsistent CiviCRM "empty-ish" values.
		if ( empty( $value ) OR $value == 'null' OR $value == 'NULL' ) {
			$value = '';
		}

		// --<
		return $value;

	}



} // Class ends.



