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

class Http {
	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	/**
	 *
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		// Process uploaded files
		if( $_FILES and count( $_FILES ) ) {
			$results = array();
			$size = min(
				Helper::getBytesFromShno( ini_get( 'post_max_size' ) ),
				Helper::getBytesFromShno( ini_get( 'upload_max_filesize' ) )
			);

			foreach( $_FILES as $group => $data ) {
				if( !array_key_exists( 'error', $data ) )
					continue;
				if( !array_key_exists( $group, $results ) )
					$results[ $group ] = array();
				$results[ $group ][] = self::_file( $data, $size );
			}

			Hook::exec( 'httpRequestHasFiles', $results );
		}

		self::$initialized = true;
	}

	/**
	 * @param null $arg
	 * @param null $default
	 *
	 * @return array|string
	 */
	private static function _get( $arg = null, $default = null ) {
		$value = Helper::getGlobal( $arg, '_GET' );
		if( is_null( $value ) )
			return $default;
		if( is_array( $value ) ) {
			$value = array_map( function( $value ) {
				return Helper::trimAllCtrlChars( $value );
			}, $value );
		} else
			$value = Helper::trimAllCtrlChars( $value );
		return $value;
	}

	/**
	 * @param null $arg
	 * @param null $default
	 *
	 * @return string
	 */
	public static function get( $arg = null, $default = null ) {
		return Html::escape( self::_get( $arg, $default ) );
	}

	/**
	 * @param null $arg
	 * @param null $default
	 *
	 * @return array|string
	 */
	public static function getUnescaped( $arg = null, $default = null ) {
		return self::_get( $arg, $default );
	}

	/**
	 * @param null $arg
	 * @param null $default
	 *
	 * @return array|string
	 */
	private static function _post( $arg = null, $default = null ) {
		$value = Helper::getGlobal( $arg, '_POST' );
		if( is_null( $value ) )
			return $default;
		if( is_array( $value ) ) {
			$value = array_map( function( $value ) {
				return Helper::trimCtrlChars( $value );
			}, $value );
		} else
			$value = Helper::trimCtrlChars( $value );
		return $value;
	}

	/**
	 * @param null $arg
	 * @param null $default
	 *
	 * @return string
	 */
	public static function post( $arg = null, $default = null ) {
		return Html::escape( self::_post( $arg, $default ) );
	}

	/**
	 * @param null $arg
	 * @param null $default
	 *
	 * @return array|string
	 */
	public static function postUnescaped( $arg = null, $default = null ) {
		return self::_post( $arg, $default );
	}

	/**
	 * @param null $data
	 * @param null $max_size
	 *
	 * @return array
	 */
	private static function _file( $data = null, $max_size = null ) {
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
					$result['error'] = __( 'Exceeded file size limit of %1$s', $max_size );
					break;
				default:
					$result['error'] = __( 'Unknown error' );
					break;
			}

			if( empty( $result['error'] ) ) {
				if( $max_size and $data['size'] > $max_size )
					$result['error'] = __( 'Exceeded filesize limit of %1$s', $max_size );

				$finfo = finfo_open( FILEINFO_MIME_TYPE );
				$result['type'] = finfo_file( $finfo, $data['tmp_name'] );
				$result['name'] = Helper::trimAllCtrlChars( $data['name'] );
				$result['tmp_name'] = Helper::trimAllCtrlChars( $data['tmp_name'] );
			}

			return $result;
		}
		return null;
	}

	/**
	 * @param null $uri
	 */
	public static function location( $uri = null ) {
		if( !is_string( $uri ) )
			$uri = './';
		die( header( sprintf( 'Location: %s', $uri ) ) );
	}

	/**
	 * @param null $code
	 *
	 * @return int
	 */
	public static function status( $code = null ) {
		if( !is_null( $code ) ) {
			$codes = array(

				// Informational
				200 => 'OK',

				// Redirection
				301 => 'Moved Permanently',

				// Client Error
				401 => 'Unauthorized',
				403 => 'Forbidden',
				404 => 'Not Found',

				// Server Error
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable'
			);

			if( array_key_exists( $code, $codes ) ) {
				header( sprintf( 'HTTP/1.1 %d %s', $code, $codes[ $code ] ) );

				// If empty response, throw an error template
				if( Document::isEmpty() ) {
					$content = Snappy::capture( 'template.' . $code );
					if( is_string( $content ) ) {
						Document::setBody( $content );
						return true;
					}
				}

				return false;
			}
		}
		return;
	}

	/**
	 * @param null $url
	 * @param array $options
	 *
	 * @return null|array
	 */
	public static function request( $url = null, $options = null, $post = null ) {
		if( !function_exists( 'curl_init' ) )
			return null;

		$parts = parse_url( $url );
		extract( $parts );
		if( !isset( $scheme ) )
			$scheme = 'http';
		if( empty( $host ) )
			$host = $_SERVER['HTTP_HOST'];
		if( !empty( $query ) )
			parse_str( $query, $query );

		$url = $scheme . '://' . $host;
		if( isset( $path ) )
			$url .= $path;
		if( isset( $query ) )
			$url .= '?' . http_build_query( $query );

		$ch = curl_init();

		// Build POST
		if( is_array( $post ) ) {
			curl_setopt( $ch, CURLOPT_POST, 1 );
			curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
		}

		// Apply options
		if( !is_array( $options ) )
			$options = array();

		$options = $options + array(
			CURLOPT_URL => $url,
			CURLOPT_HEADER => false,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_RETURNTRANSFER => true
		);

		curl_setopt_array( $ch, $options );

		// Exec request
		if( ( $response = curl_exec( $ch ) ) === false )
			Document::addErrorMessage( curl_error( $ch ), curl_errno( $ch ), 'cURL' );

		$return = curl_getinfo( $ch );
		$return['content'] = $response;

		curl_close( $ch );
		return $return;
	}
}

Hook::add( 'init', array( 'Http', 'init' ) );