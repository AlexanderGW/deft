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
	const VERSION = '0.2';

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
			'debug'            => 2,
			'dir_public'       => 'public',
			'dir_public_asset' => 'asset',
			'dir_plugin'       => 'plugin',
			'dir_lib'          => 'lib',
//			'init_callback'    => array('Snappy\Callback', 'echoResponseOutput'),
			'plugins'          => array('debug', 'example'),
			'url_separator'    => '/'
		), $config);

		define('SNAPPY_DEBUG', (int) self::$config['debug']);

		error_reporting((SNAPPY_DEBUG > 0) ? E_ALL & ~E_NOTICE & ~E_STRICT : 0);

		$stack     = debug_backtrace();
		$first     = $stack[count($stack) - 1];
		$initiator = $first['file'];

		// App pathes
		define('SNAPPY_PATH', str_replace("\\", '/', __DIR__) . DS);

		define('SNAPPY_LIB_PATH', SNAPPY_PATH . self::$config['dir_lib'] . DS);
		if (!is_dir(SNAPPY_LIB_PATH)) {
			self::error('App library directory unreadable: %1$s', SNAPPY_LIB_PATH);
		}

		define('SNAPPY_PLUGIN_PATH', SNAPPY_PATH . self::$config['dir_plugin'] . DS);
		define('SNAPPY_PUBLIC_PATH', SNAPPY_PATH . self::$config['dir_public'] . DS);
		define('SNAPPY_PUBLIC_ASSET_PATH', SNAPPY_PUBLIC_PATH . self::$config['dir_public_asset'] . DS);

		// Libraries to load
		$array = self::import(
			'document',
			'response',
			'event',
			'route',
			'sanitize',
			'request',
			'watchdog',
			'filter',
			'cache.memcached',
			'config',
			'random',
			'helper',
			'http',
			'element',
			'token',
			'language'
		);
		if (count($array)) {
			self::error('Failed to import core libraries: %1$s', implode(', ', $array));
		}

		// App URLs
		define('SNAPPY_URL_PATH', str_replace(
			str_replace("\\", '/', $_SERVER['DOCUMENT_ROOT']),
			'',
			str_replace("\\", '/', dirname($initiator))
		));
		define('SNAPPY_URL', self::request()->scheme() . '://' . self::request()->host() . SNAPPY_URL_PATH);
