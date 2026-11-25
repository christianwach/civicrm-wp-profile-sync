<?php
/**
 * Contact Reference Shortcode Class.
 *
 * Provides a Shortcode for rendering CiviCRM Contact Reference records.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.7.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Custom Shortcodes Class.
 *
 * A class that encapsulates a Shortcode for rendering CiviCRM Contact Reference records.
 *
 * @since 0.7.6
 */
class CiviCRM_Profile_Sync_ACF_Shortcode_Contact {

	/**
	 * Plugin object.
	 *
	 * @since 0.7.6
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.7.6
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_ACF_Loader
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.7.6
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM
	 */
	public $civicrm;

	/**
	 * Contact object.
	 *
	 * @since 0.7.6
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM_Contact
	 */
	public $contact;

	/**
	 * Shortcode name.
	 *
	 * @since 0.7.6
	 * @access public
	 * @var string
	 */
	public $tag = 'cwps_contact';

	/**
	 * Constructor.
	 *
	 * @since 0.7.6
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm    = $parent->civicrm;
		$this->contact    = $parent;

		// Init when the CiviCRM Contact Reference object is loaded.
		add_action( 'cwps/acf/civicrm/contact/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.7.6
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.7.6
	 */
	public function register_hooks() {

		// Register Shortcode.
		add_action( 'init', [ $this, 'shortcode_register' ] );

		// Shortcake compatibility.
		add_action( 'register_shortcode_ui', [ $this, 'shortcake' ] );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Register our Shortcode.
	 *
	 * @since 0.7.6
	 */
	public function shortcode_register() {

		// Register our Shortcode and its callback.
		add_shortcode( $this->tag, [ $this, 'shortcode_render' ] );

	}

	/**
	 * Render the Shortcode.
	 *
	 * @since 0.7.6
	 *
	 * @param array  $attr The saved Shortcode attributes.
	 * @param string $content The enclosed content of the Shortcode.
	 * @param string $tag The Shortcode which invoked the callback.
	 * @return string $content The HTML-formatted Shortcode content.
	 */
	public function shortcode_render( $attr, $content = '', $tag = '' ) {

		// Return something else for feeds.
		if ( is_feed() ) {
			return '<p>' . __( 'Visit the website to see the Contact record.', 'civicrm-wp-profile-sync' ) . '</p>';
		}

		// Default Shortcode attributes.
		$defaults = [
			'field'     => '',
			'fields'    => 'display_name',
			'separator' => ':',
			'style'     => 'list',
			'post_id'   => null,
		];

		// Get parsed attributes.
		$atts = shortcode_atts( $defaults, $attr, $tag );

		// If there's no ACF Field attribute, show a message.
		if ( empty( $atts['field'] ) ) {
			return '<p>' . __( 'Please include an ACF Field attribute.', 'civicrm-wp-profile-sync' ) . '</p>';
		}

		// Get content from theme function.
		$content = cwps_get_contacts( $atts['field'], $atts['fields'], $atts['separator'], $atts['style'], $atts['post_id'] );

		// --<
		return $content;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add compatibility with Shortcake.
	 *
	 * @since 0.7.6
	 */
	public function shortcake() {

		// For now, let's be extra-safe and bail if not present.
		if ( ! function_exists( 'shortcode_ui_register_for_shortcode' ) ) {
			return;
		}

		/*
		// Add styles for TinyMCE editor.
		add_filter( 'mce_css', [ $this, 'shortcake_styles' ] );
		*/

		// ACF Field selector.
		$field = [
			'label'       => __( 'ACF Field', 'civicrm-wp-profile-sync' ),
			'attr'        => 'field',
			'type'        => 'text',
			'description' => __( 'Please enter an ACF Field selector.', 'civicrm-wp-profile-sync' ),
		];

		// Display Fields selector.
		$fields = [
			'label'       => __( 'Display Fields', 'civicrm-wp-profile-sync' ),
			'attr'        => 'fields',
			'type'        => 'select',
			'meta'        => [ 'multiple' => true ],
			'options'     => $this->shortcake_select_contact_fields_get(),
			'description' => __( 'Please select the Contact Fields to display.', 'civicrm-wp-profile-sync' ),
		];

		// Separator selector.
		$separator = [
			'label'       => __( 'Separator', 'civicrm-wp-profile-sync' ),
			'attr'        => 'field',
			'type'        => 'text',
			'description' => __( 'Please enter a separator.', 'civicrm-wp-profile-sync' ),
		];

		// Render style select.
		$style = [
			'label'       => __( 'Style', 'civicrm-wp-profile-sync' ),
			'attr'        => 'style',
			'type'        => 'select',
			'options'     => [
				'list'   => __( 'List', 'civicrm-wp-profile-sync' ),
				'commas' => __( 'Comma-separated', 'civicrm-wp-profile-sync' ),
			],
			'description' => __( 'Please choose list or comma-separated output.', 'civicrm-wp-profile-sync' ),
		];

		// Get all used Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_get_all();

		// WordPress Post ID.
		$post_id = [
			'label'       => __( 'Post (optional)', 'civicrm-wp-profile-sync' ),
			'attr'        => 'post_id',
			'type'        => 'post_select',
			'query'       => [ 'post_type' => array_values( $mapped_post_types ) ],
			'description' => __( 'Please select a Post.', 'civicrm-wp-profile-sync' ),
		];

		// Build Settings array.
		$settings = [

			// Window title.
			'label'         => esc_html__( 'CiviCRM Contact', 'civicrm-wp-profile-sync' ),

			// Icon.
			'listItemImage' => 'dashicons-admin-users',

			// Limit to synced CPTs only?
			// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
			// 'post_type' => array_values( $mapped_post_types ),

			// Window elements.
			'attrs'         => [
				$field,
				$fields,
				$separator,
				$style,
				$post_id,
			],

		];

		// Register Shortcake options.
		shortcode_ui_register_for_shortcode( $this->tag, $settings );

	}

	/**
	 * Add stylesheet to TinyMCE when Shortcake is active.
	 *
	 * @since 0.7.6
	 *
	 * @param string $mce_css The existing list of stylesheets that TinyMCE will load.
	 * @return string $mce_css The modified list of stylesheets that TinyMCE will load.
	 */
	public function shortcake_styles( $mce_css ) {

		// Add our styles to TinyMCE.
		$mce_css .= ', ' . CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/css/cwps-shortcode-contact.css';

		// --<
		return $mce_css;

	}

	/**
	 * Get Contact Fields select array for Shortcake registration.
	 *
	 * @since 0.7.6
	 *
	 * @return array $options The properly formatted array for the select.
	 */
	public function shortcake_select_contact_fields_get() {

		// Init return.
		$options = [];

		// Try and init CiviCRM.
		if ( ! $this->plugin->civicrm->is_initialised() ) {
			return $options;
		}

		// Define params to get Contact Fields.
		$params = [
			'version'    => 3,
			'api_action' => 'get',
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'getfields', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $options;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $options;
		}

		// Mapped Contact data.
		foreach ( $result['values'] as $field ) {
			$options[ $field['name'] ] = esc_attr( $field['title'] );
		}

		// --<
		return $options;

	}

}
