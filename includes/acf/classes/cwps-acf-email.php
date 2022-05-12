<?php
/**
 * CiviCRM Email Class.
 *
 * Handles CiviCRM Email functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Email Class.
 *
 * A class that encapsulates CiviCRM Email functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Email extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
	 * "CiviCRM Email" Field key in the ACF Field data.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $acf_field_key The key of the "CiviCRM Email" in the ACF Field data.
	 */
	public $acf_field_key = 'field_cacf_civicrm_email';

	/**
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Email Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $email_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $email_field_prefix = 'caiemail_';

	/**
	 * Contact Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $fields_handled The array of Contact Fields which must be handled separately.
	 */
	public $fields_handled = [
		'email',
	];

	/**
	 * Public Email Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $email_fields The array of public Email Fields.
	 */
	public $email_fields = [
		'is_primary' => 'true_false',
		'is_billing' => 'true_false',
		'email' => 'email',
		'on_hold' => 'true_false',
		'is_bulkmail' => 'true_false',
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

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add any Email Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post created from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/acf/query_settings_field', [ $this, 'query_settings_field' ], 51, 3 );

		// Listen for queries from the ACF Bypass class.
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

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

		// Listen for events from our Mapper that require Email updates.
		add_action( 'cwps/acf/mapper/email/created', [ $this, 'email_edited' ], 10 );
		add_action( 'cwps/acf/mapper/email/edited', [ $this, 'email_edited' ], 10 );
		add_action( 'cwps/acf/mapper/email/deleted', [ $this, 'email_edited' ], 10 );

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
		remove_action( 'cwps/acf/mapper/email/created', [ $this, 'email_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/email/edited', [ $this, 'email_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/email/deleted', [ $this, 'email_edited' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}



	// -------------------------------------------------------------------------



	/**
	 * Update CiviCRM Email Fields with data from ACF Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function fields_handled_update( $args ) {

		// Bail if we have no Field data to save.
		if ( empty( $args['fields'] ) ) {
			return true;
		}

		// Init success.
		$success = true;

		// Loop through the Field data.
		foreach ( $args['fields'] as $field => $value ) {

			// Get the Field settings.
			$settings = get_field_object( $field, $args['post_id'] );
			if ( empty( $settings ) ) {
				continue;
			}

			// Maybe update a Contact Field.
			$this->field_handled_update( $field, $value, $args['contact']['id'], $settings );

		}

		// --<
		return $success;

	}



	/**
	 * Update a CiviCRM Email Field with data from an ACF Field.
	 *
	 * This Contact Field requires special handling because it is not part
	 * of the core Contact data.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data.
	 * @param mixed $value The ACF Field value.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $settings The ACF Field settings.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function field_handled_update( $field, $value, $contact_id, $settings ) {

		// Skip if it's not a Field that this class handles.
		if ( ! in_array( $settings['type'], $this->fields_handled ) ) {
			return true;
		}

		// Get the "CiviCRM Email" key.
		$email_key = $this->acf_field_key_get();

		// Skip if we don't have a synced Email.
		if ( empty( $settings[ $email_key ] ) ) {
			return true;
		}

		// Parse value by Field Type.
		$value = $this->acf_loader->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings );

		// Is this mapped to the Primary Email?
		if ( $settings[ $email_key ] == 'primary' ) {

			// Update and return early.
			$this->primary_email_update( $contact_id, $value );
			return true;

		}

		// The ID of the Location Type is the setting.
		$location_type_id = absint( $settings[ $email_key ] );

		// Update the Email.
		$this->email_update( $location_type_id, $contact_id, $value );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for an Email.
	 *
	 * @since 0.5
	 *
	 * @param integer $email_id The numeric ID of the Email.
	 * @return object $email The array of Email data, or empty if none.
	 */
	public function email_get_by_id( $email_id ) {

		// Init return.
		$email = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $email_id,
		];

		// Get Email details via API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $email;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = (object) array_pop( $result['values'] );

		// --<
		return $email;

	}



	/**
	 * Get the data for a Contact's Email by Location Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param integer $location_type_id The numeric ID of the Email Location Type.
	 * @return object $email The array of Email data, or empty if none.
	 */
	public function email_get_by_location( $contact_id, $location_type_id ) {

		// Init return.
		$email = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'location_type_id' => $location_type_id,
		];

		// Get Email details via API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $email;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = (object) array_pop( $result['values'] );

		// --<
		return $email;

	}



	/**
	 * Get the Primary Email for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return object|bool $email The Primary Email data object, or false on failure.
	 */
	public function primary_email_get( $contact_id ) {

		// Init return.
		$email = false;

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $email;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Define params to get queried Email.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_primary' => 1,
			'contact_id' => $contact_id,
		];

		// Call the API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $email;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = (object) array_pop( $result['values'] );

		// --<
		return $email;

	}



	/**
	 * Update a CiviCRM Contact's Primary Email.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $value The email to update the Contact with.
	 * @return array|bool $email The array of Email data, or false on failure.
	 */
	public function primary_email_update( $contact_id, $value ) {

		// Init return.
		$email = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Get the current Primary Email.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'is_primary' => 1,
		];

		// Call the CiviCRM API.
		$primary_email = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $primary_email['is_error'] ) && $primary_email['is_error'] == 1 ) {
			return $email;
		}

		// Create a Primary Email if there are no results.
		if ( empty( $primary_email['values'] ) ) {

			// Define params to create new Primary Email.
			$params = [
				'version' => 3,
				'contact_id' => $contact_id,
				'is_primary' => 1,
				'email' => $value,
			];

			// Call the API.
			$result = civicrm_api( 'Email', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $primary_email['values'] );

			// Bail if it hasn't changed.
			if ( $existing_data['email'] == $value ) {
				return $existing_data;
			}

			// If there is an incoming value, update.
			if ( ! empty( $value ) ) {

				// Define params to update this Email.
				$params = [
					'version' => 3,
					'id' => $primary_email['id'],
					'contact_id' => $contact_id,
					'email' => $value,
				];

				// Call the API.
				$result = civicrm_api( 'Email', 'create', $params );

			} else {

				// Define params to delete this Email.
				$params = [
					'version' => 3,
					'id' => $primary_email['id'],
				];

				// Call the API.
				$result = civicrm_api( 'Email', 'delete', $params );

				// Bail early.
				return $email;

			}

		}

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
			return $email;
		}

		// The result set should contain only one item.
		$email = array_pop( $result['values'] );

		// --<
		return $email;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Emails for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $email_data The array of Email data for the CiviCRM Contact.
	 */
	public function emails_get_for_contact( $contact_id ) {

		// Init return.
		$email_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $email_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email_data;
		}

		// Define params to get queried Emails.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $email_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email_data;
		}

		// The result set it what we want.
		$email_data = $result['values'];

		// --<
		return $email_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Sends an Email when the Email API Extension is present.
	 *
	 * @since 0.5
	 *
	 * @param array $email_params The array of Email params.
	 * @return array|bool $email The array of Email data, or false on failure.
	 */
	public function email_send( $email_params ) {

		// Init return.
		$email = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Bail if the "Email API" Extension is not enabled.
		$email_active = $this->plugin->civicrm->is_extension_enabled( 'org.civicoop.emailapi' );
		if ( ! $email_active ) {
			return $email;
		}

		// Construct API query.
		$params = [
			'version' => 3,
		] + $email_params;

		// Get Email details via API.
		$result = civicrm_api( 'Email', 'send', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $email;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $email;
		}

		// The result set should contain only one item.
		$email = array_pop( $result['values'] );

		// --<
		return $email;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the set of Email Templates.
	 *
	 * @since 0.5
	 *
	 * @return array $templates The array of Email Templates.
	 */
	public function templates_get() {

		// Init return.
		$templates = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $templates;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'return' => [
				'id',
				'msg_title',
				'msg_subject',
			],
			'workflow_id' => [
				'IS NULL' => 1,
			],
			'options' => [
				'limit' => 0,
			],
		];

		// Get Email Tempates via API.
		$result = civicrm_api( 'MessageTemplate', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $templates;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $templates;
		}

		// The result set is what we want.
		$templates = $result['values'];

		// --<
		return $templates;

	}



	/**
	 * Gets the options for displaying Email Templates in an ACF select.
	 *
	 * @since 0.5
	 *
	 * @return array $options The array of Email Template options, or false on failure.
	 */
	public function template_options_get() {

		// Return early if already calculated.
		static $options;
		if ( isset( $options ) ) {
			return $options;
		}

		// Get the Email Templates array.
		$templates = $this->templates_get();
		if ( empty( $templates ) ) {
			return $templates;
		}

		// Build return array.
		$options = [];
		foreach ( $templates as $key => $value ) {
			$options[ $value['id'] ] = $value['msg_title'];
		}

		// --<
		return $options;

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

		// Get all Emails for this Contact.
		$data = $this->emails_get_for_contact( $args['objectId'] );

		// Bail if there are no Email Fields.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Email Fields.
		if ( empty( $acf_fields['email'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['email'] as $selector => $email_field ) {

			// Let's look at each Email in turn.
			foreach ( $data as $email ) {

				// Cast as object.
				$email = (object) $email;

				// If this is mapped to the Primary Email.
				if ( $email_field == 'primary' && ! empty( $email->is_primary ) && $email->is_primary == '1' ) {
					$this->acf_loader->acf->field->value_update( $selector, $email->email, $args['post_id'] );
					continue;
				}

				// Skip if the Location Types don't match.
				if ( $email_field != $email->location_type_id ) {
					continue;
				}

				// Update it.
				$this->acf_loader->acf->field->value_update( $selector, $email->email, $args['post_id'] );

			}

		}

	}



	/**
	 * Update a CiviCRM Contact's Email Address.
	 *
	 * @since 0.4
	 *
	 * @param integer $location_type_id The numeric ID of the Location Type.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $value The Email to update the Contact with.
	 * @return array|bool $email The array of Email data, or false on failure.
	 */
	public function email_update( $location_type_id, $contact_id, $value ) {

		// Init return.
		$email = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Get the current Email for this Location Type.
		$params = [
			'version' => 3,
			'location_type_id' => $location_type_id,
			'contact_id' => $contact_id,
		];

		// Call the CiviCRM API.
		$existing_email = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $existing_email['is_error'] ) && $existing_email['is_error'] == 1 ) {
			return $email;
		}

		// Create a new Email if there are no results.
		if ( empty( $existing_email['values'] ) ) {

			// Define params to create new Email.
			$params = [
				'version' => 3,
				'location_type_id' => $location_type_id,
				'contact_id' => $contact_id,
				'email' => $value,
			];

			// Call the API.
			$result = civicrm_api( 'Email', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $existing_email['values'] );

			// Bail if it hasn't changed.
			if ( $existing_data['email'] == $value ) {
				return $existing_data;
			}

			// If there is an incoming value, update.
			if ( ! empty( $value ) ) {

				// Define params to update this Email.
				$params = [
					'version' => 3,
					'id' => $existing_email['id'],
					'contact_id' => $contact_id,
					'email' => $value,
				];

				// Call the API.
				$result = civicrm_api( 'Email', 'create', $params );

			} else {

				// Define params to delete this Email.
				$params = [
					'version' => 3,
					'id' => $existing_email['id'],
				];

				// Call the API.
				$result = civicrm_api( 'Email', 'delete', $params );

				// Bail early.
				return $email;

			}

		}

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
			return $email;
		}

		// The result set should contain only one item.
		$email = array_pop( $result['values'] );

		// --<
		return $email;

	}



	/**
	 * Update a CiviCRM Contact's Email Address Record.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $data The Email data to save.
	 * @return array|bool $email The array of Email data, or false on failure.
	 */
	public function email_record_update( $contact_id, $data ) {

		// Init return.
		$email = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $email;
		}

		// Get the current Email for this Location Type.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
			'location_type_id' => $data['location_type_id'],
		];

		// Call the CiviCRM API.
		$existing_email = civicrm_api( 'Email', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $existing_email['is_error'] ) && $existing_email['is_error'] == 1 ) {
			return $email;
		}

		// Create a new Email if there are no results.
		if ( empty( $existing_email['values'] ) ) {

			// Define params to create new Email.
			$params = [
				'version' => 3,
				'contact_id' => $contact_id,
			] + $data;

			// Call the API.
			$result = civicrm_api( 'Email', 'create', $params );

		} else {

			// There should be only one item.
			$existing_data = array_pop( $existing_email['values'] );

			/*
			// Bail if it hasn't changed.
			if ( $existing_data['email'] == $value ) {
				return $existing_data;
			}
			*/

			// Define default params to update this Email.
			$params = [
				'version' => 3,
				'id' => $existing_data['id'],
				'contact_id' => $contact_id,
			] + $data;

			// Call the API.
			$result = civicrm_api( 'Email', 'create', $params );

		}

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
			return $email;
		}

		// The result set should contain only one item.
		$email = array_pop( $result['values'] );

		// --<
		return $email;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Email has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function email_edited( $args ) {

		// Grab the Email data.
		$email_data = $args['objectRef'];

		// Bail if this is not a Contact's Email.
		if ( empty( $email_data->contact_id ) ) {
			return;
		}

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $email_data->contact_id );
		if ( $contact === false ) {
			return;
		}

		// Data may be missing for some operations, so get the full Email record.
		$email = $this->email_get_by_id( $email_data->id );
		if ( empty( $email->contact_id ) ) {
			return;
		}

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

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $email );

			}

		}

		/**
		 * Broadcast that an Email ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $email The CiviCRM Email object.
		 */
		do_action( 'cwps/acf/email/updated', $contact, $email );

	}



	/**
	 * Update an Email ACF Field on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object $email The CiviCRM email object.
	 */
	public function fields_update( $post_id, $email ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Email Fields.
		if ( empty( $acf_fields['email'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['email'] as $selector => $email_field ) {

			// If this is mapped to the Primary Email.
			if ( $email_field == 'primary' && ! empty( $email->is_primary ) ) {
				$this->acf_loader->acf->field->value_update( $selector, $email->email, $post_id );
				continue;
			}

			// Skip if the Location Types don't match.
			if ( $email_field != $email->location_type_id ) {
				continue;
			}

			// Update it.
			$this->acf_loader->acf->field->value_update( $selector, $email->email, $post_id );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Email Fields.
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
		$result = civicrm_api( 'Email', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Email Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->email_fields ) ) {
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



	// -------------------------------------------------------------------------



	/**
	 * Get the Location Types that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $location_types The array of possible Location Types.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$location_types = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $location_types;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $location_types;
		}

		// Params to get all Location Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'options' => [
				'limit' => 0,
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'LocationType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $location_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $location_types;
		}

		/**
		 * Filter the retrieved Location Types.
		 *
		 * @since 0.4
		 *
		 * @param array $location_types The retrieved array of Location Types.
		 * @param array $field The ACF Field data array.
		 */
		$location_types = apply_filters(
			'cwps/acf/email/location_types/get_for_acf_field',
			$result['values'], $field
		);

		// --<
		return $location_types;

	}



	/**
	 * Return the "CiviCRM Email" ACF Settings Field.
	 *
	 * The "Email" Field cannot map to a CiviCRM Custom Field because there isn't
	 * a matching CiviCRM Custom Field Type. The param is still present to keep
	 * the method signature the same as all other Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $custom_fields The Custom Fields to populate the ACF Field with.
	 * @param array $location_types The Location Types to populate the ACF Field with.
	 * @param bool $skip_specific True skips adding the "Primary Email" choice.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $custom_fields = [], $location_types = [], $skip_specific = false ) {

		// Bail if empty.
		if ( empty( $custom_fields ) && empty( $location_types ) ) {
			return;
		}

		// Build choices array for dropdown.
		$choices = [];

		// Maybe prepend "Primary Email" choice for dropdown.
		if ( $skip_specific === false ) {
			$specific_email_label = esc_attr__( 'Specific Emails', 'civicrm-wp-profile-sync' );
			$choices[ $specific_email_label ]['primary'] = esc_attr__( 'Primary Email', 'civicrm-wp-profile-sync' );
		}

		// Build Location Types choices array for dropdown.
		$location_types_label = esc_attr__( 'Location Types', 'civicrm-wp-profile-sync' );
		foreach ( $location_types as $location_type ) {
			$choices[ $location_types_label ][ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Define Field.
		$field = [
			'key' => $this->acf_field_key_get(),
			'label' => __( 'CiviCRM Email', 'civicrm-wp-profile-sync' ),
			'name' => $this->acf_field_key_get(),
			'type' => 'select',
			'instructions' => __( 'Choose the CiviCRM Email that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'required' => 0,
			'return_format' => 'value',
			'parent' => $this->acf_loader->acf->field_group->placeholder_group_get(),
			'choices' => $choices,
		];

		// --<
		return $field;

	}



	/**
	 * Getter method for the "CiviCRM Email" key.
	 *
	 * @since 0.4
	 *
	 * @return string $acf_field_key The key of the "CiviCRM Email" in the ACF Field data.
	 */
	public function acf_field_key_get() {

		// --<
		return $this->acf_field_key;

	}



	/**
	 * Add any Email Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Get the "CiviCRM Email" key.
		$email_key = $this->acf_field_key_get();

		// Add if it has a reference to an Email Field.
		if ( ! empty( $field[ $email_key ] ) ) {
			$acf_fields['email'][ $field['name'] ] = $field[ $email_key ];
		}

		// --<
		return $acf_fields;

	}



	/**
	 * Returns a Setting Field for an ACF "Email" Field when found.
	 *
	 * The CiviCRM "Email" Entity can only be attached to a Contact. This means
	 * it can be part of a "Contact Field Group" and a "User Field Group" in ACF.
	 *
	 * It must exclude the "Primary Email" choice if the Field Group can be shown
	 * on a "User Form" since this plugin already maps the built-in User Email
	 * Field to the "Primary Email".
	 *
	 * The "Email" Field cannot map to a CiviCRM Custom Field because there isn't
	 * a matching CiviCRM Custom Field Type.
	 *
	 * @since 0.5
	 *
	 * @param array $setting_field The existing Setting Field array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param bool $skip_check True if the check for Field Group should be skipped. Default false.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_settings_field( $setting_field, $field, $field_group, $skip_check = false ) {

		// Pass if this is not our Field Type.
		if ( 'email' !== $field['type'] ) {
			return $setting_field;
		}

		// Pass if this is not a Contact Field Group or a User Field Group.
		$is_contact_field_group = $this->civicrm->contact->is_contact_field_group( $field_group );
		$is_user_field_group = $this->acf_loader->user->is_user_field_group( $field_group );
		if ( empty( $is_contact_field_group ) && empty( $is_user_field_group ) ) {
			return $setting_field;
		}

		// Get the Email Fields for this ACF Field.
		$email_fields = $this->get_for_acf_field( $field );

		// Pass if not populated.
		if ( empty( $email_fields ) ) {
			return $setting_field;
		}

		// Maybe exclude the "Primary Email" choice.
		$skip_primary = false;
		if ( ! empty( $is_user_field_group ) ) {
			$skip_primary = true;
		}

		// Get the Setting Field.
		$setting_field = $this->acf_field_get( [], $email_fields, $skip_primary );

		// Return populated array.
		return $setting_field;

	}



	// -------------------------------------------------------------------------



	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
	 *
	 * The Email Entity cannot have Custom Fields attached to it, so we can skip
	 * that part of the logic.
	 *
	 * In fact, this isn't really necessary at all - all the logic is handled in
	 * the ACFE Form Action - but it might be confusing for people if they are
	 * unable to map some ACF Fields when they're building a Field Group for an
	 * ACFE Form... so here it is *shrug*
	 *
	 * To disable, comment out the "cwps/acf/bypass/query_settings_choices" filter.
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
			if ( $field['type'] == $this->email_fields[ $value['name'] ] ) {
				$fields_for_entity[] = $value;
			}
		}

		// Pass if not populated.
		if ( empty( $fields_for_entity ) ) {
			return $choices;
		}

		// Build Email Field choices array for dropdown.
		$email_fields_label = esc_attr__( 'Email Fields', 'civicrm-wp-profile-sync' );
		foreach ( $fields_for_entity as $email_field ) {
			$choices[ $email_fields_label ][ $this->email_field_prefix . $email_field['name'] ] = $email_field['title'];
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/email/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



} // Class ends.



