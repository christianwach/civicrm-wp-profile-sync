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
	 * @var string $name The Field Type name.
	 */
	public $name = 'civicrm_relationship';

	/**
	 * Field Type label.
	 *
	 * This must be populated in the class constructor because it is translatable.
	 *
	 * Multiple words, can include spaces, visible when selecting a Field Type.
	 *
	 * @since 0.4
	 * @access public
	 * @var string $label The Field Type label.
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
	 * @var string $label The Field Type category.
	 */
	public $category = 'CiviCRM';

	/**
	 * Field Type defaults.
	 *
	 * Array of default settings which are merged into the Field object.
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

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->acf = $parent;
		$this->civicrm = $this->acf_loader->civicrm;

		// Define label.
		$this->label = __( 'CiviCRM Relationship', 'civicrm-wp-profile-sync' );

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

		// Bail if there are no Fields.
		if ( empty( $relationships ) ) {
			return;
		}

		// Get Setting Field.
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
			$contacts = $this->plugin->civicrm->contact->get_by_ids( $contact_ids );

			// Maybe append them.
			if ( ! empty( $contacts ) ) {
				foreach ( $contacts as $contact ) {

					// Add email address if present.
					$name = $contact['display_name'];
					if ( ! empty( $contact['email'] ) ) {
						$name .= ' :: ' . $contact['email'];
					}

					// TODO: Permission to view Contact?

					// Append Contact to choices.
					$field['choices'][ $contact['contact_id'] ] = $name;

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

		// Get the autocomplete limit.
		$autocomplete_count = $this->plugin->civicrm->get_setting( 'search_autocomplete_count' );

		// Init response.
		$response = [
			'results' => [],
			'limit' => $autocomplete_count,
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

		// Load Field.
		$field = acf_get_field( $options['field_key'] );

		// Bail if Field did not load.
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
		if ( ! empty( $field[ $relationship_key ] ) ) {

			// Get the Relationship ID.
			$relationship_data = explode( '_', $field[ $relationship_key ] );
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
		 * @param integer $post_id The numeric ID of the WordPress post.
		 */
		$args = apply_filters( 'acf/fields/' . $this->name . '/query', $args, $field, $post_id );
		$args = apply_filters( 'acf/fields/' . $this->name . "/query/name={$field['_name']}", $args, $field, $post_id );
		$args = apply_filters( 'acf/fields/' . $this->name . "/query/key={$field['key']}", $args, $field, $post_id );

		// Handle paging.
		$offset = 0;
		if ( ! empty( $options['paged'] ) ) {
			$zero_adjusted = (int) $options['paged'] - 1;
			$offset = $zero_adjusted * (int) $autocomplete_count;
		}

		// Build extra params.
		$params = [
			'contact_type' => $args['contact_type'],
			'contact_sub_type' => $args['contact_sub_type'],
			'return' => $this->plugin->civicrm->get_autocomplete_options( 'contact_autocomplete_options' ),
			'rowCount' => $autocomplete_count,
			'offset' => $offset,
		];

		// Get Contacts.
		$contacts = $this->civicrm->contact->get_by_search_string( $args['search'], $params );

		// Maybe append results.
		$results = [];
		if ( ! empty( $contacts ) ) {
			foreach ( $contacts as $contact ) {

				// Add extra items if present.
				$name = $contact['label'];
				if ( ! empty( $contact['description'] ) ) {
					foreach ( $contact['description'] as $extra ) {
						$name .= ' :: ' . $extra;
					}
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
		$contact_types = $this->plugin->civicrm->contact_type->hierarchy_separate( $hierarchy );

		// Filter Fields to include each Relationship in both directions when possible.
		foreach ( $relationships as $relationship ) {

			// Check each Contact Type in turn.
			foreach ( $contact_types as $contact_type ) {

				// Check the A-to-B relationship.
				if ( $relationship['contact_type_a'] == $contact_type['type'] ) {

					// Define key.
					$key = $relationship['id'] . '_ab';

					// Add to subtype optgroup if possible.
					if ( ! empty( $relationship['contact_sub_type_a'] ) ) {
						if ( $relationship['contact_sub_type_a'] == $contact_type['subtype'] ) {
							$filtered[ $contact_type['subtype'] ][ $key ] = sprintf(
								/* translators: %s: The Relationship label */
								__( '%s (A-B)', 'civicrm-wp-profile-sync' ),
								$relationship['label_a_b']
							);
						}
					}

					// Add to type optgroup if not already added - and no subtype.
					if ( ! isset( $filtered[ $contact_type['subtype'] ][ $key ] ) ) {
						if ( empty( $relationship['contact_sub_type_a'] ) ) {
							$filtered[ $contact_type['type'] ][ $key ] = sprintf(
								/* translators: %s: The Relationship label */
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
							$filtered[ $contact_type['subtype'] ][ $key ] = sprintf(
								/* translators: %s: The Relationship label */
								__( '%s (B-A)', 'civicrm-wp-profile-sync' ),
								$relationship['label_b_a']
							);
						}
					}

					// Add to type optgroup if not already added - and no subtype.
					if ( ! isset( $filtered[ $contact_type['subtype'] ][ $key ] ) ) {
						if ( empty( $relationship['contact_sub_type_b'] ) ) {
							$filtered[ $contact_type['type'] ][ $key ] = sprintf(
								/* translators: %s: The Relationship label */
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

}
