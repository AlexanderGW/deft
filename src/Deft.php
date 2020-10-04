<?php

/**
 * Deft, a micro framework for PHP.
 *
 * @author Alexander Gailey-White <alex@gailey-white.com>
 *
 * This file is part of Deft.
 *
 * Deft is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Deft is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Deft.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Common directory separator
 */
define('DS', '/');

/**
 * UTC
 */
define('TIME_UTC', (time() - date('Z')));

/**
 * Class Deft
 */
class Deft {
	const VERSION = '0.10-dev';

	const PLUGIN_LOADED = 2;
	const PLUGIN_EXISTS = 1;
	const PLUGIN_MISSING = 0;

	/**
	 * App parameters
	 *
	 * @var array
	 */
	private static $config = array();

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	/**
	 * Class instance storage.
	 *
	 * @var array
	 */
	private static $instances = array();

	/**
	 * Log storage.
	 *
	 * @var array
	 */
	private static $logs = array();

	/**
	 * App environment is CLI?
	 *
	 * @var bool
	 */
	public static $cli = false;

	/**
	 * App start time in micro seconds.
	 *
	 * @var int
	 */
	public static $start = 0;

	/**
	 * App get() calls
	 *
	 * @var array
	 */
	private static $calls = [];

	/**
	 * Initialise Deft
	 *
	 * @param array $args
	 */
	static function init ($config = []) {
		if (self::$initialized || !is_array($config)) {
			return null;
		}

//		set_error_handler(['Deft', 'errorHandler']);

		self::$config = array_merge(array(
			'config_format'          => 'php',
			'debug'                  => 0,
			'directory.lib'          => 'Lib',
			'directory.plugin'       => 'Plugin',
			'directory.storage'      => 'storage',
			'directory.tmp'          => 'tmp',
			'directory.public'       => 'public',
			'directory.public.asset' => 'asset',
			'plugins'                => array('debug', 'example'),
			'url_separator'          => '/'
		), $config);

		define('DEFT_DEBUG', (int) self::$config['debug']);

		error_reporting((DEFT_DEBUG > 0) ? 9 : 0);

		$backtrace = debug_backtrace();

		if (!defined('DEFT_INITIATOR')) {
			$first     = $backtrace[count($backtrace) - 1];
			define('DEFT_INITIATOR', $first['file']);
		}

		// Absolute path
		if (!defined('DEFT_ABS_PATH'))
			define('DEFT_ABS_PATH', __DIR__);

		// App paths
		define('DEFT_PATH', str_replace("\\", '/', DEFT_ABS_PATH));

		define('DEFT_LIB_PATH', DEFT_PATH . DS . self::$config['directory.lib']);
		if (!is_dir(DEFT_LIB_PATH) || !is_readable(DEFT_LIB_PATH))
			self::error('Cannot read from library directory: %1$s', DEFT_LIB_PATH);

		// Storage path
		if (!array_key_exists('path.storage', self::$config))
			self::$config['path.storage'] = DEFT_PATH . DS . self::$config['directory.storage'];
		define('DEFT_STORAGE_PATH', self::$config['path.storage']);

		// Temporary path
		if (!array_key_exists('path.tmp', self::$config))
			self::$config['path.tmp'] = DEFT_STORAGE_PATH . DS . self::$config['directory.tmp'];
		define('DEFT_TMP_PATH', self::$config['path.tmp']);

		define('DEFT_PLUGIN_PATH', DEFT_PATH . DS . self::$config['directory.plugin']);

		if (!array_key_exists('path.public', self::$config))
			self::$config['path.public'] = DEFT_PATH . DS . self::$config['directory.public'];
		define('DEFT_PUBLIC_PATH', self::$config['path.public']);

		if (!array_key_exists('path.public.asset', self::$config))
			self::$config['path.public.asset'] = DEFT_PUBLIC_PATH . DS . self::$config['directory.public.asset'];
		define('DEFT_PUBLIC_ASSET_PATH', self::$config['path.public.asset']);

		// Libraries to load
		$array = self::import(
			'lib.helper',
			'lib.route',
			'lib.sanitize',
			'lib.random',
			'lib.element',
			'lib.token',
			'lib.plugin'
		);
		if (count($array)) {
			self::error('Failed to import core libraries: %1$s', implode(', ', $array));
		}

		// CLI environment?
		self::$cli = (PHP_SAPI  == 'cli' && !defined('DEFT_TESTING'));

		// Deft init time
		self::$start = \Deft\Lib\Helper::getMicroTime();

		// Process request
		$request = \Deft::request();

		// CLI environment
		if (self::$cli === true) {
			if (count(self::import(
				'lib.cli'
			))) {
				self::error('Failed to import CLI library');
			}

			$args = $request->args();
			define('DEFT_ROUTE',
				array_shift($args)
			);
		} else {

			// URLs
			define('DEFT_URL_PATH', str_replace(
				str_replace("\\", '/', $_SERVER['DOCUMENT_ROOT']),
				'',
				str_replace("\\", '/', dirname(DEFT_INITIATOR))
			));
			define('DEFT_URL',
				$request->scheme() . '://' .
				$request->host() .
				($request->port() <> 80 ? ':' . $request->port() : NULL) .
				DEFT_URL_PATH
			);
			define('DEFT_ASSET_URL', DEFT_URL . '/' . self::$config['directory.public.asset'] . '/');
			define('DEFT_PLUGIN_URL', DEFT_URL . '/' . self::$config['directory.plugin'] . '/');

			// Requested route relative to Deft URL.
			define('DEFT_ROUTE',
				\Deft\Lib\Sanitize::forHtml(
					\Deft\Lib\Sanitize::forText(
						substr(
							$request->path(),
							(strlen(DEFT_URL_PATH . '/'))
						)
					)
				)
			);

			// URL separator
			define('US', self::$config['url_separator']);
		}

		// Prevent 'Deft::init()' from being called again
		self::$initialized = true;

		// Runtime plugins
		if (count(self::$config['plugins'])) {

			// Prepare plugin paths
			$paths = [];
			if (array_key_exists('path.plugins', self::$config))
				$paths[] = self::$config['path.plugins'];
			$paths[] = DEFT_PLUGIN_PATH;

			$plugins = self::$config['plugins'];
			$ext = '.php';

			foreach ($paths as $i => $path) {
				foreach ($plugins as $k => $plugin) {
					$state = Deft::PLUGIN_MISSING;
					$plugin_path = $path . DS . $plugin;

					if (file_exists($plugin_path . $ext)) {
						$state = Deft::PLUGIN_EXISTS;
					} elseif (is_dir($plugin_path) && file_exists($plugin_path . DS . $plugin . $ext)) {
						$state = Deft::PLUGIN_EXISTS;
						$plugin_path .= DS . $plugin;
					}

					$start = 0;
					if ($state === Deft::PLUGIN_EXISTS) {
						$start = \Deft\Lib\Helper::getMicroTime();
						$plugin_path .= $ext;
						include $plugin_path;
						$state = Deft::PLUGIN_LOADED;
						unset($plugins[$k]);
					}

					self::stack('plugin/' . $plugin, array(
						'time'  => ($state ? \Deft\Lib\Helper::getMoment($start) : 0),
						'loaded' => $state,
						'path' => $plugin_path
					));
				}
			}
		}

		// Run this callback after initialization
		if (array_key_exists('ready_callback', self::$config)) {
			$callback = self::$config['ready_callback'];
			if (is_callable($callback)) {
				\Deft::event()->set('ready', $callback);
			}
		}

		// Execute initialization
		$init = \Deft::event()->exec('init');

		// Execute ready, after initialization
		$ready = \Deft::event()->exec('ready');

		// No content
		if ($init === FALSE && $ready === FALSE) {
			self::response()->status(204);
		}

		// Execute exit event
		\Deft::event()->exec('exit');
	}

