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

class Helper {

	/**
	 * Alphanumeric characters
	 */
	const ALPHANUMERIC_CHARS = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';

	/**
	 * Basic set of extended ASCII characters
	 */
	const EXTENDED_CHARS = '!#$%^&()*+-.,:;<=>?@[]_';

	/**
	 * @param null $arg
	 * @param null $global
	 */
	public static function getGlobal( $arg = null, $global = null ) {
		global $GLOBALS;
		if( !is_null( $global ) and
		    !is_null( $arg ) and
		    array_key_exists( $global, $GLOBALS ) and
		    array_key_exists( $arg, $GLOBALS[ $global ] )
		)
			return $GLOBALS[ $global ][ $arg ];
		return;
	}

	/**
	 * Private recursive function for the main Helper::dir() method.
	 *
	 * @param string $path
	 *
	 * @return null|array
	 */
	private static function _getDirectory( $path ) {
		if( !is_dir( $path ) )
			return;
		$return = array();
		$dir = opendir( $path );
		while( ( $item = readdir( $dir ) ) !== false ) {
			if( $item == '.' || $item == '..' )
				continue;
			$item_path = $path . DS . $item;
			if( is_dir( $item_path ) )
				$return[ $item ] = self::_getDirectory( $item_path );
			else
				$return[ $item ] = $item_path;
		}
		closedir( $dir );
		return $return;
	}

	/**
	 * Returns a multi-dimensional array of the directory structure if found.
	 * Values are the absolute path of the item or array if directory.
	 *
	 * @param string $path
	 *
	 * @return array
	 */
	public static function getDirectory( $path ) {
		return self::_getDirectory( $path );
	}

	/**
	 * @return float
	 */
	public static function getMicroTime() {
		$time = explode( ' ', microtime() );
		return ( (float)$time[1] + (float)$time[0] );
	}

	/**
	 * @param null $start
	 *
	 * @return float
	 */
	public static function getMoment( $start = null ) {
		if( is_null( $start ) )
			$start = \Snappy::$start;
		return round( self::getMicroTime() - $start, 12 );
	}

	/**
	 * Gets token hash for CSRF, creates if one does not exist
	 *
	 * @return string
	 */
	public static function getCsrfHash() {
		$hash = Token::get( 'csrf' );
		if( $hash ) {
			if( time() > ( Token::get( 'csrf_time' ) + \Snappy::config()->get( 'csrf_timeout', 900 ) ) )
				$hash = null;
		}

		if( !$hash ) {
			$hash = Random::getMd5();
			Token::set( array(
				'csrf' => $hash,
				'csrf_time' => time()
			) );
			Token::saveCookie();
		}
		return $hash;
	}

	// compareCsrfHash
	public static function verifyCsrfHash( $hash = null ) {
		if( is_string( $hash ) ) {
			$hash_on_token = Token::get( 'csrf' );
			if( $hash_on_token === $hash ) {
				if( time() < ( Token::get( 'csrf_time' ) + \Snappy::config()->get( 'csrf_timeout', 900 ) ) )
					return true;
			}
		}
		return false;
	}

	/**
	 * @param int $seconds
	 * @param null $separator
	 *
	 * @return string
	 */
	public static function getTimeframeFromSeconds( $seconds = 0, $separator = null ) {
		if( is_null( $separator ) )
			$separator = ' ';

		$string = '';
		$seconds -= ( ( $weeks = floor( $seconds / 604800 ) ) * 604800 );
		$seconds -= ( ( $days = floor( $seconds / 86400 ) ) * 86400 );
		$seconds -= ( ( $hours = floor( $seconds / 3600 ) ) * 3600 );
		$seconds -= ( ( $minutes = floor( $seconds / 60 ) ) * 60 );

		if( $weeks > 0 )
			$string .= __( ( $weeks == 1 ? '%d week' : '%d weeks' ), $weeks ) . $separator;
		if( $days > 0 )
			$string .= __( ( $days == 1 ? '%d day' : '%d days' ), $days ) . $separator;
		if( $hours > 0 )
			$string .= __( ( $hours == 1 ? '%d hour' : '%d hours' ), $hours ) . $separator;
		if( $minutes > 0 )
			$string .= __( ( $minutes == 1 ? '%d minute' : '%d minutes' ), $minutes ) . $separator;
		if( !strlen( $string ) or $seconds > 0 )
			$string .= __( ( $seconds == 1 ? '%d second' : '%d seconds' ), $seconds );

		return $string;
	}

	/**
	 * Converts bytes integer into appropriate prefixed multiple
	 *
	 * @param int $seconds
	 * @param null $separator
	 *
	 * @return string
	 */
	public static function getShnoFromBytes( $bytes = 0, $maxunit = false ) {
		if( !is_int( $bytes ) )
			return null;

		$unit = 0;
		// self::SI_UNIT_BYTES
		// a construct to capture custom arrays of above, etc. Before any actual (real) calls are made.
		$suffix = array( __( 'bytes' ), __( 'KB' ), __( 'MB' ), __( 'GB' ), __( 'TB' ) );
		$precision = array( 0, 0, 0, 1, 2 );

		while( $bytes >= 1024 ) {
			if( is_numeric( $maxunit ) AND $unit >= $maxunit )
				break;
			$bytes = ( $bytes / 1024 );
			$unit++;
		}

		return number_format( $bytes, $precision[ $unit ] ) . $suffix[ $unit ];
	}

	/**
	 * Convert shorthand notation bytes (with modifier) into bytes
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	static function getBytesFromShno( $value ) {
		$bytes = trim( $value );
		$modifier = strtolower( $value[ strlen( $value ) - 1 ] );

		switch( $modifier ) {
			case 't' : case 'tb' : $bytes *= 1024;
			case 'g' : case 'gb' : $bytes *= 1024;
			case 'm' : case 'mb' : $bytes *= 1024;
			case 'k' : case 'kb' : $bytes *= 1024;
		}

		return $bytes;
	}
}