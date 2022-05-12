<?php
/**
 * CiviCRM Campaign Class.
 *
 * Handles CiviCRM Campaign functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Campaign Class.
 *
 * A class that encapsulates CiviCRM Campaign functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Campaign {

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
	 * Create a CiviCRM Campaign.
	 *
	 * If you want to "create" a Campaign, do not pass $data['id'] in. The presence
	 * of an ID will cause an update to that Campaign.
	 *
	 * @since 0.5
	 *
	 * @param string $data The Campaign data.
	 * @return array|bool $campaign The array of Campaign data, or false on failure.
	 */
	public function create( $data ) {

		// Init return.
		$campaign = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $campaign;
		}

		// Define params to create new Campaign.
		$params = [
			'version' => 3,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Campaign', 'create', $params );

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
			return $campaign;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $campaign;
		}

		// The result set should contain only one item.
		$campaign = array_pop( $result['values'] );

		// --<
		return $campaign;

	}



	/**
	 * Update a CiviCRM Contact with a given set of data.
	 *
	 * This is an alias of `self::create()` except that we expect an ID to have
	 * been set in the data.
	 *
	 * @since 0.5
	 *
	 * @param array $data The CiviCRM Campaign data.
	 * @return array|bool The array of data from the CiviCRM API, or false on failure.
	 */
	public function update( $data ) {

		// Log and bail if there's no Campaign ID.
		if ( empty( $data['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numerical ID must be present to update a Campaign.', 'civicrm-wp-profile-sync' ),
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
	 * Get the data for a Campaign.
	 *
	 * @since 0.5
	 *
	 * @param integer $campaign_id The numeric ID of the Campaign.
	 * @return array $campaign The array of Campaign data, or empty if none.
	 */
	public function get_by_id( $campaign_id ) {

		// Init return.
		$campaign = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $campaign;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $campaign_id,
		];

		// Get Campaign details via API.
		$result = civicrm_api( 'Campaign', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $campaign;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $campaign;
		}

		// The result set should contain only one item.
		$campaign = (object) array_pop( $result['values'] );

		// --<
		return $campaign;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the currently-active CiviCRM Campaigns.
	 *
	 * @since 0.5
	 *
	 * @return array $campaigns The array of data for CiviCRM Campaigns.
	 */
	public function get_current() {

		// Return early if already calculated.
		static $campaigns;
		if ( isset( $campaigns ) ) {
			return $campaigns;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return [];
		}

		// Build params.
		$params = [
			'sequential' => 1,
			'is_active' => 1,
			'status_id' => [ 'NOT IN' => [ 'Completed', 'Cancelled' ] ],
			'options' => [
				'sort' => 'name',
				'limit' => 0,
			],
		];

		// Call the CiviCRM API.
		$result = civicrm_api3( 'Campaign', 'get', $params );

		// Return early if something went wrong.
		if ( ! empty( $result['error'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'params' => $params,
				'result' => $result,
				'backtrace' => $trace,
			], true ) );
			return [];
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return [];
		}

		// Assign Campaign data.
		$campaigns = $result['values'];

		// --<
		return $campaigns;

	}



	/**
	 * Gets the choices for the currently-active CiviCRM Campaigns.
	 *
	 * @since 0.5
	 *
	 * @return array $campaigns The array of choices for CiviCRM Campaigns.
	 */
	public function choices_get() {

		// Return early if already calculated.
		static $campaigns;
		if ( isset( $campaigns ) ) {
			return $campaigns;
		}

		// Get the currently-active Campaigns.
		$current = $this->get_current();

		// Build return array.
		$campaigns = [];
		foreach ( $current as $key => $value ) {
			$campaigns[ $value['id'] ] = $value['title'];
		}

		// --<
		return $campaigns;

	}



} // Class ends.



