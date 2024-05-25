<?php
/**
 * Plugin trait
 *
 * @package J7\WpUtils
 */

namespace J7\WpUtils;

if ( trait_exists( 'PluginTrait' ) ) {
	return;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
use J7_Required_Plugins;

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
	 * Required plugins
	 *
	 * @var array
	 */
	public $required_plugins = array(
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
	);

	/**
	 * Init
	 * Set the app_name, github_repo
	 *
	 * @example set_const( array( 'app_name' => 'My App', 'github_repo' => '' ) );
	 * @param array $args The arguments.
	 *
	 * @return void
	 */
	final public function init( array $args ): void {

		$this->set_const( $args );

		\register_activation_hook( __FILE__, array( $this, 'activate' ) );
		\register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

		$this->register_required_plugins();
		$this->set_puc_pat();
		$this->plugin_update_checker();
	}

	/**
	 * Set const
	 * Set the app_name, github_repo
	 *
	 * @example set_const( array( 'app_name' => 'My App', 'github_repo' => '' ) );
	 * @param array $args The arguments.
	 *
	 * @return void
	 */
	final public function set_const( array $args ): void {
		self::$app_name    = $args['app_name'];
		self::$kebab       = strtolower( str_replace( ' ', '-', $args['app_name'] ) );
		self::$snake       = strtolower( str_replace( ' ', '_', $args['app_name'] ) );
		self::$github_repo = $args['github_repo'];
	}

	/**
	 * Register required plugins
	 *
	 * @return void
	 */
	public function register_required_plugins(): void { // phpcs:ignore
		// phpcs:disable
		$config = array(
			'id'           => self::$kebab, // Unique ID for hashing notices for multiple instances of TGMPA.
			'default_path' => '', // Default absolute path to bundled plugins.
			'menu'         => 'tgmpa-install-plugins', // Menu slug.
			'parent_slug'  => 'plugins.php', // Parent menu slug.
			'capability'   => 'manage_options', // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true, // Show admin notices or not.
			'dismissable'  => false, // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => \__( '這個訊息將在依賴套件被安裝並啟用後消失。' . self::$app_name . ' 沒有這些依賴套件的情況下將無法運作！', 'wp_react_plugin' ), // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => true, // Automatically activate plugins after installation or not.
			'message'      => '', // Message to output right before the plugins table.
			'strings'      => array(
				'page_title'                      => \__( '安裝依賴套件', 'wp_react_plugin' ),
				'menu_title'                      => \__( '安裝依賴套件', 'wp_react_plugin' ),
				'installing'                      => \__( '安裝套件: %s', 'wp_react_plugin' ), // translators: %s: plugin name.
				'updating'                        => \__( '更新套件: %s', 'wp_react_plugin' ), // translators: %s: plugin name.
				'oops'                            => \__( 'OOPS! plugin API 出錯了', 'wp_react_plugin' ),
				'notice_can_install_required'     => \_n_noop(
					// translators: 1: plugin name(s).
					self::$app_name . ' 依賴套件: %1$s.',
					self::$app_name . ' 依賴套件: %1$s.',
					'wp_react_plugin'
				),
				'notice_can_install_recommended'  => \_n_noop(
					// translators: 1: plugin name(s).
					self::$app_name . ' 推薦套件: %1$s.',
					self::$app_name . ' 推薦套件: %1$s.',
					'wp_react_plugin'
				),
				'notice_ask_to_update'            => \_n_noop(
					// translators: 1: plugin name(s).
					'以下套件需要更新到最新版本來兼容 ' . self::$app_name . ': %1$s.',
					'以下套件需要更新到最新版本來兼容 ' . self::$app_name . ': %1$s.',
					'wp_react_plugin'
				),
				'notice_ask_to_update_maybe'      => \_n_noop(
					// translators: 1: plugin name(s).
					'以下套件有更新: %1$s.',
					'以下套件有更新: %1$s.',
					'wp_react_plugin'
				),
				'notice_can_activate_required'    => \_n_noop(
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
				'install_link'                    => \_n_noop(
					'安裝套件',
					'安裝套件',
					'wp_react_plugin'
				),
				'update_link'                     => \_n_noop(
					'更新套件',
					'更新套件',
					'wp_react_plugin'
				),
				'activate_link'                   => \_n_noop(
					'啟用套件',
					'啟用套件',
					'wp_react_plugin'
				),
				'return'                          => \__( '回到安裝依賴套件', 'wp_react_plugin' ),
				'plugin_activated'                => \__( '套件啟用成功', 'wp_react_plugin' ),
				'activated_successfully'          => \__( '以下套件已成功啟用:', 'wp_react_plugin' ),
				// translators: 1: plugin name.
				'plugin_already_active'           => \__( '沒有執行任何動作 %1$s 已啟用', 'wp_react_plugin' ),
				// translators: 1: plugin name.
				'plugin_needs_higher_version'     => \__( self::$app_name . ' 未啟用。' . self::$app_name . ' 需要新版本的 %s 。請更新套件。', 'wp_react_plugin' ),
				// translators: 1: dashboard link.
				'complete'                        => \__( '所有套件已成功安裝跟啟用 %1$s', 'wp_react_plugin' ),
				'dismiss'                         => \__( '關閉通知', 'wp_react_plugin' ),
				'notice_cannot_install_activate'  => \__( '有一個或以上的依賴/推薦套件需要安裝/更新/啟用', 'wp_react_plugin' ),
				'contact_admin'                   => \__( '請聯繫網站管理員', 'wp_react_plugin' ),

				'nag_type'                        => 'error', // Determines admin notice type - can only be one of the typical WP notice classes, such as 'updated', 'update-nag', 'notice-warning', 'notice-info' or 'error'. Some of which may not work as expected in older WP versions.
			),
		);

		\j7rp($this->required_plugins, $config );
	}

	/**
	 * Set Plugin Update Checker Personal Access Token
	 *
	 * @return array
	 */
	public static function set_puc_pat(): void{
		$env_file = self::$dir . '/.puc_pat';

		// Check if .env file exists
		if ( file_exists( $env_file ) ) {
			// Read contents of .env file
			$env_contents = file_get_contents( $env_file );
			self::$puc_pat = trim($env_contents);
		}
	}


	/**
	 * Plugin update checker
	 * When you push a new release to Github, user will receive updates in wp-admin/plugins.php page
	 *
	 * @return void
	 */
	public function plugin_update_checker(): void {
		try {
			$update_checker = PucFactory::buildUpdateChecker(
				self::$github_repo,
				__FILE__,
				self::$kebab
			);
			/**
			 * Type
			 *
			 * @var \Puc_v4p4_Vcs_PluginUpdateChecker $update_checker
			 */
			$update_checker->setBranch( 'master' );
			// if your repo is private, you need to set authentication
			// $update_checker->setAuthentication( self::$puc_pat );
			$update_checker->getVcsApi()->enableReleaseAssets();
		} catch ( \Throwable $th ) { // phpcs:ignore
			// throw $th;
		}
	}

		/**
		 * Activate
		 *
		 * @return void
		 */
		public function activate() { // phpcs:ignore
	}

		/**
		 * Deactivate
		 *
		 * @return void
		 */
		public function deactivate() { // phpcs:ignore
	}
}
