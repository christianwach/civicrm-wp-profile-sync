<?php
/**
 * ACF Mapped Post Type settings command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage the settings for mapped Post Types.
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
class CiviCRM_WPPS_CLI_Command_ACF_Setting extends CiviCRM_WPPS_CLI_Command {

	/**
	 * Gets the settings for a mapped Post Type.
	 *
	 * ## OPTIONS
	 *
	 * <post-type>
	 * : The slug of the Post Type.
	 *
	 * [--format=<format>]
	 * : Get the value in a particular format.
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
	 *     # Get the settings for the mapped Participant Post Type in JSON format.
	 *     $ wp profilesync acf setting get participant --format=json
	 *     {"enabled":0,"synced":1}
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function get( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		// Grab positional arguments.
		list( $post_type ) = $args;

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Check if valid.
		if ( ! $mapper->setting_exists( $post_type ) ) {
			WP_CLI::error( "Could not get settings for the '{$post_type}' Post Type. Is it mapped?" );
		}

		$value = $mapper->setting_get( $post_type );

		WP_CLI::print_value( $value, $assoc_args );

	}

	/**
	 * Creates a setting for a mapped Post Type.
	 *
	 * ## OPTIONS
	 *
	 * <post-type>
	 * : The slug of the Post Type.
	 *
	 * <key>
	 * : The setting key.
	 *
	 * [<value>]
	 * : The value. If omitted, the value is read from STDIN.
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
	 *     # Create the "audited" setting for the mapped Participant Post Type.
	 *     $ wp profilesync acf setting create participant audited 1
	 *     Success: Updated 'cwps_acf_mapping_settings' option.
	 *
	 * @alias set
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function create( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		// Grab positional arguments.
		$post_type = $args[0];
		$key       = $args[1];
		$value     = WP_CLI::get_value_from_arg_or_stdin( $args, 2 );
		$value     = WP_CLI::read_value( $value, $assoc_args );

		// Grab associative arguments.
		$format = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'plaintext' );

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Maybe get existing settings.
		$data = [];
		if ( $mapper->setting_exists( $post_type ) ) {
			$data = $mapper->setting_get( $post_type );
		}

		// Apply value.
		if ( ! isset( $data[ $key ] ) ) {
			$data[ $key ] = $value;
		} else {
			WP_CLI::error( "Setting '{$key}' for the '{$post_type}' Post Type already exists." );
		}

		if ( $mapper->setting_update( $post_type, $data ) ) {
			WP_CLI::success( "Created the '{$key}' setting for the '{$post_type}' Post Type." );
		} else {
			WP_CLI::error( "Could not creete setting '{$key}' for the '{$post_type}' Post Type." );
		}

	}

	/**
	 * Updates the settings for a mapped Post Type.
	 *
	 * ## OPTIONS
	 *
	 * <post-type>
	 * : The slug of the Post Type.
	 *
	 * <key>
	 * : The setting key.
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
	 *     # Update the "enabled" setting for the mapped Participant Post Type.
	 *     $ wp profilesync acf setting update participant enabled 1
	 *     Success: Updated 'cwps_acf_mapping_settings' option.
	 *
	 * @alias set
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function update( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		// Grab positional arguments.
		$post_type = $args[0];
		$key       = $args[1];
		$value     = WP_CLI::get_value_from_arg_or_stdin( $args, 2 );
		$value     = WP_CLI::read_value( $value, $assoc_args );

		// Grab associative arguments.
		$format = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'plaintext' );

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		if ( ! $mapper->setting_exists( $post_type ) ) {
			WP_CLI::error( "Could not find settings for the '{$post_type}' Post Type. Is it mapped?" );
		}

		$settings_key = $mapper->settings_key_get();

		// Use "wp option patch" to affect the setting.
		$command = "option patch update {$settings_key} {$post_type} {$key} {$value} --format={$format}";
		$options = [
			'launch' => false,
			'return' => false,
		];
		WP_CLI::runcommand( $command, $options );

	}

	/**
	 * Deletes the settings for a mapped Post Type.
	 *
	 * ## OPTIONS
	 *
	 * <post-type>
	 * : The slug of the Post Type.
	 *
	 * [<key>]
	 * : The setting key. If omitted, all settings for the mapped Post Type will be deleted.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete all settings for the mapped Participant Post Type.
	 *     $ wp profilesync acf setting delete participant
	 *     Success: Deleted all settings for the 'participant' Post Type.
	 *
	 *     # Delete the "audited" setting for the mapped Participant Post Type.
	 *     $ wp profilesync acf setting delete participant audited
	 *     Success: Deleted the 'audited' setting for the 'participant' Post Type.
	 *
	 * @alias unlink
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function delete( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		// Grab positional arguments.
		$post_type = $args[0];
		$key       = $args[1] ?? '';

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		if ( ! $mapper->setting_exists( $post_type ) ) {
			WP_CLI::error( "Could not find settings for the '{$post_type}' Post Type. Is it mapped?" );
		}

		// Key is omitted.
		if ( empty( $key ) ) {

			// Remove all settings for the mapped Post Type.
			if ( $mapper->setting_exists( $post_type ) ) {
				if ( $mapper->setting_remove( $post_type ) ) {
					WP_CLI::success( "Deleted all settings for the '{$post_type}' Post Type." );
				} else {
					WP_CLI::error( "Could not delete settings for the '{$post_type}' Post Type." );
				}
			} else {
				WP_CLI::error( "Settings for the '{$post_type}' Post Type do not exist." );
			}

		} else {

			// Remove a specific setting for the mapped Post Type.
			$settings = $mapper->setting_get( $post_type );
			if ( isset( $settings[ $key ] ) ) {
				unset( $settings[ $key ] );
				if ( $mapper->setting_update( $post_type, $settings ) ) {
					WP_CLI::success( "Deleted the '{$key}' setting for the '{$post_type}' Post Type." );
				} else {
					WP_CLI::error( "Could not delete setting '{$key}' for the '{$post_type}' Post Type." );
				}
			} else {
				WP_CLI::error( "Setting '{$key}' for the '{$post_type}' Post Type does not exist." );
			}

		}

	}

	/**
	 * Lists the mapped Post Type settings.
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
	 *     $ wp profilesync acf setting list --format=yaml
	 *     ---
	 *     participant:
	 *       enabled: 0
	 *       synced: 1
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function list( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		$value = $mapper->settings_get();

		WP_CLI::print_value( $value, $assoc_args );

	}

	/**
	 * Checks if a Post Type has already been mapped.
	 *
	 * @since 0.7.4
	 *
	 * @param int $post_type The slug of the Post Type.
	 * @return bool True if Post Type is mapped, false otherwise.
	 */
	private function post_type_is_mapped( $post_type ) {

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Get all mapped Post Types.
		$mapped_post_types = $mapper->mappings_get_all();

		// Cannot be used more than once.
		if ( in_array( $post_type, $mapped_post_types, true ) ) {
			return true;
		} else {
			return false;
		}

	}

}
