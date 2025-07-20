<?php

use J7\WpHelpers\Str;

describe('Str Helper', function () {
    
    it('可以創建 Str 實例', function () {
        $str = new Str('hello world');
        expect($str->value)->toBe('hello world');
    });

    describe('is_english 方法', function () {
        it('純英文字母、數字和空格回傳 true', function () {
            $str = new Str('Hello World 123');
            expect($str->is_english())->toBeTrue();
        });

        it('包含特殊字符回傳 false', function () {
            $str = new Str('Hello-World!');
            expect($str->is_english())->toBeFalse();
        });

        it('包含中文字符回傳 false', function () {
            $str = new Str('Hello 世界');
            expect($str->is_english())->toBeFalse();
        });

        it('空字串回傳 true', function () {
            $str = new Str('');
            expect($str->is_english())->toBeTrue();
        });
    });

    describe('contains_non_ascii 方法', function () {
        it('純 ASCII 字符回傳 false', function () {
            $str = new Str('Hello World 123!@#');
            expect($str->contains_non_ascii())->toBeFalse();
        });

        it('包含中文字符回傳 true', function () {
            $str = new Str('Hello 世界');
            expect($str->contains_non_ascii())->toBeTrue();
        });

        it('包含 emoji 回傳 true', function () {
            $str = new Str('Hello 😀');
            expect($str->contains_non_ascii())->toBeTrue();
        });

        it('包含重音符號回傳 true', function () {
            $str = new Str('café');
            expect($str->contains_non_ascii())->toBeTrue();
        });
    });

    describe('is_urlencoded 方法', function () {
        it('URL 編碼的字串回傳 true', function () {
            $str = new Str('Hello%20World');
            expect($str->is_urlencoded())->toBeTrue();
        });

        it('未編碼的字串回傳 false', function () {
            $str = new Str('Hello World');
            expect($str->is_urlencoded())->toBeFalse();
        });

        it('包含 + 號的編碼字串回傳 true', function () {
            $str = new Str('Hello+World');
            expect($str->is_urlencoded())->toBeTrue();
        });
    });

    describe('is_ascii 方法', function () {
        it('純 ASCII 且未編碼的字串回傳 true', function () {
            $str = new Str('Hello World 123');
            expect($str->is_ascii())->toBeTrue();
        });

        it('包含非 ASCII 字符回傳 false', function () {
            $str = new Str('Hello 世界');
            expect($str->is_ascii())->toBeFalse();
        });

        it('已 URL 編碼的字串回傳 false', function () {
            $str = new Str('Hello%20World');
            expect($str->is_ascii())->toBeFalse();
        });

        it('空字串回傳 true', function () {
            $str = new Str('');
            expect($str->is_ascii())->toBeTrue();
        });
    });

    // 測試靜態方法（如果有的話）
    if (method_exists(Str::class, 'random')) {
        describe('random 靜態方法', function () {
            it('生成指定長度的隨機字串', function () {
                $result = Str::random(10);
                expect(strlen($result))->toBe(10);
            });

            it('生成的字串包含指定字符集', function () {
                $result = Str::random(20);
                expect($result)->toMatch('/^[a-zA-Z0-9]+$/');
            });

            it('不同調用生成不同字串', function () {
                $str1 = Str::random(10);
                $str2 = Str::random(10);
                expect($str1)->not->toBe($str2);
            });
        });
    }

    // 測試輔助方法的組合使用
    describe('方法組合使用', function () {
        it('可以識別中文字串', function () {
            $str = new Str('你好世界');
            expect($str->is_ascii())->toBeFalse();
            expect($str->is_english())->toBeFalse();
            expect($str->contains_non_ascii())->toBeTrue();
            expect($str->is_urlencoded())->toBeFalse();
        });

        it('可以識別 URL 編碼的中文', function () {
            $encoded = urlencode('你好世界');
            $str = new Str($encoded);
            expect($str->is_ascii())->toBeFalse();
            expect($str->is_urlencoded())->toBeTrue();
        });

        it('可以識別混合內容', function () {
            $str = new Str('Hello 世界 123');
            expect($str->is_ascii())->toBeFalse();
            expect($str->is_english())->toBeFalse();
            expect($str->contains_non_ascii())->toBeTrue();
        });
    });

    // 邊界情況測試
    describe('邊界情況', function () {
        it('處理只有數字的字串', function () {
            $str = new Str('12345');
            expect($str->is_english())->toBeTrue();
            expect($str->is_ascii())->toBeTrue();
            expect($str->contains_non_ascii())->toBeFalse();
        });

        it('處理只有空格的字串', function () {
            $str = new Str('   ');
            expect($str->is_english())->toBeTrue();
            expect($str->is_ascii())->toBeTrue();
            expect($str->contains_non_ascii())->toBeFalse();
        });

        it('處理特殊 ASCII 字符', function () {
            $str = new Str('!@#$%^&*()');
            expect($str->is_english())->toBeFalse();
            expect($str->is_ascii())->toBeTrue();
            expect($str->contains_non_ascii())->toBeFalse();
        });
    });
});