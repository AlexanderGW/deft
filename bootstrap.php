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

// Get the framework
require 'snappy.php';

if( !defined( 'SNAPPY_LIB_DIR' ) )
	define( 'SNAPPY_LIB_DIR', 'lib' );

if( !defined( 'SNAPPY_PLUGIN_DIR' ) )
	define( 'SNAPPY_PLUGIN_DIR', 'plugin' );

if( !defined( 'SNAPPY_PUBLIC_DIR' ) )
	define( 'SNAPPY_PUBLIC_DIR', 'public' );

if( !defined( 'SNAPPY_PUBLIC_ASSET_DIR' ) )
	define( 'SNAPPY_PUBLIC_ASSET_DIR', 'asset' );

// Initialise
Snappy::init();

// Append debugging
//if( SNAPPY_DEBUG > 0 ) {
	Filter::add( 'documentBody', function( $content ) {
		$content .= Snappy::capture( 'template.debug' );
		return $content;
	} );
//}