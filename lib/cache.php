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

class Cache extends \Snappy_Concrete {

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private $data = array();

	/**
	 *
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}
//		var_dump(Snappy::get('cache.memcached')->get(Snappy::request()->url()));exit;
		self::$initialized = true;
	}

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	function __construct ($scope = null) {
		$this->scope = self::getArgs($scope);
	}

	/**
	 * @param null $args
	 *
	 * @return null|string
	 */
	public static function getArgs ($args = null) {
		if (!is_string($args)) {
			$args = 'cache';
		}

		return $args;
	}

	public function get($key = null) {
//		if (!is_null($key) AND array_key_exists($key, $this->data)) {
			$key = md5($key);
			return ($this->data[$key]);
//		}
		return;
	}

	public function set($key = null, $value = null) {
//		if (!is_null($key)) {
			$key = md5($key);
			$this->data[$key] = ($value);
//			var_dump($this->data);
			return $key;
//		}
		return;
	}

	public function dump() {
		return $this->data;
	}
}

// Process HTTP request against available route rules
//Event::set( 'init', array( 'Cache', 'init' ), 20 );