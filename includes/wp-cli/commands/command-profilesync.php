<?php
/**
 * Command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage CiviCRM Profile Sync through the command-line.
 *
 * ## EXAMPLES
 *
 *     # Get "Website Type" setting.
 *     $ wp profilesync setting get user_profile_website_type
 *     2
 *
 *     # Get the Post Type mapped to the CiviCRM Contact Type with ID 4.
 *     $  wp profilesync acf mapping get contact --entity-type-id=4
 *     student
 *
 *     # Map the Student Post Type to the Student Contact Type.
 *     $ wp profilesync acf mapping create contact 4 student
 *     Success: Mapped the Post Type 'student' to Entity Type ID '4'.
 *
 *     # Run all Entity sync jobs from CiviCRM to WordPress.
 *     $ wp profilesync acf job entity civicrm-to-wp
 *     Success: Executed 'civicrm-to-wp' job.
 *
 *     # Run all Group-to-Term sync jobs from CiviCRM to WordPress.
 *     $ wp profilesync acf job group civicrm-to-wp
 *     Success: Executed 'civicrm-to-wp' job.
 *
 * @since 0.7.4
 *
 * @package CiviCRM_WP_Profile_Sync
 */
class CiviCRM_WPPS_CLI_Command extends CiviCRM_WPPS_CLI_Command_Base {

	/**
	 * Adds our description and sub-commands.
	 *
	 * @since 0.7.4
	 *
	 * @param object $command The command.
	 * @return array $info The array of information about the command.
	 */
	private function command_to_array( $command ) {

		$info = [
			'name'        => $command->get_name(),
			'description' => $command->get_shortdesc(),
			'longdesc'    => $command->get_longdesc(),
		];

		foreach ( $command->get_subcommands() as $subcommand ) {
			$info['subcommands'][] = $this->command_to_array( $subcommand );
		}

		if ( empty( $info['subcommands'] ) ) {
			$info['synopsis'] = (string) $command->get_synopsis();
		}

		return $info;

	}

}
