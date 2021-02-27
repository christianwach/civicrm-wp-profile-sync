<?php
/**
 * CiviCRM Custom Field Class.
 *
 * Handles CiviCRM Custom Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Custom Field Class.
 *
 * A class that encapsulates CiviCRM Custom Field functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Custom_Field {

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
	 * "CiviCRM Field" field value prefix in the ACF Field data.
	 *
	 * This distinguishes Contact Fields from Custom Fields.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $custom_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $custom_field_prefix = 'caicustom_';

	/**
	 * CiviCRM Custom Field data types that can have "Select", "Radio" and
	 * "CheckBox" HTML subtypes.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $data_types The data types that can have "Select", "Radio"
	 *                        and "CheckBox" HTML subtypes.
	 */
	public $data_types = [
		'String',
		'Int',
		'Float',
		'Money',
		'Country',
		'StateProvince',
	];

	/**
	 * All CiviCRM Custom Fields that are of type "Select".
	 *
	 * @since 0.4
	 * @access public
	 * @var array $data_types The Custom Fields that are of type "Select".
	 */
	public $select_types = [
		'Select',
		'Multi-Select',
		'Autocomplete-Select',
		'Select Country',
		'Multi-Select Country',
		'Select State/Province',
		'Multi-Select State/Province',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to parent.
		$this->civicrm = $parent;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

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

		// Intercept when the content of a set of CiviCRM Custom Fields has been updated.
		add_action( 'cwps/acf/mapper/civicrm/custom/edited', [ $this, 'custom_edited' ], 10 );

		// Intercept Post synced from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

		// Intercept Post synced from Activity events.
		//add_action( 'cwps/acf/post/activity/sync', [ $this, 'activity_sync_to_post' ], 10 );

		// Intercept CiviCRM Add/Edit Custom Field postSave hook.
		//add_action( 'civicrm_postSave_civicrm_custom_field', [ $this, 'custom_field_edited' ], 10 );

		// Intercept CiviCRM Add/Edit Option Value postSave hook.
		//add_action( 'civicrm_postSave_civicrm_option_value', [ $this, 'option_value_edited' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a Post has been updated from an Activity via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Activity and WordPress Post params.
	 */
	public function activity_sync_to_post( $args ) {

		// Get the Custom Fields for this CiviCRM Activity.
		$custom_fields_for_activity = $this->get_for_activity( $args['objectRef'] );

		// Bail if we don't have any Custom Fields for this Activity.
		if ( empty( $custom_fields_for_activity ) ) {
			return;
		}

		// Get the Custom Field IDs for this Activity.
		$custom_field_ids = $this->ids_get_by_activity_id( $args['objectId'], $args['post_type'] );

		// Filter the Custom Fields array.
		$filtered = [];
		foreach( $custom_field_ids AS $selector => $custom_field_id ) {
			foreach( $custom_fields_for_activity AS $key => $custom_field_data ) {
				if ( $custom_field_data['id'] == $custom_field_id ) {
					$filtered[$selector] = $custom_field_data;
					break;
				}
			}
		}

		// Extract the Custom Field mappings.
		$custom_field_mappings = wp_list_pluck( $filtered, 'id' );

		// Get the Custom Field values for this Activity.
		$custom_field_values = $this->values_get_by_activity_id( $args['objectId'], $custom_field_mappings );

		// Build a final data array.
		$final = [];
		foreach( $filtered AS $key => $custom_field ) {
			$custom_field['value'] = $custom_field_values[$custom_field['id']];
			$custom_field['type'] = $custom_field['data_type'];
			$final[$key] = $custom_field;
		}

		// Let's populate each ACF Field in turn.
		foreach( $final AS $selector => $field ) {

			// Modify values for ACF prior to update.
			$value = $this->value_get_for_acf(
				$field['value'],
				$field,
				$selector,
				$args['post_id']
			);

			// Update the ACF Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

		}

	}



	/**
	 * Get the values for a given CiviCRM Activity ID and set of Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @param int $activity_id The numeric ID of the CiviCRM Activity to query.
	 * @param array $custom_field_ids The Custom Field IDs to query.
	 * @return array $activity_data An array of Activity data.
	 */
	public function values_get_by_activity_id( $activity_id, $custom_field_ids = [] ) {

		// Init return.
		$activity_data = [];

		// Bail if we have no Custom Field IDs.
		if ( empty( $custom_field_ids ) ) {
			return $activity_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $activity_data;
		}

		// Format codes.
		$codes = [];
		foreach( $custom_field_ids AS $custom_field_id ) {
			$codes[] = 'custom_' . $custom_field_id;
		}

		// Define params to get queried Activity.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $activity_id,
			'return' => $codes,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Activity', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $activity_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $activity_data;
		}

		// Overwrite return.
		foreach( $result['values'] AS $item ) {
			foreach( $item AS $key => $value ) {
				if ( substr( $key, 0, 7 ) == 'custom_' ) {
					$index = str_replace( 'custom_', '', $key );
					$activity_data[$index] = $value;
				}
			}
		}

		// Maybe filter here?

		// --<
		return $activity_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a Post has been updated from a Contact via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM Contact and WordPress Post params.
	 */
	public function contact_sync_to_post( $args ) {

		// Get the Custom Fields for this CiviCRM Contact.
		$custom_fields_for_contact = $this->get_for_contact( $args['objectRef'] );

		// Bail if we don't have any Custom Fields for this Contact.
		if ( empty( $custom_fields_for_contact ) ) {
			return;
		}

		// Get the Custom Field IDs for this Contact.
		$custom_field_ids = $this->ids_get_by_contact_id( $args['objectId'], $args['post_type'] );

		// Filter the Custom Fields array.
		$filtered = [];
		foreach( $custom_field_ids AS $selector => $custom_field_id ) {
			foreach( $custom_fields_for_contact AS $key => $custom_field_data ) {
				if ( $custom_field_data['id'] == $custom_field_id ) {
					$filtered[$selector] = $custom_field_data;
					break;
				}
			}
		}

		// Extract the Custom Field mappings.
		$custom_field_mappings = wp_list_pluck( $filtered, 'id' );

		// Get the Custom Field values for this Contact.
		$custom_field_values = $this->values_get_by_contact_id( $args['objectId'], $custom_field_mappings );

		// Build a final data array.
		$final = [];
		foreach( $filtered AS $key => $custom_field ) {
			$custom_field['value'] = $custom_field_values[$custom_field['id']];
			$custom_field['type'] = $custom_field['data_type'];
			$final[$key] = $custom_field;
		}

		// Let's populate each ACF Field in turn.
		foreach( $final AS $selector => $field ) {

			// Modify values for ACF prior to update.
			$value = $this->value_get_for_acf(
				$field['value'],
				$field,
				$selector,
				$args['post_id']
			);

			// Update the ACF Field.
			$this->acf_loader->acf->field->value_update( $selector, $value, $args['post_id'] );

		}

	}



	/**
	 * Get the values for a given CiviCRM Contact ID and set of Custom Fields.
	 *
	 * @since 0.4
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact to query.
	 * @param array $custom_field_ids The Custom Field IDs to query.
	 * @return array $contact_data An array of Contact data.
	 */
	public function values_get_by_contact_id( $contact_id, $custom_field_ids = [] ) {

		// Init return.
		$contact_data = [];

		// Bail if we have no Custom Field IDs.
		if ( empty( $custom_field_ids ) ) {
			return $contact_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_data;
		}

		// Format codes.
		$codes = [];
		foreach( $custom_field_ids AS $custom_field_id ) {
			$codes[] = 'custom_' . $custom_field_id;
		}

		// Define params to get queried Contact.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $contact_id,
			'return' => $codes,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $contact_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_data;
		}

		// Overwrite return.
		foreach( $result['values'] AS $item ) {
			foreach( $item AS $key => $value ) {
				if ( substr( $key, 0, 7 ) == 'custom_' ) {
					$index = str_replace( 'custom_', '', $key );
					$contact_data[$index] = $value;
				}
			}
		}

		// Maybe filter here?

		// --<
		return $contact_data;

	}



	/**
	 * Get the Custom Field correspondences for a given Contact ID and Post Type.
	 *
	 * @since 0.4
	 *
	 * @param int $contact_id The numeric ID of the CiviCRM Contact.
	 * @param str $post_type The WordPress Post Type.
	 * @return array $custom_field_ids The array of found Custom Field IDs.
	 */
	public function ids_get_by_contact_id( $contact_id, $post_type ) {

		// Init return.
		$custom_field_ids = [];

		// Grab Contact.
		$contact = $this->civicrm->contact->get_by_id( $contact_id );
		if ( $contact === false ) {
			return $custom_field_ids;
		}

		// Get the Post ID that this Contact is mapped to.
		$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );
		if ( $post_id === false ) {
			return $custom_field_ids;
		}

		// Get all fields for the Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if we don't have any Custom Fields.
		if ( empty( $acf_fields['custom'] ) ) {
			return $custom_field_ids;
		}

		// Build the array of Custom Field IDs, keyed by ACF selector.
		foreach( $acf_fields['custom'] AS $selector => $field ) {
			$custom_field_ids[$selector] = $field;
		}

		// --<
		return $custom_field_ids;

	}



	// -------------------------------------------------------------------------



	/**
	 * Callback for the CiviCRM Add/Edit Custom Field postSave hook.
	 *
	 * This method listens for changes to Custom Fields and if they are mapped
	 * to ACF Fields, attempts to update the ACF Field settings accordingly.
	 *
	 * The same limitations that apply to the Option Value postSave hook also
	 * apply here.
	 *
	 * @see self::option_value_edited()
	 *
	 * @since 0.4
	 *
	 * @param object $objectRef The DAO object.
	 */
	public function custom_field_edited( $objectRef ) {

		// Bail if not Option Value save operation.
		if ( ! ( $objectRef instanceof CRM_Core_DAO_CustomField ) ) {
			return;
		}

	}



	/**
	 * Callback for the CiviCRM Add/Edit Option Value postSave hook.
	 *
	 * The idea here is to listen for Option Value changes in Option Groups that
	 * are mapped to ACF Fields and update the ACF Field settings accordingly.
	 *
	 * The problem is that ACF stores Fields as Posts of type "acf-field" where
	 * the Post Content is a serialised array of settings. This means that the
	 * only way I can think of to discover which ACF Fields are mapped is to
	 * load *all of them* and iterate through them unserialising their content
	 * and checking for the setting. This doesn't seem very, um, elegant.
	 *
	 * CiviCRM Option Groups and Custom Fields don't have a way of saving meta
	 * data, so the only alternative approach that I can see right now would be
	 * to introduce a plugin setting that holds the mapping data. This would be
	 * more easily queried but would introduce more complexity.
	 *
	 * @since 0.4
	 *
	 * @param object $objectRef The DAO object.
	 */
	public function option_value_edited( $objectRef ) {

		// Bail if not Option Value save operation.
		if ( ! ( $objectRef instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Get the Option Group to which this Option Value is attached.
		$option_group = $this->option_group_get_by_id( $objectRef->option_group_id );

		// Bail if something went wrong.
		if ( $option_group === false ) {
			return;
		}

		// TODO: Find the ACF Fields which map to this Option Group.

	}



	/**
	 * Get the CiviCRM Option Group data for a given ID.
	 *
	 * @since 0.4
	 *
	 * @param str|int $option_group_id The numeric ID of the Custom Group.
	 * @return array|bool $option_group An array of Option Group data, or false on failure.
	 */
	public function option_group_get_by_id( $option_group_id ) {

		// Init return.
		$option_group = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
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
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
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



	// -------------------------------------------------------------------------



	/**
	 * Update ACF Fields when a set of CiviCRM Custom Fields has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function custom_edited( $args ) {

		// Init Post IDs.
		$post_ids = false;

		/**
		 * Query for the Post IDs that this set of Custom Fields are mapped to.
		 *
		 * This filter sends out a request for other classes to respond with a
		 * Post ID if they detect that the set of Custom Fields maps to an
		 * Entity Type that they are responsible for.
		 *
		 * When a Contact is created, however, the synced Post has not yet been
		 * created because the "civicrm_custom" hook fires before "civicrm_post"
		 * fires and so the Post ID will always be false.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact::query_post_id()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Activity::query_post_id()
		 *
		 * @since 0.4
		 *
		 * @param bool $post_ids False, since we're asking for Post IDs.
		 * @param array $args The array of CiviCRM Custom Fields params.
		 * @return array|bool $post_ids The array of mapped Post IDs, or false if not mapped.
		 */
		$post_ids = apply_filters( 'cwps/acf/query_post_id', $post_ids, $args );

		// Process the Post IDs that we get.
		if ( $post_ids !== false ) {

			// Handle each Post ID in turn.
			foreach( $post_ids AS $post_id ) {

				// Get the ACF Fields for this Post.
				$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

				// Bail if we don't have any Custom Fields.
				if ( empty( $acf_fields['custom'] ) ) {
					continue;
				}

				// Build a reference array for Custom Fields.
				$custom_fields = [];
				foreach( $args['custom_fields'] AS $key => $field ) {
					$custom_fields[$key] = $field['custom_field_id'];
				}

				// Let's look at each ACF Field in turn.
				foreach( $acf_fields['custom'] AS $selector => $custom_field_ref ) {

					// Skip if it isn't mapped to a Custom Field.
					if ( ! in_array( $custom_field_ref, $custom_fields ) ) {
						continue;
					}

					// Get the corresponding Custom Field.
					$args_key = array_search( $custom_field_ref, $custom_fields );
					$field = $args['custom_fields'][$args_key];

					// Modify values for ACF prior to update.
					$value = $this->value_get_for_acf(
						$field['value'],
						$field,
						$selector,
						$post_id
					);

					// Update it.
					$this->acf_loader->acf->field->value_update( $selector, $value, $post_id );

				}

			}

		}

		/**
		 * Broadcast that a set of CiviCRM Custom Fields may have been updated.
		 *
		 * @since 0.4
		 *
		 * @param array|bool $post_ids The array of mapped Post IDs, or false if not mapped.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/acf/civicrm/custom_field/custom_edited', $post_ids, $args );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the value of a Custom Field, formatted for ACF.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The Custom Field value.
	 * @param array $field The Custom Field data.
	 * @param str $selector The ACF Field selector.
	 * @param int|str $post_id The ACF "Post ID".
	 * @return mixed $value The formatted field value.
	 */
	public function value_get_for_acf( $value, $field, $selector, $post_id ) {

		// Bail if empty.
		if ( empty( $value ) ) {
			return $value;
		}

		// Convert CiviCRM value to ACF value by field type.
		switch( $field['type'] ) {

			// Used by "CheckBox" and others.
			case 'String' :
			case 'Country' :
			case 'StateProvince' :

				// Convert if the value has the special CiviCRM array-like format.
				if ( is_string( $value ) ) {
					if ( false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
						$value = CRM_Utils_Array::explodePadded( $value );
					}
				}

				break;

			// Contact Reference fields may return the Contact's "sort_name".
			case 'ContactReference' :

				// Test for a numeric value.
				if ( ! is_numeric( $value ) ) {

					/*
					 * This definitely happens when Contact Reference fields are
					 * attached to Events - when retrieving the Event from the
					 * CiviCRM API, the Custom Field values are helpfully added
					 * to the returned data. However, the value in "custom_N" is
					 * the Contact's "sort_name". The numeric ID is also returned,
					 * but this is added under the key "custom_N_id" instead.
					 */

					/*
					$e = new \Exception();
					$trace = $e->getTraceAsString();
					error_log( print_r( [
						'method' => __METHOD__,
						'value' => $value,
						'field' => $field,
						'selector' => $selector,
						'post_id' => $post_id,
						//'backtrace' => $trace,
					], true ) );
					*/

				}

				break;

			// Used by "Date Select" and  "Date Time Select".
			case 'Timestamp' :

				// Get field setting.
				$acf_setting = get_field_object( $selector, $post_id );

				// Convert to ACF format.
				$datetime = DateTime::createFromFormat( 'YmdHis', $value );
				if ( $acf_setting['type'] == 'date_picker' ) {
					$value = $datetime->format( 'Ymd' );
				} elseif ( $acf_setting['type'] == 'date_time_picker' ) {
					$value = $datetime->format( 'Y-m-d H:i:s' );
				}

				break;

		}

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Custom Group data for a given ID.
	 *
	 * @since 0.4
	 *
	 * @param str|int $group_id The numeric ID of the Custom Group.
	 * @return array|bool $group An array of Custom Group data, or false on failure.
	 */
	public function group_get_by_id( $group_id ) {

		// Init return.
		$group = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $group;
		}

		// Build params to get Custom Group data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $group_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $group;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $group;
		}

		// The result set should contain only one item.
		$group = array_pop( $result['values'] );

		// --<
		return $group;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Custom Field data for a given ID.
	 *
	 * This is called on a per-Field basis. If it ends up slowing things down
	 * too much, an alternative would be to query *all* Custom Fields, stash
	 * that data set, then query it locally for each subsequent request.
	 *
	 * @since 0.4
	 *
	 * @param str|int $field_id The numeric ID of the Custom Field.
	 * @return array|bool $field An array of Custom Field data, or false on failure.
	 */
	public function get_by_id( $field_id ) {

		// Init return.
		$field = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Build params to get Custom Group data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => $field_id,
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'CustomField', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $field;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $field;
		}

		// The result set should contain only one item.
		$field = array_pop( $result['values'] );

		// --<
		return $field;

	}



	/**
	 * Get the CiviCRM Custom Field data for a given Custom Group ID.
	 *
	 * @since 0.4
	 *
	 * @param int $custom_group_id The numeric ID of the Custom Group.
	 * @return array $fields An array of Custom Field data.
	 */
	public function get_by_group_id( $custom_group_id ) {

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Build params to get Custom Group data.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'custom_group_id' => $custom_group_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api( 'CustomField', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) AND $result['is_error'] == 1 ) {
			return $fields;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $fields;
		}

		// The result set is what we want.
		$fields = $result['values'];

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the CiviCRM Custom Fields for an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_acf_field( $field ) {

		// Init return.
		$custom_fields = [];

		// Get field group for this field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no field group.
		if ( empty( $field_group ) ) {
			return $custom_fields;
		}

		/**
		 * Query for the Custom Fields that this ACF Field can be mapped to.
		 *
		 * This filter sends out a request for other classes to respond with an
		 * array of Fields if they detect that the set of Custom Fields maps to
		 * an Entity Type that they are responsible for.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact::query_custom_fields()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Activity::query_custom_fields()
		 *
		 * @since 0.4
		 *
		 * @param array $custom_fields Empty by default.
		 * @param array $field_group The array of ACF Field Group data.
		 * @param array $custom_fields The populated array of CiviCRM Custom Fields params.
		 */
		$custom_fields = apply_filters( 'cwps/acf/query_custom_fields', $custom_fields, $field_group );

		// --<
		return $custom_fields;

	}



	/**
	 * Get the Custom Fields for a given CiviCRM Contact.
	 *
	 * @since 0.4
	 *
	 * @param array $contact The CiviCRM Contact data.
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_contact( $contact ) {

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Get Contact Type hierarchy.
		$hierarchy = $this->civicrm->contact_type->hierarchy_get_for_contact( $contact );

		// Get separated array of Contact Types.
		$contact_types = $this->civicrm->contact_type->hierarchy_separate( $hierarchy );

		// Check each Contact Type in turn.
		foreach( $contact_types AS $contact_type ) {

			// Call the method for the Contact Type.
			$fields_for_contact_type = $this->get_for_contact_type( $contact_type['type'], $contact_type['subtype'] );

			// Add to return array.
			$custom_fields = $custom_fields + $fields_for_contact_type;

		}

		// --<
		return $custom_fields;

	}



	/**
	 * Get all the Custom Fields for all CiviCRM Contact Types/Subtypes.
	 *
	 * @since 0.4
	 *
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_all_contact_types() {

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'options' => [
				'limit' => 0,
			],
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0,
				]
			],
			'extends' => [
				'IN' => [ "Individual", "Organization", "Household" ],
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Override return if we get some.
		if (
			$result['is_error'] == 0 AND
			isset( $result['values'] ) AND
			count( $result['values'] ) > 0
		) {

			// We only need the results from the chained API data.
			foreach( $result['values'] as $key => $value ) {

				// Add the Custom Fields.
				foreach( $value['api.CustomField.get']['values'] as $subkey => $item ) {
					$custom_fields[$value['title']][] = $item;
				}

			}

		}

		// --<
		return $custom_fields;

	}



	/**
	 * Get the Custom Fields for a CiviCRM Contact Type/Subtype.
	 *
	 * @since 0.4
	 *
	 * @param str $type The Contact Type that the Option Group applies to.
	 * @param str $subtype The Contact Sub-type that the Option Group applies to.
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_contact_type( $type = '', $subtype = '' ) {

		// TODO: Maybe write a get_all() method and parse from there?
		// See Profile Sync, which queries *all* Contact Types :(

		// Maybe set a key for the subtype.
		$index = $subtype;
		if ( empty( $subtype ) ) {
			$index = 'none';
		}

		// Only do this once per Entity Type.
		static $pseudocache;
		if ( isset( $pseudocache[$type][$index] ) ) {
			return $pseudocache[$type][$index];
		}

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'extends' => $type,
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0, // No limit.
				],
			],
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Override return if we get some.
		if (
			$result['is_error'] == 0 AND
			isset( $result['values'] ) AND
			count( $result['values'] ) > 0
		) {

			// We only need the results from the chained API data.
			foreach( $result['values'] as $key => $value ) {

				// Skip adding if it extends a sibling subtype.
				if ( ! empty( $subtype ) AND ! empty( $value['extends_entity_column_value'] ) ) {
					if ( ! in_array( $subtype, $value['extends_entity_column_value'] ) ) {
						continue;
					}
				}

				// Add the Custom Fields.
				foreach( $value['api.CustomField.get']['values'] as $subkey => $item ) {
					$custom_fields[] = $item;
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$type][$index] ) ) {
			$pseudocache[$type][$index] = $custom_fields;
		}

		// --<
		return $custom_fields;

	}



	/**
	 * Get the Custom Fields for a CiviCRM Entity Type/Subtype.
	 *
	 * There's a discussion to be had about whether or not to include Custom Groups
	 * for a Contact Subtype or not. The code in this method can return data
	 * specific to the Subtype, but it's presumably desirable to include all
	 * Custom Groups that apply to a Contact Type.
	 *
	 * There's also a slight weakness in this code, in that the returned array is
	 * keyed by the "title" of the Custom Group. It is possible (though unlikely)
	 * that two Custom Groups may have the same "title", in which case the Custom
	 * Fields will be grouped together in the "CiviCRM Field" dropdown. The unique
	 * element is the Custom Group's "name" property, but then we would have to
	 * retrieve the "title" somewhere else - as it stands, the return array has
	 * all the data required to build the select, so I'm leaving it as is for now.
	 *
	 * @since 0.4
	 *
	 * @param str $type The Entity Type that the Option Group applies to.
	 * @param str $subtype The Entity Sub-type that the Option Group applies to.
	 * @return array $custom_fields The array of Custom Fields.
	 */
	public function get_for_entity_type( $type = '', $subtype = '' ) {

		// Maybe set a key for the subtype.
		$index = $subtype;
		if ( empty( $subtype ) ) {
			$index = 'none';
		}

		// Only do this once per Entity Type.
		static $pseudocache = [];
		if ( isset( $pseudocache[$type][$index] ) ) {
			return $pseudocache[$type][$index];
		}

		// Init array to build.
		$custom_fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $custom_fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'is_active' => 1,
			'extends' => $type,
			'api.CustomField.get' => [
				'is_active' => 1,
				'options' => [
					'limit' => 0, // No limit.
				],
			],
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'CustomGroup', 'get', $params );

		// Override return if we get some.
		if (
			$result['is_error'] == 0 AND
			isset( $result['values'] ) AND
			count( $result['values'] ) > 0
		) {

			// We only need the results from the chained API data.
			foreach( $result['values'] as $key => $value ) {

				// Skip adding if it extends a sibling subtype.
				if ( ! empty( $subtype ) AND ! empty( $value['extends_entity_column_value'] ) ) {
					if ( ! in_array( $subtype, $value['extends_entity_column_value'] ) ) {
						continue;
					}
				}

				// Add the Custom Fields.
				foreach( $value['api.CustomField.get']['values'] as $subkey => $item ) {
					$custom_fields[$value['title']][] = $item;
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$type][$index] ) ) {
			$pseudocache[$type][$index] = $custom_fields;
		}

		// --<
		return $custom_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the mapped Custom Field ID if present.
	 *
	 * @since 0.4
	 *
	 * @param array $field The existing field data array.
	 * @return int|bool $custom_field_id The numeric ID of the Custom Field, or false if none.
	 */
	public function custom_field_id_get( $field ) {

		// Init return.
		$custom_field_id = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Get the mapped Custom Field ID if present.
		if ( isset( $field[$acf_field_key] ) ) {
			if ( false !== strpos( $field[$acf_field_key], $this->custom_field_prefix ) ) {
				$custom_field_id = absint( str_replace( $this->custom_field_prefix, '', $field[$acf_field_key] ) );
			}
		}

		/**
		 * Filter the Custom Field ID.
		 *
		 * @since 0.4
		 *
		 * @param int $custom_field_id The existing Custom Field ID.
		 * @param array $field The array of ACF Field data.
		 * @return int $custom_field_id The modified Custom Field ID.
		 */
		$custom_field_id = apply_filters( 'cwps/acf/contact/custom_field/id_get', $custom_field_id, $field );

		// --<
		return $custom_field_id;

	}



	/**
	 * Return the "CiviCRM Field" ACF Settings Field when there is only Custom Field data.
	 *
	 * @since 0.4
	 *
	 * @param array $custom_fields The Custom Fields to populate the ACF Field with.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $custom_fields = [] ) {

		// Build choices array for dropdown.
		$choices = [];

		// Build Custom Field choices array for dropdown.
		$custom_field_prefix = $this->civicrm->custom_field_prefix();
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			$custom_fields_label = esc_attr( $custom_group_name );
			foreach( $custom_group AS $custom_field ) {
				$choices[$custom_fields_label][$custom_field_prefix . $custom_field['id']] = $custom_field['label'];
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.4
		 *
		 * @param array $choices The existing select options array.
		 * @param array $choices The modified select options array.
		 */
		$choices = apply_filters( 'cwps/acf/contact/custom_field/choices', $choices );

		// Define field.
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



	// -------------------------------------------------------------------------



	/**
	 * Get the choices for the Setting of a "Select" Field.
	 *
	 * @since 0.4
	 *
	 * @param str $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the field.
	 */
	public function select_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not a data type that can have a "Select".
		if ( ! in_array( $field_data['data_type'], $this->data_types ) ) {
			return $choices;
		}

		// Bail if it's not a type of "Select".
		if ( ! in_array( $field_data['html_type'], $this->select_types ) ) {
			return $choices;
		}

		// Populate with child options where possible.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// "Country" selects require special handling.
		$country_selects = [ 'Select Country', 'Multi-Select Country' ];
		if ( in_array( $field_data['html_type'], $country_selects ) ) {
			$choices = CRM_Core_PseudoConstant::country();
		}

		// "State/Province" selects also require special handling.
		$state_selects = [ 'Select State/Province', 'Multi-Select State/Province' ];
		if ( in_array( $field_data['html_type'], $state_selects ) ) {
			$choices = CRM_Core_PseudoConstant::stateProvince();
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Select" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function select_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// ACF "Multi-Select".
		if ( $field['multiple'] == 1 ) {

			// Filter fields to include only Multi-Select types.
			$select_types = [ 'Multi-Select', 'Multi-Select Country', 'Multi-Select State/Province' ];

		// ACF "Autocomplete-Select". Sort of.
		} elseif ( $field['ui'] == 1 AND $field['ajax'] == 1 ) {

			// Filter fields to include only Autocomplete-Select.
			$select_types = [ 'Autocomplete-Select' ];

		// Otherwise, fall back.
		} else {

			// Filter fields to include only "Select" types.
			$select_types = [ 'Select', 'Select Country', 'Select State/Province' ];

		}

		// Filter fields to include only those which are compatible.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) AND  in_array( $custom_field['html_type'], $select_types ) ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Get the choices for the Setting of a "Radio" Field.
	 *
	 * @since 0.4
	 *
	 * @param str $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the field.
	 */
	public function radio_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not a data type that can have a "Radio" sub-type.
		if ( ! in_array( $field_data['data_type'], $this->data_types ) ) {
			return $choices;
		}

		// Bail if it's not "Radio".
		if ( $field_data['html_type'] !== 'Radio' ) {
			return $choices;
		}

		// Get options.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Radio" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function radio_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only "Radio" HTML types.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'Radio' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Get the choices for the Setting of a "Checkbox" Field.
	 *
	 * @since 0.4
	 *
	 * @param str $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the field.
	 */
	public function checkbox_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->acf_loader->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $choices;
		}

		// Bail if it's not "String".
		if ( $field_data['data_type'] !== 'String' ) {
			return $choices;
		}

		// Bail if it's not "Select".
		if ( $field_data['html_type'] !== 'CheckBox' ) {
			return $choices;
		}

		// Get options.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( absint( $field_data['option_group_id'] ) );
		}

		// --<
		return $choices;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "CheckBox" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function checkbox_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only Boolean/Radio.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND $custom_field['data_type'] == 'String' ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'CheckBox' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Get the Settings of a "Date" Field as required by a Custom Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The existing field data array.
	 * @param str $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $field The modified field data array.
	 */
	public function date_settings_get( $field, $custom_field_id ) {

		// Get Custom Field data.
		$field_data = $this->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $field;
		}

		// Bail if it's not Date.
		if ( $field_data['data_type'] !== 'Date' ) {
			return $field;
		}

		// Bail if it's not "Select Date".
		if ( $field_data['html_type'] !== 'Select Date' ) {
			return $field;
		}

		// Bail if the "Time Format" is set.
		if ( isset( $field_data['time_format'] ) ) {
			return $field;
		}

		// Get the mappings.
		$mappings = $this->acf_loader->mapper->date_mappings;

		// Get the ACF format.
		$acf_format = $mappings[$field_data['date_format']];

		// Set the date "format" attributes.
		$field['display_format'] = $acf_format;
		$field['return_format'] = $acf_format;

		// --<
		return $field;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Date" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function date_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only Date/Select Date.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND $custom_field['data_type'] == 'Date' ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'Select Date' ) {
						if ( ! isset( $custom_field['time_format'] ) OR $custom_field['time_format'] == '0' ) {
							$filtered_fields[$custom_group_name][] = $custom_field;
						}
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Get the Settings of a "Date Time" Field as required by a Custom Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The existing field data array.
	 * @param str $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $field The modified field data array.
	 */
	public function date_time_settings_get( $field, $custom_field_id ) {

		// Get Custom Field data.
		$field_data = $this->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $field;
		}

		// Bail if it's not Date.
		if ( $field_data['data_type'] !== 'Date' ) {
			return $field;
		}

		// Bail if it's not "Select Date".
		if ( $field_data['html_type'] !== 'Select Date' ) {
			return $field;
		}

		// Bail if the "Time Format" is not set.
		if ( ! isset( $field_data['time_format'] ) OR $field_data['time_format'] == '0' ) {
			return $field;
		}

		// Get the date mappings.
		$date_mappings = $this->acf_loader->mapper->date_mappings;

		// Get the ACF format.
		$acf_format = $date_mappings[$field_data['date_format']];

		// Get the time mappings.
		$time_mappings = $this->acf_loader->mapper->time_mappings;

		// Append to the ACF format.
		if ( ! empty( $time_mappings[$field_data['time_format']] ) ) {
			$acf_format .= ' ' . $time_mappings[$field_data['time_format']];
		}

		// Set the date "format" attributes.
		$field['display_format'] = $acf_format;
		$field['return_format'] = $acf_format;

		// --<
		return $field;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Date Time" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function date_time_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only Date/Select Date.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND $custom_field['data_type'] == 'Date' ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'Select Date' ) {
						if ( ! empty( $custom_field['time_format'] ) AND $custom_field['time_format'] != '0' ) {
							$filtered_fields[$custom_group_name][] = $custom_field;
						}
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Get the Settings of a "Text" Field as required by a Custom Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The existing field data array.
	 * @param str $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $field The modified field data array.
	 */
	public function text_settings_get( $field, $custom_field_id ) {

		// Get Custom Field data.
		$field_data = $this->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( $field_data === false ) {
			return $field;
		}

		// Bail if it's not Alphanumeric.
		if ( $field_data['data_type'] !== 'String' ) {
			return $field;
		}

		// Bail if it's not Text.
		if ( $field_data['html_type'] !== 'Text' ) {
			return $field;
		}

		// Bail if there's no "text_length" attribute.
		if ( ! array_key_exists( 'text_length', $field_data ) ) {
			return $field;
		}

		// Set the "maxlength" attribute.
		$field['maxlength'] = $field_data['text_length'];

		// --<
		return $field;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Text" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function text_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only those of HTML type "Text".
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND in_array( $custom_field['data_type'], $this->data_types ) ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'Text' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Wysiwyg" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function wysiwyg_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only Memo/RichTextEditor.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND $custom_field['data_type'] == 'Memo' ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'RichTextEditor' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "Textarea" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function textarea_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only Memo/TextArea.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND $custom_field['data_type'] == 'Memo' ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'TextArea' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "True/False" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function true_false_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only Boolean/Radio.
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND $custom_field['data_type'] == 'Boolean' ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'Radio' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



	/**
	 * Filter the Custom Fields for the Setting of a "URL" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $custom_fields The array of Custom Fields.
	 * @return array $filtered_fields The filtered Custom Fields.
	 */
	public function url_settings_filter( $field, $custom_fields ) {

		// Init return.
		$filtered_fields = [];

		// Filter fields to include only "Link".
		foreach( $custom_fields AS $custom_group_name => $custom_group ) {
			foreach( $custom_group AS $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) AND $custom_field['data_type'] == 'Link' ) {
					if ( ! empty( $custom_field['html_type'] ) AND $custom_field['html_type'] == 'Link' ) {
						$filtered_fields[$custom_group_name][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}



} // Class ends.



