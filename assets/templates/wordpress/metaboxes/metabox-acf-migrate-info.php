<?php
/**
 * Migration Page Info metabox template.
 *
 * Handles markup for the Migration Page Info metabox.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

?><!-- assets/templates/wordpress/metaboxes/metabox-acf-migrate-info.php -->
<?php if ( $metabox['args']['migrated'] === false ) : ?>

	<h3><?php esc_html_e( 'Why migrate?', 'civicrm-wp-profile-sync' ); ?></h3>

	<p><?php esc_html_e( 'The CiviCRM ACF Integration plugin is no longer being developed as a standalone plugin, so the functionality that it provides has been transferred to CiviCRM Profile Sync. New features and bug fixes will only be added to this plugin from now on.', 'civicrm-wp-profile-sync' ); ?></p>

	<h3><?php esc_html_e( 'What needs to be done?', 'civicrm-wp-profile-sync' ); ?></h3>

	<p><?php esc_html_e( 'I have tried to keep the tasks to an absolute minimum, however there are some changes that are unavoidable. Before you go ahead and deactivate and delete the CiviCRM ACF Integration plugin, there are few things that need to be done to make sure your site continues to work as normal.', 'civicrm-wp-profile-sync' ); ?> <em><?php esc_html_e( 'CiviCRM Profile Sync will not affect your site until you give it the go-ahead.', 'civicrm-wp-profile-sync' ); ?></em></p>

	<h4><?php esc_html_e( 'Filters and Actions', 'civicrm-wp-profile-sync' ); ?></h3>

	<p><em><?php esc_html_e( 'If you have not implemented any of the Filters or Actions from the CiviCRM ACF Integration plugin, then it is unlikely that you will need to take any further action before migrating.', 'civicrm-wp-profile-sync' ); ?></em></p>

	<p><?php esc_html_e( 'Filters and Actions have undergone a major overhaul and there isn’t really a simple substitution formula that I can give you. If you are technical enough to have used them to modify or extend the behaviour of the CiviCRM ACF Integration plugin, then I am confident that you are capable of figuring out their replacements by looking at the equivalent classes, functions and templates in CiviCRM Profile Sync.', 'civicrm-wp-profile-sync' ); ?></p>

	<p><?php esc_html_e( 'This is really just a reminder that you need to do so.', 'civicrm-wp-profile-sync' ); ?></p>

	<h4><?php esc_html_e( 'Settings', 'civicrm-wp-profile-sync' ); ?></h3>

	<p><?php esc_html_e( 'The uninstall routine in the CiviCRM ACF Integration plugin will auto-delete its settings when the plugin is deleted. It is therefore necessary to migrate these to CiviCRM Profile Sync before that is done. You should only deactivate and delete the CiviCRM ACF Integration plugin when you are sure everything mentioned here has been completed.', 'civicrm-wp-profile-sync' ); ?></p>

	<?php if ( is_multisite() ) : ?>

		<p><em><?php esc_html_e( 'CiviCRM Profile Sync will handle migration of settings for this site when you click the "Migrate" button. Because this is a WordPress Multisite install, you will also have to visit the equivalent Migration Page on every site where the CiviCRM ACF Integration plugin is activated and follow the same procedure as here before deactivating it.', 'civicrm-wp-profile-sync' ); ?></em></p>

	<?php else : ?>

		<p><em><?php esc_html_e( 'CiviCRM Profile Sync will handle migration of settings for you when you click the "Migrate" button.', 'civicrm-wp-profile-sync' ); ?></em></p>

	<?php endif; ?>

<?php else : ?>

	<h3><?php esc_html_e( 'Congratulations!', 'civicrm-wp-profile-sync' ); ?></h3>

	<p><em><?php esc_html_e( 'CiviCRM Profile Sync has migrated your CiviCRM ACF Integration plugin settings.', 'civicrm-wp-profile-sync' ); ?></em></p>

	<p><?php echo sprintf(
		/* translators: 1: Opening anchor tag, 2: Closing anchor tag */
		__( 'You can now go to your %1$sPlugins page%2$s and deactivate the CiviCRM ACF Integration plugin.', 'civicrm-wp-profile-sync' ),
		'<a href="' . admin_url( 'plugins.php' ) . '">',
		'</a>'
	); ?></p>

	<p><?php esc_html_e( 'When you have done that, you will be able to access the "Manual Sync" page here instead of this page.', 'civicrm-wp-profile-sync' ); ?></p>

<?php endif; ?>
