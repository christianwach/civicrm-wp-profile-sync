<?php
/**
 * CiviCRM Profile Sync Address Shortcode Class.
 *
 * Provides a Shortcode for rendering CiviCRM Address records.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Custom Shortcodes Class.
 *
 * A class that encapsulates a Shortcode for rendering CiviCRM Address records.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Shortcode_Address {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_ACF_Loader
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM
	 */
	public $civicrm;

	/**
	 * Addresses object.
	 *
	 * @since 0.4
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM_Addresses
	 */
	public $addresses;

	/**
	 * Shortcode name.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $tag = 'cwps_address';

	/**
	 * CAI Shortcode name.
	 *
	 * @since 0.4
	 * @access public
	 * @var string
	 */
	public $cai_tag = 'cai_address';

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm    = $parent->civicrm;
		$this->addresses  = $parent;

		// Init when the CiviCRM Address object is loaded.
		add_action( 'cwps/acf/civicrm/addresses/loaded', [ $this, 'initialise' ] );

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

		// Register Shortcodes.
		add_action( 'init', [ $this, 'shortcode_register' ] );

		// Shortcake compatibility.
		add_action( 'register_shortcode_ui', [ $this, 'shortcake' ] );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Register our Shortcode.
	 *
	 * @since 0.4
	 */
	public function shortcode_register() {

		// Register our Shortcode and its callback.
		add_shortcode( $this->tag, [ $this, 'shortcode_render' ] );

		// Register the legacy Shortcode and its callback.
		add_shortcode( $this->cai_tag, [ $this, 'shortcode_render' ] );

	}

	/**
	 * Render the Shortcode.
	 *
	 * @since 0.4
	 *
	 * @param array  $attr The saved Shortcode attributes.
	 * @param string $content The enclosed content of the Shortcode.
	 * @param string $tag The Shortcode which invoked the callback.
	 * @return string $content The HTML-formatted Shortcode content.
	 */
	public function shortcode_render( $attr, $content = '', $tag = '' ) {

		// Return something else for feeds.
		if ( is_feed() ) {
			return '<p>' . __( 'Visit the website to see the Address.', 'civicrm-wp-profile-sync' ) . '</p>';
		}

		// Default Shortcode attributes.
		$defaults = [
			'field'         => '',
			'location_type' => null,
			'post_id'       => null,
		];

		// Get parsed attributes.
		$atts = shortcode_atts( $defaults, $attr, $tag );

		// If there's no ACF Field attribute, show a message.
		if ( empty( $atts['field'] ) ) {
			return '<p>' . __( 'Please include an ACF Field attribute.', 'civicrm-wp-profile-sync' ) . '</p>';
		}

		// Get content from theme function.
		$content = cacf_get_address_by_type_id( $atts['field'], $atts['location_type'], $atts['post_id'] );

		// --<
		return $content;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Add compatibility with Shortcake.
	 *
	 * @since 0.4
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

		// Location Types select.
		$location_types = [
			'label'       => __( 'Location Type', 'civicrm-wp-profile-sync' ),
			'attr'        => 'location_type',
			'type'        => 'select',
			'options'     => $this->shortcake_select_location_types_get(),
			'description' => __( 'Please select a Location Type.', 'civicrm-wp-profile-sync' ),
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
			'label'         => esc_html__( 'CiviCRM Address', 'civicrm-wp-profile-sync' ),

			// Icon.
			'listItemImage' => 'dashicons-location',

			// Limit to synced CPTs only?
			// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
			// 'post_type' => array_values( $mapped_post_types ),

			// Window elements.
			'attrs'         => [
				$field,
				$location_types,
				$post_id,
			],

		];

		// Register Shortcake options.
		shortcode_ui_register_for_shortcode( $this->tag, $settings );

		// Register legacy Shortcake options.
		shortcode_ui_register_for_shortcode( $this->cai_tag, $settings );

	}

	/**
	 * Add stylesheet to TinyMCE when Shortcake is active.
	 *
	 * @since 0.4
	 *
	 * @param string $mce_css The existing list of stylesheets that TinyMCE will load.
	 * @return string $mce_css The modified list of stylesheets that TinyMCE will load.
	 */
	public function shortcake_styles( $mce_css ) {

		// Add our styles to TinyMCE.
		$mce_css .= ', ' . CIVICRM_WP_PROFILE_SYNC_PATH . 'assets/css/cwps-shortcode-address.css';

		// --<
		return $mce_css;

	}

	/**
	 * Get Location Types select array for Shortcake registration.
	 *
	 * @since 0.4
	 *
	 * @return array $options The properly formatted array for the select.
	 */
	public function shortcake_select_location_types_get() {

		// Init return.
		$options = [ '' => __( 'Select a Location Type', 'civicrm-wp-profile-sync' ) ];

		// Get Locations.
		$location_types = $this->plugin->civicrm->address->location_types_get();

		// Build Location Types choices array for dropdown.
		foreach ( $location_types as $location_type ) {
			$options[ $location_type['id'] ] = esc_attr( $location_type['display_name'] );
		}

		// --<
		return $options;

	}

}
