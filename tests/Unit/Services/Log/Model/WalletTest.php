<?php

use J7\WpServices\Log\Model\Wallet;
use J7\WpServices\Log\Model\PointType;

describe('Wallet Model', function () {
    
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
                    ],
                    2 => (object) [
                        'ID' => 2,
                        'post_title' => '購物金',
                        'post_name' => 'shopping-credits',
                        'post_content' => '購物金描述',
                        'post_excerpt' => '購物金簡述',
                        'menu_order' => 2,
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
                    // 簡單的模擬資料
                    if (strpos($query, 'wallet_id = 1') !== false) {
                        return (object) [
                            'wallet_id' => 1,
                            'user_id' => 100,
                            'point_type_id' => 1,
                            'balance' => 500.0,
                            'created_at' => '2023-01-01 12:00:00',
                            'updated_at' => '2023-01-02 12:00:00'
                        ];
                    }
                    
                    if (strpos($query, 'user_id = 100') !== false && strpos($query, 'point_type_id = 1') !== false) {
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
                    if (strpos($query, 'user_id = 100') !== false) {
                        return [
                            (object) [
                                'wallet_id' => 1,
                                'user_id' => 100,
                                'point_type_id' => 1,
                                'balance' => 500.0,
                                'created_at' => '2023-01-01 12:00:00',
                                'updated_at' => '2023-01-02 12:00:00'
                            ],
                            (object) [
                                'wallet_id' => 2,
                                'user_id' => 100,
                                'point_type_id' => 2,
                                'balance' => 1000.0,
                                'created_at' => '2023-01-01 13:00:00',
                                'updated_at' => '2023-01-02 13:00:00'
                            ]
                        ];
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
            $reflection = new ReflectionClass(Wallet::class);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
            
            $expected_properties = ['wallet_id', 'user_id', 'point_type_id', 'point_type', 'balance', 'created_at', 'updated_at'];
            $actual_properties = array_map(fn($prop) => $prop->getName(), $properties);
            
            foreach ($expected_properties as $expected) {
                expect($actual_properties)->toContain($expected);
            }
        });

        it('繼承自 DTO 類別', function () {
            expect(Wallet::class)->toBeSubclassOf(J7\WpAbstracts\DTO::class);
        });
    });

    describe('create 靜態方法', function () {
        it('可以從資料庫記錄創建 Wallet 實例', function () {
            $record = (object) [
                'wallet_id' => 1,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 500.50,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            
            expect($wallet->wallet_id)->toBe(1);
            expect($wallet->user_id)->toBe(100);
            expect($wallet->point_type_id)->toBe(1);
            expect($wallet->balance)->toBe(500.50);
            expect($wallet->created_at)->toBe('2023-01-01 12:00:00');
            expect($wallet->updated_at)->toBe('2023-01-02 12:00:00');
        });

        it('正確載入關聯的 PointType', function () {
            $record = (object) [
                'wallet_id' => 1,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 500.0,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            
            expect($wallet->point_type)->toBeInstanceOf(PointType::class);
            expect($wallet->point_type->id)->toBe(1);
            expect($wallet->point_type->name)->toBe('紅利點數');
        });
    });

    describe('get_user_wallet 靜態方法', function () {
        it('可以取得用戶特定點數類型的錢包', function () {
            $wallet = Wallet::get_user_wallet(100, 1);
            
            expect($wallet)->toBeInstanceOf(Wallet::class);
            expect($wallet->user_id)->toBe(100);
            expect($wallet->point_type_id)->toBe(1);
            expect($wallet->balance)->toBe(500.0);
        });

        it('找不到錢包時回傳 null', function () {
            $wallet = Wallet::get_user_wallet(999, 999);
            
            expect($wallet)->toBeNull();
        });
    });

    describe('get_user_wallets 靜態方法', function () {
        it('可以取得用戶的所有錢包', function () {
            $wallets = Wallet::get_user_wallets(100);
            
            expect($wallets)->toBeArray();
            expect($wallets)->toHaveCount(2);
            
            expect($wallets[0])->toBeInstanceOf(Wallet::class);
            expect($wallets[0]->user_id)->toBe(100);
            expect($wallets[0]->point_type_id)->toBe(1);
            
            expect($wallets[1])->toBeInstanceOf(Wallet::class);
            expect($wallets[1]->user_id)->toBe(100);
            expect($wallets[1]->point_type_id)->toBe(2);
        });

        it('用戶沒有錢包時回傳空陣列', function () {
            $wallets = Wallet::get_user_wallets(999);
            
            expect($wallets)->toBeArray();
            expect($wallets)->toHaveCount(0);
        });
    });

    describe('get_wallet_by_id 靜態方法', function () {
        it('可以根據錢包 ID 取得錢包', function () {
            $wallet = Wallet::get_wallet_by_id(1);
            
            expect($wallet)->toBeInstanceOf(Wallet::class);
            expect($wallet->wallet_id)->toBe(1);
            expect($wallet->user_id)->toBe(100);
        });

        it('找不到錢包時回傳 null', function () {
            $wallet = Wallet::get_wallet_by_id(999);
            
            expect($wallet)->toBeNull();
        });
    });

    describe('屬性類型驗證', function () {
        it('整數屬性是整數類型', function () {
            $record = (object) [
                'wallet_id' => 1,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 500.0,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            
            expect($wallet->wallet_id)->toBeInt();
            expect($wallet->user_id)->toBeInt();
            expect($wallet->point_type_id)->toBeInt();
        });

        it('浮點數屬性是浮點數類型', function () {
            $record = (object) [
                'wallet_id' => 1,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 500.75,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            
            expect($wallet->balance)->toBeFloat();
            expect($wallet->balance)->toBe(500.75);
        });

        it('字串屬性是字串類型', function () {
            $record = (object) [
                'wallet_id' => 1,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 500.0,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            
            expect($wallet->created_at)->toBeString();
            expect($wallet->updated_at)->toBeString();
        });
    });

    describe('to_array 方法', function () {
        it('可以轉換為陣列', function () {
            $record = (object) [
                'wallet_id' => 1,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 500.0,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            $array = $wallet->to_array();
            
            expect($array)->toBeArray();
            expect($array)->toHaveKey('wallet_id');
            expect($array)->toHaveKey('user_id');
            expect($array)->toHaveKey('point_type_id');
            expect($array)->toHaveKey('balance');
            expect($array)->toHaveKey('created_at');
            expect($array)->toHaveKey('updated_at');
        });

        it('包含巢狀的 PointType 陣列', function () {
            $record = (object) [
                'wallet_id' => 1,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 500.0,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            $array = $wallet->to_array();
            
            expect($array)->toHaveKey('point_type');
            expect($array['point_type'])->toBeArray();
            expect($array['point_type'])->toHaveKey('id');
            expect($array['point_type'])->toHaveKey('name');
        });
    });

    describe('業務邏輯場景', function () {
        it('可以表示不同點數類型的錢包', function () {
            // 紅利點數錢包
            $bonusRecord = (object) [
                'wallet_id' => 1,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 1000.0,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $bonusWallet = Wallet::create($bonusRecord);
            expect($bonusWallet->point_type->name)->toBe('紅利點數');
            
            // 購物金錢包
            $shoppingRecord = (object) [
                'wallet_id' => 2,
                'user_id' => 100,
                'point_type_id' => 2,
                'balance' => 500.0,
                'created_at' => '2023-01-01 13:00:00',
                'updated_at' => '2023-01-02 13:00:00'
            ];
            
            $shoppingWallet = Wallet::create($shoppingRecord);
            expect($shoppingWallet->point_type->name)->toBe('購物金');
        });

        it('可以處理零餘額錢包', function () {
            $record = (object) [
                'wallet_id' => 3,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 0.0,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            
            expect($wallet->balance)->toBe(0.0);
            expect($wallet->balance)->toBeFloat();
        });

        it('可以處理高精度餘額', function () {
            $record = (object) [
                'wallet_id' => 4,
                'user_id' => 100,
                'point_type_id' => 1,
                'balance' => 999.9999,
                'created_at' => '2023-01-01 12:00:00',
                'updated_at' => '2023-01-02 12:00:00'
            ];
            
            $wallet = Wallet::create($record);
            
            expect($wallet->balance)->toBe(999.9999);
        });
    });
});