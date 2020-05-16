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

\Deft::import('lib.http');

use Deft\Lib\Http;

Deft::response()->prependTitle(__('JSON API request'));

Deft::response()->addScript( 'plugin/example/asset/js/request.js' );

$form = Deft::form('example')
	->field('textarea')
	->label('Response')
	->id('json_test')
	->description('Output of the request')
	->readOnly(true)
	->cols(40)
	->rows(20);

?>
<div>
	<div>
		<h1><?php ___('Deft &ndash; The JSON API') ?></h1>
		<p><a href="./">Return to previous page.</a><br>A brief example of the JSON API provided with Deft. Requested data using <code>Deft.get()</code> (an HTTP GET wrapper for <code>Deft.request()</code>), for a response created with <code>Deft::response()-output()</code></p>
	</div>
	<?php echo $form->content() ?>
</div>