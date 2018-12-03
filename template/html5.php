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

$markup = "\r\n" . Html::element( array(
	'tag' => 'head',
	'markup' => Document::getHead()
), 'elementHead' ) .
	  "\r\n" . Html::element( array(
	'tag' => 'body',
	'markup' => Document::getBody()
), 'elementBody' );

$props = array(
	'lang' => Document::getLocale()
);
if( Document::getDirection() != 'ltr' )
	$props['dir'] = Document::getDirection();

?><!DOCTYPE html>
<?php echo Html::element( array(
	'tag' => 'html',
	'markup' => $markup,
	'props' => $props
), 'elementHtml' ) ?>