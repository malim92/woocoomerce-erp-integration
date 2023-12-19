<!-- Include the Bootstrap CSS and JavaScript (you might want to enqueue these properly in WordPress) -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<?php



if (isset($_POST['mmh_submit'])) {

   if ($_POST['data_source'] == 'json') {
      $product_handler = new ProductHandler();
      $handleJson = $product_handler->handle_json_upload();
      return;
   }

   // $product_handler = new ProductHandler();
   // $handleJson = $product_handler->handle_json_upload();
   // error_log(print_r($handleJson, true));

   // $api_url = sanitize_text_field($_POST['mmh_api_url']);

   $update_frequency = sanitize_text_field($_POST['update_frequency']);
   $is_active = isset($_POST['activate_cron_mmh']) ? 1 : 0;

   if (get_option('update_frequency') !== false) {
      update_option('update_frequency', $update_frequency);
   } else {
      add_option('update_frequency', $update_frequency);
   }

   if (get_option('activate_cron_mmh') !== false) {
      update_option('activate_cron_mmh', $is_active);
   } else {
      add_option('activate_cron_mmh', $is_active);
   }
   if ($is_active) {

      if (get_option('activate_cron_mmh') !== false) {

         if (!wp_next_scheduled('mmh_product_import_cron')) {
            wp_schedule_event(time(), 'every_15_minutes', 'mmh_product_import_cron');
         }
         if (!wp_next_scheduled('mmh_stock_import_cron')) {
            wp_schedule_event(time(), 'every_5_minutes', 'mmh_stock_import_cron');
         }
      }
      // wp_schedule_event(time(), 'hourly', 'mmh_product_import_cron');

      // Hook the product import function to the scheduled cron event

   } else {
      wp_clear_scheduled_hook('mmh_product_import_cron');
   }

   // if (get_option('mmh_api_url') !== false) {
   //    update_option('mmh_api_url', $api_url);
   // } else {
   //    add_option('mmh_api_url', $api_url);
   // }
}



?>
<div class="container">
   <h2>MMH Integration plugin</h2>
   <div class="row">
      <form method="post" action="" enctype="multipart/form-data">
         <label for="activate_cron_mmh">Activate import:</label>
         <input type="checkbox" id="activate_cron_mmh" name="activate_cron_mmh" <?php if (get_option('activate_cron_mmh') == 1) echo 'checked'  ?>><br>

         <label for="data_source">Data Source:</label>
         <select id="data_source" name="data_source">
            <option value="api" <?php selected(get_option(' data_source'), 'api', false) ?>>API</option>
            <option value="json" <?php selected(get_option('data_source'), 'json', false) ?>>JSON File</option>
         </select><br>
         <div id="api_url_input">
            <label for="api_url">API URL:</label>
            <input type="text" id="api_url" name="api_url" value="<?php esc_attr(get_option('mmh_api_url')) ?>">
         </div>
         <div id="json_upload_input" style="display:none;">
            <label for="json_upload">Upload JSON File:</label>
            <input type="file" id="json_upload" name="json_upload">
         </div>
         <label for="update_frequency">Update Frequency:</label>
         <select id="update_frequency" name="update_frequency">
            <option value="hourly" <?php selected(get_option('update_frequency'), 'hourly', false) ?>>Hourly</option>
            <option value="daily" <?php selected(get_option('update_frequency'), 'daily', false) ?>>Daily</option>
         </select><br>

         <input type="submit" name="mmh_submit" class="button button-primary" value="Save Settings">
      </form>
   </div>
</div>