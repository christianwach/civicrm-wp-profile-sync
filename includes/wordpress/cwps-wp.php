<?php
/**
 * WordPress compatibility Class.
 *
 * Handles WordPress integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync WordPress compatibility Class.
 *
 * This class provides WordPress integration.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_WordPress {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * WordPress User object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $user The WordPress User object.
	 */
	public $user;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var bool $mapper_hooks The Mapper hooks registered flag.
	 */
	public $mapper_hooks = false;

	/**
	 * Initialises this object.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store reference.
		$this->plugin = $parent;

		// Boot when plugin is loaded.
		add_action( 'civicrm_wp_profile_sync_init', [ $this, 'initialise' ] );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Include files.
		$this->include_files();

		// Set up objects and references.
		$this->setup_objects();

		// Always register plugin hooks.
		add_action( 'cwps/plugin/hooks/wp/add', [ $this, 'register_mapper_hooks' ] );
		add_action( 'cwps/plugin/hooks/wp/remove', [ $this, 'unregister_mapper_hooks' ] );

		/**
		 * Broadcast that this class is now loaded.
		 *
		 * @since 0.4
		 */
		do_action( 'cwps/wordpress/loaded' );

	}

	/**
	 * Include files.
	 *
	 * @since 0.4
	 */
	public function include_files() {

		// Include class files.
		require CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/wordpress/cwps-wp-user.php';

	}

	/**
	 * Set up this plugin's objects.
	 *
	 * @since 0.4
	 */
	public function setup_objects() {

		// Initialise objects.
		$this->user = new CiviCRM_WP_Profile_Sync_WordPress_User( $this );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Add all callbacks.
		$this->user->register_hooks();

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Remove all callbacks.
		$this->user->unregister_hooks();

	}

	/**
	 * Register Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
			return;
		}

		// Declare registered.
		$this->mapper_hooks = true;

	}

	/**
	 * Unregister Mapper hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_hooks === false ) {
			return;
		}

		// Declare unregistered.
		$this->mapper_hooks = false;

	}

	/**
	 * Replaces paragraph elements with double line-breaks.
	 *
	 * This is the inverse behavior of the wpautop() function found in WordPress
	 * which converts double line-breaks to paragraphs. Handy when you want to
	 * undo whatever it did.
	 *
	 * Code via Frankie Jarrett on GitHub.
	 *
	 * @link https://gist.github.com/fjarrett/ecddd0ed419bb853e390
	 * @link https://core.trac.wordpress.org/ticket/25615
	 *
	 * @since 0.5
	 *
	 * @param string $text The string to match paragraphs tags in.
	 * @param bool $br (Optional) Whether to process line breaks.
	 * @return string
	 */
	public function unautop( $text, $br = true ) {

		// Bail if there's nothing to parse.
		if ( trim( $text ) === '' ) {
			return '';
		}

		// Match plain <p> tags and their contents (ignore <p> tags with attributes).
		$matches = preg_match_all( '/<(p+)*(?:>(.*)<\/\1>|\s+\/>)/m', $text, $text_parts );

		// Bail if no matches.
		if ( ! $matches ) {
			return $text;
		}

		// Init replacements array.
		$replace = [
			"\n" => '',
			"\r" => '',
		];

		// Maybe add breaks to replacements array.
		if ( $br ) {
			$replace['<br>'] = "\r\n";
			$replace['<br/>'] = "\r\n";
			$replace['<br />'] = "\r\n";
		}

		// Build keyed replacements.
		foreach ( $text_parts[2] as $i => $text_part ) {
			$replace[ $text_parts[0][ $i ] ] = $text_part . "\r\n\r\n";
		}

		// Do replacements.
		$replaced = str_replace( array_keys( $replace ), array_values( $replace ), $text );

		// --<
		return rtrim( $replaced );

	}

}
