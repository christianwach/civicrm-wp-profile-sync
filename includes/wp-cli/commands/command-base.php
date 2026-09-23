<?php
/**
 * Base command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Base command class.
 *
 * @since 0.7.4
 *
 * @package CiviCRM_WP_Profile_Sync
 */
abstract class CiviCRM_WPPS_CLI_Command_Base extends \WP_CLI\CommandWithDBObject {

	/**
	 * Dependency check.
	 *
	 * @since 0.7.4
	 */
	public static function check_dependencies() {

		// Check for existence of CiviCRM.
		if ( ! function_exists( 'civicrm_initialize' ) ) {
			WP_CLI::error( 'Unable to find CiviCRM install.' );
		}

	}

	/**
	 * Bootstrap CiviCRM.
	 *
	 * @since 0.7.4
	 */
	protected function bootstrap_civicrm() {
		self::check_dependencies();
		if ( ! civicrm_initialize() ) {
			WP_CLI::error( 'Unable to initialize CiviCRM.' );
		}
	}

	/**
	 * Returns the timezone string for the current site.
	 *
	 * If a timezone identifier is used, then return it.
	 * If an offset is used, build a suitable timezone.
	 * If all else fails, uses UTC.
	 *
	 * @since 0.7.4
	 *
	 * @return string $tzstring The site timezone string.
	 */
	protected function site_timezone_get() {

		// Check our cached value first.
		$tzstring = wp_cache_get( 'cvwpps_timezone' );

		// Build value if none is cached.
		if ( false === $tzstring ) {

			// Get relevant WordPress settings.
			$tzstring = get_option( 'timezone_string' );
			$offset   = get_option( 'gmt_offset' );

			/*
			 * Setting manual offsets should be discouraged.
			 *
			 * The IANA timezone database that provides PHP's timezone support
			 * uses (reversed) POSIX style signs.
			 *
			 * @see https://github.com/stephenharris/Event-Organiser/issues/287
			 * @see https://www.php.net/manual/en/timezones.others.php
			 * @see https://bugs.php.net/bug.php?id=45543
			 * @see https://bugs.php.net/bug.php?id=45528
			 */
			// phpcs:ignore
			if ( empty( $tzstring ) && 0 != $offset && floor( $offset ) == $offset ) {
				$offset_string = $offset > 0 ? "-$offset" : '+' . absint( $offset );
				$tzstring      = 'Etc/GMT' . $offset_string;
			}

			// Default to "UTC" if the timezone string is empty.
			if ( empty( $tzstring ) ) {
				$tzstring = 'UTC';
			}

			// Cache timezone string.
			wp_cache_set( 'cvwpps_timezone', $tzstring );

		}

		// --<
		return $tzstring;

	}

	/**
	 * Gets the Formatter object for a given set of arguments.
	 *
	 * @since 0.7.4
	 *
	 * @param array $assoc_args The params passed to a command. Determines the formatting.
	 * @return \WP_CLI\Formatter
	 */
	protected function formatter_get( &$assoc_args ) {
		return new \WP_CLI\Formatter( $assoc_args, $this->obj_fields );
	}

}
