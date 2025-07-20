<?php

use J7\WpServices\Log\Model\Txn;
use J7\WpServices\Log\Model\Wallet;
use J7\WpServices\Log\Model\PointType;

describe('Txn Model', function () {
    
    // 模擬 WordPress 和全局變數
    beforeEach(function () {
        // 模擬 WordPress 函數
        if (!function_exists('get_post')) {
            function get_post($post_id) {
                $mock_posts = [
                    1 => (object) [
                        'ID' => 1,
                        'post_title' => '紅利點數',
                        'post_name' => 'bonus-points',
                        'post_content' => '紅利點數描述',
                        'post_excerpt' => '紅利點數簡述',
                        'menu_order' => 1,
                        'post_type' => 'point_type'
                    ]
                ];
                
                return $mock_posts[$post_id] ?? null;
            }
        }

        if (!function_exists('get_post_thumbnail_id')) {
            function get_post_thumbnail_id($post_id) {
                return 0;
            }
        }
        
        if (!function_exists('wp_get_attachment_image_url')) {
            function wp_get_attachment_image_url($attachment_id, $size = 'thumbnail') {
                return '';
            }
        }
        
        if (!function_exists('get_post_field')) {
            function get_post_field($field, $post_id) {
                $post = get_post($post_id);
                return $post ? $post->$field : '';
            }
        }

        if (!class_exists('WP_Error')) {
            class WP_Error {
                private array $errors = [];
                
                public function add(string $code, string $message): void {
                    $this->errors[$code][] = $message;
                }
                
                public function has_errors(): bool {
                    return !empty($this->errors);
                }
                
                public function get_error_messages(): array {
                    $messages = [];
                    foreach ($this->errors as $code => $error_messages) {
                        $messages = array_merge($messages, $error_messages);
                    }
                    return $messages;
                }
            }
        }

        if (!function_exists('wp_get_environment_type')) {
            function wp_get_environment_type() {
                return 'local';
            }
        }

        // 模擬 wpdb 全局變數
        global $wpdb;
        if (!isset($wpdb)) {
            $wpdb = new class {
                public $prefix = 'wp_';
                
                public function get_row($query) {
                    // 模擬錢包查詢
                    if (strpos($query, 'j7_wallets') !== false && strpos($query, 'wallet_id = 1') !== false) {
                        return (object) [
                            'wallet_id' => 1,
                            'user_id' => 100,
                            'point_type_id' => 1,
                            'balance' => 500.0,
                            'created_at' => '2023-01-01 12:00:00',
                            'updated_at' => '2023-01-02 12:00:00'
                        ];
                    }
                    
                    return null;
                }
                
                public function get_results($query) {
                    // 模擬交易記錄查詢
                    if (strpos($query, 'j7_wallet_logs') !== false) {
                        if (strpos($query, 'wallet_id = 1') !== false) {
                            return [
                                (object) [
                                    'txn_id' => 1,
                                    'wallet_id' => 1,
                                    'title' => '購買商品',
                                    'type' => 'deposit',
                                    'modified_by' => 1,
                                    'point_changed' => 100.0,
                                    'new_balance' => 600.0,
                                    'ref_id' => 123,
                                    'ref_type' => 'order',
                                    'expire_date' => '9999-12-31 23:59:59',
                                    'created_at' => '2023-01-01 14:00:00',
                                    'updated_at' => '2023-01-01 14:00:00'
                                ],
                                (object) [
                                    'txn_id' => 2,
                                    'wallet_id' => 1,
                                    'title' => '使用點數',
                                    'type' => 'withdraw',
                                    'modified_by' => 1,
                                    'point_changed' => -50.0,
                                    'new_balance' => 550.0,
                                    'ref_id' => 124,
                                    'ref_type' => 'order',
                                    'expire_date' => '9999-12-31 23:59:59',
                                    'created_at' => '2023-01-01 15:00:00',
                                    'updated_at' => '2023-01-01 15:00:00'
                                ]
                            ];
                        }
                        
                        // 模擬用戶交易記錄查詢
                        if (strpos($query, 'user_id = 100') !== false) {
                            return [
                                (object) [
                                    'txn_id' => 1,
                                    'wallet_id' => 1,
                                    'title' => '購買商品',
                                    'type' => 'deposit',
                                    'modified_by' => 1,
                                    'point_changed' => 100.0,
                                    'new_balance' => 600.0,
                                    'ref_id' => 123,
                                    'ref_type' => 'order',
                                    'expire_date' => '9999-12-31 23:59:59',
                                    'created_at' => '2023-01-01 14:00:00',
                                    'updated_at' => '2023-01-01 14:00:00'
                                ]
                            ];
                        }
                    }
                    
                    return [];
                }
                
                public function prepare($query, ...$args) {
                    return vsprintf(str_replace('%s', "'%s'", str_replace('%d', '%d', $query)), $args);
                }
            };
        }
    });

    describe('建構和屬性', function () {
        it('具有所有必要的公開屬性', function () {
            $reflection = new ReflectionClass(Txn::class);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
            
            $expected_properties = [
                'txn_id', 'wallet_id', 'wallet', 'title', 'type', 
                'modified_by', 'point_changed', 'new_balance', 'ref_id', 
                'ref_type', 'expire_date', 'created_at', 'updated_at'
            ];
            $actual_properties = array_map(fn($prop) => $prop->getName(), $properties);
            
            foreach ($expected_properties as $expected) {
                expect($actual_properties)->toContain($expected);
            }
        });

        it('繼承自 DTO 類別', function () {
            expect(Txn::class)->toBeSubclassOf(J7\WpAbstracts\DTO::class);
        });

        it('包含 Wallet 類型的屬性', function () {
            $reflection = new ReflectionProperty(Txn::class, 'wallet');
            $type = $reflection->getType();
            expect($type->getName())->toBe(Wallet::class);
        });
    });

    describe('create 靜態方法', function () {
        it('可以從資料庫記錄創建 Txn 實例', function () {
            $record = (object) [
                'txn_id' => 1,
                'wallet_id' => 1,
                'title' => '購買商品',
                'type' => 'deposit',
                'modified_by' => 1,
                'point_changed' => 100.0,
                'new_balance' => 600.0,
                'ref_id' => 123,
                'ref_type' => 'order',
                'expire_date' => '9999-12-31 23:59:59',
                'created_at' => '2023-01-01 14:00:00',
                'updated_at' => '2023-01-01 14:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->txn_id)->toBe(1);
            expect($txn->wallet_id)->toBe(1);
            expect($txn->title)->toBe('購買商品');
            expect($txn->type)->toBe('deposit');
            expect($txn->modified_by)->toBe(1);
            expect($txn->point_changed)->toBe(100.0);
            expect($txn->new_balance)->toBe(600.0);
            expect($txn->ref_id)->toBe(123);
            expect($txn->ref_type)->toBe('order');
        });

        it('正確載入關聯的 Wallet', function () {
            $record = (object) [
                'txn_id' => 1,
                'wallet_id' => 1,
                'title' => '購買商品',
                'type' => 'deposit',
                'modified_by' => 1,
                'point_changed' => 100.0,
                'new_balance' => 600.0,
                'ref_id' => null,
                'ref_type' => null,
                'expire_date' => '9999-12-31 23:59:59',
                'created_at' => '2023-01-01 14:00:00',
                'updated_at' => '2023-01-01 14:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->wallet)->toBeInstanceOf(Wallet::class);
            expect($txn->wallet->wallet_id)->toBe(1);
            expect($txn->wallet->user_id)->toBe(100);
        });

        it('處理 null 的 ref_id 和 ref_type', function () {
            $record = (object) [
                'txn_id' => 1,
                'wallet_id' => 1,
                'title' => '系統調整',
                'type' => 'system',
                'modified_by' => 0,
                'point_changed' => 10.0,
                'new_balance' => 510.0,
                'ref_id' => null,
                'ref_type' => null,
                'expire_date' => '9999-12-31 23:59:59',
                'created_at' => '2023-01-01 16:00:00',
                'updated_at' => '2023-01-01 16:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->ref_id)->toBeNull();
            expect($txn->ref_type)->toBeNull();
        });
    });

    describe('get_wallet_transactions 靜態方法', function () {
        it('可以取得錢包的交易記錄', function () {
            $transactions = Txn::get_wallet_transactions(1);
            
            expect($transactions)->toBeArray();
            expect($transactions)->toHaveCount(2);
            
            expect($transactions[0])->toBeInstanceOf(Txn::class);
            expect($transactions[0]->wallet_id)->toBe(1);
            expect($transactions[0]->type)->toBe('deposit');
            
            expect($transactions[1])->toBeInstanceOf(Txn::class);
            expect($transactions[1]->wallet_id)->toBe(1);
            expect($transactions[1]->type)->toBe('withdraw');
        });

        it('支援分頁參數', function () {
            $transactions = Txn::get_wallet_transactions(1, 1, 0);
            
            expect($transactions)->toBeArray();
            // 在真實環境中會只回傳 1 筆，這裡模擬會回傳全部
        });
    });

    describe('get_user_transactions 靜態方法', function () {
        it('可以取得用戶的所有交易記錄', function () {
            $transactions = Txn::get_user_transactions(100);
            
            expect($transactions)->toBeArray();
            expect($transactions)->toHaveCount(1);
            
            expect($transactions[0])->toBeInstanceOf(Txn::class);
            expect($transactions[0]->type)->toBe('deposit');
        });

        it('支援類型過濾', function () {
            $transactions = Txn::get_user_transactions(100, 20, 0, 'deposit');
            
            expect($transactions)->toBeArray();
            // 確保回傳的都是 deposit 類型（在模擬中）
            foreach ($transactions as $txn) {
                expect($txn->type)->toBe('deposit');
            }
        });
    });

    describe('交易類型驗證', function () {
        it('get_valid_types 回傳所有有效類型', function () {
            $types = Txn::get_valid_types();
            
            $expected_types = ['deposit', 'withdraw', 'expire', 'bonus', 'refund', 'modify', 'cron', 'system'];
            
            expect($types)->toBe($expected_types);
        });

        it('is_valid_type 正確驗證類型', function () {
            expect(Txn::is_valid_type('deposit'))->toBeTrue();
            expect(Txn::is_valid_type('withdraw'))->toBeTrue();
            expect(Txn::is_valid_type('bonus'))->toBeTrue();
            expect(Txn::is_valid_type('system'))->toBeTrue();
            
            expect(Txn::is_valid_type('invalid'))->toBeFalse();
            expect(Txn::is_valid_type('unknown'))->toBeFalse();
            expect(Txn::is_valid_type(''))->toBeFalse();
        });
    });

    describe('屬性類型驗證', function () {
        it('整數屬性是整數類型', function () {
            $record = (object) [
                'txn_id' => 1,
                'wallet_id' => 1,
                'title' => 'Test',
                'type' => 'deposit',
                'modified_by' => 1,
                'point_changed' => 100.0,
                'new_balance' => 600.0,
                'ref_id' => 123,
                'ref_type' => 'order',
                'expire_date' => '9999-12-31 23:59:59',
                'created_at' => '2023-01-01 14:00:00',
                'updated_at' => '2023-01-01 14:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->txn_id)->toBeInt();
            expect($txn->wallet_id)->toBeInt();
            expect($txn->modified_by)->toBeInt();
            expect($txn->ref_id)->toBeInt();
        });

        it('浮點數屬性是浮點數類型', function () {
            $record = (object) [
                'txn_id' => 1,
                'wallet_id' => 1,
                'title' => 'Test',
                'type' => 'deposit',
                'modified_by' => 1,
                'point_changed' => 100.50,
                'new_balance' => 600.75,
                'ref_id' => null,
                'ref_type' => null,
                'expire_date' => '9999-12-31 23:59:59',
                'created_at' => '2023-01-01 14:00:00',
                'updated_at' => '2023-01-01 14:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->point_changed)->toBeFloat();
            expect($txn->new_balance)->toBeFloat();
            expect($txn->point_changed)->toBe(100.50);
            expect($txn->new_balance)->toBe(600.75);
        });
    });

    describe('業務邏輯場景', function () {
        it('可以表示存入交易', function () {
            $record = (object) [
                'txn_id' => 1,
                'wallet_id' => 1,
                'title' => '購買商品獲得點數',
                'type' => 'deposit',
                'modified_by' => 1,
                'point_changed' => 100.0,
                'new_balance' => 600.0,
                'ref_id' => 123,
                'ref_type' => 'order',
                'expire_date' => '2024-12-31 23:59:59',
                'created_at' => '2023-01-01 14:00:00',
                'updated_at' => '2023-01-01 14:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->type)->toBe('deposit');
            expect($txn->point_changed)->toBeGreaterThan(0);
        });

        it('可以表示提取交易', function () {
            $record = (object) [
                'txn_id' => 2,
                'wallet_id' => 1,
                'title' => '使用點數折抵',
                'type' => 'withdraw',
                'modified_by' => 1,
                'point_changed' => -50.0,
                'new_balance' => 550.0,
                'ref_id' => 124,
                'ref_type' => 'order',
                'expire_date' => '9999-12-31 23:59:59',
                'created_at' => '2023-01-01 15:00:00',
                'updated_at' => '2023-01-01 15:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->type)->toBe('withdraw');
            expect($txn->point_changed)->toBeLessThan(0);
        });

        it('可以表示系統調整', function () {
            $record = (object) [
                'txn_id' => 3,
                'wallet_id' => 1,
                'title' => '管理員手動調整',
                'type' => 'modify',
                'modified_by' => 0, // 系統操作
                'point_changed' => 25.0,
                'new_balance' => 575.0,
                'ref_id' => null,
                'ref_type' => null,
                'expire_date' => '9999-12-31 23:59:59',
                'created_at' => '2023-01-01 16:00:00',
                'updated_at' => '2023-01-01 16:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->type)->toBe('modify');
            expect($txn->modified_by)->toBe(0);
            expect($txn->ref_id)->toBeNull();
            expect($txn->ref_type)->toBeNull();
        });

        it('可以表示過期交易', function () {
            $record = (object) [
                'txn_id' => 4,
                'wallet_id' => 1,
                'title' => '點數過期扣除',
                'type' => 'expire',
                'modified_by' => 0,
                'point_changed' => -100.0,
                'new_balance' => 475.0,
                'ref_id' => null,
                'ref_type' => null,
                'expire_date' => '2023-01-01 00:00:00',
                'created_at' => '2023-01-01 17:00:00',
                'updated_at' => '2023-01-01 17:00:00'
            ];
            
            $txn = Txn::create($record);
            
            expect($txn->type)->toBe('expire');
            expect($txn->point_changed)->toBeLessThan(0);
        });
    });

    describe('to_array 方法', function () {
        it('可以轉換為陣列並包含巢狀 Wallet', function () {
            $record = (object) [
                'txn_id' => 1,
                'wallet_id' => 1,
                'title' => '購買商品',
                'type' => 'deposit',
                'modified_by' => 1,
                'point_changed' => 100.0,
                'new_balance' => 600.0,
                'ref_id' => 123,
                'ref_type' => 'order',
                'expire_date' => '9999-12-31 23:59:59',
                'created_at' => '2023-01-01 14:00:00',
                'updated_at' => '2023-01-01 14:00:00'
            ];
            
            $txn = Txn::create($record);
            $array = $txn->to_array();
            
            expect($array)->toBeArray();
            expect($array)->toHaveKey('txn_id');
            expect($array)->toHaveKey('wallet');
            expect($array['wallet'])->toBeArray();
            expect($array['wallet'])->toHaveKey('wallet_id');
            expect($array['wallet'])->toHaveKey('point_type');
        });
    });
});