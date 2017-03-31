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

		$active_path = $active_pattern = $active_env = $active_callback = null;
		$routes = self::getRules();
		if( count( $routes ) ) {
			$divider = '/';

			// Process requested route
			if( strlen( SNAPPY_ROUTE ) ) {
				$found = array();
				$segments = explode( $divider, SNAPPY_ROUTE );
				$total_segments = count( $segments );
				$total_devide = $total_segments - 1;

				$request = SNAPPY_ROUTE;
				if( array_key_exists( $request, $routes ) ) {
					$active_path = $request;
					$active_env = $routes[ $request ]['env'];
				} else {
					for( $i = $total_segments; $i > 0; $i-- ) {
						if( $i < $total_segments )
							$request .= $divider;

						foreach( $routes as $path => $args ) {
							if( ( strpos( $path, $request ) === 0 or
							      ( substr_count( $path, $divider ) == $total_devide and strpos( $path, '[' ) !== false and strpos( $path, ']' ) !== false )
							) )
								$found[ $path ] = $args;
						}

						if( $i < $total_segments )
							break;

						array_pop( $segments );
						$request = implode( $divider, $segments );
					}

					// Process potential patterns
					if( count( $found ) ) {
						foreach( $found as $path => $args ) {
							if( array_key_exists( 'pattern', $args ) and count( $args['pattern'] ) ) {

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

									$active_path = $path;
									$active_pattern = $path_pattern;
									$active_env = $args['env'];
									$active_callback = $args['callback'];
									break;
								}
							}
						}
					}
				}
			}

			// Default
			elseif( array_key_exists( '', $routes ) and array_key_exists( 'env', $routes[''] ) ) {
				$active_path = $active_pattern = '';
				$active_env = (array)$routes['']['env'];
				$active_callback = $routes['']['callback'];
			}

			// Apply route environment
			if( is_array( $active_env ) ) {
				foreach( $active_env as $key => $value ) {
					$key = Helper::trimAllCtrlChars( $key );
					$value = Helper::trimAllCtrlChars( $value );
					$_GET[ $key ] = $value;
				}
			}
		}

		if( !is_null( $active_callback ) and is_callable( $active_callback ) )
			call_user_func( $active_callback );

		Snappy::log( 'route', array(
			'path' => $active_path,
			'pattern' => $active_pattern,
			'env' => $active_env,
			'callback' => $active_callback
		) );

		self::$parsed = true;
	}

	/**
	 * @param null $path
	 * @param array $args
	 */
	public static function add( $path = null, $args = array(), $callback = null ) {
		if( is_null( $path ) )
			return;
		if( strpos( $path, '/' ) === 0 )
			$path = substr( $path, 1 );
		if( !$path )
			$path = '';

		if( is_array( $args ) and count( $args ) ) {
			$patterns = $errors = array();
			preg_match_all( '#\[([a-z0-9]+)\]#', $path, $matches );
			if( count( $matches ) ) {
				foreach( $matches[1] as $name ) {
					if( !array_key_exists( $name, $args ) )
						$errors[] = $name;
					else {
						$patterns[ $name ] = $args[ $name ];
						unset( $args[ $name ] );
					}
				}

				if( count( $errors ) )
					Snappy::error( 'Route rule "%1$s" missing required parameter(s): %2$s', $path, implode( ', ', $errors ) );
			}
		}

		self::$rules[ $path ] = array(
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
			return;
		if( strpos( $path, '/' ) === 0 )
			$path = substr( $path, 1 );
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

Hook::add( 'init', array( 'Route', 'init' ), 1 );
Hook::add( 'init', array( 'Route', 'parse' ), 999 );