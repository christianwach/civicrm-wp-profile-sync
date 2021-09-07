/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Activity Target Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({
		type: 'civicrm_activity_target',
	});

	// Register it.
	acf.registerFieldType( Field );

	// Add basic condition types.
	acf.registerConditionForFieldType( 'hasValue', 'civicrm_activity_target' );
	acf.registerConditionForFieldType( 'hasNoValue', 'civicrm_activity_target' );

})(jQuery);
