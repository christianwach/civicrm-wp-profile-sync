<?php
/**
 * CiviCRM Phone Class.
 *
 * Handles CiviCRM Phone functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Phone Class.
 *
 * A class that encapsulates CiviCRM Phone Record functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Phone extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
		'civicrm_phone',
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
	 * This distinguishes Phone Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $email_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $phone_field_prefix = 'caiphone_';

	/**
	 * Public Phone Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $phone_fields The array of public Phone Fields.
	 */
	public $phone_fields = [
		'is_primary' => 'true_false',
		'is_billing' => 'true_false',
		'phone' => 'text',
		'phone_ext' => 'text',
		//'phone_type_id' => 'select',
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
		do_action( 'cwps/acf/civicrm/phone/loaded' );

	}



	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include Shortcode class file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/shortcodes/cwps-shortcode-phone.php';

	}



	/**
	 * Set up the child objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Init Phone Shortcode object.
		$this->shortcode = new CiviCRM_Profile_Sync_ACF_Shortcode_Phone( $this );

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add any Phone Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post created from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

		// Maybe sync the Phone Record "Phone ID" to the ACF Subfields.
		add_action( 'cwps/acf/civicrm/phone/created', [ $this, 'maybe_sync_phone_id' ], 10, 2 );

		// Listen for queries from the ACF Bypass class.
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

		// Listen for queries from our ACF Field Group class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 50, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'text_settings_modify' ], 10, 2 );

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

		// Listen for events from our Mapper that require Phone updates.
		add_action( 'cwps/acf/mapper/phone/created', [ $this, 'phone_edited' ], 10 );
		add_action( 'cwps/acf/mapper/phone/edited', [ $this, 'phone_edited' ], 10 );
		add_action( 'cwps/acf/mapper/phone/delete/pre', [ $this, 'phone_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/phone/deleted', [ $this, 'phone_deleted' ], 10 );

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
		remove_action( 'cwps/acf/mapper/phone/created', [ $this, 'phone_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/edited', [ $this, 'phone_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/delete/pre', [ $this, 'phone_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/phone/deleted', [ $this, 'phone_deleted' ], 10 );

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

			// Maybe update a Phone Record.
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

		// Update the Phone Records.
		$success = $this->phones_update( $value, $contact_id, $field, $args );

		// --<
		return $success;

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

		// Get all Phone Records for this Contact.
		$data = $this->plugin->civicrm->phone->phones_get_for_contact( $args['objectId'] );

		// Bail if there are no Phone Record Fields.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Phone Record Fields.
		if ( empty( $acf_fields['phone'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['phone'] as $selector => $phone_field ) {

			// Init Field value.
			$value = [];

			// Let's look at each Phone in turn.
			foreach ( $data as $phone ) {

				// Convert to ACF Phone data.
				$acf_phone = $this->prepare_from_civicrm( $phone );

				// Add to Field value.
				$value[] = $acf_phone;

			}

			// Now update Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

		}

	}



	/**
	 * Update all of a CiviCRM Contact's Phone Records.
	 *
	 * @since 0.4
	 *
	 * @param array $values The array of Phone Record arrays to update the Contact with.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $selector The ACF Field selector.
	 * @param array $args The array of WordPress params.
	 * @return array|bool $phones The array of Phone Record data, or false on failure.
	 */
	public function phones_update( $values, $contact_id, $selector, $args = [] ) {

		// Init return.
		$phones = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $phones;
		}

		// Get the current Phone Records.
		$current = $this->plugin->civicrm->phone->phones_get_for_contact( $contact_id );

		// If there are no existing Phone Records.
		if ( empty( $current ) ) {

			// Create a Phone Record from each value.
			foreach ( $values as $key => $value ) {

				// Build required data.
				$phone_data = $this->prepare_from_field( $value );

				// Okay, let's do it.
				$phone = $this->plugin->civicrm->phone->update( $contact_id, $phone_data );

				// Add to return array.
				$phones[] = $phone;

				// Make an array of our params.
				$params = [
					'key' => $key,
					'value' => $value,
					'phone' => $phone,
					'contact_id' => $contact_id,
					'selector' => $selector,
				];

				/**
				 * Broadcast that a Phone Record has been created.
				 *
				 * We use this internally to update the ACF Field with the Phone ID.
				 *
				 * @since 0.4
				 *
				 * @param array $params The Phone data.
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/civicrm/phone/created', $params, $args );

			}

			// No need to go any further.
			return $phones;

		}

		// We have existing Phone Records.
		$actions = [
			'create' => [],
			'update' => [],
			'delete' => [],
		];

		// Let's look at each ACF Record and check its Phone ID.
		foreach ( $values as $key => $value ) {

			// New Records have no Phone ID.
			if ( empty( $value['field_phone_id'] ) ) {
				$actions['create'][ $key ] = $value;
				continue;
			}

			// Records to update have a Phone ID.
			if ( ! empty( $value['field_phone_id'] ) ) {
				$actions['update'][ $key ] = $value;
				continue;
			}

		}

		// Grab the ACF Phone ID values.
		$acf_phone_ids = wp_list_pluck( $values, 'field_phone_id' );

		// Sanitise array contents.
		array_walk( $acf_phone_ids, function( &$item ) {
			$item = (int) trim( $item );
		} );

		// Records to delete are missing from the ACF data.
		foreach ( $current as $current_phone ) {
			if ( ! in_array( $current_phone['id'], $acf_phone_ids ) ) {
				$actions['delete'][] = $current_phone['id'];
				continue;
			}
		}

		// Create CiviCRM Phone Records.
		foreach ( $actions['create'] as $key => $value ) {

			// Build required data.
			$phone_data = $this->prepare_from_field( $value );

			// Okay, let's do it.
			$phone = $this->plugin->civicrm->phone->update( $contact_id, $phone_data );

			// Add to return array.
			$phones[] = $phone;

			// Make an array of our params.
			$params = [
				'key' => $key,
				'value' => $value,
				'phone' => $phone,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Phone Record has been created.
			 *
			 * We use this internally to update the ACF Field with the Phone ID.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Phone data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/phone/created', $params, $args );

		}

		// Update CiviCRM Phone Records.
		foreach ( $actions['update'] as $key => $value ) {

			// Build required data.
			$phone_data = $this->prepare_from_field( $value, $value['field_phone_id'] );

			// Okay, let's do it.
			$phone = $this->plugin->civicrm->phone->update( $contact_id, $phone_data );

			// Add to return array.
			$phones[] = $phone;

			// Make an array of our params.
			$params = [
				'key' => $key,
				'value' => $value,
				'phone' => $phone,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Phone Record has been updated.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Phone data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/phone/updated', $params, $args );

		}

		// Delete CiviCRM Phone Records.
		foreach ( $actions['delete'] as $phone_id ) {

			// Okay, let's do it.
			$phone = $this->plugin->civicrm->phone->delete( $phone_id );

			// Make an array of our params.
			$params = [
				'phone_id' => $phone_id,
				'phone' => $phone,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Phone Record has been deleted.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Phone data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/civicrm/phone/deleted', $params, $args );

		}

	}



	/**
	 * Prepare the CiviCRM Phone Record data from an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $value The array of Phone Record data in the ACF Field.
	 * @param integer $phone_id The numeric ID of the Phone Record (or null if new).
	 * @return array $phone_data The CiviCRM Phone Record data.
	 */
	public function prepare_from_field( $value, $phone_id = null ) {

		// Init required data.
		$phone_data = [];

		// Maybe add the Phone ID.
		if ( ! empty( $phone_id ) ) {
			$phone_data['id'] = $phone_id;
		}

		// Convert ACF data to CiviCRM data.
		$phone_data['is_primary'] = empty( $value['field_phone_primary'] ) ? '0' : '1';
		$phone_data['location_type_id'] = (int) $value['field_phone_location'];
		$phone_data['phone_type_id'] = (int) $value['field_phone_type'];
		$phone_data['phone'] = trim( $value['field_phone_number'] );
		$phone_data['phone_ext'] = trim( $value['field_phone_extension'] );

		// --<
		return $phone_data;

	}



	/**
	 * Prepare the ACF Field data from a CiviCRM Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param array $value The array of Phone Record data in CiviCRM.
	 * @return array $phone_data The ACF Phone data.
	 */
	public function prepare_from_civicrm( $value ) {

		// Init required data.
		$phone_data = [];

		// Maybe cast as an object.
		if ( ! is_object( $value ) ) {
			$value = (object) $value;
		}

		// Init optional Phone Extension.
		$phone_ext = empty( $value->phone_ext ) ? '' : $value->phone_ext;

		// Convert CiviCRM data to ACF data.
		$phone_data['field_phone_number'] = trim( $value->phone );
		$phone_data['field_phone_extension'] = $this->plugin->civicrm->denullify( $phone_ext );
		$phone_data['field_phone_location'] = (int) $value->location_type_id;
		$phone_data['field_phone_type'] = (int) $value->phone_type_id;
		$phone_data['field_phone_primary'] = empty( $value->is_primary ) ? '0' : '1';
		$phone_data['field_phone_id'] = (int) $value->id;

		// --<
		return $phone_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Phone Record has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_edited( $args ) {

		// Grab the Phone Record data.
		$civicrm_phone = $args['objectRef'];

		// Bail if this is not a Contact's Phone Record.
		if ( empty( $civicrm_phone->contact_id ) ) {
			return;
		}

		// Process the Phone Record.
		$this->phone_process( $civicrm_phone, $args );

	}



	/**
	 * A CiviCRM Contact's Phone Record is about to be deleted.
	 *
	 * Before a Phone Record is deleted, we need to retrieve the Phone Record
	 * because the data passed via "civicrm_post" only contains the ID of the
	 * Phone Record.
	 *
	 * This is not required when creating or editing a Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_pre_delete( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->phone_pre ) ) {
			unset( $this->phone_pre );
		}

		// We just need the Phone ID.
		$phone_id = (int) $args['objectId'];

		// Grab the Phone Record data from the database.
		$phone_pre = $this->plugin->civicrm->phone->phone_get_by_id( $phone_id );

		// Maybe cast previous Phone Record data as object and stash in a property.
		if ( ! is_object( $phone_pre ) ) {
			$this->phone_pre = (object) $phone_pre;
		} else {
			$this->phone_pre = $phone_pre;
		}

	}



	/**
	 * A CiviCRM Phone Record has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_deleted( $args ) {

		// Bail if we don't have a pre-delete Phone Record.
		if ( ! isset( $this->phone_pre ) ) {
			return;
		}

		// We just need the Phone ID.
		$phone_id = (int) $args['objectId'];

		// Sanity check.
		if ( $phone_id != $this->phone_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Phone Record.
		if ( empty( $this->phone_pre->contact_id ) ) {
			return;
		}

		// Process the Phone Record.
		$this->phone_process( $this->phone_pre, $args );

	}



	/**
	 * Process a CiviCRM Phone Record.
	 *
	 * @since 0.4
	 *
	 * @param object $phone The CiviCRM Phone Record object.
	 * @param array $args The array of CiviCRM params.
	 */
	public function phone_process( $phone, $args ) {

		// Convert to ACF Phone data.
		$acf_phone = $this->prepare_from_civicrm( $phone );

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $phone->contact_id );

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
				$this->fields_update( $post_id, $phone, $acf_phone, $args );

			}

		}

		/**
		 * Broadcast that a Phone ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $phone The CiviCRM Phone Record object.
		 * @param array $acf_phone The ACF Phone Record array.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/phone/updated', $contact, $phone, $acf_phone, $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Update Phone ACF Fields on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object $phone The CiviCRM Phone Record object.
	 * @param array $acf_phone The ACF Phone Record array.
	 * @param array $args The array of CiviCRM params.
	 */
	public function fields_update( $post_id, $phone, $acf_phone, $args ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Phone Record Fields.
		if ( empty( $acf_fields['phone'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['phone'] as $selector => $phone_field ) {

			// Get existing Field value.
			$existing = get_field( $selector, $post_id );

			// Before applying edit, make some checks.
			if ( $args['op'] == 'edit' ) {

				// If there is no existing Field value, treat as a 'create' op.
				if ( empty( $existing ) ) {
					$args['op'] = 'create';
				} else {

					// Grab the ACF Phone ID values.
					$acf_phone_ids = wp_list_pluck( $existing, 'field_phone_id' );

					// Sanitise array contents.
					array_walk( $acf_phone_ids, function( &$item ) {
						$item = (int) trim( $item );
					} );

					// If the ID is missing, treat as a 'create' op.
					if ( ! in_array( $phone->id, $acf_phone_ids ) ) {
						$args['op'] = 'create';
					}

				}

			}

			// Process array record.
			switch ( $args['op'] ) {

				case 'create':

					// Make sure no other Phone is Primary if this one is.
					if ( $acf_phone['field_phone_primary'] == '1' && ! empty( $existing ) ) {
						foreach ( $existing as $key => $record ) {
							$existing[ $key ]['field_phone_primary'] = '0';
						}
					}

					// Add array record.
					$existing[] = $acf_phone;

					break;

				case 'edit':

					// Make sure no other Phone is Primary if this one is.
					if ( $acf_phone['field_phone_primary'] == '1' ) {
						foreach ( $existing as $key => $record ) {
							$existing[ $key ]['field_phone_primary'] = '0';
						}
					}

					// Overwrite array record.
					foreach ( $existing as $key => $record ) {
						if ( $phone->id == $record['field_phone_id'] ) {
							$existing[ $key ] = $acf_phone;
							break;
						}
					}

					break;

				case 'delete':

					// Remove array record.
					foreach ( $existing as $key => $record ) {
						if ( $phone->id == $record['field_phone_id'] ) {
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
	 * Get the Phone Locations that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $location_types The array of possible Phone Locations.
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

		// Phone Locations are standard Location Types.
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
			'cwps/acf/phone/location_types/get_for_acf_field',
			$location_types, $field
		);

		// --<
		return $location_types;

	}



	/**
	 * Get the Phone Types that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $location_types The array of possible Phone Types.
	 */
	public function phone_types_get_for_acf_field( $field ) {

		// Init return.
		$phone_types = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $phone_types;
		}

		// Get the Phone Types array.
		$phone_type_ids = $this->plugin->civicrm->phone->phone_types_get();

		// Bail if there are no results.
		if ( empty( $phone_type_ids ) ) {
			return $phone_types;
		}

		// Assign to return.
		$phone_types = $phone_type_ids;

		// --<
		return $phone_types;

	}



	/**
	 * Add any Phone Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Add if it has a reference to a Phone Field.
		if ( ! empty( $field['type'] ) && $field['type'] == 'civicrm_phone' ) {
			$acf_fields['phone'][ $field['name'] ] = $field['type'];
		}

		// --<
		return $acf_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Sync the CiviCRM "Phone ID" to the ACF Fields on a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param array $params The Phone data.
	 * @param array $args The array of WordPress params.
	 */
	public function maybe_sync_phone_id( $params, $args ) {

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

		// Maybe cast Phone as an object.
		if ( ! is_object( $params['phone'] ) ) {
			$params['phone'] = (object) $params['phone'];
		}

		// Get existing Field value.
		$existing = get_field( $params['selector'], $args['post_id'] );

		// Add Phone ID and overwrite array element.
		if ( ! empty( $existing[ $params['key'] ] ) ) {
			$params['value']['field_phone_id'] = $params['phone']->id;
			$existing[ $params['key'] ] = $params['value'];
		}

		// Now update Field.
		$this->acf_loader->acf->field->value_update( $params['selector'], $existing, $args['post_id'] );

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Phone Fields.
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
		$result = civicrm_api( 'Phone', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Phone Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->phone_fields ) ) {
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
	 * Get the mapped Phone Field name if present.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing Field data array.
	 * @return string|bool $phone_field_name The name of the Phone Field, or false if none.
	 */
	public function phone_field_name_get( $field ) {

		// Init return.
		$phone_field_name = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Set the mapped Phone Field name if present.
		if ( isset( $field[ $acf_field_key ] ) ) {
			if ( false !== strpos( $field[ $acf_field_key ], $this->phone_field_prefix ) ) {
				$phone_field_name = (string) str_replace( $this->phone_field_prefix, '', $field[ $acf_field_key ] );
			}
		}

		/**
		 * Filter the Phone Field name.
		 *
		 * @since 0.5
		 *
		 * @param integer $phone_field_name The existing Phone Field name.
		 * @param array $field The array of ACF Field data.
		 */
		$phone_field_name = apply_filters( 'cwps/acf/civicrm/phone/phone_field/name', $phone_field_name, $field );

		// --<
		return $phone_field_name;

	}



	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
	 *
	 * The Phone Entity cannot have Custom Fields attached to it, so we can skip
	 * that part of the logic.
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
			if ( $field['type'] == $this->phone_fields[ $value['name'] ] ) {
				$fields_for_entity[] = $value;
			}
		}

		// Pass if not populated.
		if ( empty( $fields_for_entity ) ) {
			return $choices;
		}

		// Build Phone Field choices array for dropdown.
		$phone_fields_label = esc_attr__( 'Phone Fields', 'civicrm-wp-profile-sync' );
		foreach ( $fields_for_entity as $phone_field ) {
			$choices[ $phone_fields_label ][ $this->phone_field_prefix . $phone_field['name'] ] = $phone_field['title'];
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/phone/civicrm_field/choices', $choices );

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

		// Get the mapped Phone Field name if present.
		$field_name = $this->phone_field_name_get( $field );
		if ( $field_name === false ) {
			return $field;
		}

		// Get keyed array of options for this Phone Field.
		$field['choices'] = $this->options_get( $field_name );

		// "Phone Type ID" is optional.
		$field['allow_null'] = 1;

		// --<
		return $field;

	}



	/**
	 * Get the "select" options for a given CiviCRM Phone Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Phone Field.
	 * @return array $options The array of Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// We only have a few to account for.

		// Phone Type.
		if ( $name == 'phone_type_id' ) {
			$options = $this->plugin->civicrm->phone->phone_types_get();
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

		// Get the mapped Phone Field name if present.
		$phone_field_name = $this->phone_field_name_get( $field );
		if ( $phone_field_name === false ) {
			return $field;
		}

		// Get Phone Field data.
		$field_data = $this->plugin->civicrm->phone->get_by_name( $phone_field_name );

		// Set the "maxlength" attribute.
		if ( ! empty( $field_data['maxlength'] ) ) {
			$field['maxlength'] = $field_data['maxlength'];
		}

		// --<
		return $field;

	}



} // Class ends.



