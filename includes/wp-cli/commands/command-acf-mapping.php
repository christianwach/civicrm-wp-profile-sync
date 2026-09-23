<?php
/**
 * ACF Mapped Post Type mappings command class.
 *
 * @package CiviCRM_WP_Profile_Sync
 */

// Bail if WP-CLI is not present.
if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Manage the mappings between CiviCRM Entity Types and WordPress Post Types.
 *
 * ## EXAMPLES
 *
 *     # Get the Post Type mapped to the CiviCRM Contact Type with ID 4.
 *     $  wp profilesync acf mapping get contact --entity-type-id=4
 *     student
 *
 *     # Map the Student Post Type to the Student Contact Type.
 *     $ wp profilesync acf mapping create contact 4 student
 *     Success: Mapped the Post Type 'student' to Entity Type ID '4'.
 *
 *     # Update the mapping for the Student Contact Type.
 *     $ wp profilesync acf mapping update contact 4 student
 *     Success: Updated the mapping for Entity Type ID '4' to Post Type 'student'.
 *
 *     # Delete the mapping for the Student Contact Type by Entity Type ID.
 *     $ wp profilesync acf mapping delete contact 4
 *     Success: Deleted the mapping between Entity Type ID '4' and Post Type 'student'.
 *
 *     # List the mappings in JSON format.
 *     $ wp profilesync acf mapping list --format=json
 *     {"contact-post":{"9":"employee"},"participant-role-post":{"68":"speaker"}}
 *
 * @since 0.7.4
 *
 * @package CiviCRM_WP_Profile_Sync
 */
class CiviCRM_WPPS_CLI_Command_ACF_Mapping extends CiviCRM_WPPS_CLI_Command {

	/**
	 * Gets the mappings for a CiviCRM Entity.
	 *
	 * There are currently only three CiviCRM Entities that are mappable and they are
	 * mapped by their "Type ID", e.g. Contact Type, Activity Type or Participant Role.
	 * The Entity (e.g. "contact") can be passed as e.g. "contact", "contact-type" or
	 * "contact-type-id".
	 *
	 * Optionally specify a CiviCRM Entity Type ID to get its mapped WordPress Post Type
	 * or a WordPress Post Type to get its mapped CiviCRM Entity Type ID.
	 *
	 * ## OPTIONS
	 *
	 * <entity>
	 * : The slug of the CiviCRM Entity Type.
	 *
	 * [--entity-type-id=<entity-type-id>]
	 * : Get the Post Type for a particular CiviCRM Entity Type ID.
	 *
	 * [--post-type=<post-type>]
	 * : Get the CiviCRM Entity Type ID for a particular Post Type.
	 *
	 * [--format=<format>]
	 * : Get value in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Get the mappings for the CiviCRM Contact Types in JSON format.
	 *     $ wp profilesync acf mapping get contact --format=json
	 *     {"4":"student","9":"employee"}
	 *
	 *     # Get the Post Type mapped to the CiviCRM Contact Type with ID 4.
	 *     $  wp profilesync acf mapping get contact --entity-type-id=4
	 *     student
	 *
	 *     # Get the CiviCRM Contact Type ID mapped to the 'student' Post Type.
	 *     $  wp profilesync acf mapping get contact --post-type=student
	 *     4
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function get( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		// Grab positional arguments.
		list( $entity ) = $args;

		// Grab associative arguments.
		$entity_type_id = (int) \WP_CLI\Utils\get_flag_value( $assoc_args, 'entity-type-id', 0 );
		$post_type      = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'post-type', '' );

		// Sanity check.
		if ( 0 !== $entity_type_id && '' !== $post_type ) {
			WP_CLI::error( "Either '--entity-type-id' or '--post-type' can be passed, but not both." );
		}

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		/*
		 * There are currently only three Entities that are mappable - theye are mapped
		 * by their "Type ID", i.e. Contact Type, Activity Type or Participant Role.
		 */
		switch ( $entity ) {
			case 'contact':
			case 'contact-type':
			case 'contact-type-id':
				$key = 'contact-post';
				break;
			case 'activity':
			case 'activity-type':
			case 'activity-type-id':
				$key = 'activity-post';
				break;
			case 'participant':
			case 'participant-role':
			case 'participant-role-id':
				$key = 'participant-role-post';
				break;
			default:
				WP_CLI::error( "Unknown entity: '{$entity}'" );
		}

