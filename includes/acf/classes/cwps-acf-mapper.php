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
	 * CiviCRM available flag.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var bool
	 */
	public $civicrm_available = false;

	/**
	 * CiviCRM listeners registered flag.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var bool
	 */
	public $civicrm_listeners = false;

	/**
	 * Define date format mappings (CiviCRM to ACF).
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $date_mappings = [
		'mm/dd/yy'    => 'm/d/Y',
		'dd/mm/yy'    => 'd/m/Y',
		'yy-mm-dd'    => 'Y-m-d',
		'dd-mm-yy'    => 'd-m-Y',
		'dd.mm.yy'    => 'd.m.Y',
		'M d, yy'     => 'M d, Y',
		'd M yy'      => 'j M Y',
		'MM d, yy'    => 'F j, Y',
		'd MM yy'     => 'd F Y',
		'DD, d MM yy' => 'l, d F Y',
		'mm/dd'       => 'm/d',
		'dd-mm'       => 'd-m',
		'M yy'        => 'm Y',
		'M Y'         => 'm Y',
		'yy'          => 'Y',
	];

	/**
	 * Define time format mappings (CiviCRM to ACF).
	 *
	 * @since 0.4
	 * @access public
	 * @var array
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
	 * * WordPress User
	 * * WordPress Post
	 * * CiviCRM Contact
	 * * CiviCRM Activity
	 * * CiviCRM Participant
	 *
	 * Knowing this helps us determine the messaging flow.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $entity = [
		'entity' => false,
		'id'     => false,
	];

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store references to objects.
		$this->plugin     = $acf_loader->plugin;
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

		// Add CiviCRM listeners once CiviCRM is available.
		add_action( 'civicrm_config', [ $this, 'civicrm_available' ], 10, 1 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Store the Entity being edited that originally triggered the callbacks.
	 *
	 * @since 0.4
	 *
	 * @param string  $entity The name of the Entity.
	 * @param integer $id The numeric ID of the Entity.
	 * @param string  $type For WordPress Entities, this is the Post Type.
	 */
	public function entity_set( $entity, $id, $type = '' ) {

		// Bail if it has already been set.
		if ( $this->entity['entity'] !== false ) {
			return;
		}

		// Set it.
		$this->entity['entity'] = $entity;
		$this->entity['id']     = $id;
		$this->entity['type']   = $type;

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

		// Intercept ACF Fields prior to save.
		add_action( 'acf/save_post', [ $this, 'acf_fields_saved_pre' ], 5, 1 );

		// Intercept ACF Fields after save.
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
		add_action( 'pre_delete_term', [ $this, 'term_pre_delete' ], 20, 2 );
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

		// Remove ACF Fields callbacks.
		remove_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 5 );
		remove_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 20 );

	}

	/**
	 * Unregister WordPress Taxonomy hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_wordpress_tax_remove() {

		// Remove all term-related callbacks.
		remove_action( 'created_term', [ $this, 'term_created' ], 20 );
		remove_action( 'edit_terms', [ $this, 'term_pre_edit' ], 20 );
		remove_action( 'edited_term', [ $this, 'term_edited' ], 20 );
		remove_action( 'pre_delete_term', [ $this, 'term_pre_delete' ], 20 );
		remove_action( 'delete_term', [ $this, 'term_deleted' ], 20 );

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

		// Intercept Participant updates in CiviCRM.
		$this->hooks_civicrm_participant_add();

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

		// Intercept File updates in CiviCRM.
		$this->hooks_civicrm_file_add();

		// Add CiviCRM listeners.
		$this->listeners_civicrm_add();

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
	 * Register CiviCRM Participant hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_participant_add() {

		// Intercept Participant updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'participant_pre_create' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'participant_pre_edit' ], 10, 4 );
		add_action( 'civicrm_pre', [ $this, 'participant_pre_delete' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'participant_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'participant_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'participant_deleted' ], 10, 4 );

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
		add_action( 'civicrm_pre', [ $this, 'website_pre_delete' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'website_deleted' ], 10, 4 );

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
		add_action( 'civicrm_pre', [ $this, 'address_pre_delete' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_edited' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'address_deleted' ], 10, 4 );

	}

	/**
	 * Register CiviCRM Custom Table hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_custom_add() {

		// Intercept CiviCRM Custom Table updates.
		add_action( 'civicrm_customPre', [ $this, 'custom_pre_edit' ], 10, 4 );
		add_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10, 4 );

	}

	/**
	 * Register CiviCRM Group hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_group_add() {

		// Intercept Group updates in CiviCRM.
		add_action( 'civicrm_pre', [ $this, 'group_pre_delete' ], 10, 4 );

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

	/**
	 * Register CiviCRM File hooks.
	 *
	 * @since 0.5.4
	 */
	public function hooks_civicrm_file_add() {

		// Intercept File updates in CiviCRM.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_action( 'civicrm_pre', [ $this, 'file_pre_delete' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'file_created' ], 10, 4 );
		add_action( 'civicrm_post', [ $this, 'file_edited' ], 10, 4 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_action( 'civicrm_post', [ $this, 'file_deleted' ], 10, 4 );

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

		// Remove Participant update hooks.
		$this->hooks_civicrm_participant_remove();

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

		// Remove File update hooks.
		$this->hooks_civicrm_file_remove();

		// Remove CiviCRM listeners.
		$this->listeners_civicrm_remove();

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
	 * Unregister CiviCRM Participant hooks.
	 *
	 * @since 0.5
	 */
	public function hooks_civicrm_participant_remove() {

		// Remove Participant update hooks.
		remove_action( 'civicrm_pre', [ $this, 'participant_pre_create' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'participant_pre_edit' ], 10 );
		remove_action( 'civicrm_pre', [ $this, 'participant_pre_delete' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'participant_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'participant_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'participant_deleted' ], 10 );

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
		remove_action( 'civicrm_pre', [ $this, 'website_pre_delete' ], 10 );
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
		remove_action( 'civicrm_pre', [ $this, 'phone_pre_delete' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'phone_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'phone_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'phone_deleted' ], 10 );

	}

	/**
	 * Unregister CiviCRM Instant Messenger hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_im_remove() {

		// Remove Instant Messenger update hooks.
		remove_action( 'civicrm_pre', [ $this, 'im_pre_delete' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'im_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'im_edited' ], 10 );
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
		remove_action( 'civicrm_pre', [ $this, 'address_pre_delete' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_edited' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'address_deleted' ], 10 );

	}

	/**
	 * Unregister CiviCRM Custom Table hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_custom_remove() {

		// Remove CiviCRM Custom Table hooks.
		remove_action( 'civicrm_customPre', [ $this, 'custom_pre_edit' ], 10 );
		remove_action( 'civicrm_custom', [ $this, 'custom_edited' ], 10 );

	}

	/**
	 * Unregister CiviCRM Group hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_civicrm_group_remove() {

		// Remove Group update hooks.
		remove_action( 'civicrm_pre', [ $this, 'group_pre_delete' ], 10 );

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

	/**
	 * Unregister CiviCRM File hooks.
	 *
	 * @since 0.5.4
	 */
	public function hooks_civicrm_file_remove() {

		// Remove Instant Messenger update hooks.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// remove_action( 'civicrm_pre', [ $this, 'file_pre_delete' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'file_created' ], 10 );
		remove_action( 'civicrm_post', [ $this, 'file_edited' ], 10 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// remove_action( 'civicrm_post', [ $this, 'file_deleted' ], 10 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Declare CiviCRM available and register listeners.
	 *
	 * @since 0.5.4
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function civicrm_available( &$config ) {

		// Declare available.
		$this->civicrm_available = true;

		// CiviCRM listeners can now be registered.
		$this->listeners_civicrm_add();

	}

	/**
	 * Add CiviCRM listeners.
	 *
	 * These listeners mean that this plugin requires CiviCRM 5.26+.
	 *
	 * @see https://lab.civicrm.org/dev/core/issues/1638
	 *
	 * @since 0.5.4
	 */
	public function listeners_civicrm_add() {

		// Bail if CiviCRM unavailable.
		if ( $this->civicrm_available === false ) {
			return;
		}

		// Bail if already registered.
		if ( $this->civicrm_listeners === true ) {
			return;
		}

		/*
		// Add callback for CiviCRM "preInsert" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preInsert',
			[ $this, 'listener_civicrm_pre_create' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "postInsert" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.postInsert',
			[ $this, 'listener_civicrm_created' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "preUpdate" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preUpdate',
			[ $this, 'listener_civicrm_pre_edit' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "postUpdate" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.postUpdate',
			[ $this, 'listener_civicrm_edited' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "preDelete" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preDelete',
			[ $this, 'listener_civicrm_pre_delete' ],
			-100 // Default priority.
		);

		// Add callback for CiviCRM "postDelete" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.postDelete',
			[ $this, 'listener_civicrm_deleted' ],
			-100 // Default priority.
		);
		*/

		// Add callback for CiviCRM "preDelete" hook.
		Civi::service( 'dispatcher' )->addListener(
			'civi.dao.preDelete',
			[ $this, 'file_pre_delete_listener' ],
			-100 // Default priority.
		);

		// Declare registered.
		$this->civicrm_listeners = true;

	}

	/**
	 * Remove CiviCRM listeners.
	 *
	 * @since 0.5.4
	 */
	public function listeners_civicrm_remove() {

		// Bail if CiviCRM unavailable.
		if ( $this->civicrm_available === false ) {
			return;
		}

		// Bail if already unregistered.
		if ( $this->civicrm_listeners === false ) {
			return;
		}

		/*
		// Add callback for CiviCRM "preInsert" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.preInsert',
			[ $this, 'listener_civicrm_pre_create' ]
		);

		// Add callback for CiviCRM "postInsert" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.postInsert',
			[ $this, 'listener_civicrm_created' ]
		);

		// Add callback for CiviCRM "preUpdate" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.preUpdate',
			[ $this, 'listener_civicrm_pre_edit' ]
		);

		// Add callback for CiviCRM "postUpdate" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.postUpdate',
			[ $this, 'listener_civicrm_edited' ]
		);

		// Remove callback for CiviCRM "preDelete" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.preDelete',
			[ $this, 'listener_civicrm_pre_delete' ]
		);

		// Add callback for CiviCRM "postDelete" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.postDelete',
			[ $this, 'listener_civicrm_deleted' ]
		);
		*/

		// Remove callback for CiviCRM "preDelete" hook.
		Civi::service( 'dispatcher' )->removeListener(
			'civi.dao.preDelete',
			[ $this, 'file_pre_delete_listener' ]
		);

		// Declare unregistered.
		$this->civicrm_listeners = false;

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.preInsert' hook.
	 *
	 * @since 0.5.4
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function listener_civicrm_pre_create( $event, $hook ) {

		// Grab CiviCRM object for this hook.
		$object =& $event->object;

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'event' => $event,
			'hook' => $hook,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.postInsert' hook.
	 *
	 * @since 0.5.4
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function listener_civicrm_created( $event, $hook ) {

		// Grab CiviCRM object for this hook.
		$object =& $event->object;

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'event' => $event,
			'hook' => $hook,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.preInsert' hook.
	 *
	 * @since 0.5.4
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function listener_civicrm_pre_edit( $event, $hook ) {

		// Grab CiviCRM object for this hook.
		$object =& $event->object;

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'event' => $event,
			'hook' => $hook,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.postUpdate' hook.
	 *
	 * @since 0.5.4
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function listener_civicrm_edited( $event, $hook ) {

		// Grab CiviCRM object for this hook.
		$object =& $event->object;

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'event' => $event,
			'hook' => $hook,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.preDelete' hook.
	 *
	 * @since 0.5.4
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function listener_civicrm_pre_delete( $event, $hook ) {

		// Grab CiviCRM object for this hook.
		$object =& $event->object;

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'event' => $event,
			'hook' => $hook,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	/**
	 * Callback for the CiviCRM 'civi.dao.postDelete' hook.
	 *
	 * @since 0.5.4
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function listener_civicrm_deleted( $event, $hook ) {

		// Grab CiviCRM object for this hook.
		$object =& $event->object;

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'event' => $event,
			'hook' => $hook,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	// -------------------------------------------------------------------------

	/**
	 * Fires just before a CiviCRM Entity is created.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function contact_pre_create( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $object_name, $top_level_types, true ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function contact_pre_edit( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $object_name, $top_level_types, true ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
			'objectRef'  => $object_ref,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'contact', $object_id );

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function contact_created( $op, $object_name, $object_id, $object_ref ) {

		// Bail if it's not the "create" operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if it's not a Contact object.
		if ( ! ( $object_ref instanceof CRM_Contact_DAO_Contact ) ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $object_name, $top_level_types, true ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'contact', $object_id );

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function contact_edited( $op, $object_name, $object_id, $object_ref ) {

		// Bail if it's not an "edit" operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if it's not a Contact.
		if ( ! ( $object_ref instanceof CRM_Contact_DAO_Contact ) ) {
			return;
		}

		// Bail if this is not a Contact.
		$top_level_types = $this->plugin->civicrm->contact_type->types_get_top_level();
		if ( ! in_array( $object_name, $top_level_types, true ) ) {
			return;
		}

		// Get the full Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $object_id );

		// Bail if something went wrong.
		if ( $contact === false ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
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
			if ( isset( $object_ref->$property ) ) {
				$contact[ $property ] = $object_ref->$property;
			}
		}

		// Overwrite objectRef with full Contact data.
		$args['objectRef'] = (object) $contact;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'contact', $object_id );

		/**
		 * Broadcast that a relevant Contact has been updated.
		 *
		 * Used internally to:
		 *
		 * * Update a WordPress Post
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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function email_created( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Email.
		if ( $object_name != 'Email' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function email_edited( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Email.
		if ( $object_name != 'Email' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function email_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Email.
		if ( $object_name != 'Email' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function website_pre_edit( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Website.
		if ( $object_name != 'Website' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Website is about to be deleted.
	 *
	 * @since 0.5.2
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function website_pre_delete( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Website.
		if ( $object_name != 'Website' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		/**
		 * Broadcast that a CiviCRM Website is about to be deleted.
		 *
		 * @since 0.5.2
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/website/delete/pre', $args );

	}

	/**
	 * Intercept when a CiviCRM Website is created.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function website_created( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Website.
		if ( $object_name != 'Website' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function website_edited( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Website.
		if ( $object_name != 'Website' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function website_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Website.
		if ( $object_name != 'Website' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Phone is about to be deleted.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function phone_pre_delete( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Phone.
		if ( $object_name != 'Phone' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Phone is created.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function phone_created( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Phone.
		if ( $object_name != 'Phone' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function phone_edited( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Phone.
		if ( $object_name != 'Phone' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Phone has been deleted.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function phone_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Phone.
		if ( $object_name != 'Phone' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Instant Messenger is about to be deleted.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function im_pre_delete( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Instant Messenger.
		if ( $object_name != 'IM' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Instant Messenger is created.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function im_created( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Instant Messenger.
		if ( $object_name != 'IM' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function im_edited( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Instant Messenger.
		if ( $object_name != 'IM' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Instant Messenger has been deleted.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function im_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Instant Messenger.
		if ( $object_name != 'IM' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function relationship_pre_edit( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Relationship.
		if ( $object_name != 'Relationship' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function relationship_created( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Relationship.
		if ( $object_name != 'Relationship' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function relationship_edited( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Relationship.
		if ( $object_name != 'Relationship' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function relationship_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Relationship.
		if ( $object_name != 'Relationship' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function address_pre_edit( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $object_name != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Address is about to be deleted.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function address_pre_delete( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $object_name != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Contact's Address has been created.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function address_created( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $object_name != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function address_edited( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $object_name != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a CiviCRM Contact's Address has been deleted.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function address_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Address.
		if ( $object_name != 'Address' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * Intercept when a set of Custom Fields is about to be updated.
	 *
	 * @since 0.5.2
	 *
	 * @param string  $op The kind of operation.
	 * @param integer $group_id The numeric ID of the Custom Group.
	 * @param integer $entity_id The numeric ID of the CiviCRM Entity.
	 * @param array   $custom_fields The array of Custom Fields.
	 */
	public function custom_pre_edit( $op, $group_id, $entity_id, &$custom_fields ) {

		// Bail if there's nothing to see here.
		if ( empty( $custom_fields ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'            => $op,
			'group_id'      => $group_id,
			'entity_id'     => $entity_id,
			'custom_fields' => $custom_fields,
		];

		/**
		 * Broadcast that a set of CiviCRM Custom Fields is about to be updated.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Attachment::acf_fields_pre_save()
		 *
		 * @since 0.5.2
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/civicrm/custom/edit/pre', $args );

	}

	/**
	 * Intercept Custom Field updates.
	 *
	 * @since 0.4
	 *
	 * @param string  $op The kind of operation.
	 * @param integer $group_id The numeric ID of the Custom Group.
	 * @param integer $entity_id The numeric ID of the CiviCRM Entity.
	 * @param array   $custom_fields The array of Custom Fields.
	 */
	public function custom_edited( $op, $group_id, $entity_id, &$custom_fields ) {

		// Bail if there's nothing to see here.
		if ( empty( $custom_fields ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'            => $op,
			'group_id'      => $group_id,
			'entity_id'     => $entity_id,
			'custom_fields' => $custom_fields,
		];

		/**
		 * Broadcast that a set of CiviCRM Custom Fields has been updated.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Custom_Field::custom_edited()
		 * @see CiviCRM_Profile_Sync_ACF_User::custom_edited()
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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the CiviCRM Group.
	 * @param array   $object_ref The array of CiviCRM Group data.
	 */
	public function group_pre_delete( $op, $object_name, $object_id, &$object_ref ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Target our object type.
		if ( $object_name != 'Group' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the CiviCRM Group.
	 * @param array   $object_ref The array of CiviCRM Contact IDs.
	 */
	public function group_contacts_created( $op, $object_name, $object_id, &$object_ref ) {

		// Target our operation.
		if ( $op != 'create' ) {
			return;
		}

		// Target our object type.
		if ( $object_name != 'GroupContact' ) {
			return;
		}

		// Bail if there are no Contacts.
		if ( empty( $object_ref ) ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the CiviCRM Group.
	 * @param array   $object_ref Array of CiviCRM Contact IDs.
	 */
	public function group_contacts_deleted( $op, $object_name, $object_id, &$object_ref ) {

		// Target our operation.
		if ( $op != 'delete' ) {
			return;
		}

		// Target our object type.
		if ( $object_name != 'GroupContact' ) {
			return;
		}

		// Bail if there are no Contacts.
		if ( empty( $object_ref ) ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the CiviCRM Group.
	 * @param array   $object_ref Array of CiviCRM Contact IDs.
	 */
	public function group_contacts_rejoined( $op, $object_name, $object_id, &$object_ref ) {

		// Target our operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Target our object type.
		if ( $object_name != 'GroupContact' ) {
			return;
		}

		// Bail if there are no Contacts.
		if ( empty( $object_ref ) ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function activity_pre_create( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $object_name != 'Activity' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function activity_pre_edit( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $object_name != 'Activity' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function activity_created( $op, $object_name, $object_id, $object_ref ) {

		// Bail if it's not the "create" operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $object_name != 'Activity' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'activity', $object_id );

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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function activity_edited( $op, $object_name, $object_id, $object_ref ) {

		// Bail if it's not an "edit" operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $object_name != 'Activity' ) {
			return;
		}

		// Bail if it's not an Activity.
		if ( ! ( $object_ref instanceof CRM_Activity_DAO_Activity ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'activity', $object_id );

		/**
		 * Broadcast that a relevant Activity has been updated.
		 *
		 * Used internally to:
		 *
		 * * Update a WordPress Post
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
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function activity_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an Activity.
		if ( $object_name != 'Activity' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'activity', $object_id );

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
	 * Fires just before a CiviCRM Participant is created.
	 *
	 * @since 0.5
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function participant_pre_create( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Participant.
		if ( $object_name != 'Participant' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		/**
		 * Broadcast that a relevant Participant is about to be created.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/participant/create/pre', $args );

	}

	/**
	 * Fires just before a CiviCRM Participant is updated.
	 *
	 * @since 0.5
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function participant_pre_edit( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Participant.
		if ( $object_name != 'Participant' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		/**
		 * Broadcast that a relevant Participant is about to be updated.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/participant/edit/pre', $args );

	}

	/**
	 * Fires just before a CiviCRM Participant is deleted.
	 *
	 * @since 0.5
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function participant_pre_delete( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Participant.
		if ( $object_name != 'Participant' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		/**
		 * Broadcast that a relevant Participant is about to be deleted.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/participant/delete/pre', $args );

	}

	/**
	 * Create a WordPress Post when a CiviCRM Participant is created.
	 *
	 * @since 0.5
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function participant_created( $op, $object_name, $object_id, $object_ref ) {

		// Bail if it's not the "create" operation.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not a Participant.
		if ( $object_name != 'Participant' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'participant', $object_id );

		/**
		 * Broadcast that a relevant Participant has been created.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/participant/created', $args );

	}

	/**
	 * Update a WordPress Post when a CiviCRM Participant is updated.
	 *
	 * @since 0.5
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function participant_edited( $op, $object_name, $object_id, $object_ref ) {

		// Bail if it's not an "edit" operation.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not a Participant.
		if ( $object_name != 'Participant' ) {
			return;
		}

		// Bail if it's not a Participant.
		if ( ! ( $object_ref instanceof CRM_Event_BAO_Participant ) ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'participant', $object_id );

		/**
		 * Broadcast that a relevant Participant has been updated.
		 *
		 * Used internally to:
		 *
		 * * Update a WordPress Post
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/participant/edited', $args );

	}

	/**
	 * Intercept when a CiviCRM Participant has been deleted.
	 *
	 * @since 0.5
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function participant_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not a Participant.
		if ( $object_name != 'Participant' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		// Maybe set this as the originating Entity.
		$this->entity_set( 'participant', $object_id );

		/**
		 * Broadcast that a CiviCRM Participant has been deleted.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/participant/deleted', $args );

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a CiviCRM File is about to be deleted.
	 *
	 * @since 0.5.4
	 *
	 * @param object $event The event object.
	 * @param string $hook The hook name.
	 */
	public function file_pre_delete_listener( $event, $hook ) {

		// Extract CiviCRM Entity Tag for this hook.
		$entity_tag =& $event->object;

		// Bail if this isn't the type of object we're after.
		if ( ! ( $entity_tag instanceof CRM_Core_BAO_EntityTag ) ) {
			return;
		}

		// Make sure we have an Entity Table.
		if ( empty( $entity_tag->entity_table ) ) {
			return;
		}

		// Bail if this doesn't refer to a "File".
		if ( $entity_tag->entity_table !== 'civicrm_file' ) {
			return;
		}

		// Bail if there's no Entity ID.
		if ( empty( $entity_tag->entity_id ) ) {
			return;
		}

		// The Entity ID happens to be the CiviCRM File ID.

		// Get the CiviCRM File being deleted.
		$civicrm_file = $this->acf_loader->civicrm->attachment->file_get_by_id( $entity_tag->entity_id );
		if ( $civicrm_file === false ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => 'delete',
			'objectName' => 'File',
			'objectId'   => (int) $entity_tag->entity_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $civicrm_file ) ? $civicrm_file : (object) $civicrm_file;

		/**
		 * Broadcast that a CiviCRM File is about to be deleted.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/file/delete/pre', $args );

		// Get the full Attachment.
		$args['attachment'] = $this->acf_loader->civicrm->attachment->get_by_id( $args['objectId'] );

		/**
		 * Broadcast that a CiviCRM Attachment is about to be deleted.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/attachment/delete/pre', $args );

	}

	/**
	 * Intercept when a CiviCRM File is about to be deleted.
	 *
	 * Unused: does not receive events when Attachments are deleted.
	 *
	 * @since 0.5.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function file_pre_delete( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an File.
		if ( $object_name != 'File' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		/**
		 * Broadcast that a CiviCRM File is about to be deleted.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/file/delete/pre', $args );

		// Get the full Attachment.
		$args['attachment'] = $this->acf_loader->civicrm->attachment->get_by_id( $object_id );

		/**
		 * Broadcast that a CiviCRM Attachment is about to be deleted.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/attachment/delete/pre', $args );

	}

	/**
	 * Intercept when a CiviCRM File is created.
	 *
	 * @since 0.5.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function file_created( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'create' ) {
			return;
		}

		// Bail if this is not an File.
		if ( $object_name != 'File' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		/**
		 * Broadcast that a CiviCRM File has been created.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/file/created', $args );

		// Get the full Attachment.
		$args['attachment'] = $this->acf_loader->civicrm->attachment->get_by_id( $object_id );

		/**
		 * Broadcast that a CiviCRM Attachment has been created.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/attachment/created', $args );

	}

	/**
	 * Intercept when a CiviCRM File is updated.
	 *
	 * @since 0.5.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function file_edited( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'edit' ) {
			return;
		}

		// Bail if this is not an File.
		if ( $object_name != 'File' ) {
			return;
		}

		// Let's make an array of the CiviCRM params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		/**
		 * Broadcast that a CiviCRM File has been updated.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/file/edited', $args );

		// Get the full Attachment.
		$args['attachment'] = $this->acf_loader->civicrm->attachment->get_by_id( $object_id );

		/**
		 * Broadcast that a CiviCRM Attachment has been updated.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/attachment/edited', $args );

	}

	/**
	 * Intercept when a CiviCRM File has been deleted.
	 *
	 * Unused: does not receive events when Attachments are deleted.
	 *
	 * @since 0.5.4
	 *
	 * @param string  $op The type of database operation.
	 * @param string  $object_name The type of object.
	 * @param integer $object_id The ID of the object.
	 * @param object  $object_ref The object.
	 */
	public function file_deleted( $op, $object_name, $object_id, $object_ref ) {

		// Bail if not the context we want.
		if ( $op != 'delete' ) {
			return;
		}

		// Bail if this is not an File.
		if ( $object_name != 'File' ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'op'         => $op,
			'objectName' => $object_name,
			'objectId'   => $object_id,
		];

		// Maybe cast objectRef as object.
		$args['objectRef'] = is_object( $object_ref ) ? $object_ref : (object) $object_ref;

		/**
		 * Broadcast that a CiviCRM File has been deleted.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/file/deleted', $args );

		/**
		 * Broadcast that a CiviCRM Attachment has been deleted.
		 *
		 * @since 0.5.4
		 *
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/mapper/attachment/deleted', $args );

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept the Post saved operation.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The ID of the Post or revision.
	 * @param integer $post The Post object.
	 * @param bool    $update True if the Post is being updated, false if new.
	 */
	public function post_saved( $post_id, $post, $update ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'post_id' => $post_id,
			'post'    => $post,
			'update'  => $update,
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
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/mapper/post/saved', $args );

	}

	/**
	 * Fires just before the ACF Fields saved operation.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $post_id The ID of the Post or revision.
	 */
	public function acf_fields_saved_pre( $post_id ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'post_id' => $post_id,
		];

		/**
		 * Broadcast that ACF Fields are about to be saved for a Post.
		 *
		 * @since 0.5.2
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/mapper/acf_fields/saved/pre', $args );

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
		if ( is_multisite() && ms_is_switched() ) {
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
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/mapper/acf_fields/saved', $args );

	}

	// -------------------------------------------------------------------------

	/**
	 * Hook into updates to a term before the term is updated.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the new term.
	 * @param string  $taxonomy The taxonomy containing the term.
	 */
	public function term_pre_edit( $term_id, $taxonomy = null ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id'  => $term_id,
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
	 * Hook in before a Term is deleted.
	 *
	 * @since 0.5
	 *
	 * @param integer $term_id The numeric ID of the Term.
	 * @param string  $taxonomy The Taxonomy containing the Term.
	 */
	public function term_pre_delete( $term_id, $taxonomy = null ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id'  => $term_id,
			'taxonomy' => $taxonomy,
		];

		/**
		 * Broadcast that a WordPress Term is about to be deleted.
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/mapper/term/delete/pre', $args );

	}

	/**
	 * Hook into the creation of a term.
	 *
	 * @since 0.4
	 *
	 * @param array  $term_id The numeric ID of the new term.
	 * @param array  $tt_id The numeric ID of the new term.
	 * @param string $taxonomy Should be (an array containing) taxonomy names.
	 */
	public function term_created( $term_id, $tt_id, $taxonomy ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id'  => $term_id,
			'tt_id'    => $tt_id,
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
	 * Hook into updates to a term.
	 *
	 * @since 0.4
	 *
	 * @param integer $term_id The numeric ID of the edited term.
	 * @param array   $tt_id The numeric ID of the edited term taxonomy.
	 * @param string  $taxonomy Should be (an array containing) the taxonomy.
	 */
	public function term_edited( $term_id, $tt_id, $taxonomy ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id'  => $term_id,
			'tt_id'    => $tt_id,
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
	 * @param array   $tt_id The numeric ID of the deleted term taxonomy.
	 * @param string  $taxonomy Name of the taxonomy.
	 * @param object  $deleted_term The deleted term object.
	 */
	public function term_deleted( $term_id, $tt_id, $taxonomy, $deleted_term ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Let's make an array of the params.
		$args = [
			'term_id'      => $term_id,
			'tt_id'        => $tt_id,
			'taxonomy'     => $taxonomy,
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

}
