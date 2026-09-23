<?php
/**
 * WP-CLI integration for this plugin.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Set up WP-CLI commands for this plugin.
 *
 * @since 0.7.4
 */
function civicrm_wpps_cli_bootstrap() {

	// Only do this once.
	static $done;
	if ( isset( $done ) && true === $done ) {
		return;
	}

	// Include files.
	require __DIR__ . '/commands/command-base.php';
	require __DIR__ . '/commands/command-profilesync.php';
	require __DIR__ . '/commands/command-setting.php';
	require __DIR__ . '/commands/command-acf.php';
	require __DIR__ . '/commands/command-acf-job.php';
	require __DIR__ . '/commands/command-acf-job-entity.php';
	require __DIR__ . '/commands/command-acf-job-group.php';
	require __DIR__ . '/commands/command-acf-mapping.php';
	require __DIR__ . '/commands/command-acf-setting.php';

	// ----------------------------------------------------------------------------
	// Add commands.
	// ----------------------------------------------------------------------------

	// Add top-level command.
	WP_CLI::add_command( 'profilesync', 'CiviCRM_WPPS_CLI_Command' );
	WP_CLI::add_command( 'cvwpps', 'CiviCRM_WPPS_CLI_Command' );

	// Add Setting command.
	WP_CLI::add_command( 'profilesync setting', 'CiviCRM_WPPS_CLI_Command_Setting', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_Setting::check_dependencies' ] );
	WP_CLI::add_command( 'cvwpps setting', 'CiviCRM_WPPS_CLI_Command_Setting', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_Setting::check_dependencies' ] );

	// Add ACF command.
	WP_CLI::add_command( 'profilesync acf', 'CiviCRM_WPPS_CLI_Command_ACF' );
	WP_CLI::add_command( 'cvwpps acf', 'CiviCRM_WPPS_CLI_Command_ACF' );

	// Add ACF Job command.
	WP_CLI::add_command( 'profilesync acf job', 'CiviCRM_WPPS_CLI_Command_ACF_Job' );
	WP_CLI::add_command( 'cvwpps acf job', 'CiviCRM_WPPS_CLI_Command_ACF_Job' );

	// Add ACF Entity Sync Job command.
	WP_CLI::add_command( 'profilesync acf job entity', 'CiviCRM_WPPS_CLI_Command_ACF_Job_Entity', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_ACF_Job_Entity::check_dependencies' ] );
	WP_CLI::add_command( 'cvwpps acf job entity', 'CiviCRM_WPPS_CLI_Command_ACF_Job_Entity', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_ACF_Job_Entity::check_dependencies' ] );

	// Add ACF Group Sync Job command.
	WP_CLI::add_command( 'profilesync acf job group', 'CiviCRM_WPPS_CLI_Command_ACF_Job_Group', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_ACF_Job_Group::check_dependencies' ] );
	WP_CLI::add_command( 'cvwpps acf job group', 'CiviCRM_WPPS_CLI_Command_ACF_Job_Group', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_ACF_Job_Group::check_dependencies' ] );

	// Add ACF Mapping command.
	WP_CLI::add_command( 'profilesync acf mapping', 'CiviCRM_WPPS_CLI_Command_ACF_Mapping', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_ACF_Mapping::check_dependencies' ] );
	WP_CLI::add_command( 'cvwpps acf mapping', 'CiviCRM_WPPS_CLI_Command_ACF_Mapping', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_ACF_Mapping::check_dependencies' ] );

	// Add ACF Setting command.
	WP_CLI::add_command( 'profilesync acf setting', 'CiviCRM_WPPS_CLI_Command_ACF_Setting', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_ACF_Setting::check_dependencies' ] );
	WP_CLI::add_command( 'cvwpps acf setting', 'CiviCRM_WPPS_CLI_Command_ACF_Setting', [ 'before_invoke' => 'CiviCRM_WPPS_CLI_Command_ACF_Setting::check_dependencies' ] );

	// We're done.
	$done = true;

}

// Set up commands.
WP_CLI::add_hook( 'before_wp_load', 'civicrm_wpps_cli_bootstrap' );
