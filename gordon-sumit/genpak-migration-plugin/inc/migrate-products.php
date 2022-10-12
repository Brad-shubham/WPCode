<?php

namespace Genpak\Plugins\Migration;

use Illuminate\Database\Capsule\Manager as Capsule;

class MigrateProducts extends BaseClass
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

        echo "Products Migration Started.\n";

        $this->capsule->table('Products')
            ->orderBy('ID')
            ->chunk(100, function ($products) {
                foreach ($products as $product) {
                    $this->migrate($product);
                }
            });

        echo "Products Migration Completed.\n";
    }

    /**
     * @param $product
     */
    public function migrate($product)
    {
        if (!$this->productAlreadyExists($product->Num)) {

            echo "Migrating Product " . $product->Num . "\n";

            $wp_product_id = $this->createNew($product);

            if ($wp_product_id) {
                $variations = $this->capsule->table('Products')
                    ->where('Num', $product->Num)
                    ->orderBy('ID')
                    ->get();

                if ($variations->count() > 1) {

                    // Set product type
                    $this->setProductType($wp_product_id, 'variable');

                    $colorVariations = $variations->pluck('Colors')->toArray();

                    if (!empty($colorVariations)) {
                        $this->addVariationAttribute($wp_product_id, $colorVariations, 'color');
                        $this->storeProductVariations($wp_product_id, $variations, $product);
                    }
                } else {
                    update_post_meta($wp_product_id, '_sku', $product->SCC);
                    update_post_meta($wp_product_id, '_stock', $product->cart_inventory);
                    if (!is_null($product->cart_inventory) && $product->cart_inventory >= 0) {
                        update_post_meta($wp_product_id, '_manage_stock', 'yes');
                    }
                }
            }

            echo "Migrated Product " . $product->Num . "\n";

        } else {
            echo "Product " . $product->Num . ' already exists' . "\n";
        }
    }

    /**
     * @param $title
     * @return array|\WP_Post|null
     */
    public function productAlreadyExists($title)
    {
        return get_page_by_title($title, 'ARRAY_A', 'product');
    }

    /**
     * @param $product
     * @return int|\WP_Error
     */
    public function createNew($product)
    {
        $productData = [
            'post_type' => 'product',
            'post_status' => 'publish',
            'post_title' => $product->Num,
            'post_excerpt' => $product->BoxDesc ? $product->BoxDesc : '',
            'post_content' => $product->cart_detailed_description,
        ];

        // Insert product
        $wp_product_id = wp_insert_post($productData);

        if ($wp_product_id) {
            // Set product type
            $this->setProductType($wp_product_id, 'simple');

            // Add product meta
            $this->addProductMeta($wp_product_id, $product);

            // Add product dimensions
            if ($product->Dim) {
                $this->addProductDimensions($wp_product_id, $product->Dim);
            }

            return $wp_product_id;
        }
    }

    /**
     * @param $wp_product_id
     * @param $type
     */
    public function setProductType($wp_product_id, $type)
    {
        wp_set_object_terms($wp_product_id, $type, 'product_type');
    }

    /**
     * @param $wp_product_id
     * @param $variationOptions
     * @param $attributeName
     */
    public function addVariationAttribute($wp_product_id, $variationOptions, $attributeName)
    {
        wp_set_object_terms($wp_product_id, implode('|', $variationOptions), $attributeName);

        $product_attributes = array();
        $product_attributes['color'] = array(
            'name' => 'Color',
            'value' => implode('|', $variationOptions),
            'position' => 0,
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 0
        );
        update_post_meta($wp_product_id, '_product_attributes', $product_attributes);
    }

    /**
     * @param $wp_product_id
     * @param $variations
     * @param $product
     */
    public function storeProductVariations($wp_product_id, $variations, $product)
    {
        foreach ($variations as $index => $variation) {

            // Setup the post data for the variation
            $wp_product_variation = array(
                'post_title' => 'Variation ' . $product->Num . ' ' . $variation->Colors,
                //'post_name' => 'product-' . $wp_product_id . '-variation-' . $index,
                'post_status' => 'publish',
                'post_parent' => $wp_product_id,
                'post_type' => 'product_variation',
                'guid' => home_url() . '/?product_variation=product-' . $wp_product_id . '-variation-' . $index
            );

            // Insert the variation
            $wp_product_variation_id = wp_insert_post($wp_product_variation);

            update_post_meta($wp_product_variation_id, 'attribute_color', $variation->Colors);

            update_post_meta($wp_product_variation_id, 'reference_id', $variation->ID);
            update_post_meta($wp_product_variation_id, '_sku', $variation->SCC);
            update_post_meta($wp_product_variation_id, '_allowed_in_cart', $variation->cart_active ? 'yes' : 'no');
            update_post_meta($wp_product_variation_id, '_regular_price', $variation->cart_price);
            update_post_meta($wp_product_variation_id, '_price', $variation->cart_price);
            update_post_meta($wp_product_variation_id, '_stock', $variation->cart_inventory);
            update_post_meta($wp_product_variation_id, '_weight', $variation->CaseWt);
            update_post_meta($wp_product_variation_id, '_length', $variation->Length);
            update_post_meta($wp_product_variation_id, '_width', $variation->Width);
            update_post_meta($wp_product_variation_id, '_height', $variation->Height);
            update_post_meta($wp_product_variation_id, '_variation_description', $variation->Descrip);

            if (!is_null($variation->cart_inventory) && $variation->cart_inventory >= 0) {
                update_post_meta($wp_product_variation_id, '_manage_stock', 'yes');
            }

            // Set default variation attribute
            if ($index === 0) {
                $variations_default_attributes['color'] = $variation->Colors;
                update_post_meta($wp_product_id, '_default_attributes', $variations_default_attributes);
            }
        }
    }

    /**
     * @param $wp_product_id
     * @param $product
     */
    public function addProductMeta($wp_product_id, $product)
    {
        // Set visibility
        update_post_meta($wp_product_id, '_visibility', 'visible');

        if ($product->CatID) {
            $category_id = $this->findCatalogCategoryId($product);
            wp_set_object_terms($wp_product_id, $category_id, 'catalog_cat');
        }

        if ($product->cart_category) {
            $category_id = $this->findProductCategoryId($product->cart_category);
            wp_set_object_terms($wp_product_id, $category_id, 'product_cat');
        }

        // Add value to acf fields
        update_field('product_meta_line', $product->Line, $wp_product_id);
        update_field('product_meta_title', $product->Descrip, $wp_product_id);
        update_field('cart_item', $product->cart_item, $wp_product_id);
        update_field('cart_item_id', $product->cart_itemid, $wp_product_id);
        update_field('cart_locator', $product->cart_locator, $wp_product_id);

        // Add post meta
        update_post_meta($wp_product_id, 'reference_id', $product->ID);
        update_post_meta($wp_product_id, '_item_color', $product->Colors);

        update_post_meta($wp_product_id, '_upc', $product->UPC);
        update_post_meta($wp_product_id, '_length', $product->Length);
        update_post_meta($wp_product_id, '_width', $product->Width);
        update_post_meta($wp_product_id, '_height', $product->Height);
        update_post_meta($wp_product_id, '_caseCube', $product->Cube);
        update_post_meta($wp_product_id, '_palletTi', $product->Ti);
        update_post_meta($wp_product_id, '_palletHi', $product->Hi);
        update_post_meta($wp_product_id, '_gram_weight', $product->GramWt);
        update_post_meta($wp_product_id, '_caseCount', $product->CaseCount);
        update_post_meta($wp_product_id, '_sleeveCount', $product->SleeveCount);
        update_post_meta($wp_product_id, '_weight', $product->CaseWt);
        update_post_meta($wp_product_id, '_catalog_order', $product->SeqNum);
        update_post_meta($wp_product_id, '_country_use', $product->countryUse);
        update_post_meta($wp_product_id, '_allowed_in_cart', $product->cart_active ? 'yes' : 'no');
        update_post_meta($wp_product_id, '_regular_price', $product->cart_price);
        update_post_meta($wp_product_id, '_price', $product->cart_price);

        update_post_meta($wp_product_id, 'menu_order', $product->cart_sort);
        update_post_meta($wp_product_id, '_backorders', $product->cart_inv_override);
        update_post_meta($wp_product_id, '_ups_dimensional_weight', $product->ups_dimensional_weight);
        update_post_meta($wp_product_id, '_material', $product->material);
    }

    /**
     * @param $wp_product_id
     * @param $dimensions
     */
    public function addProductDimensions($wp_product_id, $dimensions)
    {
        $capacity = $length = $width = $height = false;

        $symbolCount = substr_count($dimensions, 'x');

        if (!$symbolCount) {
            $capacity = $dimensions;
        }
        if ($symbolCount === 1) {
            $explodedDimensions = explode('x', $dimensions);
            $length = $explodedDimensions[0];
            $width = $explodedDimensions[1];
        }
        if ($symbolCount === 2) {
            $explodedDimensions = explode('x', $dimensions);
            $length = $explodedDimensions[0];
            $width = $explodedDimensions[1];
            $height = $explodedDimensions[2];
        }

        if ($capacity) {
            update_post_meta($wp_product_id, '_itemCapacity', $capacity);
        }
        if ($length) {
            update_post_meta($wp_product_id, '_itemLength', $length);
        }
        if ($width) {
            update_post_meta($wp_product_id, '_itemWidth', $width);
        }
        if ($height) {
            update_post_meta($wp_product_id, '_itemHeight', $height);
        }
    }

    /**
     * @param $product
     * @return int|null
     */
    public function findCatalogCategoryId($product)
    {
        $category = $this->capsule->table('Category')->where('CatID', $product->CatID)->first();

        if ($category) {
            $slug = strtolower(str_replace(' ', '-', $category->CatDesc));
            $catalog_category = get_term_by('slug', $slug, 'catalog_cat');

            if ($catalog_category) {
                return $catalog_category->term_id;
            }
        }

        return null;
    }

    /**
     * @param $slug
     * @return int|mixed|null
     */
    public function findProductCategoryId($slug)
    {
        $product_category = get_term_by('slug', $slug, 'product_cat');

        if ($product_category) {
            return $product_category->term_id;
        }
        if (!$product_category) {
            $name = ucwords(str_replace('-', ' ', $slug), ' ');

            $args = [
                'slug' => $slug
            ];

            $term = wp_insert_term($name, 'product_cat', $args);

            update_term_meta($term['term_id'], 'reference_id', $slug);

            if ($term) {
                return $term['term_id'];
            }
        }

        return null;
    }
}
