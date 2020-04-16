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

\Snappy::import('lib.http');

use Snappy\Lib\Http;

Snappy::response()->prependTitle(__('cURL request'));

Snappy::response()->addScript( 'plugin/example/asset/js/request.js' );

// Send a POST request to "/response" on the example plugin
//$request = Http::request(
//	// URI
//	'/response',
//
//	// cURL options
//	null,
//
//	// POST data
//	array(
//		'foobar' => 'Testing post'
//	)
//);

// Capture response and build a <textarea> around it.
ob_start();
var_dump($request);
$result = ob_get_clean();

$form = Snappy::form('example')
	->field('textarea')
	->label('Response')
	->description('Output of the request')
	->value($result)
	->readOnly(true)
	->cols(40)
	->rows(20);

?>
<div>
	<div>
		<h1><?php ___('Snappy &ndash; The cURL request helper') ?></h1>
		<p><a href="./">Return to previous page.</a><br>A brief example of the URI based request helper and the information
			provided using the <code>Snappy\Lib\Http::request()</code> function.</p>
	</div>
	<?php echo $form->content() ?>
</div>