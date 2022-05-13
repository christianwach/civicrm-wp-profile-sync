<?php
/**
 * Admin Settings page Submit metabox template.
 *
 * Handles markup for the Admin Settings page Submit metabox.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

?><!-- assets/templates/wordpress/metaboxes/metabox-admin-settings-submit.php -->
<div class="submitbox">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">
				<span><?php esc_html_e( 'Save your settings here.', 'civicrm-wp-profile-sync' ); ?></span>
			</div>
		</div>
		<div class="clear"></div>
	</div>

	<div id="major-publishing-actions">
		<div id="publishing-action">
			<?php submit_button( esc_html__( 'Update', 'civicrm-wp-profile-sync' ), 'primary', 'cwps_save', false ); ?>
			<input type="hidden" name="action" value="update" />
		</div>
		<div class="clear"></div>
	</div>
</div>
