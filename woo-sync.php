<?php


// set our debug flag here
define('CIVICRM_WP_PROFILE_SYNC_WC_SYNC_DEBUG', false);


/**
 * Seperate CiviCRM WC sync class and its initialization. It requires the code in civicrm-wp-profile.php that have been executed first.
 *
 */
class CiviCRM_WP_Profile_WC_Sync {

    // flag for woocommerce
    protected $_is_woocommerce_running;

    // current buffered wp user id
    protected $_wp_user_id = null;

    // current buffered civicrm contact id
    protected $_civi_contact_id = null;

    // current buffered civicrm contact primary address id and type
    protected $_civi_primary_address_info = array();

    // current buffered civicrm contact billing address id and type
    protected $_civi_billing_address_info = array();

    // current buffered civicrm contact primary phone id
    protected $_civi_primary_phone_id = null;

    // field names mapping using civicrm api
    protected static $_address_api_mapping_wc_to_civi = array(
        'country' => 'country_id',
        'address_1' => 'street_address',
        'address_2' => 'supplemental_address_1',
        'city' => 'city',
        'state' => 'state_province_id',
        'postcode' => 'postal_code',
    );


    /**
     * construction function
     *
     */
    public function __construct() {

        // if woocommerce is active
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        $this->_is_woocommerce_running = (is_plugin_active('woocommerce/woocommerce.php')) ? true : false;


        // post process CiviCRM contact when WP user is updated, done late to let other plugins go first
        $this->_add_hooks_wp_wc();


        // sync a WP user when a CiviCRM contact is updated
        $this->_add_hooks_civi_wc();


    }


    /**  synchronise changes of address fileds of user metadata in wp&wc to civicrm
     *
     *
     *
     * @param int $meta_id ID of updated metadata entry.
     * @param int $object_id Object ID.
     * @param string $meta_key Meta key.
     * @param mixed $meta_value Meta value.
     */
    public function update_civi_address_fields_woocommerce($meta_id, $object_id, $meta_key, $_meta_value) {

        // //for debug
        // $debug_array = array('$meta_id'=>$meta_id, '$object_id'=>$object_id, '$meta_key'=>$meta_key, '$_meta_value'=> $_meta_value);
        // $this->_debug($debug_array);

        $_lower_case_meta_key = strtolower($meta_key);

        if ( $_lower_case_meta_key == 'last_update' || (strpos($_lower_case_meta_key, 'billing_') === false && strpos($_lower_case_meta_key, 'shipping_') === false) ) {
            return;
        }

        $_get_new_ids = (isset($this->_wp_user_id) && $this->_wp_user_id == $object_id) ? false : true;

        if ( $_get_new_ids ) {

            // okay, get user object
            $user = get_userdata($object_id);

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

                $this->_wp_user_id = $user->ID;
                $this->_civi_contact_id = $civi_contact->contact_id;

                //get primary address id and type and store them within the object.
                $result = civicrm_api3('Address', 'get', array(
                    'sequential' => 1,
                    'return' => array("id", "location_type_id"),
                    'contact_id' => $this->_civi_contact_id,
                    'is_primary' => 1,
                ));

                if ( empty($result['values']) ) {
                    $this->_civi_primary_address_info = array();
                } else {
                    $this->_civi_primary_address_info = array('id' => $result['values'][0]['id'], 'type' => $result['values'][0]['location_type_id']);
                }

                //get billing address id and type and store them within the object.
                $result = civicrm_api3('Address', 'get', array(
                    'sequential' => 1,
                    'return' => array("id", "location_type_id"),
                    'contact_id' => $this->_civi_contact_id,
                    'is_billing' => 1,
                ));

                if ( empty($result['values']) ) {
                    $this->_civi_billing_address_info = array();
                } else {
                    $this->_civi_billing_address_info = array('id' => $result['values'][0]['id'], 'type' => $result['values'][0]['location_type_id']);
                }

                // get primary phone id and store it within the object.
                $result = civicrm_api3('Phone', 'get', array(
                    'sequential' => 1,
                    'return' => array("id"),
                    'contact_id' => $this->_civi_contact_id,
                    'is_primary' => 1,
                ));

                if ( empty($result['values']) ) {
                    $this->_civi_primary_phone_id = null;
                } else {
                    $this->_civi_primary_phone_id = $result['values'][0]['id'];
                }

                // //debugging
                // d(array('_civi_primary_address_info'=>$this->_civi_primary_address_info,'_civi_billing_address_info'=>$this->_civi_billing_address_info));
                // $this->_debug(array('_civi_primary_address_info'=>$this->_civi_primary_address_info,'_civi_billing_address_info'=>$this->_civi_billing_address_info));
            }
        }

