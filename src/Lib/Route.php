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

namespace Deft\Lib;

class Route extends \Deft_Concrete {
	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	/**
	 * Set TRUE once parse() executes.
	 *
	 * @var array
	 */
	private static $parsed = false;

	/**
	 * Default rules group
	 *
	 * @var array
	 */
	private static $group = NULL;

	private static $rules = array();

	private static $route = array();

	/**
	 *
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		// Default rules group, based on request type
		self::$group = \Deft::request()->isCli() ? 'cli' : 'http';

		// Process request query string
//		if ($query = \Deft::request()->query()) {
//			if (count($query)) {
//				foreach ($query as $key => $val) {
//					$key        = Sanitize::forText($key);
//					$val        = Sanitize::fortext($val);
//					$_GET[$key] = $val;
//				}
//			}
//		}

		// Load config based routes
		$config = \Deft::config( 'config.route' );
		if ( ! $config->isEmpty() ) {
			$routes = $config->get( 'routes', array() );
			if ( count( $routes ) ) {
				foreach ( $routes as $path => $rule ) {
					Route::add( $path, $rule );
				}
			}
		}

		self::$initialized = true;
	}

	/**
	 *
	 */
	public static function parse() {
		if ( self::$parsed ) {
			return;
		}

		$start = Helper::getMicroTime();

		// Process requested route
		self::$route = self::match(DEFT_ROUTE);

		// Class event - Is JSON route?
		// TODO: Make this overridable, move into events?
		if (self::$route['class'] == 'json')
			\Deft::config()->set('response.type', 'http.json');

		// Route callback
		if ( is_callable( self::$route[ 'callback'  ] ) )
			call_user_func( self::$route[ 'callback' ] );

		// Null? Throw a 404 for non-CLI
		elseif ( \Deft::request()->isCli() === false )
			\Deft::response()->status( \Deft::config()->get('route.parse.response.status.null', 404) );

		// Logging
		\Deft::stack( 'route/' . self::$route[ 'name' ], array_merge(
			[
				'time' => Helper::getMoment($start)
			],
			self::$route
		) );

		return self::$parsed = true;
	}

	/**
	 * @param null $path
	 */
	public static function match ($string = null) {

		// Process requested route
		$route = \Deft::route()->get( $string );

		// No match
		if ( $route === FALSE and strlen( $string ) ) {
			$routes  = \Deft::route()->getRules();
			$divider = self::getSeparator();

			$request = explode( $divider, $string );
			array_pop( $request );
			$max = count( $request );
			//$request = implode( $divider, $request );

			foreach ( $routes as $path => $args ) {
				if (

					// Check for potentials within the same depth (by number of path dividers), which also has placeholder(s)
					substr_count( $path, $divider ) == $max

					// Has placeholders
					and strpos( $path, '[' ) !== false
					and strpos( $path, ']' ) !== false

				    // Has placeholder patterns
				    and array_key_exists( 'pattern', $args )
			        and count( $args['pattern'] )
				) {

					// Build pattern
					$path_pattern = '#^' . $path . '$#';
					foreach ( $args[ 'pattern' ] as $name => $pattern ) {
						$path_pattern = str_replace(
							'[' . $name . ']',
							'(' . $pattern . ')',
							$path_pattern
						);
					}

					// Run pattern
					preg_match( $path_pattern, DEFT_ROUTE, $matches );
					if ( count( $matches ) ) {
						preg_match_all( '#\[([a-z0-9]+)\]#', $path, $keys );
						array_shift( $matches );

						foreach ( $matches as $i => $match ) {
							$args[ 'data' ][ $keys[ 1 ][ $i ] ] = $match;
						}

						$route = array_merge(
							$args,
							array(
								'path'    => $path,
								'pattern' => $path_pattern
							)
						);

						break;
					}
				}
			}
		}

		// Apply route environment
		if ( is_array( $route[ 'data' ] ) ) {
			foreach ( $route[ 'data' ] as $key => $value ) {
				$key                           = Sanitize::forText( $key );
				$value                         = Sanitize::forText( $value );
				$route[ 'data' ][ $key ] = $value;
			}
		}

		// Route callback
		if ( is_callable( self::$route[ 'callback' ] ) ) {
			call_user_func( self::$route[ 'callback' ] );
		}

		// Return match
		return array(
			'name'     => $route[ 'name' ],
			'class'    => $route[ 'class' ],
			'request'  => $string,
			'path'     => array_key_exists('path', $route) ? $route[ 'path' ] : '',
			'pattern'  => $route[ 'pattern' ],
			'data'     => $route[ 'data' ],
			'callback' => $route[ 'callback' ]
		);
	}

