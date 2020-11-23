<?php
/**
 * Mapper Core Hooks Class.
 *
 * All "core" callbacks are registered here - referring to the original purpose
 * of this plugin, namely to keep sync between the "core" WordPress User Profile
 * fields and "core" CiviCRM Contact fields:
 *
 * - "First Name"
 * - "Last Name"
 * - "Email Address"
 * - "Website"
 * - "Nickname"
 *
 * The data (particularly the data coming from the CiviCRM callbacks) is first
 * cast to standardised formats then merged into an array and then re-broadcast
 * via custom actions.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM WordPress Profile Sync Mapper Hooks Class.
 *
 * A class that encapsulates hook events functionality.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_Mapper_Hooks_Core {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * Mapper (parent) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $mapper The Mapper object.
	 */
	public $mapper;

	/**
	 * Mapper Hooks object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $hooks The Mapper object.
	 */
	public $hooks;



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $plugin The plugin object.
	 */
	public function __construct() {

		// Initialise when parent is loaded.
		add_action( 'cwps/mapper/hooks/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Set references to other objects.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function set_references( $parent ) {

		// Store references.
		$this->plugin = $parent->mapper->plugin;
		$this->mapper = $parent->mapper;
		$this->hooks = $parent;

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

		// Register WordPress hooks.
		$this->hooks_wordpress_add();

		// Register BuddyPress hooks.
		$this->hooks_buddypress_add();

		// Register CiviCRM hooks.
		$this->hooks_civicrm_add();

	}



	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Unregister WordPress hooks.
		$this->hooks_wordpress_remove();

		// Unregister BuddyPress hooks.
		$this->hooks_buddypress_remove();

		// Unregister CiviCRM hooks.
		$this->hooks_civicrm_remove();

	}



	// -------------------------------------------------------------------------



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_add() {

		// Callbacks for new and edited WordPress User actions.
		add_action( 'user_register', [ $this, 'user_registered' ], 9, 1 );
		add_action( 'profile_update', [ $this, 'user_edited' ], 9, 2 );

	}



	/**
	 * Remove WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_remove() {

		// Remove callbacks for new and edited WordPress User actions.
		remove_action( 'user_register', [ $this, 'user_registered' ], 9 );
		remove_action( 'profile_update', [ $this, 'user_edited' ], 9 );

	}



	/**
	 * Register BuddyPress hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_buddypress_add() {

		// Callbacks for new and edited BuddyPress User actions.
		add_action( 'xprofile_updated_profile', [ $this, 'bp_xprofile_edited' ], 20, 3 );
		add_action( 'bp_core_signup_user', [ $this, 'bp_signup_user' ], 20, 3 );
		add_action( 'bp_core_activated_user', [ $this, 'bp_activated_user' ], 20, 3 );

	}



	/**
	 * Remove BuddyPress hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_buddypress_remove() {

		// Remove callbacks for new and edited BuddyPress User actions.
		remove_action( 'xprofile_updated_profile', [ $this, 'bp_xprofile_edited' ], 20 );
		remove_action( 'bp_core_signup_user', [ $this, 'bp_signup_user' ], 20 );
		remove_action( 'bp_core_activated_user', [ $this, 'bp_activated_user' ], 20 );

	}



	/**
	 * Register CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_add() {

		// Intercept Contact updates in CiviCRM.
		//add_action( 'civicrm_pre', [ $this, 'contact_pre_create' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ], 10, 4 );
		//add_action( 'civicrm_post', [ $this, 'contact_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'contact_edited' ], 10, 4 );

		// Intercept Email updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'email_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'email_edited' ], 10, 4 );

		// Intercept Website updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'website_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_edited' ], 10, 4 );

		// Intercept CiviCRM Custom Table updates.
		//add_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10, 4 );

	}



	/**
	 * Remove CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_remove() {

		// Remove all CiviCRM Contact callbacks.
		//remove_action( 'civicrm_pre', [ $this, 'contact_pre_create' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ], 10 );
		//remove_action( 'civicrm_post', [ $this, 'contact_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'contact_edited' ], 10 );

		// Remove all CiviCRM Email callbacks.
		remove_action( 'civicrm_pre', [ $this, 'email_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'email_edited' ], 10 );

		// Remove all CiviCRM Website callbacks.
		remove_action( 'civicrm_pre', [ $this, 'website_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'website_edited' ], 10 );

		// Remove CiviCRM Custom Table hooks.
		//remove_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires when a WordPress User is newly registered.
	 *
	 * @since 0.4
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 */
	public function user_registered( $user_id ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_civicrm_remove();
		$this->hooks_buddypress_remove();

		// Let's make an array of the params.
		$args = [
			'user_id' => $user_id,
		];

		/**
		 * Broadcast that a WordPress User has been registered.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/mapper/user_registered', $args );

		// Reinstate other callbacks.
		$this->hooks_civicrm_add();
		$this->hooks_buddypress_add();

	}



	/**
	 * Fires when a WordPress User has been edited.
	 *
	 * @since 0.4
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @param WP_User $old_user_data Object containing user's data prior to update.
	 */
	public function user_edited( $user_id, $old_user_data ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_civicrm_remove();
		$this->hooks_buddypress_remove();

		// Let's make an array of the params.
		$args = [
			'user_id' => $user_id,
			'old_user_data' => $old_user_data,
		];

		/**
		 * Broadcast that a WordPress User has been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/mapper/user_edited', $args );

		// Reinstate other callbacks.
		$this->hooks_civicrm_add();
		$this->hooks_buddypress_add();

	}



	/**
	 * Fires when a BuddyPress Profile has been edited.
	 *
	 * @since 0.4
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @param array $posted_field_ids The array of numeric IDs of the BuddyPress fields.
	 * @param boolean $errors True if there are errors, false otherwise.
	 */
	public function bp_xprofile_edited( $user_id = 0, $posted_field_ids, $errors ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( ! bp_disable_profile_sync() ) {
			return;
		}

		// Fetch logged-in User if none set.
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Bail if no User ID.
		if ( empty( $user_id ) ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_civicrm_remove();
		$this->hooks_wordpress_remove();

		// Let's make an array of the params.
		$args = [
			'user_id' => $user_id,
			'posted_field_ids' => $posted_field_ids,
			'errors' => $errors,
		];

		/**
		 * Broadcast that a BuddyPress Profile has been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of BuddyPress params.
		 */
		do_action( 'cwps/mapper/bp_xprofile_edited', $args );

		// Reinstate other callbacks.
		$this->hooks_civicrm_add();
		$this->hooks_wordpress_add();

	}



	/**
	 * Fires when a BuddyPress User has been signed up.
	 *
	 * @since 0.4
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @param array $posted_field_ids The array of numeric IDs of the BuddyPress fields.
	 * @param boolean $errors True if there are errors, false otherwise.
	 */
	public function bp_signup_user( $user_id = 0, $posted_field_ids, $errors ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( ! bp_disable_profile_sync() ) {
			return;
		}

		// Fetch logged-in User if none set.
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Bail if no User ID.
		if ( empty( $user_id ) ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_civicrm_remove();
		$this->hooks_wordpress_remove();

		// Let's make an array of the params.
		$args = [
			'user_id' => $user_id,
			'posted_field_ids' => $posted_field_ids,
			'errors' => $errors,
		];

		/**
		 * Broadcast that a BuddyPress User has been signed up.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of BuddyPress params.
		 */
		do_action( 'cwps/mapper/bp_signup_user', $args );

		// Reinstate other callbacks.
		$this->hooks_civicrm_add();
		$this->hooks_wordpress_add();

	}



	/**
	 * Fires when a BuddyPress User has been activated.
	 *
	 * @since 0.4
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @param array $posted_field_ids The array of numeric IDs of the BuddyPress fields.
	 * @param boolean $errors True if there are errors, false otherwise.
	 */
	public function bp_activated_user( $user_id = 0, $posted_field_ids, $errors ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( ! bp_disable_profile_sync() ) {
			return;
		}

		// Fetch logged-in User if none set.
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Bail if no User ID.
		if ( empty( $user_id ) ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_civicrm_remove();
		$this->hooks_wordpress_remove();

		// Let's make an array of the params.
		$args = [
			'user_id' => $user_id,
			'posted_field_ids' => $posted_field_ids,
			'errors' => $errors,
		];

		/**
		 * Broadcast that a BuddyPress User has been activated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of BuddyPress params.
		 */
		do_action( 'cwps/mapper/bp_activated_user', $args );

		// Reinstate other callbacks.
		$this->hooks_civicrm_add();
		$this->hooks_wordpress_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires just before a CiviCRM Contact is created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_pre_create( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_wordpress_remove();
		$this->hooks_buddypress_remove();

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a Contact is about to be created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/contact_pre_create', $args );

		// Reinstate other callbacks.
		$this->hooks_wordpress_add();
		$this->hooks_buddypress_add();

	}



	/**
	 * Fires just before a CiviCRM Contact is edited.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_wordpress_remove();
		$this->hooks_buddypress_remove();

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a Contact is about to be edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/contact_pre_edit', $args );

		// Reinstate other callbacks.
		$this->hooks_wordpress_add();
		$this->hooks_buddypress_add();

	}



	/**
	 * Fires just after a CiviCRM Contact has been created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_created( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_wordpress_remove();
		$this->hooks_buddypress_remove();

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a Contact has been created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/contact_created', $args );

		// Reinstate other callbacks.
		$this->hooks_wordpress_add();
		$this->hooks_buddypress_add();

	}



	/**
	 * Fires just after a CiviCRM Contact has been edited.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_edited( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
			return;
		}

		// Get the full Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $objectId );

		// Bail if something went wrong.
		if ( $contact === false ) {
			return;
		}

		// Remove other callbacks to prevent recursion.
		$this->hooks_wordpress_remove();
		$this->hooks_buddypress_remove();

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		/*
		 * There are mismatches between the Contact data that is passed in to
		 * this callback and the Contact data that is retrieved by the API -
		 * particularly the "employer_id" which may exist in this data but does
		 * not exist in the data from the API (which has an "employer" field
		 * whose value is the "Name" of the Employer instead) so we save the
		 * "extra" data here for use later.
		 */
		$extra_data = [
			'employer_id',
		];

		// Maybe save extra data.
		foreach( $extra_data AS $property ) {
			if ( isset( $objectRef->$property ) ) {
				$contact[$property] = $objectRef->$property;
			}
		}

		// Overwrite objectRef with full Contact data.
		$args['objectRef'] = (object) $contact;

		/**
		 * Broadcast that a Contact has been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/contact_edited', $args );

		// Reinstate other callbacks.
		$this->hooks_wordpress_add();
		$this->hooks_buddypress_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires just before a CiviCRM Contact's Email address is edited.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function email_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Email' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a Contact's Email is about to be edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/email_pre_edit', $args );

	}



	/**
	 * Fires just after a CiviCRM Contact's Email address has been edited.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function email_edited( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Email' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a Contact's Email is about to be edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/email_edited', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires just before a CiviCRM Website is edited.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function website_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Website' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a Website is about to be edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/website_pre_edit', $args );

	}



	/**
	 * Fires just after a CiviCRM Website is edited.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function website_edited( $op, $objectName, $objectId, $objectRef ) {

		// Target our object type.
		if ( $objectName != 'Website' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a Website is about to be edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/website_edited', $args );

	}



	/**
	 * Intercept Custom Field updates.
	 *
	 * @since 0.4
	 *
	 * @param str $op The kind of operation.
	 * @param int $groupID The numeric ID of the Custom Group.
	 * @param int $entityID The numeric ID of the Contact.
	 * @param array $custom_fields The array of Custom Fields.
	 */
	public function custom_edited( $op, $groupID, $entityID, &$custom_fields ) {

		// Bail if there's nothing to see here.
		if ( empty( $custom_fields ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'groupID' => $groupID,
			'entityID' => $entityID,
			'custom_fields' => $custom_fields,
		];

		/**
		 * Broadcast that a set of CiviCRM Custom Fields has been updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/custom_edited', $args );

	}



} // Class ends.



