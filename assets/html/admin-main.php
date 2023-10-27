<!-- Include the Bootstrap CSS and JavaScript (you might want to enqueue these properly in WordPress) -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

<?php



if (isset($_POST['mmh_submit'])) {

   $product_handler = new ProductHandler();
   $handleJson = $product_handler->handle_json_upload();
   error_log(print_r($handleJson, true));

   $api_url = sanitize_text_field($_POST['api_url']);

   $update_frequency = sanitize_text_field($_POST['update_frequency']);
   $is_active = isset($_POST['activate_cron']) ? 1 : 0;
}

?>
<div class="container">
   <h2>MMH Integration plugin</h2>
   <div class="row">
      <form method="post" action="" enctype="multipart/form-data">
         <label for="activate_cron">Activate import:</label>
         <input type="checkbox" id="activate_cron" name="activate_cron" <?php checked(get_option('activate_cron'), 1, false)  ?>><br>

         <label for="data_source">Data Source:</label>
         <select id="data_source" name="data_source">
            <option value="api" <?php selected(get_option(' data_source'), 'api', false) ?>>API</option>
            <option value="json" <?php selected(get_option('data_source'), 'json', false) ?>>JSON File</option>
         </select><br>
         <div id="api_url_input">
            <label for="api_url">API URL:</label>
            <input type="text" id="api_url" name="api_url" value="<?php esc_attr(get_option('api_url')) ?>">
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