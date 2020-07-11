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

namespace Deft\Lib\Response\Http;

use Deft\Lib\Response\Http;

/**
 * Response HTTP JSON class
 *
 * Class Document
 */
class Json extends Http {
	private $buffer = NULL;

	/**
	 * Database constructor.
	 *
	 * @param array $args
	 */
	function __construct ($args = array()) {
		$args = array_merge(array(), $args);
		$this->args = $this->getArgs($args);
		parent::__construct($this->args, __CLASS__);
	}

	/**
	 * @param null $buffer
	 *
	 * @return string
	 */
	public function buffer($buffer = NULL) {
		$this->buffer = $buffer;
		return TRUE;
	}

	/**
	 * @return bool
	 */
	public function isEmpty () {
		return !strlen($this->buffer);
	}

	/**
	 * @param null $scope
	 *
	 * @return mixed|string|void
	 */
	public function output($content = null) {
		$this->header('Content-type', 'text/json');

		\Deft::event()->exec('beforeResponseOutput', $this->args);

		if (is_null($content)) {
			$content = is_null($this->buffer) ? 'N;' : $this->buffer;

			// Nothing to output, set status to 404
			if (is_null($this->buffer)) {
				\Deft::response()->status(404);
			}
		}

		$content = \Deft::filter()->exec('responseHttpJsonOutput', \Deft::filter()->exec('responseOutput', $content));

		$this->header('Content-length', strlen($content));

		\Deft::event()->exec('afterResponseOutput', $this->args);

		// Set HTTP header()s
		parent::output();

		return $content;
	}
}