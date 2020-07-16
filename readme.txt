=== CiviCRM WordPress Profile Sync ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.me/interactivist
Tags: civicrm, buddypress, user, profile, xprofile, sync
Requires at least: 3.9
Tested up to: 5.5
Stable tag: 0.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

CiviCRM WordPress Profile Sync keeps a WordPress and BuddyPress user profile in sync with a CiviCRM contact.


== Description ==

The CiviCRM WordPress Profile Sync plugin keeps the "First Name", "Last Name", "Email Address" and "Website" fields of a WordPress (and BuddyPress) user profile in sync with the corresponding fields of a CiviCRM contact. The synchronisation takes place regardless of whether the changes are made in WordPress, BuddyPress or CiviCRM.

### Requirements

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6*. It also requires *BuddyPress 3.0* and the [BP XProfile WordPress User Sync](https://wordpress.org/plugins/bp-xprofile-wp-user-sync/) plugin for syncing data with BuddyPress profiles.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-wp-profile-sync).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress




== Changelog ==

= 0.3.2 =

* Fix broken method reference

= 0.3.1 =

* Explicitly suspend CiviCRM's callbacks when updating a WordPress user

= 0.3 =

* Introduces a set of "should_be_synced" filters

= 0.2.8 =

* "Bulk Add" functionality rewritten

= 0.2.7 =

* Introduces "civicrm_wp_profile_sync_primary_email_pre_update" action

= 0.2.6 =

* Allows plugin constants to be pre-defined
* Allow plugins to provide a unique username
* Provides new filters to customise bulk import process

= 0.2.5 =

* Makes synced usernames URL-friendly

= 0.2.4 =

* Adds hooks before and after sync operations
* Updates hook references for CiviCRM 4.7.x instances

= 0.2.3 =

Further fixes to bulk operations logic

= 0.2.2 =

Minor fixes to bulk operations logic

= 0.2.1 =

Initial WordPress plugin directory release

= 0.2 =

Added two-way Website sync
Fixed unpredictable detection of sync direction

= 0.1 =

Initial release
