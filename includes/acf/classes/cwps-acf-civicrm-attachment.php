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
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5.2
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
	 * Attachment meta key.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var string $attachment_key The Attachment meta key.
	 */
	public $attachment_key = '_cwps_attachment_data';

	/**
	 * Public Attachment Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var array $attachment_fields The array of public Attachment Fields.
	 */
	public $attachment_fields = [
		'file' => 'file',
		'content' => 'text',
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
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'register_hooks' ] );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.5.2
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.5.2
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
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
		if ( $this->mapper_hooks === false ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/acf_fields/saved/pre', [ $this, 'acf_fields_pre_save' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}



	// -------------------------------------------------------------------------



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

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numerical ID must be present to update an Attachment.', 'civicrm-wp-profile-sync' ),
				'data' => $data,
				'backtrace' => $trace,
			], true ) );
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
	 * @param array $data The numeric ID of the CiviCRM Attachment.
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
			'id' => $attachment_id,
		];

		// Call the API.
		$result = civicrm_api( 'Attachment', 'delete', $params );

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
	 * Make a renamed copy of a File.
	 *
	 * The CiviCRM API moves a File to the secure directory, so we need to make
	 * a copy of the File so that the WordPress Attachment remains valid.
	 *
	 * @since 0.5.2
	 *
	 * @param string $file The path to the File.
	 * @return string|bool $new_file The path to the copied File, or false on failure.
	 */
	public function file_copy( $file ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'file' => $file,
			//'backtrace' => $trace,
		), true ) );
		*/

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return false;
		}

		// Extract the filename so we can rename it in CiviCRM style.
		$filename = pathinfo( $file, PATHINFO_BASENAME );
		$new_name = CRM_Utils_File::makeFileName( $filename );

		// Build path for new File.
		$new_file = str_replace( $filename, $new_name, $file );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'filename' => $filename,
			'new_name' => $new_name,
			'new_file' => $new_file,
			//'backtrace' => $trace,
		), true ) );
		*/

		// Try and copy the File.
		if ( ! copy( $file, $new_file ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'Could not copy File.', 'civicrm-wp-profile-sync' ),
				'file' => $file,
				'new_file' => $new_file,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $new_file;

	}



	// -------------------------------------------------------------------------



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
			'id' => $attachment_id,
		];

		// Get Attachment details via API.
		$result = civicrm_api( 'Attachment', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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
			'version' => 3,
			'sequential' => 1,
			'entity_table' => 'civicrm_' . $entity,
			'entity_id' => $entity_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Attachment', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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



	// -------------------------------------------------------------------------



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
	public function get_by_name( $filename ) {

		// Init return.
		$file = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $file;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'uri' => $filename,
		];

		// Get File details via API.
		$result = civicrm_api( 'File', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
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



	// -------------------------------------------------------------------------



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
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

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



	// -------------------------------------------------------------------------



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

		// We need the Post itself.
		$post = get_post( $args['post_id'] );

		// Bail if this is a revision.
		if ( $post->post_type == 'revision' ) {
			return;
		}

		/*
		 * Get existing Field values.
		 *
		 * These are actually the *old* values because we are hooking in *before*
		 * the Fields have been saved.
		 */
		$fields = get_fields( $post->ID, false );

		// Loop through the Fields and store any File Fields.
		$this->file_fields = [];
		foreach ( $fields as $selector => $value ) {
			$settings = get_field_object( $selector, $post->ID );
			if ( $settings['type'] === 'file' ) {
				$this->file_fields[] = [
					'post_id' => $post->ID,
					'selector' => $selector,
					'attachment_id' => $value,
				];
			}
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'file_fields' => $this->file_fields,
			//'backtrace' => $trace,
		), true ) );
		*/

	}



	/**
	 * Gets the data for an ACF Field before it was saved.
	 *
	 * @since 0.5.2
	 *
	 * @param string $selector The ACF Field selector.
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



	// -------------------------------------------------------------------------



	/**
	 * Get the value of an ACF "File" Field formatted for CiviCRM.
	 *
	 * The only kind of sync that an ACF File Field can do at the moment is to
	 * sync with a CiviCRM Custom Field.
	 *
	 * Other CiviCRM Entities can also accept files as "Attachments" but we'll
	 * return to that later.
	 *
	 * The ACF File Field return format can be either 'array', 'url' or 'id' so
	 * we need to extract the appropriate file info to send to CiviCRM.
	 *
	 * @since 0.5.2
	 *
	 * @param integer|null $value The Field value (the Attachment data).
	 * @param array $settings The ACF Field settings.
	 * @param array $args Any additional arguments.
	 * @return string $value The path of the file.
	 */
	public function value_get_for_civicrm( $value, $settings, $args ) {

		// Find the data from before the save operation.
		$file_field = $this->acf_field_pre_save_get( $args['selector'], $args['post_id'] );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'value' => $value,
			'settings' => $settings,
			'args' => $args,
			'file_field' => $file_field,
			//'backtrace' => $trace,
		), true ) );
		*/

		// When value is empty.
		if ( empty( $value ) ) {

			// Return early if it was previously empty.
			if ( empty( $file_field['attachment_id'] ) ) {

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'message' => '========================= NO FILE BEFORE OR AFTER ===================================',
					//'backtrace' => $trace,
				), true ) );
				*/

				return '';
			}

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( array(
				'method' => __METHOD__,
				'message' => '========================= FILE HAS BEEN DELETED ===================================',
				//'backtrace' => $trace,
			), true ) );
			*/

			// The ACF Field has been "unlinked" from the Attachment.

			// Look for previous Attachment metadata.
			$meta = $this->metadata_get( (int) $file_field['attachment_id'] );
			if ( empty( $meta['civicrm_file'] ) ) {
				return '';
			}

			// Try and delete the previous CiviCRM Attachment.
			$filename = pathinfo( $meta['civicrm_file'], PATHINFO_BASENAME );
			$civicrm_file = $this->get_by_name( $filename );
			if ( ! empty( $civicrm_file ) ) {
				// It seems the Custom Field is cleared by doing this.
				$this->delete( $civicrm_file->id );
			}

			// Okay, bail.
			return '';

		// If it's an array, the WordPress Attachment ID is in the array.
		} elseif ( is_array( $value ) ) {
			$attachment_id = $value['id'];

		// When it's numeric, it's the ID of the Attachment.
		} elseif ( is_numeric( $value ) ) {
			$attachment_id = $value;

		// When it's a string, it must be the URL.
		} elseif ( is_string( $value ) ) {

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

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'attachment_id' => $attachment_id,
			'file' => $file,
			'meta' => $meta,
			//'backtrace' => $trace,
		), true ) );
		*/

		// If we get metadata for the current Attachment.
		if ( ! empty( $meta ) ) {

			// Skip the update if there is no change.
			if ( $meta['wordpress_file'] === $file ) {

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'message' => '========================= FILE EXISTS BUT HAS NOT CHANGED ===================================',
					//'backtrace' => $trace,
				), true ) );
				*/

				return '';
			}

		}

		// Check if the Attachment has been switched.
		if ( ! empty( $file_field['attachment_id'] ) ) {
			if ( (int) $file_field['attachment_id'] !== $attachment_id ) {

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( array(
					'method' => __METHOD__,
					'message' => '========================= FILE HAS BEEN CHANGED ===================================',
					//'backtrace' => $trace,
				), true ) );
				*/

				// Try and delete the previous CiviCRM Attachment.
				$previous_meta = $this->metadata_get( (int) $file_field['attachment_id'] );
				if ( ! empty( $previous_meta['civicrm_file'] ) ) {
					$filename = pathinfo( $previous_meta['civicrm_file'], PATHINFO_BASENAME );
					$civicrm_file = $this->get_by_name( $filename );
					if ( ! empty( $civicrm_file ) ) {
						// It seems the Custom Field is cleared by doing this.
						$this->delete( $civicrm_file->id );
					}
				}

			}
		}

		// Make a copy of the file for CiviCRM to move.
		$new_file = $this->file_copy( $file );
		if ( $new_file === false ) {
			return '';
		}

		// Save metadata.
		$data = [
			'wordpress_file' => $file,
			'civicrm_file' => $new_file,
		];

		// Store some Attachment metadata.
		$this->metadata_set( $attachment_id, $data );

		// Finally, build array for CiviCRM.
		$value = [
			'name' => $new_file,
			'type' => get_post_mime_type( $attachment_id ),
		];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( array(
			'method' => __METHOD__,
			'value-AFTER' => $value,
			//'backtrace' => $trace,
		), true ) );
		*/

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Attachment data for a given WordPress Attachment ID.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $attachment_id The numeric ID of the WordPress Attachment.
	 * @return array $metadata The CiviCRM Attachment data, empty if none exists.
	 */
	public function metadata_get( $attachment_id ) {

		// Get the Contact ID.
		$existing = get_post_meta( $attachment_id, $this->attachment_key, true );

		// Does this Post have metadata?
		if ( empty( $existing ) ) {
			$metadata = [];
		} else {
			$metadata = $existing;
		}

		// --<
		return $metadata;

	}



	/**
	 * Set the CiviCRM Attachment data for a given WordPress Attachment ID.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $attachment_id The numeric ID of the WordPress Attachment.
	 * @param array $metadata The CiviCRM Attachment data.
	 */
	public function metadata_set( $attachment_id, $metadata ) {

		// Store the CiviCRM Attachment data.
		update_post_meta( $attachment_id, $this->attachment_key, $metadata );

	}



	/**
	 * Delete the CiviCRM Attachment data for a given WordPress Attachment ID.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $attachment_id The numeric ID of the WordPress Attachment.
	 */
	public function metadata_delete( $attachment_id ) {

		// Delete the Attachment data.
		delete_post_meta( $attachment_id, $this->attachment_key );

	}



} // Class ends.



