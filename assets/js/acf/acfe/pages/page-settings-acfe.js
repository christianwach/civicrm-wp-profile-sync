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
		var acf_enabled = $('#cwps_acf_integration_checkbox'),
			acfe_enabled = $('#cwps_acfe_integration_enabled'),
			metabox = $('#cwps_acfe_integration');
			transients = $('table.cwps_acfe_transients');

		// Initial visibility toggles.
		if ( acf_enabled.prop( 'checked' ) ) {
			metabox.show();
		} else {
			metabox.hide();
		}
		if ( acfe_enabled.prop( 'checked' ) ) {
			transients.show();
		} else {
			transients.hide();
		}

		/**
		 * Add a click event listener to the "ACF Integration Enabled" checkbox.
		 *
		 * @since 0.6.6
		 *
		 * @param {Object} event The event object.
		 */
		acf_enabled.on( 'click', function( event ) {
			if ( acf_enabled.prop( 'checked' ) ) {
				metabox.show();
			} else {
				metabox.hide();
			}
		} );

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
