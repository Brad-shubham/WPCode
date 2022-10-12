<?php

namespace Genpak\Plugins\Migration;

use Illuminate\Database\Capsule\Manager as Capsule;

class MigrateOrders extends BaseClass
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

        echo "Orders Migration Started.\n";

        $this->capsule->table('tbl_order')
            ->orderBy('customer_id')
            ->chunk(100, function ($orders) {
                foreach ($orders as $order) {
                    $this->migrate($order);
                }
            });

        echo "Orders Migration Completed.\n";
    }

    /**
     * @param $order
     * @return bool
     */
    public function migrate($order)
    {
        $customer_id = null;

        if (!$this->orderHasProducts($order->order_id)) {
            echo "\e[0;31;40mEmpty order: " . $order->order_id . ".\e[0m\n";
            return false;
        }

        if (!$this->customerExist($order->customer_id)) {
            echo "\e[0;31;40mCustomer unavailable for order: " . $order->order_id . ".\e[0m\n";
        }

        if ($this->customerExist($order->customer_id)) {
            $customer_id = $this->getCustomer($order);

            if (!$customer_id) {
                echo "\e[0;31;40mCustomer not found for order: " . $order->order_id . ".\e[0m\n";
            }
        }

        $this->createOrder($order, $customer_id);

        echo "Migrated Order: " . $order->order_id . "\n";
    }

    /**
     * @param $id
     * @return mixed
     */
    public function customerExist($id)
    {
        return $this->capsule->table('tbl_customers')
            ->where('customer_id', $id)
            ->exists();
    }

    /**
     * @param $order_id
     * @return mixed
     */
    public function orderHasProducts($order_id)
    {
        return $this->capsule->table('tbl_order_products')
            ->where('order_id', $order_id)
            ->exists();
    }

    /**
     * @param $order
     * @return false|int
     */
    public function getCustomer($order)
    {
        $customer = $this->capsule->table('tbl_customers')
            ->where('customer_id', $order->customer_id)
            ->first();

        return $customer ? email_exists($customer->customer_email) : null;
    }

    /**
     * @param $order
     * @param $customer_id
     * @return bool
     */
    public function createOrder($order, $customer_id)
    {
        $billing_address = $this->getBillingAddress($customer_id);
        $shipping_address = $this->getShippingAddress($order, $customer_id);
        $order_items = $this->getOrderItems($order);

        if (empty($order_items)) {
            echo "\e[0;31;40mProducts not found for order: " . $order->order_id . ".\e[0m\n";
            return false;
        }

        $wc_order = wc_create_order();
        try {
            foreach ($order_items as $item) {
                $wc_order->add_product(wc_get_product($item['ID']), $item['purchased_quantity']);

                $added_products = $wc_order->get_items();
                foreach ($added_products as $product) {
                    if ($product['product_id'] === $item['ID']) {
                        $product->set_subtotal($item['purchased_price']);
                        $product->set_total($item['purchased_price']);
                    }
                }
            }
            $wc_order->set_customer_id($customer_id);
            $wc_order->set_shipping_total($order->shipping_total);
            $wc_order->set_total($order->final_total);
            $wc_order->set_date_created($order->order_date);
            $wc_order->set_address($billing_address, 'billing');
            $wc_order->set_address($shipping_address, 'shipping');
            $wc_order->set_status($this->getOrderStatus($order));


            $shipping_rate = new \WC_Shipping_Rate('', 'shipping', $order->shipping_total, [], 'custom_shipping_method');
            $wc_order->add_shipping($shipping_rate);

            $wc_order->save();

            update_post_meta($wc_order->get_id(), 'reference_id', $order->order_id);

        } catch (\WC_Data_Exception $e) {
            echo $e->getMessage();
        }
    }

    /**
     * @param $userId
     * @return array
     */
    public function getBillingAddress($userId)
    {
        return array(
            'first_name' => get_user_meta($userId, 'billing_first_name', true),
            'last_name' => get_user_meta($userId, 'billing_last_name', true),
            'company' => get_user_meta($userId, 'billing_company', true),
            'address_1' => get_user_meta($userId, 'billing_address_1', true),
            'address_2' => get_user_meta($userId, 'billing_address_2', true),
            'city' => get_user_meta($userId, 'billing_city', true),
            'postcode' => get_user_meta($userId, 'billing_postcode', true),
            'country' => get_user_meta($userId, 'billing_country', true),
            'state' => get_user_meta($userId, 'billing_state', true),
            'phone' => get_user_meta($userId, 'billing_phone', true),
            'email' => get_user_meta($userId, 'billing_email', true),
        );
    }

    /**
     * @param $order
     * @param $userId
     * @return array
     */
    public function getShippingAddress($order, $userId)
    {
        if (!$this->shippingExists($order)) {
            return $this->getBillingAddress($userId);
        }

        $address = $this->capsule->table('tbl_order_shipping')
            ->where('shipping_id', $order->shipping_id)
            ->first();

        return array(
            'first_name' => $address->shipping_first_name,
            'last_name' => $address->shipping_last_name,
            'company' => $address->shipping_company,
            'address_1' => $address->shipping_address,
            'address_2' => $address->shipping_address2,
            'city' => $address->shipping_city,
            'postcode' => $address->shipping_zip,
            'country' => 'US',
            'state' => $address->shipping_state,
            'phone' => $address->shipping_phone,
        );
    }

    /**
     * @param $order
     * @return mixed
     */
    public function shippingExists($order)
    {
        return $this->capsule->table('tbl_order_shipping')
            ->where('shipping_id', $order->shipping_id)
            ->exists();
    }


    /**
     * @param $order
     * @return array
     */
    public function getOrderItems($order)
    {
        $order_items = array();

        $order_products = $this->capsule->table('tbl_order_products')
            ->where('order_id', $order->order_id)
            ->get();

        $product_ids = $order_products->pluck('ID')->toArray();

        $products = $this->capsule->table('Products')
            ->whereIn('ID', $product_ids)
            ->get();

        if ($products) {
            foreach ($products as $product) {
                $item = get_page_by_title($product->Num, 'ARRAY_A', 'product');

                if ($item) {
                    $item['purchased_quantity'] = $order_products->where('ID', $product->ID)->first()->products_qty;
                    $item['purchased_price'] = $order_products->where('ID', $product->ID)->first()->products_price;
                    $order_items[] = $item;
                }
            }
        }

        return $order_items;
    }

    /**
     * @param $order
     * @return string
     */
    public function getOrderStatus($order)
    {
        switch ($order->order_status) {
            default;
                return 'wc-pending';

            case 'Shipped';
                return 'wc-completed';

            case 'Pending';
                return 'wc-processing';

            case 'Pending Shipped';
                return 'wc-processing';

            case 'Delete';
                return 'wc-cancelled';
        }
    }
}
