<?php
/**
 * BuddyPress CiviCRM Website Class.
 *
 * Handles BuddyPress CiviCRM Website functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5.2
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync BuddyPress CiviCRM Website Class.
 *
 * A class that encapsulates BuddyPress CiviCRM Website functionality.
 *
 * @since 0.5.2
 */
class CiviCRM_Profile_Sync_BP_CiviCRM_Website {

	/**
	 * Plugin object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * BuddyPress Loader object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object $bp_loader The BuddyPress Loader object.
	 */
	public $bp_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * BuddyPress xProfile object.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object $xprofile The BuddyPress xProfile object.
	 */
	public $xprofile;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_hooks The Mapper hooks registered flag.
	 */
	public $mapper_hooks = false;

	/**
	 * "CiviCRM Field" Field value prefix in the BuddyPress Field data.
	 *
	 * This distinguishes Website Fields from Custom Fields.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var string $website_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $website_field_prefix = 'cwps_website_';

	/**
	 * Public Website Fields.
	 *
	 * Mapped to their corresponding BuddyPress Field Types.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var array $website_fields The array of public Website Fields.
	 */
	public $website_fields = [
		'url' => [
			'url',
		],
	];

	/**
	 * Constructor.
	 *
	 * @since 0.5.2
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
	 * @since 0.5.2
	 */
	public function initialise() {

		// Always register plugin hooks.
		add_action( 'cwps/plugin/hooks/bp/add', [ $this, 'register_mapper_hooks' ] );
		add_action( 'cwps/plugin/hooks/bp/remove', [ $this, 'unregister_mapper_hooks' ] );

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5.2
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

		// Listen for queries from the BuddyPress Field class.
		add_filter( 'cwps/bp/field/query_setting_choices', [ $this, 'query_setting_choices' ], 40, 4 );

		// Listen for when BuddyPress Profile Fields have been saved.
		add_filter( 'cwps/bp/contact/bp_fields_edited', [ $this, 'bp_fields_edited' ], 10 );

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

		// Listen for events from our Mapper that require Website updates.
		add_action( 'cwps/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		add_action( 'cwps/mapper/website/delete/pre', [ $this, 'website_pre_delete' ], 10 );
		add_action( 'cwps/mapper/website/created', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//add_action( 'cwps/mapper/website/deleted', [ $this, 'website_deleted' ], 10 );

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
		remove_action( 'cwps/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		remove_action( 'cwps/mapper/website/delete/pre', [ $this, 'website_pre_delete' ], 10 );
		remove_action( 'cwps/mapper/website/created', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//remove_action( 'cwps/mapper/website/deleted', [ $this, 'website_deleted' ], 10 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -------------------------------------------------------------------------

	/**
	 * Fires when a CiviCRM Contact's Website is about to be edited.
	 *
	 * We need to check if an existing Website's Website Type is being changed,
	 * so we store the previous Website record here for comparison later.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_edit( $args ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
			return;
		}

		// Always clear properties if set previously.
		if ( isset( $this->website_pre ) ) {
			unset( $this->website_pre );
		}

		// Grab Website object.
		$website = $args['objectRef'];

		// We need a Contact ID in the edited Website.
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Grab the previous Website data from the database.
		$this->website_pre = (object) $this->plugin->civicrm->website->get_by_id( $website->id );

	}

	/**
	 * Fires when a CiviCRM Contact's Website is about to be deleted.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_delete( $args ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
			return;
		}

		// Grab deleted CiviCRM Website ID.
		$website_id = (int) $args['objectId'];

		// Grab the existing Website data from the database.
		$website = (object) $this->plugin->civicrm->website->get_by_id( $website_id );

		// Bail if this is not a Contact's Website Record.
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Which Website Type is the synced Website Type?
		$website_type_id = $this->plugin->admin->setting_get( 'user_profile_website_type', 0 );

		// Bail if this is the synced Website Type ID.
		if ( (int) $website->website_type_id === (int) $website_type_id ) {
			return;
		}

		// Let's make a new object so we don't overwrite the Website object.
		$deleted = new stdClass();
		$deleted->id = $website->id;
		$deleted->contact_id = $website->contact_id;
		$deleted->website_type_id = $website->website_type_id;

		// Clear the URL.
		$deleted->url = '';

		// Process the Website Record.
		$this->website_process( $deleted, $args );

	}

	/**
	 * Intercept when a CiviCRM Website Record has been updated.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_edited( $args ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
			return;
		}

		// Grab edited CiviCRM Website object.
		if ( ! is_object( $args['objectRef'] ) ) {
			$website = (object) $args['objectRef'];
		} else {
			$website = $args['objectRef'];
		}

		// Bail if this is not a Contact's Website Record.
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Which Website Type is the synced Website Type?
		$website_type_id = $this->plugin->admin->setting_get( 'user_profile_website_type', 0 );

		// Assume unchanged User Website Type.
		$unchanged = true;
		$was_user_type = false;
		$now_user_type = false;

		// Check previous Website Type if there is one.
		if ( ! empty( $this->website_pre ) && (int) $this->website_pre->id === (int) $website->id ) {

			// If it used to be the synced Website Type.
			if ( (int) $website_type_id === (int) $this->website_pre->website_type_id ) {

				// Check if it no longer is.
				if ( (int) $website_type_id !== (int) $website->website_type_id ) {
					$was_user_type = true;
					$this->bp_now_user_type = clone $website;
					$unchanged = false;
				}

			} else {

				// Check if it now is.
				if ( (int) $website_type_id === (int) $website->website_type_id ) {
					$now_user_type = true;
					$this->bp_was_user_type = clone $this->website_pre;
					$unchanged = false;
				}

			}

			// Check if it is now a different non-synced Website Type.
			if ( (int) $website->website_type_id !== (int) $this->website_pre->website_type_id ) {
				$unchanged = false;
			}

		}

		// Bail if this is the synced Website Type ID.
		if ( $unchanged && (int) $website->website_type_id === (int) $website_type_id ) {
			return;
		}

		// Process the Website Record if Website Type is unchanged.
		if ( $unchanged === true ) {
			$this->website_process( $website, $args );
			return;
		}

		/*
		 * When there is a change in the edited Website's Website Type:
		 *
		 * If it is now the synced Website Type, clear the URL from the xProfile
		 * Field since it's no longer valid. This means restoring the previous
		 * Website Type to make the update.
		 *
		 * If it is no longer the synced Website Type, edit as normal. Changes
		 * *away* from the synced Website Type are handled elsewhere.
		 */
		// Handle changes that are to do with the synced Website Type.
		if ( $now_user_type || $was_user_type ) {

			// Make a new object so we don't overwrite the Website Pre object.
			$previous = clone $this->website_pre;
			$previous->url = '';

			// Make a new object so we don't overwrite the Website object.
			$current = new stdClass();
			$current->id = $website->id;
			$current->contact_id = $website->contact_id;
			$current->url = $website->url;
			$current->website_type_id = $website->website_type_id;

			// If we're updating "is now", skip if "used to be" has been updated.
			if ( $now_user_type === true ) {
				if ( ! empty( $this->bp_now_user_type ) ) {
					if ( (int) $previous->website_type_id === (int) $this->bp_now_user_type->website_type_id ) {
						return;
					}
				}
			}

			// Restore if this Website Type has already been changed.
			if ( ! empty( $this->previous_changes[ $previous->website_type_id ] ) ) {
				$this->website_process( $this->previous_changes[ $previous->website_type_id ], $args );
				return;
			}

			// For "is now", clear the *previous* xProfile Field's URL.
			if ( $now_user_type === true ) {
				$previous->url = '';
			}

			// For "used to be", just rebuild.
			if ( $was_user_type === true ) {
				// When only the Website Type has changed, current URL may be empty.
				if ( empty( $current->url ) && ! empty( $this->website_pre->url ) ) {
					// Try and use the previous URL.
					$current->url = $this->website_pre->url;
				}
			}

			// Process the current changed Website.
			$this->website_process( $current, $args );

			// Maybe keep a log of the current Website.
			if ( ! empty( $this->bp_now_user_type ) ) {
				$this->previous_changes[ $this->bp_now_user_type->website_type_id ] = $this->bp_now_user_type;
			}

			// We're done.
			return;

		}

		/*
		 * If we get here, we're switching between Website Types neither of
		 * which is the synced Website Type.
		 *
		 * Let's clear the URL of the previous one and process it, then let
		 * the current one to process as well.
		 *
		 * On subsequent runs, we test for whether the "present" Website has
		 * already been written to and skip overwriting if that is the case.
		 */

		// Make a new object so we don't overwrite the Website Pre object.
		$previous = clone $this->website_pre;
		$previous->url = '';

		// Make a new object so we don't overwrite the Website object.
		$current = new stdClass();
		$current->id = $website->id;
		$current->contact_id = $website->contact_id;
		$current->url = $website->url;
		$current->website_type_id = $website->website_type_id;

		// Assume we want to process the previous.
		$process_previous = true;

		// Skip previous if we're updating "used to be".
		if ( ! empty( $this->bp_was_user_type ) ) {
			if ( (int) $current->website_type_id === (int) $this->bp_was_user_type->website_type_id ) {
				$process_previous = false;
			}
		}

		// Skip previous if it has already been changed.
		if ( ! empty( $this->previous_changes[ $previous->website_type_id ] ) ) {
			$process_previous = false;
		}

		// Maybe process previous.
		if ( $process_previous === true ) {
			$this->website_process( $previous, $args );
		}

		// Process the current Website.
		$this->website_process( $current, $args );

		// Keep a log of the current changed Website.
		$this->previous_changes[ $current->website_type_id ] = $current;

	}

	/**
	 * Process a CiviCRM Website Record.
	 *
	 * @since 0.5.2
	 *
	 * @param object $website The CiviCRM Website Record object.
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_process( $website, $args ) {

		// Bail if we can't find a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $website->contact_id );
		if ( $user_id === false ) {
			return $user_id;
		}

		// Get the BuddyPress Fields for this User.
		$bp_fields = $this->xprofile->fields_get_for_user( $user_id );

		// Filter out Fields not mapped to a CiviCRM Website Field.
		$bp_fields_mapped = [];
		foreach ( $bp_fields as $bp_field ) {

			// Only Fields for this Entity please.
			if ( $bp_field['field_meta']['entity_type'] !== 'Website' ) {
				continue;
			}

			// Only "Website" Fields with the matching Website Type.
			$website_type_id = (int) $bp_field['field_meta']['entity_data']['website_type_id'];
			if ( $website->website_type_id != $website_type_id ) {
				continue;
			}

			// Only "Website" Fields please.
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

		// Bail if we don't have any left.
		if ( empty( $bp_fields_mapped ) ) {
			return;
		}

		// Let's look at each BuddyPress Field in turn.
		foreach ( $bp_fields_mapped as $bp_field ) {

			// Get the CiviCRM Field name.
			$civicrm_field = $bp_field['civicrm_field'];

			// Does the mapped Website Field exist?
			if ( ! isset( $website->$civicrm_field ) ) {
				continue;
			}

			// Modify value for BuddyPress prior to update.
			$value = $this->value_get_for_bp( $website->$civicrm_field, $civicrm_field, $bp_field );

			// Okay, go ahead and save the value to the xProfile Field.
			$result = $this->xprofile->value_update( $bp_field['field_id'], $user_id, $value );

		}

		// Add the User ID to the params.
		$args['user_id'] = $user_id;

		/**
		 * Broadcast that a set of BuddyPress Website Fields may have been edited.
		 *
		 * @since 0.5.2
		 *
		 * @param object $website The CiviCRM Website Record object.
		 * @param array $args The array of CiviCRM params.
		 */
		do_action( 'cwps/bp/civicrm/website/updated', $website, $args );

	}

	/**
	 * Get the value of a Website Field, formatted for BuddyPress.
	 *
	 * @since 0.5.2
	 *
	 * @param mixed $value The Website Field value.
	 * @param array $name The Website Field name.
	 * @param array $params The array of Field params.
	 * @return mixed $value The value formatted for BuddyPress.
	 */
	public function value_get_for_bp( $value, $name, $params ) {

		// Bail if value is (string) 'null' which CiviCRM uses for some reason.
		if ( $value == 'null' || $value == 'NULL' ) {
			return '';
		}

		// Get the BuddyPress Field Type for this Website Field.
		$type = $this->get_bp_type( $name );

		// Bail if it's the "website" array since it doesn't need formatting.
		if ( is_array( $type ) ) {
			return $value;
		}

		// Convert CiviCRM value to BuddyPress value by Field Type.
		switch ( $type ) {

			case 'url':
				// Validate the URL? IIRC CiviCRM does this.
				break;

		}

		// --<
		return $value;

	}

	// -------------------------------------------------------------------------

	/**
	 * Save Website(s) when BuddyPress Profile Fields have been saved.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of BuddyPress and CiviCRM params.
	 */
	public function bp_fields_edited( $args ) {

		// Bail if there is no Field data.
		if ( empty( $args['field_data'] ) ) {
			return;
		}

		// Filter the Fields to include only Website data.
		$website_fields = [];
		foreach ( $args['field_data'] as $field ) {
			if ( empty( $field['meta']['entity_type'] ) || $field['meta']['entity_type'] !== 'Website' ) {
				continue;
			}
			$website_fields[] = $field;
		}

		// Bail if there are no Website Fields.
		if ( empty( $website_fields ) ) {
			return;
		}

		// Save each Website.
		foreach ( $website_fields as $website_field ) {

			// Prepare the CiviCRM Website data.
			$website_data = $this->prepare_from_fields( $website_field );

			// Grab the parsed data.
			$website_type_id = $website_data['website_type_id'];
			$contact_id = $args['contact_id'];
			$url = $website_data['url'];

			// Okay, write the data to CiviCRM.
			$website = $this->plugin->civicrm->website->update_for_contact( $website_type_id, $contact_id, $url );

		}

	}

	/**
	 * Prepares the CiviCRM Website data from an array of BuddyPress Field data.
	 *
	 * This method combines all Website Fields that the CiviCRM API accepts as
	 * params for ( 'Website', 'create' ) along with the linked Custom Fields.
	 *
	 * The CiviCRM API will update Custom Fields as long as they are passed to
	 * ( 'Website', 'create' ) in the correct format. This is of the form:
	 * 'custom_N' where N is the ID of the Custom Field.
	 *
	 * @since 0.5.2
	 *
	 * @param array $field_data The array of BuddyPress Field data.
	 * @return array $website_data The CiviCRM Website data.
	 */
	public function prepare_from_fields( $field_data ) {

		// Init data for Fields.
		$website_data = [];

		// Get metadata for this xProfile Field.
		$meta = $field_data['meta'];
		if ( empty( $meta ) ) {
			return $website_data;
		}

		// Get the CiviCRM Custom Field and Website Field.
		$custom_field_id = $this->xprofile->custom_field->id_get( $meta['value'] );
		$website_field_name = $this->name_get( $meta['value'] );

		// Do we have a synced Custom Field or Website Field?
		if ( ! empty( $custom_field_id ) || ! empty( $website_field_name ) ) {

			// If it's a Custom Field.
			if ( ! empty( $custom_field_id ) ) {

				// Build Custom Field code.
				$code = 'custom_' . (string) $custom_field_id;

			} elseif ( ! empty( $website_field_name ) ) {

				// The Website Field code is the setting.
				$code = $website_field_name;

			}

			// Build args for value conversion.
			$args = [
				'entity_type' => $meta['entity_type'],
				'custom_field_id' => $custom_field_id,
				'website_field_name' => $website_field_name,
			];

			// Parse value by Field Type.
			$value = $this->xprofile->value_get_for_civicrm( $field_data['value'], $field_data['field_type'], $args );

			// Add it to the Field data.
			$website_data[ $code ] = $value;

		}

		// Add Website Type ID.
		$website_data['website_type_id'] = $meta['entity_data']['website_type_id'];

		// --<
		return $website_data;

	}

	// -------------------------------------------------------------------------

	/**
	 * Returns the Website Field choices for a Setting Field from when found.
	 *
	 * @since 0.5.2
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

		// Bail if not the "Website" Entity Type.
		if ( $entity_type !== 'Website' ) {
			return $choices;
		}

		// Get the Website Fields for this BuddyPress Field Type.
		$website_fields = $this->get_for_bp_field_type( $field_type );

		// Build Website Field choices array for dropdown.
		if ( ! empty( $website_fields ) ) {
			$website_fields_label = esc_attr__( 'Website Fields', 'civicrm-wp-profile-sync' );
			foreach ( $website_fields as $website_field ) {
				$choices[ $website_fields_label ][ $this->website_field_prefix . $website_field['name'] ] = $website_field['title'];

			}
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5.2
		 *
		 * @param array $choices The array of choices for the Setting Field.
		 */
		$choices = apply_filters( 'cwps/bp/website_field/choices', $choices );

		// Return populated array.
		return $choices;

	}

	/**
	 * Get the CiviCRM Website Fields for a BuddyPress Field Type.
	 *
	 * @since 0.5.2
	 *
	 * @param string $field_type The BuddyPress Field Type.
	 * @return array $website_fields The array of Website Fields.
	 */
	public function get_for_bp_field_type( $field_type ) {

		// Init return.
		$website_fields = [];

		// Get public Fields of this type.
		$website_fields = $this->data_get( $field_type, 'public' );

		/**
		 * Filter the Website Fields.
		 *
		 * @since 0.5.2
		 *
		 * @param array $website_fields The existing array of Website Fields.
		 * @param string $field_type The BuddyPress Field Type.
		 */
		$website_fields = apply_filters( 'cwps/bp/civicrm/website_field/get_for_bp_field', $website_fields, $field_type );

		// --<
		return $website_fields;

	}

	// -------------------------------------------------------------------------

	/**
	 * Get the core Fields for a CiviCRM Website Type.
	 *
	 * @since 0.4
	 *
	 * @param string $field_type The type of xProfile Field.
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
		$result = civicrm_api( 'Website', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our Website Fields array.
				$public_fields = [];
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->website_fields ) ) {
						$public_fields[] = $value;
					}
				}

				// Skip all but those mapped to the type of xProfile Field.
				foreach ( $public_fields as $key => $value ) {
					if ( is_array( $this->website_fields[ $value['name'] ] ) ) {
						if ( in_array( $field_type, $this->website_fields[ $value['name'] ] ) ) {
							$fields[] = $value;
						}
					} else {
						if ( $field_type == $this->website_fields[ $value['name'] ] ) {
							$fields[] = $value;
						}
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
	 * Get the BuddyPress Field Type for a Website Field.
	 *
	 * @since 0.5.2
	 *
	 * @param string $name The name of the Website Field.
	 * @return string|array $type The type of BuddyPress Field (or array of types).
	 */
	public function get_bp_type( $name = '' ) {

		// Init return.
		$type = false;

		// if the key exists, return the value - which is the BuddyPress Type.
		if ( array_key_exists( $name, $this->website_fields ) ) {
			$type = $this->website_fields[ $name ];
		}

		// --<
		return $type;

	}

	/**
	 * Gets the mapped Website Field name.
	 *
	 * @since 0.5.2
	 *
	 * @param string $value The value of the BuddyPress Field setting.
	 * @return string $name The mapped Contact Field name.
	 */
	public function name_get( $value ) {

		// Init return.
		$name = '';

		// Bail if our prefix isn't there.
		if ( false === strpos( $value, $this->website_field_prefix ) ) {
			return $name;
		}

		// Get the mapped Contact Field name.
		$name = (string) str_replace( $this->website_field_prefix, '', $value );

		// --<
		return $name;

	}

}
