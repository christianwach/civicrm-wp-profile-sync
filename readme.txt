=== CiviCRM WordPress Profile Sync ===
Contributors: needle, cuny-academic-commons
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PZSKM8T5ZP3SC
Tags: civicrm, buddypress, user, profile, xprofile, sync
Requires at least: 3.9
Tested up to: 4.4
Stable tag: 0.2.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM WordPress Profile Sync keeps a WordPress and BuddyPress user profile in sync with a CiviCRM contact.


== Description ==

The CiviCRM WordPress Profile Sync plugin keeps the "First Name", "Last Name", "Email Address" and "Website" fields of a WordPress (and BuddyPress) user profile in sync with the corresponding fields of a CiviCRM contact. The synchronisation takes place regardless of whether the changes are made in WordPress, BuddyPress or CiviCRM.

### Requirements

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6-alpha1*. It also requires *BuddyPress 1.8* and the [BP XProfile WordPress User Sync](http://wordpress.org/plugins/bp-xprofile-wp-user-sync/) plugin for syncing data with BuddyPress profiles. Please refer to the installation page for how to use this plugin with versions of CiviCRM prior to 4.6-alpha1.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-wp-profile-sync).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

For versions of *CiviCRM* prior to 4.6-alpha1, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in *CiviCRM* file. Please refer to the each repo for further instructions.



== Changelog ==

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
