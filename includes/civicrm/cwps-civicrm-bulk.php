<?php
/**
 * CiviCRM Bulk Operations Class.
 *
 * Handles CiviCRM Bulk Operations.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Bulk Operations Class.
 *
 * This class provides CiviCRM Bulk Operations integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Bulk {

	/**
	 * Plugin object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store plugin reference.
		$this->plugin = $parent->plugin;

		// Store CiviCRM object reference.
		$this->civicrm = $parent;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Are we allowing bulk operations?
		if ( CIVICRM_WP_PROFILE_SYNC_BULK !== true ) {
			return;
		}

		// Add an item to the Actions dropdown.
		add_action( 'civicrm_searchTasks', [ $this, 'bulk_operations' ], 10, 2 );

		// Register PHP and template directories.
		add_action( 'civicrm_config', [ $this, 'register_php_directory' ], 10 );
		add_action( 'civicrm_config', [ $this, 'register_template_directory' ], 10 );

		// Prevent recursion when bulk adding WordPress Users via CiviCRM.
		add_action( 'civicrm_wp_profile_sync_user_add_pre', [ $this, 'contact_bulk_added_pre' ], 10 );
		add_action( 'civicrm_wp_profile_sync_user_add_post', [ $this, 'contact_bulk_added_post' ], 10 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Register directory that CiviCRM searches in for new PHP files.
	 *
	 * This only works with *new* PHP files. One cannot override existing PHP
	 * with this technique - instead, the file must be placed in the path:
	 * defined in $config->customPHPPathDir
	 *
	 * @since 0.1
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_php_directory( &$config ) {

		// Define our custom path.
		$custom_path = CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/civicrm/custom_php';

		// Add to include path.
		$include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
		set_include_path( $include_path );

	}

	/**
	 * Register directories that CiviCRM searches for php and template files.
	 *
	 * @since 0.1
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_template_directory( &$config ) {

		// Define our custom path.
		$custom_path = CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/civicrm/custom_templates';

		// Get template instance.
		$template = CRM_Core_Smarty::singleton();

		// Add our custom template directory.
		$template->addTemplateDir( $custom_path );

		// Register template directories.
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
		set_include_path( $template_include_path );

	}

	/**
	 * Add an option to the Actions dropdown.
	 *
	 * @since 0.1
	 *
	 * @param string $object_name The CiviCRM object type.
	 * @param array $tasks The CiviCRM tasks array to add our option to.
	 */
	public function bulk_operations( $object_name, &$tasks ) {

		// Only handle Contacts.
		if ( $object_name != 'contact' ) {
			return;
		}

		// Add our item to the tasks array.
		$tasks[] = [
			'title' => __( 'Create WordPress Users from Contacts', 'civicrm-wp-profile-sync' ),
			'class' => 'CRM_Contact_Form_Task_CreateWordPressUsers',
		];

	}

	// -------------------------------------------------------------------------

	/**
	 * Prevent recursion when a WordPress User is about to be bulk added.
	 *
	 * @since 0.1
	 */
	public function contact_bulk_added_pre() {

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->plugin->hooks_wp_remove();
		$this->plugin->hooks_bp_remove();

	}

	/**
	 * Re-hook when a WordPress User has been bulk added.
	 *
	 * @since 0.1
	 */
	public function contact_bulk_added_post() {

		// Re-add WordPress and BuddyPress callbacks.
		$this->plugin->hooks_wp_add();
		$this->plugin->hooks_bp_add();

	}

}
