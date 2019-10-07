<?php

/**
 * This class provides the functionality to bulk create WordPress users from
 * a selection of CiviCRM contacts.
 */
class CRM_Contact_Form_Task_CreateWordPressUsers extends CRM_Contact_Form_Task {

  /**
   * Build all the data structures needed to build the form.
   *
   * @access public
   */
  public function preProcess() {

    // Parent must pre-process first.
    parent::preProcess();

    // Get rows.
    $rows = $this->getContactRows();

    // Our array now contains all contacts who can be synced to WordPress.
    $this->assign( 'rows', $rows );

    // Add text.
    $this->assign( 'tableID', __( 'ID', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'tableType', __( 'Type', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'tableDisplayName', __( 'Display Name', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'tableEmail', __( 'Email', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'tableUser', __( 'User Exists', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'notFound', __( 'There are no Contacts selected to create WordPress users from.', 'civicrm-wp-profile-sync' ) );

  }

  /**
   * Build the form.
   *
   * The form consists of a table listing all contacts with the necessary
   * information to create a WordPress user.
   *
   * @access public
   */
  public function buildQuickForm() {

    // Set a descriptive title.
    CRM_Utils_System::setTitle( __( 'Create WordPress Users from Contacts', 'civicrm-wp-profile-sync' ) );

    // Add our required buttons.
    $this->addButtons( array(
      array(
        'type' => 'next',
        'name' => __( 'Create WordPress Users', 'civicrm-wp-profile-sync' ),
        'isDefault' => TRUE,
      ),
      array(
        'type' => 'cancel',
        'name' => __( 'Cancel', 'civicrm-wp-profile-sync' ),
      ),
    ));

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
   * Get table rows populated with Contact data.
   *
   * @access private
   *
   * @return array $rows The contacts data array.
   */
  private function getContactRows() {

    // Init rows.
    $rows = array();

    // Get contacts via CiviCRM API.
    $result = civicrm_api( 'Contact', 'get', array(
      'version' => 3,
      'sequential' => 1,
      'id' => array( 'IN' => $this->_contactIds ),
    ));

    // Bail on failure.
    if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
      return $rows;
    }

    // Bail when there are no results.
    if ( count( $result['values'] ) === 0 ) {
      return $rows;
    }

    // Store raw contact data for reference in createUsers() if needed.
    $this->contactsRaw = $result['values'];

    // Build rows.
    foreach( $this->contactsRaw AS $contact ) {

      // Check if this Contact already has a WordPress user.
      $params = array(
        'version' => 3,
        'contact_id' => $contact['id'],
        'domain_id' => CRM_Core_Config::domainID(),
      );

      // Get all UFMatch records via API.
      $uf_result = civicrm_api( 'UFMatch', 'get', $params );

      // Skip on failure.
      if ( isset( $uf_result['is_error'] ) AND $uf_result['is_error'] == '1' ) {
        continue;
      }

      // Show if there's a UFMatch.
      $match = false;
      $has_user = __( 'No', 'civicrm-wp-profile-sync' );
      if ( ! empty( $uf_result['values'] ) AND count( $uf_result['values'] ) === 1 ) {
        $has_user = __( 'Yes', 'civicrm-wp-profile-sync' );
        $match = true;
      }

      // Build contact data row.
      $row = array(
        'id' => $contact['contact_id'],
        'contact_type' => $contact['contact_type'],
        'display_name' => $contact['display_name'],
        'email' => $contact['email'],
        'has_user' => $has_user,
        'user_exists' => $match ? 'y' : 'n',
      );

      /**
       * Filter contact data row and add to array.
       *
       * Use this filter to provide further contact data from which a username
       * can be built.
       *
       * @since 0.2.6
       *
       * @param array $row The default row of contact data.
       * @param null The contact data retrieved from the database. Deprecated.
       * @param array $contact The contact data retrieved from the database.
       * @return array $row The modified row of contact data.
       */
      $rows[] = apply_filters( 'civicrm_wp_profile_sync_contact_row', $row, null, $contact );

    }

    return $rows;

  }

  /**
   * Create WordPress users from contacts.
   *
   * @access private
   *
   * @param array $rows The contacts data array.
   */
  private function createUsers( $rows ) {

    // Extend PHP's execution time.
    ini_set( 'max_execution_time', 300 );

    // Get default role only once.
    $default_role = get_option( 'default_role' );

    /**
     * Broadcast that a user is about to be added.
     *
     * This allows other plugins to add or remove hooks.
     *
     * @since 0.2.3
     */
    do_action( 'civicrm_wp_profile_sync_user_add_pre' );

    // Get CiviCRM instance.
    $civicrm = civi_wp();

    // Do we have the old-style plugin structure?
    if ( method_exists( $civicrm, 'update_user' ) ) {

      // Disable Civi's own register hooks.
      remove_action( 'user_register', array( $civicrm, 'update_user' ) );
      remove_action( 'profile_update', array( $civicrm, 'update_user' ) );

    } else {

      // Disable Civi's own register hooks.
      remove_action( 'user_register', array( $civicrm->users, 'update_user' ) );
      remove_action( 'profile_update', array( $civicrm->users, 'update_user' ) );

    }

    // Process data.
    foreach( $rows AS $row ) {

      // Skip if user already exists.
      if ( $row['user_exists'] === 'y' ) continue;

      // Skip if no email.
      if ( empty( $row['email'] ) ) continue;

      // Skip if email is not valid.
      if ( ! is_email( $row['email'] ) ) continue;

      // Skip if email already exists.
      if ( email_exists( $row['email'] ) ) {
        error_log( print_r( array(
          'method' => __METHOD__,
          'message' => sprintf( __( 'The email %s already exists.', 'civicrm-wp-profile-sync' ), $row['email'] ),
        ), true ) );
        continue;
      }

      // Create a unique WordPress username for this contact.
      $username = $this->createUsername( $row );

      // Skip if username not valid for some reason.
      if ( ! validate_username( $username ) ) {
        error_log( print_r( array(
          'method' => __METHOD__,
          'message' => sprintf( __( 'The username %s is not valid.', 'civicrm-wp-profile-sync' ), $username ),
        ), true ) );
        continue;
      }

      // Do not assume success.
      $success = false;

      // Create an arbitrary password.
      $password = substr( md5( uniqid( microtime() ) ), 0, 8 );

      // Populate user data.
      $user_data = array(
        'ID' => '',
        'user_login' => $username,
        'user_email' => $row['email'],
        'user_pass' => $password,
        'nickname' => $username,
        'display_name' => $row['display_name'],
        'role' => $default_role,
      );

      // Create the WordPress user.
      $user_id = wp_insert_user( $user_data );

      // Create UFMatch record if successful.
      if ( ! is_wp_error( $user_id ) AND isset( $row['id'] ) ) {

        // Construct params.
        $uf_params = array(
          'version' => 3,
          'uf_id' => $user_id,
          'uf_name' => $username,
          'contact_id' => $row['id'],
          'domain_id' => CRM_Core_Config::domainID(),
        );

        // Create record via API.
        $result = civicrm_api( 'UFMatch', 'create', $uf_params );

        // Log something on failure.
        if ( isset( $uf_result['is_error'] ) AND $uf_result['is_error'] == '1' ) {
          error_log( print_r( array(
            'method' => __METHOD__,
            'message' => __( 'Could not create UFMatch record.', 'civicrm-wp-profile-sync' ),
            'result' => $result,
          ), true ) );
        }

        // Set user created success flag.
        $success = true;

      }

    }

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
     * Broadcast that a user has ben added.
     *
     * This allows other plugins to add or remove hooks.
     *
     * @since 0.2.3
     */
    do_action( 'civicrm_wp_profile_sync_user_add_post' );

    // Set a message.
    if ( $success ) {
      CRM_Core_Session::setStatus(
        __( 'Users successfully added to WordPress.', 'civicrm-wp-profile-sync' ),
        __( 'Users added', 'civicrm-wp-profile-sync' ),
        'success'
      );
    } else {
      CRM_Core_Session::setStatus(
        __( 'No Users were added to WordPress. This could be for a number of reasons - perhaps WordPress Users already exist for the selected Contacts or perhaps the Contacts had no associated email address. Please review the Contacts you added and try again.', 'civicrm-wp-profile-sync' ),
        __( 'No Users added', 'civicrm-wp-profile-sync' ),
        'info'
      );
    }

    // Maybe redirect.
    CRM_Utils_System::redirect(
      CRM_Utils_System::url( 'civicrm/contact/search/advanced', 'force=1' )
    );

  }

  /**
   * Create WordPress username from contact data.
   *
   * @access private
   *
   * @param array $row The contact data array.
   * @return str $username The unique username.
   */
  private function createUsername( $row ) {

      // Filter name.
      $name = $this->filterName( $row['display_name'] );

      // Construct a likely username.
      $username = sanitize_title( sanitize_user( $name ) );

      // Make username unique.
      $username = $this->uniqueUsername( $username, $name );

      /**
       * Allow plugins to pre-emptively override the username.
       *
       * @since 0.2.6
       *
       * @param str $username The current username.
       * @param array $row The user data from which the username has been constructed.
       * @return str $username The modified username.
       */
      $username = apply_filters( 'civicrm_wp_profile_sync_override_username', $username, $row );

      // --<
      return $username;

  }

  /**
   * Generate a unique username for a WordPress user.
   *
   * @since 0.2.8
   *
   * @param str $username The previously-generated WordPress username.
   * @param str $name The CiviCRM Contact's display name.
   * @return str $new_username The modified WordPress username.
   */
  public function uniqueUsername( $username, $name ) {

    // Bail if this is already unique.
    if ( ! username_exists( $username ) ) {
      return $username;
    }

    // Init flags.
    $count = 1;
    $user_exists = 1;

    do {

      // Construct new username with numeric suffix.
      $new_username = sanitize_title( sanitize_user( $name . ' ' . $count ) );

      // How did we do?
      $user_exists = username_exists( $new_username );

      // Try the next integer.
      $count++;

    } while ( $user_exists > 0 );

    // --<
    return $new_username;

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

