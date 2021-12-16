<?php
/**
 * BuddyPress CiviCRM Address Class.
 *
 * Handles BuddyPress CiviCRM Address functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync BuddyPress CiviCRM Address Class.
 *
 * A class that encapsulates BuddyPress CiviCRM Address functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_CiviCRM_Address {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * BuddyPress Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $bp_loader The BuddyPress Loader object.
	 */
	public $bp_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * BuddyPress xProfile object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $xprofile The BuddyPress xProfile object.
	 */
	public $xprofile;

	/**
	 * "CiviCRM Field" Field value prefix in the BuddyPress Field data.
	 *
	 * This distinguishes Address Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $address_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $address_field_prefix = 'cwps_address_';

	/**
	 * Public Address Fields.
	 *
	 * Mapped to their corresponding BuddyPress Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $address_fields The array of public Address Fields.
	 */
	public $address_fields = [
		'is_primary' => 'true_false',
		'is_billing' => 'true_false',
		'street_address' => 'textbox',
		'supplemental_address_1' => 'textbox',
		'supplemental_address_2' => 'textbox',
		'supplemental_address_3' => 'textbox',
		'city' => 'textbox',
		'county_id' => 'selectbox',
		'state_province_id' => 'selectbox',
		'country_id' => 'selectbox',
		'postal_code' => 'textbox',
		'geo_code_1' => 'textbox',
		'geo_code_2' => 'textbox',
		//'name' => 'textbox',
	];



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $xprofile The BuddyPress xProfile object.
	 */
	public function __construct( $xprofile ) {

		// Store references to objects.
		$this->plugin = $xprofile->bp_loader->plugin;
		$this->bp_loader = $xprofile->bp_loader;
		$this->civicrm = $this->plugin->civicrm;
		$this->xprofile = $xprofile;

		// Init when the BuddyPress Field object is loaded.
		add_action( 'cwps/buddypress/field/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Listen for queries from the BuddyPress Field class.
		add_filter( 'cwps/bp/field/query_setting_choices', [ $this, 'query_setting_choices' ], 20, 4 );

		// Filter the xProfile Field options when saving on the "Edit Field" screen.
		add_filter( 'cwps/bp/field/query_options', [ $this, 'checkbox_settings_modify' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'true_false_settings_modify' ], 10, 3 );
		add_filter( 'cwps/bp/field/query_options', [ $this, 'select_settings_modify' ], 10, 3 );

		// Append "True/False" mappings to the "Checkbox" xProfile Field Type.
		add_filter( 'cwps/bp/civicrm/address_field/get_for_bp_field', [ $this, 'true_false_fields_append' ], 10, 2 );

		// Determine if a "Checkbox" Field is a "True/False" Field.
		add_filter( 'cwps/bp/xprofile/value/checkbox/query_type', [ $this, 'true_false_field_query' ], 10, 2 );

		// Listen for when BuddyPress Profile Fields have been saved.
		add_filter( 'cwps/bp/contact/bp_fields_edited', [ $this, 'bp_fields_edited' ], 10 );

		// Listen for queries from the Custom Field class.
		add_filter( 'cwps/bp/query_user_id', [ $this, 'query_user_id' ], 10, 2 );

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Listen for events from our Mapper that require Address updates.
		add_action( 'cwps/mapper/address/created', [ $this, 'address_edited' ], 10 );
		add_action( 'cwps/mapper/address/edited', [ $this, 'address_edited' ], 10 );
		//add_action( 'cwps/mapper/address/delete/pre', [ $this, 'address_pre_delete' ], 10 );
		//add_action( 'cwps/mapper/address/deleted', [ $this, 'address_deleted' ], 10 );

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Remove all Mapper listeners.
		remove_action( 'cwps/mapper/address/created', [ $this, 'address_edited' ], 10 );
		remove_action( 'cwps/mapper/address/edited', [ $this, 'address_edited' ], 10 );
		//remove_action( 'cwps/mapper/address/delete/pre', [ $this, 'address_pre_delete' ], 10 );
		//remove_action( 'cwps/mapper/address/deleted', [ $this, 'address_deleted' ], 10 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Intercept when a CiviCRM Address Record has been updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function address_edited( $args ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
			return;
		}

		// Grab the Address Record data.
		$address = $args['objectRef'];

		// Bail if this is not a Contact's Address Record.
		if ( empty( $address->contact_id ) ) {
			return;
		}

		// Process the Address Record.
		$this->address_process( $address, $args );

		// TODO: Handle shared Address.
		return;

		// If this address is a "Master Address" then it will return "Shared Addresses".
		$addresses_shared = $this->plugin->civicrm->address->addresses_shared_get_by_id( $address->id );

		// Bail if there are none.
		if ( empty( $addresses_shared ) ) {
			return;
		}

		// Update all of them.
		foreach ( $addresses_shared as $address_shared ) {
			$this->address_process( $address_shared, $args );
		}

	}



	/**
	 * Process a CiviCRM Address Record.
	 *
	 * @since 0.5
	 *
	 * @param object $address The CiviCRM Address Record object.
	 * @param array $args The array of CiviCRM params.
	 */
	public function address_process( $address, $args ) {

		// Bail if we can't find a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return $user_id;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			'user_id' => $user_id,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the BuddyPress Fields for this User.
		$bp_fields = $this->xprofile->fields_get_for_user( $user_id );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'bp_fields' => $bp_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Filter out Fields not mapped to a CiviCRM Address Field.
		$bp_fields_mapped = [];
		foreach ( $bp_fields as $bp_field ) {

			// Only Fields for this Entity please.
			if ( $bp_field['field_meta']['entity_type'] !== 'Address' ) {
				continue;
			}

			// Only "Address" Fields with the matching Location Type.
			$location_type_id = (int) $bp_field['field_meta']['entity_data']['location_type_id'];
			if ( $address->location_type_id != $location_type_id ) {
				continue;
			}

			// Only "Address" Fields please.
			$bp_field_mapping = $bp_field['field_meta']['value'];
			$field_name = $this->name_get( $bp_field_mapping );
			if ( empty( $field_name ) ) {
				continue;
			}

			// Save the Field name for convenience.
			$bp_field['civicrm_field'] = $field_name;

			// Okay, add it.
			$bp_fields_mapped[] = $bp_field;

		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'bp_fields_mapped' => $bp_fields_mapped,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if we don't have any left.
		if ( empty( $bp_fields_mapped ) ) {
			return;
		}

		// Let's look at each BuddyPress Field in turn.
		foreach ( $bp_fields_mapped as $bp_field ) {

			// Get the CiviCRM Field name.
			$civicrm_field = $bp_field['civicrm_field'];

			// Does the mapped Address Field exist?
			if ( ! isset( $address->$civicrm_field ) ) {
				continue;
			}

			// Modify value for BuddyPress prior to update.
			$value = $this->value_get_for_bp( $address->$civicrm_field, $civicrm_field, $bp_field );

			// Okay, go ahead and save the value to the xProfile Field.
			$result = $this->xprofile->value_update( $bp_field['field_id'], $user_id, $value );

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'value' => $value,
				'result' => $result,
				//'backtrace' => $trace,
			], true ) );
			*/

		}

		// Add the User ID to the params.
		$args['user_id'] = $user_id;

		/**
		 * Broadcast that a set of BuddyPress Address Fields may have been edited.
		 *
		 * @since 0.5
		 *
		 * @param object $address The CiviCRM Address Record object.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/bp/civicrm/address/updated', $address, $args );

	}



	/**
	 * Get the value of a Address Field, formatted for BuddyPress.
	 *
	 * @since 0.5
	 *
	 * @param mixed $value The Address Field value.
	 * @param array $name The Address Field name.
	 * @param array $params The array of Field params.
	 * @return mixed $value The value formatted for BuddyPress.
	 */
	public function value_get_for_bp( $value, $name, $params ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'value' => $value,
			'name' => $name,
			'params' => $params,
			//'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if value is (string) 'null' which CiviCRM uses for some reason.
		if ( $value == 'null' || $value == 'NULL' ) {
			return '';
		}

		// Get the BuddyPress Field Type for this Address Field.
		$type = $this->get_bp_type( $name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'type' => $type,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Convert CiviCRM value to BuddyPress value by Field Type.
		switch ( $type ) {

			// Used by "Primary" etc.
			case 'true_false':

				// Clear the value when empty.
				if ( empty( $value ) ) {
					$value = null;
				} else {
					$value = 1;
				}

				break;

			// Used by "Country", "State/Province" and "County".
			case 'selectbox':

				// Convert if the value has the special CiviCRM array-like format.
				if ( is_string( $value ) ) {
					if ( false !== strpos( $value, CRM_Core_DAO::VALUE_SEPARATOR ) ) {
						$value = CRM_Utils_Array::explodePadded( $value );
					}
				}

				break;

		}

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Save Address(es) when BuddyPress Profile Fields have been saved.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of BuddyPress and CiviCRM params.
	 */
	public function bp_fields_edited( $args ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Bail if there is no Field data.
		if ( empty( $args['field_data'] ) ) {
			return;
		}

		// Filter the Fields to include only Address data.
		$address_fields = [];
		foreach ( $args['field_data'] as $field ) {
			if ( empty( $field['meta']['entity_type'] ) || $field['meta']['entity_type'] !== 'Address' ) {
				continue;
			}
			$address_fields[] = $field;
		}

		// Bail if there are no Address Fields.
		if ( empty( $address_fields ) ) {
			return;
		}

		// Group Fields by Location.
		$address_groups = [];
		foreach ( $address_fields as $field ) {
			if ( empty( $field['meta']['entity_data']['location_type_id'] ) ) {
				continue;
			}
			$location_type_id = $field['meta']['entity_data']['location_type_id'];
			$address_groups[ $location_type_id ][] = $field;
		}

		// Bail if there are no Address Groups.
		if ( empty( $address_groups ) ) {
			return;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_groups' => $address_groups,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Save each Address.
		foreach ( $address_groups as $location_type_id => $group ) {

			// Prepare the CiviCRM Address data.
			$address_data = $this->prepare_from_fields( $group );

			// Try and get the existing Address record.
			$existing = (array) $this->plugin->civicrm->address->address_get_by_location( $args['contact_id'], $location_type_id );

			// Add its ID if present.
			if ( ! empty( $existing['id'] ) ) {
				$address_data['id'] = $existing['id'];
			}

			// Add the Location Type.
			$address_data['location_type_id'] = $location_type_id;

			/*
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'address_data' => $address_data,
				//'backtrace' => $trace,
			], true ) );
			*/

			// Okay, write the data to CiviCRM.
			$address = $this->plugin->civicrm->address->update( $args['contact_id'], $address_data );

		}

	}



	/**
	 * Prepares the CiviCRM Contact data from an array of BuddyPress Field data.
	 *
	 * This method combines all Contact Fields that the CiviCRM API accepts as
	 * params for ( 'Contact', 'create' ) along with the linked Custom Fields.
	 *
	 * The CiviCRM API will update Custom Fields as long as they are passed to
	 * ( 'Contact', 'create' ) in the correct format. This is of the form:
	 * 'custom_N' where N is the ID of the Custom Field.
	 *
	 * @since 0.5
	 *
	 * @param array $field_data The array of BuddyPress Field data.
	 * @return array $contact_data The CiviCRM Contact data.
	 */
	public function prepare_from_fields( $field_data ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'field_data' => $field_data,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Init data for Fields.
		$address_data = [];

		// Handle the data for each Field.
		foreach ( $field_data as $data ) {

			// Get metadata for this xProfile Field.
			$meta = $data['meta'];
			if ( empty( $meta ) ) {
				continue;
			}

			// Get the CiviCRM Custom Field and Address Field.
			$custom_field_id = $this->xprofile->custom_field->id_get( $meta['value'] );
			$address_field_name = $this->name_get( $meta['value'] );

			// Do we have a synced Custom Field or Address Field?
			if ( ! empty( $custom_field_id ) || ! empty( $address_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . (string) $custom_field_id;

				} elseif ( ! empty( $address_field_name ) ) {

					// The Address Field code is the setting.
					$code = $address_field_name;

				}

				// Build args for value conversion.
				$args = [
					'entity_type' => $meta['entity_type'],
					'custom_field_id' => $custom_field_id,
					'address_field_name' => $address_field_name,
				];

				/*
				$e = new \Exception();
				$trace = $e->getTraceAsString();
				error_log( print_r( [
					'method' => __METHOD__,
					//'address_fields' => $address_fields,
					//'field_type' => $field_type,
					'data' => $data,
					'args' => $args,
					//'backtrace' => $trace,
				], true ) );
				*/

				// Parse value by Field Type.
				$value = $this->xprofile->value_get_for_civicrm( $data['value'], $data['field_type'], $args );

				// Add it to the Field data.
				$address_data[ $code ] = $value;

			}

		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_data' => $address_data,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $address_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Returns the Address Field choices for a Setting Field from when found.
	 *
	 * @since 0.5
	 *
	 * @param array $choices The existing array of choices for the Setting Field.
	 * @param string $field_type The BuddyPress Field Type.
	 * @param string $entity_type The CiviCRM Entity Type.
	 * @param array $entity_type_data The array of Entity Type data.
	 * @return array $choices The modified array of choices for the Setting Field.
	 */
	public function query_setting_choices( $choices, $field_type, $entity_type, $entity_type_data ) {

		// Bail if there's something amiss.
		if ( empty( $entity_type ) || empty( $field_type ) ) {
			return $choices;
		}

		// Bail if not the "Address" Entity Type.
		if ( $entity_type !== 'Address' ) {
			return $choices;
		}

		// Get the Address Fields for this BuddyPress Field Type.
		$address_fields = $this->get_for_bp_field_type( $field_type );

		// Build Address Field choices array for dropdown.
		if ( ! empty( $address_fields ) ) {
			$address_fields_label = esc_attr__( 'Address Fields', 'civicrm-wp-profile-sync' );
			foreach ( $address_fields as $address_field ) {
				$choices[ $address_fields_label ][ $this->address_field_prefix . $address_field['name'] ] = $address_field['title'];
			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/bp/address_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



	/**
	 * Get the CiviCRM Address Fields for a BuddyPress Field Type.
	 *
	 * @since 0.5
	 *
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $address_fields The array of Address Fields.
	 */
	public function get_for_bp_field_type( $field_type ) {

		// Init return.
		$address_fields = [];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'field_type' => $field_type,
			'location_type' => $location_type,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get public Fields of this type.
		$address_fields = $this->data_get( $field_type, 'public' );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_fields' => $address_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		/**
		 * Filter the Address Fields.
		 *
		 * @since 0.5
		 *
		 * @param array $address_fields The existing array of Address Fields.
		 * @param string $field_type The BuddyPress Field Type.
		 */
		$address_fields = apply_filters( 'cwps/bp/civicrm/address_field/get_for_bp_field', $address_fields, $field_type );

		// --<
		return $address_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the core Fields for a CiviCRM Address Type.
	 *
	 * @since 0.4
	 *
	 * @param string $field_type The type of ACF Field.
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function data_get( $field_type = '', $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			return $pseudocache[ $filter ][ $field_type ];
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
		$result = civicrm_api( 'Address', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Address Fields array.
				$public_fields = [];
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->address_fields ) ) {
						$public_fields[] = $value;
					}
				}

				// Skip all but those mapped to the type of ACF Field.
				foreach ( $public_fields as $key => $value ) {
					if ( $field_type == $this->address_fields[ $value['name'] ] ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ][ $field_type ] ) ) {
			$pseudocache[ $filter ][ $field_type ] = $fields;
		}

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the BuddyPress Field Type for an Address Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Address Field.
	 * @return string $type The type of BuddyPress Field.
	 */
	public function get_bp_type( $name = '' ) {

		// Init return.
		$type = false;

		// if the key exists, return the value - which is the BuddyPress Type.
		if ( array_key_exists( $name, $this->address_fields ) ) {
			$type = $this->address_fields[ $name ];
		}

		// --<
		return $type;

	}



	/**
	 * Gets the mapped Address Field name.
	 *
	 * @since 0.5
	 *
	 * @param string $value The value of the BuddyPress Field setting.
	 * @return string $name The mapped Contact Field name.
	 */
	public function name_get( $value ) {

		// Init return.
		$name = '';

		// Bail if our prefix isn't there.
		if ( false === strpos( $value, $this->address_field_prefix ) ) {
			return $name;
		}

		// Get the mapped Contact Field name.
		$name = (string) str_replace( $this->address_field_prefix, '', $value );

		// --<
		return $name;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the BuddyPress "selectbox" options for a given CiviCRM Contact Field.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Contact Field.
	 * @return array $options The array of xProfile Field options.
	 */
	public function options_get( $name ) {

		// Init return.
		$options = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $options;
		}

		// We only have a few to account for.

		// Counties.
		if ( $name == 'county_id' ) {
			$options = $this->plugin->civicrm->address->counties_get();
		}

		// States/Provinces.
		if ( $name == 'state_province_id' ) {
			$config = CRM_Core_Config::singleton();
			// Only get the list of States/Provinces if some are chosen.
			// BuddyPress becomes unresponsive when all are returned.
			if ( ! empty( $config->provinceLimit ) ) {
				$options = $this->plugin->civicrm->address->state_provinces_get();
			}
		}

		// Countries.
		if ( $name == 'country_id' ) {
			// Only get the list of Countries if some are chosen?
			$options = CRM_Core_PseudoConstant::country();
		}

		// --<
		return $options;

	}



	// -------------------------------------------------------------------------



	/**
	 * Listen for queries from the Custom Field class.
	 *
	 * This method responds with a User ID if it detects that the set of Custom
	 * Fields maps to an Address.
	 *
	 * @since 0.5
	 *
	 * @param array|bool $user_id The existing User ID.
	 * @param array $args The array of CiviCRM Custom Fields params.
	 * @return array|bool $user_id The User ID, or false if not mapped.
	 */
	public function query_user_id( $user_id, $args ) {

		// Init Address ID.
		$address_id = false;

		// Let's tease out the context from the Custom Field data.
		foreach ( $args['custom_fields'] as $field ) {

			// Skip if it is not attached to an Address.
			if ( $field['entity_table'] != 'civicrm_address' ) {
				continue;
			}

			// Grab the Address ID.
			$address_id = (int) $field['entity_id'];

			// We can bail now that we know.
			break;

		}

		// Bail if there's no Address ID.
		if ( $address_id === false ) {
			return $user_id;
		}

		// Grab Address.
		$address = $this->plugin->civicrm->address->address_get_by_id( $address_id );
		if ( $address === false ) {
			return $user_id;
		}

		// Bail if this Address does not have a Contact ID.
		if ( empty( $address->contact_id ) ) {
			return $user_id;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $address->contact_id );
		if ( $user_id === false ) {
			return $user_id;
		}

		// --<
		return $user_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Modify the Options of a BuddyPress "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function select_settings_modify( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'selectbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Field name.
		$field_name = $this->name_get( $args['value'] );
		if ( empty( $field_name ) ) {
			return $options;
		}

		// Get keyed array of options for this Field.
		$options = $this->options_get( $field_name );

		// --<
		return $options;

	}



	/**
	 * Modify the Options of a BuddyPress "Checkbox" Field.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function checkbox_settings_modify( $options, $field_type, $args ) {

		// Bail early if not our Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $options;
		}

		// Get the mapped Field name.
		$field_name = $this->name_get( $args['value'] );
		if ( empty( $field_name ) ) {
			return $options;
		}

		// Get keyed array of options for this Address Field.
		$options = $this->options_get( $field_name );

		// --<
		return $options;

	}



	// -------------------------------------------------------------------------



	/**
	 * Modify the Options of a special case BuddyPress "Checkbox" Field.
	 *
	 * BuddyPress does not have a "True/False" Field, so we use a "Checkbox"
	 * with only a single option.
	 *
	 * @since 0.5
	 *
	 * @param array $options The initially empty array to be populated.
	 * @param array $field_type The type of xProfile Field being saved.
	 * @param array $args The array of CiviCRM mapping data.
	 * @return array $options The possibly populated array of Options.
	 */
	public function true_false_settings_modify( $options, $field_type, $args ) {

		// Bail early if not the "Checkbox" Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $options;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'options' => $options,
			'field_type' => $field_type,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get the mapped Contact Field name.
		$field_name = $this->name_get( $args['value'] );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			//'value' => $value,
			'field_name' => $field_name,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( empty( $field_name ) ) {
			return $options;
		}

		// Bail if not a "True/False" Field Type.
		$civicrm_field_type = $this->get_bp_type( $field_name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'civicrm_field_type' => $civicrm_field_type,
			//'backtrace' => $trace,
		], true ) );
		*/

		if ( $civicrm_field_type !== 'true_false' ) {
			return $options;
		}

		// Get the full details for the CiviCRM Field.
		$civicrm_field = $this->plugin->civicrm->address->get_by_name( $field_name );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'civicrm_field' => $civicrm_field,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Use title for checkbox label.
		$options = [ 1 => $civicrm_field['title'] ];

		// --<
		return $options;

	}



	/**
	 * Filter the Address Fields for a special case BuddyPress "Checkbox" Field.
	 *
	 * BuddyPress does not have a "True/False" Field, so we use a "Checkbox"
	 * with only a single option.
	 *
	 * @since 0.5
	 *
	 * @param array $address_fields The existing array of Address Fields.
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $address_fields The modified array of Address Fields.
	 */
	public function true_false_fields_append( $address_fields, $field_type ) {

		// Bail early if not the "Checkbox" Field Type.
		if ( 'checkbox' !== $field_type ) {
			return $address_fields;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_fields' => $address_fields,
			'field_type' => $field_type,
			'name' => $name,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Get public Fields of this type.
		$true_false_fields = $this->data_get( 'true_false', 'public' );
		if ( empty( $true_false_fields ) ) {
			return $address_fields;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'true_false_fields' => $true_false_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// Merge with Address Fields.
		$address_fields = array_merge( $address_fields, $true_false_fields );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'address_fields-FINAL' => $address_fields,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $address_fields;

	}



	/**
	 * Checks if a "Checkbox" Field is a "True/False" Field.
	 *
	 * @since 0.5
	 *
	 * @param bool $is_true_false True if "Checkbox" is a "True/False" Field. False by default.
	 * @param array $args The array of arguments.
	 * @return bool $is_true_false True if "Checkbox" is a "True/False" Field. False by default.
	 */
	public function true_false_field_query( $is_true_false, $args ) {

		// Bail early if not the "Address" Entity Type.
		if ( 'Address' !== $args['entity_type'] ) {
			return $is_true_false;
		}

		// Bail if not an "Address Field".
		if ( empty( $args['address_field_name'] ) ) {
			return $is_true_false;
		}

		// Check if this is a "True/False" Field Type.
		$civicrm_field_type = $this->get_bp_type( $args['address_field_name'] );
		if ( $civicrm_field_type === 'true_false' ) {
			$is_true_false = true;
		}

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		error_log( print_r( [
			'method' => __METHOD__,
			'is_true_false' => $is_true_false,
			'args' => $args,
			//'backtrace' => $trace,
		], true ) );
		*/

		// --<
		return $is_true_false;

	}



} // Class ends.



