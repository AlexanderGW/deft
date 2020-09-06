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

namespace Deft\Plugin;

use Deft\Lib\Plugin;
use Deft\Lib\Route;
use Deft\Lib\Event;
use Deft\Lib\Filter;
use Deft\Lib\Element;

class Debug extends Plugin {
	private static $hash;

	public static function init () {
		\Deft::route()->http('debug', 'debug', null, '\Deft\Plugin\Debug::returnSetting');
		\Deft::route()->http('debug.request', 'debug/request/[hash]', array(
			'hash' => '[0-9a-z]{32}'
		), '\Deft\Plugin\Debug::returnRequest');

		\Deft::event()->set('ready', '\Deft\Plugin\Debug::setResponseAssets');

		\Deft::event()->set('cliCacheClearStorage', function(){
			$fs = \Deft::filesystem();
			if ($fs->exists(DEFT_STORAGE_PATH . DS . 'debug')) {
				if (!$fs->delete(DEFT_STORAGE_PATH . DS . 'debug', true)) {
					\Deft::log()->warning(__('Failed to delete debug storage'));
				}
			}
		});
	}

	public static function setResponseAssets() {
		$res = \Deft::response();
		if ($res->type === 'http.html') {
			$hash = \Deft\Plugin\Debug::getHash();
			$res->addScriptContent("var Deft = Deft || {}; Deft.debugHash = '{$hash}';");
			$res->addScript('plugin/debug/asset/debug.js');
			$res->addStyle('plugin/debug/asset/debug.css');
		}
	}

	public static function getHash() {
		if (!self::$hash) {
			self::$hash = md5(serialize(\Deft::filter()->exec('debugRequestHashData', DEFT_ROUTE)));
		}
		return self::$hash;
	}

	public static function getPath() {
		return DEFT_STORAGE_PATH . DS . 'debug';
	}

	public static function returnSetting () {

		// Get current debug state
		$config = \Deft::config();
		if ($debug = \Deft::request()->post('debug')) {
			$config->set('debug', $debug);
			$config->save();
		}

		// Create the form
		$form = \Deft::form('debug');

		// Set as POST
		$form->post(true);

		// Create debug control
		$field = $form->field('input.radio.debug')

		// Set field label
		->label(__('Debug'))

		// Set field description
		->description(__('Set Deft debugging'));

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
		$res = \Deft::response();
		$res->appendBody(Element::html(array(
			'@tag' => 'h3',
			'@markup' => __('Deft / Debug')
		)));
		$res->appendBody($form);
	}

	public static function returnRequest() {

		// Set response output to JSON
		\Deft::config()->set('response.type', 'http.json');

		$content = \Deft::filesystem()->read(self::getPath() . DS . \Deft::route()->getParam('hash') . '.json');

		// Buffer the content
		\Deft::response()->buffer($content);
	}

	public static function shutdown() {
		if (DEFT_DEBUG) {
			register_shutdown_function( 'Deft\Plugin\Debug::captureWriteJson' );
		}
	}

	public static function captureWriteJson() {
		$fs = \Deft::filesystem();

		if ($fs->exists(self::getPath()) === false)
			$fs->install(self::getPath());

		if (!$fs->write(self::getPath() . DS . self::getHash() . '.json', \Deft::capture('plugin.debug.template.json')))
			\Deft::log()->warning(__('Failed to write debug stack JSON to storage'));
	}
}

\Deft::event()->set('init', '\Deft\Plugin\Debug::init');
\Deft::event()->set('exit', '\Deft\Plugin\Debug::shutdown', 999);