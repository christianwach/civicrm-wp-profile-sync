<?php
/**
 * CiviCRM Website Class.
 *
 * Handles CiviCRM Website functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Website Class.
 *
 * A class that encapsulates CiviCRM Website functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Website extends CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

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
	 * "CiviCRM Website" Field key in the ACF Field data.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $acf_field_key The key of the "CiviCRM Website" in the ACF Field data.
	 */
	public $acf_field_key = 'field_cacf_civicrm_website';

	/**
	 * "CiviCRM Field" Field value prefix in the ACF Field data.
	 *
	 * This distinguishes Website Fields from Custom Fields.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $website_field_prefix The prefix of the "CiviCRM Field" value.
	 */
	public $website_field_prefix = 'caiwebsite_';

	/**
	 * Contact Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $fields_handled The array of Contact Fields which must be handled separately.
	 */
	public $fields_handled = [
		'url',
	];

	/**
	 * Public Website Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $website_fields The array of public Website Fields.
	 */
	public $website_fields = [
		'url' => 'url',
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

		// Add any Website Fields attached to a Post.
		add_filter( 'cwps/acf/fields_get_for_post', [ $this, 'acf_fields_get_for_post' ], 10, 3 );

		// Add any Website Fields that are Custom Fields.
		add_filter( 'cwps/acf/contact/custom_field/id_get', [ $this, 'custom_field_id_get' ], 10, 2 );

		// Intercept Post created from Contact events.
		add_action( 'cwps/acf/post/contact_sync_to_post', [ $this, 'contact_sync_to_post' ], 10 );

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/acf/query_settings_field', [ $this, 'query_settings_field' ], 51, 3 );

		// Listen for queries from the ACF Bypass class.
		add_filter( 'cwps/acf/bypass/query_settings_choices', [ $this, 'query_bypass_settings_choices' ], 20, 4 );

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

		// Listen for events from our Mapper that require Website updates.
		add_action( 'cwps/acf/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		add_action( 'cwps/acf/mapper/website/delete/pre', [ $this, 'website_pre_delete' ], 10 );
		add_action( 'cwps/acf/mapper/website/created', [ $this, 'website_edited' ], 10 );
		add_action( 'cwps/acf/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		//add_action( 'cwps/acf/mapper/website/deleted', [ $this, 'website_deleted' ], 10 );

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
		remove_action( 'cwps/acf/mapper/website/edit/pre', [ $this, 'website_pre_edit' ], 10 );
		remove_action( 'cwps/acf/mapper/website/delete/pre', [ $this, 'website_pre_delete' ], 10 );
		remove_action( 'cwps/acf/mapper/website/created', [ $this, 'website_edited' ], 10 );
		remove_action( 'cwps/acf/mapper/website/edited', [ $this, 'website_edited' ], 10 );
		//remove_action( 'cwps/acf/mapper/website/deleted', [ $this, 'website_deleted' ], 10 );

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

			// Maybe update a Contact Field.
			$this->field_handled_update( $field, $value, $args['contact']['id'], $settings );

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
	 * @param array $field The ACF Field data.
	 * @param mixed $value The ACF Field value.
	 * @param integer $contact_id The numeric ID of the Contact.
	 * @param array $settings The ACF Field settings.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function field_handled_update( $field, $value, $contact_id, $settings ) {

		// Skip if it's not an ACF Field Type that this class handles.
		if ( ! in_array( $settings['type'], $this->fields_handled ) ) {
			return true;
		}

		// Get the "CiviCRM Website" key.
		$website_key = $this->acf_field_key_get();

		// Skip if we don't have a synced Website.
		if ( empty( $settings[ $website_key ] ) ) {
			return true;
		}

		// Skip if it maps to a Custom Field.
		if ( false !== strpos( $settings[ $website_key ], $this->civicrm->custom_field_prefix() ) ) {
			return true;
		}

		// Parse value by Field Type.
		$value = $this->acf_loader->acf->field->value_get_for_civicrm( $value, $settings['type'], $settings );

		// The ID of the Website Type is the setting.
		$website_type_id = $settings[ $website_key ];

		// Update the Website.
		$this->plugin->civicrm->website->update_for_contact( $website_type_id, $contact_id, $value );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for a Website.
	 *
	 * @since 0.4
	 *
	 * @param integer $website_id The numeric ID of the Website.
	 * @return array $website The array of Website data, or empty if none.
	 */
	public function website_get_by_id( $website_id ) {

		// Init return.
		$website = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $website_id,
		];

		// Get Website details via API.
		$result = civicrm_api( 'Website', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $website;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $website;
		}

		// The result set should contain only one item.
		$website = (object) array_pop( $result['values'] );

		// --<
		return $website;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Websites for a given Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $website_data The array of Website data for the CiviCRM Contact.
	 */
	public function websites_get_for_contact( $contact_id ) {

		// Init return.
		$website_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $website_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website_data;
		}

		// Define params to get queried Websites.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Website', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $website_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $website_data;
		}

		// The result set it what we want.
		$website_data = $result['values'];

		// --<
		return $website_data;

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

		// Get all Websites for this Contact.
		$data = $this->websites_get_for_contact( $args['objectId'] );

		// Bail if there are no Website Fields.
		if ( empty( $data ) ) {
			return;
		}

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $args['post_id'] );

		// Bail if there are no Website Fields.
		if ( empty( $acf_fields['website'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['website'] as $selector => $website_field ) {

			// Let's look at each Website in turn.
			foreach ( $data as $website ) {

				// Cast as object.
				$website = (object) $website;

				// Skip if it's a Custom Field.
				if ( false !== strpos( $website_field, $this->civicrm->custom_field_prefix() ) ) {
					continue;
				}

				// Skip if it's not the right Website Type.
				if ( $website_field != $website->website_type_id ) {
					continue;
				}

				// Get the URL safely.
				$url = empty( $website->url ) ? '' : $website->url;

				// Update it.
				$this->acf_loader->acf->field->value_update( $selector, $url, $args['post_id'] );

			}

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * A CiviCRM Contact's Website is about to be edited.
	 *
	 * Before a Website is edited, we need to store the previous data so that
	 * we can compare with the data after the edit. If there are changes, then
	 * we will need to update accordingly.
	 *
	 * This is not required for Website creation or deletion.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_edit( $args ) {

		// Always clear properties if set previously.
		if ( isset( $this->website_pre ) ) {
			unset( $this->website_pre );
		}

		// We need a Contact ID in the edited Website.
		$website = $args['objectRef'];
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Grab the previous Website data from the database.
		$this->website_pre = $this->website_get_by_id( $website->id );

	}



	/**
	 * Intercept when a CiviCRM Website has been updated.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_edited( $args ) {

		// Bail if this is not a Contact's Website.
		$website = $args['objectRef'];
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Check previous to see if its Website Type has changed.
		$website_type_changed = false;
		if ( ! empty( $this->website_pre ) ) {
			if ( (int) $this->website_pre->website_type_id !== (int) $website->website_type_id ) {
				$website_type_changed = true;
				// Make a clone so we don't overwrite the Website Pre object.
				$previous = clone $this->website_pre;
				$previous->url = '';
			}
		}

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $website->contact_id );

		// Test if any of this Contact's Contact Types is mapped.
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

				// Maybe clear the previous Field.
				if ( $website_type_changed ) {
					// Skip previous if it has already been changed.
					if ( empty( $this->previously_edited[ $previous->website_type_id ] ) ) {
						$this->fields_update( $post_id, $previous );
					}
				}

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $website );

				// Keep a clone of the Website object for future reference.
				$this->previously_edited[ $website->website_type_id ] = clone $website;

			}

		}

		/**
		 * Broadcast that a Website ACF Field may have been edited.
		 *
		 * @since 0.4
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $website The CiviCRM Website object.
		 */
		do_action( 'cwps/acf/website/updated', $contact, $website );

	}



	/**
	 * Intercept when a CiviCRM Website is about to be deleted.
	 *
	 * @since 0.5.2
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function website_pre_delete( $args ) {

		// Get the full existing Website data.
		$website = (object) $this->plugin->civicrm->website->get_by_id( $args['objectId'] );
		if ( empty( $website->contact_id ) ) {
			return;
		}

		// Skip deleting if it has already been written to.
		if ( ! empty( $this->previously_edited[ $website->website_type_id ] ) ) {
			return;
		}

		// Save a copy of the URL just in case.
		$website->deleted_url = ! empty( $website->url ) ? $website->url : '';

		// Clear URL to clear the ACF Field.
		$website->url = '';

		// Get the Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $website->contact_id );

		// Test if any of this Contact's Contact Types is mapped.
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

				// Update the ACF Fields for this Post.
				$this->fields_update( $post_id, $website );

			}

		}

		/**
		 * Broadcast that a Website ACF Field may have been deleted.
		 *
		 * @since 0.5.2
		 *
		 * @param array $contact The array of CiviCRM Contact data.
		 * @param object $website The CiviCRM Website object.
		 */
		do_action( 'cwps/acf/website/deleted', $contact, $website );

	}



	/**
	 * Update Website ACF Fields on an Entity mapped to a Contact ID.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @param object $website The CiviCRM Website Record object.
	 */
	public function fields_update( $post_id, $website ) {

		// Get the ACF Fields for this Post.
		$acf_fields = $this->acf_loader->acf->field->fields_get_for_post( $post_id );

		// Bail if there are no Website Fields.
		if ( empty( $acf_fields['website'] ) ) {
			return;
		}

		// Let's look at each ACF Field in turn.
		foreach ( $acf_fields['website'] as $selector => $website_field ) {

			// Skip if it's a Custom Field.
			if ( false !== strpos( $website_field, $this->civicrm->custom_field_prefix() ) ) {
				continue;
			}

			// Skip if it's not the right Website Type.
			if ( $website_field != $website->website_type_id ) {
				continue;
			}

			// Update it.
			$this->acf_loader->acf->field->value_update( $selector, $website->url, $post_id );

		}

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Website Fields.
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
		$result = civicrm_api( 'Website', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Website Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->website_fields ) ) {
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
	 * Filter the Custom Field ID.
	 *
	 * Some Website Fields may be mapped to CiviCRM Custom Fields. This filter
	 * teases out which ones and, if they are mapped to a Custom Field, returns
	 * their Custom Field ID.
	 *
	 * @since 0.4
	 *
	 * @param integer $custom_field_id The existing Custom Field ID.
	 * @param array $field The array of ACF Field data.
	 * @return integer $custom_field_id The modified Custom Field ID.
	 */
	public function custom_field_id_get( $custom_field_id, $field ) {

		// Get the "CiviCRM Website" key.
		$website_key = $this->acf_field_key_get();

		// Return it if the Field has a reference to a Website Custom Field.
		if ( ! empty( $field[ $website_key ] ) ) {
			if ( false !== strpos( $field[ $website_key ], $this->civicrm->custom_field_prefix() ) ) {
				$custom_field_id = absint( str_replace( $this->civicrm->custom_field_prefix(), '', $field[ $website_key ] ) );
			}
		}

		// --<
		return $custom_field_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Website Types that can be mapped to an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array $website_types The array of possible Website Types.
	 */
	public function get_for_acf_field( $field, $field_group = [] ) {

		// Init return.
		$website_types = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $website_types;
		}

		// Get the Website Types array.
		$website_type_ids = CRM_Core_PseudoConstant::get( 'CRM_Core_DAO_Website', 'website_type_id' );

		// Bail if there are no results.
		if ( empty( $website_type_ids ) ) {
			return $website_types;
		}

		/**
		 * Filter the Website Types.
		 *
		 * @since 0.5
		 *
		 * @param array $website_type_ids The array of CiviCRM Website Types.
		 * @param array $field The ACF Field data array.
		 * @param array $field_group The ACF Field Group data array.
		 */
		$website_types = apply_filters( 'cwps/acf/website/website_types', $website_type_ids, $field, $field_group );

		// --<
		return $website_types;

	}



	/**
	 * Return the "CiviCRM Website" ACF Settings Field.
	 *
	 * @since 0.4
	 *
	 * @param array $custom_fields The Custom Fields to populate the ACF Field with.
	 * @param array $website_types The Website Types to populate the ACF Field with.
	 * @return array $field The ACF Field data array.
	 */
	public function acf_field_get( $custom_fields = [], $website_types = [] ) {

		// Bail if empty.
		if ( empty( $custom_fields ) && empty( $website_types ) ) {
			return;
		}

		// Build choices array for dropdown.
		$choices = [];

		// Build Website Types choices array for dropdown.
		if ( ! empty( $website_types ) ) {
			$website_types_label = esc_attr__( 'Contact Website Type', 'civicrm-wp-profile-sync' );
			foreach ( $website_types as $website_type_id => $website_type_name ) {
				$choices[ $website_types_label ][ $website_type_id ] = esc_attr( $website_type_name );
			}
		}

		// Build Custom Field choices array for dropdown.
		foreach ( $custom_fields as $custom_group_name => $custom_group ) {
			$custom_fields_label = esc_attr( $custom_group_name );
			foreach ( $custom_group as $custom_field ) {
				$choices[ $custom_fields_label ][ $this->civicrm->custom_field_prefix() . $custom_field['id'] ] = $custom_field['label'];
			}
		}

		// Define Field.
		$field = [
			'key' => $this->acf_field_key_get(),
			'label' => __( 'CiviCRM Website', 'civicrm-wp-profile-sync' ),
			'name' => $this->acf_field_key_get(),
			'type' => 'select',
			'instructions' => __( 'Choose the CiviCRM Website Field that this ACF Field should sync with. (Optional)', 'civicrm-wp-profile-sync' ),
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
	 * Getter method for the "CiviCRM Website" key.
	 *
	 * @since 0.4
	 *
	 * @return string $acf_field_key The key of the "CiviCRM Website" in the ACF Field data.
	 */
	public function acf_field_key_get() {

		// --<
		return $this->acf_field_key;

	}



	/**
	 * Add any Website Fields that are attached to a Post.
	 *
	 * @since 0.4
	 *
	 * @param array $acf_fields The existing ACF Fields array.
	 * @param array $field The ACF Field.
	 * @param integer $post_id The numeric ID of the WordPress Post.
	 * @return array $acf_fields The modified ACF Fields array.
	 */
	public function acf_fields_get_for_post( $acf_fields, $field, $post_id ) {

		// Get the "CiviCRM Website" key.
		$website_key = $this->acf_field_key_get();

		// Add if it has a reference to a Website Field.
		if ( ! empty( $field[ $website_key ] ) ) {
			$acf_fields['website'][ $field['name'] ] = $field[ $website_key ];
		}

		// --<
		return $acf_fields;

	}



	/**
	 * Returns a Setting Field for an ACF "URL" Field when found.
	 *
	 * The CiviCRM "Website Types" can only be attached to a Contact. This means
	 * they can only be shown on a "Contact Field Group" or a "User Field Group"
	 * in ACF.
	 *
	 * @since 0.5
	 *
	 * @param array $setting_field The existing Setting Field array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @param bool $skip_check True if the check for Field Group should be skipped. Default false.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_settings_field( $setting_field, $field, $field_group, $skip_check = false ) {

		// Pass if this is not our Field Type.
		if ( 'url' !== $field['type'] ) {
			return $setting_field;
		}

		// Assume Website Fields are not required.
		$website_fields = [];

		// Check if this is a Contact Field Group or a User Field Group.
		$is_contact_field_group = $this->civicrm->contact->is_contact_field_group( $field_group );
		$is_user_field_group = $this->acf_loader->user->is_user_field_group( $field_group );
		if ( ! empty( $is_contact_field_group ) || ! empty( $is_user_field_group ) ) {

			// The Website Fields for this ACF Field are needed.
			$website_fields = $this->get_for_acf_field( $field );

			// Maybe exclude the synced "WordPress User Profile" Website Type.
			if ( ! empty( $is_user_field_group ) ) {
				$website_type_id = (int) $this->plugin->admin->setting_get( 'user_profile_website_type', 0 );
				if ( $website_type_id > 0 && isset( $website_fields[ $website_type_id ] ) ) {
					unset( $website_fields[ $website_type_id ] );
				}
			}

		}

		// Get the Custom Fields for this Field Type.
		$custom_fields = $this->civicrm->custom_field->get_for_acf_field( $field );

		/**
		 * Filter the Custom Fields.
		 *
		 * @since 0.5
		 *
		 * @param array The initially empty array of filtered Custom Fields.
		 * @param array $custom_fields The CiviCRM Custom Fields array.
		 * @param array $field The ACF Field data array.
		 */
		$filtered_fields = apply_filters( 'cwps/acf/query_settings/custom_fields_filter', [], $custom_fields, $field );

		// Pass if not populated.
		if ( empty( $website_fields ) && empty( $filtered_fields ) ) {
			return $setting_field;
		}

		// Get the Setting Field.
		$setting_field = $this->acf_field_get( $filtered_fields, $website_fields );

		// Return populated array.
		return $setting_field;

	}



	// -------------------------------------------------------------------------



	/**
	 * Appends an array of Setting Field choices for a Bypass ACF Field Group when found.
	 *
	 * The Website Entity cannot have Custom Fields attached to it, so we can skip
	 * that part of the logic.
	 *
	 * In fact, this isn't really necessary at all - all the logic is handled in
	 * the ACFE Form Action - but it might be confusing for people if they are
	 * unable to map some ACF Fields when they're building a Field Group for an
	 * ACFE Form... so here it is *shrug*
	 *
	 * To disable, comment out the "cwps/acf/bypass/query_settings_choices" filter.
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
			if ( $field['type'] == $this->website_fields[ $value['name'] ] ) {
				$fields_for_entity[] = $value;
			}
		}

		// Pass if not populated.
		if ( empty( $fields_for_entity ) ) {
			return $choices;
		}

		// Build Website Field choices array for dropdown.
		$website_fields_label = esc_attr__( 'Website Fields', 'civicrm-wp-profile-sync' );
		foreach ( $fields_for_entity as $website_field ) {
			$choices[ $website_fields_label ][ $this->website_field_prefix . $website_field['name'] ] = $website_field['title'];
		}

		/**
		 * Filter the choices to display in the "CiviCRM Field" select.
		 *
		 * @since 0.5
		 *
		 * @param array $choices The choices for the Setting Field array.
		 */
		$choices = apply_filters( 'cwps/acf/civicrm/website/civicrm_field/choices', $choices );

		// Return populated array.
		return $choices;

	}



} // Class ends.



