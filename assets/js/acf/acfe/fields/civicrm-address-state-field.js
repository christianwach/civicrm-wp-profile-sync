/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM ACFE State Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($, undefined){

	// Bail if ACF isn't defined for some reason.
	if ( typeof acf === 'undefined' ) {
		return;
	}

	/**
	 * Create Settings class.
	 *
	 * @since 0.5
	 */
	function CWPS_ACFE_State_Settings() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.5
		 */
		this.init = function() {
			me.init_settings();
		};

		// Init settings array.
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 0.5
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof CWPS_ACFE_State_Vars ) {
				me.settings = CWPS_ACFE_State_Vars.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 0.5
		 *
		 * @param {String} The identifier for the desired setting.
		 * @return The value of the setting.
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

		/**
		 * Getter for retrieving the array of States for a given Country ID.
		 *
		 * @since 0.5
		 *
		 * @param {Integer} The numeric ID of the Country.
		 * @return {Array} The array of data for the States.
		 */
		this.get_states_for_country = function( country_id ) {
			var states = me.get_setting('states');
			if ( states[country_id] ) {
				return states[country_id];
			}
			return [];
		};

		// Init pseudocache.
		me.states_pseudocache = [];

		/**
		 * Getter for retrieving the array of States for a given Country ID.
		 *
		 * @since 0.5
		 *
		 * @param {Integer} country_id The numeric ID of the Country.
		 * @return {Array} The rendered markup for the States.
		 */
		this.get_states_markup = function( country_id ) {
			if ( me.states_pseudocache[country_id] ) {
				return me.states_pseudocache[country_id];
			}
			return [];
		};

		/**
		 * Setter for storing the markup of States for a given Country ID.
		 *
		 * @since 0.5
		 *
		 * @param {Integer} country_id The numeric ID of the Country.
		 * @param {Array} markup The rendered markup for the States.
		 */
		this.set_states_markup = function( country_id, markup ) {
			if ( ! me.states_pseudocache[country_id] ) {
				me.states_pseudocache[country_id] = markup;
			}
		};

	}

	// Init Settings class.
	var cwps_acfe_state_settings = new CWPS_ACFE_State_Settings();
	cwps_acfe_state_settings.init();

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({
		type: 'cwps_acfe_address_state',
	});

	// Register it.
	acf.registerFieldType( Field );

	// Add condition types.
	acf.registerConditionForFieldType( 'hasValue', 'cwps_acfe_address_state' );
	acf.registerConditionForFieldType( 'hasNoValue', 'cwps_acfe_address_state' );
	acf.registerConditionForFieldType( 'SelectEqualTo', 'cwps_acfe_address_state' );
	acf.registerConditionForFieldType( 'SelectNotEqualTo', 'cwps_acfe_address_state' );

	/**
	 * Acts when CiviCRM ACFE State Fields are ready.
	 *
	 * @since 0.5
	 *
	 * @param {Object} field The ACF Field object.
	 */
	acf.addAction( 'ready_field/type=cwps_acfe_address_state', function( field ) {

		var classes, classes_array, country_field_key = '', $country_field;

		// Get the declared classes.
		classes = field.$el.prop('class');
		if ( ! classes ) {
			return;
		}

		// Convert to array.
		classes_array = classes.split(' ');

		// Loop to find the one we want.
		for (var i = 0, item; item = classes_array[i++];) {
			if ( item.match( 'cwps-country-' ) ) {
				country_field_key = item.split( '-' )[2];
				break;
			}
		}

		// Bail if not found or "none".
		if ( country_field_key === '' || country_field_key === 'none' ) {
			return;
		}

		// Get the CiviCRM ACFE State Field.
		$country_field = acf.findField( country_field_key );

		/**
		 * Acts when the CiviCRM ACFE Country Field is changed.
		 *
		 * @since 0.5
		 *
		 * @param {Object} e The jQuery Event.
		 */
		$country_field.on( 'change', 'select', function( e ) {

			var country_id = $(this).val(), states, new_options = [];

			// Clear it.
			field.$el.val( null ).trigger( 'change' );
			field.select2.$el.val( null ).trigger( 'change' );
			field.select2.$el.children( 'option' ).remove();

			// Do we have existing markup?
			existing = cwps_acfe_state_settings.get_states_markup( country_id );
			if ( existing.length ) {
				field.select2.$el.append( existing ).trigger( 'change' );
				field.$el.val( null ).trigger( 'change' );
				field.select2.$el.val( null ).trigger( 'change' );
				return;
			}

			// Get States for this Country ID.
			states = cwps_acfe_state_settings.get_states_for_country( country_id );
			if ( ! states.length ) {
				return;
			}

			// Repopulate the options.
			new_options.push( new Option( '- ' + field.get( 'placeholder' ) + ' -', '', false, false ) );
			for ( data of states ) {
				new_options.push( new Option( data.text, data.id, false, false ) );
			}
			field.select2.$el.append( new_options ).trigger( 'change' );

			// Cache these.
			cwps_acfe_state_settings.set_states_markup( country_id, new_options );

		} );

	} );

})(jQuery);
