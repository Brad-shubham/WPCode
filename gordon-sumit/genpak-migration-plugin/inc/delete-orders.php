<?php

namespace Genpak\Plugins\Migration;


use function Sodium\add;

class DeleteOrders
{
    /**
     *
     */
    public function __invoke()
    {
        echo "Orders Deletion Started.\n";

        $this->deleteAll();

        echo "Orders Deletion Completed.\n";
    }

    /**
     *
     */
    public function deleteAll()
    {
        $query = new \WC_Order_Query(array(
            'limit' => -1,
            'return' => 'ids',
        ));

        $order_ids = $query->get_orders();

        if ($order_ids) {
            foreach ($order_ids as $order_id) {

                if (get_post_meta($order_id, 'reference_id', true)) {
                    wp_delete_post($order_id, true);
                }
            }
        }
    }
}
