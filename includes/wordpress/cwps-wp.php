<?php
/**
 * WordPress compatibility Class.
 *
 * Handles WordPress integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync WordPress compatibility Class.
 *
 * This class provides WordPress integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_WordPress {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * WordPress User object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $user The WordPress User object.
	 */
	public $user;



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
		do_action( 'cwps/wordpress/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include class files.
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/wordpress/cwps-wp-user.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->user = new CiviCRM_WP_Profile_Sync_WordPress_User();

		// Store references.
		$this->user->set_references( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Add all callbacks.
		$this->user->register_hooks();

	}



	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Remove all callbacks.
		$this->user->unregister_hooks();

	}



	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Add all Mapper callbacks.
		$this->user->register_mapper_hooks();

	}



	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all Mapper callbacks.
		$this->user->unregister_mapper_hooks();

	}



} // Class ends.



