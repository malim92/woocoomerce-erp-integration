console.log('her');
jQuery(document).ready(function($) {
    $('#data_source').on('change', function() {
        var selectedValue = $(this).val();

        if (selectedValue === 'api') {
            $('#api_url_input').show();
            $('#json_upload_input').hide();
        } else if (selectedValue === 'json') {
            $('#api_url_input').hide();
            $('#json_upload_input').show();
        }
    });
});
