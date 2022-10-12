<?php
/**
 * API calls for the plugin.
 *
 * @link       https://google.com/
 * @since      1.0.0
 *
 * @package Saddle_Oak_Sos_Integration
 */

namespace Saddle_Oak_SOS_Integration_Upgrader\Includes\Ajax;

use Saddle_Oak_SOS_Integration_Upgrader\Includes\Saddle_Oak_SOS_Integration_Upgrader_Options as Options;
use Saddle_Oak_SOS_Integration_Upgrader\Includes\Saddle_Oak_SOS_Integration_Upgrader_Admin;

/**
 * Class Options
 */
class Saddle_Oak_SOS_Integration_Api extends Saddle_Oak_SOS_Integration_Upgrader_Admin
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options = array();

    private $endpoint = 'https://api-beta.sosinventory.com';

    private $api_version = '/api/v2/';

    private $api_params;

    private $api_headers;

    private $refresh_token;

    private $access_token;

    private $api_access_code;

    private $plugin_settings;

    function __construct()
    {
        $this->options = Options::get_options();
        $this->plugin_settings = Options::get_plugin_settings();

//        $this->get_auth_token();
    }

    public function get_auth_token($api_code)
    {
        if (!empty($api_code)) $this->api_access_code = $api_code;
        return $this->api_get_data('auth-token', 'post', array(), true);
    }

    public function api_get_new_access_token($api_refresh_token)
    {
        $this->refresh_token = $api_refresh_token;
        $api_fields = array(
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token,
        );

        return $this->api_get_data('access-token', 'post', $api_fields);
    }

    public function api_get_data($request_uri_type, $request_type, $request_fields = array(), $is_auth = false, $requested_obj_id = null)
    {
        if ($is_auth) {
            $this->api_params = array(
                'grant_type' => 'authorization_code',
                'code' => $this->api_access_code,
                'client_id' => Options::get_client_id(),
                'client_secret' => Options::get_client_sec(),
                'redirect_uri' => $this->get_url(),
            );
            $this->api_headers = array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            );

        } else {

            if (strtolower($request_uri_type) !== 'access-token' && strtolower($request_uri_type) !== 'auth-token')
                $this->access_token = Options::verify_sos_access_token();


            if (
                strtolower($request_uri_type) !== 'sales-order'
                && strtolower($request_uri_type) !== 'customer'
                && strtolower($request_uri_type) !== 'get-sales-order'
                && strtolower($request_uri_type) !== 'salesreceipt'
                && strtolower($request_uri_type) !== 'get-salesreceipt'
            ) {
                if (!$request_fields) {
                    $this->api_params = array();
                } else {
                    foreach ($request_fields as $key => $value) {
                        $this->api_params[$key] = $value;
                    }
                }
            } else {
                $this->api_params = $request_fields;
            }

            $this->api_headers = array(
                'Content-Type' => 'application/x-www-form-urlencoded',
            );

            if (!empty($this->access_token))
                $this->api_headers['Authorization'] = 'Bearer ' . $this->access_token;
        }

        if (
            strtolower($request_uri_type) !== 'sales-order'
            && strtolower($request_uri_type) !== 'customer'
            && strtolower($request_uri_type) !== 'get-sales-order'
            && strtolower($request_uri_type) !== 'salesreceipt'
            && strtolower($request_uri_type) !== 'get-salesreceipt'
        ) {
            $request_body = http_build_query($this->api_params);
        } else {
            $request_body = json_encode($this->api_params, true);
        }


        $request_options = array(
            'timeout' => 5,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
            'blocking' => true,
            'headers' => $this->api_headers,
            'cookies' => array(),
            'body' => $request_body,
            'compress' => false,
            'decompress' => true,
            'sslverify' => true,
            'stream' => false,
            'filename' => null
        );
        $request_api_uri = $this->api_get_request_uri(strtolower($request_uri_type));
        if ($request_uri_type == "get-sales-order" || $request_uri_type == "get-salesreceipt") {
            $request_api_uri = $request_api_uri . '/' . $requested_obj_id;
            $request_options = array(
                'timeout' => 5,
                'redirection' => 5,
                'httpversion' => '1.1',
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . get_bloginfo('url'),
                'headers' => $this->api_headers,
            );
        }
        if ($request_type == 'post') {
            $response = wp_remote_post(
                $request_api_uri,
                $request_options
            );
        } else {
            $response = wp_remote_get(
                $request_api_uri,
                $request_options
            );
        }
        if (is_wp_error($response)) {
            return $response->get_error_message();
        } else {
            return wp_remote_retrieve_body($response);
        }

    }

    public function api_get_request_uri($request_type)
    {
        switch ($request_type) {
            case ($request_type == "auth-token" || $request_type == "access-token"):
                return $this->endpoint . '/oauth2/token';
            case "all-items":
                if(!empty($this->plugin_settings['location']) && $this->plugin_settings['location'] !== 0) {
                    $location_name = 'default';
                    $location_id = $this->plugin_settings ['location'];

                    $location_data = json_decode($this->api_get_data('location', 'get', array()));
                    foreach($location_data->data as $single_location):
                        if($location_id == $single_location->id) {
                            $location_name = strtolower($single_location->name);
                            break;
                        }
                    endforeach;

                    return $this->endpoint . $this->api_version . "item/?location=" . $location_name;
                } else {
                    return $this->endpoint . $this->api_version . "item";
                }
            case ($request_type == "sales-order" || $request_type == "get-sales-order"):
                return $this->endpoint . $this->api_version . "salesorder";
            case "customer":
                return $this->endpoint . $this->api_version . "customer";
            case "shipment":
                return $this->endpoint . $this->api_version . "shipment";
            case "invoice":
                return $this->endpoint . $this->api_version . "invoice";
            case "channels":
                return $this->endpoint . $this->api_version . "channel";
            case "class":
                return $this->endpoint . $this->api_version . "class";
            case "deposit-accounts":
                return $this->endpoint . $this->api_version . "account?acctType=deposit";
            case "location":
                return $this->endpoint . $this->api_version . "location";
            case ($request_type == "salesreceipt" || $request_type == "get-salesreceipt"):
                return $this->endpoint . $this->api_version . "salesreceipt";
        }
    }

    public function push_sos_authentication($data)
    {
        $options = Options::get_options(true);
        $options['so_sos_authentication'] = $data;
        $options['so_sos_authentication_created'] = date('Y-m-d H:i:s');
        Options::update_options($options);
    }
}