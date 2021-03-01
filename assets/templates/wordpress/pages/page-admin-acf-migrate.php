<?php
/**
 * ACF Migrate template.
 *
 * Handles markup for the ACF Migrate admin page.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

?><!-- assets/templates/wordpress/pages/page-admin-acf-migrate.php -->
<div class="wrap">

	<h1><?php _e( 'CiviCRM Profile Sync', 'civicrm-wp-profile-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php _e( 'Settings', 'civicrm-wp-profile-sync' ); ?></a>
		<?php

		/**
		 * Allow others to add tabs.
		 *
		 * @since 0.4
		 *
		 * @param array $urls The array of subpage URLs.
		 * @param string The key of the active tab in the subpage URLs array.
		 */
		do_action( 'cwps/admin/settings/nav_tabs', $urls, 'acf-migrate' );

		?>
	</h2>

	<form method="post" id="cwps_acf_migrate_form" action="<?php echo $this->page_submit_url_get(); ?>">

		<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php wp_nonce_field( 'cwps_migrate_action', 'cwps_migrate_nonce' ); ?>

		<div id="poststuff">

			<div id="post-body" class="metabox-holder columns-<?php echo $columns;?>">

				<!--<div id="post-body-content">
				</div>--><!-- #post-body-content -->

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( $screen->id, 'side', null ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( $screen->id, 'normal', null );  ?>
					<?php do_meta_boxes( $screen->id, 'advanced', null ); ?>
				</div>

			</div><!-- #post-body -->
			<br class="clear">

		</div><!-- #poststuff -->

	</form>

</div><!-- /.wrap -->
