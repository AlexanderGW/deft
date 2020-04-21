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
 * Class DeftUnitResponseHttpHtmlTest
 *
 * @group unit.response.http.html
 */

class TestDeftUnitResponseHttpHtml extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();

		$this->response = Deft::response([
			'type' => 'http.html'
		]);
	}

	/**
	 * Test location
	 *
	 * @covers \Deft\Lib\Response\Http\Html::output
	 */
	public function test_response_http_html_output() {

		// Check empty output has the default html5 template doctype
		$output = $this->response->output();

		$this->assertStringStartsWith(
			'<!DOCTYPE html>',
			$output,
			'Default output does not start with the HTML5 doctype'
		);

		$this->assertStringEndsWith(
			"\r\n",
			$output,
			'Default output does not end with carriage return & new line'
		);

		$this->assertStringContainsString(
			'Deft ' . Deft::VERSION,
			$output,
			"Default output contains the Deft 'Generator' meta tag header"
		);

		// Check 'Content-type' is text/html
		$result = $this->response->header('Content-type');

		$this->assertEquals(
			'text/html',
			$result,
			"The HTTP 'Content-type' header should be 'text/html', instead '$result'"
		);
	}
}