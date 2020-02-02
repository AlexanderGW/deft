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

use Snappy\Lib\Helper;

$array = array(
	'time' => sprintf('%4.4f', Helper::getMoment()),
	'memory' => Helper::getShnoFromBytes( memory_get_usage() ),
	'element' => array(),
	'route' => array(),
	'instance' => array(),
	'plugin' => array(),
	'capture' => array(),
	'event' => array(),
	'filter' => array()
);

foreach( \Snappy::getLog() as $stack => $events ) {
	$scope = explode( '/', $stack );

	if( $scope[0] == 'instance') {
		if( !array_key_exists( $scope[1], $array['instance'] ) )
			$array['instance'][ $scope[1] ] = array();

		if( !array_key_exists( $scope[2], $array['instance'][ $scope[1] ] ) )
			$array['instance'][ $scope[1] ][ $scope[2] ] = $events[0];

		if( count( $scope ) == 4 ) {
			$array['instance'][ $scope[1] ][ $scope[2] ][ $scope[3] ] = $events;
		}
	}
	else {
		$array[$scope[0]][ $scope[1] ] = $events[0];
	}
}

echo json_encode($array);