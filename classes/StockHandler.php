<?php
class StockHandler extends MMH_Sync_Log
{
    public function fetchStockData($fromDate, $toDate)
    {
        //http://185.106.103.114:8080/$TableGetView?system=pos&file=itemloc&report=web_stock&compact=true&company=episkopou&driving=@modify_stamp&from=2023-12-13 08:00&to=2023-12-16 11:00
        $fromDate = '2023-12-13 08:00';
        $toDate = '2023-12-16 11:00';
        $url = 'http://185.106.103.114:8080/$TableGetView?system=pos&file=itemloc&report=web_stock&compact=true&company=episkopou&driving=@modify_stamp&from=' . $fromDate . '&to=' . $toDate;

        $args = array(
            'timeout'     => 155,
            'redirection' => 10,
            'httpversion' => '1.0',
            'blocking'    => true,
            'body'        => null,
            'compress'    => false,
            'decompress'  => true,
            'sslverify'   => false,
            'stream'      => false,
            'filename'    => null
        );
        
        $results = json_decode(wp_remote_retrieve_body(wp_remote_get($url, $args)));
        return $results;
    }

    public function StockUpdate($fromDate, $toDate)
    {
        $stockData = $this->fetchStockData($fromDate, $toDate);
        $ProductHandler = new ProductHandler();
        foreach ($stockData as $stockItem) {

            $itemCode = $stockItem->{'item.code'};
            $_product_id = wc_get_product_id_by_sku($ProductHandler->normalizeCharacters($itemCode));
            if ($_product_id == 0)
                $_product_id = wc_get_product_id_by_sku($_product_id);
            if ($_product_id > 0) {
                
                $existing_product = wc_get_product($_product_id);

                $existing_product->set_stock_quantity($stockItem->quantity);
                $existing_product->set_manage_stock(true);
                $existing_product->save();
            }
        }
    }
}
