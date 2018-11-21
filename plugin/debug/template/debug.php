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

function arrayToTable( $data ) {
	$return = '<table>';
	if( is_array( $data ) ) {
		foreach( $data as $key => $val ) {
			$return .= '<tr><td>' . $key . '</td><td>' .
			           ( is_array( $val ) ? arrayToTable( $val ) :
				            ( is_object( $val ) ? '<i>Object</i>' :
					            ( !strlen( $val ) ? '<i>NULL</i>' :
						            ( strpos( $key, 'pass' ) !== false ? '<i>omitted</i>' :
							            $val ) ) ) ) .
			           '</td></tr>';
		}
	}
	$return .= '</table>';
	return $return;
}

$i = 0; $window = Helper::getMoment(); ?>
<main>
	<div class="debug">
		<h3><?php ___( 'Snappy debug' ) ?></h3>
		<?php

		___( 'Run time: %1$4.4f seconds. Memory usage: %2$s', $window, Helper::getShnoFromBytes( memory_get_usage() ) );

		$routes = $instances = $plugins = $captures = $hooks = $filters = $others = array();
		foreach( Snappy::getLog() as $stack => $events ) {
			$scope = explode( '/', $stack );

			if( strpos( $stack, 'route/' ) === 0 ) {
				if( !array_key_exists( $scope[1], $routes ) )
					$routes[ $scope[1] ] = $events[0];
			}

			elseif( strpos( $stack, 'instance/' ) === 0 ) {
				if( !array_key_exists( $scope[1], $instances ) )
					$instances[ $scope[1] ] = array();

				if( !array_key_exists( $scope[2], $instances[ $scope[1] ] ) )
					$instances[ $scope[1] ][ $scope[2] ] = $events[0];

				if( count( $scope ) == 4 ) {
					$instances[ $scope[1] ][ $scope[2] ][ $scope[3] ] = $events;
				}
			}

			elseif( strpos( $stack, 'plugin/' ) === 0 ) {
				if( !array_key_exists( $scope[1], $plugins ) )
					$plugins[ $scope[1] ] = $events[0];
			}

			elseif( strpos( $stack, 'hook/' ) === 0 ) {
				if( !array_key_exists( $scope[1], $hooks ) )
					$hooks[ $scope[1] ] = $events[0];
			}

			elseif( strpos( $stack, 'filter/' ) === 0 ) {
				if( !array_key_exists( $scope[1], $filters ) )
					$filters[ $scope[1] ] = $events[0];
			}

			elseif( strpos( $stack, 'capture/' ) === 0 ) {
				if( !array_key_exists( $scope[1], $captures ) )
					$captures[ $scope[1] ] = $events[0];
			}

			else {
				if( !array_key_exists( $scope[0], $others ) )
					$others[ $scope[0] ] = $events[0];
			}
		}

		foreach( $plugins as $plugin => $data ) {
			echo '<h3>' . __( 'Plugin: %1$s', $plugin ) . '</h3>';
			echo arrayToTable( $data );
		}

		foreach( $routes as $route => $data ) {
			echo '<h3>' . __( 'Route: %1$s', $route ) . '</h3>';
			echo arrayToTable( $data );
		}

		foreach( $hooks as $hook => $data ) {
			echo '<h3>' . __( 'Hook: %1$s', $hook ) . '</h3>';
			echo arrayToTable( $data );
		}

		foreach( $captures as $capture => $data ) {
			echo '<h3>' . __( 'Capture: %1$s', $capture ) . '</h3>';
			echo arrayToTable( $data );
		}

		foreach( $filters as $filter => $data ) {
			echo '<h3>' . __( 'Filter: %1$s', $filter ) . '</h3>';
			echo arrayToTable( $data );
		}

		foreach( $instances as $class => $instance ) {
			echo '<h3>' . __( 'Instances of: %1$s (%2$d total)', $class, count( $instance ) ) . '</h3>';
			foreach( $instance as $key => $data ) {
				echo '<div><h4>' . __( 'Instance key: %1$s - %2$s reference call(s)', $key, count( $data['calls'] ) ) . '</h4>' . arrayToTable( $data ) . '</div>';
			}
		}

		foreach( $others as $other => $data ) {
			echo '<h3>' . $other . '</h3>';
			echo arrayToTable( $data );
		}

		?>
	</div>
</main>
<style>
	.debug {
		background-color: #aacdda;
		border: 2px solid #333;
		padding: 20px;
		margin-top: 20px;
	}

	.debug h3, .debug h4 {
		padding: 0;
		margin: 0;
		background-color: transparent;
		color: #000;
	}

	.debug h3 {
		margin: 20px 0 10px;
		padding: 0 0 10px 0;
		border-bottom: 2px solid #333;
	}

	.debug h3:first-of-type {
		margin-top: 0;
	}

	.debug h4 {
		margin: 10px 0;
	}

	.debug table {
		box-sizing: border-box;
		width: 100%;
		border-collapse: separate;
		border-spacing: 2px;
		background-color: #333;
	}

	.debug td {
		background-color: #ddd;
		padding: .2em;
		vertical-align: top;
		font-family: monospace;
	}

	.debug td:nth-child(odd) {
		text-align: right;
		font-weight: 700;
	}

	.debug td:nth-child(even) {
		background-color: #eee;
		text-align: left;
	}
</style>