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

namespace Deft\Lib\Request;

use Deft\Lib\Request;
use Deft\Lib\Sanitize;

class Cli extends Request {
	protected $flags = [];

	/**
	 * Cli request constructor.
	 *
	 * @param null $args
	 */
	function __construct () {

		\Deft::config()->set('response.type', 'cli');

		$argv = $_SERVER['argv'];

		$this->caller = array_shift($argv);

		foreach ($argv as $value) {
			$value = Sanitize::noControlAll($value);
			if (strpos($value, '-') === 0) {

				// Single flag, long form
				if (strpos($value, '-', 1) === 1) {
					$this->flags[] = substr($value, 2);
				}

				// One or more short form flags
				else {
					$this->flags = array_merge($this->flags, str_split(substr($value, 1)));
				}
			}

			// Argument
			else
				$this->args[] = $value;
		}
	}

	/**
	 *
	 */
	public function caller() {
		return $this->caller;
	}

	/**
	 *
	 */
	public function args() {
		return $this->args;
	}

	/**
	 *
	 */
	public function flags() {
		return $this->flags;
	}
}