	/**
	 * Returns the currently patched route. Provide a value for a particular route key, or return the whole object.
	 */
	public function current( $arg = null ) {
		if ( is_null( $arg ) ) {
			return (object) self::$route;
		}
		if ( is_array( self::$route ) and array_key_exists( $arg, self::$route ) ) {
			return self::$route[ $arg ];
		}

		return;
	}

	/**
	 * @param null $path
	 * @param array $args
	 */
	public function add( $name = null, $path = null, $args = array(), $callback = null, $group = null ) {

		// TODO: Need to split path by separator

		if ( is_null( $path ) ) {
			return;
		}

		if ( is_null( $name ) ) {
			$name = md5( $path );
		}

		if ( is_null( $group ) ) {
			$group = self::$group;
		}

		$sep = \Deft::config()->get( 'url_separator', '/' );
		if ( strpos( $path, $sep ) === 0 ) {
			$path = substr( $path, strlen( $sep ) );
		}

		if ( ! $path ) {
			$path = '';
		}

		// Process route arguments
		$patterns = array();
		if ( is_array( $args ) and count( $args ) ) {

			// Test for route placeholders
			preg_match_all( '#\[([a-z0-9]+)\]#', $path, $matches );
			if ( count( $matches ) ) {
				foreach ( $matches[ 1 ] as $label ) {

					// Placeholder pattern not found in route rule list
					if ( ! array_key_exists( $label, $args ) )
						$args[$label] = '[\s\S]+';

					$patterns[ $label ] = $args[ $label ];
					unset( $args[ $label ] );
				}
			}
		}

		// Store rule
		self::$rules[$group][ $path ] = array(
			'name'     => $name,
			'class'    => $class,
			'data'     => $args,
			'pattern'  => $patterns,
			'callback' => ( is_array( $callback ) ? $callback[ 0 ] . '::' . $callback[ 1 ] : $callback )
		);

		return true;
	}

	/**
	 * @param null $path
	 * @param array $args
	 */
	public function cli( $name = null, $path = null, $args = array(), $callback = null ) {
		if ( is_null( $path ) )
			return null;

		return self::add($name, $path, $args, $callback, 'cli');
	}

	/**
	 * @param null $path
	 * @param array $args
	 */
	public function http( $name = null, $path = null, $args = array(), $callback = null ) {
		if ( is_null( $path ) )
			return null;

		return self::add($name, $path, $args, $callback, 'http');
	}

	/**
	 * @param null $path
	 */
	public function get( $path = null, $group = null ) {
		if ( is_null( $path ) )
			return NULL;
		if (is_null($group))
			$group = self::$group;
		$sep = \Deft::config()->get( 'url_separator', '/' );
		if ( strpos( $path, $sep ) === 0 ) {
			$path = substr( $path, strlen( $sep ) );
		}

		if (array_key_exists($path, self::$rules[$group]) )
			return self::$rules[$group][ $path ];

		return FALSE;
	}

	/**
	 * @return array
	 */
	public function getRules($group = null) {
		if (is_null($group))
			$group = self::$group;
		return self::$rules[$group];
	}

	/**
	 * @param null $path
	 */
	public function remove( $path = null, $group = null ) {
		if ( strpos( $path, '/' ) === 0 ) {
			$path = substr( $path, 1 );
		}
		if (is_null($group))
			$group = self::$group;
		if ( array_key_exists( $path, self::$rules[$group] ) ) {
			unset( self::$rules[$group][ $path ] );
		}
	}

	/**
	 * @param null $name
	 */
	public function addSet( $name = null ) {

	}

	/**
	 * @param null $name
	 */
	public function removeSet( $name = null ) {

	}

	/**
	 *
	 */
	public function save() {
		$config = \Deft::config( 'config.route' );
		$config->set( self::getRules() );
		$config->save();
	}

	/**
	 * Syntactic suger
	 *
	 * @return mixed|object|void
	 */
	public function getName() {
		return self::current( 'name' );
	}

	public function request() {
		return self::current( 'request' );
	}

	public function getPath() {
		return self::current( 'path' );
	}

	public function getPattern() {
		return self::current( 'pattern' );
	}

	public function getParam( $key = null ) {
		$data = self::current( 'data' );
		if ( is_array( $data ) ) {
			if ( array_key_exists( $key, $data ) ) {
				return $data[ $key ];
			}
		}
	}

	public function getCallback() {
		return self::current( 'callback' );
	}
}

// Load route rules
\Deft::event()->set( 'init', '\Deft\Lib\Route::init', 10 );

// Process HTTP request against available route rules
\Deft::event()->set( 'init', '\Deft\Lib\Route::parse', 500 );