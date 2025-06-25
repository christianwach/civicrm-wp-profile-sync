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
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_ACF_Loader
	 */
	public $acf_loader;

	/**
	 * Admin object.
	 *
	 * @since 0.6.6
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_ACFE_Admin
	 */
	public $admin;

	/**
	 * ACF Form object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_ACFE_Form
	 */
	public $form;

	/**
	 * ACF Extended plugin version.
	 *
	 * @since 0.7.0
	 * @access public
	 * @var string
	 */
	public $acfe_version;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store references to objects.
		$this->plugin     = $acf_loader->plugin;
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

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
			return;
		}

		// Bail if the "ACF Extended" plugin isn't found.
		if ( ! defined( 'ACFE_VERSION' ) ) {
			$done = true;
			return;
		}

		// Store ACF Extended version.
		$this->acfe_version = ACFE_VERSION;

		// Return early if ACF Extended Integration has been disabled.
		$acf_enabled = (int) $this->plugin->admin->setting_get( 'acfe_integration_enabled', 1 );
		if ( 1 !== $acf_enabled ) {

			// Include Admin class and init.
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/classes/cwps-acf-acfe-admin.php';
			$this->admin = new CiviCRM_Profile_Sync_ACF_ACFE_Admin( $this );
			$this->admin->initialise();

			$done = true;
			return;

		}

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

		// Okay, we're done.
		$done = true;

	}

	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/classes/cwps-acf-acfe-admin.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/classes/cwps-acf-acfe-form.php';

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Init objects.
		$this->admin = new CiviCRM_Profile_Sync_ACF_ACFE_Admin( $this );
		$this->form  = new CiviCRM_Profile_Sync_ACF_ACFE_Form( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Include any Field Types that we have defined after ACFE does.
		add_action( 'acf/include_field_types', [ $this, 'register_field_types' ], 100 );

	}

	/**
	 * Registers the Field Types for ACF5+.
	 *
	 * @since 0.5
	 *
	 * @param integer $api_version The ACF Field API version.
	 */
	public function register_field_types( $api_version ) {

		// Include Reference Field Types.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-action-reference-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-action-reference-case.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-action-reference-participant.php';

		// Init Reference Field Types.
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Contact_Action_Ref( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Case_Action_Ref( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Participant_Action_Ref( $this );

		// Include Field Types.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-address-county.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-address-state.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/fields/cwps-acf-acfe-field-address-country.php';

		// Init Field Types.
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_County( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_State( $this );
		new CiviCRM_Profile_Sync_ACF_ACFE_Form_Address_Country( $this );

	}

	/**
	 * Check if ACF Extended Pro is present and active.
	 *
	 * The "ACFE_PRO" constant is only set after the "acf/include_field_types"
	 * action at priority 10 - since this is when the "ACFE_Pro" class is included.
	 *
	 * We might be able to find out earlier based on the enclosing directory of
	 * the ACF Extended plugin, because it should be "acf-extended-pro" but this
	 * is not reliable.
	 *
	 * We might also be able to look for the "pro" directory in the root of the
	 * plugin directory. This can be found by looking the "ACFE_PATH" constant.
	 * This, too, may not be reliable.
	 *
	 * @since 0.5
	 *
	 * @return bool True if ACF Extended Pro is active, false otherwise.
	 */
	public function is_pro() {

		// Return boolean based the ACFE Pro constant.
		return ( defined( 'ACFE_PRO' ) && ACFE_PRO ) ? true : false;

	}

}
