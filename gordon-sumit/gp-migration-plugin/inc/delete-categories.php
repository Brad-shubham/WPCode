<?php

namespace Genpak\Plugins\Migration;


class DeleteCategories
{
    /**
     *
     */
    public function __invoke()
    {
        echo "Categories Deletion Started.\n";

        $this->deleteProductCategories();
        $this->deleteCatalogCategories();

        echo "Categories Deletion Completed.\n";
    }

    /**
     *
     */
    public function deleteProductCategories()
    {
        $taxonomy_name = 'product_cat';
        $terms = get_terms(array(
            'taxonomy' => $taxonomy_name,
            'hide_empty' => false
        ));

        if ($terms) {
            foreach ($terms as $term) {
                if (get_term_meta($term->term_id, 'reference_id', true)) {
                    wp_delete_term($term->term_id, $taxonomy_name);
                }
            }
        }
    }

    /**
     *
     */
    public function deleteCatalogCategories()
    {
        $taxonomy_name = 'catalog_cat';
        $terms = get_terms(array(
            'taxonomy' => $taxonomy_name,
            'hide_empty' => false
        ));

        if ($terms) {
            foreach ($terms as $term) {
                if (get_term_meta($term->term_id, 'reference_id', true)) {
                    wp_delete_term($term->term_id, $taxonomy_name);
                }
            }
        }
    }
}
