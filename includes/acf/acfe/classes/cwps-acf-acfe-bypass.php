<?php
/**
 * ACF Bypass Class.
 *
 * Handles bypassing ACF so that data is sent directly to CiviCRM.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync ACF Bypass Class.
 *
 * A class that helps bypass ACF so that data is sent directly to CiviCRM.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_ACFE_Bypass {

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $acf The parent object.
	 */
	public $acfe;

	/**
	 * Supported Location Rule name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $rule_name The supported Location Rule name.
	 */
	public $rule_name = 'form_civicrm';



	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to parent.
		$this->acfe = $parent;

		// Init when this plugin is loaded.
		add_action( 'cwps/acf/acfe/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Bail if less than ACF 5.9.0.
		if ( ! function_exists( 'acf_register_location_type' ) ) {
			return;
		}

		// Include files.
		$this->include_files();

		// Register Location Types.
		$this->register_location_types();

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Include files.
	 *
	 * @since 0.5
	 */
	public function include_files() {

		// Include class files.
		include CIVICRM_WP_PROFILE_SYNC_PATH . 'includes/acf/locations/cwps-acf-location-bypass.php';

	}



	/**
	 * Register Location Types.
	 *
	 * @since 0.5
	 */
	public function register_location_types() {

		// Register Location Types with ACF.
		acf_register_location_type( 'CiviCRM_Profile_Sync_ACF_Location_Type_Bypass' );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

		// Listen for queries from the ACF Field Group class.
		add_filter( 'cwps/acf/query_field_group_mapped', [ $this, 'query_field_group_mapped' ], 10, 2 );
		add_filter( 'cwps/acf/field_group/query_supported_rules', [ $this, 'query_supported_rules' ], 10, 4 );

		// Listen for queries from the ACF Field class.
		add_filter( 'cwps/acf/query_settings_field', [ $this, 'query_settings_field' ], 200, 3 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Listen for queries from the Field Group class.
	 *
	 * This method responds with a Boolean if it detects that this Field Group
	 * should bypass ACF.
	 *
	 * @since 0.5
	 *
	 * @param boolean $mapped The existing mapping flag.
	 * @param array $field_group The array of ACF Field Group data.
	 * @param boolean $mapped True if the Field Group should bypass ACF, or pass through if not.
	 */
	public function query_field_group_mapped( $mapped, $field_group ) {

		// Bail if a Mapping has already been found.
		if ( $mapped !== false ) {
			return $mapped;
		}

		// Bail if this is not a Bypass Field Group.
		$is_bypass_field_group = $this->is_bypass_field_group( $field_group );
		if ( $is_bypass_field_group === false ) {
			return $mapped;
		}

		// --<
		return true;

	}



	/**
	 * Check if this Field Group should bypass ACF.
	 *
	 * @since 0.5
	 *
	 * @param array $field_group The Field Group to check.
	 * @return array|boolean The array of Entities if the Field Group should bypass ACF, or false otherwise.
	 */
	public function is_bypass_field_group( $field_group ) {

		// Only do this once per Field Group.
		static $pseudocache;
		if ( isset( $pseudocache[$field_group['ID']] ) ) {
			return $pseudocache[$field_group['ID']];
		}

		// Assume not visible.
		$is_visible = false;

		// Bail if no Location Rules exist.
		if ( ! empty( $field_group['location'] ) ) {

			// We only need the key to test for an ACF Bypass location.
			$params = [
				$this->rule_name => 'foo',
			];

			// Do the check.
			$is_visible = $this->acf_loader->acf->field_group->is_visible( $field_group, $params );

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$field_group['ID']] ) ) {
			$pseudocache[$field_group['ID']] = $is_visible;
		}

		// --<
		return $is_visible;

	}



	/**
	 * Listen for queries for supported Location Rules.
	 *
	 * @since 0.5
	 *
	 * @param bool $supported The existing supported Location Rules status.
	 * @param array $rule The Location Rule.
	 * @param array $params The query params array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return bool $supported The modified supported Location Rules status.
	 */
	public function query_supported_rules( $supported, $rule, $params, $field_group ) {

		// Bail if already supported.
		if ( $supported === true ) {
			return $supported;
		}

		// Test for this Location Rule.
		if ( $rule['param'] == $this->rule_name AND ! empty( $params[$this->rule_name] ) ) {
			$supported = true;
		}

		// --<
		return $supported;

	}



	// -------------------------------------------------------------------------



	/**
	 * Returns a Setting Field from this Entity when found.
	 *
	 * @since 0.5
	 *
	 * @param array $setting_field The existing Setting Field array.
	 * @param array $field The ACF Field data array.
	 * @param array $field_group The ACF Field Group data array.
	 * @return array|bool $setting_field The Setting Field array if populated, false if conflicting.
	 */
	public function query_settings_field( $setting_field, $field, $field_group ) {

		// Pass if conflicting fields have been found.
		if ( $setting_field === false ) {
			return false;
		}

		// Pass if this is not a Bypass Field Group.
		$is_visible = $this->is_bypass_field_group( $field_group );
		if ( $is_visible === false ) {
			return $setting_field;
		}

		// If already populated, then this is a conflicting field.
		if ( ! empty( $setting_field ) ) {
			return false;
		}

		// Get the Entity and ID array.
		$entity_array = $this->entity_mapping_extract( $field_group['location'] );

		/**
		 * Request a Setting Field from Entity classes.
		 *
		 * @since 0.5
		 *
		 * @param array The empty default Setting Field array.
		 * @param array $field The ACF Field data array.
		 * @param array $field_group The ACF Field Group data array.
		 * @param array $entity_array The Entity and ID array.
		 */
		$setting_field = apply_filters( 'cwps/acf/bypass/query_settings_field', [], $field, $field_group, $entity_array );

		// Return populated array.
		return $setting_field;

	}



	/**
	 * Returns an array containing the Entity and ID.
	 *
	 * @since 0.5
	 *
	 * @param array $location_rules The Location Rules for the Field.
	 * @return array $entity_mapping The array containing the Entity and ID.
	 */
	public function entity_mapping_extract( $location_rules ) {

		// Init an empty Entity and ID array.
		$entity_mapping = [
			'entity' => false,
			'entity_id' => false,
		];

		// The Location Rules outer array is made of "grouos".
		foreach ( $location_rules AS $group ) {

			// Skip group if it has no rules.
			if ( empty( $group ) ) {
				continue;
			}

			// The Location Rules inner array is made of "rules".
			foreach( $group AS $rule ) {

				// Is this a Bypass rule?
				if ( $rule['param'] === $this->rule_name ) {

					// Extract the Entity and ID.
					$tmp = explode( '-', $rule['value'] );
					$entity_mapping['entity'] = $tmp[0];
					$entity_mapping['entity_id'] = (int) $tmp[1];

					// There can only be one so return it.
					return $entity_mapping;

				}

			}

		}

		// --<
		return $entity_mapping;

	}



} // Class ends.



