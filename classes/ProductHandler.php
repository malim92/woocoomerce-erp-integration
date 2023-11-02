<?php

class ProductHandler extends MMH_Sync_Log
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
            } else {
                $this->createLog([
                    'action' => 'handle_json_upload',
                    'type' => 'file_type',
                    'type_id' => '',
                    'msg' => 'File is not Json.',
                ]);
            }
        } else {
            $this->createLog([
                'action' => 'handle_json_upload',
                'type' => 'no_file',
                'type_id' => '',
                'msg' => 'No file selected',
            ]);
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

        if ($attribute_id) {
            return $attribute_id;
        } else return false;
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
            error_log(print_r('$color here', true));
            error_log(print_r($color, true));
            $existing_term = term_exists($color, 'pa_color');

            if (!$existing_term) {
                // If the color variation doesn't exist, create it
                $term = wp_insert_term($color, 'pa_color');
                if (!is_wp_error($term)) {
                    $term_ids[] = $term['term_id'];
                } else {
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
        $product_categories = array();
        $product_additional_categories = array();
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
            $this->createLog([
                'action' => 'createProduct',
                'type' => 'new_product',
                'type_id' => '',
                'msg' => 'Error creating the product',
            ]);
            return false;
        }

        $new_product->set_sku($product_data['code']);
        $new_product->set_name($product_data['name']);
        //adding main categories
        $categories_array = explode('<', $product_data['_categories']);
        $categories_array = array_reverse($categories_array);

        $product_categories = $this->categoryCheck($categories_array);
        $product_total_categories = $product_categories;
        //adding additional categories
        if (isset($product_data['_add_category'])) {
            $additional_categories_array = explode('<', $product_data['_add_category']);
            $additional_categories_array = array_reverse($additional_categories_array);

            $product_additional_categories = $this->categoryCheck($additional_categories_array);
            $product_total_categories = array_merge($product_categories, $product_additional_categories);
            $product_total_categories = array_values(array_unique($product_total_categories));
        }

        $new_product->set_category_ids($product_total_categories);

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
                $variation_attributes = array(
                    'pa_color' => 33,
                );

                $new_product->set_attributes($variation_attributes);
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
        if (isset($product_data['producttype'])) {
            $dimensions = $this->extractDimensions($product_data['producttype']);
            $new_product->set_length($dimensions['length']);
            $new_product->set_width($dimensions['width']);
            $new_product->set_height($dimensions['height']);
        }
        if (isset($product_data['_add_style'])) {
            $style_array = explode(',', $product_data['_add_style']);
            $new_product->update_meta_data('style_filter', $style_array);
        }

        // Save the product
        $new_product_id = $new_product->save();

        $this->createLog([
            'action' => 'createProduct',
            'type' => 'Success',
            'type_id' => $product_data['code'],
            'msg' => 'Product Created successfully',
        ]);

        if (is_wp_error($new_product_id)) {
            $error_string = $new_product_id->get_error_message();
            $this->createLog([
                'action' => 'createProduct',
                'type' => 'save_product',
                'type_id' => $product_data['code'],
                'msg' => $error_string,
            ]);
        }

        return $new_product_id;
    }

    private function updateProduct($product_data, $existing_products)
    {
        $product_categories = array();
        $product_additional_categories = array();
        $product_type = 'simple';

        if (!empty($existing_products)) {
            $existing_product = reset($existing_products);

            $existing_product->set_name($product_data['name']);
            //adding main categories
            $categories_array = explode('<', $product_data['_categories']);
            $categories_array = array_reverse($categories_array);

            $product_categories = $this->categoryCheck($categories_array);
            $product_total_categories = $product_categories;
            //adding additional categories
            if (isset($product_data['_add_category'])) {
                $additional_categories_array = explode('<', $product_data['_add_category']);
                $additional_categories_array = array_reverse($additional_categories_array);
    
                $product_additional_categories = $this->categoryCheck($additional_categories_array);
                $product_total_categories = array_merge($product_categories, $product_additional_categories);
                $product_total_categories = array_values(array_unique($product_total_categories));
            }
    
            $existing_product->set_category_ids($product_total_categories);
            

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
            if (isset($product_data['_add_style'])) {
                error_log(print_r('_add_style', true));
                $style_array = explode(',', $product_data['_add_style']);
                error_log(print_r($style_array, true));
                $existing_product->update_meta_data('style_filter', $style_array);
            }
            if (isset($product_data['producttype'])) {
                $dimensions = $this->extractDimensions($product_data['producttype']);
                $existing_product->set_length($dimensions['length']);
                $existing_product->set_width($dimensions['width']);
                $existing_product->set_height($dimensions['height']);
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

            // Get the new product object from the correct classname
            $updated_product       = new $product_classname($product_id);
            // Save the product
            $existing_product->save();
            $this->createLog([
                'action' => 'updateProduct',
                'type' => 'Success',
                'type_id' => $product_data['code'],
                'msg' => 'Product Updated successfully',
            ]);
        }
    }

    private function extractDimensions($dimension_string)
    {
        //Y is height, m width, 
        $patterns = array(
            '/(\d+(\.\d+)?)\s*\(Υ\)/', // Height
            '/(\d+(\.\d+)?)\s*\(Μ\)/', // Width
            '/(\d+(\.\d+)?)\s*\(Π\)/', // Length
        );

        $height = $width = $length = 0;

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $dimension_string, $matches)) {
                $value = (float)$matches[1]; // Convert to a float
                if (strpos($pattern, 'Υ') !== false) {
                    $height = $value;
                } elseif (strpos($pattern, 'Μ') !== false) {
                    $width = $value;
                } elseif (strpos($pattern, 'Π') !== false) {
                    $length = $value;
                }
            }
        }
        $dimension_array = array("height" => $height, "width" => $width, "length" => $length);
        return $dimension_array;
    }

    private function categoryCheck($categories_array)
    {
        $parent_category_id = 0;
        $product_categories = array();

        foreach ($categories_array as $category_name) {

            $category = get_term_by('name', $category_name, 'product_cat');

            if ($category === false) {
                $category_id = $this->createCategory($category_name, $parent_category_id);
            } else {
                // Category already exists, return its term ID
                $category_id = $category->term_id;
            }
            array_push($product_categories, $category_id);
            $parent_category_id = $category_id;
        }
        return $product_categories;
    }


    private function createCategory($category_data, $parent_category)
    {
        error_log(print_r('creating category', true));
        error_log(print_r($category_data, true));
        error_log(print_r('creating $parent_category', true));
        error_log(print_r($parent_category, true));
        $category_args = array(
            'cat_name' => $category_data,
            'category_nicename' => sanitize_title($category_data),
            'parent' => $parent_category,
        );

        $result = wp_insert_term($category_data, 'product_cat', $category_args);

        if (!is_wp_error($result)) {

            $this->createLog([
                'action' => 'createCategory',
                'type' => 'Success',
                'type_id' => $category_data,
                'msg' => 'Category created successfully',
            ]);

            return $result['term_id'];
        } else {
            // Error creating the category
            $this->createLog([
                'action' => 'createCategory',
                'type' => 'Error',
                'type_id' => $category_data,
                'msg' => 'Error creating the category',
            ]);
            return false;
        }
    }
}
