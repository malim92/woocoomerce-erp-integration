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
        error_log(print_r('$_FILES', true));
        error_log(print_r($_FILES, true));
        error_log(print_r('$_POST', true));
        error_log(print_r($_POST, true));
        if (isset($_FILES['json_upload'])) {
            $file = $_FILES['json_upload'];

            // Check if the file is a JSON file
            $file_type = wp_check_filetype($file['name'], array('json' => 'application/json'));

            if ($file_type['ext'] === 'json') {
                // Read JSON data
                $json_data = file_get_contents($file['tmp_name']);

                // Parse JSON data
                $products = json_decode($json_data, true);

                $stockData = $this->fetchStockData();

                $stockMapping = array();
                foreach ($stockData as $stockItem) {
                    $itemCode = $stockItem->{'item.code'};
                    $stockMapping[$itemCode] = $stockItem;
                }

                if ($products) {
                    foreach ($products as $product_data) {
                        //appending the stock object
                        $code = $product_data['code'];
                        if (isset($stockMapping[$code])) {
                            $product_data['stock'] = $stockMapping[$code];
                        }
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

    private function fetchStockData()
    {
        $url = 'http://185.106.103.114:8080/$TableGetView?system=pos&file=stocktake&report=web_stocktake&compact=true';

        $args = array(
            'timeout'     => 5,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking'    => true,
            'body'        => null,
            'compress'    => false,
            'decompress'  => true,
            'sslverify'   => true,
            'stream'      => false,
            'filename'    => null
        );

        $results = json_decode(wp_remote_retrieve_body(wp_remote_get($url, $args)));
        return $results;
    }

    private function productCheck($product_data)
    {
        $_product_id = wc_get_product_id_by_sku($product_data['code']);

        if ($_product_id > 0) {
            $existing_products = wc_get_product($_product_id);

            $this->updateProduct($product_data, $existing_products);
        } else {
            $this->createProduct($product_data);
        }
    }


    private function createProduct($product_data)
    {
        $product_categories = array();
        $product_additional_categories = array();
        $product_type = 'simple'; // Default to simple product

        // Check if the product should be a variable product
        if (isset($product_data['_colorss']) && $product_data['_colorss']) {
            $product_type = 'variable';
        }

        if ($product_type == 'variable') {
            $new_product = new WC_Product_Variable();
        } else {
            $new_product = new WC_Product_Simple();
        }
        // error_log(print_r('$new_product 4', true));
        // error_log(print_r($new_product, true));
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
        $new_product->set_name($this->normalizeCharacters($product_data['name']));
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

        if (isset($product_data['stock'])) {
            $new_product->set_stock_quantity($product_data['stock']->quantity);
            $new_product->set_manage_stock(true);
        } else {
            $new_product->set_stock_quantity(0);
        }

        if (isset($product_data['*notes'])) {
            $new_product->set_description($product_data['*notes']);
        }
        if (isset($product_data['*notes2'])) {
            $new_product->update_meta_data('notes_en', $product_data['*notes2']);
        }
        $new_product->set_regular_price(str_replace(',', '.', $product_data['price']));
        $new_product->set_price(str_replace(',', '.', $product_data['price']));

        if (isset($product_data['design'])) {
            // $gender_array = explode('|', $this->normalizeCharacters(mb_strtolower($product_data['design'])));
            $gender_string = str_replace(' ', '', $product_data['design']);

            switch ($gender_string) {
                case str_contains($gender_string, 'Unisex'):
                    $new_product->update_meta_data('gender', 'Unisex');
                    break;
                case str_contains($gender_string, 'Αγόρι'):
                    $new_product->update_meta_data('gender', 'Αγόρι');
                    break;
                case str_contains($gender_string, 'Κορίτσι'):
                    $new_product->update_meta_data('gender', 'Κορίτσι');
                    break;

                default:
                    $new_product->update_meta_data('gender', 'Unisex');
                    break;
            }
        }

        if (isset($product_data['barcode'])) {
            $new_product->update_meta_data('barcode', $product_data['barcode']);
        }
        if (isset($product_data['brand'])) {
            $new_product->update_meta_data('brand', $this->normalizeCharacters(mb_strtolower($product_data['brand'])));
        }
        if (isset($product_data['supplier'])) {
            $new_product->update_meta_data('supplier', $this->normalizeCharacters(mb_strtolower($product_data['supplier'])));
        }
        if (isset($product_data['style'])) {
            $new_product->update_meta_data('style', $this->normalizeCharacters(mb_strtolower($product_data['style'])));
        }
        if (isset($product_data['properties'])) {
            $new_product->update_meta_data('name_en', $this->normalizeCharacters($product_data['properties']));
        }
        if (isset($product_data['producttype'])) {
            $dimensions = $this->extractDimensions($product_data['producttype']);
            $new_product->set_length($dimensions['length']);
            $new_product->set_width($dimensions['width']);
            $new_product->set_height($dimensions['height']);
        }
        if (isset($product_data['_add_style'])) {
            $style_array = explode(',', $this->normalizeCharacters($product_data['_add_style']));
            $new_product->update_meta_data('style_filter', $style_array);
        }

        if (isset($product_data['**image_names'])) {
            $image_ids_array = array();
            $images_array = explode("\n", $product_data['**image_names']);
            $imageHandler = new ImageHandler();
            $image_ids_array = $imageHandler->assignImagesToProduct($images_array);
            if (count($image_ids_array) !== 0) {
                foreach ($image_ids_array as $image_id) {
                    $new_product->set_image_id($image_id);
                    break;
                }

                $new_product->set_gallery_image_ids($image_ids_array);
            }
        }

        // Save the product
        $new_product_id = $new_product->save();

        if (isset($product_data['_colorss']) && $product_data['_colorss']) {
            $attributeHandler = new AttributeHandler();
            $colors_array = explode('|', $product_data['_colors']);

            $product_type = 'variable';

            // $product_id = $new_product[0]->get_id();
            $attribute_id = $attributeHandler->productAttributeHandler('color', $colors_array);


            $new_product->set_attributes(array('color' => 'color'));

            foreach ($colors_array as $color) {

                $variation_data = array(
                    'attributes' => array(
                        'color' => $color,
                    ),
                    'regular_price' => $product_data['_colors'],
                    // 'sale_price' => '',
                    'stock_quantity' => 10,
                    'manage_stock' => true,
                );
                $variation_id = $attributeHandler->createOrUpdateVariation($new_product_id, $variation_data);
            }
        }

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

    private function updateProduct($product_data, $existing_product)
    {
        $product_categories = array();
        $product_additional_categories = array();
        $product_type = 'simple';

        if (isset($product_data['_colorss']) && $product_data['_colorss']) {
            $product_type = 'variable';
        }

        // if ($product_type === 'variable') {
        //     $existing_product = new WC_Product_Variable();
        // } else {
        //     $existing_product = new WC_Product_Simple();
        // }

        if (!empty($existing_product)) {
            // $existing_product = reset($existing_products);
            // error_log(print_r('$product_data 6', true));
            // error_log(print_r($existing_product, true));
            $existing_product->set_name($this->normalizeCharacters($product_data['name']));

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

            if (isset($product_data['stock'])) {
                $existing_product->set_stock_quantity($product_data['stock']->quantity);
                $existing_product->set_manage_stock(true);
            } else {
                $existing_product->set_stock_quantity(0);
            }

            $existing_product->set_price(str_replace(',', '.', $product_data['price']));
            $existing_product->set_regular_price(str_replace(',', '.', $product_data['price']));

            if (isset($product_data['*notes'])) {
                $existing_product->set_description($product_data['*notes']);
            }
            if (isset($product_data['*notes2'])) {
                $existing_product->update_meta_data('notes_en', $product_data['*notes2']);
            }

            if (isset($product_data['design'])) {
                // $gender_array = explode('|', $this->normalizeCharacters(mb_strtolower($product_data['design'])));
                $gender_string = str_replace(' ', '', $product_data['design']);

                switch ($gender_string) {
                    case str_contains($gender_string, 'Unisex'):
                        $existing_product->update_meta_data('gender', 'Unisex');
                        break;
                    case str_contains($gender_string, 'Αγόρι'):
                        $existing_product->update_meta_data('gender', 'Αγόρι');
                        break;
                    case str_contains($gender_string, 'Κορίτσι'):
                        $existing_product->update_meta_data('gender', 'Κορίτσι');
                        break;

                    default:
                        $existing_product->update_meta_data('gender', 'Unisex');
                        break;
                }
            }

            if (isset($product_data['barcode'])) {
                $existing_product->update_meta_data('barcode', $product_data['barcode']);
            }
            if (isset($product_data['brand'])) {
                $existing_product->update_meta_data('brand', $this->normalizeCharacters(mb_strtolower($product_data['brand'])));
            }
            if (isset($product_data['supplier'])) {
                $existing_product->update_meta_data('supplier', $this->normalizeCharacters(mb_strtolower($product_data['supplier'])));
            }
            if (isset($product_data['style'])) {
                $existing_product->update_meta_data('style', $this->normalizeCharacters(mb_strtolower($product_data['style'])));
            }
            if (isset($product_data['properties'])) {
                $existing_product->update_meta_data('name_en', $this->normalizeCharacters($product_data['properties']));
            }
            if (isset($product_data['_add_style'])) {
                $style_array = explode(',', $this->normalizeCharacters($product_data['_add_style']));
                $existing_product->update_meta_data('style_filter', $style_array);
            }
            if (isset($product_data['producttype'])) {
                $dimensions = $this->extractDimensions($product_data['producttype']);
                $existing_product->set_length($dimensions['length']);
                $existing_product->set_width($dimensions['width']);
                $existing_product->set_height($dimensions['height']);
            }

            if (isset($product_data['_colorss']) && $product_data['_colorss']) {
                $attributeHandler = new AttributeHandler();
                $colors_array = explode('|', $product_data['_colors']);

                $product_type = 'variable';

                $product_id = $existing_products[0]->get_id();
                $attribute_obj = $attributeHandler->productAttributeHandler($product_id, 'color', $colors_array);

                error_log(print_r('$attribute_obj 2', true));
                error_log(print_r($attribute_obj, true));
                $existing_product->set_attributes($attribute_obj);

                foreach ($colors_array as $color) {

                    $variation_data = array(
                        'attributes' => array(
                            'color' => $color,
                        ),
                        'regular_price' => $product_data['_colors'],
                        // 'sale_price' => '',
                        'stock_quantity' => 10,
                        'manage_stock' => true,
                    );
                    // $variation_id = $attributeHandler->createOrUpdateVariation($product_id, $variation_data);
                }
            }
            if (isset($product_data['**image_names'])) {
                $image_ids_array = array();
                $images_array = explode("\n", $product_data['**image_names']);
                $imageHandler = new ImageHandler();
                $image_ids_array = $imageHandler->assignImagesToProduct($images_array);
                if (count($image_ids_array) !== 0) {
                    foreach ($image_ids_array as $image_id) {
                        $existing_product->set_image_id($image_id);
                        break;
                    }

                    $existing_product->set_gallery_image_ids($image_ids_array);
                }
            }

            $product_id = wc_get_product_id_by_sku($product_data['code']);
            // $product_classname = WC_Product_Factory::get_product_classname($product_id, $product_type);

            // Get the new product object from the correct classname
            // $updated_product       = new $product_classname($product_id);

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

    public function normalizeCharacters($jsonString)
    {
        // error_log(print_r('$jsonString 1', true));
        // error_log(print_r($jsonString, true));
        $jsonString = str_replace("\r\n", ' ', $jsonString);
        // error_log(print_r('$jsonString 2', true));
        // error_log(print_r($jsonString, true));
        return $jsonString;
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
        // error_log(print_r('creating category', true));
        // error_log(print_r($category_data, true));
        // error_log(print_r('creating $parent_category', true));
        // error_log(print_r($parent_category, true));
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
