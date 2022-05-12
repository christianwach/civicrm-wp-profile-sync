<?php
/**
 * CiviCRM Relationships Class.
 *
 * Handles CiviCRM Relationships functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Relationships Class.
 *
 * A class that encapsulates CiviCRM Relationships functionality.
 *
 * There are oddities in CiviCRM's relationships, particularly the "Employer Of"
 * relationship - which is both a Relationship and a "Contact Field". The ID of
 * the "Current Employer" Contact may be present in the values returned for a
 * "Contact" in the "current_employer" Field and can be set via the API by
 * populating the "employer_id" Field. I'm not sure how to handle this yet.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Relationship extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
	 * "CiviCRM Relationship" Field key in the ACF Field data.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $acf_field_key The key of the "CiviCRM Relationship" in the ACF Field data.
	 */
	public $acf_field_key = 'field_cacf_civicrm_relationship';

	/**
	 * Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $fields_handled The array of Fields which must be handled separately.
	 */
	public $fields_handled = [
		'civicrm_relationship',
	];

	/**
	 * Public Relationship Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $email_fields The array of public Relationship Fields.
	 */
	public $relationship_fields = [
		'start_date' => 'date_picker',
		'end_date' => 'date_picker',
		'is_active' => 'true_false',
		'description' => 'wysiwyg',
		'is_permission_a_b' => 'radio',
		'is_permission_b_a' => 'radio',
		'case_id' => 'select',
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

		// Process activation and deactivation.
		add_action( 'cwps/acf/civicrm/relationship/created', [ $this, 'relationship_activate' ], 10, 2 );
		add_action( 'cwps/acf/civicrm/relationship/activated', [ $this, 'relationship_activate' ], 10, 2 );
		add_action( 'cwps/acf/civicrm/relationship/deactivated', [ $this, 'relationship_deactivate' ], 10, 2 );

		// Add any Relationship Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Intercept Post synced from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

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

		// Listen for events from our Mapper that require Relationship updates.
		add_action( 'cwps/acf/mapper/relationship/created', [ $this, 'relationship_edited' ], 10 );
		add_action( 'cwps/acf/mapper/relationship/edited', [ $this, 'relationship_edited' ], 10 );
		add_action( 'cwps/acf/mapper/relationship/deleted', [ $this, 'relationship_edited' ], 10 );

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
		remove_action( 'cwps/acf/mapper/relationship/created', [ $this, 'relationship_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/relationship/edited', [ $this, 'relationship_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/relationship/deleted', [ $this, 'relationship_edited' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a Post has been updated from a Contact via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to Relationships.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get the Relationships for this Contact.
		$relationships = $this->relationships_get_for_contact( $args['objectId'] );

		// Bail if there are no Relationships.
		if ( empty( $relationships ) ) {
			return;
		}

		// Process each in turn.
		foreach ( $relationships as $relationship ) {

			// Build params.
			$params = [
				'op' => 'edit',
				'objectId' => $relationship['id'],
				'objectName' => 'Relationship',
				'objectRef' => (object) $relationship,
			];

			// Sync Relationship.
			$this->relationship_edited( $params );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Update a CiviCRM Contact's Fields with data from ACF Fields.
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

			// Maybe update a Relationship.
			$success = $this->field_handled_update( $field, $value, $args['contact']['id'], $settings );

		}

		// --<
		return $success;

	}



	/**
	 * Update a CiviCRM Contact's Relationship with data from an ACF Field.
	 *
	 * Relationships require special handling because they are not part
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

		// Get the "CiviCRM Relationship" key.
		$relationship_key = $this->acf_field_key_get();

		// Skip if we don't have a synced Relationship.
		if ( empty( $settings[ $relationship_key ] ) ) {
			return true;
		}

		// Skip if it's not a Relationship that requires special handling.
		if ( ! in_array( $settings['type'], $this->fields_handled ) ) {
			return true;
		}

		// The Relationship code is the setting.
		$code = $settings[ $relationship_key ];

		// Parse value by Field Type.
		$value = $this->acf_loader->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings );

		// Update the Relationships.
		$success = $this->relationships_update( $contact_id, $value, $code );

		// --<
		return $success;

	}



	// -------------------------------------------------------------------------



	/**
	 * Update all of a CiviCRM Contact's Relationships.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $target_contact_ids The array of Contact IDs in the ACF Field.
	 * @param string $code The code that identifies the Relationship and direction.
	 * @return array|bool $relationships The array of Relationship data, or false on failure.
	 */
	public function relationships_update( $contact_id, $target_contact_ids, $code ) {

		// Init return.
		$relationships = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationships;
		}

		// Get the Relationship data.
		$relationship_data = explode( '_', $code );
		$relationship_type_id = absint( $relationship_data[0] );
		$relationship_direction = $relationship_data[1];

		// Get the current Relationships.
		$params = [
			'version' => 3,
			'relationship_type_id' => $relationship_type_id,
		];

		// We need to find all Relationships for the Contact.
		if ( $relationship_direction == 'ab' ) {
			$params['contact_id_a'] = $contact_id;
		} else {
			$params['contact_id_b'] = $contact_id;
		}

		// Call the CiviCRM API.
		$current = civicrm_api( 'Relationship', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $current['is_error'] ) && $current['is_error'] == 1 ) {
			return $relationships;
		}

		// If there are no existing Relationships.
		if ( empty( $current['values'] ) ) {

			// Bail early if there are no target contact IDs.
			if ( empty( $target_contact_ids ) ) {
				return $relationships;
			}

			// Create a Relationship for each target.
			foreach ( $target_contact_ids as $target_contact_id ) {

				// Assign the correct Source and Target.
				if ( $relationship_direction == 'ab' ) {
					$contact_id_a = $contact_id;
					$contact_id_b = $target_contact_id;
				} else {
					$contact_id_a = $target_contact_id;
					$contact_id_b = $contact_id;
				}

				// Okay, let's do it.
				$relationship = $this->relationship_create( $contact_id_a, $contact_id_b, $relationship_type_id );
				if ( $relationship === false ) {
					continue;
				}

				// Add to return array.
				$relationships[] = $relationship;

				/**
				 * Broadcast that a Relationship has been created.
				 *
				 * @since 0.4
				 *
				 * @param array $relationship The created Relationship.
				 * @param string $relationship_direction The Relationship direction.
				 */
				do_action( 'cwps/acf/civicrm/relationship/created', $relationship, $relationship_direction );

			}

			// No need to go any further.
			return $relationships;

		}

		// We have existing relationships.
		$existing = [
			'ignore' => [],
			'activate' => [],
			'deactivate' => [],
		];

		// Make a copy of the target IDs.
		$unhandled_contact_ids = $target_contact_ids;

		// Let's look at them.
		foreach ( $current['values'] as $current_relationship ) {

			// Maybe deactivate when there are no target Contacts.
			if ( empty( $target_contact_ids ) ) {
				if ( $current_relationship['is_active'] == '1' ) {
					$existing['deactivate'][] = $current_relationship;
				} else {
					$existing['ignore'][] = $current_relationship;
				}
				continue;
			}

			// Flag unmatched.
			$active_match = false;
			$inactive_match = false;

			// Check against each target Contact.
			foreach ( $target_contact_ids as $key => $target_contact_id ) {

				// We need to assign the correct Source and Target.
				if ( $relationship_direction == 'ab' ) {
					$contact_id_a = $contact_id;
					$contact_id_b = $target_contact_id;
				} else {
					$contact_id_a = $target_contact_id;
					$contact_id_b = $contact_id;
				}

				// Is there a match?
				if ( $current_relationship['contact_id_a'] == $contact_id_a && $current_relationship['contact_id_b'] == $contact_id_b ) {

					// Flag as "active match" if the Relationship is active.
					if ( $current_relationship['is_active'] == '1' ) {
						$active_match = $key;
					} else {
						$inactive_match = $key;
					}

					// Either way, we can move on to the next item.
					break;

				}

			}

			// If we got an active match, add to "ignore".
			if ( $active_match !== false ) {

				/**
				 * This is a current Relationship that is active. For now, let's
				 * just leave it alone. What we may do in future is apply any
				 * settings that the Relationship has - e.g.
				 *
				 * * Permissions,
				 * * Description, etc
				 *
				 * This will require ACF Sub-Fields.
				 */

				// Add to the list of existing Relationships to be ignored.
				if (
					! array_key_exists( $current_relationship['id'], $existing['ignore'] ) &&
					! array_key_exists( $current_relationship['id'], $existing['activate'] ) &&
					! array_key_exists( $current_relationship['id'], $existing['deactivate'] )
				) {
					$existing['ignore'][ $current_relationship['id'] ] = $current_relationship;
				}

				// Remove from unhandled contacts.
				unset( $unhandled_contact_ids[ $active_match ] );

			} elseif ( $inactive_match !== false ) {

				/**
				 * This is a current Relationship that must be activated.
				 *
				 * We need to update as active because there is a correspondence
				 * with a target Contact.
				 */

				// Add to the list of existing Relationships to be activated.
				if (
					! array_key_exists( $current_relationship['id'], $existing['ignore'] ) &&
					! array_key_exists( $current_relationship['id'], $existing['activate'] ) &&
					! array_key_exists( $current_relationship['id'], $existing['deactivate'] )
				) {
					$existing['activate'][ $current_relationship['id'] ] = $current_relationship;
				}

				// Remove from unhandled contacts.
				unset( $unhandled_contact_ids[ $inactive_match ] );

			} else {

				/**
				 * This is a current Relationship that must be deactivated.
				 *
				 * We update as inactive because there is no correspondence
				 * with a target Contact.
				 */

				// Add to the list of existing Relationships to be deactivated.
				if (
					! array_key_exists( $current_relationship['id'], $existing['ignore'] ) &&
					! array_key_exists( $current_relationship['id'], $existing['activate'] ) &&
					! array_key_exists( $current_relationship['id'], $existing['deactivate'] )
				) {

					// But only if it's currently active.
					if ( $current_relationship['is_active'] == '1' ) {
						$existing['deactivate'][ $current_relationship['id'] ] = $current_relationship;
					} else {
						$existing['ignore'][ $current_relationship['id'] ] = $current_relationship;
					}

				}

			}

		}

		// First update all Relationships that must be deactivated.
		foreach ( $existing['deactivate'] as $current_relationship ) {

			// Copy minimum values.
			$params = [
				'id' => $current_relationship['id'],
				'contact_id_a' => $current_relationship['contact_id_a'],
				'contact_id_b' => $current_relationship['contact_id_b'],
				'relationship_type_id' => $current_relationship['relationship_type_id'],
			];

			// Just update active status.
			$params['is_active'] = '0';

			// Do update.
			$success = $this->relationship_edit( $params );

			// Continue on failure.
			if ( $success === false ) {
				continue;
			}

			// Add to return.
			$relationships[] = $success;

			/**
			 * The corresponding Contact's mapped Post also needs to be updated.
			 *
			 * @since 0.4
			 *
			 * @param array $current_relationship The updated Relationship.
			 * @param string $relationship_direction The Relationship direction.
			 */
			do_action( 'cwps/acf/civicrm/relationship/deactivated', $current_relationship, $relationship_direction );

		}

		// Next update all Relationships that must be activated.
		foreach ( $existing['activate'] as $current_relationship ) {

			// Copy minimum values.
			$params = [
				'id' => $current_relationship['id'],
				'contact_id_a' => $current_relationship['contact_id_a'],
				'contact_id_b' => $current_relationship['contact_id_b'],
				'relationship_type_id' => $current_relationship['relationship_type_id'],
			];

			// Just update active status.
			$params['is_active'] = '1';

			// Do update.
			$success = $this->relationship_edit( $params );

			// Continue on failure.
			if ( $success === false ) {
				continue;
			}

			// Add to return.
			$relationships[] = $success;

			/**
			 * The corresponding Contact's mapped Post also needs to be updated.
			 *
			 * @since 0.4
			 *
			 * @param array $current_relationship The updated Relationship.
			 * @param string $relationship_direction The Relationship direction.
			 */
			do_action( 'cwps/acf/civicrm/relationship/activated', $current_relationship, $relationship_direction );

		}

		// Finally create a Relationship for each unhandled target.
		if ( ! empty( $unhandled_contact_ids ) ) {
			foreach ( $unhandled_contact_ids as $target_contact_id ) {

				// We need to assign the correct Source and Target.
				if ( $relationship_direction == 'ab' ) {
					$contact_id_a = $contact_id;
					$contact_id_b = $target_contact_id;
				} else {
					$contact_id_a = $target_contact_id;
					$contact_id_b = $contact_id;
				}

				// Okay, let's do it.
				$relationship = $this->relationship_create( $contact_id_a, $contact_id_b, $relationship_type_id );
				if ( $relationship === false ) {
					continue;
				}

				// Add to return array.
				$relationships[] = $relationship;

				/**
				 * Broadcast that a Relationship has been created.
				 *
				 * @since 0.4
				 *
				 * @param array $relationship The created Relationship.
				 * @param string $relationship_direction The Relationship direction.
				 */
				do_action( 'cwps/acf/civicrm/relationship/created', $relationship, $relationship_direction );

			}
		}

		// --<
		return $relationships;

	}



	/**
	 * Activate a CiviCRM Relationship.
	 *
	 * This callback handles the updates of the ACF Field for the corresponding
	 * Contact's Post when the ACF Field on a Post is updated.
	 *
	 * @since 0.4
	 *
	 * @param array $relationship The updated Relationship.
	 * @param string $direction The Relationship direction.
	 */
	public function relationship_activate( $relationship, $direction ) {

		// Assign the correct Source and Target.
		if ( $direction == 'ab' ) {
			$contact_id = $relationship['contact_id_b'];
		} else {
			$contact_id = $relationship['contact_id_a'];
		}

		// Make sure we're activating.
		$relationship['is_active'] = '1';

		// Cast as an object.
		$relationship = (object) $relationship;

		// Do the update.
		$this->relationship_update( $contact_id, $relationship, 'edit' );

	}



	/**
	 * Deactivate a CiviCRM Relationship.
	 *
	 * This callback handles the updates of the ACF Field for the corresponding
	 * Contact's Post when the ACF Field on a Post is updated.
	 *
	 * @since 0.4
	 *
	 * @param array $relationship The updated Relationship as return.
	 * @param string $direction The Relationship direction.
	 */
	public function relationship_deactivate( $relationship, $direction ) {

		// Assign the correct Source and Target.
		if ( $direction == 'ab' ) {
			$contact_id = $relationship['contact_id_b'];
		} else {
			$contact_id = $relationship['contact_id_a'];
		}

		// Make sure we're deactivating.
		$relationship['is_active'] = '0';

		// Cast as an object.
		$relationship = (object) $relationship;

		// Do the update.
		$this->relationship_update( $contact_id, $relationship, 'edit' );

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Relationship.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id_a The numeric ID of Contact A.
	 * @param integer $contact_id_b The numeric ID of Contact B.
	 * @param integer $type_id The numeric ID of Relationship Type.
	 * @return array|bool $relationship The array of Relationship data, or false on failure.
	 */
	public function relationship_create( $contact_id_a, $contact_id_b, $type_id ) {

		// Init return.
		$relationship = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationship;
		}

		// Param to create the Relationship.
		$params = [
			'version' => 3,
			'contact_id_a' => $contact_id_a,
			'contact_id_b' => $contact_id_b,
			'relationship_type_id' => $type_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'Relationship', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $relationship;
		}

		// The result set should contain only one item.
		$relationship = array_pop( $result['values'] );

		// --<
		return $relationship;

	}



	/**
	 * Edit a CiviCRM Relationship.
	 *
	 * @since 0.4
	 *
	 * @param array $params The params to update the Relationship with.
	 * @return array|bool $relationship The array of Relationship data, or false on failure.
	 */
	public function relationship_edit( $params = [] ) {

		// Init return.
		$relationship = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationship;
		}

		// Build params to update the Relationship.
		$params['version'] = 3;

		// Bail if there's no ID.
		if ( empty( $params['id'] ) ) {
			return $relationship;
		}

		// Call the CiviCRM API.
		$result = civicrm_api( 'Relationship', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $relationship;
		}

		// The result set should contain only one item.
		$relationship = array_pop( $result['values'] );

		// --<
		return $relationship;

	}



	/**
	 * Creates or updates a CiviCRM Relationship Record.
	 *
	 * @since 0.5
	 *
	 * @param array $params The params to create/update the Relationship with.
	 * @return array|bool $relationship The array of Relationship data, or false on failure.
	 */
	public function relationship_record_update( $params = [] ) {

		// Init return.
		$relationship = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationship;
		}

		// Build params to create/update the Relationship.
		$params['version'] = 3;

		// Call the CiviCRM API.
		$result = civicrm_api( 'Relationship', 'create', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $relationship;
		}

		// The result set should contain only one item.
		$relationship = array_pop( $result['values'] );

		// --<
		return $relationship;

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Relationship.
	 *
	 * @since 0.5
	 *
	 * @param array $relationship The Relationship data.
	 * @return array|bool $relationship_data The array of Relationship data, or false on failure.
	 */
	public function create( $relationship ) {

		// Init return.
		$relationship_data = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationship_data;
		}

		// Param to create the Relationship.
		$params = [
			'version' => 3,
		] + $relationship;

		// Call the CiviCRM API.
		$result = civicrm_api( 'Relationship', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $relationship_data;
		}

		// The result set should contain only one item.
		$relationship_data = array_pop( $result['values'] );

		// --<
		return $relationship_data;

	}



	/**
	 * Update a CiviCRM Relationship with a given set of data.
	 *
	 * @since 0.4
	 *
	 * @param array $relationship The CiviCRM Relationship data.
	 * @return array|bool $relationship_data The array Relationship data from the CiviCRM API, or false on failure.
	 */
	public function update( $relationship ) {

		// Log and bail if there's no Activity ID.
		if ( empty( $relationship['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numerical ID must be present to update a Relationship.', 'civicrm-wp-profile-sync' ),
				'relationship' => $relationship,
				'backtrace' => $trace,
			], true ) );
			return $relationship_data;
		}

		// Pass through.
		return $this->create( $relationship );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for a Relationship.
	 *
	 * @since 0.5
	 *
	 * @param integer $relationship_id The numeric ID of the Relationship.
	 * @return object|bool $relationship The Relationship data object, or false if none.
	 */
	public function get_by_id( $relationship_id ) {

		// Init return.
		$relationship = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationship;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $relationship_id,
		];

		// Get Relationship details via API.
		$result = civicrm_api( 'Relationship', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $relationship;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $relationship;
		}

		// The result set should contain only one item.
		$relationship = (object) array_pop( $result['values'] );

		// --<
		return $relationship;

	}



	/**
	 * Query for Relationships given a set of arguments.
	 *
	 * @since 0.5
	 *
	 * @param array $args The arguments to query the Relationship by.
	 * @return array $relationships The array of Relationship data, or empty if none.
	 */
	public function get_by( $args ) {

		// Init return.
		$relationships = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationships;
		}

		// Construct API query.
		$params = [
			'version' => 3,
		] + $args;

		// Get Relationship details via API.
		$result = civicrm_api( 'Relationship', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $relationships;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $relationships;
		}

		// The result set is what we want.
		$relationships = $result['values'];

		// --<
		return $relationships;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Contact's Relationship has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function relationship_edited( $args ) {

		// Grab Relationship object.
		$relationship = $args['objectRef'];

		// We need to update the ACF Fields on both Posts since they may be synced.
		$this->relationship_update( $relationship->contact_id_a, $relationship, $args['op'] );
		$this->relationship_update( $relationship->contact_id_b, $relationship, $args['op'] );

	}



	/**
	 * Update the Relationship ACF Field on a Post mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array|object $relationship The Relationship data.
	 * @param string $op The type of database operation.
	 */
	public function relationship_update( $contact_id, $relationship, $op ) {

		// Grab Contact.
		$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );

		// Test if any of this Contact's Contact Types is mapped.
		$post_types = $this->civicrm->contact->is_mapped( $contact );
		if ( $post_types !== false ) {

			// Handle each Post Type in turn.
			foreach ( $post_types as $post_type ) {

				// Get the Post ID that this Contact is mapped to.
				$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );

				// Skip if not mapped.
				if ( $post_id === false ) {
					continue;
				}

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $relationship, $op );

			}

		}

		/**
		 * Broadcast that a Relationship ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param integer $contact_id The numeric ID of the Contact.
		 * @param array|object $relationship The Relationship data.
		 * @param string $op The type of database operation.
		 */
		do_action( 'cwps/acf/relationship/updated', $contact_id, $relationship, $op );

	}



	/**
	 * Update the Relationship ACF Fields on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The ACF "Post ID".
	 * @param array|object $relationship The Relationship data.
	 * @param string $op The type of database operation.
	 */
	public function fields_update( $post_id, $relationship, $op ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if we don't have any Relationship Fields.
		if ( empty( $acf_fields['relationship'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['relationship'] as $selector => $value ) {

			// Get the Relationship data.
			$relationship_data = explode( '_', $value );
			$relationship_type_id = absint( $relationship_data[0] );
			$relationship_direction = $relationship_data[1];

			// Skip if this Relationship is not mapped to the Field.
			if ( $relationship_type_id != $relationship->relationship_type_id ) {
				continue;
			}

			// Get the existing value, which should be an array.
			$existing = get_field( $selector, $post_id );

			// If it isn't one, let's make it an empty array.
			if ( ! is_array( $existing ) || empty( $existing ) ) {
				$existing = [];
			}

			// Assign the correct Target Contact ID.
			if ( $relationship_direction == 'ab' ) {
				$target_contact_id = $relationship->contact_id_b;
			} else {
				$target_contact_id = $relationship->contact_id_a;
			}

			// If deleting the Relationship.
			if ( $op == 'delete' ) {

				// Remove Contact ID if it's there.
				if ( in_array( $target_contact_id, $existing ) ) {
					$existing = array_diff( $existing, [ $target_contact_id ] );
				}

			// If creating the Relationship.
			} elseif ( $op == 'create' ) {

				// Add Contact ID if it's not there.
				if ( ! in_array( $target_contact_id, $existing ) ) {
					$existing[] = $target_contact_id;
				}

			} else {

				// If the Relationship is active.
				if ( $relationship->is_active == '1' ) {

					// Add Contact ID if it's not there.
					if ( ! in_array( $target_contact_id, $existing ) ) {
						$existing[] = $target_contact_id;
					}

				} else {

					// Remove Contact ID if it's there.
					if ( in_array( $target_contact_id, $existing ) ) {
						$existing = array_diff( $existing, [ $target_contact_id ] );
					}

				}

			}

			// Overwrite the ACF Field data.
			$this->acf_loader->acf->field->value_update( $selector, $existing, $post_id );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Relationship Fields.
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
		$result = civicrm_api( 'Relationship', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Relationship Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->relationship_fields ) ) {
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
	 * Get all Relationship Types.
	 *
	 * @since 0.4
	 *
	 * @return array $relationships The array of Relationship Types data.
	 */
	public function types_get_all() {

		// Init return.
		$relationships = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationships;
		}

		// Params to get all Relationship Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'RelationshipType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $relationships;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $relationships;
		}

		// The result set is what we're after.
		$relationships = $result['values'];

		// --<
		return $relationships;

	}



	/**
	 * Get a Relationship Type by its numeric ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $relationship_id The numeric ID of the Relationship Type.
	 * @return array $relationship The array of Relationship Type data.
	 */
	public function type_get_by_id( $relationship_id ) {

		// Init return.
		$relationship = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationship;
		}

		// Params to get the Relationship Type.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $relationship_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'RelationshipType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $relationship;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $relationship;
		}

		// The result set should contain only one item.
		$relationship = array_pop( $result['values'] );

		// --<
		return $relationship;

	}



	/**
	 * Get a Relationship Type by its "name" or "label".
	 *
	 * @since 0.4
	 *
	 * @param integer $relationship_name The name of the Relationship Type.
	 * @param integer $direction The direction of the Relationship. May be: 'ab' or 'ba'.
	 * @return array $relationship The array of Relationship Type data.
	 */
	public function type_get_by_name_or_label( $relationship_name, $direction = 'ab' ) {

		// Init return.
		$relationship = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationship;
		}

		// Params to get the Relationship Type.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'name_b_a' => $relationship_name,
			'label_b_a' => $relationship_name,
			'options' => [
				'or' => [
					[ 'name_b_a', 'label_b_a' ],
				],
			],
		];

		// Configure directionality.
		if ( $direction === 'ab' ) {
			$params['name_a_b'] = $relationship_name;
			$params['label_a_b'] = $relationship_name;
			$params['options']['or'] = [
				[ 'name_a_b', 'label_a_b' ],
			];
		}
		if ( $direction === 'ba' ) {
			$params['name_b_a'] = $relationship_name;
			$params['label_b_a'] = $relationship_name;
			$params['options']['or'] = [
				[ 'name_b_a', 'label_b_a' ],
			];
		}

		// Call the CiviCRM API.
		$result = civicrm_api( 'RelationshipType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $relationship;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $relationship;
		}

		// The result set should contain only one item.
		$relationship = array_pop( $result['values'] );

		// --<
		return $relationship;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Relationships for a CiviCRM Contact Type mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $relationships The array of possible Relationships.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$relationships = [];

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
		if ( empty( $field_group ) ) {
			return $relationships;
		}

		// Skip if this is not a Contact Field Group.
		$is_contact_field_group = $this->civicrm->contact->is_contact_field_group( $field_group );
		if ( $is_contact_field_group !== false ) {

			// Loop through the Post Types.
			foreach ( $is_contact_field_group as $post_type_name ) {

				// Get the Contact Type ID.
				$contact_type_id = $this->civicrm->contact_type->id_get_for_post_type( $post_type_name );

				// Get Contact Type hierarchy.
				$contact_types = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $contact_type_id );

				// Get relationships for the top-level Contact Type.
				$relationships_for_type = $this->relationships_get_for_contact_type( $contact_types['type'] );

				/**
				 * Filter the retrieved relationships.
				 *
				 * Used internally by the custom ACF "CiviCRM Relationship" Field.
				 *
				 * @since 0.4
				 *
				 * @param array $relationships The retrieved array of Relationship Types.
				 * @param array $contact_types The array of Contact Types.
				 * @param array $field The ACF Field data array.
				 */
				$relationships_for_type = apply_filters(
					'cwps/acf/civicrm/relationships/get_for_acf_field_for_type',
					$relationships_for_type, $contact_types, $field
				);

				// Merge with return array.
				$relationships = array_merge( $relationships, $relationships_for_type );

			}

		}

		/**
		 * Filter the Relationships for this Field.
		 *
		 * @since 0.4
		 *
		 * @param array $relationships The existing array of Relationship Types.
		 * @param array $field_group The ACF Field Group data array.
		 * @param array $field The ACF Field data array.
		 */
		$relationships = apply_filters(
			'cwps/acf/civicrm/relationships/get_for_acf_field',
			$relationships, $field_group, $field
		);

		// --<
		return $relationships;

	}



	/**
	 * Get all the Relationships for a given CiviCRM Contact.
	 *
	 * An optional Relationship Type ID can also be specified to limit the
	 * Relationships that are retrieved.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param integer $type_id The numeric ID of the Relationship Type.
	 * @param string $direction The direction of the Relationship. May be: 'ab', 'ba' or 'equal'.
	 * @return array|bool $relationships The array of Relationship data.
	 */
	public function get_directional( $contact_id, $type_id = 0, $direction = 'ab' ) {

		// Init return.
		$relationships = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationships;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0,
			],
		];

		// Configure directionality.
		if ( $direction === 'ab' ) {
			$params['contact_id_a'] = $contact_id;
		}
		if ( $direction === 'ba' ) {
			$params['contact_id_b'] = $contact_id;
		}
		if ( $direction === 'equal' ) {
			$params['contact_id_a'] = $contact_id;
			$params['contact_id_b'] = $contact_id;
			$params['options']['or'] = [
				[ 'contact_id_a', 'contact_id_b' ],
			];
		}

		// Add Relationship Type ID if present.
		if ( $type_id !== 0 && is_integer( $type_id ) ) {
			$params['relationship_type_id'] = $type_id;
		}

		// Get Relationship details via API.
		$result = civicrm_api( 'Relationship', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $relationships;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $relationships;
		}

		// The result set is what we want.
		$relationships = $result['values'];

		// --<
		return $relationships;

	}



	/**
	 * Get all the Relationships for a given CiviCRM Contact.
	 *
	 * An optional Relationship Type ID can also be specified to limit the
	 * Relationships that are retrieved.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param integer $type_id The numeric ID of the Relationship Type.
	 * @return array|bool $relationships The array of Relationship data.
	 */
	public function relationships_get_for_contact( $contact_id, $type_id = 0 ) {

		// Init return.
		$relationships = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationships;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'contact_id_a' => $contact_id,
			'contact_id_b' => $contact_id,
			'options' => [
				'limit' => 0,
				'or' => [
					[ 'contact_id_a', 'contact_id_b' ],
				],
			],
		];

		// Add Relationship Type ID if present.
		if ( $type_id !== 0 && is_integer( $type_id ) ) {
			$params['relationship_type_id'] = $type_id;
		}

		// Get Relationship details via API.
		$result = civicrm_api( 'Relationship', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $relationships;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $relationships;
		}

		// The result set is what we want.
		$relationships = $result['values'];

		// --<
		return $relationships;

	}



	/**
	 * Get all Relationship Types for a top-level Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type The top-level Contact Type.
	 * @return array $relationships The array of Relationships.
	 */
	public function relationships_get_for_contact_type( $contact_type ) {

		// Init return.
		$relationships = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $relationships;
		}

		// Params to get all Relationship Types for this top level Contact Type.
		// We need them in either direction.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_type_a' => $contact_type,
			'contact_type_b' => $contact_type,
			'options' => [
				'limit' => 0,
				'or' => [
					[ 'contact_type_a', 'contact_type_b' ],
				],
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'RelationshipType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $relationships;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $relationships;
		}

		// Extract the result set.
		$relationships = $result['values'];

		// --<
		return $relationships;

	}



	// -------------------------------------------------------------------------



	/**
	 * Return the "CiviCRM Relationship" ACF Settings Field.
	 *
	 * @since 0.4
	 *
	 * @param array $relationships The Relationships to populate the ACF Field with.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $relationships = [] ) {

		// Bail if empty.
		if ( empty( $relationships ) ) {
			return;
		}

		// Define Field.
		$field = [
			'key' => $this->acf_field_key_get(),
			'label' => __( 'CiviCRM Relationship', 'civicrm-wp-profile-sync' ),
			'name' => $this->acf_field_key_get(),
			'type' => 'select',
			'instructions' => __( 'Choose the CiviCRM Relationship that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
			'default_value' => '',
			'placeholder' => '',
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'required' => 0,
			'return_format' => 'value',
			'parent' => $this->acf_loader->acf->field_group->placeholder_group_get(),
			'choices' => $relationships,
		];

		// --<
		return $field;

	}



	/**
	 * Getter method for the "CiviCRM Relationship" key.
	 *
	 * @since 0.4
	 *
	 * @return string $acf_field_key The key of the "CiviCRM Relationship" in the ACF Field data.
	 */
	public function acf_field_key_get() {

		// --<
		return $this->acf_field_key;

	}



	/**
	 * Add any Relationship Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Get the "CiviCRM Relationship" key.
		$relationship_key = $this->acf_field_key_get();

		// Add if it has a reference to a Relationship Field.
		if ( ! empty( $field[ $relationship_key ] ) ) {
			$acf_fields['relationship'][ $field['name'] ] = $field[ $relationship_key ];
		}

		// --<
		return $acf_fields;

	}



} // Class ends.



