<?php
/**
 * Plugin trait
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils\Traits;

if (trait_exists('PluginTrait')) {
	return;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

trait PluginTrait {

	/**
	 * App Name
	 *
	 * @var string
	 */
	public static $app_name = '';

	/**
	 * Kebab Name
	 *
	 * @var string
	 */
	public static $kebab = '';

	/**
	 * Snake Name
	 *
	 * @var string
	 */
	public static $snake = '';

	/**
	 * Github Repo URL
	 *
	 * @var string
	 */
	public static $github_repo = '';


	/**
	 * Plugin Update Checker Personal Access Token
	 *
	 * @var string
	 */
	public static $puc_pat;

	/**
	 * Plugin Directory
	 *
	 * @var string
	 */
	public static $dir;

	/**
	 * Plugin URL
	 *
	 * @var string
	 */
	public static $url;

	/**
	 * Plugin Version
	 *
	 * @var string
	 */
	public static $version;

	/**
	 * Template Path
	 *
	 * @var string
	 */
	public static $template_path = '/inc';

	/**
	 * Template Page Names
	 *
	 * @var array
	 */
	public static $template_page_names = [ '404' ];

	/**
	 * Required plugins
	 *
	 * @var array
	 */
	public $required_plugins = [
		// array(
		// 'name'     => 'WooCommerce',
		// 'slug'     => 'woocommerce',
		// 'required' => true,
		// 'version'  => '7.6.0',
		// ),
		// array(
		// 'name'     => 'WP Toolkit',
		// 'slug'     => 'wp-toolkit',
		// 'source'   => 'https://github.com/j7-dev/wp-toolkit/releases/latest/download/wp-toolkit.zip',
		// 'required' => true,
		// ),
	];

	/**
	 * Callback after check required plugins
	 *
	 * @var array
	 */
	protected static $callback;

	/**
	 * Callback Args
	 *
	 * @var array
	 */
	protected static $callback_args = [];

	/**
	 * Plugin Entry File
	 *
	 * @var string
	 */
	protected static $plugin_entry_path;

	/**
	 * Init
	 * Set the app_name, github_repo, callback, callback_args
	 *
	 * @param array $args The arguments.
	 *
	 * @return void
	 * @example set_const( array( 'app_name' => 'My App', 'github_repo' => '', 'callback' => array($this, 'func') ) );
	 */
	final public function init( array $args ): void {

		$this->set_const($args);

		\register_activation_hook(self::$plugin_entry_path, [ $this, 'activate' ]);
		\register_deactivation_hook(self::$plugin_entry_path, [ $this, 'deactivate' ]);
		\add_action('plugins_loaded', [ $this, 'check_required_plugins' ]);
		\add_action( 'admin_menu', [ $this, 'add_debug_submenu_page' ] );

		$this->register_required_plugins();
		$this->set_puc_pat();
		$this->plugin_update_checker();
	}

	/**
	 * Set const
	 * Set the app_name, github_repo
	 *
	 * @param array $args The arguments.
	 *
	 * @return void
	 * @example set_const( array( 'app_name' => 'My App', 'github_repo' => '' ) );
	 */
	final public function set_const( array $args ): void {
		self::$app_name      = $args['app_name'];
		self::$kebab         = strtolower(str_replace(' ', '-', $args['app_name']));
		self::$snake         = strtolower(str_replace(' ', '_', $args['app_name']));
		self::$github_repo   = $args['github_repo'];
		self::$callback      = $args['callback'];
		self::$callback_args = $args['callback_args'] ?? [];
		if (isset($args['template_path'])) {
			self::$template_path = $args['template_path'];
		}
		if (isset($args['template_page_names'])) {
			self::$template_page_names = $args['template_page_names'];
		}

		$reflector               = new \ReflectionClass(get_called_class());
		self::$plugin_entry_path = $reflector?->getFileName();

		self::$dir = \untrailingslashit(\wp_normalize_path(\plugin_dir_path(self::$plugin_entry_path)));
		self::$url = \untrailingslashit(\plugin_dir_url(self::$plugin_entry_path));
		if (!\function_exists('get_plugin_data')) {
			require_once \ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data   = \get_plugin_data(self::$plugin_entry_path);
		self::$version = $plugin_data['Version'];
	}

	/**
	 * Register required plugins
	 *
	 * @return void
	 */
	final public function register_required_plugins(): void
    { // phpcs:ignore
        // phpcs:disable
        $config = array(
            'id' => self::$kebab, // Unique ID for hashing notices for multiple instances of TGMPA.
            'default_path' => '', // Default absolute path to bundled plugins.
            'menu' => 'tgmpa-install-plugins', // Menu slug.
            'parent_slug' => 'plugins.php', // Parent menu slug.
            'capability' => 'manage_options', // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
            'has_notices' => true, // Show admin notices or not.
            'dismissable' => false, // If false, a user cannot dismiss the nag message.
            'dismiss_msg' => \__('這個訊息將在依賴套件被安裝並啟用後消失。' . self::$app_name . ' 沒有這些依賴套件的情況下將無法運作！', 'wp_react_plugin'), // If 'dismissable' is false, this message will be output at top of nag.
            'is_automatic' => true, // Automatically activate plugins after installation or not.
            'message' => '', // Message to output right before the plugins table.
            'strings' => array(
                'page_title' => \__('安裝依賴套件', 'wp_react_plugin'),
                'menu_title' => \__('安裝依賴套件', 'wp_react_plugin'),
                'installing' => \__('安裝套件: %s', 'wp_react_plugin'), // translators: %s: plugin name.
                'updating' => \__('更新套件: %s', 'wp_react_plugin'), // translators: %s: plugin name.
                'oops' => \__('OOPS! plugin API 出錯了', 'wp_react_plugin'),
                'notice_can_install_required' => \_n_noop(
                // translators: 1: plugin name(s).
                    self::$app_name . ' 依賴套件: %1$s.',
                    self::$app_name . ' 依賴套件: %1$s.',
                    'wp_react_plugin'
                ),
                'notice_can_install_recommended' => \_n_noop(
                // translators: 1: plugin name(s).
                    self::$app_name . ' 推薦套件: %1$s.',
                    self::$app_name . ' 推薦套件: %1$s.',
                    'wp_react_plugin'
                ),
                'notice_ask_to_update' => \_n_noop(
                // translators: 1: plugin name(s).
                    '以下套件需要更新到最新版本來兼容 ' . self::$app_name . ': %1$s.',
                    '以下套件需要更新到最新版本來兼容 ' . self::$app_name . ': %1$s.',
                    'wp_react_plugin'
                ),
                'notice_ask_to_update_maybe' => \_n_noop(
                // translators: 1: plugin name(s).
                    '以下套件有更新: %1$s.',
                    '以下套件有更新: %1$s.',
                    'wp_react_plugin'
                ),
                'notice_can_activate_required' => \_n_noop(
                // translators: 1: plugin name(s).
                    '以下依賴套件目前為停用狀態: %1$s.',
                    '以下依賴套件目前為停用狀態: %1$s.',
                    'wp_react_plugin'
                ),
                'notice_can_activate_recommended' => \_n_noop(
                // translators: 1: plugin name(s).
                    '以下推薦套件目前為停用狀態: %1$s.',
                    '以下推薦套件目前為停用狀態: %1$s.',
                    'wp_react_plugin'
                ),
                'install_link' => \_n_noop(
                    '安裝套件',
                    '安裝套件',
                    'wp_react_plugin'
                ),
                'update_link' => \_n_noop(
                    '更新套件',
                    '更新套件',
                    'wp_react_plugin'
                ),
                'activate_link' => \_n_noop(
                    '啟用套件',
                    '啟用套件',
                    'wp_react_plugin'
                ),
                'return' => \__('回到安裝依賴套件', 'wp_react_plugin'),
                'plugin_activated' => \__('套件啟用成功', 'wp_react_plugin'),
                'activated_successfully' => \__('以下套件已成功啟用:', 'wp_react_plugin'),
                // translators: 1: plugin name.
                'plugin_already_active' => \__('沒有執行任何動作 %1$s 已啟用', 'wp_react_plugin'),
                // translators: 1: plugin name.
                'plugin_needs_higher_version' => \__(self::$app_name . ' 未啟用。' . self::$app_name . ' 需要新版本的 %s 。請更新套件。', 'wp_react_plugin'),
                // translators: 1: dashboard link.
                'complete' => \__('所有套件已成功安裝跟啟用 %1$s', 'wp_react_plugin'),
                'dismiss' => \__('關閉通知', 'wp_react_plugin'),
                'notice_cannot_install_activate' => \__('有一個或以上的依賴/推薦套件需要安裝/更新/啟用', 'wp_react_plugin'),
                'contact_admin' => \__('請聯繫網站管理員', 'wp_react_plugin'),

                'nag_type' => 'error', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
            ),
        );

        \j7rp($this->required_plugins, $config);
    }

    /**
     * Set Plugin Update Checker Personal Access Token
     *
     * @return array
     */
    public static function set_puc_pat(): void
    {
        $puc_pat_file = self::$dir . '/.puc_pat';

        // Check if .env file exists
        if (file_exists($puc_pat_file)) {
            // Read contents of .env file
            $env_contents = file_get_contents($puc_pat_file);
            self::$puc_pat = trim($env_contents);
        }
    }


    /**
     * Plugin update checker
     * When you push a new release to Github, user will receive updates in wp-admin/plugins.php page
     *
     * @return void
     */
    public function plugin_update_checker(): void
    {
        try {
            $update_checker = PucFactory::buildUpdateChecker(
                self::$github_repo,
                self::$plugin_entry_path,
                self::$kebab
            );
            /**
             * Type
             *
             * @var \Puc_v4p4_Vcs_PluginUpdateChecker $update_checker
             */
            $update_checker->setBranch('master');
            // if your repo is private, you need to set authentication
            if (self::$puc_pat) {
                $update_checker->setAuthentication(self::$puc_pat);
            }
            $update_checker->getVcsApi()->enableReleaseAssets();
        } catch (\Throwable $th) { // phpcs:ignore
            // throw $th;
        }
    }


    /**
     * Check required plugins
     *
     * @return void
     */
    public function check_required_plugins(): void
    {
        $instance = \J7_Required_Plugins::get_instance(self::$kebab);
        $is_j7rp_complete = $instance->is_j7rp_complete();

        if ($is_j7rp_complete) {
            if (is_callable(self::$callback)) {
                call_user_func_array(self::$callback, self::$callback_args);
            }
        }
    }

    private function read_debug_log() {
        $log_path = WP_CONTENT_DIR . '/debug.log'; // 使用 WP_CONTENT_DIR 常量定义日志文件路径
        if ( \file_exists( $log_path ) ) { // 检查文件是否存在
            $lines       = \file( $log_path ); // 读取文件到数组中，每行是一个数组元素
            $lastLines   = \array_slice( $lines, -1000 ); // 获取最后1000行
            $log_content = \implode( '', $lastLines ); // 将数组元素合并成字符串
            if ( !$log_content ) {
                // 处理读取错误
                return 'Error reading log file.';
            }
            return \nl2br( \esc_html( $log_content ) ); // 将换行符转换为HTML换行，并转义内容以避免XSS攻击
        } else {
            return 'Log file does not exist.';
        }
    }

    public function add_debug_submenu_page() {
        global $submenu;

        // 检查 tools.php 菜单是否存在
        if (isset($submenu['tools.php'])) {
            $debug_log_exists = false;

            // 遍历 tools.php 的子菜单
            foreach ($submenu['tools.php'] as $item) {
                // 检查是否已存在 debug-log-viewer
                if ($item[2] === 'debug-log-viewer') {
                    $debug_log_exists = true;
                    break;
                }
            }

            // 如果 debug-log-viewer 不存在，则添加子菜单
            if (!$debug_log_exists) {
                \add_submenu_page(
                    'tools.php', // 父菜单文件，指向工具菜单
                    'Debug Log Viewer', // 页面标题
                    'Debug Log', // 菜单标题
                    'manage_options', // 所需的权限，例如管理选项
                    'debug-log-viewer', // 菜单slug
                    array( $this, 'debug_log_page_content' ), // 用于渲染页面内容的回调函数
                    1000
                );
            }
        }
    }

    /**
     * Debug Log Page Content
     *
     * @return void
     */
    public function debug_log_page_content(): void
    {
        // 这里是渲染内容的函数，可以调用之前创建的 read_debug_log() 函数
        echo '<div class="wrap"><h1>Debug Log</h1>';
        echo '<p>只顯示 <code>/wp-content/debug.log</code> 最後 1000 行</p>';
        echo '<pre style="line-height: 0.75;">' . $this->read_debug_log() . '</pre></div>'; // 使用 <pre> 标签格式化文本输出
    }

			/**
	 * 從指定的模板路徑讀取模板文件並渲染數據
	 *
	 * @param string $name 指定路徑裡面的文件名
	 * @param mixed  $args 要渲染到模板中的數據
	 * @param bool   $output 是否輸出
	 * @param bool   $load_once 是否只載入一次
	 *
	 * @return ?string
	 * @throws \Exception 如果模板文件不存在.
	 */
	public static function get(
		string $name,
		mixed $args = null,
		?bool $output = true,
		?bool $load_once = false,
	): ?string {
		$result = self::safe_get( $name, $args, $output, $load_once );
		if ( '' === $result ) {
			throw new \Exception( "模板文件 {$name} 不存在" );
		}

		return $result;
	}

	/**
	 * 從指定的模板路徑讀取模板文件並渲染數據
	 *
	 * @param string $name 指定路徑裡面的文件名
	 * @param mixed  $args 要渲染到模板中的數據
	 * @param bool   $echo 是否輸出
	 * @param bool   $load_once 是否只載入一次
	 *
	 * @return string|false|null
	 * @throws \Exception 如果模板文件不存在.
	 */
	public static function safe_get(
		string $name,
		mixed $args = null,
		?bool $echo = true,
		?bool $load_once = false,
	): string|false|null {

		// 如果 $name 是以 page name 開頭的，那就去 page folder 裡面找
		$is_page = false;
		foreach ( self::$template_page_names as $page_name ) {
			if ( str_starts_with( $name, $page_name ) ) {
				$is_page = true;
				break;
			}
		}

		if ( $is_page ) {
			$template_path = self::$dir . self::$template_path .  '/templates/pages/' . $name;
		} else { // 不是區域名稱就去 components 裡面找
			$template_path = self::$dir . self::$template_path .  '/templates/components/' . $name;
		}

		// 檢查模板文件是否存在
		if ( file_exists( "{$template_path}.php" ) ) {
			if ( $echo ) {
				\load_template( "{$template_path}.php", $load_once, $args );

				return null;
			}
			ob_start();
			\load_template( "{$template_path}.php", $load_once, $args );

			return ob_get_clean();
		} elseif ( file_exists( "{$template_path}/index.php" ) ) {
			if ( $echo ) {
				\load_template( "{$template_path}/index.php", $load_once, $args );

				return null;
			}
			ob_start();
			\load_template( "{$template_path}/index.php", $load_once, $args );

			return ob_get_clean();
		}

		return '';
	}

    /**
     * Activate
     *
     * @return void
     */
    final public function activate(): void
    {
    }

    /**
     * Deactivate
     *
     * @return void
     */
    final public function deactivate(): void
    {
    }
}
