<?php

namespace J7\WpAbstracts;

use J7\WpInterfaces\CrudInterface;
use J7\WpUtils\Classes\WC;

/**
 * BaseCRUD 抽象類
 * 提供通用的 CRUD 操作功能，支援查詢建構器和資料驗證
 */
abstract class BaseCRUD implements CrudInterface {

	/** @var \wpdb WordPress 資料庫物件 */
	protected \wpdb $wpdb;

	/** @var array<string, mixed> 查詢建構器的 WHERE 條件 */
	protected array $where_conditions = [];

	/** @var array<string, string> 查詢建構器的 ORDER BY 條件 */
	protected array $order_conditions = [];

	/** @var int|null 查詢建構器的 LIMIT */
	protected ?int $limit = null;

	/** @var int|null 查詢建構器的 OFFSET */
	protected ?int $offset = null;

	/**
	 * 建構函式
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
	}

	/**
	 * 創建新記錄
	 *
	 * @param array<string, mixed> $data 要創建的資料
	 * @return DTO|false 成功返回 DTO 實例，失敗返回 false
	 */
	public function create( array $data ): DTO|false {
		// 過濾可填充的欄位
		$filtered_data = $this->filterFillableData( $data );

		// 驗證資料
		if ( ! $this->validateData( $filtered_data ) ) {
			return false;
		}

		// 添加時間戳
		$filtered_data = $this->addTimestamps( $filtered_data );

		// 執行插入
		$result = $this->wpdb->insert(
			$this->getTableName(),
			$filtered_data,
			$this->getDataFormat( $filtered_data )
		);

		if ( ! $result ) {
			$this->logError( '創建記錄失敗', $this->wpdb->last_error );
			return false;
		}

		// 取得新插入的 ID
		$id = $this->wpdb->insert_id;

		// 返回新創建的記錄
		return $this->find( $id );
	}

	/**
	 * 根據 ID 查找記錄
	 *
	 * @param int $id 主鍵 ID
	 * @return DTO|null 找到返回 DTO 實例，未找到返回 null
	 */
	public function find( int $id ): ?DTO {
		$record = $this->wpdb->get_row(
			$this->wpdb->prepare(
				"SELECT * FROM {$this->getTableName()} WHERE {$this->getPrimaryKey()} = %d",
				$id
			)
		);

		return $record ? $this->createDTO( $record ) : null;
	}

	/**
	 * 根據條件查找記錄
	 *
	 * @param array<string, mixed> $criteria 查詢條件
	 * @return DTO[] 符合條件的 DTO 陣列
	 */
	public function findBy( array $criteria ): array {
		$sql = $this->buildSelectQuery( $criteria );

		$records = $this->wpdb->get_results( $sql );

		return array_map( [ $this, 'createDTO' ], $records );
	}

	/**
	 * 更新記錄
	 *
	 * @param int                  $id 主鍵 ID
	 * @param array<string, mixed> $data 要更新的資料
	 * @return bool 更新是否成功
	 */
	public function update( int $id, array $data ): bool {
		// 過濾可填充的欄位
		$filtered_data = $this->filterFillableData( $data );

		// 驗證資料
		if ( ! $this->validateData( $filtered_data, $id ) ) {
			return false;
		}

		// 添加更新時間戳
		$filtered_data = $this->addUpdateTimestamp( $filtered_data );

		// 執行更新
		$result = $this->wpdb->update(
			$this->getTableName(),
			$filtered_data,
			[ $this->getPrimaryKey() => $id ],
			$this->getDataFormat( $filtered_data ),
			[ '%d' ]
		);

		if ( false === $result ) {
			$this->logError( '更新記錄失敗', $this->wpdb->last_error );
			return false;
		}

		return true;
	}

	/**
	 * 刪除記錄
	 *
	 * @param int $id 主鍵 ID
	 * @return bool 刪除是否成功
	 */
	public function delete( int $id ): bool {
		$result = $this->wpdb->delete(
			$this->getTableName(),
			[ $this->getPrimaryKey() => $id ],
			[ '%d' ]
		);

		if ( false === $result ) {
			$this->logError( '刪除記錄失敗', $this->wpdb->last_error );
			return false;
		}

		return true;
	}

