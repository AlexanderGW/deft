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

class Example_Plugin {
	public static function init() {
		Route::add( '/', null, array( 'Example_Plugin', 'initContent' ) );
		Route::add(
			'/[page]',              // Route path relative to Snappy framework, with regex pattern placeholder [page]
			array(
				'page' => '[a-z]+', // Pattern for [page]. Subsequent value stored in GET global. Http::get('page');
				'foo' => 'bar'      // Additional GET environment var to set if route matched.
			),
			array( 'Example_Plugin', 'initContent' )    // Route callback if matched
		);
	}

	public static function initContent() {

		// Set some document properties
		Document::addStyle( SNAPPY_ASSET_URL . 'style.css' );
		Document::setVpWidth( 0 );
		Document::addStyle( 'https://fonts.googleapis.com/css?family=Raleway:400,700' );
		Document::setTitleSeparator( ' &bull; ' );
		Document::setTitle( 'Snappy, a PHP 5.3+ framework for developers and small web apps.' );
		Document::setDescription( 'PHP 5.3+ framework for creating custom web apps with some essential Helpers for security and more.' );

		// Get requested page, routing rule created in 'init'
		$page = Http::get( 'page' );
		if( empty( $page ) ) {
			$page = 'index';
		} elseif( $page == 'index' )
			Http::location();

		// Capture the controller
		$content = Snappy::capture( 'plugin.example.page.' . $page );
		if( is_string( $content ) )
			Document::appendBody( $content );
		else
			Http::status( 404 );

		// Add header footer to document
		Document::prependBody( '<header><main><div><a href="' . SNAPPY_URL . '" class="logo">AGW</a></div></main></header><main>' );
		Document::appendBody( '</main>' );
	}
}

// Add our routing rule...
Hook::add( 'init', array( 'Example_Plugin', 'init' ) );

// Example hook for files in the request, they have been processed in Http::init() and are ready to handle
Hook::add( 'httpRequestHasFiles', function ( $files ) {
	var_dump($files); exit;
} );

function temp1($content) {
	return '<!---->' . $content;
}

function temp2($content) {
	return '<!--000-->' . $content;
}

Filter::add( 'documentGetHead', function ($content) {
	return '<!--000-->' . $content;
} );