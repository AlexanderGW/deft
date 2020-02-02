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

namespace Snappy\Lib;

class Filter {
	private static $actions = array();

	static function add( $name = null, $function = null, $priority = 10, $arg_count = 1 ) {
		if( !is_null( $name ) and !is_null( $function ) ) {
			if( !array_key_exists( $name, self::$actions ) )
				self::$actions[ $name ] = array();

			$priority = intval( $priority );
			if( !array_key_exists( $priority, self::$actions[ $name ] ) )
				self::$actions[ $name ][ $priority ] = array();

			self::$actions[ $name ][ $priority ][] = array( $function, $arg_count );
			return true;
		}
		return false;
	}

	static function get( $name = null ) {
		if( !is_null( $name ) )
			if( array_key_exists( $name, self::$actions ) )
				return self::$actions[ $name ];
		return false;
	}

	static function clear( $name = null, $function = null ) {
		if( !is_null( $name ) and array_key_exists( $name, self::$actions ) ) {
			if( is_null( $function ) )
				self::$actions[ $name ] = array();
			else {
				foreach( self::$actions[ $name ] as $priority => $actions ) {
					foreach( $actions as $i => $action ) {
						if( ( is_array( $function ) and $action[0][0] == $function[0] and $action[0][1] == $function[1] ) or $action[0] == $function ) {
							unset( self::$actions[ $name ][ $priority ][ $i ] );

							if( !count( self::$actions[ $name ][ $priority ] ) )
								unset( self::$actions[ $name ][ $priority ] );
						}
					}
				}
			}
			return true;
		}
		return false;
	}

	static function exec( /*polymorphic*/ ) {
		if( !func_num_args() )
			return;

		$args = func_get_args();
		$name = array_shift( $args );
		$value = $args[0];

		if( array_key_exists( $name, self::$actions ) ) {
			$queue =& self::$actions[ $name ];
			if( count( $queue ) ) {
				ksort( $queue );

				$array = array();
				$start = Helper::getMicroTime();

				foreach( $queue as $priority => $callbacks ) {
					foreach( $callbacks as $callback ) {
						$args[0] = $value;
						if( is_callable( $callback[0] ) ) {
							$array[ $priority ][] = ( is_array( $callback[0] ) ? $callback[0][0] . '::' . $callback[0][1] : $callback[0] );
							$value = call_user_func_array( $callback[0], array_slice( $args, 0, $callback[1] ) );
						}
					}
				}

				\Snappy::log( 'filter/' . $name, array(
					'time' => Helper::getMoment( $start ),
					'callbacks' => $array
				) );
			}
		}
		return $value;
	}
}