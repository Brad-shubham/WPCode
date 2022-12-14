<?php


class Data_Reporting_Tools
{
    // limit filtering to only those that are manually implemented for activity
    private static $filter_fields = [ 'tags', 'sources', 'type' ];
    private static $supported_filters = [
        'sort' => true,
        'limit' => true,
        'tags' => true,
        'sources' => true,
        'type' => true,
        'last_modified' => true,
        'date' => true,
    ];

    public static $data_types = [
        'contacts' => 'Contacts',
        'contact_activity' => 'Contact Activity',
        'groups' => 'Groups',
        'group_activity' => 'Group Activity',
    ];


    private static $excluded_fields = array(
        'contacts' => array( 'name', 'nickname', 'tasks', 'facebook_data' ),
        'groups' => array( 'name' ),
    );
    private static $included_hidden_fields = array(
        'contacts' => array( 'accepted', 'source_details', 'type' ),
        'groups' => array( 'type' ),
    );

    /**
     * Fetch data by type
     * @param $data_type contacts|contact_activity|groups|group_activity
     * @param $config_key
     * @param bool $flatten
     * @param null $limit
     * @return array Columns, rows, and total count
     */
    public static function get_data( $data_type, $config_key, $flatten = false, $limit = null ) {
        $config = self::get_config_by_key( $config_key );
        $config_progress = self::get_config_progress_by_key( $config_key );

        // Get the settings for this data type from the config
        $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
        $type_config = isset( $type_configs[$data_type] ) ? $type_configs[$data_type] : [];
        $last_exported_value = isset( $config_progress[$data_type] ) ? $config_progress[$data_type] : null;
        $all_data = !isset( $type_config['all_data'] ) || boolval( $type_config['all_data'] );
        // Use limit from config only if all_data is false
        $limit = $limit ?? ( !$all_data && isset( $type_config['limit'] ) ? intval( $type_config['limit'] ) : 100 );
        

        //$last_modified_date = date("Y-m-d", strtotime( date("-1 Days") ) );

        $result = null;
        switch ($data_type) {
            case 'group_activity':
                $filter = $config && isset( $config['groups_filter'] ) ? $config['groups_filter'] : null;

                if ( $limit ) {
                    $filter['limit'] = $limit;
                }
                // If not exporting everything, add limit and filter for last value
                if ( !$all_data && !empty( $last_exported_value ) ) {
                    $filter['last_modified'] = [
                        'start' => $last_exported_value,
                    ];
                }

                // $filter['last_modified'] = [
                //     'start' => $last_modified_date,
                // ];
                // Fetch the data
                $result = self::get_group_activity( false, $filter );
            break;
            case 'groups':
                $filter = $config && isset( $config['groups_filter'] ) ? $config['groups_filter'] : null;

                if ( $limit ) {
                    $filter['limit'] = $limit;
                }
                // If not exporting everything, add limit and filter for last value
                if ( !$all_data && !empty( $last_exported_value ) ) {
                    $filter['last_modified'] = [
                        'start' => $last_exported_value,
                    ];
                }

                // $filter['last_modified'] = [
                //     'start' => $last_modified_date,
                // ];
                // Fetch the data
                $result = self::get_groups( $flatten, $filter );
            break;
            case 'contact_activity':
                $filter = $config && isset( $config['contacts_filter'] ) ? $config['contacts_filter'] : array();
                if ( $limit ) {
                    $filter['limit'] = $limit;
                }
                // If not exporting everything, add limit and filter for last value
                if ( !$all_data && !empty( $last_exported_value ) ) {
                    $filter['date'] = [
                        'start' => $last_exported_value,
                    ];
                }
                // $filter['date'] = [
                //     'start' => $last_modified_date,
                // ];
                $result = self::get_contact_activity( false, $filter );
            break;
            case 'contacts':
            default:
                $filter = $config && isset( $config['contacts_filter'] ) ? $config['contacts_filter'] : null;

                if ( $limit ) {
                    $filter['limit'] = $limit;
                }
                // If not exporting everything, add limit and filter for last value
                if ( !$all_data && !empty( $last_exported_value ) ) {
                    $filter['last_modified'] = [
                        'start' => $last_exported_value,
                    ];
                }

                // $filter['last_modified'] = [
                //     'start' => $last_modified_date,
                // ];
                // Fetch the data
                $result = self::get_contacts( $flatten, $filter );
        }

        return $result;
    }

