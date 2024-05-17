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
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Contact Fields from Custom Fields.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $custom_field_prefix = 'caicustom_';

	/**
	 * CiviCRM Custom Field data types that can have "Select", "Radio" and
	 * "CheckBox" HTML subtypes.
	 *
	 * @since 0.4
	 * @access public
	 * @var array
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
	 * @var array
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

		// Intercept Post-Contact sync event.
		add_action( 'cwps/acf/post/contact/sync', [ $this, 'contact_sync_to_post' ], 10 );

		/*
		// Intercept Post synced from Activity events.
		//add_action( 'cwps/acf/post/activity/sync', [ $this, 'activity_sync_to_post' ], 10 );

		// Intercept Post synced from Participant events.
		//add_action( 'cwps/acf/post/participant/sync', [ $this, 'participant_sync_to_post' ], 10 );

		// Intercept CiviCRM Add/Edit Custom Field postSave hook.
		//add_action( 'civicrm_postSave_civicrm_custom_field', [ $this, 'custom_field_edited' ], 10 );

		// Intercept CiviCRM Add/Edit Option Value postSave hook.
		//add_action( 'civicrm_postSave_civicrm_option_value', [ $this, 'option_value_edited' ], 10 );
		*/

		// Listen for queries from our Entity classes.
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'select_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'radio_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'checkbox_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'date_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'date_time_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'text_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'wysiwyg_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'textarea_settings_filter' ], 10, 3 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'true_false_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'url_settings_filter' ], 10, 3 );
		add_filter( 'cwps/acf/query_settings/custom_fields_filter', [ $this, 'file_settings_filter' ], 10, 3 );

		// Listen for queries from our ACF Field Group class.
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'select_settings_modify' ], 10, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'radio_settings_modify' ], 10, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'checkbox_settings_modify' ], 10, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'date_picker_settings_modify' ], 10, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'date_time_picker_settings_modify' ], 10, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'text_settings_modify' ], 10, 2 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'file_settings_modify' ], 10, 2 );

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

		// Intercept when the content of a set of CiviCRM Custom Fields is updated.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_action( 'cwps/acf/mapper/civicrm/custom/edit/pre', [ $this, 'custom_pre_edit' ], 10 );
		add_action( 'cwps/acf/mapper/civicrm/custom/edited', [ $this, 'custom_edited' ], 10 );

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
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// remove_action( 'cwps/acf/mapper/civicrm/custom/edit/pre', [ $this, 'custom_pre_edit' ], 10 );
		remove_action( 'cwps/acf/mapper/civicrm/custom/edited', [ $this, 'custom_edited' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

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
		$custom_fields_for_contact = $this->plugin->civicrm->custom_field->get_for_contact( $args['objectRef'] );

		// Bail if we don't have any Custom Fields for this Contact.
		if ( empty( $custom_fields_for_contact ) ) {
			return;
		}

		// Get the Custom Field IDs for this Contact.
		$custom_field_ids = $this->ids_get_by_contact_id( $args['objectId'], $args['post_type'] );

		// Filter the Custom Fields array.
		$filtered = [];
		foreach ( $custom_field_ids as $selector => $custom_field_id ) {
			foreach ( $custom_fields_for_contact as $label => $custom_fields_data ) {
				foreach ( $custom_fields_data as $key => $custom_field_data ) {
					if ( $custom_field_data['id'] == $custom_field_id ) {
						$filtered[ $selector ] = $custom_field_data;
						break;
					}
				}
			}
		}

		// Extract the Custom Field mappings.
		$custom_field_mappings = wp_list_pluck( $filtered, 'id' );

		// Get the Custom Field values for this Contact.
		$custom_field_values = $this->plugin->civicrm->custom_field->values_get_by_contact_id( $args['objectId'], $custom_field_mappings );

		// Build a final data array.
		$final = [];
		foreach ( $filtered as $key => $custom_field ) {
			$custom_field['value'] = $custom_field_values[ (int) $custom_field['id'] ];
			$custom_field['type']  = $custom_field['data_type'];
			$final[ $key ]         = $custom_field;
		}

		// Let's populate each ACF Field in turn.
		foreach ( $final as $selector => $field ) {

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
	 * Get the Custom Field correspondences for a given Contact ID and Post Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @param string  $post_type The WordPress Post Type.
	 * @return array $custom_field_ids The array of found Custom Field IDs.
	 */
	public function ids_get_by_contact_id( $contact_id, $post_type ) {

		// Init return.
		$custom_field_ids = [];

		// Grab Contact.
		$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );
		if ( false === $contact ) {
			return $custom_field_ids;
		}

		// Get the Post ID that this Contact is mapped to.
		$post_id = $this->civicrm->contact->is_mapped_to_post( $contact, $post_type );
		if ( false === $post_id ) {
			return $custom_field_ids;
		}

		// Get all Fields for the Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if we don't have any Custom Fields.
		if ( empty( $acf_fields['custom'] ) ) {
			return $custom_field_ids;
		}

		// Build the array of Custom Field IDs, keyed by ACF selector.
		foreach ( $acf_fields['custom'] as $selector => $field ) {
			$custom_field_ids[ $selector ] = $field;
		}

		// --<
		return $custom_field_ids;

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
		foreach ( $custom_field_ids as $selector => $custom_field_id ) {
			foreach ( $custom_fields_for_activity as $key => $custom_field_data ) {
				if ( $custom_field_data['id'] == $custom_field_id ) {
					$filtered[ $selector ] = $custom_field_data;
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
		foreach ( $filtered as $key => $custom_field ) {
			$custom_field['value'] = $custom_field_values[ (int) $custom_field['id'] ];
			$custom_field['type']  = $custom_field['data_type'];
			$final[ $key ]         = $custom_field;
		}

		// Let's populate each ACF Field in turn.
		foreach ( $final as $selector => $field ) {

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
	 * @param integer $activity_id The numeric ID of the CiviCRM Activity to query.
	 * @param array   $custom_field_ids The Custom Field IDs to query.
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
		foreach ( $custom_field_ids as $custom_field_id ) {
			$codes[] = 'custom_' . $custom_field_id;
		}

		// Define params to get queried Activity.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => $activity_id,
			'return'     => $codes,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Activity', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $activity_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $activity_data;
		}

		// Overwrite return.
		foreach ( $result['values'] as $item ) {
			foreach ( $item as $key => $value ) {
				if ( substr( $key, 0, 7 ) == 'custom_' ) {
					$index                   = (int) str_replace( 'custom_', '', $key );
					$activity_data[ $index ] = $value;
				}
			}
		}

		// Maybe filter here?

		// --<
		return $activity_data;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the values for a given CiviCRM Case ID and set of Custom Fields.
	 *
	 * @since 0.5.2
	 *
	 * @param integer $case_id The numeric ID of the CiviCRM Case to query.
	 * @param array   $custom_field_ids The Custom Field IDs to query.
	 * @return array $case_data An array of Case data.
	 */
	public function values_get_by_case_id( $case_id, $custom_field_ids = [] ) {

		// Init return.
		$case_data = [];

		// Bail if we have no Custom Field IDs.
		if ( empty( $custom_field_ids ) ) {
			return $case_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $case_data;
		}

		// Format codes.
		$codes = [];
		foreach ( $custom_field_ids as $custom_field_id ) {
			$codes[] = 'custom_' . $custom_field_id;
		}

		// Define params to get queried Case.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => $case_id,
			'return'     => $codes,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Case', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $case_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $case_data;
		}

		// Overwrite return.
		foreach ( $result['values'] as $item ) {
			foreach ( $item as $key => $value ) {
				if ( substr( $key, 0, 7 ) == 'custom_' ) {
					$index               = (int) str_replace( 'custom_', '', $key );
					$case_data[ $index ] = $value;
				}
			}
		}

		// Maybe filter here?

		// --<
		return $case_data;

	}

	// -------------------------------------------------------------------------

	/**
	 * Intercept when a Post has been updated from a Participant via the Mapper.
	 *
	 * Sync any associated ACF Fields mapped to Custom Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM Participant and WordPress Post params.
	 */
	public function participant_sync_to_post( $args ) {

		// Get the Custom Fields for this CiviCRM Participant.
		$custom_fields_for_participant = $this->get_for_activity( $args['objectRef'] );

		// Bail if we don't have any Custom Fields for this Participant.
		if ( empty( $custom_fields_for_participant ) ) {
			return;
		}

		// Get the Custom Field IDs for this Participant.
		$custom_field_ids = $this->ids_get_by_participant_id( $args['objectId'], $args['post_type'] );

		// Filter the Custom Fields array.
		$filtered = [];
		foreach ( $custom_field_ids as $selector => $custom_field_id ) {
			foreach ( $custom_fields_for_participant as $key => $custom_field_data ) {
				if ( $custom_field_data['id'] == $custom_field_id ) {
					$filtered[ $selector ] = $custom_field_data;
					break;
				}
			}
		}

		// Extract the Custom Field mappings.
		$custom_field_mappings = wp_list_pluck( $filtered, 'id' );

		// Get the Custom Field values for this Participant.
		$custom_field_values = $this->values_get_by_participant_id( $args['objectId'], $custom_field_mappings );

		// Build a final data array.
		$final = [];
		foreach ( $filtered as $key => $custom_field ) {
			$custom_field['value'] = $custom_field_values[ (int) $custom_field['id'] ];
			$custom_field['type']  = $custom_field['data_type'];
			$final[ $key ]         = $custom_field;
		}

		// Let's populate each ACF Field in turn.
		foreach ( $final as $selector => $field ) {

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
	 * Get the values for a given CiviCRM Participant ID and set of Custom Fields.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_id The numeric ID of the CiviCRM Participant to query.
	 * @param array   $custom_field_ids The Custom Field IDs to query.
	 * @return array $participant_data An array of Participant data.
	 */
	public function values_get_by_participant_id( $participant_id, $custom_field_ids = [] ) {

		// Init return.
		$participant_data = [];

		// Bail if we have no Custom Field IDs.
		if ( empty( $custom_field_ids ) ) {
			return $participant_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $participant_data;
		}

		// Format codes.
		$codes = [];
		foreach ( $custom_field_ids as $custom_field_id ) {
			$codes[] = 'custom_' . $custom_field_id;
		}

		// Define params to get queried Participant.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => $participant_id,
			'return'     => $codes,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Participant', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $participant_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $participant_data;
		}

		// Overwrite return.
		foreach ( $result['values'] as $item ) {
			foreach ( $item as $key => $value ) {
				if ( substr( $key, 0, 7 ) == 'custom_' ) {
					$index                      = (int) str_replace( 'custom_', '', $key );
					$participant_data[ $index ] = $value;
				}
			}
		}

		// Maybe filter here?

		// --<
		return $participant_data;

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
	 * @param object $object_ref The DAO object.
	 */
	public function custom_field_edited( $object_ref ) {

		// Bail if not Option Value save operation.
		if ( ! ( $object_ref instanceof CRM_Core_DAO_CustomField ) ) {
			return;
		}

	}

	/**
	 * Callback for the CiviCRM Add/Edit Option Value postSave hook.
	 *
	 * Note: not implemented yet.
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
	 * @param object $object_ref The DAO object.
	 */
	public function option_value_edited( $object_ref ) {

		// Bail if not Option Value save operation.
		if ( ! ( $object_ref instanceof CRM_Core_DAO_OptionValue ) ) {
			return;
		}

		// Get the Option Group to which this Option Value is attached.
		$option_group = $this->plugin->civicrm->option_group_get_by_id( $object_ref->option_group_id );

		// Bail if something went wrong.
		if ( false === $option_group ) {
			return;
		}

		// TODO: Find the ACF Fields which map to this Option Group.

	}

	// -------------------------------------------------------------------------

	/**
	 * Called when a set of CiviCRM Custom Fields is about to be updated.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function custom_pre_edit( $args ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'args' => $args,
			//'query' => $query,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

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
		 * NOTE: This filter relies on the structure of the data returned by the
		 * "civicrm_custom" hook, which contains the "entity_table" entry for
		 * each Custom Field. This does not help us to discover the set of ACF
		 * "Post IDs" for an ACF Field.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact::query_post_id()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Activity::query_post_id()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT::query_post_id()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Participant::query_post_id()
		 * @see CiviCRM_Profile_Sync_ACF_User::query_post_id()
		 *
		 * Also used by CiviCRM Event Organiser:
		 *
		 * @see CiviCRM_WP_Event_Organiser_CWPS::query_post_id()
		 *
		 * @since 0.4
		 *
		 * @param bool $post_ids False, since we're asking for Post IDs.
		 * @param array $args The array of CiviCRM Custom Fields params.
		 */
		$post_ids = apply_filters( 'cwps/acf/query_post_id', $post_ids, $args );

		// Process the Post IDs that we get.
		if ( false !== $post_ids ) {

			// Handle each Post ID in turn.
			foreach ( $post_ids as $post_id ) {

				// Get the ACF Fields for this Post.
				$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

				// Bail if we don't have any Custom Fields.
				if ( empty( $acf_fields['custom'] ) ) {
					continue;
				}

				// Build a reference array for Custom Fields.
				$custom_fields = [];
				foreach ( $args['custom_fields'] as $key => $field ) {
					$custom_fields[ $key ] = (int) $field['custom_field_id'];
				}

				// Let's look at each ACF Field in turn.
				foreach ( $acf_fields['custom'] as $selector => $custom_field_ref ) {

					// Skip if it isn't mapped to a Custom Field.
					if ( ! in_array( (int) $custom_field_ref, $custom_fields, true ) ) {
						continue;
					}

					// Get the corresponding Custom Field.
					$args_key = array_search( $custom_field_ref, $custom_fields, true );
					$field    = $args['custom_fields'][ $args_key ];

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
	 * @param mixed          $value The Custom Field value.
	 * @param array          $field The Custom Field data.
	 * @param string         $selector The ACF Field selector.
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return mixed $value The formatted Field value.
	 */
	public function value_get_for_acf( $value, $field, $selector, $post_id ) {

		// Bail if empty.
		if ( empty( $value ) ) {
			return $value;
		}

		// Convert CiviCRM value to ACF value by Field Type.
		switch ( $field['type'] ) {

			// Used by "CheckBox" and others.
			case 'String':
			case 'Country':
			case 'StateProvince':
				// Convert if the value has the special CiviCRM array-like format.
				if ( is_string( $value ) ) {
					if ( false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
						$value = CRM_Utils_Array::explodePadded( $value );
					}
				}
				break;

			// Contact Reference Fields may return the Contact's "sort_name".
			case 'ContactReference':
				// Test for a numeric value.
				// phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
				if ( ! is_numeric( $value ) ) {

					/*
					 * This definitely happens when Contact Reference Fields are
					 * attached to Events - when retrieving the Event from the
					 * CiviCRM API, the Custom Field values are helpfully added
					 * to the returned data. However, the value in "custom_N" is
					 * the Contact's "sort_name". The numeric ID is also returned,
					 * but this is added under the key "custom_N_id" instead.
					 */

					/*
					$e = new \Exception();
					$trace = $e->getTraceAsString();
					$log = [
						'method' => __METHOD__,
						'value' => $value,
						'field' => $field,
						'selector' => $selector,
						'post_id' => $post_id,
						'backtrace' => $trace,
					];
					$this->plugin->log_error( $log );
					*/

				}
				break;

			// Used by "Date Select" and "Date Time Select".
			case 'Timestamp':
				// Get Field setting.
				$acf_setting = get_field_object( $selector, $post_id );

				// Convert to ACF format.
				$datetime = DateTime::createFromFormat( 'YmdHis', $value );
				if ( 'date_picker' === $acf_setting['type'] ) {
					$value = $datetime->format( 'Ymd' );
				} elseif ( 'date_time_picker' === $acf_setting['type'] ) {
					$value = $datetime->format( 'Y-m-d H:i:s' );
				}
				break;

			// Handle CiviCRM "File" Custom Fields.
			case 'File':
				// Delegate to method, expect an Attachment ID.
				$value = $this->civicrm->attachment->value_get_for_acf( $value, $field, $selector, $post_id );
				break;

		}

		// --<
		return $value;

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

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );

		// Bail if there's no Field Group.
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
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Participant::query_custom_fields()
		 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT::query_custom_fields()
		 * @see CiviCRM_Profile_Sync_ACF_User::query_custom_fields()
		 *
		 * Also used by CiviCRM Event Organiser:
		 *
		 * @see CiviCRM_WP_Event_Organiser_CWPS::query_custom_fields()
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

	// -------------------------------------------------------------------------

	/**
	 * Get the mapped Custom Field ID if present.
	 *
	 * @since 0.4
	 *
	 * @param array $field The existing Field data array.
	 * @return integer|bool $custom_field_id The numeric ID of the Custom Field, or false if none.
	 */
	public function custom_field_id_get( $field ) {

		// Init return.
		$custom_field_id = false;

		// Get the ACF CiviCRM Field key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Get the mapped Custom Field ID if present.
		if ( isset( $field[ $acf_field_key ] ) ) {
			if ( false !== strpos( $field[ $acf_field_key ], $this->custom_field_prefix ) ) {
				$custom_field_id = (int) str_replace( $this->custom_field_prefix, '', $field[ $acf_field_key ] );
			}
		}

		/**
		 * Filter the Custom Field ID.
		 *
		 * @since 0.4
		 *
		 * @param integer $custom_field_id The existing Custom Field ID.
		 * @param array $field The array of ACF Field data.
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
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			$custom_fields_label = esc_attr( $custom_group_name );
			foreach ( $custom_group as $custom_field ) {
				$choices[ $custom_fields_label ][ $custom_field_prefix . $custom_field['id'] ] = $custom_field['label'];
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.4
		 * @since 0.5 Filter name changed.
		 *
		 * @param array $choices The existing select options array.
		 * @param array $choices The modified select options array.
		 */
		$choices = apply_filters( 'cwps/acf/custom_field/choices', $choices );

		// Define Field.
		$field = [
			'key'           => $this->civicrm->acf_field_key_get(),
			'label'         => __( 'CiviCRM Field', 'civicrm-wp-profile-sync' ),
			'name'          => $this->civicrm->acf_field_key_get(),
			'type'          => 'select',
			'instructions'  => __( 'Choose the CiviCRM Field that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
			'default_value' => '',
			'placeholder'   => '',
			'allow_null'    => 1,
			'multiple'      => 0,
			'ui'            => 0,
			'required'      => 0,
			'return_format' => 'value',
			'parent'        => $this->acf_loader->acf->field_group->placeholder_group_get(),
			'choices'       => $choices,
		];

		// --<
		return $field;

	}

	// -------------------------------------------------------------------------

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

		// Get the mapped Custom Field ID if present.
		$custom_field_id = $this->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return $field;
		}

		// Get keyed array of settings.
		$field['choices'] = $this->select_choices_get( $custom_field_id );

		// --<
		return $field;

	}

	/**
	 * Get the choices for the Setting of a "Select" Field.
	 *
	 * @since 0.4
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the Field.
	 */
	public function select_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( false === $field_data ) {
			return $choices;
		}

		// Bail if it's not a data type that can have a "Select".
		if ( ! in_array( $field_data['data_type'], $this->data_types, true ) ) {
			return $choices;
		}

		// Bail if it's not a type of "Select".
		if ( ! in_array( $field_data['html_type'], $this->select_types, true ) ) {
			return $choices;
		}

		// Populate with child options where possible.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( (int) $field_data['option_group_id'] );
		}

		// "Country" selects require special handling.
		$country_selects = [ 'Select Country', 'Multi-Select Country' ];
		if ( in_array( $field_data['html_type'], $country_selects, true ) ) {
			$choices = CRM_Core_PseudoConstant::country();
		}

		// "State/Province" selects also require special handling.
		$state_selects = [ 'Select State/Province', 'Multi-Select State/Province' ];
		if ( in_array( $field_data['html_type'], $state_selects, true ) ) {
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
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function select_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'select' !== $field['type'] ) {
			return $filtered_fields;
		}

		if ( 1 === (int) $field['multiple'] ) {

			// Filter Fields to include only Multi-Select types.
			$select_types = [ 'Multi-Select', 'Multi-Select Country', 'Multi-Select State/Province' ];

		} elseif ( 1 === (int) $field['ui'] && 1 === (int) $field['ajax'] ) {

			// Filter Fields to include only Autocomplete-Select.
			$select_types = [ 'Autocomplete-Select' ];

		} else {

			// Otherwise filter Fields to include only "Select" types.
			$select_types = [ 'Select', 'Select Country', 'Select State/Province' ];

		}

		// Filter Fields to include only those which are compatible.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types, true ) ) {
					if ( ! empty( $custom_field['html_type'] ) && in_array( $custom_field['html_type'], $select_types, true ) ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}

	/**
	 * Modify the Settings of an ACF "Radio" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function radio_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'radio' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Custom Field ID if present.
		$custom_field_id = $this->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return $field;
		}

		// Get keyed array of settings.
		$field['choices'] = $this->radio_choices_get( $custom_field_id );

		// --<
		return $field;

	}

	/**
	 * Get the choices for the Setting of a "Radio" Field.
	 *
	 * @since 0.4
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the Field.
	 */
	public function radio_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( false === $field_data ) {
			return $choices;
		}

		// Bail if it's not a data type that can have a "Radio" sub-type.
		if ( ! in_array( $field_data['data_type'], $this->data_types, true ) ) {
			return $choices;
		}

		// Bail if it's not "Radio".
		if ( 'Radio' !== $field_data['html_type'] ) {
			return $choices;
		}

		// Get options.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( (int) $field_data['option_group_id'] );
		}

		// --<
		return $choices;

	}

	/**
	 * Filter the Custom Fields for the Setting of a "Radio" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function radio_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'radio' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only "Radio" HTML types.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types, true ) ) {
					if ( ! empty( $custom_field['html_type'] ) && 'Radio' === $custom_field['html_type'] ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}

	/**
	 * Modify the Settings of an ACF "Checkbox" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function checkbox_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Custom Field ID if present.
		$custom_field_id = $this->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return $field;
		}

		// Get keyed array of settings.
		$field['choices'] = $this->checkbox_choices_get( $custom_field_id );

		// --<
		return $field;

	}

	/**
	 * Get the choices for the Setting of a "Checkbox" Field.
	 *
	 * @since 0.4
	 *
	 * @param string $custom_field_id The numeric ID of the CiviCRM Custom Field.
	 * @return array $choices The choices for the Field.
	 */
	public function checkbox_choices_get( $custom_field_id ) {

		// Init return.
		$choices = [];

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( false === $field_data ) {
			return $choices;
		}

		// Bail if it's not "String".
		if ( 'String' !== $field_data['data_type'] ) {
			return $choices;
		}

		// Bail if it's not "Select".
		if ( 'CheckBox' !== $field_data['html_type'] ) {
			return $choices;
		}

		// Get options.
		if ( ! empty( $field_data['option_group_id'] ) ) {
			$choices = CRM_Core_OptionGroup::valuesByID( (int) $field_data['option_group_id'] );
		}

		// --<
		return $choices;

	}

	/**
	 * Filter the Custom Fields for the Setting of a "CheckBox" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function checkbox_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Boolean/Radio.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && 'String' === $custom_field['data_type'] ) {
					if ( ! empty( $custom_field['html_type'] ) && 'CheckBox' === $custom_field['html_type'] ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}

	/**
	 * Modify the Settings of an ACF "Date Picker" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function date_picker_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'date_picker' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Custom Field ID if present.
		$custom_field_id = $this->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return $field;
		}

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( false === $field_data ) {
			return $field;
		}

		// Bail if it's not Date.
		if ( 'Date' !== $field_data['data_type'] ) {
			return $field;
		}

		// Bail if it's not "Select Date".
		if ( 'Select Date' !== $field_data['html_type'] ) {
			return $field;
		}

		// Bail if the "Time Format" is set.
		if ( isset( $field_data['time_format'] ) ) {
			return $field;
		}

		// Get the mappings.
		$mappings = $this->acf_loader->mapper->date_mappings;

		// Get the ACF format.
		$acf_format = $mappings[ $field_data['date_format'] ];

		// Set the date "format" attributes.
		$field['display_format'] = $acf_format;
		$field['return_format']  = $acf_format;

		// --<
		return $field;

	}

	/**
	 * Filter the Custom Fields for the Setting of a "Date" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function date_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'date_picker' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Date/Select Date.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && 'Date' === $custom_field['data_type'] ) {
					if ( ! empty( $custom_field['html_type'] ) && 'Select Date' === $custom_field['html_type'] ) {
						if ( ! isset( $custom_field['time_format'] ) || 0 === (int) $custom_field['time_format'] ) {
							$filtered_fields[ $custom_group_name ][] = $custom_field;
						}
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}

	/**
	 * Modify the Settings of an ACF "Date Time Picker" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field The existing ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $field The modified ACF Field data array.
	 */
	public function date_time_picker_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'date_time_picker' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Custom Field ID if present.
		$custom_field_id = $this->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return $field;
		}

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );
		if ( false === $field_data ) {
			return $field;
		}

		// Bail if it's not Date.
		if ( 'Date' !== $field_data['data_type'] ) {
			return $field;
		}

		// Bail if it's not "Select Date".
		if ( 'Select Date' !== $field_data['html_type'] ) {
			return $field;
		}

		// Bail if the "Time Format" is not set.
		if ( ! isset( $field_data['time_format'] ) || 0 === (int) $field_data['time_format'] ) {
			return $field;
		}

		// Get the date mappings.
		$date_mappings = $this->acf_loader->mapper->date_mappings;

		// Get the ACF format.
		$acf_format = $date_mappings[ $field_data['date_format'] ];

		// Get the time mappings.
		$time_mappings = $this->acf_loader->mapper->time_mappings;

		// Append to the ACF format.
		if ( ! empty( $time_mappings[ $field_data['time_format'] ] ) ) {
			$acf_format .= ' ' . $time_mappings[ $field_data['time_format'] ];
		}

		// Set the date "format" attributes.
		$field['display_format'] = $acf_format;
		$field['return_format']  = $acf_format;

		// --<
		return $field;

	}

	/**
	 * Filter the Custom Fields for the Setting of a "Date Time" Field.
	 *
	 * @since 0.4
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function date_time_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'date_time_picker' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Date/Select Date.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && 'Date' === $custom_field['data_type'] ) {
					if ( ! empty( $custom_field['html_type'] ) && 'Select Date' === $custom_field['html_type'] ) {
						if ( ! empty( $custom_field['time_format'] ) && 0 !== (int) $custom_field['time_format'] ) {
							$filtered_fields[ $custom_group_name ][] = $custom_field;
						}
					}
				}
			}
		}

		// --<
		return $filtered_fields;

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

		// Get the mapped Custom Field ID if present.
		$custom_field_id = $this->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return $field;
		}

		// Get Custom Field data.
		$field_data = $this->plugin->civicrm->custom_field->get_by_id( $custom_field_id );

		// Bail if we don't get any.
		if ( false === $field_data ) {
			return $field;
		}

		// Bail if it's not Alphanumeric.
		if ( 'String' !== $field_data['data_type'] ) {
			return $field;
		}

		// Bail if it's not Text.
		if ( 'Text' !== $field_data['html_type'] ) {
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
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function text_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'text' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only those of HTML type "Text".
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && in_array( $custom_field['data_type'], $this->data_types, true ) ) {
					if ( ! empty( $custom_field['html_type'] ) && 'Text' === $custom_field['html_type'] ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
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
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function wysiwyg_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'wysiwyg' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Memo/RichTextEditor.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && 'Memo' === $custom_field['data_type'] ) {
					if ( ! empty( $custom_field['html_type'] ) && 'RichTextEditor' === $custom_field['html_type'] ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
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
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function textarea_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'textarea' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Memo/TextArea.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && 'Memo' === $custom_field['data_type'] ) {
					if ( ! empty( $custom_field['html_type'] ) && 'TextArea' === $custom_field['html_type'] ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
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
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function true_false_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'true_false' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only Boolean/Radio.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && 'Boolean' === $custom_field['data_type'] ) {
					if ( ! empty( $custom_field['html_type'] ) && 'Radio' === $custom_field['html_type'] ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
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
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function url_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'url' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only "Link".
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && 'Link' === $custom_field['data_type'] ) {
					if ( ! empty( $custom_field['html_type'] ) && 'Link' === $custom_field['html_type'] ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

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
	public function file_settings_modify( $field, $field_group ) {

		// Bail early if not our Field Type.
		if ( 'file' !== $field['type'] ) {
			return $field;
		}

		// Skip if the CiviCRM Field key isn't there or isn't populated.
		$key = $this->civicrm->acf_field_key_get();
		if ( ! array_key_exists( $key, $field ) || empty( $field[ $key ] ) ) {
			return $field;
		}

		// Get the mapped Custom Field ID if present.
		$custom_field_id = $this->custom_field_id_get( $field );
		if ( false === $custom_field_id ) {
			return $field;
		}

		// Set the "uploader" attribute.
		$field['uploader'] = 'basic';

		// Set the "max_size" attribute.
		$field['max_size'] = $this->civicrm->attachment->field_max_size_get();

		// --<
		return $field;

	}

	/**
	 * Filter the Custom Fields for the Setting of a "File" Field.
	 *
	 * @since 0.5.2
	 *
	 * @param array $filtered_fields The existing array of filtered Custom Fields.
	 * @param array $custom_fields The array of Custom Fields.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered_fields The modified array of filtered Custom Fields.
	 */
	public function file_settings_filter( $filtered_fields, $custom_fields, $field ) {

		// Bail early if not our Field Type.
		if ( 'file' !== $field['type'] ) {
			return $filtered_fields;
		}

		// Filter Fields to include only "File".
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			foreach ( $custom_group as $custom_field ) {
				if ( ! empty( $custom_field['data_type'] ) && 'File' === $custom_field['data_type'] ) {
					if ( ! empty( $custom_field['html_type'] ) && 'File' === $custom_field['html_type'] ) {
						$filtered_fields[ $custom_group_name ][] = $custom_field;
					}
				}
			}
		}

		// --<
		return $filtered_fields;

	}

}
