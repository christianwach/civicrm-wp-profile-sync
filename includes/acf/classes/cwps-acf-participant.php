<?php
/**
 * CiviCRM Participant Class.
 *
 * Handles CiviCRM Participant functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Participant Class.
 *
 * A class that encapsulates CiviCRM Participant functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Participant {

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
	 * ACF object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The ACF object.
	 */
	public $acf;

	/**
	 * Custom Post Type object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $cpt The Custom Post Type object.
	 */
	public $cpt;

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
	public $identifier = 'participant';

	/**
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Participant Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $participant_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $participant_field_prefix = 'caiparticipant_';

	/**
	 * Participant Fields that need duplicating without the "participant_" prefix.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $copy_params The Participant Fields that need duplicating.
	 */
	public $copy_params = [
		'role_id',
		'register_date',
		'status_id',
		'source',
		'registered_by_id',
		'campaign_id',
	];



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
		$this->acf = $this->acf_loader->acf;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.5
		 */
		do_action( 'cwps/acf/civicrm/participant/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Include Custom Post Type class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-participant-cpt.php';

	}



	/**
	 * Set up objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Init Custom Post Type objects.
		$this->cpt = new CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Listen for events from Manual Sync that require Participant updates.
		add_action( 'cwps/acf/admin/post-to-participant-role/sync', [ $this, 'post_sync' ], 10 );
		add_action( 'cwps/acf/admin/post-to-participant-role/acf_fields/sync', [ $this, 'acf_fields_sync' ], 10 );

		// Listen for queries from our Field Group class.
		add_filter( 'cwps/acf/query_field_group_mapped', [ $this, 'query_field_group_mapped' ], 10, 2 );

		// Listen for queries from our Custom Field class.
		add_filter( 'cwps/acf/query_custom_fields', [ $this, 'query_custom_fields' ], 10, 2 );

		// Listen for queries from the Custom Field class.
		add_filter( 'cwps/acf/query_post_id', [ $this, 'query_post_id' ], 10, 2 );

		// Listen for queries from the Attachment class.
		add_filter( 'cwps/acf/query_entity_table', [ $this, 'query_entity_table' ], 10, 2 );

		// Maybe add a link to action links on the Participant Posts list table.
		add_action( 'post_row_actions', [ $this, 'menu_item_add_to_row_actions' ], 10, 2 );

		// Maybe add a Menu Item to CiviCRM Admin Utilities menu.
		add_action( 'civicrm_admin_utilities_menu_top', [ $this, 'menu_item_add_to_cau' ], 10, 2 );

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/acf/field/query_setting_choices', [ $this, 'query_setting_choices' ], 30, 3 );

		// Listen for queries from the ACF Bypass class.
		//add_filter( 'cwps/acf/bypass/query_settings_field', [ $this, 'query_bypass_settings_field' ], 20, 4 );
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

		// Listen for queries from the ACF Bypass Location Rule class.
		add_filter( 'cwps/acf/bypass/location/query_entities', [ $this, 'query_bypass_entities' ], 20, 2 );

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

		// Listen for events from our Mapper that require Participant updates.
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
	 * Update a CiviCRM Participant when a WordPress Post is synced.
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
	 * Update a CiviCRM Participant when a WordPress Post has been updated.
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
		if ( ! $this->acf_loader->post_type->is_mapped_to_participant_role( $post->post_type ) ) {
			$this->do_not_sync = true;
			return;
		}

		/*
		 * We can't do anything more here because we can't create a Participant
		 * from Post data alone. There is mandatory API data that only arrives
		 * via ACF Fields. We therefore don't have the "do_action" here either.
		 */

	}



	/**
	 * Update a CiviCRM Participant when the ACF Fields on a WordPress Post are synced.
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
	 * Update a CiviCRM Participant when the ACF Fields on a WordPress Post have been updated.
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
		$entity = $this->acf->field->entity_type_get( $args['post_id'] );
		if ( $entity !== 'post' ) {
			return;
		}

		// We need the Post itself.
		$post = get_post( $args['post_id'] );

		// Bail if this is a revision.
		if ( $post->post_type == 'revision' ) {
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

		// Get the Participant ID.
		$participant_id = $this->acf_loader->post->participant_id_get( $post->ID );

		// Does this Post have a Participant ID?
		if ( $participant_id === false ) {

			// No - create a Participant.
			$participant = $this->create_from_fields( $fields, $post, $post->ID );

			// Store Participant ID if successful.
			if ( $participant !== false ) {
				$this->acf_loader->post->participant_id_set( $post->ID, $participant['id'] );
				$participant_id = $participant['id'];
			}

		} else {

			// Yes - update the Participant.
			$participant = $this->update_from_fields( $participant_id, $fields, $post, $post->ID );

		}

		// Add our data to the params.
		$args['participant_id'] = $participant_id;
		$args['participant'] = $participant;
		$args['post'] = $post;
		$args['fields'] = $fields;

		/**
		 * Broadcast that a Participant has been updated when ACF Fields were saved.
		 *
		 * Used internally by:
		 *
		 * Participant Fields to maintain sync with:
		 *
		 * * The ACF "Registered Date" Field
		 *
		 * @since 0.5
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/acf/participant/acf_fields_saved', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get "chunked" CiviCRM API Participant data.
	 *
	 * This method is used internally by the "Manual Sync" admin page.
	 *
	 * @since 0.5
	 *
	 * @param integer $offset The numeric offset for the query.
	 * @param integer $limit The numeric limit for the query.
	 * @return array $result The array of Participant data from the CiviCRM API.
	 */
	public function participants_chunked_data_get( $offset, $limit ) {

		// Params to query Participants.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => $limit,
				'offset' => $offset,
			],
		];

		// Call API.
		$result = civicrm_api( 'Participant', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'offset' => $offset,
				'limit' => $limit,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $result;
		}

		// Copy various "participant_*" params to "*" params. Grr.
		if ( ! empty( $result['values'] ) ) {
			foreach ( $result['values'] as &$item ) {
				foreach ( $this->copy_params as $copy ) {
					if ( isset( $item[ 'participant_' . $copy ] ) ) {
						$item[ $copy ] = $item[ 'participant_' . $copy ];
					}
				}
			}
		}

		// --<
		return $result;

	}



	/**
	 * Get "chunked" CiviCRM API Participant data for a given Participant Role.
	 *
	 * This method is used internally by the "Manual Sync" admin page.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the CiviCRM Participant Role.
	 * @param integer $offset The numeric offset for the query.
	 * @param integer $limit The numeric limit for the query.
	 * @return array $result The array of Participant data from the CiviCRM API.
	 */
	public function participants_by_role_chunked_data_get( $participant_role_id, $offset, $limit ) {

		// Sanity check.
		if ( empty( $participant_role_id ) ) {
			return 0;
		}

		// Params to query Participants.
		$params = [
			'version' => 3,
			'participant_role_id' => $participant_role_id,
			'options' => [
				'limit' => $limit,
				'offset' => $offset,
			],
		];

		// Call API.
		$result = civicrm_api( 'Participant', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'participant_role_id' => $participant_role_id,
				'offset' => $offset,
				'limit' => $limit,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $result;
		}

		// Copy various "participant_*" params to "*" params. Grr.
		if ( ! empty( $result['values'] ) ) {
			foreach ( $result['values'] as &$item ) {
				foreach ( $this->copy_params as $copy ) {
					if ( isset( $item[ 'participant_' . $copy ] ) ) {
						$item[ $copy ] = $item[ 'participant_' . $copy ];
					}
				}
			}
		}

		// --<
		return $result;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check whether a Participant's Participant Role is mapped to a Post Type.
	 *
	 * The Mapper makes use of the boolean return to bail early.
	 *
	 * @see CiviCRM_Profile_Sync_ACF_Mapper::participant_pre_create()
	 * @see CiviCRM_Profile_Sync_ACF_Mapper::participant_pre_edit()
	 *
	 * @since 0.5
	 *
	 * @param array|obj $participant The Participant data.
	 * @param string $create_post Create a mapped Post if missing. Either 'create' or 'skip'.
	 * @return string|bool $is_mapped The Post Type if the Participant is mapped, false otherwise.
	 */
	public function is_mapped( $participant, $create_post = 'skip' ) {

		// Init return.
		$is_mapped = [];

		// Maybe cast Participant data as object.
		if ( is_array( $participant ) ) {
			$participant = (object) $participant;
		}

		// Return early if missing Participant Role(s).
		if ( empty( $participant->role_id ) ) {
			return false;
		}

		// Convert Participant Role(s) to array.
		$participant_role_ids = [];
		if ( is_array( $participant->role_id ) ) {
			$participant_role_ids = $participant->role_id;
		} else {
			if ( false !== strpos( $participant->role_id, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
				$participant_role_ids = CRM_Utils_Array::explodePadded( $participant->role_id );
			} else {
				$participant_role_ids = [ $participant->role_id ];
			}
		}

		// Inspect each Participant Role ID in turn.
		foreach ( $participant_role_ids as $participant_role_id ) {

			// Get the Post Type mapped to this Participant Role.
			$post_type = $this->civicrm->participant_role->is_mapped_to_post_type( $participant_role_id );

			// Skip if this Participant Role is not mapped.
			if ( $post_type === false ) {
				continue;
			}

			// Assign Post Type.
			$is_mapped[] = $post_type;

			// Skip if there's no Participant ID.
			if ( empty( $participant->id ) ) {
				continue;
			}

			// Get the associated Post ID.
			$post_id = $this->acf_loader->post->get_by_participant_id( $participant->id, $post_type );

			// Create the Post if it's missing.
			if ( $post_id === false && $create_post === 'create' ) {

				// Prevent recursion and the resulting unexpected Post creation.
				if ( ! doing_action( 'cwps/acf/post/participant/sync' ) ) {

					// Get full Participant data.
					$participant_data = $this->get_by_id( $participant->id );

					// Remove WordPress callbacks to prevent recursion.
					$this->acf_loader->mapper->hooks_wordpress_remove();
					$this->acf_loader->mapper->hooks_civicrm_remove();

					// Let's make an array of params.
					$args = [
						'op' => 'sync',
						'objectName' => 'Participant',
						'objectId' => $participant_data['id'],
						'objectRef' => (object) $participant_data,
					];

					// Sync this Participant to the Post Type.
					$this->acf_loader->post->participant_sync_to_post( $args, $post_type );

					// Reinstate WordPress callbacks.
					$this->acf_loader->mapper->hooks_wordpress_add();
					$this->acf_loader->mapper->hooks_civicrm_add();

				}

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
	 * Check if a Participant is mapped to a Post of a particular Post Type.
	 *
	 * @since 0.5
	 *
	 * @param array|obj $participant The Participant data.
	 * @param string $post_type The WordPress Post Type.
	 * @return integer|bool $is_mapped The ID of the WordPress Post if the Participant is mapped, false otherwise.
	 */
	public function is_mapped_to_post( $participant, $post_type = 'any' ) {

		// TODO: Pseudo-cache?

		// Assume not.
		$is_mapped = false;

		// Maybe cast Participant data as object.
		if ( is_array( $participant ) ) {
			$participant = (object) $participant;
		}

		// Bail if this Participant's Participant Role is not mapped.
		$post_types = $this->is_mapped( $participant );
		if ( $post_types === false ) {
			return false;
		}

		// Grab Participant ID.
		if ( isset( $participant->id ) ) {
			$participant_id = $participant->id;
		}

		// Bail if no Participant ID is found.
		if ( empty( $participant_id ) ) {
			return $is_mapped;
		}

		// Find the Post ID of this Post Type that this Participant is synced with.
		$post_ids = $this->acf_loader->post->get_by_participant_id( $participant_id, $post_type );

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
	 * Get the CiviCRM Participant data for a given ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_id The numeric ID of the CiviCRM Participant to query.
	 * @return array|bool $participant_data An array of Participant data, or false on failure.
	 */
	public function get_by_id( $participant_id ) {

		// Init return.
		$participant_data = false;

		// Bail if we have no Participant ID.
		if ( empty( $participant_id ) ) {
			return $participant_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $participant_data;
		}

		// Define params to get queried Participant.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $participant_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Participant', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $participant_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $participant_data;
		}

		// The result set should contain only one item.
		$participant_data = array_pop( $result['values'] );

		// Copy various "participant_*" params to "*" params. Grr.
		foreach ( $this->copy_params as $copy ) {
			if ( isset( $participant_data[ 'participant_' . $copy ] ) ) {
				$participant_data[ $copy ] = $participant_data[ 'participant_' . $copy ];
			}
		}

		// --<
		return $participant_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Participant for a given set of data.
	 *
	 * @since 0.5
	 *
	 * @param array $participant The CiviCRM Participant data.
	 * @return array|bool $participant_data The array Participant data from the CiviCRM API, or false on failure.
	 */
	public function create( $participant ) {

		// Init as failure.
		$participant_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $participant_data;
		}

		// Build params to create Participant.
		$params = [
			'version' => 3,
		] + $participant;

		/*
		 * Minimum array to create a Participant:
		 *
		 * $params = [
		 *   'version' => 3,
		 *   'participant_role_id' => 56,
		 *   'source_contact_id' => "user_contact_id",
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
		 * refer to different Entities (e.g. "Participant" and "Student").
		 *
		 * Nice.
		 */

		// Call the API.
		$result = civicrm_api( 'Participant', 'create', $params );

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
			return $participant_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $participant_data;
		}

		// The result set should contain only one item.
		$participant_data = array_pop( $result['values'] );

		// --<
		return $participant_data;

	}



	/**
	 * Update a CiviCRM Participant with a given set of data.
	 *
	 * @since 0.5
	 *
	 * @param array $participant The CiviCRM Participant data.
	 * @return array|bool $participant_data The array Participant data from the CiviCRM API, or false on failure.
	 */
	public function update( $participant ) {

		// Log and bail if there's no Participant ID.
		if ( empty( $participant['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numerical ID must be present to update a Participant.', 'civicrm-wp-profile-sync' ),
				'participant' => $participant,
				'backtrace' => $trace,
			], true ) );
			return $participant_data;
		}

		// Pass through.
		return $this->create( $participant );

	}



	/**
	 * Fill out the missing data for a CiviCRM Participant.
	 *
	 * @since 0.5
	 *
	 * @param object $participant The CiviCRM Participant data object.
	 * @return object $participant The backfilled CiviCRM Participant data.
	 */
	public function backfill( $participant ) {

		// Get the full Participant data.
		$participant_full = $this->get_by_id( $participant->id );
		if ( $participant_full === false ) {
			return $participant;
		}

		// Fill out missing Participant data.
		foreach ( $participant_full as $key => $item ) {
			if ( empty( $participant->$key ) && ! empty( $item ) ) {
				$participant->$key = $item;
			}
		}

		// --<
		return $participant;

	}



	// -------------------------------------------------------------------------



	/**
	 * Prepare the required CiviCRM Participant data from a set of ACF Fields.
	 *
	 * This method combines all Participant Fields that the CiviCRM API accepts as
	 * params for ( 'Participant', 'create' ) along with the linked Custom Fields.
	 *
	 * The CiviCRM API will update Custom Fields as long as they are passed to
	 * ( 'Participant', 'create' ) in the correct format. This is of the form:
	 * 'custom_N' where N is the ID of the Custom Field.
	 *
	 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Base
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_id The numeric ID of the Participant.
	 * @param array $fields The ACF Field data.
	 * @param WP_Post $post The WordPress Post object.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $participant_data The CiviCRM Participant data.
	 */
	public function prepare_from_fields( $participant_id, $fields, $post, $post_id = null ) {

		// Init data for Fields.
		$participant_data = [];

		// Bail if we have no Field data to save.
		if ( empty( $fields ) ) {
			return $participant_data;
		}

		// Always assign Participant Role ID.
		$participant_data['role_id'] = $this->civicrm->participant_role->id_get_for_post_type( $post->post_type );

		// Loop through the Field data.
		foreach ( $fields as $selector => $value ) {

			// Get the Field settings.
			$settings = get_field_object( $selector, $post_id );

			// Get the CiviCRM Custom Field and Participant Field.
			$custom_field_id = $this->civicrm->custom_field->custom_field_id_get( $settings );
			$participant_field_name = $this->participant_field_name_get( $settings );

			// Do we have a synced Custom Field or Participant Field?
			if ( ! empty( $custom_field_id ) || ! empty( $participant_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . $custom_field_id;

				} else {

					// The Participant Field code is the setting.
					$code = $participant_field_name;

					// "Contact Existing/New" Field has to be handled differently.
					if ( $code == 'contact_id' ) {

						// Maybe create a Contact.
						$contact_id = $this->prepare_contact_from_field( $selector, $value, $settings, $post_id );
						if ( $contact_id === false ) {
							continue;
						}

						// Overwrite code and value.
						$code = 'contact_id';
						$value = (int) $contact_id;

					}

					// "Event Group" Field has to be handled differently.
					if ( $code == 'event_id' ) {

						// Get Event ID from Field.
						$event_id = $this->acf->field_type->event_group->prepare_output( $value );
						if ( empty( $event_id ) ) {
							continue;
						}

						// Overwrite code and value.
						$code = 'event_id';
						$value = (int) $event_id;

					}

				}

				// Build args for value conversion.
				$args = [
					'identifier' => $this->identifier,
					'entity_id' => $participant_id,
					'custom_field_id' => $custom_field_id,
					'field_name' => $participant_field_name,
					'selector' => $selector,
					'post_id' => $post_id,
				];

				// Parse value by Field Type.
				$value = $this->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings, $args );

				// Some Participant Fields cannot be empty.
				$cannot_be_empty = [
					'contact_id',
					'event_id',
				];

				// Add it to the Field data.
				if ( in_array( $code, $cannot_be_empty ) && empty( $value ) ) {
					// Skip.
				} else {
					$participant_data[ $code ] = $value;
				}

			}

		}

		// --<
		return $participant_data;

	}



	/**
	 * Creates a CiviCRM Contact with data from the "Contact Group" ACF Field.
	 *
	 * @since 0.5
	 *
	 * @param string $selector The ACF Field selector.
	 * @param array $value The ACF Field values.
	 * @param array $settings The ACF Field settings.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $contact The CiviCRM Contact data, or false on failure.
	 */
	public function prepare_contact_from_field( $selector, $value, $settings, $post_id ) {

		// Collate Contact data from Field value.
		$contact_data = $this->acf->field_type->contact_group->prepare_output( $value );
		if ( $contact_data === false ) {
			return false;
		}

		// Return early if we already have a Contact ID.
		if ( is_integer( $contact_data ) ) {
			return $contact_data;
		}

		// Remove all internal CiviCRM hooks.
		$this->acf_loader->mapper->hooks_civicrm_remove();

		// Create a Contact with our data.
		$contact = $this->plugin->civicrm->contact->create( $contact_data );

		// Restore all internal CiviCRM hooks.
		$this->acf_loader->mapper->hooks_civicrm_add();

		// Bail if something went wrong.
		if ( $contact === false ) {
			return false;
		}

		// Rebuild Field data.
		$data = $this->acf->field_type->contact_group->prepare_input( $contact['id'] );

		// Reverse sync the Field data.
		$this->acf->field->value_update( $selector, $data, $post_id );

		// --<
		return (int) $contact['id'];

	}



	/**
	 * Create a CiviCRM Participant with data from ACF Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $fields The ACF Field data.
	 * @param WP_Post $post The WordPress Post object.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $participant The CiviCRM Participant data, or false on failure.
	 */
	public function create_from_fields( $fields, $post, $post_id = null ) {

		// Build required data.
		$participant_data = $this->prepare_from_fields( null, $fields, $post, $post_id );

		// Update the Participant.
		$participant = $this->create( $participant_data );

		// --<
		return $participant;

	}



	/**
	 * Update a CiviCRM Participant with data from ACF Fields.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_id The numeric ID of the Participant.
	 * @param array $fields The ACF Field data.
	 * @param WP_Post $post The WordPress Post object.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $participant The CiviCRM Participant data, or false on failure.
	 */
	public function update_from_fields( $participant_id, $fields, $post, $post_id = null ) {

		// Build required data.
		$participant_data = $this->prepare_from_fields( $participant_id, $fields, $post, $post_id );

		// Add the Participant ID.
		$participant_data['id'] = $participant_id;

		// Update the Participant.
		$participant = $this->update( $participant_data );

		// --<
		return $participant;

	}



	// -------------------------------------------------------------------------



	/**
	 * Return the "CiviCRM Field" ACF Settings Field.
	 *
	 * @since 0.5
	 *
	 * @param array $custom_fields The Custom Fields to populate the ACF Field with.
	 * @param array $participant_fields The Participant Fields to populate the ACF Field with.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $custom_fields = [], $participant_fields = [] ) {

		// Build choices array for dropdown.
		$choices = [];

		// Build Participant Field choices array for dropdown.
		$participant_fields_label = esc_attr__( 'Participant Fields', 'civicrm-wp-profile-sync' );
		foreach ( $participant_fields as $participant_field ) {
			$choices[ $participant_fields_label ][ $this->participant_field_prefix . $participant_field['name'] ] = $participant_field['title'];
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
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/participant/civicrm_field/choices', $choices );

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
			'parent' => $this->acf->field_group->placeholder_group_get(),
			'choices' => $choices,
		];

		// --<
		return $field;

	}



	/**
	 * Get the mapped Participant Field name if present.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing Field data array.
	 * @return string|bool $participant_field_name The name of the Participant Field, or false if none.
	 */
	public function participant_field_name_get( $field ) {

		// Init return.
		$participant_field_name = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Set the mapped Participant Field name if present.
		if ( isset( $field[ $acf_field_key ] ) ) {
			if ( false !== strpos( $field[ $acf_field_key ], $this->participant_field_prefix ) ) {
				$participant_field_name = (string) str_replace( $this->participant_field_prefix, '', $field[ $acf_field_key ] );
			}
		}

		/**
		 * Filter the Participant Field name.
		 *
		 * @since 0.5
		 *
		 * @param integer $participant_field_name The existing Participant Field name.
		 * @param array $field The array of ACF Field data.
		 */
		$participant_field_name = apply_filters( 'cwps/acf/civicrm/participant/participant_field/name', $participant_field_name, $field );

		// --<
		return $participant_field_name;

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

		// Pass if this is not a Participant Field Group.
		$is_visible = $this->is_participant_field_group( $field_group );
		if ( $is_visible === false ) {
			return $choices;
		}

		// Get the Participant Fields for this ACF Field.
		$participant_fields = $this->civicrm->participant_field->get_for_acf_field( $field );

		// Get the Custom Fields for CiviCRM Participants.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Participant', '' );

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
		if ( empty( $participant_fields ) && empty( $filtered_fields ) ) {
			return $choices;
		}

		// Build Participant Field choices array for dropdown.
		if ( ! empty( $participant_fields ) ) {
			$participant_fields_label = esc_attr__( 'Participant Fields', 'civicrm-wp-profile-sync' );
			foreach ( $participant_fields as $participant_field ) {
				$choices[ $participant_fields_label ][ $this->participant_field_prefix . $participant_field['name'] ] = $participant_field['title'];
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
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/participant/civicrm_field/choices', $choices );

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
		$fields_for_entity = $this->civicrm->participant_field->data_get( $field['type'], 'public' );

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Participant', '' );

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
		$fields_for_entity = $this->civicrm->participant_field->data_get( $field['type'], 'public' );

		// Get the Custom Fields for this Entity.
		$custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Participant', '' );

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

		// Build Participant Field choices array for dropdown.
		if ( ! empty( $fields_for_entity ) ) {
			$participant_fields_label = esc_attr__( 'Participant Fields', 'civicrm-wp-profile-sync' );
			foreach ( $fields_for_entity as $participant_field ) {
				$choices[ $participant_fields_label ][ $this->participant_field_prefix . $participant_field['name'] ] = $participant_field['title'];
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
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/participant/civicrm_field/choices', $choices );

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

		// Get all Participant Roles.
		$participant_roles = $this->civicrm->participant_role->get_all();

		// Bail if there are none.
		if ( empty( $participant_roles ) ) {
			return $entities;
		}

		// Add Option Group and add entries for each Participant Role.
		$participant_roles_title = esc_attr( __( 'Participant Roles', 'civicrm-wp-profile-sync' ) );
		$entities[ $participant_roles_title ] = [];
		foreach ( $participant_roles as $participant_role ) {
			$entities[ $participant_roles_title ][ $this->identifier . '-' . $participant_role['value'] ] = $participant_role['label'];
		}

		// --<
		return $entities;

	}



	/**
	 * Listen for queries from the Field Group class.
	 *
	 * This method responds with a Boolean if it detects that this Field Group
	 * maps to a Participant Role.
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

		// Bail if this is not a Participant Field Group.
		$is_participant_field_group = $this->is_participant_field_group( $field_group );
		if ( $is_participant_field_group === false ) {
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

		// Bail if this is not a Participant Field Group.
		$is_visible = $this->is_participant_field_group( $field_group );
		if ( $is_visible === false ) {
			return $custom_fields;
		}

		// Get the Custom Fields for CiviCRM Participants.
		$entity_custom_fields = $this->plugin->civicrm->custom_field->get_for_entity_type( 'Participant', '' );

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
	 * set of Custom Fields maps to a Participant.
	 *
	 * @since 0.5
	 *
	 * @param array|bool $post_ids The existing "Post IDs".
	 * @param array $args The array of CiviCRM Custom Fields params.
	 * @return array|bool $post_id The mapped "Post IDs", or false if not mapped.
	 */
	public function query_post_id( $post_ids, $args ) {

		// Init Participant ID.
		$participant_id = false;

		// Let's tease out the context from the Custom Field data.
		foreach ( $args['custom_fields'] as $field ) {

			// Skip if it is not attached to a Participant.
			if ( $field['entity_table'] != 'civicrm_participant' ) {
				continue;
			}

			// Grab the Participant.
			$participant_id = $field['entity_id'];

			// We can bail now that we know.
			break;

		}

		// Bail if there's no Participant ID.
		if ( $participant_id === false ) {
			return $post_ids;
		}

		// Grab Participant.
		$participant = $this->get_by_id( $participant_id );
		if ( $participant === false ) {
			return $post_ids;
		}

		// Bail if this Participant's Participant Role is not mapped.
		$post_types = $this->is_mapped( $participant, 'create' );
		if ( $post_types === false ) {
			return $post_ids;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Init Participant Post IDs.
		$participant_post_ids = [];

		// Get the Post IDs that this Contact is mapped to.
		foreach ( $post_types as $post_type ) {

			// Get array of IDs for this Post Type.
			$ids = $this->acf_loader->post->get_by_participant_id( $participant_id, $post_type );

			// Bail if no IDs are found.
			if ( empty( $ids ) ) {
				return $post_ids;
			}

			// Add to Participant Post IDs array.
			foreach ( $ids as $id ) {

				// Exclude "reverse" edits when a Post is the originator.
				if ( $entity['entity'] !== 'post' || $id != $entity['id'] ) {
					$participant_post_ids[] = $id;
				}

			}

		}

		// Bail if no "Post IDs" are found.
		if ( empty( $participant_post_ids ) ) {
			return $post_ids;
		}

		// Add found "Post IDs" to return array.
		if ( is_array( $post_ids ) ) {
			$post_ids = array_merge( $post_ids, $participant_post_ids );
		} else {
			$post_ids = $participant_post_ids;
		}

		// --<
		return $post_ids;

	}



	/**
	 * Listen for queries from the Attachment class.
	 *
	 * This method responds with an "Entity Table" if it detects that the ACF
	 * Field Group maps to a Participant.
	 *
	 * @since 0.5.2
	 *
	 * @param array $entity_tables The existing "Entity Tables".
	 * @param array $field_group The array of ACF Field Group params.
	 * @return array $entity_tables The mapped "Entity Tables".
	 */
	public function query_entity_table( $entity_tables, $field_group ) {

		// Bail if this is not a Participant Field Group.
		$is_visible = $this->is_participant_field_group( $field_group );
		if ( $is_visible === false ) {
			return $entity_tables;
		}

		// Append our "Entity Table" if not already present.
		if ( ! array_key_exists( 'civicrm_participant', $entity_tables ) ) {
			$entity_tables['civicrm_participant'] = __( 'Participant', 'civicrm-wp-profile-sync' );
		}

		// --<
		return $entity_tables;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if a Field Group has been mapped to one or more Participant Post Types.
	 *
	 * @since 0.5
	 *
	 * @param array $field_group The Field Group to check.
	 * @return array|bool The array of Post Types if the Field Group has been mapped, or false otherwise.
	 */
	public function is_participant_field_group( $field_group ) {

		// Bail if there's no Field Group ID.
		if ( empty( $field_group['ID'] ) ) {
			return false;
		}

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache[ $field_group['ID'] ] ) ) {
			return $pseudocache[ $field_group['ID'] ];
		}

		// Assume not a Participant Field Group.
		$is_participant_field_group = false;

		// If Location Rules exist.
		if ( ! empty( $field_group['location'] ) ) {

			// Get mapped Post Types.
			$post_types = $this->acf_loader->mapping->mappings_for_participant_roles_get();

			// Bail if there are no mappings.
			if ( ! empty( $post_types ) ) {

				// Loop through them.
				foreach ( $post_types as $post_type ) {

					// Define params to test for a mapped Post Type.
					$params = [
						'post_type' => $post_type,
					];

					// Do the check.
					$is_visible = $this->acf->field_group->is_visible( $field_group, $params );

					// If it is, then add to return array.
					if ( $is_visible ) {
						$is_participant_field_group[] = $post_type;
					}

				}

			}

		}

		/**
		 * Filter the Post Types mapped to a Field Group.
		 *
		 * @since 0.5
		 *
		 * @param array|bool $is_participant_field_group The array of Post Types, false otherwise.
		 * @param array $field_group The ACF Field Group data array.
		 */
		$is_participant_field_group = apply_filters(
			'cwps/acf/civicrm/participant/is_field_group',
			$is_participant_field_group, $field_group
		);

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $field_group['ID'] ] ) ) {
			$pseudocache[ $field_group['ID'] ] = $is_participant_field_group;
		}

		// --<
		return $is_participant_field_group;

	}



	// -------------------------------------------------------------------------



	/**
	 * Add a link to action links on the Pages and Posts list tables.
	 *
	 * @since 0.5
	 *
	 * @param array $actions The array of row action links.
	 * @param WP_Post $post The WordPress Post object.
	 */
	public function menu_item_add_to_row_actions( $actions, $post ) {

		// Bail if there's no Post object.
		if ( empty( $post ) ) {
			return $actions;
		}

		// Do we need to know?
		if ( is_post_type_hierarchical( $post->post_type ) ) {
		}

		// Get Participant ID.
		$participant_id = $this->acf_loader->post->participant_id_get( $post->ID );
		if ( $participant_id === false ) {
			return $actions;
		}

		// Get Participant.
		$participant = $this->get_by_id( $participant_id );
		if ( $participant === false ) {
			return $actions;
		}

		// Get Contact ID.
		$contact_id = $participant['contact_id'];
		if ( $contact_id === false ) {
			return $actions;
		}

		// Check permission to view this Contact.
		if ( ! $this->civicrm->contact->user_can_view( $contact_id ) ) {
			return $actions;
		}

		// Get the "View" URL for this Participant.
		$query_base = 'reset=1&id=' . $participant_id . '&cid=' . $contact_id;
		$view_query = $query_base . '&action=view&context=participant';
		$view_url = $this->plugin->civicrm->get_link( 'civicrm/contact/view/participant', $view_query );

		// Add link to actions.
		$actions['civicrm'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $view_url ),
			esc_html__( 'CiviCRM', 'civicrm-wp-profile-sync' )
		);

		// --<
		return $actions;

	}



	/**
	 * Add a add a Menu Item to the CiviCRM Admin Utilities menu.
	 *
	 * @since 0.5
	 *
	 * @param string $id The menu parent ID.
	 * @param array $components The active CiviCRM Conponents.
	 */
	public function menu_item_add_to_cau( $id, $components ) {

		// Access WordPress admin bar.
		global $wp_admin_bar, $post;

		// Bail if the current screen is not an Edit Participant screen.
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( $screen instanceof WP_Screen && $screen->base != 'post' ) {
				return;
			}
			if ( $screen->id == 'add' ) {
				return;
			}
		}

		// Bail if there's no Post.
		if ( empty( $post ) ) {
			return;
		}

		// Bail if there's no Post and it's WordPress admin.
		if ( empty( $post ) && is_admin() ) {
			return;
		}

		// Get Participant ID.
		$participant_id = $this->acf_loader->post->participant_id_get( $post->ID );
		if ( $participant_id === false ) {
			return;
		}

		// Get Participant.
		$participant = $this->get_by_id( $participant_id );
		if ( $participant === false ) {
			return;
		}

		// Get Contact ID.
		$contact_id = $participant['contact_id'];
		if ( $contact_id === false ) {
			return;
		}

		// Check permission to view this Contact.
		if ( ! $this->civicrm->contact->user_can_view( $contact_id ) ) {
			return;
		}

		// Get the "View" URL for this Participant.
		$query_base = 'reset=1&id=' . $participant_id . '&cid=' . $contact_id;
		$view_query = $query_base . '&action=view&context=participant';
		$view_url = $this->plugin->civicrm->get_link( 'civicrm/contact/view/participant', $view_query );

		// Get the "Edit" URL for this Participant.
		$edit_query = $query_base . '&action=update&context=participant&selectedChild=event';
		$edit_url = $this->plugin->civicrm->get_link( 'civicrm/contact/view/participant', $edit_query );

		// Add item to Edit menu.
		$wp_admin_bar->add_node( [
			'id' => 'cau-edit',
			'parent' => 'edit',
			'title' => __( 'Edit in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href' => $edit_url,
		] );

		// Add item to View menu.
		$wp_admin_bar->add_node( [
			'id' => 'cau-view',
			'parent' => 'view',
			'title' => __( 'View in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href' => $view_url,
		] );

		// Add item to CAU menu.
		$wp_admin_bar->add_node( [
			'id' => 'cau-0',
			'parent' => $id,
			'title' => __( 'Edit in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href' => $edit_url,
		] );

	}



} // Class ends.



