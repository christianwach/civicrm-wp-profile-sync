/**
 * CiviCRM Profile Sync ACFE Form Action.
 *
 * This is a modified version of the relevant code that can be found in
 * "acf-extended/assets/js/acfe-admin.js".
 *
 * It's necessary because the ACFE ACF Model cannot be addressed or extended.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

(function($) {

	// Bail if ACF isn't defined for some reason.
	if ( typeof acf === 'undefined' ) {
		return;
	}

	/**
	 * Create Settings class.
	 *
	 * @since 0.5
	 */
	function CWPS_ACFE_Form_Action_Settings() {

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
			if ('undefined' !== typeof CWPS_ACFE_Form_Action_Vars) {
				me.settings = CWPS_ACFE_Form_Action_Vars.settings;
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
		this.get_setting = function(identifier) {
			return me.settings[identifier];
		};

	}

	// Init Settings class.
	var cwps_form_action_settings = new CWPS_ACFE_Form_Action_Settings();
	cwps_form_action_settings.init();

	/**
	 * ACFE Form Contact Action Reference Field.
	 *
	 * @since 0.5
	 */
	var cwps_form_contact_action_ref = new acf.Model({

		/**
		 * Declare Actions.
		 *
		 * @since 0.5
		 */
		actions: cwps_form_action_settings.get_setting('contact_actions_reference'),

		/**
		 * Removes options from Reference Fields when a Contact Action is removed.
		 *
		 * @since 0.5
		 */
		removeContactActionAlias: function(field) {

			var val = field.val(),
				target,
				contact_refs;

			// Bail if there are no Contact Reference Fields.
			contact_refs = acf.getFields({type:'cwps_acfe_contact_action_ref'});
			if (!contact_refs.length) {
				return;
			}

			// Remove any options that have the value of this Field.
			for (item of contact_refs) {
				target = item.$el.find('option[value="' + val + '"]');
				if (target.length) {
					target.remove();
				}
			}

		},

		/**
		 * Updates Reference Fields when a Contact Action Name is altered.
		 *
		 * @since 0.5
		 */
		newContactActionAlias: function(field) {

			// Bridges the focus and blur values.
			var previous = '';

			/**
			 * Checks the current Contact Action Name.
			 *
			 * @since 0.5
			 */
			field.$input().on('focus', function() {

				var val = $(this).val();

				// Store the current value.
				if (val) {
					previous = val;
				} else {
					previous = '';
				}

			});

			/**
			 * Update Reference Fields if a Contact Action Name changes.
			 *
			 * @since 0.5
			 */
			field.$input().on('blur', function() {

				var val = $(this).val(),
					target,
					contact_refs;

				// Bail if there are no changes.
				if (previous === val) {
					return;
				}

				// Bail if there are no Contact Reference Fields.
				contact_refs = acf.getFields({type:'cwps_acfe_contact_action_ref'});
				if (!contact_refs.length) {
					previous = val;
					return;
				}

				// Is this a new value?
				if (previous === '' && val !== '') {

					// Add option to all Reference Fields.
					for (item of contact_refs) {
						option = $('<option></option>');
						option.html( val );
						option.attr('value', val);
						item.$el.find('select').append(option);
					}

				} else {

					// If previous and current values exist, value has changed.
					if (previous !== '' && val !== '') {

						// Update any options that have the previous value.
						for (item of contact_refs) {
							target = item.$el.find('option[value="' + previous + '"]');
							if (target.length) {
								target.html( val );
								target.attr('value', val);
							}
						}

					} else {

						// Restore Field to previous value.
						$(this).val(previous);
						return;

					}

				}

				// Update bridging var.
				previous = val;

			});

		},

		/**
		 * Populates new Contact Reference Fields with options for Contact Action Names.
		 *
		 * @since 0.5
		 */
		newContactActionRefField: function(field) {

			var contact_actions, option;

			// Bail if there are no Contact Actions.
			contact_actions = acf.getFields({key:'field_cwps_contact_action_custom_alias'});
			if (!contact_actions.length) {
				return;
			}

			// Add their Action Names to this Field's select.
			for (item of contact_actions) {
				if ( field.val() != item.val() ) {
					option = $('<option></option>');
					option.html( item.val() );
					option.attr('value', item.val());
					field.$el.find('select').append(option);
				}
			}

		}

	});

	/**
	 * ACFE Form Case Action Reference Field.
	 *
	 * @since 0.5
	 */
	var cwps_form_case_action_ref = new acf.Model({

		/**
		 * Declare Actions.
		 *
		 * @since 0.5
		 */
		actions: cwps_form_action_settings.get_setting('case_actions_reference'),

		/**
		 * Removes options from Reference Fields when a Case Action is removed.
		 *
		 * @since 0.5
		 */
		removeCaseActionAlias: function(field) {

			var val = field.val(),
				target,
				case_refs;

			// Bail if there are no Case Reference Fields.
			case_refs = acf.getFields({type:'cwps_acfe_case_action_ref'});
			if (!case_refs.length) {
				return;
			}

			// Remove any options that have the value of this Field.
			for (item of case_refs) {
				target = item.$el.find('option[value="' + val + '"]');
				if (target.length) {
					target.remove();
				}
			}

		},

		/**
		 * Updates Reference Fields when a Case Action Name is altered.
		 *
		 * @since 0.5
		 */
		newCaseActionAlias: function(field) {

			// Bridges the focus and blur values.
			var previous = '';

			/**
			 * Checks the current Case Action Name.
			 *
			 * @since 0.5
			 */
			field.$input().on('focus', function() {

				var val = $(this).val();

				// Store the current value.
				if (val) {
					previous = val;
				} else {
					previous = '';
				}

			});

			/**
			 * Update Reference Fields if a Case Action Name changes.
			 *
			 * @since 0.5
			 */
			field.$input().on('blur', function() {

				var val = $(this).val(),
					target,
					case_refs;

				// Bail if there are no changes.
				if (previous === val) {
					return;
				}

				// Bail if there are no Case Reference Fields.
				case_refs = acf.getFields({type:'cwps_acfe_case_action_ref'});
				if (!case_refs.length) {
					previous = val;
					return;
				}

				// Is this a new value?
				if (previous === '' && val !== '') {

					// Add option to all Reference Fields.
					for (item of case_refs) {
						option = $('<option></option>');
						option.html( val );
						option.attr('value', val);
						item.$el.find('select').append(option);
					}

				} else {

					// If previous and current values exist, value has changed.
					if (previous !== '' && val !== '') {

						// Update any options that have the previous value.
						for (item of case_refs) {
							target = item.$el.find('option[value="' + previous + '"]');
							if (target.length) {
								target.html( val );
								target.attr('value', val);
							}
						}

					} else {

						// Restore Field to previous value.
						$(this).val(previous);
						return;

					}

				}

				// Update bridging var.
				previous = val;

			});

		},

		/**
		 * Populates new Case Reference Fields with options for Case Action Names.
		 *
		 * @since 0.5
		 */
		newCaseActionRefField: function(field) {

			var case_actions, option;

			// Bail if there are no Case Actions.
			case_actions = acf.getFields({key:'field_cwps_case_action_custom_alias'});
			if (!case_actions.length) {
				return;
			}

			// Add their Action Names to this Field's select.
			for (item of case_actions) {
				if ( field.val() != item.val() ) {
					option = $('<option></option>');
					option.html( item.val() );
					option.attr('value', item.val());
					field.$el.find('select').append(option);
				}
			}

		}

	});

})(jQuery);
