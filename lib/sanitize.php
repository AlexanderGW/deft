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

class Sanitize {

	/**
	 * @param $string
	 *
	 * @return string
	 */
	public static function forText( $string ) {
		return preg_replace( '/[\x00-\x1F\x80-\x9F]/u', '', $string );
	}

	/**
	 * @param $string
	 *
	 * @return string
	 */
	public static function forTextBlock( $string ) {
		return preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $string );
	}

	/**
	 * @param $string
	 *
	 * @return string
	 */
	public static function forHtml( $string ) {
		return htmlentities( $string, ENT_QUOTES, 'UTF-8' );
	}
}