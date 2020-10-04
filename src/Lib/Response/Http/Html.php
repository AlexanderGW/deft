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

namespace Deft\Lib\Response\Http;

use Deft\Lib\Element;
use Deft\Lib\Response\Http;
use Deft\Lib\Sanitize;

/**
 * HTML response class, default HTML5
 *
 * Class Html
 */
class Html extends Http {
	private $errors = array();
	private $robots = array(
		'index'  => true,
		'follow' => true
	);
	private $custom = array(
		'_' => array()
	);
	private $styles = array(
		'_' => array()
	);
	private $meta = array(
		'_' => array()
	);
	private $links = array(
		'_' => array()
	);
	private $scripts = array(
		'_' => array()
	);
	private $viewport = array(
		'height'  => null,
		'width'   => null,
		'initial' => null,
		'minimum' => null,
		'maximum' => null
	);
	private $eol = "\r\n";
	private $body = array();

	private $buffer = NULL;

	/**
	 * Database constructor.
	 *
	 * @param array $args
	 */
	function __construct ($args = array(), $class = __CLASS__) {
		$args = array_merge(array(
			'base'      => null,
			'encoding'  => 'utf-8',
			'locale'    => 'en',
			'direction' => 'ltr',
			'mime'      => 'text/html'
		), $args);
		$this->args = $args;
		parent::__construct($this->args, $class);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	public function setTitle ($value = null) {
		return $this->setArg('title', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	public function prependTitle ($value = null) {
		return $this->prependArg('title', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	public function appendTitle ($value = null) {
		return $this->appendArg('title', $value);
	}

	/**
	 * @return mixed|string|void
	 */
	public function getTitle () {
		return $this->getArg('title', 'documentTitle', NULL, $this->getTitleSeparator());
	}

	/**
	 * @param null $value
	 */
	public function setTitleSeparator ($value = null) {
		$this->setArg('title_separator', $value);
	}

	/**
	 * @return mixed
	 */
	public function getTitleSeparator () {
		return $this->getArg('title_separator');
	}

	/**
	 * @param null $value
	 */
	public function setDescription ($value = null) {
		$this->setArg('description', $value);
	}

	/**
	 * @param null $value
	 */
	public function prependDescription ($value = null) {
		$this->prependArg('description', $value);
	}

	/**
	 * @param null $value
	 */
	public function appendDescription ($value = null) {
		$this->appendArg('description', $value);
	}

	/**
	 * @return mixed|void
	 */
	public function getDescription () {
		return \Deft::filter()->exec('document.description', $this->getArg('description'));
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	public function setKeywords ($value = null) {
		return $this->setArg('keywords', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	public function prependKeywords ($value = null) {
		return $this->prependArg('keywords', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	public function appendKeywords ($value = null) {
		return $this->appendArg('keywords', $value);
	}

	/**
	 * @return mixed|string|void
	 */
	public function getKeywords () {
		return $this->getArg('keywords', 'documentKeywords', NULL, ', ');
	}

	/**
	 * @param null $value
	 */
	public function setBaseUrl ($value = null) {
		$this->args['base'] = $value;
	}

	/**
	 * @return mixed
	 */
	public function getBaseUrl () {
		return $this->args['base'];
	}

	/**
	 * @param bool|true $bool
	 */
	public function setIndex ($bool = true) {
		$this->robots['index'] = (bool) $bool;
	}

	/**
	 * @return mixed
	 */
	public function getIndex () {
		return $this->robots['index'];
	}

	/**
	 * @param bool|true $bool
	 */
	public function setFollow ($bool = true) {
		$this->robots['follow'] = (bool) $bool;
	}

	/**
	 * @return mixed
	 */
	public function getFollow () {
		return $this->robots['follow'];
	}

	/**
	 * @param bool|true $bool
	 */
	public function setRobots ($bool = true) {
		$this->setIndex($bool);
		$this->setFollow($bool);
	}

	/**
	 * @param null $value
	 */
	public function setEncoding ($value = null) {
		$this->args['encoding'] = $value;
	}

	/**
	 * @return mixed
	 */
	public function getEncoding () {
		return $this->args['encoding'];
	}

	/**
	 * @param null $value
	 */
	public function setLocale ($value = null) {
		$this->args['locale'] = $value;
	}

	/**
	 * @return mixed
	 */
	public function getLocale () {
		return $this->args['locale'];
	}

	/**
	 * @param null $value
	 */
	public function setDirection ($value = null) {
		$this->args['direction'] = $value;
	}

	/**
	 * @return mixed
	 */
	public function getDirection () {
		return $this->args['direction'];
	}

	/**
	 * @param $value
	 */
	public function setEOL ($value) {
		$this->eol = $value;
	}

	/**
	 * @return string
	 */
	public function getEOL () {
		return $this->eol;
	}






	/**
	 * @param int $value
	 */
	public function setVpHeight ($value = 0) {
		$this->viewport['height'] = ($value === 0 ? 'device-height' : $value);
	}

	/**
	 * @param int $value
	 */
	public function setVpWidth ($value = 0) {
		$this->viewport['width'] = ($value === 0 ? 'device-width' : $value);
	}

	/**
	 * @param null $value
	 */
	public function setVpInitial ($value = null) {
		$this->viewport['initial'] = round($value, 2);
	}

	/**
	 * @param null $value
	 */
	public function setVpMinimum ($value = null) {
		$this->viewport['minimum'] = round($value, 2);
	}

	/**
	 * @param null $value
	 */
	public function setVpMaximum ($value = null) {
		$this->viewport['maximum'] = round($value, 2);
	}







	/**
	 * @param null $content
	 * @param int $priority
	 *
	 * @return string|void
	 */
	public function addHeadCustom ($content = null, $priority = 5) {
		if (is_null($content)) {
			return;
		}

		$hash                           = Random::getMd5();
		$this->custom[$hash]            = $content;
		$this->custom['_'][$priority][] = $hash;

		return $hash;
	}

	/**
	 * @param null $hash
	 *
	 * @return bool|void
	 */
	public function removeHeadCustom ($hash = null) {
		if (is_null($hash) or strlen($hash) <> 32) {
			return;
		}

		if (array_key_exists($hash, $this->custom)) {
			unset($this->custom[$hash]);

			return true;
		}

		return false;
	}

	/**
	 * @param array $attributes
	 * @param int $priority
	 *
	 * @return bool|void
	 */
	public function addLink ($props = array(), $priority = 5) {
		if (!count($props) or !array_key_exists('rel', $props) or !array_key_exists('href', $props)) {
			return;
		}

		$hash                          = md5($props['rel'] . '-' . $props['href']);
		$this->links[$hash]            = $props;
		$this->links['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param array $attributes
	 *
	 * @return bool|void
	 */
	public function removeLink ($props = array()) {
		if (!count($props) or !array_key_exists('rel', $props) or !array_key_exists('href', $props)) {
			return;
		}

		$hash = md5($props['rel'] . '-' . $props['href']);
		if (array_key_exists($hash, $this->links)) {
			unset($this->links[$hash]);

			return true;
		}

		return false;
	}

	/**
	 * @param null $name
	 * @param string $content
	 * @param int $priority
	 *
	 * @return bool|void
	 */
	public function addMeta ($name = null, $content = '', $priority = 5) {
		if (!is_string($name) or !is_string($content)) {
			return;
		}

		$hash              = md5($name);
		$this->meta[$hash] = array(
			'name'    => $name,
			'content' => $content
		);

		$this->meta[$hash]            = \Deft::filter()->exec('documentAddMeta', $this->meta[$hash]);
		$this->meta['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param null $name
	 *
	 * @return string|null
	 */
	public function getMeta ($name = null) {
		$hash = md5($name);
		if (array_key_exists($hash, $this->meta))
			return $this->meta[$hash];
		return;
	}

	/**
	 * @param null $name
	 *
	 * @return bool|void
	 */
	public function removeMeta ($name = null) {
		if (!is_string($name)) {
			return;
		}

		$hash = md5($name);
		if (array_key_exists($hash, $this->meta)) {
			unset($this->meta[$hash]);

			return true;
		}

		return false;
	}

	/**
	 * @param null $content
	 * @param int $priority
	 * @param string $media
	 *
	 * @return bool|void
	 */
	public function addStyleContent ($content = null, $priority = 5, $media = 'all') {
		if (!is_string($content)) {
			return;
		}

		$hash                = md5($content);
		$this->styles[$hash] = array(
			'@tag'    => 'style',
			'@markup' => $content,
			'@props'  => array(
				'type'  => 'text/css',
				'media' => $media,
			)
		);

		$this->styles['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param null $path
	 * @param int $priority
	 * @param string $media
	 *
	 * @return bool|void
	 */
	public function addStyleInline ($path = null, $priority = 5, $media = 'all') {
		if (!is_string($path)) {
			return;
		}

		if (strpos($path, DEFT_PATH) !== 0) {
			$path = DEFT_PATH . DS . $path;
		}

		if (!file_exists($path)) {
			return false;
		}

		$content = file_get_contents($path);
		if ($content) {
			return $this->addStyleContent($content, $priority, $media);
		}

		return false;
	}

	/**
	 * @param null $path
	 * @param int $priority
	 * @param string $media
	 *
	 * @return bool|void
	 */
	public function addStyle ($path = null, $priority = 5, $media = 'all') {
		if (!is_string($path)) {
			return;
		}

		return $this->addLink(array(
			'href'  => $path,
			'rel'   => 'stylesheet',
			'media' => $media
		), $priority);
	}

	/**
	 * @param null $path
	 *
	 * @return bool|void
	 */
	public function removeStyle ($path = null) {
		if (!is_string($path)) {
			return;
		}

		return $this->removeLink(array(
			'href' => $path,
			'rel'  => 'stylesheet'
		));
	}

	/**
	 * @param null $content
	 * @param int $priority
	 * @param string $mime
	 *
	 * @return bool|void
	 */
	public function addScriptContent ($content = null, $priority = 5, $mime = 'text/javascript') {
		if (!is_string($content)) {
			return;
		}

		$hash                 = md5($content);
		$this->scripts[$hash] = array(
			'@tag'    => 'script',
			'@markup' => $content,
			'@props'  => array(
				'type' => $mime
			)
		);

		$this->scripts['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param null $path
	 * @param int $priority
	 * @param string $mime
	 *
	 * @return bool|void
	 */
	public function addScriptInline ($path = null, $priority = 5, $mime = 'text/javascript') {
		if (!is_string($path)) {
			return;
		}

		if (strpos($path, DEFT_PATH) !== 0) {
			$path = DEFT_PATH . DS . $path;
		}

		if (!file_exists($path)) {
			return false;
		}

		$content = file_get_contents($path);
		if ($content) {
			return $this->addScriptContent($content, $priority, $mime);
		}

		return false;
	}

	/**
	 * @param null $path
	 * @param int $priority
	 * @param string $mime
	 * @param array $attributes
	 *
	 * @return bool|void
	 */
	public function addScript ($path = null, $priority = 5, $mime = 'text/javascript', $attributes = array()) {
		if (!is_string($path)) {
			return;
		}

		$hash                 = md5($path);
		$this->scripts[$hash] = array(
			'@tag'   => 'script',
			'@props' => array_merge(array(
				'type' => $mime,
				'src'  => $path,
			), (array) $attributes)
		);

		$this->scripts[$hash]            = \Deft::filter()->exec('documentAddScript', $this->scripts[$hash]);
		$this->scripts['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param null $path
	 *
	 * @return bool|void
	 */
	public function removeScript ($path = null) {
		if (!is_string($path)) {
			return;
		}

		$hash = md5($path);
		unset($this->scripts[$hash]);

		return true;
	}






	/**
	 * @return mixed|void
	 */
	public function getHead () {
		$config = \Deft::config('document');

		if ($config->get('title') and !empty($this->getArg('title'))) {
			$this->setTitle($config->get('title'));
		}

		if (!$this->getTitleSeparator()) {
			$this->setTitleSeparator($config->get('title_separator', ', '));
		}

		if ($config->get('keywords') and !empty($this->getKeywords())) {
			$this->setKeywords($config->get('keywords'));
		}

		if ($config->get('description') and !empty($this->getDescription())) {
			$this->setDescription($config->get('description'));
		}

		if (!$this->getArg('robots')) {
			$this->setRobots($config->get('robots', true));
		}


		// TODO: This should be passed as a param, to be stored in $language, instead of storing a 'copy' on the language instance. Makes more sense!!!
		if (class_exists('\Deft\Lib\Language')) {
			$this->setEncoding(\Deft::locale()->getEncoding());
			$this->setLocale(\Deft::locale()->getIso(2));
			$this->setDirection(\Deft::locale()->getDirection());
		}

//		$this->addMeta('charset', $this->language->getEncoding());
		$this->addMeta('charset', $this->getEncoding());

		$html = '';
		if ($this->getBaseUrl()) {
			$html .= Element::html(array(
					'@tag'   => 'base',
					'@props' => array(
						'href' => $this->getBaseUrl()
					)
				), 'documentBase', true) . $this->getEOL();
		}

		$this->addMeta('generator', 'Deft ' . \Deft::VERSION);

		if ($this->getDescription()) {
			$this->addMeta('description', $this->getDescription());
		}

		if ($this->getKeywords()) {
			$this->addMeta('keywords', $this->getKeywords());
		}

		$this->addMeta('robots', ($this->getIndex() ? 'index' : 'noindex') . ',' . ($this->getFollow() ? 'follow' : 'nofollow'));

		\Deft::event()->exec('beforeDocumentGetHead');

		$viewport = array();
		if (!is_null($this->viewport['width'])) {
			$viewport[] = 'width=' . $this->viewport['width'];
		}

		if (!is_null($this->viewport['height'])) {
			$viewport[] = 'height=' . $this->viewport['height'];
		}

		if (is_null($this->viewport['initial']) and is_null($this->viewport['minimum']) and is_null($this->viewport['maximum'])) {
			$viewport[] = 'user-scalable=yes';
		} else {
			if (!is_null($this->viewport['minimum'])) {
				$viewport[] = 'minimum-scale=' . $this->viewport['minimum'];
			}

			if (!is_null($this->viewport['initial'])) {
				$viewport[] = 'initial-scale=' . $this->viewport['initial'];
			}

			if (!is_null($this->viewport['maximum'])) {
				$viewport[] = 'maximum-scale=' . $this->viewport['maximum'];
			}
		}

		$this->addMeta('viewport', implode(',', $viewport));
		unset($viewport);

		if (count($this->meta['_'])) {
			ksort($this->meta['_']);
			$this->meta = \Deft::filter()->exec('document.meta', $this->meta);
			foreach ($this->meta['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$html .= Element::html(array(
							'@tag'   => 'meta',
							'@close' => false,
							'@props' => $this->meta[$hash]
						), 'document.meta', true) . $this->getEOL();
				}
			}
		}

		if ($this->getArg('title')) {
			$html .= Element::html(array(
					'@tag'    => 'title',
					'@markup' => $this->getTitle()
				), 'documentTitle') . $this->getEOL();
		}

		if (count($this->links['_'])) {
			ksort($this->links['_']);
			$this->links = \Deft::filter()->exec('documentLinks', $this->links);

			$styles = array();
			foreach ($this->links['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$this->links[$hash]['href'] = Sanitize::forText($this->links[$hash]['href']);

					if (strpos($this->links[$hash]['href'], 'plugin/') === 0)
						$this->links[$hash]['href'] = 'Plugin/' . substr($this->links[$hash]['href'], 7);

					// Add meta..
					if (

						// If not a stylesheet..
						(!array_key_exists('rel', $this->links[$hash]) or $this->links[$hash]['rel'] != 'stylesheet')
						or (

							// or isn't in the Deft url environment...
							strpos($this->links[$hash]['href'], DEFT_PATH) !== 0

							// nor the Deft sys environment...
							and !file_exists(DEFT_PATH . DS . $this->links[$hash]['href'])
						)
					) {
						$html .= Element::html(array(
								'@tag'   => 'link',
								'@close' => false,
								'@props' => $this->links[$hash]
							), 'documentLink', true) . $this->getEOL();
					} else {
						if (!array_key_exists($this->links[$hash]['media'], $styles)) {
							$styles[$this->links[$hash]['media']] = array();
						}
						$styles[$this->links[$hash]['media']][] = $this->links[$hash];
					}
				}
			}

			if (count($styles)) {
				$styles = \Deft::filter()->exec('documentAssetCacheStyles', $styles);
				foreach ($styles as $media => $array) {
					$html .= $this->setCssAssetCache($array, $media);
				}
			}
		}

		if (count($this->styles['_'])) {
			ksort($this->styles['_']);
			$this->styles = \Deft::filter()->exec('documentHeadStyles', $this->styles);
			foreach ($this->styles['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$html .= Element::html($this->styles[$hash], 'document.style', true) . $this->getEOL();
				}
			}
		}

		if (count($this->custom['_'])) {
			ksort($this->custom['_']);
			$this->custom = \Deft::filter()->exec('documentHeadCustom', $this->custom);
			foreach ($this->custom['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$html .= $this->custom[$hash] . $this->getEOL();
				}
			}
		}

		// TODO: Store the head in memory, only clear storage on 'add' functions

		return \Deft::filter()->exec('documentGetHead', $html);
	}

	/**
	 * @param array $files
	 */
	public function setCssAssetCache ($files = array(), $media = 'all') {
		if (($total = count($files)) !== 0) {
			$hash      = md5(serialize($files));
			$path      = DEFT_PUBLIC_ASSET_PATH . DS . 'cache';
			$path_file = $path . '/' . $hash . '.css';

			$content = '';

			if (DEFT_DEBUG === 0) {
				if (!file_exists($path_file)) {
					foreach ($files as $i => $file) {
						$content .= '/* (' . ($i + 1) . '/' . $total . ') ' . $file['href'] . " */\n";

						// Absolute path
						if (strpos($file['href'], DEFT_PATH) === 0) {
							$content .= file_get_contents($file['href']) . "\n\n";
						} // Relative to Deft path
						elseif (file_exists(DEFT_PATH . DS . $file['href'])) {
							$content .= file_get_contents(DEFT_PATH . DS . $file['href']) . "\n\n";
						}
					}

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Deft::log()->add('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Deft::log()->add('Failed to write style cache file: %1$s', $path_file);
					} else {
						file_put_contents($path_file, $content);
						return Element::html(array(
								'@tag'   => 'link',
								'@close' => false,
								'@props' => array(
									'media' => $media,
									'type'  => 'text/css',
									'rel'   => 'stylesheet',
									'href'  => str_replace(
										array(dirname(DEFT_PUBLIC_ASSET_PATH), "\\"),
										array(DEFT_URL, '/'),
										$path_file
									)
								)
							), 'document.style') . $this->getEOL();
					}
				}
			}

			$return = array();
			foreach ($files as $i => $file) {

				// Absolute path
				if (strpos($file['href'], DEFT_PATH) === 0) {
					$path_file = $path . '/' . basename($file['href']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Deft::log()->add('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Deft::log()->add('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents($file['href']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Element::html(array(
								'@tag'   => 'link',
								'@close' => false,
								'@props' => array(
									'media' => $media,
									'type'  => 'text/css',
									'rel'   => 'stylesheet',
									'href'  => str_replace(
										           array(dirname(DEFT_PATH), "\\"),
										           array(DEFT_ASSET_URL, '/'),
										           $file['href']
									           ) . '?' . time()
								), 'document.style')
						);
					}
				}

				// Relative to Deft path
				elseif (file_exists(DEFT_PATH . DS . $file['href'])) {
					$path      = DEFT_PUBLIC_ASSET_PATH;
					$path_file = $path . DS . basename($file['href']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Deft::log()->add('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Deft::log()->add('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents(DEFT_PATH . DS . $file['href']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Element::html(array(
							'@tag'   => 'link',
							'@close' => false,
							'@props' => array(
								'media' => $media,
								'type'  => 'text/css',
								'rel'   => 'stylesheet',
								'href'  => DEFT_ASSET_URL . basename($file['href']) . '?' . time()
							)
						), 'document.style');
					}
				}
			}

			return implode($this->getEOL(), $return);
		}

		return null;
	}

	/**
	 * @param array $files
	 */
	public function setJsAssetCache ($scripts = array()) {
		if (($total = count($scripts)) !== 0) {
			$hash      = md5(serialize($scripts));
			$path      = DEFT_PUBLIC_ASSET_PATH . DS . 'cache';
			$path_file = $path . '/' . $hash . '.js';

			$content = '';

			if (DEFT_DEBUG === 0) {
				if (!file_exists($path_file)) {
					foreach ($scripts as $i => $script) {
						$content .= '/* (' . ($i + 1) . '/' . $total . ') ' . $script['@props']['src'] . " */\n";

						// Absolute path
						if (strpos($script['@props']['src'], DEFT_PATH) === 0) {
							$content .= file_get_contents($script['@props']['src']) . "\n\n";
						} // Relative to Deft path
						elseif (file_exists(DEFT_PATH . DS . $script['@props']['src'])) {
							$content .= file_get_contents(DEFT_PATH . DS . $script['@props']['src']) . "\n\n";
						}
					}

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Deft::log()->add('Failed to write directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Deft::log()->add('Failed to write file: %1$s', $path_file);
					} else {
						file_put_contents($path_file, $content);
					}
				}

				return Element::html(array(
						'@tag'   => 'script',
						'@props' => array(
							'type' => 'text/javascript',
							'src'  => str_replace(
								array(dirname(DEFT_PUBLIC_ASSET_PATH), "\\"),
								array(DEFT_URL, '/'),
								$path_file
							)
						),
					), 'document.script') . $this->getEOL();
			}

			$return = array();
			foreach ($scripts as $i => $script) {
				$content .= '/* (' . ($i + 1) . '/' . $total . ') ' . $script['@props']['src'] . " */\n";

				// Absolute path
				if (strpos($script['@props']['src'], DEFT_PATH) === 0) {
					$path_file = $path . '/' . basename($script['@props']['src']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Deft::log()->add('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Deft::log()->add('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents($script['@props']['src']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Element::html(array(
							'@tag'   => 'script',
							'@props' => array(
								'type' => 'text/javascript',
								'src'  => str_replace(
									array(dirname(DEFT_PUBLIC_ASSET_PATH), "\\"),
									array(DEFT_URL, '/'),
									$script['@props']['src'] . '?' . time()
								)
							),
						), 'document.script');
					}
				}


				// Relative to Deft path
				elseif (file_exists(DEFT_PATH . DS . $script['@props']['src'])) {
					$path      = DEFT_PUBLIC_ASSET_PATH . DS;
					$path_file = $path . basename($script['@props']['src']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Deft::log()->add('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Deft::log()->add('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents(DEFT_PATH . DS . $script['@props']['src']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Element::html(array(
							'@tag'   => 'script',
							'@props' => array(
								'type' => 'text/javascript',
								'src'  => DEFT_ASSET_URL . basename($script['@props']['src']) . '?' . time()
							),
						), 'document.script');
					}
				}
			}

			return implode($this->getEOL(), $return);
		}

		return null;
	}






	/**
	 * @param null $content
	 */
	public function setBody ($content = null) {
		$this->body = array($content);
	}

	/**
	 * @param null $content
	 */
	public function prependBody ($content = null, $scope = null) {
		$pos = &$this->body;
		if (is_string($scope)) {
			$items = \Deft\Lib\Helper::explodeLevel($scope);
			foreach ($items as $item) {
				if (!array_key_exists($item, $pos))
					$pos[$item] = [];
				$pos = &$pos[$item];
			}
		}

		array_unshift($pos, $content);
	}

	/**
	 * @param null $content
	 */
	public function appendBody ($content = null, $scope = null) {
		$pos = &$this->body;
		if (is_string($scope)) {
			$items = \Deft\Lib\Helper::explodeLevel($scope);
			foreach ($items as $item) {
				if (!array_key_exists($item, $pos))
					$pos[$item] = [];
				$pos = &$pos[$item];
			}
		}

		array_push($pos, $content);
	}

	/**
	 * @return string
	 */
	public function getBody () {
		if (count($this->scripts['_'])) {
			ksort($this->scripts['_']);
			$this->scripts = \Deft::filter()->exec('document.scripts', $this->scripts);

			$scripts = array();
			foreach ($this->scripts['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$this->scripts[$hash]['@props']['src'] = array_key_exists('src', $this->scripts[$hash]['@props']) ? Sanitize::forText($this->scripts[$hash]['@props']['src']) : NULL;

					if (strpos($this->scripts[$hash]['@props']['src'], 'plugin/') === 0)
						$this->scripts[$hash]['@props']['src'] = 'Plugin/' . substr($this->scripts[$hash]['@props']['src'], 7);

					// Add script..
					if (

						// If not javascript
						(array_key_exists('type', $this->scripts[$hash]['@props'])
						 and $this->scripts[$hash]['@props']['type'] != 'text/javascript')

						// Has inline markup..
						or (!empty($this->scripts[$hash]['@markup']))

						or (

							// or isn't in the Deft url environment...
							strpos($this->scripts[$hash]['@props']['src'], DEFT_PATH) !== 0

							// nor the Deft sys environment...
							and !file_exists(DEFT_PATH . DS . $this->scripts[$hash]['@props']['src'])
						)
					) {
						$this->appendBody(Element::html(
							[
								'@tag'    => 'script',
								'@markup' => array_key_exists('@markup', $this->scripts[$hash]) ? $this->scripts[$hash]['@markup'] : NULL,
								'@props'  => array_key_exists('@props', $this->scripts[$hash]) ? $this->scripts[$hash]['@props'] : NULL
							],
							'document.script'
						) . $this->getEOL());
					} else {
						$scripts[] = $this->scripts[$hash];
					}
				}
			}

			if (count($scripts)) {
				$scripts = \Deft::filter()->exec('documentAssetCacheScripts', $scripts);
				$this->appendBody($this->setJsAssetCache($scripts));
			}
		}

		// TODO: Store the head in memory, only clear storage on 'add' functions - perhaps an 'init' event?
		\Deft::event()->exec('beforeDocumentGetBody');
		$return = \Deft::filter()->exec('documentBody', implode($this->getEOL(), $this->body));
		\Deft::event()->exec('afterDocumentGetBody');
		return $return;
	}

	/**
	 * @return bool
	 */
	public function isEmpty () {
		return !count($this->body);
	}

	/**
	 * @param null $scope
	 *
	 * @return mixed|string|void
	 */
	public function output($scope = null) {
		$this->header('Content-type', 'text/html');

		// Default template name
		if (!is_string($scope)) {
			$scope = \Deft::filter()->exec('documentOutput.template', 'template.response.html5');
		}

		$this->buffer = \Deft::capture($scope);

		\Deft::event()->exec('beforeResponseOutput');

		$content = (string)\Deft::filter()->exec('responseHttpHtmlOutput', \Deft::filter()->exec('responseOutput', $this->buffer));

		$this->header('Content-length', strlen($content));

		\Deft::event()->exec('afterResponseOutput', $content);

		// Set HTTP header()s
		parent::output();

		return $content;
	}
}

//		\Deft::response()->prependBody(sprintf(
//			Element::html(array(
//				'@tag' => 'div',
//				'@props' => array(
//					'tabindex' => 0,
//					'class' => array(
//						'deft',
//						'watchdog',
//						($level === self::ERROR ? 'err' : (($level === self::WARNING ? 'warn' : 'info')))
//					),
//					'role' => 'alert'
//				),
//				'@markup' => array(
//					array(
//						'@tag' => 'div',
//						'@markup' => array(
//							array(
//								'@tag' => 'strong',
//								'@markup' => '%1$s'
//							)
//						)
//					),
//					array(
//						'@tag' => 'div',
//						'@markup' => array(
//							array(
//								'@tag' => 'span',
//								'@markup' => '%2$s'
//							)
//						)
//					)
//				)
//			), 'response.error.template'),
//			__($phrase, $stack, $code),
//			$message
//		));