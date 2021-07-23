<?php
/**
 * ACF Field Class.
 *
 * Handles ACF Field functionality.
 *
 * @package CiviCRM_WP_Profile_Sync
 * @since 0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;



/**
 * CiviCRM Profile Sync ACF Field Class.
 *
 * A class that encapsulates ACF Field functionality.
 *
 * @since 0.4
 */
class CiviCRM_Profile_Sync_ACF_Field {

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
	 * @var object $acf The parent object.
	 */
	public $acf;



	/**
	 * Constructor.
	 *
	 * @since 0.4
	 *
	 * @param object $parent The parent object reference.
	 */
	public function __construct( $parent ) {

		// Store reference to ACF Loader object.
		$this->acf_loader = $parent->acf_loader;

		// Store reference to parent.
		$this->acf = $parent;

		// Init when this plugin is loaded.
		add_action( 'cwps/acf/acf/loaded', [ $this, 'register_hooks' ] );

	}



	/**
	 * Register WordPress hooks.
	 *
	 * @since 0.4
	 */
	public function register_hooks() {

		// Validate Fields.
		add_filter( 'acf/validate_value/type=select', [ $this, 'value_validate' ], 10, 4 );
		add_filter( 'acf/validate_value/type=radio', [ $this, 'value_validate' ], 10, 4 );
		add_filter( 'acf/validate_value/type=text', [ $this, 'value_validate' ], 10, 4 );

		// Add Setting Field to Fields.
		add_action( 'acf/render_field_settings', [ $this, 'field_setting_add' ] );

		// Customise "Google Map" Fields.
		add_action( 'acf/render_field/type=google_map', [ $this, 'google_map_styles_add' ] );
		add_action( 'acf/load_value/type=google_map', [ $this, 'google_map_value_modify' ], 10, 3 );
		add_action( 'acf/update_value/type=google_map', [ $this, 'google_map_value_modify' ], 10, 3 );
		add_filter( 'cwps/acf/field_group/field/pre_update', [ $this, 'google_map_setting_modify' ], 10, 2 );

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the type of WordPress Entity that a Field refers to.
	 *
	 * @see https://www.advancedcustomfields.com/resources/get_fields/
	 * @see acf_decode_post_id()
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID" parameter.
	 * @return string The type of WordPress Entity that a Field refers to.
	 */
	public function entity_type_get( $post_id ) {

		// If numeric, it's a Post.
		if ( is_numeric( $post_id ) ) {
			return 'post';
		}

		// Does it refer to a WordPress User?
		if ( false !== strpos( $post_id, 'user_' ) ) {
			return 'user';
		}

		// Does it refer to a WordPress Taxonomy?
		if ( false !== strpos( $post_id, 'category_' ) ) {
			return 'category';
		}

		// Does it refer to a WordPress Term?
		if ( false !== strpos( $post_id, 'term_' ) ) {
			return 'term';
		}

		// Does it refer to a WordPress Comment?
		if ( false !== strpos( $post_id, 'comment_' ) ) {
			return 'comment';
		}

		// Does it refer to an ACF Options Page?
		if ( $post_id === 'options' ) {
			return 'options';
		}

		// Does it refer to an ACF Option?
		if ( $post_id === 'option' ) {
			return 'option';
		}

		// Fallback.
		return 'unknown';

	}



	/**
	 * Query for the Contact ID that this ACF "Post ID" is mapped to.
	 *
	 * We have to query like this because the ACF "Post ID" is actually only a
	 * Post ID if it's an integer. Other string values indicate other WordPress
	 * Entities, some of which may be handled by other plugins.
	 *
	 * @see https://www.advancedcustomfields.com/resources/get_fields/
	 *
	 * @since 0.4
	 *
	 * @param boolean $post_id The ACF "Post ID".
	 * @return integer|boolean $contact_id The mapped Contact ID, or false if not mapped.
	 */
	public function query_contact_id( $post_id ) {

		// Init return.
		$contact_id = false;

		// Get the WordPress Entity.
		$entity = $this->entity_type_get( $post_id );

		/**
		 * Query for the Contact ID that this ACF "Post ID" is mapped to.
		 *
		 * This filter sends out a request for other classes to respond with a
		 * Contact ID if they detect that this ACF "Post ID" maps to one.
		 *
		 * Internally, this is used by:
		 *
		 * @see CiviCRM_Profile_Sync_ACF_Custom_CiviCRM_Contact_ID_Field::load_value()
		 *
		 * @since 0.4
		 *
		 * @param boolean $contact_id False, since we're asking for a Contact ID.
		 * @param integer|string $post_id The ACF "Post ID".
		 * @param string $entity The kind of WordPress Entity.
		 * @return integer|boolean $contact_id The mapped Contact ID, or false if not mapped.
		 */
		$contact_id = apply_filters( 'cwps/acf/query_contact_id', $contact_id, $post_id, $entity );

		// --<
		return $contact_id;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get all mapped ACF Fields attached to a Post.
	 *
	 * We have to do this because both `get_fields()` and `get_field_objects()`
	 * DO NOT return the full set - only those with values that have been saved
	 * at one time or another and therefore exist as `post_meta`.
	 *
	 * As a result, this is not a reliable way to get ALL fields for a Post.
	 *
	 * Instead, we need to find all the Field Groups for a Post, then find
	 * all the Fields attached to the Field Group, then filter those so that
	 * only ones that are mapped to CiviCRM remain.
	 *
	 * @since 0.4
	 *
	 * @param integer|string $post_id The ACF "Post ID".
	 * @return array $fields The mapped ACF Fields for this post.
	 */
	public function fields_get_for_post( $post_id ) {

		// Only do this once per Post.
		static $pseudocache;
		if ( isset( $pseudocache[$post_id] ) ) {
			return $pseudocache[$post_id];
		}

		// Init return.
		$acf_fields = [];

		// Get Entity reference.
		$entity = $this->entity_type_get( $post_id );

		// TODO: Make this a filter...

		// Easy if it's a Post.
		if ( $entity === 'post' ) {
			$params = [
				'post_id' => $post_id,
			];
		}

		// If it's a User, we support the Edit Form.
		if ( $entity === 'user' ) {
			//$tmp = explode( '_', $post_id );
			$params = [
				//'user_id' => $tmp[1],
				'user_form' => 'edit',
			];
		}

		// Get all Field Groups for this ACF "Post ID".
		$acf_field_groups = acf_get_field_groups( $params );

		// Build our equivalent array to that returned by `get_fields()`.
		foreach( $acf_field_groups AS $acf_field_group ) {

			// Get all the fields in this Field Group.
			$fields_in_group = acf_get_fields( $acf_field_group );

			// Add their Field "name" to the return.
			foreach( $fields_in_group AS $field_in_group ) {

				// Get the CiviCRM Custom Field and add if it has a reference to a CiviCRM Field.
				$custom_field_id = $this->acf_loader->civicrm->custom_field->custom_field_id_get( $field_in_group );
				if ( ! empty( $custom_field_id ) ) {
					$acf_fields['custom'][$field_in_group['name']] = $custom_field_id;
				}

				// Get the CiviCRM Contact Field and add if it has a reference to a CiviCRM Field.
				$contact_field_name = $this->acf_loader->civicrm->contact->contact_field_name_get( $field_in_group );
				if ( ! empty( $contact_field_name ) ) {
					$acf_fields['contact'][$field_in_group['name']] = $contact_field_name;
				}

				// Get the CiviCRM Activity Field and add if it has a reference to a CiviCRM Field.
				$activity_field_name = $this->acf_loader->civicrm->activity->activity_field_name_get( $field_in_group );
				if ( ! empty( $activity_field_name ) ) {
					$acf_fields['activity'][$field_in_group['name']] = $activity_field_name;
				}

				// Get the CiviCRM Participant Field and add if it has a reference to a CiviCRM Field.
				$participant_field_name = $this->acf_loader->civicrm->participant->participant_field_name_get( $field_in_group );
				if ( ! empty( $participant_field_name ) ) {
					$acf_fields['participant'][$field_in_group['name']] = $participant_field_name;
				}

				/**
				 * Filter the mapped ACF Fields attached to a Post.
				 *
				 * Used internally by:
				 *
				 * - Relationship
				 * - Address
				 * - Google Map
				 * - Email
				 * - Website
				 * - Phone
				 *
				 * @since 0.4
				 *
				 * @param array $acf_fields The existing ACF Fields array.
				 * @param array $field_in_group The ACF Field.
				 * @param integer|string $post_id The ACF "Post ID".
				 * @return array $acf_fields The modified ACF Fields array.
				 */
				$acf_fields = apply_filters( 'cwps/acf/fields_get_for_post', $acf_fields, $field_in_group, $post_id );

			}

		}

		// Maybe add to pseudo-cache.
		if ( ! isset( $pseudocache[$post_id] ) ) {
			$pseudocache[$post_id] = $acf_fields;
		}

		// --<
		return $acf_fields;

	}



	// -------------------------------------------------------------------------



	/**
	 * Update the value of an ACF Field.
	 *
	 * @since 0.4
	 *
	 * @param string $selector The field name or key.
	 * @param mixed $value The value to save in the database.
	 * @param integer|string $post_id The ACF "Post ID".
	 */
	public function value_update( $selector, $value, $post_id ) {

		// Pass through to ACF.
		$success = update_field( $selector, $value, $post_id );

	}



	/**
	 * Validate the content of a Field.
	 *
	 * Unlike in ACF, CiviCRM "Text", "Select" and "Radio" Fields can be of
	 * various kinds. We need to provide validation for the matching data types
	 * here before sync can take place.
	 *
	 * @since 0.4
	 *
	 * @param boolean $valid The existing valid status.
	 * @param mixed $value The value of the Field.
	 * @param array $field The Field data array.
	 * @param string $input The input element's name attribute.
	 * @return string|boolean $valid A string to display a custom error message, boolean otherwise.
	 */
	public function value_validate( $valid, $value, $field, $input ) {

		// Bail if it's not required and is empty.
		if ( $field['required'] == '0' AND empty( $value ) ) {
			return $valid;
		}

		// Get the mapped Custom Field ID if present.
		$custom_field_id = $this->acf_loader->civicrm->custom_field->custom_field_id_get( $field );
		if ( $custom_field_id === false ) {
			return $valid;
		}

		// Get Custom Field data.
		$field_data = $this->acf_loader->civicrm->custom_field->get_by_id( $custom_field_id );
		if ( $field_data === false ) {
			return $valid;
		}

		// Validate depending on the "data_type".
		switch ( $field_data['data_type'] ) {

			case 'String' :

				// CiviCRM string fields are varchar(255) or varchar(260).
				if ( ! empty( $field_data['text_length'] ) ) {
					if ( strlen( $value ) > $field_data['text_length'] ) {
						$valid = sprintf( __( 'Must be maximum %s characters.', 'civicrm-wp-profile-sync' ), $field_data['text_length'] );
					}
				} else {
					if ( strlen( $value ) > 255 ) {
						$valid = __( 'Must be maximum 255 characters.', 'civicrm-wp-profile-sync' );
					}
				}

				break;

			case 'Int' :

				// If it's a Multi-select.
				if ( $field_data['html_type'] == 'Multi-Select' AND is_array( $value ) ) {

					// Make sure values are all integers.
					foreach( $value AS $item ) {
						if ( ! ctype_digit( $item ) ) {
							$valid = __( 'Values must all be integers.', 'civicrm-wp-profile-sync' );
						}
					}

					// CiviCRM integer fields are signed int(11).
					foreach( $value AS $item ) {
						if ( (int) $value > 2147483647 ) {
							$valid = __( 'Values must all be less than 2147483647.', 'civicrm-wp-profile-sync' );
						}
					}

				} else {

					// Value must be an integer.
					if ( ! ctype_digit( $value ) ) {
						$valid = __( 'Must be an integer.', 'civicrm-wp-profile-sync' );
					}

					// CiviCRM integer fields are signed int(11).
					if ( (int) $value > 2147483647 ) {
						$valid = __( 'Must be less than 2147483647.', 'civicrm-wp-profile-sync' );
					}

				}

				break;

			case 'Float' :

				// If it's a Multi-select.
				if ( $field_data['html_type'] == 'Multi-Select' AND is_array( $value ) ) {

					// Make sure values are all numeric.
					foreach( $value AS $item ) {
						if ( ! is_numeric( $item ) ) {
							$valid = __( 'Values must all be numbers.', 'civicrm-wp-profile-sync' );
						}
					}

				} else {

					// Value must be numeric.
					if ( ! is_numeric( $value ) ) {
						$valid = __( 'Must be a number.', 'civicrm-wp-profile-sync' );
					}

				}
				break;

			case 'Money' :

				// If it's a Multi-select.
				if ( $field_data['html_type'] == 'Multi-Select' AND is_array( $value ) ) {

					// Make sure values are all numeric.
					foreach( $value AS $item ) {

						// Must be a number.
						if ( ! is_numeric( $item ) ) {
							$valid = __( 'All values must be a valid money format.', 'civicrm-wp-profile-sync' );
						}

						// Round the number.
						$rounded = round( $item, 2 );

						// Must be not have more than 2 decimal places.
						if ( $rounded != $item ) {
							$valid = __( 'All values must have only two decimal places.', 'civicrm-wp-profile-sync' );
						}

					}

				} else {

					// Must be a number.
					if ( ! is_numeric( $value ) ) {
						$valid = __( 'Must be a valid money format.', 'civicrm-wp-profile-sync' );
					}

					// Round the number.
					$rounded = round( $value, 2 );

					// Must be not have more than 2 decimal places.
					if ( $rounded != $value ) {
						$valid = __( 'Only two decimal places please.', 'civicrm-wp-profile-sync' );
					}

				}

				break;

		}

		// --<
		return $valid;

	}



	// -------------------------------------------------------------------------



	/**
	 * Get the value of an ACF Field formatted for CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The ACF field value.
	 * @param string $type The ACF Field type.
	 * @param array $settings The ACF Field settings.
	 * @param array $args Any additional arguments.
	 * @return mixed $value The field value formatted for CiviCRM.
	 */
	public function value_get_for_civicrm( $value = 0, $type, $settings, $args = [] ) {

		// Set appropriate value per field type.
		switch( $type ) {

	 		// Parse the value of a "True/False" Field.
			case 'true_false' :
				$value = $this->true_false_value_get( $value );
				break;

	 		// Parse the value of an "Image" Field.
			case 'image' :
				$value = $this->image_value_get( $value );
				break;

	 		// Parse the value of a "Date Picker" Field.
			case 'date_picker' :
				$value = $this->date_picker_value_get( $value, $settings );
				break;

	 		// Parse the value of a "Date Time Picker" Field.
			case 'date_time_picker' :
				$value = $this->date_time_picker_value_get( $value, $settings );
				break;

			// Other Field Types may require parsing - add them here.

		}

		// --<
		return $value;

	}



	/**
	 * Get the value of a "True/False" Field formatted for CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param integer|null $value The field value, or empty when "false".
	 * @return string $value The "Yes/No" value expressed as "1" or "0".
	 */
	public function true_false_value_get( $value = '0' ) {

		// Convert 1 to string.
		if ( $value == 1 ) {
			$value = '1';
		}

		// Convert empty value.
		if ( empty( $value ) OR $value === 0 ) {
			$value = '0';
		}

		// --<
		return $value;

	}



	/**
	 * Get the value of an "Image" Field formatted for CiviCRM.
	 *
	 * The only kind of sync that an ACF Image Field can do at the moment is to
	 * sync with the CiviCRM Contact Image. This is a built-in field for Contacts
	 * and consists simply of the URL of the image.
	 *
	 * The ACF Image Field return format can be either 'array', 'url' or 'id' so
	 * we need to extract the original image URL to send to CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param integer|null $value The field value (the Attachment data).
	 * @return string $value The URL of the full size image.
	 */
	public function image_value_get( $value ) {

		// Return empty string when value is empty.
		if ( empty( $value ) ) {
			return '';
		}

		// If it's an array, extract full image URL.
		if ( is_array( $value ) ) {

			// Discard all but the URL.
			if ( ! empty( $value['url'] ) ) {
				$value = $value['url'];
			}

		// When it's numeric, get full image URL from attachment.
		} elseif ( is_numeric( $value ) ) {

			// Grab the the full size Image URL.
			$url = wp_get_attachment_image_url( (int) $value, 'full' );

			// Overwrite with the URL.
			if ( ! empty( $url ) ) {
				$value = $url;
			}

		}

		// When it's a string, it must be the URL.

		// --<
		return $value;

	}



	/**
	 * Get the value of a "Date Picker" Field formatted for CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param string $value The existing field value.
	 * @param array $settings The ACF Field settings.
	 * @return string $value The modified value for CiviCRM.
	 */
	public function date_picker_value_get( $value = '', $settings ) {

		// There are problems with the "d/m/Y" format, so convert.
		if ( false !== strpos( $settings['return_format'], 'd/m/Y' ) ) {
			$value = str_replace( '/', '-', $value );
		}

		// --<
		return $value;

	}



	/**
	 * Get the value of a "Date Time Picker" Field formatted for CiviCRM.
	 *
	 * @since 0.4
	 *
	 * @param string $value The existing field value.
	 * @param array $settings The ACF Field settings.
	 * @return string $value The modified value for CiviCRM.
	 */
	public function date_time_picker_value_get( $value = '', $settings ) {

		// There are problems with the "d/m/Y" format, so convert.
		if ( false !== strpos( $settings['return_format'], 'd/m/Y' ) ) {
			$value = str_replace( '/', '-', $value );
		}

		// --<
		return $value;

	}



	// -------------------------------------------------------------------------



	/**
	 * Add Setting to "Text" Field Settings.
	 *
	 * @since 0.4
	 *
	 * @param array $field The field data array.
	 */
	public function field_setting_add( $field ) {

		// Get the Field Group for this Field.
		$field_group = $this->acf->field_group->get_for_field( $field );

		/**
		 * Request a Setting Field from Entity classes.
		 *
		 * @since 0.5
		 *
		 * @param array The empty default Setting Field array.
		 * @param array $field The ACF Field data array.
		 * @param array $field_group The ACF Field Group data array.
		 */
		$setting = apply_filters( 'cwps/acf/query_settings_field', [], $field, $field_group );

		// Bail if we have no setting.
		if ( empty( $setting ) ) {
			return;
		}

		// Now add it.
		acf_render_field_setting( $field, $setting );

	}



	// -------------------------------------------------------------------------



	/**
	 * Add CSS when "Google Map" Field is loaded.
	 *
	 * @since 0.4
	 *
	 * @param array $field The field data array.
	 */
	public function google_map_styles_add( $field ) {

		// Get Google Map key.
		$key = $this->acf_loader->civicrm->google_map->acf_field_key_get();

		// Bail if it's not a linked field.
		if ( empty( $field[$key] ) ) {
			return;
		}

		// Get the "Make Read Only" key.
		$edit_key = $this->acf_loader->civicrm->google_map->acf_field_key_edit_get();

		// Only skip if it's explicitly *not* set to "Read Only".
		if ( isset( $field[$edit_key] ) AND $field[$edit_key] !== 1 ) {
			return;
		}

		// Hide search bar when "Read Only". Yeah I know it's a hack.
		$style = '<style type="text/css">' .
			'#' . $field['id'] . '.acf-google-map .title { display: none; }' .
		'</style>';

		// Write to page.
		echo $style;

	}



	/**
	 * Maybe modify the value of a "Google Map" Field.
	 *
	 * This merely ensures that we have an array to work with.
	 *
	 * @since 0.4
	 *
	 * @param mixed $value The existing value.
	 * @param integer $post_id The Post ID from which the value was loaded.
	 * @param array $field The field array holding all the field options.
	 * @return mixed $value The modified value.
	 */
	public function google_map_value_modify( $value, $post_id, $field ) {

		// Make sure we have an array.
		if ( empty( $value ) AND ! is_array( $value ) ) {
			$value = [];
		}

		// TODO: Maybe assign Address for this Field if empty.
		if ( empty( $value ) ) {

			// Skip if this Field isn't linked to a CiviCRM Address.
			$key = $this->acf_loader->civicrm->google_map->acf_field_key_get();
			if ( empty( $field[$key] ) ) {
				return $value;
			}

			// Skip if there is no Contact ID for this ACF "Post ID".
			$contact_id = $this->acf->field->query_contact_id( $post_id );
			if ( $contact_id === false ) {
				return $value;
			}

			// Get this Contact's Addresses.
			$addresses = $this->acf_loader->civicrm->address->addresses_get_by_contact_id( $contact_id );

			// Init location.
			$location = false;

			// Does this Field sync with the Primary Address?
			if ( $field[$key] === 'primary' ) {

				// Assign Location from the Primary Address.
				foreach( $addresses AS $address ) {
					if ( ! empty( $address->is_primary ) ) {
						$location = $address;
						break;
					}
				}

			// Does this Field sync with the Billing Address?
			} elseif ( $field[$key] === 'billing' ) {

				// Assign Location from the Primary Address.
				foreach( $addresses AS $address ) {
					if ( ! empty( $address->is_billing ) ) {
						$location = $address;
						break;
					}
				}

			// We need a Location Type.
			} elseif ( is_numeric( $field[$key] ) ) {

				// Assign Location from the type of Address.
				foreach( $addresses AS $address ) {
					if ( $address->location_type_id == $field[$key] ) {
						$location = $address;
						break;
					}
				}

			}

			// Overwrite if we get a value.
			if ( $location !== false ) {
				$value = $this->acf_loader->civicrm->google_map->field_map_prepare( $address );
			}

		}

		// --<
		return $value;

	}



	/**
	 * Maybe modify the Setting of a "Google Map" Field.
	 *
	 * Only the Primary Address can be editable in the ACF Field because it is
	 * the only CiviCRM Address that is guaranteed to be unique. There can be
	 * multiple Addresses with the same Location Type but only one that is the
	 * Primary Address.
	 *
	 * @since 0.4
	 *
	 * @param array $field The existing field data array.
	 * @param array $field_group The array of ACF Field Group data.
	 * @return array $field The modified field data array.
	 */
	public function google_map_setting_modify( $field, $field_group = [] ) {

		// Bail early if not our Field Type.
		if ( 'google_map' !== $field['type'] ) {
			return $field;
		}

		// Bail if it's not a linked field.
		$key = $this->acf_loader->civicrm->google_map->acf_field_key_get();
		if ( empty( $field[$key] ) ) {
			return $field;
		}

		// Get the "Make Read Only" key.
		$edit_key = $this->acf_loader->civicrm->google_map->acf_field_key_edit_get();

		// Always set to default if not set.
		if ( ! isset( $field[$edit_key] ) ) {
			$field[$edit_key] = 1;
		}

		// Always set to true if not a "Primary" Address.
		if ( $field[$key] != 'primary' ) {
			$field[$edit_key] = 1;
		}

		// --<
		return $field;

	}



} // Class ends.



