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
            }
            else error_log(print_r('not json', true));
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

    error_log(print_r('$existing_products', true));
    error_log(print_r($existing_products, true));

    if (!empty($existing_products)) {
        $this->updateProduct($product_data);
    } else {
        $this->createProduct($product_data);
    }
}


    private function createProduct($product_data)
    {
        // error_log(print_r('inside createProduct', true));
    }

    private function updateProduct($product_data)
    {
        
    }


}
