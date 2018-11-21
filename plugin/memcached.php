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

if (!defined('IN_SNAPPY')) {
	header('HTTP/1.0 404 Not Found');
	exit;
}

/**
 * Attempt to fetch a cached version of the request
 */
Hook::add('documentInit', function() {
	if (Http::isPostRequest() === false) {
		$document = Snappy::get('cache.memcached')->getLink()->get('snappy.document.' . md5(SNAPPY_ROUTE));
		if ($document) {
			die($document);
		}
	}
});

/**
 * Cache rendered document for future requests
 */
Filter::add('documentContent', function($content) {
	Snappy::get('cache.memcached')->getLink()->set(
		'snappy.document.' . md5(SNAPPY_ROUTE),
		$content,
		Snappy::getCfg('cache.memcached')->get('expire', 30)
	);

	return $content;
});