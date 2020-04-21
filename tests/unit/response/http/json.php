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
 * Class DeftUnitResponseHttpJsonTest
 *
 * @group unit.response.http.json
 */

class TestDeftUnitResponseHttpJson extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->response = Deft::response([
			'type' => 'http.json'
		]);
	}

	/**
	 * Test default JSON output
	 *
	 * @covers \Deft\Lib\Response\Http\Json::output
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
	}
}