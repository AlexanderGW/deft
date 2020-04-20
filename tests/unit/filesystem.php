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
 * Class SnappyUnitFilesystemTest
 *
 * @group unit.filesystem
 */

class SnappyUnitFilesystemTest extends \PHPUnit\Framework\TestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->path = SNAPPY_TMP_PATH . DS . 'foo/bar';
		$this->file = $this->path . '/baz';
	}

	/**
	 * Test validating filesystem path
	 *
	 * @covers \Snappy\Lib\Filesystem::isValid
	 */
//	public function test_filesystem_isValid() {
//
//
//	}

	/**
	 * Test getting filesystem action
	 *
	 * @covers \Snappy\Lib\Filesystem::exists
	 */
	public function test_filesystem_exists() {

		// Check that invalid path returns FALSE
		$result = \Snappy::filesystem()->exists($this->file);

		$this->assertFalse(
			$result,
			"Invalid file should return FALSE, instead $result"
		);
	}

	/**
	 * Test filesystem install
	 *
	 * @covers \Snappy\Lib\Filesystem::install
	 */
	public function test_filesystem_install() {

		// Installing valid path returns TRUE
		$result = \Snappy::filesystem()->install($this->path);

		$this->assertTrue(
			$result,
			"Failed to install filesystem path"
		);
	}

	/**
	 * Test getting filesystem action
	 *
	 * @covers \Snappy\Lib\Filesystem::touch
	 */
	public function test_filesystem_touch() {

		// Check that invalid file returns FALSE
		\Snappy::filesystem()->install($this->path);
		$result = \Snappy::filesystem()->touch($this->file);

		$this->assertEquals(
			0,
			$result,
			"Creating empty file should return TRUE, instead $result"
		);
	}

	/**
	 * Test filesystem writing
	 *
	 * @covers \Snappy\Lib\Filesystem::write
	 */
	public function test_filesystem_write() {

		// Write 'qux' to a temporary file to SNAPPY_TMP_PATH
		$result =  Snappy::filesystem()->write($this->file, 'qux');
		$this->assertGreaterThan(
			0,
			$result,
			"Failed to create temporary file."
		);
	}

	/**
	 * Test filesystem reading
	 *
	 * @depends test_filesystem_write
	 *
	 * @covers \Snappy\Lib\Filesystem::read
	 */
	public function test_filesystem_read() {

		// Reading a valid file, return something other than FALSE
		$result = \Snappy::filesystem()->read($this->file);

		$this->assertEquals(
			'qux',
			$result,
			"Temporary file content should be 'qux', instead '$result'"
		);

		// Reading a valid directory, returns TRUE
		$result = \Snappy::filesystem()->read($this->path);

		$this->assertTrue(
			$result,
			"Temporary directory reading should return TRUE, instead '$result'"
		);
	}

	/**
	 * Test filesystem deletion
	 *
	 * @depends test_filesystem_write
	 *
	 * @covers \Snappy\Lib\Filesystem::delete
	 */
	public function test_filesystem_delete() {

		$parent_path = realpath($this->path.'/..');

		// Check file delete returns TRUE
		$result = \Snappy::filesystem()->delete($this->file);

		$this->assertTrue(
			$result,
			"Deleting an existing file should return TRUE, instead '$result'"
		);

		// Check non-recursive directory delete (wth content) returns FALSE
		$result = \Snappy::filesystem()->delete($parent_path, FALSE);

		$this->assertFalse(
			$result,
			"Attempt to non-recursive remove directory with content, should return FALSE, instead '$result'"
		);

		// Check not empty directory non-recursive delete, returns FALSE
		$result = \Snappy::filesystem()->delete($parent_path, TRUE);

		$this->assertTrue(
			$result,
			"Attempt to recursive remove directory with content, should return TRUE, instead $result"
		);
	}
}