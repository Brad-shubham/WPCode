<?php
require_once(ABSPATH . 'wp-admin/includes/image.php');
// Constants
define("CUST_TOKEN", "21EC2020-3AEA-1069-A2DD-08002B30309D");
define("POST_TYPE", "ltobits");

add_action( 'rest_api_init', function() {
    register_rest_route( 'api', '/external-obit/', array(
        'methods' => 'POST',
        'callback' => 'submit_post_obituaries_data'
    ) );
    register_rest_route( 'api', '/external-obit/(?P<id>\d+)', array(
        'methods' => 'PUT',
        'callback' => 'update_post_obituaries_data'
    ) );
} );

//to insert data in wp_obit_data
function submit_post_obituaries_data(WP_REST_Request $requests){
    $parameters = $requests->get_params();
    if(CUST_TOKEN != $parameters['customer_token']){
        return new WP_Error( 'rest_no_event_date', __( 'Authentication credentials are not provided.' ), array( 'status' => 401 ) );
    }
    $empty_arr = array();
    foreach ($parameters as $key => $value) {
        if ($key == 'prefix' || $key == 'suffix' || $key == 'middle' || $key == 'gender' ) {
            continue;
        }
        if(is_array($value)) {
            foreach ($value as $key1 => $value1) {
                if ($key1 == 'prefix' || $key1 == 'suffix' || $key1 == 'middle' || $key1 == 'gender' ) {
                    continue;
                }
                if (is_array($value1)) {
                    foreach ($value1 as $key2 => $value2) {
                        if ($key2 == 'prefix' || $key2 == 'suffix' || $key2 == 'middle' || $key2 == 'gender' ) {
                            continue;
                        }
                        if(empty($value2))
                            array_push($empty_arr, $key2);
                    }
                }else{
                    if(empty($value1))
                        array_push($empty_arr, $key1);
                }
            }
        } else {
            if(empty($value))
                array_push($empty_arr, $key1);
        }
    }
    if(count($empty_arr) > 0) {
        $error_str = implode(', ', $empty_arr);
        return new WP_Error( 'bad_request', __( 'Your request is missing parameters. Please verify and resubmit.' ), array( 'status' => 400, 'missing_parameters' => $empty_arr ) );
    }

    $first_name = $parameters['obituary']['obit_name']['first'];
    $middle_name = $parameters['obituary']['obit_name']['middle'];
    $last_name =  $parameters['obituary']['obit_name']['last'];

    $name = $first_name ." ". $middle_name." ".$last_name;

    $arrCheckServiceAdded = array();
    $arrCheckServiceLocationAdded = array();
    $arrCheckCharityAdded = array();
    global $added;
    $service_flag = false;
    $service_location_flag = false;
    $charity_flag = false;

    $option_service_type = get_field('option_service_type','options');
    $option_service_location = get_field('option_service_location','options');
    $option_charity = get_field('option_charity','options');

    if (!empty($parameters['obituary']['obit_name'])) {
        foreach ($parameters['obituary'] as $key => $value) {
            if ($key == 'obit_service') {
                $tot_sevices = count($value);
                if($tot_sevices > 0) {
                    foreach ($value as $keyCheckService2 => $valueCheckService2) {
                        foreach ($valueCheckService2 as $keyCheckService1 => $valueCheckService) {
                            if ($keyCheckService1 == 'type') {
                                foreach ($option_service_type as $option_service_type_single) {
                                    if ($option_service_type_single['name'] == $valueCheckService) {
                                        $service_flag = true;
                                    }
                                }
                                if (!$service_flag) {
                                    $added = add_row('option_service_type', array('name' => $valueCheckService), 'options');
                                    array_push($arrCheckServiceAdded, '1');
                                    $service_flag = false;
                                }
                            }
                            if ($keyCheckService1 == 'location') {
                                if (is_array($valueCheckService) && array_key_exists('name', $valueCheckService)) {
                                    foreach ($option_service_location as $option_service_location_single) {
                                        if ($option_service_location_single['name'] == $valueCheckService['name']) {
                                            $service_location_flag = true;
                                        }
                                    }
                                    if (!$service_location_flag) {
                                        $add_service_location_fields = array(
                                            'name' => $valueCheckService['name'] ? $valueCheckService['name'] : '',
                                            'address' => $valueCheckService['address'] ? $valueCheckService['address'] : '',
                                            'city' => $valueCheckService['city'] ? $valueCheckService['city'] : '',
                                            'state' => $valueCheckService['state'] ? $valueCheckService['state'] : '',
                                            'zipcode' => $valueCheckService['zip'] ? $valueCheckService['zip'] : '',
                                            'phone' => $valueCheckService['tel'] ? $valueCheckService['tel'] : '',
                                            'website' => $valueCheckService['website'] ? $valueCheckService['website'] : '',
                                        );
                                        add_row('option_service_location', $add_service_location_fields, 'options');
                                        array_push($arrCheckServiceLocationAdded, '1');
                                        $service_flag = false;
                                    }
                                }
                                else{
                                    foreach ($option_service_location as $option_service_location_single) {
                                        if ($option_service_location_single['name'] == $valueCheckService) {
                                            $service_location_flag = true;
                                        }
                                    }
                                    if (!$service_location_flag) {
                                        $add_service_location_fields = array(
                                            'name' => $valueCheckService ? $valueCheckService : '',
                                            'address' => '',
                                            'city' => '',
                                            'state' => '',
                                            'zipcode' => '',
                                            'phone' => '',
                                            'website' => '',
                                        );
                                        add_row('option_service_location', $add_service_location_fields, 'options');
                                        array_push($arrCheckServiceLocationAdded, '1');
                                        $service_flag = false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($key == 'obit_charities') {
                if(count($value) > 0) {
                    foreach ($value as $keyCheckCharity1 => $valueCheckCharity) {
                        if (is_array($valueCheckCharity) && array_key_exists('name', $valueCheckCharity)) {
                            foreach ($option_charity as $option_charity_single) {
                                if ($option_charity_single['name'] == $valueCheckCharity['name']) {
                                    $charity_flag = true;
                                }
                            }
                            if (!$charity_flag) {
                                $add_charity_fields = array(
                                    'name' => $valueCheckCharity['name'] ? $valueCheckCharity['name'] : '',
                                    'address'   => $valueCheckCharity['address'] ? $valueCheckCharity['address'] : '',
                                    'city'  => $valueCheckCharity['city'] ? $valueCheckCharity['city'] : '',
                                    'state' => $valueCheckCharity['state'] ? $valueCheckCharity['state'] : '',
                                    'zipcode' =>  $valueCheckCharity['zip'] ? $valueCheckCharity['zip'] : '',
                                    'phone'  => $valueCheckCharity['tel'] ? $valueCheckCharity['tel'] : '',
                                    'website' => $valueCheckCharity['website'] ? $valueCheckCharity['website'] : '',
                                );
                                add_row('option_charity', $add_charity_fields, 'options');
                                array_push($arrCheckCharityAdded, '1');
                                $charity_flag = false;
                            }
                        }
                        else{
                            foreach ($option_service_location as $option_service_location_single) {
                                if ($option_service_location_single['name'] == $valueCheckCharity) {
                                    $charity_flag = true;
                                }
                            }
                            if (!$service_location_flag) {
                                $add_charity_fields = array(
                                    'name' => $valueCheckCharity ? $valueCheckCharity : '',
                                    'address' => '',
                                    'city' => '',
                                    'state' => '',
                                    'zipcode' => '',
                                    'phone' => '',
                                    'website' => '',
                                );
                                add_row('option_charity', $add_charity_fields, 'options');
                                array_push($arrCheckCharityAdded, '1');
                                $charity_flag = false;
                            }
                        }
                    }
                }
            }
        }

    }

    $post_id = wp_insert_post(array(
        'post_status' => 'publish', //was 'draft'
        'post_type' => POST_TYPE,
        'post_title' => $name
    ));
    $post_type = 'custom_type';
    update_post_meta( $post_id, 'provider_key', 'passare' );


    $obit_keys =array(
        "obit_name_first"       => "field_599ddb9279b75",
        "obit_name_middle"      => "field_599ddb9d79b76",
        "obit_name_last"        => "field_599ddbaa79b77",
        "obit_name_prefix"      => "field_599ddb8079b74",
        "obit_name_suffix"      => "field_599ddbb679b78",
        "obit_name_gender"      => "field_59a56911f76b5",
        "obit_birth_city"       => "field_599ddcc6fa7de",
        "obit_birth_state"      => "field_599ddcd0fa7df",
        "obit_residence_city"   => "field_599ddae0f0791",
        "obit_residence_state"  => "field_599ddb01f0792",
        "obit_birthday"         => "field_599dddd5e108e",
        "obit_death"            => "field_599dddede108f",
        "obit_currentuntil"     => "field_599ddfa1a55d6",
        "obit_text"             => "field_599ddd2d059de",
        "obit_military"         => "field_599dde3ee1cd0",
        "obit_charities"        => "field_599ee6cf5733b",
        "obit_service"          => "field_599dde7de1cd1",
        "obit_gallery_images"   => "field_5b61e18c323af",
        "obituary_image"        => "field_599de092a4231"
    );

    $obit_service_array = array(
        'type'      => 'field_599dde97e1cd2',
        'location'  => 'field_599ddfd2a55d7',
        'room'      => 'field_599de016a55d8',
        'start'     => 'field_599de027a55d9',
        'end'       => 'field_599de045a55da'
    );

    if (!empty($parameters['obituary']['obit_name'])) {
        $obituary = $parameters['obituary'];
        $groups = acf_get_field_groups(array('post_type' => POST_TYPE));
        $fields = acf_get_fields($groups[0]['key']);
        $choices_array = array();
        $choice_service = array();
        foreach ($fields as $field_value) {
            if($field_value['name'] == 'obit_charities') {
                $choices_array = $field_value['choices'];
            }
            if($field_value['name'] == 'obit_service') {
                foreach ($field_value['sub_fields'] as  $service_value) {
                    if($service_value['name'] == 'location') {
                        $choice_service = $service_value['choices'];
                    }
                }
            }
        }
        foreach ($obituary as $key => $value) {
            if($key == 'id')
                continue;
            if ($key == 'obit_military'){
                // $serialise_value = serialize($value);
                $serialise_value = $value;
                update_post_meta( $post_id, $key, $serialise_value );
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key] );
                continue;
            }
            if($key == 'obit_charities') {
                $arrCharity = array();
                $obituary = $parameters['obituary'];
                $obit_charities = $obituary['obit_charities'];
                foreach ($obit_charities as  $obit_charities_key => $obit_charities_value) {
                    if (is_array($obit_charities_value) && array_key_exists('name', $obit_charities_value)) {
                        $charityPhone = $obit_charities_value['tel'] ? "tel:" . $obit_charities_value['tel'] : '';
                        $charityWeb = $obit_charities_value['website'] ? $obit_charities_value['website'] : '';
                        $charity_field_value = '<div class="obit-charity"><h4>' . $obit_charities_value['name'] . '</h4><p class="obit-address-indent">' . $obit_charities_value['address'] . '<br>' . $obit_charities_value['city'] . ', ' . $obit_charities_value['state'] . ' ' . $obit_charities_value['zip'] . '<br><a href="' . $charityPhone . '">' . $obit_charities_value['tel'] . '</a><br><a target="_blank" href="' . $charityWeb . '">Visit the website</a></p></div>';
                        array_push($arrCharity, $charity_field_value);
                    } else{
                        $charity_field_value = '<div class="obit-charity"><h4>'.$obit_charities_value.'</h4><p class="obit-address-indent"><br>,  <br><a href="tel:"></a></p></div>';
                        array_push($arrCharity, $charity_field_value);
                    }
                }

                update_post_meta( $post_id, $key, $arrCharity);
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key]);
                continue;
            }
            if($key == 'obit_service') {
                $length = count($value); //use php
                update_post_meta( $post_id, $key, $length);
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key]);
                $groups = acf_get_field_groups(array('post_type' => POST_TYPE));
                $fields = acf_get_fields($groups[0]['key']);

                if($length > 0) {
                    $i = 0;
                    foreach ($value as $key1 => $value1) {
                        foreach ($value1 as $key2 => $value2) {
                            if($key2 == 'location')
                            {
                                if (is_array($value2) && array_key_exists('name', $value2)) {
                                    $service_location_Phone = $value2['tel'] ? "tel:" . $value2['tel'] : '';
                                    $service_location_Web = $value2['website'] ? $value2['website'] : '';
                                    $service_location_field_value = '<p class="obit-address-indent"><strong>' . $value2['name'] . '</strong><br><span>' . $value2['address'] . '<br>' . $value2['city'] . ', ' . $value2['state'] . ' ' . $value2['zip'] . '</span><br><a href="' . $service_location_Phone . '">' . $value2['tel'] . '</a><br><a target="_blank" href="' . $service_location_Web . '">' . $value2['website'] . '</a></p>';
                                } else {
                                    $service_location_field_value = '<p class="obit-address-indent"><strong>' . $value2 . '</strong><br><span><br>,  </span><br><a href="tel:"></a><br><a target="_blank" href=""></a></p>';
                                }
                                update_post_meta( $post_id, $key.'_'.$i.'_'.$key2, $service_location_field_value);
                                update_post_meta( $post_id, '_'.$key.'_'.$i.'_'.$key2, $obit_service_array[$key2]);
                            }else{
                                update_post_meta( $post_id, $key.'_'.$i.'_'.$key2, $value2);
                                update_post_meta( $post_id, '_'.$key.'_'.$i.'_'.$key2, $obit_service_array[$key2]);
                            }
                        }
                        $i++;
                    }
                }
                continue;
            }
            $obituary_image_key = "obituary_image";
            $obit_gallery_image_key = "obit_gallery_images";

            if($key == 'obituary_image') {
                $arrGallary = array();
                for($i=0; $i<count($value); $i++){
                    // Add Featured Image to Post
                    $image_url        = $value[$i]['url'];
                    $image_name       = pathinfo($value[$i]['url'])['basename'];
                    $upload_dir       = wp_upload_dir(); // Set upload folder
                    $image_data       = file_get_contents($image_url); // Get image data
                    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
                    $filename         = basename( $unique_file_name ); // Create image file name

                    // Check folder permission and define file location 
                    if( wp_mkdir_p( $upload_dir['path'] ) ) {
                        $file = $upload_dir['path'] . '/' . $filename;
                    } else {
                        $file = $upload_dir['basedir'] . '/' . $filename;
                    }

                    // Create the image  file on the server
                    file_put_contents( $file, $image_data );

                    // Check image file type
                    $wp_filetype = wp_check_filetype( $filename, null );

                    // Set attachment data
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => sanitize_file_name( $filename ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );

                    // Create the attachment
                    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $file);
                    if ($attach_data) {
                        wp_update_attachment_metadata($attach_id, $attach_data);
                    }
                    if ($i == 0) {
                        $key = $obituary_image_key;
                        update_field( $key, $attach_id, $post_id );
                    }else{
                        array_push($arrGallary, $attach_id);
                    }
                }
                if(count($value) > 1) {
                    $key = $obit_gallery_image_key;
                    update_field( $key, $arrGallary, $post_id );
                }
                continue;
            }

            if (is_array($value)) {
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key] );
                foreach ($value as $key1 => $value1) {
                    update_post_meta( $post_id, $key.'_'.$key1, $value1 );
                    update_post_meta( $post_id, '_'.$key.'_'.$key1, $obit_keys[$key.'_'.$key1] );
                }
            }else {
                update_post_meta( $post_id, $key, $value );
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key] );
            }
        }
        return array("code" => "obituaries_created","message" => "Obituaries created successfully","data"=> array( "status"=> 200, 'post_id'=> $post_id ));
    }
}

