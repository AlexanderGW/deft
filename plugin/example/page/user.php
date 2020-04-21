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

use Deft\Lib\Language;
use Deft\Lib\Token;

\Deft::response()->prependTitle(__('User environment'));

/**
 * POST of locale, negotiate with available locales, store in token, and reload page
 */
$locale = \Deft::request()->post('locale');

if ($locale) {
//	var_dump($locale);exit;
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
$config =& \Deft::config();
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
$form =& \Deft::form('user_locale');

$form->validate(['', '']);

$form->field('input.radio.locale')
     ->label(__('Language'))
     ->description(__('Select a locale then click Submit to save your preferences.'))
     ->options(array_combine($locales, $locales))
     ->value(\Deft::locale()->getLocale());

$form->field('input.text')
     ->label(__('Input (text)'))
     ->description(__('Input text test'))
     ->value('Lorem ipsum dolor sit amet');

$form->field('input.color')
     ->label(__('Input (color)'))
     ->description(__('Input color test'))
     ->value('#feed01');

$form->field('input.number')
     ->label(__('Input (number)'))
     ->description(__('Input number test'))
     ->value(5);

$form->field('input.range')
     ->label(__('Input (range)'))
     ->description(__('Input range test'))
     ->scales(0, 1, 10)
     ->value(5);

$form->field('textarea')
     ->label(__('Textarea'))
     ->description(__('Textarea test'))
     ->value('Lorem ipsum dolor sit amet.')
     ->cols(40);

$form->field('input.submit')->value(__('Submit'));

$form->save();

?>
<div>
	<h1><?php ___('User settings') ?></h1>
	<p><a href="./">Return to previous page.</a><br>Here is a basic form created with <code>Deft::form()</code>
		to set the user's locale/language (information is stored and retrieved from a PHP session and browser
		cookie using the <code>Token</code> class). See source of this script.</p>
</div>
<?php echo $form;