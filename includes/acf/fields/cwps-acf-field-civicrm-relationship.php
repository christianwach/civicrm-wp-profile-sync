<?php
/**
 * ACF "CiviCRM Relationship Field" Class.
 *
 * Provides a "CiviCRM Relationship Field" Custom ACF Field in ACF 5+.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Relationship Field.
 *
 * A class that encapsulates a "CiviCRM Relationship" Custom ACF Field in ACF 5+.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Relationship extends acf_field {

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Advanced Custom Fields object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $cpt The Advanced Custom Fields object.
	 */
	public $acf;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The CiviCRM Utilities object.
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.4
	 * @access public
	 * @var str $name The Field Type name.
	 */
	public $name = 'civicrm_relationship';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a field type.
	 *
	 * @since 0.4
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
	 * @since 0.4
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
	 * @since 0.4
	 * @access public
	 * @var array $defaults The Field Type defaults.
	 */
	public $defaults = [];

	/**
	 * Field Type settings.
	 *
	 * Contains "version", "url" and "path" as references for use with assets.
	 *
	 * @since 0.4
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
	 * var message = acf._e( 'civicrm_contact', 'error' );
	 *
	 * @since 0.4
	 * @access public
	 * @var array $l10n The Field Type translations.
	 */
	public $l10n = [];



	/**
	 * Sets up the Field Type.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to ACF Utilities.
		$this->acf = $parent;

		// Store reference to CiviCRM Utilities.
		$this->civicrm = $this->acf_loader->civicrm;

		// Define label.
		$this->label = __( 'CiviCRM Relationship', 'civicrm-wp-profile-sync' );

		// Define translations.
		$this->l10n = [
			// Example message.
			'error'	=> __( 'Error! Please enter a higher value.', 'civicrm-wp-profile-sync' ),
		];

		// Call parent.
		parent::__construct();

		// Define AJAX callbacks.
		add_action( 'wp_ajax_acf/fields/' . $this->name . '/query', [ $this, 'ajax_query' ] );
		add_action( 'wp_ajax_nopriv_acf/fields/' . $this->name . '/query', [ $this, 'ajax_query' ] );

	}



	/**
	 * Create extra Settings for this Field Type.
	 *
	 * These extra Settings will be visible when editing a Field.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field being edited.
	 */
	public function render_field_settings( $field ) {

		// Add callback to filter the retrieved Relationships.
		add_filter( 'cwps/acf/civicrm/relationships/get_for_acf_field_for_type', [ $this, 'relationship_types_filter' ], 10, 3 );

		// Get the possible Relationships for this Field Type.
		$relationships = $this->civicrm->relationship->get_for_acf_field( $field );

		// Remove callback.
		remove_filter( 'cwps/acf/civicrm/relationships/get_for_acf_field_for_type', [ $this, 'relationship_types_filter' ], 10 );

		// Bail if there are no fields.
		if ( empty( $relationships ) ) {
			return;
		}

		// Get Setting field.
		$setting = $this->civicrm->relationship->acf_field_get( $relationships );

		// Now add it.
		acf_render_field_setting( $field, $setting );

	}



	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.4
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a select.
		$field['type'] = 'select';
		$field['ui'] = 1;
		$field['ajax'] = 1;
		$field['allow_null'] = 1;
		$field['multiple'] = 1;

		// Init choices array.
		$field['choices'] = [];

		// Populate choices.
		if ( ! empty( $field['value'] ) ) {

			// Clean value into an array of IDs.
			$contact_ids = array_map( 'intval', acf_array( $field['value'] ) );

			// Get existing Contacts.
			$contacts = $this->civicrm->contact->get_by_ids( $contact_ids );

			// Maybe append them.
			if ( ! empty( $contacts ) ) {
				foreach( $contacts AS $contact ) {

					// Add email address if present.
					$name = $contact['display_name'];
					if ( ! empty( $contact['email'] ) ) {
						$name .= ' :: ' . $contact['email'];
					}

					// TODO: Permission to view Contact?

					// Append Contact to choices.
					$field['choices'][$contact['contact_id']] = $name;

				}
			}

		}

		// Render.
		acf_render_field( $field );

	}



	/**
	 * AJAX Query callback.
	 *
	 * @since 0.4
	 */
	public function ajax_query() {

		// Validate.
		if ( ! acf_verify_ajax() ) {
			die();
		}

		// Get choices.
		$response = $this->get_ajax_query( $_POST );

		// Send results.
		acf_send_ajax_results( $response );

	}



	/**
	 * AJAX Query callback.
	 *
	 * @since 0.4
	 *
	 * @param array $options The options that define the query.
	 * @return array $response The query results.
	 */
	public function get_ajax_query( $options = [] ) {

		// Init response.
		$response = [
			'results' => [],
			'limit' => 25,
		];

		// Init defaults.
		$defaults = [
			'post_id' => 0,
			's' => '',
			'field_key' => '',
			'paged' => 1,
		];

   		// Parse the incoming POST array.
   		$options = acf_parse_args( $options, $defaults );

		// Bail if there's no search string.
		if ( empty( $options['s'] ) ) {
			return $response;
		}

 		// Load field.
		$field = acf_get_field( $options['field_key'] );

		// Bail if field did not load.
		if ( ! $field ) {
			return $response;
		}

		// Grab the ACF "Post ID".
		$post_id = $options['post_id'];

		// Init args.
		$args = [];

		// Strip slashes - search may be an integer.
		$args['search'] = wp_unslash( (string) $options['s'] );

		// Get the "CiviCRM Relationship" key.
		$relationship_key = $this->civicrm->relationship->acf_field_key_get();

		// Assume any Contact Type.
		$args['contact_type'] = '';
		$args['contact_sub_type'] = '';

		// If this has a relationship.
		if ( ! empty( $field[$relationship_key] ) ) {

			// Get the Relationship ID.
			$relationship_data = explode( '_', $field[$relationship_key] );
			$relationship_id = absint( $relationship_data[0] );
			$relationship_direction = $relationship_data[1];

			// Get the Relationship Type.
			$relationship_type = $this->civicrm->relationship->type_get_by_id( $relationship_id );

			// We need to find the target Contact Type.
			if ( $relationship_direction == 'ab' ) {
				$args['contact_type'] = $relationship_type['contact_type_b'];
				if ( ! empty( $relationship_type['contact_sub_type_b'] ) ) {
					$args['contact_sub_type'] = $relationship_type['contact_sub_type_b'];
				}
			} else {
				$args['contact_type'] = $relationship_type['contact_type_a'];
				if ( ! empty( $relationship_type['contact_sub_type_a'] ) ) {
					$args['contact_sub_type'] = $relationship_type['contact_sub_type_a'];
				}
			}

		}

		/**
		 * Maintain compatibility with the usual ACF filter schema.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of query arguments.
		 * @param array $field The ACF Field data.
		 * @param int $post_id The numeric ID of the WordPress post.
		 */
		$args = apply_filters( 'acf/fields/' . $this->name . '/query', $args, $field, $post_id );
		$args = apply_filters( 'acf/fields/' . $this->name . "/query/name={$field['_name']}", $args, $field, $post_id );
		$args = apply_filters( 'acf/fields/' . $this->name . "/query/key={$field['key']}", $args, $field, $post_id );

		// Get Contacts.
		$contacts = $this->civicrm->contact->get_by_search_string(
			$args['search'],
			$args['contact_type'],
			$args['contact_sub_type']
		);

		// Maybe append results.
		$results = [];
		if ( ! empty( $contacts ) ) {
			foreach( $contacts AS $contact ) {

				// Add email address if present.
				$name = $contact['label'];
				if ( ! empty( $contact['description'] ) ) {
					$name .= ' :: ' . array_pop( $contact['description'] );
				}

				// TODO: Permission to view Contact?

				// Append to results.
				$results[] = [
					'id' => $contact['id'],
					'text' => $name,
				];

			}
		}

		// Overwrite array entry.
		$response['results'] = $results;

		// --<
		return $response;

	}



	/**
	 * This method is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.4
	 */
	public function input_admin_enqueue_scripts() {

		// Enqueue our JavaScript.
		wp_enqueue_script(
			'acf-input-' . $this->name,
			plugins_url( 'assets/js/acf/fields/civicrm-relationship-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION // Version.
		);

	}



	/**
	 * This method is called in the admin_head action on the edit screen where
	 * this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.4
	public function input_admin_head() {

	}
	 */



	/**
 	 * This method is called once on the 'input' page between the head and footer.
	 *
	 * There are 2 situations where ACF did not load during the
	 * 'acf/input_admin_enqueue_scripts' and 'acf/input_admin_head' actions
	 * because ACF did not know it was going to be used. These situations are
	 * seen on comments / user-edit forms on the front end. This function will
	 * always be called, and includes $args that related to the current screen
	 * such as $args['post_id'].
	 *
	 * @since 0.4
	 *
	 * @param array $args The arguments related to the current screen.
	public function input_form_data( $args ) {

	}
	 */



	/**
	 * This action is called in the "admin_footer" action on the edit screen
	 * where this Field is created.
	 *
	 * Use this action to add CSS and JavaScript to assist your render_field()
	 * action.
	 *
	 * @since 0.4
	public function input_admin_footer() {

	}
	 */



	/**
	 * This action is called in the "admin_enqueue_scripts" action on the edit
	 * screen where this Field is edited.
	 *
	 * Use this action to add CSS and JavaScript to assist your
	 * render_field_options() action.
	 *
	 * @since 0.4
	public function field_group_admin_enqueue_scripts() {

	}
	 */



	/**
	 * This action is called in the "admin_head" action on the edit screen where
	 * this Field is edited.
	 *
	 * Use this action to add CSS and JavaScript to assist your
	 * render_field_options() action.
	 *
	 * @since 0.4
	public function field_group_admin_head() {

	}
	 */



	/**
	 * This filter is applied to the $value after it is loaded from the database.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The value found in the database.
	 * @param int $post_id The Post ID from which the value was loaded.
	 * @param array $field The field array holding all the field options.
	 * @return mixed $value The modified value.
	public function load_value( $value, $post_id, $field ) {

		// --<
		return $value;

	}
	 */



	/**
	 * This filter is applied to the $value before it is saved in the database.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The value found in the database.
	 * @param int $post_id The Post ID from which the value was loaded.
	 * @param array $field The field array holding all the field options.
	 * @return mixed $value The modified value.
	public function update_value( $value, $post_id, $field ) {

		// --<
		return $value;

	}
	 */



	/**
	 * This filter is applied to the value after it is loaded from the database
	 * and before it is returned to the template.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The value which was loaded from the database.
	 * @param mixed $post_id The Post ID from which the value was loaded.
	 * @param array $field The field array holding all the field options.
	 * @return mixed $value The modified value.
	public function format_value( $value, $post_id, $field ) {

		// Bail early if no value.
		if ( empty( $value ) ) {
			return $value;
		}

		// Apply setting.
		if ( $field['font_size'] > 12 ) {

			// format the value
			// $value = 'something';

		}

		// --<
		return $value;

	}
	 */



	/**
	 * This filter is used to perform validation on the value prior to saving.
	 *
	 * All values are validated regardless of the field's required setting.
	 * This allows you to validate and return messages to the user if the value
	 * is not correct.
	 *
	 * @since 0.4
	 *
	 * @param bool $valid The validation status based on the value and the field's required setting.
	 * @param mixed $value The $_POST value.
	 * @param array $field The field array holding all the field options.
	 * @param str $input The corresponding input name for $_POST value.
	 * @return bool|str $valid False if not valid, or string for error message.
	public function validate_value( $valid, $value, $field, $input ) {

		// Basic usage.
		if ( $value < $field['custom_minimum_setting'] ) {
			$valid = false;
		}

		// Advanced usage.
		if ( $value < $field['custom_minimum_setting'] ) {
			$valid = __( 'The value is too little!', 'civicrm-wp-profile-sync' ),
		}

		// --<
		return $valid;

	}
	 */



	/**
	 * This action is fired after a value has been deleted from the database.
	 *
	 * Please note that saving a blank value is treated as an update, not a delete.
	 *
	 * @since 0.4
	 *
	 * @param int $post_id The Post ID from which the value was deleted.
	 * @param str $key The meta key which the value was deleted.
	public function delete_value( $post_id, $key ) {

	}
	 */



	/**
	 * This filter is applied to the Field after it is loaded from the database.
	 *
	 * @since 0.4
	 *
	 * @param array $field The field array holding all the field options.
	 * @return array $field The modified field data.
	public function load_field( $field ) {

		// --<
		return $field;

	}
	 */



	/**
	 * This filter is applied to the Field before it is saved to the database.
	 *
	 * @since 0.4
	 *
	 * @param array $field The field array holding all the field options.
	 * @return array $field The modified field data.
	public function update_field( $field ) {

		// --<
		return $field;

	}
	 */



	/**
	 * This action is fired after a Field is deleted from the database.
	 *
	 * @since 0.4
	 *
	 * @param array $field The field array holding all the field options.
	public function delete_field( $field ) {

	}
	 */



	// -------------------------------------------------------------------------



	/**
	 * Filter the retrieved Relationship Types.
	 *
	 * @since 0.4
	 *
	 * @param array $relationships The retrieved array of Relationship Types.
	 * @param array $hierarchy The array of Contact Types for a Contact.
	 * @param array $field The ACF Field data array.
	 * @return array $filtered The filtered array of Relationship Types.
	 */
	public function relationship_types_filter( $relationships, $hierarchy, $field ) {

		// Init return.
		$filtered = [];

		// Bail if there aren't any relationships.
		if ( empty( $relationships ) ) {
			return $filtered;
		}

		// Get separated array of Contact Types.
		$contact_types = $this->civicrm->contact_type->hierarchy_separate( $hierarchy );

		// Filter fields to include each Relationship in both directions when possible.
		foreach( $relationships AS $relationship ) {

			// Check each Contact Type in turn.
			foreach( $contact_types AS $contact_type ) {

				// Check the A-to-B relationship.
				if ( $relationship['contact_type_a'] == $contact_type['type'] ) {

					// Define key.
					$key = $relationship['id'] . '_ab';

					// Add to subtype optgroup if possible.
					if ( ! empty( $relationship['contact_sub_type_a'] ) ) {
						if ( $relationship['contact_sub_type_a'] == $contact_type['subtype'] ) {
							$filtered[$contact_type['subtype']][$key] = sprintf(
								__( '%s (A-B)', 'civicrm-wp-profile-sync' ),
								$relationship['label_a_b']
							);
						}
					}

					// Add to type optgroup if not already added - and no subtype.
					if ( ! isset( $filtered[$contact_type['subtype']][$key] ) ) {
						if ( empty( $relationship['contact_sub_type_a'] ) ) {
							$filtered[$contact_type['type']][$key] = sprintf(
								__( '%s (A-B)', 'civicrm-wp-profile-sync' ),
								$relationship['label_a_b']
							);
						}
					}

				}

				// Check the B-to-A relationship.
				if ( $relationship['contact_type_b'] == $contact_type['type'] ) {

					// Define key.
					$key = $relationship['id'] . '_ba';

					// Add to subtype optgroup if possible.
					if ( ! empty( $relationship['contact_sub_type_b'] ) ) {
						if ( $relationship['contact_sub_type_b'] == $contact_type['subtype'] ) {
							$filtered[$contact_type['subtype']][$key] = sprintf(
								__( '%s (B-A)', 'civicrm-wp-profile-sync' ),
								$relationship['label_b_a']
							);
						}
					}

					// Add to type optgroup if not already added - and no subtype.
					if ( ! isset( $filtered[$contact_type['subtype']][$key] ) ) {
						if ( empty( $relationship['contact_sub_type_b'] ) ) {
							$filtered[$contact_type['type']][$key] = sprintf(
								__( '%s (B-A)', 'civicrm-wp-profile-sync' ),
								$relationship['label_b_a']
							);
						}
					}

				}

			}

		}

		// --<
		return $filtered;

	}



} // Class ends.



