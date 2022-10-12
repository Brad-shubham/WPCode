<?php

namespace Genpak\Plugins\Migration;

class MigrateCustomers extends BaseClass
{
    protected $capsule;

    /**
     *
     */
    public function __invoke()
    {
        $source_credentials = $this->getSourceCredentials();

        if (!$source_credentials) {
            echo "\e[0;31;40mSource not found, please configure the plugin settings.\e[0m\n";
            return false;
        }

        $this->capsule = $this->getConnection($source_credentials);

        echo "Customers Migration Started.\n";

        $this->capsule->table('tbl_customers')
            ->orderByDesc('customer_id')
            ->chunk(100, function ($customers) {
                foreach ($customers as $customer) {
                    $this->migrate($customer);
                }
            });

        echo "Customers Migration Completed.\n";
    }

    /**
     * @param $customer
     */
    public function migrate($customer)
    {
        $wp_user_id = $this->insertUser($customer);

        if ($wp_user_id) {
            $this->updateUserMeta($customer, $wp_user_id);
            $this->updateBillingAddress($customer, $wp_user_id);
            $this->updateShippingAddress($customer, $wp_user_id);

            echo "Customer " . $customer->customer_email . " migrated.\n";
        }
    }

    /**
     * @param $customer
     * @return bool|int|\WP_Error
     */
    public function insertUser($customer)
    {
        $username = null;
        $password = $customer->customer_password;
        $email = $customer->customer_email;

        if ($email) {
            $username = explode('@', $email)[0] . '-' . mt_rand(11111, 99999);
        }
        if (!$email) {
            $username = str_replace(' ', '-', $customer->customer_first_name) . '-' . mt_rand(11111, 99999);
        }

        if (email_exists($email)) {
            echo "\e[0;31;40mCustomer " . $customer->customer_email . " already exists.\e[0m\n";
            return false;
        }

        $customer_data = [
            'user_pass' => $password,
            'user_login' => $username,
            'user_email' => $email,
            'display_name' => $customer->customer_first_name,
            'nick_name' => explode(' ', $customer->customer_first_name)[0],
            'first_name' => $customer->customer_first_name,
            'last_name' => $customer->customer_last_name,
            'user_registered' => $customer->customer_date_signup,
            'role' => 'customer',
        ];

        return wp_insert_user($customer_data);
    }

    /**
     * @param $customer
     * @param $wp_user_id
     */
    public function updateUserMeta($customer, $wp_user_id)
    {
        update_user_meta($wp_user_id, 'reference_id', $customer->customer_id);
        update_user_meta($wp_user_id, 'home_phone', $customer->customer_home_phone);
        update_user_meta($wp_user_id, 'work_phone', $customer->customer_work_phone);
        update_user_meta($wp_user_id, 'enews', $customer->customer_enews);
        update_user_meta($wp_user_id, 'ip', $customer->customer_ip);
        update_user_meta($wp_user_id, 'device', $customer->customer_device);
    }

    /**
     * @param $customer
     * @param $wp_user_id
     */
    public function updateBillingAddress($customer, $wp_user_id)
    {
        update_user_meta($wp_user_id, 'billing_first_name', $customer->customer_first_name);
        update_user_meta($wp_user_id, 'billing_last_name', $customer->customer_last_name);
        update_user_meta($wp_user_id, 'billing_company', $customer->customer_company);
        update_user_meta($wp_user_id, 'billing_address_1', $customer->customer_address);
        update_user_meta($wp_user_id, 'billing_address_2', $customer->customer_address2);
        update_user_meta($wp_user_id, 'billing_city', $customer->customer_city);
        update_user_meta($wp_user_id, 'billing_postcode', $customer->customer_zip);
        update_user_meta($wp_user_id, 'billing_country', 'US');
        update_user_meta($wp_user_id, 'billing_state', $customer->customer_state);
        update_user_meta($wp_user_id, 'billing_phone', $customer->customer_work_phone);
        update_user_meta($wp_user_id, 'billing_email', $customer->customer_email);
    }

    /**
     * @param $customer
     * @param $wp_user_id
     */
    public function updateShippingAddress($customer, $wp_user_id)
    {
        update_user_meta($wp_user_id, 'shipping_first_name', $customer->customer_first_name);
        update_user_meta($wp_user_id, 'shipping_last_name', $customer->customer_last_name);
        update_user_meta($wp_user_id, 'shipping_company', $customer->customer_company);
        update_user_meta($wp_user_id, 'shipping_address_1', $customer->customer_address);
        update_user_meta($wp_user_id, 'shipping_address_2', $customer->customer_address2);
        update_user_meta($wp_user_id, 'shipping_city', $customer->customer_city);
        update_user_meta($wp_user_id, 'shipping_postcode', $customer->customer_zip);
        update_user_meta($wp_user_id, 'shipping_country', 'US');
        update_user_meta($wp_user_id, 'shipping_state', $customer->customer_state);
        update_user_meta($wp_user_id, 'shipping_email', $customer->customer_email);
    }
}
