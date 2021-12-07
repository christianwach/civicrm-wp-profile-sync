<?php
/**
 * Mapper Core Hooks Class.
 *
 * All "core" callbacks are registered here - referring to the original purpose
 * of this plugin, namely to keep sync between the "core" WordPress User Profile
 * Fields and "core" CiviCRM Contact Fields:
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
 * CiviCRM Profile Sync Mapper Hooks Class.
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
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->plugin = $parent->mapper->plugin;
		$this->mapper = $parent->mapper;
		$this->hooks = $parent;

		// Initialise when parent is loaded.
		add_action( 'cwps/mapper/hooks/loaded', [ $this, 'initialise' ] );

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

		// Bail if already added.
		if ( has_action( 'user_register', [ $this, 'user_registered' ] ) ) {
			return;
		}

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

		// Bail if already added.
		if ( has_action( 'xprofile_updated_profile', [ $this, 'bp_xprofile_edited' ] ) ) {
			return;
		}

		// Callbacks for new and edited BuddyPress User actions.
		add_action( 'xprofile_updated_profile', [ $this, 'bp_xprofile_edited' ], 20, 5 );
		add_action( 'bp_core_signup_user', [ $this, 'bp_signup_user' ], 20, 5 );
		add_action( 'bp_core_activated_user', [ $this, 'bp_activated_user' ], 20, 3 );

		// Callback for edits to individual BuddyPress xProfile Fields.
		add_action( 'xprofile_data_after_save', [ $this, 'bp_field_edited' ], 20 );

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

		// Remove callback for edits to BuddyPress xProfile Fields.
		remove_action( 'xprofile_updated_profile', [ $this, 'bp_fields_edited' ], 20 );
		//remove_action( 'xprofile_data_after_save', [ $this, 'bp_field_edited' ], 20 );

	}



	/**
	 * Register CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_add() {

		// Bail if already added.
		if ( has_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ] ) ) {
			return;
		}

		// Register CiviCRM hooks by Entity.
		$this->hooks_civicrm_contact_add();
		$this->hooks_civicrm_email_add();
		$this->hooks_civicrm_website_add();
		$this->hooks_civicrm_phone_add();
		$this->hooks_civicrm_address_add();
		$this->hooks_civicrm_custom_add();

	}



	/**
	 * Register CiviCRM Contact hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_contact_add() {

		// Intercept Contact updates in CiviCRM.
		//add_action( 'civicrm_pre', [ $this, 'contact_pre_create' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ], 10, 4 );
		//add_action( 'civicrm_post', [ $this, 'contact_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'contact_edited' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Email hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_email_add() {

		// Intercept Email updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'email_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'email_edited' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Website hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_website_add() {

		// Intercept Website updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'website_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_edited' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Phone hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_phone_add() {

		// Intercept Phone updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'phone_pre_delete' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'phone_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'phone_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'phone_deleted' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Address hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_address_add() {

		// Intercept Address updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'address_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_edited' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'address_pre_delete' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_deleted' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Custom Table hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_custom_add() {

		// Intercept CiviCRM Custom Table updates.
		add_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10, 4 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Remove CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_remove() {

		// Remove CiviCRM hooks.
		$this->hooks_civicrm_contact_remove();
		$this->hooks_civicrm_email_remove();
		$this->hooks_civicrm_website_remove();
		$this->hooks_civicrm_phone_remove();
		$this->hooks_civicrm_address_remove();
		$this->hooks_civicrm_custom_remove();

	}



	/**
	 * Unregister CiviCRM Contact hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_contact_remove() {

		// Remove all CiviCRM Contact callbacks.
		//remove_action( 'civicrm_pre', [ $this, 'contact_pre_create' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ], 10 );
		//remove_action( 'civicrm_post', [ $this, 'contact_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'contact_edited' ], 10 );

	}



	/**
	 * Unregister CiviCRM Email hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_email_remove() {

		// Remove all CiviCRM Email callbacks.
		remove_action( 'civicrm_pre', [ $this, 'email_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'email_edited' ], 10 );

	}



	/**
	 * Unregister CiviCRM Website hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_website_remove() {

		// Remove all CiviCRM Website callbacks.
		remove_action( 'civicrm_pre', [ $this, 'website_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'website_edited' ], 10 );

	}



	/**
	 * Unregister CiviCRM Phone hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_phone_remove() {

		// Remove Phone update hooks.
		remove_action( 'civicrm_post', [ $this, 'phone_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'phone_edited' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'phone_pre_delete' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'phone_deleted' ], 10 );

	}



	/**
	 * Unregister CiviCRM Address hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_address_remove() {

		// Remove Address update hooks.
		remove_action( 'civicrm_pre', [ $this, 'address_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_edited' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'address_pre_delete' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_deleted' ], 10 );

	}



	/**
	 * Unregister CiviCRM Custom Table hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_custom_remove() {

		// Remove CiviCRM Custom Table hooks.
		remove_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10 );

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
		if ( is_multisite() && ms_is_switched() ) {
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
		if ( is_multisite() && ms_is_switched() ) {
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
	 * Fires when a BuddyPress "Profile Group" has been edited.
	 *
	 * A "Profile Group" means a group of xProfile Fields as displayed in the UI.
	 * The "xprofile_data_after_save" action fires for individual xProfile Fields
	 * which may be too granular for our needs.
	 *
	 * @since 0.4
	 *
	 * @param integer $user_id The ID for the User whose Profile is being saved.
	 * @param array $posted_field_ids The array of Field IDs that were edited.
	 * @param bool $errors Whether or not any errors occurred.
	 * @param array $old_values The array of original values before update.
	 * @param array $new_values The array of newly saved values after update.
	 */
	public function bp_xprofile_edited( $user_id, $posted_field_ids, $errors, $old_values, $new_values ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
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
			'old_values' => $old_values,
			'new_values' => $new_values,
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
	 * @param bool|WP_Error $user_id THe WordPress User ID or WP_Error on failure.
	 * @param string $user_login Login name requested by the user.
	 * @param string $user_password Password requested by the user.
	 * @param string $user_email Email address requested by the user.
	 * @param array $usermeta Metadata about the user (blog-specific signup data, xprofile data, etc).
	 */
	public function bp_signup_user( $user_id, $user_login, $user_password, $user_email, $usermeta ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
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
			'user_login' => $user_login,
			'user_password' => $user_password,
			'user_email' => $user_email,
			'usermeta' => $usermeta,
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
	 * @param string $key The Activation key.
	 * @param array $user The array of User data.
	 */
	public function bp_activated_user( $user_id, $key, $user ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
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
			'key' => $key,
			'user' => $user,
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



	/**
	 * Fires when the content of a BuddyPress xProfile Field has been edited.
	 *
	 * @since 0.5
	 *
	 * @param BP_XProfile_ProfileData $data The current instance of the xProfile data being saved.
	 */
	public function bp_field_edited( $data ) {

		// Remove other callbacks to prevent recursion.
		$this->hooks_civicrm_remove();
		$this->hooks_wordpress_remove();

		// Let's make an array of the params.
		$args = [
			'data' => $data,
		];

		/**
		 * Broadcast that a BuddyPress xProfile Field has been edited.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of BuddyPress params.
		 */
		do_action( 'cwps/mapper/bp_field_edited', $args );

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
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
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
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
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
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
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
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
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
		 * not exist in the data from the API (which has an "employer" Field
		 * whose value is the "Name" of the Employer instead) so we save the
		 * "extra" data here for use later.
		 */
		$extra_data = [
			'employer_id',
		];

		// Maybe save extra data.
		foreach ( $extra_data as $property ) {
			if ( isset( $objectRef->$property ) ) {
				$contact[ $property ] = $objectRef->$property;
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



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Phone is created.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function phone_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Phone.
		if ( $objectName != 'Phone' ) {
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
		 * Broadcast that a CiviCRM Phone has been created.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/phone/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Phone is updated.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function phone_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Phone.
		if ( $objectName != 'Phone' ) {
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
		 * Broadcast that a CiviCRM Phone has been updated.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/phone/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Phone is about to be deleted.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function phone_pre_delete( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Phone.
		if ( $objectName != 'Phone' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a CiviCRM Phone is about to be deleted.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/phone/delete/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Phone has been deleted.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function phone_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Phone.
		if ( $objectName != 'Phone' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a CiviCRM Phone has been deleted.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/phone/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Address is about to be edited.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a CiviCRM Address is about to be updated.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/address/edit/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been created.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a CiviCRM Address has been created.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/address/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been edited.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a CiviCRM Address has been updated.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/address/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Address is about to be deleted.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_pre_delete( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a CiviCRM Address is about to be deleted.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/address/delete/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been deleted.
	 *
	 * @since 0.5
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function address_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $objectName != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		/**
		 * Broadcast that a CiviCRM Address has been deleted.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/mapper/address/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept Custom Field updates.
	 *
	 * @since 0.4
	 *
	 * @param string $op The kind of operation.
	 * @param integer $groupID The numeric ID of the Custom Group.
	 * @param integer $entityID The numeric ID of the Contact.
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



