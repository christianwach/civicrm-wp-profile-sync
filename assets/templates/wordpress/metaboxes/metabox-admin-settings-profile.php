<!-- assets/templates/wordpress/metaboxes/metabox-admin-settings-profile.php -->
<table class="form-table">
	<tr>
		<th scope="row"><?php _e( 'Website Type', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<?php if ( ! empty( $options ) ) : ?>
				<p>
					<select id="cwps_website_type_select" name="cwps_website_type_select">
						<?php foreach( $options AS $key => $option ) : ?>
							<?php if ( $key === $website_type_selected ) : ?>
								<option value="<?php echo $key; ?>" selected="selected"><?php echo $option; ?></option>
							<?php else : ?>
								<option value="<?php echo $key; ?>"><?php echo $option; ?></option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="description"><?php _e( 'The CiviCRM Website Type that the WordPress User Profile Website syncs with.', 'civicrm-wp-profile-sync' ); ?></p>
			<?php endif; ?>
		</td>
	</tr>
	<tr>
		<th scope="row"><?php esc_html_e( 'Primary Email', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<p>
				<select id="cwps_email_sync_select" name="cwps_email_sync_select">
					<?php if ( $email_sync === 2 ) : ?>
						<option value="2" selected ="selected"><?php esc_html_e( '- Select Primary Email sync method -', 'civicrm-wp-profile-sync' ); ?></option>
					<?php endif; ?>
					<option value="1"<?php echo $email_sync_yes; ?>><?php esc_html_e( 'Yes, handle Primary Email sync (recommended)', 'civicrm-wp-profile-sync' ); ?></option>
					<option value="0"<?php echo $email_sync_no; ?>><?php esc_html_e( 'No, let CiviCRM handle Primary Email sync', 'civicrm-wp-profile-sync' ); ?></option>
				</select>
			</p>
			<p class="description"><?php esc_html_e( 'By default, CiviCRM is set to sync the Primary Email of a Contact to the email of a linked WordPress User. Unfortunately, CiviCRM is a bit clumsy in the way that it does this. Since you have CiviCRM Profile Sync installed, it is recommended that you let this plugin handle Primary Email sync for you.', 'civicrm-wp-profile-sync' ); ?></p>
		</td>
	</tr>
</table>
