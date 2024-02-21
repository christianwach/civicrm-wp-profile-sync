/**
 * Javascript for the Settings Page.
 *
 * Implements visibility toggles on the plugin's Settings Page.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.6.6
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Act on document ready.
	 *
	 * @since 0.6.6
	 */
	$(document).ready( function() {

		// Define vars.
		var acfe_enabled = $('#cwps_acfe_integration_checkbox'),
			transients = $('table.cwps_acfe_transients');

		// Initial visibility toggle.
		if ( acfe_enabled.prop( 'checked' ) ) {
			transients.show();
		} else {
			transients.hide();
		}

		/**
		 * Add a click event listener to the "ACF Extended Forms Integration Enabled" checkbox.
		 *
		 * @since 0.6.6
		 *
		 * @param {Object} event The event object.
		 */
		acfe_enabled.on( 'click', function( event ) {
			if ( acfe_enabled.prop( 'checked' ) ) {
				transients.show();
			} else {
				transients.hide();
			}
		} );

   	});

} )( jQuery );
