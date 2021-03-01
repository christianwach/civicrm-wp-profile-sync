<?php
/**
 * CiviCRM compatibility Class.
 *
 * Handles CiviCRM integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM compatibility Class.
 *
 * This class provides CiviCRM integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_CiviCRM {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * CiviCRM Contact object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $contact The CiviCRM Contact object.
	 */
	public $contact;

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
	 * CiviCRM Bulk Operations object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $bulk The CiviCRM Bulk Operations object.
	 */
	public $bulk;



	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 */
	public function __construct() {

		// Boot when plugin is loaded.
		add_action( 'civicrm_wp_profile_sync_init', [ $this, 'initialise' ] );

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function set_references( $parent ) {

		// Store reference.
		$this->plugin = $parent;

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/civicrm/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include class files.
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-contact.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-email.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-website.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-bulk.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->contact = new CiviCRM_WP_Profile_Sync_CiviCRM_Contact();
		$this->email = new CiviCRM_WP_Profile_Sync_CiviCRM_Email();
		$this->website = new CiviCRM_WP_Profile_Sync_CiviCRM_Website();
		$this->bulk = new CiviCRM_WP_Profile_Sync_CiviCRM_Bulk();

		// Store references.
		$this->contact->set_references( $this );
		$this->email->set_references( $this );
		$this->website->set_references( $this );
		$this->bulk->set_references( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Add all CiviCRM callbacks.
		$this->contact->register_hooks();
		$this->email->register_hooks();
		$this->website->register_hooks();

	}



	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Remove all CiviCRM callbacks.
		$this->contact->unregister_hooks();
		$this->email->unregister_hooks();
		$this->website->unregister_hooks();

	}



	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Add all CiviCRM callbacks.
		$this->contact->register_mapper_hooks();
		$this->email->register_mapper_hooks();
		$this->website->register_mapper_hooks();

	}



	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all CiviCRM callbacks.
		$this->contact->unregister_mapper_hooks();
		$this->email->unregister_mapper_hooks();
		$this->website->unregister_mapper_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if CiviCRM is initialised.
	 *
	 * @since 0.4
	 *
	 * @return boolean True if CiviCRM initialised, false otherwise.
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



	// -------------------------------------------------------------------------



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
			$path,
			$params,
			true,
			null,
			true,
			false,
			true
		);

		// --<
		return $link;

	}



} // Class ends.



