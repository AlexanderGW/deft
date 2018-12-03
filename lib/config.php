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

class Config extends Snappy_Concrete {
	private $path = null;
	private $exists = false;
	private $type;
	private $readable = false;
	private $empty = true;
	private $fields = array();
	private $exception = null;

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	function __construct ($scope = null) {
		$this->scope = self::getArgs($scope);
		$this->type = Snappy::support('yml') ? 'yml' : 'php';
		$this->path = SNAPPY_PATH . str_replace('.', DS, $this->scope) . '.' . $this->type;

		if (file_exists($this->path)) {
			$this->exists = true;
			if (is_readable($this->path)) {
				$this->readable = true;

				try {
					$array = include $this->path;
				}
				catch (Exception $e) {
					$this->exception = $e;
				}

				if (isset($array) and is_array($array)) {
					$this->fields = $array;
					$this->empty  = false;
				}
			}
		}

		parent::__construct(__CLASS__, $this->scope);
	}

	/**
	 * @param null $args
	 *
	 * @return null|string
	 */
	public static function getArgs ($args = null) {
		if (!is_string($args)) {
			$args = 'config.snappy';
		}

		return $args;
	}

	/**
	 * @param int $arg
	 * @param null $fallback
	 *
	 * @return array|null|string|void
	 */
	function get ($arg = - 1, $fallback = null) {
		if (is_null($arg)) {
			return;
		}

		if ($arg < 0) {
			return $this->fields;
		}

		if (array_key_exists($arg, $this->fields)) {
			$return = $this->fields[$arg];

			return is_string($return) ? stripslashes($return) : $return;
		}

		return $fallback;
	}

	/**
	 * @param null $x
	 * @param null $y
	 */
	function set ($x = null, $y = null) {
		if (is_null($x)) {
			return;
		}

		if (is_array($x)) {
			foreach ($x as $a => $b) {
				$this->fields[$a] = $b;
			}
		} else {
			$this->fields[$x] = $y;
		}
	}

	/**
	 * @param $field
	 *
	 * @return bool
	 */
	function remove ($field) {
		if (array_key_exists($field, $this->fields) !== false) {
			unset($this->fields[$field]);

			return true;
		}

		return false;
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
	function save () {
		if (count($this->fields)) {
			ksort($this->fields);

			if (!file_exists($this->path)) {
				touch($this->path);
			}

			$content = null;

			// YAML support?
			if ($this->type == 'yml') {
				yaml_emit_file($this->path, $this->fields, YAML_UTF8_ENCODING);
				$content = true;
			}


			// Default PHP ouput
			if (!$content) {
				$content = "<" . "?php" . PHP_EOL . PHP_EOL
				           . "/" . "**" . PHP_EOL
				           . " * File: " . str_replace(SNAPPY_PATH, '', $this->path) . PHP_EOL
				           . " * Date: " . gmdate('Y-m-d H:i:s') . PHP_EOL
				           . " * Auto-generated configuration by the Snappy Framework" . PHP_EOL
				           . " *" . PHP_EOL . PHP_EOL
				           . "return array(" . PHP_EOL;

				foreach ($this->fields AS $arg => $value) {
					$content .= "\t'" . $arg . "' => " . $this->_save($value) . "," . PHP_EOL;
				}
				$content = preg_replace("/," . PHP_EOL . "$/", PHP_EOL, $content);
				$content .= ");";
			}

			$fp = fopen($this->path, 'wb');
			if (!$fp) {
				Snappy::error('Config not accessible: %1$s', $this->path);
			} else {
				fwrite($fp, $content);
				fclose($fp);

				return true;
			}
		}

		return false;
	}

	/**
	 * @return null|string
	 */
	function getPath () {
		return $this->path;
	}

	/**
	 * @return bool
	 */
	function exists () {
		return $this->exists;
	}

	/**
	 * @return bool
	 */
	function isReadable () {
		return $this->readable;
	}

	/**
	 * @return bool
	 */
	function isEmpty () {
		return $this->empty;
	}
}