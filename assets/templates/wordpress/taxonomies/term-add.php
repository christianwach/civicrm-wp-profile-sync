<?php
/**
 * Add Term template.
 *
 * Injects markup into the Add Term page.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.6.4
 */

?><!-- assets/templates/wordpress/term-add.php -->
<div class="form-field term-cwps-civicrm-group-wrap">
	<label for="cwps-civicrm-group"><?php _e( 'CiviCRM Group for ACF Integration', 'civicrm-wp-profile-sync' ); ?></label>
	<select name="cwps-civicrm-group" id="cwps-civicrm-group" class="postform">
		<option value="0" selected="selected"><?php _e( 'None', 'civicrm-wp-profile-sync' ); ?></option>
		<?php if ( ! empty( $groups ) ) : ?>
			<?php foreach ( $groups AS $group ) : ?>
				<option value="<?php echo $group['id'] ?>"><?php echo $group['title']; ?></option>
			<?php endforeach; ?>
		<?php endif; ?>
	</select>
	<p class="description"><?php _e( 'When a CiviCRM Group is chosen here, then any Post which is given this term will have its synced Contact added to the Group. In CiviCRM, any Contact that is made a member of the chosen Group will have this term assigned to their synced Post.', 'civicrm-wp-profile-sync' ); ?></p>
</div>
