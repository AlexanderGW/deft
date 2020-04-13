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

namespace Snappy\Lib;

class Request extends \Snappy_Concrete {
	protected $url = false;
	protected $parsed = false;
	protected $post = false;
	protected $max_size = -1;
	protected $files_in = [];
	protected $files_out = [];

	public $query;

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	function __construct () {
		if (PHP_SAPI  == 'cli' && !defined('SNAPPY_TESTING')) {
//			$request = $_SERVER['argv'];
			die('CLI not yet implemented');
		} else {
			$request = $_SERVER['REQUEST_URI'];

			$pos = strpos($request, '?');
			if ($pos !== false) {
				$path = urldecode(substr($request, 0, $pos));
				$this->query = http_build_query(
					filter_input_array(
						INPUT_GET,
						FILTER_SANITIZE_STRING
					)
				);
			} else
				$path = urldecode($request);

			// Strip the port number if present
			$host = strtok($_SERVER['HTTP_HOST'], ':');

			// Build the request URL
			$this->url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https:" : "http:")
			       . "//{$host}"
	               . ($_SERVER['SERVER_PORT'] ? ':' . $_SERVER['SERVER_PORT'] : NULL)
			       . $path
			       . ($this->query ? '?' . $this->query : NULL);

			$this->parsed = parse_url($this->url);

			// Make query an array
			if (array_key_exists('query', $this->parsed)) {
				parse_str($this->parsed['query'], $this->parsed['query']);
			}

			// Store POST variables
			if ($_POST && is_array($_POST)) {
				$this->post = Filter::exec('requestPostIn', filter_input_array(
					INPUT_POST,
					FILTER_SANITIZE_STRING
				));
			}

			// Process uploaded files
			if( $_FILES and count( $_FILES ) ) {
				$this->max_size = min(
					Helper::getBytesFromShno( ini_get( 'post_max_size' ) ),
					Helper::getBytesFromShno( ini_get( 'upload_max_filesize' ) ),
					Helper::getBytesFromShno( ini_get( 'memory_limit' ) )
				);

				$this->files_in = Filter::exec('requestFilesIn', $_FILES);

				foreach( $this->files_in as $group => $data ) {
					if( !array_key_exists( 'error', $data ) )
						continue;
					if( !array_key_exists( $group, $this->files_out ) )
						$this->files_out[ $group ] = array();
					$this->files_out[ $group ][] = self::_file( $data, $this->max_size );
				}

				$this->files_out = Filter::exec('requestFilesOut', $this->files_out);
				Event::exec( 'requestHasFiles', $this->files_out );
			}
		}
	}

	/**
	 * @param null $args
	 *
	 * @return null|string
	 */
//	public static function getArgs ($args = null) {
////		if (!is_string($args)) {
////			$args = 'cache';
////		}
//
//		return $args;
//	}

	/**
	 * @param null $data
	 * @param null $max_size
	 *
	 * @return array
	 */
	private static function _file( $data = null, $max_size = 0 ) {
		if( array_key_exists( 'name', $data ) and
		    array_key_exists( 'type', $data ) and
		    array_key_exists( 'size', $data ) and
		    array_key_exists( 'tmp_name', $data ) and
		    array_key_exists( 'error', $data ) and
		    !is_array( $data['error'] )
		) {
			$result = array(
				'error' => null
			);

			switch( $data['error'] ) {
				case UPLOAD_ERR_OK:
					break;
				case UPLOAD_ERR_NO_FILE:
					$result['error'] = __( 'No file sent' );
					break;
				case UPLOAD_ERR_INI_SIZE:
				case UPLOAD_ERR_FORM_SIZE:
					$result['error'] = __( 'Exceeded upload file size limit of %1$s', $max_size );
					break;
				default:
					$result['error'] = __( 'Unknown error' );
					break;
			}

			if( empty( $result['error'] ) ) {
				if( $max_size and $data['size'] > $max_size )
					$result['error'] = __( 'Exceeded file size limit of %1$s', $max_size );

				$finfo = finfo_open( FILEINFO_MIME_TYPE );
				$result['type'] = finfo_file( $finfo, $data['tmp_name'] );
				$result['name'] = Sanitize::forText( $data['name'] );
				$result['tmp_name'] = Sanitize::forText( $data['tmp_name'] );
			}

			return $result;
		}
		return null;
	}

	/**
	 *
	 */
	public function fileInput() {
		return $this->file_in;
	}

	/**
	 *
	 */
	public function fileOutput() {
		return $this->file_out;
	}

	/**
	 *
	 */
	public function url() {
		return $this->url;
	}

	/**
	 *
	 */
	public function scheme() {
		return $this->parsed['scheme'];
	}

	/**
	 *
	 */
	public function host() {
		return $this->parsed['host'];
	}

	/**
	 *
	 */
	public function port() {
		return $this->parsed['port'];
	}

	/**
	 *
	 */
	public function path() {
		return $this->parsed['path'];
	}

	/**
	 *
	 */
	public function has($key = null) {
		return (array_key_exists($key, $this->parsed));
	}

	/**
	 *
	 */
	public function query($key = null, $default = null) {
		if (
			$this->has('query')
			&& array_key_exists($key, $this->parsed['query'])
		) {
			$value = $this->parsed['query'][$key];
			if( is_null( $value ) )
				return $default;
			if( is_array( $value ) ) {
				$value = array_map( function( $value ) {
					return Sanitize::forText( $value );
				}, $value );
			}
			return Sanitize::forText( $value );
		} else
			return $default;

//		if (is_array($this->parsed['query'])) {
//			if (is_string($key) && array_key_exists($key, $this->parsed['query'])) {
//				return $this->parsed['query'][$key];
//			} elseif (is_null($key)) {
//				return $this->parsed['query'];
//			}
//		}
//
//		return;
	}

	/**
	 *
	 */
//	public function get($key = null) {
//		if (self::has($key))
//			return $this->parsed[$key];
//		return;
//	}

	/**
	 *
	 */
	public function post($key = null) {
		if ($this->isPost() && array_key_exists($key, $this->post))
			return $this->post[$key];
		return;
	}

	/**
	 *
	 */
	public function isPost() {
		return ($this->post ? true : false);
	}
}