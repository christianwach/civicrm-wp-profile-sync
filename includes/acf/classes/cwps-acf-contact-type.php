<?php
/**
 * CiviCRM Contact Type Class.
 *
 * Handles CiviCRM Contact Type functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Contact Type Class.
 *
 * A class that encapsulates CiviCRM Contact Type functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Contact_Type {

	/**
	 * Plugin object.
	 *
	 * @since 0.5
	 * @access public
	 * @var object $plugin The plugin object.
	 */
	public $plugin;

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Parent (calling) object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $civicrm The parent object.
	 */
	public $civicrm;

	/**
	 * Mapper hooks registered flag.
	 *
	 * @since 0.5.2
	 * @access public
	 * @var object $bulk The Mapper hooks registered flag.
	 */
	public $mapper_hooks = false;

	/**
	 * Contact data bridging array.
	 *
	 * @since 0.4
	 * @access public
	 * @var array $bridging_array The Contact data bridging array.
	 */
	public $bridging_array = [];



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store references to objects.
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'register_hooks' ] );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Always register Mapper hooks.
		$this->register_mapper_hooks();

	}



	/**
	 * Register callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function register_mapper_hooks() {

		// Bail if already registered.
		if ( $this->mapper_hooks === true ) {
			return;
		}

		// Listen for events from our Mapper that may signal a change of Contact Type.
		add_action( 'cwps/acf/mapper/contact/edit/pre', [ $this, 'contact_edit_pre' ], 10 );
		add_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 9 );

		// Declare registered.
		$this->mapper_hooks = true;

	}



	/**
	 * Unregister callbacks for Mapper events.
	 *
	 * @since 0.4
	 */
	public function unregister_mapper_hooks() {

		// Bail if already unregistered.
		if ( $this->mapper_hooks === false ) {
			return;
		}

		// Remove all Mapper listeners.
		remove_action( 'cwps/acf/mapper/contact/edit/pre', [ $this, 'contact_edit_pre' ], 10 );
		remove_action( 'cwps/acf/mapper/contact/edited', [ $this, 'contact_edited' ], 9 );

		// Declare unregistered.
		$this->mapper_hooks = false;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the number of Contacts who are of a CiviCRM Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type_id The ID of the CiviCRM Contact Type.
	 * @return integer $count The number of Contacts of that Type.
	 */
	public function contact_count( $contact_type_id ) {

		// Get the hierarchy for the Contact Type ID.
		$hierarchy = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $contact_type_id, 'id' );

		// Bail if we didn't get any.
		if ( $hierarchy === false ) {
			return 0;
		}

		// Params to query Contacts.
		$params = [
			'version' => 3,
			'contact_type' => $hierarchy['type'],
			'contact_sub_type' => $hierarchy['subtype'],
			'return' => [
				'id',
			],
			'options' => [
				'limit' => 0,
			],
		];

		// Call the API.
		$result = civicrm_api( 'Contact', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_type_id' => $contact_type_id,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return empty( $result['count'] ) ? 0 : (int) $result['count'];

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Contact Type hierarchy that is mapped to a Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string|bool $post_type_name The name of Post Type.
	 * @return array $types An associative array populated with parent type and sub-type.
	 */
	public function hierarchy_get_for_post_type( $post_type_name ) {

		// Init return.
		$types = false;

		// Get the mapped Contact Type ID.
		$contact_type_id = $this->id_get_for_post_type( $post_type_name );

		// Bail on failure.
		if ( $contact_type_id === false ) {
			return $types;
		}

		// Get the array of types.
		$types = $this->plugin->civicrm->contact_type->hierarchy_get_by_id( $contact_type_id );

		// --<
		return $types;

	}



	/**
	 * Get the Contact Type that is mapped to a Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type_name The name of Post Type.
	 * @return integer|bool $contact_type_id The numeric ID of the Contact Type, or false if not mapped.
	 */
	public function id_get_for_post_type( $post_type_name ) {

		// Init return.
		$contact_type_id = false;

		// Get mappings and flip.
		$mappings = $this->acf_loader->mapping->mappings_for_contact_types_get();
		$mappings = array_flip( $mappings );

		// Overwrite the Contact Type ID if there is a value.
		if ( isset( $mappings[ $post_type_name ] ) ) {
			$contact_type_id = $mappings[ $post_type_name ];
		}

		// --<
		return $contact_type_id;

	}



	/**
	 * Gets the CiviCRM Contact Types as choices for an ACF "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @return array $choices The choices array.
	 */
	public function choices_get() {

		// Init return.
		$choices = [];

		// Get all Contact Types.
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();

		// Bail if there are none.
		if ( empty( $contact_types ) ) {
			return $choices;
		}

		// Add entries for each CiviCRM Contact Type.
		foreach ( $contact_types as $contact_type ) {

			// Top level types first.
			$choices[ $contact_type['id'] ] = $contact_type['label'];

			// Skip Sub-types if there aren't any.
			if ( empty( $contact_type['children'] ) ) {
				continue;
			}

			// Add children.
			foreach ( $contact_type['children'] as $contact_subtype ) {
				$choices[ $contact_subtype['id'] ] = '&mdash; ' . $contact_subtype['label'];
			}

		}

		// --<
		return $choices;

	}



	/**
	 * Gets the top-level CiviCRM Contact Types as choices for an ACF "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @return array $choices The choices array.
	 */
	public function choices_top_level_get() {

		// Init return.
		$choices = [];

		// Get all Contact Types.
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();

		// Bail if there are none.
		if ( empty( $contact_types ) ) {
			return $choices;
		}

		// Add entries for each CiviCRM Contact Type.
		foreach ( $contact_types as $contact_type ) {

			// Top level types only.
			$choices[ $contact_type['id'] ] = $contact_type['label'];

		}

		// --<
		return $choices;

	}



	/**
	 * Gets the CiviCRM Contact Sub-Types as choices for an ACF "Select" Field.
	 *
	 * @since 0.5
	 *
	 * @return array $choices The choices array.
	 */
	public function choices_sub_types_get() {

		// Init return.
		$choices = [];

		// Get all Contact Types.
		$contact_types = $this->plugin->civicrm->contact_type->types_get_nested();

		// Bail if there are none.
		if ( empty( $contact_types ) ) {
			return $choices;
		}

		// Add entries for each CiviCRM Contact Type.
		foreach ( $contact_types as $contact_type ) {

			// Skip top level types.
			//$choices[$contact_type['id']] = $contact_type['label'];

			// Skip Sub-types if there aren't any.
			if ( empty( $contact_type['children'] ) ) {
				continue;
			}

			// Add children.
			foreach ( $contact_type['children'] as $contact_subtype ) {
				$choices[ $contact_type['name'] ][ $contact_subtype['id'] ] = $contact_subtype['label'];
			}

		}

		// --<
		return $choices;

	}



	/**
	 * Get all Contact Types that are mapped to Post Types.
	 *
	 * @since 0.4
	 *
	 * @return array $contact_types The array of mapped Contact Types.
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
		$contact_types = [];

		// Get mapping array.
		$mappings = $this->acf_loader->mapping->mappings_for_contact_types_get();

		// Bail on empty.
		if ( empty( $mappings ) ) {
			return $contact_types;
		}

		// Get all Contact Type IDs.
		$contact_type_ids = array_keys( $mappings );

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $contact_types;
		}

		// Define params to get queried Contact Types.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'id' => [ 'IN' => $contact_type_ids ],
			'options' => [
				'sort' => 'label',
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'ContactType', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $contact_types;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $contact_types;
		}

		// The result set is what we're after.
		$contact_types = $result['values'];

		/*
		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache ) ) {
			$pseudocache = $contact_types;
		}
		*/

		// --<
		return $contact_types;

	}



	/**
	 * Check if a Contact Type is mapped to a Post Type.
	 *
	 * @since 0.4
	 *
	 * @param integer|string|array $contact_type The "ID", "name" or "hierarchy" of the Contact Type.
	 * @return string|bool $is_linked The name of the Post Type, or false otherwise.
	 */
	public function is_mapped_to_post_type( $contact_type ) {

		// Assume not.
		$is_mapped = false;

		// Parse the input when it's an array.
		if ( is_array( $contact_type ) ) {

			// Check if it's a top level Contact Type.
			if ( empty( $contact_type['subtype'] ) ) {
				$contact_type = $contact_type['type'];
			} else {
				$contact_type = $contact_type['subtype'];
			}

		}

		// Parse the input when it's an integer.
		if ( is_numeric( $contact_type ) ) {

			// Assign the numeric ID.
			$contact_type_id = $contact_type = (int) $contact_type;

		}

		// Parse the input when it's a string.
		if ( is_string( $contact_type ) ) {

			// Get data for the queried Contact Type.
			$contact_type_data = $this->plugin->civicrm->contact_type->get_data( $contact_type, 'name' );

			// Bail if we didn't get any.
			if ( $contact_type_data === false ) {
				return $is_mapped;
			}

			// Assign the numeric ID.
			$contact_type_id = $contact_type_data['id'];

		}

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_contact_types_get();

		// Check presence in mappings.
		if ( isset( $mapped_post_types[ $contact_type_id ] ) ) {
			$is_mapped = $mapped_post_types[ $contact_type_id ];
		}

		// --<
		return $is_mapped;

	}



	// -------------------------------------------------------------------------



	/**
	 * When a CiviCRM Contact is about to be updated, get existing data.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_edit_pre( $args ) {

		// Get the full existing Contact data.
		$contact = $this->plugin->civicrm->contact->get_by_id( $args['objectId'] );

		// Add to bridge.
		$this->bridging_array[ $args['objectId'] ] = $contact;

	}



	/**
	 * When a CiviCRM Contact has been updated, compare with existing data.
	 *
	 * This method is hooked in before any other Mapper listener so that it can
	 * be queried as to whether a Contact's Contact Types have changed.
	 *
	 * @since 0.4
	 *
	 * @param array $args The array of CiviCRM params.
	 */
	public function contact_edited( $args ) {

		// Get the previous Contact data.
		$contact_pre = [];
		if ( ! empty( $this->bridging_array[ $args['objectId'] ] ) ) {
			$contact_pre = $this->bridging_array[ $args['objectId'] ];
			unset( $this->bridging_array[ $args['objectId'] ] );
		}

		// Make sure we have arrays.
		if ( empty( $contact_pre['contact_sub_type'] ) ) {
			$contact_pre['contact_sub_type'] = [];
		}
		if ( empty( $args['objectRef']->contact_sub_type ) ) {
			$args['objectRef']->contact_sub_type = [];
		}

		// Find the Contact Types that are missing.
		$types_removed = array_diff( $contact_pre['contact_sub_type'], $args['objectRef']->contact_sub_type );

		// Find the Contact Types that have been added.
		$types_added = array_diff( $args['objectRef']->contact_sub_type, $contact_pre['contact_sub_type'] );

		// Save the diffs in the Contact data.
		$args['objectRef']->subtype_diffs = [
			'removed' => $types_removed,
			'added' => $types_added,
		];

	}



	// -------------------------------------------------------------------------
	// Retained methods to provide backwards compatibility.
	// -------------------------------------------------------------------------



	/**
	 * Get all top-level CiviCRM Contact Types.
	 *
	 * @since 0.4
	 *
	 * @return array $top_level_types The top level CiviCRM Contact Types.
	 */
	public function types_get_top_level() {
		return $this->plugin->civicrm->contact_type->types_get_top_level();
	}



} // Class ends.



