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
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The parent object.
	 */
	public $acf;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $name The Field Type name.
	 */
	public $name = 'civicrm_contact_existing_new';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a field type.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $label The Field Type label.
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
	 * @var str $label The Field Type category.
	 */
	public $category = 'CiviCRM';

	/**
	 * Field Type defaults.
	 *
	 * Array of default settings which are merged into the field object.
	 * These are used later in settings.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $defaults The Field Type defaults.
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $settings The Field Type settings.
	 */
	public $settings = [
		'version' => CIVICRM_WP_PROFILE_SYNC_VERSION,
		'url' => CIVICRM_WP_PROFILE_SYNC_URL,
		'path' => CIVICRM_WP_PROFILE_SYNC_PATH,
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
	 * @var array $l10n The Field Type translations.
	 */
	public $l10n = [];

	/**
	 * ACF identifier.
	 *
	 * @since 0.5
	 * @access public
	 * @var str $acf_slug The ACF identifier.
	 */
	public $acf_slug = 'cwps_participant';



	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to ACF object.
		$this->acf = $parent->acf;

		// Store reference to parent.
		$this->acf_type = $parent;

		// Define label.
		$this->label = __( 'CiviCRM Contact Existing/New', 'civicrm-wp-profile-sync' );

		// Define translations.
		$this->l10n = [
			// Example message.
			'error'	=> __( 'Error! Please enter a higher value.', 'civicrm-wp-profile-sync' ),
		];

		// Call parent.
    	parent::__construct();

		// Register this Field as a Local Field.
		add_action( 'acf/init', [ $this, 'register_field' ] );

		// Force validation.
		add_filter( 'acf/validate_value/type=group', [ $this, 'validate_value' ], 10, 4 );

	}



	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a Group.
		$field['type'] = 'group';

		// Render.
		acf_render_field( $field );

	}



	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.5
	 *
	 * @param array $field The field array holding all the field options.
	 * @return array $field The modified field data.
	 */
	public function load_field( $field ) {

		// Init Subfields.
		$sub_fields = [];

		// Maybe append to Field.
		if ( ! empty( $field['sub_fields'] ) ) {

			// Validate field first.
			foreach( $field['sub_fields'] AS $sub_field ) {
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
	 * @param array $field The field array holding all the field options.
	 * @return array $field The modified field data.
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
	 * @param array $field The field array holding all the field options.
	 * @return array $subfields The subfield array.
	 */
	public function modify_field( $field ) {

		// Set sensible defaults.
		$field['instruction_placement'] = 'field';
		$field['required'] = 1;
		$field['layout'] = 'block';

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
			'key' => 'field_' . $this->acf_slug . '_contact_group',
			'label' => __ ( 'CiviCRM Contact', 'civicrm-wp-profile-sync' ),
			'name' => $this->name,
			'type' => 'group',
			'instructions' => '',
			'instruction_placement' => 'field',
			'required' => 1,
			'layout' => 'block',
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

		// Define "Existing Contact" field.
		$contact_id = [
			'key' => 'field_' . $this->acf_slug . '_contact_id',
			'label' => __ ( 'Existing Contact', 'civicrm-wp-profile-sync' ),
			'name' => 'contact_id',
			'type' => 'civicrm_contact',
			'instructions' => __( 'Select an existing Contact in CiviCRM.', 'civicrm-wp-profile-sync' ),
			'required' => 0,
		];

		// Define "Contact Type" field.
		$contact_type = [
			'key' => 'field_' . $this->acf_slug . '_contact_type',
			'label' => __ ( 'Contact Type', 'civicrm-wp-profile-sync' ),
			'name' => 'contact_type',
			'type' => 'select',
			'instructions' => '',
			'required' => 0,
			'choices' => [
				'Individual' => __( 'Individual', 'civicrm-wp-profile-sync' ),
				'Household' => __( 'Household', 'civicrm-wp-profile-sync' ),
				'Organization' => __( 'Organization', 'civicrm-wp-profile-sync' ),
			],
			'default_value' => 'Individual',
			'allow_null' => 0,
			'multiple' => 0,
			'ui' => 0,
			'return_format' => 'value',
		];

		// Define "First Name" field.
		$first_name = [
			'key' => 'field_' . $this->acf_slug . '_contact_first_name',
			'label' => __ ( 'First Name', 'civicrm-wp-profile-sync' ),
			'name' => 'first_name',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [[[
				'field' => 'field_' . $this->acf_slug . '_contact_type',
				'operator' => '==',
				'value' => 'Individual',
			]]],
			'wrapper' => [
				'width' => '50',
				'class' => '',
				'id' => '',
			],
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		];

		// Define "Last Name" field.
		$last_name = [
			'key' => 'field_' . $this->acf_slug . '_contact_last_name',
			'label' => __ ( 'Last Name', 'civicrm-wp-profile-sync' ),
			'name' => 'last_name',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [[[
				'field' => 'field_' . $this->acf_slug . '_contact_type',
				'operator' => '==',
				'value' => 'Individual',
			]]],
			'wrapper' => [
				'width' => '50',
				'class' => '',
				'id' => '',
			],
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		];

		// Define "Household Name" field.
		$household_name = [
			'key' => 'field_' . $this->acf_slug . '_contact_household_name',
			'label' => __ ( 'Household Name', 'civicrm-wp-profile-sync' ),
			'name' => 'household_name',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [[[
				'field' => 'field_' . $this->acf_slug . '_contact_type',
				'operator' => '==',
				'value' => 'Household',
			]]],
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		];

		// Define "Organisation Name" field.
		$organization_name = [
			'key' => 'field_' . $this->acf_slug . '_contact_organization_name',
			'label' => __ ( 'Organization Name', 'civicrm-wp-profile-sync' ),
			'name' => 'organization_name',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => [[[
				'field' => 'field_' . $this->acf_slug . '_contact_type',
				'operator' => '==',
				'value' => 'Organization',
			]]],
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		];

		// Define "Email" field.
		$email = [
			'key' => 'field_' . $this->acf_slug . '_contact_email',
			'label' => __ ( 'Email Address', 'civicrm-wp-profile-sync' ),
			'name' => 'email_address',
			'type' => 'email',
			'instructions' => '',
			'required' => 0,
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		];

		// Bundle the above Fields into a "New Contact" Group.
		$new_contact = [
			'key' => 'field_' . $this->acf_slug . '_contact_new',
			'label' => __ ( 'New Contact', 'civicrm-wp-profile-sync' ),
			'name' => 'new_contact',
			'type' => 'group',
			'instructions' => __( 'Create a new Contact in CiviCRM.', 'civicrm-wp-profile-sync' ),
			'instruction_placement' => 'field',
			'required' => 0,
			'conditional_logic' => [[[
				'field' => 'field_' . $this->acf_slug . '_contact_id',
				'operator' => '==empty',
			]]],
			'layout' => 'block',
			'sub_fields' => [
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
	 * @return array|boolean $contact The CiviCRM Contact data, or false on failure.
	 */
	public function prepare_output( $value ) {

		// Let's have an easy prefix.
		$prefix = 'field_' . $this->acf_slug;

		// Return early if there is an existing Contact ID.
		$contact_id = $value[$prefix . '_contact_id'];
		if ( ! empty( $contact_id ) AND is_numeric( $contact_id ) ) {
			return (int) $contact_id;
		}

		// Get "New Contact" Group.
		$new_contact = $value[$prefix . '_contact_new'];
		if ( empty( $new_contact ) ) {
			return false;
		}

		// Get Contact Type.
		$contact_type = $new_contact[$prefix . '_contact_type'];
		if ( empty( $contact_type ) ) {
			return false;
		}

		// Get Email.
		$email = $new_contact[$prefix . '_contact_email'];
		if ( empty( $email ) ) {
			return false;
		}

		// Init Contact data.
		$contact_data = [
			'contact_type' => $contact_type,
			'email' => $email,
		];

		// Construct Contact Name.
		switch ( $contact_type ) {

			case 'Household' :
				$household_name = $new_contact[$prefix . '_contact_household_name'];
				$contact_data['household_name'] = $household_name;
				$contact_data['display_name'] = $household_name;
				break;

			case 'Organization' :
				$organisation_name = $new_contact[$prefix . '_contact_organization_name'];
				$contact_data['organization_name'] = $organisation_name;
				$contact_data['display_name'] = $organisation_name;
				break;

			case 'Individual' :
				$first_name = $new_contact[$prefix . '_contact_first_name'];
				$contact_data['first_name'] = $first_name;
				$last_name = $new_contact[$prefix . '_contact_last_name'];
				$contact_data['last_name'] = $last_name;
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
	 * @param array|int $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array|bool $field The Field data, or false on failure.
	 */
	public function prepare_input( $contact_id ) {

		// Let's have an easy prefix.
		$prefix = 'field_' . $this->acf_slug;

		// Clear the "New Contact" Sub-fields.
		$sub_fields = [
			$prefix . '_contact_type' => 'Individual',
			$prefix . '_contact_first_name' => '',
			$prefix . '_contact_last_name' => '',
			$prefix . '_contact_household_name' => '',
			$prefix . '_contact_organization_name' => '',
			$prefix . '_contact_email' => '',
		];

		// Rebuild Field.
		$field = [
			$prefix . '_contact_id' => $contact_id,
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
	 * @param boolean $valid The validation status.
	 * @param mixed $value The $_POST value.
	 * @param array $field The field array holding all the field options.
	 * @param string $input The corresponding input name for $_POST value.
	 * @return string|boolean $valid False if not valid, or string for error message.
	 */
	public function validate_value( $valid, $value, $field, $input ) {

		// Return early if this isn't our Field.
		if ( $field['key'] !== 'field_' . $this->acf_slug . '_contact_group' ) {
			return $valid;
		}

		// Return early if there is an existing Contact ID.
		$contact_id = $value['field_' . $this->acf_slug . '_contact_id'];
		if ( ! empty( $contact_id ) AND is_numeric( $contact_id ) ) {
			return $valid;
		}

		// Get "New Contact" Group.
		$new_contact = $value['field_' . $this->acf_slug . '_contact_new'];
		if ( empty( $new_contact ) ) {
			return false;
		}

		// Check that we have a Contact Type.
		$contact_type = $new_contact['field_' . $this->acf_slug . '_contact_type'];
		if ( empty( $contact_type ) ) {
			$valid = __( 'Please select a Contact Type for the New Contact', 'civicrm-wp-profile-sync' );
			return $valid;
		}

		// Check that we have an Email.
		$email = $new_contact['field_' . $this->acf_slug . '_contact_email'];
		if ( empty( $email ) ) {
			$valid = __( 'Please enter an Email Address for the New Contact', 'civicrm-wp-profile-sync' );
			return $valid;
		}

		// Init Contact data.
		$contact_data = [
			'contact_type' => $contact_type,
			'email' => $email,
		];

		// Construct Contact Name.
		switch ( $contact_type ) {

			case 'Household' :
				$household_name = $new_contact['field_' . $this->acf_slug . '_contact_household_name'];
				$contact_data['household_name'] = $household_name;
				if ( empty( $household_name ) ) {
					$valid = __( 'Please enter a name for the New Household.', 'civicrm-wp-profile-sync' );
					return $valid;
				}
				break;

			case 'Organization' :
				$organisation_name = $new_contact['field_' . $this->acf_slug . '_contact_organization_name'];
				$contact_data['organization_name'] = $organisation_name;
				if ( empty( $household_name ) ) {
					$valid = __( 'Please enter a name for the New Organization.', 'civicrm-wp-profile-sync' );
					return $valid;
				}
				break;

			case 'Individual' :
				$first_name = $new_contact['field_' . $this->acf_slug . '_contact_first_name'];
				$contact_data['first_name'] = $first_name;
				$last_name = $new_contact['field_' . $this->acf_slug . '_contact_last_name'];
				$contact_data['last_name'] = $last_name;
				if ( empty( $first_name ) AND empty( $last_name ) ) {
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
		$contact_id = $this->acf_loader->civicrm->contact->get_by_dedupe_unsupervised( $contact_data );

		// Did we get one?
		if ( ! empty( $contact_id ) ) {
			$contact = $this->acf_loader->civicrm->contact->get_by_id( $contact_id );
			$url = $this->acf_loader->civicrm->get_link( 'civicrm/contact/view', 'reset=1&cid=' . $contact_id );
			$valid = sprintf(
				__( 'It looks like this Contact already exists: %1$s %2$s', 'civicrm-wp-profile-sync' ),
				'<a href="' . $url . '" target="_blank">' . esc_html( $contact['display_name'] ) . '</a>',
				$contact['email'],
				'<br>'
			);
			return $valid;
		}

		// --<
		return $valid;

	}



} // Class ends.



