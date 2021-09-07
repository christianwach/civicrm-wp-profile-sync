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
	 * ACF Field Type object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $field_type The ACF Field Type object.
	 */
	public $field_type;

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
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-acf-field-type.php';
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

		// Init Field Type object.
		$this->field_type = new CiviCRM_Profile_Sync_ACF_Field_Type( $this );

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

	}



	/**
	 * Checks if ACF Pro is installed.
	 *
	 * @since 0.5
	 *
	 * @return bool $is_pro True if ACF Pro is installed, false otherwise.
	 */
	public function is_pro() {

		// Return the property from the Loader object.
		return $this->acf_loader->acf_pro;

	}



} // Class ends.



