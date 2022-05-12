<?php
/**
 * CiviCRM Contact Class.
 *
 * Handles CiviCRM Contact functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Contact Class.
 *
 * A class that encapsulates CiviCRM Contact functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Contact {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_hooks The Mapper hooks registered flag.
	 */
	public $mapper_hooks = false;

	/**
	 * Entity identifier.
	 *
	 * This identifier is unique to this "top level" Entity.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $identifier The unique identifier for this "top level" Entity.
	 */
	public $identifier = 'contact';

	/**
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Contact Fields from Custom Fields.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $contact_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $contact_field_prefix = 'caicontact_';



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

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

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Listen for events from Manual Sync that require Contact updates.
		add_action( 'cwps/acf/admin/post-to-contact/sync', [ $this, 'post_sync' ], 10 );
		add_action( 'cwps/acf/admin/post-to-contact/acf_fields/sync', [ $this, 'acf_fields_sync' ], 10 );

		// Listen for queries from our Field Group class.
		add_filter( 'cwps/acf/query_field_group_mapped', [ $this, 'query_field_group_mapped' ], 10, 2 );

		// Listen for queries from our Custom Field class.
		add_filter( 'cwps/acf/query_custom_fields', [ $this, 'query_custom_fields' ], 10, 2 );

		// Listen for queries from our Custom Field class.
		add_filter( 'cwps/acf/query_post_id', [ $this, 'query_post_id' ], 10, 2 );

		// Listen for queries from the Attachment class.
		add_filter( 'cwps/acf/query_entity_table', [ $this, 'query_entity_table' ], 10, 2 );

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/acf/field/query_setting_choices', [ $this, 'query_setting_choices' ], 10, 3 );

		// Listen for queries from the ACF Bypass class.
		//add_filter( 'cwps/acf/bypass/query_settings_field', [ $this, 'query_bypass_settings_field' ], 20, 4 );
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

		// Listen for queries from the ACF Bypass Location Rule class.
		add_filter( 'cwps/acf/bypass/location/query_entities', [ $this, 'query_bypass_entities' ], 10, 2 );

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
			return;
		}

		// Listen for events from our Mapper that require Contact updates.
		add_action( 'cwps/acf/mapper/post/saved', [ $this, 'post_saved' ], 10 );
		add_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ], 10 );

		// Declare registered.
		$this->mapper_hooks = true;

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_hooks === false ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/post/saved', [ $this, 'post_saved' ], 10 );
		remove_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact when a WordPress Post is synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function post_sync( $args ) {

		// Pass on.
		$this->post_saved( $args );

	}



	/**
	 * Update a CiviCRM Contact when a WordPress Post has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function post_saved( $args ) {

		// Bail if this Post should not be synced now.
		$this->do_not_sync = false;
		$post = $this->acf_loader->post->should_be_synced( $args['post'] );
		if ( false === $post ) {
			$this->do_not_sync = true;
			return;
		}

		// Bail if this Post Type is not mapped.
		if ( ! $this->acf_loader->post_type->is_mapped_to_contact_type( $post->post_type ) ) {
			$this->do_not_sync = true;
			return;
		}

		// Get the Contact ID.
		$contact_id = $this->acf_loader->post->contact_id_get( $post->ID );

		// Does this Post have a Contact ID?
		if ( $contact_id === false ) {

			// No - create a Contact.
			$contact = $this->create_from_post( $post );

			// Store Contact ID if successful.
			if ( $contact !== false ) {
				$this->acf_loader->post->contact_id_set( $post->ID, $contact['id'] );
			}

		} else {

			// Yes - update the Contact.
			$contact = $this->update_from_post( $post, $contact_id );

		}

		// Add our data to the params.
		$args['contact'] = $contact;
		$args['contact_id'] = $contact['id'];

		/**
		 * Broadcast that a Contact has been updated.
		 *
		 * Used internally by:
		 *
		 * * Groups
		 * * Post Taxonomies
		 *
		 * @since 0.4
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/acf/contact/post/saved', $args );

	}



	/**
	 * Update a CiviCRM Contact when the ACF Fields on a WordPress Post are synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function acf_fields_sync( $args ) {

		// Pass on.
		$this->acf_fields_saved( $args );

	}



	/**
	 * Update a CiviCRM Contact when the ACF Fields on a WordPress Post have been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function acf_fields_saved( $args ) {

		// Bail early if the ACF Fields are not attached to a Post Type.
		if ( ! isset( $this->do_not_sync ) ) {
			return;
		}

		// Bail early if this Post Type shouldn't be synced.
		// @see self::post_saved()
		if ( $this->do_not_sync === true ) {
			return;
		}

		// Bail if it's not a Post.
		$entity = $this->acf_loader->acf->field->entity_type_get( $args['post_id'] );
		if ( $entity !== 'post' ) {
			return;
		}

		// We need the Post itself.
		$post = get_post( $args['post_id'] );

		// Bail if this is a revision.
		if ( $post->post_type == 'revision' ) {
			return;
		}

		// Does this Post have a Contact ID?
		$contact_id = $this->acf_loader->post->contact_id_get( $post->ID );

		// Bail if there isn't one.
		if ( $contact_id === false ) {
			return;
		}

		// Get originating Entity.
		$originating_entity = $this->acf_loader->mapper->entity_get();

		/*
		 * Get existing Field values.
		 *
		 * These are actually the *new* values because we are hooking in *after*
		 * the Fields have been saved.
		 */
		$fields = get_fields( $post->ID );

		// TODO: Decide if we should get the ACF Field data without formatting.
		// This also applies to any calls to get_field_object().
		//$fields = get_fields( $post->ID, false );

		// Get submitted values. (No need for this - see hook priority)
		//$submitted_values = acf_maybe_get_POST( 'acf' );

		// Update the Contact with this data.
		$contact = $this->update_from_fields( $contact_id, $fields, $post->ID );

		// Add our data to the params.
		$args['contact_id'] = $contact_id;
		$args['contact'] = $contact;
		$args['post'] = $post;
		$args['fields'] = $fields;

		/**
		 * Broadcast that a Contact has been updated when ACF Fields were saved.
		 *
		 * Used internally by:
		 *
		 * * Contact Fields
		 * * Relationships
		 * * Addresses
		 * * Websites
		 * * WordPress Posts - to maintain sync with the Contact "Display Name"
		 *
		 * @since 0.4
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/acf/contact/acf_fields_saved', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Getter method for the "Handled Fields" array.
	 *
	 * @since 0.4
	 *
	 * @return array $fields_handled The array of Contact Fields which must be handled separately.
	 */
	public function fields_handled_get() {

		/**
		 * Filter the "Handled Fields" array.
		 *
		 * Classes in this plugin add the Fields they handle via this filter.
		 *
		 * @since 0.4
		 *
		 * @param array $fields_handled The existing array of Fields which must be handled separately.
		 */
		$fields_handled = apply_filters( 'cwps/acf/civicrm/fields_handled', [] );

		// --<
		return $fields_handled;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the Contact ID for a given checksum.
	 *
	 * @since 0.5
	 *
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false on failure.
	 */
	public function get_id_by_checksum() {

		// Fail by default.
		$contact_id = false;

		// Bail if there is no checksum.
		if ( empty( $_GET['cs'] ) ) {
			return $contact_id;
		}

		// The checksum must be accompanied by a Contact ID.
		if ( empty( $_GET['cid'] ) || ! is_numeric( $_GET['cid'] ) ) {
			return $contact_id;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_id;
		}

		// Bail if no "Edit Contact" permission or not a valid checksum.
		$cid = (int) trim( $_GET['cid'] );
		$checksum = trim( $_GET['cs'] );
		$allowed = CRM_Contact_BAO_Contact_Permission::allow( $cid, CRM_Core_Permission::EDIT );
		$valid = CRM_Contact_BAO_Contact_Utils::validChecksum( $cid, $checksum );
		if ( ! $allowed && ! $valid ) {
			return $contact_id;
		}

		// Okay, looks good.
		$contact_id = $cid;

		// --<
		return $contact_id;

	}



	/**
	 * Gets the Contact data for the logged-in User.
	 *
	 * @since 0.5
	 *
	 * @return integer|bool $contact_id The numeric ID of the Contact, or false on failure.
	 */
	public function get_for_current_user() {

		// Only do this once.
		static $contact_id;
		if ( isset( $contact_id ) ) {
			return $contact_id;
		}

		// Get the logged-in Contact.
		$user = wp_get_current_user();
		$contact_id = $this->plugin->mapper->ufmatch->contact_id_get_by_user_id( $user->ID );

		// --<
		return $contact_id;

	}



	/**
	 * Get the CiviCRM Contact data for a given search string.
	 *
	 * @since 0.4
	 *
	 * @param string $search The search string to query.
	 * @param array $args The array of search params to query.
	 * @return array|bool $contact_data An array of Contact data, or false on failure.
	 */
	public function get_by_search_string( $search, $args = [] ) {

		// Init return.
		$contact_data = false;

		// Bail if we have no Contact ID.
		if ( empty( $search ) ) {
			return $contact_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_data;
		}

		// Set some defaults.
		if ( empty( $args['action'] ) ) {
			$args['action'] = 'lookup';
		}
		if ( empty( $args['rowCount'] ) ) {
			$args['rowCount'] = 10;
		}

		// Define params to get queried Contacts.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'sort_name' => $search,
			'sort' => 'sort_name',
			'action' => $args['action'],
			'rowCount' => $args['rowCount'],
		];

		// Define the returned values.
		$params['return'] = [ 'sort_name' ];
		if ( ! empty( $args['return'] ) ) {
			$params['return'] = array_merge( $params['return'], $args['return'] );
		}

		// Maybe narrow the search to a Contact Type.
		if ( ! empty( $args['contact_type'] ) ) {
			$params['contact_type'] = $args['contact_type'];
		}

		// Maybe narrow the search to a Contact Sub-type.
		if ( ! empty( $args['contact_type'] ) && ! empty( $args['contact_subtype'] ) ) {
			$params['contact_sub_type'] = $args['contact_sub_type'];
		}

		// Maybe narrow the search to Group Membership.
		if ( ! empty( $args['groups'] ) && is_array( $args['groups'] ) ) {
			$params['group'] = $args['groups'];
		}

		// Maybe define an offset.
		if ( ! empty( $args['offset'] ) ) {
			$params['options']['offset'] = $args['offset'];
		}

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_data;
		}

		// Format the return as per "getlist".
		foreach ( $result['values'] as $value ) {
			$data = [
				'id' => $value['id'],
				'label' => $value['sort_name'],
			];
			foreach ( $args['return'] as $return ) {
				if ( $return !== 'sort_name' && ! empty( $value[ $return ] ) ) {
					$data['description'][] = $value[ $return ];
				}
			}
			$contact_data[] = $data;
		}

		// --<
		return $contact_data;

	}



	/**
	 * Get the CiviCRM Contacts with a given Image URL.
	 *
	 * In practice there is only likely to be one Contact returned, but since it
	 * is possible for more than one to share the same Image URL, an array of
	 * data is always returned (or boolean if there's a failure of some sort).
	 *
	 * @since 0.4
	 *
	 * @param string $image_url The Image URL of the CiviCRM Contact.
	 * @return array|bool $contacts An array of Contacts, or false on failure.
	 */
	public function get_by_image( $image_url ) {

		// Init return.
		$contacts = false;

		// Bail if we have no Image URL.
		if ( empty( $image_url ) ) {
			return $contacts;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contacts;
		}

		// Define params to get queried Contact.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'image_URL' => $image_url,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $contacts;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contacts;
		}

		// The result set is what we're after.
		$contacts = $result['values'];

		// --<
		return $contacts;

	}



	/**
	 * Gets a suggested CiviCRM Contact ID via the "Unsupervised" Dedupe Rule.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of CiviCRM Contact data.
	 * @param string $contact_type The Contact Type.
	 * @return integer|bool $contact_id The suggested Contact ID, or false on failure.
	 */
	public function get_by_dedupe_unsupervised( $contact, $contact_type = 'Individual' ) {

		// Bail if we have no Contact data.
		if ( empty( $contact ) ) {
			return false;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Get the Dedupe params.
		$dedupe_params = CRM_Dedupe_Finder::formatParams( $contact, $contact_type );
		$dedupe_params['check_permission'] = false;

		// Use Dedupe Rules to find possible Contact IDs.
		$contact_ids = CRM_Dedupe_Finder::dupesByParams( $dedupe_params, $contact_type, 'Unsupervised' );

		// Return the suggested Contact ID if present.
		$contact_id = 0;
		if ( ! empty( $contact_ids ) ) {
			$contact_ids = array_reverse( $contact_ids );
			$contact_id = array_pop( $contact_ids );
		}

		return $contact_id;

	}



	/**
	 * Gets a suggested CiviCRM Contact ID using a specified Dedupe Rule.
	 *
	 * @since 0.5
	 *
	 * @param array $contact The array of Contact data.
	 * @param string $contact_type The Contact Type.
	 * @param integer $dedupe_rule_id The Dedupe Rule ID.
	 * @return integer|bool $contact_id The numeric Contact ID, or false on failure.
	 */
	public function get_by_dedupe_rule( $contact, $contact_type = 'Individual', $dedupe_rule_id ) {

		// Bail if we have no Contact data.
		if ( empty( $contact ) ) {
			return false;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Build the Dedupe params.
		$dedupe_params = CRM_Dedupe_Finder::formatParams( $contact, $contact_type );
		$dedupe_params['check_permission'] = false;

		// Check for duplicates.
		$contact_ids = CRM_Dedupe_Finder::dupesByParams( $dedupe_params, $contact_type, null, [], $dedupe_rule_id );

		// Return the suggested Contact ID if present.
		$contact_id = 0;
		if ( ! empty( $contact_ids ) ) {
			$contact_ids = array_reverse( $contact_ids );
			$contact_id = array_pop( $contact_ids );
		}

		// --<
		return $contact_id;

	}



	/**
	 * Get Dedupe Rules.
	 *
	 * By default, all Dedupe Rules for all the top-level Contact Types will be
	 * returned, but you can specify a Contact Type if you want to limit what is
	 * returned.
	 *
	 * @since 0.5
	 *
	 * @param string $contact_type An optional Contact Type to filter rules by.
	 * @return array $dedupe_rules The Dedupe Rules, or empty on failure.
	 */
	public function dedupe_rules_get( $contact_type = '' ) {

		// Bail if we can't initialise CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return [];
		}

		// Add the Dedupe rules for all Contact Types.
		$dedupe_rules = [];
		$types = [ 'Organization', 'Household', 'Individual' ];
		foreach ( $types as $type ) {
			if ( empty( $contact_type ) ) {
				$dedupe_rules[ $type ] = CRM_Dedupe_BAO_RuleGroup::getByType( $type );
			} elseif ( $contact_type == $type ) {
				$dedupe_rules = CRM_Dedupe_BAO_RuleGroup::getByType( $type );
				break;
			}
		}

		// --<
		return $dedupe_rules;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get "chunked" CiviCRM API Contact data for a given Contact Type ID.
	 *
	 * This method is used internally by the "Manual Sync" admin page.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type_id The numeric ID of the CiviCRM Contact Type.
	 * @param integer $offset The numeric offset for the query.
	 * @param integer $limit The numeric limit for the query.
	 * @return array $result The array of Contact data from the CiviCRM API.
	 */
	public function contacts_chunked_data_get( $contact_type_id, $offset, $limit ) {

		// Get the hierarchy for the Contact Type ID.
		$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $contact_type_id, 'id' );

		// Bail if we didn't get any.
		if ( $hierarchy === false ) {
			return 0;
		}

		// Params to query Contacts.
		$params = [
			'version' => 3,
			'contact_type' => $hierarchy['type'],
			'contact_sub_type' => $hierarchy['subtype'],
			'options' => [
				'limit' => $limit,
				'offset' => $offset,
			],
		];

		// Call API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_type_id' => $contact_type_id,
				'offset' => $offset,
				'limit' => $limit,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $result;
		}

		// --<
		return $result;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check whether any of a Contact's Contact Types is mapped to a Post Type.
	 *
	 * The Mapper makes use of the boolean return to bail early. Other classes
	 * use the returned array of Post Types to loop through the mapped Posts.
	 *
	 * @see CiviCRM_Profile_Sync_ACF_Mapper::contact_pre_create()
	 * @see CiviCRM_Profile_Sync_ACF_Mapper::contact_pre_edit()
	 *
	 * @since 0.4
	 *
	 * @param array|obj $contact The Contact data.
	 * @param string $create_post Create a mapped Post if missing. Either 'create' or 'skip'.
	 * @return array|bool $is_mapped An array of Post Types if the Contact is mapped, false otherwise.
	 */
	public function is_mapped( $contact, $create_post = 'skip' ) {

		// Init return.
		$is_mapped = [];

		// Maybe cast Contact data as array.
		if ( is_object( $contact ) ) {
			$contact = (array) $contact;
		}

		// Get the Contact Type hierarchy.
		$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_for_contact( $contact );

		// Get separated array of Contact Types.
		$contact_types = $this->plugin->civicrm->contact_type->hierarchy_separate( $hierarchy );

		// Check each Contact Type in turn.
		foreach ( $contact_types as $contact_type ) {

			// Get the Post Type mapped to this Contact Type.
			$post_type = $this->civicrm->contact_type->is_mapped_to_post_type( $contact_type );

			// Skip if this Contact Type is not mapped.
			if ( $post_type === false ) {
				continue;
			}

			// Add mapped Post Type.
			$is_mapped[] = $post_type;

			// Let's check if the Contact has all the Posts it should have.
			$contact_id = false;
			if ( ! empty( $contact['contact_id'] ) ) {
				$contact_id = $contact['contact_id'];
			}
			if ( ! empty( $contact['id'] ) ) {
				$contact_id = $contact['id'];
			}

			// If there's no Contact ID, carry on.
			if ( $contact_id === false ) {
				continue;
			}

			// Get the associated Post IDs.
			$post_ids = $this->acf_loader->post->get_by_contact_id( $contact_id, $post_type );

			// Create the Post if it's missing.
			if ( $post_ids === false && $create_post === 'create' ) {

				// Prevent recursion and the resulting unexpected Post creation.
				if ( doing_action( 'cwps/acf/post/contact_sync_to_post' ) ) {
					continue;
				}

				// Get full Contact data.
				$contact_data = $this->get_by_id( $contact_id );

				// TODO: Do we really need to remove CiviCRM hooks?

				// Remove all callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_remove();
				$this->acf_loader->mapper->hooks_civicrm_remove();

				// Let's make an array of params.
				$args = [
					'op' => 'sync',
					'objectName' => $contact_data['contact_type'],
					'objectId' => $contact_data['contact_id'],
					'objectRef' => (object) $contact_data,
				];

				// Sync this Contact to the Post Type.
				$this->acf_loader->post->contact_sync_to_post( $args, $post_type );

				// Reinstate all callbacks.
				$this->acf_loader->mapper->hooks_wordpress_add();
				$this->acf_loader->mapper->hooks_civicrm_add();

			}

		}

		// Cast as boolean if there's no mapping.
		if ( empty( $is_mapped ) ) {
			$is_mapped = false;
		}

		// --<
		return $is_mapped;

	}



	/**
	 * Check if a Contact is mapped to a Post of a particular Post Type.
	 *
	 * @since 0.4
	 *
	 * @param array|obj $contact The Contact data.
	 * @param string $post_type The WordPress Post Type.
	 * @return integer|bool $is_mapped The ID of the WordPress Post if the Contact is mapped, false otherwise.
	 */
	public function is_mapped_to_post( $contact, $post_type = 'any' ) {

		// TODO: Query Posts with Post meta instead? Or pseudo-cache?

		// Assume not.
		$is_mapped = false;

		// Maybe cast Contact data as array.
		if ( is_object( $contact ) ) {
			$contact = (array) $contact;
		}

		// Bail if none of this Contact's Contact Types is mapped.
		$post_types = $this->is_mapped( $contact );
		if ( $post_types === false ) {
			return $is_mapped;
		}

		// "hook_civicrm_pre" sends $contact['contact_id']
		if ( isset( $contact['contact_id'] ) ) {
			$contact_id = $contact['contact_id'];
		}

		// "hook_civicrm_post" sends $contact['id']
		if ( isset( $contact['id'] ) ) {
			$contact_id = $contact['id'];
		}

		// Bail if no Contact ID is found.
		if ( empty( $contact_id ) ) {
			return $is_mapped;
		}

		// Find the Post ID of this Post Type that this Contact is synced with.
		$post_ids = $this->acf_loader->post->get_by_contact_id( $contact_id, $post_type );

		// Bail if no Post ID is found.
		if ( empty( $post_ids ) ) {
			return $is_mapped;
		}

		// There should be only one Post ID per Post Type.
		$is_mapped = array_pop( $post_ids );

		// --<
		return $is_mapped;

	}



	// -------------------------------------------------------------------------



	/**
	 * Prepare the required CiviCRM Contact data from a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param WP_Post $post The WordPress Post object.
	 * @param integer $contact_id The numeric ID of the Contact (or null if new).
	 * @return array $contact_data The CiviCRM Contact data.
	 */
	public function prepare_from_post( $post, $contact_id = null ) {

		// Init required data.
		$contact_data = [];

		// Maybe add the Contact ID.
		if ( ! empty( $contact_id ) ) {
			$contact_data['id'] = $contact_id;
		}

		// Always assign Post Title to Contact "display_name".
		if ( empty( $post->post_title ) ) {
			$contact_data['display_name'] = __( 'Name not set', 'civicrm-wp-profile-sync' );
		} else {
			$contact_data['display_name'] = $post->post_title;
		}

		// Retrieve Contact Type hierarchy for this Post Type.
		$contact_types = $this->civicrm->contact_type->hierarchy_get_for_post_type( $post->post_type );

		// If this is a new Contact.
		if ( empty( $contact_id ) ) {

			// Assign new Contact Type data.
			$contact_data['contact_type'] = $contact_types['type'];
			$contact_data['contact_sub_type'] = $contact_types['subtype'];

		} else {

			// Get the full Contact data.
			$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );

			// Get current Contact Type hierarchy for the Contact.
			$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_for_contact( $contact );

			// Contact Type is always the same.
			$contact_data['contact_type'] = $hierarchy['type'];

			// Merge existing and new Contact Subtype data.
			$subtypes = array_unique( array_merge( $hierarchy['subtype'], $contact_types['subtype'] ) );
			$contact_data['contact_sub_type'] = $subtypes;

		}

		// Flatten the hierarchy.
		$flattened = $this->plugin->civicrm->contact_type->hierarchy_separate( $contact_types );

		// Are all Subtypes empty?
		$empty_subtype = true;
		foreach ( $flattened as $flat ) {
			if ( ! empty( $flat['subtype'] ) ) {
				$empty_subtype = false;
				break;
			}
		}

		// Remove Subtype when empty.
		if ( $empty_subtype === true ) {
			unset( $contact_data['contact_sub_type'] );
		}

		// Set mandatory Fields for Contacts depending on their Contact Type.
		if ( $contact_data['contact_type'] == 'Organization' ) {
			$contact_data['organization_name'] = $contact_data['display_name'];
		}
		if ( $contact_data['contact_type'] == 'Household' ) {
			$contact_data['household_name'] = $contact_data['display_name'];
		}

		// Set a status for the Contact depending on the Post status.
		if ( $post->post_status == 'trash' ) {
			$contact_data['is_deleted'] = 1;
		} else {
			$contact_data['is_deleted'] = 0;
		}

		/**
		 * Filter the way that names are built.
		 *
		 * Syncing names is complicated!
		 *
		 * CiviCRM builds the "display_name" from the "first_name" and "last_name"
		 * params sent to the API when a Contact is *updated*. It does not do this
		 * when it *creates* a Contact.
		 *
		 * The question, therefore, is how to manage the sync between the WordPress
		 * "post_title" and the CiviCRM Contact "display_name"...
		 *
		 * When there are mapped ACF Fields for "first_name" and "last_name", this
		 * becomes less of a problem, though it's not clear how to populate these
		 * Fields for a Contact with just one name that is not a Contact Type which
		 * extends "Organisation" or "Household" (a dog, for example).
		 *
		 * Let's take the example of a dog called "Rover":
		 *
		 * * The WordPress "post_title" would be "Rover".
		 * * The Contact "display_name" should be "Rover".
		 * * The CiviCRM API requires the "first_name" and "last_name" Fields.
		 * * The CiviCRM API does not update the "display_name" directly.
		 * * There are no "first_name" and "last_name" ACF Fields.
		 *
		 * In this situation, there is no obvious way of configuring this in either
		 * the WordPress or CiviCRM UIs. WordPress has no UI for Post Types (except
		 * via a plugin - which means supporting plugins that offer a UI) and the
		 * CiviCRM UI for Contact Types would be very difficult to adapt such that
		 * these options are configurable.
		 *
		 * At present, I can't think of other situations where there's a mismatch
		 * between these Fields, so perhaps a plugin Settings Page might be needed
		 * with a checkbox per Post Type selecting between:
		 *
		 * * Sync where the Contact has just one name
		 * * Sync where the Contact has the common "first_name" and "last_name"
		 *
		 * @since 0.4
		 *
		 * @param array $contact_data The existing CiviCRM Contact data.
		 * @param WP_Post $post The WordPress Post.
		 */
		$contact_data = apply_filters( 'cwps/acf/civicrm/contact/post/data', $contact_data, $post );

		// --<
		return $contact_data;

	}



	/**
	 * Create a CiviCRM Contact from a WordPress Post.
	 *
	 * This can be merged with `self::update_from_post()` in due course.
	 *
	 * @since 0.4
	 *
	 * @param WP_Post $post The WordPress Post object.
	 * @return array|bool $contact The CiviCRM Contact data, or false on failure.
	 */
	public function create_from_post( $post ) {

		// Build required data.
		$contact_data = $this->prepare_from_post( $post );

		/*
		 * Should we save the Post ID in the "External ID" Field?
		 *
		 * The problem with this is that people actually use the Field!
		 *
		 * Reverse look-ups (i.e. from CiviCRM) can be done by querying Posts
		 * with a meta query.
		 *
		 * @see CiviCRM_Profile_Sync_ACF_Post::get_by_contact_id().
		 */

		// Create the Contact.
		$contact = $this->plugin->civicrm->contact->create( $contact_data );

		// --<
		return $contact;

	}



	/**
	 * Sync a WordPress Post with a CiviCRM Contact.
	 *
	 * When we update the Contact, we always sync the WordPress Post's title
	 * with the CiviCRM Contact's "display name".
	 *
	 * Depending on the setting for the Contact Type, we also optionally sync
	 * the "post_content" with a Custom Field.
	 *
	 * @since 0.4
	 *
	 * @param WP_Post $post The WordPress Post object.
	 * @param integer $existing_id The numeric ID of the Contact.
	 * @return array|bool $contact The CiviCRM Contact data, or false on failure.
	 */
	public function update_from_post( $post, $existing_id ) {

		// Build required data.
		$contact_data = $this->prepare_from_post( $post, $existing_id );

		// Update the Contact.
		$contact = $this->plugin->civicrm->contact->update( $contact_data );

		// --<
		return $contact;

	}



	// -------------------------------------------------------------------------



	/**
	 * Prepare the required CiviCRM Contact data from a set of ACF Fields.
	 *
	 * This method combines all Contact Fields that the CiviCRM API accepts as
	 * params for ( 'Contact', 'create' ) along with the linked Custom Fields.
	 *
	 * The CiviCRM API will update Custom Fields as long as they are passed to
	 * ( 'Contact', 'create' ) in the correct format. This is of the form:
	 * 'custom_N' where N is the ID of the Custom Field.
	 *
	 * Some Fields have to be handled elsewhere (e.g. 'email') because they are
	 * not included in these API calls.
	 *
	 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Base
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $fields The ACF Field data.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array $contact_data The CiviCRM Contact data.
	 */
	public function prepare_from_fields( $contact_id, $fields, $post_id = null ) {

		// Init data for Fields.
		$contact_data = [];

		// Bail if we have no Field data to save.
		if ( empty( $fields ) ) {
			return $contact_data;
		}

		// Get all Fields that are handled separately.
		$fields_handled = $this->fields_handled_get();

		// Loop through the Field data.
		foreach ( $fields as $selector => $value ) {

			// Get the Field settings.
			$settings = get_field_object( $selector, $post_id );

			// Get the CiviCRM Custom Field and Contact Field.
			$custom_field_id = $this->civicrm->custom_field->custom_field_id_get( $settings );
			$contact_field_name = $this->contact_field_name_get( $settings );

			// Do we have a synced Custom Field or Contact Field?
			if ( ! empty( $custom_field_id ) || ! empty( $contact_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . $custom_field_id;

				} else {

					// The Contact Field code is the setting.
					$code = $contact_field_name;

					// Skip if it's a Field that requires special handling.
					if ( in_array( $code, $fields_handled ) ) {
						continue;
					}

				}

				// Build args for value conversion.
				$args = [
					'identifier' => $this->identifier,
					'entity_id' => $contact_id,
					'custom_field_id' => $custom_field_id,
					'field_name' => $contact_field_name,
					'selector' => $selector,
					'post_id' => $post_id,
				];

				// Parse value by Field Type.
				$value = $this->acf_loader->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings, $args );

				// Add it to the Field data.
				$contact_data[ $code ] = $value;

			}

		}

		// --<
		return $contact_data;

	}



	/**
	 * Update a CiviCRM Contact with data from ACF Fields.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $fields The ACF Field data.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array|bool $contact The CiviCRM Contact data, or false on failure.
	 */
	public function update_from_fields( $contact_id, $fields, $post_id = null ) {

		// Build required data.
		$contact_data = $this->prepare_from_fields( $contact_id, $fields, $post_id );

		// Add the Contact ID.
		$contact_data['id'] = $contact_id;

		// Update the Contact.
		$contact = $this->plugin->civicrm->contact->update( $contact_data );

		// --<
		return $contact;

	}



	// -------------------------------------------------------------------------



	/**
	 * Return the "CiviCRM Field" ACF Settings Field.
	 *
	 * @since 0.4
	 *
	 * @param array $custom_fields The Custom Fields to populate the ACF Field with.
	 * @param array $contact_fields The Contact Fields to populate the ACF Field with.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $custom_fields = [], $contact_fields = [] ) {

		// Build choices array for dropdown.
		$choices = [];

		// Build Contact Field choices array for dropdown.
		$contact_fields_label = esc_attr__( 'Contact Fields', 'civicrm-wp-profile-sync' );
		foreach ( $contact_fields as $contact_field ) {
			$choices[ $contact_fields_label ][ $this->contact_field_prefix . $contact_field['name'] ] = $contact_field['title'];
		}

		// Build Custom Field choices array for dropdown.
		$custom_field_prefix = $this->civicrm->custom_field_prefix();
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			$custom_fields_label = esc_attr( $custom_group_name );
			foreach ( $custom_group as $custom_field ) {
				$choices[ $custom_fields_label ][ $custom_field_prefix . $custom_field['id'] ] = $custom_field['label'];
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.4
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/acf/contact/civicrm_field/choices', $choices );

		// Define Field.
		$field = [
			'key' => $this->civicrm->acf_field_key_get(),
			'label' => __( 'CiviCRM Field', 'civicrm-wp-profile-sync' ),
			'name' => $this->civicrm->acf_field_key_get(),
			'type' => 'select',
			'instructions' => __( 'Choose the CiviCRM Field that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'required' => 0,
			'return_format' => 'value',
			'parent' => $this->acf_loader->acf->field_group->placeholder_group_get(),
			'choices' => $choices,
		];

		// --<
		return $field;

	}



	/**
	 * Get the mapped Contact Field name if present.
	 *
	 * @since 0.4
	 *
	 * @param array $field The existing Field data array.
	 * @return string|bool $contact_field_name The name of the Contact Field, or false if none.
	 */
	public function contact_field_name_get( $field ) {

		// Init return.
		$contact_field_name = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Set the mapped Contact Field name if present.
		if ( isset( $field[ $acf_field_key ] ) ) {
			if ( false !== strpos( $field[ $acf_field_key ], $this->contact_field_prefix ) ) {
				$contact_field_name = (string) str_replace( $this->contact_field_prefix, '', $field[ $acf_field_key ] );
			}
		}

		/**
		 * Filter the Contact Field name.
		 *
		 * @since 0.4
		 *
		 * @param integer $contact_field_name The existing Contact Field name.
		 * @param array $field The array of ACF Field data.
		 */
		$contact_field_name = apply_filters( 'cwps/acf/civicrm/contact/contact_field/name', $contact_field_name, $field );

		// --<
		return $contact_field_name;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check with CiviCRM that this Contact can be viewed.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The CiviCRM Contact ID to check.
	 * @return bool $permitted True if allowed, false otherwise.
	 */
	public function user_can_view( $contact_id ) {

		// Deny by default.
		$permitted = false;

		// Always deny if CiviCRM is not active.
		if ( ! $this->civicrm->is_initialised() ) {
			return $permitted;
		}

		// Check with CiviCRM that this Contact can be viewed.
		if ( CRM_Contact_BAO_Contact_Permission::allow( $contact_id, CRM_Core_Permission::VIEW ) ) {
			$permitted = true;
		}

		/**
		 * Return permission but allow overrides.
		 *
		 * @since 0.4
		 *
		 * @param bool $permitted True if allowed, false otherwise.
		 * @param integer $contact_id The CiviCRM Contact ID.
		 */
		return apply_filters( 'cwps/acf/civicrm/contact/user_can_view', $permitted, $contact_id );

	}



	// -------------------------------------------------------------------------



	/**
	 * Returns the choices for a Setting Field from this Entity when found.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The existing array of choices for the Setting Field.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param bool $skip_check True if the check for Field Group should be skipped. Default false.
	 * @return array $choices The modified array of choices for the Setting Field.
	 */
	public function query_setting_choices( $choices, $field, $field_group, $skip_check = false ) {

		// Pass if this is not a Contact Field Group.
		$is_contact_field_group = $this->is_contact_field_group( $field_group );
		if ( empty( $is_contact_field_group ) ) {
			return $choices;
		}

		// Get the Contact Fields for this ACF Field.
		$contact_fields = $this->civicrm->contact_field->get_for_acf_field( $field );

		// Init Custom Fields array.
		$custom_fields = [];

		// Loop through the Post Types.
		foreach ( $is_contact_field_group as $post_type_name ) {

			// Get the Contact Type ID.
			$contact_type_id = $this->civicrm->contact_type->id_get_for_post_type( $post_type_name );

			// Get Contact Type hierarchy.
			$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $contact_type_id );

			// Get separated array of Contact Types.
			$contact_types = $this->plugin->civicrm->contact_type->hierarchy_separate( $hierarchy );

			// Check each Contact Type in turn.
			foreach ( $contact_types as $contact_type ) {

				// Get the Custom Fields for this CiviCRM Contact Type.
				$custom_fields_for_type = $this->plugin->civicrm->custom_field->get_for_entity_type(
					$contact_type['type'],
					$contact_type['subtype']
				);

				// Merge with existing array.
				$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			}

		}

		/**
		 * Filter the Custom Fields.
		 *
		 * @since 0.5
		 *
		 * @param array The initially empty array of filtered Custom Fields.
		 * @param array $custom_fields The CiviCRM Custom Fields array.
		 * @param array $field The ACF Field data array.
		 */
		$filtered_fields = apply_filters( 'cwps/acf/query_settings/custom_fields_filter', [], $custom_fields, $field );

		// Pass if not populated.
		if ( empty( $contact_fields ) && empty( $filtered_fields ) ) {
			return $choices;
		}

		// Build Contact Field choices array for dropdown.
		if ( ! empty( $contact_fields ) ) {
			$contact_fields_label = esc_attr__( 'Contact Fields', 'civicrm-wp-profile-sync' );
			foreach ( $contact_fields as $contact_field ) {
				$choices[ $contact_fields_label ][ $this->contact_field_prefix . $contact_field['name'] ] = $contact_field['title'];
			}
		}

		// Build Custom Field choices array for dropdown.
		if ( ! empty( $filtered_fields ) ) {
			$custom_field_prefix = $this->civicrm->custom_field_prefix();
			foreach ( $filtered_fields as $custom_group_name => $custom_group ) {
				$custom_fields_label = esc_attr( $custom_group_name );
				foreach ( $custom_group as $custom_field ) {
					$choices[ $custom_fields_label ][ $custom_field_prefix . $custom_field['id'] ] = $custom_field['label'];
				}
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.4
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/acf/contact/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



	/**
	 * Returns a Setting Field for a Bypass ACF Field Group when found.
	 *
	 * @since 0.5
	 *
	 * @param array $setting_field The existing Setting Field array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param array $entity_array The Entity and ID array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_bypass_settings_field( $setting_field, $field, $field_group, $entity_array ) {

		// Pass if not our Entity Type.
		if ( ! array_key_exists( $this->identifier, $entity_array ) ) {
			return $setting_field;
		}

		// Handle each Contact Type in turn.
		foreach ( $entity_array[ $this->identifier ] as $entity_id ) {

			// Get Contact Type hierarchy for this Entity ID.
			$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $entity_id );

			// Get the public Fields on the Entity for this Field Type.
			$fields_for_entity = $this->civicrm->contact_field->data_get( $hierarchy['type'], $field['type'], 'public' );

			// Get the Custom Fields for this Entity.
			$custom_fields = [];

			// Get separated array of Contact Types.
			$contact_types = $this->plugin->civicrm->contact_type->hierarchy_separate( $hierarchy );

			// Check each Contact Type in turn.
			foreach ( $contact_types as $contact_type ) {

				// Get the Custom Fields for this CiviCRM Contact Type.
				$custom_fields_for_type = $this->plugin->civicrm->custom_field->get_for_entity_type(
					$contact_type['type'],
					$contact_type['subtype']
				);

				// Merge with return array.
				$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			}

			/**
			 * Filter the Custom Fields.
			 *
			 * @since 0.5
			 *
			 * @param array The initially empty array of filtered Custom Fields.
			 * @param array $custom_fields The CiviCRM Custom Fields array.
			 * @param array $field The ACF Field data array.
			 */
			$filtered_fields = apply_filters( 'cwps/acf/query_settings/custom_fields_filter', [], $custom_fields, $field );

			// Skip if not populated.
			if ( empty( $fields_for_entity ) && empty( $filtered_fields ) ) {
				continue;
			}

			// Get the Setting Field.
			$setting_field = $this->acf_field_get( $filtered_fields, $fields_for_entity );

		}

		// Return populated array.
		return $setting_field;

	}



	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The existing Setting Field choices array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param array $entity_array The Entity and ID array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_bypass_settings_choices( $choices, $field, $field_group, $entity_array ) {

		// Pass if not our Entity Type.
		if ( ! array_key_exists( $this->identifier, $entity_array ) ) {
			return $choices;
		}

		// Handle each Contact Type in turn.
		foreach ( $entity_array[ $this->identifier ] as $entity_id ) {

			// Get Contact Type hierarchy for this Entity ID.
			$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $entity_id );

			// Get the public Fields on the Entity for this Field Type.
			$fields_for_entity = $this->civicrm->contact_field->data_get( $hierarchy['type'], $field['type'], 'public' );

			// Get the Custom Fields for this Entity.
			$custom_fields = [];

			// Get separated array of Contact Types.
			$contact_types = $this->plugin->civicrm->contact_type->hierarchy_separate( $hierarchy );

			// Check each Contact Type in turn.
			foreach ( $contact_types as $contact_type ) {

				// Get the Custom Fields for this CiviCRM Contact Type.
				$custom_fields_for_type = $this->plugin->civicrm->custom_field->get_for_entity_type(
					$contact_type['type'],
					$contact_type['subtype']
				);

				// Merge with return array.
				$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			}

			/**
			 * Filter the Custom Fields.
			 *
			 * @since 0.5
			 *
			 * @param array The initially empty array of filtered Custom Fields.
			 * @param array $custom_fields The CiviCRM Custom Fields array.
			 * @param array $field The ACF Field data array.
			 */
			$filtered_fields = apply_filters( 'cwps/acf/query_settings/custom_fields_filter', [], $custom_fields, $field );

			// Skip if not populated.
			if ( empty( $fields_for_entity ) && empty( $filtered_fields ) ) {
				continue;
			}

			// Build Contact Field choices array for dropdown.
			if ( ! empty( $fields_for_entity ) ) {
				$contact_fields_label = esc_attr__( 'Contact Fields', 'civicrm-wp-profile-sync' );
				foreach ( $fields_for_entity as $contact_field ) {
					$choices[ $contact_fields_label ][ $this->contact_field_prefix . $contact_field['name'] ] = $contact_field['title'];
				}
			}

			// Build Custom Field choices array for dropdown.
			if ( ! empty( $filtered_fields ) ) {
				$custom_field_prefix = $this->civicrm->custom_field_prefix();
				foreach ( $filtered_fields as $custom_group_name => $custom_group ) {
					$custom_fields_label = esc_attr( $custom_group_name );
					foreach ( $custom_group as $custom_field ) {
						$choices[ $custom_fields_label ][ $custom_field_prefix . $custom_field['id'] ] = $custom_field['label'];
					}
				}
			}

			/**
			 * Filter the choices to display in the "CiviCRM Field" select.
			 *
			 * @since 0.4
			 *
			 * @param array $choices The array of choices for the Setting Field.
			 */
			$choices = apply_filters( 'cwps/acf/contact/civicrm_field/choices', $choices );

		}

		// Return populated array.
		return $choices;

	}



	/**
	 * Appends a nested array of possible values to the Entities array for the
	 * Bypass Location Rule.
	 *
	 * @since 0.5
	 *
	 * @param array $entities The existing Entity values array.
	 * @param array $rule The current Location Rule.
	 * @return array $entities The modified Entity values array.
	 */
	public function query_bypass_entities( $entities, $rule = [] ) {

		// Get all Contact Types.
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();

		// Bail if there are none.
		if ( empty( $contact_types ) ) {
			return $entities;
		}

		// Add Option Group.
		$contact_types_title = esc_attr( __( 'Contact Types', 'civicrm-wp-profile-sync' ) );
		$entities[ $contact_types_title ] = [];

		// Add entries for each CiviCRM Contact Type.
		foreach ( $contact_types as $contact_type ) {

			// Top level types first.
			$entities[ $contact_types_title ][ 'contact-' . $contact_type['id'] ] = $contact_type['label'];

			// Skip Sub-types if there aren't any.
			if ( empty( $contact_type['children'] ) ) {
				continue;
			}

			// Add children.
			foreach ( $contact_type['children'] as $contact_subtype ) {
				$entities[ $contact_types_title ] [ $this->identifier . '-' . $contact_subtype['id'] ] = '&mdash; ' . $contact_subtype['label'];
			}

		}

		// --<
		return $entities;

	}



	/**
	 * Listen for queries from the Field Group class.
	 *
	 * This method responds with a Boolean if it detects that this Field Group
	 * maps to a Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param bool $mapped The existing mapping flag.
	 * @param array $field_group The array of ACF Field Group data.
	 * @return bool $mapped True if the Field Group is mapped, or pass through if not mapped.
	 */
	public function query_field_group_mapped( $mapped, $field_group ) {

		// Bail if a Mapping has already been found.
		if ( $mapped !== false ) {
			return $mapped;
		}

		// Bail if this is not a Contact Field Group.
		$is_contact_field_group = $this->is_contact_field_group( $field_group );
		if ( $is_contact_field_group === false ) {
			return $mapped;
		}

		// --<
		return true;

	}



	/**
	 * Listen for queries from the Custom Field class.
	 *
	 * @since 0.4
	 *
	 * @param array $custom_fields The existing Custom Fields.
	 * @param array $field_group The array of ACF Field Group data.
	 * @return array $custom_fields The populated array of CiviCRM Custom Fields params.
	 */
	public function query_custom_fields( $custom_fields, $field_group ) {

		// Bail if this is not a Contact Field Group.
		$is_contact_field_group = $this->is_contact_field_group( $field_group );
		if ( $is_contact_field_group === false || empty( $is_contact_field_group ) ) {
			return $custom_fields;
		}

		// Loop through the Post Types.
		foreach ( $is_contact_field_group as $post_type_name ) {

			// Get the Contact Type ID.
			$contact_type_id = $this->civicrm->contact_type->id_get_for_post_type( $post_type_name );

			// Get Contact Type hierarchy.
			$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $contact_type_id );

			// Get separated array of Contact Types.
			$contact_types = $this->plugin->civicrm->contact_type->hierarchy_separate( $hierarchy );

			// Check each Contact Type in turn.
			foreach ( $contact_types as $contact_type ) {

				// Get the Custom Fields for this CiviCRM Contact Type.
				$custom_fields_for_type = $this->plugin->civicrm->custom_field->get_for_entity_type(
					$contact_type['type'],
					$contact_type['subtype']
				);

				// Merge with return array.
				$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			}

		}

		// --<
		return $custom_fields;

	}



	/**
	 * Listen for queries from the Custom Field class.
	 *
	 * This method responds with an array of "Post IDs" if it detects that the
	 * set of Custom Fields maps to a Contact.
	 *
	 * @since 0.4
	 *
	 * @param array|bool $post_ids The existing "Post IDs".
	 * @param array $args The array of CiviCRM Custom Fields params.
	 * @return array|bool $post_id The mapped "Post IDs", or false if not mapped.
	 */
	public function query_post_id( $post_ids, $args ) {

		// Init Contact ID.
		$contact_id = false;

		// Let's tease out the context from the Custom Field data.
		foreach ( $args['custom_fields'] as $field ) {

			// Skip if it is not attached to a Contact.
			if ( $field['entity_table'] != 'civicrm_contact' ) {
				continue;
			}

			// Grab the Contact.
			$contact_id = $field['entity_id'];

			// We can bail now that we know.
			break;

		}

		// Bail if there's no Contact ID.
		if ( $contact_id === false ) {
			return $post_ids;
		}

		// Grab Contact.
		$contact = $this->get_by_id( $contact_id );
		if ( $contact === false ) {
			return $post_ids;
		}

		// Bail if none of this Contact's Contact Types is mapped.
		$post_types = $this->is_mapped( $contact, 'create' );
		if ( $post_types === false ) {
			return $post_ids;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Init Contact Post IDs array.
		$contact_post_ids = [];

		// Get the Post IDs that this Contact is mapped to.
		foreach ( $post_types as $post_type ) {

			// Get array of IDs for this Post Type.
			$ids = $this->acf_loader->post->get_by_contact_id( $contact_id, $post_type );

			// Skip if not mapped.
			if ( $ids === false ) {
				continue;
			}

			// Add to Contact Post IDs array.
			foreach ( $ids as $id ) {

				// Exclude "reverse" edits when a Post is the originator.
				if ( $entity['entity'] !== 'post' || $id != $entity['id'] ) {
					$contact_post_ids[] = $id;
				}

			}

		}

		// Bail if no "Post IDs" are found.
		if ( empty( $contact_post_ids ) ) {
			return $post_ids;
		}

		// Add found "Post IDs" to return array.
		if ( is_array( $post_ids ) ) {
			$post_ids = array_merge( $post_ids, $contact_post_ids );
		} else {
			$post_ids = $contact_post_ids;
		}

		// --<
		return $post_ids;

	}



	/**
	 * Listen for queries from the Attachment class.
	 *
	 * This method responds with an "Entity Table" if it detects that the ACF
	 * Field Group maps to a Contact.
	 *
	 * @since 0.5.2
	 *
	 * @param array $entity_tables The existing "Entity Tables".
	 * @param array $field_group The array of ACF Field Group params.
	 * @return array $entity_tables The mapped "Entity Tables".
	 */
	public function query_entity_table( $entity_tables, $field_group ) {

		// Bail if this is not a Contact Field Group.
		$is_visible = $this->is_contact_field_group( $field_group );
		if ( $is_visible === false ) {
			return $entity_tables;
		}

		// Append our "Entity Table" if not already present.
		if ( ! array_key_exists( 'civicrm_contact', $entity_tables ) ) {
			$entity_tables['civicrm_contact'] = __( 'Contact', 'civicrm-wp-profile-sync' );
		}

		// --<
		return $entity_tables;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if a Field Group has been mapped to one or more Contact Post Types.
	 *
	 * @since 0.4
	 *
	 * @param array $field_group The Field Group to check.
	 * @return array|bool The array of Post Types if the Field Group has been mapped, or false otherwise.
	 */
	public function is_contact_field_group( $field_group ) {

		// Bail if there's no Field Group ID.
		if ( empty( $field_group['ID'] ) ) {
			return false;
		}

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache[ $field_group['ID'] ] ) ) {
			return $pseudocache[ $field_group['ID'] ];
		}

		// Assume not a Contact Field Group.
		$is_contact_field_group = false;

		// If Location Rules exist.
		if ( ! empty( $field_group['location'] ) ) {

			// Get mapped Post Types.
			$post_types = $this->acf_loader->mapping->mappings_for_contact_types_get();

			// Bail if there are no mappings.
			if ( ! empty( $post_types ) ) {

				// Loop through them.
				foreach ( $post_types as $post_type ) {

					// Define params to test for a mapped Post Type.
					$params = [
						'post_type' => $post_type,
					];

					// Do the check.
					$is_visible = $this->acf_loader->acf->field_group->is_visible( $field_group, $params );

					// If it is, then add to return array.
					if ( $is_visible ) {
						$is_contact_field_group[] = $post_type;
					}

				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $field_group['ID'] ] ) ) {
			$pseudocache[ $field_group['ID'] ] = $is_contact_field_group;
		}

		// --<
		return $is_contact_field_group;

	}



	// -------------------------------------------------------------------------
	// Retained methods to provide backwards compatibility.
	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Contact data for a given ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact to query.
	 * @return array|bool $contact_data An array of Contact data, or false on failure.
	 */
	public function get_by_id( $contact_id ) {
		return $this->plugin->civicrm->contact->get_by_id( $contact_id );
	}



} // Class ends.



