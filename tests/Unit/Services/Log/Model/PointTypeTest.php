<?php

use J7\WpServices\Log\Model\PointType;

describe('PointType Model', function () {
    
    // 模擬 WordPress 函數
    beforeEach(function () {
        // 模擬必要的 WordPress 函數
        if (!function_exists('get_post_thumbnail_id')) {
            function get_post_thumbnail_id($post_id) {
                return $post_id === 1 ? 100 : 0;
            }
        }
        
        if (!function_exists('wp_get_attachment_image_url')) {
            function wp_get_attachment_image_url($attachment_id, $size = 'thumbnail') {
                return $attachment_id > 0 ? "https://example.com/image-{$attachment_id}.jpg" : '';
            }
        }
        
        if (!function_exists('get_post_field')) {
            function get_post_field($field, $post_id) {
                $mock_data = [
                    1 => [
                        'post_content' => '紅利點數詳細描述',
                        'post_excerpt' => '紅利點數簡短描述'
                    ],
                    2 => [
                        'post_content' => '購物金詳細描述',
                        'post_excerpt' => '購物金簡短描述'
                    ]
                ];
                
                return $mock_data[$post_id][$field] ?? '';
            }
        }

        if (!class_exists('WP_Post')) {
            class WP_Post {
                public $ID;
                public $post_title;
                public $post_name;
                public $post_content;
                public $post_excerpt;
                public $menu_order;
                public $post_type;
                
                public function __construct($data = []) {
                    foreach ($data as $key => $value) {
                        $this->$key = $value;
                    }
                }
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
    });

    describe('建構和屬性', function () {
        it('具有所有必要的公開屬性', function () {
            $reflection = new ReflectionClass(PointType::class);
            $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
            
            $expected_properties = ['id', 'name', 'slug', 'icon_url', 'description', 'short_description', 'menu_order'];
            $actual_properties = array_map(fn($prop) => $prop->getName(), $properties);
            
            foreach ($expected_properties as $expected) {
                expect($actual_properties)->toContain($expected);
            }
        });

        it('繼承自 DTO 類別', function () {
            expect(PointType::class)->toBeSubclassOf(J7\WpAbstracts\DTO::class);
        });
    });

    describe('create 靜態方法', function () {
        it('可以從 WP_Post 創建 PointType 實例', function () {
            $post = new WP_Post([
                'ID' => 1,
                'post_title' => '紅利點數',
                'post_name' => 'bonus-points',
                'post_content' => '紅利點數詳細描述',
                'post_excerpt' => '紅利點數簡短描述',
                'menu_order' => 1,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->id)->toBe(1);
            expect($pointType->name)->toBe('紅利點數');
            expect($pointType->slug)->toBe('bonus-points');
            expect($pointType->description)->toBe('紅利點數詳細描述');
            expect($pointType->short_description)->toBe('紅利點數簡短描述');
            expect($pointType->menu_order)->toBe(1);
        });

        it('正確處理特色圖片 URL', function () {
            $post = new WP_Post([
                'ID' => 1,
                'post_title' => '紅利點數',
                'post_name' => 'bonus-points',
                'menu_order' => 1,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->icon_url)->toBe('https://example.com/image-100.jpg');
        });

        it('處理沒有特色圖片的情況', function () {
            $post = new WP_Post([
                'ID' => 999, // 沒有特色圖片的 ID
                'post_title' => '購物金',
                'post_name' => 'shopping-credits',
                'menu_order' => 2,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->icon_url)->toBe('');
        });
    });

    describe('屬性類型驗證', function () {
        it('id 屬性是整數類型', function () {
            $post = new WP_Post([
                'ID' => 1,
                'post_title' => '測試點數',
                'post_name' => 'test',
                'menu_order' => 0,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->id)->toBeInt();
        });

        it('字串屬性是字串類型', function () {
            $post = new WP_Post([
                'ID' => 1,
                'post_title' => '測試點數',
                'post_name' => 'test',
                'menu_order' => 0,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->name)->toBeString();
            expect($pointType->slug)->toBeString();
            expect($pointType->icon_url)->toBeString();
            expect($pointType->description)->toBeString();
            expect($pointType->short_description)->toBeString();
        });

        it('menu_order 屬性是整數類型', function () {
            $post = new WP_Post([
                'ID' => 1,
                'post_title' => '測試點數',
                'post_name' => 'test',
                'menu_order' => 5,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->menu_order)->toBeInt();
            expect($pointType->menu_order)->toBe(5);
        });
    });

    describe('to_array 方法', function () {
        it('可以轉換為陣列', function () {
            $post = new WP_Post([
                'ID' => 1,
                'post_title' => '紅利點數',
                'post_name' => 'bonus-points',
                'menu_order' => 1,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            $array = $pointType->to_array();
            
            expect($array)->toBeArray();
            expect($array)->toHaveKey('id');
            expect($array)->toHaveKey('name');
            expect($array)->toHaveKey('slug');
            expect($array)->toHaveKey('icon_url');
            expect($array)->toHaveKey('description');
            expect($array)->toHaveKey('short_description');
            expect($array)->toHaveKey('menu_order');
        });

        it('陣列包含正確的值', function () {
            $post = new WP_Post([
                'ID' => 2,
                'post_title' => '購物金',
                'post_name' => 'shopping-credits',
                'menu_order' => 2,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            $array = $pointType->to_array();
            
            expect($array['id'])->toBe(2);
            expect($array['name'])->toBe('購物金');
            expect($array['slug'])->toBe('shopping-credits');
            expect($array['menu_order'])->toBe(2);
        });
    });

    describe('不同點數類型範例', function () {
        it('可以處理紅利點數類型', function () {
            $post = new WP_Post([
                'ID' => 1,
                'post_title' => '紅利點數',
                'post_name' => 'bonus-points',
                'menu_order' => 1,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->name)->toBe('紅利點數');
            expect($pointType->slug)->toBe('bonus-points');
        });

        it('可以處理積分類型', function () {
            $post = new WP_Post([
                'ID' => 2,
                'post_title' => '積分',
                'post_name' => 'credits',
                'menu_order' => 2,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->name)->toBe('積分');
            expect($pointType->slug)->toBe('credits');
        });

        it('可以處理現金點數類型', function () {
            $post = new WP_Post([
                'ID' => 3,
                'post_title' => '現金點數',
                'post_name' => 'cash-points',
                'menu_order' => 3,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->name)->toBe('現金點數');
            expect($pointType->slug)->toBe('cash-points');
        });

        it('可以處理購物金類型', function () {
            $post = new WP_Post([
                'ID' => 4,
                'post_title' => '購物金',
                'post_name' => 'shopping-credits',
                'menu_order' => 4,
                'post_type' => 'point_type'
            ]);
            
            $pointType = PointType::create($post);
            
            expect($pointType->name)->toBe('購物金');
            expect($pointType->slug)->toBe('shopping-credits');
        });
    });
});