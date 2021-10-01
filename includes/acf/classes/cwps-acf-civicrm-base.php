<?php
/**
 * CiviCRM Base Class.
 *
 * A class that holds methods common to CiviCRM Field classes.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Base Class.
 *
 * A class that is extended by CiviCRM classes that handle Fields separately to
 * the Contact class.
 *
 * The Fields in question must be excluded from the call to:
 *
 * civicrm_api( 'Contact', 'create', $params );
 *
 * Examples include:
 *
 * - Email
 * - Relationship
 * - Address
 *
 * @see CiviCRM_Profile_Sync_ACF_CiviCRM_Contact::prepare_from_fields()
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Base {

	/**
	 * Fields which must be handled separately.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $fields_handled The array of Fields which must be handled separately.
	 */
	public $fields_handled = [];



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 */
	public function __construct() {

		// Add callbacks.
		$this->fields_handled_hooks_add();

	}



	/**
	 * Register the "Handled Fields" callbacks.
	 *
	 * @since 0.4
	 */
	public function fields_handled_hooks_add() {

		// Add our handled Fields.
		add_filter( 'cwps/acf/civicrm/fields_handled', [ $this, 'fields_handled_add' ] );

		// Process our handled Fields.
		add_action( 'cwps/acf/contact/acf_fields_saved', [ $this, 'fields_handled_update' ], 10 );

	}



	/**
	 * Unregister the "Handled Fields" callbacks.
	 *
	 * @since 0.4
	 */
	public function fields_handled_hooks_remove() {

		// Remove our handled Fields.
		remove_filter( 'cwps/acf/civicrm/fields_handled', [ $this, 'fields_handled_add' ] );

		// Suspend processing our handled Fields.
		remove_action( 'cwps/acf/contact/acf_fields_saved', [ $this, 'fields_handled_update' ], 10 );

	}



	/**
	 * Getter method for the "Handled Fields" array.
	 *
	 * @since 0.4
	 *
	 * @return array $fields_handled The array of Contact Fields which must be handled separately.
	 */
	public function fields_handled_get() {

		// --<
		return $this->fields_handled;

	}



	/**
	 * Filter the "Handled Fields" array.
	 *
	 * @since 0.4
	 *
	 * @param array $fields_handled The existing array of Fields which must be handled separately.
	 * @return array $fields_handled The modified array of Fields which must be handled separately.
	 */
	public function fields_handled_add( $fields_handled = [] ) {

		// Add ours.
		$fields_handled = array_merge( $fields_handled, $this->fields_handled );

		// --<
		return $fields_handled;

	}



	/**
	 * Update a CiviCRM Contact's Fields with data from ACF Fields.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of WordPress params.
	 * @return bool True if updates were successful, or false on failure.
	 */
	public function fields_handled_update( $args ) {

	}



} // Class ends.