        $this->_remove_hooks_civi_wc();

        $this->_sync_to_civicrm_phone($meta_key, $_meta_value);

        $this->_sync_to_civicrm_addresses($meta_key, $_meta_value);

        $this->_add_hooks_civi_wc();
    }


    /** synchronise changes of billing phone of woo user metadata in wp&wc to civicrm
     *
     *
     * @param string $_meta_key Meta key.
     * @param mixed $_meta_value Meta value.
     */
    private function _sync_to_civicrm_phone($_meta_key, $_meta_value) {

        if ( $_meta_key != 'billing_phone' ) {
            return;
        }

        $_need_to_update_phone_id = false;

        $_query_array = array(
            'sequential' => 1,
            'contact_id' => $this->_civi_contact_id,
        );

        $_query_array['phone'] = $_meta_value;

        if ( isset($this->_civi_primary_phone_id) ) {
            $_query_array['id'] = $this->_civi_primary_phone_id;
        } else {
            $_query_array['phone_type_id'] = 'Phone';
        }

        if ( !isset($_query_array['id']) ) {
            $_query_array['location_type_id'] = 'Billing';

            $_need_to_update_phone_id = true;
        }

        try {
            $result = civicrm_api3('Phone', 'create', $_query_array);
        } catch (Exception $e) {
            $this->_log_exception($e->getMessage());
        }

        if ( $_need_to_update_phone_id ) {
            $result = $result['values'][0];

            $this->_civi_primary_phone_id = $result['id'];
        }

    }


    /** synchronise changes of billing and shipping address of woo user metadata in wp&wc to civicrm
     *
     *
     * @param string $_meta_key Meta key.
     * @param mixed $_meta_value Meta value.
     */
    private function _sync_to_civicrm_addresses($_meta_key, $_meta_value) {

        ////for debug
        //$debug_array = array('$_meta_key'=>$_meta_key, '$_meta_value'=> $_meta_value);
        //$this->_debug($debug_array);

        $tmp = explode('_', $_meta_key);

        $_address_type = strtolower(array_shift($tmp));
        $_processed_meta_key = implode('_', $tmp);

        if ( array_key_exists($_processed_meta_key, self::$_address_api_mapping_wc_to_civi) ) {

            /*
            the country field is working fine, as civicrm api can recongise short names of countries
            but WC is letting users to enter the state/province names by themselves AT WP PROFILE PAGE (on the 'my account' page)
            So it is likely that the state field could not be correctly updated.
            */

            //we need to store the states full name mapping for corresponding country in the object, otherwise the civicrm api can not
            //recongise abbrivation of state names provided by woocommerce.

            if ( strpos(strtolower($_processed_meta_key), 'state') !== false ) {

                $_country_value = get_user_meta($this->_wp_user_id, $_address_type . '_country', true);

                $_states_list = WC()->countries->get_states($_country_value);

                $_meta_value = $_states_list[$_meta_value];

            }

            $_query_array = array(
                'sequential' => 1,
                'contact_id' => $this->_civi_contact_id,
                self::$_address_api_mapping_wc_to_civi[$_processed_meta_key] => $_meta_value,
            );

        } else {
            return;
        }

        // NOTE if the the address info is empty, we need to update the attribute after creating a new address in civicrm.
        // otherwise each call will create a new address.

        $_need_to_update_object_address_info = false;

        if ( $_address_type == 'billing' ) {

            $_query_array['is_billing'] = 1;

            if ( isset($this->_civi_billing_address_info['id']) ) {
                $_need_to_update_object_address_info = false;
                $_query_array['id'] = $this->_civi_billing_address_info['id'];
                $_query_array['location_type_id'] = (isset($this->_civi_billing_address_info['type'])) ? $this->_civi_billing_address_info['type'] : 'Billing';
            } else {
                $_need_to_update_object_address_info = true;
                $_query_array['location_type_id'] = 'Billing';
            }

        } elseif ( $_address_type == 'shipping' ) {

            $_query_array['is_primary'] = 1;

            if ( isset($this->_civi_primary_address_info['id']) ) {
                $_need_to_update_object_address_info = false;
                $_query_array['id'] = $this->_civi_primary_address_info['id'];
                $_query_array['location_type_id'] = (isset($this->_civi_primary_address_info['type'])) ? $this->_civi_primary_address_info['type'] : 'Home';
            } else {
                $_need_to_update_object_address_info = true;
                $_query_array['location_type_id'] = 'Home';
            }

        } else {
            return;
        }

        try {
            $result = civicrm_api3('Address', 'create', $_query_array);
        } catch (Exception $e) {
            $this->_log_exception($e->getMessage());
        }

        // update the buffered address info if needed.
        if ( $_need_to_update_object_address_info ) {
            $result = $result['values'][0];

            /* NOTE we need to check `is_billing` first, as first it follows the order of woo's user metadata.
             * Secondly if only billing address information is filled out and the user doesn't have any address in CiviCRM,
             * CiviCRM will mark the first address created as `primary` too. This will cause incorrect data input of the fisrt
             * address field, if we check `is_primary` first.
             */

            if ( $result['is_billing'] == 1 ) {
                $this->_civi_billing_address_info = array('id' => $result['id'], 'type' => $result['location_type_id']);
            } elseif ( $result['is_primary'] == 1 ) {
                $this->_civi_primary_address_info = array('id' => $result['id'], 'type' => $result['location_type_id']);
            } else {
                return;
            }

        }

    }


    /** Synchronise primary phone changes (edite, create, delete) in civicrm to wp&wc, hooked into pre process
     *
     * @param string $_meta_key Meta key.
     * @param mixed $_meta_value Meta value.
     */
    public function civi_primary_phone_update($op, $objectName, $objectId, $objectRef) {

        // target our object type
        if ( $objectName != 'Phone' ) return;

        $_is_deletion = false;

        if ( $op == 'delete' ) {

            $result = civicrm_api3($objectName, 'get', array(
                'sequential' => 1,
                'id' => $objectId,
            ));

            $result = $result['values'][0];

            if ( $result['is_primary'] == 1 ) {
                $objectRef['contact_id'] = $result['contact_id'];
                $_is_deletion = true;
            } else {
                return;
            }

        } elseif ( $op == 'edit' || $op == 'create' ) {

            // bail if we have no contact ID
            if ( !isset($objectRef['contact_id']) ) return;

            // we only care about primary phone
            if ( !isset($objectRef['is_primary']) ) {
                return;
            } elseif ( $objectRef['is_primary'] == '0' ) {
                return;
            }

        }

        // //for debug
        // $_debug_array = array(
        // 	'op' => $op,
        // 	'objectName' => $objectName,
        // 	'objectId' => $objectId,
        // 	'objectRef' => $objectRef,
        // );
        // CRM_Core_Session::setStatus(print_r($_debug_array,true),'','error');

        // init CiviCRM to get WP user ID
        if ( !civi_wp()->initialize() ) return;

        // make sure Civi file is included
        require_once 'CRM/Core/BAO/UFMatch.php';

        // search using Civi's logic
        $user_id = CRM_Core_BAO_UFMatch::getUFId($objectRef['contact_id']);

        // kick out if we didn't get one
        if ( empty($user_id) ) return;

        // remove WordPress callbacks to prevent recursion
        $this->_remove_hooks_wp_wc();

        if ( $_is_deletion ) {

            $result = update_user_meta($user_id, 'billing_phone', '');

        } else {
            update_user_meta($user_id, 'billing_phone', $objectRef['phone']);
        }

        // re-add WordPress callbacks
        $this->_add_hooks_wp_wc();

    }


    /**
     * Synchronise primary and billing addresses changes (edite, create, delete) in civicrm to wp&wc, hooked into pre process
     *
     *
     * @param string $op The type of database operation
     * @param string $objectName The type of object
     * @param integer $objectId The ID of the object
     * @param object $objectRef The object
     */
    public function civi_primary_n_billing_addresses_update($op, $objectName, $objectId, $objectRef) {

        // target our object type
        if ( $objectName != 'Address' ) return;

        $_is_deletion = false;

        if ( $op == 'delete' ) {

            $result = civicrm_api3($objectName, 'get', array(
                'sequential' => 1,
                'id' => $objectId,
            ));

            $result = $result['values'][0];

            if ( $result['is_primary'] == '1' ) {
                $objectRef['is_primary'] = '1';
                $objectRef['contact_id'] = $result['contact_id'];
                $_is_deletion = true;
            }

            if ( $result['is_billing'] == '1' ) {
                $objectRef['is_billing'] = '1';
                $objectRef['contact_id'] = $result['contact_id'];
                $_is_deletion = true;
            }

            if ( !$_is_deletion ) {
                return;
            }

        } elseif ( $op == 'edit' || $op == 'create' ) {

            // bail if we have no contact ID
            if ( !isset($objectRef['contact_id']) ) return;

            // we only care about primary address and billing address
            if ( $objectRef->is_primary == '0' && $objectRef->is_billing == '0' ) return;

        }

        // //for debug
        // $_debug_array = array(
        //   'op' => $op,
        //   'objectName' => $objectName,
        //   'objectId' => $objectId,
        //   'objectRef' => $objectRef,
        // );
        // CRM_Core_Session::setStatus(print_r($_debug_array,true),'','error');

        // init CiviCRM to get WP user ID
        if ( !civi_wp()->initialize() ) return;

        // make sure Civi file is included
        require_once 'CRM/Core/BAO/UFMatch.php';

        // search using Civi's logic
        $user_id = CRM_Core_BAO_UFMatch::getUFId($objectRef['contact_id']);

        // kick out if we didn't get one
        if ( empty($user_id) ) return;

        // remove WordPress wc callbacks to prevent recursion
        $this->_remove_hooks_wp_wc();

        if ( $objectRef['is_primary'] == '1' ) {
            $this->_update_address_info_civicrm($user_id, $objectRef, 'shipping', $_is_deletion);
        }

        if ( $objectRef['is_billing'] == '1' ) {
            $this->_update_address_info_civicrm($user_id, $objectRef, 'billing', $_is_deletion);
        }

        // re-add WordPress wc callbacks
        $this->_add_hooks_wp_wc();

    }


    /**
     * private funtion to update correspoding different types of addresses in wp&wc
     *
     *
     * @param integer $user_id The user id in WP
     * @param object $objectRef The object of user in CiviCRM
     * @param string $_address_type The location type of the address
     * @param boolean $_is_deletion Whether this operation is deletion or not.
     */
    private function _update_address_info_civicrm($user_id, $objectRef, $_address_type, $_is_deletion) {

        //look up all fields that we care about in civicrm object.
        foreach (self::$_address_api_mapping_wc_to_civi as $key => $value) {

            if ( $key == 'state' && isset($objectRef[$value]) && $objectRef[$value] != 'null' && !$_is_deletion ) {

                //civicrm and WC both use standard state and country abbrivations.
                //But WC store and grab abbrivation of country and state in user mata data.
                //While CiviCRM API accept full name and id of different states and countries.

                $_civi_state_id = $objectRef[$value];

                //get the country id.
                if ( isset($objectRef['country_id']) && $objectRef['country_id'] != 'null' ) {
                    $_civi_country_id = $objectRef['country_id'];
                } else {
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

                update_user_meta($user_id, $_address_type . '_' . $key, $_state_abbreviation);

                continue;

            } elseif ( $key == 'country' && isset($objectRef[$value]) && $objectRef[$value] != 'null' && !$_is_deletion ) {

                $_civi_country_id = $objectRef[$value];

                //get the country abbrivation
                $result = civicrm_api3('Country', 'get', array(
                    'sequential' => 1,
                    'return' => array("iso_code"),
                    'id' => $_civi_country_id,
                ));

                update_user_meta($user_id, $_address_type . '_' . $key, $result['values'][0]['iso_code']);

                continue;

            }

            $_value_to_write = ($_is_deletion) ? '' : $objectRef[$value];

            update_user_meta($user_id, $_address_type . '_' . $key, $_value_to_write);

        }

    }


    /**
     * Add WordPress wc sync hooks.
     *
     */
    private function _add_hooks_wp_wc() {


        //  hook into meta data update process
        if ( $this->_is_woocommerce_running ) {
            add_action('updated_user_meta', array($this, 'update_civi_address_fields_woocommerce'), 100, 4);
            add_action('added_user_meta', array($this, 'update_civi_address_fields_woocommerce'), 100, 4);
        }

    }

    /**
     * remove WordPress wc sync hooks.
     *
     */
    private function _remove_hooks_wp_wc() {


        //  remove hooked function
        if ( $this->_is_woocommerce_running ) {
            remove_action('updated_user_meta', array($this, 'update_civi_address_fields_woocommerce'), 100);
            remove_action('added_user_meta', array($this, 'update_civi_address_fields_woocommerce'), 100);
        }

    }


    /**
     * Add CiviCRM wc sync hooks.
     *
     */
    private function _add_hooks_civi_wc() {

        if ( $this->_is_woocommerce_running ) {

            // hook into post process of address update in civicrm for synchronisation to WP/WC.
            add_action('civicrm_pre', array($this, 'civi_primary_n_billing_addresses_update'), 10, 4);

            // hook into post process of Phone update in civicrm for synchronisation to WP/WC.
            add_action('civicrm_pre', array($this, 'civi_primary_phone_update'), 10, 4);

        }


    }


    /**
     * Remove CiviCRM wc sync hooks.
     *
     */
    private function _remove_hooks_civi_wc() {

        if ( $this->_is_woocommerce_running ) {

            // hook into post process of address update in civicrm for synchronisation to WP/WC.
            remove_action('civicrm_pre', array($this, 'civi_primary_n_billing_addresses_update'), 10);

            // hook into post process of Phone update in civicrm for synchronisation to WP/WC.
            remove_action('civicrm_pre', array($this, 'civi_primary_phone_update'), 10);

        }
    }


    /**
     * Debugging, using kent d() function.
     *
     * @param array $msg
     *
     */
    private function _debug($msg) {

        // do we want output?
        if ( CIVICRM_WP_PROFILE_SYNC_WC_SYNC_DEBUG ) {
            d($msg);
        }

    }

    /**
     * Logging exceptions and errors.
     *
     * @param array $msg
     *
     */
    private function _log_exception($msg) {
        if ( false && CIVICRM_WP_PROFILE_SYNC_WC_SYNC_DEBUG ) {

            // uncomment this to add a backtrace
            //$msg['backtrace'] = wp_debug_backtrace_summary();

            if ( !is_array($msg) ) {
                $msg = array('exception mesage' => $msg);
            }

            $msg['method'] = __METHOD__;

            // log the message
            error_log(print_r($msg, true));

        }
    }

} // class ends


/**
 * Initialise class CiviCRM_WP_Profile_WC_Sync after CiviCRM initialises.
 *
 */
function civicrm_wc_sync_init() {

    // declare as global
    global $civicrm_wc_sync;

    // init plugin
    $civicrm_wc_sync = new CiviCRM_WP_Profile_WC_Sync;

}

// add action for the class init
add_action('civicrm_instance_loaded', 'civicrm_wc_sync_init');


