CiviCRM Profile Sync
====================

**Contributors:** [needle](https://profiles.wordpress.org/needle/), [cuny-academic-commons](https://profiles.wordpress.org/cuny-academic-commons/), [kcristiano](https://profiles.wordpress.org/kcristiano/), [tadpolecc](https://profiles.wordpress.org/tadpolecc/)<br/>
**Donate link:** https://www.paypal.me/interactivist<br/>
**Tags:** civicrm, user, buddypress, acf, profile, xprofile, sync<br/>
**Requires at least:** 4.9<br/>
**Tested up to:** 5.9<br/>
**Stable tag:** 0.5.3a<br/>
**License:** GPLv2 or later<br/>
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

Keeps a WordPress User profile in sync with a CiviCRM Contact and integrates WordPress and CiviCRM Entities with data synced via Advanced Custom Fields.



## Description

Please note: this is the development repository for CiviCRM Profile Sync. The plugin can be found in the [WordPress Plugin Directory](https://wordpress.org/plugins/civicrm-wp-profile-sync/), which is the best place to get it from if you're not a developer.

### WordPress Integration

At its simplest, the CiviCRM Profile Sync plugin keeps the "First Name", "Last Name", "Nickname", "Email Address" and "Website" fields of a WordPress User Profile in sync with their corresponding fields in a CiviCRM Contact. The synchronisation takes place regardless of whether the changes are made in WordPress or CiviCRM.

### BuddyPress Integration

The plugin also supports syncing the "First Name" and "Last Name" fields of the WordPress User and CiviCRM Contact with BuddyPress when using the BP xProfile WordPress User Sync plugin. Further integration with BuddyPress is in the pipeline.

### ACF Integration

CiviCRM Profile Sync enables integration between CiviCRM Entities and WordPress Entities with data synced via Advanced Custom Fields.

Whilst ACF integration is not complete in its coverage of the CiviCRM Entities that can be linked, it is fairly comprehensive in its mapping of the built-in CiviCRM Custom Field Types with their corresponding ACF Field Types.

So if, for example, you want to display (or create) a Contact Type on your WordPress site with ACF Fields that contain synced CiviCRM data, this feature could work for you.

Please refer to the [ACF Integration Documentation](/docs/ACF.md) for details.

### Form-building with ACF Extended

CiviCRM Profile Sync enables Forms to be built for the front-end of your website with the UI provided by the ACF Extended plugin. These Forms can send their data directly to CiviCRM in a similar (though more limited) way to Caldera Forms CiviCRM.

Form building with ACF Extended is at an early stage of development and is currently limited to submitting data for Contacts, Participants, Activities and Cases. This does, however, provide enough functionality to build some fairly powerful and useful Forms.

Please refer to the [ACFE Form-building Documentation](/docs/ACFE.md) for details.

### Requirements

This plugin recommends a minimum of WordPress 4.9 and CiviCRM 5.23.

If you want to maintain sync with BuddyPress user profiles, then it also requires BuddyPress 3.0 and the [BP xProfile WordPress User Sync](https://wordpress.org/plugins/bp-xprofile-wp-user-sync/) plugin to do so.

For integration with Advanced Custom Fields, this plugin recommends a minimum of ACF 5.8 or ACF Pro 5.8.



## Installation

There are two ways to install from GitHub:

### ZIP Download

If you have downloaded CiviCRM Profile Sync as a ZIP file from the GitHub repository, do the following to install the plugin:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-wp-profile-sync`
2. CiviCRM Profile Sync is installed. Read on for activation

### git clone

If you have cloned the code from GitHub, it is assumed that you know what you're doing.



## Activation

CiviCRM Profile Sync has some particular requirements when it comes to how it is activated. These correspond to the different types of WordPress install:

### Single Site

Easy - just activate the plugin and you are done!

### Multisite

Since Users and User metadata are stored in a single place for all sites, CiviCRM Profile Sync's User Profile settings cannot be configured on a per-site basis. This means that (whether CiviCRM Profile Sync is network-activated or not) whichever settings page you go to, you will see CiviCRM Profile Sync settings that are held in common for all sites.

It is, of course, possible to activate CiviCRM in many different ways in Multisite - it could be network-activated, activated on the main site, and/or activated on one or more sub-sites. CiviCRM could also be in Multi-Domain mode or each instance could have its own database. It is recommended that you activate CiviCRM Profile Sync in the same way that CiviCRM is activated.

If CiviCRM is not in Multi-Domain mode, you may have to write some custom code to propagate changed User details to other CiviCRM instances because (depending on the site on which a particular User's details are changed) only the Contact on the CiviCRM instance linked to that site will be updated.

Test early, test often and - above all - test on a development site first.

### Multi-Network

In Multi-Network, Users and User metadata are stored in one place for all Networks but `site_options` are stored on a per-Network basis. As a result, it is not simple for CiviCRM Profile Sync to store a single collection of User Profile settings for all Networks. You will have to make sure that they are the same across all the Networks where both CiviCRM and this plugin are activated.

A consequence of this architecture is that (depending on how you have set up CiviCRM across the Networks) there may be mismatches between the User data in WordPress and the Contact data in your CiviCRM instances. If CiviCRM is not in Multi-Domain mode, you will have to write some custom code to keep the data synced between WordPress and your CiviCRM instances.

I'll say it again: test early, test often and - above all - test on a development site first.
