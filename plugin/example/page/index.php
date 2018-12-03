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

Document::prependTitle( __( 'Welcome' ) );

?>
<h1>Sn<strong>app</strong>y</h1><h2>A PHP 5.3+ framework with essential helpers for PDO, document &amp; element distinct control, security, URI routing, events &amp; filters, and multi-lingual support for creating small custom web apps.</h2>
<p>This is the example plugin's index page. For documentation and examples of what Snappy could offer your project, visit <a href="https://gailey-white.com/snappy-php-framework">this post</a> on my blog.</p>
<p>
	<?php ___( 'Language' ) ?> (<?php echo Language::getLocale() ?>) <a href="./user">User settings</a><br>
	<a href="./request">cURL request</a>
</p>