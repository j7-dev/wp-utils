<?php

namespace J7\WpInterfaces;

use J7\WpAbstracts\DTO;

/**
 * CRUD 操作介面
 * 定義所有 CRUD 類別必須實作的標準方法
 */
interface CrudInterface {

	/**
	 * 創建新記錄
	 *
	 * @param array<string, mixed> $data 要創建的資料
	 * @return DTO|false 成功返回 DTO 實例，失敗返回 false
	 */
	public function create( array $data ): DTO|false;

	/**
	 * 根據 ID 查找記錄
	 *
	 * @param int $id 主鍵 ID
	 * @return DTO|null 找到返回 DTO 實例，未找到返回 null
	 */
	public function find( int $id ): ?DTO;

	/**
	 * 根據條件查找記錄
	 *
	 * @param array<string, mixed> $criteria 查詢條件
	 * @return DTO[] 符合條件的 DTO 陣列
	 */
	public function findBy( array $criteria ): array;

	/**
	 * 更新記錄
	 *
	 * @param int                  $id 主鍵 ID
	 * @param array<string, mixed> $data 要更新的資料
	 * @return bool 更新是否成功
	 */
	public function update( int $id, array $data ): bool;

	/**
	 * 刪除記錄
	 *
	 * @param int $id 主鍵 ID
	 * @return bool 刪除是否成功
	 */
	public function delete( int $id ): bool;

	/**
	 * 計算符合條件的記錄數
	 *
	 * @param array<string, mixed> $criteria 查詢條件
	 * @return int 記錄數
	 */
	public function count( array $criteria = [] ): int;

	/**
	 * 取得資料表名稱
	 *
	 * @return string 完整的資料表名稱（包含前綴）
	 */
	public function getTableName(): string;

	/**
	 * 取得主鍵欄位名稱
	 *
	 * @return string 主鍵欄位名稱
	 */
	public function getPrimaryKey(): string;

	/**
	 * 取得對應的 DTO 類別名稱
	 *
	 * @return string DTO 類別名稱
	 */
	public function getDTOClass(): string;

	/**
	 * 取得可填充的欄位清單
	 *
	 * @return array<string> 可填充的欄位名稱陣列
	 */
	public function getFillable(): array;
}