		// Get all the mappings.
		$mappings = $mapper->mappings_get();

		// Check if valid.
		if ( ! array_key_exists( $key, $mappings ) ) {
			WP_CLI::error( "Could not get '{$entity}' mapping. Does it exist?" );
		}

		// Get the requested value for the Entity.
		$value = $mappings[ $key ];

		// Maybe overwrite with the sub-value for the Entity Type ID.
		if ( 0 !== $entity_type_id ) {
			if ( ! array_key_exists( $entity_type_id, $value ) ) {
				WP_CLI::error( "Could not get mapping for Entity Type ID: '{$entity_type_id}'. Does it exist?" );
			}
			$value = $value[ $entity_type_id ];
		}

		// Maybe overwrite with the sub-value for the Post Type.
		if ( '' !== $post_type ) {
			$flipped = array_flip( $value );
			if ( ! array_key_exists( $post_type, $flipped ) ) {
				WP_CLI::error( "Could not get mapping for Post Type: '{$post_type}'. Does it exist?" );
			}
			$value = $flipped[ $post_type ];
		}

		WP_CLI::print_value( $value, $assoc_args );

	}

	/**
	 * Creates a mapping between a CiviCRM Entity Type ID and a WordPress Post Type.
	 *
	 * There are currently only three CiviCRM Entities that are mappable and they are
	 * mapped by their "Type ID", e.g. Contact Type, Activity Type or Participant Role.
	 * The Entity (e.g. "contact") can be passed as e.g. "contact", "contact-type" or
	 * "contact-type-id".
	 *
	 * ## OPTIONS
	 *
	 * <entity>
	 * : The slug of the CiviCRM Entity.
	 *
	 * <entity-type-id>
	 * : The numeric CiviCRM Entity Type ID.
	 *
	 * <post-type>
	 * : The slug of the WordPress Post Type.
	 *
	 * ## EXAMPLES
	 *
	 *     # Map the Student Post Type to the Student Contact Type.
	 *     $ wp profilesync acf mapping create contact 4 student
	 *     Success: Mapped the Post Type 'student' to Entity Type ID '4'.
	 *
	 * @alias link
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function create( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		// Grab positional arguments.
		list( $entity, $entity_type_id, $post_type ) = $args;

		// Sanitise values.
		$entity_type_id = (int) $entity_type_id;

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Verify that $post_type is a Post Type.
		if ( is_null( get_post_type_object( $post_type ) ) ) {
			WP_CLI::error( "Unknown Post Type: '{$post_type}'" );
		}

		// Verify that the Post Type has not already been mapped.
		if ( $this->post_type_is_mapped( $post_type ) ) {
			WP_CLI::error( "Post Type '{$post_type}' is already mapped" );
		}

		/*
		 * There are currently only three Entities that are mappable - they are mapped
		 * by their "Type ID", i.e. Contact Type, Activity Type or Participant Role.
		 */
		switch ( $entity ) {
			case 'contact':
			case 'contact-type':
			case 'contact-type-id':
				// Check that the Entity Type ID is a valid Contact Type.
				if ( $this->contact_type_exists( $entity_type_id ) ) {
					// And that it has not already been mapped.
					if ( ! $this->contact_type_is_mapped( $entity_type_id ) ) {
						$mapper->mapping_for_contact_type_update( $entity_type_id, $post_type );
					} else {
						WP_CLI::error( "Contact Type '{$entity_type_id}' is already mapped." );
					}
				} else {
					WP_CLI::error( "Unknown Contact Type: '{$entity_type_id}'" );
				}
				break;
			case 'activity':
			case 'activity-type':
			case 'activity-type-id':
				// Check that the Entity Type ID is a valid Activity Type.
				if ( $this->activity_type_exists( $entity_type_id ) ) {
					// And that it has not already been mapped.
					if ( ! $this->activity_type_is_mapped( $entity_type_id ) ) {
						$mapper->mapping_for_activity_type_update( $entity_type_id, $post_type );
					} else {
						WP_CLI::error( "Activity Type '{$entity_type_id}' is already mapped." );
					}
				} else {
					WP_CLI::error( "Unknown Activity Type: '{$entity_type_id}'" );
				}
				break;
			case 'participant':
			case 'participant-role':
			case 'participant-role-id':
				// Check that the Entity Type ID is a valid Participant Role.
				if ( $this->participant_role_exists( $entity_type_id ) ) {
					// And that it has not already been mapped.
					if ( ! $this->participant_role_is_mapped( $entity_type_id ) ) {
						$mapper->mapping_for_participant_role_update( $entity_type_id, $post_type );
					} else {
						WP_CLI::error( "Participant Role '{$entity_type_id}' is already mapped." );
					}
				} else {
					WP_CLI::error( "Unknown Participant Role: '{$entity_type_id}'." );
				}
				break;
			default:
				WP_CLI::error( "Unknown entity: '{$entity}'." );
		}

		WP_CLI::success( "Mapped the Post Type '{$post_type}' to Entity Type ID '{$entity_type_id}'." );

	}

	/**
	 * Updates the mapping between CiviCRM Entity Type ID and a WordPress Post Type.
	 *
	 * ## OPTIONS
	 *
	 * <entity>
	 * : The slug of the CiviCRM Entity.
	 *
	 * <post-type-or-entity-type-id>
	 * : The slug of the Post Type or the numeric CiviCRM Entity Type ID.
	 *
	 * [<value>]
	 * : The new value. If omitted, the value is read from STDIN.
	 *
	 * [--format=<format>]
	 * : The serialization format for the value.
	 * ---
	 * default: plaintext
	 * options:
	 *   - plaintext
	 *   - json
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Update the mapping for the Student Contact Type.
	 *     $ wp profilesync acf mapping update contact 4 student
	 *     Success: Updated the mapping for Entity Type ID '4' to Post Type 'student'.
	 *
	 *     # Update the mapping for the Student Post Type.
	 *     $ wp profilesync acf mapping update contact student 4
	 *     Success: Updated the mapping for Post Type 'student' to Entity Type ID '4'.
	 *
	 * @alias set
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function update( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		// Grab positional arguments.
		$entity = $args[0];
		$type   = $args[1];
		$value  = WP_CLI::get_value_from_arg_or_stdin( $args, 2 );
		$value  = WP_CLI::read_value( $value, $assoc_args );

		// Grab associative arguments.
		$format = (string) \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'plaintext' );

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Type is an Entity Type ID.
		if ( is_numeric( $type ) ) {

			// Verify that $value is a valid Post Type.
			if ( is_null( get_post_type_object( $value ) ) ) {
				WP_CLI::error( "Unknown Post Type: '{$value}'" );
			}

			// Verify that the Post Type has not already been mapped.
			if ( $this->post_type_is_mapped( $value ) ) {
				WP_CLI::error( "Post Type '{$value}' is already mapped" );
			}

			$entity_type_id = (int) $type;

			/*
			 * There are currently only three Entities that are mappable - they are mapped
			 * by their "Type ID", i.e. Contact Type, Activity Type or Participant Role.
			 */
			switch ( $entity ) {

				case 'contact':
				case 'contact-type':
				case 'contact-type-id':
					// Assign Post Type to Contact Type ID.
					$post_type = $mapper->mapping_for_contact_type_get( $entity_type_id );
					if ( ! empty( $post_type ) ) {
						$mapper->mapping_for_contact_type_update( $entity_type_id, $value );
					} else {
						WP_CLI::error( "Contact Type '{$entity_type_id}' is not mapped." );
					}
					break;

				case 'activity':
				case 'activity-type':
				case 'activity-type-id':
					// Assign Post Type to Activity Type ID.
					$post_type = $mapper->mapping_for_activity_type_get( $entity_type_id );
					if ( ! empty( $post_type ) ) {
						$mapper->mapping_for_activity_type_update( $entity_type_id, $value );
					} else {
						WP_CLI::error( "Activity Type '{$entity_type_id}' is not mapped." );
					}
					break;

				case 'participant':
				case 'participant-role':
				case 'participant-role-id':
					// Assign Post Type to Participant Role ID.
					$post_type = $mapper->participant_role_is_mapped( $entity_type_id );
					if ( ! empty( $post_type ) ) {
						$mapper->mapping_for_participant_role_update( $entity_type_id, $value );
					} else {
						WP_CLI::error( "Participant Role '{$entity_type_id}' is not mapped." );
					}
					break;

				default:
					WP_CLI::error( "Unknown entity: '{$entity}'" );

			}

			if ( ! empty( $post_type ) ) {
				WP_CLI::success( "Updated the mapping for Entity Type ID '{$entity_type_id}' to Post Type '{$value}'." );
			} else {
				WP_CLI::error( "Could not find the mapping for Entity Type ID '{$entity_type_id}'. Is it mapped?" );
			}

		}

		// Type is a Post Type.
		if ( ! is_numeric( $type ) ) {

			/*
			 * There are currently only three Entities that are mappable - they are mapped
			 * by their "Type ID", i.e. Contact Type, Activity Type or Participant Role.
			 */
			switch ( $entity ) {

				case 'contact':
				case 'contact-type':
				case 'contact-type-id':
					// Assign Entity Type ID to Post Type.
					$mapped_contact_types = $mapper->mappings_for_contact_types_get();
					$entity_type_id       = array_search( $type, $mapped_contact_types, true );
					if ( false !== $entity_type_id ) {
						// Check that $value is a valid Contact Type.
						if ( $this->contact_type_exists( $value ) ) {
							// And that it has not already been mapped.
							if ( ! $this->contact_type_is_mapped( $value ) ) {
								$mapper->mapping_for_contact_type_update( $value, $type );
							} else {
								WP_CLI::error( "Contact Type '{$entity_type_id}' is already mapped." );
							}
						} else {
							WP_CLI::error( "Unknown Contact Type: '{$value}'" );
						}
					}
					break;

				case 'activity':
				case 'activity-type':
				case 'activity-type-id':
					// Assign Entity Type ID to Post Type.
					$mapped_activity_types = $mapper->mappings_for_activity_types_get();
					$entity_type_id        = array_search( $type, $mapped_activity_types, true );
					if ( false !== $entity_type_id ) {
						// Check that $value is a valid Activity Type.
						if ( $this->activity_type_exists( $value ) ) {
							// And that it has not already been mapped.
							if ( ! $this->activity_type_is_mapped( $value ) ) {
								$mapper->mapping_for_activity_type_update( $value, $type );
							} else {
								WP_CLI::error( "Activity Type '{$value}' is already mapped." );
							}
						} else {
							WP_CLI::error( "Unknown Activity Type: '{$value}'" );
						}
					}
					break;

				case 'participant':
				case 'participant-role':
				case 'participant-role-id':
					// Assign Entity Type ID to Post Type.
					$mapped_activity_types = $mapper->mappings_for_participant_roles_get();
					$entity_type_id        = array_search( $type, $mapped_activity_types, true );
					if ( false !== $entity_type_id ) {
						// Check that $value is a valid Participant Role.
						if ( $this->participant_role_exists( $value ) ) {
							// And that it has not already been mapped.
							if ( ! $this->participant_role_is_mapped( $value ) ) {
								$mapper->mapping_for_participant_role_update( $value, $type );
							} else {
								WP_CLI::error( "Participant Role '{$value}' is already mapped." );
							}
						} else {
							WP_CLI::error( "Unknown Participant Role: '{$value}'" );
						}
					}
					break;

				default:
					WP_CLI::error( "Unknown entity: '{$entity}'" );

			}

			if ( false !== $entity_type_id ) {
				WP_CLI::success( "Updated the mapping for Post Type '{$type}' to Entity Type ID '{$entity_type_id}'." );
			} else {
				WP_CLI::error( "Could not find the mapping for Post Type '{$type}'. Is it mapped?" );
			}

		}

	}

	/**
	 * Deletes a mapping between a CiviCRM Entity Type and a WordPress Post Type.
	 *
	 * There are currently only three CiviCRM Entities that are mappable and they are
	 * mapped by their "Type ID", e.g. Contact Type, Activity Type or Participant Role.
	 * The Entity (e.g. "contact") can be passed as e.g. "contact", "contact-type" or
	 * "contact-type-id".
	 *
	 * ## OPTIONS
	 *
	 * <entity>
	 * : The slug of the CiviCRM Entity.
	 *
	 * <post-type-or-entity-type-id>
	 * : The slug of the Post Type or the numeric CiviCRM Entity Type ID.
	 *
	 * ## EXAMPLES
	 *
	 *     # Delete the mapping for the Student Contact Type by Entity Type ID.
	 *     $ wp profilesync acf mapping delete contact 4
	 *     Success: Deleted the mapping between Entity Type ID '4' and Post Type 'student'.
	 *
	 *     # Delete the mapping for the Student Contact Type by Post Type.
	 *     $ wp profilesync acf mapping delete contact student
	 *     Success: Deleted the mapping between Entity Type ID '4' and Post Type 'student'.
	 *
	 * @alias unlink
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function delete( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		// Grab positional arguments.
		list( $entity, $type ) = $args;

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Type is an Entity Type ID.
		if ( is_numeric( $type ) ) {

			$entity_type_id = (int) $type;

			/*
			 * There are currently only three Entities that are mappable - they are mapped
			 * by their "Type ID", i.e. Contact Type, Activity Type or Participant Role.
			 */
			switch ( $entity ) {

				case 'contact':
				case 'contact-type':
				case 'contact-type-id':
					// Check that the Entity Type ID is a valid Contact Type.
					if ( $this->contact_type_exists( $entity_type_id ) ) {
						// And that it is mapped.
						$post_type = $this->contact_type_is_mapped( $entity_type_id );
						if ( ! empty( $post_type ) ) {
							$mapper->mapping_for_contact_type_remove( $entity_type_id );
						} else {
							WP_CLI::error( "Contact Type '{$entity_type_id}' is not mapped." );
						}
					} else {
						WP_CLI::error( "Unknown Contact Type: '{$entity_type_id}'" );
					}
					break;

				case 'activity':
				case 'activity-type':
				case 'activity-type-id':
					// Check that the Entity Type ID is a valid Activity Type.
					if ( $this->activity_type_exists( $entity_type_id ) ) {
						// And that it is mapped.
						$post_type = $this->activity_type_is_mapped( $entity_type_id );
						if ( ! empty( $post_type ) ) {
							$mapper->mapping_for_activity_type_remove( $entity_type_id );
						} else {
							WP_CLI::error( "Activity Type '{$entity_type_id}' is not mapped." );
						}
					} else {
						WP_CLI::error( "Unknown Activity Type: '{$entity_type_id}'" );
					}
					break;

				case 'participant':
				case 'participant-role':
				case 'participant-role-id':
					// Check that the Entity Type ID is a valid Participant Role.
					if ( $this->participant_role_exists( $entity_type_id ) ) {
						// And that it is mapped.
						$post_type = $this->participant_role_is_mapped( $entity_type_id );
						if ( ! empty( $post_type ) ) {
							$mapper->mapping_for_participant_role_remove( $entity_type_id );
						} else {
							WP_CLI::error( "Participant Role '{$entity_type_id}' is not mapped." );
						}
					} else {
						WP_CLI::error( "Unknown Participant Role: '{$entity_type_id}'" );
					}
					break;

				default:
					WP_CLI::error( "Unknown entity: '{$entity}'" );

			}

		}

		// Type is a Post Type.
		if ( ! is_numeric( $type ) ) {

			$post_type = (string) $type;

			// Verify that $post_type is a Post Type.
			if ( is_null( get_post_type_object( $post_type ) ) ) {
				WP_CLI::error( "Unknown Post Type: '{$post_type}'." );
			}

			// Verify that the Post Type is mapped.
			if ( ! $this->post_type_is_mapped( $post_type ) ) {
				WP_CLI::error( "Post Type '{$post_type}' is not mapped." );
			}

			/*
			 * There are currently only three Entities that are mappable - they are mapped
			 * by their "Type ID", i.e. Contact Type, Activity Type or Participant Role.
			 */
			switch ( $entity ) {

				case 'contact':
				case 'contact-type':
				case 'contact-type-id':
					// Use "wpwp profilesync acf mapping get" to get the Entity Type ID.
					$command        = "cvwpps acf mapping get contact --post-type={$post_type}";
					$options        = [
						'launch' => false,
						'return' => true,
					];
					$entity_type_id = WP_CLI::runcommand( $command, $options );
					if ( ! empty( $entity_type_id ) ) {
						$mapper->mapping_for_contact_type_remove( $entity_type_id );
					} else {
						WP_CLI::error( "Unknown Entity Type ID '{$entity_type_id}'." );
					}
					break;

				case 'activity':
				case 'activity-type':
				case 'activity-type-id':
					// Use "wpwp profilesync acf mapping get" to get the Entity Type ID.
					$command        = "cvwpps acf mapping get activity --post-type={$post_type}";
					$options        = [
						'launch' => false,
						'return' => true,
					];
					$entity_type_id = WP_CLI::runcommand( $command, $options );
					if ( ! empty( $entity_type_id ) ) {
						$mapper->mapping_for_activity_type_remove( $entity_type_id );
					} else {
						WP_CLI::error( "Unknown Entity Type ID '{$entity_type_id}'." );
					}
					break;

				case 'participant':
				case 'participant-role':
				case 'participant-role-id':
					// Use "wpwp profilesync acf mapping get" to get the Entity Type ID.
					$command        = "cvwpps acf mapping get participant --post-type={$post_type}";
					$options        = [
						'launch' => false,
						'return' => true,
					];
					$entity_type_id = WP_CLI::runcommand( $command, $options );
					if ( ! empty( $entity_type_id ) ) {
						$mapper->mapping_for_participant_role_remove( $entity_type_id );
					} else {
						WP_CLI::error( "Unknown Entity Type ID '{$entity_type_id}'." );
					}
					break;

				default:
					WP_CLI::error( "Unknown entity: '{$entity}'" );

			}

		}

		WP_CLI::success( "Deleted the mapping between Entity Type ID '{$entity_type_id}' and Post Type '{$post_type}'." );

	}

	/**
	 * Lists the mappings between CiviCRM Entities and Post Types.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Get list in a particular format.
	 * ---
	 * default: var_export
	 * options:
	 *   - var_export
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # List the mappings in JSON format.
	 *     $ wp profilesync acf mapping list --format=json
	 *     {"contact-post":{"9":"employee"},"participant-role-post":{"68":"speaker"}}
	 *
	 *     # List the mappings in YAML format.
	 *     $ wp profilesync acf mapping list --format=yaml
	 *     ---
	 *     contact-post:
	 *       9: employee
	 *     participant-role-post:
	 *       576: speaker
	 *
	 * @since 0.7.4
	 *
	 * @param array $args The WP-CLI positional arguments.
	 * @param array $assoc_args The WP-CLI associative arguments.
	 */
	public function list( $args, $assoc_args ) {

		// Check for presence of Advanced Custom Fields.
		if ( ! defined( 'ACF_VERSION' ) ) {
			WP_CLI::error( 'Unable to find ACF install.' );
		}

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		$value = $mapper->mappings_get();

		WP_CLI::print_value( $value, $assoc_args );

	}

	/**
	 * Checks if a Post Type has already been mapped.
	 *
	 * @since 0.7.4
	 *
	 * @param int $post_type The slug of the Post Type.
	 * @return bool True if Post Type is mapped, false otherwise.
	 */
	private function post_type_is_mapped( $post_type ) {

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Get all mapped Post Types.
		$mapped_post_types = $mapper->mappings_get_all();

		// Cannot be used more than once.
		if ( in_array( $post_type, $mapped_post_types, true ) ) {
			return true;
		} else {
			return false;
		}

	}

	/**
	 * Checks if a CiviCRM Entity Type ID is a valid Contact Type.
	 *
	 * @since 0.7.4
	 *
	 * @param int $entity_type_id The CiviCRM Entity Type ID.
	 * @return bool True if the Entity Type ID is a Contact Type, false otherwise.
	 */
	private function contact_type_exists( $entity_type_id ) {

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		try {
			$result = \Civi\Api4\ContactType::get( false )
				->addSelect( 'id' )
				->execute();
		} catch ( CRM_Core_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		// Cannot be if there are none.
		if ( 0 === (int) $result->count() ) {
			return false;
		}

		// Extract IDs from the ArrayObject.
		$contact_types    = array_column( $result->getArrayCopy(), 'id' );
		$contact_type_ids = array_map( 'intval', $contact_types );

		// True if found.
		if ( in_array( (int) $entity_type_id, $contact_type_ids, true ) ) {
			return true;
		}

		// Otherwise not found.
		return false;

	}

	/**
	 * Checks if a Contact Type has already been mapped.
	 *
	 * @since 0.7.4
	 *
	 * @param int $entity_type_id The CiviCRM Entity Type ID.
	 * @return string|bool $post_type The Post Type if the Contact Type is mapped, false otherwise.
	 */
	private function contact_type_is_mapped( $entity_type_id ) {

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Return it if found.
		$post_type = $mapper->mapping_for_contact_type_get( (int) $entity_type_id );
		if ( ! empty( $post_type ) ) {
			return $post_type;
		}

		// Not found.
		return false;

	}

	/**
	 * Checks if a CiviCRM Entity Type ID is a valid Activity Type.
	 *
	 * @since 0.7.4
	 *
	 * @param int $entity_type_id The CiviCRM Entity Type ID.
	 * @return bool True if the Entity Type ID is an Activity Type, false otherwise.
	 */
	private function activity_type_exists( $entity_type_id ) {

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		try {
			$result = \Civi\Api4\OptionGroup::get( false )
				->addSelect( 'id' )
				->addWhere( 'name', '=', 'activity_type' )
				->addChain(
					'activity_types',
					\Civi\Api4\OptionValue::get( false )
						->addSelect( 'id' )
						->addWhere( 'option_group_id', '=', '$id' )
				)
				->execute();
		} catch ( CRM_Core_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		// Cannot be if there no Option Group is found.
		if ( 0 === (int) $result->count() ) {
			return false;
		}

		// We need the chained data array.
		$values = $result->first();
		if ( empty( $values['activity_types'] ) ) {
			return false;
		}

		// Cannot be if there are no Option Values.
		if ( 0 === count( $values['activity_types'] ) ) {
			return false;
		}

		// Extract IDs from the Option Values.
		$activity_types    = array_column( $values['activity_types'], 'id' );
		$activity_type_ids = array_map( 'intval', $activity_types );

		// True if found.
		if ( in_array( (int) $entity_type_id, $activity_type_ids, true ) ) {
			return true;
		}

		// Otherwise not found.
		return false;

	}

	/**
	 * Checks if a Activity Type has already been mapped.
	 *
	 * @since 0.7.4
	 *
	 * @param int $entity_type_id The CiviCRM Entity Type ID.
	 * @return string|bool $post_type The Post Type if the Activity Type is mapped, false otherwise.
	 */
	private function activity_type_is_mapped( $entity_type_id ) {

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Return it if found.
		$post_type = $mapper->mapping_for_activity_type_get( (int) $entity_type_id );
		if ( ! empty( $post_type ) ) {
			return $post_type;
		}

		// Not found.
		return false;

	}

	/**
	 * Checks if a CiviCRM Entity Type ID is a valid Participant Role.
	 *
	 * @since 0.7.4
	 *
	 * @param int $entity_type_id The CiviCRM Entity Type ID.
	 * @return bool True if the Entity Type ID is a Participant Role, false otherwise.
	 */
	private function participant_role_exists( $entity_type_id ) {

		// Bootstrap CiviCRM.
		$this->bootstrap_civicrm();

		try {
			$result = \Civi\Api4\OptionGroup::get( false )
				->addSelect( 'id' )
				->addWhere( 'name', '=', 'participant_role' )
				->addChain(
					'participant_roles',
					\Civi\Api4\OptionValue::get( false )
						->addSelect( 'id' )
						->addWhere( 'option_group_id', '=', '$id' )
				)
				->execute();
		} catch ( CRM_Core_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		// Cannot be if there no Option Group is found.
		if ( 0 === (int) $result->count() ) {
			return false;
		}

		// We need the chained data array.
		$values = $result->first();
		if ( empty( $values['participant_roles'] ) ) {
			return false;
		}

		// Cannot be if there are no Option Values.
		if ( 0 === count( $values['participant_roles'] ) ) {
			return false;
		}

		// Extract IDs from the Option Values.
		$participant_roles    = array_column( $values['participant_roles'], 'id' );
		$participant_role_ids = array_map( 'intval', $participant_roles );

		// True if found.
		if ( in_array( (int) $entity_type_id, $participant_role_ids, true ) ) {
			return true;
		}

		// Otherwise not found.
		return false;

	}

	/**
	 * Checks if a Participant Role has already been mapped.
	 *
	 * @since 0.7.4
	 *
	 * @param int $entity_type_id The CiviCRM Entity Type ID.
	 * @return string|bool $post_type The Post Type if the Participant Role is mapped, false otherwise.
	 */
	private function participant_role_is_mapped( $entity_type_id ) {

		$plugin = civicrm_wp_profile_sync();
		$mapper = $plugin->acf->mapping;

		// Return it if found.
		$post_type = $mapper->mapping_for_participant_role_get( (int) $entity_type_id );
		if ( ! empty( $post_type ) ) {
			return $post_type;
		}

		// Not found.
		return false;

	}

}
