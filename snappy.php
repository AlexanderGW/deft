<?php

/**
 * Snappy, a PHP framework for PHP 5.3+
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
 * App debugging output level.
 * 0 = Disabled
 * 1 = Normal
 * 2 = Verbose
 */
if( !defined( 'SNAPPY_DEBUG' ) )
	define( 'SNAPPY_DEBUG', 0 );

error_reporting( ( SNAPPY_DEBUG > 0 ) ? E_ALL &~ E_NOTICE &~ E_STRICT : 0 );

/**
 * Version
 */
define( 'SNAPPY_VERSION', '0.1' );

/**
 * Copy of DIRECTORY_SEPARATOR
 */
define( 'DS', DIRECTORY_SEPARATOR );

/**
 * UTC
 */
define( 'TIME_UTC', ( time() - date( 'Z' ) ) );

/**
 * For direct access prevention.
 */
define( 'IN_SNAPPY', 1 );

/**
 * Class Snappy
 */
class Snappy {
	const VERSION = '0.1';

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
	static function init( $args = array() ) {
		if( self::$initialized )
			return;

		$args = array_merge( array(
			'directory_content' => 'content',
			'directory_lib' => 'lib',
			'directory_plugin' => 'plugin'
		), $args );

		define( 'SNAPPY_PATH', __DIR__ . DS );
		define( 'SNAPPY_CONTENT_PATH', SNAPPY_PATH . $args['directory_content'] . DS );
		if( !is_dir( SNAPPY_CONTENT_PATH ) )
			self::error( 'App content directory unreadable: %1$s', SNAPPY_CONTENT_PATH );

		define( 'SNAPPY_DIR', substr( str_replace( "\\", '/', __DIR__ ), strlen( $_SERVER['DOCUMENT_ROOT'] ) ) . '/' );
		define( 'SNAPPY_URL', '//' . $_SERVER['HTTP_HOST'] . SNAPPY_DIR );

		define( 'SNAPPY_CONTENT_URL', SNAPPY_URL . $args['directory_content'] . '/' );

		define( 'SNAPPY_LIB_PATH', SNAPPY_CONTENT_PATH . $args['directory_lib'] . DS );
		if( !is_dir( SNAPPY_LIB_PATH ) )
			self::error( 'App library directory unreadable: %1$s', SNAPPY_LIB_PATH );

		$errors = self::import(
			'config',
			'hook',
			'filter',
			'http',
			'helper',
			'route',
			'html',
			'token',
			'language',
			'document'
		);
		if( count( $errors ) )
			self::error( 'Failed to import core libraries: %1$s', implode( ', ', $errors ) );

		define( 'SNAPPY_PLUGIN_PATH', SNAPPY_CONTENT_PATH . $args['directory_plugin'] . DS );
		define( 'SNAPPY_PLUGIN_URL', SNAPPY_CONTENT_URL . $args['directory_plugin'] . '/' );

		$url = parse_url( $_SERVER['REQUEST_URI'] );
		if( array_key_exists( 'query', $url ) ) {
			parse_str( $url['query'], $items );
			foreach( $items as $key => $val ) {
				$key = Helper::trimAllCtrlChars( $key );
				$val = Helper::trimAllCtrlChars( $val );
				$_GET[ $key ] = $val;
			}
		}

		define( 'SNAPPY_ROUTE', Html::escape( Helper::trimAllCtrlChars( substr( $url['path'], strlen( SNAPPY_DIR ) ) ) ) );

		self::$start = Helper::getMicroTime();

		$cfg =& self::getCfg();
		if( !$cfg->isEmpty() ) {
			$plugins = $cfg->get( 'plugins', array() );
			if( count( $plugins ) ) {
				foreach( $plugins as $plugin ) {
					$state = 0;
					$path = SNAPPY_PLUGIN_PATH . $plugin;

					if( file_exists( $path . '.php' ) )
						$state = true;
					elseif( is_dir( $path ) ) {
						if( file_exists( $path . DS . $plugin . '.php' ) ) {
							$state = 1;
							$path = $path . DS . $plugin;
						}
					}

					if( $state ) {
						$start = Helper::getMicroTime();
						include $path . '.php';
					}

					self::log( 'plugin/' . $plugin, array(
						'time' => ( $state ? Helper::getMoment( $start ) : 0 ),
						'loaded' => $state
					) );
				}
			}
		}

		self::$initialized = true;
		Hook::exec( 'init' );
		Hook::exec( 'initContent' );
	}

