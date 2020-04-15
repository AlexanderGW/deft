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
 * Class SnappyUnitFilterTest
 *
 * @group unit.filter
 */

class SnappyUnitFilterTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Test setting filter action
	 *
	 * @covers \Snappy\Lib\Filter::add
	 */
	public function test_filter_add() {

		// Set an filter action, to change the response title
		$filter = Snappy::filter()->add('foo', function(){}, 999);

		// Check that filter set returned TRUE
		$this->assertTrue(
			$filter,
			"Setting filter 'foo' did not return TRUE"
		);
	}

	/**
	 * Test getting filter action
	 *
	 * @depends test_filter_add
	 *
	 * @covers \Snappy\Lib\Filter::get
	 */
	public function test_filter_get() {

		// Check that we can get the filter entry
		$result = \Snappy::filter()->get('foo');
		$count = count($result);

		$this->assertCount(
			1,
			$result,
			"Should return 1 filter entry, instead $count"
		);

		//TODO: Test 'return' key on log
	}

	/**
	 * Test filter clearing
	 *
	 * @depends test_filter_get
	 *
	 * @covers \Snappy\Lib\Filter::clear
	 */
	public function test_filter_clear() {

		// Check filter clear returns TRUE
		$result = \Snappy::filter()->clear('foo');

		$this->assertTrue(
			$result,
			"Clearing filter, did not return TRUE"
		);

		// Clear all actions for an filter, returns empty list
		$result = \Snappy::filter()->get('foo');
		$count = count($result);

		$this->assertCount(
			0,
			$result,
			"Filter clear should return 0 entries, instead $count"
		);

		// Clear an filter action
		\Snappy::filter()->clear('foo', 'bar');
		$result = \Snappy::filter()->get('foo');
		$count = count($result);

		$this->assertCount(
			0,
			$result,
			"Filter action list after clear should return 0, instead $count"
		);
	}

	/**
	 * Test filter execution
	 *
	 * @depends test_filter_clear
	 *
	 * @covers \Snappy\Lib\Filter::exec
	 */
	public function test_filter_exec() {

		// Execute a valid filter
		Snappy::filter()->add('foo', function($value = NULL) {
			return $value . ' baz';
		}, $priority = 999);

		$result = \Snappy::filter()->exec('foo', 'bar');

		$this->assertEquals(
			'bar baz',
			$result,
			"Valid filter 'foo' was not modified to 'bar baz', instead '$result'"
		);

		// Check 1 stack log entry exists
		$log = Snappy::getLog("filter/foo");
		$count = count($log);

		$this->assertCount(
			1,
			$log,
			"Stack log filter execution count returned $count, instead of 1"
		);

		// Check filter actions list has one entry at priority 999
		$this->assertCount(
			1,
			$log[0]['callbacks'][999],
			"Filter did not return one action entry, and priority 999"
		);

		// Check filter action callback is callable
		$this->assertIsCallable(
			$log[0]['callbacks'][999][0],
			"Filter action callback is not callable"
		);

		// Execute an invalid filter
		$result = \Snappy::filter()->exec('bar');

		$this->assertNull(
			$result,
			"Invalid filter 'bar' did not return FALSE"
		);

		// Valid filter execution, with an invalid callback, returns exact same value
		\Snappy::filter()->clear('foo');
		\Snappy::filter()->add('foo', 'bar');
		$result = \Snappy::filter()->exec('foo', 'baz');

		$this->assertEquals(
			'baz',
			$result,
			"Executing valid filter, with invalid action 'bar' should return unmodified value of 'baz', instead '$result'"
		);
	}
}