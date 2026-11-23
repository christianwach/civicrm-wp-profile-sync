<?php
/**
 * Entity Job command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Runs sync between CiviCRM Entity Types and WordPress Post Types.
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
 * @since 0.7.4
 *
 * @package CiviCRM_WP_Profile_Sync
 */
class CiviCRM_WPPS_CLI_Command_ACF_Job_Entity extends CiviCRM_WPPS_CLI_Command {

	/**
	 * Syncs CiviCRM Entity Types to WordPress Post Types.
	 *
	 * ## OPTIONS
	 *
	 * [<entity>]
	 * : The slug of the CiviCRM Entity Type.
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
	 *     # Run all entity sync jobs from CiviCRM to WordPress.
	 *     $ wp profilesync acf job entity civicrm-to-wp
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
		$format = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		$plugin = civicrm_wpps();

		WP_CLI::log( '' );
		WP_CLI::success( "Executed 'civicrm-to-wp' job." );

	}

	/**
	 * Syncs WordPress Post Types to CiviCRM Entity Types.
	 *
	 * ## OPTIONS
	 *
	 * [<entity>]
	 * : The slug of the WordPress Post Type.
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
	 *     # Run all entity sync jobs from WordPress to CiviCRM.
	 *     $ wp profilesync acf job entity wp-to-civicrm
	 *     Success: Executed 'wp-to-civicrm' job.
	 *
	 * @alias wp-to-civicrm
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function wp_to_civicrm( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		WP_CLI::error( 'Coming soon.' );

		// Grab associative arguments.
		$format = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		$plugin = civicrm_wpps();

		WP_CLI::log( '' );
		WP_CLI::success( "Executed 'wp-to-civicrm' job." );

	}

}
