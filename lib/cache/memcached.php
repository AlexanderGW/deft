<?php

class CacheMemcached extends Snappy_Concrete {
	var $args = array();
	var $connected = null;
	var $link = false;

	function __construct ($args = array()) {
		$this->args = self::getArgs($args);

		$this->link = new Memcached();
		$this->link->addServer(
			$this->args['host'],
			$this->args['post']
		);

		parent::__construct(__CLASS__, $this->args);
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function getArgs ($args = array()) {
		$cfg =& Snappy::getCfg('config.cache.memcached');
		$args = array_merge(array(
			'host' => $cfg->get('host', '127.0.0.1'),
			'port' => $cfg->get('port', 11211)
		), $args);

		return $args;
	}

	public function getLink () {
		return $this->link;
	}
}