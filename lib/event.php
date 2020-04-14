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

use Snappy\Lib\Helper;

class Event {
	private static $actions = array();

	static function set ($name = null, $function = null, $priority = 100, $arg_count = 1) {
		if (!is_null($name) and !is_null($function)) {
			if (!array_key_exists($name, self::$actions)) {
				self::$actions[$name] = array();
			}

			$priority = intval($priority);
			if (!array_key_exists($priority, self::$actions[$name])) {
				self::$actions[$name][$priority] = array();
			}

			self::$actions[$name][$priority][] = array($function, $arg_count);

			return true;
		}

		return;
	}

	static function get ($name = null) {
		if (!is_null($name)) {
			if (array_key_exists($name, self::$actions)) {
				return self::$actions[$name];
			}
		}

		return;
	}

	static function clear ($name = null, $function = null) {
		if (!is_null($name) and array_key_exists($name, self::$actions)) {
			$state = FALSE;

			if (is_null($function)) {
				self::$actions[$name] = array();
				$state = TRUE;
			} else {
				foreach (self::$actions[$name] as $priority => $actions) {
					foreach ($actions as $i => $action) {
						if ((is_array($function) and $action[0][0] == $function[0] and $action[0][1] == $function[1]) or $action[0] == $function) {
							unset(self::$actions[$name][$priority][$i]);
							$state = TRUE;

							if (!count(self::$actions[$name][$priority])) {
								unset(self::$actions[$name][$priority]);
								$state = TRUE;
							}
						}
					}
				}
			}

			return $state;
		}

		return false;
	}

	/**
	 * Execute all event actions in order of priority, and alphabetical order.
	 *
	 * @return bool
	 */
	static function exec ( /*polymorphic*/) {
		if (!func_num_args()) {
			return;
		}

		$state = FALSE;
		$args = func_get_args();
		$name = array_shift($args);

		if (array_key_exists($name, self::$actions)) {
			$queue =& self::$actions[$name];
			if (count($queue)) {
				ksort($queue);

				$array = array();
				$start = Helper::getMicroTime();

				foreach ($queue as $priority => $callbacks) {
					foreach ($callbacks as $callback) {
						if (is_callable($callback[0])) {
							$return = call_user_func_array($callback[0], array_slice($args, 0, $callback[1]));
							$array[$priority][] = array(
								'callback' => (is_array($callback[0]) ? $callback[0][0] . '::' . $callback[0][1] : $callback[0]),
								'return'   => $return
							);

							if ($return !== FALSE)
								$state = TRUE;
						}
					}
				}

				\Snappy::log('event/' . $name, array(
					'time'      => Helper::getMoment($start),
					'callbacks' => $array
				));
			}
		}

		return $state;
	}
}