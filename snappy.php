<?php

/**
 * Snappy, a micro framework for PHP.
 *
 * @author Alexander Gailey-White <alex@gailey-white.com>
 *
 * This file is part of Snappy.
 *
 * Snappy is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Snappy is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Snappy.  If not, see <http://www.gnu.org/licenses/>.
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
 * Class Snappy
 */
class Snappy {
	const VERSION = '0.6';

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
	 * Initialise Snappy
	 *
	 * @param array $args
	 */
	static function init ($config) {
		if (self::$initialized) {
			return;
		}

//		set_error_handler(['Snappy', 'errorHandler']);

		self::$config = array_merge(array(
			'config_format'    => 'php',
			'debug'            => 0,
			'dir_public'       => 'public',
			'dir_public_asset' => 'asset',
			'dir_plugin'       => 'plugin',
			'dir_lib'          => 'lib',
			'dir_tmp'          => 'tmp',
			'plugins'          => array('debug', 'example'),
			'url_separator'    => '/'
		), $config);

		define('SNAPPY_DEBUG', (int) self::$config['debug']);

		error_reporting((SNAPPY_DEBUG > 0) ? E_ALL & ~E_NOTICE & ~E_STRICT : 0);

		$backtrace = debug_backtrace();

		if (!defined('SNAPPY_INITIATOR')) {
			$first     = $backtrace[count($backtrace) - 1];
			define('SNAPPY_INITIATOR', $first['file']);
		}

		// Absolute path
		if (!defined('SNAPPY_ABS_PATH'))
			define('SNAPPY_ABS_PATH', __DIR__);

		// App pathes
		define('SNAPPY_PATH', str_replace("\\", '/', SNAPPY_ABS_PATH));
		define('SNAPPY_LIB_PATH', SNAPPY_PATH . DS . self::$config['dir_lib']);
		if (!is_dir(SNAPPY_LIB_PATH)) {
			self::error('Cannot read from library directory: %1$s', SNAPPY_LIB_PATH);
		}
		define('SNAPPY_TMP_PATH', SNAPPY_PATH . DS . self::$config['dir_tmp']);
		if (!is_writable(SNAPPY_TMP_PATH)) {
			self::error('Cannot write to temporary directory: %1$s', SNAPPY_TMP_PATH);
		}
		define('SNAPPY_PLUGIN_PATH', SNAPPY_PATH . DS . self::$config['dir_plugin']);
		define('SNAPPY_PUBLIC_PATH', SNAPPY_PATH . DS . self::$config['dir_public']);
		define('SNAPPY_PUBLIC_ASSET_PATH', SNAPPY_PUBLIC_PATH . DS . self::$config['dir_public_asset']);

		// Libraries to load
		$array = self::import(
			'lib.helper',
			'lib.watchdog',
//			'lib.event',
			'lib.route',
			'lib.sanitize',
//			'watchdog',
//			'filter',
			'lib.random',
//			'lib.http',
			'lib.element',
			'lib.token',
			'lib.plugin'
		);
		if (count($array)) {
			self::error('Failed to import core libraries: %1$s', implode(', ', $array));
		}

		// URLs
		define('SNAPPY_URL_PATH', str_replace(
			str_replace("\\", '/', $_SERVER['DOCUMENT_ROOT']),
			'',
			str_replace("\\", '/', dirname(SNAPPY_INITIATOR))
		));
		define('SNAPPY_URL',
			self::request()->scheme() . '://' .
			self::request()->host() .
			(self::request()->port() <> 80 ? ':' . self::request()->port() : NULL) .
			SNAPPY_URL_PATH
		);
//		define('SNAPPY_URL', SNAPPY_URL_PATH);
		define('SNAPPY_ASSET_URL', SNAPPY_URL . '/' . self::$config['dir_public_asset'] . '/');
		define('SNAPPY_PLUGIN_URL', SNAPPY_URL . '/' . self::$config['dir_plugin'] . '/');

		// Requested route relative to Snappy URL.
		define('SNAPPY_ROUTE',
			\Snappy\Lib\Sanitize::forHtml(
				\Snappy\Lib\Sanitize::forText(
					substr(
						\Snappy::request()->path(),
						(strlen(SNAPPY_URL_PATH . '/'))
					)
				)
			)
		);

		// Snappy init time
		self::$start = \Snappy\Lib\Helper::getMicroTime();

		// URL separator
		define('US', self::$config['url_separator']);

		// Runtime plugins
		if (count(self::$config['plugins'])) {
			foreach (self::$config['plugins'] as $plugin) {
				$state = Snappy::PLUGIN_MISSING;
				$path  = SNAPPY_PLUGIN_PATH . DS . $plugin;
				$ext = '.php';

				if (file_exists($path . $ext)) {
					$state = Snappy::PLUGIN_EXISTS;
				} elseif (is_dir($path) && file_exists($path . DS . $plugin . $ext)) {
					$state = Snappy::PLUGIN_EXISTS;
					$path  .= DS . $plugin;
				}

				$start = 0;
				if ($state === Snappy::PLUGIN_EXISTS) {
					$start = \Snappy\Lib\Helper::getMicroTime();
					include $path . $ext;
					$state = Snappy::PLUGIN_LOADED;
				}

				self::log('plugin/' . $plugin, array(
					'time'   => ($state ? \Snappy\Lib\Helper::getMoment($start) : 0),
					'loaded' => $state
				));
			}
		}

		// Prevent 'Snappy::init()' from being called again
		self::$initialized = true;

		// Run this callback after initialization
		$callback = self::$config['ready_callback'];
		if (is_callable($callback)) {
			\Snappy::event()->set('ready', $callback);
		}

		// Execute initialization
		$init = \Snappy::event()->exec('init');

		// Execute ready, after initialization
		$ready = \Snappy::event()->exec('ready');

		// No content
		if ($init === FALSE && $ready === FALSE) {
			self::response()->status(204);
		}

		// Execute exit event
		\Snappy::event()->exec('exit');
	}

