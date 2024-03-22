<?php
/**
 * Edit Term template.
 *
 * Injects markup into the Edit Term page.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

?><!-- assets/templates/wordpress/term-edit.php -->
<tr class="form-field term-cwps-civicrm-group-wrap">
	<th scope="row"><label for="cwps-civicrm-group"><?php esc_html_e( 'CiviCRM Group for ACF Integration', 'civicrm-wp-profile-sync' ); ?></label></th>
	<td>
		<select name="cwps-civicrm-group" id="cwps-civicrm-group" class="postform">
			<?php if ( $group_id !== 0 ) : ?>
				<option value="0"><?php esc_html_e( 'None', 'civicrm-wp-profile-sync' ); ?></option>
			<?php else : ?>
				<option value="0" selected="selected"><?php esc_html_e( 'None', 'civicrm-wp-profile-sync' ); ?></option>
			<?php endif; ?>
			<?php if ( ! empty( $groups ) ) : ?>
				<?php foreach ( $groups as $group ) : ?>
					<?php if ( $group['id'] == $group_id ) : ?>
						<option value="<?php echo esc_attr( $group['id'] ); ?>" selected="selected"><?php echo esc_html( $group['title'] ); ?></option>
					<?php else : ?>
						<option value="<?php echo esc_attr( $group['id'] ); ?>"><?php echo esc_html( $group['title'] ); ?></option>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
		</select>
		<p class="description"><?php esc_html_e( 'When a CiviCRM Group is chosen, then any Contact that is a member of the chosen Group will have this term assigned to their synced Post.', 'civicrm-wp-profile-sync' ); ?></p>
	</td>
</tr>