    /**
     * Fetch contacts
     * @param bool $flatten
     * @param null $filter
     * @return array Columns, rows, and total count
     */
    public static function get_contacts( $flatten = false, $filter = null ) {
        $is_1_0 = version_compare( wp_get_theme()->version, '1.0.0', '>=' );

        try {
            $contacts = self::get_posts( 'contacts', $filter );
        } catch ( Exception $ex ) {
            write_log( "Error fetching contacts: {$ex->getMessage()}" );
            return array( null, null, 0 );
        }

        // Build contact generations
        // taken from [dt-theme]/dt-metrics/counters/counter-baptism.php::save_all_contact_generations
        $raw_baptism_generation_list = Counter_Baptism::query_get_all_baptism_connections();
        $all_baptisms = Counter_Baptism::build_baptism_generation_counts( $raw_baptism_generation_list );
        $contact_generations = array();
        foreach ( $all_baptisms as $baptism_generation ){
            $generation = $baptism_generation["generation"];
            $baptisms = $baptism_generation["ids"];
            foreach ( $baptisms as $contact ){
                $contact_generations[$contact] = $generation;
            }
        }

        $items = array();

        $post_settings = apply_filters( "get_post_type_settings", array(), 'contacts' );
        $fields = $post_settings["fields"];
        $base_url = self::get_current_site_base_url();
        $locations = self::get_location_data( $contacts['posts'] );

        foreach ($contacts['posts'] as $index => $result) {
            $contact = array(
                'ID' => $result['ID'],
                'Created' => $result['post_date'],
            );

            // Theme v1.0.0 changes post_date to a proper date object we need to format
            if ( $is_1_0 && isset( $result['post_date']['timestamp'] ) ) {
                $contact['Created'] = !empty( $result['post_date']["timestamp"] ) ? gmdate( "Y-m-d H:i:s", $result['post_date']['timestamp'] ) : "";
            }

            // Loop over all fields to parse/format each
            foreach ( $fields as $field_key => $field ){
                // skip if field is hidden, unless marked as exception above
                if ( isset( $field['hidden'] ) && $field['hidden'] == true && !in_array( $field_key, self::$included_hidden_fields['contacts'] ) ) {
                    continue;
                }
                // skip if in list of excluded fields
                if ( in_array( $field_key, self::$excluded_fields['contacts'] ) ) {
                    continue;
                }

                $type = $field['type'];

                // skip communication_channel fields since they are all PII
                if ( $type == 'communication_channel' ) {
                    continue;
                }

                $field_value = self::get_field_value( $result, $field_key, $type, $flatten, $locations );

                // if we calculated the baptism generation, set it here
                if ( $field_key == 'baptism_generation' && isset( $contact_generations[$result['ID']] ) ) {
                    if ( $fields[$field_key]['type'] === 'number' ) {
                        $generation = $contact_generations[$result['ID']];
                        $field_value = empty( $generation ) ? '' : intval( $generation );
                    } else {
                        $field_value = $contact_generations[$result['ID']];
                    }
                }

                $field_value = apply_filters( 'data_reporting_field_output', $field_value, $type, $field_key, $flatten );
                $contact[$field_key] = $field_value;
            }
            $contact['site'] = $base_url;

            $items[] = $contact;
        }
        $columns = self::build_columns( $fields, 'contacts' );
        return array( $columns, $items, $contacts['total'] );
    }

