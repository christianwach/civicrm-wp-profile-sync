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
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * ACF Bypass object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $bypass The ACF Bypass object.
	 */
	public $bypass;



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

		// Store reference to ACF Loader object.
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
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/acfe/classes/cwps-acf-acfe-bypass.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Init Bypass object.
		$this->bypass = new CiviCRM_Profile_Sync_ACF_ACFE_Bypass( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}



} // Class ends.



