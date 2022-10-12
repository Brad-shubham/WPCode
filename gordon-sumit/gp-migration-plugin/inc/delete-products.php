<?php


namespace Genpak\Plugins\Migration;


class DeleteProducts
{
    public function __invoke()
    {
        $this->deleteAll();;
    }

    /**
     *
     */
    public function deleteAll()
    {
        $args = array(
            'meta_key' => 'reference_id',
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        $products = get_posts($args);

        foreach ($products as $product) {
            wp_delete_post($product->ID, true);

            echo "Deleted Product " . $product->post_title . "\n";
        }

        $args = array(
            'meta_key' => 'reference_id',
            'post_type' => 'product_variation',
            'post_status' => 'publish',
            'posts_per_page' => -1
        );
        $variations = get_posts($args);

        foreach ($variations as $variation) {
            wp_delete_post($variation->ID, true);

            echo "Deleted Variation " . $variation->post_title . "\n";
        }
    }
}
