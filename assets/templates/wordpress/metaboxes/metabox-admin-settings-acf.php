<?php
/**
 * Admin Settings page ACF metabox template.
 *
 * Handles markup for the Admin Settings page ACF metabox.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

?><!-- assets/templates/wordpress/metaboxes/metabox-admin-settings-acf.php -->
<table class="form-table">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Enable ACF Integration', 'civicrm-wp-profile-sync' ); ?>
		</th>
		<td>
			<p>
				<input type="checkbox" id="cwps_acf_integration_checkbox" name="cwps_acf_integration_checkbox" value="1"<?php echo $acf_enabled_checked; ?>> <label for="cwps_acf_integration_checkbox"><?php esc_html_e( 'ACF Integration Enabled', 'civicrm-wp-profile-sync' ); ?></label>
			</p>
			<?php if ( 1 === $acf_enabled ) : ?>
				<p class="description"><?php esc_html_e( 'Uncheck this if you do not need ACF Integration and want to completely disable it.', 'civicrm-wp-profile-sync' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Check this if you want to enable ACF Integration.', 'civicrm-wp-profile-sync' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
</table>
