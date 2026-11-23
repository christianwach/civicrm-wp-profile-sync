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

	// Add condition types.
	acf.registerConditionForFieldType( 'hasValue', 'cwps_acfe_address_country' );
	acf.registerConditionForFieldType( 'hasNoValue', 'cwps_acfe_address_country' );
	acf.registerConditionForFieldType( 'SelectEqualTo', 'cwps_acfe_address_country' );
	acf.registerConditionForFieldType( 'SelectNotEqualTo', 'cwps_acfe_address_country' );

})(jQuery);
