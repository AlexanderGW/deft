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
 * JSON response class
 *
 * Class Document
 */
class Json extends Http {
	private $errors = array();
	private $eol = "\r\n";
	private $body = array();

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
	 * @param null $scope
	 *
	 * @return mixed|string|void
	 */
	public function output($content = null) {
		if (is_null($content))
			$content = (string)$this->getArg('content');

		\Snappy::response()->header('Content-type', 'text/json');

		\Snappy::event()->exec('beforeResponseOutput');

		$content = \Snappy::filter()->exec('responseHttpJsonOutput', \Snappy::filter()->exec('responseOutput', $content));

		\Snappy::response()->header('Content-length', strlen($content));

		\Snappy::event()->exec('afterResponseOutput', $content);

		return $content;
	}
}