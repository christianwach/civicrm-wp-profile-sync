<?php
/**
 * CiviCRM Participant CPT "Participant Role" Taxonomy Class.
 *
 * Handles sync between the "Participant Role" custom Taxonomy attached to the
 * Participant CPT and CiviCRM's Participant Roles.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Participant Role Taxonomy Class.
 *
 * This class keeps the "Participant Role" custom Taxonomy in sync with the
 * Participant Roles in CiviCRM.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT_Tax {

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
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Participant object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $participant;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $cpt;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * Taxonomy name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $taxonomy_name;

	/**
	 * Term Meta key.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $term_meta_key = '_cwps_participant_role_id';

	/**
	 * "Is Active" Term Meta key.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $term_active_meta_key = '_cwps_participant_role_active';

	/**
	 * "Counted" Term Meta key.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $term_counted_meta_key = '_cwps_participant_role_counted';

	/**
	 * An array of Term objects prior to edit.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Terms in an array and try and match them up in
	 * the post edit hook.
	 *
	 * @since 0.5
	 * @access private
	 * @var array
	 */
	private $term_edited = [];

	/**
	 * An array of Participant Roles prior to delete.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Participant Roles in an array and try and match
	 * them up in the post delete hook.
	 *
	 * @since 0.5
	 * @access private
	 * @var array
	 */
	private $bridging_array = [];

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
		$this->participant = $parent->participant;
		$this->cpt = $parent;

		// Store Taxonomy name.
		$this->taxonomy_name = $parent->taxonomy_name;

		// Init when the Participant CPT object is loaded.
		add_action( 'cwps/acf/civicrm/participant-cpt/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Bail if CPT not enabled.
		if ( $this->cpt->enabled === false ) {
			return;
		}

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Intercept WordPress Term operations.
		$this->register_mapper_hooks();

		// Add CiviCRM listeners once CiviCRM is available.
		add_action( 'civicrm_config', [ $this, 'civicrm_config' ], 10, 1 );

		// Add Form elements to Taxonomy Add/Edit.
		add_action( "{$this->taxonomy_name}_add_form_fields", [ $this, 'form_element_add_term_add' ], 10 );
		add_action( "{$this->taxonomy_name}_edit_form_fields", [ $this, 'form_element_edit_term_add' ], 10, 2 );

		// Hide "Parent Category" dropdown in Participant Role category metaboxes.
		add_action( 'add_meta_boxes_participant', [ $this, 'terms_dropdown_intercept' ], 3 );

		// Ensure new Participants have the default Term checked.
		add_filter( 'wp_terms_checklist_args', [ $this, 'term_default_checked' ], 10, 2 );

		// Create custom filters that mirror 'the_content'.
		add_filter( 'cwps/acf/civicrm/participant-cpt/term-desc', 'wptexturize' );
		add_filter( 'cwps/acf/civicrm/participant-cpt/term-desc', 'convert_smilies' );
		add_filter( 'cwps/acf/civicrm/participant-cpt/term-desc', 'convert_chars' );
		add_filter( 'cwps/acf/civicrm/participant-cpt/term-desc', 'wpautop' );
		add_filter( 'cwps/acf/civicrm/participant-cpt/term-desc', 'shortcode_unautop' );

		// Filter the create/update Post arguments.
		add_filter( 'cwps/acf/post/participant/create/args', [ $this, 'terms_add_to_post' ], 10, 2 );
		add_filter( 'cwps/acf/post/participant/update/args', [ $this, 'terms_add_to_post' ], 10, 2 );

		// Maybe add a Menu Item to CiviCRM Admin Utilities menu.
		add_action( 'civicrm_admin_utilities_menu_top', [ $this, 'menu_item_add_to_cau' ], 10, 2 );

	}

	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.5
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
			return;
		}

		// Listen for new Term creation.
		add_action( 'cwps/acf/mapper/term/created', [ $this, 'term_created' ], 20 );

		// Intercept Term updates.
		add_action( 'cwps/acf/mapper/term/edit/pre', [ $this, 'term_pre_edit' ], 20 );
		add_action( 'cwps/acf/mapper/term/edited', [ $this, 'term_edited' ], 20 );

		// Intercept Term deletion.
		add_action( 'cwps/acf/mapper/term/delete/pre', [ $this, 'term_pre_delete' ], 20 );
		add_action( 'cwps/acf/mapper/term/deleted', [ $this, 'term_deleted' ], 20 );

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Remove Mapper hooks.
	 *
	 * @since 0.5
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_hooks === false ) {
			return;
		}

		// Remove all previously added callbacks.
		remove_action( 'cwps/acf/mapper/term/created', [ $this, 'term_created' ], 20 );
		remove_action( 'cwps/acf/mapper/term/edit/pre', [ $this, 'term_pre_edit' ], 20 );
		remove_action( 'cwps/acf/mapper/term/edited', [ $this, 'term_edited' ], 20 );
		remove_action( 'cwps/acf/mapper/term/delete/pre', [ $this, 'term_pre_delete' ], 20 );
		remove_action( 'cwps/acf/mapper/term/deleted', [ $this, 'term_deleted' ], 20 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	/**
	 * Callback for "civicrm_config".
	 *
	 * @since 0.5
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function civicrm_config( &$config ) {

		// Add CiviCRM listeners once CiviCRM is available.
		$this->register_civicrm_hooks();

	}

	/**
	 * Add listeners for CiviCRM Participant Role operations.
	 *
	 * @since 0.5
	 */
	public function register_civicrm_hooks() {

		// Add callback for CiviCRM "postInsert" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.postInsert',
			[ $this, 'participant_role_created' ],
			-100 // Default priority.
		);

		/*
		// Add callback for CiviCRM "preUpdate" hook.
		// @see https://lab.civicrm.org/dev/core/issues/1638
		Civi::service('dispatcher')->addListener(
			'civi.dao.preUpdate',
			[ $this, 'participant_role_pre_update' ],
			-100 // Default priority.
		);
		*/

		// Add callback for CiviCRM "postUpdate" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.postUpdate',
			[ $this, 'participant_role_updated' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "preDelete" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preDelete',
			[ $this, 'participant_role_pre_delete' ],
			-100 // Default priority.
		);

	}

	/**
	 * Remove listeners from CiviCRM Participant Role operations.
	 *
	 * @since 0.5
	 */
	public function unregister_civicrm_hooks() {

		// Remove callback for CiviCRM "postInsert" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.postInsert',
			[ $this, 'participant_role_created' ]
		);

		/*
		// Remove callback for CiviCRM "preUpdate" hook.
		Civi::service('dispatcher')->removeListener(
			'civi.dao.preUpdate',
			[ $this, 'participant_role_pre_update' ]
		);
		*/

		// Remove callback for CiviCRM "postUpdate" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.postUpdate',
			[ $this, 'participant_role_updated' ]
		);

		// Remove callback for CiviCRM "preDelete" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.preDelete',
			[ $this, 'participant_role_pre_delete' ]
		);

	}

	// -------------------------------------------------------------------------

	/**
	 * Filters the arguments used to update a Post.
	 *
	 * @since 0.5
	 *
	 * @param array $args The arguments used to update a Post.
	 * @param array $participant The CiviCRM Participant data.
	 * @return array $args The modified arguments used to update a Post.
	 */
	public function terms_add_to_post( $args, $participant ) {

		// Grab the Post Type.
		if ( ! empty( $args['ID'] ) ) {
			$post_type = get_post_type( $args['ID'] );
		} else {
			$post_type = $args['post_type'];
		}

		// Bail if not our Post Type.
		if ( $post_type !== $this->cpt->post_type_name ) {
			return $args;
		}

		// Maybe cast Participant data as object.
		if ( is_array( $participant ) ) {
			$participant = (object) $participant;
		}

		// Check the Participant for Participant Role(s).
		if ( empty( $participant->role_id ) ) {
			return $args;
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

		// Construct Terms array.
		$terms = [];
		foreach ( $participant_role_ids as $role_id ) {

			// The "Role ID" is actually the "Role Value", so get the Role.
			$participant_role = $this->civicrm->participant_role->get_by_value( $role_id );
			if ( $participant_role === false ) {
				continue;
			}

			// Now get the corresponding Term.
			$term = $this->term_get_by_meta( $participant_role['id'] );
			if ( $term !== false ) {
				$terms[] = $term->term_id;
			}

		}

		// Add to post data.
		$args['tax_input'] = [
			$this->taxonomy_name => $terms,
		];

		// --<
		return $args;

	}

	/**
	 * Get Terms in the Participant Role category.
	 *
	 * Gets the Terms for a specific Post if a Post ID is provided.
	 *
	 * @since 0.5
	 *
	 * @param integer $post_id The numeric ID of the WordPress Participant Post.
	 * @return array $terms The found Terms in the Participant Role category.
	 */
	public function terms_get( $post_id = false ) {

		// If ID is false, get all Terms.
		if ( $post_id === false ) {

			// Since WordPress 4.5.0, the category is specified in the arguments.
			$args = [
				'taxonomy' => $this->taxonomy_name,
				'orderby' => 'count',
				'hide_empty' => 0,
			];

			// Get all Terms.
			$terms = get_terms( $args );

		} else {

			// Get Terms for the Post.
			$terms = get_the_terms( $post_id, $this->taxonomy_name );

		}

		// --<
		return $terms;

	}

	/**
	 * Hook into the creation of a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_created( $args ) {

		// Only look for Terms in the Participant Role Taxonomy.
		if ( $args['taxonomy'] != $this->taxonomy_name ) {
			return;
		}

		// Get Term object.
		$term = get_term_by( 'id', $args['term_id'], $this->taxonomy_name );

		// If "Is Active" is not set, then the Term is not active.
		if ( ! isset( $_POST['cwps-participant-role-active'] ) ) {
			$this->term_active_set( $term->term_id, false );
		} else {
			$this->term_active_set( $term->term_id, true );
		}

		// If "Counted" is not set, then the Term is not counted.
		if ( ! isset( $_POST['cwps-participant-role-counted'] ) ) {
			$this->term_counted_set( $term->term_id, false );
		} else {
			$this->term_counted_set( $term->term_id, true );
		}

		// Unhook CiviCRM.
		$this->unregister_civicrm_hooks();

		// Update CiviCRM Participant Role - or create if it doesn't exist.
		$participant_role_id = $this->participant_role_update( $term );

		// Rehook CiviCRM.
		$this->register_civicrm_hooks();

		// Store mapped Participant Role ID.
		if ( ! empty( $participant_role_id ) ) {
			$this->term_meta_set( $term->term_id, $participant_role_id );
		}

	}

	/**
	 * Hook into updates to a Participant Role category Term before the Term is updated
	 * because we need to get the corresponding CiviCRM Participant Role before the
	 * WordPress Term is updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_pre_edit( $args ) {

		// Get Term.
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

		// Check Taxonomy.
		if ( $term->taxonomy != $this->taxonomy_name ) {
			return;
		}

		// Store for reference in term_edited().
		$this->term_edited[ $term->term_id ] = clone $term;

	}

	/**
	 * Hook into updates to a Participant Role category Term.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_edited( $args ) {

		// Only look for Terms in the Participant Role Taxonomy.
		if ( $args['taxonomy'] != $this->taxonomy_name ) {
			return;
		}

		// Get current Term object.
		$new_term = get_term_by( 'id', $args['term_id'], $this->taxonomy_name );

		// Populate "Old Term" if we have it stored.
		$old_term = null;
		if ( ! empty( $this->term_edited[ $new_term->term_id ] ) ) {
			$old_term = $this->term_edited[ $new_term->term_id ];
			unset( $this->term_edited[ $new_term->term_id ] );
		}

		// Is this an Inline Edit?
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if ( ! empty( $_POST['action'] ) && $_POST['action'] == 'inline-save-tax' ) {

			// There will be no change to "Is Active" or "Counted".

		} else {

			// If "Is Active" is not set, then the Term is not active.
			if ( ! isset( $_POST['cwps-participant-role-active'] ) ) {
				$this->term_active_set( $new_term->term_id, false );
			} else {
				$this->term_active_set( $new_term->term_id, true );
			}

			// If "Counted" is not set, then the Term is not counted.
			if ( ! isset( $_POST['cwps-participant-role-counted'] ) ) {
				$this->term_counted_set( $new_term->term_id, false );
			} else {
				$this->term_counted_set( $new_term->term_id, true );
			}

		}

		// Unhook CiviCRM.
		$this->unregister_civicrm_hooks();

		// Update CiviCRM Participant Role - or create it if it doesn't exist.
		$participant_role_id = $this->participant_role_update( $new_term, $old_term );

		// Rehook CiviCRM.
		$this->register_civicrm_hooks();

		// Clear property.
		unset( $this->term_edited );

	}

	/**
	 * Hook into updates to a Participant Role category Term before the Term is updated
	 * because we need to get the corresponding CiviCRM Participant Role before the
	 * WordPress Term is updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_pre_delete( $args ) {

		// Get Term.
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

		// Check Taxonomy.
		if ( $term->taxonomy != $this->taxonomy_name ) {
			return;
		}

		// Store for reference in term_deleted().
		$this->term_deleted = clone $term;

		// Add the additional Term meta.
		$this->term_deleted->role_id = $this->term_meta_get( $term->term_id );
		$this->term_deleted->is_active = $this->term_active_get( $term->term_id );
		$this->term_deleted->filter = $this->term_counted_get( $term->term_id );

	}

	/**
	 * Hook into deletion of a Participant Role category Term.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function term_deleted( $args ) {

		// Only look for Terms in the Participant Role Taxonomy.
		if ( $args['taxonomy'] != $this->taxonomy_name ) {
			return;
		}

		// Bail if we have no stored Term.
		if ( ! isset( $this->term_deleted ) ) {
			return;
		}

		// Bail if it's not the same Term.
		if ( $this->term_deleted->term_id !== $args['deleted_term']->term_id ) {
			return;
		}

		// Check that the CiviCRM Participant Role exists.
		$existing = $this->civicrm->participant_role->get_by_id( $this->term_deleted->role_id );
		if ( $existing === false ) {
			return;
		}

		// Unhook CiviCRM.
		$this->unregister_civicrm_hooks();

		// Delete the CiviCRM Participant Role if it exists.
		$participant_role_id = $this->participant_role_delete( $this->term_deleted );

		// Rehook CiviCRM.
		$this->register_civicrm_hooks();

		// Clear property.
		unset( $this->term_deleted );

	}

	/**
	 * Creates a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role The CiviCRM Participant Role.
	 * @return array $result Array containing Participant Role category Term data.
	 */
	public function term_create( $participant_role ) {

		// Sanity check.
		if ( ! is_array( $participant_role ) ) {
			return false;
		}

		// Define description if present.
		$description = isset( $participant_role['description'] ) ? $participant_role['description'] : '';

		// Construct args.
		$args = [
			'slug' => sanitize_title( $participant_role['name'] ),
			'description' => $description,
		];

		// Unhook listeners.
		$this->unregister_mapper_hooks();

		// Insert it.
		$result = wp_insert_term( $participant_role['label'], $this->taxonomy_name, $args );

		// Rehook listeners.
		$this->register_mapper_hooks();

		// If all goes well, we get: array( 'term_id' => 12, 'term_taxonomy_id' => 34 )
		// If something goes wrong, we get a WP_Error object.
		if ( is_wp_error( $result ) ) {
			return false;
		}

		// Add the CiviCRM Participant Role ID to the Term's meta.
		$this->term_meta_set( $result['term_id'], (int) $participant_role['id'] );

		/*
		 * WordPress does not have an "Active/Inactive" Term state by default,
		 * but we can add a "term meta" value to hold this.
		 */

		// Use term meta to save "Active/Inactive" state.
		$this->term_active_set( $result['term_id'], (int) $participant_role['is_active'] );

		// Use term meta to save "Counted" option.
		$this->term_counted_set( $result['term_id'], (int) $participant_role['filter'] );

		// --<
		return $result;

	}

	/**
	 * Updates a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param array $new_role The CiviCRM Participant Role.
	 * @param array $old_role The CiviCRM Participant Role prior to the update.
	 * @return integer|bool $term_id The ID of the updated Participant Role category Term.
	 */
	public function term_update( $new_role, $old_role = null ) {

		// Sanity check.
		if ( ! is_array( $new_role ) ) {
			return false;
		}

		// First, query "term meta".
		$term = $this->term_get_by_meta( $new_role['id'] );

		// If the query produces a result.
		if ( $term instanceof WP_Term ) {

			// Grab the found Term ID.
			$term_id = $term->term_id;

		}

		// If we don't get one.
		if ( empty( $term_id ) ) {

			// Create Term.
			$result = $this->term_create( $new_role );

			// How did we do?
			if ( $result === false ) {
				return $result;
			}

			// --<
			return $result['term_id'];

		}

		// Define description if present.
		$description = isset( $new_role['description'] ) ? $new_role['description'] : '';

		// Construct Term.
		$args = [
			'name' => $new_role['label'],
			'slug' => sanitize_title( $new_role['name'] ),
			'description' => $description,
		];

		// Unhook listeners.
		$this->unregister_mapper_hooks();

		// Update Term.
		$result = wp_update_term( $term_id, $this->taxonomy_name, $args );

		// Rehook listeners.
		$this->register_mapper_hooks();

		// If all goes well, we get: array( 'term_id' => 12, 'term_taxonomy_id' => 34 )
		// If something goes wrong, we get a WP_Error object.
		if ( is_wp_error( $result ) ) {
			return false;
		}

		/*
		 * WordPress does not have an "Active/Inactive" Term state by default,
		 * but we can add a "term meta" value to hold this.
		 */

		// Use term meta to save "Active/Inactive" state.
		$this->term_active_set( $result['term_id'], (int) $new_role['is_active'] );

		// Use term meta to save "Counted" option.
		$this->term_counted_set( $result['term_id'], (int) $new_role['filter'] );

		// --<
		return $result['term_id'];

	}

	/**
	 * Deletes a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param integer $term_id The Term to delete.
	 * @return integer|boolean|WP_Error $term_id The ID of the updated Term in the Participant Role category.
	 */
	public function term_delete( $term_id ) {

		// Unhook listeners.
		$this->unregister_mapper_hooks();

		// Delete the Term.
		$result = wp_delete_term( $term_id, $this->taxonomy_name );

		// Rehook listeners.
		$this->register_mapper_hooks();

		// True on success, false if Term does not exist. Zero on attempted
		// deletion of default Category. WP_Error if the Taxonomy does not exist.
		return $result;

	}

	/**
	 * Never let Radio Buttons for Taxonomies filter get_terms() to add a null
	 * Term because CiviCRM requires a Participant to have a Term/Role.
	 *
	 * @since 0.5
	 *
	 * @param bool $set True if null Term is to be set, false otherwise.
	 * @return bool $set True if null Term is to be set, false otherwise.
	 */
	public function term_skip_null( $set ) {

		// A class property is passed in, so set that.
		$set = 0;

		// --<
		return $set;

	}

	/**
	 * Trigger hiding of "Parent Category" dropdown in metaboxes.
	 *
	 * @since 0.5
	 */
	public function terms_dropdown_intercept() {

		// Trigger emptying of dropdown.
		add_filter( 'wp_dropdown_cats', [ $this, 'terms_dropdown_clear' ], 20, 2 );

	}

	/**
	 * Always hide "Parent Category" dropdown in metaboxes.
	 *
	 * @since 0.5
	 *
	 * @param string $output The existing output.
	 * @param array $parsed_args The arguments used to build the drop-down.
	 * @return string $output The modified output.
	 */
	public function terms_dropdown_clear( $output, $parsed_args ) {

		// Only clear Participant Role category.
		if ( $parsed_args['taxonomy'] != $this->taxonomy_name ) {
			return $output;
		}

		// Only once please, in case further dropdowns are rendered.
		remove_filter( 'wp_dropdown_cats', [ $this, 'terms_dropdown_clear' ], 20 );

		// Clear.
		return '';

	}

	/**
	 * Make sure new Participant Posts have the default Term checked if no Term
	 * has been chosen - e.g. on the "Add New Participant" screen.
	 *
	 * @since 0.5
	 *
	 * @param array $args An array of arguments.
	 * @param integer $post_id The Post ID.
	 */
	public function term_default_checked( $args, $post_id ) {

		// Only modify Participant Role category.
		if ( $args['taxonomy'] != $this->taxonomy_name ) {
			return $args;
		}

		// If this is a Post.
		if ( $post_id ) {

			// Get existing Terms.
			$args['selected_cats'] = wp_get_object_terms(
				$post_id,
				$args['taxonomy'],
				array_merge( $args, [ 'fields' => 'ids' ] )
			);

			// Bail if a category is already set.
			if ( ! empty( $args['selected_cats'] ) ) {
				return $args;
			}

		}

		// Get the default CiviCRM Participant Role value.
		$participant_role_value = $this->participant_role_default_value_get();

		// Bail if something went wrong.
		if ( $participant_role_value === false ) {
			return $args;
		}

		// Get the CiviCRM Participant Role data.
		$participant_role = $this->civicrm->participant_role->get_by_value( $participant_role_value );

		// Bail if something went wrong.
		if ( $participant_role === false ) {
			return $args;
		}

		// Get corresponding Term ID.
		$term = $this->term_get_by_meta( $participant_role['id'] );
		if ( $term === false ) {
			return $args;
		}

		// Set argument.
		$args['selected_cats'] = [ $term->term_id ];

		// --<
		return $args;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Term in the Participant Role category for a given CiviCRM Participant Role ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the CiviCRM Participant Role.
	 * @return WP_Term|bool $term The Term object, or false on failure.
	 */
	public function term_get_by_meta( $participant_role_id ) {

		// Query Terms for the Term with the ID of the CiviCRM Participant Role in meta data.
		$args = [
			'taxonomy' => $this->taxonomy_name,
			'hide_empty' => false,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'meta_query' => [
				[
					'key' => $this->term_meta_key,
					'value' => $participant_role_id,
					'compare' => '=',
				],
			],
		];

		// Get what should only be a single Term.
		$terms = get_terms( $args );
		if ( empty( $terms ) ) {
			return false;
		}

		// Log a message and bail if there's an error.
		if ( is_wp_error( $terms ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $terms->get_error_message(),
				'terms' => $terms,
				'participant_role_id' => $participant_role_id,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// If we get more than one, WTF?
		if ( count( $terms ) > 1 ) {
			return false;
		}

		// Init return.
		$term = false;

		// Grab Term data.
		if ( count( $terms ) === 1 ) {
			$term = array_pop( $terms );
		}

		// --<
		return $term;

	}

	/**
	 * Get CiviCRM Participant Role for a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param integer $term_id The numeric ID of the Term.
	 * @return integer|bool $participant_role_id The ID of the CiviCRM Participant Role, or false on failure.
	 */
	public function term_meta_get( $term_id ) {

		// Get the CiviCRM Participant Role ID from the Term's meta.
		$participant_role_id = get_term_meta( $term_id, $this->term_meta_key, true );

		// Bail if there is no result.
		if ( empty( $participant_role_id ) ) {
			return false;
		}

		// --<
		return $participant_role_id;

	}

	/**
	 * Add meta data to a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param integer $term_id The numeric ID of the Term.
	 * @param integer $participant_role_id The numeric ID of the CiviCRM Participant Role.
	 * @return integer|bool $meta_id The ID of the meta, or false on failure.
	 */
	public function term_meta_set( $term_id, $participant_role_id ) {

		// Add the CiviCRM Participant Role ID to the Term's meta.
		$meta_id = add_term_meta( $term_id, $this->term_meta_key, intval( $participant_role_id ), true );

		// Log something if there's an error.
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if ( $meta_id === false ) {

			/*
			 * This probably means that the Term already has its Term meta set.
			 * Uncomment the following to debug if you need to.
			 */

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'Could not add Term meta', 'civicrm-wp-profile-sync' ),
				'term_id' => $term_id,
				'participant_role_id' => $participant_role_id,
				'backtrace' => $trace,
			], true ) );
			*/

		}

		// Log a message if the Term ID is ambiguous between Taxonomies.
		if ( is_wp_error( $meta_id ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $meta_id->get_error_message(),
				'term_id' => $term_id,
				'participant_role_id' => $participant_role_id,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $meta_id;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get "Is Active" state of a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param integer $term_id The numeric ID of the Term.
	 * @return integer $active 1 if the Term is active, 0 otherwise.
	 */
	public function term_active_get( $term_id ) {

		// Get the "Is Active" value from the Term's meta.
		$active = get_term_meta( $term_id, $this->term_active_meta_key, true );

		// Bail if there is no result.
		if ( empty( $active ) ) {
			return 0;
		}

		// --<
		return (int) $active;

	}

	/**
	 * Sets the "Is Active" meta data for a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param integer $term_id The numeric ID of the Term.
	 * @param integer|bool $active True if the Term is active, false otherwise.
	 * @return integer|bool $meta_id The ID of the meta, or false on failure.
	 */
	public function term_active_set( $term_id, $active ) {

		// Updates (or adds) the "Is Active" meta data to the Term.
		$meta_id = update_term_meta( $term_id, $this->term_active_meta_key, (int) $active );

		// Log something if there's an error.
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if ( $meta_id === false ) {

			/*
			 * This probably means that the Term already has its Term meta set.
			 * Uncomment the following to debug if you need to.
			 */

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'Could not add Term meta', 'civicrm-wp-profile-sync' ),
				'term_id' => $term_id,
				'active' => $active,
				'backtrace' => $trace,
			], true ) );
			*/

		}

		// Log a message if the Term ID is ambiguous between Taxonomies.
		if ( is_wp_error( $meta_id ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $meta_id->get_error_message(),
				'term_id' => $term_id,
				'active' => $active,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $meta_id;

	}

	/**
	 * Get "Counted" state of a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param integer $term_id The numeric ID of the Term.
	 * @return integer $counted 1 if the Term is counted, 0 otherwise.
	 */
	public function term_counted_get( $term_id ) {

		// Get the "Is Active" value from the Term's meta.
		$counted = get_term_meta( $term_id, $this->term_counted_meta_key, true );

		// Bail if there is no result.
		if ( empty( $counted ) ) {
			return 0;
		}

		// --<
		return 1;

	}

	/**
	 * Sets the "Counted" meta data for a Term in the Participant Role category.
	 *
	 * @since 0.5
	 *
	 * @param integer $term_id The numeric ID of the Term.
	 * @param integer|bool $counted True if the Term is counted, false otherwise.
	 * @return integer|bool $meta_id The ID of the meta, or false on failure.
	 */
	public function term_counted_set( $term_id, $counted ) {

		// Updates (or adds) the "Counted" meta data to the Term.
		$meta_id = update_term_meta( $term_id, $this->term_counted_meta_key, (int) $counted );

		// Log something if there's an error.
		// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
		if ( $meta_id === false ) {

			/*
			 * This probably means that the Term already has its Term meta set.
			 * Uncomment the following to debug if you need to.
			 */

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'Could not add Term meta', 'civicrm-wp-profile-sync' ),
				'term_id' => $term_id,
				'counted' => $counted,
				'backtrace' => $trace,
			], true ) );
			*/

		}

		// Log a message if the Term ID is ambiguous between Taxonomies.
		if ( is_wp_error( $meta_id ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $meta_id->get_error_message(),
				'term_id' => $term_id,
				'counted' => $counted,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $meta_id;

	}

	// -------------------------------------------------------------------------

	/**
	 * Callback for the CiviCRM 'civi.dao.postInsert' hook.
	 *
	 * @since 0.5
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function participant_role_created( $event, $hook ) {

		// Extract CiviCRM Participant Role for this hook.
		$participant_role =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $participant_role instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if it's not a CiviCRM Participant Role.
		$opt_group_id = $this->civicrm->participant_role->option_group_id_get();
		if ( $opt_group_id === false || $opt_group_id != $participant_role->option_group_id ) {
			return;
		}

		// Denullify the description.
		if ( ! empty( $participant_role->description ) ) {
			$description = $this->plugin->civicrm->denullify( $participant_role->description );
		} else {
			$description = '';
		}

		// Construct Term data.
		$term_data = [
			'id' => $participant_role->id,
			'label' => $participant_role->label,
			'name' => $participant_role->label,
			'description' => $description,
			'is_active' => $participant_role->is_active,
			'filter' => $participant_role->filter,
		];

		// Unhook listeners.
		$this->unregister_mapper_hooks();

		// Create Participant Role Term.
		$result = $this->term_create( $term_data );

		// Rehook listeners.
		$this->register_mapper_hooks();

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.preUpdate' hook.
	 *
	 * @since 0.5
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function participant_role_pre_update( $event, $hook ) {

		// Extract CiviCRM Participant Role for this hook.
		$participant_role =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $participant_role instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Cast ID as integer for array key.
		$participant_role_id = (int) $participant_role->id;

		// Get the full CiviCRM Participant Role before it is updated.
		$participant_role_pre = $this->civicrm->participant_role->get_by_id( $participant_role_id );

		// Maybe cast previous Participant Role data as object.
		if ( ! is_object( $participant_role_pre ) ) {
			$participant_role_pre = (object) $participant_role_pre;
		}

		// Stash in property array.
		$this->bridging_array[ $participant_role_id ] = $participant_role_pre;

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.postUpdate' hook.
	 *
	 * @since 0.5
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function participant_role_updated( $event, $hook ) {

		// Extract CiviCRM Participant Role for this hook.
		$participant_role =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $participant_role instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if it's not a CiviCRM Participant Role.
		$opt_group_id = $this->civicrm->participant_role->option_group_id_get();
		if ( $opt_group_id === false || (int) $opt_group_id !== (int) $participant_role->option_group_id ) {
			return;
		}

		// Get the full data for the updated CiviCRM Participant Role.
		$role_full = $this->civicrm->participant_role->get_by_id( $participant_role->id );
		if ( $role_full === false ) {
			return;
		}

		// Denullify the description.
		if ( ! empty( $role_full['description'] ) ) {
			$role_full['description'] = $this->plugin->civicrm->denullify( $role_full['description'] );
		} else {
			$role_full['description'] = '';
		}

		// Unhook listeners.
		$this->unregister_mapper_hooks();

		// Update Participant Role Term - or create if it doesn't exist.
		$result = $this->term_update( $role_full );

		// Rehook listeners.
		$this->register_mapper_hooks();

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.preDelete' hook.
	 *
	 * @since 0.5
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function participant_role_pre_delete( $event, $hook ) {

		// Extract CiviCRM Participant Role for this hook.
		$participant_role =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $participant_role instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Get the actual CiviCRM Participant Role being deleted.
		$participant_role = $this->civicrm->participant_role->get_by_id( $participant_role->id );
		if ( $participant_role === false ) {
			return;
		}

		// Bail if there's no corresponding Term.
		$term = $this->term_get_by_meta( $participant_role['id'] );
		if ( $term === false ) {
			return;
		}

		// Unhook listeners.
		$this->unregister_mapper_hooks();

		// Delete Term.
		$success = $this->term_delete( $term->term_id );

		// Rehook listeners.
		$this->register_mapper_hooks();

	}

	// -------------------------------------------------------------------------

	/**
	 * Updates a CiviCRM Participant Role.
	 *
	 * @since 0.5
	 *
	 * @param object $new_term The new Participant Role category Term.
	 * @param object $old_term The Participant Role category Term as it was before update.
	 * @return integer|bool $participant_role_id The CiviCRM Participant Role ID, or false on failure.
	 */
	public function participant_role_update( $new_term, $old_term = null ) {

		// Sanity check.
		if ( ! is_object( $new_term ) ) {
			return false;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Get Option Group ID.
		$opt_group_id = $this->civicrm->participant_role->option_group_id_get();
		if ( $opt_group_id === false ) {
			return false;
		}

		// Define CiviCRM Participant Role.
		$params = [
			'version' => 3,
			'option_group_id' => $opt_group_id,
			'label' => $new_term->name,
			//'name' => $new_term->name,
		];

		// If there is a description, apply content filters and add to params.
		if ( ! empty( $new_term->description ) ) {
			$params['description'] = apply_filters( 'cwps/acf/civicrm/participant-cpt/term-desc', $new_term->description );
		} else {
			$params['description'] = '';
		}

		// Grab the additional Term meta.
		$params['is_active'] = $this->term_active_get( $new_term->term_id );
		$params['filter'] = $this->term_counted_get( $new_term->term_id );

		// Trigger update if we find a synced CiviCRM Participant Role ID.
		$participant_role_id = $this->term_meta_get( $new_term->term_id );
		if ( ! empty( $participant_role_id ) ) {
			$params['id'] = (int) $participant_role_id;
		}

		// Create (or update) the CiviCRM Participant Role.
		$result = civicrm_api( 'OptionValue', 'create', $params );

		// Bail if there is an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'params' => $params,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Success, grab CiviCRM Participant Role ID.
		if ( ! empty( $result['id'] ) && is_numeric( $result['id'] ) ) {
			$participant_role_id = (int) $result['id'];
		}

		// --<
		return $participant_role_id;

	}

	/**
	 * Delete a CiviCRM Participant Role.
	 *
	 * @since 0.5
	 *
	 * @param object $term The Participant Role category Term.
	 * @return array|bool CiviCRM API data array on success, false on failure.
	 */
	public function participant_role_delete( $term ) {

		// Sanity check.
		if ( ! ( $term instanceof WP_Term ) ) {
			return false;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Get ID of CiviCRM Participant Role to delete.
		if ( empty( $term->role_id ) ) {
			return false;
		}

		// Define CiviCRM Participant Role.
		$params = [
			'version' => 3,
			'id' => $term->role_id,
		];

		// Delete the CiviCRM Participant Role.
		$result = civicrm_api( 'OptionValue', 'delete', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => $result['error_message'],
				'params' => $params,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $result;

	}

	/**
	 * Get a CiviCRM Participant Role value by its ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the CiviCRM Participant Role.
	 * @return integer|bool $value The value of the CiviCRM Participant Role (or false on failure)
	 */
	public function participant_role_value_get( $participant_role_id ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Get Option Group ID.
		$opt_group_id = $this->civicrm->participant_role->option_group_id_get();
		if ( $opt_group_id === false ) {
			return false;
		}

		// Define params to get item.
		$params = [
			'version' => 3,
			'option_group_id' => $opt_group_id,
			'id' => $participant_role_id,
			'options' => [
				'limit' => 1,
			],
		];

		// Get the item.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return false;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return false;
		}

		// The result set should contain only one item.
		$option_value = array_pop( $result['values'] );

		// Assign the ID.
		$value = (int) $option_value['value'];

		// --<
		return $value;

	}

	/**
	 * Syncs all CiviCRM Participant Roles to Terms in the Custom Taxonomy.
	 *
	 * @since 0.5
	 */
	public function participant_roles_sync_to_terms() {

		// Get all CiviCRM Participant Roles.
		$participant_roles = $this->civicrm->participant_role->get_all();
		if ( empty( $participant_roles ) ) {
			return;
		}

		// Create (or update) the corresponding Terms.
		foreach ( $participant_roles as $id => $participant_role ) {
			$this->term_update( $participant_role );
		}

	}

	/**
	 * Get all CiviCRM Participant Roles formatted as a dropdown list.
	 *
	 * The pseudo-ID is actually the CiviCRM Participant Role "value" rather than
	 * the CiviCRM Participant Role ID.
	 *
	 * @since 0.5
	 *
	 * @return string $html Markup containing select options.
	 */
	public function participant_roles_select_options_get() {

		// Init return.
		$html = '';

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $html;
		}

		// Get all CiviCRM Participant Roles.
		$participant_roles = $this->civicrm->participant_role->get_all();
		if ( empty( $participant_roles ) ) {
			return $html;
		}

		// Init options.
		$options = [];

		// Get existing Participant Role value.
		$existing_value = $this->participant_role_default_value_get();

		foreach ( $participant_roles as $key => $participant_role ) {

			// Get Participant Role value.
			$participant_role_value = (int) $participant_role['value'];

			// Override selected if this value is the same as in the Post.
			$selected = '';
			if ( $existing_value === $participant_role_value ) {
				$selected = ' selected="selected"';
			}

			// Construct option.
			$options[] = '<option value="' . $participant_role_value . '"' . $selected . '>' . esc_html( $participant_role['label'] ) . '</option>';

		}

		// Create markup.
		$html = implode( "\n", $options );

		// --<
		return $html;

	}

	/**
	 * Get the default CiviCRM Participant Role value.
	 *
	 * It is assumed that this is the Participant Role with the lowest weight.
	 *
	 * @since 0.5
	 *
	 * @param object $post The Participant Post object.
	 * @return integer|bool $first_value The numeric ID of the CiviCRM Participant Role.
	 */
	public function participant_role_default_value_get( $post = null ) {

		// Get all CiviCRM Participant Roles.
		$participant_roles = $this->civicrm->participant_role->get_all();
		if ( empty( $participant_roles ) ) {
			return false;
		}

		// Grab the first item.
		$first_role = array_shift( $participant_roles );
		$first_value = $first_role['value'];

		// --<
		return $first_value;

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

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/taxonomies/term-participant-role-add.php';

	}

	/**
	 * Add a form element to the "Edit Term" form.
	 *
	 * @since 0.4
	 *
	 * @param WP_Term $tag The current taxonomy term object.
	 * @param string $taxonomy The current taxonomy slug.
	 */
	public function form_element_edit_term_add( $tag, $taxonomy ) {

		// Get the meta values for the form elements.
		$active = $this->term_active_get( $tag->term_id );
		$counted = $this->term_counted_get( $tag->term_id );

		// Assign value to "Is Active" checkbox.
		$is_active = '';
		if ( ! empty( $active ) ) {
			$is_active = ' checked="checked"';
		}

		// Assign value to "Counted" checkbox.
		$is_counted = '';
		if ( ! empty( $counted ) ) {
			$is_counted = ' checked="checked"';
		}

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/taxonomies/term-participant-role-edit.php';

	}

	// -------------------------------------------------------------------------

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
		global $wp_admin_bar;

		// Bail if the current screen is not an Edit screen.
		if ( ! is_admin() ) {
			return;
		}

		// Bail if the current screen is not an Edit Taxonomy screen.
		$screen = get_current_screen();
		if ( $screen instanceof WP_Screen && $screen->base != 'edit-tags' ) {
			return;
		}

		// Bail if not our Taxonomy.
		if ( $screen->taxonomy != $this->taxonomy_name ) {
			return;
		}

		// TODO: Check permission to view Participant Roles.

		// Get the URL for the Participant Roles page in CiviCRM.
		$url = $this->plugin->civicrm->get_link( 'civicrm/admin/options/participant_role', 'reset=1' );

		// Add item to CAU menu.
		$wp_admin_bar->add_node( [
			'id' => 'cau-0',
			'parent' => $id,
			'title' => __( 'View in CiviCRM', 'civicrm-wp-profile-sync' ),
			'href' => $url,
		] );

	}

}
