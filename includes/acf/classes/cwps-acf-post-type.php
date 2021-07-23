<?php
/**
 * WordPress Post Type Class.
 *
 * Handles WordPress Post Type functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync WordPress Post Type Class
 *
 * A class that encapsulates WordPress Post Type functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Post_Type {

	/**
	 * ACF Loader object.
	 *
	 * @since 0.4
	 * @access public
	 * @var object $acf_loader The ACF Loader object.
	 */
	public $acf_loader;

	/**
	 * Supported Location Rule name.
	 *
	 * @since 0.5
	 * @access public
	 * @var string $rule_name The supported Location Rule name.
	 */
	public $rule_name = 'post_type';



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $acf_loader The ACF Loader object.
	 */
	public function __construct( $acf_loader ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $acf_loader;

		// Init when this plugin is loaded.
		add_action( 'cwps/acf/loaded', [ $this, 'initialise' ] );

	}



	/**
	 * Initialise this object.
	 *
	 * @since 0.4
	 */
	public function initialise() {

		// Register hooks.
		$this->register_hooks();

	}



	/**
	 * Register hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Listen for queries from the ACF Field Group class.
		add_filter( 'cwps/acf/field_group/query_supported_rules', [ $this, 'query_supported_rules' ], 10, 4 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all Post Types.
	 *
	 * @since 0.4
	 *
	 * @return array $post_types The array of Post Types.
	 */
	public function post_types_get_all() {

		// Get CPTs with admin UI.
		$args = [
			'public'   => true,
			'show_ui' => true,
		];

		$output = 'objects'; // Names or objects, note names is the default.
		$operator = 'and'; // Operator may be 'and' or 'or'.

		// Get Post Types.
		$post_types = get_post_types( $args, $output, $operator );

		/**
		 * Filter the Post Types.
		 *
		 * This filter can be used, for example, to exclude certain Post Types.
		 *
		 * @param array $post_types The existing Post Types.
		 * @param return $post_types The modified Post Types.
		 */
		$post_types = apply_filters( 'cwps/acf/post_types/get_all', $post_types );

		// --<
		return $post_types;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all Post Types that a Contact Type may be synced with.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type_id The numeric ID of the Contact Type.
	 * @return array $post_types The array of Post Types.
	 */
	public function post_types_get_for_contact_type( $contact_type_id ) {

		// Init return.
		$filtered = [];

		// Get all Post Types.
		$post_types = $this->post_types_get_all();

		// Get all used Post Types.
		$used_post_types = $this->acf_loader->mapping->mappings_get_all();

		// Get existing Post Type.
		$existing_post_type = '';
		if ( $contact_type_id !== 0 ) {
			$existing_post_type = $this->acf_loader->mapping->mapping_for_contact_type_get( $contact_type_id );
		}

		// Retain only those which are unused, plus the existing one.
		if ( count( $post_types ) > 0 ) {
			foreach( $post_types AS $post_type ) {
				$used = in_array( $post_type->name, $used_post_types );
				$mine = ( $post_type->name == $existing_post_type ) ? true : false;
				if ( ! $used OR $mine ) {
					$filtered[] = $post_type;
				}
			}
		}

		// --<
		return $filtered;

	}



	/**
	 * Get the Post Type that is mapped to a Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $contact_type_id The numeric ID of the Contact Type.
	 * @return string|boolean $post_type The name of Post Type, or false if not mapped.
	 */
	public function get_for_contact_type( $contact_type_id ) {

		// --<
		return $this->acf_loader->mapping->mapping_for_contact_type_get( $contact_type_id );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all Post Types that an Activity Type may be synced with.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_id The numeric ID of the Activity Type.
	 * @return array $post_types The array of Post Types.
	 */
	public function post_types_get_for_activity_type( $activity_type_id ) {

		// Init return.
		$filtered = [];

		// Get all Post Types.
		$post_types = $this->post_types_get_all();

		// Get all used Post Types.
		$used_post_types = $this->acf_loader->mapping->mappings_get_all();

		// Get existing Post Type.
		$existing_post_type = '';
		if ( $activity_type_id !== 0 ) {
			$existing_post_type = $this->acf_loader->mapping->mapping_for_activity_type_get( $activity_type_id );
		}

		// Retain only those which are unused, plus the existing one.
		if ( count( $post_types ) > 0 ) {
			foreach( $post_types AS $post_type ) {
				$used = in_array( $post_type->name, $used_post_types );
				$mine = ( $post_type->name == $existing_post_type ) ? true : false;
				if ( ! $used OR $mine ) {
					$filtered[] = $post_type;
				}
			}
		}

		// --<
		return $filtered;

	}



	/**
	 * Get the Post Type that is mapped to an Activity Type.
	 *
	 * @since 0.4
	 *
	 * @param integer $activity_type_id The numeric ID of the Activity Type.
	 * @return string|boolean $post_type The name of Post Type, or false if not mapped.
	 */
	public function get_for_activity_type( $activity_type_id ) {

		// --<
		return $this->acf_loader->mapping->mapping_for_activity_type_get( $activity_type_id );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all Post Types that a Participant Role may be synced with.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the Participant Role.
	 * @return array $post_types The array of Post Types.
	 */
	public function post_types_get_for_participant_role( $participant_role_id ) {

		// Init return.
		$filtered = [];

		// Get all Post Types.
		$post_types = $this->post_types_get_all();

		// Get all used Post Types.
		$used_post_types = $this->acf_loader->mapping->mappings_get_all();

		// Get existing Post Type.
		$existing_post_type = '';
		if ( $participant_role_id !== 0 ) {
			$existing_post_type = $this->acf_loader->mapping->mapping_for_participant_role_get( $participant_role_id );
		}

		// Retain only those which are unused, plus the existing one.
		if ( count( $post_types ) > 0 ) {
			foreach( $post_types AS $post_type ) {
				$used = in_array( $post_type->name, $used_post_types );
				$mine = ( $post_type->name == $existing_post_type ) ? true : false;
				if ( ! $used OR $mine ) {
					$filtered[] = $post_type;
				}
			}
		}

		// --<
		return $filtered;

	}



	/**
	 * Get the Post Type that is mapped to a Participant Role.
	 *
	 * @since 0.5
	 *
	 * @param integer $participant_role_id The numeric ID of the Participant Role.
	 * @return string|boolean $post_type The name of Post Type, or false if not mapped.
	 */
	public function get_for_participant_role( $participant_role_id ) {

		// --<
		return $this->acf_loader->mapping->mapping_for_participant_role_get( $participant_role_id );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the number of Posts in a WordPress Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of the WordPress Post Type.
	 * @return integer $count The number of Contacts of that Type.
	 */
	public function post_count( $post_type ) {

		// Get the count of all Posts of the Post Type.
		$counts = wp_count_posts( $post_type );

		// Cast as array.
		$counts = (array) $counts;

		// Bail if there are none.
		if ( empty( $counts ) ) {
			return 0;
		}

		// We don't care about the post status.
		$sum = array_sum( array_values( $counts ) );

		// Except for "auto-draft", which we exclude.
		if ( ! empty( $counts['auto-draft'] ) ) {
			$sum = $sum - $counts['auto-draft'];
		}

		// --<
		return $sum;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all Post Types that are mapped to an Entity Type.
	 *
	 * @since 0.4
	 *
	 * @param string $entity_type The Entity to which the Post Types are mapped.
	 * @return array $post_types The array of mapped Post Types.
	 */
	public function get_mapped( $entity_type = 'contact' ) {

		// Init return.
		$post_types = [];

		// Get all Post Types.
		$all_post_types = $this->acf_loader->post_type->post_types_get_all();

		// Get all used Post Types by Entity.
		switch ( $entity_type ) {

			case 'contact':
				$synced_post_types = $this->acf_loader->mapping->mappings_for_contact_types_get();
				break;

			case 'activity':
				$synced_post_types = $this->acf_loader->mapping->mappings_for_activity_types_get();
				break;

			case 'participant_role':
				$synced_post_types = $this->acf_loader->mapping->mappings_for_participant_roles_get();
				break;

		}

		// Loop through them and get the ones we want.
		foreach( $all_post_types AS $post_type ) {
			if ( in_array( $post_type->name, $synced_post_types ) ) {
				$post_types[] = $post_type;
			}
		}

		/**
		 * Filter the mapped Post Types.
		 *
		 * @since 0.5
		 *
		 * @param $post_types The mapped WordPress Post Types.
		 * @param $entity_type The requested CiviCRM Entity Type.
		 */
		$post_types = apply_filters( 'cwps/acf/post_types/get_mapped', $post_types, $entity_type );

		// --<
		return $post_types;

	}



	/**
	 * Check if a Post Type is mapped to a Contact Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of the Post Type.
	 * @return boolean $is_linked True if the Post Type is mapped, false otherwise.
	 */
	public function is_mapped_to_contact_type( $post_type ) {

		// Assume not.
		$is_linked = false;

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_contact_types_get();

		// Bail if there are no mappings.
		if ( empty( $mapped_post_types ) ) {
			return $is_linked;
		}

		// Override if this Post Type is mapped.
		if ( in_array( $post_type, $mapped_post_types ) ) {
			$is_linked = true;
		}

		// --<
		return $is_linked;

	}



	/**
	 * Check if a Post Type is mapped to an Activity Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of the Post Type.
	 * @return boolean $is_linked True if the Post Type is mapped, false otherwise.
	 */
	public function is_mapped_to_activity_type( $post_type ) {

		// Assume not.
		$is_linked = false;

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_activity_types_get();

		// Bail if there are no mappings.
		if ( empty( $mapped_post_types ) ) {
			return $is_linked;
		}

		// Override if this Post Type is mapped.
		if ( in_array( $post_type, $mapped_post_types ) ) {
			$is_linked = true;
		}

		// --<
		return $is_linked;

	}



	/**
	 * Check if a Post Type is mapped to Participant Roles.
	 *
	 * @since 0.5
	 *
	 * @param string $post_type The name of the Post Type.
	 * @return boolean $is_linked True if the Post Type is mapped, false otherwise.
	 */
	public function is_mapped_to_participant_role( $post_type ) {

		// Assume not.
		$is_linked = false;

		// Get mapped Post Types.
		$mapped_post_types = $this->acf_loader->mapping->mappings_for_participant_roles_get();

		// Bail if there are no mappings.
		if ( empty( $mapped_post_types ) ) {
			return $is_linked;
		}

		// Override if this Post Type is mapped.
		if ( in_array( $post_type, $mapped_post_types ) ) {
			$is_linked = true;
		}

		// --<
		return $is_linked;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the singular label for a given Post Type.
	 *
	 * @since 0.4
	 *
	 * @param string $post_type The name of the Post Type.
	 * @return string $label The singular label for the Post Type.
	 */
	public function singular_label_get( $post_type ) {

		// Get Post Type data.
		$post_type_data = get_post_type_object( $post_type );

		// Default to the (usually plural) label.
		$label = $post_type_data->label;

		// If the labels array is populated, override with singular label.
		if ( ! empty( $post_type_data->labels->singular_name ) ) {
			$label = $post_type_data->labels->singular_name;
		}

		// --<
		return $label;

	}



	// -------------------------------------------------------------------------



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



} // Class ends.