//to update data in wp_obit_data
function update_post_obituaries_data(WP_REST_Request $requests){
    $parameters = $requests->get_params();
    $post_id = $parameters['id'];
    $provider_key = get_post_meta( $post_id, 'provider_key', $single = false );
    if(CUST_TOKEN != $parameters['customer_token']){
        return new WP_Error( 'rest_no_event_date', __( 'Authentication Creadentials are not provided.' ), array( 'status' => 401 ) );
    }
    if ($provider_key[0] != 'passare') {
        return new WP_Error( 'forbidden', __( 'You do not have permission to update these fields' ), array( 'status' => 403 ) );
    }
    $empty_arr = array();
    foreach ($parameters as $key => $value) {
        if ($key == 'prefix' || $key == 'suffix' || $key == 'middle' || $key == 'gender' ) {
            continue;
        }
        if(is_array($value)) {
            foreach ($value as $key1 => $value1) {
                if ($key1 == 'prefix' || $key1 == 'suffix' || $key1 == 'middle' || $key1 == 'gender' ) {
                    continue;
                }
                if (is_array($value1)) {
                    foreach ($value1 as $key2 => $value2) {
                        if ($key2 == 'prefix' || $key2 == 'suffix' || $key2 == 'middle' || $key2 == 'gender' ) {
                            continue;
                        }
                        if(empty($value2))
                            array_push($empty_arr, $key2);
                    }
                }else{
                    if(empty($value1))
                        array_push($empty_arr, $key1);
                }
            }
        } else {
            if(empty($value))
                array_push($empty_arr, $key1);
        }
    }
    if(count($empty_arr) > 0) {
        $error_str = implode(', ', $empty_arr);
        return new WP_Error( 'bad_request', __( 'Your request is missing parameters. Please verify and resubmit.' ), array( 'status' => 400, 'missing_parameters' => $empty_arr ) );
    }

    $post_id = $parameters['id'];
    $post_data = get_post($post_id);
    if (get_post_type($post_data) != POST_TYPE) {
        return new WP_Error( 'post_not_found', __( 'Obituaries not found.' ), array( 'status' => 404 ) );       ;
    }

    $arrCheckServiceAdded = array();
    $arrCheckServiceLocationAdded = array();
    $arrCheckCharityAdded = array();
    global $added;
    $service_flag = false;
    $service_location_flag = false;
    $charity_flag = false;

    $option_service_type = get_field('option_service_type','options');
    $option_service_location = get_field('option_service_location','options');
    $option_charity = get_field('option_charity','options');

    if (!empty($parameters['obituary']['obit_name'])) {
        foreach ($parameters['obituary'] as $key => $value) {
            if ($key == 'obit_service') {
                $tot_sevices = count($value);
                if($tot_sevices > 0) {
                    foreach ($value as $keyCheckService2 => $valueCheckService2) {
                        foreach ($valueCheckService2 as $keyCheckService1 => $valueCheckService) {
                            if ($keyCheckService1 == 'type') {
                                foreach ($option_service_type as $option_service_type_single) {
                                    if ($option_service_type_single['name'] == $valueCheckService) {
                                        $service_flag = true;
                                    }
                                }
                                if (!$service_flag) {
                                    $added = add_row('option_service_type', array('name' => $valueCheckService), 'options');
                                    array_push($arrCheckServiceAdded, '1');
                                    $service_flag = false;
                                }
                            }
                            if ($keyCheckService1 == 'location') {
                                if (is_array($valueCheckService) && array_key_exists('name', $valueCheckService)) {
                                    foreach ($option_service_location as $option_service_location_single) {
                                        if ($option_service_location_single['name'] == $valueCheckService['name']) {
                                            $service_location_flag = true;
                                        }
                                    }
                                    if (!$service_location_flag) {
                                        $add_service_location_fields = array(
                                            'name' => $valueCheckService['name'] ? $valueCheckService['name'] : '',
                                            'address' => $valueCheckService['address'] ? $valueCheckService['address'] : '',
                                            'city' => $valueCheckService['city'] ? $valueCheckService['city'] : '',
                                            'state' => $valueCheckService['state'] ? $valueCheckService['state'] : '',
                                            'zipcode' => $valueCheckService['zip'] ? $valueCheckService['zip'] : '',
                                            'phone' => $valueCheckService['tel'] ? $valueCheckService['tel'] : '',
                                            'website' => $valueCheckService['website'] ? $valueCheckService['website'] : '',
                                        );
                                        add_row('option_service_location', $add_service_location_fields, 'options');
                                        array_push($arrCheckServiceLocationAdded, '1');
                                        $service_flag = false;
                                    }
                                }
                                else{
                                    foreach ($option_service_location as $option_service_location_single) {
                                        if ($option_service_location_single['name'] == $valueCheckService) {
                                            $service_location_flag = true;
                                        }
                                    }
                                    if (!$service_location_flag) {
                                        $add_service_location_fields = array(
                                            'name' => $valueCheckService ? $valueCheckService : '',
                                            'address' => '',
                                            'city' => '',
                                            'state' => '',
                                            'zipcode' => '',
                                            'phone' => '',
                                            'website' => '',
                                        );
                                        add_row('option_service_location', $add_service_location_fields, 'options');
                                        array_push($arrCheckServiceLocationAdded, '1');
                                        $service_flag = false;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            if ($key == 'obit_charities') {
                if(count($value) > 0) {
                    foreach ($value as $keyCheckCharity1 => $valueCheckCharity) {
                        if (is_array($valueCheckCharity) && array_key_exists('name', $valueCheckCharity)) {
                            foreach ($option_charity as $option_charity_single) {
                                if ($option_charity_single['name'] == $valueCheckCharity['name']) {
                                    $charity_flag = true;
                                }
                            }
                            if (!$charity_flag) {
                                $add_charity_fields = array(
                                    'name' => $valueCheckCharity['name'] ? $valueCheckCharity['name'] : '',
                                    'address'   => $valueCheckCharity['address'] ? $valueCheckCharity['address'] : '',
                                    'city'  => $valueCheckCharity['city'] ? $valueCheckCharity['city'] : '',
                                    'state' => $valueCheckCharity['state'] ? $valueCheckCharity['state'] : '',
                                    'zipcode' =>  $valueCheckCharity['zip'] ? $valueCheckCharity['zip'] : '',
                                    'phone'  => $valueCheckCharity['tel'] ? $valueCheckCharity['tel'] : '',
                                    'website' => $valueCheckCharity['website'] ? $valueCheckCharity['website'] : '',
                                );
                                add_row('option_charity', $add_charity_fields, 'options');
                                array_push($arrCheckCharityAdded, '1');
                                $charity_flag = false;
                            }
                        }
                        else{
                            foreach ($option_service_location as $option_service_location_single) {
                                if ($option_service_location_single['name'] == $valueCheckCharity) {
                                    $charity_flag = true;
                                }
                            }
                            if (!$service_location_flag) {
                                $add_charity_fields = array(
                                    'name' => $valueCheckCharity ? $valueCheckCharity : '',
                                    'address' => '',
                                    'city' => '',
                                    'state' => '',
                                    'zipcode' => '',
                                    'phone' => '',
                                    'website' => '',
                                );
                                add_row('option_charity', $add_charity_fields, 'options');
                                array_push($arrCheckCharityAdded, '1');
                                $charity_flag = false;
                            }
                        }
                    }
                }
            }
        }

    }

    $obit_keys =array(
        "obit_name_first"       => "field_599ddb9279b75",
        "obit_name_middle"      => "field_599ddb9d79b76",
        "obit_name_last"        => "field_599ddbaa79b77",
        "obit_name_prefix"      => "field_599ddb8079b74",
        "obit_name_suffix"      => "field_599ddbb679b78",
        "obit_name_gender"      => "field_59a56911f76b5",
        "obit_birth_city"       => "field_599ddcc6fa7de",
        "obit_birth_state"      => "field_599ddcd0fa7df",
        "obit_residence_city"   => "field_599ddae0f0791",
        "obit_residence_state"  => "field_599ddb01f0792",
        "obit_birthday"         => "field_599dddd5e108e",
        "obit_death"            => "field_599dddede108f",
        "obit_currentuntil"     => "field_599ddfa1a55d6",
        "obit_text"             => "field_599ddd2d059de",
        "obit_military"         => "field_599dde3ee1cd0",
        "obit_charities"        => "field_599ee6cf5733b",
        "obit_service"          => "field_599dde7de1cd1",
        "obit_gallery_images"   => "field_5b61e18c323af",
        "obituary_image"        => "field_599de092a4231"
    );

    $obit_service_array = array(
        'type'      => 'field_599dde97e1cd2',
        'location'  => 'field_599ddfd2a55d7',
        'room'      => 'field_599de016a55d8',
        'start'     => 'field_599de027a55d9',
        'end'       => 'field_599de045a55da'
    );

    if (!empty($parameters['obituary']['obit_name'])) {
        $obituary = $parameters['obituary'];
        $groups = acf_get_field_groups(array('post_type' => POST_TYPE));
        $fields = acf_get_fields($groups[0]['key']);
        $choices_array = array();
        $choice_service = array();
        foreach ($fields as $field_value) {
            if($field_value['name'] == 'obit_charities') {
                $choices_array = $field_value['choices'];
            }
            if($field_value['name'] == 'obit_service') {
                foreach ($field_value['sub_fields'] as  $service_value) {
                    if($service_value['name'] == 'location') {
                        $choice_service = $service_value['choices'];

                    }
                }
            }
        }
        foreach ($obituary as $key => $value) {
            if($key == 'id')
                continue;
            if ($key == 'obit_military'){

                $serialise_value = $value;
                update_post_meta( $post_id, $key, $serialise_value );
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key] );
                continue;
            }
            if($key == 'obit_charities') {
                $arrCharity = array();
                $obituary = $parameters['obituary'];
                $obit_charities = $obituary['obit_charities'];
                foreach ($obit_charities as  $obit_charities_key => $obit_charities_value) {
                    if (is_array($obit_charities_value) && array_key_exists('name', $obit_charities_value)) {
                        $charityPhone = $obit_charities_value['tel'] ? "tel:" . $obit_charities_value['tel'] : '';
                        $charityWeb = $obit_charities_value['website'] ? $obit_charities_value['website'] : '';
                        $charity_field_value = '<div class="obit-charity"><h4>' . $obit_charities_value['name'] . '</h4><p class="obit-address-indent">' . $obit_charities_value['address'] . '<br>' . $obit_charities_value['city'] . ', ' . $obit_charities_value['state'] . ' ' . $obit_charities_value['zip'] . '<br><a href="' . $charityPhone . '">' . $obit_charities_value['tel'] . '</a><br><a target="_blank" href="' . $charityWeb . '">Visit the website</a></p></div>';
                        array_push($arrCharity, $charity_field_value);
                    } else{
                        $charity_field_value = '<div class="obit-charity"><h4>'.$obit_charities_value.'</h4><p class="obit-address-indent"><br>,  <br><a href="tel:"></a></p></div>';
                        array_push($arrCharity, $charity_field_value);
                    }
                }

                update_post_meta( $post_id, $key, $arrCharity);
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key]);
                continue;
            }
            if($key == 'obit_service') {
                $length = count($value); //use php
                update_post_meta( $post_id, $key, $length);
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key]);
                $groups = acf_get_field_groups(array('post_type' => POST_TYPE));
                $fields = acf_get_fields($groups[0]['key']);
                if($length > 0) {
                    $i = 0;
                    foreach ($value as $key1 => $value1) {
                        foreach ($value1 as $key2 => $value2) {
                            if($key2 == 'location')
                            {
                                if (is_array($value2) && array_key_exists('name', $value2)) {
                                    $service_location_Phone = $value2['tel'] ? "tel:" . $value2['tel'] : '';
                                    $service_location_Web = $value2['website'] ? $value2['website'] : '';
                                    $service_location_field_value = '<p class="obit-address-indent"><strong>' . $value2['name'] . '</strong><br><span>' . $value2['address'] . '<br>' . $value2['city'] . ', ' . $value2['state'] . ' ' . $value2['zip'] . '</span><br><a href="' . $service_location_Phone . '">' . $value2['tel'] . '</a><br><a target="_blank" href="' . $service_location_Web . '">' . $value2['website'] . '</a></p>';
                                } else {
                                    $service_location_field_value = '<p class="obit-address-indent"><strong>' . $value2 . '</strong><br><span><br>,  </span><br><a href="tel:"></a><br><a target="_blank" href=""></a></p>';
                                }
                                update_post_meta( $post_id, $key.'_'.$i.'_'.$key2, $service_location_field_value);
                                update_post_meta( $post_id, '_'.$key.'_'.$i.'_'.$key2, $obit_service_array[$key2]);
                            }else{
                                update_post_meta( $post_id, $key.'_'.$i.'_'.$key2, $value2);
                                update_post_meta( $post_id, '_'.$key.'_'.$i.'_'.$key2, $obit_service_array[$key2]);
                            }
                        }
                        $i++;
                    }
                }
                continue;
            }
            if($key == 'obituary_image') {
                $arrGallary = array();
                for($i=0; $i<count($value); $i++){
                    // Add Featured Image to Post
                    $image_url        = $value[$i]['url'];
                    $image_name       = pathinfo($value[$i]['url'])['basename'];
                    $upload_dir       = wp_upload_dir(); // Set upload folder
                    $image_data       = file_get_contents($image_url); // Get image data
                    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); // Generate unique name
                    $filename         = basename( $unique_file_name ); // Create image file name

                    // Check folder permission and define file location
                    if( wp_mkdir_p( $upload_dir['path'] ) ) {
                        $file = $upload_dir['path'] . '/' . $filename;
                    } else {
                        $file = $upload_dir['basedir'] . '/' . $filename;
                    }

                    // Create the image  file on the server
                    file_put_contents( $file, $image_data );

                    // Check image file type
                    $wp_filetype = wp_check_filetype( $filename, null );

                    // Set attachment data
                    $attachment = array(
                        'post_mime_type' => $wp_filetype['type'],
                        'post_title'     => sanitize_file_name( $filename ),
                        'post_content'   => '',
                        'post_status'    => 'inherit'
                    );

                    // Create the attachment
                    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );
                    $attach_data = wp_generate_attachment_metadata( $attach_id, $file);
                    if ($attach_data) {
                        wp_update_attachment_metadata($attach_id, $attach_data);
                    }
                    if ($i == 0) {
                        $key = $obituary_image_key;
                        update_field( $key, $attach_id, $post_id );

                    }else{
                        array_push($arrGallary, $attach_id);
                    }
                }
                if(count($value) > 1) {
                    $key = $obit_gallery_image_key;
                    update_field( $key, $arrGallary, $post_id );
                }
                continue;
            }
            if (is_array($value)) {
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key] );
                foreach ($value as $key1 => $value1) {
                    update_post_meta( $post_id, $key.'_'.$key1, $value1 );
                    update_post_meta( $post_id, '_'.$key.'_'.$key1, $obit_keys[$key.'_'.$key1] );
                }
            }else {
                update_post_meta( $post_id, $key, $value );
                update_post_meta( $post_id, '_'.$key, $obit_keys[$key] );
            }
        }
        return array(
            "code" => "obituaries_updated",
            "message" => "Obituaries updated successfully",
            "data"=> array(
                "status"=> 200,
                'post_id'=> $post_id
            )
        );
    }
}
?>