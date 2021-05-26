/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Contact Reference Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({
		type: 'civicrm_contact_ref',
	});

	// Register it.
	acf.registerFieldType( Field );

	// Add basic condition types.
	acf.registerConditionForFieldType( 'hasValue', 'civicrm_contact_ref' );
	acf.registerConditionForFieldType( 'hasNoValue', 'civicrm_contact_ref' );
	//acf.registerConditionForFieldType( 'SelectEqualTo', 'civicrm_contact_ref' );
	//acf.registerConditionForFieldType( 'SelectNotEqualTo', 'civicrm_contact_ref' );

})(jQuery);
