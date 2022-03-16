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
	 * CiviCRM Utilities object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civicrm;

	/**
	 * WordPress Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_wp_hooks The WordPress Mapper hooks registered flag.
	 */
	public $mapper_wp_hooks = false;

	/**
	 * CiviCRM Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_civicrm_hooks The CiviCRM Mapper hooks registered flag.
	 */
	public $mapper_civicrm_hooks = false;

	/**
	 * Supported Location Rule names.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $rule_names The supported Location Rule names.
	 */
	public $rule_names = [
		'user_form',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store references.
		$this->plugin = $acf_loader->plugin;
		$this->acf_loader = $acf_loader;

		// Init when this plugin is loaded.
		add_action( 'cwps/acf/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Store CiviCRM reference late.
		$this->civicrm = $this->acf_loader->civicrm;

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

		// Listen for queries from the Attachment class.
		add_filter( 'cwps/acf/query_entity_table', [ $this, 'query_entity_table' ], 10, 2 );

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/acf/field/query_setting_choices', [ $this, 'query_setting_choices' ], 50, 3 );

		// Listen for queries from the ACF Field Group class.
		add_filter( 'cwps/acf/field_group/query_supported_rules', [ $this, 'query_supported_rules' ], 10, 4 );

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
		remove_filter( 'cwps/acf/query_entity_table', [ $this, 'query_entity_table' ], 10 );
		remove_filter( 'cwps/acf/query_contact_id', [ $this, 'query_contact_id' ], 10 );

	}



	/**
	 * Register callbacks for CiviCRM Entities that have their own hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_wp_hooks() {

		// Bail if already registered.
		if ( $this->mapper_wp_hooks === true ) {
			return;
		}

		// Listen for events from our Mapper that require CiviCRM updates.
		add_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ], 10 );

		// Declare registered.
		$this->mapper_wp_hooks = true;

	}



	/**
	 * Unregister callbacks for CiviCRM Entities that have their own hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_wp_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_wp_hooks === false ) {
			return;
		}

		// Listen for events from our Mapper that require CiviCRM updates.
		remove_action( 'cwps/acf/mapper/acf_fields/saved', [ $this, 'acf_fields_saved' ], 10 );

		// Declare unregistered.
		$this->mapper_wp_hooks = false;

	}



	/**
	 * Register callbacks for CiviCRM Entities that have their own hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_civicrm_hooks() {

		// Bail if already registered.
		if ( $this->mapper_civicrm_hooks === true ) {
			return;
		}

		// Listen for events from our Mapper that require WordPress updates.
		add_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );

		// Listen for events from our Mapper that CiviCRM Custom Fields have been edited.
		add_action( 'cwps/acf/mapper/civicrm/custom/edited', [ $this, 'custom_edited' ], 10 );

		// Act on CiviCRM Entities being edited.
		add_action( 'cwps/acf/mapper/email/created', [ $this, 'email_edited' ], 10 );
		add_action( 'cwps/acf/mapper/email/edited', [ $this, 'email_edited' ], 10 );
		add_action( 'cwps/acf/mapper/email/deleted', [ $this, 'email_edited' ], 10 );

		add_action( 'cwps/acf/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		add_action( 'cwps/acf/mapper/website/delete/pre', [ $this, 'website_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/website/created', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/acf/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		//add_action( 'cwps/acf/mapper/website/deleted', [ $this, 'website_deleted' ], 10 );

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

		// Declare registered.
		$this->mapper_civicrm_hooks = true;

	}



	/**
	 * Unregister callbacks for CiviCRM Entities that have their own hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_civicrm_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_civicrm_hooks === false ) {
			return;
		}

		// Stop listening for events from our Mapper that require WordPress updates.
		remove_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 10 );

		// Stop listening for events from our Mapper that CiviCRM Custom Fields have been edited.
		remove_action( 'cwps/acf/mapper/civicrm/custom/edited', [ $this, 'custom_edited' ], 10 );

		// Remove callbacks for CiviCRM Entities.
		remove_action( 'cwps/acf/mapper/email/created', [ $this, 'email_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/email/edited', [ $this, 'email_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/email/deleted', [ $this, 'email_edited' ], 10 );

		remove_action( 'cwps/acf/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		remove_action( 'cwps/acf/mapper/website/delete/pre', [ $this, 'website_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/website/created', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		//remove_action( 'cwps/acf/mapper/website/deleted', [ $this, 'website_deleted' ], 10 );

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

		// Declare unregistered.
		$this->mapper_civicrm_hooks = false;

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
		 * Get existing Field values.
		 *
		 * These are actually the *new* values because we are hooking in *after*
		 * the Fields have been saved.
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
		$this->civicrm->contact->update_from_fields( $args['contact_id'], $fields, $args['post_id'] );

		/**
		 * Broadcast that a Contact has been edited when ACF Fields were saved.
		 *
		 * Used to inform e.g.:
		 *
		 * * Contact Fields
		 * * Relationships
		 * * Addresses
		 * * Websites
		 * * Instant Messengers
		 * * Phones
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
	 * This callback receives data via the ACF Mapper.
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
		$this->civicrm->email->fields_update( $post_id, $email );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * A CiviCRM Contact's Website is about to be edited.
	 *
	 * Before a Website is edited, we need to store the previous data so that
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

		// Always clear properties if set previously.
		if ( isset( $this->website_pre ) ) {
			unset( $this->website_pre );
		}

		// Grab the previous Website data from the database.
		$this->website_pre = $this->civicrm->website->website_get_by_id( $website->id );

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

		// Check previous to see if its Website Type has changed.
		$website_type_changed = false;
		if ( ! empty( $this->website_pre ) ) {
			if ( (int) $this->website_pre->website_type_id !== (int) $website->website_type_id ) {
				$website_type_changed = true;
				// Make a clone so we don't overwrite the Website Pre object.
				$previous = clone $this->website_pre;
				$previous->url = '';
			}
		}

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Maybe clear the previous Field.
		if ( $website_type_changed ) {
			// Skip previous if it has already been changed.
			if ( empty( $this->previously_edited[ $previous->website_type_id ] ) ) {
				$this->civicrm->website->fields_update( $post_id, $previous );
			}
		}

		// Run the routine, but with a User reference.
		$this->civicrm->website->fields_update( $post_id, $website );

		// Keep a clone of the Website object for future reference.
		$this->previously_edited[ $website->website_type_id ] = clone $website;

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * Intercept when a CiviCRM Website is about to be deleted.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_delete( $args ) {

		// Get the full existing Website data.
		$website = (object) $this->plugin->civicrm->website->get_by_id( $args['objectId'] );
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $website->contact_id );
		if ( $user_id === false ) {
			return;
		}

		// Skip deleting if it has already been written to.
		if ( ! empty( $this->previously_edited[ $website->website_type_id ] ) ) {
			return;
		}

		// Save a copy of the URL just in case.
		$website->deleted_url = $website->url;

		// Clear URL to clear the ACF Field.
		$website->url = '';

		// Format the ACF "Post ID".
		$post_id = 'user_' . $user_id;

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->civicrm->website->fields_update( $post_id, $website );

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
		$acf_phone = $this->civicrm->phone->prepare_from_civicrm( $phone );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->civicrm->phone->fields_update( $post_id, $phone, $acf_phone, $args );

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
		$phone_pre = $this->plugin->civicrm->phone->phone_get_by_id( $phone_id );

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
		$acf_phone = $this->civicrm->phone->prepare_from_civicrm( $phone );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->civicrm->phone->fields_update( $post_id, $phone, $acf_phone, $args );

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
		$acf_im = $this->civicrm->im->prepare_from_civicrm( $im );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->civicrm->im->fields_update( $post_id, $im, $acf_im, $args );

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
		$im_pre = $this->civicrm->im->im_get_by_id( $im_id );

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
		$acf_im = $this->civicrm->im->prepare_from_civicrm( $im );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->civicrm->im->fields_update( $post_id, $im, $acf_im, $args );

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
	 * @param integer $contact_id The numeric ID of the Contact.
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
		$this->civicrm->relationship->fields_update( $post_id, $relationship, $op );

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
		$acf_address = $this->civicrm->addresses->prepare_from_civicrm( $address );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->civicrm->addresses->fields_update( $post_id, $address, $acf_address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Address Record is about to be deleted.
	 *
	 * Before an Address Record is deleted, we need to remove the corresponding
	 * element in the ACF Field data.
	 *
	 * This is not required when creating or editing an Address Record.
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
		$address_pre = $this->plugin->civicrm->address->address_get_by_id( $address_id );

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
		$acf_address = $this->civicrm->addresses->prepare_from_civicrm( $address );

		// Unregister WordPress hooks.
		$this->unregister_mapper_wp_hooks();

		// Run the routine, but with a User reference.
		$this->civicrm->addresses->fields_update( $post_id, $address, $acf_address, $args );

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
		$this->map_address_pre = $this->plugin->civicrm->address->address_get_by_id( $address->id );

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
		$this->civicrm->google_map->fields_update( $post_id, $address );

		// If this address has no "Master Address" then it might be one itself.
		$addresses_shared = $this->plugin->civicrm->address->addresses_shared_get_by_id( $address->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Update all of them.
		foreach ( $addresses_shared as $address_shared ) {

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
			$this->civicrm->google_map->fields_update( $post_id, $address_shared );

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
		$address = $this->civicrm->google_map->address_properties_check( $address, $this->map_address_pre );

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
		$this->civicrm->google_map->fields_update( $post_id, $address, $this->map_address_pre );

		// If this address has no "Master Address" then it might be one itself.
		$addresses_shared = $this->plugin->civicrm->address->addresses_shared_get_by_id( $address->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Update all of them.
		foreach ( $addresses_shared as $address_shared ) {

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
			$this->civicrm->google_map->fields_update( $post_id, $address_shared );

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
		$this->civicrm->google_map->fields_update( $post_id, $address );

		// If this address has no "Master Address" then it might be one itself.
		$addresses_shared = $this->plugin->civicrm->address->addresses_shared_get_by_id( $address->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Update all of them.
		foreach ( $addresses_shared as $address_shared ) {

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
			$this->civicrm->google_map->fields_update( $post_id, $address_shared );

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
		$this->civicrm->address_city->fields_update( $post_id, $address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Address Record is about to be deleted.
	 *
	 * Before an Address Record is deleted, we need to retrieve the existing data.
	 *
	 * This is not required when creating or editing an Address Record.
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
		$address_pre = $this->plugin->civicrm->address->address_get_by_id( $address_id );

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
		$this->civicrm->address_city->fields_update( $post_id, $address, $args );

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
		$this->civicrm->address_state->fields_update( $post_id, $address, $args );

		// Re-register WordPress hooks.
		$this->register_mapper_wp_hooks();

	}



	/**
	 * A CiviCRM Contact's Address Record is about to be deleted.
	 *
	 * Before an Address Record is deleted, we need to retrieve the existing data.
	 *
	 * This is not required when creating or editing an Address Record.
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
		$address_pre = $this->plugin->civicrm->address->address_get_by_id( $address_id );

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
		$this->civicrm->address_state->fields_update( $post_id, $address, $args );

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
		$this->civicrm->contact_id->fields_update( $post_id, $args );

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
	 * @return bool $mapped True if the Field Group is mapped, or pass through if not mapped.
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

		// Bail if this is not a User Form Field Group.
		$is_visible = $this->is_user_field_group( $field_group );
		if ( $is_visible === false ) {
			return $choices;
		}

		// Init Contact Fields array.
		$contact_fields = [];

		// Users can be mapped to any Contact Type.
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();

		// Get the Contact Fields for each CiviCRM Contact Type.
		foreach ( $contact_types as $contact_type ) {

			// Get public Fields of this type.
			$contact_fields_for_type = $this->civicrm->contact_field->data_get( $contact_type['name'], $field['type'], 'public' );

			// Merge with return array.
			$contact_fields = array_merge( $contact_fields, $contact_fields_for_type );

		}

		// Init Custom Fields array.
		$custom_fields = [];

		// Get the Custom Fields for each CiviCRM Contact Type.
		foreach ( $contact_types as $contact_type ) {

			// Top level types first.
			$type_name = $contact_type['name'];

			// Get the Custom Fields for this Contact Type.
			$custom_fields_for_type = $this->plugin->civicrm->custom_field->get_for_entity_type( $type_name, '' );

			// Merge with return array.
			$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			// Skip Sub-types if there aren't any.
			if ( empty( $contact_type['children'] ) ) {
				continue;
			}

			// Merge in children.
			foreach ( $contact_type['children'] as $contact_subtype ) {

				// Subtypes next.
				$subtype_name = $contact_subtype['name'];

				// Get the Custom Fields for this Contact Subtype.
				$custom_fields_for_type = $this->plugin->civicrm->custom_field->get_for_entity_type( $type_name, $subtype_name );

				// Merge with return array.
				$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			}

		}

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
		if ( empty( $contact_fields ) && empty( $filtered_fields ) ) {
			return $choices;
		}

		// Build Contact Field choices array for dropdown.
		if ( ! empty( $contact_fields ) ) {
			$contact_fields_label = esc_attr__( 'Contact Fields', 'civicrm-wp-profile-sync' );
			foreach ( $contact_fields as $contact_field ) {
				$choices[ $contact_fields_label ][ $this->civicrm->contact_field_prefix() . $contact_field['name'] ] = $contact_field['title'];
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
		$choices = apply_filters( 'cwps/acf/user/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

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
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();

		// Get the Custom Fields for each CiviCRM Contact Type.
		foreach ( $contact_types as $contact_type ) {

			// Top level types first.
			$type_name = $contact_type['name'];

			// Get the Custom Fields for this Contact Type.
			$custom_fields_for_type = $this->plugin->civicrm->custom_field->get_for_entity_type( $type_name, '' );

			// Merge with return array.
			$custom_fields = array_merge( $custom_fields, $custom_fields_for_type );

			// Skip Sub-types if there aren't any.
			if ( empty( $contact_type['children'] ) ) {
				continue;
			}

			// Merge in children.
			foreach ( $contact_type['children'] as $contact_subtype ) {

				// Subtypes next.
				$subtype_name = $contact_subtype['name'];

				// Get the Custom Fields for this Contact Subtype.
				$custom_fields_for_type = $this->plugin->civicrm->custom_field->get_for_entity_type( $type_name, $subtype_name );

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
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();

		// Get the Custom Fields for each CiviCRM Contact Type.
		foreach ( $contact_types as $contact_type ) {

			// Get public Fields of this type.
			$contact_fields_for_type = $this->civicrm->contact_field->data_get( $contact_type['name'], $field['type'], 'public' );

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
		$relationship_types = $this->civicrm->relationship->types_get_all();

		// Get the Custom Fields for each CiviCRM Relationship Type.
		foreach ( $relationship_types as $relationship_type ) {

			// Define key.
			$key = $relationship_type['id'] . '_ab';

			// Add to subtype optgroup if possible.
			if ( ! empty( $relationship_type['contact_sub_type_a'] ) ) {
				$relationships[ $relationship_type['contact_sub_type_a'] ][ $key ] = sprintf(
					/* translators: %s: The Relationship label */
					__( '%s (A-B)', 'civicrm-wp-profile-sync' ),
					$relationship_type['label_a_b']
				);
			}

			// Add to type optgroup if not already added - and no subtype.
			if ( empty( $relationship_type['contact_sub_type_a'] ) ) {
				if ( ! isset( $relationships[ $relationship_type['contact_type_a'] ][ $key ] ) ) {
					$relationships[ $relationship_type['contact_type_a'] ][ $key ] = sprintf(
						/* translators: %s: The Relationship label */
						__( '%s (A-B)', 'civicrm-wp-profile-sync' ),
						$relationship_type['label_a_b']
					);
				}
			}

			// Define key.
			$key = $relationship_type['id'] . '_ba';

			// Add to subtype optgroup if possible.
			if ( ! empty( $relationship_type['contact_sub_type_b'] ) ) {
				$relationships[ $relationship_type['contact_sub_type_b'] ][ $key ] = sprintf(
					/* translators: %s: The Relationship label */
					__( '%s (B-A)', 'civicrm-wp-profile-sync' ),
					$relationship_type['label_b_a']
				);
			}

			// Add to type optgroup if not already added - and no subtype.
			if ( empty( $relationship_type['contact_sub_type_b'] ) ) {
				if ( ! isset( $relationships[ $relationship_type['contact_type_b'] ][ $key ] ) ) {
					$relationships[ $relationship_type['contact_type_b'] ][ $key ] = sprintf(
						/* translators: %s: The Relationship label */
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
		foreach ( $args['custom_fields'] as $field ) {

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
	 * Listen for queries from the Attachment class.
	 *
	 * This method responds with an "Entity Table" if it detects that the ACF
	 * Field Group maps to a User.
	 *
	 * @since 0.5.2
	 *
	 * @param array $entity_tables The existing "Entity Tables".
	 * @param array $field_group The array of ACF Field Group params.
	 * @return array $entity_tables The mapped "Entity Tables".
	 */
	public function query_entity_table( $entity_tables, $field_group ) {

		// Bail if this is not a User Field Group.
		$is_visible = $this->is_user_field_group( $field_group );
		if ( $is_visible === false ) {
			return $entity_tables;
		}

		// Append our "Entity Table" if not already present.
		if ( ! array_key_exists( 'civicrm_contact', $entity_tables ) ) {
			$entity_tables['civicrm_contact'] = __( 'Contact', 'civicrm-wp-profile-sync' );
		}

		// --<
		return $entity_tables;

	}



	/**
	 * Query for the Contact ID that an ACF "Post ID" is mapped to.
	 *
	 * @since 0.4
	 *
	 * @param bool $contact_id False, since we're asking for a Contact ID.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param string $entity The kind of WordPress Entity.
	 * @return integer|bool $contact_id The mapped Contact ID, or false if not mapped.
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



	/**
	 * Listen for queries for supported Location Rules.
	 *
	 * @since 0.5
	 *
	 * @param bool $supported The existing supported Location Rules status.
	 * @param array $rule The Location Rule.
	 * @param array $params The query params array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return bool $supported The modified supported Location Rules status.
	 */
	public function query_supported_rules( $supported, $rule, $params, $field_group ) {

		// Bail if already supported.
		if ( $supported === true ) {
			return $supported;
		}

		// Test for this Location Rule.
		if ( ! in_array( $rule['param'], $this->rule_names ) ) {
			return $supported;
		}

		// Loop through our rule names to see if the query contains one.
		foreach ( $this->rule_names as $rule_name ) {
			if ( ! empty( $params[ $rule_name ] ) ) {
				$supported = true;
				break;
			}
		}

		// --<
		return $supported;

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
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return integer|bool $is_mapped The ID of the CiviCRM Contact if the User is mapped, false otherwise.
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

		// Bail if there's no Field Group ID.
		if ( empty( $field_group['ID'] ) ) {
			return false;
		}

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache[ $field_group['ID'] ] ) ) {
			return $pseudocache[ $field_group['ID'] ];
		}

		// Assume not visible.
		$is_visible = false;

		// Bail if no Location Rules exist.
		if ( ! empty( $field_group['location'] ) ) {

			// Define params to test for User Edit Form location.
			$params = [
				'user_form' => 'edit',
			];

			// Do the check.
			$is_visible = $this->acf_loader->acf->field_group->is_visible( $field_group, $params );

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $field_group['ID'] ] ) ) {
			$pseudocache[ $field_group['ID'] ] = $is_visible;
		}

		// --<
		return $is_visible;

	}



} // Class ends.



