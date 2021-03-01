CiviCRM Profile Sync
====================

**Contributors:** [needle](https://profiles.wordpress.org/needle/), [cuny-academic-commons](https://profiles.wordpress.org/cuny-academic-commons/)<br/>
**Donate link:** https://www.paypal.me/interactivist<br/>
**Tags:** civicrm, acf, sync<br/>
**Requires at least:** 4.9<br/>
**Tested up to:** 5.6<br/>
**Stable tag:** 0.4<br/>
**License:** GPLv2 or later<br/>
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Keeps entities in *CiviCRM* in sync with their equivalents in *WordPress*.

### Description

Please note: this is the development repository for *CiviCRM Profile Sync*. It can be found in the [WordPress Plugin Directory](https://wordpress.org/plugins/civicrm-wp-profile-sync/), which is the best place to get it from if you're not a developer.

The *CiviCRM Profile Sync* plugin keeps the "First Name", "Last Name", "Nickname", "Email Address" and "Website" fields of a *WordPress* User Profile in sync with their corresponding fields in a *CiviCRM* Contact. The synchronisation takes place regardless of whether the changes are made in *WordPress* or *CiviCRM*.

The plugin also supports syncing the "First Name" and "Last Name" fields of the WordPress User and CiviCRM Contact with *BuddyPress* when using the *BP xProfile WordPress User Sync* plugin.

#### CiviCRM ACF Integration

This plugin is compatible with the [CiviCRM ACF Integration](https://github.com/christianwach/civicrm-acf-integration) plugin and enables syncing of Custom Fields on CiviCRM Contacts with ACF Fields attached to the WordPress User Profiles. It currently only supports the "User Form = Add/Edit" Location Rule.

*Important note:* Please make sure you have *CiviCRM ACF Integration* version 0.8.2 or greater.

#### Requirements

This plugin recommends a minimum of *WordPress 4.9* and *CiviCRM 5.23*. If you want to maintain sync with BuddyPress user profiles, then it also requires *BuddyPress 3.0* and the [BP xProfile WordPress User Sync](https://wordpress.org/plugins/bp-xprofile-wp-user-sync/) plugin to do so.

### Installation

There are two ways to install from GitHub:

#### ZIP Download

If you have downloaded *CiviCRM Profile Sync* as a ZIP file from the GitHub repository, do the following to install and activate the plugin:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-wp-profile-sync`
2. Activate the plugin
3. You are done!

#### git clone

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
