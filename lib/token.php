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

class Token {
	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private static $props = array();
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
			self::$props = (array)Snappy::decode( $data );

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
		self::init();

		$hash = self::getHash();
		self::$props = Filter::exec( 'tokenSave', self::$props );
		$_SESSION[ $hash ] = Snappy::encode( self::$props );
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

		if( !count( self::$props ) ) {
			$encoded = -1;
			$expire = time() - 86400;
		} else
			$encoded = Snappy::encode( self::$props );

		setcookie( $hash, $encoded, $expire, SNAPPY_URL_PATH, $_SERVER['HTTP_HOST'] );
		return true;
	}

	/**
	 *
	 */
	public static function clear() {
		self::$props = array();
		self::saveCookie();
	}

	/**
	 * @param null $a
	 * @param null $b
	 */
	public static function set( $a = null, $b = null ) {
		self::init();

		if( is_string( $a ) )
			self::$props[ $a ] = $b;

		if( is_array( $a ) )
			self::$props = array_merge( self::$props, $a );
	}

	/**
	 * @param null $a
	 */
	public static function get( $a = null ) {
		self::init();

		if( !is_null( $a ) and array_key_exists( $a, self::$props ) )
			return self::$props[ $a ];

		return;
	}

	/**
	 * @return bool
	 */
	public static function hasData() {
		self::init();

		if( !is_null( self::$props ) )
			return true;
		return false;
	}

	/**
	 * @return bool
	 */
	public static function isFromCookie() {
		self::init();

		return self::$cookie;
	}
}