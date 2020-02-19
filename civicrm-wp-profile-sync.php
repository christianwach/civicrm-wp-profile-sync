<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Profile Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-profile-sync
Description: Keeps a WordPress User profile in sync with CiviCRM Contact info.
Author: Christian Wach
Version: 0.3
Author URI: http://haystack.co.uk
Text Domain: civicrm-wp-profile-sync
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// Set plugin version here.
define( 'CIVICRM_WP_PROFILE_SYNC_VERSION', '0.3' );

// Set our debug flag here.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_DEBUG' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_DEBUG', false );
}

// Set our bulk operations flag here.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_BULK' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_BULK', false );
}

// Store reference to this file.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_FILE' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_FILE', __FILE__ );
}

// Store URL to this plugin's directory.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_URL' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_URL', plugin_dir_url( CIVICRM_WP_PROFILE_SYNC_FILE ) );
}

// Store PATH to this plugin's directory.
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_PATH' ) ) {
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
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

		// Use translation.
		$this->translation();

		// Add WordPress callbacks.
		$this->_add_hooks_wp();

		// Add BuddyPress callbacks.
		$this->_add_hooks_bp();

		// Add CiviCRM callbacks.
		$this->_add_hooks_civi();

		// Are we allowing bulk operations?
		if ( CIVICRM_WP_PROFILE_SYNC_BULK ) {

			// Add an item to the actions dropdown.
			add_action( 'civicrm_searchTasks', array( $this, 'civi_bulk_operations' ), 10, 2 );

			// Register PHP and template directories.
			add_action( 'civicrm_config', array( $this, 'register_php_directory' ), 10 );
			add_action( 'civicrm_config', array( $this, 'register_template_directory' ), 10 );

			// Prevent recursion when bulk adding WordPress Users via CiviCRM.
			add_action( 'civicrm_wp_profile_sync_user_add_pre', array( $this, 'civi_contact_bulk_added_pre' ), 10 );
			add_action( 'civicrm_wp_profile_sync_user_add_post', array( $this, 'civi_contact_bulk_added_post' ), 10 );

		}

		/**
		 * Broadcast that this plugin is active.
		 *
		 * @since 0.2.4
		 */
		 do_action( 'civicrm_wp_profile_sync_init' );

	}



	//##########################################################################



	/**
	 * Enable translation.
	 *
	 * @since 0.1
	 */
	public function translation() {

		// Load translations.
		load_plugin_textdomain(
			'civicrm-wp-profile-sync', // Unique name.
			false, // Deprecated argument.
			dirname( plugin_basename( CIVICRM_WP_PROFILE_SYNC_FILE ) ) . '/languages/' // Relative path to files.
		);

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
	 * @param object $config The CiviCRM config object.
	 */
	public function register_php_directory( &$config ) {

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return;

		// Define our custom path.
		$custom_path = CIVICRM_WP_PROFILE_SYNC_PATH . 'civicrm_custom_php';

		// Add to include path.
		$include_path = $custom_path . PATH_SEPARATOR . get_include_path();
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
		$custom_path = CIVICRM_WP_PROFILE_SYNC_PATH . 'civicrm_custom_templates';

		// Kick out if no CiviCRM.
		if ( ! civi_wp()->initialize() ) return;

		// Get template instance.
		$template = CRM_Core_Smarty::singleton();

		// Add our custom template directory.
		$template->addTemplateDir( $custom_path );

		// Register template directories.
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		set_include_path( $template_include_path );

	}



	/**
	 * Add an option to the Actions dropdown.
	 *
	 * @since 0.1
	 *
	 * @param str $object_name The CiviCRM object type.
	 * @param array $tasks The CiviCRM tasks array to add our option to.
	 */
	public function civi_bulk_operations( $object_name, &$tasks ) {

		// Only handle Contacts.
		if ( $object_name != 'contact' ) return;

		// Add our item to the tasks array.
		$tasks[] = array(
			'title' => __( 'Create WordPress Users from Contacts',  'civicrm-wp-profile-sync' ),
			'class' => 'CRM_Contact_Form_Task_CreateWordPressUsers',
		);

	}



	//##########################################################################



	/**
	 * Intercept BuddyPress's attempt to sync to WordPress User profile.
	 *
	 * @since 0.1
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @param array $posted_field_ids The array of numeric IDs of the BuddyPress fields.
	 * @param boolean $errors True if there are errors, false otherwise.
	 */
	public function buddypress_contact_updated( $user_id = 0, $posted_field_ids, $errors ) {

		$this->_debug( array(
			'method' => __METHOD__,
		));

		// Get BuddyPress instance.
		$bp = buddypress();

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( ! empty( $bp->site_options['bp-disable-profile-sync'] ) && (int) $bp->site_options['bp-disable-profile-sync'] ) {
			return true;
		}

		// Fetch logged-in User if none set.
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Bail if no User ID.
		if ( empty( $user_id ) ) return false;

		// Pass to our sync method
		$this->wordpress_contact_updated( $user_id );

	}



	/**
	 * Updates a CiviCRM Contact when a WordPress User is updated.
	 *
	 * @since 0.1
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 */
	public function wordpress_contact_updated( $user_id ) {

		$this->_debug( array(
			'method' => __METHOD__,
			'user_id' => $user_id,
		));

		// Okay, get User object.
		$user = get_userdata( $user_id );

		// Bail if we didn't get one.
		if ( ! ( $user instanceof WP_User ) ) return;

		// Init CiviCRM.
		if ( ! civi_wp()->initialize() ) return;

		// Get User matching file.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Remove CiviCRM and BuddyPress callbacks to prevent recursion.
		$this->_remove_hooks_bp();
		$this->_remove_hooks_civi();

		// Get the CiviCRM Contact object.
		$civi_contact = CRM_Core_BAO_UFMatch::synchronizeUFMatch(
			$user, // User object.
			$user->ID, // ID.
			$user->user_email, // Unique identifier.
			'WordPress', // CMS.
			null, // Status (unused).
			'Individual' // Contact type.
		);

		// Bail if we don't get one for some reason.
		if ( ! isset( $civi_contact->contact_id ) ) {
			$this->_add_hooks_bp();
			$this->_add_hooks_civi();
			return;
		}

		// Update first name and last name.
		$this->_update_civi_name( $user, $civi_contact );

		// Optionally update primary email.
		$this->_update_civi_primary_email( $user, $civi_contact );

		// Optionally update website.
		$this->_update_civi_website( $user, $civi_contact );

		// Add more built-in WordPress fields here...

		/**
		 * Allow plugins to hook into the sync process.
		 *
		 * @since 0.2.4
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param array $civi_contact The array of CiviCRM Contact data.
		 */
		 do_action( 'civicrm_wp_profile_sync_wp_user_sync', $user, $civi_contact );

		// Add CiviCRM and BuddyPress callbacks once more.
		$this->_add_hooks_bp();
		$this->_add_hooks_civi();

		/**
		 * Broadcast that a WordPress User has been synced.
		 *
		 * @since 0.2.4
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param array $civi_contact The array of CiviCRM Contact data.
		 */
		 do_action( 'civicrm_wp_profile_sync_wp_user_synced', $user, $civi_contact );

	}



	//##########################################################################



	/**
	 * Fires when a CiviCRM Contact is updated, but prior to any operations taking place.
	 *
	 * This is used as a means by which to discover the direction of the update, because
	 * if the update is initiated from the WordPress side, this callback will have been
	 * unhooked and will not be called.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function civi_contact_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) return;

		// Target our object type.
		if ( $objectName != 'Individual' ) return;

		$this->_debug( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

	}



	/**
	 * Prevent recursion when a CiviCRM Contact's primary email address is updated.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function civi_primary_email_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) return;

		// Target our object type.
		if ( $objectName != 'Email' ) return;

		// Bail if we have no email.
		if ( ! isset( $objectRef['email'] ) ) return;

		$this->_debug( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

		/**
		 * Fires when a CiviCRM Contact's primary email address is about to be
		 * synced to the linked WordPress User's email address.
		 *
		 * The change of email in WordPress causes a notification email to be
		 * sent to the WordPress User. This can be suppressed using this action
		 * as the trigger to do so.
		 *
		 * @since 0.2.7
		 *
		 * @param integer $objectId The ID of the object.
		 * @param object $objectRef The object.
		 */
		do_action( 'civicrm_wp_profile_sync_primary_email_pre_update', $objectId, $objectRef );

	}



	/**
	 * Update a WordPress User when a CiviCRM Contact's website is updated.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function civi_website_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Website' ) return;

		// Bail if we have no website.
		if ( ! isset( $objectRef['url'] ) ) return;

		// Bail if we have no Contact ID.
		if ( ! isset( $objectRef['contact_id'] ) ) return;

		$this->_debug( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// Init CiviCRM to get WordPress User ID.
		if ( ! civi_wp()->initialize() ) return;

		// Make sure CiviCRM file is included.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Search using CiviCRM's logic.
		$user_id = CRM_Core_BAO_UFMatch::getUFId( $objectRef['contact_id'] );

		$this->_debug( array(
			'method' => __METHOD__,
			'user_id' => $user_id,
		));

		// Kick out if we didn't get one.
		if ( empty( $user_id ) ) return;

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

		// Do User update.
		wp_update_user( array(
			'ID' => $user_id,
			'user_url' => $objectRef['url'],
		) );

		// Re-add WordPress and BuddyPress callbacks.
		$this->_add_hooks_wp();
		$this->_add_hooks_bp();

		/**
		 * Broadcast that a CiviCRM Contact's website has been synced.
		 *
		 * @since 0.2.4
		 *
		 * @param integer $user_id The ID of the WordPress User.
		 * @param integer $objectId The ID of the CiviCRM Contact.
		 * @param object $objectRef The CiviCRM Contact object.
		 */
		 do_action( 'civicrm_wp_profile_sync_website_synced', $user_id, $objectId, $objectRef );

	}



	//##########################################################################



	/**
	 * Update a WordPress User when a CiviCRM Contact is updated.
	 *
	 * @since 0.1
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function civi_contact_updated( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) return;

		// Target our object type.
		if ( $objectName != 'Individual' ) return;

		$this->_debug( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// Check if we have a Contact email.
		if ( ! isset( $objectRef->email[0]->email ) ) {

			// No, init CiviCRM to get WordPress User ID.
			if ( ! civi_wp()->initialize() ) return;

			// Make sure CiviCRM file is included.
			require_once 'CRM/Core/BAO/UFMatch.php';

			// Search using CiviCRM's logic.
			$user_id = CRM_Core_BAO_UFMatch::getUFId( $objectId );

			// Kick out if we didn't get one.
			if ( empty( $user_id ) ) return;

		} else {

			// Yes, use it to get WordPress User.
			$user = get_user_by( 'email', $objectRef->email[0]->email );

			// Bail if not a WordPress User.
			if ( ! ( $user instanceof WP_User ) ) return;

			// Assign ID.
			$user_id = $user->ID;

		}

		// Update first name.
		update_user_meta( $user_id, 'first_name', $objectRef->first_name );

		// Update last name.
		update_user_meta( $user_id, 'last_name', $objectRef->last_name );

		// Compatibility with BP XProfile WordPress User Sync plugin.
		if ( defined( 'BP_XPROFILE_WP_USER_SYNC_VERSION' ) ) {

			// Access object.
			global $bp_xprofile_wordpress_user_sync;

			// Call the relevant sync method.
			$bp_xprofile_wordpress_user_sync->intercept_wp_user_update( $user_id );

		}

		// Avoid getting WordPress User unless we're debugging.
		if ( CIVICRM_WP_PROFILE_SYNC_DEBUG ) {

			// For debugging, let get WordPress User.
			$user = new WP_User( $user_id );

			$this->_debug( array(
				'user' => $user,
				'first_name' => $user->first_name,
				'last_name' => $user->last_name
			));

		}

		/**
		 * Broadcast that a CiviCRM Contact has been synced.
		 *
		 * @since 0.2.4
		 *
		 * @param integer $objectId The ID of the CiviCRM Contact.
		 * @param object $objectRef The CiviCRM Contact object.
		 * @param integer $user_id The ID of the WordPress User.
		 */
		 do_action( 'civicrm_wp_profile_sync_civi_contact_synced', $objectId, $objectRef, $user_id );

	}



	//##########################################################################



	/**
	 * Prevent recursion when a WordPress User is about to be bulk added.
	 *
	 * @since 0.1
	 */
	public function civi_contact_bulk_added_pre() {

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

	}



	/**
	 * Re-hook when a WordPress User has been bulk added.
	 *
	 * @since 0.1
	 */
	public function civi_contact_bulk_added_post() {

		// Re-add WordPress and BuddyPress callbacks.
		$this->_add_hooks_wp();
		$this->_add_hooks_bp();

	}



	//##########################################################################



	/**
	 * Add BuddyPress sync hooks.
	 *
	 * @since 0.1
	 */
	private function _add_hooks_bp() {

		// Callbacks for new and updated BuddyPress User actions.
		add_action( 'xprofile_updated_profile', array( $this, 'buddypress_contact_updated' ), 20, 3 );
		add_action( 'bp_core_signup_user', array( $this, 'buddypress_contact_updated' ), 20, 3 );
		add_action( 'bp_core_activated_user', array( $this, 'buddypress_contact_updated' ), 20, 3 );

	}



	/**
	 * Remove BuddyPress sync hooks.
	 *
	 * @since 0.1
	 */
	private function _remove_hooks_bp() {

		// Remove callbacks for new and updated BuddyPress User actions.
		remove_action( 'xprofile_updated_profile', array( $this, 'buddypress_contact_updated' ), 20 );
		remove_action( 'bp_core_signup_user', array( $this, 'buddypress_contact_updated' ), 20 );
		remove_action( 'bp_core_activated_user', array( $this, 'buddypress_contact_updated' ), 20 );

	}



	/**
	 * Add WordPress sync hooks.
	 *
	 * Post-processes a CiviCRM Contact when a WordPress User is updated.
	 * Hooked in late to let other plugins go first.
	 *
	 * @since 0.1
	 */
	private function _add_hooks_wp() {

		// Callbacks for new and updated WordPress User actions.
		add_action( 'user_register', array( $this, 'wordpress_contact_updated' ), 100, 1 );
		add_action( 'profile_update', array( $this, 'wordpress_contact_updated' ), 100, 1 );

	}



	/**
	 * Remove WordPress sync hooks.
	 *
	 * @since 0.1
	 */
	private function _remove_hooks_wp() {

		// Remove callbacks for new and updated WordPress User actions.
		remove_action( 'user_register', array( $this, 'wordpress_contact_updated' ), 100 );
		remove_action( 'profile_update', array( $this, 'wordpress_contact_updated' ), 100 );

	}



	/**
	 * Add CiviCRM sync hooks.
	 *
	 * Syncs data to a WordPress User when a CiviCRM Contact is updated.
	 *
	 * @since 0.1
	 */
	private function _add_hooks_civi() {

		// Intercept Contact update in CiviCRM.
		add_action( 'civicrm_pre', array( $this, 'civi_contact_pre_update' ), 10, 4 );
		add_action( 'civicrm_post', array( $this, 'civi_contact_updated' ), 10, 4 );

		// Intercept email update in CiviCRM.
		add_action( 'civicrm_pre', array( $this, 'civi_primary_email_pre_update' ), 10, 4 );

		// Intercept website update in CiviCRM.
		add_action( 'civicrm_pre', array( $this, 'civi_website_pre_update' ), 10, 4 );

	}



	/**
	 * Remove CiviCRM sync hooks.
	 *
	 * @since 0.1
	 */
	private function _remove_hooks_civi() {

		// Remove all CiviCRM callbacks.
		remove_action( 'civicrm_pre', array( $this, 'civi_contact_pre_update' ), 10 );
		remove_action( 'civicrm_post', array( $this, 'civi_contact_updated' ), 10 );
		remove_action( 'civicrm_pre', array( $this, 'civi_primary_email_pre_update' ), 10 );
		remove_action( 'civicrm_pre', array( $this, 'civi_website_pre_update' ), 10 );

	}



	/**
	 * Update a CiviCRM Contact's first name and last name.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WordPress User object.
	 * @param object $civi_contact The CiviCRM Contact object.
	 */
	private function _update_civi_name( $user, $civi_contact ) {

		// Check if this is a BuddyPress General Settings update.
		if ( function_exists( 'bp_is_current_action' ) AND bp_is_current_action( 'general' ) ) return;
		
		// Bail if no name, as we don't want to overwrite Civi with blank values.
		if (!$user->first_name && !$user->last_name) return;

		// Update the CiviCRM Contact first name and last name.
		$contact = civicrm_api( 'contact', 'create', array(
			'version' => 3,
			'id' => $civi_contact->contact_id,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
		));

	}



	/**
	 * Update a CiviCRM Contact's primary email address.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WordPress User object.
	 * @param object $civi_contact The CiviCRM Contact object.
	 */
	private function _update_civi_primary_email( $user, $civi_contact ) {

		// Get the current primary email.
		$primary_email = civicrm_api( 'email', 'get', array(
			'version' => 3,
			'contact_id' => $civi_contact->contact_id,
			'is_primary' => 1,
		));

		// Did we get one?
		if (
			isset( $primary_email['values'] ) AND
			is_array( $primary_email['values'] ) AND
			count( $primary_email['values'] ) > 0
		) {

			// Get the first (and hopefully only) item.
			$existing_data = array_pop( $primary_email['values'] );

			// Has it changed?
			if ( $existing_data['email'] != $user->user_email ) {

				// Now update their email.
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
	 * Update a CiviCRM Contact's website address.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WordPress User object.
	 * @param object $civi_contact The CiviCRM Contact object.
	 */
	private function _update_civi_website( $user, $civi_contact ) {

		// Get the current website.
		$existing_website = civicrm_api( 'website', 'get', array(
			'version' => 3,
			'contact_id' => $civi_contact->contact_id,
			//'website_type_id' => 1,
		));

		// Did we get one?
		if (
			isset( $existing_website['values'] ) AND
			is_array( $existing_website['values'] ) AND
			count( $existing_website['values'] ) > 0
		) {

			// Run through the results.
			foreach( $existing_website['values'] AS $website ) {

				// Has it changed?
				if ( $website['url'] != $user->user_url ) {

					// Are we updating?
					if ( $user->user_url != '' ) {

						// Update their website.
						$result = civicrm_api( 'website', 'create', array(
							'version' => 3,
							'id' => $website['id'],
							'contact_id' => $civi_contact->contact_id,
							'url' => $user->user_url,
						));

					} else {

						// Delete their website.
						$result = civicrm_api( 'website', 'delete', array(
							'version' => 3,
							'id' => $existing_website['id'],
						));

					}

				}

				// Kick out as we only want the first.
				break;

			} // End loop

		} else {

			// Does the User have a website?
			if ( $user->user_url != '' ) {

				// Create their website
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
	 * @param array $msg The message to log.
	 */
	private function _debug( $msg ) {

		// Do we want output?
		if ( CIVICRM_WP_PROFILE_SYNC_DEBUG ) {

			// Uncomment this to add a backtrace.
			//$msg['backtrace'] = wp_debug_backtrace_summary();

			// Log the message.
			error_log( print_r( $msg, true ) );

		}

	}



} // Class ends.



/**
 * Load plugin if not yet loaded and return reference.
 *
 * @since 0.1
 *
 * @return CiviCRM_WP_Profile_Sync $civicrm_wp_profile_sync The plugin reference.
 */
function civicrm_wp_profile_sync() {

	// Declare as global.
	global $civicrm_wp_profile_sync;

	// Instantiate plugin if not yet instantiated.
	if ( ! isset( $civicrm_wp_profile_sync ) ) {
		$civicrm_wp_profile_sync = new CiviCRM_WP_Profile_Sync();
	}

	// --<
	return $civicrm_wp_profile_sync;

}

// Load only when CiviCRM has loaded.
add_action( 'civicrm_instance_loaded', 'civicrm_wp_profile_sync' );



