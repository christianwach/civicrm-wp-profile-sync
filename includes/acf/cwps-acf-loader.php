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
	 * @var object
	 */
	public $plugin;

	/**
	 * Admin Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $admin;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * WordPress Post Type Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $post_type;

	/**
	 * WordPress Post Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $post;

	/**
	 * WordPress User Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $user;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * ACF Extended object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acfe;

	/**
	 * Mapping object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $mapping;

	/**
	 * Mapper object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $mapper;

	/**
	 * Geo Mashup object.
	 *
	 * @since 0.5.8
	 * @access public
	 * @var object
	 */
	public $geo_mashup;

	/**
	 * Loaded flag. True when conditions are met.
	 *
	 * @since 0.4
	 * @access public
	 * @var bool
	 */
	public $loaded = false;

	/**
	 * ACF Pro flag. True when ACF Pro is installed.
	 *
	 * @since 0.5
	 * @access public
	 * @var bool
	 */
	public $acf_pro = false;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store reference.
		$this->plugin = $parent;

		// Initialise on plugin init.
		add_action( 'civicrm_wp_profile_sync_init', [ $this, 'initialise' ] );

	}

	/**
	 * Getter for loaded property.
	 *
	 * @since 0.4
	 *
	 * @return bool $loaded True if fully loaded, false otherwise.
	 */
	public function is_loaded() {

		// --<
		return $this->loaded;

	}

	/**
	 * Initialise.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Only do this once.
		static $done;
		if ( isset( $done ) && true === $done ) {
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

		// Return early if ACF Integration has been disabled.
		$acf_enabled = (int) $this->plugin->admin->setting_get( 'acf_integration_enabled', 1 );
		if ( 1 !== $acf_enabled ) {

			// Include Admin class and init.
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-admin.php';
			$this->admin = new CiviCRM_Profile_Sync_ACF_Admin( $this );
			$this->admin->initialise();

			$done = true;
			return;

		}

		// Save ACF Pro flag.
		$this->acf_pro = ( defined( 'ACF_PRO' ) && ACF_PRO ) ? true : false;

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

		// We're done and loaded.
		$done         = true;
		$this->loaded = true;

	}

	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include legacy CAI functions.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/functions/cwps-acf-functions-cai.php';

		// Include functions.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/functions/cwps-acf-functions.php';

		// Include Admin class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-admin.php';

		// Include Mapper class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-mapper.php';

		// Include Mapping class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-mapping.php';

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

		// Include ACFE class.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/classes/cwps-acf-acfe.php';

		// Maybe include Geo Mashup compatibility class.
		if ( defined( 'GEO_MASHUP_VERSION' ) ) {
			include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/compat/cwps-acf-geo-mashup.php';
		}

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Init Admin object.
		$this->admin = new CiviCRM_Profile_Sync_ACF_Admin( $this );

		// Init Mapper object.
		$this->mapper = new CiviCRM_Profile_Sync_ACF_Mapper( $this );

		// Init Mapping object.
		$this->mapping = new CiviCRM_Profile_Sync_ACF_Mapping( $this );

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

		// Init ACFE object.
		$this->acfe = new CiviCRM_Profile_Sync_ACF_ACFE( $this );

		// Maybe init Geo Mashup compatibility object.
		if ( defined( 'GEO_MASHUP_VERSION' ) ) {
			$this->geo_mashup = new CiviCRM_WP_Profile_Sync_ACF_Geo_Mashup( $this );
		}

	}

}
