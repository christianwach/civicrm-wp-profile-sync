/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Instant Messenger Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

/**
 * Register ACF Field Type.
 *
 * @since 0.7.3
 */
(function($, undefined){

	// Extend the Repeater Field model.
	var Field = acf.models.RepeaterField.extend({
		type: 'civicrm_im',
	});

	// Register it.
	acf.registerFieldType( Field );

})(jQuery);

/**
 * Perform actions when dom_ready fires.
 *
 * @since 0.7.3
 */
jQuery(document).ready(function($) {

	/**
	 * Set up click handler for the "Primary Instant Messenger" radio buttons.
	 *
	 * @since 0.7.3
	 *
	 * @param {Object} event The click event object.
	 */
	function cwps_acf_primary_im_selector() {

		// Declare vars.
		var scope = $('.acf-field.civicrm_im'),
			radios = '.acf-input ul.acf-radio-list li label input';

		// Unbind first to allow repeated calls to this function.
		scope.off( 'click', radios );

		/**
		 * Callback for clicks on the "Primary Instant Messenger" radio buttons.
		 *
		 * @since 0.7.3
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
	cwps_acf_primary_im_selector();

	/**
	 * Callback for clicks on the "Add Instant Messenger" button.
	 *
	 * @since 0.7.3
	 *
	 * @param {Object} event The click event object.
	 */
	$('.acf-field.civicrm_im .acf-actions .acf-button.button-primary').click( function( event ) {

		// Reset click handler because the DOM has been added to.
		cwps_acf_primary_im_selector();

	});

}); // End document.ready()
