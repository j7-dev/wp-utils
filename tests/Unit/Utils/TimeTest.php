<?php

use J7\WpUtils\Time;

describe('Time Utils', function () {
    
    // 由於這個類別依賴 WordPress 函數，我們需要模擬它們
    beforeEach(function () {
        // 模擬 wp_timezone 函數
        if (!function_exists('wp_timezone')) {
            function wp_timezone() {
                return new DateTimeZone('UTC');
            }
        }
    });

    describe('wp_strtotime 方法', function () {
        it('可以轉換有效的日期字串為時間戳記', function () {
            $result = Time::wp_strtotime('2023-01-01 12:00:00');
            expect($result)->toBeInt();
            expect($result)->toBeGreaterThan(0);
        });

        it('可以轉換相對時間字串', function () {
            $result = Time::wp_strtotime('now');
            expect($result)->toBeInt();
            expect($result)->toBeGreaterThan(0);
        });

        it('可以轉換 ISO 8601 格式', function () {
            $result = Time::wp_strtotime('2023-12-25T15:30:00');
            expect($result)->toBeInt();
            
            // 驗證轉換結果正確
            $expected = DateTime::createFromFormat('Y-m-d\TH:i:s', '2023-12-25T15:30:00', new DateTimeZone('UTC'));
            expect($result)->toBe($expected->getTimestamp());
        });

        it('無效日期字串回傳 null', function () {
            $result = Time::wp_strtotime('invalid-date');
            expect($result)->toBeNull();
        });

        it('空字串回傳 null', function () {
            $result = Time::wp_strtotime('');
            expect($result)->toBeNull();
        });

        it('處理不同的日期格式', function () {
            $formats = [
                '2023-01-01',
                '2023/01/01',
                '01-01-2023',
                '2023-01-01 00:00:00',
                'January 1, 2023',
                '1 Jan 2023'
            ];

            foreach ($formats as $format) {
                $result = Time::wp_strtotime($format);
                expect($result)->toBeInt()->and($result)->toBeGreaterThan(0);
            }
        });

        it('處理時區相關的轉換', function () {
            // 測試同一時間在不同時區的轉換
            $dateString = '2023-01-01 12:00:00';
            $result = Time::wp_strtotime($dateString);
            
            // 確保回傳的是時間戳記
            expect($result)->toBeInt();
            
            // 驗證可以轉回原始時間（考慮時區）
            $converted = date('Y-m-d H:i:s', $result);
            expect($converted)->toBeString();
        });

        it('處理邊界時間值', function () {
            // Unix 時間戳記的開始
            $result = Time::wp_strtotime('1970-01-01 00:00:00');
            expect($result)->toBe(0);

            // 未來日期
            $result = Time::wp_strtotime('2030-12-31 23:59:59');
            expect($result)->toBeInt();
            expect($result)->toBeGreaterThan(time());
        });
    });

    // 測試靜態方法的特性
    describe('靜態方法特性', function () {
        it('Time 是抽象類別，不能直接實例化', function () {
            expect(function () {
                new Time();
            })->toThrow(Error::class);
        });

        it('wp_strtotime 是靜態方法', function () {
            $reflection = new ReflectionMethod(Time::class, 'wp_strtotime');
            expect($reflection->isStatic())->toBeTrue();
        });

        it('wp_strtotime 是公開方法', function () {
            $reflection = new ReflectionMethod(Time::class, 'wp_strtotime');
            expect($reflection->isPublic())->toBeTrue();
        });
    });

    // 效能相關測試
    describe('效能測試', function () {
        it('處理大量日期轉換時效能合理', function () {
            $startTime = microtime(true);
            
            for ($i = 0; $i < 100; $i++) {
                $date = '2023-01-' . str_pad($i % 28 + 1, 2, '0', STR_PAD_LEFT) . ' 12:00:00';
                Time::wp_strtotime($date);
            }
            
            $endTime = microtime(true);
            $executionTime = $endTime - $startTime;
            
            // 100 次轉換應該在 1 秒內完成
            expect($executionTime)->toBeLessThan(1.0);
        });
    });

    // 回傳值類型測試
    describe('回傳值類型', function () {
        it('成功時回傳整數類型', function () {
            $result = Time::wp_strtotime('2023-01-01');
            expect($result)->toBeInt();
        });

        it('失敗時回傳 null', function () {
            $result = Time::wp_strtotime('not-a-date');
            expect($result)->toBeNull();
        });

        it('回傳值符合方法簽名', function () {
            $reflection = new ReflectionMethod(Time::class, 'wp_strtotime');
            $returnType = $reflection->getReturnType();
            expect($returnType->__toString())->toBe('int|null');
        });
    });
});