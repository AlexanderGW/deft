<?php


namespace Deft\Lib;

// TODO: Switch to filesystem.local

class Filesystem extends \Deft_Concrete {

	/**
	 *
	 */
	public function exists($path = NULL) {
		return file_exists($path);
	}

	/**
	 *
	 */
	public function isRelative($path = NULL) {
		return (strpos(DEFT_PATH, realpath($path)) === 0);
	}

	/**
	 *
	 */
	public function makeRelative($path) {
		if (strpos($path, DEFT_PATH) === 0) {
			$path = substr($path, strlen(DEFT_PATH)+1);
			$path = str_replace('\\', '/', $path);
		}
		return $path;
	}

	/**
	 *
	 */
	public function install($path = NULL, $mode = NULL) {
		if (is_null($mode))
			$mode = 0750;
		$mode = intval($mode, 8);

//		$path = $this->makeRelative($path);

		$directories = explode('/', $path);
		$path = '';
		foreach ($directories as $i => $directory) {
			if ($i)
				$path .= '/';
			$path .= "{$directory}";
			if (!is_dir($path)) {
				if (!mkdir($path))
					return FALSE;
				chmod($path, $mode);
			}
		}

		return TRUE;
	}

	/**
	 *
	 */
	public function scan($path = NULL, $recursive = FALSE, $maxdepth = -1, $ignore = []) {
		if (is_dir($path)) {
			$array = [];
			if (!is_bool($recursive) && !is_int($recursive))
				$recursive = FALSE;

			// Items to exclude from the returned array
			if( !is_array($ignore) || !count($ignore))
				$ignore = ['.', '..'];

			$ignore = \Deft::filter()->exec('filesystemIgnore', $ignore);

			// TODO: Recursion limit not honoured

			$items = scandir($path);
			if ($items) {
				foreach ($items as $item) {
					if (!in_array($item, $ignore)) {
						$path_item = $path . '/' . $item;
						if (is_dir($path_item)) {
							if ($recursive >= 0 && ($maxdepth === -1 || ($maxdepth > 0 && $recursive < $maxdepth)))
								$array[$item] = $this->scan(
									$path_item,
									($recursive+1),
									$maxdepth,
									$ignore
								);
							else
								$array[$item] = TRUE;
						} else {
							$array[$item] = $path_item;
						}
					}
				}
			}
			return $array;
		}
		return FALSE;
	}

	/**
	 *
	 */
	public function touch($path = NULL) {
		if (!is_null($path)) {
			if (!is_dir($path) && is_writable($path)) {
				if (file_exists($path))
					touch($path);
				elseif (file_put_contents($path, NULL) === 0)
					return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 *
	 */
	public function read($path = NULL) {
		if (is_readable($path)) {
			if (is_dir($path))
				return TRUE;
			if (is_file($path))
				return file_get_contents($path);
			return FALSE;
		}
		return NULL;
	}

	/**
	 *
	 */
	public function write($path = NULL, $data = NULL) {
		if (!is_dir($path))
			return file_put_contents($path, $data);
		return NULL;
	}

	/**
	 *
	 */
	public function delete($path = NULL, $recursive = FALSE) {
		$result = FALSE;
		if (!is_dir($path) && is_file($path))
			return unlink($path);

		if (!is_bool($recursive))
			$recursive = FALSE;

//		$path = $this->makeRelative($path);

		// Recursive deletion, get all items to process
		if ($recursive) {
			$items = $this->scan($path, TRUE, 1);
			if ($items) {
				$failed = FALSE;
				foreach ($items as $key => $value) {

					// Delete file, $value is the full path
					if (is_string($value)) {
						$result = unlink($value);
						if (!$result)
							$failed = TRUE;
					}

					// Delete the content of the directory
					elseif ($value === TRUE) {
						$path_directory = $path . '/' . $key;
						if ($this->delete($path_directory, TRUE) === FALSE) {
							$failed = TRUE;
							break;
						}
					}
				}

				if ($failed)
					return FALSE;
			}
		}

		// Delete directory
		if (is_dir($path))
			$result = rmdir($path);
		return $result;
	}
}