    /**
     * Fetch contact activity
     * @param bool $flatten
     * @param null $filter
     * @return array Columns, rows, and total count
     */
    public static function get_contact_activity( $flatten = false, $filter = null ) {
        $filter = $filter ? array_intersect_key( $filter, self::$supported_filters ) : array();

        $activities = self::get_post_activity( 'contacts', $filter );
        write_log( sizeof( $activities['activity'] ) . ' of ' . $activities['total'] );
        $items = array();

        $base_url = self::get_current_site_base_url();

        foreach ($activities['activity'] as $index => $result) {
            $activity = $result;
            $activity['site'] = $base_url;

            $items[] = $activity;
        }

        $columns = array(
            array(
                'key' => "id",
                'name' => "ID",
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "meta_id",
                'name' => 'Meta ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "post_id",
                'name' => 'Contact ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "user_id",
                'name' => 'User ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "user_name",
                'name' => 'User',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_type",
                'name' => 'Action Type',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_field",
                'name' => 'Action Field',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value",
                'name' => 'Action Value',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value_friendly",
                'name' => 'Action Value (Friendly)',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value_order",
                'name' => 'Action Value Order',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_old_value",
                'name' => 'Action Old Value',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "note",
                'name' => 'Note',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "date",
                'name' => 'Date',
                'type' => 'date',
                'bq_type' => 'TIMESTAMP',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => 'site',
                'name' => 'Site',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
        );

        return array( $columns, $items, $activities['total'] );
    }

    /**
     * Fetch groups
     * @param bool $flatten
     * @param null $filter
     * @return array Columns, rows, and total count
     */
    public static function get_groups( $flatten = false, $filter = null ) {
        $is_1_0 = version_compare( wp_get_theme()->version, '1.0.0', '>=' );

        try {
            $groups = self::get_posts( 'groups', $filter );
        } catch ( Exception $ex ) {
            write_log( "Error fetching groups: {$ex->getMessage()}" );
            return array( null, null, 0 );
        }

        $items = array();

        $post_settings = apply_filters( "get_post_type_settings", array(), 'groups' );
        $fields = $post_settings["fields"];
        $base_url = self::get_current_site_base_url();
        $locations = self::get_location_data( $groups['posts'] );

        write_log( __METHOD__ );
        write_log( json_encode($groups) );

        foreach ($groups['posts'] as $index => $result) {
            $group = array(
                'ID' => $result['ID'],
                'Created' => $result['post_date'],
            );

            // Theme v1.0.0 changes post_date to a proper date object we need to format
            if ( $is_1_0 && isset( $result['post_date']['timestamp'] ) ) {
                $group['Created'] = !empty( $result['post_date']["timestamp"] ) ? gmdate( "Y-m-d H:i:s", $result['post_date']['timestamp'] ) : "";
            }

            // Loop over all fields to parse/format each
            foreach ( $fields as $field_key => $field ){
                // skip if field is hidden, unless marked as exception above
                if ( isset( $field['hidden'] ) && $field['hidden'] == true && !in_array( $field_key, self::$included_hidden_fields['groups'] ) ) {
                    continue;
                }
                // skip if in list of excluded fields
                if ( in_array( $field_key, self::$excluded_fields['groups'] ) ) {
                    continue;
                }

                $type = $field['type'];

                // skip communication_channel fields since they are all PII
                if ( $type == 'communication_channel' ) {
                    continue;
                }

                $field_value = self::get_field_value( $result, $field_key, $type, $flatten, $locations );

                $field_value = apply_filters( 'data_reporting_field_output', $field_value, $type, $field_key, $flatten );
                $group[$field_key] = $field_value;
            }
            $group['site'] = $base_url;

            $items[] = $group;
        }
        $columns = self::build_columns( $fields, 'groups' );
        return array( $columns, $items, $groups['total'] );
    }

    /**
     * Fetch group activity
     * @param bool $flatten
     * @param null $filter
     * @return array Columns, rows, and total count
     */
    public static function get_group_activity( $flatten = false, $filter = null ) {
        $filter = $filter ? array_intersect_key( $filter, self::$supported_filters ) : array();

        $activities = self::get_post_activity( 'groups', $filter );
        write_log( sizeof( $activities['activity'] ) . ' of ' . $activities['total'] );
        $items = array();

        $base_url = self::get_current_site_base_url();

        foreach ($activities['activity'] as $index => $result) {
            $activity = $result;
            $activity['site'] = $base_url;

            $items[] = $activity;
        }

        $columns = array(
            array(
                'key' => "id",
                'name' => "ID",
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "meta_id",
                'name' => 'Meta ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "post_id",
                'name' => 'Group ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "user_id",
                'name' => 'User ID',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "user_name",
                'name' => 'User',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_type",
                'name' => 'Action Type',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_field",
                'name' => 'Action Field',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value",
                'name' => 'Action Value',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value_friendly",
                'name' => 'Action Value (Friendly)',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_value_order",
                'name' => 'Action Value Order',
                'type' => 'number',
                'bq_type' => 'INTEGER',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "action_old_value",
                'name' => 'Action -Old Value',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "note",
                'name' => 'Note',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => "date",
                'name' => 'Date',
                'type' => 'date',
                'bq_type' => 'TIMESTAMP',
                'bq_mode' => 'NULLABLE',
            ),
            array(
                'key' => 'site',
                'name' => 'Site',
                'type' => 'string',
                'bq_type' => 'STRING',
                'bq_mode' => 'NULLABLE',
            ),
        );

        return array( $columns, $items, $activities['total'] );
    }

    /**
     * Fetch post data by type
     * @param $post_type
     * @param null $filter
     * @return array|WP_Error
     * @throws Exception If Posts::list_posts throws an error.
     */
    private static function get_posts( $post_type, $filter = null ) {
        // limit filtering to only those that are manually implemented for activity
        $filter = $filter ? array_intersect_key( $filter, self::$supported_filters ) : array();

        // By default, sort by last updated date
        if ( !isset( $filter['sort'] ) ) {
            $filter['sort'] = 'last_modified';
        }

        $posts = Posts::list_posts( $post_type, $filter );
        if ( is_wp_error( $posts ) ) {
            $error_message = $posts->get_error_message() ?? '';
            throw new Exception( $error_message );
        }

        write_log( sizeof( $posts['posts'] ) . ' of ' . $posts['total'] );
        if ( !isset( $filter['limit'] ) ) {
            // if total is greater than length, recursively get more
            $retrieved_posts = sizeof( $posts['posts'] );
            while ($retrieved_posts < $posts['total']) {
                $filter['offset'] = sizeof( $posts['posts'] );
                $next_posts = Posts::list_posts( $post_type, $filter );
                $posts['posts'] = array_merge( $posts['posts'], $next_posts['posts'] );
                write_log( 'adding ' . sizeof( $next_posts['posts'] ) );
                $retrieved_posts = sizeof( $posts['posts'] );
                write_log( $retrieved_posts . ' of ' . $posts['total'] );
            }
        }

        return $posts;
    }


    private static function get_label( $result, $key ) {
        return ( array_key_exists( $key, $result ) && is_array( $result[$key] ) && array_key_exists( 'label', $result[$key] ) ) ? $result[$key]['label'] : '';
    }

    /**
     * Fetch location grid data to get country code and admin level 1 for each
     * so we can filter out any more detailed data
     * @param $posts
     * @return mixed
     */
    private static function get_location_data( $posts ) {
        global $wpdb;

        // get all of the location IDs from each post's location_grid field
        $grid_ids = array_reduce( $posts, function ( $ids, $post ) {
            if ( isset( $post['location_grid'] ) ) {
                $location_ids = array_map(function ( $location) {
                    return $location['id'];
                }, $post['location_grid']);
                $ids = array_merge( $ids, $location_ids );
            }
            return $ids;
        }, []);

        // return empty if no posts have location data
        if ( count( $grid_ids ) == 0 ) {
            return array();
        }

        // Query to get country_code and admin1 name for each location
        $locations = $wpdb->get_results( $wpdb->prepare("
            select orig.grid_id, orig.country_code, a1.name
            from $wpdb->location_grid orig
            left join $wpdb->location_grid a1 on orig.admin1_grid_id=a1.grid_id
            where orig.grid_id in (" .
            implode( ',', array_fill( 0, count( $grid_ids ), '%d' ) ) .
            ")",
            $grid_ids
        ), ARRAY_A );

        // index results by grid_id for easy access without searching
        return array_reduce( $locations, function ( $map, $location ) {
            $map[$location['grid_id']] = $location;
            return $map;
        }, []);
    }

    /**
     * Get field value from result, taking in to account the field type
     * @param $result
     * @param $field_key
     * @param $type
     * @param $flatten
     * @return array|false|int|mixed|string
     */
    private static function get_field_value( $result, $field_key, $type, $flatten, $locations ) {
        if (key_exists( $field_key, $result )) {
            switch ($type) {
                case 'key_select':
                    $field_value = self::get_label( $result, $field_key );
                    break;
                case 'multi_select':
                case 'tags':
                    $field_value = $flatten ? implode( ",", $result[$field_key] ) : $result[$field_key];
                    break;
                case 'user_select':
                    $field_value = $result[$field_key]['id'];
                    break;
                case 'date':
                    $field_value = !empty( $result[$field_key]["timestamp"] ) ? gmdate( "Y-m-d H:i:s", $result[$field_key]['timestamp'] ) : "";
                    break;
                case 'location':
                    // Map country and admin1 data from location_grid table to restrict
                    // location to only admin level 1 (first level within a country, like states/provinces)
                    $location_names = array_map( function ( $location ) use ( $locations ) {
                        if ( isset( $locations[$location['id']] ) ) {
                            $grid_loc = $locations[$location['id']];
                            // Try to return "{2-letter-country-code}-{admin1-name}"
                            if ( !empty( $grid_loc['name'] ) ) {
                                return $grid_loc['country_code'] . "-" . $grid_loc['name'];
                            }
                            // fall back to just country code
                            return $grid_loc['country_code'];
                        }
                        // if no grid data, return null for safety of not exposing PII
                        return null;
                    }, $result[$field_key] );

                    // Remove null and duplicates
                    $location_names = array_unique( array_filter( $location_names ) );

                    $field_value = $flatten ? implode( ",", $location_names ) : $location_names;
                    break;
                case 'connection':
                    $connection_ids = array_map( function ( $connection ) { return $connection['ID'];
                    }, $result[$field_key] );
                    $field_value = $flatten ? implode( ",", $connection_ids ) : $connection_ids;
                    break;
                case 'number':
                    $field_value = empty( $result[$field_key] ) ? '' : intval( $result[$field_key] );
                    break;
                default:
                    $field_value = $result[$field_key];
                    if ( is_array( $field_value ) ) {
                        $field_value = json_encode( $field_value );
                    }
                    break;
            }
        } else {
            // Set default/blank value
            switch ($type) {
                case 'number':
                    $field_value = $field['default'] ?? 0;
                    break;
                case 'key_select':
                    $field_value = null;
                    break;
                case 'multi_select':
                case 'tags':
                    $field_value = $flatten ? null : array();
                    break;
                case 'array':
                case 'boolean':
                case 'date':
                case 'text':
                case 'location':
                default:
                    $field_value = $field['default'] ?? null;
                    break;
            }
        }

        return $field_value;
    }

    protected static function get_current_site_base_url() {
        $url = str_replace( 'http://', '', home_url() );
        $url = str_replace( 'https://', '', $url );

        return trim( $url );
    }

    /**
     * Get all configurations
     * @return array
     */
    public static function get_configs() {
        $configurations_str = get_option( "data_reporting_configurations" );
        $configurations_int = json_decode( $configurations_str, true );
        $configurations_ext = apply_filters( 'data_reporting_configurations', array() );

      // Merge locally-created and external configurations
        $configurations = array_merge( $configurations_int ?? [], $configurations_ext );

      // Filter out disabled configurations
        $configurations = array_filter($configurations, function ( $config) {
            return isset( $config['active'] ) && $config['active'] == 1;
        });
        return $configurations;
    }

    /**
     * Get configuration by key
     * @param $config_key
     * @return mixed|null configuration
     */
    public static function get_config_by_key( $config_key ) {
        $configurations = self::get_configs();

        if ( isset( $configurations[$config_key] ) ) {
            return $configurations[$config_key];
        }

        return null;
    }

  /**
   * Get the last exported values for the given config.
   * [
   *   'config-key-1' => [
   *     'contacts' => 'last-value-exported',
   *     'contact_activity' => 'last-value-exported',
   *     'groups' => 'last-value-exported',
   *     'group_activity' => 'last-value-exported',
   *   ]
   * ]
   * @param $config_key
   * @return |null
   */
    public static function get_config_progress_by_key( $config_key ) {
        $configurations_str = get_option( "data_reporting_configurations_progress" );
        $configurations = json_decode( $configurations_str, true );

        if ( isset( $configurations[$config_key] ) ) {
            return $configurations[$config_key];
        }

        return [];
    }

  /**
   * Set last exported values for the given config
   * @param $config_key
   * @param $config_progress
   */
    public static function set_config_progress_by_key( $config_key, $config_progress ) {
        $configurations_str = get_option( "data_reporting_configurations_progress" );
        $configurations = json_decode( $configurations_str, true );

        $configurations[$config_key] = $config_progress;

        update_option( "data_reporting_configurations_progress", json_encode( $configurations ) );
    }

  /**
   * Set the last export value for a given data type in the given config
   * @param $data_type
   * @param $config_key
   * @param $item
   */
    public static function set_last_exported_value( $data_type, $config_key, $item ) {
        $value = null;

      // Which field do we use to determine last exported for each type
        switch ($data_type) {
            case 'group_activity':
                $value = $item['date'];
                break;
            case 'groups':
                $value = $item['last_modified'];
            break;
            case 'contact_activity':
                $value = $item['date'];
                break;
            case 'contacts':
            default:
                $value = $item['last_modified'];
            break;
        }

      // If value is not empty, save it
        if ( !empty( $value ) ) {
            $config_progress = self::get_config_progress_by_key( $config_key );
            $config_progress[$data_type] = $value;
            self::set_config_progress_by_key( $config_key, $config_progress );
        }
    }

    /**
     * Store last export results in option in case of issue to debug
     * @param $data_type - contacts, contact_activity, groups, group_activity, etc.
     * @param $config_key
     * @param $results
     */
    public static function store_export_logs( $data_type, $config_key, $results ) {
        $export_logs_str = get_option( "data_reporting_export_logs" );
        $export_logs = json_decode( $export_logs_str, true );

        if ( !isset( $export_logs[$config_key] ) ) {
            $export_logs[$config_key] = array();
        }
        if ( !isset( $export_logs[$config_key][$data_type] ) ) {
            $export_logs[$config_key][$data_type] = array();
        }
        $export_logs[$config_key][$data_type] = $results;

        update_option( "data_reporting_export_logs", json_encode( $export_logs ) );
    }

    /**
     * Send data to provider
     * @param $columns
     * @param $rows
     * @param $type
     * @param $config
     * @return array|void|WP_Error Object with success and messages keys
     */
    public static function send_data_to_provider( $columns, $rows, $type, $config ) {
        $provider = isset( $config['provider'] ) ? $config['provider'] : 'api';

        if ($provider == 'api') {
            // return list of log messages (with type: error, success)
            $export_result = [
                'success' => false,
                'messages' => array(),
            ];
            // Get the settings for this data type from the config
            $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];
            $type_config = isset( $type_configs[$type] ) ? $type_configs[$type] : [];
            $all_data = !isset( $type_config['all_data'] ) || boolval( $type_config['all_data'] );

            $args = array(
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json; charset=utf-8'
                ),
                'body' => json_encode(array(
                    'columns' => $columns,
                    'items' => $rows,
                    'type' => $type,
                    'truncate' => $all_data
                )),
            );

            // Add auth token if it is part of the config
            if (isset( $config['token'] )) {
                $args['headers']['Authorization'] = 'Bearer ' . $config['token'];
            }

            // POST the data to the endpoint
            $result = wp_remote_post( $config['url'], $args );

            if (is_wp_error( $result )) {
                // Handle endpoint error
                $error_message = $result->get_error_message() ?? '';
                write_log( $error_message );
                $export_result['messages'][] = [
                    'type' => 'error',
                    'message' => "Error: $error_message",
                ];
            } else {
                // Success
                $status_code = wp_remote_retrieve_response_code( $result );
                $export_result['success'] = true;
                if ($status_code !== 200) {
                    $export_result['messages'][] = [
                        'type' => 'error',
                        'message' => "Error: Status Code $status_code",
                    ];
                } else {
                    $export_result['messages'][] = [
                        'type' => 'success',
                        'message' => "Success",
                    ];
                }
                // $result_body = json_decode($result['body']);
                $export_result['messages'][] = [
                    'message' => $result['body'],
                ];

                activity_insert([
                    'action' => 'export',
                    'object_type' => $type,
                    'object_subtype' => 'non-pii',
                    'meta_key' => 'provider',
                    'meta_value' => $provider,
                    'object_note' => 'disciple-tools-data-reporting'
                ]);
            }
            return $export_result;
        } else {
            // fallback for using action with no return value. Filter is preferred to return success and log messages
            do_action( "data_reporting_export_provider_$provider", $columns, $rows, $type, $config );

            // send data to provider to process and return success indicator and any log messages
            $provider_result = apply_filters( "data_reporting_export_provider_$provider", $columns, $rows, $type, $config );
            // write_log( 'provider_result: ' . json_encode( $provider_result ) );

            activity_insert([
                'action' => 'export',
                'object_type' => $type,
                'object_subtype' => 'non-pii',
                'meta_key' => 'provider',
                'meta_value' => $provider,
                'object_note' => 'disciple-tools-data-reporting'
            ]);

            if ( is_bool( $provider_result ) ) {
                return [
                    'success' => $provider_result,
                ];
            }
            return $provider_result;
        }
    }

