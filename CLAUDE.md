# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## 開發指令

### 程式碼品質檢查
- `composer run lint` - 執行 PHP_CodeSniffer 格式化與檢查 (phpcbf + phpcs)
- `composer run phpstan` - 對 src 目錄執行 PHPStan 靜態分析

### 自動載入
專案使用 PSR-4 自動載入，命名空間對應如下：
- `J7\WpHelpers\` → `src/helpers/`
- `J7\WpUtils\` → `src/utils/`
- `J7\WpAbstracts\` → `src/abstracts/`
- `J7\WpServices\` → `src/services/`
- `J7\WpUtils\Classes\` → `legacy/classes/` (舊版程式碼)
- `J7\WpUtils\Traits\` → `legacy/traits/` (舊版程式碼)

## 架構模式

### 何時使用 Services (`src/services/`)
- 有完整運作流程，擁有自己的 Enums 和 DTO (例如：Log 系統、Point 系統)
- 可被繼承做更高程度客製化
- 範例：Log 服務用於日誌系統，Point 服務用於點數系統

### 何時使用 Abstracts (`src/abstracts/`)
- 有自訂運作機制，實例化後會做驗證機制的類別
- 可能有非常多自訂規則、輔助方法，抽象只實現最核心功能
- 實例化要傳入的參數過多，使用抽象來規範
- 範例：`ExportCSV` 用於自訂匯出邏輯

### 何時使用 Helpers (`src/helpers/`)
- 實例化後經常需要反覆設定屬性，例如鏈式調用
- 支援方法鏈接功能
- 範例：`Arr` 類別用於陣列操作，具有流暢介面

### 何時使用 Utils (`src/utils/`)
- 靜態類別靜態方法調用
- 不需要狀態的工具函數
- 範例：`Time` 類別用於時間相關工具

## 程式碼標準

### PHP CodeSniffer 設定
- 基於 WordPress 程式碼標準
- 使用 tab 縮排 (相當於 4 個空格)
- 強制 PHP 8.1+ 相容性
- 排除 vendor/、node_modules/、tests/、js/ 目錄

### PHPStan 設定
- 第 9 級分析
- 包含 WordPress 和 WooCommerce stubs
- 忽略常見的 WordPress 相關類型問題

## 專案結構

### 活躍開發 (`src/`)
- `abstracts/` - 抽象基礎類別 (ApiBase, DTO, ExportCSV, ParseCSV)
- `helpers/` - 具有流暢介面的輔助類別 (Arr, Str, Table)
- `services/` - 完整服務實作 (Log 系統包含模型)
- `utils/` - 靜態工具類別 (IP, Performance, Shortcode, Time)

### 舊版程式碼 (`legacy/`)
- `classes/` - 舊版類別實作
- `traits/` - 舊版 trait 實作
- 注意：這些已標註為舊版，新開發應避免使用

### 依賴套件
- WordPress 外掛工具 (TGM Plugin Activation, Plugin Update Checker)
- WordPress Plugin Trait 提供常見外掛功能
- 開發工具：PHPStan, PHPCS 搭配 WordPress 標準

## 核心類別概覽

### ApiBase 抽象類別
- WordPress REST API 實作的基礎類別
- 需要子類別定義 `$apis` 陣列和 `$namespace`
- 在 `rest_api_init` 時自動註冊 API 端點

### Arr 輔助類別
- 提供類似 JavaScript 的陣列方法 (`some`, `every`, `find`)
- 支援 `filter` 和 `map` 的方法鏈接
- 可在嚴格模式下運作進行類型檢查

### Log 服務模型
- `PointType` - 點數類型的自訂文章類型 (積分、紅利點數等)
- Transaction 和 Wallet 模型用於點數管理系統

### Time 工具類別
- `wp_strtotime()` - 使用 WordPress 時區將本地時間字串轉換為時間戳記