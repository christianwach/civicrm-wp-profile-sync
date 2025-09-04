<?php
/**
 * BuddyPress xProfile Class.
 *
 * Handles BuddyPress xProfile functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync BuddyPress xProfile Class.
 *
 * A class that encapsulates BuddyPress xProfile functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_BP_XProfile {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * BuddyPress Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_BuddyPress
	 */
	public $bp_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_CiviCRM
	 */
	public $civicrm;

	/**
	 * CiviCRM Contact object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_Profile_Sync_BP_CiviCRM_Contact
	 */
	public $contact;

	/**
	 * CiviCRM Contact Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_Profile_Sync_BP_CiviCRM_Contact_Field
	 */
	public $contact_field;

	/**
	 * CiviCRM Custom Field object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_Profile_Sync_BP_CiviCRM_Custom_Field
	 */
	public $custom_field;

	/**
	 * CiviCRM Address object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_Profile_Sync_BP_CiviCRM_Address
	 */
	public $address;

	/**
	 * CiviCRM Phone object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_Profile_Sync_BP_CiviCRM_Phone
	 */
	public $phone;

	/**
	 * CiviCRM Website object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_Profile_Sync_BP_CiviCRM_Website
	 */
	public $website;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool
	 */
	public $mapper_hooks = false;

	/**
	 * Settings Field meta key.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $meta_key = 'cwps_mapping';

	/**
	 * Settings Field Entity Types array.
	 *
	 * The keys in this array correspond directly to those used in CiviCRM and
	 * are called "class names" because they are used to construct actual class
	 * names to call their methods as well as being the "object" of API calls.
	 *
	 * Populated in constructor to allow translation of labels.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $entity_types = [];

	/**
	 * Entity Type Settings Field name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $entity_type = 'cwps_civicrm_entity_type';

	/**
	 * Location Type Settings Field name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $location_type_id = 'cwps_civicrm_location_type';

	/**
	 * Phone Type Settings Field name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $phone_type_id = 'cwps_civicrm_phone_type';

	/**
	 * Website Type Settings Field name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $website_type_id = 'cwps_civicrm_website_type';

	/**
	 * Top Level Contact Type Settings Field name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $contact_type_id = 'cwps_civicrm_contact_type';

	/**
	 * Contact Sub-type Settings Field name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $contact_subtype_id = 'cwps_civicrm_contact_subtype';

	/**
	 * CiviCRM Field Settings Field name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $name = 'cwps_civicrm_field';

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $bp_loader The BuddyPress Loader object.
	 */
	public function __construct( $bp_loader ) {

		// Store references to objects.
		$this->plugin    = $bp_loader->plugin;
		$this->bp_loader = $bp_loader;
		$this->civicrm   = $bp_loader->plugin->civicrm;

		// Build Entity Type labels and enable translation.
		$this->entity_types = [
			'Contact' => __( 'Contact', 'civicrm-wp-profile-sync' ),
			'Address' => __( 'Address', 'civicrm-wp-profile-sync' ),
			'Phone'   => __( 'Phone', 'civicrm-wp-profile-sync' ),
			'Website' => __( 'Website', 'civicrm-wp-profile-sync' ),
		];

		// Init when the BuddyPress object is loaded.
		add_action( 'cwps/buddypress/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Always register plugin hooks.
		add_action( 'cwps/plugin/hooks/bp/add', [ $this, 'register_mapper_hooks' ] );
		add_action( 'cwps/plugin/hooks/bp/remove', [ $this, 'unregister_mapper_hooks' ] );

		// Register hooks.
		$this->register_hooks();

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.5
		 */
		do_action( 'cwps/buddypress/field/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-civicrm-contact.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-civicrm-contact-field.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-civicrm-custom-field.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-civicrm-address.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-civicrm-phone.php';
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/buddypress/cwps-bp-civicrm-website.php';

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.5
	 */
	public function setup_objects() {

		// Init objects.
		$this->contact       = new CiviCRM_Profile_Sync_BP_CiviCRM_Contact( $this );
		$this->contact_field = new CiviCRM_Profile_Sync_BP_CiviCRM_Contact_Field( $this );
		$this->custom_field  = new CiviCRM_Profile_Sync_BP_CiviCRM_Custom_Field( $this );
		$this->address       = new CiviCRM_Profile_Sync_BP_CiviCRM_Address( $this );
		$this->phone         = new CiviCRM_Profile_Sync_BP_CiviCRM_Phone( $this );
		$this->website       = new CiviCRM_Profile_Sync_BP_CiviCRM_Website( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Modify the xProfile Field display and its User values.

		// Filter the output of an xProfile Field.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_action( 'xprofile_get_field_data', [ $this, 'data_get' ], 10, 3 );

		// Filter the xProfile Field options when displaying the Field.
		add_action( 'bp_xprofile_field_get_children', [ $this, 'get_children' ], 10, 3 );

		// BuddyPress uses the "name/label" as the "value" when saving. Needs to be the "ID" instead.
		add_filter( 'bp_get_the_profile_field_options_checkbox', [ $this, 'options_checkbox' ], 10, 5 );
		add_filter( 'bp_get_the_profile_field_options_select', [ $this, 'options_select' ], 10, 5 );
		add_filter( 'bp_get_the_profile_field_options_multiselect', [ $this, 'options_multiselect' ], 10, 5 );
		add_filter( 'bp_get_the_profile_field_options_radio', [ $this, 'options_radio' ], 10, 5 );

		// This means "tricking" BuddyPress into validating these Fields.
		add_filter( 'bp_xprofile_set_field_data_pre_validate', [ $this, 'pre_validate' ], 10, 3 );

		// Modify the xProfile Field setup.

		// xProfile admin template hooks.
		add_action( 'xprofile_field_after_contentbox', [ $this, 'metabox_render' ], 10 );

		// Add Javascript after BuddyPress does.
		add_action( 'bp_admin_enqueue_scripts', [ $this, 'enqueue_js' ], 10 );

		// Modify the xProfile Field when it is saved.
		add_action( 'xprofile_field_options_before_save', [ $this, 'options_before_save' ], 10, 2 );

		// Capture our metadata when the xProfile Field when it is saved.
		add_action( 'xprofile_fields_saved_field', [ $this, 'saved_field' ], 10 );

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

	}

	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.5
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( true === $this->mapper_hooks ) {
			return;
		}

		// Listen for events from our Mapper that require Contact updates.
		add_action( 'cwps/mapper/bp_xprofile/edited', [ $this, 'fields_edited' ], 50 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_action( 'cwps/mapper/bp_field/edited', [ $this, 'field_edited' ], 50 );

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.5
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( false === $this->mapper_hooks ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/mapper/bp_xprofile/edited', [ $this, 'fields_edited' ], 50 );
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// remove_action( 'cwps/mapper/bp_field/edited', [ $this, 'field_edited' ], 50 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Called when a BuddyPress xProfile "Profile Group" has been updated.
	 *
	 * This callback is hooked in after the "core" methods of this plugin have
	 * done their thing - so a Contact will definitely exist by the time this
	 * method is called.
	 *
	 * The "core" methods will have handled the Fields that map to the built-in
	 * WordPress User Fields - so all that is left are the xProfile Fields that
	 * have been specifically mapped in their settings.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of BuddyPress params.
	 */
	public function fields_edited( $args ) {

		// Bail if there are no CiviCRM References.
		if ( empty( $this->civicrm_ref ) ) {
			return;
		}

		// Bail if this User doesn't have a Contact.
		$contact = $this->plugin->mapper->ufmatch->contact_get_by_user_id( $args['user_id'] );
		if ( false === $contact ) {
			return;
		}

		// Add our Field data to the params.
		$args['field_data'] = $this->civicrm_ref;
		$args['contact_id'] = $contact['id'];
		$args['contact']    = $contact;

		/**
		 * Broadcast that a set of mapped BuddyPress Fields were saved.
		 *
		 * Used internally by:
		 *
		 * * BuddyPress CiviCRM Contact
		 *
		 * @since 0.5
		 *
		 * @param array $args The array of params.
		 */
		do_action( 'cwps/bp/xprofile/fields_edited', $args );

	}

	/**
	 * Fires when a BuddyPress xProfile Field has been updated.
	 *
	 * @since 0.5
	 *
	 * @param array $args The array of BuddyPress params.
	 */
	public function field_edited( $args ) {

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'args' => $args,
			'civicrm_ref' => $this->civicrm_ref,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	/**
	 * Gets the raw value of a BuddyPress xProfile Field.
	 *
	 * The value will, however, be unserialised when necessary.
	 *
	 * @since 0.5
	 *
	 * @param object $field The BuddyPress Field object.
	 * @return mixed $value The raw BuddyPress Field value, or null if not present.
	 */
	public function field_get_value_raw( $field ) {

		// Init as null.
		$value = null;

		// Maybe override with the possibly unserialised value if present.
		if ( isset( $field->data->value ) ) {
			$value = maybe_unserialize( $field->data->value );
		}

		// --<
		return $value;

	}

	/**
	 * Gets the full set of BuddyPress xProfile Fields for a given User.
	 *
	 * @since 0.5
	 *
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @return array $fields The full set of BuddyPress xProfile Fields.
	 */
	public function fields_get_for_user( $user_id ) {

		/*
		// TODO: Only do this once per User?
		static $pseudocache;
		if ( isset( $pseudocache[ $user_id ] ) ) {
			//return $pseudocache[ $user_id ];
		}
		*/

		// Init return.
		$fields = [];

		// Build params by which to query xProfile.
		$query = [
			'user_id'           => $user_id,
			'hide_empty_groups' => false,
			'hide_empty_fields' => false,
		];

		// If the User has a BuddyPress Profile.
		if ( bp_has_profile( $query ) ) {

			// Do the Profile Loop.
			while ( bp_profile_groups() ) {
				bp_the_profile_group();
				while ( bp_profile_fields() ) {
					bp_the_profile_field();

					global $field;

					$field_id = bp_get_the_profile_field_id();

					// Skip if not mapped.
					$field_meta = $this->get_metadata_all( $field_id );
					if ( empty( $field_meta ) ) {
						continue;
					}

					// Add to the return array.
					$fields[] = [
						'field_id'   => $field_id,
						'field'      => $field,
						'field_meta' => $field_meta,
					];

				}
			}

		}

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $user_id ] ) ) {
			//$pseudocache[ $user_id ] = $fields;
		}
		*/

		// --<
		return $fields;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Update the value of a BuddyPress xProfile Field.
	 *
	 * @since 0.5
	 *
	 * @param string  $field_id The numeric ID of the BuddyPress Field.
	 * @param integer $user_id The numeric ID of the WordPress User.
	 * @param mixed   $value The value to save in the database.
	 * @return bool $result True if update is successful, false otherwise.
	 */
	public function value_update( $field_id, $user_id, $value ) {

		// Protect against (string) 'null' which CiviCRM uses for some reason.
		if ( 'null' === $value || 'NULL' === $value ) {
			$value = '';
		}

		// Pass through to BuddyPress.
		$result = xprofile_set_field_data( $field_id, $user_id, $value );

		// --<
		return $result;

	}

	/**
	 * Get the value of a BuddyPress Field formatted for CiviCRM.
	 *
	 * @since 0.5
	 *
	 * @param mixed  $value The BuddyPress Field value.
	 * @param string $field_type The BuddyPress Field type.
	 * @param array  $args Any additional arguments.
	 * @return mixed $value The value formatted for CiviCRM.
	 */
	public function value_get_for_civicrm( $value, $field_type, $args = [] ) {

		// Always unslash.
		$value = wp_unslash( $value );

		// Set appropriate value per Field type.
		switch ( $field_type ) {

			// Parse the value of a "Date" Field.
			case 'datebox':
				$value = $this->date_value_get_for_civicrm( $value, $args );
				break;

			// Parse the value of a "Textarea" Field.
			case 'textarea':
				$value = $this->textarea_value_get_for_civicrm( $value, $args );
				break;

			// Parse the value of a "Checkbox" Field.
			case 'checkbox':
				$value = $this->checkbox_value_get_for_civicrm( $value, $args );
				break;

			// Other Field types may require parsing - add them here.

		}

		// The CiviCRM API does not like empty arrays so assign empty string.
		if ( is_array( $value ) && empty( $value ) ) {
			$value = '';
		}

		// --<
		return $value;

	}

	/**
	 * Get the value of a "Date" Field formatted for CiviCRM.
	 *
	 * @since 0.5
	 *
	 * @param string $value The existing Field value.
	 * @param array  $args Any additional arguments.
	 * @return string $value The modified value for CiviCRM.
	 */
	public function date_value_get_for_civicrm( $value, $args ) {

		// Init format.
		$format = '';

		// BuddyPress saves in Y-m-d H:i:s format.
		$datetime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );

		// Check if it's a Contact Field date.
		if ( ! empty( $args['contact_field_name'] ) ) {
			$format = $this->contact_field->date_format_get_from_civicrm( $args['contact_field_name'] );
		}

		// Check if it's a Custom Field date.
		if ( ! empty( $args['custom_field_id'] ) ) {
			$format = $this->custom_field->date_format_get_from_civicrm( $args['custom_field_id'] );
		}

		// Convert to CiviCRM format.
		$value = $datetime->format( $format );

		// --<
		return $value;

	}

	/**
	 * Get the value of a "Textarea" Field formatted for CiviCRM.
	 *
	 * @since 0.5
	 *
	 * @param string $value The existing Field value.
	 * @param array  $args Any additional arguments.
	 * @return string $value The modified value for CiviCRM.
	 */
	public function textarea_value_get_for_civicrm( $value, $args ) {

		// Convert to full HTML.
		$value = wptexturize( $value );
		$value = convert_chars( $value );
		$value = wpautop( $value );
		$value = force_balance_tags( $value );

		// --<
		return $value;

	}

	/**
	 * Get the value of a "Checkbox" Field formatted for CiviCRM.
	 *
	 * Some Fields of type "Checkbox" are actually "True/False" Fields which
	 * need a populated array replaced with a numeric '1'.
	 *
	 * @since 0.5
	 *
	 * @param string $value The existing Field value.
	 * @param array  $args Any additional arguments.
	 * @return string $value The modified value for CiviCRM.
	 */
	public function checkbox_value_get_for_civicrm( $value, $args ) {

		// Bail if empty since this is an allowed value.
		if ( empty( $value ) ) {
			return $value;
		}

		/**
		 * Query whether this Field is a "True/False" Field.
		 *
		 * @since 0.5
		 *
		 * @param bool True if "Checkbox" is actually a "True/False" Field. False by default.
		 * @param array $args The arguments.
		 */
		$true_false = apply_filters( 'cwps/bp/xprofile/value/checkbox/query_type', false, $args );

		// Overwrite if this is a "True/False" Field.
		if ( true === $true_false ) {
			$value = 1;
		}

		// --<
		return $value;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Filters the HTML output for an xProfile Field options checkbox button.
	 *
	 * @since 0.5
	 *
	 * @param string  $new_html Label and checkbox input Field.
	 * @param object  $value The current option being rendered for.
	 * @param integer $id The ID of the Field object being rendered.
	 * @param string  $selected The current selected value.
	 * @param string  $k The current index in the foreach loop.
	 */
	public function options_checkbox( $new_html, $value, $id, $selected, $k ) {

		// Bail if there's no CiviCRM value.
		if ( empty( $value->civicrm_value ) ) {
			return $new_html;
		}

		// $new_html, $options[$k], $this->field_obj->id, $selected, $k
		$new_html = sprintf(
			'<label for="%3$s" class="option-label"><input %1$s type="checkbox" name="%2$s" id="%3$s" value="%4$s">%5$s</label>',
			$selected,
			esc_attr( bp_get_the_profile_field_input_name() . '[]' ),
			esc_attr( "option_{$value->id}" ),
			esc_attr( stripslashes( $value->civicrm_value ) ),
			esc_html( stripslashes( $value->name ) )
		);

		// --<
		return $new_html;

	}

	/**
	 * Filters the HTML output for an xProfile Field options select button.
	 *
	 * @since 0.5
	 *
	 * @param string  $new_html Label and select input Field.
	 * @param object  $value The current option being rendered for.
	 * @param integer $id The ID of the Field object being rendered.
	 * @param string  $selected The current selected value.
	 * @param string  $k The current index in the foreach loop.
	 */
	public function options_select( $new_html, $value, $id, $selected, $k ) {

		// Bail if there's no CiviCRM value.
		if ( empty( $value->civicrm_value ) ) {
			return $new_html;
		}

		// $new_html, $options[$k], $this->field_obj->id, $selected, $k
		$new_html = '<option' . $selected . ' value="' . esc_attr( stripslashes( $value->civicrm_value ) ) . '">' . esc_html( stripslashes( $value->name ) ) . '</option>';

		// --<
		return $new_html;

	}

	/**
	 * Filters the HTML output for an xProfile Field options multiselect button.
	 *
	 * @since 0.5
	 *
	 * @param string  $new_html Label and multiselect input Field.
	 * @param object  $value The current option being rendered for.
	 * @param integer $id The ID of the Field object being rendered.
	 * @param string  $selected The current selected value.
	 * @param string  $k The current index in the foreach loop.
	 */
	public function options_multiselect( $new_html, $value, $id, $selected, $k ) {

		// Bail if there's no CiviCRM value.
		if ( empty( $value->civicrm_value ) ) {
			return $new_html;
		}

		// $new_html, $options[$k], $this->field_obj->id, $selected, $k
		$new_html = '<option' . $selected . ' value="' . esc_attr( stripslashes( $value->civicrm_value ) ) . '">' . esc_html( stripslashes( $value->name ) ) . '</option>';

		// --<
		return $new_html;

	}

	/**
	 * Filters the HTML output for an xProfile Field options radio button.
	 *
	 * @since 0.5
	 *
	 * @param string  $new_html Label and radio input Field.
	 * @param object  $value The current option being rendered for.
	 * @param integer $id The ID of the Field object being rendered.
	 * @param string  $selected The current selected value.
	 * @param string  $k The current index in the foreach loop.
	 */
	public function options_radio( $new_html, $value, $id, $selected, $k ) {

		// Bail if there's no CiviCRM value.
		if ( empty( $value->civicrm_value ) ) {
			return $new_html;
		}

		// $new_html, $options[$k], $this->field_obj->id, $selected, $k
		$new_html = sprintf(
			'<label for="%3$s" class="option-label"><input %1$s type="radio" name="%2$s" id="%3$s" value="%4$s">%5$s</label>',
			$selected,
			esc_attr( bp_get_the_profile_field_input_name() ),
			esc_attr( "option_{$value->id}" ),
			esc_attr( stripslashes( $value->civicrm_value ) ),
			esc_html( stripslashes( $value->name ) )
		);

		// --<
		return $new_html;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Filter the raw submitted Profile Field value.
	 *
	 * We use this filter to modify the values submitted by users before
	 * doing field-type-specific validation.
	 *
	 * @since 0.5
	 *
	 * @param mixed                  $value The value passed to xprofile_set_field_data().
	 * @param BP_XProfile_Field      $field The Field object.
	 * @param BP_XProfile_Field_Type $field_type_obj The Field Type object.
	 * @return mixed $value The Field value.
	 */
	public function pre_validate( $value, $field, $field_type_obj ) {

		// Get metadata for this xProfile Field.
		$args = $this->get_metadata_all( $field );

		// Bail if there is none.
		if ( empty( $args ) ) {
			return $value;
		}

		// Bail if there is no value.
		if ( empty( $args['value'] ) ) {
			return $value;
		}

		/**
		 * Requests the mapped xProfile Field Options.
		 *
		 * @since 0.5
		 *
		 * @param array $options The empty array to be populated.
		 * @param array $field_type The type of xProfile Field.
		 * @param array $args The array of CiviCRM mapping data.
		 */
		$options = apply_filters( 'cwps/bp/field/query_options', [], $field->type, $args );

		// Bail if there are no Options.
		if ( empty( $options ) ) {
			// It is mapped, so add value.
			$field->civicrm_value = $value;
			$this->civicrm_ref[]  = [
				'field_id'   => $field->id,
				'field_type' => $field->type,
				'value'      => $value,
				'meta'       => $args,
			];
			return $value;
		}

		// Overwrite "value" to pass BuddyPress validation.
		if ( is_array( $value ) ) {
			$value_for_bp      = [];
			$value_for_civicrm = [];
			foreach ( $value as $item ) {
				if ( array_key_exists( $item, $options ) ) {
					$value_for_bp[]      = $options[ $item ];
					$value_for_civicrm[] = $item;
				}
			}
		} else {
			$value_for_bp      = 0;
			$value_for_civicrm = 0;
			if ( array_key_exists( $value, $options ) ) {
				$value_for_bp      = $options[ $value ];
				$value_for_civicrm = $value;
			}
		}

		// Always save the "real" CiviCRM value for later.
		$field->civicrm_value = $value_for_civicrm;
		$this->civicrm_ref[]  = [
			'field_id'   => $field->id,
			'field_type' => $field->type,
			'value'      => $value,
			'meta'       => $args,
		];

		// Now maybe overwrite the return.
		if ( ! empty( $value_for_bp ) ) {
			$value = $value_for_bp;
		}

		// --<
		return $value;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Fires when the children of a BuddyPress xProfile Field are read.
	 *
	 * @since 0.5
	 *
	 * @param object            $children Found children for a Field.
	 * @param bool              $for_editing Whether or not the Field is for editing.
	 * @param BP_XProfile_Field $field The xProfile Field object.
	 */
	public function get_children( $children, $for_editing, $field ) {

		/*
		// We only want to filter them on the Edit Field screen.
		if ( ! $for_editing ) {
			return $children;
		}
		*/

		// Get metadata for this xProfile Field.
		$args = $this->get_metadata_all( $field );

		// Bail if there are none.
		if ( empty( $args ) ) {
			return $children;
		}

		// Bail if there is no value.
		if ( empty( $args['value'] ) ) {
			return $children;
		}

		/**
		 * Requests the mapped xProfile Field Options.
		 *
		 * @since 0.5
		 *
		 * @param array $options The empty array to be populated.
		 * @param array $field_type The type of xProfile Field.
		 * @param array $args The array of CiviCRM mapping data.
		 */
		$options = apply_filters( 'cwps/bp/field/query_options', [], $field->type, $args );

		// Bail if there are no Options.
		if ( empty( $options ) ) {
			return $children;
		}

		// Add in the CiviCRM values.
		foreach ( $options as $id => $option ) {
			foreach ( $children as $child ) {
				if ( $child->name === $option ) {
					$child->civicrm_value = $id;
				}
			}
		}

		// --<
		return $children;

	}

	/**
	 * Fires when the options of a BuddyPress xProfile Field are filtered.
	 *
	 * @since 0.5
	 *
	 * @param array  $post_option The submitted options array. Need to check.
	 * @param string $field_type The type of xProfile Field.
	 */
	public function options_before_save( $post_option, $field_type ) {

		// Extract the Entity Type from our metabox.
		$entity_type = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST[ $this->entity_type ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$entity_type = wp_unslash( $_POST[ $this->entity_type ] );
		}

		// Bail if we don't have an Entity Type.
		if ( empty( $entity_type ) ) {
			return $post_option;
		}

		// Get data for the "Website" Entity Type.
		if ( 'Website' === $entity_type ) {

			// Extract the Website Type ID from our metabox.
			$website_type_id = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST[ $this->website_type_id ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$website_type_id = wp_unslash( $_POST[ $this->website_type_id ] );
			}

			// Build Entity data.
			$entity_data = [
				'website_type_id' => $website_type_id,
			];

		}

		// Get data for the "Address" and "Phone" Entity Types.
		if ( 'Address' === $entity_type || 'Phone' === $entity_type ) {

			// Extract the Location Type ID from our metabox.
			$location_type_id = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST[ $this->location_type_id ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$location_type_id = wp_unslash( $_POST[ $this->location_type_id ] );
			}

			// Build Entity data.
			$entity_data = [
				'location_type_id' => $location_type_id,
			];

		}

		// Get data for the "Contact" Entity Type.
		if ( 'Contact' === $entity_type ) {

			// Extract the Contact Type ID from our metabox.
			$contact_type_id = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST[ $this->contact_type_id ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$contact_type_id = wp_unslash( $_POST[ $this->contact_type_id ] );
			}

			// Extract the Contact Subtype ID from our metabox.
			$contact_subtype_id = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST[ $this->contact_subtype_id ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$contact_subtype_id = wp_unslash( $_POST[ $this->contact_subtype_id ] );
			}

			// Build Entity data.
			$entity_data = [
				'contact_type_id'    => $contact_type_id,
				'contact_subtype_id' => $contact_subtype_id,
			];

		}

		// Extract the value from our metabox.
		$value = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST[ $this->name ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$value = wp_unslash( $_POST[ $this->name ] );
		}

		// Bail if we don't have a value.
		if ( empty( $value ) ) {
			return $post_option;
		}

		// Let's make an array of the args.
		$args = [
			'entity_type' => $entity_type,
			'entity_data' => $entity_data,
			'value'       => $value,
		];

		/**
		 * Requests the mapped xProfile Field Options.
		 *
		 * @since 0.5
		 *
		 * @param array $options The empty array to be populated.
		 * @param array $field_type The type of xProfile Field.
		 * @param array $args The array of CiviCRM mapping data.
		 */
		$options = apply_filters( 'cwps/bp/field/query_options', [], $field_type, $args );

		// Maybe overwrite.
		if ( ! empty( $options ) ) {
			$post_option = $options;
		}

		// --<
		return $post_option;

	}

	/**
	 * Fires when a BuddyPress xProfile Field has been saved.
	 *
	 * @since 0.5
	 *
	 * @param BP_XProfile_Field $field The current xProfile Field object.
	 */
	public function saved_field( $field ) {

		// Extract the Entity Type from our metabox.
		$entity_type = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST[ $this->entity_type ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$entity_type = wp_unslash( $_POST[ $this->entity_type ] );
		}

		// Bail if we don't have an Entity Type.
		if ( empty( $entity_type ) ) {
			return;
		}

		// Init Entity data.
		$entity_data = [];

		// Get data for the "Contact" Entity Type.
		if ( 'Contact' === $entity_type ) {

			// Extract the Contact Type ID from our metabox.
			$contact_type_id = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST[ $this->contact_type_id ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$contact_type_id = wp_unslash( $_POST[ $this->contact_type_id ] );
			}

			// Extract the Contact Subtype ID from our metabox.
			$contact_subtype_id = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST[ $this->contact_subtype_id ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$contact_subtype_id = wp_unslash( $_POST[ $this->contact_subtype_id ] );
			}

			// Build Entity data.
			$entity_data = [
				'contact_type_id'    => $contact_type_id,
				'contact_subtype_id' => $contact_subtype_id,
			];

		}

		// Get data for the "Address" and "Phone" Entity Types.
		if ( 'Address' === $entity_type || 'Phone' === $entity_type ) {

			// Extract the Location Type ID from our metabox.
			$location_type_id = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST[ $this->location_type_id ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$location_type_id = wp_unslash( $_POST[ $this->location_type_id ] );
			}

			// Build Entity data.
			$entity_data = [
				'location_type_id' => $location_type_id,
			];

			// Get data for the "Phone" Entity Type.
			if ( 'Phone' === $entity_type ) {

				// Extract the Phone Type ID from our metabox.
				$phone_type_id = '';
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( ! empty( $_POST[ $this->phone_type_id ] ) ) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
					$phone_type_id = wp_unslash( $_POST[ $this->phone_type_id ] );
				}

				// Add to Entity data.
				$entity_data['phone_type_id'] = $phone_type_id;

			}

		}

		// Get data for the "Website" Entity Type.
		if ( 'Website' === $entity_type ) {

			// Extract the Website Type ID from our metabox.
			$website_type_id = '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ! empty( $_POST[ $this->website_type_id ] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$website_type_id = wp_unslash( $_POST[ $this->website_type_id ] );
			}

			// Add to Entity data.
			$entity_data['website_type_id'] = $website_type_id;

		}

		// Extract the value from our metabox.
		$value = '';
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! empty( $_POST[ $this->name ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$value = wp_unslash( $_POST[ $this->name ] );
		}

		// Bundle our data into an array.
		$args = [
			'entity_type' => $entity_type,
			'entity_data' => $entity_data,
			'value'       => $value,
		];

		// Save setting(s).
		$this->set_metadata_all( $field, $args );

		/**
		 * Broadcast our data when a BuddyPress xProfile Field has been saved.
		 *
		 * @since 0.5
		 *
		 * @param BP_XProfile_Field $field The current xProfile Field object.
		 * @param array $args The array of CiviCRM data.
		 */
		do_action( 'cwps/buddypress/field/saved', $field, $args );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Output a metabox below the xProfile Field Type metabox in the main column.
	 *
	 * TODO: Responsibility for populating the configuration for this metabox
	 * should be relocated to the various classes with an interest in populating
	 * it using a filter similar to "cwps/bp/field/query_setting_choices" below.
	 *
	 * @since 0.5
	 *
	 * @param BP_XProfile_Field $field The current XProfile Field.
	 */
	public function metabox_render( $field ) {

		// Get our Field settings.
		$meta          = $this->get_metadata_all( $field );
		$entity_type   = isset( $meta['entity_type'] ) ? $meta['entity_type'] : '';
		$entity_data   = isset( $meta['entity_data'] ) ? $meta['entity_data'] : '';
		$civicrm_field = isset( $meta['value'] ) ? $meta['value'] : '';

		// Get data for the "Address" and "Phone" Entity Types.
		$location_type_id = '';
		if ( 'Address' === $entity_type || 'Phone' === $entity_type ) {
			$location_type_id = isset( $entity_data['location_type_id'] ) ? $entity_data['location_type_id'] : '';
		}

		// Add entries for Location Types.
		$locations      = [];
		$location_types = $this->plugin->civicrm->address->location_types_get();
		if ( ! empty( $location_types ) ) {
			foreach ( $location_types as $location ) {
				$locations[ $location['id'] ] = trim( $location['display_name'] );
			}
		}

		// Get data for the "Phone" Entity Type.
		$phone_type_id = '';
		if ( 'Phone' === $entity_type ) {
			$phone_type_id = isset( $entity_data['phone_type_id'] ) ? $entity_data['phone_type_id'] : '';
		}

		// Add entries for Phone Types.
		$phones      = [];
		$phone_types = $this->plugin->civicrm->phone->phone_types_get();
		if ( ! empty( $phone_types ) ) {
			foreach ( $phone_types as $id => $label ) {
				$phones[ $id ] = trim( $label );
			}
		}

		// Get data for the "Website" Entity Type.
		$website_type_id = '';
		if ( 'Website' === $entity_type ) {
			$website_type_id = isset( $entity_data['website_type_id'] ) ? $entity_data['website_type_id'] : '';
		}

		/*
		 * Get the Website Type that is synced with the WordPress User.
		 *
		 * We need this because we have to exclude this Website Type from being
		 * synced with a BuddyPress "URL" Field. There's a built-in BuddyPress
		 * xProfile Field that does this already that can be defined using the
		 * "WordPress Fields" -> "Text Field" Field Type.
		 */
		$user_website_type_id = $this->plugin->admin->setting_get( 'user_profile_website_type', 0 );

		// Add entries for Website Types.
		$websites      = [];
		$website_types = $this->plugin->civicrm->website->types_get();
		if ( ! empty( $website_types ) ) {
			foreach ( $website_types as $id => $label ) {
				if ( (int) $user_website_type_id !== (int) $id ) {
					$websites[ $id ] = trim( $label );
				}
			}
		}

		// Get data for the "Contact" Entity.
		$top_level_type = '';
		$sub_type       = '';
		if ( 'Contact' === $entity_type ) {
			$top_level_type = isset( $entity_data['contact_type_id'] ) ? $entity_data['contact_type_id'] : '';
			$sub_type       = isset( $entity_data['contact_subtype_id'] ) ? $entity_data['contact_subtype_id'] : '';
		}

		// Init Contact arrays.
		$top_level_types = [];
		$sub_types       = [];

		// Get all Contact Types.
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();
		if ( ! empty( $contact_types ) ) {

			// Add entries for top level Contact Types.
			foreach ( $contact_types as $contact_type ) {
				$top_level_types[ $contact_type['id'] ] = $contact_type['label'];
			}

			// Add entries for CiviCRM Contact Sub-types.
			foreach ( $contact_types as $contact_type ) {
				if ( empty( $contact_type['children'] ) ) {
					continue;
				}
				foreach ( $contact_type['children'] as $contact_subtype ) {
					$sub_types[ $contact_type['name'] ][ $contact_subtype['id'] ] = $contact_subtype['label'];
				}
			}

		}

		// Init Entity Type array.
		$entity_type_data = [];

		// Get data based on Entity.
		if ( 'Contact' === $entity_type ) {

			// Set the lowest-level Contact Type ID that we can.
			$contact_type_id = 0;
			if ( ! empty( $top_level_type ) ) {
				$contact_type_id = $top_level_type;
				if ( ! empty( $sub_type ) ) {
					$contact_type_id = $sub_type;
				}
			}

			// If we got some Contact Types.
			if ( ! empty( $contact_types ) ) {

				// Assign top level Contact Type data.
				foreach ( $contact_types as $contact_type ) {
					if ( (int) $contact_type['id'] === (int) $contact_type_id ) {
						$entity_type_data = $contact_type;
					}
				}

				// Maybe override with Contact Sub-type data.
				foreach ( $contact_types as $contact_type ) {
					if ( empty( $contact_type['children'] ) ) {
						continue;
					}
					foreach ( $contact_type['children'] as $contact_subtype ) {
						if ( (int) $contact_subtype['id'] === (int) $contact_type_id ) {
							$entity_type_data = $contact_subtype;
						}
					}
				}

			}

		}

		// Assign Location Type data for the "Address" and "Phone" Entity Types.
		if ( 'Address' === $entity_type || 'Phone' === $entity_type ) {
			if ( ! empty( $location_types ) ) {
				foreach ( $location_types as $location ) {
					if ( (int) $location['id'] === (int) $location_type_id ) {
						$entity_type_data['location_type'] = $location;
					}
				}
			}
		}

		// Assign Phone Type data if we got some.
		if ( 'Phone' === $entity_type ) {
			if ( ! empty( $phone_types ) ) {
				foreach ( $phone_types as $id => $label ) {
					if ( (int) $id === (int) $phone_type_id ) {
						$entity_type_data['phone_type'] = [
							'id'    => $id,
							'label' => $label,
						];
					}
				}
			}
		}

		// Assign Website Type data if we got some.
		if ( 'Website' === $entity_type ) {
			if ( ! empty( $website_types ) ) {
				foreach ( $website_types as $id => $label ) {
					if ( (int) $id === (int) $website_type_id ) {
						$entity_type_data['website_type'] = [
							'id'    => $id,
							'label' => $label,
						];
					}
				}
			}
		}

		/**
		 * Request the choices for a Setting Field from Entity classes.
		 *
		 * @since 0.5
		 *
		 * @param array The empty default Setting Field choices array.
		 * @param string $field_type The BuddyPress xProfile Field Type.
		 * @param string $entity_type The CiviCRM Entity Type.
		 * @param array $entity_type_data The array of Entity Type data.
		 */
		$choices = apply_filters( 'cwps/bp/field/query_setting_choices', [], $field->type, $entity_type, $entity_type_data );

		// Include the Setting Field template file.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/templates/buddypress/metaboxes/metabox-bp-field-content.php';

	}

	/**
	 * Enqueue the Javascript for our xProfile Field metabox.
	 *
	 * @since 0.5
	 */
	public function enqueue_js() {

		// Same check as BuddyPress.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		if ( ! empty( $_GET['page'] ) && false !== strpos( wp_unslash( $_GET['page'] ), 'bp-profile-setup' ) ) {

			// Enqueue our JavaScript.
			wp_enqueue_script(
				'cwps-xprofile-admin-js',
				plugins_url( 'assets/js/buddypress/xprofile/cwps-bp-civicrm-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
				[ 'xprofile-admin-js' ],
				CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
				true
			);

			// Init options.
			$options = [];

			// Get all Contact Types.
			$contact_types = $this->plugin->civicrm->contact_type->types_get_all();

			// Get the Field mappings for all BuddyPress Field Types.
			foreach ( bp_xprofile_get_field_types() as $field_type => $field_type_class ) {

				// Get the Field mappings for all Contact Types.
				foreach ( $contact_types as $contact_type ) {

					/**
					 * Request the choices for a Setting Field from Entity classes.
					 *
					 * @since 0.5
					 *
					 * @param array The empty default Setting Field choices array.
					 * @param string $field_type The BuddyPress xProfile Field Type.
					 * @param string $entity_type The CiviCRM Entity Type.
					 * @param array $contact_type The array of Contact Type data.
					 */
					$choices = apply_filters( 'cwps/bp/field/query_setting_choices', [], $field_type, 'Contact', $contact_type );

					// Skip if we get no choices.
					if ( empty( $choices ) ) {
						continue;
					}

					// Build data for options.
					$data = [];
					foreach ( $choices as $optgroup => $choice ) {
						$opts = [];
						foreach ( $choice as $value => $label ) {
							$opts[] = [
								'value' => $value,
								'label' => $label,
							];
						}
						$data[] = [
							'label'   => $optgroup,
							'options' => $opts,
						];
					}

					// Add data to options.
					$options[ $field_type ][ $contact_type['id'] ] = $data;

				}

				// Get the Field mappings for other supported Entity Types.
				$shared_entities = [ 'Address', 'Phone', 'Website' ];
				foreach ( $shared_entities as $entity_type ) {

					/**
					 * Request the choices for a Setting Field from Entity classes.
					 *
					 * @since 0.5
					 *
					 * @param array The empty default Setting Field choices array.
					 * @param string $field_type The BuddyPress xProfile Field Type.
					 * @param string $entity_type The CiviCRM Entity Type.
					 * @param array Empty because data is not needed for "Address".
					 */
					$choices = apply_filters( 'cwps/bp/field/query_setting_choices', [], $field_type, $entity_type, [] );

					// Skip if we get no choices.
					if ( empty( $choices ) ) {
						continue;
					}

					// Build data for options.
					$data = [];
					foreach ( $choices as $optgroup => $choice ) {
						$opts = [];
						foreach ( $choice as $value => $label ) {
							$opts[] = [
								'value' => $value,
								'label' => $label,
							];
						}
						$data[] = [
							'label'   => $optgroup,
							'options' => $opts,
						];
					}

					// Add data to options.
					$options[ $field_type ][ $entity_type ] = $data;

				}

			}

			// Is this the default "Name" Field?
			$is_fullname_field = false;
			if ( ! empty( $_GET['field_id'] ) && (int) bp_xprofile_fullname_field_id() === (int) $_GET['field_id'] ) {
				$is_fullname_field = true;
			}

			// Build data array.
			$vars = [
				'localisation' => [
					'placeholder' => __( '- Select Field -', 'civicrm-wp-profile-sync' ),
				],
				'settings'     => [
					'options'        => $options,
					'fullname_field' => $is_fullname_field,
				],
			];

			// Localise our script.
			wp_localize_script(
				'cwps-xprofile-admin-js',
				'CWPS_BP_Field_Vars',
				$vars
			);

		}

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets all of our metadata for a BuddyPress xProfile Field.
	 *
	 * @since 0.5
	 *
	 * @param object|integer $field The xProfile Field object or Field ID.
	 * @return array $data The array of our Field metadata.
	 */
	public function get_metadata_all( $field ) {

		// Init return.
		$data = [];

		// Grab the Field ID.
		if ( is_object( $field ) ) {
			$field_id = $field->id;
		} else {
			$field_id = $field;
		}

		/*
		// Only do this once per Field ID.
		static $pseudocache;
		if ( isset( $pseudocache[ $field_id ] ) ) {
			return $pseudocache[ $field_id ];
		}
		*/

		// Grab the metadata.
		$meta = bp_xprofile_get_meta( $field_id, 'field', $this->meta_key );
		if ( empty( $meta ) ) {
			return $data;
		}

		// Grab the data. Unserialise?
		$data = $meta;

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $field_id ] ) ) {
			$pseudocache[ $field_id ] = $data;
		}
		*/

		// --<
		return $data;

	}

	/**
	 * Sets all of our metadata for a BuddyPress xProfile Field.
	 *
	 * @since 0.5
	 *
	 * @param object|integer $field The xProfile Field object or Field ID.
	 * @param array          $data The array of our Field metadata.
	 */
	public function set_metadata_all( $field, $data ) {

		// Grab the Field ID.
		if ( is_object( $field ) ) {
			$field_id = $field->id;
		} else {
			$field_id = $field;
		}

		// Set the metadata.
		bp_xprofile_update_field_meta( $field_id, $this->meta_key, $data );

	}

	/**
	 * Gets an item of our metadata for a BuddyPress xProfile Field.
	 *
	 * @since 0.5
	 *
	 * @param object|integer $field The xProfile Field object or Field ID.
	 * @param string         $setting The xProfile Field setting.
	 * @return mixed $value The value if the setting or false if not present.
	 */
	public function get_metadata( $field, $setting = 'value' ) {

		// Init return.
		$value = false;

		// Grab the Field ID.
		if ( is_object( $field ) ) {
			$field_id = $field->id;
		} else {
			$field_id = $field;
		}

		// Try and get the metadata.
		$meta = $this->get_metadata_all( $field );
		if ( empty( $meta ) ) {
			return $value;
		}

		// Try and get the setting.
		if ( array_key_exists( $setting, $meta ) ) {
			$value = $meta[ $setting ];
		}

		// --<
		return $value;

	}

	/**
	 * Sets an item of our metadata for a BuddyPress xProfile Field.
	 *
	 * @since 0.5
	 *
	 * @param object|integer $field The xProfile Field object or Field ID.
	 * @param string         $setting The xProfile Field setting.
	 * @param mixed          $value The value of the xProfile Field setting.
	 */
	public function set_metadata( $field, $setting, $value ) {

		// Grab the Field ID.
		if ( is_object( $field ) ) {
			$field_id = $field->id;
		} else {
			$field_id = $field;
		}

		// Try and get the metadata.
		$meta = $this->get_metadata_all( $field );
		if ( empty( $meta ) ) {
			$meta = [];
		}

		// Set the setting.
		$meta[ $setting ] = $value;

		// Resave.
		$this->set_metadata_all( $field, $meta );

	}

}
