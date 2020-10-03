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

$res = Deft::response();
$res->prependTitle(__('JSON tools'));
$res->addScript( 'plugin/example/asset/js/request.js' );

$form = Deft::form('request');

$form->field('textarea.url')
     ->label('Raw JSON URL')
     ->readOnly(true)
     ->cols(45)
     ->rows(2)
     ->value(\Deft::route()->toUrl('abs.example.json'));

$form->field('textarea.req')
	->label('Or a JS request to DOM')
	->readOnly(true)
	->cols(45)
	->rows(2)
->value("Deft.get('/example/json').asText('#response')");

$form->field('textarea.res')
     ->label('The #response field')
     ->id('response')
     ->readOnly(true)
     ->cols(45)
     ->rows(2);

?>
<div>
	<h1><?php ___('Deft Example') ?></h1>
	<h2><?php ___('JSON tools') ?></h2>
	<p>
		<a href="<?php echo DEFT_URL ?>">Return to previous page.</a><br>
		Basic example of the JSON API. JS request and inject response as plain text into DOM. Routed by <code>Deft::route()->http()</code></p>
</div>
<?php echo $form;