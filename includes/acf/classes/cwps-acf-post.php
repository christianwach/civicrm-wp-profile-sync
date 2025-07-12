<?php
/**
 * WordPress Post Class.
 *
 * Handles WordPress Post functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync WordPress Post Class.
 *
 * A class that encapsulates WordPress Post functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Post {

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
	 * Post Taxonomy object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_Post_Tax
	 */
	public $tax;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * Post meta Contact ID key.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $contact_id_key = '_civicrm_acf_integration_post_contact_id';

	/**
	 * Post meta Activity ID key.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $activity_id_key = '_civicrm_acf_integration_post_activity_id';

	/**
	 * Post meta Participant ID key.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $participant_id_key = '_civicrm_acf_integration_post_participant_id';

	/**
	 * An array of Participant Records prior to edit.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Participant Records in an array and try and match
	 * them up in the post edit hook.
	 *
	 * @since 0.4
	 * @access private
	 * @var array
	 */
	private $bridging_array = [];

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store references to objects.
		$this->plugin     = $acf_loader->plugin;
		$this->acf_loader = $acf_loader;

		// Init when this plugin is loaded.
		add_action( 'cwps/acf/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 */
		do_action( 'cwps/acf/post/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/classes/cwps-acf-post-tax.php';

	}

	/**
	 * Set up the child objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Init Post Taxonomy object.
		$this->tax = new CiviCRM_Profile_Sync_ACF_Post_Tax( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add our meta boxes.
		add_action( 'add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 2 );

		// Maybe add a Menu Item to CiviCRM Admin Utilities menu.
		add_action( 'civicrm_admin_utilities_menu_top', [ $this, 'menu_item_add_to_cau' ], 10, 2 );

		// Maybe add a Menu Item to CiviCRM Contact "Action" menu.
		add_action( 'civicrm_summaryActions', [ $this, 'menu_item_add_to_civi_actions' ], 10, 2 );

		// Maybe add a link to action links on the Pages and Posts list tables.
		add_action( 'page_row_actions', [ $this, 'menu_item_add_to_row_actions' ], 10, 2 );
		add_action( 'post_row_actions', [ $this, 'menu_item_add_to_row_actions' ], 10, 2 );

		// Maybe sync the Contact "Display Name" to the WordPress Post Title.
		add_action( 'cwps/acf/contact/acf_fields_saved', [ $this, 'maybe_sync_title' ], 10 );

		// Intercept calls to sync the Contact.
		add_action( 'cwps/acf/admin/contact-to-post/sync', [ $this, 'contact_sync' ], 10 );

		// Intercept calls to sync the Activity.
		add_action( 'cwps/acf/admin/activity-to-post/sync', [ $this, 'activity_sync' ], 10 );

		// Intercept calls to sync the Participant.
		add_action( 'cwps/acf/admin/participant-role-to-post/sync', [ $this, 'participant_sync' ], 10 );

		// Listen for queries for a mapped Contact ID.
		add_filter( 'cwps/acf/query_contact_id', [ $this, 'query_contact_id' ], 10, 3 );

		// Maybe backfill the Participant info to the WordPress Post Title.
		add_action( 'cwps/acf/participant/acf_fields_saved', [ $this, 'participant_maybe_sync_title' ], 10 );
		add_action( 'cwps/acf/participant-cpt/acf_fields_saved', [ $this, 'participant_maybe_sync_title' ], 10 );

	}

	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( true === $this->mapper_hooks ) {
			return;
		}

		// Listen for events from our Mapper that require Post updates.
		add_action( 'cwps/acf/mapper/contact/created', [ $this, 'contact_created' ], 10 );
		add_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );
		add_action( 'cwps/acf/mapper/contact/deleted', [ $this, 'contact_deleted' ], 10 );
		add_action( 'cwps/acf/mapper/activity/created', [ $this, 'activity_created' ], 10 );
		add_action( 'cwps/acf/mapper/activity/edited', [ $this, 'activity_edited' ], 10 );
		add_action( 'cwps/acf/mapper/participant/created', [ $this, 'participant_created' ], 10 );
		add_action( 'cwps/acf/mapper/participant/edited', [ $this, 'participant_edited' ], 10 );
		add_action( 'cwps/acf/mapper/participant/delete/pre', [ $this, 'participant_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/participant/deleted', [ $this, 'participant_deleted' ], 10 );

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
		if ( false === $this->mapper_hooks ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/contact/created', [ $this, 'contact_created' ], 10 );
		remove_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/contact/deleted', [ $this, 'contact_deleted' ], 10 );
		remove_action( 'cwps/acf/mapper/activity/created', [ $this, 'activity_created' ], 10 );
		remove_action( 'cwps/acf/mapper/activity/edited', [ $this, 'activity_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/participant/created', [ $this, 'participant_created' ], 10 );
		remove_action( 'cwps/acf/mapper/participant/edited', [ $this, 'participant_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/participant/deleted', [ $this, 'participant_deleted' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Register meta boxes.
	 *
	 * @since 0.4
	 *
	 * @param string  $post_type The WordPress Post Type.
	 * @param WP_Post $post The WordPress Post.
	 */
	public function meta_boxes_add( $post_type, $post ) {

		// Bail if this Post Type is not mapped.
		if ( ! $this->acf_loader->post_type->is_mapped_to_contact_type( $post_type ) ) {
			return;
		}

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) {
			return;
		}

		// Get Contact ID.
		$contact_id = $this->contact_id_get( $post->ID );

		// Bail if there's no corresponding Contact.
		if ( false === $contact_id ) {
			return;
		}

		// Check permission to view this Contact.
		if ( ! $this->acf_loader->civicrm->contact->user_can_view( $contact_id ) ) {
			return;
		}

		// Create CiviCRM Settings and Sync metabox.
		add_meta_box(
			'cwps_acf_metabox',
			__( 'CiviCRM', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_link_render' ], // Callback.
			$post_type, // Post Type.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

	}

	/**
	 * Render a meta box on Post edit screens with a link to the Contact.
	 *
	 * @since 0.4
	 *
	 * @param WP_Post $post The WordPress Post.
	 */
	public function meta_box_link_render( $post ) {

		// Get Contact ID.
		$contact_id = $this->contact_id_get( $post->ID );

		// Bail if we don't get one for some reason.
		if ( false === $contact_id ) {
			return;
		}

		// Get the URL for this Contact.
		$url = $this->plugin->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Construct link.
		$link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( $url ),
			esc_html__( 'View this Contact in CiviCRM', 'civicrm-wp-profile-sync' )
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<p>' . $link . '</p>';

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add a add a Menu Item to the CiviCRM Contact's "Actions" menu.
	 *
	 * @since 0.4
	 *
	 * @param array   $actions The array of actions from which the menu is rendered.
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 */
	public function menu_item_add_to_civi_actions( &$actions, $contact_id ) {

		// Bail if there's no Contact ID.
		if ( empty( $contact_id ) ) {
			return;
		}

		// Bail if there's no sub-menu.
		if ( empty( $actions['otherActions'] ) ) {
			// Maybe create one?
			return;
		}

		// Grab Contact.
		$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );
		if ( false === $contact ) {
			return;
		}

		// Bail if none of this Contact's Contact Types is mapped.
		$post_types = $this->acf_loader->civicrm->contact->is_mapped( $contact );
		if ( false === $post_types ) {
			return;
		}

		// Init weight.
		$weight = 30;

		// Handle each Post Type in turn.
		foreach ( $post_types as $post_type ) {

			// Get the Post ID that this Contact is mapped to.
			$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $contact, $post_type );
			if ( false === $post_id ) {
				continue;
			}

			// Get Post Type label.
			$label = $this->acf_loader->post_type->singular_label_get( $post_type );

			// Build view title.
			$view_title = sprintf(
				/* translators: %s: The Post Type label */
				__( 'View %s in WordPress', 'civicrm-wp-profile-sync' ),
				$label
			);

			// Build "view" link.
			$actions['otherActions'][ 'wp-view-' . $post_type ] = [
				'title'  => $view_title,
				'ref'    => 'civicrm-wp-view-' . $post_type,
				'weight' => $weight,
				'href'   => get_permalink( $post_id ),
				'tab'    => 'wp-view-' . $post_type,
				'class'  => 'wp-view',
				'icon'   => 'crm-i fa-eye',
				'key'    => 'wp-view-' . $post_type,
			];

			// Check User can edit.
			if ( current_user_can( 'edit_post', $post_id ) ) {

				// Bump weight.
				$weight++;

				// Build edit title.
				$edit_title = sprintf(
					/* translators: %s: The Post Type label */
					__( 'Edit %s in WordPress', 'civicrm-wp-profile-sync' ),
					$label
				);

				// Build "edit" link.
				$actions['otherActions'][ 'wp-edit-' . $post_type ] = [
					'title'  => $edit_title,
					'ref'    => 'civicrm-wp-edit-' . $post_type,
					'weight' => $weight,
					'href'   => get_edit_post_link( $post_id ),
					'tab'    => 'wp-edit-' . $post_type,
					'class'  => 'wp-edit',
					'icon'   => 'crm-i fa-edit',
					'key'    => 'wp-edit-' . $post_type,
				];

			}

			// Bump weight.
			$weight++;

		}

	}

	/**
	 * Add a add a Menu Item to the CiviCRM Admin Utilities menu.
	 *
	 * @since 0.4
	 *
	 * @param string $id The menu parent ID.
	 * @param array  $components The active CiviCRM Conponents.
	 */
	public function menu_item_add_to_cau( $id, $components ) {

		// Access WordPress admin bar.
		global $wp_admin_bar, $post;

		// Bail if the current screen is not an Edit screen.
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( $screen instanceof WP_Screen && 'post' !== $screen->base ) {
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

		// Get Contact ID.
		$contact_id = $this->contact_id_get( $post->ID );

		// Bail if we don't get one for some reason.
		if ( false === $contact_id ) {
			return;
		}

		// Check permission to view this Contact.
		if ( ! $this->acf_loader->civicrm->contact->user_can_view( $contact_id ) ) {
			return;
		}

		// Get the URL for this Contact.
		$url = $this->plugin->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Add item to Edit menu.
		$args = [
			'id'     => 'cau-edit',
			'parent' => 'edit',
			'title'  => __( 'Edit in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href'   => $url,
		];
		$wp_admin_bar->add_node( $args );

		// Add item to View menu.
		$args = [
			'id'     => 'cau-view',
			'parent' => 'view',
			'title'  => __( 'View in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href'   => $url,
		];
		$wp_admin_bar->add_node( $args );

		// Add item to CAU menu.
		$args = [
			'id'     => 'cau-0',
			'parent' => $id,
			'title'  => __( 'View in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href'   => $url,
		];
		$wp_admin_bar->add_node( $args );

	}

	/**
	 * Add a link to action links on the Pages and Posts list tables.
	 *
	 * @since 0.4
	 *
	 * @param array   $actions The array of row action links.
	 * @param WP_Post $post The WordPress Post object.
	 */
	public function menu_item_add_to_row_actions( $actions, $post ) {

		// Bail if there's no Post object.
		if ( empty( $post ) ) {
			return $actions;
		}

		/*
		// Do we need to know?
		if ( is_post_type_hierarchical( $post->post_type ) ) {
		}
		*/

		// Get Contact ID.
		$contact_id = $this->contact_id_get( $post->ID );

		// Bail if we don't get one for some reason.
		if ( false === $contact_id ) {
			return $actions;
		}

		// Check permission to view this Contact.
		if ( ! $this->acf_loader->civicrm->contact->user_can_view( $contact_id ) ) {
			return $actions;
		}

		// Get the URL for this Contact.
		$url = $this->plugin->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Add link to actions.
		$actions['civicrm'] = sprintf(
			/* translators: 1: The Link URL, 2: The Link text */
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'CiviCRM', 'civicrm-wp-profile-sync' )
		);

		// --<
		return $actions;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Check if a Post is mapped to a Contact.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return integer|bool $is_mapped The ID of the CiviCRM Contact if the Post is mapped, false otherwise.
	 */
	public function is_mapped_to_contact( $post_id ) {

		// Get the WordPress Entity.
		$entity = $this->acf_loader->acf->field->entity_type_get( $post_id );

		// Bail if it's not a Post.
		if ( 'post' !== $entity ) {
			return false;
		}

		// Get the Contact ID (or boolean false) from Post meta.
		$is_mapped = $this->contact_id_get( $post_id );

		// --<
		return $is_mapped;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Contact ID for a given WordPress Post ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return integer $contact_id The CiviCRM Contact ID, or false if none exists.
	 */
	public function contact_id_get( $post_id ) {

		// Get the Contact ID.
		$existing_id = get_post_meta( $post_id, $this->contact_id_key, true );

		// Does this Post have a Contact ID?
		if ( empty( $existing_id ) ) {
			$contact_id = false;
		} else {
			$contact_id = $existing_id;
		}

		// --<
		return $contact_id;

	}

	/**
	 * Set the CiviCRM Contact ID for a given WordPress Post ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @param integer $contact_id The CiviCRM Contact ID.
	 */
	public function contact_id_set( $post_id, $contact_id ) {

		// Store the Contact ID.
		add_post_meta( $post_id, $this->contact_id_key, $contact_id, true );

	}

	/**
	 * Delete the CiviCRM Contact ID for a given WordPress Post ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 */
	public function contact_id_delete( $post_id ) {

		// Store the Contact ID.
		delete_post_meta( $post_id, $this->contact_id_key );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the WordPress Post ID(s) for a given CiviCRM Contact ID and Post Type.
	 *
	 * If no Post Type is provided then an array of all synced Posts is returned.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @param string  $post_type The WordPress Post Type.
	 * @return array|bool $posts An array of Post IDs, or false on failure.
	 */
	public function get_by_contact_id( $contact_id, $post_type = 'any' ) {

		// Init as empty.
		$posts = [];

		/*
		 * Define args for query.
		 *
		 * We need to query multiple Post Statuses because we need to keep the linkage
		 * between the CiviCRM Entity and the Post throughout its life cycle.
		 *
		 * We have to specify these because "any" does not retrieve Posts with a status
		 * of "trash". We ignore "inherit" and "auto-draft" since they're not relevant.
		 *
		 * * Published: The default status for our purposes.
		 * * Trash:     Because we want to avoid a duplicate Post being created.
		 * * Private:   Ditto.
		 * * Future:    Ditto.
		 * * Draft:     When Posts are moved out of the Trash, this is their status.
		 * * Pending:   The status of Posts that are synced with trashed Contacts in CiviCRM.
		 *
		 * This may need to be revisited.
		 *
		 * @see https://developer.wordpress.org/reference/classes/wp_query/#status-parameters
		 */
		$args = [
			'post_type'      => $post_type,
			'post_status'    => [ 'publish', 'trash', 'private', 'future', 'draft', 'pending' ],
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'       => $this->contact_id_key,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value'     => (string) $contact_id,
			'posts_per_page' => -1,
			'order'          => 'ASC',
		];

		/*
		 * Filters the args for query.
		 *
		 * If you have added any custom Post statuses that need to be included, add them
		 * with a callback to this filter.
		 *
		 * @since 0.7.2
		 *
		 * @param array   $args The array of query args.
		 * @param integer $contact_id The CiviCRM Contact ID.
		 * @param string  $post_type The WordPress Post Type.
		 */
		$args = apply_filters( 'cwps/acf/post/get_by_contact_id/args', $args );

		// Do query.
		$query = new WP_Query( $args );

		// Do the loop.
		if ( $query->have_posts() ) {
			foreach ( $query->get_posts() as $found ) {

				if ( 'any' === $post_type ) {
					// Add if we want *all* Posts.
					$posts[] = $found->ID;
				} elseif ( $found->post_type === $post_type ) {
					// Grab what should be the only Post.
					$posts[] = $found->ID;
					break;
				}

			}
		}

		// Reset Post data just in case.
		wp_reset_postdata();

		// Format return.
		if ( empty( $posts ) ) {
			$posts = false;
		}

		// --<
		return $posts;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create the WordPress Posts when a CiviCRM Contact is being synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact data.
	 */
	public function contact_sync( $args ) {

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $args['objectName'], $top_level_types, true ) ) {
			return;
		}

		// Bail if none of this Contact's Contact Types is mapped.
		$post_types = $this->acf_loader->civicrm->contact->is_mapped( $args['objectRef'] );
		if ( false === $post_types ) {
			return;
		}

		// Handle each Post Type in turn.
		foreach ( $post_types as $post_type ) {
			$this->contact_sync_to_post( $args, $post_type );
		}

	}

	/**
	 * Create a WordPress Post when a CiviCRM Contact is being synced.
	 *
	 * @since 0.4
	 *
	 * @param array  $args The array of CiviCRM Contact data.
	 * @param string $post_type The WordPress Post Type.
	 */
	public function contact_sync_to_post( $args, $post_type ) {

		// Get the Post ID for this Contact.
		$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $args['objectRef'], $post_type );

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id']   = $post_id;

		/**
		 * Broadcast that a WordPress Post is about to be synced from Contact details.
		 *
		 * This action is now deprecated in favour of "cwps/acf/post/contact/sync/pre"
		 * for hook naming consistency.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/contact_sync_to_post/pre', $args );

		/**
		 * Broadcast that a WordPress Post is about to be synced from Contact details.
		 *
		 * @since 0.5.9
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/contact/sync/pre', $args );

		// Remove WordPress callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_remove();

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( false === $post_id ) {
			$post_id = $this->create_from_contact( $args['objectRef'], $post_type );
		} else {
			$this->update_from_contact( $args['objectRef'], $post_id );
		}

		// Reinstate WordPress callbacks.
		$this->acf_loader->mapper->hooks_wordpress_add();

		// Overwrite Post ID.
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been synced from Contact details.
		 *
		 * This action is now deprecated in favour of "cwps/acf/post/contact/sync"
		 * for hook naming consistency.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/contact_sync_to_post', $args );

		/**
		 * Broadcast that a WordPress Post has been synced from Contact details.
		 *
		 * Used internally to:
		 *
		 * * Update the ACF Fields for the WordPress Post.
		 * * Update the Terms for the WordPress Post.
		 *
		 * @since 0.5.9
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/contact/sync', $args );

	}

	/**
	 * Create a WordPress Post when a CiviCRM Contact has been created.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_created( $args ) {

		// Test if any of this Contact's Contact Types is mapped.
		$post_types = $this->acf_loader->civicrm->contact->is_mapped( $args['objectRef'] );
		if ( false !== $post_types ) {

			// Get originating Entity.
			$entity = $this->acf_loader->mapper->entity_get();

			// Handle each Post Type in turn.
			foreach ( $post_types as $post_type ) {

				// Check if the Post ID for this Contact already exists.
				$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $args['objectRef'], $post_type );

				/*
				 * Exclude "reverse" create procedure when a WordPress Post is the
				 * originating Entity and the Post Type matches.
				 *
				 * This is because - although there isn't a Post ID yet - there
				 * cannot be more than one Post of a particular Post Type per Contact.
				 *
				 * Instead, the Contact ID needs to be reverse synced to the Post.
				 */
				if ( 'post' === $entity['entity'] && $post_type === $entity['type'] ) {

					// Save correspondence and skip to next.
					$this->contact_id_set( $entity['id'], $args['objectId'] );
					continue;

				}

				// Remove WordPress Post callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_post_remove();

				// Create the WordPress Post.
				if ( false === $post_id ) {
					$post_id = $this->create_from_contact( $args['objectRef'], $post_type );
				}

				// Reinstate WordPress Post callbacks.
				$this->acf_loader->mapper->hooks_wordpress_post_add();

				// Add our data to the params.
				$args['post_type'] = $post_type;
				$args['post_id']   = $post_id;

				// TODO: Check if all Fields need sync - at the moment, it's just Contact Fields and Addresses.

				/**
				 * Broadcast that a WordPress Post has been updated from Contact details.
				 *
				 * Used internally to:
				 *
				 * * Update the ACF Fields for the WordPress Post
				 *
				 * @since 0.4
				 *
				 * @param array $args The array of CiviCRM and discovered params.
				 */
				do_action( 'cwps/acf/post/created', $args );

			}

		}

	}

	/**
	 * Update the synced WordPress Posts when a CiviCRM Contact has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_edited( $args ) {

		// Test if any of this Contact's Contact Types is mapped.
		$post_types = $this->acf_loader->civicrm->contact->is_mapped( $args['objectRef'] );
		if ( false !== $post_types ) {

			// Init args for Post Types.
			$post_type_args = $args;

			// Get originating Entity.
			$entity = $this->acf_loader->mapper->entity_get();

			// Handle each Post Type in turn.
			foreach ( $post_types as $post_type ) {

				// Get the Post ID for this Contact.
				$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $args['objectRef'], $post_type );

				// Exclude "reverse" edits when a Post is the originator.
				if ( 'post' === $entity['entity'] && (int) $post_id === (int) $entity['id'] ) {
					continue;
				}

				// Remove WordPress Post callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_post_remove();

				// Create the WordPress Post if it doesn't exist, otherwise update.
				if ( false === $post_id ) {
					$post_id = $this->create_from_contact( $args['objectRef'], $post_type );
				} else {
					$this->update_from_contact( $args['objectRef'], $post_id );
				}

				// Reinstate WordPress Post callbacks.
				$this->acf_loader->mapper->hooks_wordpress_post_add();

				// Add our data to the params.
				$post_type_args['post_type'] = $post_type;
				$post_type_args['post_id']   = $post_id;

				// TODO: Check if all Fields need sync - at the moment, it's just Contact Fields and Addresses.

				/**
				 * Broadcast that a WordPress Post has been updated from Contact details.
				 *
				 * Used internally to:
				 *
				 * * Update the ACF Fields for the WordPress Post
				 *
				 * @since 0.4
				 *
				 * @param array $post_type_args The array of CiviCRM and discovered params.
				 */
				do_action( 'cwps/acf/post/edited', $post_type_args );

			}

		}

		// Have any Contact Types been removed?
		$removed_types = [];
		if ( ! empty( $args['objectRef']->subtype_diffs['removed'] ) ) {
			$removed_types = $args['objectRef']->subtype_diffs['removed'];
		}

		// Process the Contact Types.
		foreach ( $removed_types as $contact_type ) {

			// Get the mapped Post Type.
			$post_type = $this->acf_loader->civicrm->contact_type->is_mapped_to_post_type( $contact_type );

			// Skip if this Contact Type is not mapped.
			if ( false === $post_type ) {
				continue;
			}

			// Get the associated Post IDs.
			$post_ids = $this->get_by_contact_id( $args['objectId'], $post_type );

			// Skip if there are no associated Post IDs.
			if ( false === $post_ids ) {
				continue;
			}

			// Process them.
			foreach ( $post_ids as $post_id ) {

				// Delete the Contact ID meta.
				$this->contact_id_delete( $post_id );

				// Remove WordPress Post callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_post_remove();

				// Set Post status to Draft.
				$args = [
					'ID'          => $post_id,
					'post_status' => 'pending',
				];

				// Update the Post.
				$result = wp_update_post( $args, true );

				/**
				 * Broadcast that a WordPress Post has been unlinked from a Contact.
				 *
				 * This hook can be used to change the status of the Post to something
				 * other than 'pending' or even to delete the Post entirely.
				 *
				 * @since 0.4
				 *
				 * @param integer $post_id The numeric ID of the WordPress Post.
				 * @param integer $post_type The WordPress Post Type.
				 * @param array $args The array of CiviCRM params.
				 * @param int|WP_Error $result The result of updating the WordPress Post.
				 */
				do_action( 'cwps/acf/post/unlinked', $post_id, $post_type, $args, $result );

				// Reinstate WordPress Post callbacks.
				$this->acf_loader->mapper->hooks_wordpress_post_add();

			}

		}

		/**
		 * Broadcast that a Contact has been updated from Contact details.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/contact/edited', $args );

	}

	/**
	 * Updates the synced WordPress Posts when a CiviCRM Contact has been deleted.
	 *
	 * @since 0.7.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_deleted( $args ) {

		// Get all associated Post IDs of all Post Types.
		$post_ids = $this->get_by_contact_id( $args['objectId'] );
		if ( false !== $post_ids ) {

			// Handle each Post in turn.
			foreach ( $post_ids as $post_id ) {

				// Delete the Contact ID meta.
				$this->contact_id_delete( $post_id );

				// Remove WordPress Post callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_post_remove();

				// Set Post status to Draft.
				$args = [
					'ID'          => $post_id,
					'post_status' => 'draft',
				];

				// Update the Post.
				$result = wp_update_post( $args, true );

				/**
				 * Fires when a WordPress Post has been unlinked from a Contact that has been deleted.
				 *
				 * This hook can be used to change the status of the Post to something
				 * other than 'pending' or even to delete the Post entirely.
				 *
				 * @since 0.7.2
				 *
				 * @param integer $post_id The numeric ID of the WordPress Post.
				 * @param integer $post_type The WordPress Post Type.
				 * @param array $args The array of CiviCRM params.
				 * @param int|WP_Error $result The result of updating the WordPress Post.
				 */
				do_action( 'cwps/acf/post/contact/delete/unlinked', $post_id, $args, $result );

			}

		}

		/**
		 * Broadcast that a Contact has been updated from Contact details.
		 *
		 * @since 0.7.2
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/contact/deleted', $args );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create a WordPress Post from a CiviCRM Contact.
	 *
	 * @since 0.4
	 *
	 * @param array  $contact The CiviCRM Contact data.
	 * @param string $post_type The name of Post Type.
	 * @return integer|bool $post_id The WordPress Post ID, or false on failure.
	 */
	public function create_from_contact( $contact, $post_type ) {

		// Maybe cast Contact data as array.
		if ( is_object( $contact ) ) {
			$contact = (array) $contact;
		}

		// Define basic Post data.
		$args = [
			'post_status'           => 'publish',
			'post_parent'           => 0,
			'comment_status'        => 'closed',
			'ping_status'           => 'closed',
			'to_ping'               => '', // Quick fix for Windows.
			'pinged'                => '', // Quick fix for Windows.
			'post_content_filtered' => '', // Quick fix for Windows.
			'post_excerpt'          => '', // Quick fix for Windows.
			'menu_order'            => 0,
			'post_type'             => $post_type,
			'post_title'            => $contact['display_name'],
			'post_content'          => '',
		];

		// Check if the Contact is in the CiviCRM Trash.
		$contact_is_deleted = false;
		if ( isset( $contact['contact_is_deleted'] ) && 1 === (int) $contact['contact_is_deleted'] ) {
			$contact_is_deleted = true;
		}

		// Set Post status to Pending or Publish.
		if ( $contact_is_deleted ) {
			$args['post_status'] = 'pending';
		}

		/**
		 * Filters the arguments used to create a Post from a Contact.
		 *
		 * @since 0.7.2
		 *
		 * @param array $args The arguments used to create a Post.
		 * @param array $contact The CiviCRM Contact data.
		 */
		$args = apply_filters( 'cwps/acf/post/contact/create/args', $args, $contact );

		// Insert the Post into the database.
		$post_id = wp_insert_post( $args );

		// Bail on failure.
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Contact ID is sometimes stored in 'contact_id', sometimes in 'id'.
		if ( ! isset( $contact['id'] ) ) {
			$contact_id = $contact['contact_id'];
		} else {
			$contact_id = $contact['id'];
		}

		// Save correspondence.
		$this->contact_id_set( $post_id, $contact_id );

		// We need to force ACF to create Fields for the Post.

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// If there are some, prime them with an empty string.
		if ( ! empty( $acf_fields ) ) {
			foreach ( $acf_fields as $field_group ) {
				foreach ( $field_group as $selector => $contact_field ) {
					$this->acf_loader->acf->field->value_update( $selector, '', $post_id );
				}
			}
		}

		// --<
		return $post_id;

	}

	/**
	 * Sync a CiviCRM Contact with a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param array   $contact The CiviCRM Contact data.
	 * @param integer $existing_id The numeric ID of the Post.
	 * @param WP_Post $post The WordPress Post object if it exists.
	 * @return integer|bool $post_id The WordPress Post ID, or false on failure.
	 */
	public function update_from_contact( $contact, $existing_id, $post = null ) {

		// Maybe cast Contact data as array.
		if ( is_object( $contact ) ) {
			$contact = (array) $contact;
		}

		// Define args to update the Post.
		$args = [
			'ID'         => $existing_id,
			'post_title' => $contact['display_name'],
		];

		// Overwrite Permalink if the current Post Title is empty.
		if ( ! is_null( $post ) && $post instanceof WP_Post ) {
			if ( empty( $post->post_title ) ) {
				$args['post_name'] = sanitize_title( $contact['display_name'] );
			}
		}

		/*
		 * Decide what to do when a Contact is in the CiviCRM Trash.
		 *
		 * CiviCRM does not have an Empty Trash schedule - a Contact has to be manually
		 * deleted permanently.
		 *
		 * What behaviour do we when the WordPress Trash is disabled? And when it's not?
		 *
		 * We can check if it's disabled with `! EMPTY_TRASH_DAYS` which means that there
		 * are zero days before emptying, i.e. immediate delete.
		 *
		 * But even when the Trash is not disabled, do we *really* want the Post to be
		 * permanently deleted when the Empty Trash procedure runs?
		 *
		 * For now, let's set the Post status to "Pending Review".
		 */

		// Check if the Contact is in the CiviCRM Trash.
		$contact_is_deleted = false;
		if ( isset( $contact['contact_is_deleted'] ) && 1 === (int) $contact['contact_is_deleted'] ) {
			$contact_is_deleted = true;
		}

		// Set Post status to Pending or Publish.
		if ( $contact_is_deleted ) {
			$args['post_status'] = 'pending';
		} else {
			$args['post_status'] = 'publish';
		}

		/**
		 * Filters the arguments used to update a Post from a Contact.
		 *
		 * @since 0.7.2
		 *
		 * @param array $args The arguments used to update a Post.
		 * @param array $contact The CiviCRM Contact data.
		 */
		$args = apply_filters( 'cwps/acf/post/contact/update/args', $args, $contact );

		// Update the Post.
		$post_id = wp_update_post( $args, true );

		// Bail on failure.
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// --<
		return $post_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Check if a Post is mapped to an Activity.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return integer|bool $is_mapped The ID of the CiviCRM Activity if the Post is mapped, false otherwise.
	 */
	public function is_mapped_to_activity( $post_id ) {

		// Get the WordPress Entity.
		$entity = $this->acf_loader->acf->field->entity_type_get( $post_id );

		// Bail if it's not a Post.
		if ( 'post' !== $entity ) {
			return;
		}

		// Get the Activity ID (or boolean false) from Post meta.
		$is_mapped = $this->activity_id_get( $post_id );

		// --<
		return $is_mapped;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Activity ID for a given WordPress Post ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return integer $activity_id The CiviCRM Activity ID, or false if none exists.
	 */
	public function activity_id_get( $post_id ) {

		// Get the Activity ID.
		$existing_id = get_post_meta( $post_id, $this->activity_id_key, true );

		// Does this Post have an Activity ID?
		if ( empty( $existing_id ) ) {
			$activity_id = false;
		} else {
			$activity_id = $existing_id;
		}

		// --<
		return $activity_id;

	}

	/**
	 * Set the CiviCRM Activity ID for a given WordPress Post ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @param integer $activity_id The CiviCRM Activity ID.
	 */
	public function activity_id_set( $post_id, $activity_id ) {

		// Store the Activity ID.
		add_post_meta( $post_id, $this->activity_id_key, $activity_id, true );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the WordPress Post ID for a given CiviCRM Activity ID and Post Type.
	 *
	 * If no Post Type is provided then an array of all synced Posts is returned.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_id The CiviCRM Activity ID.
	 * @param string  $post_type The WordPress Post Type.
	 * @return array|bool $posts An array of Post IDs, or false on failure.
	 */
	public function get_by_activity_id( $activity_id, $post_type = 'any' ) {

		// Init as failed.
		$posts = false;

		// Bail if there's no Activity ID.
		if ( empty( $activity_id ) ) {
			return $posts;
		}

		// Define args for query.
		$args = [
			'post_type'      => $post_type,
			// 'post_status' => 'publish',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'       => $this->activity_id_key,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value'     => (string) $activity_id,
			'posts_per_page' => -1,
		];

		// Do query.
		$query = new WP_Query( $args );

		// Do the loop.
		if ( $query->have_posts() ) {
			foreach ( $query->get_posts() as $found ) {

				if ( 'any' === $post_type ) {
					// Add if we want *all* Posts.
					$posts[] = $found->ID;
				} elseif ( $found->post_type === $post_type ) {
					// Grab what should be the only Post.
					$posts[] = $found->ID;
					break;
				}

			}
		}

		// Reset Post data just in case.
		wp_reset_postdata();

		// --<
		return $posts;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create the WordPress Post when a CiviCRM Activity is being synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Activity data.
	 */
	public function activity_sync( $args ) {

		// Bail if this is not an Activity.
		if ( 'Activity' !== $args['objectName'] ) {
			return;
		}

		// Bail if this Activity's Activity Type is not mapped.
		$post_type = $this->acf_loader->civicrm->activity->is_mapped( $args['objectRef'] );
		if ( false === $post_type ) {
			return;
		}

		// Handle the Post Type.
		$this->activity_sync_to_post( $args, $post_type );

	}

	/**
	 * Create a WordPress Post when a CiviCRM Activity is being synced.
	 *
	 * @since 0.4
	 *
	 * @param array  $args The array of CiviCRM Activity data.
	 * @param string $post_type The WordPress Post Type.
	 */
	public function activity_sync_to_post( $args, $post_type ) {

		// Bail if this is not an Activity.
		if ( 'Activity' !== $args['objectName'] ) {
			return;
		}

		// Backfill the Activity data.
		$args['objectRef'] = $this->acf_loader->civicrm->activity->backfill( $args['objectRef'] );

		// Get the Post ID for this Activity.
		$post_id = $this->acf_loader->civicrm->activity->is_mapped_to_post( $args['objectRef'], $post_type );

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id']   = $post_id;

		/**
		 * Broadcast that a WordPress Post is about to be synced from Activity details.
		 *
		 * @since 0.5.9
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/activity/sync/pre', $args );

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( false === $post_id ) {
			$post_id = $this->create_from_activity( $args['objectRef'], $post_type );
		} else {
			$this->update_from_activity( $args['objectRef'], $post_id );
		}

		// Overwrite Post ID.
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been synced from Activity details.
		 *
		 * Used internally to:
		 *
		 * * Update the ACF Fields for the WordPress Post.
		 * * Update the Terms for the WordPress Post.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/activity/sync', $args );

	}

	/**
	 * Create a WordPress Post when a CiviCRM Activity has been created.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function activity_created( $args ) {

		// Bail if this is not an Activity.
		if ( 'Activity' !== $args['objectName'] ) {
			return;
		}

		// Backfill the Activity data.
		$args['objectRef'] = $this->acf_loader->civicrm->activity->backfill( $args['objectRef'] );

		// Bail if this Activity's Activity Type is not mapped.
		$post_type = $this->acf_loader->civicrm->activity->is_mapped( $args['objectRef'] );
		if ( false === $post_type ) {
			return;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Check if the Post ID for this Activity already exists.
		$post_id = $this->acf_loader->civicrm->activity->is_mapped_to_post( $args['objectRef'], $post_type );

		/*
		 * Exclude "reverse" create procedure when a WordPress Post is the
		 * originating Entity and the Post Type matches.
		 *
		 * This is because - although there isn't a Post ID yet - there
		 * cannot be more than one Post of a particular Post Type per Activity.
		 *
		 * Instead, the Activity ID needs to be reverse synced to the Post.
		 */
		if ( 'post' === $entity['entity'] && $post_type === $entity['type'] ) {

			// Save correspondence and bail.
			$this->activity_id_set( $entity['id'], $args['objectId'] );
			return;

		}

		// Remove WordPress Post callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_post_remove();

		// Create the WordPress Post.
		if ( false === $post_id ) {
			$post_id = $this->create_from_activity( $args['objectRef'], $post_type );
		}

		// Reinstate WordPress Post callbacks.
		$this->acf_loader->mapper->hooks_wordpress_post_add();

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id']   = $post_id;

		/**
		 * Broadcast that a WordPress Post has been updated from Activity details.
		 *
		 * Used internally to:
		 *
		 * * Update the ACF Fields for the WordPress Post
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/activity/created', $args );

	}

	/**
	 * Update a WordPress Post when a CiviCRM Activity has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function activity_edited( $args ) {

		// Bail if this is not an Activity.
		if ( 'Activity' !== $args['objectName'] ) {
			return;
		}

		// Backfill the Activity data.
		$args['objectRef'] = $this->acf_loader->civicrm->activity->backfill( $args['objectRef'] );

		// Bail if this Activity's Activity Type is not mapped.
		$post_type = $this->acf_loader->civicrm->activity->is_mapped( $args['objectRef'] );
		if ( false === $post_type ) {
			return;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Get the Post ID for this Activity.
		$post_id = $this->acf_loader->civicrm->activity->is_mapped_to_post( $args['objectRef'], $post_type );

		// Exclude "reverse" edits when a Post is the originator.
		if ( 'post' === $entity['entity'] && (int) $post_id === (int) $entity['id'] ) {
			return;
		}

		// Remove WordPress Post callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_post_remove();

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( false === $post_id ) {
			$post_id = $this->create_from_activity( $args['objectRef'], $post_type );
		} else {
			$this->update_from_activity( $args['objectRef'], $post_id );
		}

		// Reinstate WordPress Post callbacks.
		$this->acf_loader->mapper->hooks_wordpress_post_add();

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id']   = $post_id;

		/**
		 * Broadcast that a WordPress Post has been updated from Activity details.
		 *
		 * Used internally to:
		 *
		 * * Update the ACF Fields for the WordPress Post
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/activity/edited', $args );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create a CiviCRM Activity from a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param array  $activity The CiviCRM Activity data.
	 * @param string $post_type The name of Post Type.
	 * @return integer|bool $post_id The WordPress Post ID, or false on failure.
	 */
	public function create_from_activity( $activity, $post_type ) {

		// Maybe cast Activity data as object.
		if ( is_array( $activity ) ) {
			$activity = (object) $activity;
		}

		// De-nullify critical values.
		$activity->subject = $this->plugin->civicrm->denullify( $activity->subject );
		$activity->details = $this->plugin->civicrm->denullify( $activity->details );

		// Define basic Post data.
		$args = [
			'post_status'           => 'publish',
			'post_parent'           => 0,
			'comment_status'        => 'closed',
			'ping_status'           => 'closed',
			'to_ping'               => '', // Quick fix for Windows.
			'pinged'                => '', // Quick fix for Windows.
			'post_content_filtered' => '', // Quick fix for Windows.
			'post_excerpt'          => '', // Quick fix for Windows.
			'menu_order'            => 0,
			'post_type'             => $post_type,
			'post_title'            => $activity->subject,
			'post_content'          => $activity->details,
		];

		/**
		 * Filters the arguments used to create a Post from an Activity.
		 *
		 * @since 0.7.2
		 *
		 * @param array $args The arguments used to create a Post.
		 * @param object $activity The CiviCRM Activity data.
		 */
		$args = apply_filters( 'cwps/acf/post/activity/create/args', $args, $activity );

		// Insert the Post into the database.
		$post_id = wp_insert_post( $args );

		// Bail on failure.
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Save correspondence.
		$this->activity_id_set( $post_id, $activity->id );

		// We need to force ACF to create Fields for the Post.

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// If there are some, prime them with an empty string.
		if ( ! empty( $acf_fields ) ) {
			foreach ( $acf_fields as $field_group ) {
				foreach ( $field_group as $selector => $activity_field ) {
					$this->acf_loader->acf->field->value_update( $selector, '', $post_id );
				}
			}
		}

		// --<
		return $post_id;

	}

	/**
	 * Sync a CiviCRM Activity with a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param array   $activity The CiviCRM Activity data.
	 * @param integer $existing_id The numeric ID of the Post.
	 * @param WP_Post $post The WordPress Post object if it exists.
	 * @return integer|bool $post_id The WordPress Post ID, or false on failure.
	 */
	public function update_from_activity( $activity, $existing_id, $post = null ) {

		// Maybe cast Activity data as object.
		if ( is_array( $activity ) ) {
			$activity = (object) $activity;
		}

		// De-nullify critical values.
		$activity->subject = $this->plugin->civicrm->denullify( $activity->subject );
		$activity->details = $this->plugin->civicrm->denullify( $activity->details );

		// Define args to update the Post.
		$args = [
			'ID'           => $existing_id,
			'post_title'   => $activity->subject,
			'post_content' => $activity->details,
		];

		// Overwrite Permalink if the current Post Title is empty.
		if ( ! is_null( $post ) && $post instanceof WP_Post ) {
			if ( empty( $post->post_title ) ) {
				$args['post_name'] = sanitize_title( $activity->subject );
			}
		}

		/**
		 * Filters the arguments used to update a Post from an Activity.
		 *
		 * @since 0.7.2
		 *
		 * @param array $args The arguments used to update a Post.
		 * @param object $activity The CiviCRM Activity data.
		 */
		$args = apply_filters( 'cwps/acf/post/activity/update/args', $args, $activity );

		// Update the Post.
		$post_id = wp_update_post( $args, true );

		// Bail on failure.
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// --<
		return $post_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Check if a Post is mapped to a Participant.
	 *
	 * @since 0.5
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return integer|bool $is_mapped The ID of the CiviCRM Participant if the Post is mapped, false otherwise.
	 */
	public function is_mapped_to_participant( $post_id ) {

		// Get the WordPress Entity.
		$entity = $this->acf_loader->acf->field->entity_type_get( $post_id );

		// Bail if it's not a Post.
		if ( 'post' !== $entity ) {
			return;
		}

		// Get the Participant ID (or boolean false) from Post meta.
		$is_mapped = $this->participant_id_get( $post_id );

		// --<
		return $is_mapped;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Participant ID for a given WordPress Post ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return integer $participant_id The CiviCRM Participant ID, or false if none exists.
	 */
	public function participant_id_get( $post_id ) {

		// Get the Participant ID.
		$existing_id = get_post_meta( $post_id, $this->participant_id_key, true );

		// Does this Post have a Participant ID?
		if ( empty( $existing_id ) ) {
			$participant_id = false;
		} else {
			$participant_id = $existing_id;
		}

		// --<
		return $participant_id;

	}

	/**
	 * Set the CiviCRM Participant ID for a given WordPress Post ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @param integer $participant_id The CiviCRM Participant ID.
	 */
	public function participant_id_set( $post_id, $participant_id ) {

		// Store the Participant ID.
		add_post_meta( $post_id, $this->participant_id_key, $participant_id, true );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the WordPress Post ID for a given CiviCRM Participant ID and Post Type.
	 *
	 * If no Post Type is provided then an array of all synced Posts is returned.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_id The CiviCRM Participant ID.
	 * @param string  $post_type The WordPress Post Type.
	 * @return array|bool $posts An array of Post IDs, or false on failure.
	 */
	public function get_by_participant_id( $participant_id, $post_type = 'any' ) {

		// Init Posts array.
		$posts = [];

		// Bail if there's no Participant ID.
		if ( empty( $participant_id ) ) {
			return false;
		}

		// Define args for query.
		$args = [
			'post_type'      => $post_type,
			// 'post_status' => 'publish',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'       => $this->participant_id_key,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value'     => (string) $participant_id,
			'posts_per_page' => -1,
		];

		// Do query.
		$query = new WP_Query( $args );

		// Do the loop.
		if ( $query->have_posts() ) {
			foreach ( $query->get_posts() as $found ) {

				if ( 'any' === $post_type ) {
					// Add if we want *all* Posts.
					$posts[] = $found->ID;
				} elseif ( $found->post_type === $post_type ) {
					// Grab what should be the only Post.
					$posts[] = $found->ID;
					break;
				}

			}
		}

		// Reset Post data just in case.
		wp_reset_postdata();

		// --<
		return $posts;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create the WordPress Post when a CiviCRM Participant is being synced.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Participant data.
	 */
	public function participant_sync( $args ) {

		// Bail if this is not a Participant.
		if ( 'Participant' !== $args['objectName'] ) {
			return;
		}

		// Bail if this Participant is not mapped.
		$post_types = $this->acf_loader->civicrm->participant->is_mapped( $args['objectRef'] );
		if ( false === $post_types ) {
			return;
		}

		// Handle each Post Type in turn.
		foreach ( $post_types as $post_type ) {
			$this->participant_sync_to_post( $args, $post_type );
		}

	}

	/**
	 * Create a WordPress Post when a CiviCRM Participant is being synced.
	 *
	 * @since 0.5
	 *
	 * @param array  $args The array of CiviCRM Participant data.
	 * @param string $post_type The WordPress Post Type.
	 */
	public function participant_sync_to_post( $args, $post_type ) {

		// Bail if this is not a Participant.
		if ( 'Participant' !== $args['objectName'] ) {
			return;
		}

		// Backfill the Participant data.
		$args['objectRef'] = $this->acf_loader->civicrm->participant->backfill( $args['objectRef'] );

		// Get the Post ID for this Participant.
		$post_id = $this->acf_loader->civicrm->participant->is_mapped_to_post( $args['objectRef'], $post_type );

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id']   = $post_id;

		/**
		 * Broadcast that a WordPress Post is about to be synced from Participant details.
		 *
		 * @since 0.5.9
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/participant/sync/pre', $args );

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( false === $post_id ) {
			$post_id = $this->create_from_participant( $args['objectRef'], $post_type );
		} else {
			$this->update_from_participant( $args['objectRef'], $post_id );
		}

		// Overwrite Post ID.
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
		if ( 'Participant' !== $args['objectName'] ) {
			return;
		}

		// Backfill the Participant data.
		$args['objectRef'] = $this->acf_loader->civicrm->participant->backfill( $args['objectRef'] );

		// Bail if this Participant is not mapped.
		$post_types = $this->acf_loader->civicrm->participant->is_mapped( $args['objectRef'] );
		if ( false === $post_types ) {
			return;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Handle each Post Type in turn.
		foreach ( $post_types as $post_type ) {

			// Check if the Post ID for this Participant already exists.
			$post_id = $this->acf_loader->civicrm->participant->is_mapped_to_post( $args['objectRef'], $post_type );

			/*
			 * Exclude "reverse" create procedure when a WordPress Post is the
			 * originating Entity and the Post Type matches.
			 *
			 * This is because - although there isn't a Post ID yet - there
			 * cannot be more than one Post of a particular Post Type per Participant.
			 *
			 * Instead, the Participant ID needs to be reverse synced to the Post.
			 */
			if ( 'post' === $entity['entity'] && $post_type === $entity['type'] ) {

				// Save correspondence and skip.
				$this->participant_id_set( $entity['id'], $args['objectId'] );
				continue;

			}

			// Remove WordPress Post callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_post_remove();

			// Create the WordPress Post.
			if ( false === $post_id ) {
				$post_id = $this->create_from_participant( $args['objectRef'], $post_type );
			}

			// Reinstate WordPress Post callbacks.
			$this->acf_loader->mapper->hooks_wordpress_post_add();

			// Add our data to the params.
			$args['post_type'] = $post_type;
			$args['post_id']   = $post_id;

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
		if ( 'Participant' !== $args['objectName'] ) {
			return;
		}

		// Backfill the Participant data.
		$args['objectRef'] = $this->acf_loader->civicrm->participant->backfill( $args['objectRef'] );

		// Bail if this Participant is not mapped.
		$post_types = $this->acf_loader->civicrm->participant->is_mapped( $args['objectRef'] );
		if ( false === $post_types ) {
			return;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Handle each Post Type in turn.
		foreach ( $post_types as $post_type ) {

			// Get the Post ID for this Participant.
			$post_id = $this->acf_loader->civicrm->participant->is_mapped_to_post( $args['objectRef'], $post_type );

			// Exclude "reverse" edits when a Post is the originator.
			if ( 'post' === $entity['entity'] && (int) $post_id === (int) $entity['id'] ) {
				continue;
			}

			// Remove WordPress Post callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_post_remove();

			// Create the WordPress Post if it doesn't exist, otherwise update.
			if ( false === $post_id ) {
				$post_id = $this->create_from_participant( $args['objectRef'], $post_type );
			} else {
				$this->update_from_participant( $args['objectRef'], $post_id );
			}

			// Reinstate WordPress Post callbacks.
			$this->acf_loader->mapper->hooks_wordpress_post_add();

			// Add our data to the params.
			$args['post_type'] = $post_type;
			$args['post_id']   = $post_id;

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

	}

	/**
	 * A CiviCRM Contact's Participant Record is about to be deleted.
	 *
	 * Before an Participant Record is deleted, we need to retrieve the
	 * Participant Record because the data passed via "civicrm_post" only
	 *  contains the ID of the Participant Record.
	 *
	 * This is not required when creating or editing an Participant Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function participant_pre_delete( $args ) {

		// We just need the Participant ID.
		$participant_id = (int) $args['objectId'];

		// Grab the Participant record from the database.
		$participant_pre = $this->acf_loader->civicrm->participant->get_by_id( $participant_id );

		// Maybe cast previous Participant data as object and stash in a property.
		if ( ! is_object( $participant_pre ) ) {
			$participant_pre = (object) $participant_pre;
		}

		// Store for later use.
		$this->bridging_array[ $participant_id ] = $participant_pre;

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
		if ( 'Participant' !== $args['objectName'] ) {
			return;
		}

		// We just need the Participant ID.
		$participant_id = (int) $args['objectId'];

		// Get the existing Participant Record.
		$participant_pre = [];
		if ( ! empty( $this->bridging_array[ $participant_id ] ) ) {
			$participant_pre = $this->bridging_array[ $participant_id ];
			unset( $this->bridging_array[ $participant_id ] );
		}

		// Bail if we don't have a pre-delete Participant record.
		if ( empty( $participant_pre ) || $participant_id !== (int) $participant_pre->id ) {
			return;
		}

		// Overwrite objectRef.
		$args['objectRef'] = $participant_pre;

		// Bail if this Participant is not mapped.
		$post_types = $this->acf_loader->civicrm->participant->is_mapped( $args['objectRef'] );
		if ( false === $post_types ) {
			return;
		}

		// Handle each Post Type in turn.
		foreach ( $post_types as $post_type ) {

			// Find the Post ID of this Post Type that this Participant is synced with.
			$post_id  = false;
			$post_ids = $this->get_by_participant_id( $args['objectId'], $post_type );
			if ( ! empty( $post_ids ) ) {
				$post_id = array_pop( $post_ids );
			}
			if ( false === $post_id ) {
				continue;
			}

			// Remove WordPress Post callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_post_remove();

			// Delete the WordPress Post if it exists.
			$this->delete_from_participant( $post_id );

			// Reinstate WordPress Post callbacks.
			$this->acf_loader->mapper->hooks_wordpress_post_add();

			// Add our data to the params.
			$args['post_type'] = $post_type;
			$args['post_id']   = $post_id;

			/**
			 * Broadcast that a WordPress Post has been deleted from Participant details.
			 *
			 * @since 0.5
			 *
			 * @param array $args The array of CiviCRM and discovered params.
			 */
			do_action( 'cwps/acf/post/participant/deleted', $args );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create a CiviCRM Participant from a WordPress Post.
	 *
	 * @since 0.5
	 *
	 * @param array  $participant The CiviCRM Participant data.
	 * @param string $post_type The name of Post Type.
	 * @return integer|bool $post_id The WordPress Post ID, or false on failure.
	 */
	public function create_from_participant( $participant, $post_type ) {

		// Maybe cast Participant data as object.
		if ( is_array( $participant ) ) {
			$participant = (object) $participant;
		}

		// Retrieve critical values.
		$contact = $this->plugin->civicrm->contact->get_by_id( $participant->contact_id );
		if ( false === $contact ) {
			return false;
		}

		// Retrieve Event.
		$event = $this->acf_loader->civicrm->event->get_by_id( $participant->event_id );
		if ( false === $event ) {
			return false;
		}

		// Build Post Title.
		$title = $contact['display_name'] . ' :: ' . $event['title'];
		if ( ! empty( $event['event_start_date'] ) ) {
			$title .= ' :: ' . $event['event_start_date'];
		}

		// Define basic Post data.
		$args = [
			'post_status'           => 'publish',
			'post_parent'           => 0,
			'comment_status'        => 'closed',
			'ping_status'           => 'closed',
			'to_ping'               => '', // Quick fix for Windows.
			'pinged'                => '', // Quick fix for Windows.
			'post_content_filtered' => '', // Quick fix for Windows.
			'post_excerpt'          => '', // Quick fix for Windows.
			'menu_order'            => 0,
			'post_type'             => $post_type,
			'post_title'            => $title,
			'post_content'          => '',
		];

		// Check if the Contact is in the CiviCRM Trash.
		$contact_is_deleted = false;
		if ( isset( $contact['contact_is_deleted'] ) && 1 === (int) $contact['contact_is_deleted'] ) {
			$contact_is_deleted = true;
		}

		// Set Post status to Pending.
		if ( $contact_is_deleted ) {
			$args['post_status'] = 'pending';
		}

		/**
		 * Filter the arguments used to create a Post from a Participant.
		 *
		 * @since 0.5
		 *
		 * @param array  $args The arguments used to create a Post.
		 * @param object $participant The CiviCRM Participant data.
		 */
		$args = apply_filters( 'cwps/acf/post/participant/create/args', $args, $participant );

		// Insert the Post into the database.
		$post_id = wp_insert_post( $args );

		// Bail on failure.
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// Save correspondence.
		$this->participant_id_set( $post_id, $participant->id );

		// We need to force ACF to create Fields for the Post.

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// If there are some, prime them with an empty string.
		if ( ! empty( $acf_fields ) ) {
			foreach ( $acf_fields as $field_group ) {
				foreach ( $field_group as $selector => $participant_field ) {
					$this->acf_loader->acf->field->value_update( $selector, '', $post_id );
				}
			}
		}

		// --<
		return $post_id;

	}

	/**
	 * Sync a CiviCRM Participant with a WordPress Post.
	 *
	 * @since 0.5
	 *
	 * @param array   $participant The CiviCRM Participant data.
	 * @param integer $existing_id The numeric ID of the Post.
	 * @param WP_Post $post The WordPress Post object if it exists.
	 * @return integer|bool $post_id The WordPress Post ID, or false on failure.
	 */
	public function update_from_participant( $participant, $existing_id, $post = null ) {

		// Maybe cast Participant data as object.
		if ( is_array( $participant ) ) {
			$participant = (object) $participant;
		}

		// Retrieve Contact.
		$contact = $this->plugin->civicrm->contact->get_by_id( $participant->contact_id );
		if ( false === $contact ) {
			return false;
		}

		// Retrieve Event.
		$event = $this->acf_loader->civicrm->event->get_by_id( $participant->event_id );
		if ( false === $event ) {
			return false;
		}

		// Build Post Title.
		$title = $contact['display_name'] . ' :: ' . $event['title'];
		if ( ! empty( $event['event_start_date'] ) ) {
			$title .= ' :: ' . $event['event_start_date'];
		}

		// Define args to update the Post.
		$args = [
			'ID'           => $existing_id,
			'post_title'   => $title,
			'post_content' => '',
		];

		// Overwrite Permalink if the current Post slug is auto-generated.
		if ( ! is_null( $post ) && $post instanceof WP_Post ) {
			$args['post_name'] = sanitize_title( $title );
		}

		/**
		 * Filter the arguments used to update a Post from a Participant.
		 *
		 * @since 0.5
		 *
		 * @param array  $args The arguments used to update a Post.
		 * @param object $participant The CiviCRM Participant data.
		 */
		$args = apply_filters( 'cwps/acf/post/participant/update/args', $args, $participant );

		// Update the Post.
		$post_id = wp_update_post( $args, true );

		// Bail on failure.
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// --<
		return $post_id;

	}

	/**
	 * Delete a WordPress "Participant" Post.
	 *
	 * @since 0.5
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return WP_Post|bool $post The deleted WordPress Post object, or false on failure.
	 */
	public function delete_from_participant( $post_id ) {

		// Delete the Post.
		$post = wp_delete_post( $post_id, true );

		// Bail on failure.
		if ( is_wp_error( $post ) || empty( $post ) ) {
			return false;
		}

		// --<
		return $post;

	}

	/**
	 * Check if a WordPress "Participant" Post Title should be synced.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function participant_maybe_sync_title( $args ) {

		// Bail if no Post in args.
		if ( ! ( $args['post'] instanceof WP_Post ) ) {
			return;
		}

		// Maybe cast Participant data as object.
		if ( ! is_object( $args['participant'] ) ) {
			$participant = (object) $args['participant'];
		} else {
			$participant = $args['participant'];
		}

		// Bail if no Participant Contact reference.
		if ( empty( $participant->contact_id ) ) {
			return;
		}

		// Bail if no Participant Event reference.
		if ( empty( $participant->event_id ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $args['post']->ID ) ) {
			return;
		}

		// Remove WordPress callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_remove();

		// Update the Post Title (and maybe the Post Permalink).
		$this->update_from_participant( $args['participant'], $args['post']->ID, $args['post'] );

		// Reinstate WordPress callbacks.
		$this->acf_loader->mapper->hooks_wordpress_add();

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Query for the Contact ID that an ACF "Post ID" is mapped to.
	 *
	 * @since 0.4
	 *
	 * @param bool           $contact_id False, since we're asking for a Contact ID.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param string         $entity The kind of WordPress Entity.
	 * @return integer|bool $contact_id The mapped Contact ID, or false if not mapped.
	 */
	public function query_contact_id( $contact_id, $post_id, $entity ) {

		// Bail early if a Contact ID has been found.
		if ( false !== $contact_id ) {
			return $contact_id;
		}

		// Bail early if not a Post Entity.
		if ( 'post' !== $entity ) {
			return $contact_id;
		}

		// Try and get Contact ID.
		$contact_id = $this->is_mapped_to_contact( $post_id );

		// --<
		return $contact_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Checks if a WordPress Post should be synced to CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param WP_Post $post The WordPress Post object.
	 * @return WP_Post|bool $post The WordPress Post object, or false if not allowed.
	 */
	public function should_be_synced( $post ) {

		// Assume Post should be synced.
		$should_be_synced = true;

		// Do not sync if no Post object.
		if ( ! $post ) {
			$should_be_synced = false;
		}

		// Do not sync if the User cannot edit the Post.
		if ( $should_be_synced ) {
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				$should_be_synced = false;
			}
		}

		// Do not sync if this is a draft or an auto-draft.
		if ( $should_be_synced ) {
			if ( 'draft' === $post->post_status || 'auto-draft' === $post->post_status ) {
				$should_be_synced = false;
			}
		}

		// Do not sync if this Post is in the Trash.
		if ( $should_be_synced ) {
			if ( 'trash' === $post->post_status ) {
				$should_be_synced = false;
			}
		}

		// Do not sync if this is an autosave routine.
		if ( $should_be_synced ) {
			if ( wp_is_post_autosave( $post ) ) {
				$should_be_synced = false;
			}
		}

		// Do not sync if this is a revision.
		if ( $should_be_synced ) {
			if ( wp_is_post_revision( $post ) ) {
				$should_be_synced = false;
			}
		}

		/**
		 * Filters whether or not a Post should be synced to CiviCRM.
		 *
		 * Of particular interest here is the "draft" Post status. Posts with "draft"
		 * status are not synced to CiviCRM to allow Post authors to complete the Post
		 * before syncing to CiviCRM.
		 *
		 * There is, however, a need to set an "unpublished" status on the Post when the
		 * CiviCRM Entity is trashed. We can't use "draft", because when a CiviCRM Entity
		 * is trashed and and edits are made to the Post, they will not sync to CiviCRM.
		 * If the Entity is later taken out of the trash, there will be a mismatch between
		 * the data in the Post and the data in the CiviCRM Entity.
		 *
		 * Since 0.7.2 we set Posts to "pending" when the CiviCRM Entity is trashed, but
		 * there needs to be documentation about this because sync will happen when this
		 * status is set for the Post. It should *not* be set until sync has happened
		 * and the CiviCRM Entity has sufficient data.
		 *
		 * @since 0.7.2
		 *
		 * @param bool    $should_be_synced True if the Post should be synced, false otherwise.
		 * @param WP_Post $post The WordPress Post object.
		 */
		$should_be_synced = apply_filters( 'cwps/acf/post/should_be_synced', $should_be_synced, $post );

		// Return the Post object if it should be synced.
		if ( $should_be_synced ) {
			return $post;
		}

		// Do not sync.
		return false;

	}

	/**
	 * Check if a WordPress Post Title should be synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function maybe_sync_title( $args ) {

		// Bail if no Post in args.
		if ( ! ( $args['post'] instanceof WP_Post ) ) {
			return;
		}

		// Maybe cast Contact data as array.
		if ( is_object( $args['contact'] ) ) {
			$contact = (array) $args['contact'];
		} else {
			$contact = $args['contact'];
		}

		// Bail if no Display Name.
		if ( empty( $contact['display_name'] ) ) {
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $args['post']->ID ) ) {
			return;
		}

		// Bail if the Display Name and the Title match.
		if ( $args['post']->post_title === $contact['display_name'] ) {
			return;
		}

		// Remove WordPress callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_remove();

		// Update the Post Title (and maybe the Post Permalink).
		$this->update_from_contact( $args['contact'], $args['post']->ID, $args['post'] );

		// Reinstate WordPress callbacks.
		$this->acf_loader->mapper->hooks_wordpress_add();

	}

}