	/**
	 * Import libraries relative to DEFT_PATH for use within the App.
	 *
	 * Dot-scope format "lib.response.http.html" for "DEFT_PATH/lib/response/http/html.php" class.
	 *
	 * @return array
	 */
	static function import ( /*polymorphic*/) {
		$result = array();
		if (func_num_args()) {
			$args = func_get_args();
			foreach ($args as $arg) {
				if (empty($arg) || strpos($arg, '.') === FALSE) {
					continue;
				}

				$pieces = explode('.', $arg);
				foreach ($pieces as $i => $piece)
					$pieces[$i] = ucfirst($piece);

				$path = DEFT_PATH . DS . implode(DS, $pieces) . '.php';
				if (!is_readable($path) || !is_file($path)) {
					$result[] = $path;
				} else {
					include_once $path;
				}
			}
		}

		return $result;
	}

	/**
	 * Return state of specified plugin.
	 *
	 * @param string $name
	 */
	public static function havePlugin ($name = null) {
		if (is_null($name)) {
			return;
		}
		$path   = DEFT_PLUGIN_PATH . DS . $name;
		$plugin = self::stack('plugin/' . $name);
		if (count($plugin) and $plugin[0]['loaded']) {
			return self::PLUGIN_LOADED;
		} elseif (file_exists($path . '.php') or is_dir($path)) {
			return self::PLUGIN_EXISTS;
		}

		return self::PLUGIN_MISSING;
	}

