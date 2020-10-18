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

use Deft\Lib\Token;

\Deft::response()->prependTitle(__('User environment'));

/**
 * POST of locale, negotiate with available locales, store in token, and reload page
 */
$locale = \Deft::request()->post('locale');

if ($locale) {
	$code = \Deft::locale()->negotiate($locale);
	Token::set('locale', $code);
	Token::saveCookie();
//	\Deft::response()->location('/user');
} else {
//	var_dump(Token::get('locale'), \Deft::locale()->getLocale());
}

/*
 * Get a list of locales from config, if empty add Spanish "es-ES" (see /locale/es-es.php) to test with,
 * and add the default EN for form options
 */
$config = \Deft::config();
$locales = $config->get('locales');

if (!$locales) {
	$locales = array('es-ES');
	$config->set('locales', $locales);
	$config->save();
}
array_unshift($locales, 'en-GB');

/**
 * Build form to set locale environment
 */
$form = \Deft::form('user_locale');

$form->validate(['', '']);

$form->field('input.radio.locale')
     ->label(__('Language'))
     ->description(__('Select a locale then click Submit to save your preferences.'))
     ->options(array_combine($locales, $locales))
     ->value(\Deft::locale()->getLocale());

$form->field('input.submit')->value(__('Submit'));

?>
<div>
	<h1><?php ___('Deft example') ?></h1>
	<h2><?php ___('User environment') ?></h2>
	<p><a href="<?php echo DEFT_URL ?>">Return to previous page.</a><br>Here is a basic form created with <code>Deft::form()</code>
		to set the user's locale/language (information is stored and retrieved from a PHP session and browser
		cookie using the <code>Deft\Lib\Token</code> class). This script is located at
		<code>~/plugin/example/page/user.php</code></p>
</div>
<?php echo $form;