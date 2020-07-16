<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Profile Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-profile-sync
Description: Keeps a WordPress User profile in sync with CiviCRM Contact info.
Author: Christian Wach
Version: 0.3.2
Author URI: https://haystack.co.uk
Text Domain: civicrm-wp-profile-sync
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// Set plugin version here.
define( 'CIVICRM_WP_PROFILE_SYNC_VERSION', '0.3.2' );

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
		$this->hooks_wp_add();

		// Add BuddyPress callbacks.
		$this->hooks_bp_add();

		// Add CiviCRM callbacks.
		$this->hooks_civicrm_add();

		// Are we allowing bulk operations?
		if ( CIVICRM_WP_PROFILE_SYNC_BULK ) {

			// Add an item to the actions dropdown.
			add_action( 'civicrm_searchTasks', array( $this, 'civicrm_bulk_operations' ), 10, 2 );

			// Register PHP and template directories.
			add_action( 'civicrm_config', array( $this, 'register_php_directory' ), 10 );
			add_action( 'civicrm_config', array( $this, 'register_template_directory' ), 10 );

			// Prevent recursion when bulk adding WordPress Users via CiviCRM.
			add_action( 'civicrm_wp_profile_sync_user_add_pre', array( $this, 'civicrm_contact_bulk_added_pre' ), 10 );
			add_action( 'civicrm_wp_profile_sync_user_add_post', array( $this, 'civicrm_contact_bulk_added_post' ), 10 );

		}

		/**
		 * Broadcast that this plugin is active.
		 *
		 * @since 0.2.4
		 */
		 do_action( 'civicrm_wp_profile_sync_init' );

	}



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
	public function buddypress_user_updated( $user_id = 0, $posted_field_ids, $errors ) {

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
		if ( empty( $user_id ) ) {
			return false;
		}

		// Pass to our sync method
		$this->wordpress_user_updated( $user_id );

	}



	/**
	 * Updates a CiviCRM Contact when a WordPress User is updated.
	 *
	 * @since 0.1
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 */
	public function wordpress_user_updated( $user_id ) {

		// Get the User object.
		$user = get_userdata( $user_id );

		// Bail if we didn't get one.
		if ( ! ( $user instanceof WP_User ) ) {
			return;
		}

		// Init CiviCRM.
		if ( ! civi_wp()->initialize() ) {
			return;
		}

		// Get User matching file.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Remove CiviCRM and BuddyPress callbacks to prevent recursion.
		$this->hooks_bp_remove();
		$this->hooks_civicrm_remove();

		// Creates Contact if none exists - returns the CiviCRM UFMatch object.
		$contact = CRM_Core_BAO_UFMatch::synchronizeUFMatch(
			$user, // User object.
			$user->ID, // ID.
			$user->user_email, // Unique identifier.
			'WordPress', // CMS.
			null, // Status (unused).
			'Individual' // Contact type.
		);

		// Bail if we don't get one for some reason.
		if ( ! isset( $contact->contact_id ) ) {
			$this->hooks_bp_add();
			$this->hooks_civicrm_add();
			return;
		}

		// Should this User be synced?
		if ( ! $this->user_should_be_synced( $user, $contact ) ) {
			$this->hooks_bp_add();
			$this->hooks_civicrm_add();
			return;
		}

		// Update first name and last name.
		$this->civicrm_contact_name_update( $user, $contact );

		// Optionally update primary email.
		$this->civicrm_primary_email_update( $user, $contact );

		// Optionally update website.
		$this->civicrm_website_update( $user, $contact );

		// TODO: Add more built-in WordPress fields here...

		/**
		 * Allow plugins to hook into the sync process.
		 *
		 * @since 0.2.4
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param array $contact The array of CiviCRM Contact data.
		 */
		 do_action( 'civicrm_wp_profile_sync_wp_user_sync', $user, $contact );

		// Add CiviCRM and BuddyPress callbacks once more.
		$this->hooks_bp_add();
		$this->hooks_civicrm_add();

		/**
		 * Broadcast that a WordPress User has been synced.
		 *
		 * @since 0.2.4
		 *
		 * @param WP_User $user The WordPress User object.
		 * @param array $contact The array of CiviCRM Contact data.
		 */
		 do_action( 'civicrm_wp_profile_sync_wp_user_synced', $user, $contact );

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
	public function civicrm_contact_pre( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Individual' ) {
			return;
		}

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->hooks_wp_remove();
		$this->hooks_bp_remove();

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
	public function civicrm_primary_email_pre( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Email' ) {
			return;
		}

		// Bail if we have no email.
		if ( ! isset( $objectRef['email'] ) ) {
			return;
		}

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->hooks_wp_remove();
		$this->hooks_bp_remove();

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
	public function civicrm_website_pre( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Website' ) {
			return;
		}

		// Bail if we have no website.
		if ( ! isset( $objectRef['url'] ) ) {
			return;
		}

		// Bail if we have no Contact ID.
		if ( ! isset( $objectRef['contact_id'] ) ) {
			return;
		}

		// Init CiviCRM to get WordPress User ID.
		if ( ! civi_wp()->initialize() ) {
			return;
		}

		// Make sure CiviCRM file is included.
		require_once 'CRM/Core/BAO/UFMatch.php';

		// Search using CiviCRM's logic.
		$user_id = CRM_Core_BAO_UFMatch::getUFId( $objectRef['contact_id'] );

		// Kick out if we didn't get one.
		if ( empty( $user_id ) ) {
			return;
		}

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->hooks_wp_remove();
		$this->hooks_bp_remove();

		// Do User update.
		wp_update_user( array(
			'ID' => $user_id,
			'user_url' => $objectRef['url'],
		) );

		// Re-add WordPress and BuddyPress callbacks.
		$this->hooks_wp_add();
		$this->hooks_bp_add();

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
	public function civicrm_contact_updated( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Individual' ) {
			return;
		}

		// Check if we have a Contact email.
		if ( ! isset( $objectRef->email[0]->email ) ) {

			// No, init CiviCRM to get WordPress User ID.
			if ( ! civi_wp()->initialize() ) {
				return;
			}

			// Make sure CiviCRM file is included.
			require_once 'CRM/Core/BAO/UFMatch.php';

			// Search using CiviCRM's logic.
			$user_id = CRM_Core_BAO_UFMatch::getUFId( $objectId );

			// Kick out if we didn't get one.
			if ( empty( $user_id ) ) {
				return;
			}

		} else {

			// Yes, use it to get WordPress User.
			$user = get_user_by( 'email', $objectRef->email[0]->email );

			// Bail if not a WordPress User.
			if ( ! ( $user instanceof WP_User ) OR ! $user->exists() ) {
				return;
			}

			// Assign ID.
			$user_id = $user->ID;

		}

		// Should this Contact be synced?
		if ( ! $this->contact_should_be_synced( $objectRef, $user_id ) ) {
			return;
		}

		// Remove CiviCRM's own callbacks.
		$this->hooks_misc_remove();

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

		// Add back CiviCRM's own callbacks.
		$this->hooks_misc_add();

	}



	//##########################################################################



	/**
	 * Check if a WordPress User should by synced.
	 *
	 * @since 0.3
	 *
	 * @param object $user The WordPress User object.
	 * @param object $contact The CiviCRM Contact object.
	 * @return bool $should_be_synced Whether or not the User should be synced.
	 */
	public function user_should_be_synced( $user, $contact ) {

		// Assume User should be synced.
		$should_be_synced = true;

		/**
		 * Let other plugins override whether a WordPress User should be synced.
		 *
		 * @since 0.3
		 *
		 * @param bool $should_be_synced True if the User should be synced, false otherwise.
		 * @param object $user The WordPress User object.
		 * @param object $contact The CiviCRM Contact object.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'civicrm_wp_profile_sync_user_should_be_synced', $should_be_synced, $user, $contact );

	}



	/**
	 * Check if a CiviCRM Contact should by synced.
	 *
	 * @since 0.3
	 *
	 * @param object $contact The CiviCRM Contact object.
	 * @param int $user_id The numeric ID of the WordPress User.
	 * @return bool $should_be_synced Whether or not the Contact should be synced.
	 */
	public function contact_should_be_synced( $contact, $user_id ) {

		// Assume Contact should be synced.
		$should_be_synced = true;

		/**
		 * Let other plugins override whether a CiviCRM Contact should be synced.
		 *
		 * @since 0.3
		 *
		 * @param bool $should_be_synced True if the Contact should be synced, false otherwise.
		 * @param object $contact The CiviCRM Contact object.
		 * @param int $user_id The numeric ID of the WordPress User.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'civicrm_wp_profile_sync_contact_should_be_synced', $should_be_synced, $contact, $user_id );

	}



	/**
	 * Check if a CiviCRM Contact's name should by synced.
	 *
	 * @since 0.3
	 *
	 * @param object $user The WordPress User object.
	 * @param object $contact The CiviCRM Contact object.
	 * @return bool $should_be_synced Whether or not the Contact's name should be synced.
	 */
	public function contact_name_should_be_synced( $user, $contact ) {

		// Assume User should be synced.
		$should_be_synced = true;

		/**
		 * Let other plugins override whether a CiviCRM Contact's name should be synced.
		 *
		 * @since 0.3
		 *
		 * @param bool $should_be_synced True if the Contact's name should be synced, false otherwise.
		 * @param object $user The WordPress User object.
		 * @param object $contact The CiviCRM Contact object.
		 * @return bool $should_be_synced The modified value of the sync flag.
		 */
		return apply_filters( 'civicrm_wp_profile_sync_contact_name_should_be_synced', $should_be_synced, $user, $contact );

	}



	//##########################################################################



	/**
	 * Update a CiviCRM Contact's first name and last name.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WordPress User object.
	 * @param object $contact The CiviCRM Contact object.
	 */
	private function civicrm_contact_name_update( $user, $contact ) {

		// Check if this is a BuddyPress General Settings update.
		if ( function_exists( 'bp_is_current_action' ) AND bp_is_current_action( 'general' ) ) {
			return;
		}

		// Should this Contact name be synced?
		if ( ! $this->contact_name_should_be_synced( $user, $contact ) ) {
			return;
		}

		// Update the CiviCRM Contact first name and last name.
		$result = civicrm_api( 'contact', 'create', array(
			'version' => 3,
			'id' => $contact->contact_id,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
		));

        // Log something on failure.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			error_log( print_r( array(
				'method' => __METHOD__,
				'message' => __( 'Could not update the name of the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
				'result' => $result,
			), true ) );
		}

	}



	/**
	 * Update a CiviCRM Contact's primary email address.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WordPress User object.
	 * @param object $contact The CiviCRM Contact object.
	 */
	private function civicrm_primary_email_update( $user, $contact ) {

		// Get the current primary email.
		$primary_email = civicrm_api( 'email', 'get', array(
			'version' => 3,
			'contact_id' => $contact->contact_id,
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
				$result = civicrm_api( 'email', 'create', array(
					'version' => 3,
					'id' => $primary_email['id'],
					'contact_id' => $contact->contact_id,
					'email' => $user->user_email,
				));

				// Log something on failure.
				if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
					error_log( print_r( array(
						'method' => __METHOD__,
						'message' => __( 'Could not update the email of the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
						'result' => $result,
					), true ) );
				}

			}

		}

	}



	/**
	 * Update a CiviCRM Contact's website address.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WordPress User object.
	 * @param object $contact The CiviCRM Contact object.
	 */
	private function civicrm_website_update( $user, $contact ) {

		// Get the current website.
		$existing_website = civicrm_api( 'website', 'get', array(
			'version' => 3,
			'contact_id' => $contact->contact_id,
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
							'contact_id' => $contact->contact_id,
							'url' => $user->user_url,
						));

						// Log something on failure.
						if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
							error_log( print_r( array(
								'method' => __METHOD__,
								'message' => __( 'Could not update the website for the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
								'result' => $result,
							), true ) );
						}

					} else {

						// Delete their website.
						$result = civicrm_api( 'website', 'delete', array(
							'version' => 3,
							'id' => $existing_website['id'],
						));

						// Log something on failure.
						if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
							error_log( print_r( array(
								'method' => __METHOD__,
								'message' => __( 'Could not delete the website for the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
								'result' => $result,
							), true ) );
						}
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
					'contact_id' => $contact->contact_id,
					'url' => $user->user_url,
				));

				// Log something on failure.
				if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
					error_log( print_r( array(
						'method' => __METHOD__,
						'message' => __( 'Could not create the website for the CiviCRM Contact.', 'civicrm-wp-profile-sync' ),
						'result' => $result,
					), true ) );
				}
	}

		}

	}



	//##########################################################################



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
		if ( ! civi_wp()->initialize() ) {
			return;
		}

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
		if ( ! civi_wp()->initialize() ) {
			return;
		}

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
	public function civicrm_bulk_operations( $object_name, &$tasks ) {

		// Only handle Contacts.
		if ( $object_name != 'contact' ) {
			return;
		}

		// Add our item to the tasks array.
		$tasks[] = array(
			'title' => __( 'Create WordPress Users from Contacts',  'civicrm-wp-profile-sync' ),
			'class' => 'CRM_Contact_Form_Task_CreateWordPressUsers',
		);

	}



	//##########################################################################



	/**
	 * Prevent recursion when a WordPress User is about to be bulk added.
	 *
	 * @since 0.1
	 */
	public function civicrm_contact_bulk_added_pre() {

		// Remove WordPress and BuddyPress callbacks to prevent recursion.
		$this->hooks_wp_remove();
		$this->hooks_bp_remove();

	}



	/**
	 * Re-hook when a WordPress User has been bulk added.
	 *
	 * @since 0.1
	 */
	public function civicrm_contact_bulk_added_post() {

		// Re-add WordPress and BuddyPress callbacks.
		$this->hooks_wp_add();
		$this->hooks_bp_add();

	}



	//##########################################################################



	/**
	 * Add BuddyPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_bp_add() {

		// Callbacks for new and updated BuddyPress User actions.
		add_action( 'xprofile_updated_profile', array( $this, 'buddypress_user_updated' ), 20, 3 );
		add_action( 'bp_core_signup_user', array( $this, 'buddypress_user_updated' ), 20, 3 );
		add_action( 'bp_core_activated_user', array( $this, 'buddypress_user_updated' ), 20, 3 );

	}



	/**
	 * Remove BuddyPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_bp_remove() {

		// Remove callbacks for new and updated BuddyPress User actions.
		remove_action( 'xprofile_updated_profile', array( $this, 'buddypress_user_updated' ), 20 );
		remove_action( 'bp_core_signup_user', array( $this, 'buddypress_user_updated' ), 20 );
		remove_action( 'bp_core_activated_user', array( $this, 'buddypress_user_updated' ), 20 );

	}



	/**
	 * Add WordPress sync hooks.
	 *
	 * Post-processes a CiviCRM Contact when a WordPress User is updated.
	 * Hooked in late to let other plugins go first.
	 *
	 * @since 0.1
	 */
	public function hooks_wp_add() {

		// Callbacks for new and updated WordPress User actions.
		add_action( 'user_register', array( $this, 'wordpress_user_updated' ), 100, 1 );
		add_action( 'profile_update', array( $this, 'wordpress_user_updated' ), 100, 1 );

	}



	/**
	 * Remove WordPress sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_wp_remove() {

		// Remove callbacks for new and updated WordPress User actions.
		remove_action( 'user_register', array( $this, 'wordpress_user_updated' ), 100 );
		remove_action( 'profile_update', array( $this, 'wordpress_user_updated' ), 100 );

	}



	/**
	 * Add CiviCRM sync hooks.
	 *
	 * Syncs data to a WordPress User when a CiviCRM Contact is updated.
	 *
	 * @since 0.1
	 */
	public function hooks_civicrm_add() {

		// Intercept Contact update in CiviCRM.
		add_action( 'civicrm_pre', array( $this, 'civicrm_contact_pre' ), 10, 4 );
		add_action( 'civicrm_post', array( $this, 'civicrm_contact_updated' ), 10, 4 );

		// Intercept email update in CiviCRM.
		add_action( 'civicrm_pre', array( $this, 'civicrm_primary_email_pre' ), 10, 4 );

		// Intercept website update in CiviCRM.
		add_action( 'civicrm_pre', array( $this, 'civicrm_website_pre' ), 10, 4 );

	}



	/**
	 * Remove CiviCRM sync hooks.
	 *
	 * @since 0.1
	 */
	public function hooks_civicrm_remove() {

		// Remove all CiviCRM callbacks.
		remove_action( 'civicrm_pre', array( $this, 'civicrm_contact_pre' ), 10 );
		remove_action( 'civicrm_post', array( $this, 'civicrm_contact_updated' ), 10 );
		remove_action( 'civicrm_pre', array( $this, 'civicrm_primary_email_pre' ), 10 );
		remove_action( 'civicrm_pre', array( $this, 'civicrm_website_pre' ), 10 );

	}



	/**
	 * Add back CiviCRM's callbacks.
	 *
	 * This method undoes the removal of the callbacks below.
	 *
	 * @see self::hooks_core_remove()
	 *
	 * @since 0.3.1
	 */
	private function hooks_core_add() {

		// Get CiviCRM instance.
		$civicrm = civi_wp();

		// Do we have the old-style plugin structure?
		if ( method_exists( $civicrm, 'update_user' ) ) {

			// Re-add previous CiviCRM plugin filters.
			add_action( 'user_register', array( $civicrm, 'update_user' ) );
			add_action( 'profile_update', array( $civicrm, 'update_user' ) );

		} else {

			// Re-add current CiviCRM plugin filters.
			add_action( 'user_register', array( $civicrm->users, 'update_user' ) );
			add_action( 'profile_update', array( $civicrm->users, 'update_user' ) );

		}

		/**
		 * Let other plugins know that we're adding user actions.
		 *
		 * @since 0.3.1
		 */
		do_action( 'civicrm_wp_profile_sync_hooks_core_added' );

	}



	/**
	 * Remove CiviCRM's callbacks.
	 *
	 * These may cause recursive updates when creating or editing a WordPress
	 * user. This doesn't seem to have been necessary in the past, but seems
	 * to be causing trouble when newer versions of BuddyPress and CiviCRM are
	 * active.
	 *
	 * @since 0.3.1
	 */
	private function hooks_core_remove() {

		// Get CiviCRM instance.
		$civicrm = civi_wp();

		// Do we have the old-style plugin structure?
		if ( method_exists( $civicrm, 'update_user' ) ) {

			// Remove previous CiviCRM plugin filters.
			remove_action( 'user_register', array( $civicrm, 'update_user' ) );
			remove_action( 'profile_update', array( $civicrm, 'update_user' ) );

		} else {

			// Remove current CiviCRM plugin filters.
			remove_action( 'user_register', array( $civicrm->users, 'update_user' ) );
			remove_action( 'profile_update', array( $civicrm->users, 'update_user' ) );

		}

		/**
		 * Let other plugins know that we're removing CiviCRM's callbacks.
		 *
		 * @since 0.3.1
		 */
		do_action( 'civicrm_wp_profile_sync_hooks_core_removed' );

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



