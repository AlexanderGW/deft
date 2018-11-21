<?php

/**
 * Snappy, a PHP framework for PHP 5.3+
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

if( !defined( 'IN_SNAPPY' ) ) {
	header( 'HTTP/1.0 404 Not Found' );
	exit;
}

class Db extends Snappy_Concrete {
	var $args = array();
	var $connected = null;
	var $link = false;
	var $query = '';
	var $database = false;
	var $resource = false;

	/**
	 * Db constructor.
	 *
	 * @param array $args
	 */
	function __construct( $args = array() ) {
		$this->args = self::getArgs( $args );
		parent::__construct( $this->args );

		try {
			switch( $this->args['driver'] ) {
				case 'sqlite' :
					$dsn = $this->args['driver'] . ':' . $this->args['dbname'];
					break;

				default :
					$dsn = $this->args['driver'] . ':host=' . $this->args['host'] . ';dbname=' . $this->args['dbname'] . ';port=' . $this->args['port'];
					break;
			}

			if( version_compare( PHP_VERSION, '5.3.6', '>=' ) )
				$dsn .= ';charset=utf8';

			$this->link = new PDO( $dsn, $this->args['username'], $this->args['password'] );
			$this->link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch ( PDOException $e ) {
			$this->error = $e;
		}
		$this->connected = ( $this->link ? true : false );

		if( version_compare( PHP_VERSION, '5.3.6', '<' ) )
			$this->link->exec( 'SET NAMES utf8' );
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function getArgs( $args = array() ) {
		$cfg =& Snappy::getCfg();
		$args = array_merge( array(
			'driver' => $cfg->get( 'db_driver', 'mysql' ),
			'host' => $cfg->get( 'db_hostname', 'localhost' ),
			'username' => $cfg->get( 'db_username', 'root' ),
			'password' => $cfg->get( 'db_password', '' ),
			'dbname' => $cfg->get( 'db_dbname', 'mysql' ),
			'table_prefix' => $cfg->get( 'db_table_prefix' ),
			'port' => $cfg->get( 'db_port', 3306 )
		), $args );

		return $args;
	}

	/**
	 * @return Exception|PDOException
	 */
	public function lastError() {
		return $this->error;
	}

	/**
	 * @return bool
	 */
	public function isConnected() {
		return (bool)$this->connected;
	}

	/**
	 * @return string
	 */
	public function sql( /*polymorphic*/ ) {
		if( !func_num_args() )
			return;

		$values = func_get_args();
		$query = str_replace( '#_', $this->args['table_prefix'], array_shift( $values ) );
		if( !count( $values ) )
			return $query;
		else {
			$db =& $this->link;
			$result = preg_replace_callback(
				'#\\?#',
				function( $match ) use ( $db, &$values ) {
					if( empty( $values ) )
						Snappy::error( 'Query has missing parameters.' );
					$value  = array_shift( $values );

					if( is_null( $value ) )
						return 'NULL';
					if( true === $value )
						return 'true';
					if( false === $value )
						return 'false';
					if( is_numeric( $value ) )
						return $value;

					return $db->quote( $value );
				},
				$query
			);

			return $result;
		}
	}

	/**
	 * @return PDOStatement
	 */
	public function query( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;

		if( !func_num_args() )
			return;

		$args = func_get_args();

		// New query
		if( is_string( $args[0] ) ) {

			// Process conditional query
			if( count( $args ) > 1 )
				$this->query = call_user_func_array( array( $this, 'sql' ), $args );

			// Non-conditional query
			else
				$this->query = str_replace( '#_', $this->args['table_prefix'], $args[0] );

			// Debug timer
			if( SNAPPY_DEBUG > 0 )
				$start = Helper::getMicroTime();

			// Execute query
			try {
				$this->resource = $this->link->query( $this->query );
			} catch ( PDOException $e ) {
				Document::addErrorMessage( $e->getMessage(), $e->getCode(), __( 'Database' ) );
			}

			if( SNAPPY_DEBUG > 0 ) {
				$query = Helper::trimAllCtrlChars( $this->query );
				$entry = array(
					'time' => Helper::getMoment( $start ),
					'query' => $query
				);

				Snappy::log( $this->stack . '/queries', $entry );

				switch( SNAPPY_DEBUG ) {
					case 2 :
						if( strpos( $query, 'SELECT' ) === 0 ) {
							$query = 'EXPLAIN ' . $query;

							try {
								$resource = $this->link->query( $this->query );
							} catch ( PDOException $e ) {
								Document::addErrorMessage( $e->getMessage(), $e->getCode(), __( 'Database' ) );
							}

							if( $resource ) {
								$entry = array(
									'query' => $query,
									'explain' => $resource->fetchAll( PDO::FETCH_ASSOC )
								);

								Snappy::log( $this->stack . '/queries', $entry );
							}
						}
						break;
				}
			}
		}

		// Exisiting query
		else
			$this->resource = $args[0];

		// Error
		if( !$this->resource )
			return;

		return $this->resource;
	}

	/**
	 * @param null $table
	 * @param array $args
	 *
	 * @return int
	 */
	public function insert( $table = null, $args = array() ) {
		if( !is_string( $table ) or !is_array( $args ) or !count( $args ) )
			return;

		$table = str_replace( '#_', $this->args['table_prefix'], $table );

		$query = "INSERT INTO `" . $table . "` ( `" . implode( "`,`", array_keys( $args ) ) . "` ) VALUES( " . preg_replace( '/, $/', '', str_repeat( '?, ', count( $args ) ) ) . " )";

		$array = array_values( $args );
		array_unshift( $array, $query );

		call_user_func_array( array( $this, 'query' ), $array );

		return $this->getInsertId();
	}

	/**
	 * @param null $table
	 * @param array $args
	 * @param array $conditonal
	 *
	 * @return int
	 */
	public function update( $table = null, $args = array(), $conditonal = array() ) {
		if( !is_string( $table ) or !is_array( $args ) or !count( $args ) or !is_array( $args ) or !count( $conditonal ) )
			return;

		$table = str_replace( '#_', $this->args['table_prefix'], $table );

		$set = array();
		foreach( $args AS $arg => $value )
			$set[] = "`" . $arg . "` = ?";

		$where = array();
		foreach( $conditonal AS $arg => $value )
			$where[] = "`" . $arg . "` = ?";

		$query = "UPDATE `" . $table . "` SET " . implode( ' AND ', $set ) . "  WHERE ( " . implode( ' AND ', $where )  ." )";

		$array = array_merge( array_values( $args ), array_values( $conditonal ) );
		array_unshift( $array, $query );

		call_user_func_array( array( $this, 'query' ), $array );
		return $this->affectedRows();
	}

	/**
	 * @param null $table
	 * @param array $args
	 *
	 * @return int
	 */
	public function delete( $table = null, $args = array() ) {
		if( !is_string( $table ) or !is_array( $args ) or !count( $args ) )
			return;

		$table = str_replace( '#_', $this->args['table_prefix'], $table );

		$where = array();
		foreach( $args AS $arg => $value )
			$where[] = "`" . $arg . "` = ?";

		$query = "DELETE FROM `" . $table . "` WHERE ( " . implode( ' AND ', $where )  ." )";

		$array = array_values( $args );
		array_unshift( $array, $query );

		call_user_func_array( array( $this, 'query' ), $array );
		return $this->affectedRows();
	}

	/**
	 * @param int $index
	 *
	 * @return mixed
	 */
	public function getField( $index = 0 ) {
		if( !$this->isConnected() )
			return false;

		if( !$this->resource )
			return;

		if( $this->resource instanceof PDO ) {
			$row = $this->resource->fetch( PDO::FETCH_NUM );
			return $row[ $index ];
		}
		return;
	}

	/**
	 * @return array
	 */
	public function getRow( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;

		if( func_num_args() )
			call_user_func_array( array( $this, 'query' ), func_get_args() );
		if( $this->resource instanceof PDO )
			return $this->resource->fetch( PDO::FETCH_ASSOC );
		return;
	}

	/**
	 * @return array
	 */
	public function getRows( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;

		if( func_num_args() )
			call_user_func_array( array( $this, 'query' ), func_get_args() );
		if( $this->resource instanceof PDO )
			return $this->resource->fetchAll( PDO::FETCH_ASSOC );
		return;
	}

	/**
	 * @return int
	 */
	public function numRows( /*polymorphic*/ ) {
		if( !$this->isConnected() )
			return false;

		if( func_num_args() )
			call_user_func_array( array( $this, 'query' ), func_get_args() );
		if( $this->resource instanceof PDO )
			return $this->resource->rowCount();
		return;
	}

	/**
	 * @return bool
	 */
	public function affectedRows() {
		if( !$this->isConnected() )
			return false;

		if( func_num_args() )
			call_user_func_array( array( $this, 'query' ), func_get_args() );
		if( $this->resource instanceof PDO )
			return $this->resource->rowCount();
		return;
	}

	/**
	 * @return int
	 */
	public function getInsertId() {
		if( !$this->isConnected() )
			return false;

		return $this->link->lastInsertId();
	}

	/**
	 * @return string
	 */

	public function __toString() {
		return $this->query;
	}
}