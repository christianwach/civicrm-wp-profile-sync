CiviCRM WordPress Profile Sync
==============================

The *CiviCRM WordPress Profile Sync* plugin keeps the "First Name", "Last Name" and "Email Address" fields of a *WordPress* (and *BuddyPress*) user profile in sync with a *CiviCRM* contact.

#### Notes ####

This plugin has been developed using *WordPress 3.6*, *BuddyPress 1.8* and *CiviCRM 4.3.5*. It requires the master branch of the [CiviCRM WordPress plugin](https://github.com/civicrm/civicrm-wordpress) plus the custom WordPress.php hook file from the [CiviCRM Hook Tester repo on GitHub](https://github.com/christianwach/civicrm-wp-hook-tester) so that it overrides the built-in *CiviCRM* file. It also requires the [BP XProfile WordPress User Sync plugin](http://wordpress.org/plugins/bp-xprofile-wp-user-sync/) for consistency with BuddyPress profiles.

#### Installation ####

There are two ways to install from GitHub:

###### ZIP Download ######

If you have downloaded *CiviCRM WordPress Profile Sync* as a ZIP file from the GitHub repository, do the following to install and activate the plugin and theme:

1. Unzip the .zip file and, if needed, rename the enclosing folder so that the plugin's files are located directly inside `/wp-content/plugins/civicrm-wp-profile-sync`
2. Activate the plugin
3. You are done!

###### git clone ######

If you have cloned the code from GitHub, it is assumed that you know what you're doing.
