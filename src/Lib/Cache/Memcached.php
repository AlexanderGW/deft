<?php

namespace Deft\Lib\Cache\Memcached;

\Deft::import('cache');

use Deft\Lib\Event;
use Deft\Lib\Filter;

class Memcached extends \Deft\Lib\Cache {
	var $args = array();
	var $connected = null;
	var $link = false;

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	/**
	 *
	 */
	public static function init() {
		if ( self::$initialized ) {
			return;
		}

		// TODO: what to do with this
		$result = \Deft::lib('cache.memcached')->get(\Deft::request()->url());
		if ($result) {
			echo $result; exit;
		}


		self::$initialized = true;
	}

	function __construct ($args = array()) {
		$this->args = self::getArgs($args);

		$this->link = new Memcached();
		$this->link->addServer(
			$this->args['host'],
			$this->args['post']
		);

		parent::__construct($this->args, __CLASS__);
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public static function getArgs ($args = array()) {
		$config = \Deft::config('config.cache.memcached');
		$args = array_merge(array(
			'host' => $config->get('host', '127.0.0.1'),
			'port' => $config->get('port', 11211)
		), $args);

		return $args;
	}

	public function getLink () {
		return $this->link;
	}

	public function get($key = null) {
//		if (!is_null($key) AND array_key_exists($key, $this->data)) {
		$key = md5($key);
		return $this->getLink()->get($key);
//		}
		return;
	}

	public function set($key = null, $value = null) {
//		if (!is_null($key)) {
		$key = md5($key);
		$this->getLink()->set($key, $value, 3);
//			var_dump($this->data);
		return $key;
//		}
		return;
	}

	public static function documentOutput($value) {
		// Cache the ouput
		if (\Deft::request()->isPost() === false) {
			$url = \Deft::request()->url();
			if (strpos($url, 'debug') === false) {
				$key = $url;
				$state = \Deft::lib('cache.memcached')->set($key, $value);
			}
//			var_dump($state);exit;
//			var_dump(Deft::cache()->get($key));exit;
		}
		return $value;
	}
}

// Process HTTP request against available route rules
\Deft::event()->set( 'init', '\Deft\Lib\Cache\Memcached::init', 20 );

Filter::add('documentOutput', '\Deft\Lib\Cache\Memcached::documentOutput', 999);