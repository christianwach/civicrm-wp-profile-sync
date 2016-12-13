<?php /*
--------------------------------------------------------------------------------
Plugin Name: CiviCRM WordPress Profile Sync
Plugin URI: https://github.com/christianwach/civicrm-wp-profile-sync
Description: Keeps a WordPress User profile in sync with CiviCRM Contact info
Author: Christian Wach
Version: 0.2.6
Author URI: http://haystack.co.uk
Text Domain: civicrm-wp-profile-sync
Domain Path: /languages
Depends: CiviCRM
--------------------------------------------------------------------------------
*/



// set our debug flag here
define( 'CIVICRM_WP_PROFILE_SYNC_DEBUG', true );

// set our bulk operations flag here
define( 'CIVICRM_WP_PROFILE_SYNC_BULK', false );

// set our version here
define( 'CIVICRM_WP_PROFILE_SYNC_VERSION', '0.2.6' );

// store reference to this file
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_FILE' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_FILE', __FILE__ );
}

// store URL to this plugin's directory
if ( ! defined( 'CIVICRM_WP_PROFILE_SYNC_URL' ) ) {
	define( 'CIVICRM_WP_PROFILE_SYNC_URL', plugin_dir_url( CIVICRM_WP_PROFILE_SYNC_FILE ) );
}

