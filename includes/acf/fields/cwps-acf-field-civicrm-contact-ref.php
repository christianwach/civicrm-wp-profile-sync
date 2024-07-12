<?php
/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Contact Reference Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * ACF "CiviCRM Contact Reference" Class.
 *
 * A class that encapsulates a "CiviCRM Contact Reference" Custom ACF Field in ACF 5+.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_Custom_CiviCRM_Contact_Ref extends acf_field {

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
	 * Advanced Custom Fields object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * CiviCRM Utilities object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Field Type name.
	 *
	 * Single word, no spaces. Underscores allowed.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $name = 'civicrm_contact_ref';

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
		$this->civicrm    = $this->acf_loader->civicrm;

		// Define label.
		$this->label = __( 'CiviCRM Participant: Contact Ref', 'civicrm-wp-profile-sync' );

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
	 * @since 0.5
	 *
	 * @param array $field The Field being edited.
	 */
	public function render_field_settings( $field ) {

		// Get the Participant Fields for this ACF Field Type.
		$participant_fields = $this->civicrm->participant_field->get_for_acf_field( $field );
		if ( empty( $participant_fields ) ) {
			return;
		}

		// Get Setting Field.
		$setting = $this->civicrm->participant->acf_field_get( [], $participant_fields );

		// Now add it.
		acf_render_field_setting( $field, $setting );

	}

	/**
	 * Creates the HTML interface for this Field Type.
	 *
	 * @since 0.5
	 *
	 * @param array $field The Field being rendered.
	 */
	public function render_field( $field ) {

		// Change Field into a select.
		$field['type']              = 'select';
		$field['ui']                = 1;
		$field['ajax']              = 1;
		$field['allow_null']        = 1;
		$field['multiple']          = 0;
		$field['required']          = 0;
		$field['conditional_logic'] = 0;

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
	 * @since 0.5
	 */
	public function ajax_query() {

		// Verify AJAX by version.
		if ( version_compare( ACF_VERSION, '6.3.2', '<' ) ) {

			// Validate the old way.
			if ( ! acf_verify_ajax() ) {
				die();
			}

		} else {

			// Get validation params.
			$nonce     = acf_request_arg( 'nonce', '' );
			$field_key = acf_request_arg( 'field_key', '' );

			// Back-compat for field settings.
			if ( ! acf_is_field_key( $field_key ) ) {
				$nonce     = '';
				$field_key = '';
			}

			// Validate the new way.
			if ( ! acf_verify_ajax( $nonce, $field_key ) ) {
				die();
			}

		}

		// Get choices.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$response = $this->get_ajax_query( $_POST );

		// Send results.
		acf_send_ajax_results( $response );

	}

	/**
	 * AJAX Query callback.
	 *
	 * @since 0.5
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
			'limit'   => $autocomplete_count,
		];

		// Init defaults.
		$defaults = [
			'post_id'   => 0,
			's'         => '',
			'field_key' => '',
			'paged'     => 1,
		];

		// Parse the incoming POST array.
		$options = acf_parse_args( $options, $defaults );

		// Bail if there's no search string.
		if ( empty( $options['s'] ) ) {
			return $response;
		}

		// Load Field.
		$field = acf_get_field( $options['field_key'] );
		if ( ! $field ) {
			return $response;
		}

		// Grab the Post ID.
		$post_id = (int) $options['post_id'];

		// Init args.
		$args = [];

		// Strip slashes - search may be an integer.
		$args['search'] = wp_unslash( (string) $options['s'] );

		// Get the "CiviCRM Field" key.
		$acf_field_key = $this->civicrm->acf_field_key_get();

		// Default to any Contact Type.
		$args['contact_type'] = '';

		/**
		 * Maintain compatibility with the usual ACF filter schema.
		 *
		 * @since 0.5
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
			$offset        = $zero_adjusted * (int) $autocomplete_count;
		}

		// Build extra params.
		$params = [
			'contact_type' => $args['contact_type'],
			'return'       => $this->plugin->civicrm->get_autocomplete_options( 'contact_autocomplete_options' ),
			'rowCount'     => $autocomplete_count,
			'offset'       => $offset,
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
					'id'   => $contact['id'],
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
	 * @since 0.5
	 */
	public function input_admin_enqueue_scripts() {

		// Enqueue our JavaScript.
		wp_enqueue_script(
			'acf-input-' . $this->name,
			plugins_url( 'assets/js/acf/fields/civicrm-contact-ref-field.js', CIVICRM_WP_PROFILE_SYNC_FILE ),
			[ 'acf-input' ],
			CIVICRM_WP_PROFILE_SYNC_VERSION, // Version.
			true
		);

	}

}
