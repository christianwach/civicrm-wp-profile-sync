<?php
/**
 * CiviCRM Contact Field compatibility Class.
 *
 * Handles CiviCRM Contact Field integration.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Contact Field compatibility Class.
 *
 * This class provides CiviCRM Contact Field integration.
 *
 * @since 0.5
 */
class CiviCRM_WP_Profile_Sync_CiviCRM_Contact_Field {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $plugin;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object
	 */
	public $civicrm;

	/**
	 * Initialises this object.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin  = $parent->plugin;
		$this->civicrm = $parent;

		// Init when the CiviCRM object is loaded.
		add_action( 'cwps/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Do stuff on plugin init.
	 *
	 * @since 0.5
	 */
	public function initialise() {

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5.2
	 */
	public function register_hooks() {

	}

	/**
	 * Unregister hooks.
	 *
	 * @since 0.5.2
	 */
	public function unregister_hooks() {

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the Contact Field options for a given Field Name.
	 *
	 * @since 0.5
	 *
	 * @param string $name The name of the Contact Field.
	 * @return array $field The array of Contact Field data.
	 */
	public function get_by_name( $name ) {

		// Init return.
		$field = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $field;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'name'    => $name,
			'action'  => 'get',
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'getfield', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $field;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $field;
		}

		// The result set is the item.
		$field = $result['values'];

		// --<
		return $field;

	}

	/**
	 * Get "age" as a string for a given date.
	 *
	 * @since 0.5
	 *
	 * @param string $date The date in CiviCRM-style "Ymdhis" format.
	 * @return string $age_string The age expressed as a string.
	 */
	public function date_age_get( $date ) {

		// TODO: Duplicated in ACF.

		// Init return.
		$age_string = '';

		// CiviCRM has handy methods for this.
		$age_date = CRM_Utils_Date::customFormat( $date, '%Y%m%d' );
		$age      = CRM_Utils_Date::calculateAge( $age_date );
		$years    = CRM_Utils_Array::value( 'years', $age );
		$months   = CRM_Utils_Array::value( 'months', $age );

		// Maybe construct string from years.
		if ( $years ) {
			$age_string = sprintf(
				/* translators: %d: The number of years */
				_n( '%d year', '%d years', $years, 'civicrm-wp-profile-sync' ),
				$years
			);
		}

		// Maybe construct string from months.
		if ( $months ) {
			$age_string = sprintf(
				/* translators: %d: The number of months */
				_n( '%d month', '%d months', $months, 'civicrm-wp-profile-sync' ),
				$months
			);
		}

		// Maybe construct string for less than a month.
		if ( empty( $years ) && 0 === $months ) {
			$age_string = __( 'Under a month', 'civicrm-wp-profile-sync' );
		}

		// --<
		return $age_string;

	}

}
