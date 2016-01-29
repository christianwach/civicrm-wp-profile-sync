<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Profile Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-profile-sync
Description: Keeps a WordPress User profile in sync with CiviCRM Contact info
Author: Christian Wach
Version: 0.2.3
Author URI: http://haystack.co.uk
Text Domain: civicrm-wp-profile-sync
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// set our debug flag here
define( 'CIVICRM_WP_PROFILE_SYNC_DEBUG', false );

// set our bulk operations flag here
define( 'CIVICRM_WP_PROFILE_SYNC_BULK', false );

// set our version here
define( 'CIVICRM_WP_PROFILE_SYNC_VERSION', '0.2.3' );

// store reference to this file
if ( !defined( 'CIVICRM_WP_PROFILE_SYNC_FILE' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_FILE', __FILE__ );
}

// store URL to this plugin's directory
if ( !defined( 'CIVICRM_WP_PROFILE_SYNC_URL' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_URL', plugin_dir_url( CIVICRM_WP_PROFILE_SYNC_FILE ) );
}

// store PATH to this plugin's directory
if ( !defined( 'CIVICRM_WP_PROFILE_SYNC_PATH' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_PATH', plugin_dir_path( CIVICRM_WP_PROFILE_SYNC_FILE ) );
}



/**
 * CiviCRM WordPress Profile Sync Class.
 *
 * A class that encapsulates this plugin's functionality.
 *
 * @since 0.1
 */
class CiviCRM_WP_Profile_Sync {

	/**
	 * Error messages.
	 *
	 * @since 0.1
	 * @access public
	 * @var array $messages The error messages array
	 */
	public $messages = array();



	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// use translation
		add_action( 'plugins_loaded', array( $this, 'translation' ) );

		// post process CiviCRM contact when WP user is updated, done late to let other plugins go first
		$this->_add_hooks_wp();

		// update the default name field before xprofile_sync_wp_profile is called
		$this->_add_hooks_bp();

		// sync a WP user when a CiviCRM contact is updated
		$this->_add_hooks_civi();

		// are we allowing bulk operations?
		if ( CIVICRM_WP_PROFILE_SYNC_BULK ) {

			// add an item to the actions dropdown
			add_action( 'civicrm_searchTasks', array( $this, 'civi_bulk_operations' ), 10, 2 );

			// register php and template directories
			add_action( 'civicrm_config', array( $this, 'register_php_directory' ), 10, 1 );
			add_action( 'civicrm_config', array( $this, 'register_template_directory' ), 10, 1 );

			// prevent recursion when bulk adding users via CiviCRM
			add_action( 'civicrm_wp_profile_sync_user_add_pre', array( $this, 'civi_contact_bulk_added_pre' ), 10 );
			add_action( 'civicrm_wp_profile_sync_user_add_post', array( $this, 'civi_contact_bulk_added_post' ), 10 );

		}

	}



	//##########################################################################



	/**
	 * Load translation if present.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function translation() {

		// only use, if we have it...
		if ( function_exists( 'load_plugin_textdomain' ) ) {

			// there are no translations as yet, but they can now be added
			load_plugin_textdomain(

				// unique name
				'civicrm-wp-profile-sync',

				// deprecated argument
				false,

				// relative path to directory containing translation files
				dirname( plugin_basename( CIVICRM_WP_PROFILE_SYNC_FILE ) ) . '/languages/'

			);

		}

	}



	/**
	 * Register directory that CiviCRM searches in for new PHP files.
	 *
	 * This only works with *new* PHP files. One cannot override existing PHP
	 * with this technique - instead, the file must be placed in the path:
	 * defined in $config->customPHPPathDir
	 *
	 * @since 0.1
	 *
	 * @param object $config The CiviCRM config object
	 * @return void
	 */
	public function register_php_directory( &$config ) {

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return;

		// define our custom path
		$custom_path = CIVICRM_WP_PROFILE_SYNC_PATH . 'civicrm_custom_php';

		// add to include path
		$include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $include_path );

	}



	/**
	 * Register directories that CiviCRM searches for php and template files.
	 *
	 * @since 0.1
	 *
	 * @param object $config The CiviCRM config object
	 * @return void
	 */
	public function register_template_directory( &$config ) {

		// define our custom path
		$custom_path = CIVICRM_WP_PROFILE_SYNC_PATH . 'civicrm_custom_templates';

		// kick out if no CiviCRM
		if ( ! civi_wp()->initialize() ) return;

		// get template instance
		$template = CRM_Core_Smarty::singleton();

		// add our custom template directory
		$template->addTemplateDir( $custom_path );

		// register template directories
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Add an option to the Actions dropdown.
	 *
	 * @since 0.1
	 *
	 * @param str $object_name The CiviCRM object type
	 * @param array $tasks The CiviCRM tasks array to add our option to
	 * @return void
	 */
	public function civi_bulk_operations( $object_name, &$tasks ) {

		// only handle contacts
		if ( $object_name != 'contact' ) return;

		/*
		// sort by key for clarity
		ksort( $tasks );

		// debug
		print_r( array(
			'object_name' => $object_name,
			'tasks' => $tasks,
		) ); die();
		*/

		// add our item to the tasks array
		$tasks[] = array(
			'title' => __( 'Create WordPress Users from Contacts',  'civicrm-wp-profile-sync' ),
			'class' => 'CRM_Contact_Form_Task_CreateWordPressUsers',
		);

	}



	//##########################################################################



	/**
	 * Intercept BuddyPress's attempt to sync to WordPress user profile.
	 *
	 * @since 0.1
	 *
	 * @param integer $user_id The numeric ID of the WordPress user
	 * @param array $posted_field_ids The array of numeric IDs of the BuddyPress fields
	 * @param boolean $errors True if there are errors, false otherwise
	 * @return void
	 */
	public function buddypress_contact_updated( $user_id = 0, $posted_field_ids, $errors ) {

		$this->_debug( array(
			'function' => 'buddypress_contact_updated',
		));

		// get BP instance
		$bp = buddypress();

		if ( !empty( $bp->site_options['bp-disable-profile-sync'] ) && (int) $bp->site_options['bp-disable-profile-sync'] )
			return true;

		if ( empty( $user_id ) )
			$user_id = bp_loggedin_user_id();

		if ( empty( $user_id ) )
			return false;

		// pass to our sync method
		$this->wordpress_contact_updated( $user_id );

	}



	/**
	 * Updates a CiviCRM Contact when a WordPress user is updated.
	 *
	 * @since 0.1
	 *
	 * @param integer $user_id The numeric ID of the WordPress user
	 * @return void
	 */
	public function wordpress_contact_updated( $user_id ) {

		$this->_debug( array(
			'function' => 'wordpress_contact_updated',
			'user_id' => $user_id,
		));

		// okay, get user
		$user = get_userdata( $user_id );

		// did we get one?
		if ( $user ) {

			// init CiviCRM
			if ( !civi_wp()->initialize() ) return;

			// get user matching file
			require_once 'CRM/Core/BAO/UFMatch.php';

			// remove CiviCRM and BuddyPress callbacks to prevent recursion
			$this->_remove_hooks_bp();
			$this->_remove_hooks_civi();

			// get the Civi contact object
			$civi_contact = CRM_Core_BAO_UFMatch::synchronizeUFMatch(
				$user, // user object
				$user->ID, // ID
				$user->user_email, // unique identifier
				'WordPress', // CMS
				null, // status (unused)
				'Individual' // contact type
			);

			// update first name and last name
			$this->_update_civi_name( $user, $civi_contact );

			// optionally update primary email
			$this->_update_civi_primary_email( $user, $civi_contact );

			// optionally update website
			$this->_update_civi_website( $user, $civi_contact );

			// add more built-in WordPress fields here...

			// add CiviCRM and BuddyPress callbacks once more
			$this->_add_hooks_bp();
			$this->_add_hooks_civi();

		}

	}



	//##########################################################################



	/**
	 * Fires when a CiviCRM contact is updated, but prior to any operations taking place.
	 *
	 * This is used as a means by which to discover the direction of the update, because
	 * if the update is initiated from the WordPress side, this callback will have been
	 * unhooked and will not be called.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation
	 * @param string $objectName The type of object
	 * @param integer $objectId The ID of the object
	 * @param object $objectRef The object
	 * @return void
	 */
	public function civi_contact_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// target our operation
		if ( $op != 'edit' ) return;

		// target our object type
		if ( $objectName != 'Individual' ) return;

		$this->_debug( array(
			'function' => 'civi_contact_pre_update',
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// remove WordPress and BuddyPress callbacks to prevent recursion
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

	}



	/**
	 * Prevent recursion when a CiviCRM contact's primary email address is updated.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation
	 * @param string $objectName The type of object
	 * @param integer $objectId The ID of the object
	 * @param object $objectRef The object
	 * @return void
	 */
	public function civi_primary_email_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// target our operation
		if ( $op != 'edit' ) return;

		// target our object type
		if ( $objectName != 'Email' ) return;

		// bail if we have no email
		if ( ! isset( $objectRef['email'] ) ) return;

		$this->_debug( array(
			'function' => 'civi_primary_email_pre_update',
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// remove WordPress and BuddyPress callbacks to prevent recursion
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

	}



	/**
	 * Update a WordPress user when a CiviCRM contact's website is updated.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation
	 * @param string $objectName The type of object
	 * @param integer $objectId The ID of the object
	 * @param object $objectRef The object
	 * @return void
	 */
	public function civi_website_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// target our object type
		if ( $objectName != 'Website' ) return;

		// bail if we have no website
		if ( ! isset( $objectRef['url'] ) ) return;

		// bail if we have no contact ID
		if ( ! isset( $objectRef['contact_id'] ) ) return;

		$this->_debug( array(
			'function' => 'civi_website_pre_update',
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// init CiviCRM to get WP user ID
		if ( ! civi_wp()->initialize() ) return;

		// make sure Civi file is included
		require_once 'CRM/Core/BAO/UFMatch.php';

		// search using Civi's logic
		$user_id = CRM_Core_BAO_UFMatch::getUFId( $objectRef['contact_id'] );

		$this->_debug( array(
			'function' => 'civi_website_pre_update',
			'user_id' => $user_id,
		));

		// kick out if we didn't get one
		if ( empty( $user_id ) ) return;

		// remove WordPress and BuddyPress callbacks to prevent recursion
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

		// do user update
		wp_update_user( array(
			'ID' => $user_id,
			'user_url' => $objectRef['url'],
		) );

		// re-add WordPress and BuddyPress callbacks
		$this->_add_hooks_wp();
		$this->_add_hooks_bp();

	}



	//##########################################################################



	/**
	 * Update a WP user when a CiviCRM contact is updated.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation
	 * @param string $objectName The type of object
	 * @param integer $objectId The ID of the object
	 * @param object $objectRef The object
	 * @return void
	 */
	public function civi_contact_updated( $op, $objectName, $objectId, $objectRef ) {

		// target our operation
		if ( $op != 'edit' ) return;

		// target our object type
		if ( $objectName != 'Individual' ) return;

		$this->_debug( array(
			'function' => 'civi_contact_updated',
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// check if we have a contact email
		if ( ! isset( $objectRef->email[0]->email ) ) {

			// no, init CiviCRM to get WP user ID
			if ( ! civi_wp()->initialize() ) return;

			// make sure Civi file is included
			require_once 'CRM/Core/BAO/UFMatch.php';

			// search using Civi's logic
			$user_id = CRM_Core_BAO_UFMatch::getUFId( $objectId );

			// kick out if we didn't get one
			if ( empty( $user_id ) ) return;

		} else {

			// yes, use it to get WP user
			$user = get_user_by( 'email', $objectRef->email[0]->email );

			// bail if not a WP user
			if ( ! ( $user instanceof WP_User ) ) return;

			// assign ID
			$user_id = $user->ID;

		}

		// update first name
		update_user_meta( $user_id, 'first_name', $objectRef->first_name );

		// update last name
		update_user_meta( $user_id, 'last_name', $objectRef->last_name );

		// compatibility with BP XProfile WordPress User Sync plugin
		if ( defined( 'BP_XPROFILE_WP_USER_SYNC_VERSION' ) ) {

			// access object
			global $bp_xprofile_wordpress_user_sync;

			// call the relevant sync method
			$bp_xprofile_wordpress_user_sync->intercept_wp_user_update( $user_id );

		}

		// avoid getting WP user unless we're debugging
		if ( CIVICRM_WP_PROFILE_SYNC_DEBUG ) {

			// for debugging, let get WP user
			$user = new WP_User( $user_id );

			$this->_debug( array(
				'user' => $user,
				'first_name' => $user->first_name,
				'last_name' => $user->last_name
			));

		}

	}



	//##########################################################################



	/**
	 * Prevent recursion when a WordPress user is about to be bulk added.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function civi_contact_bulk_added_pre() {

		// remove WordPress and BuddyPress callbacks to prevent recursion
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

	}



	/**
	 * Re-hook when a WordPress user has been bulk added.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	public function civi_contact_bulk_added_post() {

		// re-add WordPress and BuddyPress callbacks
		$this->_add_hooks_wp();
		$this->_add_hooks_bp();

	}



	//##########################################################################



	/**
	 * Add BuddyPress sync hooks.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	private function _add_hooks_bp() {

		// callbacks for new and updated BuddyPress user actions
		add_action( 'xprofile_updated_profile', array( $this, 'buddypress_contact_updated' ), 20, 3 );
		add_action( 'bp_core_signup_user', array( $this, 'buddypress_contact_updated' ), 20, 3 );
		add_action( 'bp_core_activated_user', array( $this, 'buddypress_contact_updated' ), 20, 3 );

	}



	/**
	 * Remove BuddyPress sync hooks.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	private function _remove_hooks_bp() {

		// remove callbacks for new and updated BuddyPress user actions
		remove_action( 'xprofile_updated_profile', array( $this, 'buddypress_contact_updated' ), 20 );
		remove_action( 'bp_core_signup_user', array( $this, 'buddypress_contact_updated' ), 20 );
		remove_action( 'bp_core_activated_user', array( $this, 'buddypress_contact_updated' ), 20 );

	}



	/**
	 * Add WordPress sync hooks.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	private function _add_hooks_wp() {

		// callbacks for new and updated WordPress user actions
		add_action( 'user_register', array( $this, 'wordpress_contact_updated' ), 100, 1 );
		add_action( 'profile_update', array( $this, 'wordpress_contact_updated' ), 100, 1 );

	}



	/**
	 * Remove WordPress sync hooks.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	private function _remove_hooks_wp() {

		// remove callbacks for new and updated WordPress user actions
		remove_action( 'user_register', array( $this, 'wordpress_contact_updated' ), 100 );
		remove_action( 'profile_update', array( $this, 'wordpress_contact_updated' ), 100 );

	}



	/**
	 * Add CiviCRM sync hooks.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	private function _add_hooks_civi() {

		// intercept contact update in CiviCRM
		add_action( 'civicrm_pre', array( $this, 'civi_contact_pre_update' ), 10, 4 );
		add_action( 'civicrm_post', array( $this, 'civi_contact_updated' ), 10, 4 );

		// intercept email update in CiviCRM
		add_action( 'civicrm_pre', array( $this, 'civi_primary_email_pre_update' ), 10, 4 );

		// intercept website update in CiviCRM
		add_action( 'civicrm_pre', array( $this, 'civi_website_pre_update' ), 10, 4 );

	}



	/**
	 * Remove CiviCRM sync hooks.
	 *
	 * @since 0.1
	 *
	 * @return void
	 */
	private function _remove_hooks_civi() {

		// remove all CiviCRM callbacks
		remove_action( 'civicrm_pre', array( $this, 'civi_contact_pre_update' ), 10 );
		remove_action( 'civicrm_post', array( $this, 'civi_contact_updated' ), 10 );
		remove_action( 'civicrm_pre', array( $this, 'civi_primary_email_pre_update' ), 10 );
		remove_action( 'civicrm_pre', array( $this, 'civi_website_pre_update' ), 10 );

	}



	/**
	 * Update a Civi contact's first name and last name.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WP user object
	 * @param object $civi_contact The Civi Contact object
	 * @return void
	 */
	private function _update_civi_name( $user, $civi_contact ) {

		// check if this is a BuddyPress General Settings update
		if ( function_exists( 'bp_is_current_action' ) AND bp_is_current_action( 'general' ) ) return;

		// update the Civi Contact first name and last name
		$contact = civicrm_api( 'contact', 'create', array(
			'version' => 3,
			'id' => $civi_contact->contact_id,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
		));

	}



	/**
	 * Update a Civi contact's primary email address.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WP user object
	 * @param object $civi_contact The Civi Contact object
	 * @return void
	 */
	private function _update_civi_primary_email( $user, $civi_contact ) {

		// get the current primary email
		$primary_email = civicrm_api( 'email', 'get', array(
			'version' => 3,
			'contact_id' => $civi_contact->contact_id,
			'is_primary' => 1,
		));

		// did we get one?
		if (
			isset( $primary_email['values'] )
			AND
			is_array( $primary_email['values'] )
			AND
			count( $primary_email['values'] ) > 0
		) {

			// get the first (and hopefully only) item
			$existing_data = array_pop( $primary_email['values'] );

			// has it changed?
			if ( $existing_data['email'] != $user->user_email ) {

				// now update their email
				$new_email = civicrm_api( 'email', 'create', array(
					'version' => 3,
					'id' => $primary_email['id'],
					'contact_id' => $civi_contact->contact_id,
					'email' => $user->user_email,
				));

			}

		}

	}



	/**
	 * Update a Civi contact's website address.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WP user object
	 * @param object $civi_contact The Civi Contact object
	 * @return void
	 */
	private function _update_civi_website( $user, $civi_contact ) {

		// get the current website
		$existing_website = civicrm_api( 'website', 'get', array(
			'version' => 3,
			'contact_id' => $civi_contact->contact_id,
			//'website_type_id' => 1,
		));

		// did we get one?
		if (
			isset( $existing_website['values'] )
			AND
			is_array( $existing_website['values'] )
			AND
			count( $existing_website['values'] ) > 0
		) {

			// run through the results
			foreach( $existing_website['values'] AS $website ) {

				// has it changed?
				if ( $website['url'] != $user->user_url ) {

					// are we updating?
					if ( $user->user_url != '' ) {

						// update their website
						$result = civicrm_api( 'website', 'create', array(
							'version' => 3,
							'id' => $website['id'],
							'contact_id' => $civi_contact->contact_id,
							'url' => $user->user_url,
						));

					} else {

						// delete their website
						$result = civicrm_api( 'website', 'delete', array(
							'version' => 3,
							'id' => $existing_website['id'],
						));

					}

				}

				// kick out as we only want the first
				break;

			} // end loop

		} else {

			// does the user have a website?
			if ( $user->user_url != '' ) {

				// create their website
				$result = civicrm_api( 'website', 'create', array(
					'version' => 3,
					'contact_id' => $civi_contact->contact_id,
					'url' => $user->user_url,
				));

			}

		}

	}



	/**
	 * Debugging.
	 *
	 * @since 0.1
	 *
	 * @param array $msg
	 * @return string
	 */
	private function _debug( $msg ) {

		// add to internal array
		$this->messages[] = $msg;

		// do we want output?
		if ( CIVICRM_WP_PROFILE_SYNC_DEBUG ) print_r( $msg );

	}



} // class ends



/**
 * Initialise our plugin after CiviCRM initialises.
 *
 * @since 0.1
 *
 * @return void
 */
function civicrm_wp_profile_sync_init() {

	// declare as global
	global $civicrm_wp_profile_sync;

	// init plugin
	$civicrm_wp_profile_sync = new CiviCRM_WP_Profile_Sync;

}

// add action for plugin init
add_action( 'civicrm_instance_loaded', 'civicrm_wp_profile_sync_init' );



