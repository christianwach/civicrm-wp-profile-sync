/**
 * CiviCRM Profile Sync Custom ACF Field Type - CiviCRM Participant Event Type Field.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.5
 *
 * @param {Object} $ The jQuery object.
 */
(function($) {

	/**
	 * Create Settings class.
	 *
	 * @since 0.5
	 */
	function CWPS_Event_Group_Settings() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * @since 0.5
		 */
		this.init = function() {
			me.init_localisation();
			me.init_settings();
		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * @since 0.5
		 */
		this.dom_ready = function() {};

		// Init localisation array.
		me.localisation = [];

		/**
		 * Init localisation from settings object.
		 *
		 * @since 0.5
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof CWPS_Event_Group_Vars ) {
				me.localisation = CWPS_Event_Group_Vars.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 0.5
		 *
		 * @param {String} identifier The identifier for the desired localisation string.
		 * @return {String} The localised string.
		 */
		this.get_localisation = function( identifier ) {
			return me.localisation[identifier];
		};

		// Init settings array.
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 0.5
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof CWPS_Event_Group_Vars ) {
				me.settings = CWPS_Event_Group_Vars.settings;
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

	}

	/**
	 * Create Select class.
	 *
	 * @since 0.5
	 */
	function CWPS_Event_Group_Select() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Select.
		 *
		 * @since 0.5
		 */
		this.init = function() {};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * @since 0.5
		 */
		this.dom_ready = function() {
			me.setup();
			me.listeners();
		};

		/**
		 * Do initial setup.
		 *
		 * @since 0.5
		 */
		this.setup = function() {

			// Assign properties.
			me.event_id = $('select[name*="field_cwps_participant_event_id"]');
			me.event_type = $('select[name*="field_cwps_participant_event_type"]');

		};

		/**
		 * Initialise listeners.
		 *
		 * @since 0.5
		 */
		this.listeners = function() {

			/**
			 * Add an onchange event listener to the "Event ID" select.
			 *
			 * @param {Object} event The event object.
			 */
			me.event_id.on( 'change', function( event ) {

				// Bail if nothing selected.
				if ( ! me.event_id.val() ) {
					me.event_type.val( '' );
					me.event_type.trigger( 'change' );
					return;
				}

				// Submit value to server.
				me.send( me.event_id.val() );

			});


		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.5
		 *
		 * @param {Mixed} value The value to send.
		 */
		this.send = function( value ) {

			// Define vars.
			var url, data;

			// URL to post to.
			url = CWPS_Event_Group_Settings.get_setting( 'ajax_url' );

			// Data received by WordPress.
			data = {
				action: 'event_type_get_value',
				value: value
			};

			// Use jQuery post method.
			$.post( url, data,

				/**
				 * AJAX callback which receives response from the server.
				 *
				 * @since 0.5
				 *
				 * @param {Array} data The value to send.
				 * @param {String} textStatus The status of the response.
				 */
				function( data, textStatus ) {
					if ( textStatus == 'success' ) {
						me.feedback( data );
					} else {
						if ( console.log ) {
							console.log( textStatus );
						}
					}
				},

				// Expected format.
				'json'

			);

		};

		/**
		 * Provide feedback given a set of data from the server.
		 *
		 * @since 0.5
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.feedback = function(data) {

			// Set the value of the select.
			if ( data.success ) {
				me.event_type.val( data.result );
				me.event_type.trigger( 'change' );
			}

		};

	}

	// Init Settings and Select classes.
	var CWPS_Event_Group_Settings = new CWPS_Event_Group_Settings();
	var CWPS_Event_Group_Select = new CWPS_Event_Group_Select();
	CWPS_Event_Group_Settings.init();
	CWPS_Event_Group_Select.init();

	/**
	 * Trigger dom_ready methods where necessary.
	 *
	 * @since 0.5
	 *
	 * @param {Object} $ The jQuery object.
	 */
	$(document).ready(function($) {
		CWPS_Event_Group_Settings.dom_ready();
		CWPS_Event_Group_Select.dom_ready();
	}); // End document.ready()

})(jQuery);
