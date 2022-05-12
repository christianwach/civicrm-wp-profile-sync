<?php
/**
 * CiviCRM Instant Messenger Class.
 *
 * Handles CiviCRM Instant Messenger functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Instant Messenger Class.
 *
 * A class that encapsulates CiviCRM Instant Messenger functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Instant_Messenger extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
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
	 * Parent (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_hooks The Mapper hooks registered flag.
	 */
	public $mapper_hooks = false;

	/**
	 * ACF Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $fields_handled The array of ACF Fields which must be handled separately.
	 */
	public $fields_handled = [
		'civicrm_im',
	];

	/**
	 * Shortcode object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $shortcode The Shortcode object.
	 */
	public $shortcode;

	/**
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Instant Messenger Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $email_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $im_field_prefix = 'caiim_';

	/**
	 * Public Instant Messenger Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $im_fields The array of public Instant Messenger Fields.
	 */
	public $im_fields = [
		'is_primary' => 'true_false',
		'is_billing' => 'true_false',
		'name' => 'text',
		//'provider_id' => 'select',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

		// Init parent.
		parent::__construct();

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/acf/civicrm/im/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include Shortcode class file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/shortcodes/cwps-shortcode-im.php';

	}



	/**
	 * Set up the child objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Init Instant Messenger Shortcode object.
		$this->shortcode = new CiviCRM_Profile_Sync_ACF_Shortcode_Instant_Messenger( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add any Instant Messenger Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post created from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

		// Maybe sync the Instant Messenger Record "Instant Messenger ID" to the ACF Subfields.
		add_action( 'cwps/acf/civicrm/im/created', [ $this, 'maybe_sync_im_id' ], 10, 2 );

		// Listen for queries from the ACF Bypass class.
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

		// Listen for queries from our ACF Field Group class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 50, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'text_settings_modify' ], 50, 2 );

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
			return;
		}

		// Listen for events from our Mapper that require Instant Messenger updates.
		add_action( 'cwps/acf/mapper/im/created', [ $this, 'im_edited' ], 10 );
		add_action( 'cwps/acf/mapper/im/edited', [ $this, 'im_edited' ], 10 );
		add_action( 'cwps/acf/mapper/im/delete/pre', [ $this, 'im_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/im/deleted', [ $this, 'im_deleted' ], 10 );

		// Declare registered.
		$this->mapper_hooks = true;

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_hooks === false ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/im/created', [ $this, 'im_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/im/edited', [ $this, 'im_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/im/delete/pre', [ $this, 'im_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/im/deleted', [ $this, 'im_deleted' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact's Fields with data from ACF Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool $success True if updates were successful, or false on failure.
	 */
	public function fields_handled_update( $args ) {

		// Init success.
		$success = true;

		// Bail if we have no Field data to save.
		if ( empty( $args['fields'] ) ) {
			return $success;
		}

		// Loop through the Field data.
		foreach ( $args['fields'] as $field => $value ) {

			// Get the Field settings.
			$settings = get_field_object( $field, $args['post_id'] );
			if ( empty( $settings ) ) {
				continue;
			}

			// Maybe update an Instant Messenger Record.
			$success = $this->field_handled_update( $field, $value, $args['contact']['id'], $settings, $args );

		}

		// --<
		return $success;

	}



	/**
	 * Update a CiviCRM Contact's Field with data from an ACF Field.
	 *
	 * These Fields require special handling because they are not part
	 * of the core Contact data.
	 *
	 * @since 0.4
	 *
	 * @param string $field The ACF Field selector.
	 * @param mixed $value The ACF Field value.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $settings The ACF Field settings.
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function field_handled_update( $field, $value, $contact_id, $settings, $args ) {

		// Skip if it's not an ACF Field Type that this class handles.
		if ( ! in_array( $settings['type'], $this->fields_handled ) ) {
			return true;
		}

		// Update the Instant Messenger Records.
		$success = $this->ims_update( $value, $contact_id, $field, $args );

		// --<
		return $success;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for an Instant Messenger Record.
	 *
	 * @since 0.4
	 *
	 * @param integer $im_id The numeric ID of the Instant Messenger Record.
	 * @return array $im The array of Instant Messenger Record data, or empty if none.
	 */
	public function im_get_by_id( $im_id ) {

		// Init return.
		$im = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $im;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $im_id,
		];

		// Get Instant Messenger Record details via API.
		$result = civicrm_api( 'Im', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $im;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $im;
		}

		// The result set should contain only one item.
		$im = array_pop( $result['values'] );

		// --<
		return $im;

	}



	/**
	 * Get the data for a Contact's Instant Messenger Records by Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer $location_type_id The numeric ID of the Instant Messenger Location Type.
	 * @param integer $provider_id The numeric ID of the Instant Messenger Type.
	 * @return array $ims The array of Instant Messenger Record data, or empty if none.
	 */
	public function ims_get_by_type( $contact_id, $location_type_id, $provider_id ) {

		// Init return.
		$ims = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $ims;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'location_type_id' => $location_type_id,
			'provider_id' => $provider_id,
		];

		// Get Instant Messenger Record details via API.
		$result = civicrm_api( 'Im', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $ims;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $ims;
		}

		// We want the result set.
		foreach ( $result['values'] as $value ) {
			$ims[] = (object) $value;
		}

		// --<
		return $ims;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Instant Messenger Records for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $im_data The array of Instant Messenger Record data for the CiviCRM Contact.
	 */
	public function ims_get_for_contact( $contact_id ) {

		// Init return.
		$im_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $im_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $im_data;
		}

		// Define params to get queried Instant Messenger Records.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Im', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $im_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $im_data;
		}

		// The result set it what we want.
		$im_data = $result['values'];

		// --<
		return $im_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a Post is been synced from a Contact.
	 *
	 * Sync any associated ACF Fields mapped to Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get all Instant Messenger Records for this Contact.
		$data = $this->ims_get_for_contact( $args['objectId'] );

		// Bail if there are no Instant Messenger Record Fields.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Instant Messenger Record Fields.
		if ( empty( $acf_fields['im'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['im'] as $selector => $im_field ) {

			// Init Field value.
			$value = [];

			// Let's look at each Instant Messenger in turn.
			foreach ( $data as $im ) {

				// Convert to ACF Instant Messenger data.
				$acf_im = $this->prepare_from_civicrm( $im );

				// Add to Field value.
				$value[] = $acf_im;

			}

			// Now update Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

		}

	}



	/**
	 * Update all of a CiviCRM Contact's Instant Messenger Records.
	 *
	 * @since 0.4
	 *
	 * @param array $values The array of Instant Messenger Records to update the Contact with.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $selector The ACF Field selector.
	 * @param array $args The array of WordPress params.
	 * @return array|bool $ims The array of Instant Messenger Records, or false on failure.
	 */
	public function ims_update( $values, $contact_id, $selector, $args = [] ) {

		// Init return.
		$ims = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $ims;
		}

		// Get the current Instant Messenger Records.
		$current = $this->ims_get_for_contact( $contact_id );

		// If there are no existing Instant Messenger Records.
		if ( empty( $current ) ) {

			// Create an Instant Messenger Record from each value.
			foreach ( $values as $key => $value ) {

				// Build required data.
				$im_data = $this->prepare_from_field( $value );

				// Okay, let's do it.
				$im = $this->update( $contact_id, $im_data );

				// Add to return array.
				$ims[] = $im;

				// Make an array of our params.
				$params = [
					'key' => $key,
					'value' => $value,
					'im' => $im,
					'contact_id' => $contact_id,
					'selector' => $selector,
				];

				/**
				 * Broadcast that an Instant Messenger Record has been created.
				 *
				 * We use this internally to update the ACF Field with the Instant Messenger ID.
				 *
				 * @since 0.4
				 *
				 * @param array $params The Instant Messenger data.
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/civicrm/im/created', $params, $args );

			}

			// No need to go any further.
			return $ims;

		}

		// We have existing Instant Messenger Records.
		$actions = [
			'create' => [],
			'update' => [],
			'delete' => [],
		];

		// Let's look at each ACF Record and check its Instant Messenger ID.
		foreach ( $values as $key => $value ) {

			// New Records have no Instant Messenger ID.
			if ( empty( $value['field_im_id'] ) ) {
				$actions['create'][ $key ] = $value;
				continue;
			}

			// Records to update have an Instant Messenger ID.
			if ( ! empty( $value['field_im_id'] ) ) {
				$actions['update'][ $key ] = $value;
				continue;
			}

		}

		// Grab the ACF Instant Messenger ID values.
		$acf_im_ids = wp_list_pluck( $values, 'field_im_id' );

		// Sanitise array contents.
		array_walk( $acf_im_ids, function( &$item ) {
			$item = (int) trim( $item );
		} );

		// Records to delete are missing from the ACF data.
		foreach ( $current as $current_im ) {
			if ( ! in_array( $current_im['id'], $acf_im_ids ) ) {
				$actions['delete'][] = $current_im['id'];
				continue;
			}
		}

		// Create CiviCRM Instant Messenger Records.
		foreach ( $actions['create'] as $key => $value ) {

			// Build required data.
			$im_data = $this->prepare_from_field( $value );

			// Okay, let's do it.
			$im = $this->update( $contact_id, $im_data );

			// Add to return array.
			$ims[] = $im;

			// Make an array of our params.
			$params = [
				'key' => $key,
				'value' => $value,
				'im' => $im,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that an Instant Messenger Record has been created.
			 *
			 * We use this internally to update the ACF Field with the Instant Messenger ID.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Instant Messenger data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/im/created', $params, $args );

		}

		// Update CiviCRM Instant Messenger Records.
		foreach ( $actions['update'] as $key => $value ) {

			// Build required data.
			$im_data = $this->prepare_from_field( $value, $value['field_im_id'] );

			// Okay, let's do it.
			$im = $this->update( $contact_id, $im_data );

			// Add to return array.
			$ims[] = $im;

			// Make an array of our params.
			$params = [
				'key' => $key,
				'value' => $value,
				'im' => $im,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that an Instant Messenger Record has been updated.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Instant Messenger data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/im/updated', $params, $args );

		}

		// Delete CiviCRM Instant Messenger Records.
		foreach ( $actions['delete'] as $im_id ) {

			// Okay, let's do it.
			$im = $this->delete( $im_id );

			// Make an array of our params.
			$params = [
				'im_id' => $im_id,
				'im' => $im,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that an Instant Messenger Record has been deleted.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Instant Messenger data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/im/deleted', $params, $args );

		}

	}



	/**
	 * Prepare the CiviCRM Instant Messenger Record from an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $value The array of Instant Messenger Record data in the ACF Field.
	 * @param integer $im_id The numeric ID of the Instant Messenger Record (or null if new).
	 * @return array $im_data The CiviCRM Instant Messenger Record data.
	 */
	public function prepare_from_field( $value, $im_id = null ) {

		// Init required data.
		$im_data = [];

		// Maybe add the Instant Messenger ID.
		if ( ! empty( $im_id ) ) {
			$im_data['id'] = $im_id;
		}

		// Convert ACF data to CiviCRM data.
		$im_data['is_primary'] = empty( $value['field_im_primary'] ) ? '0' : '1';
		$im_data['location_type_id'] = (int) $value['field_im_location'];
		$im_data['provider_id'] = (int) $value['field_im_provider'];
		$im_data['name'] = trim( $value['field_im_name'] );

		// --<
		return $im_data;

	}



	/**
	 * Prepare the ACF Field data from a CiviCRM Instant Messenger Record.
	 *
	 * @since 0.4
	 *
	 * @param array $value The array of Instant Messenger Record data in CiviCRM.
	 * @return array $im_data The ACF Instant Messenger data.
	 */
	public function prepare_from_civicrm( $value ) {

		// Init required data.
		$im_data = [];

		// Maybe cast as an object.
		if ( ! is_object( $value ) ) {
			$value = (object) $value;
		}

		// Convert CiviCRM data to ACF data.
		$im_data['field_im_name'] = trim( $value->name );
		$im_data['field_im_location'] = (int) $value->location_type_id;
		$im_data['field_im_provider'] = (int) $value->provider_id;
		$im_data['field_im_primary'] = empty( $value->is_primary ) ? '0' : '1';
		$im_data['field_im_id'] = (int) $value->id;

		// --<
		return $im_data;

	}



	/**
	 * Update a CiviCRM Contact's Instant Messenger Record.
	 *
	 * If you want to "create" an Instant Messenger Record, do not pass
	 * $data['id'] in. The presence of an ID will cause an update to that
	 * Instant Messenger Record.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $data The Instant Messenger data to update the Contact with.
	 * @return array|bool $im The array of Instant Messenger Record data, or false on failure.
	 */
	public function update( $contact_id, $data ) {

		// Init return.
		$im = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $im;
		}

		// Define params to create new Instant Messenger Record.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Im', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $im;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $im;
		}

		// The result set should contain only one item.
		$im = array_pop( $result['values'] );

		// --<
		return $im;

	}



	/**
	 * Delete an Instant Messenger Record in CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param integer $im_id The numeric ID of the Instant Messenger Record.
	 * @return bool $success True if successfully deleted, or false on failure.
	 */
	public function delete( $im_id ) {

		// Init return.
		$success = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Define params to delete this Instant Messenger Record.
		$params = [
			'version' => 3,
			'id' => $im_id,
		];

		// Call the API.
		$result = civicrm_api( 'Im', 'delete', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $success;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $success;
		}

		// The result set should contain only one item.
		$success = ( $result['values'] == '1' ) ? true : false;

		// --<
		return $success;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Instant Messenger Record has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function im_edited( $args ) {

		// Grab the Instant Messenger Record data.
		$civicrm_im = $args['objectRef'];

		// Bail if this is not a Contact's Instant Messenger Record.
		if ( empty( $civicrm_im->contact_id ) ) {
			return;
		}

		// Process the Instant Messenger Record.
		$this->im_process( $civicrm_im, $args );

	}



	/**
	 * A CiviCRM Contact's Instant Messenger Record is about to be deleted.
	 *
	 * Before an Instant Messenger Record is deleted, we need to retrieve the
	 * Instant Messenger Record because the data passed via "civicrm_post" only
	 *  contains the ID of the Instant Messenger Record.
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
		$im_pre = $this->im_get_by_id( $im_id );

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

		// Process the Instant Messenger Record.
		$this->im_process( $this->im_pre, $args );

	}



	/**
	 * Process a CiviCRM Instant Messenger Record.
	 *
	 * @since 0.4
	 *
	 * @param object $im The CiviCRM Instant Messenger Record object.
	 * @param array $args The array of CiviCRM params.
	 */
	public function im_process( $im, $args ) {

		// Convert to ACF Instant Messenger data.
		$acf_im = $this->prepare_from_civicrm( $im );

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $im->contact_id );

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Test if any of this Contact's Contact Types is mapped to a Post Type.
		$post_types = $this->civicrm->contact->is_mapped( $contact, 'create' );
		if ( $post_types !== false ) {

			// Handle each Post Type in turn.
			foreach ( $post_types as $post_type ) {

				// Get the Post ID for this Contact.
				$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );

				// Skip if not mapped or Post doesn't yet exist.
				if ( $post_id === false ) {
					continue;
				}

				// Exclude "reverse" edits when a Post is the originator.
				if ( $entity['entity'] === 'post' && $post_id == $entity['id'] ) {
					continue;
				}

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $im, $acf_im, $args );

			}

		}

		/**
		 * Broadcast that an Instant Messenger ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $im The CiviCRM Instant Messenger Record object.
		 * @param array $acf_im The ACF Instant Messenger Record array.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/im/updated', $contact, $im, $acf_im, $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update Instant Messenger ACF Fields on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object $im The CiviCRM Instant Messenger Record object.
	 * @param array $acf_im The ACF Instant Messenger Record array.
	 * @param array $args The array of CiviCRM params.
	 */
	public function fields_update( $post_id, $im, $acf_im, $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Instant Messenger Record Fields.
		if ( empty( $acf_fields['im'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['im'] as $selector => $im_field ) {

			// Get existing Field value.
			$existing = get_field( $selector, $post_id );

			// Before applying edit, make some checks.
			if ( $args['op'] == 'edit' ) {

				// If there is no existing Field value, treat as a 'create' op.
				if ( empty( $existing ) ) {
					$args['op'] = 'create';
				} else {

					// Grab the ACF Instant Messenger ID values.
					$acf_im_ids = wp_list_pluck( $existing, 'field_im_id' );

					// Sanitise array contents.
					array_walk( $acf_im_ids, function( &$item ) {
						$item = (int) trim( $item );
					} );

					// If the ID is missing, treat as a 'create' op.
					if ( ! in_array( $im->id, $acf_im_ids ) ) {
						$args['op'] = 'create';
					}

				}

			}

			// Process array record.
			switch ( $args['op'] ) {

				case 'create':

					// Make sure no other Instant Messenger is Primary if this one is.
					if ( $acf_im['field_im_primary'] == '1' && ! empty( $existing ) ) {
						foreach ( $existing as $key => $record ) {
							$existing[ $key ]['field_im_primary'] = '0';
						}
					}

					// Add array record.
					$existing[] = $acf_im;

					break;

				case 'edit':

					// Make sure no other Instant Messenger is Primary if this one is.
					if ( $acf_im['field_im_primary'] == '1' ) {
						foreach ( $existing as $key => $record ) {
							$existing[ $key ]['field_im_primary'] = '0';
						}
					}

					// Overwrite array record.
					foreach ( $existing as $key => $record ) {
						if ( $im->id == $record['field_im_id'] ) {
							$existing[ $key ] = $acf_im;
							break;
						}
					}

					break;

				case 'delete':

					// Remove array record.
					foreach ( $existing as $key => $record ) {
						if ( $im->id == $record['field_im_id'] ) {
							unset( $existing[ $key ] );
							break;
						}
					}

					break;

			}

			// Now update Field.
			$this->acf_loader->acf->field->value_update( $selector, $existing, $post_id );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Instant Messenger Locations that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $location_types The array of possible Instant Messenger Locations.
	 */
	public function location_types_get_for_acf_field( $field ) {

		// Init return.
		$location_types = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $location_types;
		}

		// Instant Messenger Locations are standard Location Types.
		$location_types = $this->plugin->civicrm->address->location_types_get();

		/**
		 * Filter the retrieved Location Types.
		 *
		 * @since 0.4
		 *
		 * @param array $location_types The retrieved array of Location Types.
		 * @param array $field The ACF Field data array.
		 */
		$location_types = apply_filters(
			'cwps/acf/im/location_types/get_for_acf_field',
			$location_types, $field
		);

		// --<
		return $location_types;

	}



	/**
	 * Get the Instant Messenger Providers that are defined in CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @return array $im_providers The array of possible Instant Messenger Providers.
	 */
	public function im_providers_get() {

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$im_providers = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $im_providers;
		}

		// Get the Instant Messenger Providers array.
		$im_provider_ids = CRM_Core_PseudoConstant::get( 'CRM_Core_DAO_IM', 'provider_id' );

		// Bail if there are no results.
		if ( empty( $im_provider_ids ) ) {
			return $im_providers;
		}

		// Assign to return.
		$im_providers = $im_provider_ids;

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $im_providers;
		}

		// --<
		return $im_providers;

	}



	/**
	 * Get the Instant Messenger Providers that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $im_providers The array of possible Instant Messenger Providers.
	 */
	public function im_providers_get_for_acf_field( $field ) {

		// Init return.
		$im_providers = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $im_providers;
		}

		// Get the Instant Messenger Providers array.
		$im_provider_ids = $this->im_providers_get();

		// Bail if there are no results.
		if ( empty( $im_provider_ids ) ) {
			return $im_providers;
		}

		// Assign to return.
		$im_providers = $im_provider_ids;

		// --<
		return $im_providers;

	}



	/**
	 * Add any Instant Messenger Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Add if it has a reference to an Instant Messenger Field.
		if ( ! empty( $field['type'] ) && $field['type'] == 'civicrm_im' ) {
			$acf_fields['im'][ $field['name'] ] = $field['type'];
		}

		// --<
		return $acf_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Sync the CiviCRM "Instant Messenger ID" to the ACF Fields on a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param array $params The Instant Messenger data.
	 * @param array $args The array of WordPress params.
	 */
	public function maybe_sync_im_id( $params, $args ) {

		// Get Entity reference.
		$entity = $this->acf_loader->acf->field->entity_type_get( $args['post_id'] );

		// Check permissions if it's a Post.
		if ( $entity === 'post' ) {
			if ( ! current_user_can( 'edit_post', $args['post_id'] ) ) {
				return;
			}
		}

		// Check permissions if it's a User.
		if ( $entity === 'user' ) {
			if ( ! current_user_can( 'edit_user', $args['user_id'] ) ) {
				return;
			}
		}

		// Maybe cast Instant Messenger data as an object.
		if ( ! is_object( $params['im'] ) ) {
			$params['im'] = (object) $params['im'];
		}

		// Get existing Field value.
		$existing = get_field( $params['selector'], $args['post_id'] );

		// Add Instant Messenger ID and overwrite array element.
		if ( ! empty( $existing[ $params['key'] ] ) ) {
			$params['value']['field_im_id'] = $params['im']->id;
			$existing[ $params['key'] ] = $params['value'];
		}

		// Now update Field.
		$this->acf_loader->acf->field->value_update( $params['selector'], $existing, $args['post_id'] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Instant Messenger Fields.
	 *
	 * @since 0.5
	 *
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function civicrm_fields_get( $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ] ) ) {
			return $pseudocache[ $filter ];
		}

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Im', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Instant Messenger Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->im_fields ) ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ] ) ) {
			$pseudocache[ $filter ] = $fields;
		}

		// --<
		return $fields;

	}



	/**
	 * Get the Instant Messenger Field options for a given Field ID.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Field.
	 * @return array $field The array of Field data.
	 */
	public function get_by_name( $name ) {

		// Init return.
		$field = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'name' => $name,
			'action' => 'get',
		];

		// Call the API.
		$result = civicrm_api( 'Im', 'getfield', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $field;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $field;
		}

		// The result set is the item.
		$field = $result['values'];

		// --<
		return $field;

	}



	/**
	 * Get the mapped Instant Messenger Field name if present.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing Field data array.
	 * @return string|bool $im_field_name The name of the Instant Messenger Field, or false if none.
	 */
	public function im_field_name_get( $field ) {

		// Init return.
		$im_field_name = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Set the mapped Instant Messenger Field name if present.
		if ( isset( $field[ $acf_field_key ] ) ) {
			if ( false !== strpos( $field[ $acf_field_key ], $this->im_field_prefix ) ) {
				$im_field_name = (string) str_replace( $this->im_field_prefix, '', $field[ $acf_field_key ] );
			}
		}

		/**
		 * Filter the Instant Messenger Field name.
		 *
		 * @since 0.5
		 *
		 * @param integer $im_field_name The existing Instant Messenger Field name.
		 * @param array $field The array of ACF Field data.
		 */
		$im_field_name = apply_filters( 'cwps/acf/civicrm/im/im_field/name', $im_field_name, $field );

		// --<
		return $im_field_name;

	}



	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
	 *
	 * The Instant Messenger Entity cannot have Custom Fields attached to it, so
	 * we can skip that part of the logic.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The existing Setting Field choices array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param array $entity_array The Entity and ID array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_bypass_settings_choices( $choices, $field, $field_group, $entity_array ) {

		// Pass if a Contact Entity is not present.
		if ( ! array_key_exists( 'contact', $entity_array ) ) {
			return $choices;
		}

		// Get the public Fields on the Entity for this Field Type.
		$public_fields = $this->civicrm_fields_get( 'public' );
		$fields_for_entity = [];
		foreach ( $public_fields as $key => $value ) {
			if ( $field['type'] == $this->im_fields[ $value['name'] ] ) {
				$fields_for_entity[] = $value;
			}
		}

		// Pass if not populated.
		if ( empty( $fields_for_entity ) ) {
			return $choices;
		}

		// Build Instant Messenger Field choices array for dropdown.
		$im_fields_label = esc_attr__( 'Instant Messenger Fields', 'civicrm-wp-profile-sync' );
		foreach ( $fields_for_entity as $im_field ) {
			$choices[ $im_fields_label ][ $this->im_field_prefix . $im_field['name'] ] = $im_field['title'];
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/im/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



	/**
	 * Modify the Settings of an ACF "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function select_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'select' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Instant Messenger Field name if present.
		$field_name = $this->im_field_name_get( $field );
		if ( $field_name === false ) {
			return $field;
		}

		// Get keyed array of options for this Instant Messenger Field.
		$field['choices'] = $this->options_get( $field_name );

		// "Provider ID" is optional.
		$field['allow_null'] = 1;

		// --<
		return $field;

	}



	/**
	 * Get the "select" options for a given CiviCRM Instant Messenger Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Instant Messenger Field.
	 * @return array $options The array of Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// We only have a few to account for.

		// Provider IDs.
		if ( $name == 'provider_id' ) {
			$options = $this->im_providers_get();
		}

		// --<
		return $options;

	}



	/**
	 * Modify the Settings of an ACF "Text" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function text_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'text' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Instant Messenger Field name if present.
		$phone_field_name = $this->im_field_name_get( $field );
		if ( $phone_field_name === false ) {
			return $field;
		}

		// Get Instant Messenger Field data.
		$field_data = $this->get_by_name( $phone_field_name );

		// Set the "maxlength" attribute.
		if ( ! empty( $field_data['maxlength'] ) ) {
			$field['maxlength'] = $field_data['maxlength'];
		}

		// --<
		return $field;

	}



} // Class ends.



