<?php

class AttributeHandler
{

    public function productAttributeHandler($product, $colors_array)
    {

        $attributes = array();
        foreach ($colors_array as $color) {
            
            if (!term_exists($color, 'pa_color')) {
                $term_data = wp_insert_term($color, 'pa_color');
                $term_id   = $term_data['term_id'];
            } else {
                $term_id   = get_term_by('name', $color, 'pa_color');
                if (isset($term_id->term_id))
                $term_id = $term_id->term_id;
                else continue;
            }
            array_push($attributes, $term_id);
        }

        $attributes_arr =
            array(
                'id' => wc_attribute_taxonomy_id_by_name('pa_color'),
                'name' => 'pa_color',
                'options' => $attributes,
                'position' => 1,
                'visible' => true,
                'variation' => true,
            );

        if ($attributes_arr) {

            $attribute = new WC_Product_Attribute();
            if (isset($attributes_arr['id'])) {
                $attribute->set_id($attributes_arr['id']);
            }
            $attribute->set_name($attributes_arr['name']);
            $attribute->set_options($attributes_arr['options']);
            $attribute->set_position($attributes_arr['position']);
            $attribute->set_visible($attributes_arr['visible']);
            $attribute->set_variation($attributes_arr['variation']);
            $attributes[] = $attribute;
        }

        $product->set_attributes($attributes);

        return $product;
    }


    public function createVariation($product_id, $variation_data, $stock_quantity, $price)
    {
        $stock_quantity = $this->formatStockArray($stock_quantity);

        foreach ($variation_data as $color) {
            $term = term_exists($color, 'pa_color');
            if (!$term) {

                $term = wp_insert_term($color, 'pa_color', array('slug' => $color));
                if (!is_wp_error($term) && isset($term['term_id'])) {
                    $term_id = $term['term_id'];
                } else {
                    $error_message = is_wp_error($term) ? $term->get_error_message() : 'Unknown error';
                    error_log(print_r('Error creating term: ' . $error_message, true));
                }
            } else {
                $term_id = $term['term_id'];
            }
            $term_obj = get_term_by('id', $term_id, 'pa_color');
            $slug = $term_obj->slug;

            $slug = $term_obj->slug;
            $color = str_replace("\r\n", ' ', strtolower($color));

            $variationStock = isset($stock_quantity[$color]) ? $stock_quantity[$color] : $stock_quantity[';'];
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);

            $variation->set_attributes(array('pa_color' => $slug));
            $variation->set_manage_stock(true);
            $variation->set_stock_quantity($variationStock);
            $variation->set_regular_price($price);
            $variation->save();
        }
        return;
    }

    private function formatStockArray($jsonData)
    {
        // $jsonData = json_decode($data, true);
        if (
            is_array($jsonData) && count($jsonData) > 0 && key($jsonData) !== ';'
        ) {
            foreach ($jsonData as $key => $value) {
                $newKey = strtolower(rtrim($key, ';'));
                unset($jsonData[$key]);
                $jsonData[$newKey] = $value;
            }
        } else if (isset($jsonData[';'])) {
            $jsonData = $jsonData[';'];
        }
        return $jsonData;
    }

    public function checkVariationExist($colors_array, $product_id)
    {
        $variations = get_posts(array(
            'post_type'     => 'product_variation',
            'post_status'   => 'publish',
            'numberposts'   => -1,
            'post_parent'   => $product_id,
        ));

        
        foreach ($variations as $key => $variation) {
            $variation_id = $variation->ID;
            $variation_attributes = wc_get_product_variation_attributes($variation_id);

            $color_key = array_search(strtolower(str_replace("-", ' ', $variation_attributes['attribute_pa_color'])), $colors_array);
            if ($color_key !== false) {
                unset($colors_array[$color_key]);
            }
        }

        return $colors_array;
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
