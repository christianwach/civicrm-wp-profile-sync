<?php
/**
 * CiviCRM Group Class.
 *
 * Handles CiviCRM Group functionality.
 *
 * For now, Smart Groups are excluded from sync. The reasons for this are that
 * there are no hooks that fire when a Contact is added or removed from a Smart
 * Group and, to complicate matters, a Contact can be manually added and/or
 * removed to/from a Smart Group. There is code here to retrieve Smart Group
 * membership, but it is inactive until it is decided how to proceed.
 *
 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Group::groups_get_current_for_contact()
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Group Class.
 *
 * A class that encapsulates CiviCRM Group functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Group {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_ACF_Loader
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM
	 */
	public $civicrm;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm    = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'register_hooks' ] );

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Intercept Post-Contact sync event.
		add_action( 'cwps/acf/post/contact/sync', [ $this, 'contact_sync_to_post' ], 10 );

		// Intercept calls to sync the Group.
		add_action( 'cwps/acf/admin/group-to-term/sync', [ $this, 'group_sync' ], 10 );

	}

	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.5.2
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( true === $this->mapper_hooks ) {
			return;
		}

		// Intercept prior to a CiviCRM Group being deleted.
		add_action( 'cwps/acf/mapper/group/delete/pre', [ $this, 'group_deleted_pre' ], 10 );

		// Intercept CiviCRM's add Contacts to Group.
		add_action( 'cwps/acf/mapper/group/contacts/created', [ $this, 'group_contacts_created' ], 10 );

		// Intercept CiviCRM's delete Contacts from Group.
		add_action( 'cwps/acf/mapper/group/contacts/deleted', [ $this, 'group_contacts_deleted' ], 10 );

		// Intercept CiviCRM's rejoin Contacts to Group.
		add_action( 'cwps/acf/mapper/group/contacts/rejoined', [ $this, 'group_contacts_rejoined' ], 10 );

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.5.2
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( false === $this->mapper_hooks ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/group/delete/pre', [ $this, 'group_deleted_pre' ], 10 );
		remove_action( 'cwps/acf/mapper/group/contacts/created', [ $this, 'group_contacts_created' ], 10 );
		remove_action( 'cwps/acf/mapper/group/contacts/deleted', [ $this, 'group_contacts_deleted' ], 10 );
		remove_action( 'cwps/acf/mapper/group/contacts/rejoined', [ $this, 'group_contacts_rejoined' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept when a Post is been synced from a Contact.
	 *
	 * Sync any associated Terms mapped to CiviCRM Groups.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get "current" Groups for this Contact.
		$current = $this->groups_get_current_for_contact( $args['objectId'] );

		// Get "removed" Groups for this Contact.
		$removed = $this->groups_get_removed_for_contact( $args['objectId'] );

		// Process terms for "current" Group Contacts.
		if ( ! empty( $current ) ) {
			foreach ( $current as $group_membership ) {

				// Get params.
				$group_id    = $group_membership['group_id'];
				$contact_ids = [ $args['objectId'] ];

				// Sync this Group Contact to WordPress Terms.
				$this->acf_loader->post->tax->terms_update_for_group_contacts( $group_id, $contact_ids, 'add' );

			}
		}

		// Process terms for "removed" Group Contacts.
		if ( ! empty( $removed ) ) {
			foreach ( $removed as $group_membership ) {

				// Get params.
				$group_id    = $group_membership['group_id'];
				$contact_ids = [ $args['objectId'] ];

				// Sync this Group Contact to WordPress Terms.
				$this->acf_loader->post->tax->terms_update_for_group_contacts( $group_id, $contact_ids, 'remove' );

			}
		}

	}

	/**
	 * Create a WordPress Post when a CiviCRM Contact is being synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact data.
	 */
	public function group_sync( $args ) {

		// Extract "current" and "removed" members.
		$current = wp_list_filter( $args['objectRef'], [ 'status' => 'Added' ] );
		$removed = wp_list_filter( $args['objectRef'], [ 'status' => 'Removed' ] );

		// Process terms for "current" Group Contacts.
		if ( ! empty( $current ) ) {
			$current_contact_ids = wp_list_pluck( $current, 'contact_id' );
			$this->acf_loader->post->tax->terms_update_for_group_contacts( $args['objectId'], $current_contact_ids, 'add' );
		}

		// Process terms for "removed" Group Contacts.
		if ( ! empty( $removed ) ) {
			$removed_contact_ids = wp_list_pluck( $removed, 'contact_id' );
			$this->acf_loader->post->tax->terms_update_for_group_contacts( $args['objectId'], $removed_contact_ids, 'remove' );
		}

		// Add our data to the params.
		$args['current'] = $current;
		$args['removed'] = $removed;

		/**
		 * Broadcast that WordPress Terms have been synced from Group Contact details.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/admin/group/contact/sync', $args );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get all mapped CiviCRM Groups.
	 *
	 * @since 0.4
	 *
	 * @return array $groups The array of mapped CiviCRM Groups.
	 */
	public function groups_get_mapped() {

		// Init return.
		$groups = [];

		// Get all synced terms.
		$synced_terms = $this->acf_loader->post->tax->synced_terms_get_all();

		// Grab just the Group IDs.
		$group_ids = wp_list_pluck( $synced_terms, 'group_id' );

		// Bail if there are none.
		if ( empty( $group_ids ) ) {
			return $groups;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $groups;
		}

		// Params to get queried Groups.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'is_hidden'  => 0,
			'is_active'  => 1,
			'id'         => [ 'IN' => $group_ids ],
			'options'    => [
				'sort'  => 'title',
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Group', 'get', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return [];
		}

		// Assign Groups data.
		$groups = $result['values'];

		// --<
		return $groups;

	}

	/**
	 * Get all CiviCRM Groups that are not Smart Groups.
	 *
	 * @since 0.4
	 *
	 * @return array $groups The array of CiviCRM Groups.
	 */
	public function groups_get_all() {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return [];
		}

		// Params to get all Groups (except Smart Groups).
		$params = [
			'version'         => 3,
			'sequential'      => 1,
			'is_hidden'       => 0,
			'is_active'       => 1,
			'saved_search_id' => [ // Exclude Smart Groups.
				'IS NULL' => 1,
			],
			'options'         => [
				'sort'  => 'name',
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Group', 'get', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return [];
		}

		// Assign Groups data.
		$groups = $result['values'];

		// --<
		return $groups;

	}

	/**
	 * Get all CiviCRM Groups that a Contact is a "current" member of.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $group_data The array of Group data for the CiviCRM Contact.
	 */
	public function groups_get_current_for_contact( $contact_id ) {

		// Init return.
		$group_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $group_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_data;
		}

		// Params to query Group membership.
		$params = [
			'version'    => 3,
			'contact_id' => $contact_id,
			'status'     => 'Added',
			'sequential' => 1,
			'is_hidden'  => 0,
			'is_active'  => 1,
			'options'    => [
				'limit' => 0,
			],
		];

		// TODO: Decide on whether to allow Smart Group sync.

		/*
		 * Note: The CiviCRM API does not fetch Smart Groups for a Contact.
		 *
		 * To retrieve Contacts in a Smart Group, we can use the Contact API,
		 * passing "contact_id" and "group_id" as params.
		 */

		// Call API.
		$result = civicrm_api( 'GroupContact', 'get', $params );

		/*
		 * Note: The native query returns the same info as the API however it
		 * is also possible to retrieve the Smart Groups that a Contact has
		 * been manually added to. The cache method below does not seem to.
		 */

		/*
		// Query via native method.
		$smart = CRM_Contact_BAO_GroupContact::getContactGroup(
			$contact_id, // $contactId
			'Added', // $status
			null, // $numGroupContact
			false, // $count
			false, // $ignorePermission
			false, // $onlyPublicGroups
			true, // $excludeHidden
			null, // $groupId
			true // $includeSmartGroups
		);
		*/

		/*
		 * Note: The Smart Group cache query returns only the "group_id" as
		 * useful information. Further processing of the array is necessary to
		 * make it compatible with the format of the two nethods above.
		 *
		 * CiviCRM notes:
		 *
		 * "Note that this could potentially be a super slow function since
		 * it ensures that all Contact Groups are loaded in the cache."
		 */

		// Query via cache method.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// $cache = CRM_Contact_BAO_GroupContactCache::contactGroup( $contact_id );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'params' => $params,
			'result' => $result,
			//'smart' => $smart,
			//'cache' => $cache,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'     => __METHOD__,
				'contact_id' => $contact_id,
				'params'     => $params,
				'result'     => $result,
				'backtrace'  => $trace,
			];
			$this->plugin->log_error( $log );
			return $group_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $group_data;
		}

		// Assign Groups data.
		$group_data = $result['values'];

		// --<
		return $group_data;

	}

	/**
	 * Get all CiviCRM Groups that a Contact is a "removed" member of.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $group_data The array of Group data for the CiviCRM Contact.
	 */
	public function groups_get_removed_for_contact( $contact_id ) {

		// Init return.
		$group_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $group_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group_data;
		}

		// Params to query Group membership.
		$params = [
			'version'    => 3,
			'contact_id' => $contact_id,
			'status'     => 'Removed',
			'sequential' => 1,
			// 'is_hidden' => 0,
			'is_active'  => 1,
			'options'    => [
				'limit' => 0,
			],
		];

		// Call API.
		$result = civicrm_api( 'GroupContact', 'get', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'     => __METHOD__,
				'contact_id' => $contact_id,
				'params'     => $params,
				'result'     => $result,
				'backtrace'  => $trace,
			];
			$this->plugin->log_error( $log );
			return $group_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $group_data;
		}

		// Assign Groups data.
		$group_data = $result['values'];

		// --<
		return $group_data;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the number of Contacts who are members of a CiviCRM Group.
	 *
	 * @since 0.4
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @return integer $count The number of Contacts in the Group, or false otherwise.
	 */
	public function group_contact_count( $group_id ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return 0;
		}

		// Params to query Group membership.
		$params = [
			'version'  => 3,
			'group_id' => $group_id,
			'options'  => [
				'limit' => 0,
			],
		];

		// Call API.
		$result = civicrm_api( 'GroupContact', 'get', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'group_id'  => $group_id,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return empty( $result['count'] ) ? 0 : (int) $result['count'];

	}

	/**
	 * Check if a CiviCRM Contact is a member of a CiviCRM Group.
	 *
	 * @since 0.4
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_id The numeric ID of a CiviCRM Contact.
	 * @return bool $is_member True if the Contact is in the Group, or false otherwise.
	 */
	public function group_contact_exists( $group_id, $contact_id ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Params to query Group membership.
		$params = [
			'version'    => 3,
			'group_id'   => $group_id,
			'contact_id' => $contact_id,
		];

		// Call API.
		$result = civicrm_api( 'GroupContact', 'get', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'     => __METHOD__,
				'group_id'   => $group_id,
				'contact_id' => $contact_id,
				'params'     => $params,
				'result'     => $result,
				'backtrace'  => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return empty( $result['values'] ) ? false : true;

	}

	/**
	 * Add a CiviCRM Contact to a CiviCRM Group.
	 *
	 * @since 0.4
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_id The numeric ID of a CiviCRM Contact.
	 * @return array|bool $result The Group-Contact data, or false on failure.
	 */
	public function group_contact_create( $group_id, $contact_id ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Params to add Group membership.
		$params = [
			'version'    => 3,
			'group_id'   => $group_id,
			'contact_id' => $contact_id,
			'status'     => 'Added',
		];

		// Call API.
		$result = civicrm_api( 'GroupContact', 'create', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'     => __METHOD__,
				'group_id'   => $group_id,
				'contact_id' => $contact_id,
				'params'     => $params,
				'result'     => $result,
				'backtrace'  => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return $result;

	}

	/**
	 * Delete a CiviCRM Contact from a CiviCRM Group.
	 *
	 * @since 0.4
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_id The numeric ID of a CiviCRM Contact.
	 * @return array|bool $result The Group-Contact data, or false on failure.
	 */
	public function group_contact_delete( $group_id, $contact_id ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Params to remove Group membership.
		$params = [
			'version'    => 3,
			'group_id'   => $group_id,
			'contact_id' => $contact_id,
			'status'     => 'Removed',
		];

		// Call API.
		$result = civicrm_api( 'GroupContact', 'create', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'     => __METHOD__,
				'group_id'   => $group_id,
				'contact_id' => $contact_id,
				'params'     => $params,
				'result'     => $result,
				'backtrace'  => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return $result;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add a CiviCRM Contact to a CiviCRM Group with Double Opt In.
	 *
	 * @since 0.5
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_id The numeric ID of a CiviCRM Contact.
	 * @return array|bool $result The Group-Contact data, or false on failure.
	 */
	public function group_contact_create_via_opt_in( $group_id, $contact_id ) {

		// Init return.
		$result = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $result;
		}

		// Get the Contact's Primary Email record.
		$primary_email = $this->civicrm->email->primary_email_get( $contact['id'] );

		// Skip if we can't find it.
		if ( false === $primary_email ) {
			return $result;
		}

		// Define params to send the Opt In Email.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'group_id'   => $group['group_id'],
			'contact_id' => $contact['id'],
			'email'      => $primary_email['email'],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'MailingEventSubscribe', 'create', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'     => __METHOD__,
				'message'    => __( 'Could not send Opt In Email.', 'civicrm-wp-profile-sync' ),
				'group_id'   => $group['group_id'],
				'contact_id' => $contact['id'],
				'params'     => $params,
				'result'     => $result,
				'backtrace'  => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return $result;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get "chunked" CiviCRM API Group Contact data for a given Group ID.
	 *
	 * This method is used internally by the "Manual Sync" admin page to get the
	 * details for Group members regardless of their status.
	 *
	 * @since 0.4
	 *
	 * @param integer $group_id The numeric ID of the CiviCRM Group.
	 * @param integer $offset The numeric offset for the query.
	 * @param integer $limit The numeric limit for the query.
	 * @return array $result The array of Group Contact data from the CiviCRM API.
	 */
	public function group_contacts_chunked_data_get( $group_id, $offset, $limit ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return [];
		}

		// Params to query Group membership.
		$params = [
			'version'  => 3,
			'group_id' => $group_id,
			'status'   => [
				'IS NOT NULL' => 1,
			],
			'options'  => [
				'limit'  => $limit,
				'offset' => $offset,
			],
		];

		// Call API.
		$result = civicrm_api( 'GroupContact', 'get', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'group_id'  => $group_id,
				'offset'    => $offset,
				'limit'     => $limit,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return $result;
		}

		// --<
		return $result;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept a CiviCRM group prior to it being deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function group_deleted_pre( $args ) {

		// Get terms that are synced to this Group ID.
		$terms_for_group = $this->acf_loader->post->tax->terms_get_by_group_id( $args['objectId'] );

		// Bail if there are none.
		if ( empty( $terms_for_group ) ) {
			return;
		}

		// Delete the term meta for each term.
		foreach ( $terms_for_group as $term ) {
			$this->acf_loader->post->tax->term_meta_delete( $term->term_id );
		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept when a CiviCRM Contact is added to a Group.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function group_contacts_created( $args ) {

		// Process terms for Group Contacts.
		$this->acf_loader->post->tax->terms_update_for_group_contacts( $args['objectId'], $args['objectRef'], 'add' );

	}

	/**
	 * Intercept when a CiviCRM Contact is deleted (or removed) from a Group.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function group_contacts_deleted( $args ) {

		// Process terms for Group Contacts.
		$this->acf_loader->post->tax->terms_update_for_group_contacts( $args['objectId'], $args['objectRef'], 'remove' );

	}

	/**
	 * Intercept when a CiviCRM Contact is re-added to a Group.
	 *
	 * The issue here is that CiviCRM fires 'civicrm_pre' with $op = 'delete' regardless
	 * of whether the Contact is being removed or deleted. If a Contact is later re-added
	 * to the Group, then 'create' !== $op, so we need to intercept $op = 'edit'.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function group_contacts_rejoined( $args ) {

		// Process terms for Group Contacts.
		$this->acf_loader->post->tax->terms_update_for_group_contacts( $args['objectId'], $args['objectRef'], 'add' );

	}

}
