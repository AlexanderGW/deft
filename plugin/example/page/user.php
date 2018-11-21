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

Document::prependTitle(__('User environment'));

/**
 * POST of locale, negotiate with available locales, store in token, and reload page
 */
$locale = Http::post('locale');
if ($locale) {
	Token::set('locale', Language::negotiate($locale));
	Token::saveCookie();
	Http::location('./user');
}

/*
 * Get a list of locales from config, if empty add Spanish "es-ES" (see /locale/es-es.php) to test with,
 * and add the default EN for form options
 */
$cfg     =& Snappy::getCfg();
$locales = $cfg->get('locales');
if (!$locales) {
	$locales = array('es-ES');
	$cfg->set('locales', $locales);
	$cfg->save();
}
array_unshift($locales, 'en-GB');

/**
 * Build form to set locale environment
 */
$form =& Snappy::getForm('user-locale')->route('example.page')->props(array(
	'method' => 'post'
));

$form->field('input.radio.locale')
     ->label(__('Language'))
     ->description(__('Select a locale then click Submit to save your preferences.'))
     ->options(array_combine($locales, $locales))
     ->value(Language::getLocale());

$form->field('input.checkbox.locale2')
     ->label(__('Language'))
     ->description(__('Select a locale then click Submit to save your preferences.'))
     ->options(array_combine($locales, $locales))
     ->value(Language::getLocale());

$form->field('select.locale3')
     ->label(__('Language'))
     ->description(__('Select a locale then click Submit to save your preferences.'))
     ->options(array_combine($locales, $locales))
     ->value(Language::getLocale());

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
	<h3><?php ___('Snappy / User') ?></h3>
	<p><a href="./">Return to previous page.</a><br>Here is a basic form created with <code>Snappy::getForm()</code>
		to set the user's locale/language (information is stored and retrieved from a PHP session and browser
		cookie using the <code>Token</code> class). See source of this script.</p>
<?php echo $form;