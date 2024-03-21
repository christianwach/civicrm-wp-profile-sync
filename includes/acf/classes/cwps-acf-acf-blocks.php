<?php
/**
 * ACF Blocks Class.
 *
 * Handles ACF Blocks functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync ACF Blocks Class.
 *
 * A class that encapsulates ACF Blocks functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Blocks {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * ACF object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object
	 */
	public $acf;

	/**
	 * Constructor.
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

		// Init when the parent class is loaded.
		add_action( 'cwps/acf/acf/loaded', [ $this, 'register_hooks' ] );

	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Can we add Blocks?
		if ( ! function_exists( 'acf_register_block_type' ) ) {
			return;
		}

		// Add some Blocks.
		add_action( 'acf/init', [ $this, 'register_blocks' ] );

	}

	// -------------------------------------------------------------------------

	/**
	 * Register some Blocks.
	 *
	 * @since 0.4
	 */
	public function register_blocks() {

		// Add some Blocks.

		// Define Block.
		$block = [
			'name' => 'cwps-phone',
			'title' => __( 'CiviCRM Phone', 'civicrm-wp-profile-sync' ),
			'description' => __( 'A custom phone block.', 'civicrm-wp-profile-sync' ),
			'render_callback' => [ 'CiviCRM_Profile_Sync_ACF_Blocks', 'block_test_render' ],
			'category' => 'common',
			'keywords' => [ 'civicrm' ],
			'post_types' => [ 'page' ],
		];

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'block' => $block,
			'callable' => is_callable( $block['render_callback'] ),
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

		// Register it.
		$result = acf_register_block_type( $block );

		/*
		$e = new \Exception();
		$trace = $e->getTraceAsString();
		$log = [
			'method' => __METHOD__,
			'result' => $result,
			//'backtrace' => $trace,
		];
		$this->plugin->log_error( $log );
		*/

	}

	/**
	 * Render a Test Block.
	 *
	 * @since 0.4
	 *
	 * @param array          $block The Block settings and attributes.
	 * @param string         $content The Block inner HTML (empty).
	 * @param bool           $is_preview True during AJAX preview.
	 * @param integer|string $post_id The Post ID this Block is saved to.
	 */
	public function block_test_render( $block, $content = '', $is_preview = false, $post_id = 0 ) {

		// Create ID attribute allowing for custom "anchor" value.
		$id = 'cwps-test-' . $block['id'];
		if ( ! empty( $block['anchor'] ) ) {
			$id = $block['anchor'];
		}

		// Create class attribute allowing for custom "className" and "align" values.
		$class_name = 'cwps-test-class';
		if ( ! empty( $block['className'] ) ) {
			$class_name .= ' ' . $block['className'];
		}
		if ( ! empty( $block['align'] ) ) {
			$class_name .= ' align' . $block['align'];
		}

		// Load values and assign defaults.
		// phpcs:ignore Squiz.Commenting.InlineComment.InvalidEndChar
		//$data = get_field( 'selector' );

		?>
		<div id="<?php echo esc_attr( $id ); ?>" class="<?php echo esc_attr( $class_name ); ?>">
			<span class="-text">Markup via complex class method</span>
		</div>
		<?php

	}

}
