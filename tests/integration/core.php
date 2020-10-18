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

/**
 * Here is my attempt to convey my thought process for Deft core, and how it should work.
 *
 * Class DeftIntegrationCoreTest
 *
 * @group integration.deft
 */
class DeftTestIntegrationCore extends \PHPUnit\Framework\TestCase {
	private $output = NULL;

	protected function setUp(): void {
		parent::setUp();

		$this->scope = 'response.http.html';
		$this->class = '\\Deft\\Lib\\Response\\Http\\Html';
		$this->args = [
			'base'      => null,
			'encoding'  => 'utf-8',
			'locale'    => 'en',
			'direction' => 'ltr',
			'mime'      => 'text/html'
		];

		$this->output = \Deft::response()->output();
	}

	/**
	 * Test instance management
	 */
	public function test_constants() {

		// Check Deft path is the same as the working directory
		$this->assertEquals(
			DEFT_ABS_PATH,
			DEFT_PATH,
			"The root Deft path does not match"
		);

		$path = DEFT_ABS_PATH . DS . 'Lib';

		$this->assertEquals(
			$path,
			DEFT_LIB_PATH,
			"The default Deft 'lib' path '" . DEFT_LIB_PATH . "' does not match '$path'"
		);

//		$path = $_SERVER['TEMP'] . DS . 'tmp';
//
//		$this->assertEquals(
//			$path,
//			DEFT_TMP_PATH,
//			"The default Deft 'tmp' path '" . DEFT_TMP_PATH . "' does not match '$path'"
//		);

		$path = DEFT_ABS_PATH . DS . 'plugin';

		$this->assertEquals(
			$path,
			DEFT_PLUGIN_PATH,
			"The default Deft 'plugin' path '" . DEFT_PLUGIN_PATH . "' does not match '$path'"
		);

		$path = DEFT_ABS_PATH . DS . 'public';

		$this->assertEquals(
			$path,
			DEFT_PUBLIC_PATH,
			"The default Deft 'public' path '" . DEFT_PUBLIC_PATH . "' does not match '$path'"
		);

		$path = DEFT_ABS_PATH . DS . 'public' . DS . 'asset';

		$this->assertEquals(
			$path,
			DEFT_PUBLIC_ASSET_PATH,
			"The default Deft 'public_asset' path '" . DEFT_PUBLIC_ASSET_PATH . "' does not match '$path'"
		);

		// Check the Deft path, relative to the document root of the web server
		$path = str_replace(
			str_replace("\\", '/', $_SERVER['DOCUMENT_ROOT']),
			'',
			str_replace("\\", '/', dirname(DEFT_INITIATOR))
		);

		$this->assertEquals(
			$path,
			DEFT_URL_PATH,
			"The DEFT_URL_PATH (The URI path of Deft, relative to the document root of web server) '" . DEFT_URL_PATH . "' does not match '$url'"
		);

		// Check the Deft URI, with relative document root path
		$uri = "http://{$_SERVER['HTTP_HOST']}:{$_SERVER['SERVER_PORT']}{$path}";

		$this->assertEquals(
			$uri,
			DEFT_URL,
			"The DEFT_URL (The full URI of Deft) '" . DEFT_URL_PATH . "' does not match '$uri'"
		);

		// Check the Deft URL for assets
		$url_asset = "{$uri}/asset/";

		$this->assertEquals(
			$url_asset,
			DEFT_ASSET_URL,
			"The DEFT_ASSET_URL (The URL to Deft public assets) '" . DEFT_ASSET_URL . "' does not match '$url_asset'"
		);

		// Check the Deft route path
		$path_route = '';

		$this->assertEquals(
			$path_route,
			DEFT_ROUTE,
			"The DEFT_ROUTE (The path relative to Deft URL) '" . DEFT_ROUTE . "' does not match '$path_route'"
		);
	}

