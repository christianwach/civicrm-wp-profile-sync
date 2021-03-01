CiviCRM Profile Sync
====================

**Contributors:** [needle](https://profiles.wordpress.org/needle/), [cuny-academic-commons](https://profiles.wordpress.org/cuny-academic-commons/)<br/>
**Donate link:** https://www.paypal.me/interactivist<br/>
**Tags:** civicrm, acf, sync<br/>
**Requires at least:** 4.9<br/>
**Tested up to:** 5.7<br/>
**Stable tag:** 0.4<br/>
**License:** GPLv2 or later<br/>
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Keeps a WordPress User profile in sync with CiviCRM Contact info and enables integration between CiviCRM Entities and WordPress Entities with data synced via Advanced Custom Fields.



## Description

Please note: this is the development repository for CiviCRM Profile Sync. The plugin can be found in the [WordPress Plugin Directory](https://wordpress.org/plugins/civicrm-wp-profile-sync/), which is the best place to get it from if you're not a developer.

### WordPress Integration

At its simplest, the CiviCRM Profile Sync plugin keeps the "First Name", "Last Name", "Nickname", "Email Address" and "Website" fields of a WordPress User Profile in sync with their corresponding fields in a CiviCRM Contact. The synchronisation takes place regardless of whether the changes are made in WordPress or CiviCRM.

### BuddyPress Integration

The plugin also supports syncing the "First Name" and "Last Name" fields of the WordPress User and CiviCRM Contact with BuddyPress when using the BP xProfile WordPress User Sync plugin. Further integration with BuddyPress is in the pipeline.

### ACF Integration

CiviCRM Profile Sync also enables integration between CiviCRM Entities and WordPress Entities with data synced via Advanced Custom Fields.

Please be aware that ACF integration is at an early stage of development and (although it is limited in its coverage of the entities that can be linked) it is fairly comprehensive in its mapping of the built-in CiviCRM Custom Field Types with their corresponding ACF Field Types.

So if, for example, you want to display (or create) a Contact Type on your WordPress site with ACF Fields that contain synced CiviCRM data, this plugin could work for you.

Please refer to the [ACF Integration Documentation](/docs/ACF.md) for details.



### Requirements

This plugin recommends a minimum of WordPress 4.9 and CiviCRM 5.23.

If you want to maintain sync with BuddyPress user profiles, then it also requires BuddyPress 3.0 and the [BP xProfile WordPress User Sync](https://wordpress.org/plugins/bp-xprofile-wp-user-sync/) plugin to do so.

For integration with Advanced Custom Fields, this plugin recommends a minimum of ACF 5.8 or ACF Pro 5.8.



## Installation

There are two ways to install from GitHub:

### ZIP Download

If you have downloaded CiviCRM Profile Sync as a ZIP file from the GitHub repository, do the following to install and activate the plugin:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-wp-profile-sync`
2. Activate the plugin
3. You are done!

### git clone

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
