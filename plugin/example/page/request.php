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

Document::prependTitle(__('cURL request'));

// Send a POST request to "/response" on the example plugin
$request = Http::request(
// URI
	SNAPPY_URL . '/response',

	// cURL options
	null,

	// POST data
	array(
		'foobar' => "Testing post"
	)
);

// Capture response and build a <textarea> around it.
ob_start();
var_dump($request);
$result = ob_get_clean();

$form = Snappy::getForm();
$form->field('textarea')->label('Response')->description('API call response')->value($result)->readOnly(true)->cols(40)->rows(20);

?>
	<h3><?php ___('Snappy &ndash; The cURL request helper') ?></h3>
	<p><a href="./">Return to previous page.</a><br>A brief example of the URI based request helper and the information
		provided using the <code>Http::request()</code> function.</p>
<?php echo $form;