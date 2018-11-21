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

if( !defined( 'IN_SNAPPY' ) ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit;
}

class Route {
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

	private static $rules = array();

	private static $route = array();

	/**
	 *
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		$cfg =& Snappy::getCfg( 'config.route' );
		if( !$cfg->isEmpty() ) {
			$routes = $cfg->get( 'routes', array() );
			if( count( $routes ) ) {
				foreach( $routes as $path => $rule )
					Route::add( $path, $rule );
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

		// Process requested route
		self::$route = self::get( SNAPPY_ROUTE );

		// No match
		if( self::$route === null and strlen( SNAPPY_ROUTE ) ) {
			$routes = self::getRules();
			$divider = Snappy::getCfg()->get( 'url_separator', '/' );

			$request = explode( $divider, SNAPPY_ROUTE );
			array_pop( $request );
			$max = count( $request );
			//$request = implode( $divider, $request );

			foreach( $routes as $path => $args ) {
				if(

					// Check for potentials with the same depth (path dividers), which also has placeholder(s)
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
					foreach( $args['pattern'] as $name => $pattern )
						$path_pattern = str_replace( '[' . $name . ']', '(' . $pattern . ')', $path_pattern );

					// Run pattern
					preg_match( $path_pattern, SNAPPY_ROUTE, $matches );
					if( count( $matches ) ) {
						preg_match_all( '#\[([a-z0-9]+)\]#', $path, $keys );
						array_shift( $matches );

						foreach( $matches as $i => $match )
							$args['env'][ $keys[1][ $i ] ] = $match;

						self::$route = array_merge(
							$args,
							array(
								'path' => $path,
								'pattern' => $path_pattern
							)
						);

						break;
					}
				}
			}
		}

		// Apply route environment
		if( is_array( self::$route['env'] ) ) {
			foreach( self::$route['env'] as $key => $value ) {
				$key = Helper::trimAllCtrlChars( $key );
				$value = Helper::trimAllCtrlChars( $value );
				$_GET[ $key ] = $value;
			}
		}

		// Route callback
		if( is_callable( self::$route['callback'] ) )
			call_user_func( self::$route['callback'] );

		// Logging
		Snappy::log( 'route/' . self::$route['name'], array(
			'name' => self::$route['name'],
			'request' => SNAPPY_ROUTE,
			'path' => self::$route['path'],
			'pattern' => self::$route['pattern'],
			'env' => self::$route['env'],
			'callback' => self::$route['callback']
		) );

		self::$parsed = true;
	}

	/**
	 *
	 */
	public static function current( $arg = null ) {
		if( is_null( $arg ) )
			return (object)self::$route;
		if( array_key_exists( $arg, self::$route ) )
			return self::$route[ $arg ];
		return;
	}

	/**
	 * @param null $path
	 * @param array $args
	 */
	public static function add( $name = null, $path = null, $args = array(), $callback = null ) {
		if( is_null( $path ) )
			return;
		if( is_null( $name ) )
			$name = md5( $path );
		if( strpos( $path, Snappy::getCfg()->get( 'url_separator', '/' ) ) === 0 )
			$path = substr( $path, 1 );
		if( !$path )
			$path = '';

		// Process route arguments
		if( is_array( $args ) and count( $args ) ) {
			$patterns = $errors = array();

			// Test for route placeholders
			preg_match_all( '#\[([a-z0-9]+)\]#', $path, $matches );
			if( count( $matches ) ) {
				foreach( $matches[1] as $label ) {

					// Placeholder pattern not found in route rule list
					if( !array_key_exists( $label, $args ) )
						$errors[] = $label;
					else {
						$patterns[ $label ] = $args[ $label ];
						unset( $args[ $label ] );
					}
				}

				// Throw route rule pattern errors
				if( count( $errors ) )
					Snappy::error( 'Route rule "%1$s" missing required parameter(s): %2$s', $path, implode( ', ', $errors ) );
			}
		}

		// Store rule
		self::$rules[ $path ] = array(
			'name' => $name,
			'env' => $args,
			'pattern' => $patterns,
			'callback' => ( is_array( $callback ) ? $callback[0] . '::' . $callback[1] : $callback )
		);

		return true;
	}

	/**
	 * @param null $path
	 */
	public static function get( $path = null ) {
		if( is_null( $path ) )
			return null;

		$sep = Snappy::getCfg()->get( 'url_separator', '/' );
		if( strpos( $path, $sep ) === 0 )
			$path = substr( $path, strlen( $sep ) );
		return self::$rules[ $path ];
	}

	/**
	 * @return array
	 */
	public static function getRules() {
		return self::$rules;
	}

	/**
	 * @param null $path
	 */
	public static function remove( $path = null ) {
		if( strpos( $path, '/' ) === 0 )
			$path = substr( $path, 1 );
		if( array_key_exists( $path, self::$rules ) )
			unset( self::$rules[ $path ] );
	}

	/**
	 * @param null $name
	 */
	public static function addSet( $name = null ) {

	}

	/**
	 * @param null $name
	 */
	public static function removeSet( $name = null ) {

	}

	/**
	 *
	 */
	public static function save() {
		$cfg =& Snappy::getCfg( 'config.route' );
		$cfg->set( self::getRules() );
		$cfg->save();
	}
}

// Load route rules
Hook::add( 'init', array( 'Route', 'init' ), 1 );

// Process HTTP request against available route rules
Hook::add( 'init', array( 'Route', 'parse' ), 999 );