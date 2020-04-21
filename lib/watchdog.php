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

class Watchdog {
	const INFORMATION = 0;
	const WARNING = 1;
	const ERROR = 2;

	private static $errors = 0;

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private static $args = array(
		'base'      => null,
		'encoding'  => 'utf-8',
		'locale'    => 'en',
		'direction' => 'ltr'
	);

	/**
	 * @name static function
	 */
	static function init () {
		if (self::$initialized) {
			return;
		}

		\Deft::event()->exec('documentInit');
		self::$initialized = true;
	}

	/**
	 * @param null $message
	 * @param int $code
	 * @param null $stack
	 */
	static function set ($message = null, $code = 0, $stack = null, $level = self::ERROR) {
		if($level === self::ERROR) {
			self::$errors++;
		}

		// Too many watchdog errors
		$limit = \Deft::config()->get('max_errors', 30);
		if ($limit && self::$errors >= $limit) {
			\Deft::error('Document error limit reached (%1%d)', $limit);
		}

		if (!is_string($message)) {
			return;
		}

		if (!is_string($stack)) {
			$stack = 'app';
		}

//		if (!array_key_exists($stack, self::$errors)) {
//			self::$errors[$stack] = array();
//		}
//		self::$errors[$stack][] = array(
//			'code'    => $code,
//			'message' => $message
//		);

		$phrase = 'Watchdog';
		if ($level === self::ERROR) {
			$phrase = 'Error';
		} elseif ($level === self::WARNING) {
			$phrase = 'Warning';
		} elseif ($level === 0) {
			$phrase = 'Info';
		}
		$phrase .= ': %1$s (%2$d)';

		\Deft::response()->prependBody(sprintf(
			Element::html(array(
				'@tag' => 'div',
				'@props' => array(
					'tabindex' => 0,
					'class' => array(
						'deft',
						'watchdog',
						($level === self::ERROR ? 'err' : (($level === self::WARNING ? 'warn' : 'info')))
					),
					'role' => 'alert'
				),
				'@markup' => array(
					array(
						'@tag' => 'div',
						'@markup' => array(
							array(
								'@tag' => 'strong',
								'@markup' => '%1$s'
							)
						)
					),
					array(
						'@tag' => 'div',
						'@markup' => array(
							array(
								'@tag' => 'span',
								'@markup' => '%2$s'
							)
						)
					)
				)
			), 'response.error.template'),
			__($phrase, $stack, $code),
			$message
		));
	}
}