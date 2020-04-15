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

/**
 * Here is my attempt to convey my thought process for Snappy core, and how it should work.
 *
 * Class SnappyUnitCoreTest
 *
 * @group unit.snappy
 */

class SnappyUnitCoreTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->scope = 'lib.response';
		$this->class = \Snappy\Lib\Response::class;
		$this->args = [
			'foo' => 'bar'
		];
	}

	/**
	 * Test library importing
	 *
	 * @covers Snappy::import
	 */
	public function test_import() {
		$errors = Snappy::import(
			$this->scope
		);
		$this->assertCount(
			0,
			$errors,
			"Valid library returned a failure"
		);

		$errors = Snappy::import(
			'foobar'
		);
		$this->assertCount(
			1,
			$errors,
			"Invalid library did not return a failure"
		);
	}

	/**
	 * Test plugin checks
	 *
	 * @covers Snappy::havePlugin
	 */
	public function test_havePlugin() {
		$result = Snappy::havePlugin(
			'example'
		);
		$this->assertEquals(Snappy::PLUGIN_LOADED, $result);

		$result = Snappy::havePlugin(
			'debug'
		);
		$this->assertEquals(Snappy::PLUGIN_EXISTS, $result);

		$result = Snappy::havePlugin(
			'foobar'
		);
		$this->assertEquals(Snappy::PLUGIN_MISSING, $result);
	}

	/**
	 * Test instance key
	 *
	 * @covers Snappy::getInstanceKey
	 */
	public function test_getInstanceKey() {
		$key = Snappy::getInstanceKey($this->class, $this->args);
		$expected = $this->class . '_' . md5(serialize($this->args));
		$this->assertEquals(
			$expected,
			$key,
			"Instance key is $key, instead of $expected"
		);
	}

	/**
	 * Test log
	 *
	 * @depends test_getInstanceKey
	 *
	 * @covers Snappy::log
	 * @covers Snappy::getLog
	 */
	public function test_log() {
		$key = Snappy::getInstanceKey($this->class, $this->args);

		$stack = "instance/{$this->class}/{$key}";

		// Create stack log entry
		Snappy::log($stack, $this->args, $replace = FALSE);
		$log = Snappy::getLog($stack);
		$count = count($log);

		$this->assertCount(
			1,
			$log,
			"Stack log returned $count entries, instead of 1"
		);

		$this->assertIsFloat(
			$log[0]['moment'],
			"Stack log 'moment' key is not a float"
		);

		$this->assertEquals(
			'bar',
			$log[0]['foo'],
			"Stack log delta 0 does not contain argument 'foo' with value 'bar'"
		);

		// Append new stack entry with argument 'foo' value 'baz'
		$args = array_merge(
			$this->args,
			[
				'foo' => 'baz'
			]
		);
		Snappy::log($stack, $args, $replace = FALSE);
		$log = Snappy::getLog($stack);
		$count = count($log);

		$this->assertCount(
			2,
			$log,
			"Stack log returned $count entries, instead of 2"
		);

		$this->assertEquals(
			'baz',
			$log[1]['foo'],
			"Stack log delta 1 does not contain argument 'foo' with value 'baz'"
		);

		// Check both entries aren't identical
		$this->assertNotEquals(
			$log[0]['moment'],
			$log[1]['moment'],
			"Stack log entries have identical 'moment' keys"
		);

		// Replace stack entry with argument 'foo' value 'qux'
		$args = array_merge(
			$this->args,
			[
				'foo' => 'qux'
			]
		);
		Snappy::log($stack, $args, $replace = TRUE);
		$log = Snappy::getLog($stack);
		$count = count($log);

		$this->assertCount(
			1,
			$log,
			"Replacement stack log returned $count entries, instead of 1"
		);

		$this->assertEquals(
			'qux',
			$log[0]['foo'],
			"Replacement stack log delta 0 does not contain argument 'foo' with value 'qux'"
		);
	}

	/**
	 * Test log
	 *
	 * @depends test_getInstanceKey
	 *
	 * @covers Snappy_Concrete
	 */
	public function test_concrete() {
		$class = 'Snappy_Concrete';
		$key = Snappy::getInstanceKey($class, $this->args);
		$stack = "instance/{$class}/{$key}";
		$instance = new \Snappy_Concrete($this->args);

		$this->assertEquals(
			$stack,
			$instance->getStack(),
			'Stack ID format is not valid'
		);

		$result = $instance->get('foo');

		$this->assertEquals(
			'bar',
			$result,
			"Argument 'foo' should equal 'bar', instead returned '$result'"
		);

		$this->assertTrue(
			$instance->put('foo', 'baz'),
			"Set argument 'foo' to 'baz' failed"
		);

		$result = $instance->get('foo');

		$this->assertEquals(
			'baz',
			$result,
			"Argument 'foo' should equal 'baz', instead returned '$result'"
		);

		$this->assertTrue(
			$instance->put('foo'),
			"Failed to set argument 'foo' to NULL"
		);

		$this->assertNull(
			$instance->get('foo'),
			"Argument 'foo' should be NULL"
		);
	}

	/**
	 * Test new instance
	 *
	 * @depends test_import
	 *
	 * @covers Snappy::newInstance
	 */
	public function test_newInstance() {
		$key = Snappy::getInstanceKey($this->class, $this->args);

		// First instance created
		$instance = Snappy::newInstance($this->class, $this->args);
		$this->assertInstanceOf(
			$this->class,
			$instance,
			"An instance of {$this->class}, was not returned"
		);

		$log = Snappy::getLog("instance/{$this->class}/{$key}/calls");
		$count = count($log);

		$this->assertCount(
			1,
			$log,
			"Stack log number of instance calls count returned $count, instead of 1"
		);

		$this->assertIsFloat(
			$log[0]['moment'],
			"Stack log 'moment' key is not a float"
		);
	}

	/**
	 * Test have instance
	 *
	 * @depends test_getInstanceKey
	 *
	 * @covers Snappy::haveInstance
	 */
	public function test_haveInstance() {
		$this->assertTrue(
			Snappy::haveInstance(Snappy::getInstanceKey($this->class, $this->args)),
			"Valid instance returned as not exists"
		);

		$this->assertFalse(
			Snappy::haveInstance(Snappy::getInstanceKey($this->class, 'baz')),
			"Invalid instance returned as exists"
		);
	}

	/**
	 * Test get instance
	 *
	 * @depends test_newInstance
	 *
	 * @covers Snappy::get
	 */
	public function test_getInstance() {
		$args = 'foo';
		$key = Snappy::getInstanceKey($this->class, $this->args);

		$instance = Snappy::get($this->scope, $this->args);

		$this->assertInstanceOf(
			$this->class,
			$instance,
			"Returned value was not an instance of \\Snappy\\Lib::class"
		);

		$log = Snappy::getLog("instance/{$this->class}/{$key}/calls");
		$count = count($log);

		$this->assertCount(
			1,
			$log,
			"Stack log number of instance calls count returned $count, instead of 1"
		);
	}

	/**
	 * Test encode
	 *
	 * @covers Snappy::encode
	 */
	public function test_encode() {
		$config = Snappy::config();

		$this->assertNull(
			$config->get('secret'),
			"Secret should not initially exist"
		);

		$encoded = Snappy::encode($this->args);

		$this->assertRegExp(
			'%^[!-~]+$%',
			$config->get('secret'),
			'Secret of ASCII characters was not generated for encoding'
		);

		$this->assertRegExp(
			'%^[!-~]+$%',
			$encoded,
			'Do not have a valid encoded string of ASCII characters'
		);
	}

	/**
	 * Test encode
	 *
	 * @depends test_encode
	 *
	 * @covers Snappy::decode
	 */
	public function test_decode() {
		$this->assertEquals(
			$this->args,
			Snappy::decode(Snappy::encode($this->args)),
			'The encoded object does not decode to the original value'
		);
	}

	/**
	 * Test object capture
	 *
	 * @covers Snappy::capture
	 */
	public function test_capture() {
		$config = Snappy::config();

		$scope = 'template.response.html5';

//		$this->assertNull(
//			$config->get('capture_hash'),
//			"Capture hash should not initially exist"
//		);

		$capture = Snappy::capture($scope);
//		var_dump($scope);

		$this->assertRegExp(
			'%^[a-z0-9]{32}$%',
			$config->get('capture_hash'),
			'Capture hash is not MD5'
		);

		$this->assertStringStartsWith(
			'<!DOCTYPE html>',
			$capture,
			'HTML5 template does not start with doctype'
		);

		$this->assertStringEndsWith(
			"\r\n",
			$capture,
			'HTML5 template does not end with carriage return & new line'
		);

		$this->assertStringContainsString(
			Snappy::VERSION,
			$capture
		);

		$log = Snappy::getLog("capture/{$scope}");
		$count = count($log);

		$this->assertCount(
			1,
			$log,
			"Stack log number of capture calls count returned $count, instead of 1"
		);

		$this->assertIsFloat(
			$log[0]['time'],
			"Stack log 'time' key is not a float"
		);

		$time = 0.02;
		$this->assertLessThan(
			$time,
			$log[0]['time'],
			"Capture took more than $time seconds to complete"
		);
	}

	/**
	 * Test critical error
	 * TODO: Expand test once finalised
	 *
	 * @covers Snappy::error
	 */
	public function test_error() {
		$this->expectOutputRegex('%\<h1\>App error\<\/h1\>%');

		Snappy::error('foobar');
	}
}