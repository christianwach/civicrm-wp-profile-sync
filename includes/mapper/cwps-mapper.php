<?php
/**
 * Mapper Parent Class.
 *
 * Handles mapping functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync Mapper Parent Class.
 *
 * This class provides mapping integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_Mapper {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Hooks utility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $hooks The Hooks utility object.
	 */
	public $hooks;

	/**
	 * UFMatch utility object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $single The UFMatch utility object.
	 */
	public $ufmatch;

	/**
	 * Define date format mappings (CiviCRM to PHP).
	 *
	 * @since 0.5
	 * @access public
	 * @var array $date_mappings The CiviCRM to PHP date format mappings.
	 */
	public $date_mappings = [
		'mm/dd/yy' => 'm/d/Y',
		'dd/mm/yy' => 'd/m/Y',
		'yy-mm-dd' => 'Y-m-d',
		'dd-mm-yy' => 'd-m-Y',
		'dd.mm.yy' => 'd.m.Y',
		'M d, yy' => 'M d, Y',
		'd M yy' => 'j M Y',
		'MM d, yy' => 'F j, Y',
		'd MM yy' => 'd F Y',
		'DD, d MM yy' => 'l, d F Y',
		'mm/dd' => 'm/d',
		'dd-mm' => 'd-m',
		'M yy' => 'm Y',
		'M Y' => 'm Y',
		'yy' => 'Y',
	];

	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store reference.
		$this->plugin = $parent;

		// Boot when plugin is loaded.
		add_action( 'civicrm_wp_profile_sync_init', [ $this, 'initialise' ] );

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
		do_action( 'cwps/mapper/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include class files.
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/mapper/cwps-mapper-hooks.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/mapper/cwps-mapper-ufmatch.php';

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->hooks = new CiviCRM_WP_Profile_Sync_Mapper_Hooks( $this );
		$this->ufmatch = new CiviCRM_WP_Profile_Sync_Mapper_UFMatch( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Add callbacks.
		$this->hooks->register_hooks();
		$this->ufmatch->register_hooks();

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Remove all callbacks.
		$this->hooks->unregister_hooks();
		$this->ufmatch->unregister_hooks();

	}

}
