/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Attachment Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

/**
 * Register ACF Field Type.
 *
 * @since 0.5.2
 */
(function($, undefined){

	// Extend the Repeater Field model.
	var Field = acf.models.RepeaterField.extend({
		type: 'civicrm_attachment',
	});

	// Register it.
	acf.registerFieldType( Field );

})(jQuery);
