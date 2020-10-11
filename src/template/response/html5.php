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

use \Deft\Lib\Element;

$markup = "\r\n" . Element::html( array(
	'@tag' => 'head',
	'@markup' => \Deft::response()->getHead()
), 'element.head' ) .
	  "\r\n" . Element::html( array(
	'@tag' => 'body',
	'@markup' => \Deft::response()->getBody() . '<!--Rendered: ' . date('Y-m-d H:i:s') . '-->',
	'@props' => [
		'class' => Deft::filter()->exec('response.html5.body.class', [
			'no-js'
		])
	]
), 'element.body' );

$props = array(
	'lang' => \Deft::response()->getLocale(),
	'class' => Deft::filter()->exec('response.html5.html.class')
);
if( \Deft::response()->getDirection() != 'ltr' )
	$props['dir'] = \Deft::response()->getDirection();

?><!DOCTYPE html>
<?php echo Element::html( array(
	'@tag' => 'html',
	'@markup' => $markup,
	'@props' => $props
), 'element.html' ) ?>