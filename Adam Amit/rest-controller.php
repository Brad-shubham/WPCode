<?php
/**
 * Class to handle # Notification Service
 */

defined( 'ABSPATH' ) || exit;

if( ! class_exists( 'P_Notification_Service' ) ) :

    class P_Notification_Service {

        /**
         * Version of the REST API
         */
        private $version;

        /**
         * Namespace for custom routes
         */
        private $namespace;

        /**
         * Site nick name for handling Theme X POST  
         */
        private $sitenickname;

        /**
         * Secret key for tokens
         */
        private $secretkey;

        /**
         * Audience of tokens
         */
        private $audience;

        /**
         * Hash Algo of tokens
         */
        private $hashalgo;

        public function __construct(){
            $this->version      = 'v1';
            $this->namespace    = 'themeX/' . $this->version;
            $this->sitenickname = sanitize_option('theme_settings_x_api_site_nickname', get_option('theme_settings_x_api_site_nickname'));
            $this->secretkey    = sanitize_text_field( THEME_X_SECRET_KEY );
            $this->audience     = sanitize_text_field( THEME_X_AUDIENCE_KEY );
            $this->hashalgo     = 'HS512';

            // initialize custom REST routes
            if( $this->sitenickname && $this->secretkey ) {
                add_action('rest_api_init', [ $this, 'register_custom_rest_routes' ]);
            }
        }

        public function register_custom_rest_routes() {
            /**
             * Route for generating auth token
             */
            register_rest_route($this->namespace, 'auth/token', [
                'methods'               => 'POST',
                'callback'              => [ $this, 'rest_auth_token_callback' ],
                'sanitize_callback'     => [ $this, 'rest_arg_sanitize_callback' ],
                'permission_callback'   => '__return_true'
            ]);
        
            /**
             * Route for validating auth token and handling X referral process
             */
            register_rest_route($this->namespace, '/api/site/'. $this->sitenickname .'/ref/(?P<id>\d+)/updatestatus', [
                'methods'               => 'POST',
                'callback'              => [ $this, 'rest_auth_ref_update_callback' ],
                'sanitize_callback'     => [ $this, 'rest_arg_sanitize_callback' ],
                'args'                  => array(
                    'id'                => array(
                      'validate_callback' => 'is_numeric',
                      'required'          => true
                    ),
                ),
                'permission_callback'   => '__return_true'
            ]);
        }

        public function rest_arg_sanitize_callback($value, $request, $param) {
            return sanitize_text_field($value);
        }

        public function rest_auth_token_callback($req) {
            // sanitize request variables
            $grant_type     = sanitize_text_field($req['grant_type']);
            $client_id      = sanitize_text_field($req['client_id']);
            $client_secret  = sanitize_text_field($req['client_secret']);

            if (empty($grant_type) || empty($client_id) || empty($client_secret)) {
                return new WP_Error(
                    'rest_invalid',
                    esc_html__('Invalid Request.', 'theme-x'),
                    array('status' => 401)
                );
            }

            if ('client_credentials' !== $grant_type) {
                return new WP_Error(
                    'rest_invalid',
                    esc_html__('Invalid Request.', 'theme-x'),
                    array('status' => 401)
                );
            }

            $theme_x_user = get_option('theme_x_client_username');
            if (!$theme_x_user) {
                return new WP_Error(
                    'missing_dependency',
                    esc_html__('Unable to locate dependency.', 'theme-x'),
                    array('status' => 401)
                );
            }

            if ($theme_x_user !== $client_id) {
                return new WP_Error(
                    'mismatch_dependency',
                    esc_html__('Dependency matching failed.', 'theme-x'),
                    array('status' => 401)
                );
            }

            $theme_x_pass = THEME_X_PASS_KEY;    // For Authentication use client secret stored in config
            if (!$theme_x_pass) {
                return new WP_Error(
                    'missing_dependency',
                    esc_html__('Unable to locate dependency.', 'theme-x'),
                    array('status' => 401)
                );
            }

            if ($theme_x_pass !== $client_secret) {
                return new WP_Error(
                    'mismatch_dependency',
                    esc_html__('Dependency matching failed.', 'theme-x'),
                    array('status' => 401)
                );
            }

            $issued_at   = new DateTimeImmutable();
            $expire   = $issued_at->modify('+23 Hour 59 minutes')->getTimestamp();

            $payload = [
                "iss"   => get_site_url(),
                "aud"   => $this->audience,
                "iat"   => $issued_at,
                "nbf"   => $issued_at,
                "exp"   => $expire
            ];

            try {
                $jwt = Firebase\JWT\JWT::encode($payload, $this->secretkey, $this->hashalgo);
                if ($jwt) {
                    return [
                        'access_token'      => $jwt,
                        "token_type"        => "bearer",
                        "expires_in"        => 86399
                    ];
                } else {
                    return new WP_Error(
                        'internal_error',
                        esc_html__('Unable to process request.', 'theme-x'),
                        array('status' => 401)
                    );
                }
            } catch (\Exception $e) {
                return new WP_Error(
                    'internal_error',
                    esc_html__('Unable to process request.', 'theme-x'),
                    array('status' => 401)
                );
            }
        }

        public function rest_auth_ref_update_callback($req) {
            $headers = $req->get_headers();
            $auth_token = $headers['authorization'][0];

            if (empty($auth_token)) {
                return new WP_Error(
                    'auth_not_set',
                    esc_html__('Authorization missing or not set.', 'theme-x'),
                    array('status' => 401)
                );
            }

            if (!preg_match('/Bearer\s(\S+)/', $auth_token, $matches)) {
                return new WP_Error(
                    'auth_not_set',
                    esc_html__('Authorization missing or not set.', 'theme-x'),
                    array('status' => 401)
                );
            }

            $jwt = $matches[1];
            if (!$jwt) {
                return new WP_Error(
                    'auth_not_set',
                    esc_html__('Authorization missing or not set.', 'theme-x'),
                    array('status' => 401)
                );
            }

            try {
                $token      = Firebase\JWT\JWT::decode($jwt, new Firebase\JWT\Key($this->secretkey, $this->hashalgo));
                $now        = new DateTimeImmutable();
                $site_url   = get_site_url();

                if (
                    $token->iss !== $site_url ||
                    $token->aud !== $this->audience ||
                    $token->nbf > $now->getTimestamp() ||
                    $token->exp < $now->getTimestamp()
                ) {
                    return new WP_Error(
                        'invalid_request',
                        esc_html__('Invalid Request.', 'theme-x'),
                        array('status' => 401)
                    );
                }

                $referral_id = sanitize_text_field( $req->get_param('id') );
                $referral_status = $req['ReferralStatus'];
                if( $referral_status && $referral_status != 'Rejected' &&
                 $req['ReferralPatient']['ThemeId'] && $req['ReferralPatient']['PatientId'] ) {
                    $themex_user = get_option('theme_x_settings_themex_username');
                    $passphrase     = sanitize_text_field( THEME_X_PASSPHRASE );
                    $themex_pass = \ThemeX\theme_x_app_decrypt_string(get_option('themex_settings_key'), $passphrase);

                    $app_id = $this->get_app_id_from_referral_id( $referral_id );
                    if( $app_id ) {
                        $app = new \ThemeX\Application($app_id);

                        if( $app->is_application() ) {
                            $app->update_meta('theme-x-keys', [
                                'PbId'       => $req['X']['themexId'],
                                'PId'        => $req['X']['PatientId'],
                            ]);
                            

                            // If sales order doesn't already exist
                            if( ! $app->get_meta( 'salesorder-ids' ) ) {
                                // Initialize class to use below
                                $client = new \API\Orders($themex_user, $themex_pass);

                                // create an array of products
                                $products = [];

                                // add placeholder products that user selected in QTI form
                                $placeholder_products = $app->get_meta('product') ? unserialize( $app->get_meta('product') ) : [];
                                if( ! empty( $placeholder_products ) ) {
                                    foreach( $placeholder_products as $item ) {
                                        $products[] = $item;
                                    } 
                                } 

                                // add user selected product after approval to sales order as well
                                $product_title = $app->get_meta('product-title');
                                if( $product_title ) {
                                    // get product id by product title 
                                    $product_selected = get_page_by_title($product_title, OBJECT, 'product');
                                    if( $product_selected && $product_selected->ID ) {
                                        $product_id = $product_selected->ID;
                                        $terms = get_the_terms( $product_id, 'product_cat' );
                                        if( is_array( $terms ) && $terms[0]->name ) {
                                            // get product category term name 
                                            $cat_name = $terms[0]->name;
                                        
                                            // check if product categories contain a value with this category name
                                            $product_api_item = $client::PRODUCT_CATEGORIES[$cat_name];
                            
                                            // get category name from API placeholder items array
                                            $product_cat_item = array_search($product_api_item, $client::API_PLACEHOLDER_ITEMS);
                    
                                            // if product category item is present and not false remove this redundant item from products 
                                            if( $product_cat_item !== false ) {
                                                $product_item_index = array_search($product_cat_item, $products);
                    
                                                if( $product_item_index !== false ) {
                                                    unset( $products[$product_item_index] );
                                                    // Insert selected product ID instead of placeholder product
                                                    array_splice( $products, $product_item_index, 0, $product_id  );
                                                }
                                            }
                                        }
                                    }
                                }
                                
                                //Call Sales Order Methods with PbId (Patient ID)
                                if( ! empty( $products ) ) {
                                    $orders = $client->Orders(
                                        $req['ThemeX']['Id'],
                                        $products,
                                        $app->get_meta('state'),
                                        $app->get_status(),
                                        $app->get_meta('due-date'),
                                        $app->get_meta('x-order-ids')
                                    );
                                    
                                    if( $orders ) {
                                        $app->update_meta('x-order-ids', $orders);
                                        return new WP_REST_Response(
                                            array(
                                                'status'    => 200,
                                                'message'   => 'Orders created successfully!',
                                            )
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                return new WP_Error(
                    'invalid_request',
                    esc_html__('Unable to process request.', 'theme-x'),
                    array('status' => 401)
                );
            }
        }

        private function get_app_id_from_referral_id( $referral_id = 0 ) {
            if( ! $referral_id ) return false;

            global $wpdb;
            return $wpdb->get_var( 
                $wpdb->prepare( "SELECT app_id FROM `{$wpdb->prefix}appmeta` 
                WHERE meta_key = %s && meta_value = %d", 'x-referral-key', $referral_id )
            );
        }
    }

    new P_Notification_Service();

endif;