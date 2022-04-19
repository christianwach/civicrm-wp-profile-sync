=== CiviCRM Profile Sync ===
Contributors: needle, cuny-academic-commons, kcristiano, tadpolecc
Donate link: https://www.paypal.me/interactivist
Tags: civicrm, user, buddypress, acf, profile, xprofile, sync
Requires at least: 4.9
Tested up to: 5.9
Requires PHP: 7.1
Stable tag: 0.5.4a
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Keeps a WordPress User profile in sync with a CiviCRM Contact and integrates WordPress and CiviCRM Entities with data synced via Advanced Custom Fields.



== Description ==

### WordPress Integration

At its simplest, the CiviCRM Profile Sync plugin keeps the "First Name", "Last Name", "Nickname", "Email Address" and "Website" fields of a WordPress User Profile in sync with their corresponding fields in a CiviCRM Contact. The synchronisation takes place regardless of whether the changes are made in WordPress or CiviCRM.

### BuddyPress Integration

The plugin also supports syncing the "First Name" and "Last Name" fields of the WordPress User and CiviCRM Contact with BuddyPress when using the BP xProfile WordPress User Sync plugin. Further integration with BuddyPress is in the pipeline.

### ACF Integration

CiviCRM Profile Sync enables integration between CiviCRM Entities and WordPress Entities with data synced via Advanced Custom Fields.

Whilst ACF integration is not complete in its coverage of the CiviCRM Entities that can be linked, it is fairly comprehensive in its mapping of the built-in CiviCRM Custom Field Types with their corresponding ACF Field Types.

So if, for example, you want to display (or create) a Contact Type on your WordPress site with ACF Fields that contain synced CiviCRM data, this feature could work for you.

Please refer to the [ACF Integration Documentation](https://github.com/christianwach/civicrm-wp-profile-sync/blob/master/docs/ACF.md) for details.

### Form-building with ACF Extended

CiviCRM Profile Sync enables Forms to be built for the front-end of your website with the UI provided by the ACF Extended plugin. These Forms can send their data directly to CiviCRM in a similar (though more limited) way to Caldera Forms CiviCRM.

Form building with ACF Extended is at an early stage of development and is currently limited to submitting data for Contacts, Participants, Activities and Cases. This does, however, provide enough functionality to build some fairly powerful and useful Forms.

Please refer to the [ACFE Form-building Documentation](https://github.com/christianwach/civicrm-wp-profile-sync/blob/master/docs/ACFE.md) for details.

### Requirements

This plugin recommends a minimum of *WordPress 4.9* and *CiviCRM 5.23*. It also requires *BuddyPress 3.0* and the [BP XProfile WordPress User Sync](https://wordpress.org/plugins/bp-xprofile-wp-user-sync/) plugin for syncing "First Name" and "Last Name" with BuddyPress profiles.

### Plugin Development

This plugin is in active development. For feature requests and bug reports (or if you're a plugin author and want to contribute) please visit the plugin's [GitHub repository](https://github.com/christianwach/civicrm-wp-profile-sync).



== Installation ==

1. Extract the plugin archive
1. Upload plugin files to your `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress

### Activation

CiviCRM Profile Sync has some particular requirements when it comes to how it is activated. These correspond to the different types of WordPress install:

#### Single Site

Easy - just activate the plugin and you are done!

#### Multisite

Since Users and User metadata are stored in a single place for all sites, CiviCRM Profile Sync's User Profile settings cannot be configured on a per-site basis. This means that (whether CiviCRM Profile Sync is network-activated or not) whichever settings page you go to, you will see CiviCRM Profile Sync settings that are held in common for all sites.

It is, of course, possible to activate CiviCRM in many different ways in Multisite - it could be network-activated, activated on the main site, and/or activated on one or more sub-sites. CiviCRM could also be in Multi-Domain mode or each instance could have its own database. It is recommended that you activate CiviCRM Profile Sync in the same way that CiviCRM is activated.

If CiviCRM is not in Multi-Domain mode, you may have to write some custom code to propagate changed User details to other CiviCRM instances because (depending on the site on which a particular User's details are changed) only the Contact on the CiviCRM instance linked to that site will be updated.

Test early, test often and - above all - test on a development site first.

#### Multi-Network

In Multi-Network, Users and User metadata are stored in one place for all Networks but `site_options` are stored on a per-Network basis. As a result, it is not simple for CiviCRM Profile Sync to store a single collection of User Profile settings for all Networks. You will have to make sure that they are the same across all the Networks where both CiviCRM and this plugin are activated.

A consequence of this architecture is that (depending on how you have set up CiviCRM across the Networks) there may be mismatches between the User data in WordPress and the Contact data in your CiviCRM instances. If CiviCRM is not in Multi-Domain mode, you will have to write some custom code to keep the data synced between WordPress and your CiviCRM instances.

I'll say it again: test early, test often and - above all - test on a development site first.


== Changelog ==

= 0.5.3 =

* Fixes error on CiviCRM Activity Action.

= 0.5.2 =

* Improves handling of CiviCRM Websites.
* Improves handling of CiviCRM Contact Images.
* Misc bug fixes.

= 0.5.1 =

* Improves building forms with ACF Extended.
* Misc bug fixes.

= 0.5.0 =

* Enables building forms with ACF Extended.
* Introduces Participant Post Type sync via Advanced Custom Fields.

= 0.4.1 =

* Skips sync when CiviCRM creates a User.

= 0.4 =

* Introduces a Settings Page to set Website Type and Email sync options.
* Enables integration between CiviCRM Entities and WordPress Entities with data synced via Advanced Custom Fields.

= 0.3.3 =

* Sync First Name and Last Name when bulk creating WordPress users.

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

* Further fixes to bulk operations logic

= 0.2.2 =

* Minor fixes to bulk operations logic

= 0.2.1 =

* Initial WordPress plugin directory release

= 0.2 =

* Added two-way Website sync
* Fixed unpredictable detection of sync direction

= 0.1 =

* Initial release
