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

class Token {
	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private static $props = array();
	private static $cookie = false;
	private static $consent = false;

	/**
	 *
	 */
	public static function init() {
		if( self::$initialized )
			return;

		session_start();

		$hash = self::getHash();
		$data = null;
		if( !empty($_SESSION) && array_key_exists( $hash, $_SESSION ) )
			$data = &$_SESSION[ $hash ];
		elseif( array_key_exists( $hash, $_COOKIE ) ) {
			$data = &$_COOKIE[ $hash ];
			self::$cookie = true;
		}

		if( is_string( $data ) )
			self::$props = (array)\Deft::decode( $data );

		if(array_key_exists('consent', self::$props) and self::$props['consent'] != false) {
			self::$consent = true;
		}

		self::$initialized = true;
	}

	/**
	 * @return string
	 */
	public static function getHash() {
		$config = \Deft::config();
		$hash = $config->get( 'token.hash' );
		if( is_null( $hash ) ) {
			$hash = Random::getMd5();
			$config->set( array(
				'token.hash' => $hash,
				'token.timeout' => 2592000
			) );
			$config->save();
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
//		$_SESSION[ $hash ] = \Deft::encode( self::$props );
		return true;
	}

	/**
	 * @return bool
	 */
	public static function saveCookie($path = DEFT_URL_PATH, $host = null) {
		self::save();

		$timeout = (int)\Deft::config()->get( 'token.timeout', 2592000 );
		$timezone = (float)self::get( 'timezone' );
		$expire = ( ( TIME_UTC + ( 3600 * $timezone ) ) + $timeout );

		if( !count( self::$props ) ) {
			$value = -1;
			$expire = time() - 86400;
		} else
			$value = \Deft::encode( self::$props );

		$name = self::getHash();
		$host = null;//$host ?: \Deft::request()->host();
		setcookie( $name, $value, $expire, $path, $host );

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
	 *
	 */
	public static function haveConsent() {
		self::init();

		return self::$consent;
	}

	/**
	 * @return bool
	 */
	public static function isFromCookie() {
		self::init();

		return self::$cookie;
	}
}