	/**
	 * Get unique instance key for with class with arguments
	 *
	 * @param null $class
	 * @param null $seed
	 *
	 * @return string
	 */
	static function getInstanceKey ($class = null, $seed = null) {
		return ($class . '#' . md5(serialize($seed)));
	}

	/**
	 * Checks if an instance exists by key.
	 *
	 * @param null $key
	 *
	 * @return bool
	 */
	static function haveInstance ($key = null) {
		if (!is_string($key) and strlen($key) <> 32) {
			return false;
		}

		return array_key_exists($key, self::$instances);
	}

	/**
	 * Create new instance of class with arguments.
	 *
	 * HelperExample for "helper/example.php" class.
	 *
	 * @param null $scope
	 * @param array $args
	 */
	static function newInstance ($class = null, $args = array()) {
		if (is_null($class) or !class_exists($class)) {
			return NULL;
		}

		$key = self::getInstanceKey($class, $args);
		$start = \Deft\Lib\Helper::getMicroTime();

		self::$instances[ $key ] = new $class( $args );
		self::stack("instance/{$class}/{$key}", array(
			'time' => \Deft\Lib\Helper::getMoment($start),
			'args' => $args
		));
		self::stack("instance/{$class}/{$key}/calls", TRUE);

		return self::$instances[$key];
	}

	/**
	 * Returns the instance of class with arguments, will create instance if does not exist.
	 *
	 * Dot-scope format "lib.response.http.html" for "lib/response/http/html.php" class.
	 *
	 * @param null $scope
	 * @param array $args
	 */
	static function &get ($scope = null, $args = array()) {
		if (!is_string($scope)) {
			return;
		}

		$class = '\\Deft\\' . str_replace(' ', '\\', ucwords(str_replace('.', ' ', $scope)));

		$stack = "{$scope}";

		$log = self::stack($stack);

		// First time calls may need to import library
		if (!count($log)) {

			// Import if not already
			if (!class_exists($class)) {
				$array = \Deft\Lib\Helper::explodeLevel($scope);
				$array[] = $scope;
				$errors = call_user_func_array([__CLASS__, 'import'], $array);
				if (count($errors)) {
					\Deft::error('Failed to import and instantiate: %1$s', implode(', ', $errors));
				}
			}

			self::stack($stack);
		}

		// Generate instance key
		$key = self::getInstanceKey($class, $args);

		// Check instance cache
		$result = self::haveInstance($key);

		// Return existing
		if ($result) {
			self::stack("instance/{$class}/{$key}/calls", TRUE);

			return self::$instances[$key];
		}
		// Create and return
		$instance = self::newInstance($class, $args);

		return $instance;
	}

	public static function lib ($scope, $args = array()) {
		return \Deft::get('lib.' . $scope, $args);
	}

	/**
	 * @return \Deft\Lib\Log
	 */
	public static function log ($args = array()) {
		return self::lib('log', $args);
	}

	public static function cache ($args = array()) {
		return self::lib('cache', $args);
	}

	public static function config ($args = array()) {
		if (is_string($args))
			$args = [
				'scope' => $args
			];

		$scope = 'config';
		if (is_array($args)) {
			if (!count($args))
				$args = self::$config;
			else {
				$args = array_merge(array(
					'scope' => 'config.deft',
					'format' => 'php',
					'filesystem.type' => 'local'
				), $args);

				if (array_key_exists('format', $args) && is_string($args['format']))
					$args['format'] = 'php';

				$scope = "config.{$args['format']}";
			}
		}

		return self::lib($scope, $args);
	}

