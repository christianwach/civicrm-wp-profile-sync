<?php
/**
 * Add Term template.
 *
 * Injects markup into the Add Term page.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

?><!-- assets/templates/wordpress/term-participant-role-add.php -->
<div class="form-field term-participant-role-active-wrap">
	<label for="cwps-participant-role-active"><?php _e( 'Enabled?', 'civicrm-wp-profile-sync' ); ?></label>
	<input type="checkbox" class="settings-checkbox" name="cwps-participant-role-active" id="cwps-participant-role-active" value="1" />
</div>
<div class="form-field term-participant-role-counted-wrap">
	<label for="cwps-participant-role-counted"><?php _e( 'Counted?', 'civicrm-wp-profile-sync' ); ?></label>
	<input type="checkbox" class="settings-checkbox" name="cwps-participant-role-counted" id="cwps-participant-role-counted" value="1" />
</div>
