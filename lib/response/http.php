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

namespace Snappy\Lib\Response;

use Snappy\Lib\Response;

class Http extends Response {

	/**
	 * @param null $uri
	 */
	public function header( $key = null, $value ) {
		if( is_string($key)) {
			if (is_null($value)) {
				unset($this->headers[$key]);
			} else {
				$this->headers[$key] = $value;
			}
			return true;
		}
		return false;
	}

	/**
	 * @param null $uri
	 */
	public function location( $path = null ) {
		if( !is_string( $path ) )
			$uri = null;
		$this->header('Location', SNAPPY_URL . $path);

		// Empty the response, headers only.
//		\Snappy::response()->setBody();

		die( \Snappy::response()->output() );

//		die( header( sprintf( 'Location: %s', $uri ), true ) );
	}

	/**
	 * @param null $code
	 *
	 * @return int
	 */
	public function status( $code = null ) {
		if( !is_null( $code ) ) {
			$codes = array(

				// Informational
				200 => 'OK',
				201 => 'Created',
				204 => 'No Content',

				// State
				300 => 'Multiple Choice',
				301 => 'Moved Permanently',
				304 => 'Not Modified',
				307 => 'Temporary Redirect',
				308 => 'Permanent Redirect',

				// Client Error
				400 => 'Bad Request',
				401 => 'Unauthorized',
				403 => 'Forbidden',
				404 => 'Not Found',
				405 => 'Method Not Allowed',
				406 => 'Not Acceptable',
				418 => 'I\'m a teapot',
				429 => 'Too Many Requests',

				// Server Error
				500 => 'Internal Server Error',
				501 => 'Not Implemented',
				502 => 'Bad Gateway',
				503 => 'Service Unavailable'
			);

			if( array_key_exists( $code, $codes ) ) {
				header( sprintf( 'HTTP/1.1 %d %s', $code, $codes[ $code ] ), true );

				// If empty response, show an error template
				if( $this->isEmpty() ) {
					$content = \Snappy::capture( 'template.' . $code );
					if( is_string( $content ) ) {
						$this->setBody( $content );
						return true;
					}
				}

				return false;
			}
		}
		return;
	}

	public function cookie($key = null, $value = null) {
		if (is_string($key)) {
			$this->cookies[$key] = $value;
		}
		return false;
	}

	/**
	 * @param null $scope
	 *
	 * @return mixed|string|void
	 */
	public function output($scope = null) {
		\Snappy::response()->header('X-Generator', 'Snappy/' . \Snappy::VERSION);

		$this->headers = \Snappy::filter()->exec('responseOutputHeaders', $this->headers);
		foreach($this->headers as $key => $value) {
			header($key . ': ' . $value, true);
		}
		return parent::output();
	}
}