	public static function database ($args = []) {
		$config  = self::config();
		return self::storage(array_merge(array(
			'structure'    => $config->get('database.structure', 'relational'),
			'type'         => $config->get('database.type', 'sql'),
			'driver'       => $config->get('database.driver'),
			'host'         => $config->get('database.hostname'),
			'username'     => $config->get('database.username'),
			'password'     => $config->get('database.password'),
			'dbname'       => $config->get('database.name'),
			'table_prefix' => $config->get('database.table.prefix'),
			'port'         => $config->get('database.port')
		), $args));
	}

	public static function storage ($args = []) {
		$c  = self::config();
		$args = array_merge(array(
			'structure'    => $c->get('storage.structure', 'filesystem'),
			'type'         => $c->get('storage.type', 'local')
		), $args);

		$scope = 'storage';
		self::import('lib.' . $scope);
		$scope .= '.' . $args['structure'];
		self::import('lib.' . $scope);
		$scope .= '.' . $args['type'];
		self::import('lib.' . $scope);

		if (array_key_exists('driver', $args) && is_string($args['driver']))
			$scope .= '.'.$args['driver'];

		return self::lib($scope, $args);
	}

//	public static function document ($args = array()) {
//		$args = array_merge([
//			'type' => 'html5',
//		], $args);
//
//		if ($args['type'] == 'html5') {
//			$args = array_merge(array(
//				'base'      => null,
//				'encoding'  => 'utf-8',
//				'locale'    => 'en',
//				'direction' => 'ltr',
//				'mime'      => 'text/html'
//			), $args);
//		}
//
//		return self::lib('document', $args);
//	}

	public static function event () {
		return self::lib('event');
	}

	public static function filter () {
		return self::lib('filter');
	}

	public static function form ($id = null) {
		return self::lib('form', $id);
	}

	public static function locale () {
		return self::lib('locale');
	}

	/**
	 * @return \Deft\Lib\Request\Cli|\Deft\Lib\Request\Http
	 */
	public static function request () {
		$scope = 'request.' . (self::$cli ? 'cli' : 'http');
		$return = self::lib($scope);
		return $return;
	}

	/**
	 * @return \Deft\Lib\Route
	 */
	public static function route () {
		return self::lib('route');
	}

	/**
	 * @return \Deft\Lib\Filesystem
	 */
	public static function filesystem ($args = []) {
		$config = self::config();

		/** @returns \Deft\Lib\Storage\Filesystem\Local */
		return self::storage($args = array_merge(array(
			'structure' => $config->get('filesystem.type', 'filesystem'),
			'type' => $config->get('filesystem.type', 'local')
		), $args));
	}

	/**
	 * @param array $args
	 *
	 * @return \Deft\Lib\Response
	 */
	public static function response ($args = []) {
		$config = self::config();

		if ($config) {
			$args = array_merge(array(
				'type' => $config->get('response.type', 'http.html'),
				'base'      => $config->get('response.base'),
				'encoding'  => $config->get('response.encoding', 'utf-8'),
				'locale'    => $config->get('response.locale', 'en'),
				'direction' => $config->get('response.direction', 'ltr'),
				'mime'      => $config->get('response.mime', 'text/html')
			), $args);
		}

		$scope = 'response';
		if ($args['type'])
			$scope .= '.' . $args['type'];

		return self::lib($scope, $args);
	}

	/**
	 * Encodes $args into serialized string with App secret.
	 *
	 * @param mixed $args
	 *
	 * @return string
	 */
	static function encode ($args = null) {
		if (is_null($args)) {
			return;
		}
		$config = self::config();

		$secret = $config->get('secret');
		if (empty($secret)) {
			$config->set('secret', str_shuffle(\Deft\Lib\Helper::ALPHANUMERIC_CHARS . \Deft\Lib\Helper::EXTENDED_CHARS));
			$config->save();
		}

		return strtr(
			serialize($args),
			\Deft\Lib\Helper::ALPHANUMERIC_CHARS . \Deft\Lib\Helper::EXTENDED_CHARS,
			$secret
		);
	}

