<?php

require_once __DIR__ . '/../src/helpers/Arr.php';

use J7\WpHelpers\Arr;

// 範例資料
$users = [
	[
		'name'   => '張三',
		'age'    => 25,
		'active' => true,
	],
	[
		'name'   => '李四',
		'age'    => 30,
		'active' => false,
	],
	[
		'name'   => '王五',
		'age'    => 35,
		'active' => true,
	],
	[
		'name'   => '趙六',
		'age'    => 28,
		'active' => true,
	],
];

$numbers = [ 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 ];

echo "=== Arr 類別使用範例 ===\n\n";

// 1. 創建 Arr 實例
$arr_users   = new Arr( $users );
$arr_numbers = Arr::create( $numbers ); // 靜態建立方法

// 2. some() - 檢查是否有元素滿足條件
echo "2. some() 範例：\n";
$has_adult = $arr_users->some( fn( $user ) => $user['age'] >= 30 );
echo '是否有用戶年齡 >= 30: ' . ( $has_adult ? '是' : '否' ) . "\n";

$has_even = $arr_numbers->some( fn( $num ) => $num % 2 === 0 );
echo '是否有偶數: ' . ( $has_even ? '是' : '否' ) . "\n\n";

// 3. every() - 檢查所有元素是否都滿足條件
echo "3. every() 範例：\n";
$all_adults = $arr_users->every( fn( $user ) => $user['age'] >= 18 );
echo '是否所有用戶都是成年人: ' . ( $all_adults ? '是' : '否' ) . "\n";

$all_positive = $arr_numbers->every( fn( $num ) => $num > 0 );
echo '是否所有數字都是正數: ' . ( $all_positive ? '是' : '否' ) . "\n\n";

// 4. find() - 找到第一個滿足條件的元素
echo "4. find() 範例：\n";
$first_inactive = $arr_users->find( fn( $user ) => ! $user['active'] );
echo '第一個非活躍用戶: ' . ( $first_inactive ? $first_inactive['name'] : '無' ) . "\n";

$first_big_number = $arr_numbers->find( fn( $num ) => $num > 5 );
echo '第一個大於 5 的數字: ' . ( $first_big_number ?? '無' ) . "\n\n";

// 5. find_index() - 找到第一個滿足條件的元素索引
echo "5. find_index() 範例：\n";
$inactive_index = $arr_users->find_index( fn( $user ) => ! $user['active'] );
echo '第一個非活躍用戶的索引: ' . ( $inactive_index !== null ? $inactive_index : '無' ) . "\n\n";

// 6. filter() - 過濾元素（鏈式調用）
echo "6. filter() 範例：\n";
$active_users = $arr_users->filter( fn( $user ) => $user['active'] );
echo "活躍用戶:\n";
foreach ( $active_users as $user ) {
	echo "- {$user['name']} (年齡: {$user['age']})\n";
}

$even_numbers = $arr_numbers->filter( fn( $num ) => $num % 2 === 0 );
echo '偶數: ' . implode( ', ', $even_numbers->to_array() ) . "\n\n";

// 7. map() - 映射元素（鏈式調用）
echo "7. map() 範例：\n";
$user_names = $arr_users->map( fn( $user ) => $user['name'] );
echo '所有用戶名稱: ' . implode( ', ', $user_names->to_array() ) . "\n";

$squared_numbers = $arr_numbers->map( fn( $num ) => $num * $num );
echo '數字的平方: ' . implode( ', ', $squared_numbers->to_array() ) . "\n\n";

// 8. 鏈式調用範例
echo "8. 鏈式調用範例：\n";
$young_active_names = $arr_users
	->filter( fn( $user ) => $user['age'] < 30 )
	->filter( fn( $user ) => $user['active'] )
	->map( fn( $user ) => $user['name'] );

echo '年輕且活躍的用戶: ' . implode( ', ', $young_active_names->to_array() ) . "\n";

$big_even_doubled = $arr_numbers
	->filter( fn( $num ) => $num > 5 )
	->filter( fn( $num ) => $num % 2 === 0 )
	->map( fn( $num ) => $num * 2 );

echo '大於 5 的偶數乘以 2: ' . implode( ', ', $big_even_doubled->to_array() ) . "\n\n";

// 9. reduce() - 歸納
echo "9. reduce() 範例：\n";
$total_age = $arr_users->reduce( fn( $sum, $user ) => $sum + $user['age'], 0 );
echo "所有用戶年齡總和: {$total_age}\n";

$sum_numbers = $arr_numbers->reduce( fn( $sum, $num ) => $sum + $num, 0 );
echo "數字總和: {$sum_numbers}\n\n";

// 10. 其他實用方法
echo "10. 其他實用方法：\n";
echo '用戶數量: ' . $arr_users->length() . "\n";
echo '第一個用戶: ' . $arr_users->first()['name'] . "\n";
echo '最後一個用戶: ' . $arr_users->last()['name'] . "\n";
echo '數字陣列是否為空: ' . ( $arr_numbers->is_empty() ? '是' : '否' ) . "\n";
echo '是否包含數字 5: ' . ( $arr_numbers->includes( 5 ) ? '是' : '否' ) . "\n\n";

// 11. 迭代支援
echo "11. 迭代支援：\n";
echo "使用 foreach 迭代用戶:\n";
foreach ( $arr_users as $index => $user ) {
	echo "索引 {$index}: {$user['name']}\n";
}
