<?php
/**
 * Post Taxonomy Class.
 *
 * Handles Post Taxonomy functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync Post Taxonomy Class.
 *
 * A class that encapsulates Post Taxonomy functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Post_Tax {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * Post object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $post;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * Term Meta key.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $term_meta_key = '_cai_civicrm_group_id';

	/**
	 * Synced Taxonomies.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $taxonomies = [];

	/**
	 * Terms to compare before and after a Post is saved.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $terms_pre;

	/**
	 * An array of Term objects prior to edit.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Terms in an array and try and match them up in
	 * the post edit hook.
	 *
	 * @since 0.4
	 * @access private
	 * @var array
	 */
	private $term_edited = [];

	/**
	 * An array of Terms prior to edit.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Terms in an array and try and match them up in
	 * the post edit hook.
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
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->post       = $parent;

		// Init when the "mapping" class has loaded.
		add_action( 'cwps/acf/post/loaded', [ $this, 'register_hooks' ] );

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Build array of Taxonomies to use.
		add_action( 'init', [ $this, 'taxonomies_build' ], 1000 );

		// Register Taxonomy Form hooks on admin init.
		add_action( 'admin_init', [ $this, 'register_hooks_admin' ] );

		// Listen for when a Post is about to be updated.
		add_action( 'pre_post_update', [ $this, 'post_saved_pre' ], 10, 2 );

		// Listen for events that require Taxonomy updates.
		add_action( 'cwps/acf/contact/post/saved', [ $this, 'post_saved' ], 10 );

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

		// Listen for new term creation.
		add_action( 'cwps/acf/mapper/term/created', [ $this, 'term_created' ], 20 );

		// Intercept term updates.
		add_action( 'cwps/acf/mapper/term/edit/pre', [ $this, 'term_pre_edit' ], 20 );
		add_action( 'cwps/acf/mapper/term/edited', [ $this, 'term_edited' ], 20 );

		// Intercept term deletion.
		add_action( 'cwps/acf/mapper/term/deleted', [ $this, 'term_deleted' ], 20 );

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
		remove_action( 'cwps/acf/mapper/term/created', [ $this, 'term_created' ], 20 );
		remove_action( 'cwps/acf/mapper/term/edit/pre', [ $this, 'term_pre_edit' ], 20 );
		remove_action( 'cwps/acf/mapper/term/edited', [ $this, 'term_edited' ], 20 );
		remove_action( 'cwps/acf/mapper/term/deleted', [ $this, 'term_deleted' ], 20 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	/**
	 * Build an array of Taxonomies.
	 *
	 * The taxonomies that we store here are those which are associated with the
	 * Post Types that are synced to CiviCRM Contact Types.
	 *
	 * @since 0.4
	 */
	public function taxonomies_build() {

		// Init taxonomies property.
		$this->taxonomies = [];

		// Get all used Post Types.
		$synced_post_types = $this->acf_loader->mapping->mappings_for_contact_types_get();

		// Add to stored array if we have some.
		if ( ! empty( $synced_post_types ) ) {
			foreach ( $synced_post_types as $synced_post_type ) {
				$taxonomies = get_object_taxonomies( $synced_post_type );
				if ( ! empty( $taxonomies ) ) {
					foreach ( $taxonomies as $taxonomy ) {
						$this->taxonomies[] = $taxonomy;
					}
				}
			}
		}

	}

	/**
	 * Register WordPress admin hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks_admin() {

		// Add actions for all associated Taxonomies.
		if ( ! empty( $this->taxonomies ) ) {
			foreach ( $this->taxonomies as $taxonomy ) {
				add_action( "{$taxonomy}_add_form_fields", [ $this, 'form_element_add_term_add' ], 10 );
				add_action( "{$taxonomy}_edit_form_fields", [ $this, 'form_element_edit_term_add' ], 10, 2 );
			}
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Add a form element to the "Add Term" form.
	 *
	 * @since 0.4
	 *
	 * @param string $taxonomy The taxonomy slug.
	 */
	public function form_element_add_term_add( $taxonomy ) {

		// Get the filtered Groups.
		$groups = $this->form_element_get_groups( $taxonomy );

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/taxonomies/term-add.php';

	}

	/**
	 * Add a form element to the "Edit Term" form.
	 *
	 * @since 0.4
	 *
	 * @param WP_Term $tag The current taxonomy term object.
	 * @param string  $taxonomy The current taxonomy slug.
	 */
	public function form_element_edit_term_add( $tag, $taxonomy ) {

		// Get chosen the Group ID, if present.
		$group_id = $this->term_meta_get( $tag->term_id );

		// Cast failures as "None set".
		if ( false === $group_id ) {
			$group_id = 0;
		}

		// Get the filtered Groups.
		$groups = $this->form_element_get_groups( $taxonomy, $group_id );

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/taxonomies/term-edit.php';

	}

	/**
	 * Get the filtered Groups for the "Add Term" and "Edit Term" forms.
	 *
	 * @since 0.4
	 *
	 * @param string  $taxonomy The current taxonomy slug.
	 * @param integer $group_id The chosen Group ID, if present.
	 * @return array $groups The array of Groups to display.
	 */
	public function form_element_get_groups( $taxonomy, $group_id = null ) {

		// Get all Groups from CiviCRM.
		$groups_all = $this->acf_loader->civicrm->group->groups_get_all();
		$group_ids  = wp_list_pluck( $groups_all, 'id' );

		// Get the full taxonomy data.
		$tax_object = get_taxonomy( $taxonomy );

		// Get the object types for this taxonomy.
		$post_types = $tax_object->object_type;

		// Get all the terms for these post types.
		$terms_all = [];
		if ( ! empty( $post_types ) ) {
			foreach ( $post_types as $post_type ) {
				$terms_for_post_type = $this->synced_terms_get_for_post_type( $post_type );
				if ( ! empty( $terms_for_post_type ) ) {
					foreach ( $terms_for_post_type as $term_for_post_type ) {
						$terms_all[] = $term_for_post_type;
					}
				}
			}
		}

		// Grab just the IDs.
		$term_ids_all = wp_list_pluck( $terms_all, 'group_id' );

		// Filter groups to exclude those already synced.
		$filtered = array_diff( $group_ids, $term_ids_all );

		// Add existing Group if it exists.
		if ( ! is_null( $group_id ) && 0 !== $group_id ) {
			$filtered[] = $group_id;
		}

		// Sanity check.
		$filtered = array_unique( $filtered );
		$filtered = array_map( 'intval', $filtered );

		// Build Groups.
		$groups = [];
		foreach ( $groups_all as $group ) {
			if ( in_array( (int) $group['id'], $filtered, true ) ) {
				$groups[] = $group;
			}
		}

		// --<
		return $groups;

	}

	// -------------------------------------------------------------------------

	/**
	 * Hook into the creation of a term.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_created( $args ) {

		// Bail if our element is not set.
		if ( ! isset( $_POST['cwps-civicrm-group'] ) ) {
			return;
		}

		// Check authentication.
		check_admin_referer( 'add-tag', '_wpnonce_add-tag' );

		// Sanitise input.
		$group_id = (int) sanitize_text_field( wp_unslash( $_POST['cwps-civicrm-group'] ) );

		// Bail if Group ID is zero.
		if ( 0 === $group_id ) {
			return;
		}

		// Store Group ID in term meta.
		$success = $this->term_meta_update( $args['term_id'], $group_id );

	}

	/**
	 * Hook into updates to a term before the term is updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_pre_edit( $args ) {

		// Get term.
		$term = get_term_by( 'id', $args['term_id'], $args['taxonomy'] );

		// Error check.
		if ( is_null( $term ) ) {
			return;
		}
		if ( is_wp_error( $term ) ) {
			return;
		}
		if ( ! is_object( $term ) ) {
			return;
		}

		// Store for reference in term_edited().
		$this->term_edited[ $term->term_id ] = clone $term;

	}

	/**
	 * Hook into updates to a term.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_edited( $args ) {

		// Bail if our element is not set.
		if ( ! isset( $_POST['cwps-civicrm-group'] ) ) {
			return;
		}

		// Check authentication.
		check_admin_referer( 'update-tag_' . $args['term_id'] );

		// Sanitise input.
		$group_id = (int) sanitize_text_field( wp_unslash( $_POST['cwps-civicrm-group'] ) );

		// Get current Term object.
		$new_term = get_term_by( 'id', $args['term_id'], $args['taxonomy'] );

		// Populate "Old Term" if we have it stored.
		$old_term = null;
		if ( ! empty( $this->term_edited[ $new_term->term_id ] ) ) {
			$old_term = $this->term_edited[ $new_term->term_id ];
			unset( $this->term_edited[ $new_term->term_id ] );
		}

		/*
		 * If Group ID is zero, there are a couple of possibilities:
		 *
		 * 1. No Group ID is set and none exists.
		 * 2. A Group ID was set and is now being deleted.
		 */
		if ( 0 === $group_id ) {

			// Remove term meta if it exists.
			$existing = $this->term_meta_get( $args['term_id'] );
			if ( false !== $existing ) {
				$this->term_meta_delete( $args['term_id'] );

				// TODO: Terms must be removed from Posts now.

			}

			// Bail.
			return;

		}

		// Store group ID in term meta.
		$success = $this->term_meta_update( $args['term_id'], $group_id );

		// TODO: Terms must be added to (or replaced in) Posts now.

	}

	/**
	 * Hook into deletion of a term.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_deleted( $args ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'args' => $args,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the CiviCRM Group ID for a term.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the term.
	 * @return integer|bool $group_id The ID of the CiviCRM Group, or false on failure.
	 */
	public function term_meta_get( $term_id ) {

		// Get the Group ID from the term's meta.
		$group_id = get_term_meta( $term_id, $this->term_meta_key, true );

		// Bail if there is no result.
		if ( empty( $group_id ) ) {
			return false;
		}

		// --<
		return (int) $group_id;

	}

	/**
	 * Add meta data to a term.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the term.
	 * @param integer $group_id The numeric ID of the CiviCRM Group.
	 * @return integer|bool $meta_id The ID of the meta, or false on failure.
	 */
	public function term_meta_add( $term_id, $group_id ) {

		// Add the Group ID to the term's meta.
		$meta_id = add_term_meta( $term_id, $this->term_meta_key, (int) $group_id, true );

		// Log something if there's an error.
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if ( false === $meta_id ) {

			/*
			 * This probably means that the term already has its term meta set.
			 * Uncomment the following to debug if you need to.
			 */

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			$log = [
				'method' => __METHOD__,
				'message' => __( 'Could not add term_meta', 'civicrm-wp-profile-sync' ),
				'term_id' => $term_id,
				'group_id' => $group_id,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			*/

		}

		// Log a message if the term_id is ambiguous between taxonomies.
		if ( is_wp_error( $meta_id ) ) {

			// Log error message.
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => $meta_id->get_error_message(),
				'term'      => $term,
				'group_id'  => $group_id,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );

			// Also overwrite return.
			$meta_id = false;

		}

		// --<
		return $meta_id;

	}

	/**
	 * Update the meta data for a term.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the term.
	 * @param integer $group_id The numeric ID of the CiviCRM Group.
	 * @return integer|bool $meta_id The ID of the meta if new, true on success or false on failure.
	 */
	public function term_meta_update( $term_id, $group_id ) {

		// Get the existing Group ID from the term's meta data.
		$existing_id = $this->term_meta_get( $term_id );

		// Update the Group ID in the term's meta data.
		$meta_id = update_term_meta( $term_id, $this->term_meta_key, (int) $group_id );

		// Return early on successful update.
		if ( true === $meta_id ) {
			return $meta_id;
		}

		/*
		 * Log something if there's an error.
		 *
		 * Note that this is also triggered when the value has not changed, so
		 * we have to compare against the existing value as well.
		 */
		if ( false === $meta_id ) {
			if ( false !== $existing_id && (int) $existing_id !== (int) $group_id ) {
				$e     = new \Exception();
				$trace = $e->getTraceAsString();
				$log   = [
					'method'    => __METHOD__,
					'message'   => __( 'Could not update term_meta', 'civicrm-wp-profile-sync' ),
					'term_id'   => $term_id,
					'group_id'  => $group_id,
					'backtrace' => $trace,
				];
				$this->plugin->log_error( $log );
			}
		}

		// Log a message if the term_id is ambiguous between taxonomies.
		if ( is_wp_error( $meta_id ) ) {

			// Log error message.
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => $meta_id->get_error_message(),
				'term_id'   => $term_id,
				'group_id'  => $group_id,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );

			// Also overwrite return.
			$meta_id = false;

		}

		// --<
		return $meta_id;

	}

	/**
	 * Get the CiviCRM Group ID for a term.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the term.
	 * @return bool True if successful, or false on failure.
	 */
	public function term_meta_delete( $term_id ) {

		// Delete the term's meta data.
		$success = delete_term_meta( $term_id, $this->term_meta_key );

		// --<
		return $success;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the terms for a given CiviCRM Group.
	 *
	 * @since 0.4
	 *
	 * @param integer $group_id The numeric ID of CiviCRM Group.
	 * @return array|bool $terms The array of term objects, or false on failure.
	 */
	public function terms_get_by_group_id( $group_id ) {

		// Query terms for those with the ID of the Group in meta data.
		$args = [
			'taxonomy'   => $this->taxonomies,
			'hide_empty' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'     => $this->term_meta_key,
					'value'   => $group_id,
					'compare' => '=',
				],
			],
		];

		// Get the terms.
		$terms = get_terms( $args );

		// Bail if there are no results.
		if ( empty( $terms ) ) {
			return false;
		}

		// Log a message and bail if there's an error.
		if ( is_wp_error( $terms ) ) {

			// Write error message.
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => $terms->get_error_message(),
				'group_id'  => $group_id,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;

		}

		// --<
		return $terms;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the terms for a given WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of WordPress Post Type.
	 * @return array $terms The array of term objects.
	 */
	public function terms_get_by_post_type( $post_type ) {

		// Get the taxonomies for this Post Type.
		$taxonomies = get_object_taxonomies( $post_type );

		// Bail if there are no taxonomies.
		if ( empty( $taxonomies ) ) {
			return [];
		}

		// Query terms in those taxonomies.
		$args = [
			'taxonomy'   => $taxonomies,
			'hide_empty' => false,
		];

		// Grab the terms.
		$terms = get_terms( $args );

		// Bail if there are no terms or there's an error.
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		// --<
		return $terms;

	}

	/**
	 * Gets all the Terms in Synced Taxonomies for a given WordPress Post ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array $terms The array of Term objects.
	 */
	public function terms_get_for_post( $post_id ) {

		$terms = [];

		// Bail if there are no Taxonomies.
		if ( empty( $this->taxonomies ) ) {
			return $terms;
		}

		// Build array of all Terms.
		foreach ( $this->taxonomies as $taxonomy ) {

			// Grab the Terms.
			$terms_in_tax = get_the_terms( $post_id, $taxonomy );

			// Skip if there are no Terms or there's an error.
			if ( empty( $terms_in_tax ) || is_wp_error( $terms_in_tax ) ) {
				continue;
			}

			// Append Terms.
			$terms = array_merge( $terms, $terms_in_tax );

		}

		// --<
		return $terms;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the synced terms for a given WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of WordPress Post Type.
	 * @return array|bool $terms The array of term objects, or false on failure.
	 */
	public function synced_terms_get_for_post_type( $post_type ) {

		// Get the taxonomies for this Post Type.
		$taxonomies = get_object_taxonomies( $post_type );

		// Bail if there are no taxonomies.
		if ( empty( $taxonomies ) ) {
			return [];
		}

		// Query terms in those taxonomies.
		$args = [
			'taxonomy'   => $taxonomies,
			'hide_empty' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'     => $this->term_meta_key,
					'compare' => 'EXISTS',
				],
			],
		];

		// Grab the terms.
		$terms = get_terms( $args );

		// Bail if there are no terms or there's an error.
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		// Let's add the Group ID to the term object.
		foreach ( $terms as $term ) {
			$term->group_id = $this->term_meta_get( $term->term_id );
		}

		// --<
		return $terms;

	}

	/**
	 * Get the synced terms for a given WordPress Post ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array|bool $terms The array of term objects, or false on failure.
	 */
	public function synced_terms_get_for_post( $post_id ) {

		// Limit terms to those with Group IDs in their meta.
		$params = [
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'     => $this->term_meta_key,
					'compare' => 'EXISTS',
				],
			],
		];

		// Grab the terms.
		$terms = wp_get_object_terms( $post_id, $this->taxonomies, $params );

		// Bail if there are no terms or there's an error.
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		// Let's add the Group ID to the term object.
		foreach ( $terms as $term ) {
			$term->group_id = $this->term_meta_get( $term->term_id );
		}

		// --<
		return $terms;

	}

	/**
	 * Get the terms for a Post that are synced to a CiviCRM Group.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param integer        $group_id The ID of the CiviCRM Group.
	 * @return array $terms The array of terms.
	 */
	public function synced_terms_get_for_post_and_group( $post_id, $group_id ) {

		/*
		// Only do this once per Post and Group.
		static $pseudocache;
		if ( isset( $pseudocache[$post_id][$group_id] ) ) {
			return $pseudocache[$post_id][$group_id];
		}
		*/

		// Init as empty.
		$terms_for_post = [];

		// Bail if Post is not mapped.
		if ( ! $this->post->is_mapped_to_contact( $post_id ) ) {
			return $terms_for_post;
		}

		// Grab Post object.
		$post = get_post( $post_id );

		// Get all synced term IDs for the Post Type.
		$synced_terms_for_post_type = $this->synced_terms_get_for_post_type( $post->post_type );

		// Filter synced terms for just this Group.
		$args           = [ 'group_id' => $group_id ];
		$terms_for_post = wp_filter_object_list( $synced_terms_for_post_type, $args );

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$post_id][$group_id] ) ) {
			$pseudocache[$post_id][$group_id] = $terms_for_post;
		}
		*/

		// --<
		return $terms_for_post;

	}

	/**
	 * Get the synced terms for a given WordPress taxonomy.
	 *
	 * @since 0.4
	 *
	 * @param string $taxonomy The name of WordPress taxonomy.
	 * @return array|bool $terms The array of term objects, or false on failure.
	 */
	public function synced_terms_get_for_taxonomy( $taxonomy ) {

		// Query terms in those taxonomies.
		$args = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'     => $this->term_meta_key,
					'compare' => 'EXISTS',
				],
			],
		];

		// Grab the terms.
		$terms = get_terms( $args );

		// Bail if there are no terms or there's an error.
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		// Let's add the Group ID to the term object.
		foreach ( $terms as $term ) {
			$term->group_id = $this->term_meta_get( $term->term_id );
		}

		// --<
		return $terms;

	}

	/**
	 * Get all synced terms in all WordPress taxonomies.
	 *
	 * @since 0.4
	 *
	 * @return array|bool $terms The array of term objects, or false on failure.
	 */
	public function synced_terms_get_all() {

		// Query terms in those taxonomies.
		$args = [
			'taxonomy'   => $this->taxonomies,
			'hide_empty' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key'     => $this->term_meta_key,
					'compare' => 'EXISTS',
				],
			],
		];

		// Grab the terms.
		$terms = get_terms( $args );

		// Bail if there are no terms or there's an error.
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return [];
		}

		// Let's add the Group ID to the term object.
		foreach ( $terms as $term ) {
			$term->group_id = $this->term_meta_get( $term->term_id );
		}

		// --<
		return $terms;

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a WordPress Post is about to be updated.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param array          $data The array of unslashed post data.
	 */
	public function post_saved_pre( $post_id, $data ) {

		// Get the full Post.
		$post = get_post( $post_id );

		// Bail if this Post should not be synced.
		$post = $this->post->should_be_synced( $post );
		if ( false === $post ) {
			return;
		}

		// Bail if this Post Type is not mapped.
		if ( ! $this->acf_loader->post_type->is_mapped_to_contact_type( $post->post_type ) ) {
			return;
		}

		// Get the filtered terms.
		$terms = $this->synced_terms_get_for_post( $post_id );
		if ( empty( $terms ) ) {
			return;
		}

		// Store for later use.
		$this->bridging_array[ $post_id ] = $terms;

	}

	/**
	 * Update the terms when a WordPress Post has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function post_saved( $args ) {

		// Get the Post ID.
		$post_id = (int) $args['post_id'];

		// Get the existing filtered term IDs.
		$terms_pre = [];
		if ( ! empty( $this->bridging_array[ $post_id ] ) ) {
			$terms_pre = $this->bridging_array[ $post_id ];
			unset( $this->bridging_array[ $post_id ] );
		}
		$term_ids_pre = wp_list_pluck( $terms_pre, 'term_id' );

		// Get the new filtered term IDs.
		$terms    = $this->synced_terms_get_for_post( $post_id );
		$term_ids = wp_list_pluck( $terms, 'term_id' );

		// Find the existing terms that are missing in the current terms.
		$terms_removed = array_diff( $term_ids_pre, $term_ids );

		// Find the current terms that are missing in the existing terms.
		$terms_added = array_diff( $term_ids, $term_ids_pre );

		// Loop through terms removed and collect Group IDs.
		$group_ids_removed = [];
		foreach ( $terms_removed as $term_id ) {
			$group_id = $this->term_meta_get( $term_id );
			if ( false === $group_id ) {
				continue;
			}
			$group_ids_removed[ $term_id ] = $group_id;
		}

		// Loop through terms added and collect Group IDs.
		$group_ids_added = [];
		foreach ( $terms_added as $term_id ) {
			$group_id = $this->term_meta_get( $term_id );
			if ( false === $group_id ) {
				continue;
			}
			$group_ids_added[ $term_id ] = $group_id;
		}

		// If there are Group IDs to add Contact to.
		if ( ! empty( $group_ids_added ) ) {
			foreach ( $group_ids_added as $term_id => $group_id ) {

				// If not already a member.
				$is_member = $this->acf_loader->civicrm->group->group_contact_exists( $group_id, $args['contact_id'] );
				if ( false === $is_member ) {

					// Add to the Group.
					$this->acf_loader->civicrm->group->group_contact_create( $group_id, $args['contact_id'] );

					/**
					 * Broadcast that a Contact has been added to a Group.
					 *
					 * @since 0.4
					 *
					 * @param array $args The array of data.
					 * @param integer $group_id The ID of the CiviCRM Group.
					 * @param integer $term_id The ID of the term.
					 */
					do_action( 'cwps/acf/post_tax/group_contact/created', $args, $group_id, $term_id );

				}

			}
		}

		// If there are Group IDs to remove Contact from.
		if ( ! empty( $group_ids_removed ) ) {
			foreach ( $group_ids_removed as $term_id => $group_id ) {

				// If already a member.
				$is_member = $this->acf_loader->civicrm->group->group_contact_exists( $group_id, $args['contact_id'] );
				if ( true === $is_member ) {

					// Remove from the Group.
					$this->acf_loader->civicrm->group->group_contact_delete( $group_id, $args['contact_id'] );

					/**
					 * Broadcast that a Contact has been removed from a Group.
					 *
					 * @since 0.4
					 *
					 * @param array $args The array of data.
					 * @param integer $group_id The ID of the CiviCRM Group.
					 * @param integer $term_id The ID of the term.
					 */
					do_action( 'cwps/acf/post_tax/group_contact/deleted', $args, $group_id, $term_id );

				}

			}
		}

		// Add our data to the params.
		$args['terms']             = $terms;
		$args['term_ids']          = $term_ids;
		$args['terms_added']       = $terms_added;
		$args['terms_removed']     = $terms_removed;
		$args['group_ids_added']   = $group_ids_added;
		$args['group_ids_removed'] = $group_ids_removed;

		/**
		 * Broadcast that changes to the Post's taxonomies have been acted upon.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of data.
		 */
		do_action( 'cwps/acf/post_tax/post/saved', $args );

	}

	// -------------------------------------------------------------------------

	/**
	 * Process terms for Contacts when they are added or removed from a Group.
	 *
	 * @since 0.4
	 *
	 * @param integer $group_id The ID of the CiviCRM Group.
	 * @param array   $contact_ids Array of CiviCRM Contact IDs.
	 * @param string  $op The kind of operation - 'add' or 'remove'.
	 */
	public function terms_update_for_group_contacts( $group_id, $contact_ids, $op ) {

		// Get Term IDs that are synced to this Group ID.
		$terms_for_group = $this->terms_get_by_group_id( $group_id );
		if ( empty( $terms_for_group ) ) {
			return;
		}

		// Get the just the Term IDs.
		$term_ids_for_group = wp_list_pluck( $terms_for_group, 'term_id' );

		// Loop through added Contacts.
		foreach ( $contact_ids as $contact_id ) {

			// Grab Contact.
			$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );
			if ( false === $contact ) {
				continue;
			}

			// Test if any of this Contact's Contact Types is mapped.
			$post_types = $this->acf_loader->civicrm->contact->is_mapped( $contact, 'create' );
			if ( false !== $post_types ) {

				// Handle each Post Type in turn.
				foreach ( $post_types as $post_type ) {

					// Get the Post ID that this Contact is mapped to.
					$post_id = $this->acf_loader->civicrm->contact->is_mapped_to_post( $contact, $post_type );
					if ( false === $post_id ) {
						continue;
					}

					// Grab Post object.
					$post = get_post( $post_id );

					// Get all synced term IDs for the Post Type.
					$synced_terms_for_post_type    = $this->synced_terms_get_for_post_type( $post->post_type );
					$synced_term_ids_for_post_type = wp_list_pluck( $synced_terms_for_post_type, 'term_id' );

					// Find the term ID(s) from those the Group syncs with.
					$term_ids_for_post = array_intersect( $term_ids_for_group, $synced_term_ids_for_post_type );

					// Find the term(s) from those the Group syncs with.
					$terms_for_post = [];
					foreach ( $term_ids_for_post as $term_id_for_post ) {
						foreach ( $terms_for_group as $term_for_group ) {
							if ( $term_for_group->term_id == $term_id_for_post ) {
								$terms_for_post[ $term_for_group->term_id ] = $term_for_group;
							}
						}
					}

					// Get all the current terms for the Post.
					$terms_in_post    = $this->terms_get_for_post( $post_id );
					$term_ids_in_post = wp_list_pluck( $terms_in_post, 'term_id' );
					$term_ids_in_post = array_map( 'intval', $term_ids_in_post );

					// If the term(s) need to be added.
					if ( 'add' === $op ) {

						// If the Post does not have the term(s), add them.
						foreach ( $term_ids_for_post as $term_id_for_post ) {
							if ( ! in_array( (int) $term_id_for_post, $term_ids_in_post, true ) ) {
								$terms_in_post[] = $terms_for_post[ $term_id_for_post ];
							}
						}

					}

					// If the term(s) need to be removed.
					if ( 'remove' === $op ) {

						// Init final array.
						$term_ids_final = array_diff( $term_ids_in_post, $term_ids_for_post );

						// Rebuild terms-in-post array.
						$terms_in_post_new = [];

						foreach ( $term_ids_final as $term_id_final ) {
							foreach ( $terms_in_post as $term_in_post ) {
								if ( $term_in_post->term_id == $term_id_final ) {
									$terms_in_post_new[] = $term_in_post;
									continue;
								}
							}
						}

						// Overwrite.
						$terms_in_post = $terms_in_post_new;

					}

					// Grab the taxonomies for the synced terms.
					$taxonomies = array_unique( wp_list_pluck( $synced_terms_for_post_type, 'taxonomy' ) );

					// Loop through them.
					foreach ( $taxonomies as $taxonomy ) {

						// Find the terms in this taxonomy.
						$args         = [ 'taxonomy' => $taxonomy ];
						$terms_in_tax = wp_filter_object_list( $terms_in_post, $args );

						// If there are none.
						if ( empty( $terms_in_tax ) ) {
							$term_ids_in_tax = [];
						} else {
							$term_ids_in_tax = wp_list_pluck( $terms_in_tax, 'term_id' );
						}

						// Overwrite with new set of terms.
						wp_set_object_terms( $post_id, $term_ids_in_tax, $taxonomy, false );

						// Clear cache.
						clean_object_term_cache( $post_id, $taxonomy );

					}

				}

			}

		}

	}

}
