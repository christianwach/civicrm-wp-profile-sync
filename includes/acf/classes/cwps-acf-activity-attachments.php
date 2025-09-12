<?php
/**
 * CiviCRM Activity Attachments Class.
 *
 * Handles CiviCRM Activity Attachments functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Activity Attachments Class.
 *
 * A class that encapsulates CiviCRM Activity Attachments functionality.
 *
 * @since 0.5.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Activity_Attachments {

	/**
	 * Plugin object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_ACF_Loader
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM
	 */
	public $civicrm;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * "CiviCRM Attachments" Field key in the ACF Field data.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var string
	 */
	public $acf_field_key = 'field_cacf_civicrm_attachments';

	/**
	 * Fields which are handled by this class.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var array
	 */
	public $fields_handled = [
		'civicrm_attachment',
	];

	/**
	 * Attachment Shortcode object.
	 *
	 * @since 0.5.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_Shortcode_Attachment
	 */
	public $shortcode_attachment;

	/**
	 * Constructor.
	 *
	 * @since 0.5.4
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

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.5.4
		 */
		do_action( 'cwps/acf/civicrm/activity/attachments/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 0.5.4
	 */
	public function include_files() {

		// Include Shortcode class files.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/shortcodes/cwps-shortcode-attachment.php';

	}

	/**
	 * Set up the child objects.
	 *
	 * @since 0.5.4
	 */
	public function setup_objects() {

		// Init Shortcode objects.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// $this->shortcode_attachment = new CiviCRM_Profile_Sync_ACF_Shortcode_Attachment( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add any Attachment Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post created from Activity events.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_action( 'cwps/acf/post/activity/sync', [ $this, 'fields_handled_update' ], 10 );

		/*
		 * Intercept events that require CiviCRM Attachment updates.
		 *
		 * At the moment, we only support Attachments on Activities.
		 *
		 * At some point, Attachments on Notes need to be considered - but that
		 * may be better implemented in a class of its own since Notes are not
		 * supported in Post Sync at the moment.
		 */
		add_action( 'cwps/acf/activity/acf_fields_saved', [ $this, 'fields_handled_update' ], 10 );

		// Maybe sync the Attachment ID to the ACF Subfields.
		add_action( 'cwps/acf/activity/attachment/created', [ $this, 'maybe_sync_attachment_data' ], 10, 2 );

		// Build array of CiviCRM URLs and maybe filter the ACF Attachment URL.
		add_filter( 'acf/load_value/type=civicrm_attachment', [ $this, 'acf_load_filter' ], 10, 3 );
		add_filter( 'acf/render_field/type=civicrm_attachment', [ $this, 'acf_render_filter' ], 9, 3 );
		add_filter( 'acf/load_attachment', [ $this, 'acf_attachment_filter' ], 10, 3 );

	}

	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.5.4
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( true === $this->mapper_hooks ) {
			return;
		}

		// Listen for events from our Mapper that require Attachment updates.
		add_action( 'cwps/acf/mapper/attachment/created', [ $this, 'attachment_edited' ], 10 );
		add_action( 'cwps/acf/mapper/attachment/edited', [ $this, 'attachment_edited' ], 10 );
		add_action( 'cwps/acf/mapper/attachment_entity/delete/pre', [ $this, 'attachment_pre_delete' ], 10 );

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.5.4
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( false === $this->mapper_hooks ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/attachment/created', [ $this, 'attachment_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/attachment/edited', [ $this, 'attachment_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/attachment_entity/delete/pre', [ $this, 'attachment_pre_delete' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Update a CiviCRM Entity's Fields with data from ACF Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function fields_handled_update( $args ) {

		// Init success.
		$success = true;

		// Bail if there are no Attachment Record Fields.
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

			// Maybe update an Attachment Record.
			$success = $this->field_handled_update( $field, $value, $args['activity']['id'], $settings, $args );

		}

		// --<
		return $success;

	}

	/**
	 * Update a CiviCRM Activity's Attachments with data from an ACF Field.
	 *
	 * These Fields require special handling because they are not part
	 * of the core Activity data.
	 *
	 * @since 0.5.4
	 *
	 * @param string  $field The ACF Field selector.
	 * @param mixed   $value The ACF Field value.
	 * @param integer $activity_id The numeric ID of the Activity.
	 * @param array   $settings The ACF Field settings.
	 * @param array   $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function field_handled_update( $field, $value, $activity_id, $settings, $args ) {

		// Skip if it's not an ACF Field Type that this class handles.
		if ( ! in_array( $settings['type'], $this->fields_handled, true ) ) {
			return true;
		}

		// Update the Attachment Records.
		$success = $this->attachments_update( $value, $activity_id, $field, $args );

		// --<
		return $success;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept when a Post has been updated from an Activity via the Mapper.
	 *
	 * Sync any associated Attachment Fields.
	 *
	 * @since 0.5.4
	 *
	 * @param array $args The array of CiviCRM Activity and WordPress Post params.
	 */
	public function activity_sync_to_post( $args ) {

		// Get all Attachment Records for this Activity.
		$data = $this->civicrm->attachment->get_for_entity( 'activity', $args['objectId'] );
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Attachment Record Fields.
		if ( empty( $acf_fields['attachments'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['attachments'] as $selector => $attachment_field ) {

			// Init Field value.
			$value = [];

			// Let's look at each Attachment in turn.
			foreach ( $data as $attachment ) {

				// Convert to ACF Attachment data.
				$acf_attachment = $this->prepare_from_civicrm( $attachment );

				// Add to Field value.
				$value[] = $acf_attachment;

			}

			// Now update the ACF Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

		}

	}

	/**
	 * Prepare the CiviCRM Attachment Record from an ACF Field.
	 *
	 * @since 0.5.4
	 *
	 * @param array   $value The array of Attachment Record data in the ACF Field.
	 * @param integer $attachment_id The numeric ID of the Attachment Record (or null if new).
	 * @return array $attachment_data The CiviCRM Attachment Record data.
	 */
	public function prepare_from_field( $value, $attachment_id = null ) {

		// Init required data.
		$attachment_data = [];

		// Maybe add the Attachment ID.
		if ( ! empty( $attachment_id ) ) {
			$attachment_data['id'] = $attachment_id;
		}

		// Convert ACF data to CiviCRM data.
		$attachment_data['file']        = (int) trim( $value['field_attachment_file'] );
		$attachment_data['description'] = trim( $value['field_attachment_description'] );

		// --<
		return $attachment_data;

	}

	/**
	 * Prepare the ACF Field data from a CiviCRM Attachment.
	 *
	 * @since 0.5.4
	 *
	 * @param array $value The array of Attachment data in CiviCRM.
	 * @return array $attachment_data The ACF Attachment data.
	 */
	public function prepare_from_civicrm( $value ) {

		// Init required data.
		$attachment_data = [];

		// Maybe cast as an object.
		if ( ! is_object( $value ) ) {
			$value = (object) $value;
		}

		// Convert CiviCRM data to ACF data.
		$attachment_data['field_attachment_description'] = empty( $value->description ) ? '' : trim( $value->description );
		$attachment_data['field_attachment_id']          = (int) $value->id;

		// Add existing Attachment ID if we find one.
		$filename    = pathinfo( $value->path, PATHINFO_BASENAME );
		$possible_id = $this->civicrm->attachment->query_by_file( $filename, 'civicrm' );
		if ( ! empty( $possible_id ) ) {
			$attachment_data['field_attachment_file'] = (int) $possible_id;
		}

		// --<
		return $attachment_data;

	}

	/**
	 * Creates a WordPress Attachment.
	 *
	 * @since 0.5.4
	 *
	 * @param array $value The pathless filename of the CiviCRM Attachment.
	 * @param array $post_id The numeric ID of the WordPress Post.
	 * @return array|bool $attachment_id The numeric ID of the WordPress Attachment, or false on failure.
	 */
	public function attachment_wp_create( $value, $post_id = null ) {

		// Init return.
		$attachment_id = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $attachment_id;
		}

		// Get CiviCRM config.
		$config = CRM_Core_Config::singleton();

		// Copy the File for WordPress to move.
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$tmp_name = $this->civicrm->attachment->file_copy_for_acf( $config->customFileUploadDir . $value );

		// Find the name of the new File.
		$name = pathinfo( $tmp_name, PATHINFO_BASENAME );
		$name = CRM_Utils_File::cleanFileName( $name );

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
			$wp_filesystem = $this->plugin->wp->filesystem_init();
			if ( $wp_filesystem ) {
				$wp_filesystem->delete( $files['tmp_name'] );
			}
			return '';
		}

		// Save metadata.
		$data = [
			'wordpress_file' => get_attached_file( $attachment_id, true ),
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			'civicrm_file'   => $config->customFileUploadDir . $value,
		];

		// Store some Attachment metadata.
		$this->civicrm->attachment->metadata_set( $attachment_id, $data );

		// --<
		return $attachment_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Update all of a CiviCRM Activity's Attachment Records.
	 *
	 * @since 0.5.4
	 *
	 * @param array   $values The array of Attachment Record arrays.
	 * @param integer $activity_id The numeric ID of the Activity.
	 * @param string  $selector The ACF Field selector.
	 * @param array   $args The array of WordPress params.
	 * @return array|bool $attachments The array of Attachment Record data, or false on failure.
	 */
	public function attachments_update( $values, $activity_id, $selector, $args = [] ) {

		// Init as array.
		$attachments = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $attachments;
		}

		// Get the current Attachment Records.
		$current = $this->civicrm->attachment->get_for_entity( 'activity', $activity_id );

		// If there are no existing Attachment Records.
		if ( empty( $current ) ) {

			// Create an Attachment Record from each value.
			foreach ( $values as $key => $value ) {

				// Build required data.
				$attachment_data = $this->prepare_from_field( $value );

				// Build params for internal method.
				$params = [
					'key'             => $key,
					'value'           => $value,
					'attachment_data' => $attachment_data,
					'activity_id'     => $activity_id,
					'selector'        => $selector,
				];

				// Hand off to internal method.
				$attachment = $this->attachment_create( $params, $args );
				if ( false === $attachment ) {
					continue;
				}

				// Add to return array.
				$attachments[] = $attachment;

			}

			// Cast as boolean when empty.
			if ( empty( $attachments ) ) {
				$attachments = false;
			}

			// No need to go any further.
			return $attachments;

		}

		// We have existing Attachment Records.
		$actions = [
			'create' => [],
			'update' => [],
			'delete' => [],
		];

		// Let's look at each ACF Record and check its Attachment ID.
		foreach ( $values as $key => $value ) {

			// New Records have no Attachment ID.
			if ( empty( $value['field_attachment_id'] ) ) {
				$actions['create'][ $key ] = $value;
				continue;
			}

			// Records to update have an Attachment ID.
			if ( ! empty( $value['field_attachment_id'] ) ) {
				$actions['update'][ $key ] = $value;
				continue;
			}

		}

		// Grab the ACF Attachment ID values.
		$acf_attachment_ids = wp_list_pluck( $values, 'field_attachment_id' );

		// Sanitise array contents.
		array_walk(
			$acf_attachment_ids,
			function( &$item ) {
				$item = (int) trim( $item );
			}
		);

		// Records to delete are missing from the ACF data.
		foreach ( $current as $current_attachment ) {
			if ( ! in_array( (int) $current_attachment['id'], $acf_attachment_ids, true ) ) {
				$actions['delete'][] = $current_attachment['id'];
				continue;
			}
		}

		// Create CiviCRM Attachment Records.
		foreach ( $actions['create'] as $key => $value ) {

			// Build required data.
			$attachment_data = $this->prepare_from_field( $value );

			// Build params for internal method.
			$params = [
				'key'             => $key,
				'value'           => $value,
				'attachment_data' => $attachment_data,
				'activity_id'     => $activity_id,
				'selector'        => $selector,
			];

			// Hand off to internal method.
			$attachment = $this->attachment_create( $params, $args );
			if ( false === $attachment ) {
				continue;
			}

			// Add to return array.
			$attachments[] = $attachment;

		}

		// Update CiviCRM Attachment Records.
		foreach ( $actions['update'] as $key => $value ) {

			// Build required data.
			$attachment_data = $this->prepare_from_field( $value, $value['field_attachment_id'] );

			// Cast Attachment ID as integer.
			$attachment_id = (int) $attachment_data['file'];

			// Get the Attachment metadata.
			$meta = $this->civicrm->attachment->metadata_get( $attachment_id );

			// Get the existing CiviCRM Attachment.
			$civicrm_attachment = [];
			foreach ( $current as $item ) {
				if ( (int) $item['id'] === (int) $attachment_data['id'] ) {
					$civicrm_attachment = $item;
					break;
				}
			}

			// Sanity check.
			if ( empty( $civicrm_attachment ) ) {
				continue;
			}

			// Has the WordPress File changed?
			$wordpress_file_changed = false;
			$file                   = get_attached_file( $attachment_id, true );
			if ( $meta['wordpress_file'] !== $file ) {
				$wordpress_file_changed = true;
			}

			// Has the File Description changed?
			$description_changed = false;
			if ( $attachment_data['description'] !== $civicrm_attachment['description'] ) {
				$description_changed = true;
			}

			// Skip when nothing has changed.
			if ( ! $wordpress_file_changed && ! $description_changed ) {
				continue;
			}

			// When only the description has changed.
			if ( ! $wordpress_file_changed && $description_changed ) {

				// Build the API params.
				$params = [
					'id'          => $civicrm_attachment['id'],
					'description' => $attachment_data['description'],
				];

				// Update the Attachment.
				$attachment = $this->civicrm->attachment->update( $params );

				// Make an array of our params.
				$params = [
					'key'         => $key,
					'value'       => $value,
					'attachment'  => $attachment,
					'activity_id' => $activity_id,
					'selector'    => $selector,
				];

				/**
				 * Broadcast that an Attachment Record has been updated.
				 *
				 * @since 0.5.4
				 *
				 * @param array $params The Attachment data.
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/activity/attachment/updated', $params, $args );

				// Add to return array.
				$attachments[] = $attachment;

				continue;

			}

			/*
			 * The WordPress File has changed.
			 *
			 * We have to delete the current CiviCRM Attachment and re-create a
			 * new one. We delete before re-creating in case we hit the limit
			 * of allowed Entity Attachments.
			 *
			 * Also worth considering whether to delete the previous WordPress
			 * Attachment. ACF doesn't, so perhaps we shouldn't either.
			 */

			// Delete the current CiviCRM Attachment.
			$this->attachment_delete( $civicrm_attachment['id'], $activity_id, $selector, $args );

			// Build params for internal method.
			$params = [
				'key'             => $key,
				'value'           => $value,
				'attachment_data' => $attachment_data,
				'activity_id'     => $activity_id,
				'selector'        => $selector,
			];

			// Hand off to internal method.
			$attachment = $this->attachment_create( $params, $args );
			if ( false === $attachment ) {
				continue;
			}

			// Add to return array.
			$attachments[] = $attachment;

		}

		// Delete CiviCRM Attachment Records.
		foreach ( $actions['delete'] as $attachment_id ) {
			$this->attachment_delete( $attachment_id, $activity_id, $selector, $args );
		}

		// Cast as boolean when empty.
		if ( empty( $attachments ) ) {
			$attachments = false;
		}

		// --<
		return $attachments;

	}

	/**
	 * Creates a CiviCRM Attachment Record.
	 *
	 * @since 0.5.4
	 *
	 * @param array $data The array of relevant data.
	 * @param array $args The array of WordPress params.
	 * @return array|bool $attachment The array of Attachment Record data, or false on failure.
	 */
	public function attachment_create( $data, $args = [] ) {

		// Cast Attachment ID as integer.
		$attachment_id = (int) $data['attachment_data']['file'];

		// Get the WordPress File, Filename and Mime Type.
		$file      = get_attached_file( $attachment_id, true );
		$filename  = pathinfo( $file, PATHINFO_BASENAME );
		$mime_type = get_post_mime_type( $attachment_id );

		// Make a backup of the File.
		$backup = $this->civicrm->attachment->file_copy_for_civicrm( $file );

		// Build the API params.
		$params = [
			'entity_id'    => $data['activity_id'],
			'entity_table' => 'civicrm_activity',
			'name'         => $filename,
			'description'  => $data['attachment_data']['description'],
			'mime_type'    => $mime_type,
			'options'      => [
				'move-file' => $file,
			],
		];

		// Create the Attachment.
		$attachment = $this->civicrm->attachment->create( $params );

		// Always restore the backup File.
		$original      = $this->civicrm->attachment->file_copy( $backup, $filename );
		$wp_filesystem = $this->plugin->wp->filesystem_init();
		if ( $wp_filesystem ) {
			$wp_filesystem->delete( $backup );
		}

		// Skip if we failed to create the Attachment.
		if ( false === $attachment ) {
			return false;
		}

		// Save metadata.
		$meta = [
			'wordpress_file' => $file,
			'civicrm_file'   => $attachment['path'],
		];

		// Store some Attachment metadata.
		$this->civicrm->attachment->metadata_set( $attachment_id, $meta );

		// Make an array of our params.
		$params = [
			'key'         => $data['key'],
			'value'       => $data['value'],
			'attachment'  => $attachment,
			'activity_id' => $data['activity_id'],
			'selector'    => $data['selector'],
		];

		/**
		 * Broadcast that an Attachment Record has been created.
		 *
		 * We use this internally to update the ACF Field with the Attachment ID.
		 *
		 * @since 0.5.4
		 *
		 * @param array $params The Attachment data.
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/activity/attachment/created', $params, $args );

		// --<
		return $attachment;

	}

	/**
	 * Deletes a CiviCRM Attachment Record.
	 *
	 * @since 0.5.4
	 *
	 * @param integer $attachment_id The numeric ID of the CiviCRM Attachment.
	 * @param integer $activity_id The numeric ID of the Activity.
	 * @param string  $selector The ACF Field selector.
	 * @param array   $args The array of WordPress params.
	 */
	public function attachment_delete( $attachment_id, $activity_id, $selector, $args = [] ) {

		// Okay, let's do it.
		$attachment = $this->civicrm->attachment->delete( $attachment_id );

		// Make an array of our params.
		$params = [
			'attachment_id' => $attachment_id,
			'attachment'    => $attachment,
			'activity_id'   => $activity_id,
			'selector'      => $selector,
		];

		/**
		 * Broadcast that an Attachment Record has been deleted.
		 *
		 * @since 0.5.4
		 *
		 * @param array $params The Attachment data.
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/acf/activity/attachment/deleted', $params, $args );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Intercept when a CiviCRM Attachment Record has been updated.
	 *
	 * @since 0.5.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function attachment_edited( $args ) {

		// Grab the Attachment Record data.
		$attachment = $args['attachment'];

		// Bail if there is no CiviCRM Attachment data.
		if ( empty( $attachment ) ) {
			return;
		}

		// Bail if this is not an Activity Attachment.
		if ( 'civicrm_activity' !== $attachment->entity_table ) {
			return;
		}

		// Process the Attachment Record.
		$this->attachment_process( $attachment, $args );

	}

	/**
	 * Called when a CiviCRM Attachment is about to be deleted.
	 *
	 * @since 0.5.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function attachment_pre_delete( $args ) {

		// Grab the Attachment Record data.
		$attachment = $args['attachment'];

		// Bail if there is no CiviCRM Attachment data.
		if ( empty( $attachment ) ) {
			return;
		}

		// Bail if this is not an Activity Attachment.
		if ( 'civicrm_activity' !== $attachment->entity_table ) {
			return;
		}

		// Process the Attachment Record.
		$this->attachment_process( $attachment, $args );

	}

	/**
	 * Process a CiviCRM Attachment Record.
	 *
	 * @since 0.5.4
	 *
	 * @param object $attachment The CiviCRM Attachment Record object.
	 * @param array  $args The array of CiviCRM params.
	 */
	public function attachment_process( $attachment, $args ) {

		// Grab the Activity ID.
		$activity_id = (int) $attachment->entity_id;

		// Get the Activity data.
		$activity = $this->civicrm->activity->get_by_id( $activity_id );

		// Get originating Entity.
		$entity = $this->acf_loader->mapper->entity_get();

		// Bail if this Activity's Activity Type is not mapped.
		$post_type = $this->civicrm->activity->is_mapped( $activity, 'create' );
		if ( false === $post_type ) {
			return;
		}

		// Get the Post ID for this Activity.
		$post_id = $this->civicrm->activity->is_mapped_to_post( $activity, $post_type );
		if ( false !== $post_id ) {

			// Exclude "reverse" edits when a Post is the originator.
			if ( 'post' === $entity['entity'] && (int) $post_id === (int) $entity['id'] ) {
				return;
			}

			// Update the ACF Fields for this Post.
			$this->fields_update( $post_id, $attachment, $args );

		}

		/**
		 * Broadcast that an Attachment ACF Field may have been edited.
		 *
		 * @since 0.5.4
		 *
		 * @param array $activity The array of CiviCRM Activity data.
		 * @param object $attachment The CiviCRM Attachment Record object.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/attachments/attachment/updated', $activity, $attachment, $args );

	}

	/**
	 * Update Attachment ACF Fields on an Entity mapped to an Activity ID.
	 *
	 * @since 0.5.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object         $attachment The CiviCRM Attachment Record object.
	 * @param array          $args The array of CiviCRM params.
	 */
	public function fields_update( $post_id, $attachment, $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Attachment Record Fields.
		if ( empty( $acf_fields['attachments'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['attachments'] as $selector => $attachment_field ) {

			// Get existing Field value.
			$existing = get_field( $selector, $post_id );

			// Catch empty Field value and cast as array.
			if ( empty( $existing ) ) {
				$existing = [];
			}

			// Before applying edit, make some checks.
			if ( 'edit' === $args['op'] ) {

				// If there is no existing Field value, treat as a 'create' op.
				if ( empty( $existing ) ) {
					$args['op'] = 'create';
				} else {

					// Grab the ACF Attachment ID values.
					$acf_attachment_ids = wp_list_pluck( $existing, 'field_attachment_id' );

					// Sanitise array contents.
					array_walk(
						$acf_attachment_ids,
						function( &$item ) {
							$item = (int) trim( $item );
						}
					);

					// If the ID is missing, treat as a 'create' op.
					if ( ! in_array( (int) $attachment->id, $acf_attachment_ids, true ) ) {
						$args['op'] = 'create';
					}

				}

			}

			// Convert to ACF Attachment data.
			$acf_attachment = $this->prepare_from_civicrm( $attachment );

			// Process array record.
			switch ( $args['op'] ) {

				case 'create':
					// If the WordPress Attachment ID is empty, create one.
					if ( empty( $acf_attachment['field_attachment_file'] ) ) {
						$filename      = pathinfo( $attachment->path, PATHINFO_BASENAME );
						$attachment_id = $this->attachment_wp_create( $filename, $post_id );
						if ( ! empty( $attachment_id ) ) {
							$acf_attachment['field_attachment_file'] = $attachment_id;
						}
					}

					// Add array record.
					$existing[] = $acf_attachment;
					break;

				case 'edit':
					// If the WordPress Attachment ID is empty, create one.
					if ( empty( $acf_attachment['field_attachment_file'] ) ) {
						$filename      = pathinfo( $attachment->path, PATHINFO_BASENAME );
						$attachment_id = $this->attachment_wp_create( $filename, $post_id );
						if ( ! empty( $attachment_id ) ) {
							$acf_attachment['field_attachment_file'] = $attachment_id;
						}
					}

					// Overwrite array record.
					foreach ( $existing as $key => $record ) {
						if ( (int) $attachment->id === (int) $record['field_attachment_id'] ) {
							$existing[ $key ] = $acf_attachment;
							break;
						}
					}
					break;

				case 'delete':
					// If the WordPress Attachment ID is not empty, delete it.
					if ( ! empty( $acf_attachment['field_attachment_file'] ) ) {
						wp_delete_attachment( $acf_attachment['field_attachment_file'], true );
					}

					// Remove array record.
					foreach ( $existing as $key => $record ) {
						if ( (int) $attachment->id === (int) $record['field_attachment_id'] ) {
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

	// -----------------------------------------------------------------------------------

	/**
	 * Getter method for the "CiviCRM Attachments" key.
	 *
	 * @since 0.5.4
	 *
	 * @return string $acf_field_key The key of the "CiviCRM Attachments" in the ACF Field data.
	 */
	public function acf_field_key_get() {

		// --<
		return $this->acf_field_key;

	}

	/**
	 * Add any Attachment Fields that are attached to a Post.
	 *
	 * @since 0.5.4
	 *
	 * @param array   $acf_fields The existing ACF Fields array.
	 * @param array   $field The ACF Field.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Add if it has a reference to an Attachments Field.
		if ( ! empty( $field['type'] ) && 'civicrm_attachment' === $field['type'] ) {
			$acf_fields['attachments'][ $field['name'] ] = $field['type'];
		}

		// --<
		return $acf_fields;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Builds an array of CiviCRM URLs for filtering the ACF Attachment.
	 *
	 * This method is called when loading values via get_field().
	 *
	 * @since 0.5.4
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
		if ( empty( $field['file_link'] ) ) {
			return $value;
		}

		// Skip filter if using WordPress File.
		if ( 1 === (int) $field['file_link'] ) {
			return $value;
		}

		// Loop through our repeater.
		foreach ( $value as $item ) {

			// Skip items that do not have a WordPress Attachment ID.
			if ( empty( $item['field_attachment_file'] ) ) {
				continue;
			}

			// Cast as integer.
			$attachment_id = (int) $item['field_attachment_file'];

			// Skip if already parsed.
			if ( ! empty( $this->attachments[ $attachment_id ] ) ) {
				return $value;
			}

			// Get the Attachment metadata.
			$meta = $this->civicrm->attachment->metadata_get( $attachment_id );
			if ( empty( $meta['civicrm_file'] ) ) {
				continue;
			}

			// Try and find the CiviCRM File data.
			$filename     = pathinfo( $meta['civicrm_file'], PATHINFO_BASENAME );
			$civicrm_file = $this->civicrm->attachment->file_get_by_name( $filename );
			if ( empty( $civicrm_file ) ) {
				continue;
			}

			// Get the full CiviCRM Attachment data.
			$attachment = $this->civicrm->attachment->get_by_id( $civicrm_file->id );
			if ( empty( $attachment->url ) ) {
				continue;
			}

			// Store CiviCRM URL for filtering the ACF Attachment data.
			$this->attachments[ $attachment_id ] = $attachment->url;

		}

		// --<
		return $value;

	}

	/**
	 * Builds an array of CiviCRM URLs for filtering the ACF Attachment.
	 *
	 * This method is called when rendering the Field, e.g. in ACFE front end Forms.
	 *
	 * @since 0.5.4
	 *
	 * @param array $field The Field array holding all the Field options.
	 */
	public function acf_render_filter( $field ) {

		// Skip filter if CiviCRM File is not set.
		if ( empty( $field['file_link'] ) ) {
			return;
		}

		// Skip filter if using WordPress File.
		if ( 1 === (int) $field['file_link'] ) {
			return;
		}

		// Skip filter if there is no value.
		if ( empty( $field['value'] ) ) {
			return;
		}

		// Loop through our repeater.
		foreach ( $field['value'] as $item ) {

			// Skip items that do not have a WordPress Attachment ID.
			if ( empty( $item['field_attachment_file'] ) ) {
				continue;
			}

			// Cast as integer.
			$attachment_id = (int) $item['field_attachment_file'];

			// Skip if already parsed.
			if ( ! empty( $this->attachments[ $attachment_id ] ) ) {
				continue;
			}

			// Get the Attachment metadata.
			$meta = $this->civicrm->attachment->metadata_get( $attachment_id );
			if ( empty( $meta['civicrm_file'] ) ) {
				continue;
			}

			// Try and find the CiviCRM File data.
			$filename     = pathinfo( $meta['civicrm_file'], PATHINFO_BASENAME );
			$civicrm_file = $this->civicrm->attachment->file_get_by_name( $filename );
			if ( empty( $civicrm_file ) ) {
				continue;
			}

			// Get the full CiviCRM Attachment data.
			$attachment = $this->civicrm->attachment->get_by_id( $civicrm_file->id );
			if ( empty( $attachment->url ) ) {
				continue;
			}

			// Store CiviCRM URL for filtering the ACF Attachment data.
			$this->attachments[ $attachment_id ] = $attachment->url;

		}

	}

	/**
	 * Maybe filter the URL of the File.
	 *
	 * @since 0.5.4
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
	 * Sync new CiviCRM Attachment data back to the ACF Fields on a WordPress Post.
	 *
	 * The Attachment ID needs to be reverse-synced to the relevant array element
	 * in the Field.
	 *
	 * @since 0.5.4
	 *
	 * @param array $params The Attachment data.
	 * @param array $args The array of WordPress params.
	 */
	public function maybe_sync_attachment_data( $params, $args ) {

		// Get Entity reference.
		$entity = $this->acf_loader->acf->field->entity_type_get( $args['post_id'] );

		// Check permissions if it's a Post.
		if ( 'post' === $entity ) {
			if ( ! current_user_can( 'edit_post', $args['post_id'] ) ) {
				return;
			}
		}

		// Maybe cast Attachment as an object.
		if ( ! is_object( $params['attachment'] ) ) {
			$params['attachment'] = (object) $params['attachment'];
		}

		// Get existing Field value.
		$existing = get_field( $params['selector'], $args['post_id'] );

		// Add Attachment ID and overwrite array element.
		if ( ! empty( $existing[ $params['key'] ] ) ) {

			// Assign Attachment ID.
			$params['value']['field_attachment_id'] = $params['attachment']->id;

			// Apply changes.
			$existing[ $params['key'] ] = $params['value'];

		}

		// Now update Field.
		$this->acf_loader->acf->field->value_update( $params['selector'], $existing, $args['post_id'] );

	}

}
