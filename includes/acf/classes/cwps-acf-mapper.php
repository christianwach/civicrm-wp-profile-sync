<?php
/**
 * Mapper Class.
 *
 * Keeps a WordPress Entity synced with a CiviCRM Entity via ACF Fields.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Mapper Class.
 *
 * A class that encapsulates methods to keep a WordPress Entity synced with a
 * CiviCRM Entity via ACF Fields.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Mapper {

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Define date format mappings (CiviCRM to ACF).
	 *
	 * @since 0.4
	 * @access public
	 * @var array $date_mappings The CiviCRM to ACF date format mappings.
	 */
	public $date_mappings = [
		'mm/dd/yy' => 'm/d/Y',
		'dd/mm/yy' => 'd/m/Y',
		'yy-mm-dd' => 'Y-m-d',
		'dd-mm-yy' => 'd-m-Y',
		'dd.mm.yy' => 'd.m.Y',
		'M d, yy' => 'M d, Y',
		'd M yy' => 'j M Y',
		'MM d, yy' => 'F j, Y',
		'd MM yy' => 'd F Y',
		'DD, d MM yy' => 'l, d F Y',
		'mm/dd' => 'm/d',
		'dd-mm' => 'd-m',
		'M yy' => 'm Y',
		'M Y' => 'm Y',
		'yy' => 'Y',
	];

	/**
	 * Define time format mappings (CiviCRM to ACF).
	 *
	 * @since 0.4
	 * @access public
	 * @var array $time_mappings The CiviCRM to ACF time format mappings.
	 */
	public $time_mappings = [
		'1' => 'g:i a',
		'2' => 'H:i',
	];

	/**
	 * Entity being edited that originally triggered the callbacks.
	 *
	 * This could be one of the "top level" Entities, e.g.
	 *
	 * - WordPress User
	 * - WordPress Post
	 * - CiviCRM Contact
	 * - CiviCRM Activity
	 *
	 * Knowing this helps us determine the messaging flow.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $entity The Entity that originally triggered the callbacks.
	 */
	public $entity = [
		'entity' => false,
		'id' => false,
	];



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

		// Register CiviCRM hooks.
		$this->hooks_civicrm_add();

	}



	// -------------------------------------------------------------------------



	/**
	 * Store the Entity being edited that originally triggered the callbacks.
	 *
	 * @since 0.4
	 *
	 * @param string $entity The name of the Entity.
	 * @param integer $id The numeric ID of the Entity.
	 * @param string $type For WordPress Entities, this is the Post Type.
	 */
	public function entity_set( $entity, $id, $type = '' ) {

		// Bail if it has already been set.
		if ( $this->entity['entity'] !== false ) {
			return;
		}

		// Set it.
		$this->entity['entity'] = $entity;
		$this->entity['id'] = $id;
		$this->entity['type'] = $type;

	}



	/**
	 * Get the Entity being edited that originally triggered the callbacks.
	 *
	 * @since 0.4
	 *
	 * @return array $entity An array containing the name and ID of the Entity.
	 */
	public function entity_get() {

		// --<
		return $this->entity;

	}



	// -------------------------------------------------------------------------



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_add() {

		// Intercept WordPress Post updates.
		$this->hooks_wordpress_post_add();

		// Intercept ACF Field updates.
		$this->hooks_wordpress_acf_add();

		// Intercept Taxonomy updates.
		$this->hooks_wordpress_tax_add();

	}



	/**
	 * Register WordPress Post hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_post_add() {

		// Intercept Post update in WordPress super-early.
		add_action( 'save_post', [ $this, 'post_saved' ], 1, 3 );

	}



	/**
	 * Register WordPress ACF Field hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_acf_add() {

		// Intercept ACF fields prior to save.
		//add_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 5, 1 );

		// Intercept ACF fields after save.
		add_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 20, 1 );

	}



	/**
	 * Register WordPress Taxonomy hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_tax_add() {

		// Intercept new term creation.
		add_action( 'created_term', [ $this, 'term_created' ], 20, 3 );

		// Intercept term updates.
		add_action( 'edit_terms', [ $this, 'term_pre_edit' ], 20, 2 );
		add_action( 'edited_term', [ $this, 'term_edited' ], 20, 3 );

		// Intercept term deletion.
		add_action( 'delete_term', [ $this, 'term_deleted' ], 20, 4 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Remove WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_remove() {

		// Remove Post update hooks.
		$this->hooks_wordpress_post_remove();

		// Remove ACF Field hooks.
		$this->hooks_wordpress_acf_remove();

		// Remove Taxonomy callbacks.
		$this->hooks_wordpress_tax_remove();

	}



	/**
	 * Unregister WordPress Post hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_post_remove() {

		// Remove Post update hook.
		remove_action( 'save_post', [ $this, 'post_saved' ], 1 );

	}



	/**
	 * Unregister WordPress ACF Field hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_acf_remove() {

		// Remove ACF fields update hook.
		//remove_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 5 );

		// Remove ACF fields update hook.
		remove_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 20 );

	}



	/**
	 * Unregister WordPress Taxonomy hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_tax_remove() {

		// Remove all term-related callbacks.
		remove_action( 'created_term', [ $this, 'intercept_create_term' ], 20 );
		remove_action( 'edit_terms', [ $this, 'intercept_pre_update_term' ], 20 );
		remove_action( 'edited_term', [ $this, 'intercept_update_term' ], 20 );
		remove_action( 'delete_term', [ $this, 'intercept_delete_term' ], 20 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Register CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_add() {

		// Intercept Contact updates in CiviCRM.
		$this->hooks_civicrm_contact_add();

		// Intercept Activity updates in CiviCRM.
		$this->hooks_civicrm_activity_add();

		// Intercept Email updates in CiviCRM.
		$this->hooks_civicrm_email_add();

		// Intercept Website updates in CiviCRM.
		$this->hooks_civicrm_website_add();

		// Intercept Phone updates in CiviCRM.
		$this->hooks_civicrm_phone_add();

		// Intercept Instant Messenger updates in CiviCRM.
		$this->hooks_civicrm_im_add();

		// Intercept Relationship updates in CiviCRM.
		$this->hooks_civicrm_relationship_add();

		// Intercept Address updates in CiviCRM.
		$this->hooks_civicrm_address_add();

		// Intercept CiviCRM Custom Table updates.
		$this->hooks_civicrm_custom_add();

		// Intercept Group updates in CiviCRM.
		$this->hooks_civicrm_group_add();

		// Intercept Group Membership updates in CiviCRM.
		$this->hooks_civicrm_group_contact_add();

	}



	/**
	 * Register CiviCRM Contact hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_contact_add() {

		// Intercept Contact updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'contact_pre_create' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'contact_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'contact_edited' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Activity hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_activity_add() {

		// Intercept Activity updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'activity_pre_create' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'activity_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'activity_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'activity_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'activity_deleted' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Email hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_email_add() {

		// Intercept Email updates in CiviCRM.
		add_action( 'civicrm_post', [ $this, 'email_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'email_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'email_deleted' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Website hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_website_add() {

		// Intercept Website updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'website_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_edited' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Phone hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_phone_add() {

		// Intercept Phone updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'phone_pre_delete' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'phone_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'phone_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'phone_deleted' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Instant Messenger hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_im_add() {

		// Intercept Instant Messenger updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'im_pre_delete' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'im_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'im_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'im_deleted' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Relationship hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_relationship_add() {

		// Intercept Relationship updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'relationship_pre_edit' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'relationship_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'relationship_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'relationship_deleted' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Address hooks.
	 *
	 * @since 0.4
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
	 * @since 0.4
	 */
	public function hooks_civicrm_custom_add() {

		// Intercept CiviCRM Custom Table updates.
		add_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Group hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_group_add() {

		// Intercept Group updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'group_deleted_pre' ], 10, 4 );

	}



	/**
	 * Register CiviCRM Group Contact hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_group_contact_add() {

		// Intercept Group Membership updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'group_contacts_created' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'group_contacts_deleted' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'group_contacts_rejoined' ], 10, 4 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Remove CiviCRM hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_remove() {

		// Remove Contact update hooks.
		$this->hooks_civicrm_contact_remove();

		// Remove Activity update hooks.
		$this->hooks_civicrm_activity_remove();

		// Remove Email update hooks.
		$this->hooks_civicrm_email_remove();

		// Remove Website update hooks.
		$this->hooks_civicrm_website_remove();

		// Remove Phone update hooks.
		$this->hooks_civicrm_phone_remove();

		// Remove Instant Messenger update hooks.
		$this->hooks_civicrm_im_remove();

		// Remove Relationship update hooks.
		$this->hooks_civicrm_relationship_remove();

		// Remove Address update hooks.
		$this->hooks_civicrm_address_remove();

		// Remove CiviCRM Custom Table hooks.
		$this->hooks_civicrm_custom_remove();

		// Remove Group update hooks.
		$this->hooks_civicrm_group_remove();

		// Remove Group Membership update hooks.
		$this->hooks_civicrm_group_contact_remove();

	}



	/**
	 * Unregister CiviCRM Contact hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_contact_remove() {

		// Remove Contact update hooks.
		remove_action( 'civicrm_pre', [ $this, 'contact_pre_create' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'contact_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'contact_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'contact_edited' ], 10 );

	}



	/**
	 * Unregister CiviCRM Activity hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_activity_remove() {

		// Remove Activity update hooks.
		remove_action( 'civicrm_pre', [ $this, 'activity_pre_create' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'activity_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'activity_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'activity_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'activity_deleted' ], 10 );

	}



	/**
	 * Unregister CiviCRM Email hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_email_remove() {

		// Remove Email update hooks.
		remove_action( 'civicrm_post', [ $this, 'email_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'email_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'email_deleted' ], 10 );

	}



	/**
	 * Unregister CiviCRM Website hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_website_remove() {

		// Remove Website update hooks.
		remove_action( 'civicrm_pre', [ $this, 'website_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'website_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'website_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'website_deleted' ], 10 );

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
	 * Unregister CiviCRM Instant Messenger hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_im_remove() {

		// Remove Instant Messenger update hooks.
		remove_action( 'civicrm_post', [ $this, 'im_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'im_edited' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'im_pre_delete' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'im_deleted' ], 10 );

	}



	/**
	 * Unregister CiviCRM Relationship hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_relationship_remove() {

		// Remove Relationship update hooks.
		remove_action( 'civicrm_pre', [ $this, 'relationship_pre_edit' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'relationship_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'relationship_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'relationship_deleted' ], 10 );

	}



	/**
	 * Unregister CiviCRM Address hooks.
	 *
	 * @since 0.4
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
	 * @since 0.4
	 */
	public function hooks_civicrm_custom_remove() {

		// Remove CiviCRM Custom Table hooks.
		remove_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10 );

	}



	/**
	 * Unregister CiviCRM Group hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_group_remove() {

		// Remove Group update hooks.
		remove_action( 'civicrm_pre', [ $this, 'group_deleted_pre' ], 10 );

	}



	/**
	 * Unregister CiviCRM Group Contact hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_group_contact_remove() {

		// Remove Group Membership update hooks.
		remove_action( 'civicrm_pre', [ $this, 'group_contacts_created' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'group_contacts_deleted' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'group_contacts_rejoined' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires just before a CiviCRM Entity is created.
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
		$top_level_types = $this->acf_loader->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
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
		 * Broadcast that a relevant Contact is about to be created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/contact/create/pre', $args );

	}



	/**
	 * Fires just before a CiviCRM Entity is updated.
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
		$top_level_types = $this->acf_loader->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op' => $op,
			'objectName' => $objectName,
			'objectId' => $objectId,
			'objectRef' => $objectRef,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $objectRef ) ? $objectRef : (object) $objectRef;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'contact', $objectId );

		/**
		 * Broadcast that a relevant Contact is about to be updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/contact/edit/pre', $args );

	}



	/**
	 * Create a WordPress Post when a CiviCRM Contact is created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if it's not the "create" operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if it's not a Contact object.
		if ( ! ( $objectRef instanceof CRM_Contact_DAO_Contact ) ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->acf_loader->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
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

		// Maybe set this as the originating Entity.
		$this->entity_set( 'contact', $objectId );

		/**
		 * Broadcast that a relevant Contact has been created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/contact/created', $args );

	}



	/**
	 * Update a WordPress Post when a CiviCRM Contact is updated.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function contact_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if it's not an "edit" operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if it's not a Contact.
		if ( ! ( $objectRef instanceof CRM_Contact_DAO_Contact ) ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->acf_loader->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $objectName, $top_level_types ) ) {
			return;
		}

		// Get the full Contact data.
		$contact = $this->acf_loader->civicrm->contact->get_by_id( $objectId );

		// Bail if something went wrong.
		if ( $contact === false ) {
			return;
		}

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

		// Maybe set this as the originating Entity.
		$this->entity_set( 'contact', $objectId );

		/**
		 * Broadcast that a relevant Contact has been updated.
		 *
		 * Used internally to:
		 *
		 * - Update a WordPress Post
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/contact/edited', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Email is created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function email_created( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Email.
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
		 * Broadcast that a CiviCRM Email has been created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/email/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Email is updated.
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

		// Bail if this is not an Email.
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
		 * Broadcast that a CiviCRM Email has been updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/email/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Email is deleted.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function email_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Email.
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
		 * Broadcast that a CiviCRM Email has been deleted.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/email/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Website is about to be edited.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function website_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Website.
		if ( $objectName != 'Website' ) {
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
		 * Broadcast that a CiviCRM Website is about to be updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/website/edit/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Website is created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function website_created( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Website.
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
		 * Broadcast that a CiviCRM Website has been created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/website/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Website is updated.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function website_edited( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Website.
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
		 * Broadcast that a CiviCRM Website has been updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/website/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Website is deleted.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function website_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Website.
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
		 * Broadcast that a CiviCRM Website has been deleted.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/website/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Phone is created.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/phone/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Phone is updated.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/phone/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Phone is about to be deleted.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/phone/delete/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Phone has been deleted.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/phone/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Instant Messenger is created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function im_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Instant Messenger.
		if ( $objectName != 'IM' ) {
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
		 * Broadcast that a CiviCRM Instant Messenger has been created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/im/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Instant Messenger is updated.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function im_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Instant Messenger.
		if ( $objectName != 'IM' ) {
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
		 * Broadcast that a CiviCRM Instant Messenger has been updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/im/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Instant Messenger is about to be deleted.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function im_pre_delete( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Instant Messenger.
		if ( $objectName != 'IM' ) {
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
		 * Broadcast that a CiviCRM Instant Messenger is about to be deleted.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/im/delete/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Instant Messenger has been deleted.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function im_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Instant Messenger.
		if ( $objectName != 'IM' ) {
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
		 * Broadcast that a CiviCRM Instant Messenger has been deleted.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/im/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Relationship is about to be edited.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function relationship_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Relationship.
		if ( $objectName != 'Relationship' ) {
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
		 * Broadcast that a CiviCRM Relationship is about to be updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/relationship/edit/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Relationship has been created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function relationship_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Relationship.
		if ( $objectName != 'Relationship' ) {
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
		 * Broadcast that a CiviCRM Relationship has been created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/relationship/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Relationship has been updated.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function relationship_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Relationship.
		if ( $objectName != 'Relationship' ) {
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
		 * Broadcast that a CiviCRM Contact's Relationship has been updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/relationship/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Relationship has been deleted.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function relationship_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Relationship.
		if ( $objectName != 'Relationship' ) {
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
		 * Broadcast that a CiviCRM Relationship has been deleted.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/relationship/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Address is about to be edited.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/address/edit/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been created.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/address/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been edited.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/address/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Address is about to be deleted.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/address/delete/pre', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact's Address has been deleted.
	 *
	 * @since 0.4
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
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/address/deleted', $args );

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
		 * Used internally to:
		 *
		 * - Update a WordPress Post
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/civicrm/custom/edited', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept a CiviCRM group prior to it being deleted.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the CiviCRM Group.
	 * @param array $objectRef The array of CiviCRM Group data.
	 */
	public function group_deleted_pre( $op, $objectName, $objectId, &$objectRef ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'Group' ) {
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
		 * Broadcast that a CiviCRM Group is about to be deleted.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/group/delete/pre', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Contact is added to a Group.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the CiviCRM Group.
	 * @param array $objectRef The array of CiviCRM Contact IDs.
	 */
	public function group_contacts_created( $op, $objectName, $objectId, &$objectRef ) {

		// Target our operation.
		if ( $op != 'create' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'GroupContact' ) {
			return;
		}

		// Bail if there are no Contacts.
		if ( empty( $objectRef ) ) {
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
		 * Broadcast that Contacts have been added to a CiviCRM Group.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/group/contacts/created', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact is deleted (or removed) from a Group.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the CiviCRM Group.
	 * @param array $objectRef Array of CiviCRM Contact IDs.
	 */
	public function group_contacts_deleted( $op, $objectName, $objectId, &$objectRef ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'GroupContact' ) {
			return;
		}

		// Bail if there are no Contacts.
		if ( empty( $objectRef ) ) {
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
		 * Broadcast that Contacts have been deleted from a CiviCRM Group.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/group/contacts/deleted', $args );

	}



	/**
	 * Intercept when a CiviCRM Contact is re-added to a Group.
	 *
	 * The issue here is that CiviCRM fires 'civicrm_pre' with $op = 'delete' regardless
	 * of whether the Contact is being removed or deleted. If a Contact is later re-added
	 * to the Group, then $op != 'create', so we need to intercept $op = 'edit'.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the CiviCRM Group.
	 * @param array $objectRef Array of CiviCRM Contact IDs.
	 */
	public function group_contacts_rejoined( $op, $objectName, $objectId, &$objectRef ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $objectName != 'GroupContact' ) {
			return;
		}

		// Bail if there are no Contacts.
		if ( empty( $objectRef ) ) {
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
		 * Broadcast that Contacts have rejoined a CiviCRM Group.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/group/contacts/rejoined', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Fires just before a CiviCRM Activity is created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function activity_pre_create( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $objectName != 'Activity' ) {
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
		 * Broadcast that a relevant Activity is about to be created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/activity/create/pre', $args );

	}



	/**
	 * Fires just before a CiviCRM Entity is updated.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function activity_pre_edit( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $objectName != 'Activity' ) {
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
		 * Broadcast that a relevant Activity is about to be updated.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/activity/edit/pre', $args );

	}



	/**
	 * Create a WordPress Post when a CiviCRM Activity is created.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function activity_created( $op, $objectName, $objectId, $objectRef ) {

		// Bail if it's not the "create" operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $objectName != 'Activity' ) {
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

		// Maybe set this as the originating Entity.
		$this->entity_set( 'activity', $objectId );

		/**
		 * Broadcast that a relevant Activity has been created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/activity/created', $args );

	}



	/**
	 * Update a WordPress Post when a CiviCRM Activity is updated.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function activity_edited( $op, $objectName, $objectId, $objectRef ) {

		// Bail if it's not an "edit" operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $objectName != 'Activity' ) {
			return;
		}

		// Bail if it's not an Activity.
		if ( ! ( $objectRef instanceof CRM_Activity_DAO_Activity ) ) {
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

		// Maybe set this as the originating Entity.
		$this->entity_set( 'activity', $objectId );

		/**
		 * Broadcast that a relevant Activity has been updated.
		 *
		 * Used internally to:
		 *
		 * - Update a WordPress Post
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/activity/edited', $args );

	}



	/**
	 * Intercept when a CiviCRM Activity has been deleted.
	 *
	 * @since 0.4
	 *
	 * @param string $op The type of database operation.
	 * @param string $objectName The type of object.
	 * @param integer $objectId The ID of the object.
	 * @param object $objectRef The object.
	 */
	public function activity_deleted( $op, $objectName, $objectId, $objectRef ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $objectName != 'Activity' ) {
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

		// Maybe set this as the originating Entity.
		$this->entity_set( 'activity', $objectId );

		/**
		 * Broadcast that a CiviCRM Activity has been deleted.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/activity/deleted', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept the Post saved operation.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The ID of the Post or revision.
	 * @param integer $post The Post object.
	 * @param boolean $update True if the Post is being updated, false if new.
	 */
	public function post_saved( $post_id, $post, $update ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'post_id' => $post_id,
			'post' => $post,
			'update' => $update,
		];

		// Maybe set this as the originating Entity.
		if ( $this->acf_loader->post->should_be_synced( $post ) ) {
			$this->entity_set( 'post', $post_id, $post->post_type );
		}

		/**
		 * Broadcast that a WordPress Post has been saved.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/post/saved', $args );

	}



	/**
	 * Intercept the ACF Fields saved operation.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The ID of the Post or revision.
	 */
	public function acf_fields_saved( $post_id ) {

		// TODO: Do we need to specify is_admin()?

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'post_id' => $post_id,
		];

		/**
		 * Broadcast that ACF Fields have been saved for a Post.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/acf_fields/saved', $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Hook into the creation of a term.
	 *
	 * @since 0.4
	 *
	 * @param array $term_id The numeric ID of the new term.
	 * @param array $tt_id The numeric ID of the new term.
	 * @param string $taxonomy Should be (an array containing) taxonomy names.
	 */
	public function term_created( $term_id, $tt_id, $taxonomy ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id' => $term_id,
			'tt_id' => $tt_id,
			'taxonomy' => $taxonomy,
		];

		/**
		 * Broadcast that a WordPress Term has been created.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/mapper/term/created', $args );

	}



	/**
	 * Hook into updates to a term before the term is updated.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the new term.
	 * @param string $taxonomy The taxonomy containing the term.
	 */
	public function term_pre_edit( $term_id, $taxonomy = null ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id' => $term_id,
			'taxonomy' => $taxonomy,
		];

		/**
		 * Broadcast that a WordPress Term is about to be edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/mapper/term/edit/pre', $args );

	}



	/**
	 * Hook into updates to a term.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the edited term.
	 * @param array $tt_id The numeric ID of the edited term taxonomy.
	 * @param string $taxonomy Should be (an array containing) the taxonomy.
	 */
	public function term_edited( $term_id, $tt_id, $taxonomy ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id' => $term_id,
			'tt_id' => $tt_id,
			'taxonomy' => $taxonomy,
		];

		/**
		 * Broadcast that a WordPress Term has been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/mapper/term/edited', $args );

	}



	/**
	 * Hook into deletion of a term.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the deleted term.
	 * @param array $tt_id The numeric ID of the deleted term taxonomy.
	 * @param string $taxonomy Name of the taxonomy.
	 * @param object $deleted_term The deleted term object.
	 */
	public function term_deleted( $term_id, $tt_id, $taxonomy, $deleted_term ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() AND ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id' => $term_id,
			'tt_id' => $tt_id,
			'taxonomy' => $taxonomy,
			'deleted_term' => $deleted_term,
		];

		/**
		 * Broadcast that a WordPress Term has been deleted.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/mapper/term/deleted', $args );

	}



} // Class ends.



