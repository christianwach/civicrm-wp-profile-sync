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
 * CiviCRM Profile Sync WordPress User Class.
 *
 * A class that encapsulates WordPress User functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_User {

	/**
	 * Plugin object.
	 *
	 * @since 0.4
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
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store references.
		$this->acf_loader = $acf_loader;
		$this->plugin = $acf_loader->plugin;

		// Init when this plugin is loaded.
		add_action( 'cwps/acf/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/acf/user/loaded' );

	}



	/**
	 * Register hooks on plugin init.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register query hooks.
		$this->register_query_hooks();

		// Register entity hooks.
		$this->register_mapper_wp_hooks();
		$this->register_mapper_civicrm_hooks();

		// TODO: Create AJAX-based Manual Sync.

		// Listen for the start and end of the User-to-Contact sync process.
		//add_action( 'cwps/wordpress/user_sync/pre', [ $this, 'wp_user_sync_pre' ], 10 );
		//add_action( 'cwps/wordpress/user_sync/post', [ $this, 'wp_user_synced' ], 10 );

		// Listen for the start and end of the Contact-to-User sync process.
		//add_action( 'cwps/civicrm/contact/contact_sync/pre', [ $this, 'contact_sync_pre' ], 10 );
		//add_action( 'cwps/civicrm/contact/contact_sync/post', [ $this, 'contact_synced' ], 10 );

		// Check if a Contact is being trashed and has a WordPress User.
		add_filter( 'cwps/acf/civicrm/contact/post/data', [ $this, 'contact_data' ], 50, 2 );

		// Listen for reverse-sync actions and prevent triggering needless procedures.
		add_action( 'cwps/acf/contact_field/reverse_sync/pre', [ $this, 'unregister_mapper_civicrm_hooks' ], 10 );
		add_action( 'cwps/acf/contact_field/reverse_sync/post', [ $this, 'register_mapper_civicrm_hooks' ], 10 );

	}



	/**
	 * Register callbacks for query filters.
	 *
	 * @since 0.4
	 */
	public function register_query_hooks() {

		// Listen for queries from other classes.
		add_filter( 'cwps/acf/query_field_group_mapped', [ $this, 'query_field_group_mapped' ], 10, 2 );
		add_filter( 'cwps/acf/query_custom_fields', [ $this, 'query_custom_fields' ], 10, 2 );
		add_filter( 'cwps/acf/civicrm/contact_field/get_for_acf_field', [ $this, 'query_contact_fields' ], 10, 3 );
		add_filter( 'cwps/acf/civicrm/relationships/get_for_acf_field', [ $this, 'query_relationship_fields' ], 10, 3 );
		add_filter( 'cwps/acf/query_post_id', [ $this, 'query_post_id' ], 10, 2 );
		add_filter( 'cwps/acf/query_contact_id', [ $this, 'query_contact_id' ], 10, 3 );

	}



	/**
	 * Unregister callbacks for query filters.
	 *
	 * @since 0.4
	 */
	public function unregister_query_hooks() {

		// Remove callbacks for queries.
		remove_filter( 'cwps/acf/query_field_group_mapped', [ $this, 'query_field_group_mapped' ], 10 );
		remove_filter( 'cwps/acf/query_custom_fields', [ $this, 'query_custom_fields' ], 10 );
		remove_filter( 'cwps/acf/civicrm/contact_field/get_for_acf_field', [ $this, 'query_contact_fields' ], 10 );
		remove_filter( 'cwps/acf/civicrm/relationships/get_for_acf_field', [ $this, 'query_relationship_fields' ], 10 );
		remove_filter( 'cwps/acf/query_post_id', [ $this, 'query_post_id' ], 10 );
		remove_filter( 'cwps/acf/query_contact_id', [ $this, 'query_contact_id' ], 10 );

	}



	/**
	 * Register callbacks for CiviCRM Entities that have their own hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_wp_hooks() {

		// Listen for events from our Mapper that require CiviCRM updates.
		add_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ], 10 );

	}



	/**
	 * Unregister callbacks for CiviCRM Entities that have their own hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_wp_hooks() {

		// Listen for events from our Mapper that require CiviCRM updates.
		remove_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ], 10 );

	}



	/**
	 * Register callbacks for CiviCRM Entities that have their own hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_civicrm_hooks() {

		// Listen for events from our Mapper that require WordPress updates.
		add_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );

		// Listen for events from our Mapper that CiviCRM Custom Fields have been edited.
		add_action( 'cwps/acf/mapper/civicrm/custom/edited', [ $this, 'custom_edited' ], 10 );

		// Act on CiviCRM Entities being edited.
		add_action( 'cwps/acf/mapper/email/created', [ $this, 'email_edited' ], 10 );
		add_action( 'cwps/acf/mapper/email/edited', [ $this, 'email_edited' ], 10 );
		add_action( 'cwps/acf/mapper/email/deleted', [ $this, 'email_edited' ], 10 );

		add_action( 'cwps/acf/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		add_action( 'cwps/acf/mapper/website/created', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/acf/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/acf/mapper/website/deleted', [ $this, 'website_edited' ], 10 );

		add_action( 'cwps/acf/mapper/phone/created', [ $this, 'phone_edited' ], 10 );
		add_action( 'cwps/acf/mapper/phone/edited', [ $this, 'phone_edited' ], 10 );
		add_action( 'cwps/acf/mapper/phone/delete/pre', [ $this, 'phone_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/phone/deleted', [ $this, 'phone_deleted' ], 10 );

		add_action( 'cwps/acf/mapper/im/created', [ $this, 'im_edited' ], 10 );
		add_action( 'cwps/acf/mapper/im/edited', [ $this, 'im_edited' ], 10 );
		add_action( 'cwps/acf/mapper/im/delete/pre', [ $this, 'im_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/im/deleted', [ $this, 'im_deleted' ], 10 );

		add_action( 'cwps/acf/mapper/relationship/created', [ $this, 'relationship_edited' ], 10 );
		add_action( 'cwps/acf/mapper/relationship/edited', [ $this, 'relationship_edited' ], 10 );
		add_action( 'cwps/acf/mapper/relationship/deleted', [ $this, 'relationship_edited' ], 10 );

		add_action( 'cwps/acf/mapper/address/created', [ $this, 'address_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/edited', [ $this, 'address_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/delete/pre', [ $this, 'address_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/address/deleted', [ $this, 'address_deleted' ], 10 );

		add_action( 'cwps/acf/mapper/address/edit/pre', [ $this, 'map_pre_edit' ], 10 );
		add_action( 'cwps/acf/mapper/address/created', [ $this, 'map_created' ], 10 );
		add_action( 'cwps/acf/mapper/address/edited', [ $this, 'map_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/deleted', [ $this, 'map_deleted' ], 10 );

		add_action( 'cwps/acf/mapper/address/created', [ $this, 'city_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/edited', [ $this, 'city_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/delete/pre', [ $this, 'city_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/address/deleted', [ $this, 'city_deleted' ], 10 );

		add_action( 'cwps/acf/mapper/address/created', [ $this, 'state_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/edited', [ $this, 'state_edited' ], 10 );
		add_action( 'cwps/acf/mapper/address/delete/pre', [ $this, 'state_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/address/deleted', [ $this, 'state_deleted' ], 10 );

		add_action( 'cwps/acf/mapper/contact/created', [ $this, 'contact_id_edited' ], 10 );
		add_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_id_edited' ], 10 );

	}



	/**
	 * Unregister callbacks for CiviCRM Entities that have their own hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_civicrm_hooks() {

		// Stop listening for events from our Mapper that require WordPress updates.
		remove_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );

		// Stop listening for events from our Mapper that CiviCRM Custom Fields have been edited.
		remove_action( 'cwps/acf/mapper/civicrm/custom/edited', [ $this, 'custom_edited' ], 10 );

		// Remove callbacks for CiviCRM Entities.
		remove_action( 'cwps/acf/mapper/email/created', [ $this, 'email_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/email/edited', [ $this, 'email_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/email/deleted', [ $this, 'email_edited' ], 10 );

		remove_action( 'cwps/acf/mapper/website/edit/pre', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/website/created', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/website/deleted', [ $this, 'website_edited' ], 10 );

		remove_action( 'cwps/acf/mapper/phone/created', [ $this, 'phone_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/edited', [ $this, 'phone_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/delete/pre', [ $this, 'phone_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/deleted', [ $this, 'phone_deleted' ], 10 );

		remove_action( 'cwps/acf/mapper/im/created', [ $this, 'im_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/im/edited', [ $this, 'im_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/im/delete/pre', [ $this, 'im_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/im/deleted', [ $this, 'im_deleted' ], 10 );

		remove_action( 'cwps/acf/mapper/relationship/created', [ $this, 'relationship_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/relationship/edited', [ $this, 'relationship_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/relationship/deleted', [ $this, 'relationship_edited' ], 10 );

		remove_action( 'cwps/acf/mapper/address/created', [ $this, 'address_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/edited', [ $this, 'address_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/delete/pre', [ $this, 'address_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/address/deleted', [ $this, 'address_deleted' ], 10 );

		remove_action( 'cwps/acf/mapper/address/edit/pre', [ $this, 'map_pre_edit' ], 10 );
		remove_action( 'cwps/acf/mapper/address/created', [ $this, 'map_created' ], 10 );
		remove_action( 'cwps/acf/mapper/address/edited', [ $this, 'map_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/deleted', [ $this, 'map_deleted' ], 10 );

		remove_action( 'cwps/acf/mapper/address/created', [ $this, 'city_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/edited', [ $this, 'city_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/delete/pre', [ $this, 'city_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/address/deleted', [ $this, 'city_deleted' ], 10 );

		remove_action( 'cwps/acf/mapper/address/created', [ $this, 'state_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/edited', [ $this, 'state_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/address/delete/pre', [ $this, 'state_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/address/deleted', [ $this, 'state_deleted' ], 10 );

		remove_action( 'cwps/acf/mapper/contact/created', [ $this, 'contact_id_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_id_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update the CiviCRM Contact when the ACF Fields on a WordPress User have been edited.
	 *
	 * During the "Add User" process, ACF hooks into "user_register" earlier than
	 * this plugin, so the link to the CiviCRM Contact has not yet been made when
	 * ACF fires the "acf/save_post" action.
	 *
	 * @see CiviCRM_WP_Profile_Sync_CAI::wp_user_synced()
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function acf_fields_saved( $args ) {

		// Bail early if this Field Group is attached to a Post Type.
		if ( is_numeric( $args['post_id'] ) ) {
			return;
		}

		// Bail early if this Field Group is not attached to a WordPress User.
		if ( false === strpos( $args['post_id'], 'user_' ) ) {
			return;
		}

		// Get the WordPress User ID.
		$tmp = explode( '_', $args['post_id'] );
		$user_id = absint( $tmp[1] );

		// Bail if this is not a WordPress User.
		$user = new WP_User( $user_id );
		if ( ! $user->exists() ) {
			return;
		}

		// Bail if this User doesn't have a Contact.
		$contact = $this->plugin->mapper->ufmatch->contact_get_by_user_id( $user->ID );
		if ( $contact === false ) {
			return;
		}

		// Add our data to the params.
		$args['contact_id'] = $contact['id'];
		$args['contact'] = $contact;

		// We need the User not the Post.
		$args['user_id'] = $user->ID;
		$args['user'] = $user;
		$args['post'] = '';

		/*
		 * Get existing field values.
		 *
		 * These are actually the *new* values because we are hooking in *after*
		 * the fields have been saved.
		 *
		 * When getting values here, we pass in the Post ID, which is "user_N":
		 *
		 * @see https://www.advancedcustomfields.com/resources/get_fields/
		 */
		$fields = get_fields( $args['post_id'] );

		// Add the Fields to the args.
		$args['fields'] = $fields;

		// Remove this plugin's callbacks.
		$this->plugin->hooks_civicrm_remove();

		// Unregister entity hooks.
		$this->unregister_mapper_civicrm_hooks();

		// Update the Fields on the CiviCRM Contact.
		$this->acf_loader->civicrm->contact->update_from_fields( $args['contact_id'], $fields, $args['post_id'] );

		/**
		 * Broadcast that a Contact has been edited when ACF Fields were saved.
		 *
		 * Used to inform e.g.:
		 *
		 * - Contact Fields
		 * - Relationships
		 * - Addresses
		 * - Websites
		 * - Instant Messengers
		 * - Phones
		 *
		 * @since 0.4
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/acf/contact/acf_fields_saved', $args );

		// Re-register entity hooks.
		$this->register_mapper_civicrm_hooks();

		// Restore this plugin's callbacks.
		$this->plugin->hooks_civicrm_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a WordPress User when a CiviCRM Contact has been edited.
	 *
	 * This callback receives data via the CiviCRM ACF Integration mapper.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_edited( $args ) {

		// Sanity check.
		if ( empty( $args['objectRef']->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $args['objectRef']->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$args['post_id'] = 'user_' . $user_id;
		$args['post_type'] = '';

		/**
		 * Broadcast that a WordPress Entity has been edited from Contact details.
		 *
		 * Although we haven't edited a mapped WordPress Entity as such (even though
		 * this plugin handles sync with WordPress Users) we do want associated
		 * data to be synced. Calling this action does so.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM and discovered params.
		 */
		do_action( 'cwps/acf/post/edited', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update an Email ACF Field on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function email_edited( $args ) {

		// Grab the Email data.
		$email = $args['objectRef'];

		// Sanity check.
		if ( empty( $email->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $email->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->email->fields_update( $post_id, $email );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * A CiviCRM Contact's Website is about to be edited.
	 *
	 * Before an Website is edited, we need to store the previous data so that
	 * we can compare with the data after the edit. If there are changes, then
	 * we will need to update accordingly.
	 *
	 * This is not required for Website creation or deletion.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_edit( $args ) {

		// Grab the Website data.
		$website = $args['objectRef'];

		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $website->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Run the pre-edit routine.
		$this->acf_loader->civicrm->website->website_pre_edit( $args );

	}



	/**
	 * Update Website ACF Fields on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_edited( $args ) {

		// Grab the Website data.
		$website = $args['objectRef'];

		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $website->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->website->fields_update( $post_id, $website );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update Phone ACF Fields on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_edited( $args ) {

		// Extract Phone.
		$phone = $args['objectRef'];

		// Sanity check.
		if ( empty( $phone->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $phone->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Convert to ACF Phone data.
		$acf_phone = $this->acf_loader->civicrm->phone->prepare_from_civicrm( $phone );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->phone->fields_update( $post_id, $phone, $acf_phone, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Phone Record is about to be deleted.
	 *
	 * Before a Phone Record is deleted, we need to remove the corresponding
	 * element in the ACF Field data.
	 *
	 * This is not required when creating or editing a Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_pre_delete( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->phone_pre ) ) {
			unset( $this->phone_pre );
		}

		// We just need the Phone ID.
		$phone_id = (int) $args['objectId'];

		// Grab the Phone Record data from the database.
		$phone_pre = $this->acf_loader->civicrm->phone->phone_get_by_id( $phone_id );

		// Maybe cast previous Phone Record data as object and stash in a property.
		if ( ! is_object( $phone_pre ) ) {
			$this->phone_pre = (object) $phone_pre;
		} else {
			$this->phone_pre = $phone_pre;
		}

	}



	/**
	 * A CiviCRM Phone Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_deleted( $args ) {

		// Bail if we don't have a pre-delete Phone Record.
		if ( ! isset( $this->phone_pre ) ) {
			return;
		}

		// We just need the Phone ID.
		$phone_id = (int) $args['objectId'];

		// Sanity check.
		if ( $phone_id != $this->phone_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Phone Record.
		if ( empty( $this->phone_pre->contact_id ) ) {
			return;
		}

		// Overwrite empty Phone Record with full Record.
		$phone = $this->phone_pre;

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $phone->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Convert to ACF Phone data.
		$acf_phone = $this->acf_loader->civicrm->phone->prepare_from_civicrm( $phone );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->phone->fields_update( $post_id, $phone, $acf_phone, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update Instant Messenger ACF Fields on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function im_edited( $args ) {

		// Extract Instant Messenger.
		$im = $args['objectRef'];

		// Sanity check.
		if ( empty( $im->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $im->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Convert to ACF Instant Messenger data.
		$acf_im = $this->acf_loader->civicrm->im->prepare_from_civicrm( $im );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->im->fields_update( $post_id, $im, $acf_im, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Instant Messenger Record is about to be deleted.
	 *
	 * Before an Instant Messenger Record is deleted, we need to remove the
	 * corresponding element in the ACF Field data.
	 *
	 * This is not required when creating or editing an Instant Messenger Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function im_pre_delete( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->im_pre ) ) {
			unset( $this->im_pre );
		}

		// We just need the Instant Messenger ID.
		$im_id = (int) $args['objectId'];

		// Grab the Instant Messenger Record data from the database.
		$im_pre = $this->acf_loader->civicrm->im->im_get_by_id( $im_id );

		// Maybe cast previous Instant Messenger Record data as object and stash in a property.
		if ( ! is_object( $im_pre ) ) {
			$this->im_pre = (object) $im_pre;
		} else {
			$this->im_pre = $im_pre;
		}

	}



	/**
	 * A CiviCRM Instant Messenger Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function im_deleted( $args ) {

		// Bail if we don't have a pre-delete Instant Messenger Record.
		if ( ! isset( $this->im_pre ) ) {
			return;
		}

		// We just need the Instant Messenger ID.
		$im_id = (int) $args['objectId'];

		// Sanity check.
		if ( $im_id != $this->im_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Instant Messenger Record.
		if ( empty( $this->im_pre->contact_id ) ) {
			return;
		}

		// Overwrite empty Instant Messenger Record with full Record.
		$im = $this->im_pre;

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $im->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Convert to ACF Instant Messenger data.
		$acf_im = $this->acf_loader->civicrm->im->prepare_from_civicrm( $im );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->im->fields_update( $post_id, $im, $acf_im, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a Relationship ACF Field on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function relationship_edited( $args ) {

		// Grab Relationship object.
		$relationship = $args['objectRef'];

		// We need to update the ACF Fields on both Posts since they may be synced.
		$this->relationship_update( $relationship->contact_id_a, $relationship, $args['op'] );
		$this->relationship_update( $relationship->contact_id_b, $relationship, $args['op'] );

	}



	/**
	 * Update the Relationship ACF Field on a Post mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param int $contact_id The numeric ID of the Contact.
	 * @param array|object $relationship The Relationship data.
	 * @param string $op The type of database operation.
	 */
	public function relationship_update( $contact_id, $relationship, $op ) {

		// Sanity check.
		if ( empty( $contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->relationship->fields_update( $post_id, $relationship, $op );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update Address ACF Fields on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function address_edited( $args ) {

		// Extract Address.
		$address = $args['objectRef'];

		// Sanity check.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Convert to ACF Address data.
		$acf_address = $this->acf_loader->civicrm->addresses->prepare_from_civicrm( $address );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->addresses->fields_update( $post_id, $address, $acf_address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Address Record is about to be deleted.
	 *
	 * Before a Address Record is deleted, we need to remove the corresponding
	 * element in the ACF Field data.
	 *
	 * This is not required when creating or editing a Address Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function address_pre_delete( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->address_pre ) ) {
			unset( $this->address_pre );
		}

		// We just need the Address ID.
		$address_id = (int) $args['objectId'];

		// Grab the Address Record data from the database.
		$address_pre = $this->acf_loader->civicrm->address->address_get_by_id( $address_id );

		// Maybe cast previous Address Record data as object and stash in a property.
		if ( ! is_object( $address_pre ) ) {
			$this->address_pre = (object) $address_pre;
		} else {
			$this->address_pre = $address_pre;
		}

	}



	/**
	 * A CiviCRM Address Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function address_deleted( $args ) {

		// Bail if we don't have a pre-delete Address Record.
		if ( ! isset( $this->address_pre ) ) {
			return;
		}

		// We just need the Address ID.
		$address_id = (int) $args['objectId'];

		// Sanity check.
		if ( $address_id != $this->address_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Address Record.
		if ( empty( $this->address_pre->contact_id ) ) {
			return;
		}

		// Overwrite empty Address Record with full Record.
		$address = $this->address_pre;

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Convert to ACF Address data.
		$acf_address = $this->acf_loader->civicrm->addresses->prepare_from_civicrm( $address );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->addresses->fields_update( $post_id, $address, $acf_address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * A CiviCRM Contact's Address is about to be edited.
	 *
	 * Before an Address is edited, we need to store the previous data so that
	 * we can compare with the data after the edit. If there are changes, then
	 * we will need to update accordingly.
	 *
	 * This is not required for Address creation or deletion.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function map_pre_edit( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->map_address_pre ) ) {
			unset( $this->map_address_pre );
		}

		// Grab the Address object.
		$address = $args['objectRef'];

		// We need a Contact ID in the edited Address.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Grab the previous Address data from the database via API.
		$this->map_address_pre = $this->acf_loader->civicrm->address->address_get_by_id( $address->id );

	}



	/**
	 * A CiviCRM Contact's Address has just been created.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function map_created( $args ) {

		// Grab the Address object.
		$address = $args['objectRef'];

		// We need a Contact ID in the edited Address.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Update the ACF Fields for this Post.
		$this->acf_loader->civicrm->google_map->fields_update( $post_id, $address );

		// If this address has no "Master Address" then it might be one itself.
		$addresses_shared = $this->acf_loader->civicrm->address->addresses_shared_get_by_id( $address->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Update all of them.
		foreach( $addresses_shared AS $address_shared ) {

			// We need a Contact ID in the shared Address.
			if ( empty( $address_shared->contact_id ) ) {
				return;
			}

			// Bail if this Contact doesn't have a User ID.
			$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address_shared->contact_id );
			if ( $user_id === false ) {
				continue;
			}

			// Format the ACF "Post ID".
			$post_id = 'user_' . $user_id;

			// Now update the Fields.
			$this->acf_loader->civicrm->google_map->fields_update( $post_id, $address_shared );

		}

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * Update an Address ACF Field on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function map_edited( $args ) {

		// Grab the Address object.
		$address = $args['objectRef'];

		// We need a Contact ID in the edited Address.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Check if the edited Address has had its properties toggled.
		$address = $this->acf_loader->civicrm->google_map->address_properties_check( $address, $this->map_address_pre );

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Update the ACF Fields for this Post.
		$this->acf_loader->civicrm->google_map->fields_update( $post_id, $address, $this->map_address_pre );

		// If this address has no "Master Address" then it might be one itself.
		$addresses_shared = $this->acf_loader->civicrm->address->addresses_shared_get_by_id( $address->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Update all of them.
		foreach( $addresses_shared AS $address_shared ) {

			// We need a Contact ID in the shared Address.
			if ( empty( $address_shared->contact_id ) ) {
				return;
			}

			// Bail if this Contact doesn't have a User ID.
			$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address_shared->contact_id );
			if ( $user_id === false ) {
				continue;
			}

			// Format the ACF "Post ID".
			$post_id = 'user_' . $user_id;

			// Now update the Fields.
			$this->acf_loader->civicrm->google_map->fields_update( $post_id, $address_shared );

		}

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Address has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function map_deleted( $args ) {

		// Grab the Address object.
		$address = $args['objectRef'];

		// We need a Contact ID in the edited Address.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Set a property to flag that it's being deleted.
		$address->to_delete = true;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Update the ACF Fields for this Post.
		$this->acf_loader->civicrm->google_map->fields_update( $post_id, $address );

		// If this address has no "Master Address" then it might be one itself.
		$addresses_shared = $this->acf_loader->civicrm->address->addresses_shared_get_by_id( $address->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Update all of them.
		foreach( $addresses_shared AS $address_shared ) {

			// We need a Contact ID in the shared Address.
			if ( empty( $address_shared->contact_id ) ) {
				return;
			}

			// Bail if this Contact doesn't have a User ID.
			$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address_shared->contact_id );
			if ( $user_id === false ) {
				continue;
			}

			// Format the ACF "Post ID".
			$post_id = 'user_' . $user_id;

			// Now update the Fields.
			$this->acf_loader->civicrm->google_map->fields_update( $post_id, $address_shared );

		}

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update City ACF Fields on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function city_edited( $args ) {

		// Extract Address.
		$address = $args['objectRef'];

		// Sanity check.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->address_city->fields_update( $post_id, $address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Address Record is about to be deleted.
	 *
	 * Before a Address Record is deleted, we need to retrieve the existing data.
	 *
	 * This is not required when creating or editing a Address Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function city_pre_delete( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->city_address_pre ) ) {
			unset( $this->city_address_pre );
		}

		// We just need the Address ID.
		$address_id = (int) $args['objectId'];

		// Grab the Address Record data from the database.
		$address_pre = $this->acf_loader->civicrm->address->address_get_by_id( $address_id );

		// Maybe cast previous Address Record data as object and stash in a property.
		if ( ! is_object( $address_pre ) ) {
			$this->city_address_pre = (object) $address_pre;
		} else {
			$this->city_address_pre = $address_pre;
		}

	}



	/**
	 * A CiviCRM Address Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function city_deleted( $args ) {

		// Bail if we don't have a pre-delete Address Record.
		if ( ! isset( $this->city_address_pre ) ) {
			return;
		}

		// We just need the Address ID.
		$address_id = (int) $args['objectId'];

		// Sanity check.
		if ( $address_id != $this->city_address_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Address Record.
		if ( empty( $this->city_address_pre->contact_id ) ) {
			return;
		}

		// Overwrite empty Address Record with full Record.
		$address = $this->city_address_pre;

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->address_city->fields_update( $post_id, $address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update City ACF Fields on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function state_edited( $args ) {

		// Extract Address.
		$address = $args['objectRef'];

		// Sanity check.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->address_state->fields_update( $post_id, $address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Address Record is about to be deleted.
	 *
	 * Before a Address Record is deleted, we need to retrieve the existing data.
	 *
	 * This is not required when creating or editing a Address Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function state_pre_delete( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->state_address_pre ) ) {
			unset( $this->state_address_pre );
		}

		// We just need the Address ID.
		$address_id = (int) $args['objectId'];

		// Grab the Address Record data from the database.
		$address_pre = $this->acf_loader->civicrm->address->address_get_by_id( $address_id );

		// Maybe cast previous Address Record data as object and stash in a property.
		if ( ! is_object( $address_pre ) ) {
			$this->state_address_pre = (object) $address_pre;
		} else {
			$this->state_address_pre = $address_pre;
		}

	}



	/**
	 * A CiviCRM Address Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function state_deleted( $args ) {

		// Bail if we don't have a pre-delete Address Record.
		if ( ! isset( $this->state_address_pre ) ) {
			return;
		}

		// We just need the Address ID.
		$address_id = (int) $args['objectId'];

		// Sanity check.
		if ( $address_id != $this->state_address_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Address Record.
		if ( empty( $this->state_address_pre->contact_id ) ) {
			return;
		}

		// Overwrite empty Address Record with full Record.
		$address = $this->state_address_pre;

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->address_state->fields_update( $post_id, $address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a Contact ID ACF Field on a User when a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_id_edited( $args ) {

		// Grab the Contact ID.
		$contact_id = $args['objectId'];

		// Sanity check.
		if ( empty( $contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->acf_loader->civicrm->contact_id->fields_update( $post_id, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Update ACF Fields when a set of CiviCRM Custom Fields has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function custom_edited( $args ) {

		/*
		$e = new \Exception;
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

	}



	// -------------------------------------------------------------------------



	/**
	 * Listen for queries from the Field Group class.
	 *
	 * This method responds with a Boolean if it detects that this Field Group
	 * maps to a WordPress User form.
	 *
	 * @since 0.4
	 *
	 * @param bool $mapped The existing mapping flag.
	 * @param array $field_group The array of ACF Field Group data.
	 * @param bool $mapped True if the Field Group is mapped, or pass through if not mapped.
	 */
	public function query_field_group_mapped( $mapped, $field_group ) {

		// Bail if a Mapping has already been found.
		if ( $mapped !== false ) {
			return $mapped;
		}

		// Bail if this is not a User Field Group.
		$is_user_field_group = $this->is_user_field_group( $field_group );
		if ( $is_user_field_group === false ) {
			return $mapped;
		}

		// --<
		return true;

	}



	/**
	 * Listen for queries from the Custom Field class.
	 *
	 * Users can potentially map to any kind of Contact Type and Sub-type. It is
	 * therefore necessary to collate all Custom Fields for every Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param array $custom_fields The existing Custom Fields.
	 * @param array $field_group The array of ACF Field Group data.
	 * @return array $custom_fields The populated array of CiviCRM Custom Fields params.
	 */
	public function query_custom_fields( $custom_fields, $field_group ) {

		// Bail if this is not a User Form Field Group.
		$is_visible = $this->is_user_field_group( $field_group );
		if ( $is_visible === false ) {
			return $custom_fields;
		}

		// Users can be mapped to any Contact Type.
		$contact_types = $this->acf_loader->civicrm->contact_type->types_get_nested();

		// Get the Custom Fields for each CiviCRM Contact Type.
		foreach( $contact_types AS $contact_type ) {

			// Top level types first.
			$type_name = $contact_type['name'];

			// Get the Custom Fields for this Contact Type.
			$custom_fields_for_type = $this->acf_loader->civicrm->custom_field->get_for_entity_type( $type_name, '' );

			// Merge with return array.
			$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			// Skip Sub-types if there aren't any.
			if ( empty( $contact_type['children'] ) ) {
				continue;
			}

			// Merge in children.
			foreach( $contact_type['children'] AS $contact_subtype ) {

				// Subtypes next.
				$subtype_name = $contact_subtype['name'];

				// Get the Custom Fields for this Contact Subtype.
				$custom_fields_for_type = $this->acf_loader->civicrm->custom_field->get_for_entity_type( $type_name, $subtype_name );

				// Merge with return array.
				$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			}

		}

		// --<
		return $custom_fields;

	}



	/**
	 * Listen for queries from the Contact Field class.
	 *
	 * Users can potentially map to any kind of Contact Type. The Contact Fields
	 * are attached to the top-level Contact Type so we only need to check those.
	 *
	 * @since 0.4
	 *
	 * @param array $contact_fields The existing Contact Fields.
	 * @param array $field_group The array of ACF Field Group data.
	 * @param array $field The ACF Field data array.
	 * @return array $contact_fields The populated array of CiviCRM Custom Fields params.
	 */
	public function query_contact_fields( $contact_fields, $field_group, $field ) {

		// Bail if this is not a User Form Field Group.
		$is_visible = $this->is_user_field_group( $field_group );
		if ( $is_visible === false ) {
			return $contact_fields;
		}

		// Users can be mapped to any Contact Type.
		$contact_types = $this->acf_loader->civicrm->contact_type->types_get_nested();

		// Get the Custom Fields for each CiviCRM Contact Type.
		foreach( $contact_types AS $contact_type ) {

			// Get public fields of this type.
			$contact_fields_for_type = $this->acf_loader->civicrm->contact_field->data_get( $contact_type['name'], $field['type'], 'public' );

			// Merge with return array.
			$contact_fields = array_merge( $contact_fields, $contact_fields_for_type );

		}

		// --<
		return $contact_fields;

	}



	/**
	 * Listen for queries from the Relationship class.
	 *
	 * Users can potentially map to any kind of Contact Type. The Relationships
	 * can refer to any Contact Type combination so we need to check all of them.
	 *
	 * @since 0.4
	 *
	 * @param array $relationships The existing Relationships.
	 * @param array $field_group The array of ACF Field Group data.
	 * @param array $field The ACF Field data array.
	 * @return array $relationships The populated array of CiviCRM Custom Fields params.
	 */
	public function query_relationship_fields( $relationships, $field_group, $field ) {

		// Bail if this is not a User Form Field Group.
		$is_visible = $this->is_user_field_group( $field_group );
		if ( $is_visible === false ) {
			return $relationships;
		}

		// Start from scratch.
		$relationships = [];

		// Get all Relationship Types.
		$relationship_types = $this->acf_loader->civicrm->relationship->types_get_all();

		// Get the Custom Fields for each CiviCRM Relationship Type.
		foreach( $relationship_types AS $relationship_type ) {

			// Define key.
			$key = $relationship_type['id'] . '_ab';

			// Add to subtype optgroup if possible.
			if ( ! empty( $relationship_type['contact_sub_type_a'] ) ) {
				$relationships[$relationship_type['contact_sub_type_a']][$key] = sprintf(
					__( '%s (A-B)', 'civicrm-wp-profile-sync' ),
					$relationship_type['label_a_b']
				);
			}

			// Add to type optgroup if not already added - and no subtype.
			if ( empty( $relationship_type['contact_sub_type_a'] ) ) {
				if ( ! isset( $relationships[$relationship_type['contact_type_a']][$key] ) ) {
					$relationships[$relationship_type['contact_type_a']][$key] = sprintf(
					__( '%s (A-B)', 'civicrm-wp-profile-sync' ),
					$relationship_type['label_a_b']
				);
				}
			}

			// Define key.
			$key = $relationship_type['id'] . '_ba';

			// Add to subtype optgroup if possible.
			if ( ! empty( $relationship_type['contact_sub_type_b'] ) ) {
				$relationships[$relationship_type['contact_sub_type_b']][$key] = sprintf(
					__( '%s (B-A)', 'civicrm-wp-profile-sync' ),
					$relationship_type['label_b_a']
				);
			}

			// Add to type optgroup if not already added - and no subtype.
			if ( empty( $relationship_type['contact_sub_type_b'] ) ) {
				if ( ! isset( $relationships[$relationship_type['contact_type_b']][$key] ) ) {
					$relationships[$relationship_type['contact_type_b']][$key] = sprintf(
					__( '%s (B-A)', 'civicrm-wp-profile-sync' ),
					$relationship_type['label_b_a']
				);
				}
			}

		}

		// --<
		return $relationships;

	}



	/**
	 * Listen for queries from the Custom Field class.
	 *
	 * This method responds with an array containing the User ID in ACF "user_N"
	 * format if it detects that the set of Custom Fields maps to a Contact.
	 *
	 * @since 0.4
	 *
	 * @param array|bool $post_ids The existing "Post IDs".
	 * @param array $args The array of CiviCRM Custom Fields params.
	 * @return array|bool $post_id The mapped User ID in ACF "user_N" format, or false if not mapped.
	 */
	public function query_post_id( $post_ids, $args ) {

		// Init Contact ID.
		$contact_id = false;

		// Let's tease out the context from the Custom Field data.
		foreach( $args['custom_fields'] AS $field ) {

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

		// Does this Contact have a User ID?
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $contact_id );

		// Bail if there isn't one.
		if ( $user_id === false ) {
			return $post_ids;
		}

		// Cast as an array in ACF User format.
		$user_ids = [ 'user_' . $user_id ];

		// Add found "Post IDs" to return array.
		if ( is_array( $post_ids ) ) {
			$post_ids = array_merge( $post_ids, $user_ids );
		} else {
			$post_ids = $user_ids;
		}

		// --<
		return $post_ids;

	}



	/**
	 * Query for the Contact ID that an ACF "Post ID" is mapped to.
	 *
	 * @since 0.4
	 *
	 * @param bool $contact_id False, since we're asking for a Contact ID.
	 * @param int|str $post_id The ACF "Post ID".
	 * @param str $entity The kind of WordPress Entity.
	 * @return int|bool $contact_id The mapped Contact ID, or false if not mapped.
	 */
	public function query_contact_id( $contact_id, $post_id, $entity ) {

		// Bail early if a Contact ID has been found.
		if ( $contact_id !== false ) {
			return $contact_id;
		}

		// Bail early if not a User Entity.
		if ( $entity !== 'user' ) {
			return $contact_id;
		}

		// Try and get Contact ID.
		$contact_id = $this->is_mapped_to_contact( $post_id );

		// --<
		return $contact_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Filter the Contact data.
	 *
	 * Check if a Contact is being trashed and has a WordPress User. If it does,
	 * then do not put the Contact in the trash since the User-Contact link
	 * remains active and valid.
	 *
	 * @since 0.4
	 *
	 * @param array $contact_data The existing CiviCRM Contact data.
	 * @param WP_Post $post The WordPress Post.
	 * @return array $contact_data The modified CiviCRM Contact data.
	 */
	public function contact_data( $contact_data, $post ) {

		// Skip if the Post Status isn't 'trash'.
		if ( $post->post_status !== 'trash' ) {
			return $contact_data;
		}

		// Skip if there's no Contact ID.
		if ( empty( $contact_data['id'] ) ) {
			return $contact_data;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $contact_data['id'] );
		if ( $user_id === false ) {
			return $contact_data;
		}

		// Override the status for the Contact.
		$contact_data['is_deleted'] = 0;

		// --<
		return $contact_data;

	}



	/**
	 * Check if a User is mapped to a Contact.
	 *
	 * @since 0.4
	 *
	 * @param int|str $post_id The ACF "Post ID".
	 * @return int|bool $is_mapped The ID of the CiviCRM Contact if the User is mapped, false otherwise.
	 */
	public function is_mapped_to_contact( $post_id ) {

		// Init as failure.
		$is_mapped = false;

		// Bail early if this "Post ID" refers to a Post Type.
		if ( is_numeric( $post_id ) ) {
			return $is_mapped;
		}

		// Bail early if this "Post ID" does not refer to a WordPress User.
		if ( false === strpos( $post_id, 'user_' ) ) {
			return $is_mapped;
		}

		// Get the WordPress User ID.
		$tmp = explode( '_', $post_id );
		$user_id = absint( $tmp[1] );

		// We need the User itself.
		$user = new WP_User( $user_id );

		// Bail if this is not a WordPress User.
		if ( ! $user->exists() ) {
			return $is_mapped;
		}

		// Does this User have a Contact?
		$contact_id = $this->plugin->mapper->ufmatch->contact_id_get_by_user_id( $user->ID );

		// Bail if there isn't one.
		if ( $contact_id === false ) {
			return $is_mapped;
		}

		// Make sure we return an integer.
		$is_mapped = absint( $contact_id );

		// --<
		return $is_mapped;

	}



	/**
	 * Check if this Field Group has been mapped to a WordPress User Form.
	 *
	 * @since 0.4
	 *
	 * @param array $field_group The Field Group to check.
	 * @return bool True if the Field Group has been mapped to a WordPress User Form, or false otherwise.
	 */
	public function is_user_field_group( $field_group ) {

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache[$field_group['ID']] ) ) {
			return $pseudocache[$field_group['ID']];
		}

		// Assume not visible.
		$is_visible = false;

		// Bail if no location rules exist.
		if ( ! empty( $field_group['location'] ) ) {

			// Define params to test for User Edit Form location.
			$params = [
				'user_form' => 'edit',
			];

			// Do the check.
			$is_visible = $this->acf_loader->acf->field_group->is_visible( $field_group, $params );

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$field_group['ID']] ) ) {
			$pseudocache[$field_group['ID']] = $is_visible;
		}

		// --<
		return $is_visible;

	}



} // Class ends.



