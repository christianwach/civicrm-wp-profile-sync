<?php
/**
 * CiviCRM Participant Role Class.
 *
 * Handles CiviCRM Participant Role functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * CiviCRM Profile Sync CiviCRM Participant Role Class.
 *
 * A class that encapsulates CiviCRM Participant Role functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Participant_Role {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_WP_Profile_Sync_ACF_Loader
	 */
	public $acf_loader;

	/**
	 * CiviCRM object.
	 *
	 * @since 0.5
	 * @access public
	 * @var CiviCRM_Profile_Sync_ACF_CiviCRM
	 */
	public $civicrm;

	/**
	 * Constructor.
	 *
	 * @since 0.5
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin     = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm    = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'initialise' ] );

	}

	/**
	 * Initialise this object.
	 *
	 * @since 0.5
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}

	/**
	 * Register hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the numeric ID of the "Participant Roles" Option Group.
	 *
	 * @since 0.5
	 *
	 * @return integer|bool $option_group_id The ID of the Option Group, or false on failure.
	 */
	public function option_group_id_get() {

		// Only do this once.
		static $option_group_id;
		if ( isset( $option_group_id ) ) {
			return $option_group_id;
		}

		// Init return.
		$option_group_id = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $option_group_id;
		}

		// Define params to get Participant Roles Option Group.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'name'       => 'participant_role',
		];

		// Call API.
		$result = civicrm_api( 'OptionGroup', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $option_group_id;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $option_group_id;
		}

		// The result set should contain only one item.
		$option_group = array_pop( $result['values'] );

		// Assign the ID.
		$option_group_id = (int) $option_group['id'];

		// --<
		return $option_group_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the data for an "Participant Role" by ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the Participant Role.
	 * @return array|bool $participant_role An array of Participant Role data, or false on failure.
	 */
	public function get_by_id( $participant_role_id ) {

		// Init return.
		$participant_role = false;

		// Bail if we have no Participant Role ID.
		if ( empty( $participant_role_id ) ) {
			return $participant_role;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $participant_role;
		}

		// Define params to get queried Participant Role.
		$params = [
			'version'    => 3,
			'sequential' => 1,
			'id'         => $participant_role_id,
			'options'    => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $participant_role;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $participant_role;
		}

		// The result set should contain only one item.
		$participant_role = array_pop( $result['values'] );

		// --<
		return $participant_role;

	}

	/**
	 * Get the data for an "Participant Role" by value.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_value The numeric value of the Participant Role.
	 * @return array|bool $participant_role An array of Participant Role data, or false on failure.
	 */
	public function get_by_value( $participant_role_value ) {

		// Init return.
		$participant_role = false;

		// Bail if we have no Participant Role value.
		if ( empty( $participant_role_value ) ) {
			return $participant_role;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $participant_role;
		}

		// Define params to get queried Participant Role.
		$params = [
			'version'         => 3,
			'sequential'      => 1,
			'value'           => $participant_role_value,
			'option_group_id' => $this->option_group_id_get(),
			'options'         => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $participant_role;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $participant_role;
		}

		// The result set should contain only one item.
		$participant_role = array_pop( $result['values'] );

		// --<
		return $participant_role;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the number of Participants for a given CiviCRM Participant Role.
	 *
	 * Pass an empty Role ID to get the count for all Participants.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The ID of the CiviCRM Participant Role.
	 * @return integer $count The number of Participants of that Type.
	 */
	public function participant_count( $participant_role_id = null ) {

		// Params to query Participants.
		$params = [
			'version'             => 3,
			'participant_role_id' => $participant_role_id,
			'return'              => [
				'id',
			],
			'options'             => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Participant', 'get', $params );

		// Add log entry on failure.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			$e     = new \Exception();
			$trace = $e->getTraceAsString();
			$log   = [
				'method'              => __METHOD__,
				'participant_role_id' => $participant_role_id,
				'params'              => $params,
				'result'              => $result,
				'backtrace'           => $trace,
			];
			$this->plugin->log_error( $log );
			return false;
		}

		// --<
		return empty( $result['count'] ) ? 0 : (int) $result['count'];

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get the Participant Role that is mapped to a Post Type.
	 *
	 * @since 0.5
	 *
	 * @param string $post_type_name The name of Post Type.
	 * @return integer|bool $participant_role_id The numeric ID of the Participant Role, or false if not mapped.
	 */
	public function id_get_for_post_type( $post_type_name ) {

		// Init return.
		$participant_role_id = false;

		// Get mappings and flip.
		$mappings = $this->acf_loader->mapping->mappings_for_participant_roles_get();
		$mappings = array_flip( $mappings );

		// Overwrite the Participant Role ID if there is a value.
		if ( isset( $mappings[ $post_type_name ] ) ) {
			$participant_role_id = $mappings[ $post_type_name ];
		}

		// --<
		return $participant_role_id;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Gets the CiviCRM Participant Roles as choices for an ACF "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @return array $choices The choices array.
	 */
	public function choices_get() {

		// Return early if already calculated.
		static $choices;
		if ( isset( $choices ) ) {
			return $choices;
		}

		// Get the Participant Roles.
		$participant_roles = $this->get_all();

		// Build return array.
		$choices = [];
		foreach ( $participant_roles as $key => $value ) {
			$choices[ $value['value'] ] = $value['label'];
		}

		// --<
		return $choices;

	}

	// -----------------------------------------------------------------------------------

	/**
	 * Get all Participant Roles.
	 *
	 * @since 0.5
	 *
	 * @return array $participant_roles The array of all Participant Roles.
	 */
	public function get_all() {

		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}

		// Init return.
		$participant_roles = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $participant_roles;
		}

		// Define params to get all Participant Roles.
		$params = [
			'version'         => 3,
			'sequential'      => 1,
			'option_group_id' => $this->option_group_id_get(),
			'options'         => [
				'sort'  => 'label',
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $participant_roles;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $participant_roles;
		}

		// The result set is what we're after.
		$participant_roles = $result['values'];

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $participant_roles;
		}

		// --<
		return $participant_roles;

	}

	/**
	 * Gets all Participant Roles that count towards the total for the Event.
	 *
	 * @since 0.5
	 *
	 * @return array $not_counted The array of counted Participant Roles.
	 */
	public function get_counted() {

		// Return early if already calculated.
		static $not_counted;
		if ( isset( $not_counted ) ) {
			return $not_counted;
		}

		// Get the Participant Roles.
		$participant_roles = $this->get_all();

		// Build return array.
		$not_counted = [];
		foreach ( $participant_roles as $key => $value ) {
			if ( isset( $value['filter'] ) && 1 === (int) $value['filter'] ) {
				$not_counted[ $value['value'] ] = $value['label'];
			}
		}

		// --<
		return $not_counted;

	}

	/**
	 * Get all Participant Roles that are mapped to Post Types.
	 *
	 * @since 0.5
	 *
	 * @return array $participant_roles The array of mapped Participant Roles.
	 */
	public function get_mapped() {

		/*
		// Only do this once.
		static $pseudocache;
		if ( isset( $pseudocache ) ) {
			return $pseudocache;
		}
		*/

		// Init return.
		$participant_roles = [];

		// Get mapping array.
		$mappings = $this->acf_loader->mapping->mappings_for_participant_roles_get();

		// Bail on empty.
		if ( empty( $mappings ) ) {
			return $participant_roles;
		}

		// Get all Participant Role IDs.
		$participant_role_ids = array_keys( $mappings );

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $participant_roles;
		}

		// Define params to get queried Participant Roles.
		$params = [
			'version'         => 3,
			'sequential'      => 1,
			'value'           => [ 'IN' => $participant_role_ids ],
			'option_group_id' => $this->option_group_id_get(),
			'options'         => [
				'sort'  => 'label',
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'OptionValue', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && 1 === (int) $result['is_error'] ) {
			return $participant_roles;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $participant_roles;
		}

		// The result set is what we're after.
		$participant_roles = $result['values'];

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $participant_roles;
		}
		*/

		// --<
		return $participant_roles;

	}

	/**
	 * Check if a Participant Role is mapped to a Post Type.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the Participant Role.
	 * @return string|bool $is_linked The name of the Post Type, or false otherwise.
	 */
	public function is_mapped_to_post_type( $participant_role_id ) {

		// Assume not.
		$is_mapped = false;

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_participant_roles_get();

		// Check presence in mappings.
		if ( isset( $mapped_post_types[ $participant_role_id ] ) ) {
			$is_mapped = $mapped_post_types[ $participant_role_id ];
		}

		// --<
		return $is_mapped;

	}

}
