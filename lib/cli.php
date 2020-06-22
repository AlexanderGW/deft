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

class Cli extends \Deft_Concrete {

	/**
	 *
	 */
	public static function init() {

		// Echo all new log entries
		\Deft::event()->set('newLogEntry', '\Deft\Lib\Cli::echoLogEntry');

		// Check if the CLI command was routed
		\Deft::event()->set('exit', '\Deft\Lib\Cli::wasRouted');

		// Cache cleanup management
		\Deft::route()->cli(__('Cache management'), 'cache.clear.[task]', [
			'action' => 'clear',
			'task' => '[a-z]+'
		], '\Deft\Lib\Cli::cacheManagement');

		// Cache management
		\Deft::route()->cli(__('Cache management'), 'cache.[action]', [
			'action' => '[a-z]+'
		], '\Deft\Lib\Cli::cacheManagement');

		// CRON management
		\Deft::route()->cli(__('CRON management'), 'cron', null, '\Deft\Lib\Cli::cronManagement');
	}

	public static function logLastEventCall($event = null) {
		$stacks = \Deft::stack('event/' . $event);
		if (count($stacks)) {
			$i = key(array_slice($stacks, -1));
			$stack = array_pop($stacks);
			foreach ( $stack[ 'callbacks' ] as $priority => $entry ) {
				foreach ( $entry as $j => $data ) {
					if ( $data[ 'return' ] ) {
						\Deft::log()->info( __( 'Executed #' . $i . ' [P' . $priority . '/D' . $j . ']: "%1$s"', $data[ 'callback' ] ), 'event' );
					} else {
						\Deft::log()->warning( __( 'Executed #' . $i . ' [P' . $priority . '/D' . $j . ']: "%1$s"', $data[ 'callback' ] ), 'event' );
					}
				}
			}
		}
	}

	/**
	 * @param $entry
	 */
	public static function echoLogEntry($entry) {
		$level = false;
		switch ($entry['level']) {
			case Log::INFORMATION:
				$colour = '0;30;42m';
				$level = __('INFO');
				break;
			case Log::WARNING:
				$colour = '0;30;43m';
				$level = __('WARNING');
				break;
			case Log::ERROR:
				$colour = '1;37;41m';
				$level = __('ERROR');
				break;
			case Log::STATUS:
			default:
				$colour = '0m';
				break;
		}

		echo "\e[{$colour}" .
		     ($level ? "{$level} ({$entry['stack']})\e[0m " : '') .
		     "{$entry['message']}\n";
	}

	/**
	 *
	 */
	public static function wasRouted() {
		if (is_null(\Deft::route()->current('path'))) {
			\Deft::log()->error(__('Unknown command "%1$s"', \Deft::request()->args()[0]));
		}
	}

	/**
	 * Cache management
	 */
	public static function cacheManagement() {
		$actions = [
			'clear'
		];
		$action = \Deft::route()->current()->data['action'];

		if (!in_array($action, $actions))
			\Deft::log()->error(__('Unknown cache management action "%1$s"', $action));

		// Clear one or more caches
		if ($action == 'clear') {
			$tasks = [
				'all',
				'public'
			];
			$task = \Deft::route()->current()->data['task'];

			if (!in_array($task, $tasks))
				return \Deft::log()->error(__('Unknown cache management action "clear" task "%1$s"', $task));

			\Deft::log()->status(__('Started clearing "%1$s" caches', $task));

			$fs = \Deft::filesystem();

			// Delete the public asset directory and its contents
			if ($task === 'public' || $task === 'all') {
				if ($fs->exists(DEFT_PUBLIC_ASSET_PATH)) {
					if (!$fs->delete(DEFT_PUBLIC_ASSET_PATH, true)) {
						\Deft::log()->warning(__('Failed to delete public assets', $task));
					}
				}

				\Deft::event()->exec('cliCacheClearPublic');
				self::logLastEventCall('cliCacheClearPublic');
			}

			if ($task === 'all') {
				\Deft::event()->exec('cliCacheClearAll');
				self::logLastEventCall('cliCacheClearAll');
			}

			\Deft::log()->info(__('Successfully cleared "%1$s" caches', $task));
		}
	}

	/**
	 * Cache management
	 */
	public static function cronManagement() {
		\Deft::log()->info(__('Successfully ran CRON'));
	}
}

\Deft::event()->set( 'init', '\Deft\Lib\Cli::init' );