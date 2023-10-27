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
        // error_log(print_r('$product_data', true));
        // error_log(print_r($product_data, true));
        $existing_products = wc_get_products(array(
            'sku' => $product_data['code'],
        ));

        // error_log(print_r('$existing_products', true));
        // error_log(print_r($existing_products, true));

        if (!empty($existing_products)) {
            $this->updateProduct($product_data);
        } else {
            $this->createProduct($product_data);
        }
    }


    private function createProduct($product_data)
    {
        // error_log(print_r('inside createProduct', true));
        // error_log(print_r($product_data, true));
        $this->categoryCheck($product_data['category']);
    }

    private function updateProduct($product_data)
    {
    }

    private function categoryCheck($category_name) {
    
        $category = get_term_by('name', $category_name, 'product_cat');
    
        if ($category === false) {
            $category_args = array(
                'cat_name' => $category_name,
                'category_nicename' => sanitize_title($category_name),
            );
    
            $result = wp_insert_term($category_name, 'product_cat', $category_args);
    
            if (!is_wp_error($result)) {
                // Category created successfully
                return $result['term_id'];
            } else {
                // Error creating the category
                return false;
            }
        } else {
            // Category already exists, return its term ID
            return $category->term_id;
        }
    }
    

    private function createCategory($category_data)
    {
    }
}
