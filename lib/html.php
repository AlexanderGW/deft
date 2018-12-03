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
	public static function element( $value = null, $filter = null ) {

		// Not an array, return it...
		if( !is_array( $value ) )
			return $value;

		// Add props key to avoid throwing notices
		if( !array_key_exists( 'props', $value ) or !is_array( $value['props'] ) )
			$value['props'] = array();

		// Filter structure
		if( is_string( $filter ) )
			$value = Filter::exec( $filter, $value );

		// Not an element, may be an array of elements...
		if( !array_key_exists( 'tag', $value ) and !array_key_exists( 'markup', $value ) ) {
			$markup = array();
			foreach( $value as $key => $val ) {
				$element = self::element( $val );
				if( !is_null( $element ) )
					$markup[] = $element;
			}

			if( !count( $markup ) )
				return;

			return implode( null, $markup );
		}

		// A nested array of elements in markup...
		elseif( is_array( $value['markup'] ) ) {
			$markup = array();
			foreach( $value['markup'] as $key => $val ) {
				$element = self::element( $val );
				if( !is_null( $element ) )
					$markup[] = $element;
			}

			if( !count( $markup ) )
				return;

			$value['markup'] = implode( null, $markup );

			if( !array_key_exists( 'tag', $value ) )
				return $value['markup'];
		}

		// Value glue for props with an array for a value
		if( !array_key_exists( 'prop_glue', $value ) )
			$value['prop_glue'] = ' ';

		// Return closing element markup?
		if( !array_key_exists( 'close', $value ) )
			$value['close'] = true;
		else
			$value['close'] = (bool)$value['close'];

		// Process array of props
		$props = '';
		if( array_key_exists( 'props', $value ) and is_array( $value['props'] ) ) {
			ksort( $value['props'] );
			foreach( $value['props'] as $key => $val ) {
				if( !is_null( $val ) )
					$props .= ' ' . $key . (
						!is_bool( $val )
							? '="' . trim( is_array( $val )
								? implode( $value['prop_glue'], $val )
								: $val ) . '"'
							: ''
						);
			}
		}

		// Reference string of props
		elseif( is_string( $value['props'] ) )
			$props =& $value['props'];

		$element = '';
		if (SNAPPY_DEBUG)
			$element .= "<!-- OpenTag: {$value['tag']} -> @{$filter} -->\r\n";

		// Open markup
		if( empty( $value['close'] ) )
			$element .= '<' . $value['tag'] . $props . '>' . "\r\n";

		// Close markup
		else
			$element .= '<' . $value['tag'] . $props . '>' . $value['markup'] . '</' . $value['tag'] . '>' . "\r\n";

		if (SNAPPY_DEBUG) {
			$element .= "<!-- CloseTag: {$value['tag']} -->";

			if (SNAPPY_DEBUG > 1)
				$value['markup'] = htmlspecialchars($value['markup']);
			else
				$value['markup'] = null;
		}

		Snappy::log('element/' . $filter, array(
			'element' => $value
		));
		
		return $element;
	}
}