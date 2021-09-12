/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM ACFE Country Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({
		type: 'cwps_acfe_address_country',
	});

	// Register it.
	acf.registerFieldType( Field );

})(jQuery);
