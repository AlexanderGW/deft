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
 * Class DeftUnitCoreTest
 *
 * @group unit.deft
 */

class TestDeftUnitCore extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->scope = 'lib.response';
		$this->class = \Deft\Lib\Response::class;
		$this->args = [
			'foo' => 'bar'
		];
	}

	/**
	 * Test library importing
	 *
	 * @covers Deft::import
	 */
	public function test_import() {
		$errors = Deft::import(
			$this->scope
		);
		$this->assertCount(
			0,
			$errors,
			"Valid library returned a failure"
		);

		$errors = Deft::import(
			'foo.bar'
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
	 * @covers Deft::havePlugin
	 */
	public function test_havePlugin() {
		$result = Deft::havePlugin(
			'example'
		);
		$this->assertEquals(Deft::PLUGIN_LOADED, $result);

		$result = Deft::havePlugin(
			'debug'
		);
		$this->assertEquals(Deft::PLUGIN_EXISTS, $result);

		$result = Deft::havePlugin(
			'foobar'
		);
		$this->assertEquals(Deft::PLUGIN_MISSING, $result);
	}

	/**
	 * Test instance key
	 *
	 * @covers Deft::getInstanceKey
	 */
	public function test_getInstanceKey() {
		$key = Deft::getInstanceKey($this->class, $this->args);
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
	 * @covers Deft::log
	 * @covers Deft::stack
	 */
	public function test_stack() {
		$key = Deft::getInstanceKey($this->class, $this->args);

		$stack = "instance/{$this->class}/{$key}";

		// Create stack log entry
		Deft::stack($stack, $this->args, $replace = FALSE);
		$log = Deft::stack($stack);
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
		Deft::stack($stack, $args, $replace = FALSE);
		$log = Deft::stack($stack);
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
		Deft::stack($stack, $args, $replace = TRUE);
		$log = Deft::stack($stack);
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
	 * @covers Deft_Concrete
	 */
	public function test_concrete() {
		$class = 'Deft_Concrete';
		$key = Deft::getInstanceKey($class, $this->args);
		$stack = "instance/{$class}/{$key}";
		$instance = new \Deft_Concrete($this->args);

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
	 * @covers Deft::newInstance
	 */
	public function test_newInstance() {
		$key = Deft::getInstanceKey($this->class, $this->args);

		// First instance created
		$instance = Deft::newInstance($this->class, $this->args);
		$this->assertInstanceOf(
			$this->class,
			$instance,
			"An instance of {$this->class}, was not returned"
		);

		$log = Deft::stack("instance/{$this->class}/{$key}/calls");
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
	 * @covers Deft::haveInstance
	 */
	public function test_haveInstance() {
		$this->assertTrue(
			Deft::haveInstance(Deft::getInstanceKey($this->class, $this->args)),
			"Valid instance returned as not exists"
		);

		$this->assertFalse(
			Deft::haveInstance(Deft::getInstanceKey($this->class, 'baz')),
			"Invalid instance returned as exists"
		);
	}

	/**
	 * Test get instance
	 *
	 * @depends test_newInstance
	 *
	 * @covers Deft::get
	 */
	public function test_getInstance() {
		$args = 'foo';
		$key = Deft::getInstanceKey($this->class, $this->args);

		$instance = Deft::get($this->scope, $this->args);

		$this->assertInstanceOf(
			$this->class,
			$instance,
			"Returned value was not an instance of \\Deft\\Lib::class"
		);

		$log = Deft::stack("instance/{$this->class}/{$key}/calls");
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
	 * @covers Deft::encode
	 */
	public function test_encode() {
		$config = Deft::config();

		$this->assertNull(
			$config->get('secret'),
			"Secret should not initially exist"
		);

		$encoded = Deft::encode($this->args);

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
	 * @covers Deft::decode
	 */
	public function test_decode() {
		$this->assertEquals(
			$this->args,
			Deft::decode(Deft::encode($this->args)),
			'The encoded object does not decode to the original value'
		);
	}

	/**
	 * Test object capture
	 *
	 * @covers Deft::capture
	 */
	public function test_capture() {
		$config = Deft::config();

		$scope = 'template.response.html5';

//		$this->assertNull(
//			$config->get('capture_hash'),
//			"Capture hash should not initially exist"
//		);

		$capture = Deft::capture($scope);
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

		$log = Deft::stack("capture/{$scope}");
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
	 * @covers Deft::error
	 */
	public function test_error() {
		$this->expectOutputRegex('%\<h1\>App error\<\/h1\>%');

		Deft::error('foobar');
	}
}