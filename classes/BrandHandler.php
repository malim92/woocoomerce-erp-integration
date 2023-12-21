<?php


class BrandHandler
{
    public function handleBrands($brands_array, $product_id)
    {
        foreach ($brands_array as $brand) {

            $taxonomy_exists = $this->brandExist($brand);

            if (!$taxonomy_exists) {
                $this->createBrand($brand);
            } else {
                $term_info = get_term_by('name', $brand, 'yith_product_brand');
                
                $new_taxonomy_id = $term_info->term_id;
                
                wp_set_object_terms($product_id, $new_taxonomy_id, 'yith_product_brand', true);
            }
        }
    }

    private function brandExist($brand)
    {
        
        $taxonomy_exists = term_exists(sanitize_title($brand), 'yith_product_brand');
       
        return $taxonomy_exists;
    }

    private function createBrand($brand)
    {
        $term = wp_insert_term($brand, 'yith_product_brand');

        if (!is_wp_error($term)) {
            $new_taxonomy_id = $term['term_id'];
            error_log(print_r('$new_taxonomy_id 2', true));
            error_log(print_r($new_taxonomy_id, true));
        } else {
            // Handle error if taxonomy creation fails
            echo 'Error creating taxonomy: ' . $term->get_error_message();
        }
    }
}
