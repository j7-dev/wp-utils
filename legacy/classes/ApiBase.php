<?php

namespace J7\WpUtils\Classes;

/**
 * ApiBase class
 *
 * @deprecated 使用 \J7\WpAbstracts\ApiBase 替代
 * 用法:
 * 1. 繼承 ApiBase 類別
 * 2. child class 指定 $apis 和 $namespace 就好
 * 3. 填寫 API schema
 *
 * @see https://developer.wordpress.org/rest-api/extending-the-rest-api/schema/
 */
abstract class ApiBase extends \J7\WpAbstracts\ApiBase {
}