	/**
	 * Decodes serialized string into object with App secret.
	 *
	 * @param null $path
	 *
	 * @return bool|mixed|string|void
	 */
	static function decode ($string = null) {
		if (!is_string($string)) {
			return;
		}

		$secret = self::config()->get('secret');
		if (!$secret) {
			return;
		}

		$decoded = strtr(
			$string,
			$secret,
			\Deft\Lib\Helper::ALPHANUMERIC_CHARS . \Deft\Lib\Helper::EXTENDED_CHARS
		);

		$result = @unserialize($decoded);
		if ($result !== false) {
			return $result;
		}

		return;
	}

	/**
	 * Capture and return output of a script.
	 *
	 * @param null $scope
	 * @param null $hash
	 */
	static function capture ($scope = null, $hash = null) {
		if (!is_string($scope)) {
			return;
		}

		$config  = self::config();
		if (is_null($hash))
			$hash = $config->get('capture_hash');

		if (is_null($hash)) {
			$hash = \Deft\Lib\Random::getMd5();
			$config->set('capture_hash', $hash);
		}

		${'capture_scope__' . $hash} = strtolower(\Deft::filter()->exec('beforeCapture', $scope));

		$path = ${'capture_scope__' . $hash};
		if (substr($path, 0, 7) === 'plugin.')
			$path = ucfirst($path);

		$path  = DEFT_PATH . DS . str_replace('.', DS, $path) . '.php';

		if (!file_exists($path)) {
			return;
		}

		${'capture_path__' . $hash} = $path;
		\Deft::event()->exec('beforeCapture',
			\Deft::event()->exec('beforeCapture__' . $hash, ${'capture_scope__' . $hash})
		);


		${'capture_start__' . $hash} = \Deft\Lib\Helper::getMicroTime();

		ob_start();
		include ${'capture_path__' . $hash};
		${'capture_content__' . $hash} = ob_get_contents();
		ob_end_clean();

		self::stack('capture/' . ${'capture_scope__' . $hash}, array(
			'time' => \Deft\Lib\Helper::getMoment(${'capture_start__' . $hash})
		));

		return \Deft::filter()->exec('captureContent', ${'capture_content__' . $hash});
	}

	/**
	 * Append log stack entry
	 *
	 * Slash-scope format "app/state"
	 *
	 * @param null $stack
	 * @param $args
	 * @param bool|false $replace
	 */
	static function stack ($stack = null, $args = -1, $replace = false) {

		// Nothing provided, return the entire stack
		if (is_null($stack))
			return self::$logs;

		// Default the stack
		if (!is_string($stack))
			$stack = 'app';

		// Args not provided, return the stack
		if ($args === -1) {
			if (array_key_exists($stack, self::$logs)) {
				return self::$logs[$stack];
			}
			return [];
		}

		// Explicitly passed NULL, clear the stack
		if (is_null($args)) {
			self::$logs[$stack] = [];
			return TRUE;
		}

		// Stack entry data
		$entry = array('moment' => \Deft\Lib\Helper::getMoment()) + (array) $args;

		// Replace all stack entries
		if ($replace) {
			self::$logs[$stack] = [
				$entry
			];
		}

		// Append to the stack
		else {
			if (!array_key_exists($stack, self::$logs))
				self::$logs[$stack] = [];
			self::$logs[$stack][] = $entry;
		}
	}

	/**
	 * Raises App critical error
	 */
	static function error ( /*polymorphic*/) {
		if (!func_num_args()) {
			return;
		}

		\Deft::event()->exec('onDeftError');

		$template = \Deft::filter()->exec('DeftErrorTemplate', [
			'@tag' => 'html',
			'@markup' => [
				'@tag' => 'body',
				'@markup' => [
					'@tag' => '',
				]
			]
		]);

		//if (\Deft::response()->status(500) !== true) {
			echo '<h1>' . __('App error') . '</h1>';
			if (DEFT_DEBUG > 0) {

			}
			var_dump(func_get_args());
		//}
	}

	public static function errorHandler($errno, $errstr, $errfile, $errline) {
		if (!(error_reporting() & $errno)) {
			// This error code is not included in error_reporting, so let it fall
			// through to the standard PHP error handler
			return false;
		}

		switch ($errno) {
			case E_USER_ERROR:
				echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
				echo "  Fatal error on line $errline in file $errfile";
				echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
				echo "Aborting...<br />\n";
				exit(1);
				break;

			case E_USER_WARNING:
				echo "<b>My WARNING</b> [$errno] $errstr<br />\n";
				break;

			case E_USER_NOTICE:
				echo "<b>My NOTICE</b> [$errno] $errstr<br />\n";
				break;

			default:
				echo "Unknown error type: [$errno] $errstr<br />\n";
				break;
		}

		/* Don't execute PHP internal error handler */
		return true;
	}
}

