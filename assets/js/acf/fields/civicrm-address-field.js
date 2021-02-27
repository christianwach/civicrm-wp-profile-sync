/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Address Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

/**
 * Register ACF Field Type.
 *
 * @since 0.8.2
 */
(function($, undefined){

	// Extend the Repeater Field model.
	var Field = acf.models.RepeaterField.extend({
		type: 'civicrm_address',
	});

	// Register it.
	acf.registerFieldType( Field );

})(jQuery);

/**
 * Perform actions when dom_ready fires.
 *
 * @since 0.8.2
 */
jQuery(document).ready(function($) {

	/**
	 * Set up click handler for the "Primary Address" radio buttons.
	 *
	 * @since 0.8.2
	 *
	 * @param {Object} event The click event object.
	 */
	function cwps_acf_primary_address_selector() {

		// Declare vars.
		var scope = $('.acf-field.civicrm_address'),
			radios = '.acf-input ul.acf-radio-list li label input';

		// Unbind first to allow repeated calls to this function.
		scope.off( 'click', radios );

		/**
		 * Callback for clicks on the "Primary Address Record" radio buttons.
		 *
		 * @since 0.8.2
		 */
		scope.on( 'click', radios, function( event ) {

			// Prevent bubbling.
			event.stopPropagation();

			// Declare vars.
			var container, buttons;

			// Get container element.
			container = $(this).parents( 'table.acf-table tbody' );

			// Get radio button elements.
			buttons = $( 'ul.acf-radio-list li label input', container );

			// Set all radio buttons to unchecked.
			buttons.prop( 'checked', false );
			buttons.parent().removeClass( 'selected' );

			// Keep this radio button checked.
			$(this).prop( 'checked', true );
			$(this).parent().addClass( 'selected' );

		});

	}

	// Set up click handler immediately.
	cwps_acf_primary_address_selector();

	/**
	 * Callback for clicks on the "Add Address" button.
	 *
	 * @since 0.8.2
	 *
	 * @param {Object} event The click event object.
	 */
	$('.acf-field.civicrm_address .acf-actions .acf-button.button-primary').click( function( event ) {

		// Reset click handler because the DOM has been added to.
		cwps_acf_primary_address_selector();

	});

}); // End document.ready()
