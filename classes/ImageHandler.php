<?php

class ImageHandler
{

    public function assignImagesToProduct($images_array)
    {
        // Define the folder where the product images are located
        $image_folder = ABSPATH . 'mmh/';

        $image_ids_array = array();
        foreach ($images_array as $image) {
            $image_path = $image_folder . $image;
            error_log(print_r('$image_path here', true));
            error_log(print_r($image_path, true));
            if (file_exists($image_path)) {
                error_log(print_r('file_exists here', true));
                $file = array(
                    'name'     => basename($image_path),
                    'tmp_name' => $image_path
                );

                $attachment_id = media_handle_sideload($file, 0);
                array_push($image_ids_array, $attachment_id);
            }
        }
        error_log(print_r('$image_ids_array here', true));
        error_log(print_r($image_ids_array, true));
        return $image_ids_array;
    }
}
