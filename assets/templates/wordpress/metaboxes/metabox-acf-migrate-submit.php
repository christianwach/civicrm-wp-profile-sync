<!-- assets/templates/wordpress/metaboxes/metabox-acf-migrate-submit.php -->
<div class="submitbox">
	<div id="minor-publishing">
		<div id="misc-publishing-actions">
			<div class="misc-pub-section">
				<span><?php _e( 'When you are ready to deactivate the CiviCRM ACF Integration plugin, click the "Migrate" button below.', 'civicrm-admin-utilities' ); ?></span>
			</div>
		</div>
		<div class="clear"></div>
	</div>

	<div id="major-publishing-actions">
		<div id="publishing-action">
			<?php submit_button( esc_html__( 'Migrate', 'civicrm-wp-profile-sync' ), 'primary', 'cwps_migrate_submit', false ); ?>
			<input type="hidden" name="action" value="migrate" />
		</div>
		<div class="clear"></div>
	</div>
</div>
