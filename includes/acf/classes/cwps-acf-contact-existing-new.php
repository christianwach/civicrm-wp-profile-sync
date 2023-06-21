<?php
/**
 * CiviCRM "Existing & New Contact" Class.
 *
 * Handles CiviCRM "Existing & New Contact" functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM "Existing & New Contact" Class.
 *
 * A class that encapsulates CiviCRM "Existing & New Contact" functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Existing_New extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * Fields which must be handled separately.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $fields_handled = [
		'civicrm_contact_existing_new',
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

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

		// Init parent.
		parent::__construct();

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
		$this->register_mapper_hooks();

		// Add any Contact ID Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post-Contact sync event.
		add_action( 'cwps/acf/post/contact/sync', [ $this, 'contact_sync_to_post' ], 10 );

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

		// Listen for events from our Mapper that require Contact ID updates.
		add_action( 'cwps/acf/mapper/contact/created', [ $this, 'contact_edited' ], 10 );
		add_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );

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
		remove_action( 'cwps/acf/mapper/contact/created', [ $this, 'contact_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a Post has been updated from a Contact via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Contact Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// The Contact ID is the Object ID.
		$contact_id = $args['objectId'];

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Contact ID Fields.
		if ( empty( $acf_fields['contact_id'] ) ) {
			return;
		}

		// Let's update each ACF Field in turn.
		foreach ( $acf_fields['contact_id'] as $selector => $dummy ) {
			$this->acf_loader->acf->field->value_update( $selector, $contact_id, $args['post_id'] );
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a CiviCRM Contact Record has been updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_edited( $args ) {

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $args['objectId'] );

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Test if any of this Contact's Contact Types is mapped to a Post Type.
		$post_types = $this->civicrm->contact->is_mapped( $contact );
		if ( $post_types !== false ) {

			// Handle each Post Type in turn.
			foreach ( $post_types as $post_type ) {

				// Get the Post ID for this Contact.
				$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );

				// Skip if not mapped or Post doesn't yet exist.
				if ( $post_id === false ) {
					continue;
				}

				// Exclude "reverse" edits when a Post is the originator.
				if ( $entity['entity'] === 'post' && $post_id == $entity['id'] ) {
					continue;
				}

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $args );

			}

		}

		/**
		 * Broadcast that a Contact Existing & New ACF Field may have been edited.
		 *
		 * @since 0.5.2
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/contact_existing_new/updated', $contact, $args );

	}

	/**
	 * Update Contact ID ACF Fields on an Entity mapped to a Contact ID.
	 *
	 * @since 0.5
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param array $args The array of CiviCRM params.
	 */
	public function fields_update( $post_id, $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Contact ID Fields.
		if ( empty( $acf_fields['contact_id'] ) ) {
			return;
		}

		// Let's update each ACF Field in turn.
		foreach ( $acf_fields['contact_id'] as $selector => $dummy ) {
			$this->acf_loader->acf->field->value_update( $selector, $args['objectId'], $post_id );
		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Add any Contact ID Fields that are attached to a Post.
	 *
	 * @since 0.5
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Add if it is a Contact ID Field.
		if ( ! empty( $field['type'] ) && $field['type'] == 'civicrm_contact_id' ) {
			$acf_fields['contact_id'][ $field['name'] ] = 1;
		}

		// --<
		return $acf_fields;

	}

}
