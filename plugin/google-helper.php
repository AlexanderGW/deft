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

class Snappy_Google_Helper_Plugin {
	public static function addFonts() {
		if( count( Google::$fonts ) ) {
			$fonts = array();
			foreach( Google::$fonts as $name => $args ) {
				$font = str_replace( ' ', '+', $name );
				if( count( $args['weights'] ) ) {
					sort( $args['weights'] );
					$font .= ':' . implode( ',', $args['weights'] );
				}
				if( count( $args['sets'] ) ) {
					sort( $args['sets'] );
					$font .= '&amp;subset=' . implode( ',', $args['sets'] );
				}
				$fonts[] = $font;
			}

			Document::addStyle( 'https://fonts.googleapis.com/css?family=' . implode( '|', $fonts ), 999 );
		}
	}
}

class Google {
	private static $font_weights = array( '300', '300i', '400', '400i', '500', '500i', '600', '600i', '700', '700i', '800', '800i' );
	private static $font_subsets = array( 'cyrillic', 'cyrillic-ext', 'greek', 'greek-ext', 'latin-ext', 'vietnamese' );
	public static $fonts = array();

	public static function addFont( $name, $weights = '', $sets = '' ) {
		$weights = explode( ',', $weights );
		if( strlen( $weights[0] ) ) {
			foreach( $weights as $i => $weight ) {
				if( !in_array( $weight, self::$font_weights ) )
					unset( $weights[ $i ] );
			}
		} else
			$weights = array();

		$sets = explode( ',', $sets );
		if( strlen( $sets[0] ) ) {
			foreach( $sets as $i => $set ) {
				if( !in_array( $set, self::$font_subsets ) )
					unset( $sets[ $i ] );
			}
		} else
			$sets = array();

		if( !array_key_exists( $name, self::$fonts ) )
			self::$fonts[ $name ] = array(
				'weights' => $weights,
				'sets' => $sets
			);
		else {
			self::$fonts[ $name ] = array(
				'weights' => array_keys( array_flip( array_merge( $weights, self::$fonts[ $name ]['weights'] ) ) ),
				'sets' => array_keys( array_flip( array_merge( $sets, self::$fonts[ $name ]['sets'] ) ) )
			);
		}
	}
}

Event::add( 'beforeDocumentGetHead', array( 'Snappy_Google_Helper_Plugin', 'addFonts' ) );
