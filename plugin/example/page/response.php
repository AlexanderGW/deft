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

// TODO: send to a wrapper function for the Snappy payloads stuff
echo json_encode([
	'snappy' => Snappy::VERSION,
	'querySelector' => 'textarea',
	'data' => "[['time' => \Snappy\Lib\Sanitize::forHtml(time())],[3234324 => 'LOREM IPSUM', 'FOO' => 'BAR'], [[[[[[[[[['baz' => 'qux']]]]]]]]]]]"
]);