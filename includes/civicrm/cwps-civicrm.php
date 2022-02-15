<?php
/**
 * CiviCRM compatibility Class.
 *
 * Handles CiviCRM integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM compatibility Class.
 *
 * This class provides CiviCRM integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_CiviCRM {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * CiviCRM Contact Type object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $contact_type The CiviCRM Contact Type object.
	 */
	public $contact_type;

	/**
	 * CiviCRM Contact object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $contact The CiviCRM Contact object.
	 */
	public $contact;

	/**
	 * CiviCRM Contact Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $contact_field The CiviCRM Contact Field object.
	 */
	public $contact_field;

	/**
	 * CiviCRM Custom Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $custom_field The CiviCRM Custom Field object.
	 */
	public $custom_field;

	/**
	 * CiviCRM Custom Group object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $custom_group The CiviCRM Custom Group object.
	 */
	public $custom_group;

	/**
	 * CiviCRM Email object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $email The CiviCRM Email object.
	 */
	public $email;

	/**
	 * CiviCRM Website object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $website The CiviCRM Website object.
	 */
	public $website;

	/**
	 * CiviCRM Address object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $address The CiviCRM Address object.
	 */
	public $address;

	/**
	 * CiviCRM Phone object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $phone The CiviCRM Phone object.
	 */
	public $phone;

	/**
	 * CiviCRM Bulk Operations object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $bulk The CiviCRM Bulk Operations object.
	 */
	public $bulk;



	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store reference.
		$this->plugin = $parent;

		// Boot when plugin is loaded.
		add_action( 'civicrm_wp_profile_sync_init', [ $this, 'initialise' ] );

	}



	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/civicrm/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include class files.
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-contact-type.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-contact.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-contact-field.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-custom-field.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-custom-group.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-email.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-website.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-address.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-phone.php';
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/civicrm/cwps-civicrm-bulk.php';

	}



	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->contact_type = new CiviCRM_WP_Profile_Sync_CiviCRM_Contact_Type( $this );
		$this->contact = new CiviCRM_WP_Profile_Sync_CiviCRM_Contact( $this );
		$this->contact_field = new CiviCRM_WP_Profile_Sync_CiviCRM_Contact_Field( $this );
		$this->custom_field = new CiviCRM_WP_Profile_Sync_CiviCRM_Custom_Field( $this );
		$this->custom_group = new CiviCRM_WP_Profile_Sync_CiviCRM_Custom_Group( $this );
		$this->email = new CiviCRM_WP_Profile_Sync_CiviCRM_Email( $this );
		$this->website = new CiviCRM_WP_Profile_Sync_CiviCRM_Website( $this );
		$this->address = new CiviCRM_WP_Profile_Sync_CiviCRM_Address( $this );
		$this->phone = new CiviCRM_WP_Profile_Sync_CiviCRM_Phone( $this );
		$this->bulk = new CiviCRM_WP_Profile_Sync_CiviCRM_Bulk( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Add all CiviCRM callbacks.
		$this->contact->register_hooks();
		$this->email->register_hooks();
		$this->website->register_hooks();

	}



	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Remove all CiviCRM callbacks.
		$this->contact->unregister_hooks();
		$this->custom_field->unregister_hooks();
		$this->email->unregister_hooks();
		$this->website->unregister_hooks();

	}



	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Add all CiviCRM callbacks.
		$this->contact->register_mapper_hooks();
		$this->email->register_mapper_hooks();
		$this->website->register_mapper_hooks();

	}



	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all CiviCRM callbacks.
		$this->contact->unregister_mapper_hooks();
		$this->email->unregister_mapper_hooks();
		$this->website->unregister_mapper_hooks();

	}



	// -------------------------------------------------------------------------



	/**
	 * Check if CiviCRM is initialised.
	 *
	 * @since 0.4
	 *
	 * @return bool True if CiviCRM initialised, false otherwise.
	 */
	public function is_initialised() {

		// Init only when CiviCRM is fully installed.
		if ( ! defined( 'CIVICRM_INSTALLED' ) ) {
			return false;
		}
		if ( ! CIVICRM_INSTALLED ) {
			return false;
		}

		// Bail if no CiviCRM init function.
		if ( ! function_exists( 'civi_wp' ) ) {
			return false;
		}

		// Try and initialise CiviCRM.
		return civi_wp()->initialize();

	}



	// -------------------------------------------------------------------------



	/**
	 * Finds out if a CiviCRM Component is active.
	 *
	 * @since 0.5
	 *
	 * @param string $component The name of the CiviCRM Component, e.g. 'CiviContribute'.
	 * @return bool $active True if the Component is active, false otherwise.
	 */
	public function is_component_enabled( $component = '' ) {

		// Init return.
		$active = false;

		// Bail if we can't initialise CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $active;
		}

		// Get the Component array. CiviCRM handles caching.
		$components = CRM_Core_Component::getEnabledComponents();

		// Override if Component is active.
		if ( array_key_exists( $component, $components ) ) {
			$active = true;
		}

		// --<
		return $active;

	}



	/**
	 * Finds out if an Extension is installed and enabled.
	 *
	 * @since 0.5
	 *
	 * @param string $extension The "name" of the CiviCRM Extension, e.g. 'org.civicoop.emailapi'.
	 * @return bool $active True if the Extension is active, false otherwise.
	 */
	public function is_extension_enabled( $extension = '' ) {

		// Init return.
		$active = false;

		// Get the Extensions array.
		$extensions = $this->extensions_get_enabled();

		// Override if Extension is active.
		if ( in_array( $extension, $extensions ) ) {
			$active = true;
		}

		// --<
		return $active;

	}



	/**
	 * Gets the Extensions that are enabled in CiviCRM.
	 *
	 * The return array contains the unique 'key' of each enabled Extension.
	 *
	 * @since 0.5
	 *
	 * @return array $enabled_extensions The array of enabled Extensions.
	 */
	public function extensions_get_enabled() {

		// Only do this once per page load.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$enabled_extensions = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $enabled_extensions;
		}

		// Define params to query for enabled Extensions.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'status' => 'installed',
			'statusLabel' => 'Enabled',
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Extension', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $enabled_extensions;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $enabled_extensions;
		}

		// Build return array.
		foreach ( $result['values'] as $key => $extension ) {
			$enabled_extensions[] = $extension['key'];
		}

		// Maybe populate to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $enabled_extensions;
		}

		// --<
		return $enabled_extensions;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a CiviCRM Setting.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the CiviCRM Setting.
	 * @return mixed $setting The value of the CiviCRM Setting, or false on failure.
	 */
	public function get_setting( $name ) {

		// Init return.
		$setting = false;

		// Init CiviCRM or bail.
		if ( ! $this->is_initialised() ) {
			return $setting;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'name' => $name,
		];

		// Call the CiviCRM API.
		$setting = civicrm_api( 'Setting', 'getvalue', $params );

		// Convert if the value has the special CiviCRM array-like format.
		if ( is_string( $setting ) && false !== strpos( $setting, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
			$setting = CRM_Utils_Array::explodePadded( $setting );
		}

		// --<
		return $setting;

	}



	/**
	 * Gets the active CiviCRM Autocomplete Options.
	 *
	 * @since 0.5
	 *
	 * @param string $type The type of Autocomplete Options to return.
	 * @return array $autocomplete_options The active CiviCRM Autocomplete Options.
	 */
	public function get_autocomplete_options( $type = 'contact_reference_options' ) {

		// Init return.
		$autocomplete_options = [];

		// Init CiviCRM or bail.
		if ( ! $this->is_initialised() ) {
			return $autocomplete_options;
		}

		// Get the list of autocomplete options.
		$autocomplete_values = CRM_Core_BAO_Setting::valueOptions(
			CRM_Core_BAO_Setting::SYSTEM_PREFERENCES_NAME,
			$type
		);

		// Filter out the inactive ones.
		$autocomplete_options = array_keys( $autocomplete_values, '1' );

		// --<
		return $autocomplete_options;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get a CiviCRM admin link.
	 *
	 * @since 0.4
	 *
	 * @param string $path The CiviCRM path.
	 * @param string $params The CiviCRM parameters.
	 * @return string $link The URL of the CiviCRM page.
	 */
	public function get_link( $path = '', $params = null ) {

		// Init link.
		$link = '';

		// Init CiviCRM or bail.
		if ( ! $this->is_initialised() ) {
			return $link;
		}

		// Use CiviCRM to construct link.
		$link = CRM_Utils_System::url(
			$path, // Path to the resource.
			$params, // Params to pass to resource.
			true, // Force an absolute link.
			null, // Fragment (#anchor) to append.
			true, // Encode special HTML characters.
			false, // CMS front end.
			true // CMS back end.
		);

		// --<
		return $link;

	}



	/**
	 * Utility for de-nullifying CiviCRM data.
	 *
	 * @since 0.4
	 * @since 0.5 Moved to this class.
	 *
	 * @param mixed $value The existing value.
	 * @return mixed $value The cleaned value.
	 */
	public function denullify( $value ) {

		// Catch inconsistent CiviCRM "empty-ish" values.
		if ( empty( $value ) || $value == 'null' || $value == 'NULL' ) {
			$value = '';
		}

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets a CiviCRM Option Group by name.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Option Group.
	 * @return array $option_group The array of Option Group data.
	 */
	public function option_group_get( $name ) {

		// Only do this once per named Option Group.
		static $pseudocache;
		if ( isset( $pseudocache[ $name ] ) ) {
			return $pseudocache[ $name ];
		}

		// Init return.
		$options = [];

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $options;
		}

		// Define query params.
		$params = [
			'name' => $name,
			'version' => 3,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'OptionGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $options;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $options;
		}

		// The result set should contain only one item.
		$options = array_pop( $result['values'] );

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $name ] ) ) {
			$pseudocache[ $name ] = $options;
		}

		// --<
		return $options;

	}



	/**
	 * Get the CiviCRM Option Group data for a given ID.
	 *
	 * @since 0.4
	 *
	 * @param string|integer $option_group_id The numeric ID of the Option Group.
	 * @return array|bool $option_group An array of Option Group data, or false on failure.
	 */
	public function option_group_get_by_id( $option_group_id ) {

		// Init return.
		$option_group = false;

		// Try and init CiviCRM.
		if ( ! $this->is_initialised() ) {
			return $option_group;
		}

		// Build params to get Option Group data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $option_group_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'OptionGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $option_group;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $option_group;
		}

		// The result set should contain only one item.
		$option_group = array_pop( $result['values'] );

		// --<
		return $option_group;

	}



} // Class ends.



