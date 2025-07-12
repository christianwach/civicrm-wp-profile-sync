<?php
/**
 * CiviCRM City Class.
 *
 * Handles CiviCRM City functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM City Class.
 *
 * A class that encapsulates CiviCRM City functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Address_City extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
	 * CiviCRM object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM
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
	 * An array of Addresses prior to delete.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Addresses in an array and try and match them up
	 * in the post delete hook.
	 *
	 * @since 0.4
	 * @access private
	 * @var array
	 */
	private $bridging_array = [];

	/**
	 * Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $fields_handled = [
		'civicrm_address_city',
	];

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
		$this->civicrm    = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

		// Init parent.
		parent::__construct();

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

		// Add any City Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post-Contact sync event.
		add_action( 'cwps/acf/post/contact/sync', [ $this, 'contact_sync_to_post' ], 10 );

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

		// Listen for events from our Mapper that require City updates.
		add_action( 'cwps/acf/mapper/address/created', [ $this, 'address_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/edited', [ $this, 'address_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/delete/pre', [ $this, 'address_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/address/deleted', [ $this, 'address_deleted' ], 10 );

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
		remove_action( 'cwps/acf/mapper/address/created', [ $this, 'address_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/edited', [ $this, 'address_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/delete/pre', [ $this, 'address_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/address/deleted', [ $this, 'address_deleted' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept when a Post has been updated from a Contact via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to built-in Contact Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get all Address Records for this Contact.
		$data = $this->plugin->civicrm->address->addresses_get_by_contact_id( $args['objectId'] );

		// Bail if there are no Address Records.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no City Fields.
		if ( empty( $acf_fields['city'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['city'] as $selector => $address_field ) {

			// Init Field value.
			$value = '';

			if ( 'primary' === $address_field ) {

				// Assign City from the Primary Address.
				foreach ( $data as $address ) {
					if ( ! empty( $address->is_primary ) ) {
						$value = $address->city;
						break;
					}
				}

			} elseif ( is_numeric( $address_field ) ) {

				// Assign City from the Address Location Type.
				foreach ( $data as $address ) {
					if ( (int) $address->location_type_id === (int) $address_field ) {
						$value = $address->city;
						break;
					}
				}

			}

			// Now update the ACF Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept when a CiviCRM Address Record has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function address_edited( $args ) {

		// Grab the Address Record data.
		$address = $args['objectRef'];

		// Bail if this is not a Contact's Address Record.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Process the Address Record.
		$this->address_process( $address, $args );

		// If this address is a "Master Address" then it will return "Shared Addresses".
		$addresses_shared = $this->plugin->civicrm->address->addresses_shared_get_by_id( $address->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Update all of them.
		foreach ( $addresses_shared as $address_shared ) {
			$this->address_process( $address_shared, $args );
		}

	}

	/**
	 * A CiviCRM Contact's Address Record is about to be deleted.
	 *
	 * Before an Address Record is deleted, we need to retrieve the Address Record
	 * because the data passed via "civicrm_post" only contains the ID of the
	 * Address Record.
	 *
	 * This is not required when creating or editing an Address Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function address_pre_delete( $args ) {

		// We just need the Address ID.
		$address_id = (int) $args['objectId'];

		// Grab the Address Record data from the database.
		$address_pre = $this->plugin->civicrm->address->address_get_by_id( $address_id );

		// Maybe cast previous Address Record data as object.
		if ( ! is_object( $address_pre ) ) {
			$address_pre = (object) $address_pre;
		}

		// Stash in property array.
		$this->bridging_array[ $address_id ] = $address_pre;

	}

	/**
	 * A CiviCRM Address Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function address_deleted( $args ) {

		// We just need the Address ID.
		$address_id = (int) $args['objectId'];

		// Populate "Previous Address" if we have it stored.
		$address_pre = null;
		if ( ! empty( $this->bridging_array[ $address_id ] ) ) {
			$address_pre = $this->bridging_array[ $address_id ];
			unset( $this->bridging_array[ $address_id ] );
		}

		// Bail if we can't find the previous Address Record or it doesn't match.
		if ( empty( $address_pre ) || $address_id !== (int) $address_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Address Record.
		if ( empty( $address_pre->contact_id ) ) {
			return;
		}

		// Process the Address Record.
		$this->address_process( $address_pre, $args );

		// If this address is a "Master Address" then it will return "Shared Addresses".
		$addresses_shared = $this->plugin->civicrm->address->addresses_shared_get_by_id( $address_pre->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Process all of them.
		foreach ( $addresses_shared as $address_shared ) {
			$this->address_process( $address_shared, $args );
		}

	}

	/**
	 * Process a CiviCRM Address Record.
	 *
	 * @since 0.4
	 *
	 * @param object $address The CiviCRM Address Record object.
	 * @param array  $args The array of CiviCRM params.
	 */
	public function address_process( $address, $args ) {

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $address->contact_id );

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Test if any of this Contact's Contact Types is mapped to a Post Type.
		$post_types = $this->civicrm->contact->is_mapped( $contact, 'create' );
		if ( false !== $post_types ) {

			// Handle each Post Type in turn.
			foreach ( $post_types as $post_type ) {

				// Get the Post ID for this Contact.
				$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );

				// Skip if not mapped or Post doesn't yet exist.
				if ( false === $post_id ) {
					continue;
				}

				// Exclude "reverse" edits when a Post is the originator.
				if ( 'post' === $entity['entity'] && (int) $post_id === (int) $entity['id'] ) {
					continue;
				}

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $address, $args );

			}

		}

		/**
		 * Broadcast that an Address ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $address The CiviCRM Address Record object.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/address/city/updated', $contact, $address, $args );

	}

	/**
	 * Update City ACF Fields on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object         $address The CiviCRM Address Record object.
	 * @param array          $args The array of CiviCRM params.
	 */
	public function fields_update( $post_id, $address, $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Address Record Fields.
		if ( empty( $acf_fields['city'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['city'] as $selector => $address_field ) {

			// Init value.
			$value = '';

			// Process Address if not deleting it.
			if ( 'delete' !== $args['op'] ) {

				if ( 'primary' === $address_field ) {

					// Assign City from the Primary Address.
					if ( ! empty( $address->is_primary ) ) {
						$value = $address->city;
					}

				} elseif ( is_numeric( $address_field ) ) {

					// Assign City from the Address Location Type.
					if ( (int) $address->location_type_id === (int) $address_field ) {
						$value = $address->city;
					}

				}

			}

			// Now update Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $post_id );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add any City Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array   $acf_fields The existing ACF Fields array.
	 * @param array   $field The ACF Field.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Add if it has a reference to a City Field.
		if ( ! empty( $field['type'] ) && 'civicrm_address_city' === $field['type'] ) {
			if ( 1 === $field['city_is_primary'] ) {
				$acf_fields['city'][ $field['name'] ] = 'primary';
			} else {
				$acf_fields['city'][ $field['name'] ] = $field['city_location_type_id'];
			}
		}

		// --<
		return $acf_fields;

	}

}
