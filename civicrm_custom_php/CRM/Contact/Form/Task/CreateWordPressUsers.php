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
 * This class provides the functionality to bulk create WordPress users from
 * a list of CiviCRM contacts
 */
class CRM_Contact_Form_Task_CreateWordPressUsers extends CRM_Contact_Form_Task {

  /**
   * Build all the data structures needed to build the form
   *
   * @access public
   *
   * @return void
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
      //$config->userSystem->checkUserNameEmailExists($check_params, $errors);

      // we got one - skip
      //if ( ! empty( $errors ) ) continue;

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

  	// set debug flag when testing
  	$debug = false;

  	// init debug arrays
  	$users = array();
  	$messages = array();

    // extend PHP's execution time
    ini_set('max_execution_time', 300);

    // get default role only once
    $default_role = get_option('default_role');

    // get Civi config object
    $config = CRM_Core_Config::singleton();

    // code for redirect grabbed from CRM_Contact_Form_Task_Delete::postProcess()
    $urlParams = 'force=1';
    $urlString = "civicrm/contact/search/advanced";

    // let WordPress plugins know what we're about to do
    do_action( 'civicrm_wp_profile_sync_user_add_pre' );

    // disable Civi's own register hooks
    remove_action( 'user_register', array( civi_wp(), 'update_user' ) );
    remove_action( 'profile_update', array( $this, 'update_user' ) );

    // process data
    foreach ($rows AS $row) {

      // skip if no email
      if ( empty( $row['email'] ) ) continue;

      // skip if email is not valid
      if ( ! is_email( $row['email'] ) ) continue;

      // skip if email already exists
      if ( email_exists( $row['email'] ) ) {
        $messages[] = $row['email'] . ' already exists';
        continue;
      }

      // filter names
      $first_name = $this->filterName( $row['first_name'] );
      $middle_name = $this->filterName( $row['middle_name'] );
      $last_name = $this->filterName( $row['last_name'] );

      // lots of first names are simply initials - if so, use both first and middle names
      if ( strlen( $first_name ) == 1 ) {
      	$first_name .= $middle_name;
      }

      // lets only take a maximum of 8 letters of the last name
      $last_name = substr( $last_name, 0, 8 );

      // concatenate first and last names
      $uname = $first_name . $last_name;

      // construct a likely username
      $uname = sanitize_user( $uname );

      // skip if username not valid
      if ( ! validate_username( $uname ) ) {
        $messages[] = 'username ' . $uname . ' is not valid';
        continue;
      }

      // does this username already exist?
      if ( username_exists( $uname ) ) {

        $messages[] = 'username ' . $uname . ' already exists';
        $messages[] = $row;

        // let's try adding in the middle name
        $uname = $first_name . $middle_name . $last_name;

        // construct a likely username
        $uname = sanitize_user( $uname );

        // skip if username not valid
        if ( ! validate_username( $uname ) ) {
          $messages[] = 'username ' . $uname . ' is not valid';
          continue;
        }

        // skip if this username already exists
        if ( username_exists( $uname ) ) {
          $messages[] = 'extra username ' . $uname . ' already exists';
          continue;
        } else {
          $messages[] = 'extra username ' . $uname . ' does not exist - we could add it';
        }

      }

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

      // populate user data
      $user_data = array(
        'ID' => '',
        'user_login' => $uname,
        'user_email' => $row['email'],
        'user_pass' => $password,
        'nickname' => $uname,
        'role' => $default_role,
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
      );

      // skip if debugging
      if ( ! $debug ) {

        // add the user
        $user_id = wp_insert_user($user_data);

        // if contact doesn't already exist create UF Match
        if ( $user_id !== FALSE && isset($row['id']) ) {

          $transaction = new CRM_Core_Transaction();

          // create the UF Match record
          $ufmatch             = new CRM_Core_DAO_UFMatch();
          $ufmatch->domain_id  = CRM_Core_Config::domainID();
          $ufmatch->uf_id      = $user_id;
          $ufmatch->contact_id = $row['id'];
          $ufmatch->uf_name    = $row['mail'];

          if (!$ufmatch->find(TRUE)) {
            $ufmatch->save();
            $ufmatch->free();
            $transaction->commit();
          }
       }

      } else {

      	// add to debug array
      	$users[] = $user_data;

      }

    }

    // if debugging die now
    if ( $debug ) {
	  trigger_error( print_r( array(
		'method' => 'createUsers',
		//'rows' => $rows,
		'messages' => $messages,
		'count' => count( $messages ),
		'users' => $users,
	  ), true ), E_USER_ERROR ); die();
	}

    // re-enable Civi's register hooks
    add_action( 'user_register', array( civi_wp(), 'update_user' ) );
    add_action( 'profile_update', array( civi_wp(), 'update_user' ) );

    // let WordPress plugins know what we've done
    do_action( 'civicrm_wp_profile_sync_user_add_post' );

    // set a message
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

  /**
   * Filter a name to remove unwanted chars
   *
   * @access private
   *
   * @param str The unfiltered name
   * @return str The filtered name
   */
  private function filterName( $name ) {

	// build array of replacements
	$replacements = array( '.', ' ', '-', "'", "’" );

	// do replacement
    $name = str_replace( $replacements, '', $name );

	// --<
	return $name;

  }

}

