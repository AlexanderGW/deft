<?php
/**
 * Created by PhpStorm.
 * User: Anonymous
 * Date: 23/10/2018
 * Time: 23:53
 */

class Cache {

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
		if (!is_null($key)) {
			return;
		}
		return;
	}
}