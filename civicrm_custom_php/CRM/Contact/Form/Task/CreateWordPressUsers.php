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

/*
 * Not sure why this should differ from the above and I would prefer my code to
 * be released under GPL v2+ but am deferring to http://civicrm.org/what/licensing
 * Copyright (C) 2014 Christian Wach <needle@haystack.co.uk>
 * Licensed to CiviCRM under the Academic Free License version 3.0.
 */

/**
 * This class provides the functionality to bulk create WordPress users from
 * a list of CiviCRM contacts.
 */
class CRM_Contact_Form_Task_CreateWordPressUsers extends CRM_Contact_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @access public
   */
  function preProcess() {
    parent::preProcess();

    // Get rows.
    $rows = $this->getContactRows();

    // Our array now contains all contacts who can be synced to WordPress.
    $this->assign('rows', $rows);

  }

  /**
   * Build the form - it consists of a table listing all contacts with the
   * necessary information to create a WordPress user.
   *
   * @access public
   */
  function buildQuickForm() {

    // Add our required buttons.
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
   * Process the form after the input has been submitted and validated.
   *
   * @access public
   */
  public function postProcess() {

    // Get rows again since I can't figure out how to override contactIDs.
    $rows = $this->getContactRows();

    // Create them.
    $this->createUsers( $rows );

  }

  /**
   * Get contacts with no WordPress user.
   *
   * @access private
   *
   * @return array The contacts data array.
   */
  private function getContactRows() {

    // Use Civi's user check.
    $config = CRM_Core_Config::singleton();

    // Process data.
    $rows = array();
    $dao = CRM_Core_DAO::executeQuery($this->getQuery());
    while ($dao->fetch()) {

      // Does this contact have a WP user?
      $errors = array();
      $check_params = array(
        'mail' => $dao->email,
      );
      //$config->userSystem->checkUserNameEmailExists($check_params, $errors);

      // We got one - skip.
      //if ( ! empty( $errors ) ) continue;

      // Build contact data item.
      $row = array(
        'id' => $dao->contact_id,
        'first_name' => $dao->first_name,
        'middle_name' => $dao->middle_name,
        'last_name' => $dao->last_name,
        'contact_type' => $dao->contact_type,
        'email' => $dao->email,
      );

      /**
       * Filter contact data and add to array.
       *
       * Use this filter together with `civicrm_wp_profile_sync_contact_query` to
       * provide further contact data from which a username can be built.
       *
       * @since 0.2.6
       *
       * @param array $row The default contact data.
       * @param obj $dao The contact data retrieved from the database.
       * @return array $row The modified contact data.
       */
      $rows[] = apply_filters( 'civicrm_wp_profile_sync_contact_row', $row, $dao );

    }

    return $rows;

  }

  /**
   * Create WordPress user from contacts.
   *
   * @access private
   *
   * @param array $rows The contacts data array.
   */
  private function createUsers( $rows ) {

    // Set debug flag when testing.
    $debug = false;

    // Init debug arrays.
    $users = array();
    $messages = array();

    // Extend PHP's execution time.
    ini_set('max_execution_time', 300);

    // Get default role only once.
    $default_role = get_option('default_role');

    // Get Civi config object.
    $config = CRM_Core_Config::singleton();

    // Code for redirect grabbed from CRM_Contact_Form_Task_Delete::postProcess().
    $urlParams = 'force=1';
    $urlString = "civicrm/contact/search/advanced";

    /**
     * Broadcast that a user is about to be added, allowing other plugins to add
     * or remove hooks.
     *
     * @since 0.2.3
     */
    do_action( 'civicrm_wp_profile_sync_user_add_pre' );

    // Get CiviCRM instance.
    $civi = civi_wp();

    // Do we have the old-style plugin structure?
    if ( method_exists( $civi, 'update_user' ) ) {

      // Disable Civi's own register hooks.
      remove_action( 'user_register', array( civi_wp(), 'update_user' ) );
      remove_action( 'profile_update', array( civi_wp(), 'update_user' ) );

    } else {

      // Disable Civi's own register hooks.
      remove_action( 'user_register', array( civi_wp()->users, 'update_user' ) );
      remove_action( 'profile_update', array( civi_wp()->users, 'update_user' ) );

    }

    // Process data.
    foreach ($rows AS $row) {

      // Skip if no email.
      if ( empty( $row['email'] ) ) continue;

      // Skip if email is not valid.
      if ( ! is_email( $row['email'] ) ) continue;

      // Skip if email already exists.
      if ( email_exists( $row['email'] ) ) {
        $messages[] = $row['email'] . ' already exists';
        continue;
      }

      // Filter names.
      $first_name = $this->filterName( $row['first_name'] );
      $middle_name = $this->filterName( $row['middle_name'] );
      $last_name = $this->filterName( $row['last_name'] );

      // Lots of first names are simply initials - if so, use both first and middle names.
      if ( strlen( $first_name ) == 1 ) {
        $first_name .= $middle_name;
      }

      // Let's only take a maximum of 8 letters of the last name.
      $last_name = substr( $last_name, 0, 8 );

      // Concatenate first and last names.
      $uname = $first_name . $last_name;

      // Construct a likely username.
      $uname = sanitize_title( sanitize_user( $uname ) );

      /**
       * Allow plugins to pre-emptively override the username.
       *
       * @since 0.2.6
       *
       * @param str $uname The current username.
       * @param array $row The user data from which the username has been constructed.
       * @return str $uname The modified username.
       */
      $uname = apply_filters( 'civicrm_wp_profile_sync_override_username', $uname, $row );

      // Skip if username not valid.
      if ( ! validate_username( $uname ) ) {
        $messages[] = 'username ' . $uname . ' is not valid';
        continue;
      }

      // Does this username already exist?
      if ( username_exists( $uname ) ) {

        $messages[] = 'username ' . $uname . ' already exists';
        $messages[] = $row;

        // Let's try adding in the middle name.
        $uname = $first_name . $middle_name . $last_name;

        // Construct a likely username.
        $uname = sanitize_title( sanitize_user( $uname ) );

        // Skip if username not valid.
        if ( ! validate_username( $uname ) ) {
          $messages[] = 'username ' . $uname . ' is not valid';
          continue;
        }

        // Skip if this username already exists.
        if ( username_exists( $uname ) ) {
          $messages[] = 'extra username ' . $uname . ' already exists';

          /**
           * Allow plugins to provide a username that does exist.
           *
           * @since 0.2.6
           *
           * @param str $uname The current username.
           * @param array $row The user data from which the username has been constructed.
           * @return str $uname The modified username.
           */
          $uname = apply_filters( 'civicrm_wp_profile_sync_unique_username', $uname, $row );

          // Let's test again just to be sure.
          if ( username_exists( $uname ) ) {
            $messages[] = 'filtered username ' . $uname . ' already exists';
            continue;
          } else {
            $messages[] = 'filtered username ' . $uname . ' does not exist - we can add it';
          }

        } else {
          $messages[] = 'extra username ' . $uname . ' does not exist - we can add it';
        }

      }

      /*
       * We cannot create WP user using CRM_Core_BAO_CMSUser::create() because it
       * will attempt to log the user in and notify them of their new account. We
       * have to find another means to do this.
       *
       * In the meantime, what follows is cloned from the CiviCRM process for
       * creating WordPress users and modified accordingly.
       */

      // Create an arbitrary password.
      $password = substr( md5( uniqid( microtime() ) ), 0, 8 );

      // Populate user data.
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

      // Skip if debugging.
      if ( ! $debug ) {

        // Add the user.
        $user_id = wp_insert_user($user_data);

        // If contact doesn't already exist create UF Match.
        if ( ! is_wp_error($user_id) && isset($row['id'] ) ) {

          $transaction = new CRM_Core_Transaction();

          // Create the UF Match record.
          $ufmatch             = new CRM_Core_DAO_UFMatch();
          $ufmatch->domain_id  = CRM_Core_Config::domainID();
          $ufmatch->uf_id      = $user_id;
          $ufmatch->contact_id = $row['id'];
          $ufmatch->uf_name    = $row['email'];

          if (!$ufmatch->find(TRUE)) {
            $ufmatch->save();
            $ufmatch->free();
            $transaction->commit();
          }
       }

      } else {

        // Add to debug array.
        $users[] = $user_data;

      }

    }

    // If debugging add log entry now.
    if ( $debug ) {
      error_log( print_r( array(
        'method' => __METHOD__,
        //'rows' => $rows,
        'messages' => $messages,
        'count' => count( $messages ),
        'users' => $users,
        //'backtrace' => debug_backtrace( 0 ),
      ), true ) );
    }

    // Do we have the old-style plugin structure?
    if ( method_exists( $civi, 'update_user' ) ) {

      // Re-add previous CiviCRM plugin filters.
      add_action( 'user_register', array( civi_wp(), 'update_user' ) );
      add_action( 'profile_update', array( civi_wp(), 'update_user' ) );

    } else {

      // Re-add current CiviCRM plugin filters.
      add_action( 'user_register', array( civi_wp()->users, 'update_user' ) );
      add_action( 'profile_update', array( civi_wp()->users, 'update_user' ) );

    }

    /**
     * Broadcast that a user has ben added, allowing other plugins to add or
     * remove hooks.
     *
     * @since 0.2.3
     */
    do_action( 'civicrm_wp_profile_sync_user_add_post' );

    // Set a message.
    CRM_Core_Session::setStatus('', ts('Users Added to WordPress'), 'success');

    // Maybe redirect.
    CRM_Utils_System::redirect(CRM_Utils_System::url($urlString, $urlParams));

  }

  /**
   * Get query to retrive contact data.
   *
   * @access private
   *
   * @return array The contacts data array.
   */
  private function getQuery() {

    // Get selected contact IDs.
    $contactIDs = implode( ',', $this->_contactIds );

    // Construct query to get name and email of selected contact ids.
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

    /**
     * Filter contact query.
     *
     * Use this filter in combination with `civicrm_wp_profile_sync_contact_row`
     * to modify the data for each contact if, for example, you want other fields
     * from which to determine the final username.
     *
     * @since 0.2.6
     *
     * @param str $query The default contact query.
     * @param array $contactIDs The array of contact IDs.
     * @return str $query The modified contact query.
     */
    return apply_filters( 'civicrm_wp_profile_sync_contact_query', $query, $contactIDs );

  }

  /**
   * Filter a name to remove unwanted chars.
   *
   * @access private
   *
   * @param str The unfiltered name.
   * @return str The filtered name.
   */
  private function filterName( $name ) {

  // Build array of replacements.
  $replacements = array( '.', ' ', '-', "'", "’" );

    // Do replacement.
    $name = str_replace( $replacements, '', $name );

    // --<
    return $name;

  }

}

