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

\Deft::response()->prependTitle( __( 'Welcome' ) );

//echo Sanitize::forHtml(Deft::request()->query('aaa'));

?>
<div>
	<h1>Deft, a micro framework for PHP & JavaScript.</h1>
	<p>This is the sample page, routed to <strong>Deft\Plugin\Example</strong>. For examples on what Deft can do,
		visit <a href="https://gailey-white.com/deft-php-framework">this post</a> on my blog.</p>
	<p class="cta"><strong><?php ___('User environment') ?> (<a href="./user">modify</a>)</strong><br><?php ___( 'Language' ) ?> (<?php echo \Deft::locale()->getLocale() ?>)</p>
	<p>
		<a href="./request">JSON API</a>
	</p>
</div>