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

		// Set an event action, to change the argument of an instance (the response title)
		$event = Snappy::event()->set('foo', function() {
			\Snappy::response()->setTitle('EVENT1');
		}, 999);

		\Snappy::event()->exec('foo');

		$result = \Snappy::response()->getTitle();

		$this->assertEquals(
			'EVENT1',
			$result,
			"Snappy response title was not changed to 'EVENT1', instead '$result'"
		);
	}
}