<?php

namespace Deft\Lib\Storage\Database;

use Deft\Lib\Filter;
use Deft\Lib\Storage\Database;

class Mysql extends Database {

	/**
	 * Storage constructor.
	 *
	 * @param array $args
	 */
	function __construct ($args = [], $class = __CLASS__) {
		$this->args = self::getArgs($args);

		// Establish the PDO connection
		parent::__construct($this->args, $class);

		if (
			$this->connected
			&& version_compare(PHP_VERSION, '5.3.6', '<')
		)
			$this->link->exec(Filter::exec('onDatabaseConstructQueryUtf8', 'SET NAMES utf8'));
	}
}