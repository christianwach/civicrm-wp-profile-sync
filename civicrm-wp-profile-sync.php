<?php
/*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Profile Sync
Description: Keeps a WordPress User profile in sync with CiviCRM Contact info
Version: 0.1
Author: Christian Wach
Author URI: http://haystack.co.uk
Plugin URI: http://haystack.co.uk
--------------------------------------------------------------------------------
*/



// set our debug flag here
define( 'CIVICRM_WP_PROFILE_SYNC_DEBUG', false );

// set our version here
define( 'CIVICRM_WP_PROFILE_SYNC_VERSION', '0.1' );



/*
--------------------------------------------------------------------------------
CiviCRM_WP_Profile_Sync Class
--------------------------------------------------------------------------------
*/

class CiviCRM_WP_Profile_Sync {

	/** 
	 * properties
	 */
	
	// error messages
	public $messages = array();
	
	
	
	/** 
	 * @description: initialises this object
	 * @return object
	 */
	function __construct() {
	
		// post process CiviCRM contact when WP user is updated, done late to let other plugins go first
		add_action( 'user_register', array( $this, 'wordpress_contact_updated' ), 100, 1 );
		add_action( 'profile_update', array( $this, 'wordpress_contact_updated' ), 100, 1 );
		
		// set directional flag because the primary email is updated before the contact
		add_action( 'civicrm_pre', array( $this, 'civi_primary_email_will_be_updated' ), 10, 4 );
		
		// sync a WP user when a CiviCRM contact is updated
		add_action( 'civicrm_post', array( $this, 'civi_contact_updated' ), 10, 4 );
		
		// update the default name field before xprofile_sync_wp_profile is called
		add_action( 'xprofile_updated_profile', array( $this, 'buddypress_contact_updated' ), 20, 3 );
		add_action( 'bp_core_signup_user', array( $this, 'buddypress_contact_updated' ), 20, 3 );
		add_action( 'bp_core_activated_user', array( $this, 'buddypress_contact_updated' ), 20, 3 );
		
		// --<
		return $this;

	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * @description: intercept BP core's attempt to sync to WP user profile
	 * @param integer $user_id
	 * @param array $posted_field_ids
	 * @param boolean $errors
	 * @return nothing
	 */
	public function buddypress_contact_updated( $user_id = 0, $posted_field_ids, $errors ) {
		
		$this->_debug( array( 
			'function' => 'buddypress_contact_updated',
			'this->direction' => isset( $this->direction ) ? $this->direction : 'direction not set',
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
	 * @description: updates a CiviCRM Contact when a WordPress user is updated
	 * @param integer $user_id
	 * @return nothing
	 */
	public function wordpress_contact_updated( $user_id ) {
		
		$this->_debug( array( 
			'function' => 'wordpress_contact_updated',
			'this->direction' => isset( $this->direction ) ? $this->direction : 'direction not set',
		));
		
		// check flag
		if ( isset( $this->direction ) AND $this->direction == 'civi-to-wp' ) return;
		
		$this->_debug( array( 
			'user_id' => $user_id,
		));
		
		// set flag
		$this->direction = 'wp-to-civi';
		
		// okay, get user
		$user = get_userdata( $user_id );
		
		// did we get one?
		if ( $user ) {
			
			// init CiviCRM
			if ( !civi_wp()->initialize() ) return;
		
			// get user matching file
			require_once 'CRM/Core/BAO/UFMatch.php';

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
		
		}
		
	}
	
	
	
	/**
	 * @description: update a WP user when a CiviCRM contact is updated
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
	 * @return nothing
	 */
	public function civi_contact_updated( $op, $objectName, $objectId, $objectRef ) {
		
		// target our operation
		if ( $op != 'edit' ) return;
		
		// target our object type
		if ( $objectName != 'Individual' ) return;
		
		$this->_debug( array( 
			'function' => 'civi_contact_updated',
			'this->direction' => isset( $this->direction ) ? $this->direction : 'direction not set',
		));
		
		// check flag
		if ( isset( $this->direction ) AND $this->direction == 'wp-to-civi' ) return;
		
		$this->_debug( array( 
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));
		
		// check if we have a contact
		if ( isset( $objectRef->email[0]->email ) ) {
		
			// set flag
			$this->direction = 'civi-to-wp';
			
			// get WP user by email
			$user = get_user_by( 'email', $objectRef->email[0]->email );
		
			// update first name
			update_user_meta( $user->ID, 'first_name', $objectRef->first_name );
			
			// update last name
			update_user_meta( $user->ID, 'last_name', $objectRef->last_name );
			
			// compatibility with BP XProfile WordPress User Sync plugin
			if ( defined( 'BP_XPROFILE_WP_USER_SYNC_VERSION' ) ) {
				
				// access object
				global $bp_xprofile_wordpress_user_sync;
				
				// call the relevant sync method
				$bp_xprofile_wordpress_user_sync->intercept_wp_user_update( $user->ID );
				
			}
			
			$this->_debug( array( 
				'user' => $user,
				'first_name' => $user->first_name,
				'last_name' => $user->last_name
			));
			
		}
		
	}
	
	
	
	/**
	 * @description: update a WP user when a CiviCRM contact is updated
	 * @param string $op the type of database operation
	 * @param string $objectName the type of object
	 * @param integer $objectId the ID of the object
	 * @param object $objectRef the object
	 * @return nothing
	 */
	public function civi_primary_email_will_be_updated( $op, $objectName, $objectId, $objectRef ) {
		
		// target our operation
		if ( $op != 'edit' ) return;
		
		// target our object type
		if ( $objectName != 'Email' ) return;
		
		$this->_debug( array( 
			'function' => 'civi_primary_email_will_be_updated',
			'this->direction' => isset( $this->direction ) ? $this->direction : 'direction not set',
		));
		
		// check flag
		if ( isset( $this->direction ) AND $this->direction == 'wp-to-civi' ) return;
		
		$this->_debug( array( 
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));
		
		// check if we have an email
		if ( isset( $objectRef['email'] ) ) {
			
			// set flag
			$this->direction = 'civi-to-wp';
			
		}
	
	}
	
	
	
	//##########################################################################
	
	
	
	/**
	 * @description: update a Civi contact's first name and last name
	 * @param object $user the WP user object
	 * @param object $civi_contact the Civi Contact object
	 * @return nothing
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
	 * @description: update a Civi contact's primary email address
	 * @param object $user the WP user object
	 * @param object $civi_contact the Civi Contact object
	 * @return nothing
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
	 * @description: update a Civi contact's website address
	 * @param object $user the WP user object
	 * @param object $civi_contact the Civi Contact object
	 * @return nothing
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
	 * @description: debugging
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
 * @description: initialise our plugin after CiviCRM initialises
 */
function civicrm_wp_profile_sync_init() {

	// declare as global
	global $civicrm_wp_profile_sync;
	
	// init plugin
	$civicrm_wp_profile_sync = new CiviCRM_WP_Profile_Sync;
	
}

// add action for plugin init
add_action( 'civicrm_instance_loaded', 'civicrm_wp_profile_sync_init' );