// store PATH to this plugin's directory
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

  //newadded flag for woocommerce
  private $_is_woocommerce_running;

  //newadded current buffered wp user id
  private $_wp_user_id = null;

  //newadded current buffered civicrm contact id
  private $_civi_contact_id = null;

  //newadded current buffered civicrm contact primary address id and type
  private $_civi_primary_address_info = array();

  //newadded current buffered civicrm contact billing address id and type
  private $_civi_billing_address_info = array();

  //newadded current buffered civicrm contact primary email id
  private $_civi_primary_email_id = null;

  //newadded current buffered civicrm contact primary phone id
  private $_civi_primary_phone_id = null;


  //newadded field names mapping using civicrm api
  private static $_address_api_mapping_wc_to_civi = array(
    'country'=>'country_id',
    'address_1'=>'street_address',
    'address_2'=> 'supplemental_address_1',
    'city' => 'city',
    'state' => 'state_province_id',
    'postcode' => 'postal_code',
  );




	/**
	 * Initialises this object.
	 *
	 * @since 0.1
	 */
	public function __construct() {

    //newadded if woocommerce is active
    include_once ABSPATH . 'wp-admin/includes/plugin.php';
    $this->_is_woocommerce_running = (is_plugin_active( 'woocommerce/woocommerce.php' ) ) ? true : false;





    // // TODO to be removed, just for debugging.
    // if(is_plugin_active( 'kint-debugger/plugin.php') ){
    //   require_once CIVICRM_WP_PROFILE_SYNC_PATH.'../kint-debugger/vendor/kint/Kint.class.php';
    // }

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

		/**
		 * Broadcast that this plugin is active.
		 *
		 * @since 0.2.4
		 */
		 do_action( 'civicrm_wp_profile_sync_init' );

	}



	//##########################################################################



	/**
	 * Load translation if present.
	 *
	 * @since 0.1
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
	 */
	public function buddypress_contact_updated( $user_id = 0, $posted_field_ids, $errors ) {

		$this->_log_exception( array(
			'method' => __METHOD__,
		));

		// get BP instance
		$bp = buddypress();

		// bail if BuddyPress is not set to sync to WordPress
		if ( ! empty( $bp->site_options['bp-disable-profile-sync'] ) && (int) $bp->site_options['bp-disable-profile-sync'] ) {
			return true;
		}

		// fetch logged-in user if none set
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// bail if no user ID
		if ( empty( $user_id ) ) return false;

		// pass to our sync method
		$this->wordpress_contact_updated( $user_id );

	}



	/**
	 * Updates a CiviCRM Contact when a WordPress user is updated.
	 *
	 * @since 0.1
	 *
	 * @param integer $user_id The numeric ID of the WordPress user
	 */
	public function wordpress_contact_updated( $user_id ) {

    // TODO to be uncommented
		// $this->_log_exception( array(
		// 	'method' => __METHOD__,
		// 	'user_id' => $user_id,
		// ));


		// okay, get user object
		$user = get_userdata( $user_id );

		// did we get one?
		if ( $user ) {

			// init CiviCRM
			if ( ! civi_wp()->initialize() ) return;

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

			/**
			 * Allow plugins to hook into the sync process.
			 *
			 * @since 0.2.4
			 *
			 * @param WP_User $user The WordPress user object
			 * @param array $civi_contact The array of CiviCRM contact data
			 */
			 do_action( 'civicrm_wp_profile_sync_wp_user_sync', $user, $civi_contact );

			// add CiviCRM and BuddyPress callbacks once more
			$this->_add_hooks_bp();
			$this->_add_hooks_civi();

			/**
			 * Broadcast that a WordPress user has been synced.
			 *
			 * @since 0.2.4
			 *
			 * @param WP_User $user The WordPress user object
			 * @param array $civi_contact The array of CiviCRM contact data
			 */
			 do_action( 'civicrm_wp_profile_sync_wp_user_synced', $user, $civi_contact );

		}

	}



	//##########################################################################



  /** NOTE newadded
  *
  *
  *
  * @param int    $meta_id    ID of updated metadata entry.
  * @param int    $object_id  Object ID.
  * @param string $meta_key   Meta key.
  * @param mixed  $meta_value Meta value.
  */
  public function update_civi_address_fields_woocommerce( $meta_id, $object_id, $meta_key, $_meta_value){

    // //for debug
    // $debug_array = array('$meta_id'=>$meta_id, '$object_id'=>$object_id, '$meta_key'=>$meta_key, '$_meta_value'=> $_meta_value);
    // $this->_debug($debug_array);



    $_lower_case_meta_key = strtolower($meta_key);

    if($_lower_case_meta_key == 'last_update' || (strpos($_lower_case_meta_key, 'billing_') === false && strpos($_lower_case_meta_key, 'shipping_') === false) ) {
      return;
    }

    $_get_new_ids = (isset($this->_wp_user_id) && $this->_wp_user_id == $object_id ) ? false : true;

    if($_get_new_ids){

      // okay, get user object
      $user = get_userdata( $object_id );

      // did we get one?
      if ( $user ) {

        // init CiviCRM
        if ( ! civi_wp()->initialize() ) return;

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

      $this->_wp_user_id = $user->ID;
      $this->_civi_contact_id = $civi_contact->contact_id;

      //get primary address id and type and store them with the object.
      $result = civicrm_api3('Address', 'get', array(
        'sequential' => 1,
        'return' => array("id", "location_type_id"),
        'contact_id' => $this->_civi_contact_id,
        'is_primary' => 1,
      ));

      if( empty($result['values']) ){
        $this->_civi_primary_address_info = array();
      }else {
        $this->_civi_primary_address_info = array('id'=>$result['values'][0]['id'],'type'=>$result['values'][0]['location_type_id']);
      }

      //get billing address id and type and store them with the object.
      $result = civicrm_api3('Address', 'get', array(
        'sequential' => 1,
        'return' => array("id", "location_type_id"),
        'contact_id' => $this->_civi_contact_id,
        'is_billing' => 1,
      ));

      if( empty($result['values']) ){
        $this->_civi_billing_address_info = array();
      }else {
        $this->_civi_billing_address_info = array('id'=>$result['values'][0]['id'],'type'=>$result['values'][0]['location_type_id']);
      }

      // get primary email id and type and store them with the object.
      $result = civicrm_api3('Email', 'get', array(
        'sequential' => 1,
        'return' => array("id"),
        'contact_id' => $this->_civi_contact_id,
        'is_primary' => 1,
      ));

      if( empty($result['values']) ){
        $this->_civi_primary_email_id = null;
      }else {
        $this->_civi_primary_email_id = $result['values'][0]['id'];
      }

      // get first primary phone id, location type and phone tyepe and store them within the object.
      $result = civicrm_api3('Phone', 'get', array(
        'sequential' => 1,
        'return' => array("id"),
        'contact_id' => $this->_civi_contact_id,
        'is_primary' => 1,
      ));

      if( empty($result['values']) ){
        $this->_civi_primary_phone_id = null;
      }else {
        $this->_civi_primary_phone_id = $result['values'][0]['id'];
      }


      // //debugging
      // d(array('_civi_primary_address_info'=>$this->_civi_primary_address_info,'_civi_billing_address_info'=>$this->_civi_billing_address_info));
      // $this->_debug(array('_civi_primary_address_info'=>$this->_civi_primary_address_info,'_civi_billing_address_info'=>$this->_civi_billing_address_info));
    }
  }

  $this->_sync_to_civicrm_email_n_phone($meta_key, $_meta_value);

  $this->_sync_to_civicrm_addresses($meta_key, $_meta_value);
}


