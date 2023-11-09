<?php

class AttributeHandler
{
    private function productAttributeCheck($attribute_name)
    {
        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);

        if ($attribute_id) {
            $attribute_object = wc_get_attribute($attribute_id);
            error_log(print_r('$attribute_object 0', true));
            error_log(print_r($attribute_object, true));
            return $attribute_object;
        } else return false;
    }

    public function productAttributeHandler($product_id , $attribute_name, $colors_array)
    {
        // Check if the attribute exists
        $attribute_obj = $this->productAttributeCheck($attribute_name);
        if (!$attribute_obj) {
            // Create the attribute
            $attribute_args = array(
                'name' => $attribute_name,
                'type' => 'select',
            );

            $attribute_id = wc_create_attribute($attribute_args);

            if (!$attribute_id) {
                return false;
            }
        }

        if (!taxonomy_exists('pa_color')) {
            // Register the taxonomy
            register_taxonomy(
                'pa_color',
                'product',
                array(
                    'label' => __('Color', 'color'),
                    'public' => false,
                    'rewrite' => false,
                    'hierarchical' => true,
                    'show_ui' => false,
                )
            );
        }
        foreach ($colors_array as $color) {
            if (!term_exists($color, 'pa_color')) {
                $term_data = wp_insert_term($color, 'pa_color');
                error_log(print_r('$term_data 0', true));
                error_log(print_r($term_data, true));
                $term_id   = $term_data['term_id'];
            } else {
                $term_id   = get_term_by('name', $color, 'pa_color')->term_id;
                error_log(print_r('$term_id 0', true));
                error_log(print_r($term_id, true));
            }
            error_log(print_r('$ewrd 0', true));
            
            $ewrd = wp_set_object_terms($product_id,intval($term_id) , 'pa_color');
            error_log(print_r('$ewrd 1', true));
            error_log(print_r($ewrd, true));
        }
        
        return $attribute_obj;
    }

    public function createOrUpdateVariation($product_id, $variation_data)
    {
        // Check if variation exists
        $variation_id = $this->getVariationIdByAttributes($product_id, $variation_data['attributes']);
        error_log(print_r('$variation_id 0', true));
        error_log(print_r($variation_id, true));
        if ($variation_id) {
            $this->updateVariation($variation_id, $variation_data);
        } else {
            $variation_id = $this->createVariation($product_id, $variation_data);
        }

        return $variation_id;
    }

    private function getVariationIdByAttributes($product_id, $attributes)
    {
        $variations = get_posts(array(
            'post_type' => 'product_variation',
            'post_status' => array('private', 'publish'),
            'numberposts' => 1,
            'meta_query' => array(
                array(
                    'key' => 'pa_color',
                    'value' => $attributes['color'],
                ),
            ),
            'post_parent' => $product_id,
        ));
        error_log(print_r('$variations 3', true));
        error_log(print_r($variations, true));
        return (empty($variations)) ? 0 : $variations[0]->ID;
    }

    private function createVariation($product_id, $variation_data)
    {
        error_log(print_r('$product_id 4', true));
        error_log(print_r($product_id, true));
        $variation = new WC_Product_Variation();
        $variation->set_parent_id($product_id);
        $variation->set_attributes($variation_data['attributes']);
        // $variation->set_sku($variation_data['sku']);
        $variation->set_regular_price($variation_data['regular_price']);
        // $variation->set_sale_price($variation_data['sale_price']);
        $variation->set_stock_quantity($variation_data['stock_quantity']);
        $variation->set_manage_stock($variation_data['manage_stock']);

        $variation_id = $variation->save();
        error_log(print_r('$variation_id 4', true));
        error_log(print_r($variation_id, true));
        return $variation_id;
    }

    private function updateVariation($variation_id, $variation_data)
    {
        $variation = wc_get_product($variation_id);

        $variation->set_attributes($variation_data['attributes']);
        // $variation->set_sku($variation_data['sku']);
        $variation->set_regular_price($variation_data['regular_price']);
        // $variation->set_sale_price($variation_data['sale_price']);
        $variation->set_stock_quantity($variation_data['stock_quantity']);
        $variation->set_manage_stock($variation_data['manage_stock']);

        $variation->save();
    }
}
