<?php

/**
 * This class provides the functionality to bulk create WordPress Users from
 * a selection of CiviCRM Contacts.
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

    // Our array now contains all Contacts who can be synced to WordPress.
    $this->assign( 'rows', $rows );

    // Add text.
    $this->assign( 'tableID', __( 'ID', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'tableType', __( 'Type', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'tableDisplayName', __( 'Display Name', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'tableEmail', __( 'Email', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'tableUser', __( 'User Exists', 'civicrm-wp-profile-sync' ) );
    $this->assign( 'notFound', __( 'There are no Contacts selected to create WordPress Users from.', 'civicrm-wp-profile-sync' ) );

  }

  /**
   * Build the form.
   *
   * The form consists of a table listing all Contacts with the necessary
   * information to create a WordPress User.
   *
   * @access public
   */
  public function buildQuickForm() {

    // Set a descriptive title.
    CRM_Utils_System::setTitle( __( 'Create WordPress Users from Contacts', 'civicrm-wp-profile-sync' ) );

    // Add our required buttons.
    $this->addButtons( [
      [
        'type' => 'next',
        'name' => __( 'Create WordPress Users', 'civicrm-wp-profile-sync' ),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => __( 'Cancel', 'civicrm-wp-profile-sync' ),
      ],
    ]);

  }

  /**
   * Process the form after the input has been submitted and validated.
   *
   * @access public
   */
  public function postProcess() {

    // Get rows again since I can't figure out how to override Contact IDs.
    $rows = $this->getContactRows();

    // Create them.
    $this->createUsers( $rows );

  }

  /**
   * Get table rows populated with Contact data.
   *
   * @access private
   *
   * @return array $rows The Contacts data array.
   */
  private function getContactRows() {

    // Init rows.
    $rows = [];

    // Get Contacts via CiviCRM API.
    $result = civicrm_api( 'Contact', 'get', [
      'version' => 3,
      'sequential' => 1,
      'id' => [ 'IN' => $this->_contactIds ],
			'options' => [
				'limit' => 0, // No limit.
			],
    ]);

    // Bail on failure.
    if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
      return $rows;
    }

    // Bail when there are no results.
    if ( count( $result['values'] ) === 0 ) {
      return $rows;
    }

    // Store raw Contact data for reference in createUsers() if needed.
    $this->contactsRaw = $result['values'];

    // Build rows.
    foreach( $this->contactsRaw AS $contact ) {

      // Check if this Contact already has a WordPress User.
      $params = [
        'version' => 3,
        'contact_id' => $contact['id'],
        'domain_id' => CRM_Core_Config::domainID(),
      ];

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

      // Build Contact data row.
      $row = [
        'id' => $contact['contact_id'],
        'contact_type' => $contact['contact_type'],
        'display_name' => ! empty( $contact['display_name'] ) ? $contact['display_name'] : '',
        'first_name' => ! empty( $contact['first_name'] ) ? $contact['first_name'] : '',
        'last_name' => ! empty( $contact['last_name'] ) ? $contact['last_name'] : '',
        'email' => $contact['email'],
        'has_user' => $has_user,
        'user_exists' => $match ? 'y' : 'n',
      ];

      /**
       * Filter Contact data row and add to array.
       *
       * Use this filter to provide further Contact data from which a WordPress
       * User's username can be built.
       *
       * @since 0.2.6
       *
       * @param array $row The default row of Contact data.
       * @param null The Contact data retrieved from the database. Deprecated.
       * @param array $contact The Contact data retrieved from the database.
       * @return array $row The modified row of Contact data.
       */
      $rows[] = apply_filters( 'civicrm_wp_profile_sync_contact_row', $row, null, $contact );

    }

    return $rows;

  }

  /**
   * Create WordPress Users from Contacts.
   *
   * @access private
   *
   * @param array $rows The Contacts data array.
   */
  private function createUsers( $rows ) {

    // Extend PHP's execution time.
    ini_set( 'max_execution_time', 300 );

    // Get default role only once.
    $default_role = get_option( 'default_role' );

    /**
     * Broadcast that a User is about to be added.
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

      // Disable CiviCRM's own register hooks.
      remove_action( 'user_register', [ $civicrm, 'update_user' ] );
      remove_action( 'profile_update', [ $civicrm, 'update_user' ] );

    } else {

      // Disable CiviCRM's own register hooks.
      remove_action( 'user_register', [ $civicrm->users, 'update_user' ] );
      remove_action( 'profile_update', [ $civicrm->users, 'update_user' ] );

    }

    // Init success and failure arrays.
    $success = [];
    $failure = [];

    // Process data.
    foreach( $rows AS $row ) {

      // Skip if User already exists.
      if ( $row['user_exists'] === 'y' ) {
        $failure[] = $row['display_name'];
        continue;
      }

      // Skip if no email.
      if ( empty( $row['email'] ) ) {
        $failure[] = $row['display_name'];
        continue;
      }

      // Skip if email is not valid.
      if ( ! is_email( $row['email'] ) ) {
        $failure[] = $row['display_name'];
        continue;
      }

      // Skip if email already exists.
      if ( email_exists( $row['email'] ) ) {
        error_log( print_r( [
          'method' => __METHOD__,
          'message' => sprintf( __( 'The email %s already exists.', 'civicrm-wp-profile-sync' ), $row['email'] ),
        ], true ) );
        $failure[] = $row['display_name'];
        continue;
      }

      // Create a unique WordPress username for this Contact.
      $username = $this->createUsername( $row );

      // Skip if username not valid for some reason.
      if ( ! validate_username( $username ) ) {
        error_log( print_r( [
          'method' => __METHOD__,
          'message' => sprintf( __( 'The username %s is not valid.', 'civicrm-wp-profile-sync' ), $username ),
        ], true ) );
        $failure[] = $row['display_name'];
        continue;
      }

      // Create an arbitrary password.
      $password = substr( md5( uniqid( microtime() ) ), 0, 8 );

      // Populate User data.
      $user_data = [
        'ID' => '',
        'user_login' => $username,
        'user_email' => $row['email'],
        'user_pass' => $password,
        'nickname' => $username,
        'display_name' => $row['display_name'],
        'first_name' => $row['first_name'],
        'last_name' => $row['last_name'],
        'role' => $default_role,
      ];

      // Create the WordPress User.
      $user_id = wp_insert_user( $user_data );

      // Create UFMatch record if successful.
      if ( ! is_wp_error( $user_id ) AND isset( $row['id'] ) ) {

        // Construct params.
        $uf_params = [
          'version' => 3,
          'uf_id' => $user_id,
          'uf_name' => $username,
          'contact_id' => $row['id'],
          'domain_id' => CRM_Core_Config::domainID(),
        ];

        // Create record via API.
        $result = civicrm_api( 'UFMatch', 'create', $uf_params );

        // Log something on failure.
        if ( isset( $uf_result['is_error'] ) AND $uf_result['is_error'] == '1' ) {
          error_log( print_r( [
            'method' => __METHOD__,
            'message' => __( 'Could not create UFMatch record.', 'civicrm-wp-profile-sync' ),
            'result' => $result,
          ], true ) );
        }

        // Add User to success array.
        $success[] = $row['display_name'];

      } else {

        // Add User to failure array.
        $failure[] = $row['display_name'];

      }

    }

    // Do we have the old-style plugin structure?
    if ( method_exists( $civicrm, 'update_user' ) ) {

      // Re-add previous CiviCRM plugin filters.
      add_action( 'user_register', [ $civicrm, 'update_user' ] );
      add_action( 'profile_update', [ $civicrm, 'update_user' ] );

    } else {

      // Re-add current CiviCRM plugin filters.
      add_action( 'user_register', [ $civicrm->users, 'update_user' ] );
      add_action( 'profile_update', [ $civicrm->users, 'update_user' ] );

    }

    /**
     * Broadcast that a WordPress User has ben added.
     *
     * This allows other plugins to add or remove hooks.
     *
     * @since 0.2.3
     */
    do_action( 'civicrm_wp_profile_sync_user_add_post' );

    // Build success message.
    if ( ! empty( $success ) ) {
      $users_added = sprintf(
        __( 'Users successfully added to WordPress: %s', 'civicrm-wp-profile-sync' ),
        implode( ', ', $success )
      );
      CRM_Core_Session::setStatus( $users_added, __( 'Users added', 'civicrm-wp-profile-sync' ), 'success' );
    }

    // Build failure message.
    if ( ! empty( $failure ) ) {
      $users_not_added = sprintf(
        __( 'Users not added to WordPress: %s. Please review these Contacts and try again.', 'civicrm-wp-profile-sync' ),
        implode( ', ', $failure )
      );
      CRM_Core_Session::setStatus( $users_not_added, __( 'Users not added', 'civicrm-wp-profile-sync' ), 'error' );
    }

    // Maybe redirect.
    CRM_Utils_System::redirect(
      CRM_Utils_System::url( 'civicrm/contact/search/advanced', 'force=1' )
    );

  }

  /**
   * Create WordPress username from Contact data.
   *
   * @access private
   *
   * @param array $row The Contact data array.
   * @return string $username The unique username.
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
       * @param string $username The current username.
       * @param array $row The User data from which the username has been constructed.
       * @return string $username The modified username.
       */
      $username = apply_filters( 'civicrm_wp_profile_sync_override_username', $username, $row );

      // --<
      return $username;

  }

  /**
   * Generate a unique username for a WordPress User.
   *
   * @since 0.2.8
   *
   * @param string $username The previously-generated WordPress username.
   * @param string $name The CiviCRM Contact's display name.
   * @return string $new_username The modified WordPress username.
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
   * @param string The unfiltered name.
   * @return string The filtered name.
   */
  private function filterName( $name ) {

    // Build array of replacements.
    $replacements = [ '.', ' ', '-', "'", "’" ];

    // Do replacement.
    $name = str_replace( $replacements, '', $name );

    // --<
    return $name;

  }

}