/** NOTE newadded
*
*
* @param string $_meta_key   Meta key.
* @param mixed  $_meta_value Meta value.
*/
private function _sync_to_civicrm_email_n_phone($_meta_key, $_meta_value){

  if($_meta_key != 'billing_email' && $_meta_key != 'billing_phone'){
    return;
  }

  $_need_to_update_email_or_phone_id = false;

  $_query_array = array(
    'sequential'=>1,
    'contact_id'=>$this->_civi_contact_id,
  );

  if($_meta_key == 'billing_email'){

    $_entity_name='Email';
    $_query_array['email'] = $_meta_value;

    if(isset($this->_civi_primary_email_id)){
      $_query_array['id'] = $this->_civi_primary_email_id;
    }

  }else{
    $_entity_name='Phone';
    $_query_array['phone'] = $_meta_value;

    if(isset($this->_civi_primary_phone_id)){
      $_query_array['id'] = $this->_civi_primary_phone_id;
    }else {
      $_query_array['phone_type_id'] = 'Phone';
    }
  }

  if(!isset($_query_array['id'])){
    $_query_array['location_type_id'] = 'Billing';

    $_need_to_update_email_or_phone_id = true;
  }



  try {
    $result = civicrm_api3($_entity_name, 'create',$_query_array);
  } catch (Exception $e) {
    $this->_log_exception($e->getMessage());
  }

  if($_need_to_update_email_or_phone_id){
    $result = $result['values'][0];

    if($_entity_name=='Email'){
      $this->_civi_primary_email_id = $result['id'];
    }else {
      $this->_civi_primary_phone_id = $result['id'];
    }

  }


}




