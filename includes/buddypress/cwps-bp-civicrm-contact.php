<?php
/**
 * BuddyPress CiviCRM Contact Field Class.
 *
 * Handles BuddyPress CiviCRM Contact Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync BuddyPress CiviCRM Contact Field Class.
 *
 * A class that encapsulates BuddyPress CiviCRM Contact Field functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_CiviCRM_Contact {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * BuddyPress Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $bp_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * BuddyPress xProfile object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $xprofile;

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

		// Listen for updates to mapped BuddyPress xProfile Fields.
		add_action( 'cwps/bp/xprofile/fields_edited', [ $this, 'bp_fields_edited' ], 50 );

		// Hook into CiviCRM-to-WordPress Contact sync process.
		add_action( 'cwps/civicrm/contact/contact_sync/post', [ $this, 'contact_synced' ], 50 );

		// Listen for queries from the Custom Field class.
		add_filter( 'cwps/bp/query_user_id', [ $this, 'query_user_id' ], 10, 2 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Fires when a BuddyPress xProfile "Profile Group" with mapped Fields has been updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of params.
	 */
	public function bp_fields_edited( $args ) {

		// Prepare the CiviCRM Contact data.
		$contact_data = $this->prepare_from_fields( $args['field_data'] );

		// Add the Contact ID.
		$contact_data['id'] = $args['contact_id'];

		// Update the Contact.
		$contact = $this->plugin->civicrm->contact->update( $contact_data );

		// Overwrite the params with our data.
		$args['contact_id'] = $contact['id'];
		$args['contact'] = $contact;

		/**
		 * Broadcast that a Contact has been updated when a set of BuddyPress Fields were saved.
		 *
		 * Used internally by:
		 *
		 * * CiviCRM_Profile_Sync_BP_CiviCRM_Address::bp_fields_edited()
		 * * CiviCRM_Profile_Sync_BP_CiviCRM_Phone::bp_fields_edited()
		 * * CiviCRM_Profile_Sync_BP_CiviCRM_Website::bp_fields_edited()
		 *
		 * @since 0.5
		 *
		 * @param array $args The updated array of WordPress params.
		 */
		do_action( 'cwps/bp/contact/bp_fields_edited', $args );

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

		// Init data for Fields.
		$contact_data = [];

		// Handle the data for each Field.
		foreach ( $field_data as $data ) {

			// Get metadata for this xProfile Field.
			$meta = $data['meta'];
			if ( empty( $meta ) ) {
				continue;
			}

			// Skip if it's not a "Contact" xProfile Field.
			if ( empty( $meta['entity_type'] ) || $meta['entity_type'] !== 'Contact' ) {
				continue;
			}

			// Get the CiviCRM Custom Field and Contact Field.
			$custom_field_id = $this->xprofile->custom_field->id_get( $meta['value'] );
			$contact_field_name = $this->xprofile->contact_field->name_get( $meta['value'] );

			// Do we have a synced Custom Field or Contact Field?
			if ( ! empty( $custom_field_id ) || ! empty( $contact_field_name ) ) {

				// If it's a Custom Field.
				if ( ! empty( $custom_field_id ) ) {

					// Build Custom Field code.
					$code = 'custom_' . (string) $custom_field_id;

				} elseif ( ! empty( $contact_field_name ) ) {

					// The Contact Field code is the setting.
					$code = $contact_field_name;

				}

				// Build args for value conversion.
				$args = [
					'entity_type' => $meta['entity_type'],
					'custom_field_id' => $custom_field_id,
					'contact_field_name' => $contact_field_name,
				];

				// Parse value by Field Type.
				$value = $this->xprofile->value_get_for_civicrm( $data['value'], $data['field_type'], $args );

				// Add it to the Field data.
				$contact_data[ $code ] = $value;

			}

		}

		// --<
		return $contact_data;

	}

	// -------------------------------------------------------------------------

	/**
	 * Populate the BuddyPress xProfile Fields.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_synced( $args ) {

		// Bail if BuddyPress is not set to sync to WordPress.
		if ( bp_disable_profile_sync() ) {
			return;
		}

		// Build params by which to query xProfile.
		$query = [
			'user_id' => $args['user_id'],
			'hide_empty_groups' => false,
			'hide_empty_fields' => false,
		];

		// Bail if the User has no BuddyPress Profile.
		if ( ! bp_has_profile( $query ) ) {
			return;
		}

		// Do the Profile Loop.
		while ( bp_profile_groups() ) {
			bp_the_profile_group();

			global $profile_template;

			// Do the Profile Fields Loop.
			while ( bp_profile_fields() ) {
				bp_the_profile_field();

				global $field;

				$field_id = bp_get_the_profile_field_id();

				// Skip if not mapped.
				$field_meta = $this->xprofile->get_metadata_all( $field_id );
				if ( empty( $field_meta ) ) {
					continue;
				}

				// Only handle Contact data.
				if ( $field_meta['entity_type'] !== 'Contact' ) {
					continue;
				}

				// Build an array of params.
				$params = [
					'field' => $field,
					'field_id' => $field_id,
					'field_meta' => $field_meta,
				];

				/**
				 * Broadcast that a mapped Contact Field needs to be synced to BuddyPress.
				 *
				 * Used internally by:
				 *
				 * * BuddyPress CiviCRM Contact Field
				 *
				 * @since 0.5
				 *
				 * @param $params The array of Field params.
				 * @param $args The array of arguments from the Mapper.
				 */
				do_action( 'cwps/buddypress/contact/field/sync', $params, $args );

			}

		}

	}

	// -------------------------------------------------------------------------

	/**
	 * Listen for queries from the Custom Field class.
	 *
	 * This method responds with a User ID if it detects that the set of Custom
	 * Fields maps to a Contact.
	 *
	 * @since 0.5
	 *
	 * @param array|bool $user_id The existing User ID.
	 * @param array      $args The array of CiviCRM Custom Fields params.
	 * @return array|bool $user_id The User ID, or false if not mapped.
	 */
	public function query_user_id( $user_id, $args ) {

		// Init Contact ID.
		$contact_id = false;

		// Let's tease out the context from the Custom Field data.
		foreach ( $args['custom_fields'] as $field ) {

			// Skip if it is not attached to an Contact.
			if ( $field['entity_table'] != 'civicrm_contact' ) {
				continue;
			}

			// Grab the Contact ID.
			$contact_id = (int) $field['entity_id'];

			// We can bail now that we know.
			break;

		}

		// Bail if there's no Contact ID.
		if ( $contact_id === false ) {
			return $user_id;
		}

		// Bail if this Contact doesn't have a User ID.
		$user_id = $this->plugin->mapper->ufmatch->user_id_get_by_contact_id( $contact_id );
		if ( $user_id === false ) {
			return $user_id;
		}

		// --<
		return $user_id;

	}

}
