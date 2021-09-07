/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Contact Reference Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({

		type: 'cwps_acfe_contact_action_ref',

		/*
		render: function() {
			console.log( 'here', here );
			acf.models.SelectField.render();
		}

		wait: 'load',

		actions: {
			'new_field': 'newField'
		},

		newField: function(field) {

		}
		*/

	});

	// Register it.
	acf.registerFieldType( Field );

	// Add basic condition types.
	acf.registerConditionForFieldType( 'hasValue', 'cwps_acfe_contact_action_ref' );
	acf.registerConditionForFieldType( 'hasNoValue', 'cwps_acfe_contact_action_ref' );
	acf.registerConditionForFieldType( 'SelectEqualTo', 'cwps_acfe_contact_action_ref' );
	acf.registerConditionForFieldType( 'SelectNotEqualTo', 'cwps_acfe_contact_action_ref' );

})(jQuery);