	/**
	 * Import libraries relative to SNAPPY_PATH for use within the App.
	 *
	 * Dot-scope format "lib.response.http.html" for "SNAPPY_PATH/lib/response/http/html.php" class.
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

				$path = SNAPPY_PATH . DS . str_replace('.', DS, $arg) . '.php';
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
		$path   = SNAPPY_PLUGIN_PATH . DS . $name;
		$plugin = self::getLog('plugin/' . $name);
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
		return ($class . '_' . md5(serialize($seed)));
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
		$start = \Snappy\Lib\Helper::getMicroTime();

		self::$instances[ $key ] = new $class( $args );
		self::log("instance/{$class}/{$key}", array(
			'time' => \Snappy\Lib\Helper::getMoment($start),
			'args' => $args
		));
		self::log("instance/{$class}/{$key}/calls");

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

		$class = '\\Snappy\\' . str_replace(' ', '\\', ucwords(str_replace('.', ' ', $scope)));

		$stack = "{$scope}";

		$log = self::getLog($stack);

		// First time calls may need to import library
		if (!count($log)) {

			// Import if not already
			if (!class_exists($class)) {
				$offset = 0;
				$array = [];
				if (strpos($scope, '.')) {
					while ($offset < strlen($scope) && ($pos = strpos($scope, '.', $offset)) !== false) {
						$value = substr($scope, 0, $pos);
						//if ($value !== 'lib')
							$array[] = $value;
						$offset += ($pos+1);
					}
				}

				//if ($value !== 'lib')
					$array[] = $scope;

				$errors = call_user_func_array([__CLASS__, 'import'], $array);
				if (count($errors)) {
					\Snappy::error('Failed to import and instantiate: %1$s', implode(', ', $errors));
				}
			}

			self::log($stack);
		}

		// Generate instance key
		$key = self::getInstanceKey($class, $args);

		// Check instance cache
		$result = self::haveInstance($key);

		// Return existing
		if ($result) {
			self::log("instance/{$class}/{$key}/calls");

			return self::$instances[$key];
		}
		// Create and return
		$instance = self::newInstance($class, $args);

		return $instance;
	}

	public static function lib ($scope, $args = array()) {
		return \Snappy::get('lib.' . $scope, $args);
	}

	public static function cache ($args = array()) {
		return self::lib('cache', $args);
	}

	public static function config ($args = null) {
		if (is_null($args)) {
			$args = self::$config;
		}

		return self::lib('config', $args);
	}

	public static function database ($args = []) {
		$config  = \Snappy::config();
		$args = array_merge(array(
			'driver'       => $config->get('database.driver'),
			'host'         => $config->get('database.hostname'),
			'username'     => $config->get('database.username'),
			'password'     => $config->get('database.password'),
			'dbname'       => $config->get('database.name'),
			'table_prefix' => $config->get('database.table.prefix'),
			'port'         => $config->get('database.port')
		), $args);

		$scope = 'storage.database';
		if ($args['driver'])
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
	 * @return \Snappy\Lib\Request
	 */

	public static function request () {
		return self::lib('request');
	}

	/**
	 * @return \Snappy\Lib\Route
	 */
	public static function route () {
		return self::lib('route');
	}

	/**
	 * @return \Snappy\Lib\Filesystem
	 */
	public static function filesystem ($args = []) {
		$config = self::config();

		if ($config) {
			$args = array_merge(array(
				'type' => $config->get('filesystem.type', 'local')
			), $args);
		}

		return self::lib('filesystem', $args);
	}

