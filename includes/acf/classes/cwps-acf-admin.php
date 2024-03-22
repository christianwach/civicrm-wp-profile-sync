<?php
/**
 * Admin Class.
 *
 * Handles ACF admin functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync ACF Admin Class
 *
 * A class that encapsulates ACF Admin functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Admin {

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
	 * The installed version of the plugin.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $plugin_version;

	/**
	 * Settings data.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $settings = [];

	/**
	 * Sync Page handle.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $sync_page;

	/**
	 * How many items to process per AJAX request.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $step_counts = [
		'contact_post_types'          => 5, // Number of Contact Posts per WordPress Post Type.
		'contact_types'               => 5, // Number of Contacts per CiviCRM Contact Type.
		'groups'                      => 10, // Number of Group Members per CiviCRM Group.
		'activity_post_types'         => 10, // Number of Activity Posts per WordPress Post Type.
		'activity_types'              => 10, // Number of Activities per CiviCRM Activity Type.
		'participant_role_post_types' => 10, // Number of Participant Role Posts per WordPress Post Type.
		'participant_roles'           => 10, // Number of Participants per CiviCRM Participant Role.
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
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Bail if WordPress Network Admin.
		if ( is_multisite() && is_network_admin() ) {
			return;
		}

		// Bail if not WordPress Admin.
		if ( ! is_admin() ) {
			return;
		}

		// Add our ACF Settings.
		add_filter( 'cwps/settings/defaults', [ $this, 'settings_acf_defaults' ], 10, 1 );
		add_action( 'cwps/admin/page/settings/meta_boxes/added', [ $this, 'settings_meta_boxes_add' ], 11, 1 );
		add_action( 'cwps/admin/settings/update/pre', [ $this, 'settings_acf_update' ] );

		// Return early when ACF Integration is disabled.
		$acf_enabled = (int) $this->plugin->admin->setting_get( 'acf_integration_enabled', 1 );
		if ( 1 !== $acf_enabled ) {
			return;
		}

		// Add menu item(s) to WordPress admin menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 30 );

		// Add our ACF Integration meta boxes.
		add_action( 'cwps/acf/admin/page/add_meta_boxes', [ $this, 'meta_boxes_sync_add' ], 11, 1 );

		// Add AJAX handlers.
		add_action( 'wp_ajax_sync_posts_to_contacts', [ $this, 'stepped_sync_posts_to_contacts' ] );
		add_action( 'wp_ajax_sync_contacts_to_posts', [ $this, 'stepped_sync_contacts_to_posts' ] );
		add_action( 'wp_ajax_sync_groups_to_terms', [ $this, 'stepped_sync_groups_to_terms' ] );
		add_action( 'wp_ajax_sync_posts_to_activities', [ $this, 'stepped_sync_posts_to_activities' ] );
		add_action( 'wp_ajax_sync_activities_to_posts', [ $this, 'stepped_sync_activities_to_posts' ] );
		// Register CPT hooks prior to Participant Role hooks.
		add_action( 'wp_ajax_sync_posts_to_participant_roles', [ $this, 'stepped_sync_posts_to_participants' ] );
		add_action( 'wp_ajax_sync_participant_roles_to_posts', [ $this, 'stepped_sync_participants_to_posts' ] );
		add_action( 'wp_ajax_sync_posts_to_participant_roles', [ $this, 'stepped_sync_posts_to_participant_roles' ] );
		add_action( 'wp_ajax_sync_participant_roles_to_posts', [ $this, 'stepped_sync_participant_roles_to_posts' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Adds the default ACF Integration settings.
	 *
	 * @since 0.6.1
	 *
	 * @param array $settings The existing default settings array.
	 * @return array $settings The modified default settings array.
	 */
	public function settings_acf_defaults( $settings ) {

		// Default "ACF Integration Enabled" to "on".
		$settings['acf_integration_enabled'] = 1;

		// --<
		return $settings;

	}

	/**
	 * Register settings meta boxes.
	 *
	 * @since 0.6.1
	 *
	 * @param string $screen_id The Admin Page Screen ID.
	 */
	public function settings_meta_boxes_add( $screen_id ) {

		// Create ACF Settings metabox.
		add_meta_box(
			'cwps_acf_integration',
			__( 'ACF Settings', 'civicrm-wp-profile-sync' ),
			[ $this, 'settings_meta_box_acf_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

	}

	/**
	 * Render ACF Settings meta box on Admin screen.
	 *
	 * @since 0.6.1
	 */
	public function settings_meta_box_acf_render() {

		// Get ACF Integration Enabled setting.
		$acf_enabled = (int) $this->plugin->admin->setting_get( 'acf_integration_enabled', 1 );

		// Init template vars.
		$acf_enabled_checked = ( 1 === $acf_enabled ) ? 1 : 0;

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-admin-settings-acf.php';

	}

	/**
	 * Updates the Integration Enabled setting value when saved on the Admin screen.
	 *
	 * @since 0.6.1
	 */
	public function settings_acf_update() {

		// Get ACF Integration Enabled setting. Nonce is checked in admin class.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$acf_enabled = ! empty( $_POST['cwps_acf_integration_checkbox'] ) ? 1 : 0;

		// Always set ACF Integration Enabled setting.
		$this->plugin->admin->setting_set( 'acf_integration_enabled', $acf_enabled );

	}

	// -------------------------------------------------------------------------

	/**
	 * Add our admin page(s) to the WordPress admin menu.
	 *
	 * @since 0.4
	 */
	public function admin_menu() {

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Add our "ACF Integration" page to the CiviCRM menu.
		$this->sync_page = add_submenu_page(
			'cwps_parent', // Parent slug.
			__( 'CiviCRM Profile Sync', 'civicrm-wp-profile-sync' ),
			__( 'ACF Integration', 'civicrm-wp-profile-sync' ),
			'manage_options',
			'cwps_acf_sync',
			[ $this, 'page_manual_sync' ]
		);

		// Ensure correct menu item is highlighted.
		add_action( 'admin_head-' . $this->sync_page, [ $this->plugin->admin, 'admin_menu_highlight' ], 50 );

		/*
		 * Add styles and scripts only on our "Manual Sync" page.
		 * @see wp-admin/admin-header.php
		 */
		add_action( 'admin_head-' . $this->sync_page, [ $this, 'admin_head' ] );
		add_action( 'admin_print_styles-' . $this->sync_page, [ $this, 'admin_styles' ] );
		add_action( 'admin_print_scripts-' . $this->sync_page, [ $this, 'admin_scripts' ] );

		// Filter the list of single site subpages and add multidomain page.
		add_filter( 'cwps/admin/settings/subpages', [ $this, 'admin_subpages_filter' ] );

		// Filter the list of single site page URLs and add multidomain page URL.
		add_filter( 'cwps/admin/settings/tab_urls', [ $this, 'page_tab_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'cwps/admin/settings/show_tabs', [ $this, 'page_show_tabs' ] );

		// Add tab to setting templates.
		add_action( 'cwps/admin/settings/nav_tabs', [ $this, 'page_add_tab' ], 10, 2 );

		// Try and update options.
		add_action( 'load-' . $this->sync_page, [ $this, 'settings_update_router' ], 50 );

	}

	/**
	 * Initialise plugin help.
	 *
	 * @since 0.4
	 */
	public function admin_head() {

		// Enqueue WordPress scripts.
		wp_enqueue_script( 'common' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'dashboard' );

	}

	/**
	 * Enqueue any styles needed by our Sync page.
	 *
	 * @since 0.4
	 */
	public function admin_styles() {

		// Enqueue CSS.
		wp_enqueue_style(
			'cwps-admin-style',
			CIVICRM_WP_PROFILE_SYNC_URL . 'assets/css/acf/pages/page-admin-acf-sync.css',
			null,
			CIVICRM_WP_PROFILE_SYNC_VERSION,
			'all' // Media.
		);

	}

	/**
	 * Enqueue any scripts needed by our Sync page.
	 *
	 * @since 0.4
	 */
	public function admin_scripts() {

		// Enqueue Javascript.
		wp_enqueue_script(
			'cwps-admin-script',
			CIVICRM_WP_PROFILE_SYNC_URL . 'assets/js/acf/pages/page-admin-acf-sync.js',
			[ 'jquery', 'jquery-ui-core', 'jquery-ui-progressbar' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION,
			true
		);

		// Get all Post Types mapped to Contacts.
		$mapped_contact_post_types = $this->acf_loader->post_type->get_mapped( 'contact' );

		// Loop through them and get the data we want.
		$contact_post_types = [];
		foreach ( $mapped_contact_post_types as $contact_post_type ) {
			$contact_post_types[ $contact_post_type->name ] = [
				'label' => esc_html( $contact_post_type->label ),
				'count' => $this->acf_loader->post_type->post_count( $contact_post_type->name ),
			];
		}

		// Get all mapped Contact Types.
		$mapped_contact_types = $this->acf_loader->civicrm->contact_type->get_mapped();

		// Loop through them and get the data we want.
		$contact_types = [];
		foreach ( $mapped_contact_types as $contact_type ) {
			$contact_types[ $contact_type['id'] ] = [
				'label' => esc_html( $contact_type['label'] ),
				'count' => $this->acf_loader->civicrm->contact_type->contact_count( $contact_type['id'] ),
			];
		}

		// Get all mapped Groups.
		$mapped_groups = $this->acf_loader->civicrm->group->groups_get_mapped();

		// Loop through them and get the data we want.
		$groups = [];
		foreach ( $mapped_groups as $group ) {
			$groups[ $group['id'] ] = [
				'label' => esc_html( $group['title'] ),
				'count' => $this->acf_loader->civicrm->group->group_contact_count( $group['id'] ),
			];
		}

		// Get all Post Types mapped to Activities.
		$mapped_activity_post_types = $this->acf_loader->post_type->get_mapped( 'activity' );

		// Loop through them and get the data we want.
		$activity_post_types = [];
		foreach ( $mapped_activity_post_types as $activity_post_type ) {
			$activity_post_types[ $activity_post_type->name ] = [
				'label' => esc_html( $activity_post_type->label ),
				'count' => $this->acf_loader->post_type->post_count( $activity_post_type->name ),
			];
		}

		// Get all mapped Activity Types.
		$mapped_activity_types = $this->acf_loader->civicrm->activity_type->get_mapped();

		// Loop through them and get the data we want.
		$activity_types = [];
		foreach ( $mapped_activity_types as $activity_type ) {
			$activity_types[ $activity_type['value'] ] = [
				'label' => esc_html( $activity_type['label'] ),
				'count' => $this->acf_loader->civicrm->activity_type->activity_count( $activity_type['value'] ),
			];
		}

		// Get all Post Types mapped to Participant Roles.
		$mapped_participant_post_types = $this->acf_loader->post_type->get_mapped( 'participant_role' );

		/**
		 * Filter the mapped Participant Role Post Types.
		 *
		 * @since 0.5
		 *
		 * @param $mapped_participant_post_types The mapped WordPress Post Types.
		 */
		$mapped_participant_post_types = apply_filters(
			'cwps/acf/admin/router/participant_role/post_types',
			$mapped_participant_post_types
		);

		// Loop through them and get the data we want.
		$participant_post_types = [];
		foreach ( $mapped_participant_post_types as $participant_post_type ) {
			$participant_post_types[ $participant_post_type->name ] = [
				'label' => esc_html( $participant_post_type->label ),
				'count' => $this->acf_loader->post_type->post_count( $participant_post_type->name ),
			];
		}

		// Get all mapped Participant Roles.
		$mapped_participant_roles = $this->acf_loader->civicrm->participant_role->get_mapped();

		/**
		 * Filter the mapped Participant Roles.
		 *
		 * @since 0.5
		 *
		 * @param $participant_roles The mapped CiviCRM Participant Roles.
		 */
		$mapped_participant_roles = apply_filters(
			'cwps/acf/admin/router/participant_role/roles',
			$mapped_participant_roles
		);

		// Loop through them and get the data we want.
		$participant_roles = [];
		foreach ( $mapped_participant_roles as $participant_role ) {
			if ( $participant_role['value'] == 'cpt' ) {
				$count = $this->acf_loader->civicrm->participant_role->participant_count();
			} else {
				$count = $this->acf_loader->civicrm->participant_role->participant_count( $participant_role['value'] );
			}
			$participant_roles[ $participant_role['value'] ] = [
				'label' => esc_html( $participant_role['label'] ),
				'count' => $count,
			];
		}

		// Init settings.
		$settings = [
			'ajax_url'                         => admin_url( 'admin-ajax.php' ),
			'contact_post_types'               => $contact_post_types,
			'contact_types'                    => $contact_types,
			'groups'                           => $groups,
			'activity_post_types'              => $activity_post_types,
			'activity_types'                   => $activity_types,
			'participant_post_types'           => $participant_post_types,
			'participant_roles'                => $participant_roles,
			'step_contact_post_types'          => $this->step_count_get( 'contact_post_types' ),
			'step_contact_types'               => $this->step_count_get( 'contact_types' ),
			'step_groups'                      => $this->step_count_get( 'groups' ),
			'step_activity_post_types'         => $this->step_count_get( 'activity_post_types' ),
			'step_activity_types'              => $this->step_count_get( 'activity_types' ),
			'step_participant_role_post_types' => $this->step_count_get( 'participant_role_post_types' ),
			'step_participant_roles'           => $this->step_count_get( 'participant_roles' ),
		];

		// Init localisation.
		$localisation = [];

		// Add Contact Post Types localisation.
		$localisation['contact_post_types'] = [
			'total'    => __( 'Posts to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current'  => __( 'Processing posts {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing posts {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count'    => count( $contact_post_types ),
		];

		// Add Contact Types localisation.
		$localisation['contact_types'] = [
			'total'    => __( 'Contacts to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current'  => __( 'Processing contacts {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing contacts {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count'    => count( $contact_types ),
		];

		// Add Groups localisation.
		$localisation['groups'] = [
			'total'    => __( 'Group members to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current'  => __( 'Processing group members {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing group members {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count'    => count( $groups ),
		];

		// Add Activity Post Types localisation.
		$localisation['activity_post_types'] = [
			'total'    => __( 'Posts to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current'  => __( 'Processing posts {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing posts {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count'    => count( $activity_post_types ),
		];

		// Add Activity Types localisation.
		$localisation['activity_types'] = [
			'total'    => __( 'Activities to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current'  => __( 'Processing activities {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing activities {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count'    => count( $activity_types ),
		];

		// Add Participant Role Post Types localisation.
		$localisation['participant_post_types'] = [
			'total'    => __( 'Posts to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current'  => __( 'Processing posts {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing posts {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count'    => count( $participant_post_types ),
		];

		// Add Participant Roles localisation.
		$localisation['participant_roles'] = [
			'total'    => __( 'Participants to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current'  => __( 'Processing participants {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing participants {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count'    => count( $participant_roles ),
		];

		// Add common localisation.
		$localisation['common'] = [
			'done' => __( 'All done!', 'civicrm-wp-profile-sync' ),
		];

		// Localisation array.
		$vars = [
			'settings'     => $settings,
			'localisation' => $localisation,
		];

		// Localise the WordPress way.
		wp_localize_script(
			'cwps-admin-script',
			'CiviCRM_Profile_Sync_ACF_Sync_Vars',
			$vars
		);

	}

	/**
	 * Append the "Manual Sync" page to Settings page.
	 *
	 * This ensures that the correct parent menu item is highlighted for our
	 * "Manual Sync" subpage.
	 *
	 * @since 0.4
	 *
	 * @param array $subpages The existing list of subpages.
	 * @return array $subpages The modified list of subpages.
	 */
	public function admin_subpages_filter( $subpages ) {

		// Add "Manual Sync" page.
		$subpages[] = 'cwps_acf_sync';

		// --<
		return $subpages;

	}

	/**
	 * Get the URL for the form action.
	 *
	 * @since 0.4
	 *
	 * @return string $target_url The URL for the admin form action.
	 */
	public function admin_form_url_get() {

		// Sanitise admin page url.
		// TODO: switch to menu_page_url() for URL.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$target_url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( ! empty( $target_url ) ) {
			$url_array = explode( '&', $target_url );
			if ( $url_array ) {
				$target_url = htmlentities( $url_array[0] . '&updated=true' );
			}
		}

		// --<
		return $target_url;

	}

	// -------------------------------------------------------------------------

	/**
	 * Show our "Manual Sync" page.
	 *
	 * @since 0.4
	 */
	public function page_manual_sync() {

		// We must be network admin in multisite.
		if ( is_multisite() && ! is_super_admin() ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'civicrm-wp-profile-sync' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'civicrm-wp-profile-sync' ) );
		}

		// Get current screen.
		$screen = get_current_screen();

		/**
		 * Allow meta boxes to be added to this screen.
		 *
		 * The Screen ID to use is: "civicrm_page_cwps_acf_sync".
		 *
		 * @since 0.4
		 *
		 * @param string $screen_id The ID of the current screen.
		 */
		do_action( 'cwps/acf/admin/page/add_meta_boxes', $screen->id, null );

		// Get the column CSS class.
		$columns     = (int) $screen->get_columns();
		$columns_css = '';
		if ( $columns ) {
			$columns_css = " columns-$columns";
		}

		// Get admin page URLs.
		$urls = $this->plugin->admin->page_tab_urls_get();

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/pages/page-admin-acf-sync.php';

	}

	/**
	 * Append the Manual Sync settings page URL to the subpage URLs.
	 *
	 * @since 0.4
	 *
	 * @param array $urls The existing list of URLs.
	 * @return array $urls The modified list of URLs.
	 */
	public function page_tab_urls_filter( $urls ) {

		// Add multidomain settings page.
		$urls['manual-sync'] = menu_page_url( 'cwps_acf_sync', false );

		// --<
		return $urls;

	}

	/**
	 * Show subpage tabs on settings pages.
	 *
	 * @since 0.4
	 *
	 * @param bool $show_tabs True if tabs are shown, false otherwise.
	 * @return bool $show_tabs True if tabs are to be shown, false otherwise.
	 */
	public function page_show_tabs( $show_tabs ) {

		// Always show tabs.
		$show_tabs = true;

		// --<
		return $show_tabs;

	}

	/**
	 * Add subpage tab to tabs on settings pages.
	 *
	 * @since 0.4
	 *
	 * @param array  $urls The array of subpage URLs.
	 * @param string $active_tab The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'ACF Integration', 'civicrm-wp-profile-sync' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( $active_tab === 'manual-sync' ) {
			$active = ' nav-tab-active';
		}

		// Render tab.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '<a href="' . $urls['manual-sync'] . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $title ) . '</a>' . "\n";

	}

	// -------------------------------------------------------------------------

	/**
	 * Register sync meta boxes.
	 *
	 * @since 0.4
	 *
	 * @param string $screen_id The Admin Page Screen ID.
	 */
	public function meta_boxes_sync_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_cwps_acf_sync',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids, true ) ) {
			return;
		}

		// Bail if user cannot access CiviCRM.
		if ( ! current_user_can( 'access_civicrm' ) ) {
			return;
		}

		// Create WordPress Posts to CiviCRM Contacts metabox.
		add_meta_box(
			'cwps_acf_post_contact',
			__( 'WordPress Posts &rarr; CiviCRM Contacts', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_post_contact_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Closed by default.
		add_filter( "postbox_classes_{$screen_id}_cwps_acf_post_contact", [ $this, 'meta_box_closed' ] );

		// Create CiviCRM Contacts to WordPress Posts metabox.
		add_meta_box(
			'cwps_acf_contact_post',
			__( 'CiviCRM Contacts &rarr; WordPress Posts', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_contact_post_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Closed by default.
		add_filter( "postbox_classes_{$screen_id}_cwps_acf_contact_post", [ $this, 'meta_box_closed' ] );

		// Create WordPress Posts to CiviCRM Activities metabox.
		add_meta_box(
			'cwps_acf_post_activity',
			__( 'WordPress Posts &rarr; CiviCRM Activities', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_post_activity_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Closed by default.
		add_filter( "postbox_classes_{$screen_id}_cwps_acf_post_activity", [ $this, 'meta_box_closed' ] );

		// Create CiviCRM Activities to WordPress Posts metabox.
		add_meta_box(
			'cwps_acf_activity_post',
			__( 'CiviCRM Activities &rarr; WordPress Posts', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_activity_post_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Closed by default.
		add_filter( "postbox_classes_{$screen_id}_cwps_acf_activity_post", [ $this, 'meta_box_closed' ] );

		// Create WordPress Posts to CiviCRM Participant Roles metabox.
		add_meta_box(
			'cwps_acf_post_participant_role',
			__( 'WordPress Posts &rarr; CiviCRM Participants', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_post_participant_role_render' ], // Callback.
			$screen_id, // Screen ID.
			'side', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Closed by default.
		add_filter( "postbox_classes_{$screen_id}_cwps_acf_post_participant_role", [ $this, 'meta_box_closed' ] );

		// Create CiviCRM Participants to WordPress Posts metabox.
		add_meta_box(
			'cwps_acf_participant_role_post',
			__( 'CiviCRM Participants &rarr; WordPress Posts', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_participant_role_post_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Closed by default.
		add_filter( "postbox_classes_{$screen_id}_cwps_acf_participant_role_post", [ $this, 'meta_box_closed' ] );

		// Create CiviCRM Groups to WordPress Terms metabox.
		add_meta_box(
			'cwps_acf_group_term',
			__( 'CiviCRM Groups &rarr; WordPress Terms', 'civicrm-wp-profile-sync' ),
			[ $this, 'meta_box_group_term_render' ], // Callback.
			$screen_id, // Screen ID.
			'normal', // Column: options are 'normal' and 'side'.
			'core' // Vertical placement: options are 'core', 'high', 'low'.
		);

		// Closed by default.
		add_filter( "postbox_classes_{$screen_id}_cwps_acf_group_term", [ $this, 'meta_box_closed' ] );

	}

	/**
	 * Load our meta boxes as closed by default.
	 *
	 * @since 0.4
	 *
	 * @param string[] $classes An array of postbox classes.
	 */
	public function meta_box_closed( $classes ) {

		// Add closed class.
		if ( is_array( $classes ) ) {
			if ( ! in_array( 'closed', $classes, true ) ) {
				$classes[] = 'closed';
			}
		}

		// --<
		return $classes;

	}

	/**
	 * Render WordPress Posts to CiviCRM Contacts meta box.
	 *
	 * @since 0.4
	 */
	public function meta_box_post_contact_render() {

		// Get all Post Types mapped to Contacts.
		$mapped_contact_post_types = $this->acf_loader->post_type->get_mapped( 'contact' );

		// Loop through them and get the data we want.
		$contact_post_types = [];
		foreach ( $mapped_contact_post_types as $contact_post_type ) {
			$contact_post_types[ $contact_post_type->name ] = $contact_post_type->label;
		}

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-posts-contacts.php';

	}

	/**
	 * Render CiviCRM Contacts to WordPress Posts meta box.
	 *
	 * @since 0.4
	 */
	public function meta_box_contact_post_render() {

		// Get all mapped Contact Types.
		$mapped_contact_types = $this->acf_loader->civicrm->contact_type->get_mapped();

		// Loop through them and get the data we want.
		$contact_types = [];
		foreach ( $mapped_contact_types as $contact_type ) {
			$contact_types[ $contact_type['id'] ] = $contact_type['label'];
		}

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-contacts-posts.php';

	}

	/**
	 * Render WordPress Posts to CiviCRM Activities meta box.
	 *
	 * @since 0.4
	 */
	public function meta_box_post_activity_render() {

		// Get all Post Types mapped to Activities.
		$mapped_activity_post_types = $this->acf_loader->post_type->get_mapped( 'activity' );

		// Loop through them and get the data we want.
		$activity_post_types = [];
		foreach ( $mapped_activity_post_types as $activity_post_type ) {
			$activity_post_types[ $activity_post_type->name ] = $activity_post_type->label;
		}

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-posts-activities.php';

	}

	/**
	 * Render CiviCRM Activities to WordPress Posts meta box.
	 *
	 * @since 0.4
	 */
	public function meta_box_activity_post_render() {

		// Get all mapped Activity Types.
		$mapped_activity_types = $this->acf_loader->civicrm->activity_type->get_mapped();

		// Loop through them and get the data we want.
		$activity_types = [];
		foreach ( $mapped_activity_types as $activity_type ) {
			$activity_types[ $activity_type['value'] ] = $activity_type['label'];
		}

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-activities-posts.php';

	}

	/**
	 * Render WordPress Posts to CiviCRM Participants meta box.
	 *
	 * @since 0.4
	 */
	public function meta_box_post_participant_role_render() {

		// Get all Post Types mapped to Participant Roles.
		$mapped_participant_role_post_types = $this->acf_loader->post_type->get_mapped( 'participant_role' );

		// Loop through them and get the data we want.
		$post_types = [];
		foreach ( $mapped_participant_role_post_types as $participant_role_post_type ) {
			$post_types[ $participant_role_post_type->name ] = $participant_role_post_type->label;
		}

		/**
		 * Filter the mapped Participant Role Post Types.
		 *
		 * @since 0.5
		 *
		 * @param $post_types The mapped WordPress Post Types.
		 */
		$participant_role_post_types = apply_filters( 'cwps/acf/admin/participant_role/post_types', $post_types );

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-posts-participants.php';

	}

	/**
	 * Render CiviCRM Participant Roles to WordPress Posts meta box.
	 *
	 * @since 0.4
	 */
	public function meta_box_participant_role_post_render() {

		// Get all mapped Participant Roles.
		$mapped_participant_roles = $this->acf_loader->civicrm->participant_role->get_mapped();

		// Loop through them and get the data we want.
		$participant_roles = [];
		foreach ( $mapped_participant_roles as $participant_role ) {
			$participant_roles[ $participant_role['value'] ] = $participant_role['label'];
		}

		/**
		 * Filter the mapped Participant Roles.
		 *
		 * @since 0.5
		 *
		 * @param $participant_roles The mapped CiviCRM Participant Roles.
		 */
		$participant_roles = apply_filters( 'cwps/acf/admin/participant_role/roles', $participant_roles );

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-participants-posts.php';

	}

	/**
	 * Render CiviCRM Groups to WordPress Terms meta box.
	 *
	 * @since 0.4
	 */
	public function meta_box_group_term_render() {

		// Get all mapped Groups.
		$mapped_groups = $this->acf_loader->civicrm->group->groups_get_mapped();

		// Loop through them and get the data we want.
		$groups = [];
		foreach ( $mapped_groups as $group ) {
			$groups[ $group['id'] ] = $group['title'];
		}

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-groups-terms.php';

	}

	// -------------------------------------------------------------------------

	/**
	 * Route settings updates to relevant methods.
	 *
	 * @since 0.4
	 */
	public function settings_update_router() {

		// Get all Post Types mapped to Contacts.
		$mapped_contact_post_types = $this->acf_loader->post_type->get_mapped( 'contact' );

		// Loop through them and get the data we want.
		$contact_post_types = [];
		foreach ( $mapped_contact_post_types as $contact_post_type ) {
			$contact_post_types[ $contact_post_type->name ] = 'cwps_acf_post_to_contact_' . $contact_post_type->name . '_stop';
		}

		// Get all mapped Contact Types.
		$mapped_contact_types = $this->acf_loader->civicrm->contact_type->get_mapped();

		// Loop through them and get the data we want.
		$contact_types = [];
		foreach ( $mapped_contact_types as $contact_type ) {
			$contact_types[ $contact_type['id'] ] = 'cwps_acf_contact_to_post_' . $contact_type['id'] . '_stop';
		}

		// Get all mapped Groups.
		$mapped_groups = $this->acf_loader->civicrm->group->groups_get_mapped();

		// Loop through them and get the data we want.
		$groups = [];
		foreach ( $mapped_groups as $group ) {
			$groups[ $group['id'] ] = 'cwps_acf_group_to_term_' . $group['id'] . '_stop';
		}

		// Get all Post Types mapped to Activities.
		$mapped_activity_post_types = $this->acf_loader->post_type->get_mapped( 'activity' );

		// Loop through them and get the data we want.
		$activity_post_types = [];
		foreach ( $mapped_activity_post_types as $activity_post_type ) {
			$activity_post_types[ $activity_post_type->name ] = 'cwps_acf_post_to_activity_' . $activity_post_type->name . '_stop';
		}

		// Get all mapped Activity Types.
		$mapped_activity_types = $this->acf_loader->civicrm->activity_type->get_mapped();

		// Loop through them and get the data we want.
		$activity_types = [];
		foreach ( $mapped_activity_types as $activity_type ) {
			$activity_types[ $activity_type['id'] ] = 'cwps_acf_activity_to_post_' . $activity_type['id'] . '_stop';
		}

		// Get all Post Types mapped to Participant Roles.
		$mapped_participant_role_post_types = $this->acf_loader->post_type->get_mapped( 'participant_role' );

		/**
		 * Filter the mapped Participant Role Post Types.
		 *
		 * @since 0.5
		 *
		 * @param $post_types The mapped WordPress Post Types.
		 */
		$mapped_participant_role_post_types = apply_filters(
			'cwps/acf/admin/router/participant_role/post_types',
			$mapped_participant_role_post_types
		);

		// Loop through them and get the data we want.
		$participant_role_post_types = [];
		foreach ( $mapped_participant_role_post_types as $participant_role_post_type ) {
			$participant_role_post_types[ $participant_role_post_type->name ] = 'cwps_acf_post_to_participant_role_' . $participant_role_post_type->name . '_stop';
		}

		// Get all mapped Participant Roles.
		$mapped_participant_roles = $this->acf_loader->civicrm->participant_role->get_mapped();

		/**
		 * Filter the mapped Participant Roles.
		 *
		 * @since 0.5
		 *
		 * @param $participant_roles The mapped CiviCRM Participant Roles.
		 */
		$mapped_participant_roles = apply_filters(
			'cwps/acf/admin/router/participant_role/roles',
			$mapped_participant_roles
		);

		// Loop through them and get the data we want.
		$participant_roles = [];
		foreach ( $mapped_participant_roles as $participant_role ) {
			$participant_roles[ $participant_role['value'] ] = 'cwps_acf_participant_role_to_post_' . $participant_role['value'] . '_stop';
		}

		// Init stop, continue and sync flags.
		$stop      = false;
		$continue  = false;
		$sync_type = false;
		$entity_id = false;

		// Find out if a Contact Post Type button has been pressed.
		foreach ( $contact_post_types as $contact_post_type => $stop_code ) {

			// Define replacements.
			$replacements = [ 'cwps_acf_post_to_contact_', '_stop' ];

			// Was a "Stop Sync" button pressed?
			if ( isset( $_POST[ $stop_code ] ) ) {
				$stop      = $stop_code;
				$sync_type = 'contact_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

			// Was a "Sync Now" or "Continue Sync" button pressed?
			$button = str_replace( '_stop', '', $stop_code );
			if ( isset( $_POST[ $button ] ) ) {
				$continue  = $button;
				$sync_type = 'contact_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

		}

		// Find out if a Contact Type button has been pressed.
		if ( $stop === false && $continue === false ) {
			foreach ( $contact_types as $contact_type_id => $stop_code ) {

				// Define replacements.
				$replacements = [ 'cwps_acf_contact_to_post_', '_stop' ];

				// Was a "Stop Sync" button pressed?
				if ( isset( $_POST[ $stop_code ] ) ) {
					$stop      = $stop_code;
					$sync_type = 'contact_type';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

				// Was a "Sync Now" or "Continue Sync" button pressed?
				$button = str_replace( '_stop', '', $stop_code );
				if ( isset( $_POST[ $button ] ) ) {
					$continue  = $button;
					$sync_type = 'contact_type';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

			}
		}

		// Find out if a Group "Stop Sync" button has been pressed.
		if ( $stop === false ) {
			foreach ( $groups as $group_id => $stop_code ) {

				// Define replacements.
				$replacements = [ 'cwps_acf_group_to_term_', '_stop' ];

				// Was a "Stop Sync" button pressed?
				if ( isset( $_POST[ $stop_code ] ) ) {
					$stop      = $stop_code;
					$sync_type = 'group';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

				// Was a "Sync Now" or "Continue Sync" button pressed?
				$button = str_replace( '_stop', '', $stop_code );
				if ( isset( $_POST[ $button ] ) ) {
					$continue  = $button;
					$sync_type = 'group';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

			}
		}

		// Find out if an Activity Post Type button has been pressed.
		foreach ( $activity_post_types as $activity_post_type => $stop_code ) {

			// Define replacements.
			$replacements = [ 'cwps_acf_post_to_activity_', '_stop' ];

			// Was a "Stop Sync" button pressed?
			if ( isset( $_POST[ $stop_code ] ) ) {
				$stop      = $stop_code;
				$sync_type = 'activity_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

			// Was a "Sync Now" or "Continue Sync" button pressed?
			$button = str_replace( '_stop', '', $stop_code );
			if ( isset( $_POST[ $button ] ) ) {
				$continue  = $button;
				$sync_type = 'activity_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

		}

		// Find out if an Activity Type button has been pressed.
		if ( $stop === false && $continue === false ) {
			foreach ( $activity_types as $activity_type_id => $stop_code ) {

				// Define replacements.
				$replacements = [ 'cwps_acf_activity_to_post_', '_stop' ];

				// Was a "Stop Sync" button pressed?
				if ( isset( $_POST[ $stop_code ] ) ) {
					$stop      = $stop_code;
					$sync_type = 'activity_type';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

				// Was a "Sync Now" or "Continue Sync" button pressed?
				$button = str_replace( '_stop', '', $stop_code );
				if ( isset( $_POST[ $button ] ) ) {
					$continue  = $button;
					$sync_type = 'activity_type';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

			}
		}

		// Find out if a Participant Role Post Type button has been pressed.
		foreach ( $participant_role_post_types as $participant_role_post_type => $stop_code ) {

			// Define replacements.
			$replacements = [ 'cwps_acf_post_to_participant_role_', '_stop' ];

			// Was a "Stop Sync" button pressed?
			if ( isset( $_POST[ $stop_code ] ) ) {
				$stop      = $stop_code;
				$sync_type = 'participant_role_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

			// Was a "Sync Now" or "Continue Sync" button pressed?
			$button = str_replace( '_stop', '', $stop_code );
			if ( isset( $_POST[ $button ] ) ) {
				$continue  = $button;
				$sync_type = 'participant_role_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

		}

		// Find out if a Participant Role button has been pressed.
		if ( $stop === false && $continue === false ) {
			foreach ( $participant_roles as $participant_role_id => $stop_code ) {

				// Define replacements.
				$replacements = [ 'cwps_acf_participant_role_to_post_', '_stop' ];

				// Was a "Stop Sync" button pressed?
				if ( isset( $_POST[ $stop_code ] ) ) {
					$stop      = $stop_code;
					$sync_type = 'participant_role';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

				// Was a "Sync Now" or "Continue Sync" button pressed?
				$button = str_replace( '_stop', '', $stop_code );
				if ( isset( $_POST[ $button ] ) ) {
					$continue  = $button;
					$sync_type = 'participant_role';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

			}
		}

		// Bail if no button was pressed.
		if ( empty( $stop ) && empty( $continue ) ) {
			return;
		}

		// Check that we trust the source of the data.
		check_admin_referer( 'cwps_acf_sync_action', 'cwps_acf_sync_nonce' );

		// Was a "Stop Sync" button pressed?
		if ( ! empty( $stop ) ) {

			// Define slugs.
			$slugs = [
				'contact_post_type'          => 'post_to_contact_',
				'contact_type'               => 'contact_to_post_',
				'group'                      => 'group_to_term_',
				'activity_post_type'         => 'post_to_activity_',
				'activity_type'              => 'activity_to_post_',
				'participant_role_post_type' => 'post_to_participant_role_',
				'participant_role'           => 'participant_role_to_post_',
			];

			// Build key.
			$key = $slugs[ $sync_type ] . $entity_id;

			// Clear offset and bail.
			$this->stepped_offset_delete( $key );
			return;

		}

		// Bail if there's no sync type.
		if ( empty( $sync_type ) ) {
			return;
		}

		// Was a Contact Post Type "Sync Now" button pressed?
		if ( $sync_type == 'contact_post_type' ) {
			$this->stepped_sync_posts_to_contacts( $entity_id );
		}

		// Was a Contact Type "Sync Now" button pressed?
		if ( $sync_type == 'contact_type' ) {
			$this->stepped_sync_contacts_to_posts( $entity_id );
		}

		// Was a Group "Sync Now" button pressed?
		if ( $sync_type == 'group' ) {
			$this->stepped_sync_groups_to_terms( $entity_id );
		}

		// Was an Activity Post Type "Sync Now" button pressed?
		if ( $sync_type == 'activity_post_type' ) {
			$this->stepped_sync_posts_to_activities( $entity_id );
		}

		// Was an Activity Type "Sync Now" button pressed?
		if ( $sync_type == 'activity_type' ) {
			$this->stepped_sync_activities_to_posts( $entity_id );
		}

		// Was a Participant Role Post Type "Sync Now" button pressed?
		if ( $sync_type == 'participant_role_post_type' ) {
			if ( $entity_id == 'participant' ) {
				$this->stepped_sync_posts_to_participants( $entity_id );
			} else {
				$this->stepped_sync_posts_to_participant_roles( $entity_id );
			}
		}

		// Was a Participant Role "Sync Now" button pressed?
		if ( $sync_type == 'participant_role' ) {
			if ( $entity_id == 'cpt' ) {
				$this->stepped_sync_participants_to_posts( $entity_id );
			} else {
				$this->stepped_sync_participant_roles_to_posts( $entity_id );
			}
		}

	}

	/**
	 * Stepped synchronisation of WordPress Posts to CiviCRM Contacts.
	 *
	 * @since 0.4
	 *
	 * @param string $entity The identifier for the entity - here it's Post ID.
	 */
	public function stepped_sync_posts_to_contacts( $entity = null ) {

		// Get all mapped Post Types.
		$mapped_contact_post_types = $this->acf_loader->post_type->get_mapped( 'contact' );

		// Loop through them and get the data we want.
		$contact_post_types = [];
		foreach ( $mapped_contact_post_types as $contact_post_type ) {
			$contact_post_types[] = $contact_post_type->name;
		}

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$contact_post_type = empty( $entity ) ? '' : $entity;
		} else {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$contact_post_type = isset( $_POST['entity_id'] ) ? trim( wp_unslash( $_POST['entity_id'] ) ) : '';
			$contact_post_type = sanitize_text_field( $contact_post_type );
		}

		// Sanity check input.
		if ( ! in_array( $contact_post_type, $contact_post_types, true ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Build key.
		$key = 'post_to_contact_' . $contact_post_type;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $contact_post_type === '' || $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Construct args.
		$args = [
			'post_type'     => $contact_post_type,
			'no_found_rows' => true,
			'numberposts'   => $this->step_count_get( 'contact_post_types' ),
			'offset'        => $offset,
		];

		// Get all posts.
		$posts = get_posts( $args );

		// If we get results.
		if ( count( $posts ) > 0 ) {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there less items than the step count?
			if ( count( $posts ) < $this->step_count_get( 'contact_post_types' ) ) {
				$diff = count( $posts );
			} else {
				$diff = $this->step_count_get( 'contact_post_types' );
			}

			// Set from and to flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove CiviCRM callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_civicrm_remove();

			// Sync each Post in turn.
			foreach ( $posts as $post ) {

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
					'post'    => $post,
					'update'  => true,
				];

				/**
				 * Broadcast that the Post must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update a CiviCRM Contact
				 * * Update the CiviCRM Custom Fields
				 * * Update the CiviCRM Group memberships
				 *
				 * @since 0.4
				 *
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/admin/post-to-contact/sync', $args );

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
				];

				/**
				 * Broadcast that the ACF Fields must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update the CiviCRM Custom Fields
				 *
				 * @since 0.4
				 *
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/admin/post-to-contact/acf_fields/sync', $args );

			}

			// Reinstate CiviCRM callbacks.
			$this->acf_loader->mapper->hooks_civicrm_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		} else {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Stepped synchronisation of CiviCRM Contacts to WordPress Posts.
	 *
	 * @since 0.4
	 *
	 * @param string $entity The identifier for the entity - here it's Contact Type ID.
	 */
	public function stepped_sync_contacts_to_posts( $entity = null ) {

		// Init AJAX return.
		$data = [];

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$contact_type_id = is_numeric( $entity ) ? (int) $entity : 0;
		} else {
			$contact_type_id = isset( $_POST['entity_id'] ) ? (int) $_POST['entity_id'] : 0;
		}

		// Build key.
		$key = 'contact_to_post_' . $contact_type_id;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $contact_type_id === 0 || $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Init query result.
		$result = [];

		// Init CiviCRM.
		if ( $this->acf_loader->civicrm->is_initialised() ) {

			// Get the Contact data.
			$result = $this->acf_loader->civicrm->contact->contacts_chunked_data_get(
				$contact_type_id,
				$offset,
				$this->step_count_get( 'contact_types' )
			);

		} else {

			// Do not allow progress.
			$result['is_error'] = 1;

		}

		// Did we get an error?
		$error = false;
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$error = true;
		}

		// Finish sync on failure or empty result.
		if ( $error || empty( $result['values'] ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		} else {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there fewer items than the step count?
			if ( count( $result['values'] ) < $this->step_count_get( 'contact_types' ) ) {
				$diff = count( $result['values'] );
			} else {
				$diff = $this->step_count_get( 'contact_types' );
			}

			// Set "from" and "to" flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove WordPress callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_remove();

			// Trigger sync for each Contact in turn.
			foreach ( $result['values'] as $contact ) {

				// Let's make an array of params.
				$args = [
					'op'         => 'sync',
					'objectName' => $contact['contact_type'],
					'objectId'   => $contact['contact_id'],
					'objectRef'  => (object) $contact,
				];

				/**
				 * Broadcast that the Contact must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update a WordPress Post
				 * * Update the WordPress Terms
				 *
				 * @since 0.4
				 *
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/admin/contact-to-post/sync', $args );

			}

			// Reinstate WordPress callbacks.
			$this->acf_loader->mapper->hooks_wordpress_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Stepped synchronisation of WordPress Posts to CiviCRM Activities.
	 *
	 * @since 0.4
	 *
	 * @param string $entity The identifier for the entity - here it's Post ID.
	 */
	public function stepped_sync_posts_to_activities( $entity = null ) {

		// Get all mapped Post Types.
		$mapped_activity_post_types = $this->acf_loader->post_type->get_mapped( 'activity' );

		// Loop through them and get the data we want.
		$activity_post_types = [];
		foreach ( $mapped_activity_post_types as $activity_post_type ) {
			$activity_post_types[] = $activity_post_type->name;
		}

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$activity_post_type = empty( $entity ) ? '' : $entity;
		} else {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$activity_post_type = isset( $_POST['entity_id'] ) ? trim( wp_unslash( $_POST['entity_id'] ) ) : '';
			$activity_post_type = sanitize_text_field( $activity_post_type );
		}

		// Sanity check input.
		if ( ! in_array( $activity_post_type, $activity_post_types, true ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Build key.
		$key = 'post_to_activity_' . $activity_post_type;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $activity_post_type === '' || $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Construct args.
		$args = [
			'post_type'     => $activity_post_type,
			'no_found_rows' => true,
			'numberposts'   => $this->step_count_get( 'activity_post_types' ),
			'offset'        => $offset,
		];

		// Get all posts.
		$posts = get_posts( $args );

		// If we get results.
		if ( count( $posts ) > 0 ) {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there less items than the step count?
			if ( count( $posts ) < $this->step_count_get( 'activity_post_types' ) ) {
				$diff = count( $posts );
			} else {
				$diff = $this->step_count_get( 'activity_post_types' );
			}

			// Set from and to flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove CiviCRM callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_civicrm_remove();

			// Sync each Post in turn.
			foreach ( $posts as $post ) {

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
					'post'    => $post,
					'update'  => true,
				];

				/**
				 * Broadcast that the Post must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update a CiviCRM Activity
				 * * Update the CiviCRM Custom Fields
				 *
				 * @since 0.4
				 *
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/admin/post-to-activity/sync', $args );

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
				];

				/**
				 * Broadcast that the ACF Fields must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update the CiviCRM Custom Fields
				 *
				 * @since 0.4
				 *
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/admin/post-to-activity/acf_fields/sync', $args );

			}

			// Reinstate CiviCRM callbacks.
			$this->acf_loader->mapper->hooks_civicrm_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		} else {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Stepped synchronisation of CiviCRM Activities to WordPress Posts.
	 *
	 * @since 0.4
	 *
	 * @param string $entity The identifier for the Entity - here it's Activity Type ID.
	 */
	public function stepped_sync_activities_to_posts( $entity = null ) {

		// Init AJAX return.
		$data = [];

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$activity_type_id = is_numeric( $entity ) ? (int) $entity : 0;
		} else {
			$activity_type_id = isset( $_POST['entity_id'] ) ? (int) $_POST['entity_id'] : 0;
		}

		// Build key.
		$key = 'activity_to_post_' . $activity_type_id;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $activity_type_id === 0 || $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Init query result.
		$result = [];

		// Init CiviCRM.
		if ( $this->acf_loader->civicrm->is_initialised() ) {

			// Get the Activity data.
			$result = $this->acf_loader->civicrm->activity->activities_chunked_data_get(
				$activity_type_id,
				$offset,
				$this->step_count_get( 'activity_types' )
			);

		} else {

			// Do not allow progress.
			$result['is_error'] = 1;

		}

		// Did we get an error?
		$error = false;
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$error = true;
		}

		// Finish sync on failure or empty result.
		if ( $error || empty( $result['values'] ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		} else {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there fewer items than the step count?
			if ( count( $result['values'] ) < $this->step_count_get( 'activity_types' ) ) {
				$diff = count( $result['values'] );
			} else {
				$diff = $this->step_count_get( 'activity_types' );
			}

			// Set "from" and "to" flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove WordPress callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_remove();

			// Trigger sync for each Activity in turn.
			foreach ( $result['values'] as $activity ) {

				// Let's make an array of params.
				$args = [
					'op'         => 'sync',
					'objectName' => 'Activity',
					'objectId'   => $activity['id'],
					'objectRef'  => (object) $activity,
				];

				/**
				 * Broadcast that the Activity must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update a WordPress Post
				 *
				 * @since 0.4
				 *
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/admin/activity-to-post/sync', $args );

			}

			// Reinstate WordPress callbacks.
			$this->acf_loader->mapper->hooks_wordpress_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Stepped synchronisation of WordPress Posts to CiviCRM Participants.
	 *
	 * @since 0.5
	 *
	 * @param string $entity The identifier for the entity - here it's Post ID.
	 */
	public function stepped_sync_posts_to_participant_roles( $entity = null ) {

		// Get all mapped Post Types.
		$mapped_participant_post_types = $this->acf_loader->post_type->get_mapped( 'participant_role' );

		// Loop through them and get the data we want.
		$participant_post_types = [];
		foreach ( $mapped_participant_post_types as $participant_post_type ) {
			$participant_post_types[] = $participant_post_type->name;
		}

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$participant_post_type = empty( $entity ) ? '' : $entity;
		} else {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$participant_post_type = isset( $_POST['entity_id'] ) ? trim( wp_unslash( $_POST['entity_id'] ) ) : '';
			$participant_post_type = sanitize_text_field( $participant_post_type );
		}

		// If "participant", then bail.
		if ( $participant_post_type == 'participant' ) {
			return;
		}

		// Sanity check input.
		if ( ! in_array( $participant_post_type, $participant_post_types, true ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Build key.
		$key = 'post_to_participant_role_' . $participant_post_type;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $participant_post_type === '' || $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Construct args.
		$args = [
			'post_type'     => $participant_post_type,
			'no_found_rows' => true,
			'numberposts'   => $this->step_count_get( 'participant_role_post_types' ),
			'offset'        => $offset,
		];

		// Get all posts.
		$posts = get_posts( $args );

		// If we get results.
		if ( count( $posts ) > 0 ) {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there less items than the step count?
			if ( count( $posts ) < $this->step_count_get( 'participant_role_post_types' ) ) {
				$diff = count( $posts );
			} else {
				$diff = $this->step_count_get( 'participant_role_post_types' );
			}

			// Set from and to flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove CiviCRM callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_civicrm_remove();

			// Sync each Post in turn.
			foreach ( $posts as $post ) {

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
					'post'    => $post,
					'update'  => true,
				];

				/**
				 * Broadcast that the Post must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update a CiviCRM Participant
				 * * Update the CiviCRM Custom Fields
				 *
				 * @since 0.5
				 *
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/admin/post-to-participant-role/sync', $args );

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
				];

				/**
				 * Broadcast that the ACF Fields must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update the CiviCRM Custom Fields
				 *
				 * @since 0.5
				 *
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/admin/post-to-participant-role/acf_fields/sync', $args );

			}

			// Reinstate CiviCRM callbacks.
			$this->acf_loader->mapper->hooks_civicrm_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		} else {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Stepped synchronisation of CiviCRM Participants to WordPress Posts.
	 *
	 * @since 0.5
	 *
	 * @param string $entity The identifier for the Entity - here it's Participant ID.
	 */
	public function stepped_sync_participant_roles_to_posts( $entity = null ) {

		// Init AJAX return.
		$data = [];

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$participant_role_id = is_numeric( $entity ) ? $entity : 0;
		} else {
			$participant_role_id = isset( $_POST['entity_id'] ) ? sanitize_text_field( wp_unslash( $_POST['entity_id'] ) ) : 0;
		}

		// If "cpt", then bail.
		if ( $participant_role_id == 'cpt' ) {
			return;
		}

		// Build key.
		$key = 'participant_role_to_post_' . $participant_role_id;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $participant_role_id === 0 || $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Init query result.
		$result = [];

		// Init CiviCRM.
		if ( $this->acf_loader->civicrm->is_initialised() ) {

			// Get the Participant data.
			$result = $this->acf_loader->civicrm->participant->participants_by_role_chunked_data_get(
				$participant_role_id,
				$offset,
				$this->step_count_get( 'participant_roles' )
			);

		} else {

			// Do not allow progress.
			$result['is_error'] = 1;

		}

		// Did we get an error?
		$error = false;
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$error = true;
		}

		// Finish sync on failure or empty result.
		if ( $error || empty( $result['values'] ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		} else {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there fewer items than the step count?
			if ( count( $result['values'] ) < $this->step_count_get( 'participant_roles' ) ) {
				$diff = count( $result['values'] );
			} else {
				$diff = $this->step_count_get( 'participant_roles' );
			}

			// Set "from" and "to" flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove WordPress callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_remove();

			// Trigger sync for each Participant in turn.
			foreach ( $result['values'] as $participant ) {

				// Let's make an array of params.
				$args = [
					'op'         => 'sync',
					'objectName' => 'Participant',
					'objectId'   => $participant['id'],
					'objectRef'  => (object) $participant,
				];

				/**
				 * Broadcast that the Participant must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update a WordPress Post
				 *
				 * @since 0.5
				 *
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/admin/participant-role-to-post/sync', $args );

			}

			// Reinstate WordPress callbacks.
			$this->acf_loader->mapper->hooks_wordpress_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Stepped synchronisation of WordPress Posts to CiviCRM Participants.
	 *
	 * @since 0.5
	 *
	 * @param string $entity The identifier for the entity - here it's Post Type.
	 */
	public function stepped_sync_posts_to_participants( $entity = null ) {

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$entity_id = empty( $entity ) ? '' : $entity;
		} else {
			$entity_id = isset( $_POST['entity_id'] ) ? sanitize_text_field( wp_unslash( $_POST['entity_id'] ) ) : '';
		}

		// If not "participant", then bail.
		if ( $entity_id != 'participant' ) {
			return;
		}

		// Build key.
		$key = 'post_to_participant_role_' . $entity_id;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Construct args.
		$args = [
			'post_type'     => $entity_id,
			'no_found_rows' => true,
			'numberposts'   => $this->step_count_get( 'participant_role_post_types' ),
			'offset'        => $offset,
		];

		// Get all posts.
		$posts = get_posts( $args );

		// If we get results.
		if ( count( $posts ) > 0 ) {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there less items than the step count?
			if ( count( $posts ) < $this->step_count_get( 'participant_role_post_types' ) ) {
				$diff = count( $posts );
			} else {
				$diff = $this->step_count_get( 'participant_role_post_types' );
			}

			// Set from and to flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove CiviCRM callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_civicrm_remove();

			// Sync each Post in turn.
			foreach ( $posts as $post ) {

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
					'post'    => $post,
					'update'  => true,
				];

				/**
				 * Broadcast that the Post must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update a CiviCRM Participant
				 * * Update the CiviCRM Custom Fields
				 *
				 * @since 0.5
				 *
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/admin/post-to-participant/sync', $args );

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
				];

				/**
				 * Broadcast that the ACF Fields must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update the CiviCRM Custom Fields
				 *
				 * @since 0.5
				 *
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/admin/post-to-participant/acf_fields/sync', $args );

			}

			// Reinstate CiviCRM callbacks.
			$this->acf_loader->mapper->hooks_civicrm_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		} else {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Stepped synchronisation of CiviCRM Participants to WordPress Posts.
	 *
	 * @since 0.5
	 *
	 * @param string $entity The identifier for the Entity - here it's 'cpt'.
	 */
	public function stepped_sync_participants_to_posts( $entity = null ) {

		// Init AJAX return.
		$data = [];

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$entity_id = $entity == 'cpt' ? $entity : 0;
		} else {
			$entity_id = isset( $_POST['entity_id'] ) ? sanitize_text_field( wp_unslash( $_POST['entity_id'] ) ) : 0;
		}

		// If not "cpt", then bail.
		if ( $entity_id != 'cpt' ) {
			return;
		}

		// Build key.
		$key = 'participant_role_to_post_' . $entity_id;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $entity_id === 0 || $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Init query result.
		$result = [];

		// Init CiviCRM.
		if ( $this->acf_loader->civicrm->is_initialised() ) {

			// Get the Participant data.
			$result = $this->acf_loader->civicrm->participant->participants_chunked_data_get(
				$offset,
				$this->step_count_get( 'participant_roles' )
			);

		} else {

			// Do not allow progress.
			$result['is_error'] = 1;

		}

		// Did we get an error?
		$error = false;
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$error = true;
		}

		// Finish sync on failure or empty result.
		if ( $error || empty( $result['values'] ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		} else {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there fewer items than the step count?
			if ( count( $result['values'] ) < $this->step_count_get( 'participant_roles' ) ) {
				$diff = count( $result['values'] );
			} else {
				$diff = $this->step_count_get( 'participant_roles' );
			}

			// Set "from" and "to" flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove WordPress callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_remove();

			// Trigger sync for each Participant in turn.
			foreach ( $result['values'] as $participant ) {

				// Let's make an array of params.
				$args = [
					'op'         => 'sync',
					'objectName' => 'Participant',
					'objectId'   => $participant['id'],
					'objectRef'  => (object) $participant,
				];

				/**
				 * Broadcast that the Participant must be synced.
				 *
				 * Used internally to:
				 *
				 * * Update a WordPress Post
				 *
				 * @since 0.5
				 *
				 * @param array $args The array of CiviCRM params.
				 */
				do_action( 'cwps/acf/admin/participant-to-post/sync', $args );

			}

			// Reinstate WordPress callbacks.
			$this->acf_loader->mapper->hooks_wordpress_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Stepped synchronisation of CiviCRM Groups to WordPress Terms.
	 *
	 * @since 0.4
	 *
	 * @param string $entity The identifier for the entity - here it's Group ID.
	 */
	public function stepped_sync_groups_to_terms( $entity = null ) {

		// Init AJAX return.
		$data = [];

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$group_id = is_numeric( $entity ) ? (int) $entity : 0;
		} else {
			$group_id = isset( $_POST['entity_id'] ) ? (int) $_POST['entity_id'] : 0;
		}

		// Build key.
		$key = 'group_to_term_' . $group_id;

		// If this is an AJAX request, check security.
		$result = true;
		if ( wp_doing_ajax() ) {
			$result = check_ajax_referer( 'cwps_acf_' . $key, false, false );
		}

		// If we get an error.
		if ( $group_id === 0 || $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			if ( wp_doing_ajax() ) {
				wp_send_json( $data );
			}
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Init query result.
		$result = [];

		// Init CiviCRM.
		if ( $this->acf_loader->civicrm->is_initialised() ) {

			// Get the Group Contact data.
			$result = $this->acf_loader->civicrm->group->group_contacts_chunked_data_get(
				$group_id,
				$offset,
				$this->step_count_get( 'groups' )
			);

		} else {

			// Do not allow progress.
			$result['is_error'] = 1;

		}

		// Did we get an error?
		$error = false;
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$error = true;
		}

		// Finish sync on failure or empty result.
		if ( $error || empty( $result['values'] ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Delete the option to start from the beginning.
			$this->stepped_offset_delete( $key );

		} else {

			// Set finished flag.
			$data['finished'] = 'false';

			// Are there fewer items than the step count?
			if ( count( $result['values'] ) < $this->step_count_get( 'groups' ) ) {
				$diff = count( $result['values'] );
			} else {
				$diff = $this->step_count_get( 'groups' );
			}

			// Set "from" and "to" flags.
			$data['from'] = (int) $offset;
			$data['to']   = $data['from'] + $diff;

			// Remove WordPress callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_remove();

			// Let's make an array of params.
			$args = [
				'op'         => 'sync',
				'objectName' => 'GroupContact',
				'objectId'   => $group_id,
				'objectRef'  => $result['values'],
			];

			/**
			 * Broadcast that the Contacts in this Group must be synced.
			 *
			 * Used internally to:
			 *
			 * * Update the WordPress Terms
			 *
			 * @since 0.4
			 *
			 * @param array $args The array of CiviCRM params.
			 */
			do_action( 'cwps/acf/admin/group-to-term/sync', $args );

			// Reinstate WordPress callbacks.
			$this->acf_loader->mapper->hooks_wordpress_add();

			// Increment offset option.
			$this->stepped_offset_update( $key, $data['to'] );

		}

		// Send data to browser.
		if ( wp_doing_ajax() ) {
			wp_send_json( $data );
		}

	}

	/**
	 * Init the synchronisation stepper.
	 *
	 * @since 0.4
	 *
	 * @param string $key The unique identifier for the stepper.
	 */
	public function stepped_offset_init( $key ) {

		// Construct option name.
		$option = '_cwps_acf_' . $key . '_offset';

		// If the offset value doesn't exist.
		if ( 'fgffgs' == get_option( $option, 'fgffgs' ) ) {

			// Start at the beginning.
			$offset = 0;
			add_option( $option, '0' );

		} else {

			// Use the existing value.
			$offset = (int) get_option( $option, '0' );

		}

		// --<
		return $offset;

	}

	/**
	 * Update the synchronisation stepper.
	 *
	 * @since 0.4
	 *
	 * @param string $key The unique identifier for the stepper.
	 * @param string $to The value for the stepper.
	 */
	public function stepped_offset_update( $key, $to ) {

		// Construct option name.
		$option = '_cwps_acf_' . $key . '_offset';

		// Increment offset option.
		update_option( $option, (string) $to );

	}

	/**
	 * Delete the synchronisation stepper.
	 *
	 * @since 0.4
	 *
	 * @param string $key The unique identifier for the stepper.
	 */
	public function stepped_offset_delete( $key ) {

		// Construct option name.
		$option = '_cwps_acf_' . $key . '_offset';

		// Delete the option to start from the beginning.
		delete_option( $option );

	}

	// -------------------------------------------------------------------------

	/**
	 * Get all step counts.
	 *
	 * @since 0.4
	 *
	 * @return array $step_counts The array of step counts.
	 */
	public function step_counts_get() {

		/**
		 * Filter the step counts.
		 *
		 * @since 0.4
		 *
		 * @param array $step_counts The default step counts.
		 */
		return apply_filters( 'cwps/acf/step_counts/get', $this->step_counts );

	}

	/**
	 * Get the step count for a given mapping.
	 *
	 * There's no error-checking here. Make sure the $mapping param is correct.
	 *
	 * @since 0.4
	 *
	 * @param string $type The type of mapping.
	 * @return integer $step_count The number of items to sync for this mapping.
	 */
	public function step_count_get( $type ) {

		// Only call getter once.
		static $step_counts = [];

		// Get all step counts.
		if ( empty( $step_counts ) ) {
			$step_counts = $this->step_counts_get();
		}

		// Return the value for the given key.
		return $step_counts[ $type ];

	}

}
