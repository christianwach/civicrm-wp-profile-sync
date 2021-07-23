<?php
/**
 * ACF "Bypass" Location Type Class.
 *
 * Handles the "Bypass" ACF Location Type.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync ACF "Bypass" Location Rule Class.
 *
 * A class that handles the ACF "Bypass" Location Rule.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_Location_Type_Bypass extends ACF_Location {



	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialize() {

		// Set up this Location Rule.
		$this->name = 'form_civicrm';
		$this->label = __( 'CiviCRM Entity', 'civicrm-wp-profile-sync' );
		$this->category = 'forms';
		$this->object_type = 'civicrm';

	}



	// -------------------------------------------------------------------------



	/**
	 * Matches the provided rule against the screen args.
	 *
	 * Since this is not a true "location" but rather a way of linking a Field
	 * Group to a CiviCRM Entity, all that's needed is to test for the presence
	 * of the key in the "screen".
	 *
	 * @since 0.5
	 *
	 * @param array $rule The Location Rule.
	 * @param array $screen The screen args.
	 * @param array $field_group The field group settings.
	 * @return bool True if the rule applies, false otherwise.
	 */
	public function match( $rule, $screen, $field_group ) {

		// Bail if not the screen we're after.
		if ( ! array_key_exists( $this->name, $screen ) ) {
			return false;
		}

		// --<
		return true;

	}



	/**
	 * Returns an array of operators for this Location Type.
	 *
	 * Linking a Field Group to a CiviCRM Entity only require the equality operator.
	 *
	 * @since 0.5
	 *
	 * @param array $rule THe Location Rule.
	 * @return array The array of labelled operators.
	 */
	public static function get_operators( $rule ) {

		return [
			'==' => __( 'is', 'civicrm-wp-profile-sync' ),
		];

	}



	/**
	 * Returns an array of possible values for this location.
	 *
	 * @since 0.5
	 *
	 * @param array $rule The Location Rule.
	 * @return array
	 */
	public function get_values( $rule ) {

		/**
		 * Requests a nested array of possible values from Entity classes.
		 *
		 * @since 0.5
		 *
		 * @param array The empty default Entity values array.
		 * @param array $rule The current Location Rule.
		 */
		$entities = apply_filters( 'cwps/acf/bypass/location/query_entities', [], $rule );

		// --<
		return $entities;

	}



} // Class ends.



