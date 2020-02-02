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

use Snappy\Lib\Filter;

Filter::add( 'element.body', function( $array ) {
	if( array_key_exists( 'class', $array['@props'] ) )
		$array['@props']['class'] .= ' http-404';
	else
		$array['@props']['class'] = 'http-404';
	return $array;
} );

?><h1><?php ___( '404 - Page Not Found' ) ?></h1>
<h2><?php ___( 'The resource you requested could not be found. <a href="%1$s">Return home</a>', SNAPPY_URL ) ?></h2>
