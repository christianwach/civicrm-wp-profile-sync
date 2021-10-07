<?php
/**
 * ACFE Class.
 *
 * Handles general "ACF Extended" functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync "ACF Extended" Class.
 *
 * A class that encapsulates ACF Extended functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE {

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
	 * ACF Form object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $form The ACF Form object.
	 */
	public $form;



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Bail if the "ACF Extended" plugin isn't found.
		if ( ! function_exists( 'acfe' ) ) {
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
	 * @since 0.5
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
		 * @since 0.5
		 */
		do_action( 'cwps/acf/acfe/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/classes/cwps-acf-acfe-form.php';

		// Include Reference Field Types.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-action-reference-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-action-reference-case.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-action-reference-participant.php';

		// Include Field Types.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-address-county.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-address-state.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-address-country.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Init Form object.
		$this->form = new CiviCRM_Profile_Sync_ACF_ACFE_Form( $this );

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

		// Init Reference Field Types.
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Contact_Action_Ref( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Case_Action_Ref( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Participant_Action_Ref( $this );

		// Init Field Types.
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_County( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_State( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_Country( $this );

	}



} // Class ends.



