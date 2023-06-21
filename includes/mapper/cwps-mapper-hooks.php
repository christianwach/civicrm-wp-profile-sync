<?php
/**
 * Mapper Hooks Class.
 *
 * Handles Hooks functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync Mapper Hooks Class.
 *
 * This class provides hooks functionality.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_Mapper_Hooks {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * Mapper (parent) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $mapper;

	/**
	 * Core Hooks utility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $core;

	/**
	 * ACF Hooks utility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * ACF loaded flag.
	 *
	 * True if ACF is present, false otherwise.
	 *
	 * @since 0.4
	 * @access public
	 * @var bool
	 */
	public $acf_loaded = false;

	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store reference.
		$this->plugin = $parent->plugin;
		$this->mapper = $parent;

		// Boot when Mapper is loaded.
		add_action( 'cwps/mapper/loaded', [ $this, 'initialise' ] );

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
		do_action( 'cwps/mapper/hooks/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include class files.
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/mapper/cwps-mapper-hooks-core.php';

		// Maybe load legacy CAI file.
		if ( $this->plugin->cai->is_loaded() ) {
			require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/mapper/cwps-mapper-hooks-acf.php';
		}

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->core = new CiviCRM_WP_Profile_Sync_Mapper_Hooks_Core( $this );

		// Maybe initialise legacy CAI object.
		if ( $this->plugin->cai->is_loaded() ) {
			$this->acf = new CiviCRM_WP_Profile_Sync_Mapper_Hooks_ACF( $this );
			$this->acf_loaded === true;
		}

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Add core callbacks.
		$this->core->register_hooks();

		// Maybe add ACF callbacks.
		if ( $this->acf_loaded === true ) {
			$this->acf->register_hooks();
		}

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Remove core callbacks.
		$this->core->unregister_hooks();

		// Maybe add ACF callbacks.
		if ( $this->acf_loaded === true ) {
			$this->acf->unregister_hooks();
		}

	}

}
