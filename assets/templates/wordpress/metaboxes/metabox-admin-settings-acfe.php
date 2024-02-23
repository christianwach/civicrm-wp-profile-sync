<?php
/**
 * Admin Settings page ACF Extended metabox template.
 *
 * Handles markup for the Admin Settings page ACF Extended metabox.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.6.6
 */

?><!-- assets/templates/wordpress/metaboxes/metabox-admin-settings-acfe.php -->
<table class="form-table">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Enable ACF Extended Forms Integration', 'civicrm-wp-profile-sync' ); ?>
		</th>
		<td>
			<p>
				<input type="checkbox" id="cwps_acfe_integration_enabled" name="cwps_acfe_integration_enabled" value="1"<?php checked( 1, $acfe_enabled ); ?>> <label for="cwps_acfe_integration_enabled"><?php esc_html_e( 'ACF Extended Forms Integration Enabled', 'civicrm-wp-profile-sync' ); ?></label>
			</p>
			<?php if ( 1 === $acfe_enabled ) : ?>
				<p class="description"><?php esc_html_e( 'Uncheck this if you do not need ACF Extended Forms Integration and want to completely disable it.', 'civicrm-wp-profile-sync' ); ?></p>
			<?php else : ?>
				<p class="description"><?php esc_html_e( 'Check this if you want to enable ACF Extended Forms Integration.', 'civicrm-wp-profile-sync' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
</table>

<table class="form-table cwps_acfe_transients">
	<tr>
		<th scope="row">
			<?php esc_html_e( 'Form Action cache', 'civicrm-wp-profile-sync' ); ?>
		</th>
		<td>
			<p>
				<input type="checkbox" id="cwps_acfe_integration_transients" name="cwps_acfe_integration_transients" value="1"<?php checked( 1, $acfe_transients ); ?>> <label for="cwps_acfe_integration_transients"><?php esc_html_e( 'Use a Form Action cache', 'civicrm-wp-profile-sync' ); ?></label>
			</p>
			<p class="description"><?php esc_html_e( 'The Form Actions that this plugin provides make a lot of database queries in order to expose the structure of CiviCRM Entities for use in ACF Extended Forms. When you are not actively changing the configuration of CiviCRM, you can enable this transient cache to minimise the number of database queries that are made.', 'civicrm-wp-profile-sync' ); ?></p>
		</td>
	</tr>
</table>
