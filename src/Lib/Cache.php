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

class Cache extends \Deft_Concrete {

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private $data = array();

	private $storage = NULL;

	/**
	 *
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}
//		var_dump(Deft::lib('cache.memcached')->get(Deft::request()->url()));exit;
		self::$initialized = true;
	}

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	function __construct ($scope = null) {
		$this->scope = self::getArgs($scope);
//		$config = \Deft::config('cache.' . $scope);

		$config  = \Deft::config();
		$this->storage = \Deft::storage(array(
			'structure'    => $config->get('cache.structure', 'dictionary'),
			'type'         => $config->get('cache.type', 'memcached'),
			'host'         => $config->get('cache.hostname'),
			'username'     => $config->get('cache.username'),
			'password'     => $config->get('cache.password'),
			'dbname'       => $config->get('cache.name'),
			'table_prefix' => $config->get('cache.table.prefix'),
			'port'         => $config->get('cache.port')
		));
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

	public function storage($args = NULL) {
		if (is_null($args))
			return $this->storage;
	}

	public function get($key = null) {
//		if (!is_null($key) AND array_key_exists($key, $this->data)) {
			$key = md5($key);
			if (array_key_exists($key, $this->data)) {
				$this->data[$key] = $this->storage()->get($key);
			}
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
//\Deft::event()->set( 'init', array( 'Cache', 'init' ), 20 );