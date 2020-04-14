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
 * Class SnappyIntegrationEventTest
 *
 * @group integration.event
 */
class SnappyIntegrationEventTest extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Test event management
	 */
	public function test_event_management() {

		// Execute an invalid event
		$result = \Snappy::event()->exec('foo');

		$this->assertFalse(
			$result,
			"Invalid event 'foo' did not return FALSE"
		);

		// Set an event action, to change the response title
		$event = Snappy::event()->set('foo', function() {
			\Snappy::response()->setTitle('EVENT1');
		}, 999);

		// Check that event set returned TRUE
		$this->assertTrue(
			$event,
			"Seting event 'foo' did not return TRUE"
		);

		// Check that we can get the event entry
		$result = \Snappy::event()->get('foo');
		$count = count($result);

		$this->assertCount(
			1,
			$result,
			"Should return 1 event entry, instead $count"
		);

		// Check that the event executed something
		$result = \Snappy::event()->exec('foo');

		$this->assertTrue(
			$result,
			"Executing event 'foo' did not return TRUE"
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

		// Check that the response title was set
		$result = \Snappy::response()->getTitle();

		$this->assertEquals(
			'EVENT1',
			$result,
			"Snappy response title was not changed to 'EVENT1', instead '$result'"
		);

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

		// Event execution, with no valid callables, returns FALSE
		\Snappy::event()->set('foo', 'bar');
		$result = \Snappy::event()->exec('foo');

		$this->assertFalse(
			$result,
			"Executing invalid event action 'bar' did not return FALSE"
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
}