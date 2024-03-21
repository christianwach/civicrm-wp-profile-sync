<?php
/**
 * CiviCRM Multiple Record Set Class.
 *
 * Handles CiviCRM Multiple Record Set functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Multiple Record Set Class.
 *
 * A class that encapsulates CiviCRM Multiple Record Set functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Multiple_Record_Set extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
	 * CiviCRM object.
	 *
	 * @since 0.4
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
	 * An array of Instant Messenger Records prior to delete.
	 *
	 * There are situations where nested updates take place (e.g. via CiviRules)
	 * so we keep copies of the Instant Messenger Records in an array and try
	 * and match them up in the post delete hook.
	 *
	 * @since 0.4
	 * @access private
	 * @var array
	 */
	private $bridging_array = [];

	/**
	 * ACF Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $fields_handled = [
		'civicrm_multiset',
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

		/*
		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Add any Multiple Record Set Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post-Contact sync event.
		add_action( 'cwps/acf/post/contact/sync', [ $this, 'contact_sync_to_post' ], 10 );

		// Maybe sync the Multiple Record Set "Multiple Record Set ID" to the ACF Subfields.
		add_action( 'cwps/acf/multiset/created', [ $this, 'maybe_sync_multiset_id' ], 10, 2 );
		*/

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

		// Intercept when a CiviCRM Multiple Record Set has been updated.
		add_action( 'cwps/acf/mapper/multiset/created', [ $this, 'multiset_edited' ], 10 );
		add_action( 'cwps/acf/mapper/multiset/edited', [ $this, 'multiset_edited' ], 10 );

		// Intercept when a CiviCRM Multiple Record Set is being deleted.
		add_action( 'cwps/acf/mapper/multiset/delete/pre', [ $this, 'multiset_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/multiset/deleted', [ $this, 'multiset_deleted' ], 10 );

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
		remove_action( 'cwps/acf/mapper/multiset/created', [ $this, 'multiset_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/multiset/edited', [ $this, 'multiset_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/multiset/delete/pre', [ $this, 'multiset_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/multiset/deleted', [ $this, 'multiset_deleted' ], 10 );

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
			$settings = get_field_object( $field );

			// Maybe update a Multiple Record Set.
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

		// Update the Multiple Record Sets.
		$success = $this->multisets_update( $value, $contact_id, $field, $args );

		// --<
		return $success;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the data for a Multiple Record Set.
	 *
	 * @since 0.4
	 *
	 * @param integer $multiset_id The numeric ID of the Multiple Record Set.
	 * @return array $multiset The array of Multiple Record Set data, or empty if none.
	 */
	public function multiset_get_by_id( $multiset_id ) {

		// Query the Custom Group.
		return $this->plugin->civicrm->custom_group->get_by_id( $multiset_id );

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Multiple Record Sets for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $multiset_data The array of Multiple Record Set data for the CiviCRM Contact.
	 */
	public function multisets_get_for_contact( $contact_id ) {

		// Init return.
		$multiset_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $multiset_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $multiset_data;
		}

		// Define params to get queried Multiple Record Sets.
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
			return $multiset_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $multiset_data;
		}

		// The result set it what we want.
		$multiset_data = $result['values'];

		// --<
		return $multiset_data;

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

		// Get all Multiple Record Sets for this Contact.
		$data = $this->multisets_get_for_contact( $args['objectId'] );

		// Bail if there are no Multiple Record Set Fields.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Multiple Record Set Fields.
		if ( empty( $acf_fields['multiset'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['multiset'] as $selector => $multiset_field ) {

			// Init Field value.
			$value = [];

			// Let's look at each Multiple Record Set in turn.
			foreach ( $data as $multiset ) {

				// Convert to ACF Multiple Record Set data.
				$acf_multiset = $this->prepare_from_civicrm( $multiset );

				// Add to Field value.
				$value[] = $acf_multiset;

			}

			// Now update Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

		}

	}

	/**
	 * Update all of a CiviCRM Contact's Multiple Record Sets.
	 *
	 * @since 0.4
	 *
	 * @param array $values The array of Multiple Record Sets to update the Contact with.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $selector The ACF Field selector.
	 * @param array $args The array of WordPress params.
	 * @return array|bool $multisets The array of Multiple Record Sets, or false on failure.
	 */
	public function multisets_update( $values, $contact_id, $selector, $args = [] ) {

		// Init return.
		$multisets = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $multisets;
		}

		// Get the current Multiple Record Sets.
		$current = $this->multisets_get_for_contact( $contact_id );

		// If there are no existing Multiple Record Sets.
		if ( empty( $current ) ) {

			// Create a Multiple Record Set from each value.
			foreach ( $values as $key => $value ) {

				// Build required data.
				$multiset_data = $this->prepare_from_field( $value );

				// Okay, let's do it.
				$multiset = $this->update( $contact_id, $multiset_data );

				// Add to return array.
				$multisets[] = $multiset;

				// Make an array of our params.
				$params = [
					'key' => $key,
					'value' => $value,
					'multiset' => $multiset,
					'contact_id' => $contact_id,
					'selector' => $selector,
				];

				/**
				 * Broadcast that a Multiple Record Set has been created.
				 *
				 * We use this internally to update the ACF Field with the Multiple Record Set ID.
				 *
				 * @since 0.4
				 *
				 * @param array $params The Multiple Record Set data.
				 * @param array $args The array of WordPress params.
				 */
				do_action( 'cwps/acf/multiset/created', $params, $args );

			}

			// No need to go any further.
			return $multisets;

		}

		// We have existing Multiple Record Sets.
		$actions = [
			'create' => [],
			'update' => [],
			'delete' => [],
		];

		// Let's look at each ACF Record and check its Multiple Record Set ID.
		foreach ( $values as $key => $value ) {

			// New Records have no Multiple Record Set ID.
			if ( empty( $value['field_multiset_id'] ) ) {
				$actions['create'][ $key ] = $value;
				continue;
			}

			// Records to update have a Multiple Record Set ID.
			if ( ! empty( $value['field_multiset_id'] ) ) {
				$actions['update'][ $key ] = $value;
				continue;
			}

		}

		// Grab the ACF Multiple Record Set ID values.
		$acf_multiset_ids = wp_list_pluck( $values, 'field_multiset_id' );

		// Sanitise array contents.
		array_walk( $acf_multiset_ids, function( &$item ) {
			$item = (int) trim( $item );
		} );

		// Records to delete are missing from the ACF data.
		foreach ( $current as $current_multiset ) {
			if ( ! in_array( $current_multiset['id'], $acf_multiset_ids ) ) {
				$actions['delete'][] = $current_multiset['id'];
				continue;
			}
		}

		// Create CiviCRM Multiple Record Sets.
		foreach ( $actions['create'] as $key => $value ) {

			// Build required data.
			$multiset_data = $this->prepare_from_field( $value );

			// Okay, let's do it.
			$multiset = $this->update( $contact_id, $multiset_data );

			// Add to return array.
			$multisets[] = $multiset;

			// Make an array of our params.
			$params = [
				'key' => $key,
				'value' => $value,
				'multiset' => $multiset,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Multiple Record Set has been created.
			 *
			 * We use this internally to update the ACF Field with the Multiple Record Set ID.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Multiple Record Set data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/multiset/created', $params, $args );

		}

		// Update CiviCRM Multiple Record Sets.
		foreach ( $actions['update'] as $key => $value ) {

			// Build required data.
			$multiset_data = $this->prepare_from_field( $value, $value['field_multiset_id'] );

			// Okay, let's do it.
			$multiset = $this->update( $contact_id, $multiset_data );

			// Add to return array.
			$multisets[] = $multiset;

			// Make an array of our params.
			$params = [
				'key' => $key,
				'value' => $value,
				'multiset' => $multiset,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Multiple Record Set has been updated.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Multiple Record Set data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/multiset/updated', $params, $args );

		}

		// Delete CiviCRM Multiple Record Sets.
		foreach ( $actions['delete'] as $multiset_id ) {

			// Okay, let's do it.
			$multiset = $this->delete( $multiset_id );

			// Make an array of our params.
			$params = [
				'multiset_id' => $multiset_id,
				'multiset' => $multiset,
				'contact_id' => $contact_id,
				'selector' => $selector,
			];

			/**
			 * Broadcast that a Multiple Record Set has been deleted.
			 *
			 * @since 0.4
			 *
			 * @param array $params The Multiple Record Set data.
			 * @param array $args The array of WordPress params.
			 */
			do_action( 'cwps/acf/multiset/deleted', $params, $args );

		}

	}

	/**
	 * Prepare the CiviCRM Multiple Record Set from an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $value The array of Multiple Record Set data in the ACF Field.
	 * @param integer $multiset_id The numeric ID of the Multiple Record Set (or null if new).
	 * @return array $multiset_data The CiviCRM Multiple Record Set data.
	 */
	public function prepare_from_field( $value, $multiset_id = null ) {

		// Init required data.
		$multiset_data = [];

		// Maybe add the Multiple Record Set ID.
		if ( ! empty( $multiset_id ) ) {
			$multiset_data['id'] = $multiset_id;
		}

		// Convert ACF data to CiviCRM data.
		$multiset_data['is_primary'] = empty( $value['field_multiset_primary'] ) ? '0' : '1';
		$multiset_data['location_type_id'] = (int) $value['field_multiset_location'];
		$multiset_data['provider_id'] = (int) $value['field_multiset_provider'];
		$multiset_data['name'] = trim( $value['field_multiset_name'] );

		// --<
		return $multiset_data;

	}

	/**
	 * Prepare the ACF Field data from a CiviCRM Multiple Record Set.
	 *
	 * @since 0.4
	 *
	 * @param array $value The array of Multiple Record Set data in CiviCRM.
	 * @return array $multiset_data The ACF Multiple Record Set data.
	 */
	public function prepare_from_civicrm( $value ) {

		// Init required data.
		$multiset_data = [];

		// Maybe cast as an object.
		if ( ! is_object( $value ) ) {
			$value = (object) $value;
		}

		// Convert CiviCRM data to ACF data.
		$multiset_data['field_multiset_name'] = trim( $value->name );
		$multiset_data['field_multiset_location'] = (int) $value->location_type_id;
		$multiset_data['field_multiset_provider'] = (int) $value->provider_id;
		$multiset_data['field_multiset_primary'] = empty( $value->is_primary ) ? '0' : '1';
		$multiset_data['field_multiset_id'] = (int) $value->id;

		// --<
		return $multiset_data;

	}

	/**
	 * Update a CiviCRM Contact's Multiple Record Set.
	 *
	 * If you want to "create" a Multiple Record Set, do not pass
	 * $data['id'] in. The presence of an ID will cause an update to that
	 * Multiple Record Set.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param string $data The Multiple Record Set data to update the Contact with.
	 * @return array|bool $multiset The array of Multiple Record Set data, or false on failure.
	 */
	public function update( $contact_id, $data ) {

		// Init return.
		$multiset = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $multiset;
		}

		// Define params to create new Multiple Record Set.
		$params = [
			'version' => 3,
			'contact_id' => $contact_id,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Im', 'create', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $multiset;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $multiset;
		}

		// The result set should contain only one item.
		$multiset = array_pop( $result['values'] );

		// --<
		return $multiset;

	}

	/**
	 * Delete a Multiple Record Set in CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param integer $multiset_id The numeric ID of the Multiple Record Set.
	 * @return bool $success True if successfully deleted, or false on failure.
	 */
	public function delete( $multiset_id ) {

		// Init return.
		$success = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $success;
		}

		// Define params to delete this Multiple Record Set.
		$params = [
			'version' => 3,
			'id' => $multiset_id,
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
	 * Intercept when a CiviCRM Multiple Record Set has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function multiset_edited( $args ) {

		// Grab the Multiple Record Set data.
		$civicrm_multiset = $args['objectRef'];

		// Bail if this is not a Contact's Multiple Record Set.
		if ( empty( $civicrm_multiset->contact_id ) ) {
			return;
		}

		// Process the Multiple Record Set.
		$this->multiset_process( $civicrm_multiset, $args );

	}

	/**
	 * A CiviCRM Contact's Multiple Record Set is about to be deleted.
	 *
	 * Before a Multiple Record Set is deleted, we need to remove the
	 * corresponding element in the ACF Field data.
	 *
	 * This is not required when creating or editing a Multiple Record Set.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function multiset_pre_delete( $args ) {

		// We just need the Multiple Record Set ID.
		$multiset_id = (int) $args['objectId'];

		// Grab the Multiple Record Set data from the database.
		$multiset_pre = $this->multiset_get_by_id( $multiset_id );

		// Maybe cast previous Multiple Record Set data as object and stash in a property.
		if ( ! is_object( $multiset_pre ) ) {
			$multiset_pre = (object) $multiset_pre;
		}

		// Stash in property array.
		$this->bridging_array[ $multiset_id ] = $multiset_pre;

	}

	/**
	 * A CiviCRM Multiple Record Set has just been deleted.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function multiset_deleted( $args ) {

		// We just need the Multiple Record Set ID.
		$multiset_id = (int) $args['objectId'];

		// Populate "Previous Multiple Record Set" if we have it stored.
		$multiset_pre = null;
		if ( ! empty( $this->bridging_array[ $multiset_id ] ) ) {
			$multiset_pre = $this->bridging_array[ $multiset_id ];
			unset( $this->bridging_array[ $multiset_id ] );
		}

		// Bail if we can't find the previous Multiple Record Set or it doesn't match.
		if ( empty( $multiset_pre ) || $multiset_id !== (int) $multiset_pre->id ) {
			return;
		}

		// Bail if this is not a Contact's Multiple Record Set.
		if ( empty( $multiset_pre->contact_id ) ) {
			return;
		}

		// Process the Multiple Record Set.
		$this->multiset_process( $multiset_pre, $args );

	}

	/**
	 * Process a CiviCRM Multiple Record Set.
	 *
	 * @since 0.4
	 *
	 * @param object $multiset The CiviCRM Multiple Record Set object.
	 * @param array $args The array of CiviCRM params.
	 */
	public function multiset_process( $multiset, $args ) {

		// Convert to ACF Multiple Record Set data.
		$acf_multiset = $this->prepare_from_civicrm( $multiset );

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $multiset->contact_id );

		// Bail if none of this Contact's Contact Types is mapped.
		$post_types = $this->civicrm->contact->is_mapped( $contact, 'create' );
		if ( $post_types === false ) {
			return;
		}

		// Handle each Post Type in turn.
		foreach ( $post_types as $post_type ) {

			// Get the Post ID for this Contact.
			$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );

			// Skip if not mapped or Post doesn't yet exist.
			if ( $post_id === false ) {
				continue;
			}

			// Get the ACF Fields for this Post.
			$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

			// Bail if there are no Multiple Record Set Fields.
			if ( empty( $acf_fields['multiset'] ) ) {
				continue;
			}

			// TODO: Find the ACF Fields to update.
			// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
			//$fields_to_update = $this->fields_to_update_get( $acf_fields, $multiset, $args['op'] );

			// Let's look at each ACF Field in turn.
			foreach ( $acf_fields['multiset'] as $selector => $multiset_field ) {

				// Get existing Field value.
				$existing = get_field( $selector, $post_id );

				// Before applying edit, make some checks.
				if ( $args['op'] == 'edit' ) {

					// If there is no existing Field value, treat as a 'create' op.
					if ( empty( $existing ) ) {
						$args['op'] = 'create';
					} else {

						// Grab the ACF Multiple Record Set ID values.
						$acf_multiset_ids = wp_list_pluck( $existing, 'field_multiset_id' );

						// Sanitise array contents.
						array_walk( $acf_multiset_ids, function( &$item ) {
							$item = (int) trim( $item );
						} );

						// If the ID is missing, treat as a 'create' op.
						if ( ! in_array( $multiset->id, $acf_multiset_ids ) ) {
							$args['op'] = 'create';
						}

					}

				}

				// Process array record.
				switch ( $args['op'] ) {

					case 'create':

						// Make sure no other Multiple Record Set is Primary if this one is.
						if ( $acf_multiset['field_multiset_primary'] == '1' && ! empty( $existing ) ) {
							foreach ( $existing as $key => $record ) {
								$existing[ $key ]['field_multiset_id'] = '0';
							}
						}

						// Add array record.
						$existing[] = $acf_multiset;

						break;

					case 'edit':

						// Overwrite array record.
						foreach ( $existing as $key => $record ) {
							if ( $multiset->id == $record['field_multiset_id'] ) {
								$existing[ $key ] = $acf_multiset;
								break;
							}
						}

						break;

					case 'delete':

						// Remove array record.
						foreach ( $existing as $key => $record ) {
							if ( $multiset->id == $record['field_multiset_id'] ) {
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

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the Multiple Record Sets that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $multisets The array of possible Multiple Record Sets.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$multisets = [];

		// Add extra param.
		// NOTE: This is not implemented.
		$extra = [
			'is_multiple' => 1,
		];

		// Init mapped flag.
		$mapped = false;

		/**
		 * Query if this Field Group is mapped.
		 *
		 * This filter sends out a request for other classes to respond with a
		 * array of Post Types if they detect that this Field Group maps to an
		 * Entity Type that they are responsible for (or Boolean "false" if not).
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact::query_field_group_mapped()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Activity::query_field_group_mapped()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Participant::query_field_group_mapped()
		 *
		 * @since 0.4
		 *
		 * @param bool $mapped False, since we're asking for a mapping.
		 * @param array $field_group The array of ACF Field Group data.
		 * @param array|bool $mapped An array of Post Types if the Field Group is mapped, or false if not mapped.
		 */
		$mapped = apply_filters( 'cwps/acf/query_field_group_mapped', $mapped, $field_group );

		// Bail if this Field Group is not mapped.
		if ( $mapped === false ) {
			return $field_group;
		}

		// Get all Multiple Record Sets for this Entity Type.
		// NOTE: "$extra" is not implemented. See the method for details.
		$type = '';
		$subtype = '';
		$custom_groups = $this->plugin->civicrm->custom_group->get_for_entity_type( $type, $subtype, $extra );

		// Filter groups to include only "Multiple".
		$filtered_groups = [];
		foreach ( $custom_groups as $custom_group_name => $custom_group ) {
			$filtered_groups[ $custom_group_name ][] = $custom_group;
		}

		// Bail if there are no groups.
		if ( empty( $filtered_groups ) ) {
			return $multisets;
		}

		// --<
		return $multisets;

	}

	// -------------------------------------------------------------------------

	/**
	 * Return the "CiviCRM Group" ACF Settings Field.
	 *
	 * @since 0.4
	 *
	 * @param array $custom_groups The array of Custom Groups.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $custom_groups = [] ) {

		// Build choices array for dropdown.
		$choices = [];

		/*
		// Build Custom Group choices array for dropdown.
		$custom_group_prefix = $this->civicrm->custom_group_prefix();
		foreach ( $custom_groups as $custom_group_name => $custom_group ) {
			$custom_groups_label = esc_attr( $custom_group_name );
			foreach ( $custom_group as $custom_field ) {
				$choices[$custom_fields_label][$custom_field_prefix . $custom_field['id']] = $custom_field['label'];
			}
		}
		*/

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.4
		 *
		 * @param array $choices The existing select options array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/multiset/choices', $choices );

		// Define Field.
		$field = [
			'key' => $this->civicrm->acf_field_key_get(),
			'label' => __( 'CiviCRM Field', 'civicrm-wp-profile-sync' ),
			'name' => $this->civicrm->acf_field_key_get(),
			'type' => 'select',
			'instructions' => __( 'Choose the CiviCRM Field that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
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
	 * Add any Multiple Record Set Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Add if it has a reference to a Multiple Record Set Field.
		if ( ! empty( $field['type'] == 'civicrm_multiset' ) ) {
			$acf_fields['multiset'][ $field['name'] ] = $field['type'];
		}

		// --<
		return $acf_fields;

	}

	// -------------------------------------------------------------------------

	/**
	 * Sync the CiviCRM "Multiple Record Set ID" to the ACF Fields on a WordPress Post.
	 *
	 * @since 0.4
	 *
	 * @param array $params The Multiple Record Set data.
	 * @param array $args The array of WordPress params.
	 */
	public function maybe_sync_multiset_id( $params, $args ) {

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $args['post']->ID ) ) {
			return;
		}

		// Maybe cast Multiple Record Set data as an object.
		if ( ! is_object( $params['multiset'] ) ) {
			$params['multiset'] = (object) $params['multiset'];
		}

		// Get existing Field value.
		$existing = get_field( $params['selector'], $args['post']->ID );

		// Add Multiple Record Set ID and overwrite array element.
		if ( ! empty( $existing[ $params['key'] ] ) ) {
			$params['value']['field_multiset_id'] = $params['multiset']->id;
			$existing[ $params['key'] ] = $params['value'];
		}

		// Now update Field.
		$this->acf_loader->acf->field->value_update( $params['selector'], $existing, $args['post']->ID );

	}

}
