<?php
/**
 * ACF compatibility Class.
 *
 * Handles ACF compatibility.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Loader Class.
 *
 * A class that encapsulates ACF compatibility.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_ACF_Loader {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Admin Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The Admin Utilities object.
	 */
	public $admin;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civicrm;

	/**
	 * WordPress Post Type Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $post_type The Post Type Utilities object.
	 */
	public $post_type;

	/**
	 * WordPress Post Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $post The Post Utilities object.
	 */
	public $post;

	/**
	 * WordPress User Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $user The User Utilities object.
	 */
	public $user;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $cpt The Advanced Custom Fields object.
	 */
	public $acf;

	/**
	 * Mapping object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $mapping The Mapping object.
	 */
	public $mapping;

	/**
	 * Mapper object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $mapper The Mapper object.
	 */
	public $mapper;



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 */
	public function __construct() {

		// Initialise on plugin init.
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
	 * Initialise.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) AND $done === true ) {
			return;
		}

		// Bail if there's no ACF plugin present.
		if ( ! function_exists( 'acf' ) ) {
			$done = true;
			return;
		}

		// Defer to "CiviCRM ACF Integration" if present.
		if ( function_exists( 'civicrm_acf_integration' ) ) {

			// Include Admin Migrate class and init.
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-admin-migrate.php';
			$this->migrate = new CiviCRM_Profile_Sync_ACF_Admin_Migrate( $this );

			$done = true;
			return;

		}

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/acf/loaded' );

		// We're done.
		$done = true;

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include functions.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/functions/cwps-acf-functions.php';

		// Include Admin class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-admin.php';

		// Include CiviCRM class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-civicrm.php';

		// Include Post Type class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-post-type.php';

		// Include Post class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-post.php';

		// Include User class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-user.php';

		// Include ACF class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-acf.php';

		// Include Mapping class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-mapping.php';

		// Include Mapper class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-mapper.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Init Admin object.
		$this->admin = new CiviCRM_Profile_Sync_ACF_Admin( $this );

		// Init CiviCRM object.
		$this->civicrm = new CiviCRM_Profile_Sync_ACF_CiviCRM( $this );

		// Init Post Type object.
		$this->post_type = new CiviCRM_Profile_Sync_ACF_Post_Type( $this );

		// Init Post object.
		$this->post = new CiviCRM_Profile_Sync_ACF_Post( $this );

		// Init User object.
		$this->user = new CiviCRM_Profile_Sync_ACF_User( $this );

		// Init ACF object.
		$this->acf = new CiviCRM_Profile_Sync_ACF( $this );

		// Init Mapping object.
		$this->mapping = new CiviCRM_Profile_Sync_ACF_Mapping( $this );

		// Init Mapper object.
		$this->mapper = new CiviCRM_Profile_Sync_ACF_Mapper( $this );

	}



}



