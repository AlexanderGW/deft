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

class Random {

	/**
	 * @return float
	 */
	public static function getSeed() {
		return (Helper::getMicroTime() * 1000000 + (time() / 2));
	}

	/**
	 * @return string
	 */
	public static function getMd5() {
		mt_srand(self::getSeed());
		return md5(uniqid(mt_rand()));
	}

	/**
	 * @param int $length
	 * @param bool|false $extended
	 * @param string $exception
	 *
	 * @return string
	 */
	public static function getChars($length = 0, $extended = false, $exception = '') {
		$map = self::ALPHANUMERIC_CHARS;
		if ($extended)
			$map .= self::EXTENDED_CHARS;
		$map_length = (strlen($map) - 1);

		$string = '';
		for ($i = 0; $i < $length; $i++) {
			mt_srand((int)self::getSeed());
			$offset = mt_rand(0, $map_length);
			$char = substr($map, $offset, 1);
			if(strpos($exception, $char) !== false)
				$i--;
			else
				$string .= $char;
		}
		return $string;
	}

	/**
	 * @return string
	 */
	public static function getSalt() {
		return self::getChars(3, true);
	}
}