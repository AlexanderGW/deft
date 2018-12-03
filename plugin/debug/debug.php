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

class Debug_Plugin {
	private static $hash;

	public static function init () {
		Route::add('debug', 'debug', null, array('Debug_Plugin', 'returnSetting'));
		Route::add('debug.request', 'debug/request/[hash]', array(
			'hash' => '[0-9a-z]{32}'
		), array('Debug_Plugin', 'returnRequest'));

		$hash = self::getHash();
		Document::addScriptContent("var Snappy = Snappy || {}; Snappy.debugHash = '{$hash}';");
		Document::addScript('plugin/debug/asset/debug.js');
		Document::addStyle('plugin/debug/asset/debug.css');

		Event::add('onConfigConstruct', function($instance){
			var_dump($instance);
		});
	}

	public static function getHash() {
		if (!self::$hash) {
			self::$hash = md5(serialize(Filter::exec('debugRequestHashData', SNAPPY_ROUTE)));
		}
		return self::$hash;
	}

	public static function getPath() {
		return SNAPPY_PLUGIN_PATH . 'debug' . DS . 'cache';
	}

	public static function returnSetting () {

		// Get current debug state
		$cfg   =& Snappy::getCfg();
		if ($debug = Http::post('debug')) {
			$cfg->set('debug', $debug);
			$cfg->save();
		}

		// Create the form
		$form =& Snappy::getForm('debug');

		// Set as POST
		$form->post(true);

		// Create debug control
		$field = $form->field('input.radio.debug')

		// Set field label
		->label(__('Debug'))

		// Set field description
		->description(__('Set Snappy debugging'));

		// Set field options
		$field->options(array(
			__('Disabled'),
			__('Enabled')
		))

		// Set current value
		->value($cfg->get('debug'));

		// Add submit control
		$form->field('input.submit');

		Document::appendBody(Html::element(array(
			'tag' => 'h3',
			'markup' => __('Snappy / Debug')
		)));

		// Add form markup to document
		Document::appendBody($form);
	}

	public static function returnRequest() {
//		Http::header('Content-type: text/json');
		die(file_get_contents(self::getPath() . DS . Route::getData('hash') . '.json'));
	}
}

// Append debuggingvagrant up
if (SNAPPY_DEBUG) {
	Event::add('afterDocumentContent', function() {
		$path = Debug_Plugin::getPath();
		if (!is_dir($path)) {
			mkdir($path);
		}
		file_put_contents($path . DS . Debug_Plugin::getHash() . '.json', Snappy::capture('plugin.debug.template.debug'));
	}, 999);
}

Event::add('init', array('Debug_Plugin', 'init'));
Event::add('init', array('Debug_Plugin', 'dump'), 999999);