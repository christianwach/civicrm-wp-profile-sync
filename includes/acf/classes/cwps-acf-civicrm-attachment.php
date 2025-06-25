<?php
/**
 * CiviCRM Attachment Class.
 *
 * Handles CiviCRM Attachment functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Attachment Class.
 *
 * A class that encapsulates CiviCRM Attachment functionality.
 *
 * @since 0.5.2
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Attachment {

	/**
	 * Plugin object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * CiviCRM hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $civicrm_hooks = false;

	/**
	 * WordPress hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $wordpress_hooks = false;

	/**
	 * File Fields found in a Field Group.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var array
	 */
	public $file_fields;

	/**
	 * WordPress Attachment meta key.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var string
	 */
	public $attachment_wp_key = '_cwps_attachment_wordpress_file';

	/**
	 * CiviCRM Attachment meta key.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var string
	 */
	public $attachment_civicrm_key = '_cwps_attachment_civicrm_file';

	/**
	 * Public Attachment Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var array
	 */
	public $attachment_fields = [
		'description' => 'text',
	];

	/**
	 * Constructor.
	 *
	 * @since 0.5.2
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm    = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.5.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.5.4
		 */
		do_action( 'cwps/acf/civicrm/attachment/loaded' );

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.5.2
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Always register WordPress hooks.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// $this->register_wordpress_hooks();

		// Add CiviCRM listeners once CiviCRM is available.
		add_action( 'civicrm_config', [ $this, 'civicrm_config' ], 10, 1 );

		// Build array of CiviCRM URLs and maybe filter the ACF Attachment URL.
		add_filter( 'acf/load_value/type=file', [ $this, 'acf_load_filter' ], 10, 3 );
		add_filter( 'acf/render_field/type=file', [ $this, 'acf_render_filter' ], 9, 3 );
		add_filter( 'acf/load_attachment', [ $this, 'acf_attachment_filter' ], 10, 3 );

		// ACF File Fields require additional settings.
		add_action( 'cwps/acf/field/entity_field_setting/added', [ $this, 'field_settings_acfe_add' ], 10, 3 );
		add_action( 'cwps/acf/field/generic_field_setting/added', [ $this, 'field_settings_acf_add' ], 10, 3 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'field_settings_modify' ], 10, 2 );

		// Listen for queries from the ACF Bypass class.
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 5, 4 );

	}

	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.5.2
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( true === $this->mapper_hooks ) {
			return;
		}

		// Intercept before a set of ACF Fields has been updated.
		add_action( 'cwps/acf/mapper/acf_fields/saved/pre', [ $this, 'acf_fields_pre_save' ], 10 );

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.5.2
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( false === $this->mapper_hooks ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/acf_fields/saved/pre', [ $this, 'acf_fields_pre_save' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	/**
	 * Callback for "civicrm_config".
	 *
	 * @since 0.5.2
	 *
	 * @param object $config The CiviCRM config object.
	 */
	public function civicrm_config( &$config ) {

		// Add CiviCRM listeners once CiviCRM is available.
		$this->register_civicrm_hooks();

	}

	/**
	 * Add listeners for CiviCRM Entity Tag operations.
	 *
	 * @since 0.5.2
	 */
	public function register_civicrm_hooks() {

		// Bail if already registered.
		if ( true === $this->civicrm_hooks ) {
			return;
		}

		// Intercept before a CiviCRM File/Attachment is deleted.
		add_action( 'cwps/acf/mapper/attachment/delete/pre', [ $this, 'civicrm_file_pre_delete' ], 10 );

		// Declare registered.
		$this->civicrm_hooks = true;

	}

	/**
	 * Remove listeners from CiviCRM Entity Tag operations.
	 *
	 * @since 0.5.2
	 */
	public function unregister_civicrm_hooks() {

		// Bail if already unregistered.
		if ( false === $this->civicrm_hooks ) {
			return;
		}

		// Remove Mapper callbacks.
		remove_action( 'cwps/acf/mapper/attachment/delete/pre', [ $this, 'civicrm_file_pre_delete' ], 10 );

		// Declare unregistered.
		$this->civicrm_hooks = false;

	}

	/**
	 * Add listeners for WordPress Attachment operations.
	 *
	 * @since 0.5.2
	 */
	public function register_wordpress_hooks() {

		// Bail if already registered.
		if ( true === $this->wordpress_hooks ) {
			return;
		}

		// Add callback for when a WordPress Attachment is deleted.
		add_action( 'delete_attachment', [ $this, 'wordpress_attachment_deleted' ], 10 );

		// Declare registered.
		$this->wordpress_hooks = true;

	}

	/**
	 * Remove listeners from WordPress Attachment operations.
	 *
	 * @since 0.5.2
	 */
	public function unregister_wordpress_hooks() {

		// Bail if already unregistered.
		if ( false === $this->wordpress_hooks ) {
			return;
		}

		// Remove WordPress callbacks.
		remove_action( 'delete_attachment', [ $this, 'wordpress_attachment_deleted' ], 10 );

		// Declare unregistered.
		$this->wordpress_hooks = false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Create a CiviCRM Attachment.
	 *
	 * If you want to "create" an Attachment, do not pass $data['id'] in. The
	 * presence of an ID will cause an update to that Attachment.
	 *
	 * @since 0.5.2
	 *
	 * @param string $data The Attachment data.
	 * @return array|bool $attachment The array of Attachment data, or false on failure.
	 */
	public function create( $data ) {

		// Init return.
		$attachment = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $attachment;
		}

		// Define params to create new Attachment.
		$params = [
			'version' => 3,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Attachment', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'params'    => $params,
				'result'    => $result,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return $attachment;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $attachment;
		}

		// The result set should contain only one item.
		$attachment = array_pop( $result['values'] );

		// --<
		return $attachment;

	}

	/**
	 * Update a CiviCRM Attachment with a given set of data.
	 *
	 * This is an alias of `self::create()` except that we expect an ID to have
	 * been set in the data.
	 *
	 * @since 0.5.2
	 *
	 * @param array $data The CiviCRM Attachment data.
	 * @return array|bool The array of data from the CiviCRM API, or false on failure.
	 */
	public function update( $data ) {

		// Log and bail if there's no Attachment ID.
		if ( empty( $data['id'] ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'A numeric ID must be present to update an Attachment.', 'civicrm-wp-profile-sync' ),
				'data'      => $data,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// Pass through.
		return $this->create( $data );

	}

	/**
	 * Deletes a CiviCRM Attachment.
	 *
	 * @since 0.5.2
	 *
	 * @param array $attachment_id The numeric ID of the CiviCRM Attachment.
	 * @return bool $success True if successfully deleted, or false on failure.
	 */
	public function delete( $attachment_id ) {

		// Init return.
		$success = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Define params to delete this Phone Record.
		$params = [
			'version' => 3,
			'id'      => $attachment_id,
		];

		// Call the API.
		$result = civicrm_api( 'Attachment', 'delete', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $success;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $success;
		}

		// The result set should contain only one item.
		$success = ( 1 === (int) $result['values'] ) ? true : false;

		// --<
		return $success;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Make a copy of a File.
	 *
	 * @since 0.5.4
	 *
	 * @param string $file The path to the File.
	 * @param string $new_name The new name of the File.
	 * @return string|bool $new_file The path to the copied File, or false on failure.
	 */
	public function file_copy( $file, $new_name ) {

		// Extract the filename so we can rename it.
		$filename = pathinfo( $file, PATHINFO_BASENAME );

		// Build path for new File.
		$new_file = str_replace( $filename, $new_name, $file );

		// Try and copy the File.
		if ( ! copy( $file, $new_file ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'    => __METHOD__,
				'message'   => __( 'Could not copy File.', 'civicrm-wp-profile-sync' ),
				'file'      => $file,
				'new_name'  => $new_name,
				'new_file'  => $new_file,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return $new_file;

	}

	/**
	 * Make a renamed copy of a WordPress File for CiviCRM to copy.
	 *
	 * The CiviCRM API moves a File to the secure directory, so we need to make
	 * a copy of the File so that the WordPress Attachment remains valid.
	 *
	 * @since 0.5.2
	 *
	 * @param string $file The path to the File.
	 * @return string|bool $new_file The path to the copied File, or false on failure.
	 */
	public function file_copy_for_civicrm( $file ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Extract the filename.
		$filename = pathinfo( $file, PATHINFO_BASENAME );

		// Make a new file name in CiviCRM style.
		$new_name = CRM_Utils_File::makeFileName( $filename );

		// Try and make a copy.
		$new_file = $this->file_copy( $file, $new_name );

		// --<
		return $new_file;

	}

	/**
	 * Make a renamed copy of a CiviCRM File for WordPress to copy.
	 *
	 * The WordPress moves a File to the uploads directory, so we need to make
	 * a copy of the File so that the CiviCRM Attachment remains valid.
	 *
	 * @since 0.5.2
	 *
	 * @param string $file The path to the File.
	 * @return string|bool $new_file The path to the copied File, or false on failure.
	 */
	public function file_copy_for_acf( $file ) {

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Use CiviCRM's method to duplicate.
		$new_file = CRM_Utils_File::duplicate( $file );

		// --<
		return $new_file;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the data for an Attachment.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $attachment_id The numeric ID of the Attachment.
	 * @return array $attachment The array of Attachment data, or empty if none.
	 */
	public function get_by_id( $attachment_id ) {

		// Init return.
		$attachment = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $attachment;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id'      => $attachment_id,
		];

		// Get Attachment details via API.
		$result = civicrm_api( 'Attachment', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $attachment;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $attachment;
		}

		// The result set should contain only one item.
		$attachment = (object) array_pop( $result['values'] );

		// --<
		return $attachment;

	}

	/**
	 * Gets the Attachments for a given Entity ID.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $entity The "name" of the CiviCRM Entity.
	 * @param integer $entity_id The numeric ID of the CiviCRM Entity.
	 * @return array $attachment_data The array of Attachment data for the CiviCRM Contact.
	 */
	public function get_for_entity( $entity, $entity_id ) {

		// Init return.
		$attachment_data = [];

		// Bail if we have no Entity ID.
		if ( empty( $entity_id ) ) {
			return $attachment_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $attachment_data;
		}

		// Define params to get queried Attachments.
		$params = [
			'version'      => 3,
			'sequential'   => 1,
			'entity_table' => 'civicrm_' . $entity,
			'entity_id'    => $entity_id,
			'options'      => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Attachment', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $attachment_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $attachment_data;
		}

		// The result set it what we want.
		$attachment_data = $result['values'];

		// --<
		return $attachment_data;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the data for a CiviCRM File by its ID.
	 *
	 * The ID is the same although the Attachment API provides more data.
	 *
	 * @since 0.5.2
	 *
	 * @param string $file_id The numeric ID of the CiviCRM File.
	 * @return array $file The array of File data, or empty if none.
	 */
	public function file_get_by_id( $file_id ) {

		// Init return.
		$file = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $file;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id'      => $file_id,
		];

		// Get File details via API.
		$result = civicrm_api( 'File', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $file;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $file;
		}

		// The result set should contain only one item.
		$file = (object) array_pop( $result['values'] );

		// --<
		return $file;

	}

	/**
	 * Gets the data for a CiviCRM File by its filename.
	 *
	 * We can't use the Attachment API to search for an entry by filename, but
	 * we can use the File API *happy face*. The ID of the Entity is the same,
	 * though the Attachment API provides more data.
	 *
	 * @since 0.5.2
	 *
	 * @param string $filename The name of the File on disk.
	 * @return array $file The array of File data, or empty if none.
	 */
	public function file_get_by_name( $filename ) {

		// Init return.
		$file = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $file;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'uri'     => $filename,
		];

		// Get File details via API.
		$result = civicrm_api( 'File', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $file;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $file;
		}

		// The result set should contain only one item.
		$file = (object) array_pop( $result['values'] );

		// --<
		return $file;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Attachment Fields.
	 *
	 * @since 0.5.2
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
		$result = civicrm_api( 'Attachment', 'getfields', $params );

		// Override return if we get some.
		if ( empty( $result['is_error'] ) && ! empty( $result['values'] ) ) {

			if ( 'none' === $filter ) {

				// Grab all Fields.
				$fields = $result['values'];

			} elseif ( 'public' === $filter ) {

				// Skip all but those defined in our public Attachment Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->attachment_fields ) ) {
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

	// -----------------------------------------------------------------------------------

	/**
	 * Fires just before a set of ACF Fields have been updated.
	 *
	 * We need to find out if any of the ACF Fields are of type 'file' because
	 * there is no other way to find out whether it has been severed from its
	 * WordPress Attachment.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of WordPress params.
	 */
	public function acf_fields_pre_save( $args ) {

		// Bail if there's no ACF "Post ID".
		if ( empty( $args['post_id'] ) ) {
			return;
		}

		// When the ACF "Post ID" is numeric.
		if ( is_numeric( $args['post_id'] ) ) {

			// We need the Post itself.
			$post = get_post( $args['post_id'] );

			// Bail if this is a revision.
			if ( 'revision' === $post->post_type ) {
				return;
			}

		}

		/*
		 * Get existing Field values.
		 *
		 * These are actually the *old* values because we are hooking in *before*
		 * the Fields have been saved.
		 */
		$fields = get_fields( $args['post_id'], false );

		// Bail if there are no ACF Fields.
		if ( empty( $fields ) ) {
			return;
		}

		// Loop through the Fields and store any File Fields.
		$this->file_fields = [];
		foreach ( $fields as $selector => $value ) {
			$settings = get_field_object( $selector, $args['post_id'] );
			if ( 'file' === $settings['type'] ) {
				$this->file_fields[] = [
					'post_id'       => $args['post_id'],
					'selector'      => $selector,
					'attachment_id' => $value,
				];
			}
		}

	}

	/**
	 * Gets the data for an ACF Field before it was saved.
	 *
	 * @since 0.5.2
	 *
	 * @param string  $selector The ACF Field selector.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $file_field The data for the ACF Field, or false if not found.
	 */
	public function acf_field_pre_save_get( $selector = '', $post_id = 0 ) {

		// Init return.
		$file_field = false;

		// Bail if there are no File Fields.
		if ( empty( $this->file_fields ) ) {
			return $file_field;
		}

		// Was there a File before the ACF Field was saved?
		foreach ( $this->file_fields as $field ) {

			// Skip if the data doesn't match.
			if ( $field['selector'] !== $selector ) {
				continue;
			}
			if ( (int) $field['post_id'] !== (int) $post_id ) {
				continue;
			}

			// We've got it.
			$file_field = $field;
			break;

		}

		// --<
		return $file_field;

	}

	/**
	 * Builds an array of CiviCRM URLs for filtering the ACF Attachment.
	 *
	 * This method is called when loading values via get_field().
	 *
	 * @since 0.5.2
	 *
	 * @param mixed $value The value which was loaded from the database.
	 * @param mixed $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function acf_load_filter( $value, $post_id, $field ) {

		// Skip filter if there is no value.
		if ( empty( $value ) ) {
			return $value;
		}

		// Skip filter if CiviCRM File is not set.
		if ( empty( $field['civicrm_file_field_type'] ) ) {
			return $value;
		}

		// Skip filter if using WordPress File.
		if ( 1 === (int) $field['civicrm_file_field_type'] ) {
			return $value;
		}

		// Skip if already parsed.
		if ( ! empty( $this->attachments[ $value ] ) ) {
			return $value;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $value;
		}

		// Skip if there is no mapped Custom Field ID.
		$custom_field_id = $this->civicrm->custom_field->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return $value;
		}

		// Get the Attachment metadata.
		$meta = $this->metadata_get( $value );
		if ( empty( $meta['civicrm_file'] ) ) {
			return $value;
		}

		// Try and find the CiviCRM File data.
		$filename     = pathinfo( $meta['civicrm_file'], PATHINFO_BASENAME );
		$civicrm_file = $this->file_get_by_name( $filename );
		if ( empty( $civicrm_file ) ) {
			return $value;
		}

		// Get the full CiviCRM Attachment data.
		$attachment = $this->get_by_id( $civicrm_file->id );
		if ( empty( $attachment->url ) ) {
			return $value;
		}

		// Store CiviCRM URL for filtering the ACF Attachment data.
		$this->attachments[ $value ] = $attachment->url;

		// --<
		return $value;

	}

	/**
	 * Builds an array of CiviCRM URLs for filtering the ACF Attachment.
	 *
	 * This method is called when rendering the Field, e.g. in ACFE front end Forms.
	 *
	 * @since 0.5.2
	 *
	 * @param array $field The Field array holding all the Field options.
	 */
	public function acf_render_filter( $field ) {

		// Skip filter if CiviCRM File is not set.
		if ( empty( $field['civicrm_file_field_type'] ) ) {
			return;
		}

		// Skip filter if using WordPress File.
		if ( 1 === (int) $field['civicrm_file_field_type'] ) {
			return;
		}

		// Skip filter if there is no value.
		if ( empty( $field['value'] ) ) {
			return;
		}

		// The value is an Attachment ID.
		$value = (int) $field['value'];

		// Skip if already parsed.
		if ( ! empty( $this->attachments[ $value ] ) ) {
			return;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return;
		}

		// Skip if there is no mapped Custom Field ID.
		$custom_field_id = $this->civicrm->custom_field->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return;
		}

		// Get the Attachment metadata.
		$meta = $this->metadata_get( $value );
		if ( empty( $meta['civicrm_file'] ) ) {
			return;
		}

		// Try and find the CiviCRM File data.
		$filename     = pathinfo( $meta['civicrm_file'], PATHINFO_BASENAME );
		$civicrm_file = $this->file_get_by_name( $filename );
		if ( empty( $civicrm_file ) ) {
			return;
		}

		// Get the full CiviCRM Attachment data.
		$attachment = $this->get_by_id( $civicrm_file->id );
		if ( empty( $attachment->url ) ) {
			return;
		}

		// Store CiviCRM URL for filtering the ACF Attachment data.
		$this->attachments[ $value ] = $attachment->url;

	}

	/**
	 * Maybe filter the URL of the File.
	 *
	 * @since 0.5.2
	 *
	 * @param array       $response The array of loaded Attachment data.
	 * @param WP_Post     $attachment The Attachment object.
	 * @param array|false $meta The array of Attachment metadata, or false if there is none.
	 * @return mixed $response The modified array of Attachment data.
	 */
	public function acf_attachment_filter( $response, $attachment, $meta ) {

		// Skip filter if no File URL has been stored.
		if ( empty( $this->attachments[ (int) $response['id'] ] ) ) {
			return $response;
		}

		// Overwrite URL.
		$response['url'] = $this->attachments[ (int) $response['id'] ];

		// --<
		return $response;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the value of an ACF "File" Field formatted for a CiviCRM Custom Field.
	 *
	 * The ACF File Field return format can be either 'array', 'url' or 'id' so
	 * we need to extract the appropriate file info to send to CiviCRM.
	 *
	 * @since 0.5.2
	 *
	 * @param integer|null $value The Field value (the Attachment data).
	 * @param array        $settings The ACF Field settings.
	 * @param array        $args Any additional arguments.
	 * @return array|string $value Array containing the path and mime type of the file, or empty string on failure.
	 */
	public function value_get_for_civicrm( $value, $settings, $args ) {

		// Find the data from before the save operation.
		$file_field = $this->acf_field_pre_save_get( $args['selector'], $args['post_id'] );

		if ( empty( $value ) ) {

			// When value is empty.

			// Return early if it was previously empty.
			if ( empty( $file_field['attachment_id'] ) ) {
				return '';
			}

			// The ACF Field has been "unlinked" from the Attachment.

			// Look for previous Attachment metadata.
			$meta = $this->metadata_get( (int) $file_field['attachment_id'] );
			if ( empty( $meta['civicrm_file'] ) ) {
				return '';
			}

			// Try and delete the previous CiviCRM Attachment.
			$filename     = pathinfo( $meta['civicrm_file'], PATHINFO_BASENAME );
			$civicrm_file = $this->file_get_by_name( $filename );
			if ( ! empty( $civicrm_file ) ) {

				// It seems the Custom Field is cleared by doing this.
				$this->delete( $civicrm_file->id );

				// Mimic "civicrm_custom" for reverse syncs.
				$this->mimic_civicrm_custom( $settings, $args );

			}

			// Okay, bail.
			return '';

		} elseif ( is_array( $value ) ) {

			// If it's an array, the WordPress Attachment ID is in the array.
			$attachment_id = $value['id'];

		} elseif ( is_numeric( $value ) ) {

			// When it's numeric, it's the ID of the Attachment.
			$attachment_id = $value;

		} elseif ( is_string( $value ) ) {

			// When it's a string, it must be the URL.
			// Try and get the existing Attachment ID.
			$attachment_id = attachment_url_to_postid( $value );

		}

		// Bail if something is amiss.
		if ( empty( $attachment_id ) ) {
			return '';
		}

		// Make sure we have an integer.
		$attachment_id = (int) $attachment_id;

		// Get the current WordPress File.
		$file = get_attached_file( $attachment_id, true );

		// Get the current Attachment metadata.
		$meta = $this->metadata_get( $attachment_id );

		// If we get metadata for the current Attachment.
		if ( ! empty( $meta ) ) {

			// Skip the update if there is no change.
			if ( $meta['wordpress_file'] === $file ) {
				return '';
			}

		}

		// Check if the Attachment has been switched.
		if ( ! empty( $file_field['attachment_id'] ) ) {
			if ( (int) $file_field['attachment_id'] !== $attachment_id ) {

				// Try and delete the previous CiviCRM Attachment.
				$previous_meta = $this->metadata_get( (int) $file_field['attachment_id'] );
				if ( ! empty( $previous_meta['civicrm_file'] ) ) {
					$filename     = pathinfo( $previous_meta['civicrm_file'], PATHINFO_BASENAME );
					$civicrm_file = $this->file_get_by_name( $filename );
					if ( ! empty( $civicrm_file ) ) {

						// It seems the Custom Field is cleared by doing this.
						$this->delete( $civicrm_file->id );

						// Mimic "civicrm_custom" for reverse syncs.
						$this->mimic_civicrm_custom( $settings, $args );

					}
				}

			}
		}

		// Make a copy of the file for CiviCRM to move.
		$new_file = $this->file_copy_for_civicrm( $file );
		if ( false === $new_file ) {
			return '';
		}

		// Save metadata.
		$data = [
			'wordpress_file' => $file,
			'civicrm_file'   => $new_file,
		];

		// Store some Attachment metadata.
		$this->metadata_set( $attachment_id, $data );

		// Finally, build array for CiviCRM.
		$value = [
			'name' => $new_file,
			'type' => get_post_mime_type( $attachment_id ),
		];

		// --<
		return $value;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the value of a "File" Field as required by an ACF Field.
	 *
	 * @since 0.5.2
	 *
	 * @param mixed  $value The Custom Field value - either the filename or the File ID.
	 * @param array  $field The Custom Field data.
	 * @param string $selector The ACF Field selector.
	 * @param mixed  $post_id The ACF "Post ID".
	 * @return mixed $value The formatted Field value.
	 */
	public function value_get_for_acf( $value, $field, $selector, $post_id ) {

		/*
		 * If it's numeric, overwrite the "value" with the filename.
		 *
		 * This happens because there is a mismatch between the value returned
		 * when querying the Custom Field and the Custom Field data receieved by
		 * the "civicrm_custom" callback.
		 */
		if ( is_numeric( $value ) ) {
			$civicrm_file = $this->file_get_by_id( $value );
			if ( empty( $civicrm_file ) ) {
				return '';
			}
			$value = $civicrm_file->uri;
		}

		// Grab the raw data (Attachment ID) from the ACF Field.
		$existing = get_field( $selector, $post_id, false );

		// Assume no sync necessary.
		$sync = false;

		// If there's no ACF data.
		if ( empty( $existing ) ) {

			// We're good to sync.
			$sync = true;

		} else {

			// Get the current Attachment metadata.
			$meta = $this->metadata_get( (int) $existing );

			// If we get metadata for the current Attachment.
			if ( ! empty( $meta['civicrm_file'] ) ) {

				// Sync the new File if the filename has changed.
				$filename = pathinfo( $meta['civicrm_file'], PATHINFO_BASENAME );
				if ( ! empty( $filename ) && $filename !== $value ) {
					$sync = true;
				}

			} else {

				// Sync if both file paths are empty.
				if ( empty( $meta['wordpress_file'] ) ) {
					$sync = true;
				}

			}

		}

		// Bail if no sync is necessary.
		if ( false === $sync ) {

			// The Attachment ID is the existing value.
			$value = (int) $existing;

			// Return early.
			return $value;

		}

		// Return early if we find an existing Attachment ID.
		$possible_id = $this->query_by_file( $value, 'civicrm' );
		if ( ! empty( $possible_id ) ) {
			$value = (int) $possible_id;
			return $value;
		}

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		// Copy the File for WordPress to move.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$tmp_name = $this->file_copy_for_acf( $config->customFileUploadDir . $value );

		// Find the name of the new File.
		$name = pathinfo( $tmp_name, PATHINFO_BASENAME );

		// Find the mime type of the File.
		$mime_type = wp_check_filetype( $tmp_name );

		// Find the filesize in bytes.
		$size = filesize( $tmp_name );

		/*
		 * Normally this is used to store an error should the upload fail.
		 * Since we aren't actually building an instance of $_FILES, we can
		 * default to zero instead.
		 */
		$error = 0;

		// Create an array that mimics $_FILES.
		$files = [
			'name'     => $name,
			'type'     => $mime_type,
			'tmp_name' => $tmp_name,
			'error'    => $error,
			'size'     => $size,
		];

		// Only assign to a Post if the ACF "Post ID" is numeric.
		if ( ! is_numeric( $post_id ) ) {
			$target_post_id = null;
		} else {
			$target_post_id = $post_id;
		}

		// Possibly include the required files.
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// Transfer the CiviCRM File to WordPress and grab ID.
		$attachment_id = media_handle_sideload( $files, $target_post_id );

		// Handle sideload errors.
		if ( is_wp_error( $attachment_id ) ) {
			@unlink( $files['tmp_name'] );
			return '';
		}

		// Save metadata.
		$data = [
			'wordpress_file' => get_attached_file( $attachment_id, true ),
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			'civicrm_file'   => $config->customFileUploadDir . $value,
		];

		// Store some Attachment metadata.
		$this->metadata_set( $attachment_id, $data );

		// Get the Attachment for the ID we've determined.
		$attachment = acf_get_attachment( $attachment_id );

		// The value in ACF is the Attachment ID.
		$value = $attachment['ID'];

		// --<
		return $value;

	}

	/**
	 * Mimics the values broadcast by the CiviCRM "civicrm_custom" hook.
	 *
	 * @since 0.5.2
	 *
	 * @param array $settings The ACF Field settings.
	 * @param array $args Any additional arguments.
	 */
	public function mimic_civicrm_custom( $settings, $args ) {

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $settings );

		/**
		 * Query for the Entity Tables that this ACF Field Group is mapped to.
		 *
		 * This filter sends out a request for other classes to respond with an
		 * Entity Table if they detect that the ACF Field Group maps to an
		 * Entity Type that they are responsible for.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact::query_entity_table()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Activity::query_entity_table()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT::query_entity_table()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Participant::query_entity_table()
		 * @see CiviCRM_Profile_Sync_ACF_User::query_entity_table()
		 *
		 * Also used by CiviCRM Event Organiser:
		 *
		 * @see CiviCRM_WP_Event_Organiser_CWPS::query_entity_table()
		 *
		 * @since 0.5.2
		 *
		 * @param array $entity_tables Empty array, since we're asking for Entity Tables.
		 * @param array $field_group The array of ACF Field Group params.
		 */
		$entity_tables = apply_filters( 'cwps/acf/query_entity_table', [], $field_group );

		// Get the CiviCRM Custom Field.
		$custom_field = $this->plugin->civicrm->custom_field->get_by_id( $args['custom_field_id'] );

		// Handle all Entity Tables.
		foreach ( $entity_tables as $entity_table => $entity_name ) {

			// Build Custom Field array.
			$custom_fields   = [];
			$custom_fields[] = [
				'entity_table'    => $entity_table,
				'entity_id'       => (int) $args['entity_id'],
				'value'           => '',
				'type'            => 'File',
				'custom_field_id' => (int) $args['custom_field_id'],
				'custom_group_id' => (int) $custom_field['custom_group_id'],
				'is_multiple'     => 0,
				'serialize'       => 0,
				'file_id'         => '',
			];

			// Mimic the CiviCRM params.
			$op        = 'edit';
			$group_id  = (int) $custom_field['custom_group_id'];
			$entity_id = (int) $args['entity_id'];

			/**
			 * Use ACF Mapper to broadcast that an Attachment has been deleted.
			 *
			 * We have to fire this action because "File" CiviCRM Custom Fields
			 * behave differently to other kinds of CiviCRM Custom Fields.
			 *
			 * They do not get updated because their value is empty and they are
			 * therefore not included in the array of edited Fields passed to the
			 * `civicrm_custom` hook.
			 *
			 * This means that any "reverse sync" processes do not occur and so
			 * any ACF Fields attached to other WordPress Entities do not receive
			 * the event that clears their value.
			 */
			$this->acf_loader->mapper->custom_edited( $op, $group_id, $entity_id, $custom_fields );

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Deletes the CiviCRM and WordPress Attachments given a CiviCRM Attachment ID.
	 *
	 * Also clears any associated ACF Fields of type "File" that may be synced via
	 * their Post Types to the Entity.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $file_id The numeric ID of the CiviCRM Attachment.
	 * @param array   $settings The ACF Field settings.
	 * @param array   $args Any additional arguments.
	 */
	public function fields_clear( $file_id, $settings, $args ) {

		// TODO: Return success/failure?

		// Bail if there's no CiviCRM File ID.
		if ( empty( $file_id ) ) {
			return;
		}

		// Get the CiviCRM File data.
		$civicrm_file = $this->civicrm->attachment->file_get_by_id( $file_id );
		if ( empty( $civicrm_file ) ) {
			return;
		}

		// It seems the Custom Field is cleared by doing this.
		$this->delete( $civicrm_file->id );

		// Mimic "civicrm_custom" for reverse syncs.
		$this->mimic_civicrm_custom( $settings, $args );

		// Get the WordPress Attachment ID.
		$attachment_id = $this->query_by_file( $civicrm_file->uri, 'civicrm' );
		if ( empty( $attachment_id ) ) {
			return;
		}

		// Delete it.
		wp_delete_attachment( $attachment_id, true );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Queries WordPress Attachments for a given File.
	 *
	 * @since 0.5.2
	 *
	 * @param string $filename The name of the File.
	 * @param string $source Either 'wp' or 'civicrm'. Default is 'civicrm'.
	 * @return integer|bool $attachment_id The ID of the WordPress Attachment, or false if none is found.
	 */
	public function query_by_file( $filename, $source = 'civicrm' ) {

		// Init return.
		$attachment_id = false;

		// Choose meta key.
		$meta_key = $this->attachment_civicrm_key;
		if ( 'civicrm' !== $source ) {
			$meta_key = $this->attachment_wp_key;
		}

		// Build arguments for query.
		$args = [
			'post_type'      => 'attachment',
			'post_status'    => 'any',
			'no_found_rows'  => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_key'       => $meta_key,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_value'     => $filename,
			'meta_compare'   => 'LIKE',
			'posts_per_page' => -1,
			'order'          => 'ASC',
		];

		// Do the query.
		$query = new WP_Query( $args );

		// There should be only one Attachment ID.
		if ( $query->have_posts() ) {
			foreach ( $query->get_posts() as $found ) {
				$attachment_id = $found->ID;
				break;
			}
		}

		// Reset Post data just in case.
		wp_reset_postdata();

		// --<
		return $attachment_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Callback for the CiviCRM 'civi.dao.preDelete' hook.
	 *
	 * This is the only hook that fires when a File is deleted via the CiviCRM UI.
	 * All other deletions are direct SQL queries.
	 *
	 * We can't use the Attachment API to search for an entry by ID during the
	 * File deletion process because the "Entity File" has already been deleted.
	 * We can use the File API, however *happy face*.
	 *
	 * I have opened an issue and PR that allows us to distinguish between Files
	 * that are "Attachments" and Files that are uploaded to Custom Fields to be
	 * distinguished from one another.
	 *
	 * With the patch in the GitHub PR, this method is able to delete
	 *
	 * @see https://lab.civicrm.org/dev/core/-/issues/3130
	 * @see https://github.com/civicrm/civicrm-core/pull/22994
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The CiviCRM arguments.
	 */
	public function civicrm_file_pre_delete( $args ) {

		// We have my patch if there is a CiviCRM Attachment.
		if ( ! empty( $args['attachment'] ) ) {

			// Bail if this is not a File attached to a Custom Field.
			if ( strstr( $args['attachment']->entity_table, 'civicrm_value_' ) === false ) {
				return;
			}

			/*
			// Pass to separate method and skip.
			$this->civicrm_attachment_pre_delete( $args );
			return;
			*/

		}

		// Sanity check.
		if ( empty( $args['objectRef'] ) ) {
			return;
		}

		/*
		 * What do we want to do now?
		 *
		 * When we have found our way to the WordPress Attachment:
		 *
		 * We could delete it, which would at least guarantee that all ACF Fields
		 * that relate to it will refer to a non-existent Attachment.
		 *
		 * We could alter its metadata and trigger a change next time the ACF Field
		 * or CiviCRM Field gets updated.
		 *
		 * Hmm...
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Field::image_attachment_deleted()
		 */

		// Let's try and find a WordPress Attachment.
		$attachment_id = $this->query_by_file( $args['objectRef']->uri, 'civicrm' );
		if ( empty( $attachment_id ) ) {
			return;
		}

		// Save metadata.
		$data = [
			'wordpress_file' => get_attached_file( $attachment_id, true ),
			'civicrm_file'   => '',
		];

		// Force-delete the Attachment.
		wp_delete_attachment( $attachment_id, true );

	}

	/**
	 * Update the ACF Fields that need their Attachment ID removed.
	 *
	 * It seems remarkably difficult to work backwards from the Attachment to
	 * the Entities and Custom Fields to which that File is assigned. Keeping
	 * this code here to remind me where I got to.
	 *
	 * @since 0.5.4
	 *
	 * @param array $args The CiviCRM arguments.
	 */
	public function civicrm_attachment_pre_delete( $args ) {

		// Make sure we have an Entity Table.
		if ( empty( $args['attachment']->entity_table ) ) {
			return;
		}

		// Get the Custom Group for this Entity Table.
		$custom_group = $this->plugin->custom_group->get_by_entity_table( $args['attachment']->entity_table );
		if ( empty( $custom_group ) ) {
			return;
		}

		$params = [
			'version'         => 3,
			'sequential'      => 1,
			'custom_group_id' => $custom_group['id'],
			'data_type'       => 'File',
		];

		// This will return the Custom Fields of type "File" in that Group.
		$result = civicrm_api( 'CustomField', 'get', $params );

		/*
		 * We can tell the CiviCRM Entity by the Custom Group "entity" value.
		 *
		 * Can we query that Entity using 'custom_N' for Fields with the value
		 * of the File ID? Will that help? I'm note sure yet.
		 *
		 * It looks at the moment as though its good enough just to delete the
		 * WordPress Attachment - although my patch is needed so that deleting
		 * CiviCRM Attachments on Activitiesn (for example) can also update the
		 * "CiviCRM Activity: Attachments" ACF Fields so they don't lose their
		 * data integrity.
		 */

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Fires just before an Attachment is deleted.
	 *
	 * ACF File Fields store the WordPress Attachment ID, so when an Attachment
	 * is deleted (and depending on the return format) nothing bad happens.
	 * CiviCRM Custom Fields of type "File" store the CiviCRM Attachment ID.
	 *
	 * The problem here is that there is no way of finding out the ACF Field(s)
	 * that reference the Attachment that is being deleted, so we cannot know if
	 * the corresponding CiviCRM Attachment should be deleted.
	 *
	 * I am going to leave this disabled pending further thought.
	 *
	 * @see self::register_hooks()
	 *
	 * @since 0.5.2
	 *
	 * @param integer $attachment_id The numeric ID of the Attachment.
	 */
	public function wordpress_attachment_deleted( $attachment_id ) {

		// Look for Attachment metadata.
		$meta = $this->metadata_get( (int) $attachment_id );
		if ( empty( $meta['civicrm_file'] ) ) {
			return;
		}

		// Try and delete the CiviCRM Attachment.
		$filename     = pathinfo( $meta['civicrm_file'], PATHINFO_BASENAME );
		$civicrm_file = $this->file_get_by_name( $filename );
		if ( empty( $civicrm_file ) ) {
			return;
		}

		// It seems the Custom Field is cleared by doing this.
		$this->delete( $civicrm_file->id );

		// Mimic "civicrm_custom" for reverse syncs.
		$this->mimic_civicrm_custom( $settings, $args );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
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

		// Bail early if not our Field Type.
		if ( 'file' !== $field['type'] ) {
			return $choices;
		}

		/**
		 * Query for the choices from Entities that support Attachments.
		 *
		 * This filter sends out a request for other classes to respond with an
		 * Entity Table and Label if they support Attachments.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Activity::query_attachment_choices()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Note::query_attachment_choices()
		 *
		 * @since 0.5.2
		 *
		 * @param array $entities Empty array, since we're querying for Entities.
		 * @param array $entity_array The Entity and ID array.
		 */
		$entities = apply_filters( 'cwps/acf/query_attachment_choices', [], $entity_array );

		// Bail if none.
		if ( empty( $entities ) ) {
			return $choices;
		}

		// Build choices.
		$optgroup_label = esc_attr__( 'Attach to CiviCRM Entity', 'civicrm-wp-profile-sync' );
		foreach ( $entities as $table => $label ) {
			$choices[ $optgroup_label ][ $table ] = $label;
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5.2
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/attachment/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Adds additional Settings to an ACF "File" Field when in an ACFE context.
	 *
	 * @since 0.5.2
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $setting_field The ACF Field setting array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function field_settings_acfe_add( $field, $setting_field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'file' !== $field['type'] ) {
			return $field;
		}

		// First add ACF settings.
		$this->field_settings_acf_add( $field, $setting_field, $field_group );

		// Define Setting Field.
		$upload_setting_field = [
			'key'               => 'civicrm_file_no_wp',
			'label'             => __( 'CiviCRM Only', 'civicrm-wp-profile-sync' ),
			'name'              => 'civicrm_file_no_wp',
			'type'              => 'true_false',
			'instructions'      => __( 'Delete WordPress Attachment after upload.', 'civicrm-wp-profile-sync' ),
			'required'          => 0,
			'wrapper'           => [
				'width' => '',
				'class' => '',
				'id'    => '',
			],
			'acfe_permissions'  => '',
			'message'           => '',
			'default_value'     => 0,
			'ui'                => 1,
			'ui_on_text'        => '',
			'ui_off_text'       => '',
			'conditional_logic' => [
				[
					[
						'field'    => $this->acf_loader->civicrm->acf_field_key_get(),
						'operator' => '==contains',
						'value'    => 'caicustom_',
					],
				],
			],
		];

		// Now add it.
		acf_render_field_setting( $field, $upload_setting_field );

	}

	/**
	 * Adds additional Settings to an ACF "File" Field.
	 *
	 * @since 0.5.2
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $setting_field The ACF Field setting array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function field_settings_acf_add( $field, $setting_field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'file' !== $field['type'] ) {
			return $field;
		}

		// Define Setting Field.
		$usage_setting_field = [
			'key'               => 'civicrm_file_field_type',
			'label'             => __( 'File Link', 'civicrm-wp-profile-sync' ),
			'name'              => 'civicrm_file_field_type',
			'type'              => 'select',
			'instructions'      => __( 'Choose which File this ACF Field should link to.', 'civicrm-wp-profile-sync' ),
			'default_value'     => '',
			'placeholder'       => '',
			'allow_null'        => 0,
			'multiple'          => 0,
			'ui'                => 0,
			'required'          => 0,
			'return_format'     => 'value',
			'parent'            => $this->acf_loader->acf->field_group->placeholder_group_get(),
			'choices'           => [
				1 => __( 'Use public WordPress File', 'civicrm-wp-profile-sync' ),
				2 => __( 'Use permissioned CiviCRM File', 'civicrm-wp-profile-sync' ),
			],
			'conditional_logic' => [
				[
					[
						'field'    => $this->acf_loader->civicrm->acf_field_key_get(),
						'operator' => '==contains',
						'value'    => 'caicustom_',
					],
				],
			],
		];

		// Now add it.
		acf_render_field_setting( $field, $usage_setting_field );

	}

	/**
	 * Modify the Settings of an ACF "File" Field.
	 *
	 * @since 0.5.2
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function field_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'file' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the Entities that support Attachments.
		$entities = $this->field_entities_get();

		if ( ! array_key_exists( $field[ $key ], $entities ) ) {
			return $field;
		}

		// Set the "uploader" attribute.
		$field['uploader'] = 'basic';

		// Set the "max_size" attribute.
		$field['max_size'] = $this->field_max_size_get();

		// --<
		return $field;

	}

	/**
	 * Gets the maximum file size of Attachments for CiviCRM Entities.
	 *
	 * @since 0.5.4
	 *
	 * @return integer $max_size The the maximum file size of Attachments.
	 */
	public function field_max_size_get() {

		// Default to 3MB.
		$max_size = 3;

		// Bail if no CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $max_size;
		}

		// Get the "max_size" attribute.
		$config = CRM_Core_Config::singleton();
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$max_size = $config->maxFileSize;

		// Default to 3MB if not set for some reason.
		if ( empty( $max_size ) ) {
			$max_size = 3;
		}

		// --<
		return $max_size;

	}

	/**
	 * Gets the maximum number of Attachments for CiviCRM Entities.
	 *
	 * @since 0.5.4
	 *
	 * @return integer $max_attachments The the maximum number of Attachments.
	 */
	public function field_max_attachments_get() {

		// Get the Attachments limit.
		$max_attachments = $this->plugin->civicrm->get_setting( 'max_attachments' );

		// If it's not set for some reason, default to 3.
		if ( empty( $max_attachments ) ) {
			$max_attachments = 3;
		}

		// --<
		return $max_attachments;

	}

	/**
	 * Gets an array of CiviCRM Entities that support Attachments.
	 *
	 * @since 0.5.2
	 *
	 * @return array $entities The array of CiviCRM Entities that support Attachments.
	 */
	public function field_entities_get() {

		/**
		 * Query for the CiviCRM Entities that support Attachments.
		 *
		 * This filter sends out a request for other classes to respond with an
		 * Entity Table and Label if they support Attachments.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Activity::query_attachment_support()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Note::query_attachment_support()
		 *
		 * @since 0.5.2
		 *
		 * @param array $entities Empty array, since we're querying for Entities.
		 * @param array $entity_array The Entity and ID array.
		 */
		$entities = apply_filters( 'cwps/acf/query_attachment_support', [] );

		// --<
		return $entities;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the metadata for a given WordPress Attachment ID.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $attachment_id The numeric ID of the WordPress Attachment.
	 * @return array $metadata The CiviCRM Attachment data, empty values if none exists.
	 */
	public function metadata_get( $attachment_id ) {

		// Init return.
		$metadata = [
			'wordpress_file' => '',
			'civicrm_file'   => '',
		];

		// Get the WordPress File.
		$existing_wp = get_post_meta( $attachment_id, $this->attachment_wp_key, true );
		if ( ! empty( $existing_wp ) ) {
			$metadata['wordpress_file'] = $existing_wp;
		}

		// Get the CiviCRM File.
		$existing_civicrm = get_post_meta( $attachment_id, $this->attachment_civicrm_key, true );
		if ( ! empty( $existing_civicrm ) ) {
			$metadata['civicrm_file'] = $existing_civicrm;
		}

		// --<
		return $metadata;

	}

	/**
	 * Sets the metadata for a given WordPress Attachment ID.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $attachment_id The numeric ID of the WordPress Attachment.
	 * @param array   $metadata The CiviCRM Attachment data.
	 */
	public function metadata_set( $attachment_id, $metadata ) {

		// Store the Attachment metadata.
		update_post_meta( $attachment_id, $this->attachment_wp_key, $metadata['wordpress_file'] );
		update_post_meta( $attachment_id, $this->attachment_civicrm_key, $metadata['civicrm_file'] );

	}

	/**
	 * Deletes the metadata for a given WordPress Attachment ID.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $attachment_id The numeric ID of the WordPress Attachment.
	 */
	public function metadata_delete( $attachment_id ) {

		// Delete the Attachment metadata.
		delete_post_meta( $attachment_id, $this->attachment_wp_key );
		delete_post_meta( $attachment_id, $this->attachment_civicrm_key );

	}

}
