<?php
/**
 * Mapper UFMatch Class.
 *
 * Handles User-Contact matching functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync UFMatch Class.
 *
 * A class that encapsulates User-Contact matching functionality.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_Mapper_UFMatch {

	/**
	 * Plugin object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * Mapper object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_Mapper
	 */
	public $mapper;

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->plugin = $parent->plugin;
		$this->mapper = $parent;

		// Initialise when plugin is loaded.
		add_action( 'cwps/mapper/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Listen for changes to WordPress User Email.
		add_action( 'cwps/civicrm/email/primary_updated', [ $this, 'entries_update' ], 10, 3 );

		// Listen for changes to CiviCRM Contact Primary Email.
		add_action( 'civicrm_wp_profile_sync_primary_email_synced', [ $this, 'entries_update' ], 10, 3 );

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get a CiviCRM Contact ID for a given WordPress User ID.
	 *
	 * By default, CiviCRM will return the matching Contact ID in the current
	 * Domain only. Pass a numeric Domain ID and only that Domain will be queried.
	 *
	 * Sometimes, however, we need to know if there is a matching Contact in
	 * *any* Domain - if so, pass a string such as "all" for "$domain_id" and
	 * all Domains will be searched for a matching Contact.
	 *
	 * @since 0.4
	 *
	 * @param integer        $user_id The numeric ID of the WordPress User.
	 * @param integer|string $domain_id The Domain ID (defaults to current Domain ID) or a string to search all Domains.
	 * @return integer|bool $contact_id The CiviCRM contact ID, or false on failure.
	 */
	public function contact_id_get_by_user_id( $user_id, $domain_id = '' ) {

		/*
		// Only do this once per Contact ID and Domain.
		static $pseudocache;
		if ( isset( $pseudocache[$domain_id][$user_id] ) ) {
			return $pseudocache[$domain_id][$user_id];
		}
		*/

		// Init return.
		$contact_id = false;

		// If CiviCRM is initialised.
		if ( $this->plugin->civicrm->is_initialised() ) {

			// Get UFMatch entry.
			$entry = $this->entry_get_by_user_id( $user_id, $domain_id );

			// If we get one.
			if ( false !== $entry ) {

				// Get the Contact ID if present.
				if ( ! empty( $entry->contact_id ) ) {
					$contact_id = (int) $entry->contact_id;
				}

				// Get the Contact ID from the returned array.
				if ( is_array( $entry ) ) {
					foreach ( $entry as $item ) {
						$contact_id = (int) $item->contact_id;
						break;
					}
				}

			}

		}

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$domain_id][$user_id] ) ) {
			$pseudocache[$domain_id][$user_id] = $contact_id;
		}
		*/

		// --<
		return $contact_id;

	}

	/**
	 * Get a CiviCRM Contact for a given WordPress User ID.
	 *
	 * @since 0.4
	 *
	 * @param integer        $user_id The numeric ID of the WordPress User.
	 * @param integer|string $domain_id The Domain ID (defaults to current Domain ID) or a string to search all Domains.
	 * @return array|bool $contact The CiviCRM Contact data, or false on failure.
	 */
	public function contact_get_by_user_id( $user_id, $domain_id = '' ) {

		// Get the contact ID.
		$contact_id = $this->contact_id_get_by_user_id( $user_id, $domain_id );

		// Bail if we didn't get one.
		if ( false === $contact_id ) {
			return false;
		}

		// Get Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );

		// --<
		return $contact;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get a WordPress User ID given a CiviCRM Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer        $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer|string $domain_id The Domain ID (defaults to current Domain ID) or a string to search all Domains.
	 * @return int|bool $user_id The numeric WordPress User ID, or false on failure.
	 */
	public function user_id_get_by_contact_id( $contact_id, $domain_id = '' ) {

		/*
		// Only do this once per Contact ID and Domain.
		static $pseudocache;
		if ( isset( $pseudocache[$domain_id][$contact_id] ) ) {
			return $pseudocache[$domain_id][$contact_id];
		}
		*/

		// Init return.
		$user_id = false;

		// Get UFMatch entry (or entries).
		$entry = $this->entry_get_by_contact_id( $contact_id, $domain_id );

		// If we get a UFMatch entry.
		if ( false !== $entry ) {

			// Get the User ID if a single UFMatch item is returned.
			if ( ! empty( $entry->uf_id ) ) {
				$user_id = (int) $entry->uf_id;
			}

			// Get the User ID from the returned array.
			if ( is_array( $entry ) ) {
				foreach ( $entry as $item ) {
					$user_id = (int) $item->uf_id;
					break;
				}
			}

		}

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$domain_id][$contact_id] ) ) {
			$pseudocache[$domain_id][$contact_id] = $user_id;
		}
		*/

		// --<
		return $user_id;

	}

	/**
	 * Get a WordPress User given a CiviCRM Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer        $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer|string $domain_id The Domain ID (defaults to current Domain ID) or a string to search all Domains.
	 * @return WP_User|bool $user The WordPress User object, or false on failure.
	 */
	public function user_get_by_contact_id( $contact_id, $domain_id = '' ) {

		// Get WordPress User ID.
		$user_id = $this->user_id_get_by_contact_id( $contact_id, $domain_id );

		// Bail if we didn't get one.
		if ( false === $user_id ) {
			return false;
		}

		// Get User object.
		$user = new WP_User( $user_id );

		// --<
		return $user;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create a link between a WordPress User and a CiviCRM Contact.
	 *
	 * This method optionally allows a Domain ID to be specified in the UFMatch
	 * data array. If none is specified, then CiviCRM will default to the current
	 * Domain ID.
	 *
	 * @since 0.4
	 *
	 * @param array $ufmatch The CiviCRM UFMatch data.
	 * @return array|bool $ufmatch_data The UFMatch data on success, or false on failure.
	 */
	public function entry_create( $ufmatch ) {

		// Init as failure.
		$ufmatch_data = false;

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $ufmatch_data;
		}

		// Build params to create a UFMatch record.
		$params = [
			'version' => 3,
			// 'debug' => 1,
		] + $ufmatch;

		// Maybe add Domain ID.
		if ( ! empty( $ufmatch['domain_id'] ) ) {
			$params['domain_id'] = $ufmatch['domain_id'];
		}

		/*
		 * Minimum array to create a UFMatch record:
		 *
		 * $params = [
		 *   'version' => 3,
		 *   'uf_id' => 123,
		 *   'uf_name' => "foo@bar.com",
		 *   'contact_id' => 456,
		 * ];
		 *
		 * Updates are triggered by:
		 *
		 * $params['id'] = 789;
		 *
		 * A Domain ID can be specified with:
		 *
		 * $params['domain_id'] = 10;
		 */

		// Create record via API.
		$result = civicrm_api( 'UFMatch', 'create', $params );

		// Log and bail on failure.
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
			return false;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $ufmatch_data;
		}

		// The result set should contain only one item.
		$ufmatch_data = array_pop( $result['values'] );

		// --<
		return $ufmatch_data;

	}

	/**
	 * Updates a CiviCRM UFMatch record with a given set of data.
	 *
	 * This is an alias of `self::entry_create()` except that we expect a
	 * UFMatch ID to have been set in the data array.
	 *
	 * @since 0.5.9
	 *
	 * @param array $ufmatch The CiviCRM UFMatch data.
	 * @return array|bool The array of UFMatch data, or false on failure.
	 */
	public function entry_update( $ufmatch ) {

		// Log and bail if there's no UFMatch ID.
		if ( empty( $ufmatch['id'] ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'A numeric ID must be present to update a UFMatch record.', 'civicrm-wp-profile-sync' ),
				'ufmatch'   => $ufmatch,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Pass through.
		return $this->entry_create( $ufmatch );

	}

	/**
	 * Delete the link between a WordPress User and a CiviCRM Contact.
	 *
	 * @since 0.4
	 *
	 * @param integer $ufmatch_id The numeric ID of the UFMatch entry.
	 * @return array|bool The UFMatch data on success, or false on failure.
	 */
	public function entry_delete( $ufmatch_id ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return false;
		}

		// Sanity checks.
		if ( ! is_numeric( $ufmatch_id ) ) {
			return false;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'id'      => $ufmatch_id,
		];

		// Create record via API.
		$result = civicrm_api( 'UFMatch', 'delete', $params );

		// Log and bail on failure.
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
			return false;
		}

		// --<
		return $result;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the UFMatch data for a given CiviCRM Contact ID.
	 *
	 * This method optionally allows a Domain ID to be specified:
	 *
	 * * If no Domain ID is passed, then we default to current Domain ID.
	 * * If a Domain ID is passed as a string, then we search all Domain IDs.
	 *
	 * @since 0.4
	 *
	 * @param integer        $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer|string $domain_id The CiviCRM Domain ID (defaults to current Domain ID).
	 * @return array|object|bool $entry The UFMatch data on success, or false on failure.
	 */
	public function entry_get_by_contact_id( $contact_id, $domain_id = '' ) {

		// Init return.
		$entry = false;

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $entry;
		}

		// Sanity checks.
		if ( ! is_numeric( $contact_id ) ) {
			return $entry;
		}

		// Construct params.
		$params = [
			'version'    => 3,
			'contact_id' => $contact_id,
		];

		// If no Domain ID is specified, default to current Domain ID.
		if ( empty( $domain_id ) ) {
			$params['domain_id'] = CRM_Core_Config::domainID();
		}

		// Maybe add Domain ID if passed as an integer.
		if ( ! empty( $domain_id ) && is_numeric( $domain_id ) ) {
			$params['domain_id'] = $domain_id;
		}

		/**
		 * Filters the params used to query the UFMatch data.
		 *
		 * This filter may be used, for example, to modify the Domain ID when a
		 * Contact is edited by a CiviCRM Admin and that Contact does NOT have a
		 * UFMatch record in the current Domain.
		 *
		 * @since 0.5.9
		 *
		 * @param array $params The params passed to the CiviCRM API.
		 */
		$params = apply_filters( 'cwps/mapper/ufmatch/entry_get_by_contact_id', $params );

		// Get all UFMatch records via API.
		$result = civicrm_api( 'UFMatch', 'get', $params );

		// Log and bail on failure.
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
			return $entry;
		}

		// Bail if there's no entry data.
		if ( empty( $result['values'] ) ) {
			return $entry;
		}

		// Assign the entry data if there's only one.
		if ( count( $result['values'] ) === 1 ) {
			$entry = (object) array_pop( $result['values'] );
		}

		// Assign entries to an array if there's more than one.
		if ( count( $result['values'] ) > 1 ) {
			$entry = [];
			foreach ( $result['values'] as $item ) {
				$entry[] = (object) $item;
			}
		}

		// --<
		return $entry;

	}

	/**
	 * Get the UFMatch data for a given WordPress User ID.
	 *
	 * This method optionally allows a Domain ID to be specified:
	 *
	 * * If no Domain ID is passed, then we default to current Domain ID.
	 * * If a Domain ID is passed as a string, then we search all Domain IDs.
	 *
	 * @since 0.4
	 *
	 * @param integer        $user_id The numeric ID of the WordPress User.
	 * @param integer|string $domain_id The CiviCRM Domain ID (defaults to current Domain ID).
	 * @return array|object|bool $entry The UFMatch data on success, or false on failure.
	 */
	public function entry_get_by_user_id( $user_id, $domain_id = '' ) {

		// Init return.
		$entry = false;

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $entry;
		}

		// Sanity checks.
		if ( ! is_numeric( $user_id ) ) {
			return $entry;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'uf_id'   => $user_id,
		];

		// If no Domain ID is specified, default to current Domain ID.
		if ( empty( $domain_id ) ) {
			$params['domain_id'] = CRM_Core_Config::domainID();
		}

		// Maybe add Domain ID if passed as an integer.
		if ( ! empty( $domain_id ) && is_numeric( $domain_id ) ) {
			$params['domain_id'] = $domain_id;
		}

		/**
		 * Filters the params used to query the UFMatch data.
		 *
		 * @since 0.5.9
		 *
		 * @param array $params The params passed to the CiviCRM API.
		 */
		$params = apply_filters( 'cwps/mapper/ufmatch/entry_get_by_user_id', $params );

		// Get all UFMatch records via API.
		$result = civicrm_api( 'UFMatch', 'get', $params );

		// Log and bail on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'user_id'   => $user_id,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return $entry;
		}

		// Bail if there's no entry data.
		if ( empty( $result['values'] ) ) {
			return $entry;
		}

		// Assign the entry data if there's only one.
		if ( count( $result['values'] ) === 1 ) {
			$entry = (object) array_pop( $result['values'] );
		}

		// Assign entries to an array if there's more than one.
		if ( count( $result['values'] ) > 1 ) {
			$entry = [];
			foreach ( $result['values'] as $item ) {
				$entry[] = (object) $item;
			}
		}

		// --<
		return $entry;

	}

	/**
	 * Get the UFMatch data for a given WordPress User email.
	 *
	 * This method optionally allows a Domain ID to be specified.
	 * If no Domain ID is passed, then we default to current Domain ID.
	 * If a Domain ID is passed as a string, then we search all Domain IDs.
	 *
	 * @since 0.4
	 *
	 * @param string         $email The WordPress User's email address.
	 * @param integer|string $domain_id The CiviCRM Domain ID (defaults to current Domain ID).
	 * @return array|object|bool $entry The UFMatch data on success, or false on failure.
	 */
	public function entry_get_by_user_email( $email, $domain_id = '' ) {

		// Init return.
		$entry = false;

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $entry;
		}

		// Sanity checks.
		if ( ! is_email( $email ) ) {
			return $entry;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'uf_name' => $email,
		];

		// If no Domain ID is specified, default to current Domain ID.
		if ( empty( $domain_id ) ) {
			$params['domain_id'] = CRM_Core_Config::domainID();
		}

		// Maybe add Domain ID if passed as an integer.
		if ( ! empty( $domain_id ) && is_numeric( $domain_id ) ) {
			$params['domain_id'] = $domain_id;
		}

		// Get all UFMatch records via API.
		$result = civicrm_api( 'UFMatch', 'get', $params );

		// Log and bail on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'email'     => $email,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return $entry;
		}

		// Bail if there's no entry data.
		if ( empty( $result['values'] ) ) {
			return $entry;
		}

		// Assign the entry data if there's only one.
		if ( count( $result['values'] ) === 1 ) {
			$entry = (object) array_pop( $result['values'] );
		}

		// Assign entries to an array if there's more than one.
		if ( count( $result['values'] ) > 1 ) {
			$entry = [];
			foreach ( $result['values'] as $item ) {
				$entry[] = (object) $item;
			}
		}

		// --<
		return $entry;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Updates the UFMatch entry in the current Domain for a CiviCRM Contact when
	 * their Primary Email changes.
	 *
	 * We only need to update the UFMatch entry in the current Domain because
	 * the CiviCRM Multisite Extension updates the rest when CiviCRM is set up to
	 * use multiple Domains.
	 *
	 * @see https://github.com/eileenmcnaughton/org.civicrm.multisite/blob/master/multisite.php#L143-L167
	 *
	 * @since 0.5.9
	 *
	 * @param integer $user_id The ID of the WordPress User.
	 * @param integer $email_id The ID of the CiviCRM Email.
	 * @param object  $email The CiviCRM Email object.
	 */
	public function entries_update( $user_id, $email_id, $email ) {

		// Bail if CiviCRM is not active.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return;
		}

		// Never overwrite with an empty Email address.
		if ( empty( $email->email ) ) {
			return;
		}

		// The Email must be associated with a Contact.
		if ( empty( $email->contact_id ) ) {
			return;
		}

		// Must be the Primary Email.
		if ( empty( $email->is_primary ) || 1 !== (int) $email->is_primary ) {
			return;
		}

		// Get the UFMatch entry for this Contact in the current Domain.
		$entry = $this->entry_get_by_contact_id( $email->contact_id );

		// Bail if there's no UFMatch entry.
		if ( false === $entry ) {
			return;
		}

		// When we get an array back, there are multiple entries.
		if ( is_array( $entry ) ) {
			// In this context, this is an error.
			return;
		}

		// When we get an object back, there's only one entry.
		if ( ! is_object( $entry ) ) {
			return;
		}

		// Build params.
		$params = [
			'id'         => $entry->id,
			'uf_id'      => $entry->uf_id,
			'uf_name'    => $email->email,
			'contact_id' => $entry->contact_id,
			'domain_id'  => $entry->domain_id,
		];

		// Update the UFMatch record.
		$ufmatch = $this->entry_update( $params );

	}

}
