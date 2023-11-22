<?php


class ImageHandler
{
    private static $watermark_img_path = 'https://mama.com2go.co/wp-content/uploads/2023/11/viber_image_2023-11-20_17-05-14-897.png';

    public function assignImagesToProduct($images_array)
    {
        $image_folder = ABSPATH . 'mmh/';
        // $image_folder = 'C:\wamp64\www\mamastars\mmh\\';

        $image_ids_array = array();

        foreach ($images_array as $image) {
            $image_path = $image_folder . $image;

            if (file_exists($image_path)) {

                $image_watermarked = $this->addWaterMark($image_path, $image);
                $file = array(
                    'name'     => basename($image_watermarked),
                    'tmp_name' => $image_watermarked
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

        // Get dimensions of the watermark image
        $watermarkWidth = imagesx($watermarkImage);
        $watermarkHeight = imagesy($watermarkImage);

        // Calculate the position to place the watermark (e.g., bottom-right corner)
        $positionX = ($originalWidth - $watermarkWidth) / 2;
        $positionY = ($originalHeight - $watermarkHeight) / 2;

        // Overlay the watermark onto the original image
        imagecopy($originalImage, $watermarkImage, $positionX, $positionY, 0, 0, $watermarkWidth, $watermarkHeight);

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
