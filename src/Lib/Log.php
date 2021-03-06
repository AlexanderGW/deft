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

class Log extends \Deft_Concrete {
	const INFORMATION = 0;
	const WARNING = 1;
	const ERROR = 2;
	const STATUS = 3;

	private $entries = [
		self::INFORMATION => [],
		self::WARNING => [],
		self::ERROR => [],
		self::STATUS => []
	];

	/**
	 * Log constructor.
	 *
	 * @param array $args
	 */
	function __construct ($args = array(), $class = __CLASS__) {
		$this->args = self::getArgs($args);
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function getArgs ($args = array()) {
		return array_merge(array(
			'base'      => null,
			'encoding'  => 'utf-8',
			'locale'    => 'en',
			'direction' => 'ltr'
		), $args);
	}

	/**
	 * @param null $message
	 * @param int $code
	 * @param null $stack
	 */
	function add ($message = null, $stack = null, $level = self::STATUS) {

		// Too many watchdog errors
		$limit = \Deft::config()->get('log.error.max', 30);
		if ($limit && count($this->entries[self::ERROR]) >= $limit)
			\Deft::error('Error limit reached (%1%d)', $limit);

		if (!is_string($message))
			return NULL;

		if (!is_string($stack))
			$stack = 'app';

		if (!array_key_exists($level, $this->entries))
			$this->entries[$level] = [];

		// Unknown log level, default to a warning
		if (!in_array($level, [
			self::ERROR,
			self::STATUS,
			self::WARNING,
			self::INFORMATION
		]))
			$level = self::WARNING;

		if (!array_key_exists($stack, $this->entries[$level]))
			$this->entries[$level][$stack] = [];

		$entry = [
			'stack' => $stack,
			'level' => $level,
			'message' => $message,
		];

		$entry = \Deft::filter()->exec('newLogEntry', $entry);
		if ($entry) {
			$this->entries[$level][$stack][] = $entry;
			\Deft::event()->exec('newLogEntry', $entry);
		}

		return true;
	}

	public function error($message = null, $stack = null) {
		self::add($message, $stack, self::ERROR);
	}

	public function warning($message = null, $stack = null) {
		self::add($message, $stack, self::WARNING);
	}

	public function status($message = null, $stack = null) {
		self::add($message, $stack, self::STATUS);
	}

	public function info($message = null, $stack = null) {
		self::add($message, $stack, self::INFORMATION);
	}

	public function get($level = null) {
		if (array_key_exists($level, $this->entries)) {
			return $this->entries[$level];
		}

		return false;
	}
}