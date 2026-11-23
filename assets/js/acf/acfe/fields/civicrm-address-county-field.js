/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM ACFE County Field.
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
	function CWPS_ACFE_County_Settings() {

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
			if ( 'undefined' !== typeof CWPS_ACFE_County_Vars ) {
				me.settings = CWPS_ACFE_County_Vars.settings;
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
		 * Getter for retrieving the array of Counties for a given State ID.
		 *
		 * @since 0.5
		 *
		 * @param {Integer} The numeric ID of the State.
		 * @return {Array} The array of data for the Counties.
		 */
		this.get_counties_for_state = function( state_id ) {
			var counties = me.get_setting('counties');
			if ( counties[state_id] ) {
				return counties[state_id];
			}
			return [];
		};

		// Init pseudocache.
		me.counties_pseudocache = [];

		/**
		 * Getter for retrieving the array of Counties for a given State ID.
		 *
		 * @since 0.5
		 *
		 * @param {Integer} state_id The numeric ID of the State.
		 * @return {Array} The rendered markup for the Counties.
		 */
		this.get_counties_markup = function( state_id ) {
			if ( me.counties_pseudocache[state_id] ) {
				return me.counties_pseudocache[state_id];
			}
			return [];
		};

		/**
		 * Setter for storing the markup of Counties for a given State ID.
		 *
		 * @since 0.5
		 *
		 * @param {Integer} state_id The numeric ID of the State.
		 * @param {Array} markup The rendered markup for the Counties.
		 */
		this.set_counties_markup = function( state_id, markup ) {
			if ( ! me.counties_pseudocache[state_id] ) {
				me.counties_pseudocache[state_id] = markup;
			}
		};

	}

	// Init Settings class.
	var cwps_acfe_county_settings = new CWPS_ACFE_County_Settings();
	cwps_acfe_county_settings.init();

	// Extend the Select Field model.
	var Field = acf.models.SelectField.extend({
		type: 'cwps_acfe_address_county',
	});

	// Register it.
	acf.registerFieldType( Field );

	// Add condition types.
	acf.registerConditionForFieldType( 'hasValue', 'cwps_acfe_address_county' );
	acf.registerConditionForFieldType( 'hasNoValue', 'cwps_acfe_address_county' );
	acf.registerConditionForFieldType( 'SelectEqualTo', 'cwps_acfe_address_county' );
	acf.registerConditionForFieldType( 'SelectNotEqualTo', 'cwps_acfe_address_county' );

	/**
	 * Acts when CiviCRM ACFE County Fields are ready.
	 *
	 * @since 0.5
	 *
	 * @param {Object} field The ACF Field object.
	 */
	acf.addAction( 'ready_field/type=cwps_acfe_address_county', function( field ) {

		var classes,
			classes_array,
			state_field_key = '',
			$state_field,
			initial_state_id,
			initial_counties,
			initial_options = [];

		// Get the declared classes.
		classes = field.$el.prop('class');
		if ( ! classes ) {
			return;
		}

		// Convert to array.
		classes_array = classes.split(' ');

		// Loop to find the one we want.
		for (var i = 0, item; item = classes_array[i++];) {
			if ( item.match( 'cwps-state-' ) ) {
				state_field_key = item.split('-')[2];
				break;
			}
		}

		// Bail if not found or "none".
		if ( state_field_key === '' || state_field_key === 'none' ) {
			return;
		}

		// Get the CiviCRM ACFE State Field.
		$state_field = acf.findField( state_field_key );

		// Does it have a value?
		initial_state_id = $state_field.find( 'select' ).val();
		if ( initial_state_id ) {

			// Get Counties for this State ID.
			initial_counties = cwps_acfe_county_settings.get_counties_for_state( initial_state_id );
			if ( initial_counties.length ) {

				// Populate the options.
				initial_options.push( new Option( '- ' + field.get( 'placeholder' ) + ' -', '', false, false ) );
				for ( data of initial_counties ) {
					initial_options.push( new Option( data.text, data.id, false, false ) );
				}
				field.$el.find( 'select' ).append( initial_options ).trigger( 'change' );

				// Cache these.
				cwps_acfe_county_settings.set_counties_markup( initial_state_id, initial_options );

			}

		}

		/**
		 * Acts when the CiviCRM ACFE State Field is changed.
		 *
		 * @since 0.5
		 *
		 * @param {Object} e The jQuery Event.
		 */
		$state_field.on( 'change', 'select', function( e ) {

			var state_id = $(this).val(), counties, new_options = [], data;

			// Clear it.
			field.$el.val( null ).trigger( 'change' );
			field.select2.$el.val( null ).trigger( 'change' );
			field.select2.$el.children( 'option' ).remove();

			// Do we have existing markup?
			existing = cwps_acfe_county_settings.get_counties_markup( state_id );
			if ( existing.length ) {
				field.select2.$el.append( existing ).trigger( 'change' );
				field.$el.val( null ).trigger( 'change' );
				field.select2.$el.val( null ).trigger( 'change' );
				return;
			}

			// Get Counties for this State ID.
			counties = cwps_acfe_county_settings.get_counties_for_state( state_id );
			if ( ! counties.length ) {
				return;
			}

			// Repopulate the options.
			new_options.push( new Option( '- ' + field.get( 'placeholder' ) + ' -', '', false, false ) );
			for ( data of counties ) {
				new_options.push( new Option( data.text, data.id, false, false ) );
			}
			field.select2.$el.append( new_options ).trigger( 'change' );

			// Cache these.
			cwps_acfe_county_settings.set_counties_markup( state_id, new_options );

		} );

	} );

})(jQuery);
