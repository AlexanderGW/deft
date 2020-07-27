<?php

namespace Deft\Lib\Storage\Relational\Sql;

use Deft\Lib\Filter;
use Deft\Lib\Storage\Relational\Sql;

class Mysql extends Sql {

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

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function getArgs ($args = array()) {
		$c  = \Deft::config();
		$args = array_merge(array(
			'structure'    => 'relational',
			'type'         => 'sql',
			'driver'       => 'mysql',
			'host'         => $c->get('storage.relational.sql.mysql.hostname', '127.0.0.1'),
			'username'     => $c->get('storage.relational.sql.mysql.username', 'root'),
			'password'     => $c->get('storage.relational.sql.mysql.password'),
			'name'         => $c->get('storage.relational.sql.mysql.name', 'test'),
			'table_prefix' => $c->get('storage.relational.sql.mysql.table.prefix'),
			'port'         => $c->get('storage.relational.sql.mysql.port', 3306)
		), $args);

		return $args;
	}
}