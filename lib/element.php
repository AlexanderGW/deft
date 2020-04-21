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

class Element {

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private static $patterns = [
		'prop.key' => '/([^0-9A-Za-z:_\-\xB7\x{0300}-\x{036F}\x{203F}-\x{2040}]+)/u',
		'prop.value' => null
	];

	/**
	 * @name static function
	 */
	static function init () {
		if (self::$initialized) {
			return;
		}

		\Deft::event()->exec('htmlInit');
		self::$initialized = true;
	}

	/**
	 * @param bool|true $bool
	 */
	static function setPattern ($name = true, $value = null) {
		self::$patterns[$name] = $value;
	}

	/**
	 * @return mixed
	 */
	static function getPattern ($name = null) {
		if( is_null($name)) {
			return self::$patterns;
		}
		return self::$patterns[$name];
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
	public static function html( $value = null, $filter = null ) {

		// Not an array, return it...
		if( !is_array( $value ) )
			return $value;

		// Add props key to avoid throwing notices
		if( !array_key_exists( '@props', $value ) or !is_array( $value['@props'] ) )
			$value['@props'] = array();

		$start = Helper::getMicroTime();

		// Filter structure
		if( is_string( $filter ) )
			$value = \Deft::filter()->exec( $filter, $value );

		// Not an element, may be an array of elements...
		if( !array_key_exists( '@tag', $value ) and !array_key_exists( '@markup', $value ) ) {
			$markup = array();
			foreach( $value as $key => $val ) {
				$element = self::html( $val );
				if( !is_null( $element ) )
					$markup[] = $element;
			}

			if( !count( $markup ) )
				return;

			return implode( null, $markup );
		}

		// A nested array of elements in markup...
		elseif( is_array( $value['@markup'] ) ) {
			$markup = array();
			foreach( $value['@markup'] as $key => $val ) {
				$element = self::html( $val );
				if( !is_null( $element ) )
					$markup[] = $element;
			}

			if( !count( $markup ) )
				return;

			$value['@markup'] = implode( null, $markup );

			if( !array_key_exists( '@tag', $value ) )
				return $value['@markup'];
		}

		// Value glue for props with an array for a value
		if( !array_key_exists( '@prop_glue', $value ) )
			$value['@prop_glue'] = ' ';

		// Return closing element markup?
		if( !array_key_exists( '@close', $value ) )
			$value['@close'] = true;
		else
			$value['@close'] = (bool)$value['@close'];

		// Process array of props
		$props = '';
		if( array_key_exists( '@props', $value ) and is_array( $value['@props'] ) ) {
			ksort( $value['@props'] );
			foreach( $value['@props'] as $key => $val ) {
				$pattern = self::getPattern('prop.key');
				$key = ($pattern ? preg_replace($pattern, '', $key) : $key);

				if( !is_null( $val ) ) {
					if (!is_bool($val)) {
						$val = trim( is_array( $val )
							? implode( $value['@prop_glue'], $val )
							: $val );

						$pattern = self::getPattern('prop.value');
						$val = ($pattern ? preg_replace($pattern, '', $val) : $val);
					}

					$props .= ' ' . $key . (
						!is_bool( $val )
							? '="' . $val . '"'
							: ''
						);
				}
			}
		}

		// Reference string of props
		elseif( is_string( $value['@props'] ) )
			$props =& $value['@props'];

		$element = '';
		if (DEFT_DEBUG) {
			$element .= "<!-- Element({$value['@tag']})";
			if ($filter) {
				$element .= " >>> Filter({$filter})";
			}
			$element .= " -->\r\n";
		}


		// Open markup
		if( empty( $value['@close'] ) )
			$element .= '<' . $value['@tag'] . $props . '>' . "\r\n";

		// Close markup
		else
			$element .= '<' . $value['@tag'] . $props . '>' . $value['@markup'] . '</' . $value['@tag'] . '>' . "\r\n";

		if (DEFT_DEBUG) {
			$element .= "<!-- /Element({$value['@tag']}) -->";

			if (DEFT_DEBUG > 1)
				$value['@markup'] = htmlspecialchars($value['@markup']);
			else
				$value['@markup'] = null;
		}

		\Deft::log('element/' . $filter, array(
			'time'     => Helper::getMoment( $start ),
			'element' => $value
		));
		
		return $element;
	}
}

\Deft::event()->set('init', '\Deft\Lib\Element::init');