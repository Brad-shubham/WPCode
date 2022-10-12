<?php


namespace Genpak\Plugins\Migration;


use WP_Post;

class LinkProductMedia
{
    public function __invoke()
    {
        echo "Media Linking Started.\n";

        $products = $this->getProducts();

        if (!empty($products)) {
            $this->link($products);
        }

        echo "Media Linking Completed.\n";
    }

    /**
     * @return int[]|WP_Post[]
     */
    public function getProducts()
    {
        return get_posts([
            'numberposts' => -1,
            'post_type' => 'product',
        ]);
    }

    /**
     * @param $products
     */
    public function link($products)
    {
        foreach ($products as $product) {

            $mediaItems = $this->searchMediaByTitle($product->post_title);

            if (!empty($mediaItems)) {

                $thumbnail_id = null;
                $gallery_ids = [];

                foreach ($mediaItems as $mediaItem) {
                    if ($mediaItem->post_title === $product->post_title) {
                        $thumbnail_id = $mediaItem->ID;
                    }
                    if ($mediaItem->post_title !== $product->post_title) {
                        $gallery_ids[] = $mediaItem->ID;
                    }
                }

                if ($thumbnail_id) {
                    update_post_meta($product->ID, '_thumbnail_id', $thumbnail_id);
                    echo "Thumbnail Linked with " . $product->post_title . " product\n";
                }

                if (!empty($gallery_ids) && $thumbnail_id) {
                    update_post_meta($product->ID, '_product_image_gallery', implode(',', $gallery_ids));
                    echo "Gallery Linked with " . $product->post_title . " product\n";
                }
            }
        }
    }

    /**
     * @param $title
     * @return int[]|WP_Post[]
     */
    public function searchMediaByTitle($title)
    {
        return get_posts([
            'numberposts' => -1,
            'post_type' => 'attachment',
            's' => $title
        ]);
    }

    /**
     * @param $products
     */
    public function unlink($products)
    {
        foreach ($products as $product) {
            update_post_meta($product->ID, '_product_image_gallery', '');
        }
    }
}
