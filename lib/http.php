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

class Http {

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
			$scheme = \Deft::request()->scheme();
		if( empty( $host ) )
			$host = \Deft::request()->host();
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
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER => [
				'User-Agent: Deft/' . \Deft::VERSION
			]
		);

		curl_setopt_array( $ch, $options );

		// Get response
		if( ( $response = curl_exec( $ch ) ) === false )
			Watchdog::set( curl_error( $ch ), curl_errno( $ch ), 'curl' );

		$return = curl_getinfo( $ch );
		$return['response'] = $response;

		curl_close( $ch );
		return $return;
	}
}