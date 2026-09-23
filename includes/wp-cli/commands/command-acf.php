<?php
/**
 * ACF command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage the sync between CiviCRM and WordPress when Advanced Custom Fields is active.
 *
 * ## EXAMPLES
 *
 *     # Get the settings for the mapped Participant Post Type in JSON format.
 *     $ wp profilesync acf setting get participant --format=json
 *     {"enabled":0,"synced":1}
 *
 *     # Create the "audited" setting for the mapped Participant Post Type.
 *     $ wp profilesync acf setting create participant audited 1
 *     Success: Updated 'cwps_acf_mapping_settings' option.
 *
 *     # Update the "enabled" setting for the mapped Participant Post Type.
 *     $ wp profilesync acf setting update participant enabled 1
 *     Success: Updated 'cwps_acf_mapping_settings' option.
 *
 *     # Delete the "audited" setting for the mapped Participant Post Type.
 *     $ wp profilesync acf setting delete participant audited
 *     Success: Deleted the 'audited' setting for the 'participant' Post Type.
 *
 *     # List the settings in YAML format.
 *     $ wp profilesync acf setting list --format=yaml
 *     ---
 *     participant:
 *       enabled: 0
 *       synced: 1
 *
 * @since 0.7.4
 *
 * @package CiviCRM_WP_Profile_Sync
 */
class CiviCRM_WPPS_CLI_Command_ACF extends CiviCRM_WPPS_CLI_Command {

}
