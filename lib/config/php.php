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

namespace Snappy\Lib\Config;

use Snappy\Lib\Config;

class Php extends Config {

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	function __construct ($scope = null, $class = __CLASS__) {
		$this->scope =

			// The initial Snappy config is always passed as an array, so default to 'config.snappy'
			is_array($scope) ? 'config.snappy'

				// Otherwise it should be a scope string
				: self::getArgs($scope);

		if (is_array($scope)) {
			$this->fields = $scope;
			$this->empty  = FALSE;
			$this->path = SNAPPY_PATH . DS . 'config' . DS . 'snappy.php';
		} else {
			$this->path = SNAPPY_PATH . DS . str_replace('.', DS, $this->scope) . '.' . $this->type;

			if (file_exists($this->path)) {
				$this->exists = TRUE;
				if (is_readable($this->path)) {
					$this->readable = TRUE;

					try {
						$array = include $this->path;
					}
					catch (Exception $e) {
						$this->exception = $e;
					}

					if (isset($array) and is_array($array)) {
						$this->fields = $array;
						$this->empty  = FALSE;
					}
				}
			}
		}

		parent::__construct($this->scope, $class);
	}

	/**
	 * @param null $item
	 * @param int $depth
	 *
	 * @return string
	 */
	private function _save ($item = null, $depth = 1) {
		if (is_null($item)) {
			return 'null';
		}

		$padding_char = "\t";
		$padding      = str_repeat($padding_char, $depth);

		if (is_array($item)) {
			$content = "array(\r\n";

			foreach ($item as $key => $val) {
				if (!is_null($val)) {
					$array_key = (!is_string($key) ? $key : "'" . $key . "'");

					$content .= sprintf(
						"%s%s => %s,%s",
						$padding . $padding_char,
						$array_key,
						$this->_save($val, ($depth + 1)),
						"\r\n"
					);
				}
			}

			$content = preg_replace("/,\r\n$/", "\r\n", $content);
			$content .= $padding . ")";

			return $content;
		}

		if (is_int($item)) {
			return $item;
		}

		return "'" . $item . "'";
	}

	/**
	 * @return bool
	 */
	function output() {
		if (count($this->fields)) {
			ksort($this->fields);

			$content = null;

			// Default PHP ouput
			if (!$content) {
				$content = "<" . "?php" . PHP_EOL . PHP_EOL
				           . "/" . "**" . PHP_EOL
				           . " * File: " . str_replace(SNAPPY_PATH . DS, '', $this->path) . PHP_EOL
				           . " * Date: " . gmdate('Y-m-d H:i:s') . PHP_EOL
				           . " * Auto-generated configuration by the Snappy Framework" . PHP_EOL
				           . " */" . PHP_EOL . PHP_EOL
				           . "return array(" . PHP_EOL;

				foreach ($this->fields AS $arg => $value) {
					$content .= "\t'" . $arg . "' => " . $this->_save($value) . "," . PHP_EOL;
				}
				$content = preg_replace("/," . PHP_EOL . "$/", PHP_EOL, $content);
				$content .= ");";
			}

			return $content;
		}

		return FALSE;
	}


	/**
	 * @return bool
	 */
	function save ($type = NULL) {
		$args = [
			'type' => is_string($type) ? $type : $this->args['filesystem.type']
		];

		// Get the filesystem
		$this->filesystem = \Snappy::filesystem($args);
		if ($this->filesystem) {
//			$this->filesystem->touch($this->path);

			$content = $this->output();

			// Write the contents to the filesystem
			$result = $this->filesystem->write($this->path, $content);
			if ($result)
				return TRUE;
			else {
				var_dump('write failure: ' . $this->path);
			}
//			\Snappy::watchdog()->add();
		} else {
//			\Snappy::watchdog()->add();
		}

		return FALSE;
	}
}