    /**
     * Run export to fetch data, send to provider, and log results
     * @param $config_key
     * @param $config
     * @param $type
     * @param $provider_details
     * @return array|void|WP_Error
     */
    public static function run_export( $config_key, $config, $type, $provider_details ) {
        $provider = isset( $config['provider'] ) ? $config['provider'] : 'api';
        $flatten = false;
        $log_messages = array();
        if ( $provider == 'api' && empty( $config['url'] ) ) {
            $log_messages[] = [ 'message' => 'Configuration is missing endpoint URL' ];
        }
        if ( $provider != 'api' ) {
            if ( !empty( $provider_details ) && isset( $provider_details['flatten'] ) ) {
                $flatten = boolval( $provider_details['flatten'] );
            }
        }
        $log_messages[] = [ 'message' => 'Exporting to ' . $config['name'] ];

        // Run export based on the type of data requested
        $log_messages[] = [ 'message' => 'Fetching data...' ];
        [ $columns, $rows, $total ] = self::get_data( $type, $config_key, $flatten );
        $log_messages[] = [ 'message' => 'Exporting ' . count( $rows ) . ' items from a total of ' . $total . '.' ];
        $log_messages[] = [ 'message' => 'Sending data to provider...' ];

        // Send data to provider
        $export_result = self::send_data_to_provider( $columns, $rows, $type, $config );
        // write_log( json_encode( $export_result ) );

        // Merge log messages from above and from provider
        $export_result['messages'] = array_merge( $log_messages, isset( $export_result['messages'] ) ? $export_result['messages'] : [] );

        // If provider was successful, store the last value exported
        $success = isset( $export_result['success'] ) ? $export_result['success'] : boolval( $export_result );
        if ( $success && !empty( $rows ) ) {
            $last_item = array_slice( $rows, -1 )[0];
            self::set_last_exported_value( $type, $config_key, $last_item );
        }

        // Store the result of this export for debugging later
        self::store_export_logs( $type, $config_key, $export_result );

        return $export_result;
    }

