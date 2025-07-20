<?php

use J7\WpHelpers\Arr;

describe('Arr Helper', function () {
    
    it('可以創建 Arr 實例', function () {
        $arr = new Arr([1, 2, 3]);
        expect($arr->items)->toEqual([1, 2, 3]);
    });

    it('可以使用靜態 create 方法創建實例', function () {
        $arr = Arr::create([1, 2, 3], true);
        expect($arr->items)->toEqual([1, 2, 3]);
    });

    describe('some 方法', function () {
        it('當至少有一個元素滿足條件時回傳 true', function () {
            $arr = Arr::create([1, 2, 3, 4, 5]);
            $result = $arr->some(fn($value) => $value > 3);
            expect($result)->toBeTrue();
        });

        it('當沒有元素滿足條件時回傳 false', function () {
            $arr = Arr::create([1, 2, 3]);
            $result = $arr->some(fn($value) => $value > 5);
            expect($result)->toBeFalse();
        });

        it('空陣列回傳 false', function () {
            $arr = Arr::create([]);
            $result = $arr->some(fn($value) => true);
            expect($result)->toBeFalse();
        });
    });

    describe('every 方法', function () {
        it('當所有元素都滿足條件時回傳 true', function () {
            $arr = Arr::create([2, 4, 6, 8]);
            $result = $arr->every(fn($value) => $value % 2 === 0);
            expect($result)->toBeTrue();
        });

        it('當有元素不滿足條件時回傳 false', function () {
            $arr = Arr::create([2, 3, 4, 6]);
            $result = $arr->every(fn($value) => $value % 2 === 0);
            expect($result)->toBeFalse();
        });

        it('空陣列回傳 true', function () {
            $arr = Arr::create([]);
            $result = $arr->every(fn($value) => false);
            expect($result)->toBeTrue();
        });
    });

    describe('find 方法', function () {
        it('找到第一個滿足條件的元素', function () {
            $arr = Arr::create([1, 2, 3, 4, 5]);
            $result = $arr->find(fn($value) => $value > 2);
            expect($result)->toBe(3);
        });

        it('沒找到時回傳 null', function () {
            $arr = Arr::create([1, 2, 3]);
            $result = $arr->find(fn($value) => $value > 5);
            expect($result)->toBeNull();
        });
    });

    describe('find_index 方法', function () {
        it('找到第一個滿足條件的元素索引', function () {
            $arr = Arr::create(['a', 'b', 'c', 'd']);
            $result = $arr->find_index(fn($value) => $value === 'c');
            expect($result)->toBe(2);
        });

        it('沒找到時回傳 null', function () {
            $arr = Arr::create(['a', 'b', 'c']);
            $result = $arr->find_index(fn($value) => $value === 'z');
            expect($result)->toBeNull();
        });
    });

    describe('filter 方法', function () {
        it('可以過濾陣列元素', function () {
            $arr = Arr::create([1, 2, 3, 4, 5]);
            $result = $arr->filter(fn($value) => $value % 2 === 0);
            expect($result->items)->toEqual([1 => 2, 3 => 4]);
        });

        it('支援方法鏈接', function () {
            $arr = Arr::create([1, 2, 3, 4, 5, 6]);
            $result = $arr->filter(fn($value) => $value % 2 === 0)
                          ->filter(fn($value) => $value > 2);
            expect($result->items)->toEqual([3 => 4, 5 => 6]);
        });
    });

    describe('map 方法', function () {
        it('可以映射陣列元素', function () {
            $arr = Arr::create([1, 2, 3]);
            $result = $arr->map(fn($value) => $value * 2);
            expect($result->items)->toEqual([2, 4, 6]);
        });

        it('支援方法鏈接', function () {
            $arr = Arr::create([1, 2, 3]);
            $result = $arr->map(fn($value) => $value * 2)
                          ->map(fn($value) => $value + 1);
            expect($result->items)->toEqual([3, 5, 7]);
        });
    });

    describe('includes 方法', function () {
        it('找到值時回傳 true', function () {
            $arr = Arr::create([1, 2, 3, 'hello']);
            expect($arr->includes(2))->toBeTrue();
            expect($arr->includes('hello'))->toBeTrue();
        });

        it('沒找到值時回傳 false', function () {
            $arr = Arr::create([1, 2, 3]);
            expect($arr->includes(5))->toBeFalse();
        });

        it('嚴格模式下類型不匹配回傳 false', function () {
            $arr = Arr::create([1, 2, 3], true);
            expect($arr->includes('1'))->toBeFalse();
        });
    });

    describe('基本屬性方法', function () {
        it('length 回傳正確的長度', function () {
            $arr = Arr::create([1, 2, 3, 4, 5]);
            expect($arr->length())->toBe(5);
        });

        it('first 回傳第一個元素', function () {
            $arr = Arr::create(['a', 'b', 'c']);
            expect($arr->first())->toBe('a');
        });

        it('last 回傳最後一個元素', function () {
            $arr = Arr::create(['a', 'b', 'c']);
            expect($arr->last())->toBe('c');
        });

        it('空陣列的 first 和 last 回傳 null', function () {
            $arr = Arr::create([]);
            expect($arr->first())->toBeNull();
            expect($arr->last())->toBeNull();
        });

        it('is_empty 正確判斷空陣列', function () {
            $empty = Arr::create([]);
            $notEmpty = Arr::create([1]);
            expect($empty->is_empty())->toBeTrue();
            expect($notEmpty->is_empty())->toBeFalse();
        });
    });

    describe('轉換方法', function () {
        it('to_array 回傳原始陣列', function () {
            $items = [1, 2, 3];
            $arr = Arr::create($items);
            expect($arr->to_array())->toEqual($items);
        });

        it('to_string 正確轉換為字串', function () {
            $arr = Arr::create(['a', 'b', 'c']);
            expect($arr->to_string())->toBe('a,b,c');
            expect($arr->to_string(' | '))->toBe('a | b | c');
        });

        it('values 回傳值陣列', function () {
            $arr = Arr::create(['x' => 1, 'y' => 2, 'z' => 3]);
            expect($arr->values())->toEqual([1, 2, 3]);
        });

        it('keys 回傳鍵陣列', function () {
            $arr = Arr::create(['x' => 1, 'y' => 2, 'z' => 3]);
            expect($arr->keys())->toEqual(['x', 'y', 'z']);
        });
    });

    describe('diff 方法', function () {
        it('回傳差集', function () {
            $arr = Arr::create([1, 2, 3, 4, 5]);
            $result = $arr->diff([1, 2, 3]);
            expect($result->items)->toEqual([3 => 4, 4 => 5]);
        });

        it('當比較陣列更大時回傳空陣列', function () {
            $arr = Arr::create([1, 2, 3]);
            $result = $arr->diff([1, 2, 3, 4, 5]);
            expect($result->items)->toEqual([]);
        });
    });

    describe('push 和 unshift 方法', function () {
        it('push 在末尾添加元素', function () {
            $arr = Arr::create([1, 2]);
            $result = $arr->push([3]);
            expect($result->items)->toEqual([1, 2, [3]]);
        });

        it('unshift 在開頭添加元素', function () {
            $arr = Arr::create([2, 3]);
            $result = $arr->unshift([1]);
            expect($result->items)->toEqual([[1], 2, 3]);
        });
    });

    describe('pop 和 shift 方法', function () {
        it('pop 移除最後一個元素', function () {
            $arr = Arr::create([1, 2, 3]);
            $result = $arr->pop();
            expect($result->items)->toEqual([1, 2]);
        });

        it('shift 移除第一個元素', function () {
            $arr = Arr::create([1, 2, 3]);
            $result = $arr->shift();
            expect($result->items)->toEqual([2, 3]);
        });
    });

    describe('remove_duplicates 方法', function () {
        it('移除重複值', function () {
            $arr = Arr::create([1, 2, 2, 3, 3, 4]);
            $result = $arr->remove_duplicates();
            expect($result->items)->toEqual([1, 2, 3, 4]);
        });

        it('嚴格模式下保留類型不同的值', function () {
            $arr = Arr::create([1, '1', 2, '2']);
            $result = $arr->remove_duplicates(true);
            expect($result->items)->toEqual([1, '1', 2, '2']);
        });
    });

    describe('parse 方法', function () {
        it('將字串轉換為對應類型', function () {
            $arr = Arr::create([
                'empty' => '[]',
                'true_val' => 'true',
                'false_val' => 'false',
                'normal' => 'hello'
            ]);
            $result = $arr->parse();
            expect($result->items)->toEqual([
                'empty' => [],
                'true_val' => true,
                'false_val' => false,
                'normal' => 'hello'
            ]);
        });
    });
});