/** NOTE newadded
*
*
* @param string $_meta_key   Meta key.
* @param mixed  $_meta_value Meta value.
*/
private function _sync_to_civicrm_addresses($_meta_key, $_meta_value){
  // //for debug
  // $debug_array = array('$_meta_key'=>$_meta_key, '$_meta_value'=> $_meta_value);
  // $this->_debug($debug_array);

  $tmp = explode('_',$_meta_key);

  $_address_type = strtolower(array_shift($tmp)) ;
  $_processed_meta_key = implode('_',$tmp);

  if(array_key_exists($_processed_meta_key,self::$_address_api_mapping_wc_to_civi)){

    /*
    the country field is working fine, as civicrm api can recongise short names of countries
    but WC is letting users to enter the state/province names by themselves AT WP PROFILE PAGE (on the 'my account' page)
    So it is likely that the state field could not be correctly updated.
    */
    //we need to store the states full name mapping for corresponding country in the object, otherwise the civicrm api can not
    //recongise abbrivation of state names provided by woocommerce.
    if(strpos(strtolower($_processed_meta_key),'state') !== false){

      $_country_value = get_user_meta( $this->_wp_user_id, $_address_type.'_country',true);

      $_states_list = WC()->countries->get_states($_country_value);

      $_meta_value = $_states_list[$_meta_value];


    }


    $_query_array = array(
      'sequential' => 1,
      'contact_id' => $this->_civi_contact_id,
      self::$_address_api_mapping_wc_to_civi[$_processed_meta_key] => $_meta_value,
    );



  }else{
    //TODO throw ErrorException
    return;
  }

  // NOTE if the the address info is empty, we need to update the attribute after creating a new address in civicrm.
  // otherwise each call will create a new address.
  $_need_to_update_object_address_info = false;


  if($_address_type == 'billing'){

    $_query_array['is_billing'] = 1;

    if( isset($this->_civi_billing_address_info['id']) ){
      $_need_to_update_object_address_info = false;
      $_query_array['id']= $this->_civi_billing_address_info['id'];
      $_query_array['location_type_id'] = (isset($this->_civi_billing_address_info['type'])) ? $this->_civi_billing_address_info['type']:'Billing';
    }else {
      $_need_to_update_object_address_info = true;
      $_query_array['location_type_id'] = 'Billing';
    }



  }elseif ($_address_type == 'shipping') {

    $_query_array['is_primary'] = 1;

    if( isset($this->_civi_primary_address_info['id']) ){
      $_need_to_update_object_address_info = false;
      $_query_array['id']= $this->_civi_primary_address_info['id'];
      $_query_array['location_type_id'] = (isset($this->_civi_primary_address_info['type'])) ? $this->_civi_primary_address_info['type']:'Home';
    }else {
      $_need_to_update_object_address_info = true;
      $_query_array['location_type_id'] = 'Home';
    }

  }else {
    //TODO throw ErrorException
    return;
  }


  try {
    $result = civicrm_api3('Address', 'create', $_query_array);
  } catch (Exception $e) {
    $this->_log_exception($e->getMessage());
  }


  // update the buffered address info if needed.
  if($_need_to_update_object_address_info){
    $result = $result['values'][0];

    if($result['is_primary'] == 1){
      $this->_civi_primary_address_info = array('id'=>$result['id'],'type'=>$result['location_type_id']);
    }elseif ($result['is_billing'] == 1) {
      $this->_civi_billing_address_info = array('id'=>$result['id'],'type'=>$result['location_type_id']);
    }else {
      //TODO throw ErrorException
      return;
    }

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
	 */
	public function civi_contact_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// target our operation
		if ( $op != 'edit' ) return;

		// target our object type
		if ( $objectName != 'Individual' ) return;

		$this->_log_exception( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// remove WordPress and BuddyPress callbacks to prevent recursion
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();

	}




  public function civi_primary_n_billing_addresses_update( $op, $objectName, $objectId, $objectRef ) {

    // target our operation
    if ( $op != 'edit' ) return;

    // target our object type
    if ( $objectName != 'Address' ) return;

    // bail if we have no contact ID
    if ( ! isset( $objectRef->contact_id ) ) return;

    // we only care about primary address and billing address
    if ( $objectRef->is_primary == '0' && $objectRef->is_billing == '0' ) return;

    // //for debug
    // $_debug_array = array(
    //   'op' => $op,
    //   'objectName' => $objectName,
    //   'objectId' => $objectId,
    //   'objectRef' => $objectRef,
    // );
    // CRM_Core_Session::setStatus(print_r($_debug_array,true),'','error');

    // init CiviCRM to get WP user ID
    if ( ! civi_wp()->initialize() ) return;

    // make sure Civi file is included
    require_once 'CRM/Core/BAO/UFMatch.php';

    // search using Civi's logic
    $user_id = CRM_Core_BAO_UFMatch::getUFId( $objectRef->contact_id );

    // kick out if we didn't get one
    if ( empty( $user_id ) ) return;

    // remove WordPress and BuddyPress callbacks to prevent recursion
    $this->_remove_hooks_wp();
    $this->_remove_hooks_bp();

    $_address_type = ($objectRef->is_primary != '0' ) ? 'shipping' : 'billing';


		//look up all fields that we care about in civicrm object.
    foreach (self::$_address_api_mapping_wc_to_civi as $key => $value) {

      if($key == 'state' && isset($objectRef->{$value})){

				//civicrm and WC both use standard state and country abbrivations.
				//But WC store and grab abbrivation of country and state in user mata data.
				//While CiviCRM API accept full name and id of different states and countries.

				//workingon

				$_civi_state_id = $objectRef->{$value};

				//get the country id.
				if (isset($objectRef->country_id)){
					$_civi_country_id = $objectRef->country_id;
				}else {
					//if no country id is provided, the state can not be set.
					continue;
				}

				// civicrm api 3 is not supporting state province now.
				$query = "SELECT abbreviation FROM civicrm_state_province WHERE country_id = %1 AND id = %2";
				$params = array(
					1 => array($_civi_country_id, 'Integer'),
					2 => array($_civi_state_id, 'Integer')
				);

					$_state_abbreviation = CRM_Core_DAO::singleValueQuery($query, $params);

					// //debug
					//CRM_Core_Session::setStatus(print_r(array('abb' => $_state_abbreviation),true),'','error');

					update_user_meta( $user_id,$_address_type.'_'.$key , $_state_abbreviation );

					continue;

      }elseif ($key == 'country' && isset($objectRef->{$value}) ) {

					$_civi_country_id = $objectRef->{$value};

					//get the country abbrivation
					$result = civicrm_api3('Country', 'get', array(
						'sequential' => 1,
						'return' => array("iso_code"),
						'id' => $_civi_country_id,
					));

					update_user_meta( $user_id,$_address_type.'_'.$key , $result['values'][0]['iso_code']);

					continue;

      }

      update_user_meta( $user_id,$_address_type.'_'.$key , $objectRef->{$value} );

    }

    // re-add WordPress and BuddyPress callbacks
    $this->_add_hooks_wp();
    $this->_add_hooks_bp();


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
	 */
	public function civi_primary_email_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// target our operation
		if ( $op != 'edit' ) return;

		// target our object type
		if ( $objectName != 'Email' ) return;

		// bail if we have no email
		if ( ! isset( $objectRef['email'] ) ) return;

		$this->_log_exception( array(
			'method' => __METHOD__,
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		));

		// remove WordPress and BuddyPress callbacks to prevent recursion
		$this->_remove_hooks_wp();
		$this->_remove_hooks_bp();



    // re-add WordPress and BuddyPress callbacks
    $this->_add_hooks_wp();
    $this->_add_hooks_bp();

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
	 */
	public function civi_website_pre_update( $op, $objectName, $objectId, $objectRef ) {

		// target our object type
		if ( $objectName != 'Website' ) return;

		// bail if we have no website
		if ( ! isset( $objectRef['url'] ) ) return;

		// bail if we have no contact ID
		if ( ! isset( $objectRef['contact_id'] ) ) return;

		$this->_log_exception( array(
			'method' => __METHOD__,
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

		$this->_log_exception( array(
			'method' => __METHOD__,
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

		/**
		 * Broadcast that a CiviCRM contact's website has been synced.
		 *
		 * @since 0.2.4
		 *
		 * @param integer $user_id The ID of the WordPress user
		 * @param integer $objectId The ID of the CiviCRM contact
		 * @param object $objectRef The CiviCRM contact object
		 */
		 do_action( 'civicrm_wp_profile_sync_website_synced', $user_id, $objectId, $objectRef );

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
	 */
	public function civi_contact_updated( $op, $objectName, $objectId, $objectRef ) {

		// target our operation
		if ( $op != 'edit' ) return;

		// target our object type
		if ( $objectName != 'Individual' ) return;

		$this->_log_exception( array(
			'method' => __METHOD__,
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

    //TODO this bit is not a good practice and need to be changed.
		// update display name
    // wp_update_user( array( 'ID' => $user_id, 'display_name' => ( $objectRef->first_name . " " . $objectRef->last_name ) ) );

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

			$this->_log_exception( array(
				'user' => $user,
				'first_name' => $user->first_name,
				'last_name' => $user->last_name
			));

		}

		/**
		 * Broadcast that a CiviCRM contact has been synced.
		 *
		 * @since 0.2.4
		 *
		 * @param integer $objectId The ID of the CiviCRM contact
		 * @param object $objectRef The CiviCRM contact object
		 * @param integer $user_id The ID of the WordPress user
		 */
		 do_action( 'civicrm_wp_profile_sync_civi_contact_synced', $objectId, $objectRef, $user_id );

	}



	//##########################################################################



	/**
	 * Prevent recursion when a WordPress user is about to be bulk added.
	 *
	 * @since 0.1
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
	 */
	private function _add_hooks_wp() {

		// callbacks for new and updated WordPress user actions
		add_action( 'user_register', array( $this, 'wordpress_contact_updated' ), 100, 1 );
		add_action( 'profile_update', array( $this, 'wordpress_contact_updated' ), 100, 1 );


    // newadded hook into meta data update process
    if($this->_is_woocommerce_running){
      add_action( 'updated_user_meta', array( $this, 'update_civi_address_fields_woocommerce' ), 100, 4 );
    }

	}



	/**
	 * Remove WordPress sync hooks.
	 *
	 * @since 0.1
	 */
	private function _remove_hooks_wp() {

		// remove callbacks for new and updated WordPress user actions
		remove_action( 'user_register', array( $this, 'wordpress_contact_updated' ), 100 );
		remove_action( 'profile_update', array( $this, 'wordpress_contact_updated' ), 100 );

    // newadded remove hooked function
    if($this->_is_woocommerce_running){
    remove_action( 'updated_user_meta', array( $this, 'update_civi_address_fields_woocommerce' ), 100);
    }

	}



	/**
	 * Add CiviCRM sync hooks.
	 *
	 * @since 0.1
	 */
	private function _add_hooks_civi() {

		// intercept contact update in CiviCRM
		add_action( 'civicrm_pre', array( $this, 'civi_contact_pre_update' ), 10, 4 );
		add_action( 'civicrm_post', array( $this, 'civi_contact_updated' ), 10, 4 );

		// intercept email update in CiviCRM
		add_action( 'civicrm_pre', array( $this, 'civi_primary_email_pre_update' ), 10, 4 );

		// intercept website update in CiviCRM
		add_action( 'civicrm_pre', array( $this, 'civi_website_pre_update' ), 10, 4 );



    if($this->_is_woocommerce_running){
      //newadded hook into post process of address update in civicrm for synchronisation to WP/WC.
      add_action( 'civicrm_post', array( $this, 'civi_primary_n_billing_addresses_update' ), 10, 4 );

    }


	}



	/**
	 * Remove CiviCRM sync hooks.
	 *
	 * @since 0.1
	 */
	private function _remove_hooks_civi() {

		// remove all CiviCRM callbacks
		remove_action( 'civicrm_pre', array( $this, 'civi_contact_pre_update' ), 10 );
		remove_action( 'civicrm_post', array( $this, 'civi_contact_updated' ), 10 );
		remove_action( 'civicrm_pre', array( $this, 'civi_primary_email_pre_update' ), 10 );
		remove_action( 'civicrm_pre', array( $this, 'civi_website_pre_update' ), 10 );

    if($this->_is_woocommerce_running){
    //newadded hook into post process of address update in civicrm for synchronisation to WP/WC.
    remove_action( 'civicrm_post', array( $this, 'civi_primary_n_billing_addresses_update' ), 10 );
    }
	}



	/**
	 * Update a Civi contact's first name and last name.
	 *
	 * @since 0.1
	 *
	 * @param object $user The WP user object
	 * @param object $civi_contact The Civi Contact object
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
			isset( $primary_email['values'] ) AND
			is_array( $primary_email['values'] ) AND
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
			isset( $existing_website['values'] ) AND
			is_array( $existing_website['values'] ) AND
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

		// do we want output?
		if ( CIVICRM_WP_PROFILE_SYNC_DEBUG ) {
			d($msg);
		}

	}

  /**
	 * Logging exceptions and errors.
	 *
	 * @param array $msg
	 * @return string
	 */
  private function _log_exception($msg){
    if ( false && CIVICRM_WP_PROFILE_SYNC_DEBUG ) {
    // uncomment this to add a backtrace
    //$msg['backtrace'] = wp_debug_backtrace_summary();

    if(!is_array($msg)){
      $msg = array('exception mesage'=>$msg);
    }

    $msg['method'] = __METHOD__;

    // log the message
    error_log( print_r( $msg, true ) );

    }
  }



} // class ends



/**
 * Initialise our plugin after CiviCRM initialises.
 *
 * @since 0.1
 */
function civicrm_wp_profile_sync_init() {

	// declare as global
	global $civicrm_wp_profile_sync;

	// init plugin
	$civicrm_wp_profile_sync = new CiviCRM_WP_Profile_Sync;

}

// add action for plugin init
add_action( 'civicrm_instance_loaded', 'civicrm_wp_profile_sync_init' );