    /**
     * Run all exports that are configured to be run automatically
     */
    public static function run_scheduled_exports() {

        $configurations = self::get_configs();
        $providers = apply_filters( 'data_reporting_providers', array() );

        // loop over configurations
        foreach ($configurations as $config_key => $config) {

            $provider = isset( $config['provider'] ) ? $config['provider'] : 'api';
            $provider_details = $provider != 'api' ? $providers[$provider] : array();
            $type_configs = isset( $config['data_types'] ) ? $config['data_types'] : [];

            // loop over each data type in each config
            foreach (array_keys( self::$data_types ) as $data_type) {

                $schedule = isset( $type_configs[$data_type] ) && isset( $type_configs[$data_type]['schedule'] ) ? $type_configs[$data_type]['schedule'] : '';
                // if scheduled export enabled, run export (get data, send to provider)
                if ( $schedule == 'daily') {
                    self::run_export( $config_key, $config, $data_type, $provider_details );
                }
            }
        }
    }

    /**
     * @param $fields
     * @return array
     */
    private static function build_columns( $fields, $type ): array
    {
        $columns = array();
        array_push($columns, array(
            'key' => "id",
            'name' => "ID",
            'type' => 'number',
            'bq_type' => 'INTEGER',
            'bq_mode' => 'NULLABLE',
            ), array(
            'key' => "created",
            'name' => "Created",
            'type' => 'date',
            'bq_type' => 'TIMESTAMP',
            'bq_mode' => 'NULLABLE',
        ));

        foreach ($fields as $field_key => $field) {
            // skip if field is hidden
            if (isset( $field['hidden'] ) && $field['hidden'] == true && !in_array( $field_key, self::$included_hidden_fields[$type] )) {
                continue;
            }
            // skip if in list of excluded fields
            if (in_array( $field_key, self::$excluded_fields[$type] )) {
                continue;
            }

            // skip communication_channel fields since they are all PII
            if ($field['type'] == 'communication_channel') {
                continue;
            }

            $column = array(
                'key' => $field_key,
                'name' => $field['name'],
                'type' => $field['type'],
            );
            switch ($field['type']) {
                case 'array':
                case 'location':
                case 'multi_select':
                case 'tags':
                    $column['bq_type'] = 'STRING';
                    $column['bq_mode'] = 'REPEATED';
                    break;
                case 'connection':
                case 'user_select':
                    $column['bq_type'] = 'INTEGER';
                    $column['bq_mode'] = 'REPEATED';
                    break;
                case 'date':
                    $column['bq_type'] = 'TIMESTAMP';
                    $column['bq_mode'] = 'NULLABLE';
                    break;
                case 'number':
                    $column['bq_type'] = 'INTEGER';
                    $column['bq_mode'] = 'NULLABLE';
                    break;
                case 'boolean':
                    $column['bq_type'] = 'BOOLEAN';
                    $column['bq_mode'] = 'NULLABLE';
                    break;
                case 'key_select':
                case 'text':
                default:
                    $column['bq_type'] = 'STRING';
                    $column['bq_mode'] = 'NULLABLE';
                    break;
            }
            if ($field_key == 'last_modified') {
                $column['type'] = 'date';
                $column['bq_type'] = 'TIMESTAMP';
                $column['bq_mode'] = 'NULLABLE';

            }
            array_push( $columns, $column );
        }
        array_push($columns, array(
            'key' => 'site',
            'name' => 'Site',
            'type' => 'text',
            'bq_type' => 'STRING',
            'bq_mode' => 'NULLABLE',
        ));
        return $columns;
    }
}
