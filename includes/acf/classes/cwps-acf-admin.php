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
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * The installed version of the plugin.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $plugin_version The plugin version.
	 */
	public $plugin_version;

	/**
	 * Settings data.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $settings The plugin settings data.
	 */
	public $settings = [];

	/**
	 * How many items to process per AJAX request.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $step_counts The array of item counts to process per AJAX request.
	 */
	public $step_counts = [
		'contact_post_types' => 5, // Number of Contact Posts per WordPress Post Type.
		'contact_types' => 5, // Number of Contacts per CiviCRM Contact Type.
		'groups' => 10, // Number of Group Members per CiviCRM Group.
		'activity_post_types' => 10, // Number of Activity Posts per WordPress Post Type.
		'activity_types' => 10, // Number of Activities per CiviCRM Activity Type.
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
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Is this the back end?
		if ( ! is_admin() ) {
			return;
		}

		// Add AJAX handler.
		add_action( 'wp_ajax_sync_acf_and_civicrm', [ $this, 'sync_acf_and_civicrm' ] );

		// Add menu item(s) to WordPress admin menu.
		add_action( 'admin_menu', [ $this, 'admin_menu' ], 30 );

		// Add our meta boxes.
		add_action( 'add_meta_boxes', [ $this, 'meta_boxes_add' ], 11, 1 );

		// Add AJAX handlers.
		add_action( 'wp_ajax_sync_posts_to_contacts', [ $this, 'stepped_sync_posts_to_contacts' ] );
		add_action( 'wp_ajax_sync_contacts_to_posts', [ $this, 'stepped_sync_contacts_to_posts' ] );
		add_action( 'wp_ajax_sync_groups_to_terms', [ $this, 'stepped_sync_groups_to_terms' ] );
		add_action( 'wp_ajax_sync_posts_to_activities', [ $this, 'stepped_sync_posts_to_activities' ] );
		add_action( 'wp_ajax_sync_activities_to_posts', [ $this, 'stepped_sync_activities_to_posts' ] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Add our admin page(s) to the WordPress admin menu.
	 *
	 * @since 0.4
	 */
	public function admin_menu() {

		// We must be network admin in Multisite.
		if ( is_multisite() AND ! is_super_admin() ) {
			return;
		}

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
		add_action( 'admin_head-' . $this->sync_page, [ $this->acf_loader->plugin->admin, 'admin_menu_highlight' ], 50 );

		// Add styles and scripts only on our "Manual Sync" page.
		// @see wp-admin/admin-header.php
		add_action( 'admin_head-' . $this->sync_page, [ $this, 'admin_head' ] );
		add_action( 'admin_print_styles-' . $this->sync_page, [ $this, 'admin_styles' ] );
		add_action( 'admin_print_scripts-' . $this->sync_page, [ $this, 'admin_scripts' ] );

		// Filter the list of single site subpages and add multidomain page.
		add_filter( 'cwps/admin/settings/subpages', [ $this, 'admin_subpages_filter' ] );

		// Filter the list of single site page URLs and add multidomain page URL.
		add_filter( 'cwps/admin/settings/page_urls', [ $this, 'page_urls_filter' ] );

		// Filter the "show tabs" flag for setting templates.
		add_filter( 'cwps/admin/settings/show_tabs', [ $this, 'page_show_tabs' ] );

		// Add tab to setting templates.
		add_filter( 'cwps/admin/settings/nav_tabs', [ $this, 'page_add_tab' ], 10, 2 );

		// Try and update options.
		$this->settings_update_router();

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
			CIVICRM_WP_PROFILE_SYNC_VERSION
		);

		// Get all Post Types mapped to Contacts.
		$mapped_contact_post_types = $this->acf_loader->post_type->get_mapped( 'contact' );

		// Loop through them and get the data we want.
		$contact_post_types = [];
		foreach( $mapped_contact_post_types AS $contact_post_type ) {
			$contact_post_types[$contact_post_type->name] = [
				'label' => esc_html( $contact_post_type->label ),
				'count' => $this->acf_loader->post_type->post_count( $contact_post_type->name ),
			];
		}

		// Get all mapped Contact Types.
		$mapped_contact_types = $this->acf_loader->civicrm->contact_type->get_mapped();

		// Loop through them and get the data we want.
		$contact_types = [];
		foreach( $mapped_contact_types AS $contact_type ) {
			$contact_types[$contact_type['id']] = [
				'label' => esc_html( $contact_type['label'] ),
				'count' => $this->acf_loader->civicrm->contact_type->contact_count( $contact_type['id'] ),
			];
		}

		// Get all mapped Groups.
		$mapped_groups = $this->acf_loader->civicrm->group->groups_get_mapped();

		// Loop through them and get the data we want.
		$groups = [];
		foreach( $mapped_groups AS $group ) {
			$groups[$group['id']] = [
				'label' => esc_html( $group['title'] ),
				'count' => $this->acf_loader->civicrm->group->group_contact_count( $group['id'] ),
			];
		}

		// Get all Post Types mapped to Activities.
		$mapped_activity_post_types = $this->acf_loader->post_type->get_mapped( 'activity' );

		// Loop through them and get the data we want.
		$activity_post_types = [];
		foreach( $mapped_activity_post_types AS $activity_post_type ) {
			$activity_post_types[$activity_post_type->name] = [
				'label' => esc_html( $activity_post_type->label ),
				'count' => $this->acf_loader->post_type->post_count( $activity_post_type->name ),
			];
		}

		// Get all mapped Activity Types.
		$mapped_activity_types = $this->acf_loader->civicrm->activity_type->get_mapped();

		// Loop through them and get the data we want.
		$activity_types = [];
		foreach( $mapped_activity_types AS $activity_type ) {
			$activity_types[$activity_type['value']] = [
				'label' => esc_html( $activity_type['label'] ),
				'count' => $this->acf_loader->civicrm->activity_type->activity_count( $activity_type['value'] ),
			];
		}

		// Init settings.
		$settings = [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'contact_post_types' => $contact_post_types,
			'contact_types' => $contact_types,
			'groups' => $groups,
			'activity_post_types' => $activity_post_types,
			'activity_types' => $activity_types,
			'step_contact_post_types' => $this->step_count_get( 'contact_post_types' ),
			'step_contact_types' => $this->step_count_get( 'contact_types' ),
			'step_groups' => $this->step_count_get( 'groups' ),
			'step_activity_post_types' => $this->step_count_get( 'activity_post_types' ),
			'step_activity_types' => $this->step_count_get( 'activity_types' ),
		];

		// Init localisation.
		$localisation = [];

		// Add Contact Post Types localisation.
		$localisation['contact_post_types'] = [
			'total' => __( 'Posts to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current' => __( 'Processing posts {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing posts {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count' => count( $contact_post_types ),
		];

		// Add Contact Types localisation.
		$localisation['contact_types'] = [
			'total' => __( 'Contacts to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current' => __( 'Processing contacts {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing contacts {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count' => count( $contact_types ),
		];

		// Add Groups localisation.
		$localisation['groups'] = [
			'total' => __( 'Group members to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current' => __( 'Processing group members {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing group members {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count' => count( $groups ),
		];

		// Add Activity Post Types localisation.
		$localisation['activity_post_types'] = [
			'total' => __( 'Posts to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current' => __( 'Processing posts {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing posts {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count' => count( $activity_post_types ),
		];

		// Add Activity Types localisation.
		$localisation['activity_types'] = [
			'total' => __( 'Activitys to sync: {{total}}', 'civicrm-wp-profile-sync' ),
			'current' => __( 'Processing activities {{from}} to {{to}}', 'civicrm-wp-profile-sync' ),
			'complete' => __( 'Processing activities {{from}} to {{to}} complete', 'civicrm-wp-profile-sync' ),
			'count' => count( $activity_types ),
		];

		// Add common localisation.
		$localisation['common'] = [
			'done' => __( 'All done!', 'civicrm-wp-profile-sync' ),
		];

		// Localisation array.
		$vars = [
			'settings' => $settings,
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
		$target_url = $_SERVER['REQUEST_URI'];
		$url_array = explode( '&', $target_url );
		if ( $url_array ) {
			$target_url = htmlentities( $url_array[0] . '&updated=true' );
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
		if ( is_multisite() AND ! is_super_admin() ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-wp-profile-sync' ) );
		}

		// Check user permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have permission to access this page.', 'civicrm-wp-profile-sync' ) );
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
		do_action( 'add_meta_boxes', $screen->id, null );

		// Get the column CSS class.
		$columns = absint( $screen->get_columns() );
		$columns_css = '';
		if ( $columns ) {
			$columns_css = " columns-$columns";
		}

		// Get admin page URLs.
		$urls = $this->acf_loader->plugin->admin->page_get_urls();

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
	public function page_urls_filter( $urls ) {

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
	 * @param boolean $show_tabs True if tabs are shown, false otherwise.
	 * @return boolean $show_tabs True if tabs are to be shown, false otherwise.
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
	 * @param array $urls The array of subpage URLs.
	 * @param string The key of the active tab in the subpage URLs array.
	 */
	public function page_add_tab( $urls, $active_tab ) {

		// Define title.
		$title = __( 'ACF Integration', 'civicrm-admin-utilities' );

		// Default to inactive.
		$active = '';

		// Make active if it's our subpage.
		if ( $active_tab === 'manual-sync' ) {
			$active = ' nav-tab-active';
		}

		// Render tab.
		echo '<a href="' . $urls['manual-sync'] . '" class="nav-tab' . $active . '">' . $title . '</a>' . "\n";

	}



	// -------------------------------------------------------------------------



	/**
	 * Register meta boxes.
	 *
	 * @since 0.4
	 *
	 * @param string $screen_id The Admin Page Screen ID.
	 */
	public function meta_boxes_add( $screen_id ) {

		// Define valid Screen IDs.
		$screen_ids = [
			'admin_page_cwps_acf_sync',
		];

		// Bail if not the Screen ID we want.
		if ( ! in_array( $screen_id, $screen_ids ) ) {
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
			if ( ! in_array( 'closed', $classes ) ) {
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
		foreach( $mapped_contact_post_types AS $contact_post_type ) {
			$contact_post_types[$contact_post_type->name] = $contact_post_type->label;
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
		foreach( $mapped_contact_types AS $contact_type ) {
			$contact_types[$contact_type['id']] = $contact_type['label'];
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
		foreach( $mapped_activity_post_types AS $activity_post_type ) {
			$activity_post_types[$activity_post_type->name] = $activity_post_type->label;
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
		foreach( $mapped_activity_types AS $activity_type ) {
			$activity_types[$activity_type['value']] = $activity_type['label'];
		}

		// Include template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/wordpress/metaboxes/metabox-acf-activities-posts.php';

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
		foreach( $mapped_groups AS $group ) {
			$groups[$group['id']] = $group['title'];
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
		foreach( $mapped_contact_post_types AS $contact_post_type ) {
			$contact_post_types[$contact_post_type->name] = 'cwps_acf_post_to_contact_' . $contact_post_type->name . '_stop';
		}

		// Get all mapped Contact Types.
		$mapped_contact_types = $this->acf_loader->civicrm->contact_type->get_mapped();

		// Loop through them and get the data we want.
		$contact_types = [];
		foreach( $mapped_contact_types AS $contact_type ) {
			$contact_types[$contact_type['id']] = 'cwps_acf_contact_to_post_' . $contact_type['id'] . '_stop';
		}

		// Get all mapped Groups.
		$mapped_groups = $this->acf_loader->civicrm->group->groups_get_mapped();

		// Loop through them and get the data we want.
		$groups = [];
		foreach( $mapped_groups AS $group ) {
			$groups[$group['id']] = 'cwps_acf_group_to_term_' . $group['id'] . '_stop';
		}

		// Get all Post Types mapped to Activities.
		$mapped_activity_post_types = $this->acf_loader->post_type->get_mapped( 'activity' );

		// Loop through them and get the data we want.
		$activity_post_types = [];
		foreach( $mapped_activity_post_types AS $activity_post_type ) {
			$activity_post_types[$activity_post_type->name] = 'cwps_acf_post_to_activity_' . $activity_post_type->name . '_stop';
		}

		// Get all mapped Activity Types.
		$mapped_activity_types = $this->acf_loader->civicrm->activity_type->get_mapped();

		// Loop through them and get the data we want.
		$activity_types = [];
		foreach( $mapped_activity_types AS $activity_type ) {
			$activity_types[$activity_type['id']] = 'cwps_acf_activity_to_post_' . $activity_type['id'] . '_stop';
		}

		// Init stop, continue and sync flags.
		$stop = false;
		$continue = false;
		$sync_type = false;
		$entity_id = false;

		// Find out if a Contact Post Type button has been pressed.
		foreach( $contact_post_types AS $contact_post_type => $stop_code ) {

			// Define replacements.
			$replacements = [ 'cwps_acf_post_to_contact_', '_stop' ];

			// Was a "Stop Sync" button pressed?
			if ( isset( $_POST[$stop_code] ) ) {
				$stop = $stop_code;
				$sync_type = 'contact_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

			// Was a "Sync Now" or "Continue Sync" button pressed?
			$button = str_replace( '_stop', '', $stop_code );
			if ( isset( $_POST[$button] ) ) {
				$continue = $button;
				$sync_type = 'contact_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

		}

		// Find out if a Contact Type button has been pressed.
		if ( $stop === false AND $continue === false ) {
			foreach( $contact_types AS $contact_type_id => $stop_code ) {

				// Define replacements.
				$replacements = [ 'cwps_acf_contact_to_post_', '_stop' ];

				// Was a "Stop Sync" button pressed?
				if ( isset( $_POST[$stop_code] ) ) {
					$stop = $stop_code;
					$sync_type = 'contact_type';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

				// Was a "Sync Now" or "Continue Sync" button pressed?
				$button = str_replace( '_stop', '', $stop_code );
				if ( isset( $_POST[$button] ) ) {
					$continue = $button;
					$sync_type = 'contact_type';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

			}
		}

		// Find out if a Group "Stop Sync" button has been pressed.
		if ( $stop === false ) {
			foreach( $groups AS $group_id => $stop_code ) {

				// Define replacements.
				$replacements = [ 'cwps_acf_group_to_term_', '_stop' ];

				// Was a "Stop Sync" button pressed?
				if ( isset( $_POST[$stop_code] ) ) {
					$stop = $stop_code;
					$sync_type = 'group';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

				// Was a "Sync Now" or "Continue Sync" button pressed?
				$button = str_replace( '_stop', '', $stop_code );
				if ( isset( $_POST[$button] ) ) {
					$continue = $button;
					$sync_type = 'group';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

			}
		}

		// Find out if an Activity Post Type button has been pressed.
		foreach( $activity_post_types AS $activity_post_type => $stop_code ) {

			// Define replacements.
			$replacements = [ 'cwps_acf_post_to_activity_', '_stop' ];

			// Was a "Stop Sync" button pressed?
			if ( isset( $_POST[$stop_code] ) ) {
				$stop = $stop_code;
				$sync_type = 'activity_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

			// Was a "Sync Now" or "Continue Sync" button pressed?
			$button = str_replace( '_stop', '', $stop_code );
			if ( isset( $_POST[$button] ) ) {
				$continue = $button;
				$sync_type = 'activity_post_type';
				$entity_id = str_replace( $replacements, '', $stop_code );
				break;
			}

		}

		// Find out if an Activity Type button has been pressed.
		if ( $stop === false AND $continue === false ) {
			foreach( $activity_types AS $activity_type_id => $stop_code ) {

				// Define replacements.
				$replacements = [ 'cwps_acf_activity_to_post_', '_stop' ];

				// Was a "Stop Sync" button pressed?
				if ( isset( $_POST[$stop_code] ) ) {
					$stop = $stop_code;
					$sync_type = 'activity_type';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

				// Was a "Sync Now" or "Continue Sync" button pressed?
				$button = str_replace( '_stop', '', $stop_code );
				if ( isset( $_POST[$button] ) ) {
					$continue = $button;
					$sync_type = 'activity_type';
					$entity_id = str_replace( $replacements, '', $stop_code );
					break;
				}

			}
		}

	 	// Bail if no button was pressed.
		if ( empty( $stop ) AND empty( $continue ) ) {
			return;
		}

		// Check that we trust the source of the data.
		check_admin_referer( 'cwps_acf_sync_action', 'cwps_acf_sync_nonce' );

	 	// Was a "Stop Sync" button pressed?
		if ( ! empty( $stop ) ) {

			// Define slugs.
			$slugs = [
				'contact_post_type' => 'post_to_contact_',
				'contact_type' => 'contact_to_post_',
				'group' => 'group_to_term_',
				'activity_post_type' => 'post_to_activity_',
				'activity_type' => 'activity_to_post_',
			];

			// Build key.
			$key = $slugs[$sync_type] . $entity_id;

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
		foreach( $mapped_contact_post_types AS $contact_post_type ) {
			$contact_post_types[] = $contact_post_type->name;
		}

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$contact_post_type = empty( $entity ) ? '' : $entity;
		} else {
			$contact_post_type = isset( $_POST['entity_id'] ) ? trim( $_POST['entity_id'] ) : '';
		}

		// Sanity check input.
		if ( ! in_array( $contact_post_type, $contact_post_types ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			$this->send_data( $data );
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
		if ( $contact_post_type === '' OR $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			$this->send_data( $data );
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Construct args.
		$args = [
			'post_type' => $contact_post_type,
			'no_found_rows' => true,
			'numberposts' => $this->step_count_get( 'contact_post_types' ),
			'offset' => $offset,
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
			$data['to'] = $data['from'] + $diff;

			// Remove CiviCRM callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_civicrm_remove();

			// Sync each Post in turn.
			foreach( $posts AS $post ) {

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
					'post' => $post,
					'update' => true,
				];

				/**
				 * Broadcast that the Post must be synced.
				 *
				 * Used internally to:
				 *
				 * - Update a CiviCRM Contact
				 * - Update the CiviCRM Custom Fields
				 * - Update the CiviCRM Group memberships
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
				 * - Update the CiviCRM Custom Fields
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
		$this->send_data( $data );

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
		if ( $contact_type_id === 0 OR $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			$this->send_data( $data );
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
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$error = true;
		}

		// Finish sync on failure or empty result.
		if ( $error OR empty( $result['values'] ) ) {

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
			$data['to'] = $data['from'] + $diff;

			// Remove WordPress callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_remove();

			// Trigger sync for each Contact in turn.
			foreach( $result['values'] AS $contact ) {

				// Let's make an array of params.
				$args = [
					'op' => 'sync',
					'objectName' => $contact['contact_type'],
					'objectId' => $contact['contact_id'],
					'objectRef' => (object) $contact,
				];

				/**
				 * Broadcast that the Contact must be synced.
				 *
				 * Used internally to:
				 *
				 * - Update a WordPress Post
				 * - Update the WordPress Terms
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
		$this->send_data( $data );

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
		foreach( $mapped_activity_post_types AS $activity_post_type ) {
			$activity_post_types[] = $activity_post_type->name;
		}

		// Sanitise input.
		if ( ! wp_doing_ajax() ) {
			$activity_post_type = empty( $entity ) ? '' : $entity;
		} else {
			$activity_post_type = isset( $_POST['entity_id'] ) ? trim( $_POST['entity_id'] ) : '';
		}

		// Sanity check input.
		if ( ! in_array( $activity_post_type, $activity_post_types ) ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			$this->send_data( $data );
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
		if ( $activity_post_type === '' OR $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			$this->send_data( $data );
			return;

		}

		// Get the current offset.
		$offset = $this->stepped_offset_init( $key );

		// Construct args.
		$args = [
			'post_type' => $activity_post_type,
			'no_found_rows' => true,
			'numberposts' => $this->step_count_get( 'activity_post_types' ),
			'offset' => $offset,
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
			$data['to'] = $data['from'] + $diff;

			// Remove CiviCRM callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_civicrm_remove();

			// Sync each Post in turn.
			foreach( $posts AS $post ) {

				// Let's make an array of params.
				$args = [
					'post_id' => $post->ID,
					'post' => $post,
					'update' => true,
				];

				/**
				 * Broadcast that the Post must be synced.
				 *
				 * Used internally to:
				 *
				 * - Update a CiviCRM Activity
				 * - Update the CiviCRM Custom Fields
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
				 * - Update the CiviCRM Custom Fields
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
		$this->send_data( $data );

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
		if ( $activity_type_id === 0 OR $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			$this->send_data( $data );
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
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$error = true;
		}

		// Finish sync on failure or empty result.
		if ( $error OR empty( $result['values'] ) ) {

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
			$data['to'] = $data['from'] + $diff;

			// Remove WordPress callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_remove();

			// Trigger sync for each Activity in turn.
			foreach( $result['values'] AS $activity ) {

				// Let's make an array of params.
				$args = [
					'op' => 'sync',
					'objectName' => 'Activity',
					'objectId' => $activity['id'],
					'objectRef' => (object) $activity,
				];

				/**
				 * Broadcast that the Activity must be synced.
				 *
				 * Used internally to:
				 *
				 * - Update a WordPress Post
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
		$this->send_data( $data );

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
		if ( $group_id === 0 OR $result === false ) {

			// Set finished flag.
			$data['finished'] = 'true';

			// Send data to browser.
			$this->send_data( $data );
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
		if ( isset( $result['is_error'] ) AND $result['is_error'] == '1' ) {
			$error = true;
		}

		// Finish sync on failure or empty result.
		if ( $error OR empty( $result['values'] ) ) {

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
			$data['to'] = $data['from'] + $diff;

			// Remove WordPress callbacks to prevent recursion.
			$this->acf_loader->mapper->hooks_wordpress_remove();

			// Let's make an array of params.
			$args = [
				'op' => 'sync',
				'objectName' => 'GroupContact',
				'objectId' => $group_id,
				'objectRef' => $result['values'],
			];

			/**
			 * Broadcast that the Contacts in this Group must be synced.
			 *
			 * Used internally to:
			 *
			 * - Update the WordPress Terms
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
		$this->send_data( $data );

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
	 * @param array $step_counts The array of step counts.
	 */
	public function step_counts_get() {

		/**
		 * Filter the step counts.
		 *
		 * @since 0.4
		 *
		 * @param array $step_counts The default step counts.
		 * @return array $step_counts The filtered step counts.
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
		return $step_counts[$type];

	}



	// -------------------------------------------------------------------------



	/**
	 * Send JSON data to the browser.
	 *
	 * @since 0.4
	 *
	 * @param array $data The data to send.
	 */
	private function send_data( $data ) {

		// Is this an AJAX request?
		if ( defined( 'DOING_AJAX' ) AND DOING_AJAX ) {

			// Set reasonable headers.
			header('Content-type: text/plain');
			header("Cache-Control: no-cache");
			header("Expires: -1");

			// Echo.
			echo json_encode( $data );

			// Die.
			exit();

		}

	}



} // Class ends.



