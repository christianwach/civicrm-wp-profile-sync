/**
 * CiviCRM Profile Sync "Manual Sync" Javascript.
 *
 * Implements sync functionality on the CiviCRM Profile Sync "Manual Sync" page.
 *
 * @package WordPress
 * @subpackage CiviCRM_Profile_Sync
 */



/**
 * Create CiviCRM Profile Sync "Manual Sync" object.
 *
 * This works as a "namespace" of sorts, allowing us to hang properties, methods
 * and "sub-namespaces" from it.
 *
 * @since 0.4
 */
var CiviCRM_Profile_Sync_ACF_Sync = CiviCRM_Profile_Sync_ACF_Sync || {};



/**
 * Pass the jQuery shortcut in.
 *
 * @since 0.4
 *
 * @param {Object} $ The jQuery object.
 */
( function( $ ) {

	/**
	 * Create Settings Singleton.
	 *
	 * @since 0.4
	 */
	CiviCRM_Profile_Sync_ACF_Sync.settings = new function() {

		// Prevent reference collisions.
		var me = this;

		/**
		 * Initialise Settings.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.4
		 */
		this.init = function() {

			// Init localisation.
			me.init_localisation();

			// Init settings.
			me.init_settings();

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.4
		 */
		this.dom_ready = function() {

		};

		// Init localisation array.
		me.localisation = [];

		/**
		 * Init localisation from settings object.
		 *
		 * @since 0.4
		 */
		this.init_localisation = function() {
			if ( 'undefined' !== typeof CiviCRM_Profile_Sync_ACF_Sync_Vars ) {
				me.localisation = CiviCRM_Profile_Sync_ACF_Sync_Vars.localisation;
			}
		};

		/**
		 * Getter for localisation.
		 *
		 * @since 0.4
		 *
		 * @param {String} key The key for the desired localisation group.
		 * @param {String} identifier The identifier for the desired localisation string.
		 * @return {String} The localised string.
		 */
		this.get_localisation = function( key, identifier ) {
			return me.localisation[key][identifier];
		};

		// Init settings array.
		me.settings = [];

		/**
		 * Init settings from settings object.
		 *
		 * @since 0.4
		 */
		this.init_settings = function() {
			if ( 'undefined' !== typeof CiviCRM_Profile_Sync_ACF_Sync_Vars ) {
				me.settings = CiviCRM_Profile_Sync_ACF_Sync_Vars.settings;
			}
		};

		/**
		 * Getter for retrieving a setting.
		 *
		 * @since 0.4
		 *
		 * @param {String} The identifier for the desired setting.
		 * @return The value of the setting.
		 */
		this.get_setting = function( identifier ) {
			return me.settings[identifier];
		};

	};

	/**
	 * Create Progress Bar Class.
	 *
	 * @since 0.4
	 *
	 * @param {Object} options The setup options for the object.
	 */
	function CAI_ProgressBar( options ) {

		// Private var prevents reference collisions.
		var me = this;

		// Assign properties.
		me.bar = $(options.bar);
		me.label = $(options.label);

		// Assign labels.
		me.label_init = CiviCRM_Profile_Sync_ACF_Sync.settings.get_localisation( options.key, 'total' );
		me.label_current = CiviCRM_Profile_Sync_ACF_Sync.settings.get_localisation( options.key, 'current' );
		me.label_complete = CiviCRM_Profile_Sync_ACF_Sync.settings.get_localisation( options.key, 'complete' );
		me.label_done = CiviCRM_Profile_Sync_ACF_Sync.settings.get_localisation( 'common', 'done' );

		// Get count.
		me.count = options.count;

		// The triggering button.
		me.button = $(options.button);

		// The step setting.
		me.step = options.step;

		// The WordPress AJAX method token.
		me.action = options.action;

		// The Entity ID.
		me.entity_id = options.entity_id;

		// The AJAX nonce.
		me.ajax_nonce = me.button.data( 'security' );

		/**
		 * Add a click event listener to start sync.
		 *
		 * @param {Object} event The event object.
		 */
		me.button.on( 'click', function( event ) {

			// Prevent form submission.
			if ( event.preventDefault ) {
				event.preventDefault();
			}

			// Initialise progress bar.
			me.bar.progressbar({
				value: false,
				max: me.count
			});

			// Show progress bar if not already shown.
			me.bar.show();

			// Initialise progress bar label.
			me.label.html( me.label_init.replace( '{{total}}', me.count ) );

			// Send.
			me.send();

		});

		/**
		 * Send AJAX request.
		 *
		 * @since 0.4
		 *
		 * @param {Array} data The data received from the server.
		 */
		this.update = function( data ) {

			// Declare vars.
			var val;

			// Are we still in progress?
			if ( data.finished == 'false' ) {

				// Get current value of progress bar.
				val = me.bar.progressbar( 'value' ) || 0;

				// Update progress bar label.
				me.label.html(
					me.label_complete.replace( '{{from}}', data.from ).replace( '{{to}}', data.to )
				);

				// Update progress bar.
				me.bar.progressbar( 'value', val + CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( me.step ) );

				// Trigger next batch.
				me.send();

			} else {

				// Update progress bar label.
				me.label.html( me.label_done );

				// Hide the progress bar.
				setTimeout(function () {
					me.bar.hide();
				}, 2000 );

			}

		};

		/**
		 * Send AJAX request.
		 *
		 * @since 0.4
		 */
		this.send = function() {

			// Define vars.
			var url, data;

			// URL to post to.
			url = CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( 'ajax_url' );

			// Data received by WordPress.
			data = {
				action: me.action,
				entity_id: me.entity_id,
				_ajax_nonce: me.ajax_nonce
			};

			// Use jQuery post method.
			$.post( url, data,

				// Callback.
				function( data, textStatus ) {

					// If success.
					if ( textStatus == 'success' ) {

						// Update progress bar.
						me.update( data );

					} else {

						// Show error.
						if ( console.log ) {
							console.log( textStatus );
						}

					}

				},

				// Expected format.
				'json'

			);

		};

	};

	/**
	 * Create Progress Bar Singleton.
	 *
	 * @since 0.4
	 */
	CiviCRM_Profile_Sync_ACF_Sync.progress_bar = new function() {

		// Prevent reference collisions.
		var me = this;

		// Init bars array.
		me.bars = [];

		/**
		 * Initialise Progress Bar.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.4
		 */
		this.init = function() {

		};

		/**
		 * Do setup when jQuery reports that the DOM is ready.
		 *
		 * This method should only be called once.
		 *
		 * @since 0.4
		 */
		this.dom_ready = function() {

			// Set up instance.
			me.setup();

		};

		/**
		 * Set up Progress Bar instances.
		 *
		 * @since 0.4
		 */
		this.setup = function() {

			// Define vars.
			var contact_post_types, contact_types, groups, activity_post_types,
				activity_types, participant_post_types, participant_roles,
				prop, obj;

			// WordPress Posts to CiviCRM Contacts.
			contact_post_types = CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( 'contact_post_types' );
			for ( prop in contact_post_types ) {
				obj = new CAI_ProgressBar({
					bar: '#progress-bar-cwps_acf_post_to_contact_' + prop,
					label: '#progress-bar-cwps_acf_post_to_contact_' + prop + ' .progress-label',
					key: 'contact_post_types',
					button: '#cwps_acf_post_to_contact_' + prop,
					step: 'step_contact_post_types',
					action: 'sync_posts_to_contacts',
					entity_id: prop,
					count: contact_post_types[prop].count
				});
				me.bars.push( obj );
			}

			// CiviCRM Contacts to WordPress Posts.
			contact_types = CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( 'contact_types' );
			for ( prop in contact_types ) {
				obj = new CAI_ProgressBar({
					bar: '#progress-bar-cwps_acf_contact_to_post_' + prop,
					label: '#progress-bar-cwps_acf_contact_to_post_' + prop + ' .progress-label',
					key: 'contact_types',
					button: '#cwps_acf_contact_to_post_' + prop,
					step: 'step_contact_types',
					action: 'sync_contacts_to_posts',
					entity_id: prop,
					count: contact_types[prop].count
				});
				me.bars.push( obj );
			}

			// CiviCRM Groups to WordPress Terms.
			groups = CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( 'groups' );
			for ( prop in groups ) {
				obj = new CAI_ProgressBar({
					bar: '#progress-bar-cwps_acf_group_to_term_' + prop,
					label: '#progress-bar-cwps_acf_group_to_term_' + prop + ' .progress-label',
					key: 'groups',
					button: '#cwps_acf_group_to_term_' + prop,
					step: 'step_groups',
					action: 'sync_groups_to_terms',
					entity_id: prop,
					count: groups[prop].count
				});
				me.bars.push( obj );
			}

			// WordPress Posts to CiviCRM Activities.
			activity_post_types = CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( 'activity_post_types' );
			for ( prop in activity_post_types ) {
				obj = new CAI_ProgressBar({
					bar: '#progress-bar-cwps_acf_post_to_activity_' + prop,
					label: '#progress-bar-cwps_acf_post_to_activity_' + prop + ' .progress-label',
					key: 'activity_post_types',
					button: '#cwps_acf_post_to_activity_' + prop,
					step: 'step_activity_post_types',
					action: 'sync_posts_to_activities',
					entity_id: prop,
					count: activity_post_types[prop].count
				});
				me.bars.push( obj );
			}

			// CiviCRM Activities to WordPress Posts.
			activity_types = CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( 'activity_types' );
			for ( prop in activity_types ) {
				obj = new CAI_ProgressBar({
					bar: '#progress-bar-cwps_acf_activity_to_post_' + prop,
					label: '#progress-bar-cwps_acf_activity_to_post_' + prop + ' .progress-label',
					key: 'activity_types',
					button: '#cwps_acf_activity_to_post_' + prop,
					step: 'step_activity_types',
					action: 'sync_activities_to_posts',
					entity_id: prop,
					count: activity_types[prop].count
				});
				me.bars.push( obj );
			}

			// WordPress Posts to CiviCRM Participants.
			participant_post_types = CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( 'participant_post_types' );
			for ( prop in participant_post_types ) {
				obj = new CAI_ProgressBar({
					bar: '#progress-bar-cwps_acf_post_to_participant_role_' + prop,
					label: '#progress-bar-cwps_acf_post_to_participant_role_' + prop + ' .progress-label',
					key: 'participant_post_types',
					button: '#cwps_acf_post_to_participant_role_' + prop,
					step: 'step_participant_role_post_types',
					action: 'sync_posts_to_participant_roles',
					entity_id: prop,
					count: participant_post_types[prop].count
				});
				me.bars.push( obj );
			}

			// CiviCRM Participants to WordPress Posts.
			participant_roles = CiviCRM_Profile_Sync_ACF_Sync.settings.get_setting( 'participant_roles' );
			for ( prop in participant_roles ) {
				obj = new CAI_ProgressBar({
					bar: '#progress-bar-cwps_acf_participant_role_to_post_' + prop,
					label: '#progress-bar-cwps_acf_participant_role_to_post_' + prop + ' .progress-label',
					key: 'participant_roles',
					button: '#cwps_acf_participant_role_to_post_' + prop,
					step: 'step_participant_roles',
					action: 'sync_participant_roles_to_posts',
					entity_id: prop,
					count: participant_roles[prop].count
				});
				me.bars.push( obj );
			}

		};

	};

	// Init settings.
	CiviCRM_Profile_Sync_ACF_Sync.settings.init();

	// Init Progress Bar.
	CiviCRM_Profile_Sync_ACF_Sync.progress_bar.init();

} )( jQuery );



/**
 * Trigger dom_ready methods where necessary.
 *
 * @since 0.4
 */
jQuery(document).ready(function($) {

	// The DOM is loaded now.
	CiviCRM_Profile_Sync_ACF_Sync.settings.dom_ready();

	// The DOM is loaded now.
	CiviCRM_Profile_Sync_ACF_Sync.progress_bar.dom_ready();

}); // End document.ready()



