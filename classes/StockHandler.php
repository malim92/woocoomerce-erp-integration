<?php
class StockHandler extends StockLog
{
    public function fetchStockData($dynamicDate, $lastHour)
    {


        $url = 'http://185.106.103.114:8080/$TableGetView?system=pos&file=itemloc&report=web_stock&compact=true&company=episkopou&driving=@modify_stamp&from=' . $lastHour . '&to=' . $dynamicDate;

        $zeroStockUrl = 'http://185.106.103.114:8080/$TableGetView?system=pos&file=itemloc&report=web_zero&compact=true&company=episkopou&driving=@modify_stamp&from=' . $lastHour . '&to=' . $dynamicDate;
        $url = preg_replace('/\s+/', '%20', $url);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 125);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL certificate verification (not recommended for production)
        $data = curl_exec($ch);
        curl_close($ch);
        $normalStockArray = json_decode($data, true);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $zeroStockUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 125);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL certificate verification (not recommended for production)
        $data = curl_exec($ch);
        curl_close($ch);
        $zeroStockArray = json_decode($data, true);

        $stockArray = array_merge($normalStockArray, $zeroStockArray);

        if (!$stockArray) {
            $this->createLog([
                'sku' => null,
                'status' => 'empty stock array',
                'msg' => 'No stock to update',
            ]);
            return;
        }
        return $stockArray;
    }

    public function StockUpdate($dynamicDate, $lastHour)
    {
        $stockData = $this->fetchStockData($dynamicDate, $lastHour);
        $ProductHandler = new ProductHandler();
        foreach ($stockData as $stockItem) {

            $itemCode = $stockItem->{'item.code'};
            $_product_id = wc_get_product_id_by_sku($ProductHandler->normalizeCharacters($itemCode));
            if ($_product_id == 0)
                $_product_id = wc_get_product_id_by_sku($_product_id);
            if ($_product_id > 0) {

                $existing_product = wc_get_product($_product_id);

                if (isset($stockItem->quantity)) {
                    $existing_product->set_stock_quantity($stockItem->quantity);
                    $existing_product->set_manage_stock(true);
                } else {
                    $existing_product->set_stock_quantity(0);
                    $existing_product->set_stock_status('outofstock');
                }
                $existing_product->save();
                $this->createLog([
                    'sku' => $itemCode,
                    'status' => 'Success',
                    'msg' => 'Stock updated successfully',
                ]);
            }
            if (is_wp_error($existing_product)) {
                $error_string = $existing_product->get_error_message();
                $this->createLog([
                    'sku' => $itemCode,
                    'status' => 'Success',
                    'msg' => $error_string,
                ]);
            }
        }
    }

    public function fetchAllStockData(){
        $url = 'http://185.106.103.114:8080/$TableGetView?system=pos&file=itemloc&report=web_stock&compact=true&company=episkopou';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 125);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL certificate verification (not recommended for production)
        $data = curl_exec($ch);
        curl_close($ch);
        $normalStockArray = json_decode($data, true);

        return $normalStockArray;
    }
}
