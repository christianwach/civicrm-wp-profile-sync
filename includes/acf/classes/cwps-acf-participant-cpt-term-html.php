<?php
/**
 * Term Description Class.
 *
 * Replicates the functionality of WooDojo HTML Term Description plugin.
 *
 * @package CiviCRM_WP_Event_Organiser
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Participant Role Term Description Class.
 *
 * This class replicates the functionality of WooDojo HTML Term Description
 * plugin since that plugin has now been withdrawn. It was described thus:
 *
 * "The WooDojo HTML term description feature adds the ability to use html in
 * term descriptions, as well as a visual editor to make input easier."
 *
 * The difference here is that only the Participant Role custom taxonomy is
 * affected.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_CPT_Term_HTML {

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
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Participant object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $participant;

	/**
	 * Custom Post Type object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $cpt;

	/**
	 * Taxonomy name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string
	 */
	public $taxonomy_name;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin      = $parent->acf_loader->plugin;
		$this->acf_loader  = $parent->acf_loader;
		$this->civicrm     = $parent->civicrm;
		$this->participant = $parent->participant;
		$this->cpt         = $parent;

		// Store Taxonomy name.
		$this->taxonomy_name = $parent->taxonomy_name;

		// Init when the Participant CPT object is loaded.
		add_action( 'cwps/acf/civicrm/participant-cpt/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Bail if CPT not enabled.
		if ( false === $this->cpt->enabled ) {
			return;
		}

		// Register hooks on admin init.
		add_action( 'admin_init', [ $this, 'register_hooks' ] );

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Look for an existing WooDojo HTML Term Description install.
		if ( class_exists( 'WooDojo_HTML_Term_Description' ) ) {
			return;
		}

		// Bail if user doesn't have the "unfiltered_html" capability.
		if ( ! current_user_can( 'unfiltered_html' ) ) {
			return;
		}

		// Allow HTML in term descriptions.
		remove_filter( 'pre_term_description', 'wp_filter_kses' );
		remove_filter( 'term_description', 'wp_kses_data' );

		// Add TinyMCE to the Participant Role taxonomy.
		add_action( $this->taxonomy_name . '_edit_form_fields', [ $this, 'render_field_edit' ], 1, 2 );
		add_action( $this->taxonomy_name . '_add_form_fields', [ $this, 'render_field_add' ], 1, 1 );

	}

	// -------------------------------------------------------------------------

	/**
	 * Add the WYSIWYG editor to the "Edit" Field.
	 *
	 * @since 0.5
	 *
	 * @param object $term The WordPress Term.
	 * @param string $taxonomy The WordPress Taxonomy.
	 */
	public function render_field_edit( $term, $taxonomy ) {

		$settings = [
			'textarea_name' => 'description',
			'quicktags'     => true,
			'tinymce'       => true,
			'editor_css'    => '<style>#wp-html-description-editor-container .wp-editor-area { height: 200px; }</style>',
		];

		?>
		<tr>
			<th scope="row" valign="top"><label for="description"><?php echo esc_html_x( 'Description', 'Taxonomy Description', 'civicrm-wp-profile-sync' ); ?></label></th>
			<td><?php wp_editor( htmlspecialchars_decode( $term->description ), 'html-description', $settings ); ?>
			<span class="description"><?php esc_html_e( 'The description is not prominent by default, however some themes may show it.', 'civicrm-wp-profile-sync' ); ?></span></td>
			<script type="text/javascript">
				// Remove the non-HTML Field.
				jQuery( 'textarea#description' ).closest( '.form-field' ).remove();
			</script>
		</tr>
		<?php

	}

	/**
	 * Add the WYSIWYG editor to the "Add" Field.
	 *
	 * @since 0.5
	 *
	 * @param string $taxonomy The WordPress Taxonomy.
	 */
	public function render_field_add( $taxonomy ) {

		$settings = [
			'textarea_name' => 'description',
			'quicktags'     => true,
			'tinymce'       => true,
			// 'editor_css' => '<style>#wp-html-tag-description-editor-container .wp-editor-area { height: 200px; }</style>',
		];

		?>
		<div class="form-field term-description-wrap">
			<label for="tag-description"><?php echo esc_html_x( 'Description', 'Taxonomy Description', 'civicrm-wp-profile-sync' ); ?></label>
			<?php wp_editor( '', 'html-tag-description', $settings ); ?>
			<p class="description"><?php esc_html_e( 'The description is not prominent by default, however some themes may show it.', 'civicrm-wp-profile-sync' ); ?></p>
			<script type="text/javascript">
				// Remove the non-HTML Field.
				jQuery( 'textarea#tag-description' ).closest( '.form-field' ).remove();
				// Trigger save.
				jQuery( function() {
					// This fires when submitted via keyboard.
					jQuery( '#addtag' ).on( 'keydown', '#submit', function() {
						tinyMCE.triggerSave();
					});
					// This does not fire when submitted via keyboard.
					jQuery( '#addtag' ).on( 'mousedown', '#submit', function() {
						tinyMCE.triggerSave();
					});
				});
			</script>
		</div>
		<?php

	}

}
