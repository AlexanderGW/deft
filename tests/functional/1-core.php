<?php

/**
 * test snappy.php
 *
 * @group core.integration
 */

class SnappyIntegrationTest extends \PHPUnit\Framework\TestCase {
	private $response = NULL;
	protected function setUp(): void {
		parent::setUp();

		$this->scope = 'document';
		$this->class = '\\Snappy\\Lib\\Document';
		$this->args = [
			'base'      => null,
			'encoding'  => 'utf-8',
			'locale'    => 'en',
			'direction' => 'ltr',
			'mime'      => 'text/html'
		];

		$this->response = \Snappy::response()->output();
	}

	/**
	 * Test instance management
	 */
	public function test_instance_management() {
		$key = Snappy::getInstanceKey($this->class, $this->args);

		$instance1 = Snappy::newInstance($this->class, $this->args);
		$instance2 = Snappy::get($this->scope, $this->args);
		$instance3 = Snappy::get($this->scope, $this->args);
		$instance4 = Snappy::get($this->scope, 'bar');

		// Check all instance calls are of the same class
		$this->assertContainsOnlyInstancesOf(
			\Snappy\Lib\Document::class,
			[
				$instance1,
				$instance2,
				$instance3,
				$instance4
			],
			"All instances calls are of \\Snappy\\Lib\\Document::class"
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
		$log = Snappy::getLog("instance/{$this->class}/{$key}/calls");
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
			"Value prepended to 'foo' should be 'baz' for 'baz bar', instead '$result'"
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
			"Value prepended to 'foo' should be 'qux' for 'baz bar qux', instead '$result'"
		);
	}
}