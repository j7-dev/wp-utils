<?php

namespace J7\WpUtils\Classes;

if( class_exists( 'DTO' ) ) {
    return;
}

/** Class DTO 可能單例也可能多例，需要自行額外實現 instance() 方法 */
abstract class DTO {
    
    /** @var \WP_Error Error */
    protected \WP_Error $dto_error;
    
    /** @var static|null @deprecated 單例模式已經棄用 */
    protected static $dto_instance;
    
    /** @var array<string>|'ALL' 必須的屬性，如果沒有設定則會拋出錯誤, 如果是 ALL 代表所有屬性都必要 */
    protected array|string $require_properties = [];
    
    /** @var array<string, \ReflectionProperty[]> 靜態緩存各類別的屬性 */
    protected static array $reflection_cache = [];
    
    /** @var string 唯一 key，通常是 to_unique_key() 的結果，可以用來快取 */
    protected string $unique_key = '';
    
    /**
     * @param array<string,mixed> $dto_data The data to set.
     *
     * @return void
     * @throws \Exception If the property is not defined.
     */
    public function __construct( protected array $dto_data = [] ) {
        $this->dto_error = new \WP_Error();
        $strict = false;
        if( function_exists( 'wp_get_environment_type' ) ) {
            $strict = ( 'local' === \wp_get_environment_type() );
        }
        try {
            $this->before_init();
            foreach ( $this->dto_data as $key => $value ) {
                if( property_exists( $this, $key ) ) {
                    $this->$key = $value;
                }
            }
            $this->validate();
            $this->after_init();
            if( $this->dto_error->has_errors() ) {
                throw new \Exception( implode( "\n", $this->dto_error->get_error_messages() ) ); // phpcs:ignore
            }
        }
        catch ( \Throwable $th ) {
            $error_messages = $th->getMessage();
            // 如果嚴格模式，則拋出錯誤
            if( $strict ) {
                throw new \Exception( $error_messages ); // phpcs:ignore
            }
            // 如果有錯誤，則記錄錯誤
            WC::logger(
                'DTO Error ' . $error_messages, 'error', [], 'dto'
            );
        }
    }
    
    /**
     * 取得公開的屬性 array
     *
     * @return array<string,mixed>
     */
    public function to_array(): array {
        $class_name = static::class;
        
        // 使用靜態緩存避免重複反射操作
        if( !isset( static::$reflection_cache[$class_name] ) ) {
            $reflection = new \ReflectionClass( $this );
            static::$reflection_cache[$class_name] = $reflection->getProperties( \ReflectionProperty::IS_PUBLIC );
        }
        
        $props = static::$reflection_cache[$class_name];
        $result = [];
        
        foreach ( $props as $prop ) {
            // 如果沒被初始化就跳過
            if( !$prop->isInitialized( $this ) ) {
                continue;
            }
            
            $value = $prop->getValue( $this );
            $prop_name = $prop->getName();
            $result[$prop_name] = $this->get_formatted_value( $value );
        }
        
        return $result;
    }
    
    /**
     * 轉換為另一個 DTO
     * @param string $dto_class
     * @param callable|null $data_filter 在轉換前過濾資料的 callback function
     *
     * @return $this|self
     */
    public function to_dto( string $dto_class, callable $data_filter = null  ): self {
        try {
            $data = $this->to_array();
            if($data_filter){
                $data = $data_filter( $data );
            }
            return call_user_func( [ $dto_class, 'parse' ], $data ); // @phpstan-ignore-line
        }catch ( \Throwable $th ) {
            // 如果有錯誤，則記錄錯誤
            WC::logger(
                "DTO Error to_dto {$dto_class} failed: " . $th->getMessage(), 'critical', [], 'dto'
            );
            return $this;
        }
    }
    
    /**
     * 格式化 Value
     * to_array 時要把值轉為 primitive 值
     * 1. array 遞迴
     * 2. 巢狀 DTO 要巢狀 to_array
     * 3. 枚舉要轉為 value
     *
     * @param mixed $value The value to format.
     *
     * @return mixed
     */
    private function get_formatted_value( mixed $value ): mixed {
        if( is_array( $value ) ) {
            return array_map(
                fn( $item ) => $this->get_formatted_value( $item ), $value
            );
        }
        
        if( $value instanceof self ) {
            return $value->to_array();
        }
        
        if( $value instanceof \BackedEnum ) {
            return $value->value;
        }
        
        return $value;
    }
    
    /**
     * Set dynamic property to container.
     *
     * @param string $property The property name.
     * @param mixed  $value    The property value.
     *
     * @return void
     * @throws \Error SimpleDTOs are immutable
     */
    public function __set( string $property, mixed $value ): void {
        $this->dto_error->add( 'immutable', 'DTOs are immutable. Create a new DTO to set a new value.' );
    }
    
    /**
     * Check if the property is defined.
     *
     * @param string $property The property name.
     *
     * @return bool
     */
    public function __isset( string $property ): bool {
        return array_key_exists( $property, $this->dto_data );
    }
    
    
    /**
     *
     * @param mixed $data Parse data to DTO
     *
     * @return static
     * @throws \Exception 如果生成 DTO 錯誤
     */
    public static function parse( mixed $data ): static {
        return new static( $data ); // @phpstan-ignore-line
    }
    
    /**
     * Parse array to DTO array
     * 例如 物件組成的 array
     *
     * @param array<string,mixed> $input The data to parse.
     *
     * @return array<int,static>
     */
    public static function parse_array( array $input ): array {
        $arr = [];
        foreach ( $input as $item ) {
            $arr[] = static::parse( $item );
        }
        return $arr;
    }
    
    /** @return void 初始化前的處理。例如：初始化前 Merge 參數，提供預設參數 */
    protected function before_init(): void {}
    
    /** @return void 初始化後的處理 */
    protected function after_init(): void {}
    
    /**
     * @return void 驗證 DTO
     * @throws \Exception 如果驗證失敗則拋出錯誤
     * */
    protected function validate(): void {
        
        if( is_array( $this->require_properties ) ) {
            foreach ( $this->require_properties as $property ) {
                if( !isset( $this->$property ) ) {
                    throw new \Exception( "Property {$property} is required." );
                }
            }
            return;
        }
        
        $all_properties = get_object_vars( $this );
        foreach ( $all_properties as $property => $value ) {
            if( !isset( $this->$property ) ) {
                throw new \Exception( "Property {$property} is required." );
            }
        }
    }
    
    /**
     * 生成唯一的 key
     * @param bool $md5
     *
     * @return string
     */
    public function to_unique_key( bool $md5 = true ):string {
        if('' !== $this->unique_key) {
            return $this->unique_key;
        }
        $array = $this->to_array();
        ksort( $array );
        $json_string = \wp_json_encode( $array ) ?: '';
        if($md5){
            $json_string = md5(  $json_string );
        }
        $this->unique_key = $json_string;
        return  $json_string;
    }
}
