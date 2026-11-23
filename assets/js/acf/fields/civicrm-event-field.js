/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Participant Event Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({
		type: 'civicrm_event',
	});

	// Register it.
	acf.registerFieldType( Field );

	// Add basic condition types.
	acf.registerConditionForFieldType( 'hasValue', 'civicrm_event' );
	acf.registerConditionForFieldType( 'hasNoValue', 'civicrm_event' );
	acf.registerConditionForFieldType( 'SelectEqualTo', 'civicrm_event' );
	acf.registerConditionForFieldType( 'SelectNotEqualTo', 'civicrm_event' );

})(jQuery);
