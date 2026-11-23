<?php
/**
 * Setting command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage the CiviCRM Profile Sync plugin settings.
 *
 * ## EXAMPLES
 *
 *     # Get "Website Type" setting.
 *     $ wp profilesync setting get user_profile_website_type
 *     2
 *
 *     # Update the "Website Type" setting.
 *     $ wp profilesync setting update user_profile_website_type 2
 *     Success: Updated 'user_profile_website_type' setting.
 *
 *     # List the settings in YAML format.
 *     $ wp profilesync setting list --format=yaml
 *     ---
 *     user_profile_website_type: 2
 *     user_profile_email_sync: 1
 *     user_profile_nickname_sync: 0
 *     acf_integration_enabled: 1
 *     acfe_integration_enabled: 1
 *     acfe_integration_transients: 1
 *
 * @since 0.7.4
 *
 * @package CiviCRM_WP_Profile_Sync
 */
class CiviCRM_WPPS_CLI_Command_Setting extends CiviCRM_WPPS_CLI_Command {

	/**
	 * Gets the value for a CiviCRM Profile Sync setting.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : Key for the setting.
	 *
	 * [--default-value=<default-value>]
	 * : Optionally supply a default value.
	 *
	 * [--format=<format>]
	 * : Get value in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get "Website Type" setting.
	 *     $ wp profilesync setting get user_profile_website_type
	 *     2
	 *
	 *     # Get "ACF integration enabled" setting.
	 *     $ wp profilesync setting get acf_integration_enabled
	 *     1
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function get( $args, $assoc_args ) {

		// Grab positional arguments.
		list( $key ) = $args;

		// Grab associative arguments.
		$default_value = \WP_CLI\Utils\get_flag_value( $assoc_args, 'default-value', false );

		$plugin = civicrm_wp_profile_sync();

		// Check if valid.
		if ( ! $plugin->admin->setting_exists( $key ) ) {
			WP_CLI::error( "Could not get '{$key}' setting. Does it exist?" );
		}

		$value = $plugin->admin->setting_get( $key );

		WP_CLI::print_value( $value, $assoc_args );

	}

	/**
	 * Updates the value of a CiviCRM Profile Sync setting.
	 *
	 * ## OPTIONS
	 *
	 * <key>
	 * : The name of the setting to update.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Update the "Website Type" setting.
	 *     $ wp profilesync setting update user_profile_website_type 2
	 *     Success: Updated 'user_profile_website_type' setting.
	 *
	 * @alias set
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function update( $args, $assoc_args ) {

		// Grab positional arguments.
		$key   = $args[0];
		$value = WP_CLI::get_value_from_arg_or_stdin( $args, 1 );
		$value = WP_CLI::read_value( $value, $assoc_args );

		$plugin = civicrm_wp_profile_sync();

		// Check if setting exists.
		if ( ! $plugin->admin->setting_exists( $key ) ) {
			WP_CLI::error( "Could not update '{$key}' setting. Does it exist?" );
		}

		// Sanitise incoming value.
		$value = sanitize_text_field( $value );

		// Sanitise existing value.
		$old_value = sanitize_text_field( $plugin->admin->setting_get( $key ) );

		/*
		 * Currently all plugin settings are integers.
		 * Let's not assume that they will be in future.
		 */
		$integers = [
			'acf_integration_enabled',
			'acfe_integration_enabled',
			'acfe_integration_transients',
			'user_profile_email_sync',
			'user_profile_nickname_sync',
			'user_profile_website_type',
		];

		// Cast values as integers if required.
		if ( in_array( $key, $integers, true ) ) {
			$value     = (int) $value;
			$old_value = (int) $old_value;
		}

		// Skip if unchanged.
		if ( $value === $old_value ) {
			WP_CLI::success( "Value passed for '{$key}' setting is unchanged." );
			return;
		}

		// Let's set it now and save the settings.
		$plugin->admin->setting_set( $key, $value );
		if ( $plugin->admin->settings_save() ) {
			WP_CLI::success( "Updated '{$key}' setting." );
		} else {
			WP_CLI::error( "Could not update setting '{$key}'." );
		}

	}

	/**
	 * Lists the CiviCRM Profile Sync settings.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Get list in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List the settings in YAML format.
	 *     $ wp profilesync setting list --format=yaml
	 *     ---
	 *     user_profile_website_type: 2
	 *     user_profile_email_sync: 1
	 *     user_profile_nickname_sync: 0
	 *     acf_integration_enabled: 1
	 *     acfe_integration_enabled: 1
	 *     acfe_integration_transients: 1
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function list( $args, $assoc_args ) {

		$plugin = civicrm_wp_profile_sync();

		$value = $plugin->admin->settings_get();

		WP_CLI::print_value( $value, $assoc_args );

	}

}
