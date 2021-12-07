<?php
/**
 * Edit Term template.
 *
 * Injects markup into the Edit Term page.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

?><!-- assets/templates/wordpress/term-participant-role-edit.php -->
<tr class="form-field term-participant-role-active-wrap">
	<th scope="row"><label for="cwps-participant-role-active"><?php esc_html_e( 'Enabled?', 'civicrm-wp-profile-sync' ); ?></label></th>
	<td>
		<input type="checkbox" class="settings-checkbox" name="cwps-participant-role-active" id="cwps-participant-role-active" value="1"<?php echo $is_active; ?> />
	</td>
</tr>
<tr class="form-field term-participant-role-counted-wrap">
	<th scope="row"><label for="cwps-participant-role-counted"><?php esc_html_e( 'Counted?', 'civicrm-wp-profile-sync' ); ?></label></th>
	<td>
		<input type="checkbox" class="settings-checkbox" name="cwps-participant-role-counted" id="cwps-participant-role-counted" value="1"<?php echo $is_counted; ?> />
	</td>
</tr>
