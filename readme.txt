=== CiviCRM WordPress Profile Sync ===
Contributors: needle
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=PZSKM8T5ZP3SC
Tags: civicrm, buddypress, user, profile, xprofile, sync
Requires at least: 3.5
Tested up to: 3.6
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

CiviCRM WordPress Profile Sync keeps a WordPress and BuddyPress user profile in sync with a CiviCRM contact


== Description ==

The CiviCRM WordPress Profile Sync plugin keeps the "First Name", "Last Name", "Email Address" and "Website" elments of a WordPress (and BuddyPress) user profile in sync with a CiviCRM contact.

This plugin  requires a minimum of *WordPress 3.6*, *BuddyPress 1.8* and *CiviCRM 4.3.5*. It requires the master branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in *CiviCRM* file. It also requires the [BP XProfile WordPress User Sync plugin](http://wordpress.org/plugins/bp-xprofile-wp-user-sync/) for consistency with BuddyPress profiles.



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress



== Changelog ==

= 0.2 =

Added two-way Website sync
Fixed unpredictable detection of sync direction

= 0.1 =

Initial release
