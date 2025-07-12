<?php
/**
 * Mapping Class.
 *
 * Implements UI elements and data storage for linking Entities.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync ACF Mapping Class.
 *
 * A class that encapsulates Mapping functionality and implements UI elements
 * and data storage for linking:
 *
 * * CiviCRM Contact Types with WordPress Post Types.
 * * CiviCRM Activity Types with WordPress Post Types.
 * * CiviCRM Participant Roles with WordPress Post Types.
 *
 * More linkages to follow.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Mapping {

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
	 * Sync mappings.
	 *
	 * Sync between CiviCRM Entity Types and WordPress Custom Post Types can
	 * only really be made on a site-by-site basis in Multisite, since CPTs are
	 * defined per-site and may not be available network-wide.
	 *
	 * The option for the sync mappings is therefore stored via `get_option()`
	 * family of functions rather than `get_site_option()` etc.
	 *
	 * Example array (nested array key is CiviCRM Entity Type ID, value is CPT name):
	 *
	 * [
	 *   'contact-post' => [ 3 => 'post', 8 => 'student' ],
	 *   'activity-post' => [ 123 => 'award', 258 => 'foo' ],
	 *   'participant-role-post' => [ 256 => 'attendee', 576 => 'speaker' ],
	 * ]
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $mappings = [];

	/**
	 * Post Types sync settings.
	 *
	 * The option for the sync settings is also stored via `get_option()` family
	 * of functions.
	 *
	 * Example array (key is CPT name, value is array of settings for this CPT):
	 *
	 * [
	 *   'parent' => [
	 *     'some_option' => 1,
	 *     'another_option' => 'foo',
	 *   ],
	 *   'student' => [
	 *     'some_option' => 0,
	 *     'another_option' => 'bar',
	 *   ],
	 *   'participant' => [
	 *     'enabled' => 0,
	 *   ],
	 * ]
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $settings = [];

	/**
	 * Mappings option key.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $mappings_key = 'cwps_acf_mappings';

	/**
	 * Mapped items Settings option key.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $settings_key = 'cwps_acf_mapping_settings';

	/**
	 * Mapped items Settings option key.
	 *
	 * @since 0.4
	 * @access public
	 * @var integer
	 */
	public $saved_participant_role_id;

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

		// Store default mappings if none exist.
		if ( ! $this->option_exists( $this->mappings_key ) ) {
			$this->option_set( $this->mappings_key, $this->mappings );
		}

		// Load mappings array.
		$this->mappings = $this->option_get( $this->mappings_key, $this->mappings );

		// Store default settings if none exist.
		if ( ! $this->option_exists( $this->settings_key ) ) {
			$this->option_set( $this->settings_key, $this->settings );
		}

		// Load settings array.
		$this->settings = $this->option_get( $this->settings_key, $this->settings );

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/acf/mapping/loaded' );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Register template directory for form amends.
		add_action( 'civicrm_config', [ $this, 'register_form_directory' ], 10 );

		// Modify CiviCRM Add/Edit Contact Type form.
		add_action( 'civicrm_buildForm', [ $this, 'form_contact_type_build' ], 10, 2 );

		// Intercept CiviCRM Add/Edit Contact Type form submission process.
		add_action( 'civicrm_postProcess', [ $this, 'form_contact_type_process' ], 10, 2 );

		// Intercept CiviCRM Add/Edit Contact Type postSave hook.
		add_action( 'civicrm_postSave_civicrm_contact_type', [ $this, 'form_contact_type_post_save' ], 10 );

		// Modify CiviCRM Add/Edit Activity Type form.
		add_action( 'civicrm_buildForm', [ $this, 'form_activity_type_build' ], 10, 2 );

		// Intercept CiviCRM Add/Edit Activity Type form submission process.
		add_action( 'civicrm_postProcess', [ $this, 'form_activity_type_process' ], 10, 2 );

		// Intercept CiviCRM Add/Edit Activity Type postSave hook.
		add_action( 'civicrm_postSave_civicrm_option_value', [ $this, 'form_activity_type_post_save' ], 10 );

		// Modify CiviCRM Add/Edit Participant Role form.
		add_action( 'civicrm_buildForm', [ $this, 'form_participant_role_build' ], 10, 2 );

		// Intercept CiviCRM Add/Edit Participant Role form submission process.
		add_action( 'civicrm_postProcess', [ $this, 'form_participant_role_process' ], 10, 2 );

		// Intercept CiviCRM Add/Edit Participant Role postSave hook.
		add_action( 'civicrm_postSave_civicrm_option_value', [ $this, 'form_participant_role_post_save' ], 10 );

		// Modify CiviCRM Event Component Settings form.
		add_action( 'civicrm_buildForm', [ $this, 'form_participant_build' ], 10, 2 );

		// Intercept CiviCRM Event Component Settings form submission process.
		add_action( 'civicrm_postProcess', [ $this, 'form_participant_process' ], 10, 2 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Register directory that CiviCRM searches in for our form template file.
	 *
	 * @since 0.4
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function register_form_directory( &$config ) {

		// Get template instance.
		$template = CRM_Core_Smarty::singleton();

		// Define our custom path.
		$custom_path = CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/civicrm';

		// Add our custom template directory.
		$template->addTemplateDir( $custom_path );

		// Register template directory.
		$template_include_path = $custom_path . PATH_SEPARATOR . get_include_path();
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.runtime_configuration_set_include_path
		set_include_path( $template_include_path );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Enable a WordPress Custom Post Type to be linked to a CiviCRM Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_contact_type_build( $form_name, &$form ) {

		// Is this the Contact Type edit form?
		if ( 'CRM_Admin_Form_ContactType' !== $form_name ) {
			return;
		}

		// Bail if this is the CiviCRM "Delete Action".
		$action = $form->getVar( '_action' );
		if ( isset( $action ) && 8 === (int) $action ) {
			return;
		}

		// Get CiviCRM Contact Type.
		$contact_type = $form->getVar( '_values' );

		// Determine form mode by whether we have a Contact Type.
		if ( isset( $contact_type ) && ! empty( $contact_type ) ) {
			$mode = 'edit';
		} else {
			$mode = 'create';
		}

		// Build options array.
		$options = [
			'' => '- ' . __( 'No Synced Post Type', 'civicrm-wp-profile-sync' ) . ' -',
		];

		// Maybe assign Contact Type ID.
		$contact_type_id = 0;
		if ( 'edit' === $mode ) {
			$contact_type_id = (int) $contact_type['id'];
		}

		// Get all available Post Types for this Contact Type.
		$post_types = $this->acf_loader->post_type->post_types_get_for_contact_type( $contact_type_id );

		// Add select option for those which are public.
		if ( count( $post_types ) > 0 ) {
			foreach ( $post_types as $post_type ) {

				/*
				 * Exclude built-in WordPress private Post Types.
				 *
				 * * nav_menu_item
				 * * revision
				 * * customize_changeset
				 * * etc, etc
				 *
				 * ACF does not support these.
				 */
				if ( $post_type->_builtin && ! $post_type->public ) {
					continue;
				}

				// ACF does not support the built-in WordPress Media Post Type.
				if ( 'attachment' === $post_type->name ) {
					continue;
				}

				// Add anything else.
				$options[ esc_attr( $post_type->name ) ] = esc_html( $post_type->labels->singular_name );

			}
		}

		// Add Post Types dropdown.
		$cpt_select = $form->add(
			'select',
			'cwps_acf_cpt',
			__( 'Synced Post Type', 'civicrm-wp-profile-sync' ),
			$options,
			false,
			[]
		);

		/*
		// Add a description.
		$form->assign( 'cwps_acf_cpt_desc', __( 'Blah', 'civicrm-wp-profile-sync' ) );
		*/

		// Amend form in edit mode.
		if ( 'edit' === $mode ) {

			// Get existing CPT.
			$cpt_name = $this->mapping_for_contact_type_get( $contact_type['id'] );

			// If we have a mapped CPT.
			if ( false !== $cpt_name ) {

				// Set selected value of our dropdown.
				$cpt_select->setSelected( $cpt_name );

				// Get CPT settings.
				$cpt_settings = $this->setting_get( $cpt_name );

			}

			/*
			// Do we allow changes to be made?
			$cpt_select->freeze();
			*/

		}

		// Insert template block into the page.
		CRM_Core_Region::instance( 'page-body' )->add( [ 'template' => 'cwps-acf-contact-type-cpt.tpl' ] );

	}

	/**
	 * Callback for the Add/Edit Contact Type form's postSave hook.
	 *
	 * Since neither "civicrm_pre" nor "civicrm_post" fire for this CiviCRM
	 * entity, we grab the saved ID here, store it, then use it in the form's
	 * postProcess callback.
	 *
	 * @see form_contact_type_process()
	 *
	 * @since 0.4
	 *
	 * @param object $object_ref The DAO object.
	 */
	public function form_contact_type_post_save( $object_ref ) {

		// Bail if not Contact Type save operation.
		if ( ! ( $object_ref instanceof CRM_Contact_DAO_ContactType ) ) {
			return;
		}

		// Bail if no Contact Type ID.
		if ( empty( $object_ref->id ) ) {
			return;
		}

		// Store locally for use in form_contact_type_process().
		$this->saved_contact_type_id = $object_ref->id;

	}

	/**
	 * Callback for the Add/Edit Contact Type form's postProcess hook.
	 *
	 * Neither "civicrm_pre" nor "civicrm_post" fire for this CiviCRM entity,
	 * so the link between the Contact Type and the WordPress Post Type must be
	 * made here.
	 *
	 * @since 0.4
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_contact_type_process( $form_name, &$form ) {

		// Bail if not Contact Type edit form.
		if ( ! ( $form instanceof CRM_Admin_Form_ContactType ) ) {
			return;
		}

		// Grab Contact Type ID from form.
		$contact_type_id = $form->getVar( '_id' );

		// Get Contact Type ID if not present in the form.
		if ( empty( $contact_type_id ) ) {
			if ( isset( $this->saved_contact_type_id ) ) {
				$contact_type_id = $this->saved_contact_type_id;
			}
		}

		// Is this the CiviCRM "Delete Action"?
		$action = $form->getVar( '_action' );
		if ( isset( $action ) && 8 === (int) $action ) {
			$contact_type_id = $form->getVar( '_id' );
		}

		// Bail if we don't get a Contact Type for some reason.
		if ( empty( $contact_type_id ) ) {
			return;
		}

		// Grab submitted values.
		$values = $form->getSubmitValues();

		// Inspect our select value.
		if ( empty( $values['cwps_acf_cpt'] ) ) {

			// Remove (or ignore) linkage.
			$this->mapping_for_contact_type_remove( $contact_type_id );

			/**
			 * Broadcast that the mapping has been removed.
			 *
			 * @since 0.4
			 *
			 * @param integer $contact_type_id The removed Contact Type ID.
			 * @param array $values The form values.
			 */
			do_action( 'cwps/acf/mapping/contact/removed', $contact_type_id, $values );

		} else {

			// Let's grab the Post Type.
			$post_type = trim( $values['cwps_acf_cpt'] );

			// Add/Update linkage.
			$this->mapping_for_contact_type_update( $contact_type_id, $post_type );

			/**
			 * Broadcast that the mapping has been updated.
			 *
			 * @since 0.4
			 *
			 * @param integer $contact_type_id The updated Contact Type ID.
			 * @param string $post_type The updated Post Type name.
			 * @param array $values The form values.
			 */
			do_action( 'cwps/acf/mapping/contact/edited', $contact_type_id, $post_type, $values );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Enable a WordPress Custom Post Type to be linked to a CiviCRM Activity Type.
	 *
	 * @since 0.4
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_activity_type_build( $form_name, &$form ) {

		// Is this the Options edit form?
		if ( 'CRM_Admin_Form_Options' !== $form_name ) {
			return;
		}

		// Is this the Activity Type edit form?
		if ( 'activity_type' !== $form->get( 'gName' ) ) {
			return;
		}

		// Bail if this is the CiviCRM "Delete Action".
		$action = $form->getVar( '_action' );
		if ( isset( $action ) && 8 === (int) $action ) {
			return;
		}

		// Get list of allowed Activity Types.
		$allowed_activity_types = [];
		$option_group           = $this->plugin->civicrm->option_group_get( 'activity_type' );
		if ( ! empty( $option_group ) ) {
			$allowed_activity_types = CRM_Core_OptionGroup::valuesByID( $option_group['id'] );
		}

		// Bail if there are none.
		if ( empty( $allowed_activity_types ) ) {
			return;
		}

		// Get displayed CiviCRM Activity Type.
		$activity_type = $form->getVar( '_values' );

		// Determine form mode by whether we have an Activity Type.
		if ( isset( $activity_type ) && ! empty( $activity_type ) ) {
			$mode = 'edit';
		} else {
			$mode = 'create';
		}

		// Bail if not an allowed Activity Type.
		if ( 'edit' === $mode && ! array_key_exists( $activity_type['value'], $allowed_activity_types ) ) {
			return;
		}

		// Build options array.
		$options = [
			'' => '- ' . __( 'No Synced Post Type', 'civicrm-wp-profile-sync' ) . ' -',
		];

		// Maybe assign Activity Type ID.
		$activity_type_id = 0;
		if ( 'edit' === $mode ) {
			$activity_type_id = (int) $activity_type['value'];
		}

		// Get all available Post Types for this Activity Type.
		$post_types = $this->acf_loader->post_type->post_types_get_for_activity_type( $activity_type_id );

		// Add select option for those which are public.
		if ( count( $post_types ) > 0 ) {
			foreach ( $post_types as $post_type ) {

				/*
				 * Exclude built-in WordPress private Post Types.
				 *
				 * * nav_menu_item
				 * * revision
				 * * customize_changeset
				 * * etc, etc
				 *
				 * ACF does not support these.
				 */
				if ( $post_type->_builtin && ! $post_type->public ) {
					continue;
				}

				// ACF does not support the built-in WordPress Media Post Type.
				if ( 'attachment' === $post_type->name ) {
					continue;
				}

				// Add anything else.
				$options[ esc_attr( $post_type->name ) ] = esc_html( $post_type->labels->singular_name );

			}
		}

		// Add Post Types dropdown.
		$cpt_select = $form->add(
			'select',
			'cwps_acf_cpt',
			__( 'Synced Post Type', 'civicrm-wp-profile-sync' ),
			$options,
			false,
			[]
		);

		/*
		// Add a description.
		$form->assign( 'cwps_acf_cpt_desc', __( 'Blah', 'civicrm-wp-profile-sync' ) );
		*/

		// Amend form in edit mode.
		if ( 'edit' === $mode ) {

			// Get existing CPT.
			$cpt_name = $this->mapping_for_activity_type_get( $activity_type['value'] );

			// If we have a mapped CPT.
			if ( false !== $cpt_name ) {

				// Set selected value of our dropdown.
				$cpt_select->setSelected( $cpt_name );

				// Get CPT settings.
				$cpt_settings = $this->setting_get( $cpt_name );

			}

			/*
			// Do we allow changes to be made?
			$cpt_select->freeze();
			*/

		}

		// Insert template block into the page.
		CRM_Core_Region::instance( 'page-body' )->add( [ 'template' => 'cwps-acf-activity-type-cpt.tpl' ] );

	}

	/**
	 * Callback for the Add/Edit Activity Type form's postSave hook.
	 *
	 * Since neither "civicrm_pre" nor "civicrm_post" fire for this CiviCRM
	 * entity, we grab the saved ID here, store it, then use it in the form's
	 * postProcess callback.
	 *
	 * @see form_activity_type_process()
	 *
	 * @since 0.4
	 *
	 * @param object $object_ref The DAO object.
	 */
	public function form_activity_type_post_save( $object_ref ) {

		// Bail if not Activity Type save operation.
		if ( ! ( $object_ref instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if no Activity Type ID.
		if ( empty( $object_ref->id ) ) {
			return;
		}

		// Bail if no Option Group ID.
		if ( empty( $object_ref->option_group_id ) ) {
			return;
		}

		// Get the ID of the Activity Types Option Group.
		$activity_types_id = $this->acf_loader->civicrm->activity_type->option_group_id_get();

		// Bail if not in Activity Types Option Group.
		if ( (int) $object_ref->option_group_id !== (int) $activity_types_id ) {
			return;
		}

		// Get the data for the Activity Type.
		$activity_type = $this->acf_loader->civicrm->activity_type->get_by_id( $object_ref->id );

		// Bail on failure.
		if ( false === $activity_type ) {
			return;
		}

		// Store ID (actually "value") locally for use in form_activity_type_process().
		$this->saved_activity_type_id = (int) $activity_type['value'];

	}

	/**
	 * Callback for the Add/Edit Activity Type form's postProcess hook.
	 *
	 * Neither "civicrm_pre" nor "civicrm_post" fire for this CiviCRM entity,
	 * so the link between the Activity Type and the WordPress Post Type must be
	 * made here.
	 *
	 * @since 0.4
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_activity_type_process( $form_name, &$form ) {

		// Bail if not Activity Type edit form.
		if ( ! ( $form instanceof CRM_Admin_Form_Options ) ) {
			return;
		}

		// Grab "Group Name" from form.
		$group_name = $form->getVar( '_gName' );

		// Bail if not Activity Type.
		if ( 'activity_type' !== $group_name ) {
			return;
		}

		// Get Activity Type ID if not present in the form.
		if ( ! empty( $values['value'] ) ) {
			$activity_type_id = (int) $values['value'];
		} else {
			if ( isset( $this->saved_activity_type_id ) ) {
				$activity_type_id = $this->saved_activity_type_id;
			}
		}

		// Is this the CiviCRM "Delete Action"?
		$action = $form->getVar( '_action' );
		if ( isset( $action ) && 8 === (int) $action ) {
			$activity_type_id = $form->getVar( '_id' );
		}

		// Bail if we don't get an Activity Type for some reason.
		if ( empty( $activity_type_id ) ) {
			return;
		}

		// Grab submitted values.
		$values = $form->getSubmitValues();

		// Inspect our select value.
		if ( empty( $values['cwps_acf_cpt'] ) ) {

			// Remove (or ignore) linkage.
			$this->mapping_for_activity_type_remove( $activity_type_id );

			/**
			 * Broadcast that the mapping has been removed.
			 *
			 * @since 0.4
			 *
			 * @param integer $activity_type_id The removed Activity Type ID.
			 * @param array $values The form values.
			 */
			do_action( 'cwps/acf/mapping/activity/removed', $activity_type_id, $values );

		} else {

			// Let's grab the Post Type.
			$post_type = trim( $values['cwps_acf_cpt'] );

			// Add/Update linkage.
			$this->mapping_for_activity_type_update( $activity_type_id, $post_type );

			/**
			 * Broadcast that the mapping has been updated.
			 *
			 * @since 0.4
			 *
			 * @param integer $activity_type_id The updated Activity Type ID.
			 * @param string $post_type The updated Post Type name.
			 * @param array $values The form values.
			 */
			do_action( 'cwps/acf/mapping/activity/edited', $activity_type_id, $post_type, $values );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Enable a WordPress Custom Post Type to be linked to a CiviCRM Participant Role.
	 *
	 * @since 0.5
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_participant_role_build( $form_name, &$form ) {

		// Is this the Options edit form?
		if ( 'CRM_Admin_Form_Options' !== $form_name ) {
			return;
		}

		// Is this a Participant Role form?
		if ( $form->get( 'gName' ) !== 'participant_role' ) {
			return;
		}

		// Bail if this is the CiviCRM "Delete Action".
		$action = $form->getVar( '_action' );
		if ( isset( $action ) && 8 === (int) $action ) {
			return;
		}

		// Get displayed CiviCRM Participant Role.
		$participant_role = $form->getVar( '_values' );

		// Determine form mode by whether we have a Participant Role.
		if ( isset( $participant_role ) && ! empty( $participant_role ) ) {
			$mode = 'edit';
		} else {
			$mode = 'create';
		}

		// Build options array.
		$options = [
			'' => '- ' . __( 'No Synced Post Type', 'civicrm-wp-profile-sync' ) . ' -',
		];

		// Maybe assign Participant Role ID.
		$participant_role_id = 0;
		if ( 'edit' === $mode ) {
			$participant_role_id = (int) $participant_role['value'];
		}

		// Get all available Post Types for this Participant Role.
		$post_types = $this->acf_loader->post_type->post_types_get_for_participant_role( $participant_role_id );

		// Add select option for those which are public.
		if ( count( $post_types ) > 0 ) {
			foreach ( $post_types as $post_type ) {

				/*
				 * Exclude built-in WordPress private Post Types.
				 *
				 * * nav_menu_item
				 * * revision
				 * * customize_changeset
				 * * etc, etc
				 *
				 * ACF does not support these.
				 */
				if ( $post_type->_builtin && ! $post_type->public ) {
					continue;
				}

				// ACF does not support the built-in WordPress Media Post Type.
				if ( 'attachment' === $post_type->name ) {
					continue;
				}

				// Add anything else.
				$options[ esc_attr( $post_type->name ) ] = esc_html( $post_type->labels->singular_name );

			}
		}

		// Add Post Types dropdown.
		$cpt_select = $form->add(
			'select',
			'cwps_acf_cpt',
			__( 'Synced Post Type', 'civicrm-wp-profile-sync' ),
			$options,
			false,
			[]
		);

		/*
		// Add a description.
		$form->assign( 'cwps_acf_cpt_desc', __( 'Blah', 'civicrm-wp-profile-sync' ) );
		*/

		// Amend form in edit mode.
		if ( 'edit' === $mode ) {

			// Get existing CPT.
			$cpt_name = $this->mapping_for_participant_role_get( $participant_role['value'] );

			// If we have a mapped CPT.
			if ( false !== $cpt_name ) {

				// Set selected value of our dropdown.
				$cpt_select->setSelected( $cpt_name );

				// Get CPT settings.
				$cpt_settings = $this->setting_get( $cpt_name );

			}

			/*
			// Do we allow changes to be made?
			$cpt_select->freeze();
			*/

		}

		// Insert template block into the page.
		CRM_Core_Region::instance( 'page-body' )->add( [ 'template' => 'cwps-acf-participant-role-cpt.tpl' ] );

	}

	/**
	 * Callback for the Add/Edit Participant Role form's postSave hook.
	 *
	 * Since neither "civicrm_pre" nor "civicrm_post" fire for this CiviCRM
	 * entity, we grab the saved ID here, store it, then use it in the form's
	 * postProcess callback.
	 *
	 * @see form_participant_role_process()
	 *
	 * @since 0.5
	 *
	 * @param object $object_ref The DAO object.
	 */
	public function form_participant_role_post_save( $object_ref ) {

		// Bail if not Participant Role save operation.
		if ( ! ( $object_ref instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Bail if no Participant Role ID.
		if ( empty( $object_ref->id ) ) {
			return;
		}

		// Bail if no Option Group ID.
		if ( empty( $object_ref->option_group_id ) ) {
			return;
		}

		// Get the ID of the Participant Roles Option Group.
		$participant_roles_id = $this->acf_loader->civicrm->participant_role->option_group_id_get();

		// Bail if not in Participant Roles Option Group.
		if ( (int) $object_ref->option_group_id !== (int) $participant_roles_id ) {
			return;
		}

		// Get the data for the Participant Role.
		$participant_role = $this->acf_loader->civicrm->participant_role->get_by_id( $object_ref->id );

		// Bail on failure.
		if ( false === $participant_role ) {
			return;
		}

		// Store ID (actually "value") locally for use in form_participant_role_process().
		$this->saved_participant_role_id = (int) $participant_role['value'];

	}

	/**
	 * Callback for the Add/Edit Participant Role form's postProcess hook.
	 *
	 * Neither "civicrm_pre" nor "civicrm_post" fire for this CiviCRM entity,
	 * so the link between the Participant Role and the WordPress Post Type must be
	 * made here.
	 *
	 * @since 0.5
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_participant_role_process( $form_name, &$form ) {

		// Bail if not Participant Role edit form.
		if ( ! ( $form instanceof CRM_Admin_Form_Options ) ) {
			return;
		}

		// Grab "Group Name" from form.
		$group_name = $form->getVar( '_gName' );

		// Bail if not Participant Role.
		if ( 'participant_role' !== $group_name ) {
			return;
		}

		// Grab submitted values.
		$values = $form->getSubmitValues();

		// Get Participant Role ID if not present in the form.
		if ( ! empty( $values['value'] ) ) {
			$participant_role_id = (int) $values['value'];
		} else {
			if ( isset( $this->saved_participant_role_id ) ) {
				$participant_role_id = $this->saved_participant_role_id;
			}
		}

		// Is this the CiviCRM "Delete Action"?
		$action = $form->getVar( '_action' );
		if ( isset( $action ) && 8 === (int) $action ) {
			$form_values = $form->getVar( '_values' );
			if ( ! empty( $form_values['value'] ) ) {
				$participant_role_id = (int) $form_values['value'];
			}
		}

		// Bail if we don't get a Participant Role for some reason.
		if ( empty( $participant_role_id ) ) {
			return;
		}

		// Inspect our select value.
		if ( empty( $values['cwps_acf_cpt'] ) ) {

			// Remove (or ignore) linkage.
			$this->mapping_for_participant_role_remove( $participant_role_id );

			/**
			 * Broadcast that the mapping has been removed.
			 *
			 * @since 0.5
			 *
			 * @param integer $participant_role_id The removed Participant Role ID.
			 * @param array $values The form values.
			 */
			do_action( 'cwps/acf/mapping/participant/removed', $participant_role_id, $values );

		} else {

			// Let's grab the Post Type.
			$post_type = trim( $values['cwps_acf_cpt'] );

			// Add/Update linkage.
			$this->mapping_for_participant_role_update( $participant_role_id, $post_type );

			/**
			 * Broadcast that the mapping has been updated.
			 *
			 * @since 0.5
			 *
			 * @param integer $participant_role_id The updated Participant Role ID.
			 * @param string $post_type The updated Post Type name.
			 * @param array $values The form values.
			 */
			do_action( 'cwps/acf/mapping/participant/edited', $participant_role_id, $post_type, $values );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Enable or disable the "Participant" Custom Post Type provided by this plugin.
	 *
	 * @since 0.5
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_participant_build( $form_name, &$form ) {

		// Is this the Event Component edit form?
		if ( 'CRM_Admin_Form_Generic' !== $form_name ) {
			return;
		}

		// Grab "URL Path" from form.
		$form_url_path = $form->getVar( 'urlPath' );
		$url_path      = [ 'civicrm', 'admin', 'setting', 'preferences', 'event' ];
		if ( $form_url_path !== $url_path ) {
			return;
		}

		// Add "Enable CPT" checkbox.
		$checkbox = $form->add(
			'checkbox',
			'cwps_acf_enable_cpt',
			__( 'Enable the "Participant" Custom Post Type', 'civicrm-wp-profile-sync' )
		);

		// Add help text.
		$form->assign(
			'cwps_acf_enable_cpt_help',
			__( 'If you would like to use the "Participant" Custom Post Type provided by this plugin then check this box. This will also enable a Custom Taxonomy on the "Participant" Post Type which is kept in sync with the Participant Roles in CiviCRM.', 'civicrm-wp-profile-sync' )
		);

		// Get settings.
		$settings = $this->setting_get( 'participant' );

		// Set status of checkbox based on setting.
		if ( false !== $settings ) {
			if ( isset( $settings['enabled'] ) && 1 === $settings['enabled'] ) {
				$checkbox->setChecked( true );
			}
		}

		// Insert template block into the page.
		CRM_Core_Region::instance( 'page-body' )->add( [ 'template' => 'cwps-acf-participant-cpt.tpl' ] );

	}

	/**
	 * Callback for the CiviEvent Component Settings form's postProcess hook.
	 *
	 * @since 0.5
	 *
	 * @param string $form_name The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_participant_process( $form_name, &$form ) {

		// Is this the Event Component edit form?
		if ( 'CRM_Admin_Form_Generic' !== $form_name ) {
			return;
		}

		// Grab "URL Path" from form.
		$form_url_path = $form->getVar( 'urlPath' );
		$url_path      = [ 'civicrm', 'admin', 'setting', 'preferences', 'event' ];
		if ( $form_url_path !== $url_path ) {
			return;
		}

		// Grab submitted values.
		$values = $form->getSubmitValues();

		// Assume the "Enable CPT" checkbox is not checked, but override if it is.
		$current = 0;
		if ( isset( $values['cwps_acf_enable_cpt'] ) && 1 === (int) $values['cwps_acf_enable_cpt'] ) {
			$current = 1;
		}

		// Get current setting data.
		$data = $this->setting_get( 'participant' );

		// Grab previous value for comparison below.
		$previous = empty( $data['enabled'] ) ? 0 : 1;

		// Bail if there is no change.
		if ( $previous === $current ) {
			return;
		}

		// Add/Update setting.
		$data['enabled'] = $current;

		// Overwrite setting.
		$this->setting_update( 'participant', $data );

		// Fire one of two actions depending on what has happened.
		$action = 'enabled';
		if ( 1 === $previous && 0 === $current ) {
			$action = 'disabled';
		}

		/**
		 * Broadcast that the mapping has been updated.
		 *
		 * @since 0.5
		 *
		 * @param array $values The form values.
		 * @param array $data The settings.
		 */
		do_action( "cwps/acf/mapping/participant-cpt/{$action}", $values, $data );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get all Entity Type to Post Type mappings.
	 *
	 * @since 0.4
	 *
	 * @return array $mappings The array of mappings.
	 */
	public function mappings_get_all() {

		// Get existing mappings.
		$for_contacts     = $this->mappings_for_contact_types_get();
		$for_activities   = $this->mappings_for_activity_types_get();
		$for_participants = $this->mappings_for_participant_roles_get();

		// --<
		return array_merge( $for_contacts, $for_activities, $for_participants );

	}

	/**
	 * Delete the mappings array.
	 *
	 * @since 0.4
	 */
	public function mappings_delete() {

		// Delete the mappings option.
		$this->option_delete( $this->mappings_key );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get all Contact Type to Post Type mappings.
	 *
	 * @since 0.4
	 *
	 * @return array $mappings The array of mappings.
	 */
	public function mappings_for_contact_types_get() {

		// --<
		return ! empty( $this->mappings['contact-post'] ) ? $this->mappings['contact-post'] : [];

	}

	/**
	 * Get the WordPress Post Type for a CiviCRM Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type_id The numeric ID of the Contact Type.
	 * @return string|bool $cpt_name The name of the Post Type or false if none exists.
	 */
	public function mapping_for_contact_type_get( $contact_type_id ) {

		// Init as false.
		$cpt_name = false;

		// Overwrite if a mapping exists.
		if ( isset( $this->mappings['contact-post'][ $contact_type_id ] ) ) {
			$cpt_name = $this->mappings['contact-post'][ $contact_type_id ];
		}

		// --<
		return $cpt_name;

	}

	/**
	 * Add or update the link between a CiviCRM Contact Type and a WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type_id The numeric ID of the Contact Type.
	 * @param string  $cpt_name The name of the WordPress Post Type.
	 */
	public function mapping_for_contact_type_update( $contact_type_id, $cpt_name ) {

		// Overwrite (or create) mapping.
		$this->mappings['contact-post'][ $contact_type_id ] = $cpt_name;

		// Update option.
		$this->option_set( $this->mappings_key, $this->mappings );

	}

	/**
	 * Maybe delete the link between a CiviCRM Contact Type and a WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type_id The numeric ID of the Contact Type.
	 */
	public function mapping_for_contact_type_remove( $contact_type_id ) {

		// If a mapping exists.
		if ( isset( $this->mappings['contact-post'][ $contact_type_id ] ) ) {

			// We also need to remove the setting.
			$this->setting_remove( $this->mappings['contact-post'][ $contact_type_id ] );

			// Finally, remove mapping.
			unset( $this->mappings['contact-post'][ $contact_type_id ] );

		}

		// Update option.
		$this->option_set( $this->mappings_key, $this->mappings );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get all Activity Type to Post Type mappings.
	 *
	 * @since 0.4
	 *
	 * @return array $mappings The array of mappings.
	 */
	public function mappings_for_activity_types_get() {

		// --<
		return ! empty( $this->mappings['activity-post'] ) ? $this->mappings['activity-post'] : [];

	}

	/**
	 * Get the WordPress Post Type for a CiviCRM Activity Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_id The numeric ID of the Activity Type.
	 * @return string|bool $cpt_name The name of the Post Type or false if none exists.
	 */
	public function mapping_for_activity_type_get( $activity_type_id ) {

		// Init as false.
		$cpt_name = false;

		// Overwrite if a mapping exists.
		if ( isset( $this->mappings['activity-post'][ $activity_type_id ] ) ) {
			$cpt_name = $this->mappings['activity-post'][ $activity_type_id ];
		}

		// --<
		return $cpt_name;

	}

	/**
	 * Add or update the link between a CiviCRM Activity Type and a WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_id The numeric ID of the Activity Type.
	 * @param string  $cpt_name The name of the WordPress Post Type.
	 */
	public function mapping_for_activity_type_update( $activity_type_id, $cpt_name ) {

		// Overwrite (or create) mapping.
		$this->mappings['activity-post'][ $activity_type_id ] = $cpt_name;

		// Update option.
		$this->option_set( $this->mappings_key, $this->mappings );

	}

	/**
	 * Maybe delete the link between a CiviCRM Activity Type and a WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_id The numeric ID of the Activity Type.
	 */
	public function mapping_for_activity_type_remove( $activity_type_id ) {

		// If a mapping exists.
		if ( isset( $this->mappings['activity-post'][ $activity_type_id ] ) ) {

			// We also need to remove the setting.
			$this->setting_remove( $this->mappings['activity-post'][ $activity_type_id ] );

			// Finally, remove mapping.
			unset( $this->mappings['activity-post'][ $activity_type_id ] );

		}

		// Update option.
		$this->option_set( $this->mappings_key, $this->mappings );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get all Participant Role to Post Type mappings.
	 *
	 * @since 0.5
	 *
	 * @return array $mappings The array of mappings.
	 */
	public function mappings_for_participant_roles_get() {

		// --<
		return ! empty( $this->mappings['participant-role-post'] ) ? $this->mappings['participant-role-post'] : [];

	}

	/**
	 * Get the WordPress Post Type for a CiviCRM Participant Role.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the Participant Role.
	 * @return string|bool $cpt_name The name of the Post Type or false if none exists.
	 */
	public function mapping_for_participant_role_get( $participant_role_id ) {

		// Init as false.
		$cpt_name = false;

		// Overwrite if a mapping exists.
		if ( isset( $this->mappings['participant-role-post'][ $participant_role_id ] ) ) {
			$cpt_name = $this->mappings['participant-role-post'][ $participant_role_id ];
		}

		// --<
		return $cpt_name;

	}

	/**
	 * Add or update the link between a CiviCRM Participant Role and a WordPress Post Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the Participant Role.
	 * @param string  $cpt_name The name of the WordPress Post Type.
	 */
	public function mapping_for_participant_role_update( $participant_role_id, $cpt_name ) {

		// Overwrite (or create) mapping.
		$this->mappings['participant-role-post'][ $participant_role_id ] = $cpt_name;

		// Update option.
		$this->option_set( $this->mappings_key, $this->mappings );

	}

	/**
	 * Maybe delete the link between a CiviCRM Participant Role and a WordPress Post Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the Participant Role.
	 */
	public function mapping_for_participant_role_remove( $participant_role_id ) {

		// If a mapping exists.
		if ( isset( $this->mappings['participant-role-post'][ $participant_role_id ] ) ) {

			// We also need to remove the setting.
			$this->setting_remove( $this->mappings['participant-role-post'][ $participant_role_id ] );

			// Finally, remove mapping.
			unset( $this->mappings['participant-role-post'][ $participant_role_id ] );

		}

		// Update option.
		$this->option_set( $this->mappings_key, $this->mappings );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get all mapped settings.
	 *
	 * @since 0.4
	 *
	 * @return array $settings The array of settings.
	 */
	public function settings_get() {

		// --<
		return $this->settings;

	}

	/**
	 * Delete the settings array.
	 *
	 * @since 0.4
	 */
	public function settings_delete() {

		// Delete the settings option.
		$this->option_delete( $this->settings_key );

	}

	/**
	 * Get the mapped settings for a WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of the Post Type.
	 * @return array|bool $setting The array of settings for the Post Type, or false if none exist.
	 */
	public function setting_get( $post_type ) {

		// Init as false.
		$setting = false;

		// Overwrite if a setting exists.
		if ( isset( $this->settings[ $post_type ] ) ) {
			$setting = $this->settings[ $post_type ];
		}

		// --<
		return $setting;

	}

	/**
	 * Add or update the mapped settings for a WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of the Post Type.
	 * @param array  $data The settings data for the Post Type.
	 */
	public function setting_update( $post_type, $data ) {

		// Overwrite (or create) setting.
		$this->settings[ $post_type ] = $data;

		// Update option.
		$this->option_set( $this->settings_key, $this->settings );

	}

	/**
	 * Maybe delete the mapped settings for a WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of the Post Type.
	 */
	public function setting_remove( $post_type ) {

		// If a setting exists, delete it.
		if ( isset( $this->settings[ $post_type ] ) ) {
			unset( $this->settings[ $post_type ] );
		}

		// Update option.
		$this->option_set( $this->settings_key, $this->settings );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Test existence of a specified option.
	 *
	 * @since 0.4
	 *
	 * @param string $option_name The name of the option.
	 * @return bool $exists Whether or not the option exists.
	 */
	public function option_exists( $option_name ) {

		// Test by getting option with unlikely default.
		if ( $this->option_get( $option_name, 'fenfgehgefdfdjgrkj' ) === 'fenfgehgefdfdjgrkj' ) {
			return false;
		} else {
			return true;
		}

	}

	/**
	 * Return a value for a specified option.
	 *
	 * @since 0.4
	 *
	 * @param string $option_name The name of the option.
	 * @param string $default The default value of the option if it has no value.
	 * @return mixed $value the value of the option.
	 */
	public function option_get( $option_name, $default = false ) {

		// Get option.
		$value = get_option( $option_name, $default );

		// --<
		return $value;

	}

	/**
	 * Set a value for a specified option.
	 *
	 * @since 0.4
	 *
	 * @param string $option_name The name of the option.
	 * @param mixed  $value The value to set the option to.
	 * @return bool $success True if the value of the option was successfully updated.
	 */
	public function option_set( $option_name, $value ) {

		// Update option.
		return update_option( $option_name, $value );

	}

	/**
	 * Delete a specified option.
	 *
	 * @since 0.4
	 *
	 * @param string $option_name The name of the option.
	 * @return bool $success True if the option was successfully deleted.
	 */
	public function option_delete( $option_name ) {

		// Delete option.
		return delete_option( $option_name );

	}

}
