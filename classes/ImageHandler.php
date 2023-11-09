<?php

class ImageHandler
{

    public function assignImagesToProduct($images_array)
    {
        $image_folder = ABSPATH . 'mmh/';
        // $image_folder = 'C:\wamp64\www\mamastars\mmh\\';

        $image_ids_array = array();

        foreach ($images_array as $image) {
            $image_path = $image_folder . $image;

            if (file_exists($image_path)) {
                $file = array(
                    'name'     => basename($image_path),
                    'tmp_name' => $image_path
                );

                $attachment_id = media_handle_sideload($file, 0);
                array_push($image_ids_array, $attachment_id);
            }
        }
        return $image_ids_array;
    }
}
