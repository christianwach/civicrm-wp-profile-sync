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
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Post Taxonomy object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $tax The Post Taxonomy object.
	 */
	public $tax;

	/**
	 * Post meta Contact ID key.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $contact_id_key The Post meta Contact ID key.
	 */
	public $contact_id_key = '_civicrm_acf_integration_post_contact_id';

	/**
	 * Post meta Activity ID key.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $activity_id_key The Post meta Contact ID key.
	 */
	public $activity_id_key = '_civicrm_acf_integration_post_activity_id';



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store reference to ACF Loader object.
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

		// Listen for queries for a mapped Contact ID.
		add_filter( 'cwps/acf/query_contact_id', [ $this, 'query_contact_id' ], 10, 3 );

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Listen for events from our Mapper that require Post updates.
		add_action( 'cwps/acf/mapper/contact/created', [ $this, 'contact_created' ], 10 );
		add_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );
		add_action( 'cwps/acf/mapper/activity/created', [ $this, 'activity_created' ], 10 );
		add_action( 'cwps/acf/mapper/activity/edited', [ $this, 'activity_edited' ], 10 );

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/contact/created', [ $this, 'contact_created' ], 10 );
		remove_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/activity/created', [ $this, 'activity_created' ], 10 );
		remove_action( 'cwps/acf/mapper/activity/edited', [ $this, 'activity_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Register meta boxes.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The WordPress Post Type.
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
		if ( $contact_id === false ) {
			return;
		}

		// Check permission to view this Contact.
		if ( ! $this->acf_loader->civicrm->contact->user_can_view( $contact_id ) ) {
			return;
		}

		// Create CiviCRM Settings and Sync metabox.
		add_meta_box(
			'cwps_acf_metabox',
			__( 'CiviCRM ACF Integration', 'civicrm-wp-profile-sync' ),
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
		if ( $contact_id === false ) {
			return;
		}

		// Get the URL for this Contact.
		$url = $this->acf_loader->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Construct link.
		$link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'View this Contact in CiviCRM', 'civicrm-wp-profile-sync' )
		);

		// Show it.
		echo '<p>' . $link . '</p>';

	}



	// -------------------------------------------------------------------------



	/**
	 * Add a add a Menu Item to the CiviCRM Contact's "Actions" menu.
	 *
	 * @since 0.4
	 *
	 * @param array $actions The array of actions from which the menu is rendered.
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
		$contact = $this->acf_loader->civicrm->contact->get_by_id( $contact_id );
		if ( $contact === false ) {
			return;
		}

		// Bail if none of this Contact's Contact Types is mapped.
		$post_types = $this->acf_loader->civicrm->contact->is_mapped( $contact );
		if ( $post_types === false ) {
			return;
		}

		// Init weight.
		$weight = 30;

		// Handle each Post Type in turn.
		foreach( $post_types AS $post_type ) {

			// Get the Post ID that this Contact is mapped to.
			$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $contact, $post_type );

			if ( $post_id === false ) {
				continue;
			}

			// Get Post Type label.
			$label = $this->acf_loader->post_type->singular_label_get( $post_type );

			// Build view title.
			$view_title = sprintf( __( 'View %s in WordPress', 'civicrm-wp-profile-sync' ), $label );

			// Build "view" link.
			$actions['otherActions']['wp-view-' . $post_type] = [
				'title' => $view_title,
				'ref' => 'civicrm-wp-view-' . $post_type,
				'weight' => $weight,
				'href' => get_permalink( $post_id ),
				'tab' => 'wp-view-' . $post_type,
				'class' => 'wp-view',
				'icon' => 'crm-i fa-eye',
				'key' => 'wp-view-' . $post_type,
			];

			// Check User can edit.
			if ( current_user_can( 'edit_post', $post_id ) ) {

				// Bump weight.
				$weight++;

				// Build edit title.
				$edit_title = sprintf( __( 'Edit %s in WordPress', 'civicrm-wp-profile-sync' ), $label );

				// Build "edit" link.
				$actions['otherActions']['wp-edit-' . $post_type] = [
					'title' => $edit_title,
					'ref' => 'civicrm-wp-edit-' . $post_type,
					'weight' => $weight,
					'href' => get_edit_post_link( $post_id ),
					'tab' => 'wp-edit-' . $post_type,
					'class' => 'wp-edit',
					'icon' => 'crm-i fa-edit',
					'key' => 'wp-edit-' . $post_type,
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
	 * @param array $components The active CiviCRM Conponents.
	 */
	public function menu_item_add_to_cau( $id, $components ) {

		// Access WordPress admin bar.
		global $wp_admin_bar, $post;

		// Bail if the current screen is not an Edit screen.
		if ( is_admin() ) {
			$screen = get_current_screen();
			if ( $screen instanceof WP_Screen AND $screen->base != 'post' ) {
				return;
			}
		}

		// Bail if there's no Post.
		if ( empty( $post ) ) {
			return;
		}

		// Bail if there's no Post and it's WordPress admin.
		if ( empty( $post ) AND is_admin() ) {
			return;
		}

		// Get Contact ID.
		$contact_id = $this->contact_id_get( $post->ID );

		// Bail if we don't get one for some reason.
		if ( $contact_id === false ) {
			return;
		}

		// Check permission to view this Contact.
		if ( ! $this->acf_loader->civicrm->contact->user_can_view( $contact_id ) ) {
			return;
		}

		// Get the URL for this Contact.
		$url = $this->acf_loader->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Add item to Edit menu.
		$wp_admin_bar->add_node( [
			'id' => 'cau-edit',
			'parent' => 'edit',
			'title' => __( 'Edit in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href' => $url,
		] );

		// Add item to View menu.
		$wp_admin_bar->add_node( [
			'id' => 'cau-view',
			'parent' => 'view',
			'title' => __( 'View in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href' => $url,
		] );

		// Add item to CAU menu.
		$wp_admin_bar->add_node( [
			'id' => 'cau-0',
			'parent' => $id,
			'title' => __( 'View in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href' => $url,
		] );

	}



	/**
	 * Add a link to action links on the Pages and Posts list tables.
	 *
	 * @since 0.4
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

		// Get Contact ID.
		$contact_id = $this->contact_id_get( $post->ID );

		// Bail if we don't get one for some reason.
		if ( $contact_id === false ) {
			return $actions;
		}

		// Check permission to view this Contact.
		if ( ! $this->acf_loader->civicrm->contact->user_can_view( $contact_id ) ) {
			return $actions;
		}

		// Get the URL for this Contact.
		$url = $this->acf_loader->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );

		// Add link to actions.
		$actions['civicrm'] = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $url ),
			esc_html__( 'CiviCRM', 'civicrm-wp-profile-sync' )
		);

		// --<
		return $actions;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if a Post is mapped to a Contact.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return integer|boolean $is_mapped The ID of the CiviCRM Contact if the Post is mapped, false otherwise.
	 */
	public function is_mapped_to_contact( $post_id ) {

		// Get the WordPress Entity.
		$entity = $this->acf_loader->acf->field->entity_type_get( $post_id );

		// Bail if it's not a Post.
		if ( $entity !== 'post' ) {
			return false;
		}

		// Get the Contact ID (or boolean false) from Post meta.
		$is_mapped = $this->contact_id_get( $post_id );

		// --<
		return $is_mapped;

	}



	// -------------------------------------------------------------------------



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



	// -------------------------------------------------------------------------



	/**
	 * Get the WordPress Post ID(s) for a given CiviCRM Contact ID and Post Type.
	 *
	 * If no Post Type is provided then an array of all synced Posts is returned.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The CiviCRM Contact ID.
	 * @param string $post_type The WordPress Post Type.
	 * @return array|boolean $posts An array of Post IDs, or false on failure.
	 */
	public function get_by_contact_id( $contact_id, $post_type = 'any' ) {

		// Init as failed.
		$posts = false;

		/*
		 * Define args for query.
		 *
		 * We need to query multiple Post Statuses because we need to keep the
		 * linkage between the CiviCRM Entity and the Post throughout its
		 * life cycle, e.g.
		 *
		 * - Published: The default status for our purposes.
		 * - Trash: Because we want to avoid a duplicate Post being created.
		 * - Draft: When Posts are moved out of the Trash, this is their status.
		 *
		 * This may need to be revisited.
		 */
		$args = [
			'post_type' => $post_type,
			'post_status' => [ 'publish', 'trash', 'draft' ],
			'no_found_rows' => true,
			'meta_key' => $this->contact_id_key,
			'meta_value' => (string) $contact_id,
			'posts_per_page' => -1,
		];

		// Do query.
		$query = new WP_Query( $args );

		// Do the loop.
		if ( $query->have_posts() ) {
			foreach( $query->get_posts() AS $found ) {

				// Add if we want *all* Posts.
				if ( $post_type === 'any' ) {
					$posts[] = $found->ID;

				// Grab what should be the only Post.
				} elseif ( $found->post_type == $post_type ) {
					$posts[] = $found->ID;
					break;
				}

			}
		}

		// --<
		return $posts;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create the WordPress Posts when a CiviCRM Contact is being synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact data.
	 */
	public function contact_sync( $args ) {

		// Bail if this is not a Contact.
		$top_level_types = $this->acf_loader->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $args['objectName'], $top_level_types ) ) {
			return;
		}

		// Bail if none of this Contact's Contact Types is mapped.
		$post_types = $this->acf_loader->civicrm->contact->is_mapped( $args['objectRef'] );
		if ( $post_types === false ) {
			return;
		}

		// Handle each Post Type in turn.
		foreach( $post_types AS $post_type ) {
			$this->contact_sync_to_post( $args, $post_type );
		}

	}



	/**
	 * Create a WordPress Post when a CiviCRM Contact is being synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact data.
	 * @param string $post_type The WordPress Post Type.
	 */
	public function contact_sync_to_post( $args, $post_type ) {

		// Get the Post ID for this Contact.
		$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $args['objectRef'], $post_type );

		/**
		 * Broadcast that a WordPress Post is about to be synced from Contact details.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/contact_sync_to_post/pre', $args );

		// Remove WordPress callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_remove();

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( $post_id === false ) {
			$post_id = $this->create_from_contact( $args['objectRef'], $post_type );
		} else {
			$this->update_from_contact( $args['objectRef'], $post_id );
		}

		// Reinstate WordPress callbacks.
		$this->acf_loader->mapper->hooks_wordpress_add();

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been synced from Contact details.
		 *
		 * Used internally to:
		 *
		 * - Update the ACF Fields for the WordPress Post.
		 * - Update the Terms for the WordPress Post.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/contact_sync_to_post', $args );

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
		if ( $post_types !== false ) {

			// Get originating Entity.
			$entity = $this->acf_loader->mapper->entity_get();

			// Handle each Post Type in turn.
			foreach( $post_types AS $post_type ) {

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
				if ( $entity['entity'] === 'post' AND $post_type == $entity['type'] ) {

					// Save correspondence and skip to next.
					$this->contact_id_set( $entity['id'], $args['objectId'] );
					continue;

				}

				// Remove WordPress Post callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_post_remove();

				// Create the WordPress Post.
				if ( $post_id === false ) {
					$post_id = $this->create_from_contact( $args['objectRef'], $post_type );
				}

				// Reinstate WordPress Post callbacks.
				$this->acf_loader->mapper->hooks_wordpress_post_add();

				// Add our data to the params.
				$args['post_type'] = $post_type;
				$args['post_id'] = $post_id;

				// TODO: Check if all Fields need sync - at the moment, it's just Contact Fields and Addresses.

				/**
				 * Broadcast that a WordPress Post has been updated from Contact details.
				 *
				 * Used internally to:
				 *
				 * - Update the ACF Fields for the WordPress Post
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
		if ( $post_types !== false ) {

			// Init args for Post Types.
			$post_type_args = $args;

			// Get originating Entity.
			$entity = $this->acf_loader->mapper->entity_get();

			// Handle each Post Type in turn.
			foreach( $post_types AS $post_type ) {

				// Get the Post ID for this Contact.
				$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $args['objectRef'], $post_type );

				// Exclude "reverse" edits when a Post is the originator.
				if ( $entity['entity'] === 'post' AND $post_id == $entity['id'] ) {
					continue;
				}

				// Remove WordPress Post callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_post_remove();

				// Create the WordPress Post if it doesn't exist, otherwise update.
				if ( $post_id === false ) {
					$post_id = $this->create_from_contact( $args['objectRef'], $post_type );
				} else {
					$this->update_from_contact( $args['objectRef'], $post_id );
				}

				// Reinstate WordPress Post callbacks.
				$this->acf_loader->mapper->hooks_wordpress_post_add();

				// Add our data to the params.
				$post_type_args['post_type'] = $post_type;
				$post_type_args['post_id'] = $post_id;

				// TODO: Check if all Fields need sync - at the moment, it's just Contact Fields and Addresses.

				/**
				 * Broadcast that a WordPress Post has been updated from Contact details.
				 *
				 * Used internally to:
				 *
				 * - Update the ACF Fields for the WordPress Post
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
		foreach( $removed_types AS $contact_type ) {

			// Get the mapped Post Type.
			$post_type = $this->acf_loader->civicrm->contact_type->is_mapped_to_post_type( $contact_type );

			// Skip if this Contact Type is not mapped.
			if ( $post_type === false ) {
				continue;
			}

			// Get the associated Post IDs.
			$post_ids = $this->get_by_contact_id( $args['objectId'], $post_type );

			// Skip if there are no associated Post IDs.
			if ( $post_type === false ) {
				continue;
			}

			// Process them.
			foreach( $post_ids AS $post_id ) {

				// Delete the Contact ID meta.
				$this->contact_id_delete( $post_id );

				// Remove WordPress Post callbacks to prevent recursion.
				$this->acf_loader->mapper->hooks_wordpress_post_remove();

				/**
				 * Broadcast that a WordPress Post has been unlinked from a Contact.
				 *
				 * This hook can be used to change the status of the Post to 'draft'
				 * or even to delete the Post entirely.
				 *
				 * @since 0.4
				 *
				 * @param int $post_id The numeric ID of the WordPress Post.
				 * @param int $post_type The WordPress Post Type.
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/post/unlinked', $post_id, $post_type, $args );

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



	// -------------------------------------------------------------------------



	/**
	 * Create a WordPress Post from a CiviCRM Contact.
	 *
	 * @since 0.4
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @param string $post_type The name of Post Type.
	 * @return integer|boolean $post_id The WordPress Post ID, or false on failure.
	 */
	public function create_from_contact( $contact, $post_type ) {

		// Maybe cast Contact data as array.
		if ( is_object( $contact ) ) {
			$contact = (array) $contact;
		}

		// Define basic Post data.
		$args = [
			'post_status' => 'publish',
			'post_parent' => 0,
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'to_ping' => '', // Quick fix for Windows.
			'pinged' => '', // Quick fix for Windows.
			'post_content_filtered' => '', // Quick fix for Windows.
			'post_excerpt' => '', // Quick fix for Windows.
			'menu_order' => 0,
			'post_type' => $post_type,
			'post_title' => $contact['display_name'],
			'post_content' => '',
		];

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
			foreach( $acf_fields AS $field_group ) {
				foreach( $field_group AS $selector => $contact_field ) {
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
	 * @param array $contact The CiviCRM Contact data.
	 * @param integer $existing_id The numeric ID of the Post.
	 * @param WP_Post $post The WordPress Post object if it exists.
	 * @return integer|boolean $post_id The WordPress Post ID, or false on failure.
	 */
	public function update_from_contact( $contact, $existing_id, $post = null ) {

		// Maybe cast Contact data as array.
		if ( is_object( $contact ) ) {
			$contact = (array) $contact;
		}

		// Define args to update the Post.
		$args = [
			'ID' => $existing_id,
			'post_title' => $contact['display_name'],
		];

		// Overwrite Permalink if the current Post Title is empty.
		if ( ! is_null( $post ) AND $post instanceof WP_Post ) {
			if ( empty( $post->post_title ) ) {
				$args['post_name'] = sanitize_title( $contact['display_name'] );
			}
		}

		// Update the Post.
		$post_id = wp_update_post( $args, true );

		// Bail on failure.
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// --<
		return $post_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if a Post is mapped to an Activity.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return integer|boolean $is_mapped The ID of the CiviCRM Activity if the Post is mapped, false otherwise.
	 */
	public function is_mapped_to_activity( $post_id ) {

		// Get the WordPress Entity.
		$entity = $this->acf_loader->acf->field->entity_type_get( $post_id );

		// Bail if it's not a Post.
		if ( $entity !== 'post' ) {
			return;
		}

		// Get the Activity ID (or boolean false) from Post meta.
		$is_mapped = $this->activity_id_get( $post_id );

		// --<
		return $is_mapped;

	}



	// -------------------------------------------------------------------------



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



	// -------------------------------------------------------------------------



	/**
	 * Get the WordPress Post ID for a given CiviCRM Activity ID and Post Type.
	 *
	 * If no Post Type is provided then an array of all synced Posts is returned.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_id The CiviCRM Activity ID.
	 * @param string $post_type The WordPress Post Type.
	 * @return array|boolean $posts An array of Post IDs, or false on failure.
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
			'post_type' => $post_type,
			//'post_status' => 'publish',
			'no_found_rows' => true,
			'meta_key' => $this->activity_id_key,
			'meta_value' => (string) $activity_id,
			'posts_per_page' => -1,
		];

		// Do query.
		$query = new WP_Query( $args );

		// Do the loop.
		if ( $query->have_posts() ) {
			foreach( $query->get_posts() AS $found ) {

				// Add if we want *all* Posts.
				if ( $post_type === 'any' ) {
					$posts[] = $found->ID;

				// Grab what should be the only Post.
				} elseif ( $found->post_type == $post_type ) {
					$posts[] = $found->ID;
					break;
				}

			}
		}

		// --<
		return $posts;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create the WordPress Post when a CiviCRM Activity is being synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Activity data.
	 */
	public function activity_sync( $args ) {

		// Bail if this is not an Activity.
		if ( $args['objectName'] != 'Activity' ) {
			return;
		}

		// Bail if this Activity's Activity Type is not mapped.
		$post_type = $this->acf_loader->civicrm->activity->is_mapped( $args['objectRef'] );
		if ( $post_type === false ) {
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
	 * @param array $args The array of CiviCRM Activity data.
	 * @param string $post_type The WordPress Post Type.
	 */
	public function activity_sync_to_post( $args, $post_type ) {

		// Bail if this is not an Activity.
		if ( $args['objectName'] != 'Activity' ) {
			return;
		}

		// Backfill the Activity data.
		$args['objectRef'] = $this->acf_loader->civicrm->activity->backfill( $args['objectRef'] );

		// Get the Post ID for this Activity.
		$post_id = $this->acf_loader->civicrm->activity->is_mapped_to_post( $args['objectRef'], $post_type );

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( $post_id === false ) {
			$post_id = $this->create_from_activity( $args['objectRef'], $post_type );
		} else {
			$this->update_from_activity( $args['objectRef'], $post_id );
		}

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been synced from Activity details.
		 *
		 * Used internally to:
		 *
		 * - Update the ACF Fields for the WordPress Post.
		 * - Update the Terms for the WordPress Post.
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
		if ( $args['objectName'] != 'Activity' ) {
			return;
		}

		// Backfill the Activity data.
		$args['objectRef'] = $this->acf_loader->civicrm->activity->backfill( $args['objectRef'] );

		// Bail if this Activity's Activity Type is not mapped.
		$post_type = $this->acf_loader->civicrm->activity->is_mapped( $args['objectRef'] );
		if ( $post_type === false ) {
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
		if ( $entity['entity'] === 'post' AND $post_type == $entity['type'] ) {

			// Save correspondence and bail.
			$this->activity_id_set( $entity['id'], $args['objectId'] );
			return;

		}

		// Remove WordPress Post callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_post_remove();

		// Create the WordPress Post.
		if ( $post_id === false ) {
			$post_id = $this->create_from_activity( $args['objectRef'], $post_type );
		}

		// Reinstate WordPress Post callbacks.
		$this->acf_loader->mapper->hooks_wordpress_post_add();

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been updated from Activity details.
		 *
		 * Used internally to:
		 *
		 * - Update the ACF Fields for the WordPress Post
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
		if ( $args['objectName'] != 'Activity' ) {
			return;
		}

		// Backfill the Activity data.
		$args['objectRef'] = $this->acf_loader->civicrm->activity->backfill( $args['objectRef'] );

		// Bail if this Activity's Activity Type is not mapped.
		$post_type = $this->acf_loader->civicrm->activity->is_mapped( $args['objectRef'] );
		if ( $post_type === false ) {
			return;
		}

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Get the Post ID for this Activity.
		$post_id = $this->acf_loader->civicrm->activity->is_mapped_to_post( $args['objectRef'], $post_type );

		// Exclude "reverse" edits when a Post is the originator.
		if ( $entity['entity'] === 'post' AND $post_id == $entity['id'] ) {
			return;
		}

		// Remove WordPress Post callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_post_remove();

		// Create the WordPress Post if it doesn't exist, otherwise update.
		if ( $post_id === false ) {
			$post_id = $this->create_from_activity( $args['objectRef'], $post_type );
		} else {
			$this->update_from_activity( $args['objectRef'], $post_id );
		}

		// Reinstate WordPress Post callbacks.
		$this->acf_loader->mapper->hooks_wordpress_post_add();

		// Add our data to the params.
		$args['post_type'] = $post_type;
		$args['post_id'] = $post_id;

		/**
		 * Broadcast that a WordPress Post has been updated from Activity details.
		 *
		 * Used internally to:
		 *
		 * - Update the ACF Fields for the WordPress Post
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/activity/edited', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Activity from a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param array $activity The CiviCRM Activity data.
	 * @param string $post_type The name of Post Type.
	 * @return integer|boolean $post_id The WordPress Post ID, or false on failure.
	 */
	public function create_from_activity( $activity, $post_type ) {

		// Maybe cast Activity data as object.
		if ( is_array( $activity ) ) {
			$activity = (object) $activity;
		}

		// De-nullify critical values.
		$activity->subject = $this->acf_loader->civicrm->denullify( $activity->subject );
		$activity->details = $this->acf_loader->civicrm->denullify( $activity->details );

		// Define basic Post data.
		$args = [
			'post_status' => 'publish',
			'post_parent' => 0,
			'comment_status' => 'closed',
			'ping_status' => 'closed',
			'to_ping' => '', // Quick fix for Windows.
			'pinged' => '', // Quick fix for Windows.
			'post_content_filtered' => '', // Quick fix for Windows.
			'post_excerpt' => '', // Quick fix for Windows.
			'menu_order' => 0,
			'post_type' => $post_type,
			'post_title' => $activity->subject,
			'post_content' => $activity->details,
		];

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
			foreach( $acf_fields AS $field_group ) {
				foreach( $field_group AS $selector => $activity_field ) {
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
	 * @param array $activity The CiviCRM Activity data.
	 * @param integer $existing_id The numeric ID of the Post.
	 * @param WP_Post $post The WordPress Post object if it exists.
	 * @return integer|boolean $post_id The WordPress Post ID, or false on failure.
	 */
	public function update_from_activity( $activity, $existing_id, $post = null ) {

		// Maybe cast Activity data as object.
		if ( is_array( $activity ) ) {
			$activity = (object) $activity;
		}

		// De-nullify critical values.
		$activity->subject = $this->acf_loader->civicrm->denullify( $activity->subject );
		$activity->details = $this->acf_loader->civicrm->denullify( $activity->details );

		// Define args to update the Post.
		$args = [
			'ID' => $existing_id,
			'post_title' => $activity->subject,
			'post_content' => $activity->details,
		];

		// Overwrite Permalink if the current Post Title is empty.
		if ( ! is_null( $post ) AND $post instanceof WP_Post ) {
			if ( empty( $post->post_title ) ) {
				$args['post_name'] = sanitize_title( $activity->subject );
			}
		}

		// Update the Post.
		$post_id = wp_update_post( $args, true );

		// Bail on failure.
		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		// --<
		return $post_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Query for the Contact ID that an ACF "Post ID" is mapped to.
	 *
	 * @since 0.4
	 *
	 * @param boolean $contact_id False, since we're asking for a Contact ID.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param string $entity The kind of WordPress Entity.
	 * @return integer|boolean $contact_id The mapped Contact ID, or false if not mapped.
	 */
	public function query_contact_id( $contact_id, $post_id, $entity ) {

		// Bail early if a Contact ID has been found.
		if ( $contact_id !== false ) {
			return $contact_id;
		}

		// Bail early if not a Post Entity.
		if ( $entity !== 'post' ) {
			return $contact_id;
		}

		// Try and get Contact ID.
		$contact_id = $this->is_mapped_to_contact( $post_id );

		// --<
		return $contact_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if a WordPress Post should be synced.
	 *
	 * @since 0.4
	 *
	 * @param WP_Post $post_obj The WordPress Post object.
	 * @return WP_Post|boolean $post The WordPress Post object, or false if not allowed.
	 */
	public function should_be_synced( $post_obj ) {

		// Init return.
		$post = false;

		// Bail if no Post object.
		if ( ! $post_obj ) {
			return $post;
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $post_obj->ID ) ) {
			return $post;
		}

		// Bail if this is a draft or an auto-draft.
		if ( $post_obj->post_status == 'draft' OR $post_obj->post_status == 'auto-draft' ) {
			return $post;
		}

		// Bail if this is an autosave routine.
		if ( wp_is_post_autosave( $post_obj ) ) {
			return $post;
		}

		// Bail if this is a revision.
		if ( wp_is_post_revision( $post_obj ) ) {
			return $post;
		}

		// The Post should be synced.
		$post = $post_obj;

		// --<
		return $post;

	}



	/**
	 * Check if a WordPress Post Title should be synced.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return boolean True if updates were successful, or false on failure.
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
		if ( $args['post']->post_title == $contact['display_name'] ) {
			return;
		}

		// Remove WordPress callbacks to prevent recursion.
		$this->acf_loader->mapper->hooks_wordpress_remove();

		// Update the Post Title (and maybe the Post Permalink).
		$this->update_from_contact( $args['contact'], $args['post']->ID, $args['post'] );

		// Reinstate WordPress callbacks.
		$this->acf_loader->mapper->hooks_wordpress_add();

	}



} // Class ends.



