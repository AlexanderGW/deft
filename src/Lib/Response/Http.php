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

namespace Deft\Lib\Response;

use Deft\Lib\Response;

class Http extends Response {
	protected $cookies = [];
	protected $headers = [];

	/**
	 * @param null $uri
	 */
	public function header( $key = NULL, $value = -1 ) {
		if( is_string($key)) {
			if ($value === -1) {
				return $this->headers[$key];
			} elseif (is_null($value)) {
				unset($this->headers[$key]);
				return NULL;
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
		$this->header('Location', DEFT_URL . $path);

		// Set an event to empty the body
		\Deft::event()->set('beforeResponseOutput', '\Deft\Lib\Response\Http::event__responseOutput', 999);

		return TRUE;
	}

	/**
	 * @param null $code
	 *
	 * @return int
	 */
	public function status( $code = null ) {
		if(is_null( $code ))
			return NULL;

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
//			if( $this->isEmpty() ) {
//				$content = \Deft::capture( 'template.' . $code );
//				if( is_string( $content ) ) {
//					$this->buffer( $content );
//				}
//			}

			return TRUE;
		}

		return FALSE;
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
	 * @return string
	 */
	public function output($content = '') {
		parent::output();

		$this->header('X-Generator', 'Deft/' . \Deft::VERSION);

		$this->headers = \Deft::filter()->exec('responseOutputHeaders', $this->headers);
		foreach($this->headers as $key => $value) {
			header($key . ': ' . $value, true);
		}
		return $content;
	}

	/**
	 * Event that is added to 'beforeResponseOutput' after location() to clear the buffer()
	 *
	 * @param null $args
	 */
	public function event__responseOutput($args = null) {
		\Deft::response($args)->buffer(NULL);
	}
}