	/**
	 * 計算符合條件的記錄數
	 *
	 * @param array<string, mixed> $criteria 查詢條件
	 * @return int 記錄數
	 */
	public function count( array $criteria = [] ): int {
		$sql = $this->buildCountQuery( $criteria );

		return (int) $this->wpdb->get_var( $sql );
	}

	/**
	 * 查詢建構器：添加 WHERE 條件
	 *
	 * @param string $column 欄位名稱
	 * @param mixed  $value 值
	 * @param string $operator 操作符
	 * @return static 返回自己以支援鏈式調用
	 */
	public function where( string $column, $value, string $operator = '=' ): static {
		$this->where_conditions[] = [
			'column'   => $column,
			'operator' => $operator,
			'value'    => $value,
		];

		return $this;
	}

	/**
	 * 查詢建構器：添加 ORDER BY 條件
	 *
	 * @param string $column 欄位名稱
	 * @param string $direction 排序方向
	 * @return static 返回自己以支援鏈式調用
	 */
	public function orderBy( string $column, string $direction = 'ASC' ): static {
		$this->order_conditions[ $column ] = strtoupper( $direction );

		return $this;
	}

	/**
	 * 查詢建構器：設定 LIMIT
	 *
	 * @param int $limit 限制數量
	 * @return static 返回自己以支援鏈式調用
	 */
	public function limit( int $limit ): static {
		$this->limit = $limit;

		return $this;
	}

	/**
	 * 查詢建構器：設定 OFFSET
	 *
	 * @param int $offset 偏移量
	 * @return static 返回自己以支援鏈式調用
	 */
	public function offset( int $offset ): static {
		$this->offset = $offset;

		return $this;
	}

	/**
	 * 查詢建構器：執行查詢並取得結果
	 *
	 * @return DTO[] 查詢結果的 DTO 陣列
	 */
	public function get(): array {
		$sql = $this->buildQueryFromBuilder();

		$records = $this->wpdb->get_results( $sql );

		// 重置查詢建構器
		$this->resetQueryBuilder();

		return array_map( [ $this, 'createDTO' ], $records );
	}

	/**
	 * 取得完整的資料表名稱（包含前綴）
	 *
	 * @return string 完整的資料表名稱
	 */
	public function getTableName(): string {
		return $this->wpdb->prefix . $this->getBaseTableName();
	}

	// 抽象方法，子類必須實作

	/**
	 * 取得基礎資料表名稱（不含前綴）
	 *
	 * @return string 基礎資料表名稱
	 */
	abstract protected function getBaseTableName(): string;

	/**
	 * 取得主鍵欄位名稱
	 *
	 * @return string 主鍵欄位名稱
	 */
	abstract public function getPrimaryKey(): string;

	/**
	 * 取得對應的 DTO 類別名稱
	 *
	 * @return string DTO 類別名稱
	 */
	abstract public function getDTOClass(): string;

	/**
	 * 取得可填充的欄位清單
	 *
	 * @return array<string> 可填充的欄位名稱陣列
	 */
	abstract public function getFillable(): array;

	/**
	 * 創建 DTO 實例
	 *
	 * @param object $record 資料庫記錄
	 * @return DTO DTO 實例
	 */
	abstract protected function createDTO( object $record ): DTO;

	// 受保護的輔助方法

	/**
	 * 過濾可填充的資料
	 *
	 * @param array<string, mixed> $data 原始資料
	 * @return array<string, mixed> 過濾後的資料
	 */
	protected function filterFillableData( array $data ): array {
		$fillable = $this->getFillable();

		return array_intersect_key( $data, array_flip( $fillable ) );
	}

	/**
	 * 驗證資料
	 *
	 * @param array<string, mixed> $data 要驗證的資料
	 * @param int|null             $id 更新時的記錄 ID
	 * @return bool 驗證是否通過
	 */
	protected function validateData( array $data, ?int $id = null ): bool {
		// 子類可以覆寫此方法來添加自訂驗證邏輯
		return true;
	}