/**
 * Class Deft_Concrete for instantiation based classes
 */
class Deft_Concrete {

	/**
	 * @var array
	 */
	protected $args = array();

	/**
	 * @var null|string*
	 */
	protected $stack = null;

	/**
	 * App_Class constructor.
	 *
	 * @param array $args
	 */
	public function __construct ($args = array(), $class = __CLASS__) {
		$this->args = self::getArgs($args);
		$this->stack = 'instance/' . get_class($this) . '/' . \Deft::getInstanceKey(get_class($this), $args);

		if (is_string($class)) {
			\Deft::event()->exec("on{$class}Construct", $this);
		}
		\Deft::event()->exec('onDeftConcrete', $this);
	}

	/**
	 * @param array $args
	 */

	public static function getArgs ($args = array()) {
		return $args;
	}

	/**
	 *
	 */

	public function getStack () {
		return $this->stack;
	}

	/**
	 * @param null $arg
	 */
	public function get ($key = null) {
		if ((is_string($key) or is_integer($key)) and array_key_exists($key, $this->args)) {
			return $this->args[$key];
		}

		return NULL;
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	public function put ($arg = null, $value = null) {
		if ((is_string($arg) or is_integer($arg))) {
			$this->args[$arg] = $value;

			return TRUE;
		}

		return FALSE;
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	public function setArg ($arg = null, $value = null) {
		if (is_null($arg)) {
			return false;
		}
		$this->args[$arg] = array($value);

		return true;
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	public function prependArg ($arg = null, $value = null) {
		if (is_null($arg))
			return FALSE;

		if (!array_key_exists($arg, $this->args))
			$this->args[$arg] = [];

		if (is_string($this->args[$arg])) {
			$this->args[$arg] = $value . $this->args[$arg];
		} else {
			if (!is_array($this->args[$arg])) {
				$this->args[$arg] = array($this->args[$arg]);
			}

			if (is_string($value)) {
				array_unshift($this->args[$arg], $value);
			} else {
				array_merge($value, $this->args[$arg]);
			}
		}

		return true;
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	public function appendArg ($arg = null, $value = null) {
		if (is_null($arg))
			return false;

		if (!array_key_exists($arg, $this->args))
			$this->args[$arg] = [];

		if (is_string($this->args[$arg])) {
			$this->args[$arg] .= $value;
		} else {
			if (!is_array($this->args[$arg])) {
				$this->args[$arg] = array();
			}

			if (is_string($value)) {
				array_push($this->args[$arg], $value);
			} else {
				array_merge($this->args[$arg], $value);
			}
		}

		return true;
	}

	/**
	 * @param null $arg
	 * @param null $filter
	 * @param string $seperator
	 *
	 * @return mixed
	 */
	public function getArg ($arg = null, $filter = null, $default = NULL, $seperator = ' ') {
		$return = $default;
		if (array_key_exists($arg, $this->args)) {
			if (is_array($this->args[$arg]))
				$return = implode($seperator, $this->args[$arg]);
			else
				$return = $this->args[$arg];
		}

		if (is_string($filter)) {
			$return = \Deft::filter()->exec($filter, $return);
		}

		return $return;
	}

	/**
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function __get($key) {
		return $this->getArg($key);
	}

//	public function __set($key, $value) {
//		return $this->setArg($key, $value);
//	}
}

/**
 * Return locale phrase
 *
 * @return null|string
 */
function __ ( /*polymorphic*/) {
	if (!func_num_args()) {
		return;
	}
	$args = func_get_args();
	$phrase = array_shift($args);

	if (\Deft::locale()->isDefault() === false) {
		$phrase = \Deft::locale()->getPhrase($phrase);
	}

	if (count($args)) {
		$phrase = vsprintf($phrase, $args);
	}

	return $phrase;
}

/**
 * Echo wrapper for __
 */
function ___ ( /*polymorphic*/) {
	echo call_user_func_array('__', func_get_args());
}