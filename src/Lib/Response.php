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

use Deft\Lib;

class Response extends \Deft_Concrete {
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
	public function type($buffer = NULL) {
		if (is_null($buffer))
			return $this->getArg('type');
		$this->setArg('type', $buffer);
		return TRUE;
	}

	/**
	 * @param null $buffer
	 *
	 * @return string
	 */
	public function buffer ($buffer = null, $scope = null) {
		$pos = &$this->buffer;
		if (is_string($scope)) {
			$items = \Deft\Lib\Helper::explodeLevel($scope);
			foreach ($items as $item) {
				if (!array_key_exists($item, $pos))
					$pos[$item] = [];
				$pos = &$pos[$item];
			}
		}

		array_push($pos, $buffer);
		return TRUE;
	}

	/**
	 * @param null $scope
	 *
	 * @return string
	 */
	public function output($content = NULL) {
		return is_null($content) ? $this->buffer : $content;
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