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

define( 'SNAPPY_OPTIMIZE_CACHE_EXPIRE', 300 );

define( 'SNAPPY_OPTIMIZE_CACHE_PATH', dirname( __FILE__ ) . DS . 'cache' . DS );
define( 'SNAPPY_OPTIMIZE_CACHE_URL', SNAPPY_PLUGIN_URL . 'document-optimize/cache/' );

class Snappy_Document_Optimize {
	/**
	 * @return string
	 */
	static function getHash() {
		return md5( SNAPPY_ROUTE );
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	static function combineAllScripts( $args ) {
		$content = '';
		$list = array();

		// Collect contents of all enqueued scripts and remove original files from list
		foreach( $args['_'] as $priority => $hashes ) {
			foreach( $hashes as $hash ) {
				$script =& $args[ $hash ];
				if( $script['type'] == 'text/javascript' and strpos( $script['src'], SNAPPY_CONTENT_URL ) === 0 ) {
					$list[] = $hash;

					$path = str_replace(
						array( SNAPPY_CONTENT_URL, '/' ),
						array( SNAPPY_CONTENT_PATH, DS ),
						$script['src']
					);
					if( file_exists( $path ) ) {
						$content .= '/* ' . $script['src'] . " */\r\n" . file_get_contents( $path ) . "\r\n\r\n";

						// Remove original entries
						unset( $args[ $hash ] );
						$key = array_search( $hash, $args['_'][ $priority ] );
						unset( $hash, $args['_'][ $priority ][ $key ] );
					}
				}
			}
		}
		sort( $list );

		// Create combined script, cache, and add to script list.
		if( strlen( $content ) ) {
			$hash = md5( serialize( $list ) );
			$path = SNAPPY_OPTIMIZE_CACHE_PATH . $hash . '.js';
			file_put_contents( $path, $content );

			$args['_'][0][] = $hash;
			$args[ $hash ] = array(
				'type' => 'text/javascript',
				'src' => str_replace( SNAPPY_OPTIMIZE_CACHE_PATH, SNAPPY_OPTIMIZE_CACHE_URL, $path )
			);
		}
		return $args;
	}

	/**
	 * @param $args
	 *
	 * @return array
	 */
	static function combineAllCss( $args ) {
		$contents = array();
		$lists = array();

		// Collect contents of all enqueued styles and remove original files from list
		foreach( $args['_'] as $priority => $hashes ) {
			foreach( $hashes as $hash ) {
				$style =& $args[ $hash ];
				if( $style['rel'] == 'stylesheet' and strpos( $style['href'], SNAPPY_CONTENT_URL ) === 0 ) {
					if( !array_key_exists( $style['media'], $lists ) ) {
						$lists[ $style['media'] ] = array();
						$contents[ $style['media'] ] = '';
					}
					$lists[ $style['media'] ][] = $hash;

					$path = str_replace(
						array( SNAPPY_CONTENT_URL, '/' ),
						array( SNAPPY_CONTENT_PATH, DS ),
						$style['href']
					);
					if( file_exists( $path ) ) {
						if( !array_key_exists( $style['media'], $contents ) )
							$contents[ $style['media'] ] = '';
						$contents[ $style['media'] ] .= '/* ' . $style['href'] . " */\r\n" . file_get_contents( $path ) . "\r\n\r\n";

						// Remove original entries
						unset( $args[ $hash ] );
						$key = array_search( $hash, $args['_'][ $priority ] );
						unset( $hash, $args['_'][ $priority ][ $key ] );
					}
				}
			}
		}
		foreach( $lists as $list => $hashes )
			sort( $lists[ $list ] );

		// Create combined style, cache, and add to style list.
		if( count( $contents ) ) {
			foreach( $contents as $media => $content ) {
				$hash = md5( serialize( $lists[ $media ] ) );
				$path = SNAPPY_OPTIMIZE_CACHE_PATH . $hash . '.css';
				file_put_contents( $path, $content );

				$args['_'][0][] = $hash;
				$args[ $hash ] = array(
					'rel' => 'stylesheet',
					'media' => $media,
					'href' => str_replace( SNAPPY_OPTIMIZE_CACHE_PATH, SNAPPY_OPTIMIZE_CACHE_URL, $path )
				);
			}
		}
		return $args;
	}

	/**
	 *
	 */
	static function returnCachedDocument() {
		$hash = self::getHash();
		$path = SNAPPY_OPTIMIZE_CACHE_PATH . $hash . '.html';
		if( file_exists( $path ) ) {
			if( !count( $_POST ) and ( filemtime( $path ) + SNAPPY_OPTIMIZE_CACHE_EXPIRE ) > time() ) {
				include $path;
				exit;
			} else {
				unlink( $path );
			}
		}
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	static function cacheDocument( $content ) {
		$content = str_replace( array( "\r\n" ), array( '' ), $content );

		$hash = self::getHash();
		$path = SNAPPY_OPTIMIZE_CACHE_PATH . $hash . '.html';
		file_put_contents( $path, $content );
		return $content;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	static function clearCacheItem( $item = null ) {
		if( !is_string( $item ) )
			return;
		$path = SNAPPY_OPTIMIZE_CACHE_PATH . $item;
		if( file_exists( $path ) ) {
			unlink( $path );
			return true;
		}
		return false;
	}
}

Filter::add( 'documentScripts', array( 'Snappy_Document_Optimize', 'combineAllScripts' ), 999 );
Filter::add( 'documentLinks', array( 'Snappy_Document_Optimize', 'combineAllCss' ), 999 );
Filter::add( 'documentContent', array( 'Snappy_Document_Optimize', 'cacheDocument' ), 999 );
Hook::add( 'init', array( 'Snappy_Document_Optimize', 'returnCachedDocument' ), 999 );