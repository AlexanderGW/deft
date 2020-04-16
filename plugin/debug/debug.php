<?php

/**
 * Snappy, a micro framework for PHP.
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

namespace Snappy\Plugin;

use Snappy\Lib\Plugin;
use Snappy\Lib\Route;
use Snappy\Lib\Event;
use Snappy\Lib\Filter;
use Snappy\Lib\Element;

class Debug extends Plugin {
	private static $hash;

	public static function init () {
		\Snappy::route()->add('debug', 'debug', null, '\\Snappy\\Plugin\\Debug::returnSetting');
		\Snappy::route()->add('debug.request', 'debug/request/[hash]', array(
			'hash' => '[0-9a-z]{32}'
		), '\\Snappy\\Plugin\\Debug::returnRequest');

		\Snappy::event()->set('ready', '\Snappy\Plugin\Debug::setResponseAssets');
	}

	public static function setResponseAssets() {
		$res = \Snappy::response();
		if ($res->getArg('type') === 'http.html') {
			$hash = \Snappy\Plugin\Debug::getHash();
			$res->addScriptContent("var Snappy = Snappy || {}; Snappy.debugHash = '{$hash}';");
			$res->addScript('plugin/debug/asset/debug.js');
			$res->addStyle('plugin/debug/asset/debug.css');
		}
	}

	public static function getHash() {
		if (!self::$hash) {
			self::$hash = md5(serialize(\Snappy::filter()->exec('debugRequestHashData', SNAPPY_ROUTE)));
		}
		return self::$hash;
	}

	public static function getPath() {
		return SNAPPY_PLUGIN_PATH . DS . 'debug' . DS . 'cache';
	}

	public static function returnSetting () {

		// Get current debug state
		$config = \Snappy::config();
		if ($debug = \Snappy::request()->post('debug')) {
			$config->set('debug', $debug);
			$config->save();
		}

		// Create the form
		$form = \Snappy::form('debug');

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
		->value($config->get('debug'));

		// Add submit control
		$form->field('input.submit');

		// Add form markup to document
		$res = \Snappy::response();
		$res->appendBody(Element::html(array(
			'@tag' => 'h3',
			'@markup' => __('Snappy / Debug')
		)));
		$res->appendBody($form);
	}

	public static function returnRequest() {

		// Set response output to JSON
		\Snappy::config()->set('response.type', 'http.json');

		// Buffer the content
		\Snappy::response()->buffer(file_get_contents(self::getPath() . DS . \Snappy::route()->getParam('hash') . '.json'));
	}

	public static function writeJson() {

		// Create debugging report for API query
		if (SNAPPY_DEBUG) {
			$path = self::getPath();
			if (!is_dir($path)) {
				mkdir($path);
			}
			file_put_contents($path . DS . self::getHash() . '.json', \Snappy::capture('plugin.debug.template.debug'));
		}
	}
}

\Snappy::event()->set('init', '\Snappy\Plugin\Debug::init');
\Snappy::event()->set('exit', '\Snappy\Plugin\Debug::writeJson', 99);