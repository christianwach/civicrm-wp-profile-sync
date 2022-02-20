<?php
/**
 * CiviCRM Participant Custom Post Type Class.
 *
 * Provides a Participant Custom Post Type.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;




/**
 * Participants Custom Post Type Class.
 *
 * A class that encapsulates a Custom Post Type for Participants.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT {

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
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $participant The parent object.
	 */
	public $participant;

	/**
	 * Taxonomy Sync object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $tax The Taxonomy Sync object.
	 */
	public $tax;

	/**
	 * Term HTML object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $term_html The Term HTML object.
	 */
	public $term_html;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_hooks The Mapper hooks registered flag.
	 */
	public $mapper_hooks = false;

	/**
	 * Custom Post Type name.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $cpt The name of the Custom Post Type.
	 */
	public $post_type_name = 'participant';

	/**
	 * Taxonomy name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $taxonomy_name The name of the Custom Taxonomy.
	 */
	public $taxonomy_name = 'participant-role';

	/**
	 * Free Taxonomy name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $taxonomy_tag_name The name of the Custom Free Taxonomy.
	 */
	public $tag_name = 'participant-tag';

	/**
	 * ACF identifier.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $acf_slug The ACF identifier.
	 */
	public $acf_slug = 'cwps_participant';



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
		$this->civicrm = $parent->civicrm;
		$this->participant = $parent;

		// Determine if the CPT is enabled.
		$setting = $this->acf_loader->mapping->setting_get( $this->post_type_name );
		$this->enabled = false;
		if ( $setting !== false ) {
			$this->enabled = empty( $setting['enabled'] ) ? false : true;
		}

		// Init when the Participant object is loaded.
		add_action( 'cwps/acf/civicrm/participant/loaded', [ $this, 'initialise' ] );

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
		do_action( 'cwps/acf/civicrm/participant-cpt/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-participant-cpt-tax.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-participant-cpt-term-html.php';

	}



	/**
	 * Set up objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Init objects.
		$this->tax = new CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT_Tax( $this );
		$this->term_html = new CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT_Term_HTML( $this );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Listen for activation and deactivation via the setting.
		add_action( 'cwps/acf/mapping/participant-cpt/enabled', [ $this, 'enabled' ] );
		add_action( 'cwps/acf/mapping/participant-cpt/disabled', 'flush_rewrite_rules' );

		// Bail if CPT not enabled.
		if ( $this->enabled === false ) {
			return;
		}

		// Register Mapper hooks when enabled.
		$this->register_mapper_hooks();

		// Register CPT hooks when enabled.
		$this->register_cpt_hooks();

		// Register ACF hooks when enabled.
		$this->register_acf_hooks();

		// Listen for events from Manual Sync that require Participant updates.
		add_action( 'cwps/acf/admin/post-to-participant/sync', [ $this, 'post_sync' ], 10 );
		add_action( 'cwps/acf/admin/post-to-participant/acf_fields/sync', [ $this, 'acf_fields_sync' ], 10 );

		// Intercept calls to sync the Participant.
		add_action( 'cwps/acf/admin/participant-to-post/sync', [ $this, 'participant_sync' ], 10 );

		// Listen for queries from our Field Group class.
		add_filter( 'cwps/acf/query_field_group_mapped', [ $this, 'query_field_group_mapped' ], 10, 2 );

		// Listen for queries from our Custom Field class.
		add_filter( 'cwps/acf/query_custom_fields', [ $this, 'query_custom_fields' ], 10, 2 );
		add_filter( 'cwps/acf/query_post_id', [ $this, 'query_post_id' ], 10, 2 );

		add_filter( 'cwps/acf/admin/router/participant_role/post_types', [ $this, 'admin_sync_router_add_cpt' ] );
		add_filter( 'cwps/acf/admin/router/participant_role/roles', [ $this, 'admin_sync_router_add_role' ] );
		add_filter( 'cwps/acf/admin/participant_role/post_types', [ $this, 'admin_sync_add_cpt' ] );
		add_filter( 'cwps/acf/admin/participant_role/roles', [ $this, 'admin_sync_add_role' ] );

		// Filter the Participant Post Types mapped to a Field Group.
		add_filter( 'cwps/acf/civicrm/participant/is_field_group', [ $this, 'is_field_group' ], 10, 3 );

		// Maybe add a link to action links on the Participant Posts list table.
		add_action( 'post_row_actions', [ $this, 'menu_item_add_to_row_actions' ], 10, 2 );

		// Maybe add a Menu Item to CiviCRM Admin Utilities menu.
		add_action( 'civicrm_admin_utilities_menu_top', [ $this, 'menu_item_add_to_cau' ], 10, 2 );

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
		add_action( 'cwps/acf/mapper/post/saved', [ $this, 'post_saved' ] );
		add_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ] );

		// Listen for events from our Mapper that require Post updates.
		add_action( 'cwps/acf/mapper/participant/created', [ $this, 'participant_created' ] );
		add_action( 'cwps/acf/mapper/participant/edited', [ $this, 'participant_edited' ] );
		add_action( 'cwps/acf/mapper/participant/deleted', [ $this, 'participant_deleted' ] );

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
		remove_action( 'cwps/acf/mapper/post/saved', [ $this, 'post_saved' ] );
		remove_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ] );
		remove_action( 'cwps/acf/mapper/participant/created', [ $this, 'participant_created' ] );
		remove_action( 'cwps/acf/mapper/participant/edited', [ $this, 'participant_edited' ] );
		remove_action( 'cwps/acf/mapper/participant/deleted', [ $this, 'participant_deleted' ] );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}



	/**
	 * Register callbacks for CPT events.
	 *
	 * @since 0.5
	 */
	public function register_cpt_hooks() {

		// Always create Post Type.
		add_action( 'init', [ $this, 'post_type_create' ] );
		add_action( 'admin_init', [ $this, 'post_type_remove_title' ] );

		// Make sure our feedback is appropriate.
		add_filter( 'post_updated_messages', [ $this, 'post_type_messages' ] );

		// Make sure our UI text is appropriate.
		add_filter( 'enter_title_here', [ $this, 'post_type_title' ] );

		// Create Taxonomy.
		add_action( 'init', [ $this, 'taxonomies_create' ] );

		// Fix hierarchical Taxonomy metabox display.
		add_filter( 'wp_terms_checklist_args', [ $this, 'taxonomy_fix_metabox' ], 10, 2 );

		// Add a filter to the wp-admin listing table.
		add_action( 'restrict_manage_posts', [ $this, 'taxonomy_filter_post_type' ] );

		// Add feature image size.
		//add_action( 'after_setup_theme', [ $this, 'feature_image_create' ] );

	}



	/**
	 * Unregister callbacks for CPT events.
	 *
	 * @since 0.5
	 */
	public function unregister_cpt_hooks() {

		// Remove all CPT listeners.
		remove_action( 'init', [ $this, 'post_type_create' ] );
		remove_action( 'admin_init', [ $this, 'post_type_remove_title' ] );
		remove_filter( 'post_updated_messages', [ $this, 'post_type_messages' ] );
		remove_filter( 'enter_title_here', [ $this, 'post_type_title' ] );
		remove_action( 'init', [ $this, 'taxonomies_create' ] );
		remove_filter( 'wp_terms_checklist_args', [ $this, 'taxonomy_fix_metabox' ] );
		remove_action( 'restrict_manage_posts', [ $this, 'taxonomy_filter_post_type' ] );
		//remove_action( 'after_setup_theme', [ $this, 'feature_image_create' ] );

	}



	/**
	 * Register callbacks for ACF events.
	 *
	 * @since 0.5
	 */
	public function register_acf_hooks() {

		// Add Field Groups and Fields.
		add_action( 'acf/init', [ $this, 'field_group_add' ], 20 );

		// Maybe hide a Field.
		add_filter( 'acf/prepare_field', [ $this, 'maybe_hide_field' ] );

	}



	/**
	 * Unregister callbacks for ACF events.
	 *
	 * @since 0.5
	 */
	public function unregister_acf_hooks() {

		// Remove all ACF listeners.
		remove_action( 'acf/init', [ $this, 'field_group_add' ] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Act when the CPT is enabled via the CiviCRM Event Component settings.
	 *
	 * @since 0.5
	 */
	public function enabled() {

		// Force Entities to be created.
		$this->post_type_create();
		$this->taxonomies_create();

		// Flush so they appear.
		flush_rewrite_rules();

		// Get current setting data.
		$data = $this->acf_loader->mapping->setting_get( $this->post_type_name );

		// Only do this once.
		if ( ! empty( $data['synced'] ) && $data['synced'] === 1 ) {
			//return;
		}

		// Sync them.
		$this->tax->participant_roles_sync_to_terms();

		// Add/Update setting.
		$data['synced'] = 1;

		// Overwrite setting.
		$this->acf_loader->mapping->setting_update( $this->post_type_name, $data );

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
		if ( $post->post_type !== $this->post_type_name ) {
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

		// Get the Participant ID from Post meta.
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
		 * * The WordPress Post class to overwrite the Post Title.
		 *
		 * @since 0.5
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/acf/participant-cpt/acf_fields_saved', $args );

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
	 * @param array $fields The ACF Field data.
	 * @param WP_Post $post The WordPress Post object.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $participant_data The CiviCRM Participant data.
	 */
	public function prepare_from_fields( $fields, $post, $post_id = null ) {

		// Init data for Fields.
		$participant_data = [];

		// Bail if we have no Field data to save.
		if ( empty( $fields ) ) {
			return $participant_data;
		}

		// Always assign Participant Role Value(s).
		$terms = $this->tax->terms_get( $post->ID );
		if ( ! empty( $terms ) ) {
			foreach ( $terms as $term ) {
				$role_id = $this->tax->term_meta_get( $term->term_id );
				if ( ! empty( $role_id ) ) {
					$role_value = $this->tax->participant_role_value_get( $role_id );
					$participant_data['role_id'][] = $role_value;
				}
			}
		}

		// Loop through the Field data.
		foreach ( $fields as $selector => $value ) {

			// Get the Field settings.
			$settings = get_field_object( $selector, $post_id );

			// Get the CiviCRM Custom Field and Participant Field.
			$custom_field_id = $this->civicrm->custom_field->custom_field_id_get( $settings );
			$participant_field_name = $this->participant->participant_field_name_get( $settings );

			// Do we have a synced Custom Field or Participant Field?
			if ( ! empty( $custom_field_id ) || ! empty( $participant_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . $custom_field_id;

				} else {

					// The Participant Field code is the setting.
					$code = $participant_field_name;

					// "Contact Group" Field has to be handled differently.
					if ( $code == 'contact_id' ) {

						// Maybe create a Contact.
						$contact_id = $this->participant->prepare_contact_from_field( $selector, $value, $settings, $post_id );
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
						$event_id = $this->acf_loader->acf->field_type->event_group->prepare_output( $value );
						if ( empty( $event_id ) ) {
							continue;
						}

						// Overwrite code and value.
						$code = 'event_id';
						$value = (int) $event_id;

					}

				}

				// Parse value by Field Type.
				$value = $this->acf_loader->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings );

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
		$participant_data = $this->prepare_from_fields( $fields, $post, $post_id );

		// Update the Participant.
		$participant = $this->participant->create( $participant_data );

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
		$participant_data = $this->prepare_from_fields( $fields, $post, $post_id );

		// Add the Participant ID.
		$participant_data['id'] = $participant_id;

		// Update the Participant.
		$participant = $this->participant->update( $participant_data );

		// --<
		return $participant;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create the WordPress Post when a CiviCRM Participant is being synced.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Participant data.
	 */
	public function participant_sync( $args ) {

		// Bail if this is not a Participant.
		if ( $args['objectName'] != 'Participant' ) {
			return;
		}

		// Backfill the Participant data.
		$args['objectRef'] = $this->civicrm->participant->backfill( $args['objectRef'] );

		// Find the Post ID of this Post Type that this Participant is synced with.
		$post_id = false;
		$post_ids = $this->acf_loader->post->get_by_participant_id( $args['objectId'], $this->post_type_name );
		if ( ! empty( $post_ids ) ) {
			$post_id = array_pop( $post_ids );
		}

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( $post_id === false ) {
			$post_id = $this->acf_loader->post->create_from_participant( $args['objectRef'], $this->post_type_name );
		} else {
			$this->acf_loader->post->update_from_participant( $args['objectRef'], $post_id );
		}

		// Add our data to the params.
		$args['post_type'] = $this->post_type_name;
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been synced from Participant details.
		 *
		 * Used internally to:
		 *
		 * * Update the ACF Fields for the WordPress Post.
		 * * Update the Terms for the WordPress Post.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/participant/sync', $args );

	}



	/**
	 * Create a WordPress Post when a CiviCRM Participant has been created.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function participant_created( $args ) {

		// Bail if this is not a Participant.
		if ( $args['objectName'] != 'Participant' ) {
			return;
		}

		// Backfill the Participant data.
		$args['objectRef'] = $this->civicrm->participant->backfill( $args['objectRef'] );

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		/*
		 * Exclude "reverse" create procedure when a WordPress Post is the
		 * originating Entity and the Post Type matches.
		 *
		 * This is because - although there isn't a Post ID yet - there
		 * cannot be more than one Post of a particular Post Type per Participant.
		 *
		 * Instead, the Participant ID needs to be reverse synced to the Post.
		 */
		if ( $entity['entity'] === 'post' && $this->post_type_name == $entity['type'] ) {

			// Save correspondence and skip.
			$this->acf_loader->post->participant_id_set( $entity['id'], $args['objectId'] );
			return;

		}

		// Find the Post ID of this Post Type that this Participant is synced with.
		$post_id = false;
		$post_ids = $this->acf_loader->post->get_by_participant_id( $args['objectId'], $this->post_type_name );
		if ( ! empty( $post_ids ) ) {
			$post_id = array_pop( $post_ids );
		}

		// Remove WordPress Post callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_post_remove();

		// Create the WordPress Post.
		if ( $post_id === false ) {
			$post_id = $this->acf_loader->post->create_from_participant( $args['objectRef'], $this->post_type_name );
		}

		// Reinstate WordPress Post callbacks.
		$this->acf_loader->mapper->hooks_wordpress_post_add();

		// Add our data to the params.
		$args['post_type'] = $this->post_type_name;
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been updated from Participant details.
		 *
		 * Used internally to:
		 *
		 * * Update the ACF Fields for the WordPress Post
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/participant/created', $args );

	}



	/**
	 * Update a WordPress Post when a CiviCRM Participant has been updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function participant_edited( $args ) {

		// Bail if this is not a Participant.
		if ( $args['objectName'] != 'Participant' ) {
			return;
		}

		// Backfill the Participant data.
		$args['objectRef'] = $this->civicrm->participant->backfill( $args['objectRef'] );

		// Find the Post ID of this Post Type that this Participant is synced with.
		$post_id = false;
		$post_ids = $this->acf_loader->post->get_by_participant_id( $args['objectId'], $this->post_type_name );
		if ( ! empty( $post_ids ) ) {
			$post_id = array_pop( $post_ids );
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Exclude "reverse" edits when a Post is the originator.
		if ( $entity['entity'] === 'post' && $post_id == $entity['id'] ) {
			return;
		}

		// Remove WordPress Post callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_post_remove();

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( $post_id === false ) {
			$post_id = $this->acf_loader->post->create_from_participant( $args['objectRef'], $this->post_type_name );
		} else {
			$this->acf_loader->post->update_from_participant( $args['objectRef'], $post_id );
		}

		// Reinstate WordPress Post callbacks.
		$this->acf_loader->mapper->hooks_wordpress_post_add();

		// Add our data to the params.
		$args['post_type'] = $this->post_type_name;
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been updated from Participant details.
		 *
		 * Used internally to:
		 *
		 * * Update the ACF Fields for the WordPress Post
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/participant/edited', $args );

	}



	/**
	 * Delete a WordPress Post when a CiviCRM Participant has been deleted.
	 *
	 * Unusually for this plugin, it is necessary to delete the corresponding
	 * Post when a Participant (or Event Registration) is deleted in CiviCRM.
	 * When the CiviCRM record is removed, it makes no sense to keep data for
	 * historical reasons.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function participant_deleted( $args ) {

		// Bail if this is not a Participant.
		if ( $args['objectName'] != 'Participant' ) {
			return;
		}

		// Find the Post ID of this Post Type that this Participant is synced with.
		$post_id = false;
		$post_ids = $this->acf_loader->post->get_by_participant_id( $args['objectId'], $this->post_type_name );
		if ( ! empty( $post_ids ) ) {
			$post_id = array_pop( $post_ids );
		}
		if ( $post_id === false ) {
			return;
		}

		// Remove WordPress Post callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_post_remove();

		// Delete the WordPress Post if it exists.
		$this->acf_loader->post->delete_from_participant( $post_id );

		// Reinstate WordPress Post callbacks.
		$this->acf_loader->mapper->hooks_wordpress_post_add();

		// Add our data to the params.
		$args['post_type'] = $this->post_type_name;
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been deleted from Participant details.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/participant/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Listen for queries from the Field Group class.
	 *
	 * This method responds with a Boolean if it detects that this Field Group
	 * maps to the Participant Post Type.
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
		$participant = $this->participant->get_by_id( $participant_id );
		if ( $participant === false ) {
			return $post_ids;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Init Participant Post IDs.
		$participant_post_ids = [];

		// Get array of IDs for this Post Type.
		$ids = $this->acf_loader->post->get_by_participant_id( $participant_id, $this->post_type_name );
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



	// -------------------------------------------------------------------------



	/**
	 * Filter the Post Types mapped to a Field Group.
	 *
	 * @since 0.5
	 *
	 * @param array|bool $is_participant_field_group The array of Post Types, false otherwise.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array|bool The array of Post Types if the Field Group has been mapped, or false otherwise.
	 */
	public function is_field_group( $is_participant_field_group, $field_group ) {

		// Maybe add this Post Type.
		$post_types = $this->is_participant_field_group( $field_group );
		if ( $post_types !== false ) {
			if ( empty( $is_participant_field_group ) ) {
				$is_participant_field_group = $post_types;
			} else {
				$is_participant_field_group = array_merge( $is_participant_field_group, $post_types );
			}
		}

		// --<
		return $is_participant_field_group;

	}



	/**
	 * Check if a Field Group has been mapped to the Participant Post Type.
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

			// Define params to test for our Post Type.
			$params = [
				'post_type' => $this->post_type_name,
			];

			// Do the check.
			$is_visible = $this->acf_loader->acf->field_group->is_visible( $field_group, $params );

			// If it is, then add to return array.
			if ( $is_visible ) {
				$is_participant_field_group[] = $this->post_type_name;
			}

		}

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

		// Bail if not our Post Type.
		if ( $post->post_type !== $this->post_type_name ) {
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
		$participant = $this->participant->get_by_id( $participant_id );
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
			/* translators: 1: The Link URL, 2: The Link text */
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
			if ( $screen->id != $this->post_type_name ) {
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
		$participant = $this->participant->get_by_id( $participant_id );
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



	// -------------------------------------------------------------------------



	/**
	 * Create our Custom Post Type.
	 *
	 * @since 0.5
	 */
	public function post_type_create() {

		// Only call this once.
		static $registered;
		if ( isset( $registered ) && $registered === true ) {
			return;
		}

		// Set up the Post Type called "Participant".
		register_post_type( $this->post_type_name, [

			// Labels.
			'labels' => [
				'name'               => __( 'Participants', 'civicrm-wp-profile-sync' ),
				'singular_name'      => __( 'Participant', 'civicrm-wp-profile-sync' ),
				'add_new'            => __( 'Add New', 'civicrm-wp-profile-sync' ),
				'add_new_item'       => __( 'Add New Participant', 'civicrm-wp-profile-sync' ),
				'edit_item'          => __( 'Edit Participant', 'civicrm-wp-profile-sync' ),
				'new_item'           => __( 'New Participant', 'civicrm-wp-profile-sync' ),
				'all_items'          => __( 'All Participants', 'civicrm-wp-profile-sync' ),
				'view_item'          => __( 'View Participant', 'civicrm-wp-profile-sync' ),
				'search_items'       => __( 'Search Participants', 'civicrm-wp-profile-sync' ),
				'not_found'          => __( 'No matching Participant found', 'civicrm-wp-profile-sync' ),
				'not_found_in_trash' => __( 'No Participants found in Trash', 'civicrm-wp-profile-sync' ),
				'menu_name'          => __( 'Participants', 'civicrm-wp-profile-sync' ),
			],

			// Defaults.
			'menu_icon'   => 'dashicons-nametag',
			'description' => __( 'A Participant Post Type', 'civicrm-wp-profile-sync' ),
			'public' => true,
			'publicly_queryable' => true,
			'exclude_from_search' => false,
			'show_ui' => true,
			'show_in_nav_menus' => true,
			'show_in_menu' => true,
			'show_in_admin_bar' => true,
			'show_in_rest' => true,
			'has_archive' => true,
			'query_var' => true,
			'capability_type' => 'post',
			'hierarchical' => false,
			'menu_position' => 25,
			'map_meta_cap' => true,

			// Rewrite.
			'rewrite' => [
				'slug' => 'participants',
				'with_front' => false,
			],

			// Supports.
			'supports' => [
				'title',
				//'editor',
				//'excerpt',
				//'thumbnail',
				//'revisions',
			],

		] );

		//flush_rewrite_rules();

		// Flag done.
		$registered = true;

	}



	/**
	 * Removes the Title Field from our Custom Post Type.
	 *
	 * @since 0.5
	 */
	public function post_type_remove_title() {

		// Remove it.
		remove_post_type_support( $this->post_type_name, 'title' );

	}



	/**
	 * Override messages for a Custom Post Type.
	 *
	 * @since 0.5
	 *
	 * @param array $messages The existing messages.
	 * @return array $messages The modified messages.
	 */
	public function post_type_messages( $messages ) {

		// Access relevant globals.
		global $post, $post_ID;

		// Define custom messages for our Custom Post Type.
		$messages[ $this->post_type_name ] = [

			// Unused - messages start at index 1.
			0 => '',

			// Item updated.
			1 => sprintf(
				/* translators: %s: The Link URL */
				__( 'Participant updated. <a href="%s">View Participant</a>', 'civicrm-wp-profile-sync' ),
				esc_url( get_permalink( $post_ID ) )
			),

			// Custom Fields.
			2 => __( 'Custom field updated.', 'civicrm-wp-profile-sync' ),
			3 => __( 'Custom field deleted.', 'civicrm-wp-profile-sync' ),
			4 => __( 'Participant updated.', 'civicrm-wp-profile-sync' ),

			// Item restored to a revision.
			5 => isset( $_GET['revision'] ) ?

					// Revision text.
					sprintf(
						/* translators: %s: Date and time of the revision */
						__( 'Participant restored to revision from %s', 'civicrm-wp-profile-sync' ),
						wp_post_revision_title( (int) $_GET['revision'], false )
					) :

					// No revision.
					false,

			// Item published.
			6 => sprintf(
				/* translators: %s: The Link URL */
				__( 'Participant published. <a href="%s">View Participant</a>', 'civicrm-wp-profile-sync' ),
				esc_url( get_permalink( $post_ID ) )
			),

			// Item saved.
			7 => __( 'Participant saved.', 'civicrm-wp-profile-sync' ),

			// Item submitted.
			8 => sprintf(
				/* translators: %s: The Link URL */
				__( 'Participant submitted. <a target="_blank" href="%s">Preview Participant</a>', 'civicrm-wp-profile-sync' ),
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
			),

			// Item scheduled.
			9 => sprintf(
				/* translators: 1: The Date string, 2: The Link URL */
				__( 'Participant scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Participant</a>', 'civicrm-wp-profile-sync' ),
				/* translators: Publish box date format, see http://php.net/date */
				date_i18n( __( 'M j, Y @ G:i', 'civicrm-wp-profile-sync' ),
				strtotime( $post->post_date ) ),
				esc_url( get_permalink( $post_ID ) )
			),

			// Draft updated.
			10 => sprintf(
				/* translators: %s: The Link URL */
				__( 'Participant draft updated. <a target="_blank" href="%s">Preview Participant</a>', 'civicrm-wp-profile-sync' ),
				esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) )
			),

		];

		// --<
		return $messages;

	}



	/**
	 * Override the "Add title" label.
	 *
	 * @since 0.5
	 *
	 * @param string $title The existing title - usually "Add title".
	 * @return string $title The modified title.
	 */
	public function post_type_title( $title ) {

		// Bail if not our Post Type.
		if ( $this->post_type_name !== get_post_type() ) {
			return $title;
		}

		// Overwrite with our string.
		$title = __( 'Add the name of the Participant', 'civicrm-wp-profile-sync' );

		// --<
		return $title;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create our Custom Taxonomy.
	 *
	 * @since 0.5
	 */
	public function taxonomies_create() {

		// Only call this once.
		static $registered;
		if ( isset( $registered ) && $registered === true ) {
			return;
		}

		// Register a Taxonomy for this CPT.
		register_taxonomy(

			// Taxonomy name.
			$this->taxonomy_name,

			// Post type.
			$this->post_type_name,

			// Arguments.
			[

				// Same as "category".
				'hierarchical' => true,

				// Labels.
				'labels' => [
					'name'              => _x( 'Participant Roles', 'taxonomy general name', 'civicrm-wp-profile-sync' ),
					'singular_name'     => _x( 'Participant Role', 'taxonomy singular name', 'civicrm-wp-profile-sync' ),
					'search_items'      => __( 'Search Participant Roles', 'civicrm-wp-profile-sync' ),
					'all_items'         => __( 'All Participant Roles', 'civicrm-wp-profile-sync' ),
					'parent_item'       => __( 'Parent Participant Role', 'civicrm-wp-profile-sync' ),
					'parent_item_colon' => __( 'Parent Participant Role:', 'civicrm-wp-profile-sync' ),
					'edit_item'         => __( 'Edit Participant Role', 'civicrm-wp-profile-sync' ),
					'update_item'       => __( 'Update Participant Role', 'civicrm-wp-profile-sync' ),
					'add_new_item'      => __( 'Add New Participant Role', 'civicrm-wp-profile-sync' ),
					'new_item_name'     => __( 'New Participant Role Name', 'civicrm-wp-profile-sync' ),
					'not_found'         => __( 'No Participant Roles found.', 'civicrm-wp-profile-sync' ),
					'no_terms'         => __( 'No Participant Roles', 'civicrm-wp-profile-sync' ),
					'menu_name'         => __( 'Participant Roles', 'civicrm-wp-profile-sync' ),
				],

				// Rewrite rules.
				'rewrite' => [
					'slug' => 'participant-roles',
				],

				// Show column in wp-admin.
				'show_admin_column' => true,
				'show_ui' => true,

			]

		);

		// Register a free Taxonomy for this CPT.
		register_taxonomy(

			// Taxonomy name.
			$this->tag_name,

			// Post type.
			$this->post_type_name,

			// Arguments.
			[

				// Same as "tags".
				'hierarchical' => false,

				// Labels.
				'labels' => [
					'name'              => _x( 'Participant Tags', 'taxonomy general name', 'civicrm-wp-profile-sync' ),
					'singular_name'     => _x( 'Participant Tag', 'taxonomy singular name', 'civicrm-wp-profile-sync' ),
					'search_items'      => __( 'Search Participant Tags', 'civicrm-wp-profile-sync' ),
					'all_items'         => __( 'All Participant Tags', 'civicrm-wp-profile-sync' ),
					'parent_item'       => __( 'Parent Participant Tag', 'civicrm-wp-profile-sync' ),
					'parent_item_colon' => __( 'Parent Participant Tag:', 'civicrm-wp-profile-sync' ),
					'edit_item'         => __( 'Edit Participant Tag', 'civicrm-wp-profile-sync' ),
					'update_item'       => __( 'Update Participant Tag', 'civicrm-wp-profile-sync' ),
					'add_new_item'      => __( 'Add New Participant Tag', 'civicrm-wp-profile-sync' ),
					'new_item_name'     => __( 'New Participant Tag', 'civicrm-wp-profile-sync' ),
					'not_found'         => __( 'No Participant Tags found.', 'civicrm-wp-profile-sync' ),
					'menu_name'         => __( 'Participant Tags', 'civicrm-wp-profile-sync' ),
				],

				// Rewrite rules.
				'rewrite' => [
					'slug' => 'participant-tags',
				],

				// Show column in wp-admin.
				//'show_admin_column' => true,
				//'show_ui' => true,

			]

		);

		//flush_rewrite_rules();

		// Flag done.
		$registered = true;

	}



	/**
	 * Fix the Custom Taxonomy metabox.
	 *
	 * @see https://core.trac.wordpress.org/ticket/10982
	 *
	 * @since 0.5
	 *
	 * @param array $args The existing arguments.
	 * @param integer $post_id The WordPress post ID.
	 */
	public function taxonomy_fix_metabox( $args, $post_id ) {

		// If rendering metabox for our Taxonomy.
		if ( isset( $args['taxonomy'] ) && $args['taxonomy'] == $this->taxonomy_name ) {

			// Setting 'checked_ontop' to false seems to fix this.
			$args['checked_ontop'] = false;

		}

		// --<
		return $args;

	}



	/**
	 * Create our Feature Image size.
	 *
	 * @since 0.5
	 */
	public function feature_image_create() {

		// Define a small, square custom image size, cropped to fit.
		add_image_size(
			'idocs-participant',
			apply_filters( 'cwps/participant/cpt/image/width', 384 ),
			apply_filters( 'cwps/participant/cpt/image/height', 384 ),
			true // Crop.
		);

	}



	/**
	 * Add a filter for this Custom Taxonomy to the Custom Post Type listing.
	 *
	 * @since 0.5
	 */
	public function taxonomy_filter_post_type() {

		// Access current Post Type.
		global $typenow;

		// Bail if not our Post Type,
		if ( $typenow != $this->post_type_name ) {
			return;
		}

		// Get Taxonomy object.
		$taxonomy = get_taxonomy( $this->taxonomy_name );

		// Show a dropdown.
		wp_dropdown_categories( [
			/* translators: %s: The Taxonomy name */
			'show_option_all' => sprintf( __( 'Show All %s', 'civicrm-wp-profile-sync' ), $taxonomy->label ),
			'taxonomy' => $this->taxonomy_name,
			'name' => $this->taxonomy_name,
			'orderby' => 'name',
			'selected' => isset( $_GET[ $this->taxonomy_name ] ) ? $_GET[ $this->taxonomy_name ] : '',
			'show_count' => true,
			'hide_empty' => true,
			'value_field' => 'slug',
			'hierarchical' => 1,
		] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Adds the ACF Field Group.
	 *
	 * @since 0.5
	 */
	public function field_group_add() {

		// Get the ACF Field definitions.
		$register_date = $this->field_register_date_get();
		$event = $this->acf_loader->acf->field_type->event_group->get_field_definition();
		$status = $this->field_status_get();
		$contact = $this->acf_loader->acf->field_type->contact_group->get_field_definition();
		$source = $this->field_source_get();

		// Attach the Field Group to our CPT.
		$field_group_location = [[[
			'param' => 'post_type',
			'operator' => '==',
			'value' => $this->post_type_name,
		]]];

		// Hide UI elements on our CPT edit page.
		$field_group_hide_elements = [
			'the_content',
			'excerpt',
			'discussion',
			'comments',
			'revisions',
			'author',
			'format',
			'page_attributes',
			'featured_image',
			'send-trackbacks',
		];

		// Define Field Group.
		$field_group = [
			'key' => 'group_' . $this->acf_slug,
			'title' => __( 'Participant Data', 'civicrm-wp-profile-sync' ),
			'fields' => [
				$register_date,
				$event,
				$status,
				$contact,
				$source,
			],
			'location' => $field_group_location,
			'hide_on_screen' => $field_group_hide_elements,
			'position' => 'acf_after_title',
			'label_placement' => 'left',
			'instruction_placement' => 'field',
			//'style' => 'seamless',
		];

		// Now add the group.
		acf_add_local_field_group( $field_group );

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the "Registration Date" Field data.
	 *
	 * @since 0.5
	 *
	 * @return array $field The ACF Field definition.
	 */
	public function field_register_date_get() {

		// Define Field.
		$field = [
			'key' => 'field_' . $this->acf_slug . '_register_date',
			'label' => __( 'Registration Date', 'civicrm-wp-profile-sync' ),
			'name' => 'register_date',
			'type' => 'date_time_picker',
			'instructions' => __( 'Use the Date Picker to choose the Date and Time of the Registration.', 'civicrm-wp-profile-sync' ),
			'required' => 1,
			'display_format' => 'Y-m-d H:i:s',
			'return_format' => 'd/m/Y g:i a',
			'first_day' => 1,
			'field_cacf_civicrm_custom_field' => 'caiparticipant_register_date',
		];

		// --<
		return $field;

	}



	/**
	 * Gets the "Status ID" Field data.
	 *
	 * @since 0.5
	 *
	 * @return array $field The ACF Field definition.
	 */
	public function field_status_get() {

		// Define Field.
		$field = [
			'key' => 'field_' . $this->acf_slug . '_status_id',
			'label' => __( 'Status', 'civicrm-wp-profile-sync' ),
			'name' => 'status_id',
			'type' => 'select',
			'instructions' => '',
			'required' => 1,
			'choices' => $this->civicrm->participant_field->options_get( 'status_id' ),
			'default_value' => false,
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'field_cacf_civicrm_custom_field' => 'caiparticipant_status_id',
		];

		// --<
		return $field;

	}



	/**
	 * Gets the "Source" Field data.
	 *
	 * @since 0.5
	 *
	 * @return array $field The ACF Field definition.
	 */
	public function field_source_get() {

		// Define Field.
		$field = [
			'key' => 'field_' . $this->acf_slug . '_source',
			'label' => __( 'Source', 'civicrm-wp-profile-sync' ),
			'name' => 'source',
			'type' => 'text',
			'instructions' => __( 'The source of this event registration.', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'default_value' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
			'field_cacf_civicrm_custom_field' => 'caiparticipant_source',
		];

		// --<
		return $field;

	}



	// -------------------------------------------------------------------------



	/**
	 * Maybe hide a Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field data array.
	 * @return array|bool $field False if the Field must be hidden, the Field data otherwise.
	 */
	public function maybe_hide_field( $field ) {

		// Get the CiviCRM Custom Field ID.
		$custom_field_id = $this->civicrm->custom_field->custom_field_id_get( $field );
		if ( empty( $custom_field_id ) ) {
			return $field;
		}

		// Get the CiviCRM Custom Field.
		$custom_field = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );
		if ( empty( $custom_field ) ) {
			return $field;
		}

		// Get the CiviCRM Custom Group.
		$custom_group = $this->plugin->civicrm->custom_group->get_by_id( $custom_field['custom_group_id'] );
		if ( empty( $custom_group ) ) {
			return $field;
		}

		// Only handle Participant Custom Groups at this stage.
		if ( $custom_group['extends'] !== 'Participant' ) {
			return $field;
		}

		/*
		 * For Participant: "extends_entity_column_id" means:
		 *
		 * * Missing "extends_entity_column_id": All Participants.
		 * * Missing "extends_entity_column_value": All Participants.
		 *
		 * 1: The VALUE of the 'participant_role'
		 * 2: The ID of the CiviCRM Event
		 * 3: The VALUE of the 'event_type'
		 *
		 * 1 needs to be handled by creating a Field Group whose Location Rule
		 * targets the "Participant Role" Taxonomy.
		 *
		 * 2 & 3 are handled below. The best way is to create a "Participant
		 * Meta Data" Field Group and add all Custom Fields to that. This code
		 * will handle showing and hiding the individual Fields.
		 */

		// Missing "extends_entity_column_value" means All Participants.
		if ( empty( $custom_group['extends_entity_column_value'] ) ) {
			return $field;
		}

		// Set a condition for Fields that only show on Events.
		if ( $custom_group['extends_entity_column_id'] == 2 ) {
			$or = [];
			foreach ( $custom_group['extends_entity_column_value'] as $value ) {
				$or[] = [[
					'field' => 'field_' . $this->acf_slug . '_event_id',
					'operator' => '==',
					'value' => $value,
				]];
			}
			if ( ! empty( $or ) ) {
				$field['conditional_logic'] = $or;
			}
		}

		// Set a condition for Event Custom Groups.
		if ( $custom_group['extends_entity_column_id'] == 3 ) {
			$or = [];
			foreach ( $custom_group['extends_entity_column_value'] as $value ) {
				$or[] = [[
					'field' => 'field_' . $this->acf_slug . '_event_type',
					'operator' => '==',
					'value' => $value,
				]];
			}
			if ( ! empty( $or ) ) {
				$field['conditional_logic'] = $or;
			}
		}

		// --<
		return $field;

	}



	// -------------------------------------------------------------------------



	/**
	 * Filter the mapped Post Types to include this one.
	 *
	 * @since 0.5
	 *
	 * @param $post_types The mapped WordPress Post Types.
	 */
	public function admin_sync_router_add_cpt( $post_types ) {

		// Add this Post Type.
		$post_types[ $this->post_type_name ] = get_post_type_object( $this->post_type_name );

		// --<
		return $post_types;

	}



	/**
	 * Filter the mapped Participant Roles.
	 *
	 * @since 0.5
	 *
	 * @param $participant_roles The mapped CiviCRM Participant Roles.
	 */
	public function admin_sync_router_add_role( $participant_roles ) {

		// Get our Post Type.
		$post_type = get_post_type_object( $this->post_type_name );

		// Add a "pseudo-role".
		$participant_roles[] = [
			'value' => 'cpt',
			'label' => esc_html( $post_type->labels->singular_name ),
		];

		// --<
		return $participant_roles;

	}



	/**
	 * Filter the mapped Post Types to include this one.
	 *
	 * @since 0.5
	 *
	 * @param $post_types The mapped WordPress Post Types.
	 */
	public function admin_sync_add_cpt( $post_types ) {

		// Get our Post Type.
		$post_type = get_post_type_object( $this->post_type_name );

		// Add this Post Type.
		$post_types[ $this->post_type_name ] = $post_type->label;

		// --<
		return $post_types;

	}



	/**
	 * Filter the mapped Participant Roles.
	 *
	 * @since 0.5
	 *
	 * @param $participant_roles The mapped CiviCRM Participant Roles.
	 */
	public function admin_sync_add_role( $participant_roles ) {

		// Get our Post Type.
		$post_type = get_post_type_object( $this->post_type_name );

		// Add a "pseudo-role".
		$participant_roles['cpt'] = $post_type->labels->singular_name;

		// --<
		return $participant_roles;

	}



} // Class ends.



