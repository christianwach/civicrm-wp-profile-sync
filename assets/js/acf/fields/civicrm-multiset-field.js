/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Multiple Record Set Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

/**
 * Register ACF Field Type.
 *
 * @since 0.8
 */
(function($, undefined){

	// Extend the Repeater Field model.
	var Field = acf.models.RepeaterField.extend({
		type: 'civicrm_multiset',
	});

	// Register it.
	acf.registerFieldType( Field );

})(jQuery);

/**
 * Perform actions when dom_ready fires.
 *
 * @since 0.8
 */
jQuery(document).ready(function($) {

	/**
	 * Callback for clicks on the "Add Record Set" button.
	 *
	 * @since 0.8
	 *
	 * @param {Object} event The click event object.
	 */
	$('.acf-field.civicrm_multiset .acf-actions .acf-button.button-primary').click( function( event ) {

		console.log( 'Add Record Set clicked' );

	});

}); // End document.ready()
