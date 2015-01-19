CiviCRM WordPress Profile Sync
==============================

The *CiviCRM WordPress Profile Sync* plugin keeps the "First Name", "Last Name", "Email Address" and "Website" fields of a *WordPress* (and *BuddyPress*) user profile in sync with the corresponding fields of a *CiviCRM* contact. The synchronisation takes place regardless of whether the changes are made in *WordPress*, *BuddyPress* or *CiviCRM*.

#### Notes ####

This plugin requires a minimum of *WordPress 3.9* and *CiviCRM 4.6-alpha1*. It also requires *BuddyPress 1.8* and the [BP XProfile WordPress User Sync](http://wordpress.org/plugins/bp-xprofile-wp-user-sync/) plugin for syncing data with BuddyPress profiles.

For versions of *CiviCRM* prior to 4.6-alpha1, this plugin requires the corresponding branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in *CiviCRM* file. Please refer to the each repo for further instructions.

#### Installation ####

There are two ways to install from GitHub:

###### ZIP Download ######

If you have downloaded *CiviCRM WordPress Profile Sync* as a ZIP file from the GitHub repository, do the following to install and activate the plugin and theme:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-wp-profile-sync`
2. Activate the plugin
3. You are done!

###### git clone ######

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
