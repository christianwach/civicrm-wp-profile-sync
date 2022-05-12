<?php
/**
 * CiviCRM Case Class.
 *
 * Handles CiviCRM Case functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Case Class.
 *
 * A class that encapsulates CiviCRM Case functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Case {

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
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
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
	public $identifier = 'case';

	/**
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Case Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $case_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $case_field_prefix = 'caicase_';



	/**
	 * Constructor.
	 *
	 * @since 0.5
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
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		//$this->register_mapper_hooks();

		// Listen for events from Manual Sync that require Case updates.
		//add_action( 'cwps/acf/admin/post-to-case/sync', [ $this, 'post_sync' ], 10 );
		//add_action( 'cwps/acf/admin/post-to-case/acf_fields/sync', [ $this, 'acf_fields_sync' ], 10 );

		// Listen for queries from our Field Group class.
		//add_filter( 'cwps/acf/query_field_group_mapped', [ $this, 'query_field_group_mapped' ], 10, 2 );

		// Listen for queries from our Custom Field class.
		//add_filter( 'cwps/acf/query_custom_fields', [ $this, 'query_custom_fields' ], 10, 2 );

		// Listen for queries from the Custom Field class.
		//add_filter( 'cwps/acf/query_post_id', [ $this, 'query_post_id' ], 10, 2 );

		// Listen for queries from the Attachment class.
		//add_filter( 'cwps/acf/query_entity_table', [ $this, 'query_entity_table' ], 10, 2 );

		// Listen for queries from the ACF Field class.
		//add_filter( 'cwps/acf/field/query_setting_choices', [ $this, 'query_setting_choices' ], 20, 3 );

		// Listen for queries from the ACF Bypass class.
		//add_filter( 'cwps/acf/bypass/query_settings_field', [ $this, 'query_bypass_settings_field' ], 20, 4 );
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

		// Listen for queries from the ACF Bypass Location Rule class.
		add_filter( 'cwps/acf/bypass/location/query_entities', [ $this, 'query_bypass_entities' ], 50, 2 );

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.5
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
			return;
		}

		// Listen for events from our Mapper that require Case updates.
		add_action( 'cwps/acf/mapper/post/saved', [ $this, 'post_saved' ], 10 );
		add_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ], 10 );

		// Declare registered.
		$this->mapper_hooks = true;

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.5
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
	 * Update a CiviCRM Case when a WordPress Post is synced.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function post_sync( $args ) {

		// Pass on.
		$this->post_saved( $args );

	}



	/**
	 * Update a CiviCRM Case when a WordPress Post has been updated.
	 *
	 * @since 0.5
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
		if ( ! $this->acf_loader->post_type->is_mapped_to_case_type( $post->post_type ) ) {
			$this->do_not_sync = true;
			return;
		}

		// Get the Case ID.
		$case_id = $this->acf_loader->post->case_id_get( $post->ID );

		/*
		// Get previous values.
		$prev_values = get_fields( $post_id );

		// Get submitted values.
		$values = acf_maybe_get_POST( 'acf' );
		*/

		// Does this Post have a Case ID?
		if ( $case_id === false ) {

			// No - create a Case.
			$case = $this->create_from_post( $post );

			// Store Case ID if successful.
			if ( $case !== false ) {
				$this->acf_loader->post->case_id_set( $post->ID, $case['id'] );
			}

		} else {

			// Yes - update the Case.
			$case = $this->update_from_post( $post, $case_id );

		}

		// Add our data to the params.
		$args['case'] = $case;
		$args['case_id'] = $case['id'];

		/**
		 * Broadcast that a Case has been updated.
		 *
		 * May be used internally by:
		 *
		 * * Groups
		 * * Post Taxonomies
		 *
		 * @since 0.5
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/acf/case/post_saved', $args );

	}



	/**
	 * Update a CiviCRM Case when the ACF Fields on a WordPress Post are synced.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function acf_fields_sync( $args ) {

		// Pass on.
		$this->acf_fields_saved( $args );

	}



	/**
	 * Update a CiviCRM Case when the ACF Fields on a WordPress Post have been updated.
	 *
	 * @since 0.5
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

		// Does this Post have a Case ID?
		$case_id = $this->acf_loader->post->case_id_get( $post->ID );

		// Bail if there isn't one.
		if ( $case_id === false ) {
			return;
		}

		/*
		 * Get existing Field values.
		 *
		 * These are actually the *new* values because we are hooking in *after*
		 * the Fields have been saved.
		 */
		$fields = get_fields( $post->ID, false );

		// TODO: Decide if we should get the ACF Field data without formatting.
		// This also applies to any calls to get_field_object().
		//$fields = get_fields( $post->ID, false );

		// Get submitted values. (No need for this - see hook priority)
		//$submitted_values = acf_maybe_get_POST( 'acf' );

		// Update the Case with this data.
		$case = $this->update_from_fields( $case_id, $fields, $post->ID );

		// Add our data to the params.
		$args['case_id'] = $case_id;
		$args['case'] = $case;
		$args['post'] = $post;
		$args['fields'] = $fields;

		/**
		 * Broadcast that a Case has been updated when ACF Fields were saved.
		 *
		 * Used internally by:
		 *
		 * Case Fields to maintain sync with:
		 *
		 * * The ACF "Case Date Time" Field
		 * * The ACF "Created Date" Field
		 * * The ACF "Modified Date" Field
		 *
		 * @since 0.5
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/acf/case/acf_fields_saved', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get "chunked" CiviCRM API Case data for a given Case Type.
	 *
	 * This method is used internally by the "Manual Sync" admin page.
	 *
	 * @since 0.5
	 *
	 * @param integer $case_type_id The numeric ID of the CiviCRM Case Type.
	 * @param integer $offset The numeric offset for the query.
	 * @param integer $limit The numeric limit for the query.
	 * @return array $result The array of Case data from the CiviCRM API.
	 */
	public function cases_chunked_data_get( $case_type_id, $offset, $limit ) {

		// Sanity check.
		if ( empty( $case_type_id ) ) {
			return 0;
		}

		// Params to query Cases.
		$params = [
			'version' => 3,
			'case_type_id' => $case_type_id,
			'options' => [
				'limit' => $limit,
				'offset' => $offset,
			],
		];

		// Call API.
		$result = civicrm_api( 'Case', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'case_type_id' => $case_type_id,
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
	 * Check whether a Case's Case Type is mapped to a Post Type.
	 *
	 * The Mapper makes use of the boolean return to bail early.
	 *
	 * @see CiviCRM_Profile_Sync_ACF_Mapper::case_pre_create()
	 * @see CiviCRM_Profile_Sync_ACF_Mapper::case_pre_edit()
	 *
	 * @since 0.5
	 *
	 * @param array|obj $case The Case data.
	 * @param string $create_post Create a mapped Post if missing. Either 'create' or 'skip'.
	 * @return string|bool $is_mapped The Post Type if the Case is mapped, false otherwise.
	 */
	public function is_mapped( $case, $create_post = 'skip' ) {

		// Init return.
		$is_mapped = false;

		// Maybe cast Case data as object.
		if ( is_array( $case ) ) {
			$case = (object) $case;
		}

		// Skip if there is no Case Type.
		if ( empty( $case->case_type_id ) ) {
			return $is_mapped;
		}

		// Get the Post Type mapped to this Case Type.
		$post_type = $this->civicrm->case_type->is_mapped_to_post_type( $case->case_type_id );

		// Skip if this Case Type is not mapped.
		if ( $post_type === false ) {
			return $is_mapped;
		}

		// Bail if there's no Case ID.
		if ( empty( $case->id ) ) {
			return $is_mapped;
		}

		// Get the associated Post ID.
		$post_id = $this->acf_loader->post->get_by_case_id( $case->id, $post_type );

		// Create the Post if it's missing.
		if ( $post_id === false && $create_post === 'create' ) {

			// Prevent recursion and the resulting unexpected Post creation.
			if ( ! doing_action( 'cwps/acf/post/case/sync' ) ) {

				// Get full Case data.
				$case_data = $this->get_by_id( $case->id );

				// Remove WordPress callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_remove();
				$this->acf_loader->mapper->hooks_civicrm_remove();

				// Let's make an array of params.
				$args = [
					'op' => 'sync',
					'objectName' => 'Case',
					'objectId' => $case_data['id'],
					'objectRef' => (object) $case_data,
				];

				// Sync this Case to the Post Type.
				$this->acf_loader->post->case_sync_to_post( $args, $post_type );

				// Reinstate WordPress callbacks.
				$this->acf_loader->mapper->hooks_wordpress_add();
				$this->acf_loader->mapper->hooks_civicrm_add();

			}

		}

		// Assign Post Type.
		$is_mapped = $post_type;

		// --<
		return $is_mapped;

	}



	/**
	 * Check if a Case is mapped to a Post of a particular Post Type.
	 *
	 * @since 0.5
	 *
	 * @param array|obj $case The Case data.
	 * @param string $post_type The WordPress Post Type.
	 * @return integer|bool $is_mapped The ID of the WordPress Post if the Case is mapped, false otherwise.
	 */
	public function is_mapped_to_post( $case, $post_type = 'any' ) {

		// TODO: Query Posts with Post meta instead? Or pseudo-cache?

		// Assume not.
		$is_mapped = false;

		// Maybe cast Case data as object.
		if ( is_array( $case ) ) {
			$case = (object) $case;
		}

		// Bail if this Case's Case Type is not mapped.
		$post_type = $this->is_mapped( $case );
		if ( $post_type === false ) {
			return false;
		}

		// Grab Case ID.
		if ( isset( $case->id ) ) {
			$case_id = $case->id;
		}

		// Bail if no Case ID is found.
		if ( empty( $case_id ) ) {
			return $is_mapped;
		}

		// Find the Post ID of this Post Type that this Case is synced with.
		$post_ids = $this->acf_loader->post->get_by_case_id( $case_id, $post_type );

		// Bail if no Post IDs are found.
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
	 * Get the CiviCRM Case data for a given ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $case_id The numeric ID of the CiviCRM Case to query.
	 * @return array|bool $case_data An array of Case data, or false on failure.
	 */
	public function get_by_id( $case_id ) {

		// Init return.
		$case_data = false;

		// Bail if we have no Case ID.
		if ( empty( $case_id ) ) {
			return $case_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $case_data;
		}

		// Define params to get queried Case.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $case_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Case', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $case_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $case_data;
		}

		// The result set should contain only one item.
		$case_data = array_pop( $result['values'] );

		/*
		// Backfill Target IDs.
		$case_data['target_contact_id'] = [];
		$targets = $this->get_target_contact_ids( $case_id );
		if ( ! empty( $targets ) ) {
			$case_data['target_contact_id'] = $targets;
		}

		// Backfill Assignee IDs.
		$case_data['assignee_contact_id'] = [];
		$assignees = $this->get_assignee_contact_ids( $case_id );
		if ( ! empty( $assignees ) ) {
			$case_data['assignee_contact_id'] = $assignees;
		}
		*/

		// --<
		return $case_data;

	}



	/**
	 * Get the CiviCRM Case data for a given Case Type ID and Contact ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $case_type_id The numeric ID of the CiviCRM Case Type to query.
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact to query.
	 * @return array|bool $case_data An array of Case data, or false on failure.
	 */
	public function get_by_type_and_contact( $case_type_id, $contact_id ) {

		// Init return.
		$case_data = false;

		// Bail if we have no Case Type ID or Contact ID.
		if ( empty( $case_type_id ) || empty( $contact_id ) ) {
			return $case_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $case_data;
		}

		// Define params to get queried Case.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'case_type_id' => $case_type_id,
			'contact_id' => $contact_id,
			'is_deleted' => 0,
			'options' => [
				'limit' => 0, // No limit.
				'sort' => 'id desc',
			],
		];

		// Call the API.
		$result = civicrm_api( 'Case', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $case_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $case_data;
		}

		// The result set should contain only one item.
		$case_data = array_pop( $result['values'] );

		/*
		// Backfill Target IDs.
		$case_data['target_contact_id'] = [];
		$targets = $this->get_target_contact_ids( $case_id );
		if ( ! empty( $targets ) ) {
			$case_data['target_contact_id'] = $targets;
		}

		// Backfill Assignee IDs.
		$case_data['assignee_contact_id'] = [];
		$assignees = $this->get_assignee_contact_ids( $case_id );
		if ( ! empty( $assignees ) ) {
			$case_data['assignee_contact_id'] = $assignees;
		}
		*/

		// --<
		return $case_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Case Target Contact IDs for a given ID.
	 *
	 * For reference, CaseContact records can have a "record_type_id" which
	 * has the following possible values:
	 *
	 * 1: assignee
	 * 2: creator
	 * 3: focus or target
	 *
	 * @since 0.5
	 *
	 * @param integer $case_id The numeric ID of the CiviCRM Case to query.
	 * @return array|bool $contact_ids An array of Contact IDs, or false on failure.
	 */
	public function get_target_contact_ids( $case_id ) {

		// Init return.
		$contact_ids = false;

		// Bail if we have no Case ID.
		if ( empty( $case_id ) ) {
			return $contact_ids;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_ids;
		}

		// Define params to get queried Case Targets.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'case_id' => $case_id,
			'record_type_id' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CaseContact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $contact_ids;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_ids;
		}

		// Grab just the Contact IDs.
		$contact_ids = wp_list_pluck( $result['values'], 'contact_id' );

		// --<
		return $contact_ids;

	}



	/**
	 * Get the CiviCRM Case Assignee Contact IDs for a given ID.
	 *
	 * For reference, CaseContact records can have a "record_type_id" which
	 * has the following possible values:
	 *
	 * 1: assignee
	 * 2: creator
	 * 3: focus or target
	 *
	 * @since 0.5
	 *
	 * @param integer $case_id The numeric ID of the CiviCRM Case to query.
	 * @return array|bool $contact_ids An array of Contact IDs, or false on failure.
	 */
	public function get_assignee_contact_ids( $case_id ) {

		// Init return.
		$contact_ids = false;

		// Bail if we have no Case ID.
		if ( empty( $case_id ) ) {
			return $contact_ids;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_ids;
		}

		// Define params to get queried Case Targets.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'case_id' => $case_id,
			'record_type_id' => 1,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CaseContact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $contact_ids;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_ids;
		}

		// Grab just the Contact IDs.
		$contact_ids = wp_list_pluck( $result['values'], 'contact_id' );

		// --<
		return $contact_ids;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Case for a given set of data.
	 *
	 * @since 0.5
	 *
	 * @param array $case The CiviCRM Case data.
	 * @return array|bool $case_data The array Case data from the CiviCRM API, or false on failure.
	 */
	public function create( $case ) {

		// Init as failure.
		$case_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $case_data;
		}

		// Build params to create Case.
		$params = [
			'version' => 3,
		] + $case;

		/*
		 * Minimum array to create a Case:
		 *
		 * $params = [
		 *   'version' => 3,
		 *   'case_type_id' => 6,
		 *   'contact_id' => "user_contact_id",
		 * ];
		 *
		 * Updates are triggered by:
		 *
		 * $params['id'] = 654;
		 *
		 * Custom Fields are addressed by ID:
		 *
		 * $params['custom_9'] = "Blah";
		 * $params['custom_7'] = 1;
		 * $params['custom_8'] = 0;
		 *
		 * CiviCRM kindly ignores any Custom Fields which are passed to it that
		 * aren't attached to the Entity. This is of significance when a Field
		 * Group is attached to multiple Post Types (for example) and the Fields
		 * refer to different Entities (e.g. "Case" and "Student").
		 *
		 * Nice.
		 */

		// Call the API.
		$result = civicrm_api( 'Case', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $case_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $case_data;
		}

		// The result set should contain only one item.
		$case_data = array_pop( $result['values'] );

		// --<
		return $case_data;

	}



	/**
	 * Update a CiviCRM Case with a given set of data.
	 *
	 * @since 0.5
	 *
	 * @param array $case The CiviCRM Case data.
	 * @return array|bool $case_data The array Case data from the CiviCRM API, or false on failure.
	 */
	public function update( $case ) {

		// Log and bail if there's no Case ID.
		if ( empty( $case['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numerical ID must be present to update a Case.', 'civicrm-wp-profile-sync' ),
				'case' => $case,
				'backtrace' => $trace,
			], true ) );
			return $case_data;
		}

		// Pass through.
		return $this->create( $case );

	}



	/**
	 * Fill out the missing data for a CiviCRM Case.
	 *
	 * @since 0.5
	 *
	 * @param object $case The CiviCRM Case data object.
	 * @return object $case The backfilled CiviCRM Case data.
	 */
	public function backfill( $case ) {

		// Get the full Case data.
		$case_full = $this->get_by_id( $case->id );

		// Bail on failure.
		if ( $case_full === false ) {
			return $case;
		}

		// Fill out missing Case data.
		foreach ( $case_full as $key => $item ) {
			if ( empty( $case->$key ) && ! empty( $item ) ) {
				$case->$key = $item;
			}
		}

		// --<
		return $case;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Case Contact for a given set of data.
	 *
	 * @since 0.5
	 *
	 * @param array $case_contact The CiviCRM Case and Contact data.
	 * @return array|bool $case_contact_data The array CaseContact data from the CiviCRM API, or false on failure.
	 */
	public function contact_create( $case_contact ) {

		// Init as failure.
		$case_contact_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $case_contact_data;
		}

		// Build params to create Case.
		$params = [
			'version' => 3,
		] + $case_contact;

		// Call the API.
		$result = civicrm_api( 'CaseContact', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $case_contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $case_contact_data;
		}

		// The result set should contain only one item.
		$case_contact_data = array_pop( $result['values'] );

		// --<
		return $case_contact_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Case Manager for a given set of data.
	 *
	 * @since 0.5
	 *
	 * @param array $case_data The CiviCRM Case data from the Form.
	 * @param array $case The full CiviCRM Case data.
	 */
	public function manager_add( $case_data, $case ) {

		// Bail if there is no Manager ID.
		if ( empty( $case_data['manager_id'] ) || ! is_numeric( $case_data['manager_id'] ) ) {
			return;
		}

		// Bail if there is something wrong with the Case.
		if ( empty( $case['id'] ) ) {
			return;
		}

		// Get the Case Type data.
		$case_type = $this->civicrm->case_type->get_by_id( $case['case_type_id'] );

		// Bail if we don't get a Case Type.
		if ( $case_type === false ) {
			return;
		}

		// Get the Case Type "Case Roles" Definition.
		$case_roles = [];
		if ( ! empty( $case_type['definition']['caseRoles'] ) ) {
			$case_roles = $case_type['definition']['caseRoles'];
		}

		// Bail if we don't get any Case Roles.
		if ( empty( $case_roles ) ) {
			return;
		}

		// Get the Manager Case Role.
		$manager_role = [];
		foreach ( $case_roles as $case_role ) {
			if ( ! empty( $case_role['manager'] ) ) {
				$manager_role = $case_role;
			}
		}

		// Bail if we don't get a Manager Role.
		if ( empty( $manager_role ) ) {
			return;
		}

		// Try and get a Relationship Type.
		$relationship_type = $this->civicrm->relationship->type_get_by_name_or_label( $manager_role['name'], 'ba' );

		// Bail if we don't get a Relationship Type.
		if ( empty( $relationship_type ) ) {
			return;
		}

		// Query for the existing Relationship.
		$query = [
			'relationship_type_id' => $relationship_type['id'],
			'case_id' => $case['id'],
			'is_active' => 1,
		];

		// Try and get the existing Relationship.
		$existing = $this->civicrm->relationship->get_by( $query );

		// Build params to create the Relationship.
		$params = [
			'contact_id_a' => $case_data['contact_id'],
			'contact_id_b' => $case_data['manager_id'],
			'relationship_type_id' => $relationship_type['id'],
			'case_id' => $case['id'],
			'start_date' => date( 'YmdHis', strtotime( 'now' ) ),
		];

		// If there's an existing Relationship, update.
		if ( count( $existing ) === 1 ) {
			$relationship = array_pop( $existing );
			$params['id'] = $relationship['id'];
			$result = $this->civicrm->relationship->update( $params );
		} else {
			$result = $this->civicrm->relationship->create( $params );
		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Prepare the required CiviCRM Case data from a WordPress Post.
	 *
	 * @since 0.5
	 *
	 * @param WP_Post $post The WordPress Post object.
	 * @param integer $case_id The numeric ID of the Case (or null if new).
	 * @return array $case_data The CiviCRM Case data.
	 */
	public function prepare_from_post( $post, $case_id = null ) {

		// Init required data.
		$case_data = [];

		// Maybe add the Case ID.
		if ( ! empty( $case_id ) ) {
			$case_data['id'] = $case_id;
		}

		// Assign Date Fields if creating Case.
		if ( empty( $case_id ) ) {
			$case_data['case_date_time'] = $post->post_date;
			$case_data['created_date'] = $post->post_date;
			$case_data['modified_date'] = $post->post_modified;
		}

		// Assign Creator if creating Case.
		if ( empty( $case_id ) ) {
			$case_data['source_contact_id'] = 'user_contact_id';
		}

		// Assign a default Status - "Scheduled" - if creating Case.
		if ( empty( $case_id ) ) {
			$case_data['status_id'] = 1;
		}

		// Assign a default Priority - "Normal" - if creating Case.
		if ( empty( $case_id ) ) {
			$case_data['priority_id'] = 2;
		}

		// Always assign Post Title to Case "subject".
		if ( empty( $post->post_title ) ) {
			$case_data['subject'] = __( 'Name not set', 'civicrm-wp-profile-sync' );
		} else {
			$case_data['subject'] = $post->post_title;
		}

		// Always assign Post Content to Case "details".
		$case_data['details'] = $post->post_content;

		// Always assign Case Type ID.
		$case_data['case_type_id'] = $this->civicrm->case_type->id_get_for_post_type( $post->post_type );

		/*
		// Set a status for the Case depending on the Post status.
		if ( $post->post_status == 'trash' ) {
			$case_data['is_deleted'] = 1;
		} else {
			$case_data['is_deleted'] = 0;
		}
		*/

		/**
		 * Filter the Case data.
		 *
		 * @since 0.5
		 *
		 * @param array $case_data The existing CiviCRM Case data.
		 * @param WP_Post $post The WordPress Post.
		 */
		$case_data = apply_filters( 'cwps/acf/civicrm/case/post/data', $case_data, $post );

		// --<
		return $case_data;

	}



	/**
	 * Create a CiviCRM Case from a WordPress Post.
	 *
	 * This can be merged with `self::update_from_post()` in due course.
	 *
	 * @since 0.5
	 *
	 * @param WP_Post $post The WordPress Post object.
	 * @return array|bool $case The CiviCRM Case data, or false on failure.
	 */
	public function create_from_post( $post ) {

		// Build required data.
		$case_data = $this->prepare_from_post( $post );

		// Create the Case.
		$case = $this->create( $case_data );

		// --<
		return $case;

	}



	/**
	 * Sync a WordPress Post with a CiviCRM Case.
	 *
	 * When we update the Case, we always sync:
	 *
	 * * The WordPress Post's "title" with the CiviCRM Case's "subject".
	 * * The WordPress Post's "content" with the CiviCRM Case's "details".
	 *
	 * @since 0.5
	 *
	 * @param WP_Post $post The WordPress Post object.
	 * @param integer $existing_id The numeric ID of the Case.
	 * @return array|bool $case The CiviCRM Case data, or false on failure.
	 */
	public function update_from_post( $post, $existing_id ) {

		// Build required data.
		$case_data = $this->prepare_from_post( $post, $existing_id );

		// Update the Case.
		$case = $this->update( $case_data );

		// --<
		return $case;

	}



	// -------------------------------------------------------------------------



	/**
	 * Prepare the required CiviCRM Case data from a set of ACF Fields.
	 *
	 * This method combines all Case Fields that the CiviCRM API accepts as
	 * params for ( 'Case', 'create' ) along with the linked Custom Fields.
	 *
	 * The CiviCRM API will update Custom Fields as long as they are passed to
	 * ( 'Case', 'create' ) in the correct format. This is of the form:
	 * 'custom_N' where N is the ID of the Custom Field.
	 *
	 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Base
	 *
	 * @since 0.5
	 *
	 * @param integer $case_id The numeric ID of the Case.
	 * @param array $fields The ACF Field data.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $case_data The CiviCRM Case data.
	 */
	public function prepare_from_fields( $case_id, $fields, $post_id = null ) {

		// Init data for Fields.
		$case_data = [];

		// Bail if we have no Field data to save.
		if ( empty( $fields ) ) {
			return $case_data;
		}

		// Loop through the Field data.
		foreach ( $fields as $selector => $value ) {

			// Get the Field settings.
			$settings = get_field_object( $selector, $post_id );

			// Get the CiviCRM Custom Field and Case Field.
			$custom_field_id = $this->civicrm->custom_field->custom_field_id_get( $settings );
			$case_field_name = $this->case_field_name_get( $settings );

			// Do we have a synced Custom Field or Case Field?
			if ( ! empty( $custom_field_id ) || ! empty( $case_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . $custom_field_id;

				} else {

					// The Case Field code is the setting.
					$code = $case_field_name;

					// Unless it's the "target" Field.
					if ( $code == 'target_contact_id' ) {
						$code = 'target_id';
					}

					// Or it's the "assignee" Field, FFS.
					if ( $code == 'assignee_contact_id' ) {
						$code = 'assignee_id';
					}

				}

				// Build args for value conversion.
				$args = [
					'identifier' => $this->identifier,
					'entity_id' => $case_id,
					'custom_field_id' => $custom_field_id,
					'field_name' => $case_field_name,
					'selector' => $selector,
					'post_id' => $post_id,
				];

				// Parse value by Field type.
				$value = $this->acf_loader->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings, $args );

				// Some Case Fields cannot be empty.
				$cannot_be_empty = [
					'source_contact_id',
					'case_date_time',
					'created_date',
					'modified_date',
				];

				// Add it to the Field data.
				if ( in_array( $code, $cannot_be_empty ) && empty( $value ) ) {
					// Skip.
				} else {
					$case_data[ $code ] = $value;
				}

			}

		}

		// --<
		return $case_data;

	}



	/**
	 * Update a CiviCRM Case with data from ACF Fields.
	 *
	 * @since 0.5
	 *
	 * @param integer $case_id The numeric ID of the Case.
	 * @param array $fields The ACF Field data.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $case The CiviCRM Case data, or false on failure.
	 */
	public function update_from_fields( $case_id, $fields, $post_id = null ) {

		// Build required data.
		$case_data = $this->prepare_from_fields( $case_id, $fields, $post_id );

		// Add the Case ID.
		$case_data['id'] = $case_id;

		// Update the Case.
		$case = $this->update( $case_data );

		// --<
		return $case;

	}



	// -------------------------------------------------------------------------



	/**
	 * Return the "CiviCRM Field" ACF Settings Field.
	 *
	 * @since 0.5
	 *
	 * @param array $custom_fields The Custom Fields to populate the ACF Field with.
	 * @param array $case_fields The Case Fields to populate the ACF Field with.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $custom_fields = [], $case_fields = [] ) {

		// Build choices array for dropdown.
		$choices = [];

		// Build Case Field choices array for dropdown.
		$case_fields_label = esc_attr__( 'Case Fields', 'civicrm-wp-profile-sync' );
		foreach ( $case_fields as $case_field ) {
			$choices[ $case_fields_label ][ $this->case_field_prefix . $case_field['name'] ] = $case_field['title'];
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
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/case/civicrm_field/choices', $choices );

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
	 * Get the mapped Case Field name if present.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing Field data array.
	 * @return string|bool $case_field_name The name of the Case Field, or false if none.
	 */
	public function case_field_name_get( $field ) {

		// Init return.
		$case_field_name = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Set the mapped Case Field name if present.
		if ( isset( $field[ $acf_field_key ] ) ) {
			if ( false !== strpos( $field[ $acf_field_key ], $this->case_field_prefix ) ) {
				$case_field_name = (string) str_replace( $this->case_field_prefix, '', $field[ $acf_field_key ] );
			}
		}

		/**
		 * Filter the Case Field name.
		 *
		 * @since 0.5
		 *
		 * @param integer $case_field_name The existing Case Field name.
		 * @param array $field The array of ACF Field data.
		 */
		$case_field_name = apply_filters( 'cwps/acf/civicrm/case/case_field/name', $case_field_name, $field );

		// --<
		return $case_field_name;

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

		// Pass if this is not a Case Field Group.
		$is_visible = $this->is_case_field_group( $field_group );
		if ( $is_visible === false ) {
			return $choices;
		}

		// Get the Case Fields for this ACF Field.
		$case_fields = $this->civicrm->case_field->get_for_acf_field( $field );

		// Get the Custom Fields for CiviCRM Cases.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Case', '' );

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
		if ( empty( $case_fields ) && empty( $filtered_fields ) ) {
			return $choices;
		}

		// Build Case Field choices array for dropdown.
		if ( ! empty( $case_fields ) ) {
			$case_fields_label = esc_attr__( 'Case Fields', 'civicrm-wp-profile-sync' );
			foreach ( $case_fields as $case_field ) {
				$choices[ $case_fields_label ][ $this->case_field_prefix . $case_field['name'] ] = $case_field['title'];
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
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/case/civicrm_field/choices', $choices );

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

		// Get the public Fields on the Entity for this Field Type.
		$fields_for_entity = $this->civicrm->case_field->data_get( $field['type'], 'public' );

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Case', '' );

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
		if ( empty( $fields_for_entity ) && empty( $filtered_fields ) ) {
			return $setting_field;
		}

		// Get the Setting Field.
		$setting_field = $this->acf_field_get( $filtered_fields, $fields_for_entity );

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

		// Get the public Fields on the Entity for this Field Type.
		$fields_for_entity = $this->civicrm->case_field->data_get( $field['type'], 'public' );

		// Prepend the ones that are needed in ACFE Forms (i.e. Subject and Details).
		foreach ( $this->civicrm->case_field->bypass_fields as $name => $field_type ) {
			if ( $field_type == $field['type'] ) {
				array_unshift( $fields_for_entity, $this->civicrm->case_field->get_by_name( $name ) );
			}
		}

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Case', '' );

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
		if ( empty( $fields_for_entity ) && empty( $filtered_fields ) ) {
			return $choices;
		}

		// Build Case Field choices array for dropdown.
		if ( ! empty( $fields_for_entity ) ) {
			$case_fields_label = esc_attr__( 'Case Fields', 'civicrm-wp-profile-sync' );
			foreach ( $fields_for_entity as $case_field ) {
				$choices[ $case_fields_label ][ $this->case_field_prefix . $case_field['name'] ] = $case_field['title'];
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
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/case/civicrm_field/choices', $choices );

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

		// Get all Case Types.
		$case_types = $this->civicrm->case_type->get_all();

		// Bail if there are none.
		if ( empty( $case_types ) ) {
			return $entities;
		}

		// Add Option Group and add entries for each Case Type.
		$case_types_title = esc_attr( __( 'Case Types', 'civicrm-wp-profile-sync' ) );
		$entities[ $case_types_title ] = [];
		foreach ( $case_types as $id => $label ) {
			$entities[ $case_types_title ][ $this->identifier . '-' . $id ] = $label;
		}

		// --<
		return $entities;

	}



	/**
	 * Listen for queries from the Field Group class.
	 *
	 * This method responds with a Boolean if it detects that this Field Group
	 * maps to a Case Type.
	 *
	 * @since 0.5
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

		// Bail if this is not a Case Field Group.
		$is_case_field_group = $this->is_case_field_group( $field_group );
		if ( $is_case_field_group === false ) {
			return $mapped;
		}

		// --<
		return true;

	}



	/**
	 * Listen for queries from the Custom Field class.
	 *
	 * @since 0.5
	 *
	 * @param array $custom_fields The existing Custom Fields.
	 * @param array $field_group The array of ACF Field Group data.
	 * @return array $custom_fields The populated array of CiviCRM Custom Fields params.
	 */
	public function query_custom_fields( $custom_fields, $field_group ) {

		// Bail if this is not a Case Field Group.
		$is_visible = $this->is_case_field_group( $field_group );
		if ( $is_visible === false ) {
			return $custom_fields;
		}

		// Get the Custom Fields for CiviCRM Cases.
		$entity_custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Case', '' );

		// Maybe merge with passed in array.
		if ( ! empty( $entity_custom_fields ) ) {
			$custom_fields = array_merge( $custom_fields, $entity_custom_fields );
		}

		// --<
		return $custom_fields;

	}



	/**
	 * Listen for queries from the Custom Field class.
	 *
	 * This method responds with an array of "Post IDs" if it detects that the
	 * set of Custom Fields maps to a Case.
	 *
	 * @since 0.5
	 *
	 * @param array|bool $post_ids The existing "Post IDs".
	 * @param array $args The array of CiviCRM Custom Fields params.
	 * @return array|bool $post_id The mapped "Post IDs", or false if not mapped.
	 */
	public function query_post_id( $post_ids, $args ) {

		// Init Case ID.
		$case_id = false;

		// Let's tease out the context from the Custom Field data.
		foreach ( $args['custom_fields'] as $field ) {

			// Skip if it is not attached to a Case.
			if ( $field['entity_table'] != 'civicrm_case' ) {
				continue;
			}

			// Grab the Case.
			$case_id = $field['entity_id'];

			// We can bail now that we know.
			break;

		}

		// Bail if there's no Case ID.
		if ( $case_id === false ) {
			return $post_ids;
		}

		// Grab Case.
		$case = $this->get_by_id( $case_id );
		if ( $case === false ) {
			return $post_ids;
		}

		// Bail if this Case's Case Type is not mapped.
		$post_type = $this->is_mapped( $case, 'create' );
		if ( $post_type === false ) {
			return $post_ids;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Init Case Post IDs.
		$case_post_ids = [];

		// Get array of IDs for this Post Type.
		$ids = $this->acf_loader->post->get_by_case_id( $case_id, $post_type );

		// Add to Case Post IDs array.
		foreach ( $ids as $id ) {

			// Exclude "reverse" edits when a Post is the originator.
			if ( $entity['entity'] !== 'post' || $id != $entity['id'] ) {
				$case_post_ids[] = $id;
			}

		}

		// Bail if no "Post IDs" are found.
		if ( empty( $case_post_ids ) ) {
			return $post_ids;
		}

		// Add found "Post IDs" to return array.
		if ( is_array( $post_ids ) ) {
			$post_ids = array_merge( $post_ids, $case_post_ids );
		} else {
			$post_ids = $case_post_ids;
		}

		// --<
		return $post_ids;

	}



	/**
	 * Listen for queries from the Attachment class.
	 *
	 * This method responds with an "Entity Table" if it detects that the ACF
	 * Field Group maps to a Case.
	 *
	 * @since 0.5.2
	 *
	 * @param array $entity_tables The existing "Entity Tables".
	 * @param array $field_group The array of ACF Field Group params.
	 * @return array $entity_tables The mapped "Entity Tables".
	 */
	public function query_entity_table( $entity_tables, $field_group ) {

		// Bail if this is not a Case Field Group.
		$is_visible = $this->is_case_field_group( $field_group );
		if ( $is_visible === false ) {
			return $entity_tables;
		}

		// Append our "Entity Table" if not already present.
		if ( ! array_key_exists( 'civicrm_case', $entity_tables ) ) {
			$entity_tables['civicrm_case'] = __( 'Case', 'civicrm-wp-profile-sync' );
		}

		// --<
		return $entity_tables;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if a Field Group has been mapped to one or more Case Post Types.
	 *
	 * @since 0.5
	 *
	 * @param array $field_group The Field Group to check.
	 * @return array|bool The array of Post Types if the Field Group has been mapped, or false otherwise.
	 */
	public function is_case_field_group( $field_group ) {

		// Bail if there's no Field Group ID.
		if ( empty( $field_group['ID'] ) ) {
			return false;
		}

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache[ $field_group['ID'] ] ) ) {
			return $pseudocache[ $field_group['ID'] ];
		}

		// Assume not a Case Field Group.
		$is_case_field_group = false;

		// If Location Rules exist.
		if ( ! empty( $field_group['location'] ) ) {

			// Get mapped Post Types.
			$post_types = $this->acf_loader->mapping->mappings_for_case_types_get();

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
						$is_case_field_group[] = $post_type;
					}

				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $field_group['ID'] ] ) ) {
			$pseudocache[ $field_group['ID'] ] = $is_case_field_group;
		}

		// --<
		return $is_case_field_group;

	}



} // Class ends.



