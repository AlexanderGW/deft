/* (1/4) plugin/debug/asset/debug.js */
Deft.debug = {};

/* Recursively render some buggy time bars */
Deft.debug.generateTimeline = function(thing){
	let html = '';
	if (typeof thing == 'object') {
// console.log(Deft.debug.time/100);
		let style = '';
		for (key2 in thing) {
			let type = typeof thing[key2];
			// console.log(key2);

			// Item value
			switch (type) {

				// Item has other things, recurse
				case 'object':
					html += Deft.debug.generateTimeline(thing[key2]);
					break;

				// Item is a number, string, or bool
				case 'number':
				case 'string':
				case 'boolean':
					if (key2 == 'time' || key2 == 'moment') {
						let value = ((thing[key2]/Deft.debug.time)*100).toFixed(30);

						if (key2 == 'time') {
							style += 'width:'+(value < 1 ? 1 : value)+'%;';
						}

						else if (key2 == 'moment') {
							style += 'left:'+value+'%;';
						}
					}
					break;
			}
		}

		if (style.length) {
			html += '<div style="'+style+'"></div>';
		}
	}
	return html;
};

/* Recursivly render object as an HTML table */
Deft.debug.recursiveTableObject = function(thing, depth, label){
	if (typeof depth == 'undefined') {
		depth = 0;
	}
	if (typeof label == 'undefined') {
		label = '';
	}
	let html = '';
	if (typeof thing == 'object') {

		// Check things items
		let size = 0, key;
		for (key in thing) {
			if (thing.hasOwnProperty(key))
				size++;
		}

		// Thing has stuff
		if (size) {

			// Table for things items
			html += '<table>';
			for (key in thing) {
				let type = typeof thing[key];

				// Item row
				html += '<tr><td'+(!depth ? ' data-label="'+label+'"' : '')+'><span>'+(key.length > 0 ? key : '<i>NULL</i>')+'</span></td><td>';

				// Item type
				html += type;

				// Item value
				switch (type) {

					// Item has other things, recurse
					case 'object':
						html += Deft.debug.recursiveTableObject(thing[key], depth+1);
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

let xhr = new XMLHttpRequest();
xhr.open('GET', '/debug/request/' + Deft.debugHash);
xhr.send(null);
xhr.onreadystatechange = function () {
	let DONE = 4; // readyState 4 means the request is done.
	let OK = 200; // status 200 is a successful return.
	if (xhr.readyState === DONE) {
		if (xhr.status === OK) {
			let debug = JSON.parse(xhr.responseText);
			Deft.debug.time = debug.time;
			let html = '<section class="deft-debug"><div>' +
				'<h2>Deft stack</h2>' +
				'<p>Run time: '+debug.time+' seconds. Memory usage: '+debug.memory + '</p>';

			for (key in debug) {
				switch (typeof debug[key]) {
					case 'object':
						html += '<section class="'+(key == 'event' ? 'expanded' : 'collapsed')+'"><div>';
						html += '<h3>'+key+'</h3>';
						html += '</div><div class="timeline">';
						html += Deft.debug.generateTimeline(debug[key]);
						html += '</div><div class="detail">';
						html += Deft.debug.recursiveTableObject(debug[key], 0, key);
						html += '</div></section>';
						break;
				}
			}

			html += '</div></section>';

			document.querySelector('body').innerHTML += html;
		} else {
			console.log('Error: ' + xhr.status);
		}
	}
};

/* (2/4) plugin/example/asset/js/main.js */


/* (3/4) plugin/example/asset/js/main.js */


/* (4/4) plugin/example/asset/js/main.js */


