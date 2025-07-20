<?php

declare (strict_types = 1);

namespace J7\WpAbstracts;

/**
 * ParseCSV 解析 CSV
 *
 * @example
 * ```php
 * $csv = new ParseCSV($file);
 * $data = $csv->parse();
 * ```
 * */
abstract class ParseCSV {

	/** @var resource 檔案資源 */
	protected $handle;

	/**
	 * 建構函式
	 *
	 * @param array $file 上傳的 CSV 檔
	 * @return void
	 * @throws \Exception 如果文件上傳失敗或不存在, 或無法打開文件.
	 */
	public function __construct(
		/** @var array $file 上傳的 CSV 檔 */
		protected array $file
	) {
		// 檢查文件是否存在
		if (!isset($file['tmp_name'])) {
			throw new \Exception('文件上傳失敗或不存在');
		}

		// 打開文件
		$handle = fopen($file['tmp_name'], 'r');
		if ($handle === false) {
			throw new \Exception('無法打開文件 ' . $file['tmp_name']);
		}
		$this->handle = $handle;
	}

	/**
	 * 解析上傳的 CSV 檔
	 *
	 * @return array 解析後的 CSV 數據
	 * @throws \Exception 如果文件上傳失敗或不存在, 或無法打開文件.
	 */
	public function parse(): array {

		$data = [];
		$row  = 0;

		// 逐行讀取 CSV
		while (( $fileop = fgetcsv($this->handle, 0, ',') ) !== false) {
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
		fclose($this->handle);

		return $data;
	}
}
