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

class Token {
	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private static $fields = array();
	private static $cookie = false;

	/**
	 *
	 */
	public static function init() {
		if( self::$initialized )
			return;

		session_start();

		$hash = self::getHash();
		$data = null;
		if( array_key_exists( $hash, $_SESSION ) )
			$data =& $_SESSION[ $hash ];
		elseif( array_key_exists( $hash, $_COOKIE ) ) {
			$data =& $_COOKIE[ $hash ];
			self::$cookie = true;
		}

		if( is_string( $data ) )
			self::$fields = (array)Snappy::decode( $data );

		self::$initialized = true;
	}

	/**
	 * @return string
	 */
	public static function getHash() {
		$cfg =& Snappy::getCfg();
		$hash = $cfg->get( 'token_hash' );
		if( is_null( $hash ) ) {
			$hash = Helper::getRandomHash();
			$cfg->set( array(
				'token_hash' => $hash,
				'token_timeout' => 2592000
			) );
			$cfg->save();
		}

		return $hash;
	}

	/**
	 * @return bool
	 */
	public static function save() {
		if( !self::$initialized )
			self::init();

		$hash = self::getHash();
		self::$fields = Filter::exec( 'tokenSave', self::$fields );
		$_SESSION[ $hash ] = Snappy::encode( self::$fields );
		return true;
	}

	/**
	 * @return bool
	 */
	public static function saveCookie() {
		self::save();
		$hash = self::getHash();
		$timeout = (int)Snappy::getCfg()->get( 'token_timeout', 2592000 );
		$expire = ( ( TIME_UTC + ( 3600 * (float)self::get( 'timezone' ) ) ) + $timeout );

		if( !count( self::$fields ) ) {
			$encoded = -1;
			$expire = time() - 86400;
		} else
			$encoded = Snappy::encode( self::$fields );

		setcookie( $hash, $encoded, $expire );
		return true;
	}

	/**
	 *
	 */
	public static function clear() {
		self::$fields = array();
		self::saveCookie();
	}

	/**
	 * @param null $a
	 * @param null $b
	 */
	public static function set( $a = null, $b = null ) {
		if( !self::$initialized )
			self::init();

		if( is_string( $a ) )
			self::$fields[ $a ] = $b;

		if( is_array( $a ) )
			self::$fields = array_merge( self::$fields, $a );
	}

	/**
	 * @param null $a
	 */
	public static function get( $a = null ) {
		if( !self::$initialized )
			self::init();

		if( !is_null( $a ) and array_key_exists( $a, self::$fields ) )
			return self::$fields[ $a ];

		return;
	}

	/**
	 * @return bool
	 */
	public static function hasData() {
		if( !self::$initialized )
			self::init();

		if( !is_null( self::$fields ) )
			return true;
		return false;
	}

	/**
	 * @return bool
	 */
	public static function isFromCookie() {
		if( !self::$initialized )
			self::init();

		return self::$cookie;
	}
}