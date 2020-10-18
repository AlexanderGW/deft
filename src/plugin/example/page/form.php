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

\Deft::response()->prependTitle(__('Form API'));

/**
 * POST of locale, negotiate with available locales, store in token, and reload page
 */
$locale = \Deft::request()->post('locale');

if ($locale) {
	$code = \Deft::locale()->negotiate($locale);
	Token::set('locale', $code);
	Token::save();
	\Deft::response()->location('/form');
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

$options = array_combine($locales, $locales);

/**
 * Build form to set locale environment
 */
$form = \Deft::form('example.form');

$form->validate(['', '']);

$form->field('input.radio.locale')
     ->label(__('Radios'))
     ->description(__('Descriptions and labels are WCAG ready with ARIA properties'))
     ->options($options)
     ->value(\Deft::locale()->getLocale());

$form->field('input.checkbox.locale2')
     ->label(__('Checkboxes'))
     ->options($options)
     ->value(array_values($options));

$form->field('input.text')
     ->label(__('Input (text)'))
     ->value('Lorem ipsum dolor sit amet');

$form->field('input.color')
     ->label(__('Input (color)'))
     ->value('#feed01');

$form->field('input.number')
     ->label(__('Input (number)'))
     ->value(5);

$form->field('input.range')
     ->label(__('Input (range)'))
     ->scales(0, 1, 10)
     ->value(5);

$form->field('select.single')
     ->label(__('Select (single)'))
     ->options($options)
     ->value(array_values($options));

$form->field('select.multiple')
     ->label(__('Select (multiple)'))
     ->options(array_keys($config->get()))
     ->size(3);

$form->field('textarea')
     ->label(__('Textarea'))
     ->value('Lorem ipsum dolor sit amet.')
     ->cols(30);

$form->field('input.submit')->value(__('Submit'));

?>
<div>
	<h1><?php ___('Deft Example') ?></h1>
	<h2><?php ___('Form API') ?></h2>
	<p><a href="<?php echo DEFT_URL ?>">Return to previous page.</a><br>Here is a form created with <code>Deft::form()</code>
		This script is located at <code>~/plugin/example/page/form.php</code></p>
</div>
<?php echo $form;