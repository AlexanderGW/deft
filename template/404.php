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

function addBodyClass( $args ) {
	if( array_key_exists( 'class', $args ) )
		$args['class'] .= ' http-404';
	else
		$args['class'] = 'http-404';
	return $args;
}
Filter::add( 'elementBody', 'addBodyClass' );

?><h1><?php ___( 'Error 404' ) ?></h1>
<h2><?php ___( 'The resource you requested could not be found. <a href="%1$s">Return home</a>', SNAPPY_URL ) ?></h2>
