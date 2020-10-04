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
 * Class DeftIntegrationRouteTest
 *
 * @group integration.route
 */
class TestDeftIntegrationRoute extends \PHPUnit\Framework\TestCase {
	protected function setUp(): void {
		parent::setUp();
	}

	/**
	 * Test simple routes, with no parameters
	 */
	public function test_route_simple() {
		$name = 'example.page';
		$request = 'foo/bar';
		$callback = '\Deft\Plugin\Example::content';

		// Invalid route
		$result = Deft::route()->match($request);

		$this->assertArrayHasKey(
			'name',
			$result,
			"Route match must always return key 'name'"
		);

		$this->assertNull(
			$result['name'],
			"Invalid route key 'name' should be NULL"
		);

		$this->assertArrayHasKey(
			'class',
			$result,
			"Route match must always return key 'class'"
		);

		$this->assertNull(
			$result['class'],
			"Invalid route key 'class' should be NULL"
		);

		$this->assertArrayHasKey(
			'request',
			$result,
			"Route match must always return key 'request'"
		);

		$this->assertEquals(
			$request,
			$result['request'],
			"Route match key 'request' should equal method argument"
		);

		$this->assertArrayHasKey(
			'path',
			$result,
			"Route match should return key 'path'"
		);

		$this->assertEquals(
			'',
			$result['path'],
			"Route match key 'path' should be empty string"
		);

		$this->assertArrayHasKey(
			'pattern',
			$result,
			"Route match must always return key 'pattern'"
		);

		$this->assertNull(
			$result['pattern'],
			"Invalid route key 'pattern' should be NULL"
		);

		$this->assertArrayHasKey(
			'data',
			$result,
			"Route match must always return key 'data'"
		);

		$this->assertNull(
			$result['data'],
			"Invalid route key 'data' should be NULL"
		);

		$this->assertArrayHasKey(
			'callback',
			$result,
			"Route match must always return key 'callback'"
		);

		$this->assertNull(
			$result['callback'],
			"Invalid route key 'callback' should be NULL"
		);

		$request = 'example/request';

		// Test simple route created by 'example' plugin
		$result = Deft::route()->match($request);

		$this->assertEquals(
			$name,
			$result['name'],
			"Route named 'example.page' is not named 'example.page', instead '" . $result['name'] . "'"
		);

		$this->assertEquals(
			$request,
			$result['request'],
			"Route named 'example.page' path is not '$request', instead '" . $result['request'] . "'"
		);

		$this->assertEquals(
			$callback,
			$result['callback'],
			"Route named 'example.page' path is not '$callback', instead '" . $result['callback'] . "'"
		);

		// Meta description set by the 'example.index' callback
		$meta = \Deft::response()->getMeta('plugin.example');
		$this->assertEquals(
			$meta['content'],
			'1',
			"Route callback 'example.index' should have set response meta 'plugin.example' with value '1'"
		);
	}

	/**
	 * Test dynamic routes, with parameters
	 */
	public function test_route_dynamic() {
		$name = 'test.route.dynamic';
		$path = '[a]/[bb]';
		$request = 'foo/123';

		Deft::route()->add($name, $path, array(
			'a' => '[a-z]+',
			'bb' => '\d+'
		));

		$result = Deft::route()->match($request);

		$this->assertArrayHasKey(
			'a',
			$result['data'],
			"Route match key 'data' should have key 'a'"
		);

		$this->assertEquals(
			'foo',
			$result['data']['a'],
			"Route match key 'data', key 'a', should be string 'foo', instead '" . $result['data']['a'] . "'"
		);

		$this->assertArrayHasKey(
			'bb',
			$result['data'],
			"Route match key 'data' should have key 'bb'"
		);

		$this->assertEquals(
			123,
			$result['data']['bb'],
			"Route match key 'data', key 'bb', should be number '123', instead '" . $result['data']['bb'] . "'"
		);

		// Different request data, same structure, should match the same route
		$request = 'bar/45678';

		$result = Deft::route()->match($request);

		$this->assertEquals(
			'bar',
			$result['data']['a'],
			"Route match key 'data', key 'a', should be string 'bar', instead '" . $result['data']['a'] . "'"
		);

		$this->assertEquals(
			$name,
			$result['name'],
			"New request data, same structure, should return the same route match as the previous match, instead '" . $result['name'] . "'"
		);

		// Invalid [bb] parameter value constraint, on route 'test.route.dynamic'
		$request = 'bar/baz';

		$result = Deft::route()->match($request);

		$this->assertNull(
			$result['name'],
			"Invalid route parameter value constraint should fail to match anything, instead '" . $result['name'] . "'"
		);
	}
}