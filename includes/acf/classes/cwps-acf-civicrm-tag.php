<?php
/**
 * CiviCRM Tag Class.
 *
 * Handles CiviCRM Tag functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Tag Class.
 *
 * A class that encapsulates CiviCRM Tag functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Tag {

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
	 * @var object $civicrm The parent object.
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
		$this->plugin = $parent->acf_loader->plugin;
		$this->acf_loader = $parent->acf_loader;
		$this->civicrm = $parent;

		// Init when the ACF CiviCRM object is loaded.
		add_action( 'cwps/acf/civicrm/loaded', [ $this, 'register_hooks' ] );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.5
	 */
	public function register_hooks() {

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Tag.
	 *
	 * If you want to "create" a Tag, do not pass $data['id'] in. The presence
	 * of an ID will cause an update to that Tag.
	 *
	 * @since 0.5
	 *
	 * @param string $data The Tag data.
	 * @return array|bool $tag The array of Tag data, or false on failure.
	 */
	public function create( $data ) {

		// Init return.
		$tag = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $tag;
		}

		// Define params to create new Tag.
		$params = [
			'version' => 3,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Tag', 'create', $params );

		// Log and bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			$e = new Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return $tag;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $tag;
		}

		// The result set should contain only one item.
		$tag = array_pop( $result['values'] );

		// --<
		return $tag;

	}



	/**
	 * Update a CiviCRM Contact with a given set of data.
	 *
	 * This is an alias of `self::create()` except that we expect an ID to have
	 * been set in the data.
	 *
	 * @since 0.5
	 *
	 * @param array $data The CiviCRM Tag data.
	 * @return array|bool The array of data from the CiviCRM API, or false on failure.
	 */
	public function update( $data ) {

		// Log and bail if there's no Tag ID.
		if ( empty( $data['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numerical ID must be present to update a Tag.', 'civicrm-wp-profile-sync' ),
				'data' => $data,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// Pass through.
		return $this->create( $data );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the data for a Tag.
	 *
	 * @since 0.5
	 *
	 * @param integer $tag_id The numeric ID of the Tag.
	 * @return array $tag The array of Tag data, or empty if none.
	 */
	public function get_by_id( $tag_id ) {

		// Init return.
		$tag = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $tag;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $tag_id,
		];

		// Get Tag details via API.
		$result = civicrm_api( 'Tag', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $tag;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $tag;
		}

		// The result set should contain only one item.
		$tag = (object) array_pop( $result['values'] );

		// --<
		return $tag;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the Tags that can be applied to CiviCRM Contacts.
	 *
	 * @since 0.5
	 *
	 * @return array $tag_data The array of Tag data for CiviCRM Contacts.
	 */
	public function get_for_contacts() {

		// Init return.
		$tag_data = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $tag_data;
		}

		// Define params to get queried Tags.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'used_for' => 'civicrm_contact',
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Tag', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $tag_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $tag_data;
		}

		// The result set it what we want.
		$tag_data = $result['values'];

		// --<
		return $tag_data;

	}



	/**
	 * Check if a CiviCRM Contact has a Tag.
	 *
	 * @since 0.5
	 *
	 * @param array $contact_id The numeric ID of a CiviCRM Contact.
	 * @param integer $tag_id The numeric ID of the Tag.
	 * @return bool $has_tag True if the Contact has the Tag, or false otherwise.
	 */
	public function contact_has_tag( $contact_id, $tag_id ) {

		// Params to query Entity Tag.
		$params = [
			'version' => 3,
			'entity_table' => 'civicrm_contact',
			'contact_id' => $contact_id,
			'tag_id' => $tag_id,
		];

		// Call API.
		$result = civicrm_api( 'EntityTag', 'get', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_id' => $contact_id,
				'tag_id' => $tag_id,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return empty( $result['values'] ) ? false : true;

	}



	/**
	 * Add a Tag to a CiviCRM Contact.
	 *
	 * @since 0.5
	 *
	 * @param array $contact_id The numeric ID of a CiviCRM Contact.
	 * @param integer $tag_id The numeric ID of the Tag.
	 * @return array|bool $result The Group-Contact data, or false on failure.
	 */
	public function contact_tag_add( $contact_id, $tag_id ) {

		// Params to add a Tag.
		$params = [
			'version' => 3,
			'entity_table' => 'civicrm_contact',
			'contact_id' => $contact_id,
			'tag_id' => $tag_id,
		];

		// Call API.
		$result = civicrm_api( 'EntityTag', 'create', $params );

		// Add log entry on failure.
		if ( isset( $result['is_error'] ) && $result['is_error'] == '1' ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'contact_id' => $contact_id,
				'tag_id' => $tag_id,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return false;
		}

		// --<
		return $result;

	}



} // Class ends.



