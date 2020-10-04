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

'use strict';

var _deftObjectResponse = {
	xhr: null,
	atReadyState4: {},
	asText: function(querySelector) {
		if (this.xhr.readyState === 1)
			this.atReadyState4['asText'] = querySelector;
		if (this.xhr.readyState === 4) {
			document.querySelector(querySelector).innerText = this.xhr.responseText;
		}
		return this;
	},
	asHtml: function(querySelector) {
		if (this.xhr.readyState === 1)
			this.atReadyState4['asHtml'] = querySelector;
		if (this.xhr.readyState === 4) {
			document.querySelector(querySelector).innerHTML = this.xhr.responseText;
		}
		return this;
	}
}

var Deft = Deft || {
	DEBUG: 1,
	stack: null
};

Deft.stack = {
	__stack: {},
	get: function(name) {
		return this.__stack[name];
	},
	set: function(name, obj) {
		return this.__stack[name] = obj;
	}
};




Deft.request = function (method, url, data) {
	let obj = Object.create(_deftObjectResponse);
	obj.xhr = new XMLHttpRequest();
	obj.xhr.open(method, url);
	obj.xhr.send(data);
	obj.xhr.onreadystatechange = function () {
		if (obj.xhr.readyState === 4) {
			for (let prop in obj.atReadyState4) {
				obj[prop](obj.atReadyState4[prop]);
			}
		}
	};
	return Deft.stack.set(url, obj);
};

Deft.delete = function (url) {
	return Deft.request('DELETE', url);
};

Deft.get = function (url) {
	return Deft.request('GET', url);
};

Deft.post = function (url, data) {
	return Deft.request('POST', url, data);
};

Deft.put = function (url) {
	return Deft.request('PUT', url, data);
};