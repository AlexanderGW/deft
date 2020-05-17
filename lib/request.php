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

class Request extends \Deft_Concrete {
	protected $url = false;
	protected $parsed = false;
	protected $post = false;
	protected $max_size = -1;
	protected $working_size = 0;
	protected $files_in = [];
	protected $files_out = [];

	public $query;

	/**
	 * Config constructor.
	 *
	 * @param null $args
	 */
	function __construct () {
		if (PHP_SAPI  == 'cli' && !defined('DEFT_TESTING')) {
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
				$this->post = \Deft::filter()->exec('requestPostIn', filter_input_array(
					INPUT_POST,
					FILTER_SANITIZE_STRING
				));
			}

			// Process uploaded files
			if( $_FILES and count( $_FILES ) ) {
				$this->max_size = $this->working_size = min(
					Helper::getBytesFromShno( ini_get( 'post_max_size' ) ),
					Helper::getBytesFromShno( ini_get( 'upload_max_filesize' ) ),
					Helper::getBytesFromShno( ini_get( 'memory_limit' ) )
				);

				$this->mimes = \Deft::config()->get('request.files.mimes', []);

				$this->files_in = \Deft::filter()->exec('requestFilesIn', $_FILES);

				foreach( $this->files_in as $group => $data ) {
					if( !array_key_exists( 'error', $data ) )
						continue;
					if( !array_key_exists( $group, $this->files_out ) )
						$this->files_out[ $group ] = array();
					$this->files_out[ $group ][] = $this->_fileGroup( $group, $data );
				}

				$this->files_out = \Deft::filter()->exec('requestFilesOut', $this->files_out);
				\Deft::event()->exec( 'requestHasFiles', $this->files_out );
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
	 * @param null $this->max_size
	 *
	 * @return array|bool
	 */
	private function _fileGroup( $group = null, $items = null ) {
		if( is_array($this->mimes) and
		    array_key_exists( 'name', $items ) and
		    array_key_exists( 'type', $items ) and
		    array_key_exists( 'size', $items ) and
		    array_key_exists( 'tmp_name', $items ) and
		    array_key_exists( 'error', $items )
		) {
			$total = count($items['error']);
			$results = [];
			for ($i = 0; $i < $total; $i++) {
				$validated = false;
				$results[$i] = [
					'error' => null,
					'validated' => false
				];

				// Process PHP upload error codes
				if ($items['error'][$i] !== 0) {
					switch( $items['error'][$i] ) {
						case UPLOAD_ERR_OK:
							break;
						case UPLOAD_ERR_NO_FILE:
							$results[$i]['error'] = __( 'No file sent' );
							break;
						case UPLOAD_ERR_INI_SIZE:
						case UPLOAD_ERR_FORM_SIZE:
							$results[$i]['error'] = __( 'Exceeded upload file size limit of %1$s', $this->max_size );
							break;
						default:
							$results[$i]['error'] = __( 'Unknown error' );
							break;
					}
				}

				// Uploads have exceeded the maximum possible upload size
				if( $this->working_size && $items['size'][$i] > $this->working_size )
					$results[$i]['error'] = __( 'Exceeded remaining upload limit of %1$s', $this->working_size );

				// Add standard $_FILES results
				$results[$i]['name'] = Sanitize::forText( $items['name'][$i] );
				$results[$i]['tmp_name'] = Sanitize::forText( $items['tmp_name'][$i] );
				$results[$i]['type'] = Sanitize::forText( $items['type'][$i] );
				$results[$i]['size'] = (int)$items['size'][$i];

				// No errors found
				if( is_null( $results[$i]['error'] ) ) {

					// Deduct file size from upload limit
					$this->working_size -= $results[$i]['size'];

					// Check that PHP uploaded the file
					if( is_uploaded_file($results[$i]['tmp_name']) === FALSE) {
						\Deft::log()->add( __( 'The request file %1$s[%2$d] failed with "%3$s"', $group, $i, $results[ $i ][ 'tmp_name' ], $results[$i]['error'] ), 'request' );
					} else {
						$results[$i]['clean_tmp_name'] = str_replace('\\', '/', $items['tmp_name'][$i]);

						// Get file MIME information
						$fi = finfo_open( FILEINFO_MIME_TYPE );
						if (!is_resource($fi)) {
							\Deft::log()->add( __( 'Failed to open file information database for file MIMEs' ), 'request' );
							break;
						} else {

							// Store the MIME type
							$results[$i]['clean_type'] = strtolower(finfo_file($fi, $results[$i]['clean_tmp_name']));

							// Sanitised 'tmp' location of the file
							$results[$i]['clean_name'] = Sanitize::forFileName($results[$i]['name']);

							// Get basic filename name and extension parts
							$pos = strrpos($results[$i]['clean_name'], '.');
							$results[$i]['clean_extension'] = substr($results[$i]['clean_name'], ($pos+1));
							$results[$i]['clean_name'] = substr($results[$i]['clean_name'], 0, $pos);

							// Lookup file extension, if possible
							if (version_compare(PHP_VERSION, '7.2.0') >= 0) {
								$fi = finfo_open( FILEINFO_EXTENSION );
								if (!is_resource($fi)) {
									\Deft::log()->add( __( 'Failed to open file information database for file extensions' ), 'request' );
									break;
								} else {
									$extension_list = finfo_file($fi, $results[$i]['clean_tmp_name']);

									// Extensions found for MIME type
									if ($extension_list != '???') {
										$array = explode('/', $extension_list);

										// Current file extension not supported for this MIME time, use the first on the list
										if (!in_array($results[$i]['clean_extension'], $array))
											$results[$i]['clean_extension'] = array_shift($array);
									}
								}
							}

							// Set cleaned file name with best the extension for this MIME type
							$results[$i]['clean_name'] = DEFT_TMP_PATH . '/' . $results[$i]['clean_name'] . '.' . $results[$i]['clean_extension'];

							// Filter the entry before handling
							$results[$i] = \Deft::filter()->exec('requestFileOutGroupEntry', $results[$i]);

							// Only continue with allowed MIME types
							$matches = [];
							if (

								// No exact match
								in_array($results[$i]['clean_type'], $this->mimes) === false

								// Prefix and suffix search
								&& count($matches = array_filter(array_map(

									/* Function: Test allowed MIME entry (prefix or suffix only) matches, request file MIME */
									function ($mime) use ($results, $i) {

										// Suffix match
										if (
											strpos($mime, '*') === 0
											&& preg_match('#' . substr($mime, 1) . '$#', $results[$i]['clean_type'], $matches)
										)
											return $mime;

										// Prefix match
										elseif (
											strpos($mime, '*') === (strlen($mime)-1)
											&& preg_match('#^' . substr($mime, 0, -1) . '#', $results[$i]['clean_type'], $matches)
										)
											return $mime;
										return null;
									}
								, $this->mimes))) === 0
							) {
								\Deft::log()->add( __( 'The request file %1$s[%2$d] MIME format "%3$s" is not allowed.', $group, $i, $results[ $i ][ 'clean_type' ] ), 'request' );
							} else {

								// Store the MIME patterns, that this file matched
								$results[$i]['mime_match'] = count($matches) ? $matches : [$results[$i]['clean_type']];

								// Sanitise images by passing them through the GD library.
								if (substr($results[$i]['clean_type'], 0, 5) === 'image') {
									$fh = fopen($results[$i]['clean_tmp_name'],'rb');
									if ($fh) {

										// Get first six bytes from the file, and handle by one of three core types
										$peek6 = fread($fh,6);
										fclose($fh);

										if ($peek6 !== FALSE) {
											$img = $result_img = false;

											// JPEG
											if (substr($peek6,0,3) == "\xff\xd8\xff") {
												$img = imagecreatefromjpeg($results[$i]['clean_tmp_name']);
												if ($img)
													$result_img = imagejpeg($img, $results[$i]['clean_name']);
											}

											// PNG
											elseif ($peek6 == "\x89PNG\x0d\x0a") {
												$img = imagecreatefrompng($results[$i]['clean_tmp_name']);
												if ($img)
													$result_img = imagepng($img, $results[$i]['clean_name']);
											}

											// GIF
											elseif ($peek6 == 'GIF87a' || $peek6 == 'GIF89a') {
												$img = imagecreatefromgif($results[$i]['clean_tmp_name']);
												if ($img)
													$result_img = imagegif($img, $results[$i]['clean_name']);
											}

											// GD resource event
											$result_event = \Deft::event()->exec('requestFileOutGroupEntryImageResource', $img, $results[$i]);

											// Failed to process image
											if ($img == false && $result_img === false && $result_event === false) {
												\Deft::log()->add(__('Failed to process request file image %1$s[%2$d] of MIME type "%3$s"', $group, $i, $results[$i]['clean_type']), 'request');
											} else {

												// Clean up
												if ($img)
													imagedestroy($img);
												unlink($results[$i]['clean_tmp_name']);

												// Validated
												$validated = true;
											}
										}
									}
								}

								// All other files
								else {

									// Filter the entry before handling
									$results[$i] = \Deft::filter()->exec('requestFileOutGroupEntry', $results[$i]);

									// Failed to move
									if (move_uploaded_file($results[$i]['tmp_name'], $results[$i]['clean_name']) === false) {
										\Deft::log()->add(__('Failed to move request file %1$s[%2$d] to "%3$s"', $group, $i, $results[$i]['clean_name']), 'request');
									}

									// Validated
									else
										$validated = true;
								}
							}

							finfo_close($fi);
						}
					}
				}

				// File was successfully uploaded, and processed, into the Deft 'tmp' directory
				$results[$i]['validated'] = $validated;
			}

			return $results;
		}

		return FALSE;
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