<?php


namespace Genpak\Plugins\Migration;


class MigrateCategories extends BaseClass
{

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

        $capsule = $this->getConnection($source_credentials);

        echo "Categories Migration Started.\n";

        $capsule->table('Category')->orderBy('CatID')->chunk(100, function ($categories) {
            foreach ($categories as $category) {
                try {
                    $this->migrate($category);
                } catch (\Exception $exception) {
                    echo 'Unable to migrate ' . $category->CatID . '\n';
                }
            }

            echo "Categories Migration Completed.\n";
        });
    }

    /**
     * @param $category
     */
    public function migrate($category)
    {
        $args = [
            'description' => $category->CatDetail,
            'slug' => strtolower(str_replace(' ', '-', $category->CatDesc))
        ];

        $term = wp_insert_term($category->CatDesc, 'catalog_cat', $args);

        update_term_meta($term['term_id'], 'reference_id', $category->CatID);

        echo "Migrated Category " . $category->CatID . "\n";
    }

    /**
     *
     */
    public function deleteAll()
    {
        $taxonomy_name = 'catalog_cat';
        $terms = get_terms(array(
            'taxonomy' => $taxonomy_name,
            'hide_empty' => false
        ));
        foreach ($terms as $term) {
            wp_delete_term($term->term_id, $taxonomy_name);
        }
    }
}
