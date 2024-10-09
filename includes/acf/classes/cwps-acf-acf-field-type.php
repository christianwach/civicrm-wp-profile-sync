<?php
/**
 * ACF Class.
 *
 * Handles ACF Field Types.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync ACF Field Type Class.
 *
 * A class that encapsulates registration of ACF Field Types.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_Field_Type {

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
	 * ACF object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * "Event Group" Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $event_group;

	/**
	 * "Contact Group" Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $contact_group;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The ACF object.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf        = $parent;

		// Init when the parent class is loaded.
		add_action( 'cwps/acf/acf/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Include any Field Types that we have defined.
		add_action( 'acf/include_field_types', [ $this, 'register_field_types' ] );

	}

	/**
	 * Registers the Field Types for ACF5.
	 *
	 * @since 0.5
	 *
	 * @param integer $api_version The ACF Field API version.
	 */
	public function register_field_types( $api_version ) {

		// Bail if there's no CiviCRM.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return;
		}

		// We do not need the ACF Fields in CiviCRM admin.
		$civicrm_args = civi_wp()->get_request_args();
		if ( is_admin() && ! empty( $civicrm_args['args'][0] ) && 'civicrm' === $civicrm_args['args'][0] ) {
			return;
		}

		// Include files.
		$this->include_field_types( $api_version );

		// Set up Field objects.
		$this->setup_field_types( $api_version );

	}

	/**
	 * Include Field Types for ACF5.
	 *
	 * @since 0.5
	 *
	 * @param integer $api_version The ACF Field API version.
	 */
	public function include_field_types( $api_version ) {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-contact-id.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-relationship.php';

		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address-city.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address-state.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address-country.php';

		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-phone-single.php';

		if ( $this->acf->is_pro() ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address.php';
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-phone.php';
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-im.php';
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-attachment.php';
		}

		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-multiset.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-yes-no.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-creator.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-target.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-assignee.php';

		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-event.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-event-group.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-contact-existing-new.php';

	}

	/**
	 * Include Field Types for ACF5+.
	 *
	 * @since 0.5
	 *
	 * @param integer $api_version The ACF Field API version.
	 */
	public function setup_field_types( $api_version ) {

		// Create Fields.
		$contact_id_field = new CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_ID_Field( $this );
		acf_register_field_type( $contact_id_field );
		$contact_field = new CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_Field( $this );
		acf_register_field_type( $contact_field );
		$relationship = new CiviCRM_Profile_Sync_Custom_CiviCRM_Relationship( $this );
		acf_register_field_type( $relationship );

		$city = new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_City_Field( $this );
		acf_register_field_type( $city );
		$state = new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_State_Field( $this );
		acf_register_field_type( $state );
		$country = new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_Country_Field( $this );

		$phone_single = new CiviCRM_Profile_Sync_Custom_CiviCRM_Phone_Single( $this );
		acf_register_field_type( $phone_single );

		if ( $this->acf->is_pro() ) {
			$address = new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_Field( $this );
			acf_register_field_type( $address );
			$phone = new CiviCRM_Profile_Sync_Custom_CiviCRM_Phone_Field( $this );
			acf_register_field_type( $phone );
			$im = new CiviCRM_Profile_Sync_Custom_CiviCRM_Instant_Messenger( $this );
			acf_register_field_type( $im );
			$attachment = new CiviCRM_Profile_Sync_Custom_CiviCRM_Attachment( $this );
			acf_register_field_type( $attachment );
		}

		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// new CiviCRM_Profile_Sync_Custom_CiviCRM_Multiple_Record_Set( $this );
		$yes_no = new CiviCRM_Profile_Sync_Custom_CiviCRM_Yes_No( $this );
		acf_register_field_type( $yes_no );

		$activity_creator = new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Creator( $this );
		acf_register_field_type( $activity_creator );
		$activity_target = new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Target( $this );
		acf_register_field_type( $activity_target );
		$activity_assignee = new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Assignee( $this );
		acf_register_field_type( $activity_assignee );

		$event = new CiviCRM_Profile_Sync_Custom_CiviCRM_Event_Field( $this );
		acf_register_field_type( $event );
		$this->event_group = new CiviCRM_Profile_Sync_Custom_CiviCRM_Event_Group( $this );
		acf_register_field_type( $this->event_group );
		$this->contact_group = new CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_Existing_New( $this );
		acf_register_field_type( $this->contact_group );

	}

}
