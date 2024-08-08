<?php
/**
 * File 處理檔案
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'File' ) ) {
	return;
}

/**
 * File class
 */
abstract class File {

	/**
	 * 解析上傳的 CSV 檔
	 *
	 * @param array $file 上傳的 CSV 檔
	 * @return array 解析後的 CSV 數據
	 */
	public static function parse_uploaded_csv( $file ): array {
		// 檢查文件是否存在
		if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
			return '文件上傳失敗或不存在';
		}

		// 打開文件
		$handle = fopen($file['tmp_name'], 'r');
		if ($handle === false) {
			return '無法打開文件';
		}

		$data = [];
		$row  = 0;

		// 逐行讀取 CSV
		while (( $fileop = fgetcsv($handle, 0, ',') ) !== false) {
			$num = count($fileop);
			++$row;

			// 跳過第一行如果它是標題
			if ($row == 1) {
				continue;
			}

			// 處理每一列數據
			$item = [];
			for ($c=0; $c < $num; $c++) {
				$item[] = $fileop[ $c ];
			}
			$data[] = $item;
		}

		// 關閉文件
		fclose($handle);

		return $data;
	}
}
