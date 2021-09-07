/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Case Reference Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({
		type: 'cwps_acfe_case_action_ref',
	});

	// Register it.
	acf.registerFieldType( Field );

	// Add basic condition types.
	acf.registerConditionForFieldType( 'hasValue', 'cwps_acfe_case_action_ref' );
	acf.registerConditionForFieldType( 'hasNoValue', 'cwps_acfe_case_action_ref' );
	acf.registerConditionForFieldType( 'SelectEqualTo', 'cwps_acfe_case_action_ref' );
	acf.registerConditionForFieldType( 'SelectNotEqualTo', 'cwps_acfe_case_action_ref' );

})(jQuery);
