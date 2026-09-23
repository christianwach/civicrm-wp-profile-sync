<?php
/**
 * ACF Job command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Runs sync between CiviCRM and WordPress when Advanced Custom Fields is active.
 *
 * ## EXAMPLES
 *
 *     # Run all entity sync jobs from CiviCRM to WordPress.
 *     $ wp profilesync acf job entity civicrm-to-wp
 *     Success: Executed 'civicrm-to-wp' job.
 *
 *     # Run all entity sync jobs from WordPress to CiviCRM.
 *     $ wp profilesync acf job entity wp-to-civicrm
 *     Success: Executed 'wp-to-civicrm' job.
 *
 *     # Run all Group-to-Term sync jobs from CiviCRM to WordPress.
 *     $ wp profilesync acf job group civicrm-to-wp
 *     Success: Executed 'civicrm-to-wp' job.
 *
 * @since 0.7.4
 *
 * @package CiviCRM_WP_Profile_Sync
 */
class CiviCRM_WPPS_CLI_Command_ACF_Job extends CiviCRM_WPPS_CLI_Command {

}
