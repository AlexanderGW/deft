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
 * Class SnappyUnitResponseHttpJsonTest
 *
 * @group unit.response.http.json
 */

class SnappyUnitResponseHttpJsonTest extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->response = Snappy::response([
			'type' => 'http.json'
		]);
	}

	/**
	 * Test HTTP headers
	 *
	 * @covers \Snappy\Lib\Response\Http\Json::header
	 */
//	public function test_response_http_json_buffer() {
//
//
//	}

	/**
	 * Test location
	 *

	 *
	 * @covers \Snappy\Lib\Response\Http\Json::output
	 */
	public function test_response_http_json_output() {

		// Check empty output is JSON NULL (N;)
		$output = $this->response->output();
		$this->assertEquals(
			'N;',
			$output,
			"Empty JSON buffer should be 'N;', instead '$output'"
		);

		// Check 'Content-type' is text/json
		$result = $this->response->header('Content-type');

		$this->assertEquals(
			'text/json',
			$result,
			"The HTTP 'Content-type' header should be 'text/json', instead '$result'"
		);

		// Check 'beforeResponseOutput' event action was set
		$result = \Snappy::event()->get('beforeResponseOutput');

		$this->assertEquals(
			'\Snappy\Lib\Response\Http::event__responseOutput',
			$result[999][0][0],
			"Setting location should return TRUE, instead $result"
		);
	}
}