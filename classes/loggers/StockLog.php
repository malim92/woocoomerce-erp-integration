<?php 
	class StockLog {
		
		public function createLog($log_data = []) {
			if (!empty($log_data)) {
				// WP Globals
				global $wpdb;

				// Customer Table
				$logTable = 'mmh_stock_log';
				$log_data['log_time'] = date('Y-m-d H:i:s');
				$wpdb->insert( $logTable, $log_data);
			}
		}
	}
?>