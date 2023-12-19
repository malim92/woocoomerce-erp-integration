<?php


class ImageHandler
{
    private static $watermark_img_path = 'https://mama.com2go.co/wp-content/uploads/2023/12/2023-11-28_12-45-58-862.png';

    public function assignImagesToProduct($images_array)
    {
        $image_folder = ABSPATH . 'mmh/';
        // $image_folder = 'C:\wamp64\www\mamastars\mmh\\';

        $image_ids_array = array();

        foreach ($images_array as $image) {
            $image_path = $image_folder . $image;
            if (file_exists($image_path)) {
                // $image_watermarked = $this->addWaterMark($image_path, $image);
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

    public function addWaterMark($image_path, $imgName)
    {
        // Load original image
        $originalImage = imagecreatefromjpeg($image_path);

        // Load watermark image
        $watermarkImage = imagecreatefrompng(self::$watermark_img_path);

        // Get dimensions of the original image
        $originalWidth = imagesx($originalImage);
        $originalHeight = imagesy($originalImage);

        // Resize the watermark image (adjust the scale factor as needed)
        $scaleFactor = 1; // Change this value to control the size of the watermark
        $resizedWidth = $scaleFactor * imagesx($watermarkImage);
        $resizedHeight = $scaleFactor * imagesy($watermarkImage);

        $resizedWatermark = imagescale($watermarkImage, $resizedWidth, $resizedHeight);


        // Calculate the position to place the watermark (e.g., bottom-right corner)
        $positionX = ($originalWidth - $resizedWidth) / 2;
        $positionY = ($originalHeight - $resizedHeight) / 2;

        // Overlay the watermark onto the original image
        imagecopy($originalImage, $resizedWatermark, $positionX, $positionY, 0, 0, $resizedWidth, $resizedHeight);

        $watermarked_img_full_path = $_SERVER['DOCUMENT_ROOT'] . '/wp-content/watermarked/' . $imgName;
        $watermarked_image = imagejpeg($originalImage, $watermarked_img_full_path);
        // Free up memory
        imagedestroy($originalImage);
        imagedestroy($watermarkImage);

        if ($watermarked_image)
            return $watermarked_img_full_path;
        else return $image_path;
    }
}