	/**
	 * @param array $args
	 *
	 * @return \Snappy\Lib\Response
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
			$config->set('secret', str_shuffle(\Snappy\Lib\Helper::ALPHANUMERIC_CHARS . \Snappy\Lib\Helper::EXTENDED_CHARS));
			$config->save();
		}

		return strtr(
			serialize($args),
			\Snappy\Lib\Helper::ALPHANUMERIC_CHARS . \Snappy\Lib\Helper::EXTENDED_CHARS,
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
			\Snappy\Lib\Helper::ALPHANUMERIC_CHARS . \Snappy\Lib\Helper::EXTENDED_CHARS
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
	 * @param null $path
	 *
	 * @return bool|mixed|string|void
	 */
	static function capture ($scope = null) {
		if (!is_string($scope)) {
			return;
		}

		$config  = self::config();
		$hash = $config->get('capture_hash');
		if (is_null($hash)) {
			$hash = \Snappy\Lib\Random::getMd5();
			$config->set('capture_hash', $hash);
			$config->save();
		}

		${'scope_' . $hash} = \Snappy::filter()->exec('beforeCapture', $scope);
		${'path_' . $hash}  = SNAPPY_PATH . DS . str_replace('.', DS, ${'scope_' . $hash}) . '.php';

		if (!file_exists(${'path_' . $hash})) {
			return;
		}

		\Snappy::event()->exec('beforeCapture', ${'scope_' . $hash});

		${'start_' . $hash} = \Snappy\Lib\Helper::getMicroTime();

		ob_start();
		include ${'path_' . $hash};
		${'content_' . $hash} = ob_get_contents();
		ob_end_clean();

		self::log('capture/' . ${'scope_' . $hash}, array(
			'time' => \Snappy\Lib\Helper::getMoment(${'start_' . $hash})
		));

		return \Snappy::filter()->exec('captureContent', ${'content_' . $hash});
	}

	/**
	 * Get support state for something
	 */
	static public function support($string = null) {
		if (is_string($string)) {
			$supports = array(
				'yaml' => function_exists('yaml_emit_file')
			);
			$supports = \Snappy::filter()->exec('beforeSupport', $supports);
			return (array_key_exists($string, $supports) and $supports[$string] === true);
		}
		return false;
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
	static function log ($stack = null, $args = array(), $replace = false) {
		if (!is_string($stack)) {
			$stack = 'app';
		}
		if (!array_key_exists($stack, self::$logs) and !$replace) {
			self::$logs[$stack] = array();
		}

		$entry = array('moment' => \Snappy\Lib\Helper::getMoment()) + (array) $args;

		if ($replace) {
			self::$logs[$stack] = array($entry);
		} else {
			self::$logs[$stack][] = $entry;
		}
	}

	/**
	 * Get log entries
	 *
	 * Slash-scope format "app/state"
	 *
	 * @param null $stack
	 *
	 * @return array
	 */
	static function getLog ($stack = null) {
		$return = array();
		if (!is_null($stack)) {
			if (array_key_exists($stack, self::$logs)) {
				$return = self::$logs[$stack];
			}
		} else {
			$return = self::$logs;
		}

		return $return;
	}

	/**
	 * Raises App critical error
	 */
	static function error ( /*polymorphic*/) {
		if (!func_num_args()) {
			return;
		}

		\Snappy::event()->exec('onSnappyError');

		$template = \Snappy::filter()->exec('SnappyErrorTemplate', [
			'@tag' => 'html',
			'@markup' => [
				'@tag' => 'body',
				'@markup' => [
					'@tag' => '',
				]
			]
		]);

		//if (\Snappy::response()->status(500) !== true) {
			echo '<h1>' . __('App error') . '</h1>';
			if (SNAPPY_DEBUG > 0) {

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
 * Class Snappy_Concrete for instantiation based classes
 */
class Snappy_Concrete {

	/**
	 * @var array
	 */
	public $args = array();

	/**
	 * @var null|string*
	 */
	public $stack = null;

	/**
	 * App_Class constructor.
	 *
	 * @param array $args
	 */
	public function __construct ($args = array(), $class = __CLASS__) {
		$this->args = self::getArgs($args);
		$this->stack = 'instance/' . get_class($this) . '/' . \Snappy::getInstanceKey(get_class($this), $args);

		if (is_string($class)) {
			\Snappy::event()->exec("on{$class}Construct", $this);
		}
		\Snappy::event()->exec('onSnappyConcrete', $this);
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
		if (is_null($arg)) {
			return false;
		}
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
		if (is_null($arg)) {
			return false;
		}
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
		if (is_string($this->args[$arg])) {
			$return = $this->args[$arg];
		} elseif (is_array($this->args[$arg])) {
			$return = implode($seperator, $this->args[$arg]);
		}

		if (is_string($filter)) {
			$return = \Snappy::filter()->exec($filter, $return);
		}

		return $return;
	}
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

	if (\Snappy::locale()->isDefault() === false) {
		$phrase = \Snappy::locale()->getPhrase($phrase);
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