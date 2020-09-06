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

namespace Deft\Lib\Storage\Relational;

use Deft\Lib\Helper;
use Deft\Lib\Log;
use Deft\Lib\Sanitize;
use Deft\Lib\Storage\Relational;
use Deft\Lib\Watchdog;

class Sql extends Relational {
	var $args = array();
	var $connected = null;
	var $link = false;
	var $statement = '';
	var $database = false;
	var $resource = false;

	/**
	 * Database constructor.
	 *
	 * @param array $args
	 */
	function __construct ($args = array(), $class = __CLASS__) {
		$this->args = self::getArgs($args);

		switch ($this->args['driver']) {
			case 'sqlite' :
				$this->args['dsn'] = $this->args['driver']
				                     . ':' . $this->args['dbname'];
				break;

			default :
				$this->args['dsn'] = $this->args['driver']
				                     . ':host='. $this->args['host']
				                     . ';dbname=' . $this->args['dbname']
				                     . ';port=' . $this->args['port'];
				break;
		}

		if (version_compare(PHP_VERSION, '5.3.6', '>=')) {
			$this->args['dsn'] .= ';charset=utf8';
		}

		try {
			$this->link = new \PDO($this->args['dsn'], $this->args['username'], $this->args['password']);
			$this->link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		}
		catch (\PDOException $e) {
			$this->error = $e;
		}
		$this->connected = ($this->link ? true : false);

		parent::__construct($this->arg, $class);
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
			'driver'       => $c->get('storage.relational.sql.driver'),
			'host'         => $c->get('storage.relational.sql.hostname', '127.0.0.1'),
			'username'     => $c->get('storage.relational.sql.username'),
			'password'     => $c->get('storage.relational.sql.password'),
			'name'         => $c->get('storage.relational.sql.name'),
			'table_prefix' => $c->get('storage.relational.sql.table.prefix'),
			'port'         => $c->get('storage.relational.sql.port')
		), $args);

		return $args;
	}

	/**
	 * @return Exception|\PDOException
	 */
	public function lastError () {
		return $this->error;
	}

	/**
	 * @return bool
	 */
	public function isConnected () {
		return $this->connected;
	}

	/**
	 * @param null $str
	 *
	 * @return string|string[]|null
	 */
	public function addTablePrefix($str = NULL) {
		return str_replace('#_', $this->args['table_prefix'], $str);
	}

	/**
	 * @return string
	 */
	public function sql ( /*polymorphic*/) {
		if (!func_num_args()) {
			return;
		}

		$values = func_get_args();
		$statement  = $this->addTablePrefix(array_shift($values));
		if (!count($values)) {
			return $statement;
		} else {
			$db     =& $this->link;
			$result = preg_replace_callback(
				'#\\?#',
				function($match) use ($db, &$values) {
					if (empty($values)) {
						\Deft::error('Statement has missing parameters.');
					}
					$value = array_shift($values);

					if (is_null($value)) {
						return 'NULL';
					}
					if (true === $value) {
						return 'true';
					}
					if (false === $value) {
						return 'false';
					}
					if (is_numeric($value)) {
						return $value;
					}

					return $db->quote($value);
				},
				$statement
			);

			return $result;
		}
	}

	/**
	 * @return \PDOStatement
	 */
	public function query ( /*polymorphic*/) {
		if (!$this->isConnected()) {
			return false;
		}

		if (!func_num_args()) {
			return;
		}

		$args = func_get_args();

		// New query
		if (is_string($args[0])) {

			// Process conditional query
			if (count($args) > 1) {
				$this->statement = call_user_func_array(array($this, 'sql'), $args);
			}

			// Non-conditional query
			else {
				$this->statement = $this->addTablePrefix($args[0]);
			}

			// Debug timer
			if (DEFT_DEBUG > 0) {
				$start = Helper::getMicroTime();
			}

			// Execute query
			try {
				$this->resource = $this->link->query($this->statement);
			}
			catch (\PDOException $e) {
				\Deft::log()->add($e->getCode() . ': ' . $e->getMessage(), $this->getStack(), Log::ERROR);
			}

			// TODO: This is messing up due to the getArgs() != to Deft::storage() args
//			if (DEFT_DEBUG > 0) {
//				$statement = Sanitize::forText($this->statement);
//				$entry = array(
//					'time'  => Helper::getMoment($start),
//					'statement' => $statement
//				);
//
//				\Deft::stack($this->stack . '/statements', $entry);
//
//				switch (DEFT_DEBUG) {
//					case 2 :
//						if (strpos($statement, 'SELECT') === 0) {
//							$statement = 'EXPLAIN ' . $this->statement;
//
//							try {
//								$resource = $this->link->query($statement);
//							}
//							catch (\PDOException $e) {
//								\Deft::log()->add($e->getMessage(), $e->getCode(), 'db');
//							}
//
//							if ($resource) {
//								$entry = array(
//									'statement'   => Sanitize::forText($statement),
//									'explain' => $resource->fetchAll(PDO::FETCH_ASSOC)
//								);
//
//								\Deft::stack($this->stack . '/statements', $entry);
//							}
//						}
//						break;
//				}
//			}
		}

		// Exisiting statement
		elseif ($this->resource instanceof \PDOStatement)
			$this->resource = $args[0];

		// Error
		if (!$this->resource)
			return NULL;

		return $this->resource;
	}

	/**
	 * @param null $table
	 * @param array $args
	 *
	 * @return int
	 */
	public function insert ($table = null, $args = array()) {
		if (!is_string($table) or !is_array($args) or !count($args)) {
			return;
		}

		$table = $this->addTablePrefix($table);

		$statement = "INSERT INTO `" . $table . "` "
		. "( `" . implode("`,`", array_keys($args)) . "` ) "
		. "VALUES( " . preg_replace('/, $/', '', str_repeat('?, ', count($args))) . " )";

		$array = array_values($args);
		array_unshift($array, $statement);

		call_user_func_array(array($this, 'query'), $array);

		return $this->getInsertId();
	}

	/**
	 * @param null $table
	 * @param array $args
	 * @param array $conditonal
	 *
	 * @return int
	 */
	public function update ($table = null, $args = array(), $conditonal = array()) {
		if (!is_string($table) or !is_array($args) or !count($args) or !count($conditonal)) {
			return;
		}

		$table = $this->addTablePrefix($table);

		$set = array();
		foreach ($args AS $arg => $value) {
			$set[] = "`" . $arg . "` = ?";
		}

		$where = array();
		foreach ($conditonal AS $arg => $value) {
			$where[] = "`" . $arg . "` = ?";
		}

		$statement = "UPDATE `" . $table . "` "
		. "SET " . implode(' AND ', $set) . " "
		. "WHERE ( " . implode(' AND ', $where) . " );";

		$array = array_merge(array_values($args), array_values($conditonal));
		array_unshift($array, $statement);

		call_user_func_array(array($this, 'query'), $array);

		return $this->affectedRows();
	}

	/**
	 * @param null $table
	 * @param array $args
	 *
	 * @return int
	 */
	public function delete ($table = null, $args = array()) {
		if (!is_string($table) or !is_array($args) or !count($args)) {
			return;
		}

		$table = $this->addTablePrefix($table);

		$where = array();
		foreach ($args AS $arg => $value) {
			$where[] = "`" . $arg . "` = ?";
		}

		$statement = "DELETE FROM `" . $table . "` "
		. "WHERE ( " . implode(' AND ', $where) . " );";

		$array = array_values($args);
		array_unshift($array, $statement);

		call_user_func_array(array($this, 'query'), $array);

		return $this->affectedRows();
	}

	/**
	 * @param int $index
	 *
	 * @return mixed
	 */
	public function getField ($index = 0) {
		if (!$this->isConnected())
			return false;

		if (!$this->resource)
			return NULL;

		if ($this->resource instanceof \PDOStatement) {
			$row = $this->resource->fetch(\PDO::FETCH_NUM);

			return $row[$index];
		}

		return;
	}

	/**
	 * @return array
	 */
	public function getRow ( /*polymorphic*/) {
		if (func_num_args())
			call_user_func_array(array($this, 'query'), func_get_args());
		if ($this->resource instanceof \PDOStatement)
			return $this->resource->fetch(\PDO::FETCH_ASSOC);

		return;
	}

	/**
	 * @return array
	 */
	public function getRows ( /*polymorphic*/) {
		if (func_num_args())
			call_user_func_array(array($this, 'query'), func_get_args());
		if ($this->resource instanceof \PDOStatement)
			return $this->resource->fetchAll(\PDO::FETCH_ASSOC);

		return;
	}

	/**
	 * @return int
	 */
	public function numRows ( /*polymorphic*/) {
		if (func_num_args())
			call_user_func_array(array($this, 'query'), func_get_args());
		if ($this->resource instanceof \PDOStatement)
			return $this->resource->rowCount();

		return;
	}

	/**
	 * @return bool
	 */
	public function affectedRows () {
		if (func_num_args())
			call_user_func_array(array($this, 'query'), func_get_args());
		if ($this->resource instanceof \PDOStatement)
			return $this->resource->rowCount();

		return;
	}

	/**
	 * @return int
	 */
	public function getInsertId () {
		if (!$this->isConnected()) {
			return false;
		}

		return $this->link->lastInsertId();
	}

	/**
	 * @return string
	 */

	public function __toString () {
		return $this->statement;
	}
}