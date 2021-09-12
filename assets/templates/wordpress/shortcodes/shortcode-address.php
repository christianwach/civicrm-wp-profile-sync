<?php
/**
 * Address Shortcode template.
 *
 * Builds an Address for display.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.8.2
 */

?><!-- assets/templates/wordpress/shortcode-address.php -->
<address>

<?php if ( ! empty( $street_address ) ) : ?>
	<?php echo $street_address; ?><br>
<?php endif; ?>

<?php if ( ! empty( $supplemental_address_1 ) ) : ?>
	<?php echo $supplemental_address_1; ?><br>
<?php endif; ?>

<?php if ( ! empty( $supplemental_address_2 ) ) : ?>
	<?php echo $supplemental_address_2; ?><br>
<?php endif; ?>

<?php if ( ! empty( $supplemental_address_3 ) ) : ?>
	<?php echo $supplemental_address_3; ?><br>
<?php endif; ?>

<?php if ( ! empty( $city ) ) : ?>
	<?php echo $city; ?>
<?php endif; ?>
<?php if ( ! empty( $state_short ) ) : ?>
	 <?php echo $state_short; ?>
<?php endif; ?>
<?php if ( ! empty( $postal_code ) ) : ?>
	 <?php echo $postal_code; ?>
<?php endif; ?>
<?php if ( ! empty( $city ) || ! empty( $postal_code ) ) : ?>
	<br>
<?php endif; ?>

<?php if ( ! empty( $country ) ) : ?>
	<?php echo $country; ?><br>
<?php endif; ?>

</address>
