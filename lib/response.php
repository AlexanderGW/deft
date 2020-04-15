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

use Snappy\Lib;

class Response extends \Snappy_Concrete {
	private $buffer = NULL;

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
//	function __construct ($args = [], $class = __CLASS__) {
//		$args = array_merge(array(), $args);
//		$this->args = $args;
//		parent::__construct($this->args, $class);
//	}

	/**
	 * @param null $buffer
	 *
	 * @return string
	 */
	public function buffer($buffer = NULL) {
		$this->buffer = $buffer;
	}

	/**
	 * @param null $scope
	 *
	 * @return string
	 */
	public function output($content = NULL) {
		return $content;
	}

	/**
	 * Return the output()
	 *
	 * @return mixed
	 */
	public function __toString() {
		$content = $this->output();
		return $content;
	}
}