	/**
	 * Test plugin management
	 */
	public function test_plugin_management() {
		$plugins = \Deft::config()->get('plugins');

		// We only have the 'example' plugin.
		$this->assertEquals(
			[
				'debug',
				'example'
			],
			$plugins,
			"The 'debug', and 'example' plugins was not present in the 'plugins' config"
		);

		$log = Deft::stack("plugin/example");
		$count = count($log);

		// Check that 1 stack log exists for the directory-based 'example' plugin
		$this->assertCount(
			1,
			$log,
			"Stack log for plugin 'example' count returned $count, instead of 1"
		);

		// Is the state of the 'example' plugin, Deft::PLUGIN_LOADED.
		$state = $log[0]['loaded'];

		$this->assertEquals(
			Deft::PLUGIN_LOADED,
			$state,
			"The 'example' plugin is not in the loaded state"
		);

		// Is the 'example' plugin load time, greater than 0
		$time = $log[0]['time'];

		$this->assertGreaterThan(
			0,
			$time,
			"The 'example' plugin time returned 0"
		);

		$log = Deft::stack("plugin/debug");
		$count = count($log);

		// Check that 1 stack log exists for the file-based 'debug' plugin
		$this->assertCount(
			1,
			$log,
			"Stack log for plugin 'debug' count returned $count, instead of 1"
		);

		// Is the state of the 'debug' plugin, Deft::PLUGIN_LOADED.
		$state = $log[0]['loaded'];

		$this->assertEquals(
			Deft::PLUGIN_LOADED,
			$state,
			"The 'debug' plugin is not in the loaded state"
		);

		// Is the 'debug' plugin load time, greater than 0
		$time = $log[0]['time'];

		$this->assertGreaterThan(
			0,
			$time,
			"The 'debug' plugin time returned 0"
		);
	}

	/**
	 * Test instance management
	 */
	public function test_instance_management() {
		$key = Deft::getInstanceKey($this->class, $this->args);

		$instance1 = Deft::newInstance($this->class, $this->args);
		$instance2 = Deft::lib($this->scope, $this->args);
		$instance3 = Deft::lib($this->scope, $this->args);
		$instance4 = Deft::lib($this->scope, 'bar');

		// Check all instance calls are of the same class
		$this->assertContainsOnlyInstancesOf(
			\Deft\Lib\Response\Http\Html::class,
			[
				$instance1,
				$instance2,
				$instance3,
				$instance4
			],
			"All calls are not instances of \\Deft\\Lib\\Response\\Http\\Html::class"
		);

		// Check new instance is equal to the subsequent call
		$this->assertEquals(
			$instance1,
			$instance2,
			"Created class instance and subsequent call are not identical objects"
		);

		// Check two identical calls, return the same instance
		$this->assertEquals(
			$instance2,
			$instance3,
			"Identical instance calls did not return identical objects"
		);

		// Check call with different argument value, returns a new instance of the same class
		$this->assertNotEquals(
			$instance3,
			$instance4,
			"Call with different arguments returns an identical object"
		);

		// Check stack log shows 3 calls for the original instance with $args
		$log = Deft::stack("instance/{$this->class}/{$key}/calls");
		$count = count($log);

		$this->assertCount(
			3,
			$log,
			"Stack log number of instance calls count returned $count, instead of 3"
		);

		// Check arg set on instance call A, can be returned from instance call B
		$instance2->setArg('foo', 'bar');
		$value3 = $instance3->getArg('foo');

		$this->assertEquals(
			'bar',
			$value3,
			"Failed to get same value, that was set on another call to the same instance"
		);

		// No longer needed, remove...
		unset($instance2, $instance3, $instance4);

		// Prepend argument 'foo' with 'baz'
		$result = $instance1->prependArg('foo', 'baz');

		$this->assertTrue(
			$result,
			"Prepending value 'baz' onto 'foo' returned FALSE"
		);

		// Check prepended value exists
		$value = $instance1->getArg('foo');

		$this->assertEquals(
			'baz bar',
			$value,
			"Value prepended to 'foo' should be 'baz' for 'baz bar', instead '$value'"
		);

		// Append argument 'foo' with 'baz'
		$result = $instance1->appendArg('foo', 'qux');

		$this->assertTrue(
			$result,
			"Appending value 'baz' onto 'foo' returned FALSE"
		);

		// Check appended value exists
		$value = $instance1->getArg('foo');

		$this->assertEquals(
			'baz bar qux',
			$value,
			"Value prepended to 'foo' should be 'qux' for 'baz bar qux', instead '$value'"
		);
	}
}