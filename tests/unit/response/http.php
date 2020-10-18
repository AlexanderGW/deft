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
 * Class DeftUnitResponseHttpTest
 *
 * @group unit.response.http
 */

class DeftTestUnitResponseHttp extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->response = Deft::response([
			'type' => 'http'
		]);
	}

	/**
	 * Test HTTP headers
	 *
	 * @covers \Deft\Lib\Response\Http::header
	 */
	public function test_response_http_header() {

		// Check setting a header
		$result = $this->response->header('foo', 'bar');

		$this->assertTrue(
			$result,
			"Set header 'foo' to 'bar' should return TRUE"
		);

		// Check getting a header
		$result = $this->response->header('foo');

		$this->assertEquals(
			'bar',
			$result,
			"Getting header 'foo' should return 'bar', instead '$result'"
		);

		// Check clearing header returns NULL
		$result = $this->response->header('foo', NULL);

		$this->assertNull(
			$result,
			"Clearing header should return NULL, instead '$result'"
		);

		// Check that invalid header is NULL
		$result = $this->response->header('foo');

		$this->assertNull(
			$result,
			"An invalid header should return NULL"
		);
	}

	/**
	 * Test location
	 *
	 * @depends test_response_http_header
	 *
	 * @covers \Deft\Lib\Response\Http::location
	 */
	public function test_response_http_location() {

		// Check setting location is acknowledged
		$result = $this->response->location('#foo');

		$this->assertTrue(
			$result,
			"Setting location should return TRUE, instead $result"
		);

		// Check 'Location' header exists, prefixed with  DEFT_URL
		$result = $this->response->header('Location');

		$this->assertEquals(
			DEFT_URL . '#foo',
			$result,
			"Getting header 'Location' should return 'DEFT_URL#foo', instead '$result'"
		);

		// Check 'beforeResponseOutput' event action was set
		$result = \Deft::event()->get('beforeResponseOutput');

		$this->assertEquals(
			'\Deft\Lib\Response\Http::event__responseOutput',
			$result[999][0][0],
			"Check if action 'event__responseOutput' was added to event 'beforeResponseOutput'"
		);
	}

	/**
	 * Test location
	 *
	 * @depends test_response_http_header
	 *
	 * @covers \Deft\Lib\Response\Http::status
	 */
	public function test_response_http_status() {

		// Tell the world you're a TRUE teapot
		$result = \Deft::response()->status(418);

		$this->assertTrue(
			$result,
			"You should be a teapot, instead '$result'"
		);

		// Invalid HTTP code returns FALSE
		$result = \Deft::response()->status(999);

		$this->assertFalse(
			$result,
			"Invalid HTTP code should return FALSE, instead '$result'"
		);
	}
}