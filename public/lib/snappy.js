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

'use strict';

var Snappy = Snappy || {
	DEBUG: 1
};

Snappy.request = function (method, url, data) {
	var xhr = new XMLHttpRequest();
	xhr.open(method, url);
	xhr.send(data);
	xhr.onreadystatechange = function () {
		if (xhr.readyState === 4) {
			Snappy.response(xhr);
		}
	};
};

Snappy.response = function (xhr /*XMLHttpRequest*/ ) {
	console.log(xhr);
};

Snappy.delete = function (url) {
	Snappy.request('DELETE', url);
};

Snappy.get = function (url) {
	Snappy.request('GET', url);
};

Snappy.post = function (url, data) {
	Snappy.request('POST', url, data);
};

Snappy.put = function (url) {
	Snappy.request('PUT', url, data);
};