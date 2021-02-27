<?php
/**
 * ACF Class.
 *
 * Handles general ACF functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync ACF Class.
 *
 * A class that encapsulates ACF functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF {

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * ACF Field Group object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $field_group The ACF Field Group object.
	 */
	public $field_group;

	/**
	 * ACF Field object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $field The ACF Field object.
	 */
	public $field;

	/**
	 * ACF Blocks object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $blocks The ACF Blocks object.
	 */
	//public $blocks;



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Bail if ACF isn't found.
		if ( ! function_exists( 'acf' ) ) {
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
		do_action( 'cwps/acf/acf/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-acf-field-group.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-acf-field.php';
		//include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-acf-blocks.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Init Field Group object.
		$this->field_group = new CiviCRM_Profile_Sync_ACF_Field_Group( $this );

		// Init Field object.
		$this->field = new CiviCRM_Profile_Sync_ACF_Field( $this );

		// Init Blocks object.
		//$this->blocks = new CiviCRM_Profile_Sync_ACF_Blocks( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Include any Field Types that we have defined.
		add_action( 'acf/include_field_types', [ $this, 'include_field_types' ] );

	}



	/**
	 * Include Field Types for ACF5.
	 *
	 * @since 0.4
	 *
	 * @param str $version The installed version of ACF.
	 */
	public function include_field_types( $version ) {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-contact-id.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-relationship.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address-city.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-address-state.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-phone.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-im.php';
		//include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-multiset.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-yes-no.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-creator.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-target.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/fields/cwps-acf-field-civicrm-activity-assignee.php';

		// Create fields.
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_ID_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Relationship( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_City_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Address_State_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Phone_Field( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Instant_Messenger( $this );
		//new CiviCRM_Profile_Sync_Custom_CiviCRM_Multiple_Record_Set( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Yes_No( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Creator( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Target( $this );
		new CiviCRM_Profile_Sync_Custom_CiviCRM_Activity_Assignee( $this );

	}



} // Class ends.



