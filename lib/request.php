<?php

/**
 * Snappy, a PHP framework for PHP 5.3+
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

class Request {

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	public static $url = false;

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		if (PHP_SAPI  == 'cli') {
			$request = $_SERVER['argv'];
			die('CLI not implemented');
		} else {
			$request = $_SERVER['REQUEST_URI'];

			$query = null;
			$pos = strpos($request, '?');
			if ($pos !== false) {
				$path = urldecode(substr($request, 0, $pos));
				$query = http_build_query(
					filter_input_array(
						INPUT_GET,
						FILTER_SANITIZE_URL
					)
				);
			} else
				$path = urldecode($request);

			self::$url = parse_url((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https:" : "http:")
			                       . "//{$_SERVER['HTTP_HOST']}"
			                       . $path
			                       . ($query ? '?' . $query : NULL));

			// Make query an array
			if (array_key_exists('query', self::$url)) {
				parse_str(self::$url['query'], self::$url['query']);
			}
		}

		self::$initialized = TRUE;
	}

	/**
	 *
	 */
	public static function has($key = null) {
		return (array_key_exists($key, self::$url));
	}

	/**
	 *
	 */
	public static function get($key = null) {
		if (self::has($key))
			return self::$url[$key];
		return;
	}
}

Request::init();