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
	public $acf;

	/**
	 * "Event Group" Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $event_group The "Event Group" Field object.
	 */
	public $event_group;

	/**
	 * "Contact Group" Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $contact_group The "Contact Group" Field object.
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
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf = $parent;

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
	 * @param string $version The installed version of ACF.
	 */
	public function register_field_types( $version ) {

		// Include files.
		$this->include_field_types( $version );

		// Set up Field objects.
		$this->setup_field_types( $version );

	}

	/**
	 * Include Field Types for ACF5.
	 *
	 * @since 0.5
	 *
	 * @param string $version The installed version of ACF.
	 */
	public function include_field_types( $version ) {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-contact-id.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-relationship.php';

		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address-city.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address-state.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address-country.php';

		if ( $this->acf->is_pro() ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address.php';
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-phone.php';
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-im.php';
		}

		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-multiset.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-yes-no.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-creator.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-target.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-assignee.php';

		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-event.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-event-group.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-contact-existing-new.php';

	}

	/**
	 * Include Field Types for ACF5.
	 *
	 * @since 0.5
	 *
	 * @param string $version The installed version of ACF.
	 */
	public function setup_field_types( $version ) {

		// Create Fields.
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_ID_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Relationship( $this );

		new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_City_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_State_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_Country_Field( $this );

		if ( $this->acf->is_pro() ) {
			new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_Field( $this );
			new CiviCRM_Profile_Sync_Custom_CiviCRM_Phone_Field( $this );
			new CiviCRM_Profile_Sync_Custom_CiviCRM_Instant_Messenger( $this );
		}

		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//new CiviCRM_Profile_Sync_Custom_CiviCRM_Multiple_Record_Set( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Yes_No( $this );

		new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Creator( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Target( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Assignee( $this );

		new CiviCRM_Profile_Sync_Custom_CiviCRM_Event_Field( $this );
		$this->event_group = new CiviCRM_Profile_Sync_Custom_CiviCRM_Event_Group( $this );
		$this->contact_group = new CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_Existing_New( $this );

	}

}
