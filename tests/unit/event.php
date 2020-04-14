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
 * Class SnappyUnitEventTest
 *
 * @group unit.event
 */

class SnappyUnitEventTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Test setting event action
	 *
	 * @covers \Snappy\Lib\Event::set
	 */
	public function test_event_set() {

		// Set an event action, to change the response title
		$event = Snappy::event()->set('foo', function(){}, 999);

		// Check that event set returned TRUE
		$this->assertTrue(
			$event,
			"Setting event 'foo' did not return TRUE"
		);
	}

	/**
	 * Test getting event action
	 *
	 * @depends test_event_set
	 *
	 * @covers \Snappy\Lib\Event::get
	 */
	public function test_event_get() {

		// Check that we can get the event entry
		$result = \Snappy::event()->get('foo');
		$count = count($result);

		$this->assertCount(
			1,
			$result,
			"Should return 1 event entry, instead $count"
		);

		//TODO: Test 'return' key on log
	}

	/**
	 * Test event clearing
	 *
	 * @depends test_event_get
	 *
	 * @covers \Snappy\Lib\Event::clear
	 */
	public function test_event_clear() {

		// Check event clear returns TRUE
		$result = \Snappy::event()->clear('foo');

		$this->assertTrue(
			$result,
			"Clearing event, did not return TRUE"
		);

		// Clear all actions for an event, returns empty list
		$result = \Snappy::event()->get('foo');
		$count = count($result);

		$this->assertCount(
			0,
			$result,
			"Event clear should return 0 entries, instead $count"
		);

		// Clear an event action
		\Snappy::event()->clear('foo', 'bar');
		$result = \Snappy::event()->get('foo');
		$count = count($result);

		$this->assertCount(
			0,
			$result,
			"Event action list after clear should return 0, instead $count"
		);
	}

	/**
	 * Test event execution
	 *
	 * @depends test_event_clear
	 *
	 * @covers \Snappy\Lib\Event::exec
	 */
	public function test_event_exec() {

		// Execute a valid event
		Snappy::event()->set('foo', function() {}, $priority = 999);

		$result = \Snappy::event()->exec('foo');

		$this->assertTrue(
			$result,
			"Valid event 'foo' did not return TRUE"
		);

		// Check 1 stack log entry exists
		$log = Snappy::getLog("event/foo");
		$count = count($log);

		$this->assertCount(
			1,
			$log,
			"Stack log event execution count returned $count, instead of 1"
		);

		// Check event actions list has one entry at priority 999
		$this->assertCount(
			1,
			$log[0]['callbacks'][999],
			"Event did not return one action entry, and priority 999"
		);

		// Check event action callback is callable
		$this->assertIsCallable(
			$log[0]['callbacks'][999][0]['callback'],
			"Event action callback is not callable"
		);

		//TODO: Test 'return' key on log

		// Execute an invalid event
		$result = \Snappy::event()->exec('bar');

		$this->assertFalse(
			$result,
			"Invalid event 'bar' did not return FALSE"
		);

		// Valid event execution, with an invalid callback, returns FALSE
		\Snappy::event()->clear('foo');
		\Snappy::event()->set('foo', 'bar');
		$result = \Snappy::event()->exec('foo');

		$this->assertFalse(
			$result,
			"Executing valid event, with invalid action 'bar' did not return FALSE"
		);
	}
}