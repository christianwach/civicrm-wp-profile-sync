<?php
/**
 * Geo Mashup compatibility class.
 *
 * Manages Geo Mashup functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.8
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Geo Mashup class.
 *
 * @since 0.5.8
 */
class CiviCRM_WP_Profile_Sync_ACF_Geo_Mashup {

	/**
	 * Plugin object.
	 *
	 * @since 0.5.8
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5.8
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5.8
	 * @access public
	 * @var object $civicrm The CiviCRM object.
	 */
	public $civicrm;

	/**
	 * Class constructor.
	 *
	 * @since 0.5.8
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store references to objects.
		$this->plugin = $acf_loader->plugin;
		$this->acf_loader = $acf_loader;
		$this->civicrm = $acf_loader->civicrm;

		// Init when this plugin is loaded.
		add_action( 'cwps/acf/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.5.8
	 */
	public function initialise() {

		// Register all hooks.
		$this->register_hooks();
		$this->register_mapper_hooks();

	}

	/**
	 * Register hooks that should not be unhooked.
	 *
	 * @since 0.5.8
	 */
	public function register_hooks() {

		// Add setting for Geo Mashup Location sync.
		add_action( 'acf/render_field_settings/type=civicrm_address', [ $this, 'field_setting_add' ], 12 );

		// Catch updates to Address Fields and perform sync.
		add_filter( 'acf/update_value/type=civicrm_address', [ $this, 'field_modified' ], 10, 4 );

		// Modify CiviCRM Add/Edit Contact Type form.
		add_action( 'civicrm_buildForm', [ $this, 'form_contact_type_build' ], 11, 2 );

		// Listen for mapping updates.
		add_action( 'cwps/acf/mapping/contact/edited', [ $this, 'mapping_edited' ], 10, 3 );

		// Maybe hide map on Edit Post screens.
		add_action( 'add_meta_boxes', [ $this, 'metabox_remove' ], 100 );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5.8
	 */
	public function register_mapper_hooks() {

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.5.8
	 */
	public function unregister_mapper_hooks() {

	}

	// -------------------------------------------------------------------------

	/**
	 * Creates a Geo Mashup Location with a given set of data.
	 *
	 * @since 0.5.8
	 *
	 * @param array $location The Location data.
	 * @param bool  $do_lookups Pass true to refresh Location geodata.
	 * @return int|bool $result The new Location ID, or false on failure.
	 */
	public function create( $location, $do_lookups = false ) {

		// Cast lookups variable appropriately.
		if ( false === $do_lookups ) {
			$do_lookups = null;
		}

		// Returns Location ID or WP_Error.
		$result = GeoMashupDB::set_location( $location, $do_lookups );

		// Log and bail on error.
		if ( is_wp_error( $result ) ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$data  = [
				'method'    => __METHOD__,
				'location'  => $location,
				'error'     => $result->get_error_message(),
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $data );
			return false;
		}

		// --<
		return $result;

	}

	/**
	 * Updates a Geo Mashup Location with a given set of data.
	 *
	 * @since 0.5.8
	 *
	 * @param array $location The Location data.
	 * @param bool  $do_lookups Pass true to refresh Location geodata.
	 * @return int|bool $location_id The Location ID, or false on failure.
	 */
	public function update( $location, $do_lookups = false ) {

		// Log and bail if there's no Location ID.
		if ( empty( $location['id'] ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$data  = [
				'method'    => __METHOD__,
				'message'   => __( 'A numeric ID must be present to update a Location.', 'civicrm-wp-profile-sync' ),
				'location'  => $location,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $data );
			return false;
		}

		// Cast lookups variable appropriately.
		if ( false === $do_lookups ) {
			$do_lookups = null;
		}

		// Pass through.
		return $this->create( $location, $do_lookups );

	}

	/**
	 * Deletes a Geo Mashup Location for a given Location or Location ID.
	 *
	 * @since 0.5.8
	 *
	 * @param int|array $location The numeric ID of the Location, or a Location array.
	 * @return int|bool $result The number of rows affected, or false on failure.
	 */
	public function delete( $location ) {

		// Assign Location ID.
		if ( is_array( $location ) ) {
			$location_id = (int) $location['id'];
		} else {
			$location_id = (int) $location;
		}

		// Log and bail if there's no Location ID.
		if ( empty( $location_id ) ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$data  = [
				'method'    => __METHOD__,
				'message'   => __( 'A numeric ID must be present to delete a Location.', 'civicrm-wp-profile-sync' ),
				'location'  => $location,
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $data );
			return false;
		}

		// Wrap in an array.
		$location_ids = [ $location_id ];

		// Returns rows affected or WP_Error.
		$result = GeoMashupDB::delete_location( $location_ids );

		// Log and bail on error.
		if ( is_wp_error( $result ) ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$data  = [
				'method'      => __METHOD__,
				'location_id' => $location_id,
				'location'    => $location,
				'error'       => $result->get_error_message(),
				'backtrace'   => $trace,
			];
			$this->plugin->log_error( $data );
			return false;
		}

		// --<
		return $result;

	}

	// -------------------------------------------------------------------------

	/**
	 * Gets the Geo Mashup Location for a given Location ID.
	 *
	 * @since 0.5.8
	 *
	 * @param int $location_id The numeric ID of the Location.
	 * @return object|bool $location The Location data object, or false if not found.
	 */
	public function location_get_by_id( $location_id ) {

		// Get Location from Geo Mashup.
		$location = GeoMashupDB::get_location( $location_id );

		// Bail if not found.
		if ( empty( $location ) ) {
			return false;
		}

		// --<
		return $location;

	}

	/**
	 * Gets the Geo Mashup Location for a given Post ID.
	 *
	 * @since 0.5.8
	 *
	 * @param int $post_id The numeric ID of the Post.
	 * @return object|bool $location The Location data object, or false if not found.
	 */
	public function location_get_by_post_id( $post_id ) {

		// Get Location from Geo Mashup.
		$location = GeoMashupDB::get_post_location( $post_id );

		// Bail if not found.
		if ( empty( $location ) ) {
			return false;
		}

		// --<
		return $location;

	}

	/**
	 * Assigns the Geo Mashup Location to a given Post ID.
	 *
	 * @since 0.5.8
	 *
	 * @param int       $post_id The numeric ID of the Post.
	 * @param int|array $location The numeric ID of the Location or an array of Location data.
	 * @return int|bool $result The Location ID associated with the Post, false otherwise.
	 */
	public function location_save( $post_id, $location ) {

		// Set geodate to now.
		$geo_date_obj = new DateTime( 'now', $this->plugin->wp->get_site_timezone() );
		$geo_date     = $geo_date_obj->format( 'Y-m-d H:i:s' );

		// Do we want to refresh the geodata?
		$do_lookups = null;

		// Store location for Post.
		$result = GeoMashupDB::set_object_location( 'post', $post_id, $location, $do_lookups, $geo_date );

		// Log and bail on error.
		if ( is_wp_error( $result ) ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$data  = [
				'method'    => __METHOD__,
				'post_id'   => $post_id,
				'location'  => $location,
				'error'     => $result->get_error_message(),
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $data );
			return false;
		}

		// --<
		return $result;

	}

	/**
	 * Clears the Geo Mashup Location for a given Post ID.
	 *
	 * @since 0.5.8
	 *
	 * @param int $post_id The numeric ID of the Post.
	 * @return bool $result True on success, false otherwise.
	 */
	public function location_clear( $post_id ) {

		// Delete location for post.
		$object_name = 'post';
		$object_ids  = [ $post_id ];

		// Returns int|WP_Error Rows affected or WordPress error.
		$result = GeoMashupDB::delete_object_location( $object_name, $object_ids );

		// Log and bail on error.
		if ( is_wp_error( $result ) ) {
			$e     = new Exception();
			$trace = $e->getTraceAsString();
			$data  = [
				'method'    => __METHOD__,
				'post_id'   => $post_id,
				'error'     => $result->get_error_message(),
				'backtrace' => $trace,
			];
			$this->plugin->log_error( $data );
			return false;
		}

		// --<
		return true;

	}

	// -------------------------------------------------------------------------

	/**
	 * Prepares the Geo Mashup Location for a given Address.
	 *
	 * @since 0.5.8
	 *
	 * @param array $address The array of Address data.
	 * @return array $location The array of Location data.
	 */
	public function prepare_from_address( $address ) {

		// Init return.
		$location = [];

		// Assign "Saved Name" from Address Name or Street Address.
		$location['saved_name'] = empty( $address['field_address_name'] ) ? '' : $address['field_address_name'];
		if ( empty( $location['saved_name'] ) ) {
			$location['saved_name'] = empty( $address['field_address_street_address'] ) ? '' : $address['field_address_street_address'];
		}

		// Post Code.
		$location['postal_code'] = empty( $address['field_address_postal_code'] ) ? '' : $address['field_address_postal_code'];

		// Maybe add "State/Province" to array.
		$state_id = isset( $address['field_address_state_province_id'] ) ? $address['field_address_state_province_id'] : '';
		if ( ! empty( $state_id ) ) {
			$state = $this->plugin->civicrm->address->state_province_get_by_id( $state_id );
			if ( ! empty( $state ) ) {
				$address['state'] = $state['name'];
			}
		}

		// Maybe add "Country" to array.
		$country_id = isset( $address['field_address_country_id'] ) ? $address['field_address_country_id'] : '';
		if ( ! empty( $country_id ) ) {
			$country = $this->plugin->civicrm->address->country_get_by_id( $country_id );
			if ( ! empty( $country ) ) {
				$address['country'] = $country['name'];
			}
		}

		// Fields to concatenate into "address" field.
		$concatenate = [
			'field_address_street_address',
			'field_address_city',
			'state',
			'country',
			'field_address_postal_code',
		];

		// Build "address" field.
		$address_array = [];
		foreach ( $concatenate as $property ) {
			if ( ! empty( $address[ $property ] ) ) {
				$address_array[] = $address[ $property ];
			}
		}

		// Build "address" field.
		if ( ! empty( $address_array ) ) {
			$location['address'] = implode( ', ', $address_array );
		}

		// We might have an address that's more than 255 chars.
		if ( strlen( $location['address'] ) > 254 ) {

			// Try without State.
			$concatenate   = [ 'field_address_street_address', 'field_address_city', 'country', 'field_address_postal_code' ];
			$address_array = [];
			foreach ( $concatenate as $property ) {
				if ( ! empty( $address[ $property ] ) ) {
					$address_array[] = $address[ $property ];
				}
			}

			// Build "address" field.
			if ( ! empty( $address_array ) ) {
				$location['address'] = implode( ', ', $address );
			}

		}

		// Apply latitude and longitude.
		$location['lat'] = isset( $address['field_address_geo_code_1'] ) ? (float) trim( $address['field_address_geo_code_1'] ) : '';
		$location['lng'] = isset( $address['field_address_geo_code_2'] ) ? (float) trim( $address['field_address_geo_code_2'] ) : '';

		// --<
		return $location;

	}

	/**
	 * Maybe sync the Address to Geo Mashup.
	 *
	 * This filter is applied to the value before it is saved in the database.
	 * This callback doesn't actually modify the value, but does sync the data
	 * to Geo Mashup at this point.
	 *
	 * @since 0.5.8
	 *
	 * @param mixed $value The value being saved to the database.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array $field The Field array holding all the Field options.
	 * @return mixed $value The modified value.
	 */
	public function field_modified( $value, $post_id, $field ) {

		// Skip if there is no mapped Location Type.
		$location_type = isset( $field['geo_mashup_location_type'] ) ? $field['geo_mashup_location_type'] : '';
		if ( empty( $location_type ) ) {
			return $value;
		}

		// Get the Post Type.
		$post_type = get_post_type( $post_id );
		if ( empty( $post_type ) ) {
			return $value;
		}

		// Get Post Type settings.
		$settings = $this->acf_loader->mapping->setting_get( $post_type );
		if ( empty( $settings ) ) {
			return $value;
		}

		// Skip if the "enabled" setting has not been set.
		if ( empty( $settings['geo_mashup'] ) ) {
			return $value;
		}

		// Clear Location and bail if we have no data.
		if ( empty( $value ) || ! is_array( $value ) ) {
			$this->location_clear( $post_id );
			return $value;
		}

		// Check each Address Record for the mapped Location Type.
		foreach ( $value as $key => $address ) {

			// Skip if the mapped Location Type is "primary" and this Address isn't.
			$is_primary = isset( $address['field_address_primary'] ) ? $address['field_address_primary'] : '';
			if ( $location_type === 'primary' && empty( $is_primary ) ) {
				continue;
			}

			// Skip if the mapped Location Type is "billing" and this Address isn't.
			$is_billing = isset( $address['field_address_billing'] ) ? $address['field_address_billing'] : '';
			if ( $location_type === 'billing' && empty( $is_billing ) ) {
				continue;
			}

			// Skip if the mapped Location Type is numeric and this Address isn't of that Location Type.
			$address_location_type = isset( $address['field_address_location_type'] ) ? (int) $address['field_address_location_type'] : '';
			if ( is_numeric( $location_type ) && $address_location_type !== (int) $location_type ) {
				continue;
			}

			// Convert to Geo Mashup data format.
			$location = $this->prepare_from_address( $address );

			// Get the correspondence.
			$existing_location = $this->location_get_by_post_id( $post_id );

			// Assign Location ID if one exists.
			if ( ! empty( $existing_location_id ) ) {
				$location['id'] = $existing_location->id;
			}

			// Create or update the Geo Mashup Location.
			if ( empty( $existing_location_id ) ) {
				$location_id = $this->create( $location, true );
			} else {
				$location_id = $this->update( $location, true );
			}

			// Finally apply Location to Post.
			$result = $this->location_save( $post_id, $location_id );

		}

		// Clear if no Location has been updated - no match found.
		if ( ! isset( $location_id ) ) {
			$this->location_clear( $post_id );
		}

		// --<
		return $value;

	}

	/**
	 * Create extra Settings for the "CiviCRM Address" Field Type.
	 *
	 * @since 0.5.8
	 *
	 * @param array $field The Field being edited.
	 */
	public function field_setting_add( $field ) {

		global $geo_mashup_options;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Get Field Group for this Field's parent.
		$field_group = $this->acf_loader->acf->field_group->get_for_field( $field );
		if ( empty( $field_group ) ) {
			return $field;
		}

		// Skip if this is not a Contact Field Group.
		$is_contact_field_group = $this->civicrm->contact->is_contact_field_group( $field_group );
		if ( empty( $is_contact_field_group ) ) {
			return $field;
		}

		// Get the Post Types that Geo Mashup syncs.
		$located_post_types = $geo_mashup_options->get( 'overall', 'located_post_types' );
		if ( empty( $located_post_types ) ) {
			return $field;
		}

		// Check if the Post Types are mapped either here or in Geo Mashup.
		$in_both = [];
		foreach ( $is_contact_field_group as $post_type_name ) {
			if ( in_array( $post_type_name, $located_post_types ) ) {
				$in_both[] = $post_type_name;
			}
		}

		// Disallow if none of the Post Types are mapped either here or in Geo Mashup.
		if ( empty( $in_both ) ) {
			return $field;
		}

		// Check our Contact Type settings.
		$allowed = [];
		foreach ( $in_both as $post_type ) {

			// Get Post Type settings.
			$settings = $this->acf_loader->mapping->setting_get( $post_type );
			if ( empty( $settings ) ) {
				continue;
			}

			// Allow if the setting has been set.
			if ( ! empty( $settings['geo_mashup'] ) ) {
				$allowed[] = $post_type;
			}

		}

		// Disallow if none of the Post Types have been declared as synced.
		if ( empty( $allowed ) ) {
			return $field;
		}

		// Init choices array for dropdown.
		$choices = [];

		// Build specific Locations.
		$specific_label = esc_attr__( 'Specific Location', 'civicrm-wp-profile-sync' );
		$choices[ $specific_label ]['primary'] = esc_attr__( 'Primary', 'civicrm-wp-profile-sync' );
		$choices[ $specific_label ]['billing'] = esc_attr__( 'Billing', 'civicrm-wp-profile-sync' );

		// Build Location Types choices array for dropdown.
		$location_types = $this->plugin->civicrm->address->location_types_get();
		$location_label = esc_attr__( 'Location Types', 'civicrm-wp-profile-sync' );
		foreach ( $location_types as $location_type ) {
			$choices[ $location_label ][ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// Define Location Field.
		$location = [
			'label' => __( 'Geo Mashup sync', 'civicrm-wp-profile-sync' ),
			'name' => 'geo_mashup_location_type',
			'type' => 'select',
			'instructions' => __( 'Choose the Location Type to sync to Geo Mashup. (Optional)', 'civicrm-wp-profile-sync' ),
			'required' => 0,
			'conditional_logic' => 0,
			'allow_null' => 1,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
			'ajax' => 0,
			'placeholder' => '',
			'prefix' => '',
			'default_value' => false,
			'choices' => $choices,
		];

		// Now add it.
		acf_render_field_setting( $field, $location );

	}

	// -------------------------------------------------------------------------

	/**
	 * Adds settings for a CiviCRM Contact Type.
	 *
	 * @since 0.5.8
	 *
	 * @param string $formName The CiviCRM form name.
	 * @param object $form The CiviCRM form object.
	 */
	public function form_contact_type_build( $formName, &$form ) {

		global $geo_mashup_options;

		// Get the Post Types that Geo Mashup syncs.
		$located_post_types = $geo_mashup_options->get( 'overall', 'located_post_types' );
		if ( empty( $located_post_types ) ) {
			return;
		}

		// Is this the Contact Type edit form?
		if ( $formName != 'CRM_Admin_Form_ContactType' ) {
			return;
		}

		// Bail if this is the CiviCRM "Delete Action".
		$action = $form->getVar( '_action' );
		if ( isset( $action ) && 8 === (int) $action ) {
			return;
		}

		// Get CiviCRM Contact Type.
		$contact_type = $form->getVar( '_values' );

		// Determine form mode by whether we have a Contact Type.
		if ( isset( $contact_type ) && ! empty( $contact_type ) ) {
			$mode = 'edit';
		} else {
			$mode = 'create';
		}

		// Add Geo Mashup synced Post Types.
		$form->assign( 'geo_mashup_post_types', wp_json_encode( $located_post_types ) );

		// Add checkbox for enabling Geo Mashup sync.
		$geo_mashup = $form->add(
			'checkbox',
			'geo_mashup',
			__( 'Geo Mashup', 'civicrm-wp-profile-sync' )
		);

		// Add help text.
		$form->assign(
			'geo_mashup_help',
			__( 'Enable Address Location sync with Geo Mashup on the synced Post Type.', 'civicrm-wp-profile-sync' )
		);

		// Add checkbox for disabling the Geo Mashup metabox.
		$geo_mashup_metabox = $form->add(
			'checkbox',
			'geo_mashup_metabox',
			__( 'Geo Mashup Metabox', 'civicrm-wp-profile-sync' )
		);

		// Add help text.
		$form->assign(
			'geo_mashup_metabox_help',
			__( 'Hide the Geo Mashup Map metabox on the synced Post Type.', 'civicrm-wp-profile-sync' )
		);

		// Amend form in edit mode.
		if ( $mode === 'edit' ) {

			// Get existing CPT.
			$cpt_name = $this->acf_loader->mapping->mapping_for_contact_type_get( $contact_type['id'] );

			// If we have a mapped CPT.
			if ( $cpt_name !== false ) {

				// Get CPT settings.
				$cpt_settings = $this->acf_loader->mapping->setting_get( $cpt_name );

				// If we have some settings.
				if ( $cpt_settings !== false ) {

					// Set status of checkbox based on setting.
					if ( isset( $cpt_settings['geo_mashup'] ) && 1 === (int) $cpt_settings['geo_mashup'] ) {
						$geo_mashup->setChecked( true );
					}

					// Set status of checkbox based on setting.
					if ( isset( $cpt_settings['geo_mashup_metabox'] ) && 1 === (int) $cpt_settings['geo_mashup_metabox'] ) {
						$geo_mashup_metabox->setChecked( true );
					}

				}

			}

		}

		// Insert template block into the page.
		CRM_Core_Region::instance( 'page-body' )->add( [
			'template' => 'cwps-acf-geo-mashup-compat.tpl',
		] );

	}

	/**
	 * Act when a Mapping has been edited.
	 *
	 * @since 0.5.8
	 *
	 * @param int $contact_type_id The updated Contact Type ID.
	 * @param str $post_type The updated Post Type name.
	 * @param array $values The form values.
	 */
	public function mapping_edited( $contact_type_id, $post_type, $values ) {

		// Get the "enabled" checkbox value.
		$geo_mashup = 0;
		if ( isset( $values['geo_mashup'] ) && 1 === (int) $values['geo_mashup'] ) {
			$geo_mashup = 1;
		}

		// Get the "metabox" checkbox value.
		$geo_mashup_metabox = 0;
		if ( isset( $values['geo_mashup_metabox'] ) && 1 === (int) $values['geo_mashup_metabox'] ) {
			$geo_mashup_metabox = 1;
		}

		// Get current settings data.
		$data = $this->acf_loader->mapping->setting_get( $post_type );

		// Add/Update settings.
		$data['geo_mashup'] = $geo_mashup;
		$data['geo_mashup_metabox'] = $geo_mashup_metabox;

		// Override the "metabox" checkbox value when sync is off.
		if ( $geo_mashup === 0 ) {
			$data['geo_mashup_metabox'] = 0;
		}

		// Overwrite settings.
		$this->acf_loader->mapping->setting_update( $post_type, $data );

	}

	/**
	 * Removes the Geo Mashup metabox.
	 *
	 * @since 0.5.8
	 */
	public function metabox_remove() {

		// Get Post Type settings.
		$settings = $this->acf_loader->mapping->settings_get();
		if ( empty( $settings ) ) {
			return;
		}

		// Do not show the Geo Mashup metabox on selected Post screens.
		foreach ( $settings as $post_type => $setting ) {
			if ( ! empty( $setting['geo_mashup_metabox'] ) ) {
				remove_meta_box( 'geo_mashup_post_edit', $post_type, 'advanced' );
			}
		}

	}

}
