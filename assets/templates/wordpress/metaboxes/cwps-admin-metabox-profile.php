<!-- assets/templates/wordpress/metaboxes/cwps-admin-metabox-profile.php -->
<table class="form-table">
	<tr>
		<th scope="row"><?php _e( 'Website Type', 'civicrm-admin-utilities' ); ?></th>
		<td>
			<?php if ( ! empty( $options ) ) : ?>
				<p>
					<select id="cwps_website_type_select" name="cwps_website_type_select">
						<?php foreach( $options AS $key => $option ) : ?>
							<?php if ( $key === $selected ) : ?>
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
</table>
