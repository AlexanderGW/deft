Deft.example = {};

/* Recursivly render object as an HTML table */
Deft.example.recursiveTableObject = function(thing, depth, label){
	if (typeof depth == 'undefined') {
		depth = 0;
	}
	if (typeof label == 'undefined') {
		label = '';
	}
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
				html += '<tr><td'+(!depth ? ' data-label="'+label+'"' : '')+'><span>'+(key.length > 0 ? key : '<i>NULL</i>')+'</span></td><td>';

				// Item type
				html += type;

				// Item value
				switch (type) {

					// Item has other things, recurse
					case 'object':
						html += Deft.example.recursiveTableObject(thing[key], depth+1);
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

var xhr2 = new XMLHttpRequest();
xhr2.open('GET', '/response');
xhr2.send(null);
xhr2.onreadystatechange = function () {
	var DONE = 4; // readyState 4 means the request is done.
	var OK = 200; // status 200 is a successful return.
	if (xhr2.readyState === DONE) {
		if (xhr2.status === OK) {
			var debug = JSON.parse(xhr2.responseText);

			if (debug.querySelector) {
				console.log('Changed: "' + debug.querySelector + '"');
				document.querySelector(debug.querySelector).innerText = debug.data;
			} else if(debug.querySelectorAll) {
				console.log('Changed: "' + debug.querySelectorAll + '"');
				document.querySelector(debug.querySelectorAll).innerText = debug.data;
			}


			var html = '<article class="sy-debug-report"><div>' +
				'<h2>Deft &ndash; Response for "'+debug.querySelector+'"</h2>' +
				'<div>Version: '+debug.deft + '</div>';

			for (key in debug) {
				switch (typeof debug[key]) {
					case 'object':
						html += '<section class="expanded">';
						html += '<h3>'+key+'</h3>';
						html += Deft.example.recursiveTableObject(debug[key], 0, key);
						html += '</section>';
						break;
					case 'string':
						html += '<section class="expanded">';
						html += '<h3>'+key+'</h3>';
						html += debug[key];
						html += '</section>';
						break;
				}
			}

			html += '</div></article>';

			document.querySelector('body').innerHTML += html;
		} else {
			console.log('Error: ' + xhr2.status);
		}
	}
};