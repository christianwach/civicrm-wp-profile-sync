<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * Not sure why this should differ from the above and I would prefer my code to
 * be released under GPL v2+ but am deferring to http://civicrm.org/what/licensing
 * Copyright (C) 2014 Christian Wach <needle@haystack.co.uk>
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 */
 
/**
 * This class provides the functionality to save a search
 * Saved Searches are used for saving frequently used queries
 */
class CRM_Contact_Form_Task_CreateWordPressUsers extends CRM_Contact_Form_Task {

  /**
   * build all the data structures needed to build the form
   *
   * @return void
   * @access public
   */
  function preProcess() {
    parent::preProcess();
    
    // get rows
    $rows = $this->getContactRows();
    
    // our array now contains all contacts who can be synced to WordPress
    $this->assign('rows', $rows);
    
  }

  /**
   * Build the form - it consists of a table listing all contacts with the 
   * necessary information to create a WordPress user
   *
   * @access public
   *
   * @return void
   */
  function buildQuickForm() {
    
    // add our required buttons
    $this->addButtons(
      array(
        array(
          'type' => 'next',
          'name' => ts('Add'),
          'isDefault' => TRUE,
        ),
        array(
          'type' => 'cancel',
          'name' => ts('Cancel'),
        ),
      )
    );
    
  }

  /**
   * Process the form after the input has been submitted and validated
   *
   * @access public
   *
   * @return void
   */
  public function postProcess() {
    
    // get rows again (I can't figure out how to override contactIDs)
    $rows = $this->getContactRows();
  
    // create them...
    $this->createUsers( $rows );
    
  }

  /**
   * Get contacts with no WordPress user
   *
   * @access private
   *
   * @return array The contacts data array
   */
  private function getContactRows() {
  
    // use Civi's user check
    $config = CRM_Core_Config::singleton();
    
    // process data
    $rows = array();
    $dao = CRM_Core_DAO::executeQuery($this->getQuery());
    while ($dao->fetch()) {
      
      // does this contact have a WP user?
      $errors = array();
      $check_params = array(
        'mail' => $dao->email,
      );
      $config->userSystem->checkUserNameEmailExists($check_params, $errors);
      
      // we got one - skip
      if ( ! empty( $errors ) ) continue;
      
      // add to our display
      $rows[] = array(
        'id' => $dao->contact_id,
        'first_name' => $dao->first_name,
        'middle_name' => $dao->middle_name,
        'last_name' => $dao->last_name,
        'contact_type' => $dao->contact_type,
        'email' => $dao->email,
      );
      
    }
    
    return $rows;
    
  }

  /**
   * Create WordPress user from contacts
   *
   * @access private
   *
   * @param array $rows The contacts data array
   * @return void
   */
  private function createUsers( $rows ) {
  
    // get Civi config object
    $config = CRM_Core_Config::singleton();
    
    // code for redirect grabbed from CRM_Contact_Form_Task_Delete::postProcess()
    $context   = CRM_Utils_Request::retrieve('context', 'String', $this, FALSE, 'basic');
    $urlParams = 'force=1';
    $urlString = "civicrm/contact/search/$context";
    
    if (CRM_Utils_Rule::qfKey($this->_searchKey)) {
      $urlParams .= "&qfKey=$this->_searchKey";
    }
    elseif ($context == 'search') {
      $urlParams .= "&qfKey={$this->controller->_key}";
      $urlString = 'civicrm/contact/search';
    }
    
    // process data
    foreach ($rows AS $row) {
      
      // concatenate names
      $uname = $row['first_name'] . $row['middle_name'] . $row['last_name'];
      
      // strip unwanted chars
      $uname = str_replace( '.', '', $uname );
      $uname = str_replace( ' ', '', $uname );
      
      // construct a likely username
      $uname = sanitize_user( $uname );
      
      // does this contact have a WP user?
      $errors = array();
      $params = array(
        'name' => $uname,
        'mail' => $row['email'],
      );
      $config->userSystem->checkUserNameEmailExists($params, $errors);
      
      // we got one - skip
      if ( ! empty( $errors ) ) continue;
      
      /**
       * We cannot create WP user using CRM_Core_BAO_CMSUser::create() because it
       * will attempt to log the user in and notify them of their new account. We
       * have to find another means to do this.
       *
       * In the meantime, what follows is cloned from the CiviCRM process for
       * creating WordPress users and modified accordingly.
       */
    
      // create an arbitrary password
      $password = substr( md5( uniqid( microtime() ) ), 0, 8 );
      
      // first name is slightly problematic
      $first_name = $row['first_name'];
      if ( ! empty( $row['middle_name'] ) ) {
      	// merge first and middle names if a middle name exists
        $first_name .= ' ' . $row['middle_name'];
      }
      
      // populate user data
      $user_data = array(
        'ID' => '',
        'user_login' => $params['name'],
        'user_email' => $params['mail'],
        'user_pass' => $password,
        'nickname' => $params['name'],
        'role' => get_option('default_role'),
        'first_name' => $first_name,
        'last_name' => $row['last_name'],
      );
      
      // let WordPress plugins know what we're about to do
      do_action( 'civicrm_wp_profile_sync_user_add_pre' );
      
      // add the user
      $user_id = wp_insert_user($user_data);

      // let WordPress plugins know what we've done
      do_action( 'civicrm_wp_profile_sync_user_add_post' );
      
      // if contact doesn't already exist create UF Match
      if ($user_id !== FALSE && is_numeric( $user_id ) && isset($row['id'])) {
      	$user = get_user_by('id', $user_id);
      	CRM_Core_BAO_UFMatch::synchronizeUFMatch( $user, $user->ID, $user->user_email, 'WordPress' );
      }
      
    }
    
    CRM_Core_Session::setStatus('', ts('Users Added to WordPress'), 'success');
    
    // redirect?
    CRM_Utils_System::redirect(CRM_Utils_System::url($urlString, $urlParams));
  
  }

  /**
   * Get query to retrive contact data
   *
   * @access private
   *
   * @return array The contacts data array
   */
  private function getQuery() {
  
    // get selected contact IDs
    $contactIDs = implode( ',', $this->_contactIds );
    
    // construct query to get name and email of selected contact ids
    $query = "
		SELECT c.id as contact_id, 
		       c.first_name as first_name, 
		       c.middle_name as middle_name, 
		       c.last_name as last_name,
			   c.contact_type as contact_type, 
			   e.email as email
		FROM   civicrm_contact c, 
		       civicrm_email e
		WHERE  e.contact_id = c.id
		AND    e.is_primary = 1
		AND    c.id IN ( $contactIDs )";
	
	// --<
	return $query;

  }

}