//		define('SNAPPY_URL', SNAPPY_URL_PATH);
		define('SNAPPY_ASSET_URL', SNAPPY_URL . '/' . self::$config['dir_public_asset'] . '/');
		define('SNAPPY_PLUGIN_URL', SNAPPY_URL . self::$config['dir_plugin'] . '/');

		// Snappy init time
		self::$start = \Snappy\Lib\Helper::getMicroTime();

		// URL separator
		define('US', self::$config['url_separator']);

		// Runtime plugins
		if (count(self::$config['plugins'])) {
			foreach (self::$config['plugins'] as $plugin) {
				$state = 0;
				$path  = SNAPPY_PLUGIN_PATH . $plugin;

				if (file_exists($path . '.php')) {
					$state = true;
				} elseif (is_dir($path)) {
					if (file_exists($path . DS . $plugin . '.php')) {
						$state = 1;
						$path  = $path . DS . $plugin;
					}
				}

				$start = 0;
				if ($state) {
					$start = \Snappy\Lib\Helper::getMicroTime();
					include $path . '.php';
				}

				self::log('plugin/' . $plugin, array(
					'time'   => ($state ? \Snappy\Lib\Helper::getMoment($start) : 0),
					'loaded' => $state
				));
			}
		}

		// Prevent 'init' from being called again...
		self::$initialized = true;

		// The very first event...
		$init = \Snappy\Lib\Event::exec('init');

		// The initial callback from snappy config
		$callback = self::$config['init_callback'];
		if (!is_callable($callback)) {
			\Snappy\Lib\Event::set('initCallback', $callback);
		}

		$init_callback = \Snappy\Lib\Event::exec('initCallback');
		if ($init === false and $init_callback === false) {
			self::error('Dead in the water...');
		}

		// Exit event...
		\Snappy\Lib\Event::exec('exit');
	}

	/**
	 * Import libraries relative to SNAPPY_LIB_PATH for use within the App.
	 *
	 * Dot-scope format "helper.example" for "helper/example.php" class.
	 *
	 * @return array
	 */
	static function import ( /*polymorphic*/) {
		$result = array();
		if (func_num_args()) {
			$args = func_get_args();
			foreach ($args as $arg) {
				if (empty($arg)) {
					continue;
				}

				$path = SNAPPY_LIB_PATH . str_replace('.', DS, $arg) . '.php';
				if (!is_readable($path)) {
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
		$path   = SNAPPY_PLUGIN_PATH . $name;
		$plugin = self::getLog('plugin/' . $name);
		if (count($plugin) and $plugin[0]['loaded']) {
			return 2;
		} elseif (file_exists($path . '.php') or is_dir($path)) {
			return 1;
		}

		return 0;
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
			return;
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
	 * Returns the instance of class with arguments, will create if does not exist.
	 *
	 * Dot-scope format "helper.example" for "helper/example.php" class.
	 *
	 * @param null $scope
	 * @param array $args
	 */
	static function get ($scope = null, $args = array()) {
		if (!is_string($scope)) {
			return;
		}

		if ($scope == 'pdo') {
			$scope = 'db';
		}

		$class = '\\Snappy\\Lib\\' . ucfirst(str_replace('.', '\\', $scope));

		// Import if not already
		if (!class_exists($class)) {
			$errors = self::import($scope);
			if (count($errors)) {
				\Snappy::error('Failed to import and instantiate: %1$s', implode(', ', $errors));
			}
		}

		// Generate instance key
		$key = self::getInstanceKey($class, $args);

		// Get cached object
//		if ($scope !== 'cache' and strpos($scope, 'cache.') === false) {
//			$datastore = self::cache()->get($key);
//			if ($datastore) {
//				self::$instances[$key] = $datastore;
//			}
//		}

		// Get instance data enironment
//		elseif (method_exists($class, 'getArgs')) {
//			$args = $class::getArgs($args);
//		}

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

	public static function cache ($args = array()) {
		return \Snappy::get('cache', $args);
	}

	public static function config ($args = null) {
		if (is_null($args)) {
			$args = self::$config;
		}

		return \Snappy::get('config', $args);
	}

	public static function database ($args = array()) {
		return \Snappy::get('db', $args);
	}

	public static function document ($args = array()) {
		$args = array_merge([
			'type' => 'html5',
		], $args);

		if ($args['type'] == 'html5') {
			$args = array_merge(array(
				'base'      => null,
				'encoding'  => 'utf-8',
				'locale'    => 'en',
				'direction' => 'ltr',
				'mime'      => 'text/html'
			), $args);
		}

		return \Snappy::get('document', $args);
	}

	public static function form ($id = null) {
		return \Snappy::get('form', $id);
	}

	public static function request () {
		return \Snappy::get('request');
	}

	public static function route () {
		return \Snappy::get('route');
	}

	public static function response () {
		$config = self::get('config');
		$args = [];
		if ($config) {
			$args = [
				'base'      => null,
				'encoding'  => $config->get('response.encoding', 'utf-8'),
				'locale'    => $config->get('response.locale', 'en'),
				'direction' => $config->get('response.direction', 'ltr'),
				'mime'      => $config->get('response.mime', 'text/html')
			];
		}

		return \Snappy::get('response', $args);
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
		$config =& self::config();

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

		$config  =& \Snappy::config();
		$hash = $config->get('capture_hash');
		if (is_null($hash)) {
			$hash = \Snappy\Lib\Random::getMd5();
			$config->set('capture_hash', $hash);
			$config->save();
		}

		${'scope_' . $hash} = \Snappy\Lib\Filter::exec('beforeCapture', $scope);
		${'path_' . $hash}  = SNAPPY_PATH . str_replace('.', DS, ${'scope_' . $hash}) . '.php';

		if (!file_exists(${'path_' . $hash})) {
			return;
		}

		\Snappy\Lib\Event::exec('beforeCapture', ${'scope_' . $hash});

		${'start_' . $hash} = \Snappy\Lib\Helper::getMicroTime();

		ob_start();
		include ${'path_' . $hash};
		${'content_' . $hash} = ob_get_contents();
		ob_end_clean();

		self::log('capture/' . ${'scope_' . $hash}, array(
			'time' => \Snappy\Lib\Helper::getMoment(${'start_' . $hash})
		));

		return \Snappy\Lib\Filter::exec('captureContent', ${'content_' . $hash});
	}

	/**
	 * Get support state for something
	 */
	static public function support($string = null) {
		if (is_string($string)) {
			$supports = array(
				'yaml' => function_exists('yaml_emit_file')
			);
			$supports = \Snappy\Lib\Filter::exec('beforeSupport', $supports);
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

		\Snappy\Lib\Event::exec('onSnappyError');

		$template = \Snappy\Lib\Filter::exec('SnappyErrorTemplate', [
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
	public function __construct ($class = '', $args = array()) {
		$this->stack = 'instance/' . get_class($this) . '/' . \Snappy::getInstanceKey(get_class($this), $args);

		if (is_string($class)) {
			\Snappy\Lib\Event::exec("on{$class}Construct", $this);
		}
		\Snappy\Lib\Event::exec('onSnappyConcrete', $this);
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

		return;
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

	if (class_exists('\Snappy\Lib\Language') && \Snappy\Lib\Language::isDefault() === false) {
		$phrase = \Snappy\Lib\Language::getPhrase($phrase);
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