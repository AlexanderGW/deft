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

if( !defined( 'IN_SNAPPY' ) ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit;
}

class Html {

	/**
	 * @param $string
	 *
	 * @return string
	 */
	public static function escape( $string ) {
		return htmlentities( $string, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Build a filtered HTML element
	 *
	 * @param null $label
	 * @param null $attributes
	 * @param null $filter
	 * @param bool|false $self_close
	 *
	 * @return string
	 */
	public static function element( $label = null, $attributes = null, $filter = null, $self_close = false ) {
		if( is_null( $label ) or ( !is_array( $attributes ) and !is_string( $attributes ) ) )
			return;

		if( is_string( $attributes ) )
			$attributes = array( 'html' => $attributes );

		if( !array_key_exists( 'html', $attributes ) )
			$attributes['html'] = '';

		if( is_string( $filter ) )
			$attributes = Filter::exec( $filter, $attributes );

		$html_attributes = '';
		if( is_array( $attributes ) ) {
			ksort( $attributes );
			foreach( $attributes as $name => $value ) {
				if( $name == 'html' )
					continue;
				$html_attributes .= ' ' . $name . ( !is_bool( $value ) ? '="' . $value . '"' : '' );
			}
		} elseif( is_string( $attributes ) )
			$html_attributes =& $attributes;

		if( $self_close )
			$html_tag = '<' . $label . $html_attributes . '>';
		else
			$html_tag = '<' . $label . $html_attributes . '>' . $attributes['html'] . '</' . $label . '>';

		return $html_tag;
	}
}