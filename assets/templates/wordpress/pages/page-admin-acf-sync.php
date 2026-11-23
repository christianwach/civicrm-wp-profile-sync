<?php
/**
 * Manual Sync template.
 *
 * Handles markup for the Manual Sync admin page.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

?><!-- assets/templates/wordpress/pages/page-admin-acf-sync.php -->
<div class="wrap">

	<h1><?php esc_html_e( 'CiviCRM Profile Sync', 'civicrm-wp-profile-sync' ); ?></h1>

	<h2 class="nav-tab-wrapper">
		<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
		<a href="<?php echo $urls['settings']; ?>" class="nav-tab"><?php esc_html_e( 'Settings', 'civicrm-wp-profile-sync' ); ?></a>
		<?php

		/**
		 * Allow others to add tabs.
		 *
		 * @since 0.4
		 *
		 * @param array $urls The array of subpage URLs.
		 * @param string The key of the active tab in the subpage URLs array.
		 */
		do_action( 'cwps/admin/settings/nav_tabs', $urls, 'manual-sync' );

		?>
	</h2>

	<p><?php esc_html_e( 'Things can be a little complicated on initial setup because there can be data in WordPress or CiviCRM or both. The utilities below should help you get going.', 'civicrm-wp-profile-sync' ); ?></p>

	<?php if ( ! empty( $messages ) ) : ?>
		<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
		<?php echo $messages; ?>
	<?php endif; ?>

	<?php /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped */ ?>
	<form method="post" id="cwps_acf_sync_form" action="<?php echo $this->admin_form_url_get(); ?>">

		<?php wp_nonce_field( 'cwps_acf_sync_action', 'cwps_acf_sync_nonce' ); ?>
		<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>

		<div id="welcome-panel" class="welcome-panel hidden">
		</div>

		<div id="dashboard-widgets-wrap">

			<div id="dashboard-widgets" class="metabox-holder<?php echo esc_attr( $columns_css ); ?>">

				<div id="postbox-container-1" class="postbox-container">
					<?php do_meta_boxes( $screen->id, 'normal', '' ); ?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php do_meta_boxes( $screen->id, 'side', '' ); ?>
				</div>

			</div><!-- #post-body -->
			<br class="clear">

		</div><!-- #dashboard-widgets-wrap -->

	</form>

</div><!-- /.wrap -->
