<?php
/**
 * Group-to-Term Job command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Runs sync between CiviCRM Groups and WordPress Terms.
 *
 * ## EXAMPLES
 *
 *     # Run all Group-to-Term sync jobs from CiviCRM to WordPress.
 *     $ wp profilesync acf job group civicrm-to-wp
 *     Success: Executed 'civicrm-to-wp' job.
 *
 * @since 0.7.4
 *
 * @package CiviCRM_WP_Profile_Sync
 */
class CiviCRM_WPPS_CLI_Command_ACF_Job_Group extends CiviCRM_WPPS_CLI_Command {

	/**
	 * Syncs CiviCRM Groups to WordPress Terms.
	 *
	 * ## OPTIONS
	 *
	 * [--group-id=<group-id>]
	 * : Restrict sync to a specific CiviCRM Group ID.
	 *
	 * [--format=<format>]
	 * : Render output in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - pretty
	 *   - json
	 *   - table
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Run all Group-to-Term sync jobs from CiviCRM to WordPress.
	 *     $ wp profilesync acf job group civicrm-to-wp
	 *     Success: Executed 'civicrm-to-wp' job.
	 *
	 * @alias civicrm-to-wp
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function civicrm_to_wp( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		WP_CLI::error( 'Coming soon.' );

		// Grab associative arguments.
		$group_id = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'group-id', 0 );
		$format   = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		$plugin = civicrm_wpps();

		WP_CLI::log( '' );
		WP_CLI::success( "Executed 'civicrm-to-wp' job." );

	}

}
