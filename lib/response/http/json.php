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

namespace Snappy\Lib\Response\Http;

use Snappy\Lib\Response\Http;

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
	 * @param null $scope
	 *
	 * @return mixed|string|void
	 */
	public function output($content = null) {
		$this->header('Content-type', 'text/json');

		\Snappy::event()->exec('beforeResponseOutput', $this->args);

		if (is_null($content)) {
			$content = is_null($this->buffer) ? 'N;' : $this->buffer;

			// Nothing to output, set status to 404
			if (is_null($this->buffer)) {
				\Snappy::response()->status(404);
			}
		}

		$content = \Snappy::filter()->exec('responseHttpJsonOutput', \Snappy::filter()->exec('responseOutput', $content));

		$this->header('Content-length', strlen($content));

		\Snappy::event()->exec('afterResponseOutput', $this->args);

		// Set HTTP header()s
		parent::output();

		return $content;
	}
}