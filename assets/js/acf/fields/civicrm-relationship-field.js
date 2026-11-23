/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Relationship.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({
		type: 'civicrm_relationship',
	});

	// Register it.
	acf.registerFieldType( Field );

	// Add basic condition types.
	acf.registerConditionForFieldType( 'hasValue', 'civicrm_relationship' );
	acf.registerConditionForFieldType( 'hasNoValue', 'civicrm_relationship' );
	acf.registerConditionForFieldType( 'SelectEqualTo', 'civicrm_relationship' );
	acf.registerConditionForFieldType( 'SelectNotEqualTo', 'civicrm_relationship' );

})(jQuery);
