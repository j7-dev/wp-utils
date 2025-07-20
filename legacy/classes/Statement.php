<?php
/**
 * Statement class
 * 可以用來組織語句
 * 參考 WC 的 SqlQuery 類
 */

namespace J7\WpUtils\Classes;

if ( class_exists( 'Statement' ) ) {
	return;
}
/**
 * Statement class
 *
 * @deprecated \J7\WpHelpers\Arr::to_string()
 */
class Statement {

	/**
	 * Data store context used to pass to filters.
	 *
	 * @var string
	 */
	public $context;

	/**
	 * @var array<string, string|array<string>> 語句
	 */
	public $clauses = [];

	/**
	 * Constructor.
	 *
	 * @param string $context Optional context passed to filters. Default empty string.
	 */
	public function __construct( $context = '' ) {
		$this->context = $context;
	}

	/**
	 * 取得單一語句
	 *
	 * @param string $key   Key
	 * @return string|array
	 */
	public function get( $key ): string|array {
		return $this->clauses[ $key ] ?? '';
	}

	/**
	 * 新增語句
	 *
	 * @param string               $key   Key 如果重複會被蓋過
	 * @param string|array<string> $clauses clauses.
	 */
	public function add( $key, $clauses ): void {
		$this->clauses[ $key ] = $clauses;
	}

	/**
	 * 刪除語句
	 *
	 * @param string $key   Key
	 */
	public function delete( $key ): void {
		unset($this->clauses[ $key ]);
	}


	/**
	 * Get the full SQL statement.
	 *
	 * @param string $seperator 分隔符號
	 *
	 * @return string
	 */
	public function get_statement( string $seperator = '' ) {
		$clauses   = $this->clauses;
		$statement = '';
		foreach ($clauses as $key => $string_or_array_clause) {
			$statement .= is_array($string_or_array_clause) ? implode('', $string_or_array_clause) : $string_or_array_clause;
			if ($seperator) {
				$statement .= $seperator;
			}
		}
		return $statement;
	}
}
