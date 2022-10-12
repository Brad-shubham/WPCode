<?php


namespace Genpak\Plugins\Migration;


class MigrateProductCrossSells extends BaseClass
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

        echo "Products Cross Sells Migration Started.\n";

        $this->capsule->table('Products')
            ->orderBy('ID')
            ->chunk(1, function ($products) {
                foreach ($products as $product) {
                    $this->migrate($product);
                }
            });

        echo "Products Cross Sells Migration Completed.\n";
    }

    /**
     * @param $product
     */
    public function migrate($product)
    {
        if ($product->related_prod_num) {

            $cross_sell_ids = [];

            $variations = $this->capsule->table('Products')
                ->where('Num', $product->Num)
                ->orderBy('ID')
                ->get();

            $cross_sell_names = $this->getCrossSellNames($variations);

            if ($cross_sell_names) {
                $cross_sell_ids = $this->getCrossSellIds($cross_sell_names);
            }

            if ($cross_sell_ids) {
                $wp_product = get_page_by_title($product->Num, 'ARRAY_A', 'product');

                if ($wp_product) {

                    $wp_migrated_product = new \WC_Product($wp_product['ID']);

                    if ($variations->count() > 1) {
                        $wp_migrated_product = new \WC_Product_Variable($wp_product['ID']);
                    }

                    $wp_migrated_product->set_cross_sell_ids($cross_sell_ids);
                    $wp_migrated_product->save();

                    echo "Product " . $product->Num . " Cross Sells Migrated.\n";
                }
            }
        } else {
            echo "Product " . $product->Num . " Cross Sells Not Found.\n";
        }
    }

    /**
     * @param $variations
     * @return array
     */
    public function getCrossSellNames($variations)
    {
        $cross_sell_names = [];

        foreach ($variations as $variation) {
            if ($variation->related_prod_num) {
                $names = explode(',', $variation->related_prod_num);
                if ($names) {
                    foreach ($names as $name) {
                        $cross_sell_names[] = $name;
                    }
                }
            }
        }

        return array_unique($cross_sell_names);
    }

    /**
     * @param $cross_sell_names
     * @return array
     */
    public function getCrossSellIds($cross_sell_names)
    {
        $cross_sell_ids = [];

        foreach ($cross_sell_names as $name) {
            $wp_product = get_page_by_title($name, 'ARRAY_A', 'product');
            if ($wp_product) {
                $cross_sell_ids[] = $wp_product['ID'];
            }
        }

        return $cross_sell_ids;
    }
}
