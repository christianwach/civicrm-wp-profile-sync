<?php
/**
 * Mapper ACF Hooks Class.
 *
 * All ACF callbacks are registered here. The data (particularly the data
 * coming from the CiviCRM callbacks) is first cast to standardised formats then
 * merged into an array and finally re-broadcast via custom actions.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync ACF Mapper Class.
 *
 * A class that encapsulates methods to keep a WordPress Entity synced with a
 * CiviCRM Entity via ACF Fields.
 *
 * @since 0.4
 */
class CiviCRM_WP_Profile_Sync_Mapper_Hooks_ACF {

	/**
	 * Plugin (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * Mapper (parent) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $mapper;

	/**
	 * Mapper Hooks object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $hooks;

	/**
	 * Define date format mappings (CiviCRM to ACF).
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $date_mappings = [
		'mm/dd/yy'    => 'm/d/Y',
		'dd/mm/yy'    => 'd/m/Y',
		'yy-mm-dd'    => 'Y-m-d',
		'dd-mm-yy'    => 'd-m-Y',
		'dd.mm.yy'    => 'd.m.Y',
		'M d, yy'     => 'M d, Y',
		'd M yy'      => 'j M Y',
		'MM d, yy'    => 'F j, Y',
		'd MM yy'     => 'd F Y',
		'DD, d MM yy' => 'l, d F Y',
		'mm/dd'       => 'm/d',
		'dd-mm'       => 'd-m',
		'M yy'        => 'm Y',
		'M Y'         => 'm Y',
		'yy'          => 'Y',
	];

	/**
	 * Define time format mappings (CiviCRM to ACF).
	 *
	 * @since 0.4
	 * @access public
	 * @var array
	 */
	public $time_mappings = [
		'1' => 'g:i a',
		'2' => 'H:i',
	];

	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references.
		$this->plugin = $parent->mapper->plugin;
		$this->mapper = $parent->mapper;
		$this->hooks  = $parent;

		// Init when this plugin is loaded.
		add_action( 'cwps/mapper/hooks/loaded', [ $this, 'initialise' ] );

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

		// Register ACF hooks.
		$this->hooks_acf_add();

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.4
	 */
	public function unregister_hooks() {

		// Unregister ACF hooks.
		$this->hooks_acf_remove();

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_acf_add() {

		// Callbacks for ACF Fields pre- and post-edited actions.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// add_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 5, 1 );
		add_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 20, 1 );

	}

	/**
	 * Remove WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function hooks_acf_remove() {

		// Remove callbacks for ACF Fields pre- and post-edited actions.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		// remove_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 5 );
		remove_action( 'acf/save_post', [ $this, 'acf_fields_saved' ], 20 );

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Fires when ACF Fields have been saved.
	 *
	 * @since 0.4
	 *
	 * @param integer $post_id The ACF "Post ID".
	 */
	public function acf_fields_saved( $post_id ) {

		// Bail if there was a Multisite switch.
		if ( is_multisite() && ms_is_switched() ) {
			return;
		}

		// Remove CiviCRM callbacks to prevent recursion.
		$this->hooks->core->hooks_civicrm_remove();

		// Let's make an array of the params.
		$args = [
			'post_id' => $post_id,
		];

		/**
		 * Broadcast that ACF Fields have been saved.
		 *
		 * @since 0.4
		 *
		 * @param array $args The array of WordPress params.
		 */
		do_action( 'cwps/mapper/acf_fields_saved', $args );

		// Reinstate CiviCRM callbacks.
		$this->hooks->core->hooks_civicrm_add();

	}

}
