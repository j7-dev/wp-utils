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
 *
 * @deprecated 改用 J7\WpAbstracts\ParseCSV
 */
abstract class File {

	/**
	 * 解析上傳的 CSV 檔
	 *
	 * @deprecated 改用 J7\WpAbstracts\ParseCSV
	 *
	 * @param array $file 上傳的 CSV 檔
	 * @return array 解析後的 CSV 數據
	 * @throws \Exception 如果文件上傳失敗或不存在, 或無法打開文件.
	 */
	public static function parse_csv( $file ): array {
		return ( new \J7\WpAbstracts\ParseCSV($file) )->parse();
	}


	/**
	 * 以 streaming 方式解析 CSV 檔案
	 *
	 * @deprecated 改用 J7\WpAbstracts\ParseCSV
	 *
	 * @param array $file 上傳的 CSV 檔案
	 * @param int   $batch 批次，從 0 開始
	 * @param int   $batch_size 每批次讀取的行數
	 * @return array 解析後的 CSV 資料
	 * @throws \Exception 如果文件上傳失敗或不存在, 或無法打開文件.
	 */
	public static function parse_csv_streaming( $file, $batch = 0, $batch_size = 1000 ): array {
		// 檢查文件是否存在
		if (!isset($file['tmp_name'])) {
			throw new \Exception('文件上傳失敗或不存在');
		}

		// 打開文件
		$handle = fopen($file['tmp_name'], 'r');
		if ($handle === false) {
			throw new \Exception('無法打開文件');
		}

		$offset    = $batch * $batch_size;
		$data      = [];
		$row       = 0;
		$start_row = $offset === 0 ? 1 : $offset; // 加 1 是為了跳過標題行
		$end_row   = ( $batch + 1 ) * $batch_size;

		// 逐行讀取 CSV
		while (( $fileop = fgetcsv($handle, 0, ',') ) !== false) {
			++$row;

			// 跳過不在範圍內的行
			if ($row <= $start_row || $row > $end_row) {
				continue;
			}

			// 處理每一列數據
			$data[] = $fileop;

			// 如果已讀取到指定的批次大小,則停止讀取
			if ($row >= $end_row) {
				break;
			}
		}

		// 關閉文件
		fclose($handle);

		return $data;
	}

	/**
	 * 用 $attachment_id 取得附件檔案
	 *
	 * @param int $attachment_id 附件 ID
	 * @return array{name: string, tmp_name: string, error: int, size: int} 附件檔案
	 */
	public static function get_file_by_id( int $attachment_id ): array {
		$file_path = \get_attached_file($attachment_id);

		$file = [
			'name'     => basename($file_path),
			'tmp_name' => $file_path,
			'error'    => 0,
			'size'     => filesize($file_path),
		];

		return $file;
	}
}