	/**
	 * Import libraries relative to SNAPPY_LIB_PATH for use within the App.
	 *
	 * Dot-scope format "helper.example" for "helper/example.php" class.
	 *
	 * @return array
	 */
	static function import( /*polymorphic*/ ) {
		$result = array();
		if( func_num_args() ) {
			$args = func_get_args();
			foreach( $args as $arg ) {
				if( empty( $arg ) )
					continue;

				$path = SNAPPY_LIB_PATH . str_replace( '.', DS, $arg ) . '.php';
				if( !is_readable( $path ) )
					$result[] = $path;
				else {
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
	public static function havePlugin( $name = null ) {
		if( is_null( $name ) )
			return;
		$path = SNAPPY_PLUGIN_PATH . $name;
		$plugin = self::getLog( 'plugin/' . $name );
		if( count( $plugin ) and $plugin[0]['loaded'] )
			return 2;
		elseif( file_exists( $path . '.php' ) or is_dir( $path ) )
			return 1;
		return 0;
	}

	/**
	 * Get unique instance key for with class with arguments
	 *
	 * @param null $class
	 * @param array $args
	 *
	 * @return string
	 */
	static function getInstanceKey( $class = null, $args = array() ) {
		return md5( $class . '-' . serialize( (array)$args ) );
	}

	/**
	 * Checks if an instance exists by key.
	 *
	 * @param null $key
	 *
	 * @return bool
	 */
	static function haveInstance( $key = null ) {
		if( !is_string( $key ) and strlen( $key ) <> 32 )
			return false;
		return array_key_exists( $key, self::$instances );
	}

	/**
	 * Create new instance of class with arguments.
	 *
	 * Dot-scope format "helper.example" for "helper/example.php" class.
	 *
	 * @param null $scope
	 * @param array $args
	 */
	static function newInstance( $class = null, $args = array() ) {
		if( is_null( $class ) or !class_exists( $class ) )
			return;

		$key = self::getInstanceKey( $class, $args );

		$start = Helper::getMicroTime();

		self::$instances[ $key ] = new $class( $args );

		self::log( "instance/{$class}/{$key}", array(
			'time' => Helper::getMoment( $start ),
			'args' => $args
		) );
		self::log( "instance/{$class}/{$key}/calls" );

		return self::$instances[ $key ];
	}

	/**
	 * Returns the instance of class with arguments, will create if does not exist.
	 *
	 * Dot-scope format "helper.example" for "helper/example.php" class.
	 *
	 * @param null $scope
	 * @param array $args
	 */
	static function get( $scope = null, $args = array() ) {
		if( !is_string( $scope ) )
			return;

		if( $scope == 'pdo' )
			$scope = 'db';

		$class = str_replace( ' ', '', ucwords( str_replace( '.', ' ', $scope ) ) );

		// Import if not already
		if( !class_exists( $class ) ) {
			$errors = self::import( $scope );
			if( count( $errors ) )
				Snappy::error( 'Failed to import to instantiate: %1$s', implode( ', ', $errors ) );
		}

		// Get instance environment
		if( method_exists( $class, 'getArgs' ) )
			$args = $class::getArgs( $args );

		// Generate instance key
		$key = self::getInstanceKey( $class, $args );

		// Check instance cache
		$result = self::haveInstance( $key );

		// Return exisiting
		if( $result ) {
			self::log( "instance/{$class}/{$key}/calls" );
			return self::$instances[ $key ];
		}

		// Create and return
		return self::newInstance( $class, $args );
	}

	public static function getCfg( $args = array() ) {
		return Snappy::get( 'config', $args );
	}

	public static function getDb( $args = array() ) {
		return Snappy::get( 'db', $args );
	}

	public static function getForm( $id = null ) {
		return Snappy::get( 'form', $id );
	}

	/**
	 * Encodes $args into serialized string with App secret.
	 *
	 * @param mixed $args
	 *
	 * @return string
	 */
	static function encode( $args = null ) {
		if( is_null( $args ) )
			return;
		$cfg =& self::getCfg();

		$secret = $cfg->get( 'secret' );
		if( empty( $secret ) ) {
			$cfg->set( 'secret', str_shuffle( Helper::ALPHANUMERIC_CHARS . Helper::EXTENDED_CHARS ) );
			$cfg->save();
		}

		return strtr(
			serialize( $args ),
			Helper::ALPHANUMERIC_CHARS . Helper::EXTENDED_CHARS,
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
	static function decode( $string = null ) {
		if( !is_string( $string ) )
			return;
		$cfg =& self::getCfg();

		$secret = $cfg->get( 'secret' );
		if( !$secret )
			return;

		$decoded = strtr(
			$string,
			$secret,
			Helper::ALPHANUMERIC_CHARS . Helper::EXTENDED_CHARS
		);

		$result = @unserialize( $decoded );
		if( $result !== false )
			return $result;
		return;
	}

	/**
	 * Capture and return output of a script.
	 *
	 * @param null $path
	 *
	 * @return bool|mixed|string|void
	 */
	static function capture( $scope = null ) {
		if( !is_string( $scope ) )
			return;

		$cfg =& Snappy::getCfg();
		$hash = $cfg->get( 'capture_hash' );
		if( is_null( $hash ) ) {
			$hash = Helper::getRandomHash();
			$cfg->set( 'capture_hash', $hash );
			$cfg->save();
		}

		${ 'scope_' . $cfg->get( 'capture_hash' ) } = Filter::exec( 'beforeCapture', $scope );
		${ 'path_' . $cfg->get( 'capture_hash' ) } = SNAPPY_CONTENT_PATH . str_replace( '.', DS, ${ 'scope_' . $cfg->get( 'capture_hash' ) } ) . '.php';

		if( !file_exists( ${ 'path_' . $cfg->get( 'capture_hash' ) } ) )
			return;

		Hook::exec( 'beforeCapture', ${ 'scope_' . $cfg->get( 'capture_hash' ) } );

		${ 'start_' . $cfg->get( 'capture_hash' ) } = Helper::getMicroTime();

		ob_start();
		include ${ 'path_' . $cfg->get( 'capture_hash' ) };
		${ 'content_' . $cfg->get( 'capture_hash' ) } = ob_get_contents();
		ob_end_clean();

		self::log( 'capture/' . ${ 'scope_' . $cfg->get( 'capture_hash' ) }, array(
			'time' => Helper::getMoment( ${'start_' . $cfg->get( 'capture_hash' ) } )
		) );

		return Filter::exec( 'captureContent', ${ 'content_' . $cfg->get( 'capture_hash' ) } );
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
	static function log( $stack = null, $args = array(), $replace = false ) {
		if( !is_string( $stack ) )
			$stack = 'app';
		if( !array_key_exists( $stack, self::$logs ) and !$replace )
			self::$logs[ $stack ] = array();

		$args = (array)$args;
		$entry = array( 'moment' => Helper::getMoment() ) + $args;

		if( $replace )
			self::$logs[ $stack ] = array( $entry );
		else
			self::$logs[ $stack ][] = $entry;
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
	static function getLog( $stack = null ) {
		$return = array();
		if( !is_null( $stack ) ) {
			if( array_key_exists( $stack, self::$logs ) )
				$return = self::$logs[ $stack ];
		} else
			$return = self::$logs;
		return $return;
	}

	/**
	 * Raises App critical error
	 */
	static function error( /*polymorphic*/ ) {
		if( !func_num_args() )
			return;
		header( 'HTTP/1.1 500 Internal Server Error' );
		die( '<h1>' . __( 'App error' ) . '</h1>' . call_user_func_array( '__', func_get_args() ) );
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
	 * @var null|string
	 */
	public $stack = null;

	/**
	 * App_Class constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$this->stack = 'instance/' . get_class( $this ) . '/' . Snappy::getInstanceKey( get_class( $this ), $args );
	}

	/**
	 * @param array $args
	 */

	public static function getArgs( $args = array() ) {
		return $args;
	}

	/**
	 * @param null $arg
	 */
	public function get( $arg = null ) {
		if( ( is_string( $arg ) or is_integer( $arg ) ) and array_key_exists( $arg, $this->args ) )
			return $this->args[ $arg ];
		return;
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	public function put( $arg = null, $value = null ) {
		if( ( is_string( $arg ) or is_integer( $arg ) ) ) {
			$this->args[ $arg ] = $value;
			return true;
		}
		return false;
	}
}

/**
 * Return locale phrase
 *
 * @return mixed|string|void
 */
function __( /*polymorphic*/ ) {
	if( !func_num_args() )
		return;
	$args = func_get_args();

	$phrase = array_shift( $args );

	if( class_exists( 'Language' ) and Language::isDefault() == false )
		$phrase = Language::getPhrase( $phrase );

	if( count( $args ) )
		$phrase = vsprintf( $phrase, $args );
	return $phrase;
}

/**
 * Echo wrapper for __
 */
function ___( /*polymorphic*/ ) {
	echo call_user_func_array( '__', func_get_args() );
}

/**
 * Initialise
 */
Snappy::init();