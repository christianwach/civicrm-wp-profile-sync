<?php
/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM "Existing & New Contact" Reference Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * ACF "CiviCRM Existing & New Contact" Class.
 *
 * A class that encapsulates a "CiviCRM Existing & New Contact" Custom ACF Field in ACF 5+.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_Existing_New extends acf_field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Field API version.
	 *
	 * @since 0.6.9
	 * @access public
	 * @var string
	 */
	public $api_version;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * ACF object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $name = 'civicrm_contact_existing_new';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $label = '';

	/**
	 * Field Type category.
	 *
	 * Choose between the following categories:
	 *
	 * basic | content | choice | relational | jquery | layout | CUSTOM GROUP NAME
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $category = 'CiviCRM';

	/**
	 * Field Type defaults.
	 *
	 * Array of default settings which are merged into the Field object.
	 * These are used later in settings.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $settings = [
		'version' => CIVICRM_WP_PROFILE_SYNC_VERSION,
		'url'     => CIVICRM_WP_PROFILE_SYNC_URL,
		'path'    => CIVICRM_WP_PROFILE_SYNC_PATH,
	];

	/**
	 * Field Type translations.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Array of strings that are used in JavaScript. This allows JS strings
	 * to be translated in PHP and loaded via:
	 *
	 * var message = acf._e( 'civicrm_participant', 'error' );
	 *
	 * @since 0.5
	 * @access public
	 * @var array
	 */
	public $l10n = [];

	/**
	 * ACF identifier.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $acf_slug = 'cwps_participant';

	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.5
	 * @since 0.6.9 Added $api_version param.
	 *
	 * @param object $parent The parent object reference.
	 * @param string $api_version The ACF plugin version.
	 */
	public function __construct( $parent, $api_version ) {

		// Store ACF Field API version.
		$this->api_version = $api_version;

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf        = $parent->acf;
		$this->acf_type   = $parent;

		// Define label.
		$this->label = __( 'CiviCRM Contact: Existing/New', 'civicrm-wp-profile-sync' );

		// Define category.
		if ( function_exists( 'acfe' ) ) {
			$this->category = __( 'CiviCRM Post Type Sync only', 'civicrm-wp-profile-sync' );
		} else {
			$this->category = __( 'CiviCRM Post Type Sync', 'civicrm-wp-profile-sync' );
		}

		// Define translations.
		$this->l10n = [
			// Example message.
			'error' => __( 'Error! Please enter a higher value.', 'civicrm-wp-profile-sync' ),
		];

		// Call parent.
		parent::__construct();

		// Register this Field as a Local Field.
		add_action( 'acf/init', [ $this, 'register_field' ] );

		// Force validation.
		add_filter( 'acf/validate_value/type=group', [ $this, 'validate_value' ], 10, 4 );

		/*
		// Remove this Field from the list of available Fields.
		add_filter( 'acf/get_field_types', [ $this, 'remove_field_type' ], 100, 1 );
		*/

	}

	/**
	 * Removes this Field Type from the list of available Field Types.
	 *
	 * @since 0.5
	 *
	 * @param array $groups The Field being rendered.
	 */
	public function remove_field_type( $groups ) {

		// Bail if the "CiviCRM" group is missing.
		if ( empty( $groups[ $this->category ] ) ) {
			return $groups;
		}

		// Remove this Field Type.
		if ( isset( $groups[ $this->category ][ $this->name ] ) ) {
			unset( $groups[ $this->category ][ $this->name ] );
		}

		// --<
		return $groups;

	}

	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Get the ACF Field definition.
		$field = $this->get_field_definition();

		// Render.
		acf_render_field( $field );

	}

	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function load_field( $field ) {

		// Init Subfields.
		$sub_fields = [];

		// Maybe append to Field.
		if ( ! empty( $field['sub_fields'] ) ) {

			// Validate Field first.
			foreach ( $field['sub_fields'] as $sub_field ) {
				$sub_fields[] = acf_validate_field( $sub_field );
			}

		}

		// Overwrite subfields.
		$field['sub_fields'] = $sub_fields;

		// --<
		return $field;

	}

	/**
	 * This filter is applied to the Field before it is saved to the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $field The modified Field data.
	 */
	public function update_field( $field ) {

		// Modify the Field with our settings.
		$field = $this->modify_field( $field );

		// Add Subfields to Field.
		$field['sub_fields'] = $this->get_subfield_definitions();

		// --<
		return $field;

	}

	/**
	 * Modify the Field with defaults and Subfield definitions.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field array holding all the Field options.
	 * @return array $subfields The subfield array.
	 */
	public function modify_field( $field ) {

		// Set sensible defaults.
		$field['instruction_placement'] = 'field';
		$field['required']              = 1;
		$field['layout']                = 'block';

		// Use for Participants only.
		// TODO: make available elsewhere?
		$field['field_cacf_civicrm_custom_field'] = 'caiparticipant_contact_id';

		// Set wrapper class.
		$field['wrapper']['class'] = 'civicrm_contact_existing_new';

		// --<
		return $field;

	}

	/**
	 * Registers the ACF Field.
	 *
	 * This seems to be required in order for AJAX calls in the Contact ID
	 * Sub-field to find a registered Field.
	 *
	 * @since 0.5
	 */
	public function register_field() {

		// Get the ACF Field definition.
		$field = $this->get_field_definition();

		// Add it as a "local field".
		acf_add_local_field( $field );

	}

	/**
	 * Gets the "Existing Contact / New Contact" Field.
	 *
	 * @since 0.5
	 *
	 * @return array $field The ACF Field definition.
	 */
	public function get_field_definition() {

		// Bundle the above Fields into a container group.
		$field = [
			'key'                             => 'field_' . $this->acf_slug . '_contact_group',
			'label'                           => __( 'CiviCRM Contact', 'civicrm-wp-profile-sync' ),
			'name'                            => $this->name,
			'type'                            => 'group',
			'instructions'                    => '',
			'instruction_placement'           => 'field',
			'required'                        => 1,
			'layout'                          => 'block',
			'field_cacf_civicrm_custom_field' => 'caiparticipant_contact_id',
		];

		// Add the Sub-fields.
		$field['sub_fields'] = $this->get_subfield_definitions();

		// --<
		return $field;

	}

	/**
	 * Gets the Sub-fields of this Field.
	 *
	 * @since 0.5
	 *
	 * @return array $sub_fields The ACF Sub-field definitions.
	 */
	public function get_subfield_definitions() {

		// Define "Existing Contact" Field.
		$contact_id = [
			'key'          => 'field_' . $this->acf_slug . '_contact_id',
			'label'        => __( 'Existing Contact', 'civicrm-wp-profile-sync' ),
			'name'         => 'contact_id',
			'type'         => 'civicrm_contact',
			'instructions' => __( 'Select an existing Contact in CiviCRM.', 'civicrm-wp-profile-sync' ),
			'required'     => 0,
		];

		// Define "Contact Type" Field.
		$contact_type = [
			'key'           => 'field_' . $this->acf_slug . '_contact_type',
			'label'         => __( 'Contact Type', 'civicrm-wp-profile-sync' ),
			'name'          => 'contact_type',
			'type'          => 'select',
			'instructions'  => '',
			'required'      => 0,
			'choices'       => [
				'Individual'   => __( 'Individual', 'civicrm-wp-profile-sync' ),
				'Household'    => __( 'Household', 'civicrm-wp-profile-sync' ),
				'Organization' => __( 'Organization', 'civicrm-wp-profile-sync' ),
			],
			'default_value' => 'Individual',
			'allow_null'    => 0,
			'multiple'      => 0,
			'ui'            => 0,
			'return_format' => 'value',
		];

		// Define "First Name" Field.
		$first_name = [
			'key'               => 'field_' . $this->acf_slug . '_contact_first_name',
			'label'             => __( 'First Name', 'civicrm-wp-profile-sync' ),
			'name'              => 'first_name',
			'type'              => 'text',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $this->acf_slug . '_contact_type',
						'operator' => '==',
						'value'    => 'Individual',
					],
				],
			],
			'wrapper'           => [
				'width' => '50',
				'class' => '',
				'id'    => '',
			],
			'default_value'     => '',
			'placeholder'       => '',
			'prepend'           => '',
			'append'            => '',
			'maxlength'         => '',
		];

		// Define "Last Name" Field.
		$last_name = [
			'key'               => 'field_' . $this->acf_slug . '_contact_last_name',
			'label'             => __( 'Last Name', 'civicrm-wp-profile-sync' ),
			'name'              => 'last_name',
			'type'              => 'text',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $this->acf_slug . '_contact_type',
						'operator' => '==',
						'value'    => 'Individual',
					],
				],
			],
			'wrapper'           => [
				'width' => '50',
				'class' => '',
				'id'    => '',
			],
			'default_value'     => '',
			'placeholder'       => '',
			'prepend'           => '',
			'append'            => '',
			'maxlength'         => '',
		];

		// Define "Household Name" Field.
		$household_name = [
			'key'               => 'field_' . $this->acf_slug . '_contact_household_name',
			'label'             => __( 'Household Name', 'civicrm-wp-profile-sync' ),
			'name'              => 'household_name',
			'type'              => 'text',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $this->acf_slug . '_contact_type',
						'operator' => '==',
						'value'    => 'Household',
					],
				],
			],
			'default_value'     => '',
			'placeholder'       => '',
			'prepend'           => '',
			'append'            => '',
			'maxlength'         => '',
		];

		// Define "Organisation Name" Field.
		$organization_name = [
			'key'               => 'field_' . $this->acf_slug . '_contact_organization_name',
			'label'             => __( 'Organization Name', 'civicrm-wp-profile-sync' ),
			'name'              => 'organization_name',
			'type'              => 'text',
			'instructions'      => '',
			'required'          => 0,
			'conditional_logic' => [
				[
					[
						'field'    => 'field_' . $this->acf_slug . '_contact_type',
						'operator' => '==',
						'value'    => 'Organization',
					],
				],
			],
			'default_value'     => '',
			'placeholder'       => '',
			'prepend'           => '',
			'append'            => '',
			'maxlength'         => '',
		];

		// Define "Email" Field.
		$email = [
			'key'           => 'field_' . $this->acf_slug . '_contact_email',
			'label'         => __( 'Email Address', 'civicrm-wp-profile-sync' ),
			'name'          => 'email_address',
			'type'          => 'email',
			'instructions'  => '',
			'required'      => 0,
			'default_value' => '',
			'placeholder'   => '',
			'prepend'       => '',
			'append'        => '',
		];

		// Bundle the above Fields into a "New Contact" Group.
		$new_contact = [
			'key'                   => 'field_' . $this->acf_slug . '_contact_new',
			'label'                 => __( 'New Contact', 'civicrm-wp-profile-sync' ),
			'name'                  => 'new_contact',
			'type'                  => 'group',
			'instructions'          => __( 'Create a new Contact in CiviCRM.', 'civicrm-wp-profile-sync' ),
			'instruction_placement' => 'field',
			'required'              => 0,
			'conditional_logic'     => [
				[
					[
						'field'    => 'field_' . $this->acf_slug . '_contact_id',
						'operator' => '==empty',
					],
				],
			],
			'layout'                => 'block',
			'sub_fields'            => [
				$contact_type,
				$first_name,
				$last_name,
				$household_name,
				$organization_name,
				$email,
			],
		];

		// Build Sub-fields array.
		$sub_fields = [
			$contact_id,
			$new_contact,
		];

		// --<
		return $sub_fields;

	}

	/**
	 * Prepares a CiviCRM Contact with data from this Field.
	 *
	 * @since 0.5
	 *
	 * @param array $value The ACF Field values.
	 * @return array|bool $contact The CiviCRM Contact data, or false on failure.
	 */
	public function prepare_output( $value ) {

		// Let's have an easy prefix.
		$prefix = 'field_' . $this->acf_slug;

		// Return early if there is an existing Contact ID.
		$contact_id = $value[ $prefix . '_contact_id' ];
		if ( ! empty( $contact_id ) && is_numeric( $contact_id ) ) {
			return (int) $contact_id;
		}

		// Get "New Contact" Group.
		$new_contact = $value[ $prefix . '_contact_new' ];
		if ( empty( $new_contact ) ) {
			return false;
		}

		// Get Contact Type.
		$contact_type = $new_contact[ $prefix . '_contact_type' ];
		if ( empty( $contact_type ) ) {
			return false;
		}

		// Get Email.
		$email = $new_contact[ $prefix . '_contact_email' ];
		if ( empty( $email ) ) {
			return false;
		}

		// Init Contact data.
		$contact_data = [
			'contact_type' => $contact_type,
			'email'        => $email,
		];

		// Construct Contact Name.
		switch ( $contact_type ) {

			case 'Household':
				$household_name                 = $new_contact[ $prefix . '_contact_household_name' ];
				$contact_data['household_name'] = $household_name;
				$contact_data['display_name']   = $household_name;
				break;

			case 'Organization':
				$organisation_name                 = $new_contact[ $prefix . '_contact_organization_name' ];
				$contact_data['organization_name'] = $organisation_name;
				$contact_data['display_name']      = $organisation_name;
				break;

			case 'Individual':
				$first_name                 = $new_contact[ $prefix . '_contact_first_name' ];
				$contact_data['first_name'] = $first_name;
				$last_name                  = $new_contact[ $prefix . '_contact_last_name' ];
				$contact_data['last_name']  = $last_name;
				break;

		}

		// --<
		return $contact_data;

	}

	/**
	 * Prepares this Field with data from a CiviCRM Contact.
	 *
	 * @since 0.5
	 *
	 * @param array|integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array|bool $field The Field data, or false on failure.
	 */
	public function prepare_input( $contact_id ) {

		// Let's have an easy prefix.
		$prefix = 'field_' . $this->acf_slug;

		// Clear the "New Contact" Sub-fields.
		$sub_fields = [
			$prefix . '_contact_type'              => 'Individual',
			$prefix . '_contact_first_name'        => '',
			$prefix . '_contact_last_name'         => '',
			$prefix . '_contact_household_name'    => '',
			$prefix . '_contact_organization_name' => '',
			$prefix . '_contact_email'             => '',
		];

		// Rebuild Field.
		$field = [
			$prefix . '_contact_id'  => $contact_id,
			$prefix . '_contact_new' => $sub_fields,
		];

		// --<
		return $field;

	}

	/**
	 * Validates the values of this Field prior to saving.
	 *
	 * @since 0.5
	 *
	 * @param bool   $valid The validation status.
	 * @param mixed  $value The $_POST value.
	 * @param array  $field The Field array holding all the Field options.
	 * @param string $input The corresponding input name for $_POST value.
	 * @return string|bool $valid False if not valid, or string for error message.
	 */
	public function validate_value( $valid, $value, $field, $input ) {

		// Return early if this isn't our Field.
		if ( 'field_' . $this->acf_slug . '_contact_group' !== $field['key'] ) {
			return $valid;
		}

		// Return early if there is an existing Contact ID.
		$contact_id = $value[ 'field_' . $this->acf_slug . '_contact_id' ];
		if ( ! empty( $contact_id ) && is_numeric( $contact_id ) ) {
			return $valid;
		}

		// Get "New Contact" Group.
		$new_contact = $value[ 'field_' . $this->acf_slug . '_contact_new' ];
		if ( empty( $new_contact ) ) {
			return false;
		}

		// Check that we have a Contact Type.
		$contact_type = $new_contact[ 'field_' . $this->acf_slug . '_contact_type' ];
		if ( empty( $contact_type ) ) {
			$valid = __( 'Please select a Contact Type for the New Contact', 'civicrm-wp-profile-sync' );
			return $valid;
		}

		// Check that we have an Email.
		$email = $new_contact[ 'field_' . $this->acf_slug . '_contact_email' ];
		if ( empty( $email ) ) {
			$valid = __( 'Please enter an Email Address for the New Contact', 'civicrm-wp-profile-sync' );
			return $valid;
		}

		// Init Contact data.
		$contact_data = [
			'contact_type' => $contact_type,
			'email'        => $email,
		];

		// Construct Contact Name.
		switch ( $contact_type ) {

			case 'Household':
				$household_name                 = $new_contact[ 'field_' . $this->acf_slug . '_contact_household_name' ];
				$contact_data['household_name'] = $household_name;
				if ( empty( $household_name ) ) {
					$valid = __( 'Please enter a name for the New Household.', 'civicrm-wp-profile-sync' );
					return $valid;
				}
				break;

			case 'Organization':
				$organisation_name                 = $new_contact[ 'field_' . $this->acf_slug . '_contact_organization_name' ];
				$contact_data['organization_name'] = $organisation_name;
				if ( empty( $household_name ) ) {
					$valid = __( 'Please enter a name for the New Organization.', 'civicrm-wp-profile-sync' );
					return $valid;
				}
				break;

			case 'Individual':
				$first_name                 = $new_contact[ 'field_' . $this->acf_slug . '_contact_first_name' ];
				$contact_data['first_name'] = $first_name;
				$last_name                  = $new_contact[ 'field_' . $this->acf_slug . '_contact_last_name' ];
				$contact_data['last_name']  = $last_name;
				if ( empty( $first_name ) && empty( $last_name ) ) {
					$valid = __( 'Please enter a First Name and a Last Name for the New Contact.', 'civicrm-wp-profile-sync' );
					return $valid;
				}
				if ( empty( $first_name ) ) {
					$valid = __( 'Please enter a First Name for the New Contact.', 'civicrm-wp-profile-sync' );
					return $valid;
				}
				if ( empty( $last_name ) ) {
					$valid = __( 'Please enter a Last Name for the New Contact.', 'civicrm-wp-profile-sync' );
					return $valid;
				}
				break;

		}

		// Fetch a possible CiviCRM Contact ID using dedupe.
		// TODO: Implement Dedupe Rule selection option.
		$contact_id = $this->acf_loader->civicrm->contact->get_by_dedupe_unsupervised( $contact_data, $contact_type );

		// Did we get one?
		if ( ! empty( $contact_id ) ) {
			$contact = $this->plugin->civicrm->contact->get_by_id( $contact_id );
			$url     = $this->plugin->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );
			$valid   = sprintf(
				/* translators: 1: The Link to the Contact, 2: The Email Address of the Contact */
				__( 'It looks like this Contact already exists: %1$s %2$s', 'civicrm-wp-profile-sync' ),
				'<a href="' . $url . '" target="_blank">' . esc_html( $contact['display_name'] ) . '</a>',
				$contact['email']
			);
			return $valid;
		}

		// --<
		return $valid;

	}

}
