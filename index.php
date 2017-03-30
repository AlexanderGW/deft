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

// Enable debugging
define( 'SNAPPY_DEBUG', 1 );

// Get the framework
require 'snappy.php';

// Is this a first run? Add the "example" plugin to the master config and reload page.
$cfg =& Snappy::getCfg();
if( $cfg->isEmpty() ) {
	$cfg->set( 'plugins', array( 'example' ) );
	$cfg->save();
	Http::location();
}

if( Document::isEmpty() )
	Document::setBody( '<h2>No content</h2>' );
echo Document::content();