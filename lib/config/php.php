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

namespace Deft\Lib\Config;

use Deft\Lib\Config;

class Php extends Config {

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	function __construct ($args = null, $class = __CLASS__) {
		$this->path = DEFT_PATH . DS . str_replace('.', DS, $args['scope']) . '.' . $args['format'];

//		var_dump($args['path']);

		if (file_exists($this->path)) {
			$this->exists = TRUE;
			if (is_readable($this->path)) {
				$this->readable = TRUE;

				try {
					$array = include $this->path;
				}
				catch (\Exception $e) {
					$this->exception = $e;
				}

				if (isset($array) and is_array($array)) {
					$this->fields = $array;
					$this->empty  = FALSE;
				}
			}
		}

//		$this->args = $args;

		parent::__construct($args, $class);
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
				           . " * File: " . str_replace(DEFT_PATH . DS, '', $this->path) . PHP_EOL
				           . " * Date: " . gmdate('Y-m-d H:i:s') . PHP_EOL
				           . " * Auto-generated configuration by the Deft Framework" . PHP_EOL
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
		$this->filesystem = \Deft::filesystem($args);
		if ($this->filesystem) {
//			$this->filesystem->touch($this->path);

			$content = $this->output();

			// Write the contents to the filesystem
			$result = $this->filesystem->write($this->path, $content);
			if ($result)
				return TRUE;

			\Deft::log()->add(__('Failed to write "%1$s" over filesystem "%2$s"', $this->path, $args['scope']), 2, 'config.php');
		} else {
			\Deft::log()->add(__('Failed to instantiate filesystem "%1$s"', $args['scope']), 1, 'config.php');
		}

		return FALSE;
	}
}