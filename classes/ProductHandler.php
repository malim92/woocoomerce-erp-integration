<?php

class ProductHandler
{
    // Constructor, initialize actions and filters
    public function __construct()
    {
        add_action('admin_init', array($this, 'handle_json_upload'));
    }

    // Method to handle JSON file upload
    public function handle_json_upload()
    {
        // error_log(print_r('$_FILES', true));
        // error_log(print_r($_FILES, true));
        // error_log(print_r('$_POST', true));
        // error_log(print_r($_POST, true));
        if (isset($_FILES['json_upload'])) {
            $file = $_FILES['json_upload'];

            // Check if the file is a JSON file
            $file_type = wp_check_filetype($file['name'], array('json' => 'application/json'));

            if ($file_type['ext'] === 'json') {
                // Read JSON data
                $json_data = file_get_contents($file['tmp_name']);

                // Parse JSON data
                $products = json_decode($json_data, true);

                if ($products) {
                    // Loop through the products and create them
                    foreach ($products as $product_data) {
                        $this->productCheck($product_data);
                    }
                }
            } else error_log(print_r('not json', true));
        } else {
            error_log(print_r('no file', true));
        }
    }

    private function productCheck($product_data)
    {
        $existing_products = wc_get_products(array(
            'sku' => $product_data['code'],
        ));

        if (!empty($existing_products)) {
            $this->updateProduct($product_data, $existing_products);
        } else {
            $this->createProduct($product_data);
        }
    }

    private function productAttributeCheck($attribute_name)
    {
        $attribute_id = wc_attribute_taxonomy_id_by_name($attribute_name);
        
        if ($attribute_id ) {
            return $attribute_id;
        }
        else return false;
        
    }