	/**
	 * 添加創建和更新時間戳
	 *
	 * @param array<string, mixed> $data 資料
	 * @return array<string, mixed> 添加時間戳後的資料
	 */
	protected function addTimestamps( array $data ): array {
		$now = current_time( 'mysql' );

		if ( in_array( 'created_at', $this->getFillable(), true ) ) {
			$data['created_at'] = $now;
		}

		if ( in_array( 'updated_at', $this->getFillable(), true ) ) {
			$data['updated_at'] = $now;
		}

		return $data;
	}

	/**
	 * 添加更新時間戳
	 *
	 * @param array<string, mixed> $data 資料
	 * @return array<string, mixed> 添加時間戳後的資料
	 */
	protected function addUpdateTimestamp( array $data ): array {
		if ( in_array( 'updated_at', $this->getFillable(), true ) ) {
			$data['updated_at'] = current_time( 'mysql' );
		}

		return $data;
	}

	/**
	 * 取得資料格式陣列
	 *
	 * @param array<string, mixed> $data 資料
	 * @return array<string> 格式陣列
	 */
	protected function getDataFormat( array $data ): array {
		$format = [];

		foreach ( $data as $value ) {
			if ( is_int( $value ) ) {
				$format[] = '%d';
			} elseif ( is_float( $value ) ) {
				$format[] = '%f';
			} else {
				$format[] = '%s';
			}
		}

		return $format;
	}

	/**
	 * 建構 SELECT 查詢
	 *
	 * @param array<string, mixed> $criteria 查詢條件
	 * @return string SQL 查詢語句
	 */
	protected function buildSelectQuery( array $criteria ): string {
		$sql = "SELECT * FROM {$this->getTableName()}";

		if ( ! empty( $criteria ) ) {
			$where_clauses = [];
			foreach ( $criteria as $column => $value ) {
				$where_clauses[] = $this->wpdb->prepare( "{$column} = %s", $value );
			}
			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		return $sql;
	}

	/**
	 * 建構 COUNT 查詢
	 *
	 * @param array<string, mixed> $criteria 查詢條件
	 * @return string SQL 查詢語句
	 */
	protected function buildCountQuery( array $criteria ): string {
		$sql = "SELECT COUNT(*) FROM {$this->getTableName()}";

		if ( ! empty( $criteria ) ) {
			$where_clauses = [];
			foreach ( $criteria as $column => $value ) {
				$where_clauses[] = $this->wpdb->prepare( "{$column} = %s", $value );
			}
			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		return $sql;
	}

	/**
	 * 從查詢建構器建構 SQL 查詢
	 *
	 * @return string SQL 查詢語句
	 */
	protected function buildQueryFromBuilder(): string {
		$sql = "SELECT * FROM {$this->getTableName()}";

		// 建構 WHERE 子句
		if ( ! empty( $this->where_conditions ) ) {
			$where_clauses = [];
			foreach ( $this->where_conditions as $condition ) {
				$where_clauses[] = $this->wpdb->prepare(
					"{$condition['column']} {$condition['operator']} %s",
					$condition['value']
				);
			}
			$sql .= ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		// 建構 ORDER BY 子句
		if ( ! empty( $this->order_conditions ) ) {
			$order_clauses = [];
			foreach ( $this->order_conditions as $column => $direction ) {
				$order_clauses[] = "{$column} {$direction}";
			}
			$sql .= ' ORDER BY ' . implode( ', ', $order_clauses );
		}

		// 建構 LIMIT 子句
		if ( null !== $this->limit ) {
			$sql .= $this->wpdb->prepare( ' LIMIT %d', $this->limit );

			if ( null !== $this->offset ) {
				$sql .= $this->wpdb->prepare( ' OFFSET %d', $this->offset );
			}
		}

		return $sql;
	}

	/**
	 * 重置查詢建構器
	 *
	 * @return void
	 */
	protected function resetQueryBuilder(): void {
		$this->where_conditions = [];
		$this->order_conditions = [];
		$this->limit            = null;
		$this->offset           = null;
	}

	/**
	 * 記錄錯誤
	 *
	 * @param string $message 錯誤訊息
	 * @param string $details 錯誤詳情
	 * @return void
	 */
	protected function logError( string $message, string $details = '' ): void {
		$log_message = $message;
		if ( ! empty( $details ) ) {
			$log_message .= ': ' . $details;
		}

		WC::logger( $log_message, 'error', [], 'crud' );
	}
}
