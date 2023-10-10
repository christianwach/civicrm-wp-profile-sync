/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Yes/No.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Declare the Field type.
	var Radio = acf.getFieldType( 'radio' );
	var Field = Radio.extend({
		type: 'civicrm_yes_no',
	});

	// Register it.
	acf.registerFieldType( Field );

	// Get condition types.
	var EqualTo = acf.getConditionType( 'EqualTo' );
	var NotEqualTo = acf.getConditionType( 'NotEqualTo' );

	/**
	 *  CiviCRM Yes/No "EqualTo" condition.
	 *
	 *  @since 0.6.6
	 */
	var CiviCRMYesNoEqualTo = EqualTo.extend({
		type: 'CiviCRMYesNoEqualTo',
		choiceType: 'select',
		fieldTypes: [ 'civicrm_yes_no' ],
		choices: function( field ) {
			return [
				{
					id: 1,
					text: acf.__( 'Yes' )
				},
				{
					id: 0,
					text: acf.__( 'No' )
				},
				{
					id: 2,
					text: acf.__( 'Unknown' )
				}
			];
		}
	});

	/**
	 *  CiviCRM Yes/No "NotEqualTo" condition.
	 *
	 *  @since 0.6.6
	 */
	var CiviCRMYesNoNotEqualTo = NotEqualTo.extend({
		type: 'CiviCRMYesNoNotEqualTo',
		choiceType: 'select',
		fieldTypes: [ 'civicrm_yes_no' ],
		choices: function( field ) {
			return [
				{
					id: 1,
					text: acf.__( 'Yes' )
				},
				{
					id: 0,
					text: acf.__( 'No' )
				},
				{
					id: 2,
					text: acf.__( 'Unknown' )
				}
			];
		}
	});

	// Register condition types.
	acf.registerConditionType( CiviCRMYesNoEqualTo );
	acf.registerConditionType( CiviCRMYesNoNotEqualTo );

	// Add condition types.
	acf.registerConditionForFieldType( 'CiviCRMYesNoEqualTo', 'civicrm_yes_no' );
	acf.registerConditionForFieldType( 'CiviCRMYesNoNotEqualTo', 'civicrm_yes_no' );

})(jQuery);