    private function productAttributeHandler($attribute_name)
    {
        // Check if the attribute exists
        $attribute_id = $this->productAttributeCheck($attribute_name);

        if (!$attribute_id) {
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

        return $attribute_id;
    }

    private function createOrGetAttributeVariation($colorVariations)
    {
        $term_ids = array();
        
        foreach ($colorVariations as $key => $color) {
            // Check if the color variation already exists
            error_log(print_r('$key here', true));
            error_log(print_r($key, true));
            $existing_term = term_exists($color, 'pa_color');

            if (!$existing_term) {
                // If the color variation doesn't exist, create it
                $term = wp_insert_term($color, 'pa_color');
                if (!is_wp_error($term)) {
                    $term_ids[] = $term['term_id'];
                }
                else {
                    error_log(print_r('error here', true));
                    error_log(print_r($term->get_error_message(), true));
                }
            } else {
                // If the color variation already exists, retrieve its term ID
                $term_ids[] = $existing_term['term_id'];
            }
        }
        return $term_ids;
    }


    private function createProduct($product_data)
    {
        $category_array = array();
        $category_id = $this->categoryCheck($product_data['category']);
        array_push($category_array, $category_id);
        $product_type = 'simple'; // Default to simple product

        // Check if the product should be a variable product
        if (isset($product_data['color']) && $product_data['color']) {
            $product_type = 'variable';
        }

        if ($product_type === 'variable') {
            $new_product = new WC_Product_Variable();
        } else {
            $new_product = new WC_Product_Simple();
        }

        if (!$new_product) {
            // Error creating the product
            error_log(print_r('Error creating the product', true));
            return false;
        }
        $new_product->set_sku($product_data['code']);
        $new_product->set_name($product_data['name']);
        $new_product->set_category_ids($category_array);

        if (isset($product_data['desc2'])) {
            $new_product->set_description($product_data['desc2']);
        }

        $new_product->set_regular_price(str_replace(',', '.', $product_data['price']));
        $new_product->set_price(str_replace(',', '.', $product_data['price']));

        if ($product_type === 'variable' && isset($product_data['color'])) {

            $colorVariations = array($product_data['color']);
            $attribute_id = $this->productAttributeHandler('color');
            error_log(print_r('checking $attribute_id', true));
            error_log(print_r($attribute_id, true));
            if ($attribute_id) {
                $term_ids  = $this->createOrGetAttributeVariation($colorVariations);
                error_log(print_r('checking $attribute_id after', true));
                error_log(print_r($term_ids, true));
                $new_product->set_attributes(array(
                    'pa_color' => $term_ids,
                ));
                // $new_product->set_variation_default_attributes($variation_attributes);

            }
        }

        if (isset($product_data['barcode'])) {
            $new_product->update_meta_data('barcode', $product_data['barcode']);
        }
        if (isset($product_data['brand'])) {
            $new_product->update_meta_data('brand', $product_data['brand']);
        }
        if (isset($product_data['supplier'])) {
            $new_product->update_meta_data('supplier', $product_data['supplier']);
        }
        if (isset($product_data['style'])) {
            $new_product->update_meta_data('style', $product_data['style']);
        }

        // Save the product
        $new_product_id = $new_product->save();
        if (is_wp_error($new_product_id)) {
            $error_string = $new_product_id->get_error_message();
            error_log(print_r('error creating product', true));
            error_log(print_r($error_string, true));
        }

        return $new_product_id;
    }

    private function updateProduct($product_data, $existing_products)
    {
        $category_array = array();
        $product_type = 'simple';
        if (!empty($existing_products)) {
            $existing_product = reset($existing_products);

            $existing_product->set_name($product_data['name']);

            $category_id = $this->categoryCheck($product_data['category']);
            array_push($category_array, $category_id);
            $existing_product->set_category_ids($category_array);

            $existing_product->set_price(str_replace(',', '.', $product_data['price']));
            $existing_product->set_regular_price(str_replace(',', '.', $product_data['price']));

            if (isset($product_data['desc2'])) {
                $existing_product->set_description($product_data['desc2']);
            }

            if (isset($product_data['barcode'])) {
                $existing_product->update_meta_data('barcode', $product_data['barcode']);
            }
            if (isset($product_data['brand'])) {
                $existing_product->update_meta_data('brand', $product_data['brand']);
            }
            if (isset($product_data['supplier'])) {
                $existing_product->update_meta_data('supplier', $product_data['supplier']);
            }
            if (isset($product_data['style'])) {
                $existing_product->update_meta_data('style', $product_data['style']);
            }

            if (isset($product_data['color']) && $product_data['color']) {
                $product_type = 'variable';
                $existing_product->set_type('variable');
            }
            if ($product_type === 'variable' && isset($product_data['variations'])) {
                $variations = array();

                foreach ($product_data['variations'] as $variation) {
                    $variation_data = array(
                        'attributes' => $variation['attributes'],
                        'regular_price' => $variation['price'],
                        'price' => $variation['price'],
                    );

                    $variations[] = $variation_data;
                }

                $existing_product->set_attributes(array_keys($product_data['variations'][0]['attributes']));
                $existing_product->set_available_variations($variations);
            }

            $product_id = wc_get_product_id_by_sku($product_data['code']);
            $product_classname = WC_Product_Factory::get_product_classname($product_id, $product_type);
            error_log(print_r('$product_classname', true));
            error_log(print_r($product_classname, true));

            // Get the new product object from the correct classname
            $updated_product       = new $product_classname($product_id);
            error_log(print_r('$updated_product', true));
            error_log(print_r($updated_product, true));
            // Save the product
            $updated_product->save();
        }
    }


    private function categoryCheck($category_name)
    {

        $category = get_term_by('name', $category_name, 'product_cat');

        if ($category === false) {
            return $this->createCategory($category_name);
        } else {
            // Category already exists, return its term ID
            return $category->term_id;
        }
    }


    private function createCategory($category_data)
    {
        $category_args = array(
            'cat_name' => $category_data,
            'category_nicename' => sanitize_title($category_data),
        );

        $result = wp_insert_term($category_data, 'product_cat', $category_args);

        if (!is_wp_error($result)) {
            // Category created successfully
            return $result['term_id'];
        } else {
            // Error creating the category
            return false;
        }
    }
}
