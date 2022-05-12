<?php
/**
 * CiviCRM Note Class.
 *
 * Handles CiviCRM Note functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.5
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync CiviCRM Note Class.
 *
 * A class that encapsulates CiviCRM Note functionality.
 *
 * @since 0.5
 */
class CiviCRM_Profile_Sync_ACF_CiviCRM_Note {

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
	 * Public Note Fields.
	 *
	 * Mapped to their corresponding ACF Field Types.
	 *
	 * @since 0.5
	 * @access public
	 * @var array $note_fields The array of public Note Fields.
	 */
	public $note_fields = [
		'note' => 'textarea',
		'subject' => 'text',
	];



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

		// Listen for queries from the Attachment class.
		add_filter( 'cwps/acf/query_attachment_support', [ $this, 'query_attachment_support' ], 20 );
		add_filter( 'cwps/acf/query_attachment_choices', [ $this, 'query_attachment_choices' ], 20, 2 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Create a CiviCRM Note.
	 *
	 * If you want to "create" a Note, do not pass $data['id'] in. The presence
	 * of an ID will cause an update to that Note.
	 *
	 * @since 0.5
	 *
	 * @param string $data The Note data.
	 * @return array|bool $note The array of Note data, or false on failure.
	 */
	public function create( $data ) {

		// Init return.
		$note = false;

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $note;
		}

		// Define params to create new Note.
		$params = [
			'version' => 3,
		] + $data;

		// Call the API.
		$result = civicrm_api( 'Note', 'create', $params );

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
			return $note;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $note;
		}

		// The result set should contain only one item.
		$note = array_pop( $result['values'] );

		// --<
		return $note;

	}



	/**
	 * Update a CiviCRM Contact with a given set of data.
	 *
	 * This is an alias of `self::create()` except that we expect an ID to have
	 * been set in the data.
	 *
	 * @since 0.5
	 *
	 * @param array $data The CiviCRM Note data.
	 * @return array|bool The array of data from the CiviCRM API, or false on failure.
	 */
	public function update( $data ) {

		// Log and bail if there's no Note ID.
		if ( empty( $data['id'] ) ) {
			$e = new \Exception();
			$trace = $e->getTraceAsString();
			error_log( print_r( [
				'method' => __METHOD__,
				'message' => __( 'A numerical ID must be present to update a Note.', 'civicrm-wp-profile-sync' ),
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
	 * Get the data for a Note.
	 *
	 * @since 0.5
	 *
	 * @param integer $note_id The numeric ID of the Note.
	 * @return array $note The array of Note data, or empty if none.
	 */
	public function get_by_id( $note_id ) {

		// Init return.
		$note = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $note;
		}

		// Construct API query.
		$params = [
			'version' => 3,
			'id' => $note_id,
		];

		// Get Note details via API.
		$result = civicrm_api( 'Note', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $note;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $note;
		}

		// The result set should contain only one item.
		$note = (object) array_pop( $result['values'] );

		// --<
		return $note;

	}



	/**
	 * Get the Notes for a given Contact ID.
	 *
	 * @since 0.5
	 *
	 * @param integer $contact_id The numeric ID of the CiviCRM Contact.
	 * @return array $note_data The array of Note data for the CiviCRM Contact.
	 */
	public function notes_get_for_contact( $contact_id ) {

		// Init return.
		$note_data = [];

		// Bail if we have no Contact ID.
		if ( empty( $contact_id ) ) {
			return $note_data;
		}

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $note_data;
		}

		// Define params to get queried Notes.
		$params = [
			'version' => 3,
			'sequential' => 1,
			'contact_id' => $contact_id,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Note', 'get', $params );

		// Bail if there's an error.
		if ( ! empty( $result['is_error'] ) && $result['is_error'] == 1 ) {
			return $note_data;
		}

		// Bail if there are no results.
		if ( empty( $result['values'] ) ) {
			return $note_data;
		}

		// The result set it what we want.
		$note_data = $result['values'];

		// --<
		return $note_data;

	}



	// -------------------------------------------------------------------------



	/**
	 * Gets the CiviCRM Note Fields.
	 *
	 * @since 0.5
	 *
	 * @param string $filter The token by which to filter the array of Fields.
	 * @return array $fields The array of Field names.
	 */
	public function civicrm_fields_get( $filter = 'none' ) {

		// Only do this once per Field Type and filter.
		static $pseudocache;
		if ( isset( $pseudocache[ $filter ] ) ) {
			return $pseudocache[ $filter ];
		}

		// Init return.
		$fields = [];

		// Try and init CiviCRM.
		if ( ! $this->civicrm->is_initialised() ) {
			return $fields;
		}

		// Construct params.
		$params = [
			'version' => 3,
			'options' => [
				'limit' => 0, // No limit.
			],
		];

		// Call the API.
		$result = civicrm_api( 'Note', 'getfields', $params );

		// Override return if we get some.
		if ( $result['is_error'] == 0 && ! empty( $result['values'] ) ) {

			// Check for no filter.
			if ( $filter == 'none' ) {

				// Grab all of them.
				$fields = $result['values'];

			// Check public filter.
			} elseif ( $filter == 'public' ) {

				// Skip all but those defined in our public Note Fields array.
				foreach ( $result['values'] as $key => $value ) {
					if ( array_key_exists( $value['name'], $this->note_fields ) ) {
						$fields[] = $value;
					}
				}

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[ $filter ] ) ) {
			$pseudocache[ $filter ] = $fields;
		}

		// --<
		return $fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Listen for Attachment support queries.
	 *
	 * This method responds with an "Entity Table" because Note Attachments are
	 * supported in the CiviCRM UI.
	 *
	 * @since 0.5.2
	 *
	 * @param array $entity_tables The existing "Entity Tables".
	 * @return array $entity_tables The mapped "Entity Tables".
	 */
	public function query_attachment_support( $entity_tables ) {

		// Append our "Entity Table" if not already present.
		if ( ! array_key_exists( 'civicrm_note', $entity_tables ) ) {
			$entity_tables['civicrm_note'] = __( 'Note', 'civicrm-wp-profile-sync' );
		}

		// --<
		return $entity_tables;

	}



	/**
	 * Respond to queries for Attachment choices from the Attachment class.
	 *
	 * This method responds with an "Entity Table" because Note Attachments are
	 * supported in the CiviCRM UI.
	 *
	 * @since 0.5.2
	 *
	 * @param array $entity_tables The existing "Entity Tables".
	 * @param array $entity_array The Entity and ID array.
	 * @return array $entity_tables The mapped "Entity Tables".
	 */
	public function query_attachment_choices( $entity_tables, $entity_array ) {

		// Return early if there is no Location Rule for the "Contact" Entity.
		if ( ! array_key_exists( $this->civicrm->contact->identifier, $entity_array ) ) {
			return $entity_tables;
		}

		// Append our "Entity Table" if not already present.
		$entity_tables = $this->query_attachment_support( $entity_tables );

		// --<
		return $entity_tables;

	}



} // Class ends.



