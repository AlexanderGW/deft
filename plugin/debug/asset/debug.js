var xhr = new XMLHttpRequest();
xhr.open('GET', '/debug/request/' + Snappy.debugHash);
xhr.send(null);
xhr.onreadystatechange = function () {
	var DONE = 4; // readyState 4 means the request is done.
	var OK = 200; // status 200 is a successful return.
	if (xhr.readyState === DONE) {
		if (xhr.status === OK) {
			var debug = JSON.parse(xhr.responseText);
			var html = '<article class="sy-debug-report"><div>' +
				'<h2>Snappy &ndash; Stack debug</h2>' +
				'<div>Run time: '+debug.time+' seconds. Memory usage: '+debug.memory + '</div>';

			for (key in debug) {
				switch (typeof debug[key]) {
					case 'object':
						html += '<h3>'+key+'</h3>';
						html += Snappy.debug.recursiveTableObject(debug[key]);
						break;
				}
			}

			html += '</div></article>';

			document.querySelector('body').innerHTML += html;
		} else {
			console.log('Error: ' + xhr.status);
		}
	}
};

Snappy.debug = {};

/* Recursivly render object as an HTML table */
Snappy.debug.recursiveTableObject = function(thing){
	var html = '';
	if (typeof thing == 'object') {

		// Check things items
		var size = 0, key;
		for (key in thing) {
			if (thing.hasOwnProperty(key))
				size++;
		}

		// Thing has stuff
		if (size) {

			// Table for things items
			html += '<table>';
			for (key in thing) {
				var type = typeof thing[key];

				// Item row
				html += '<tr><td>'+(key.length > 0 ? key : '<i>NULL</i>')+'</td><td>';

				// Item type
				html += type;

				// Item value
				switch (type) {

					// Item has other things, recurse
					case 'object':
						html += Snappy.debug.recursiveTableObject(thing[key]);
						break;

					// Item is a number, string, or bool
					case 'number':
					case 'string':
					case 'boolean':
						html += '('+thing[key]+')';
						break;

					// Item is null
					default:
						html += '<i>NULL</i>';
				}

				html += '</td></tr>';
			}
			html += '</table>';
		}

		// Thing has nothing
		else {
			html += '(<i>NULL</i>)';
		}
	}
	return html;
};
