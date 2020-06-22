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

/**
 * Attempt to fetch a cached version of the request
 */
Event::set('documentInit', function() {
	if (Deft::request()->isPost() === false) {
		$document = \Deft::lib('cache.memcached')->getLink()->get('deft.document.' . md5(DEFT_ROUTE));
		if ($document) {
			die($document);
		}
	}
});

/**
 * Cache rendered document for future requests
 */
Filter::add('documentOutput', function($content) {
	\Deft::lib('cache.memcached')->getLink()->set(
		'deft.document.' . md5(DEFT_ROUTE),
		$content,
		\Deft::config('cache.memcached')->get('expire', 900)
	);

	return $content;
});