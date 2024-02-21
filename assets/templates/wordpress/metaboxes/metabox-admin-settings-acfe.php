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
				<input type="checkbox" id="cwps_acfe_integration_checkbox" name="cwps_acfe_integration_checkbox" value="1"<?php echo $acfe_enabled_checked; ?>> <label for="cwps_acfe_integration_checkbox"><?php esc_html_e( 'ACF Extended Forms Integration Enabled', 'civicrm-wp-profile-sync' ); ?></label>
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
				<input type="checkbox" id="cwps_acfe_integration_transients" name="cwps_acfe_integration_transients" value="1"> <label for="cwps_acfe_integration_transients"><?php esc_html_e( 'Clear the Form Action cache', 'civicrm-wp-profile-sync' ); ?></label>
			</p>
			<p class="description"><?php esc_html_e( 'The Form Actions that this plugin provides to integrate CiviCRM Entities in ACF Extended Forms make use of transients to cache the many queries that they need to make. This means that, for example, if you add or modify a Custom Field, then you should check this box to clear this cache so that the changes show up in the Form Actions.', 'civicrm-wp-profile-sync' ); ?></p>
		</td>
	</tr>
</table>
