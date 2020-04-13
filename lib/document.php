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

namespace Snappy\Lib;

/**
 * Document class, default HTML5
 *
 * Class Document
 */
class Document extends \Snappy_Concrete {
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

	/**
	 * Database constructor.
	 *
	 * @param array $args
	 */
	function __construct ($args = array()) {
		$args = array_merge(array(
			'base'      => null,
			'encoding'  => 'utf-8',
			'locale'    => 'en',
			'direction' => 'ltr',
			'mime'      => 'text/html'
		), $args);
		$this->args = self::getArgs($args);
		parent::__construct($this->args, __CLASS__);
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	public function setArg ($arg = null, $value = null) {
		if (is_null($arg)) {
			return false;
		}
		$this->args[$arg] = array($value);

		return true;
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	public function prependArg ($arg = null, $value = null) {
		if (is_null($arg)) {
			return false;
		}
		if (is_string($this->args[$arg])) {
			$this->args[$arg] = $value . $this->args[$arg];
		} else {
			if (!is_array($this->args[$arg])) {
				$this->args[$arg] = array($this->args[$arg]);
			}

			if (is_string($value)) {
				array_unshift($this->args[$arg], $value);
			} else {
				array_merge($value, $this->args[$arg]);
			}
		}

		return true;
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	public function appendArg ($arg = null, $value = null) {
		if (is_null($arg)) {
			return false;
		}
		if (is_string($this->args[$arg])) {
			$this->args[$arg] .= $value;
		} else {
			if (!is_array($this->args[$arg])) {
				$this->args[$arg] = array();
			}

			if (is_string($value)) {
				array_push($this->args[$arg], $value);
			} else {
				array_merge($this->args[$arg], $value);
			}
		}

		return true;
	}

	/**
	 * @param null $arg
	 * @param null $filter
	 * @param string $seperator
	 *
	 * @return mixed
	 */
	public function getArg ($arg = null, $filter = null, $seperator = ' ') {
		$return = null;
		if (is_string($this->args[$arg])) {
			$return = $this->args[$arg];
		} elseif (is_array($this->args[$arg])) {
			$return = implode($seperator, $this->args[$arg]);
		}

		if (is_string($filter)) {
			$return = Filter::exec($filter, $return);
		}

		return $return;
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
		return $this->getArg('title', 'documentTitle', $this->getTitleSeparator());
	}

	/**
	 * @param null $value
	 */
	public function setTitleSeparator ($value = null) {
		$this->args['title_separator'] = $value;
	}

	/**
	 * @return mixed
	 */
	public function getTitleSeparator () {
		return $this->args['title_separator'];
	}

	/**
	 * @param null $value
	 */
	public function setDescription ($value = null) {
		$this->args['description'] = $value;
	}

	/**
	 * @param null $value
	 */
	public function prependDescription ($value = null) {
		$this->args['description'] = $value . $this->args['description'];
	}

	/**
	 * @param null $value
	 */
	public function appendDescription ($value = null) {
		$this->args['description'] .= $value;
	}

	/**
	 * @return mixed|void
	 */
	public function getDescription () {
		return Filter::exec('document.description', $this->args['description']);
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
		return $this->getArg('keywords', 'documentKeywords', ', ');
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

		$this->meta[$hash]            = Filter::exec('documentAddMeta', $this->meta[$hash]);
		$this->meta['_'][$priority][] = $hash;

		return true;
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

		if (strpos($path, SNAPPY_PATH) !== 0) {
			$path = SNAPPY_PATH . DS . $path;
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

		if (strpos($path, SNAPPY_PATH) !== 0) {
			$path = SNAPPY_PATH . DS . $path;
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

		$this->scripts[$hash]            = Filter::exec('documentAddScript', $this->scripts[$hash]);
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
		$config =& \Snappy::config('document');

		if ($config->get('title') and !empty($this->getArg('title'))) {
			$this->setTitle($config->get('title'));
		}

		if (!$this->args['title_separator']) {
			$this->setTitleSeparator($config->get('title_separator', ', '));
		}

		if ($config->get('keywords') and !empty($this->getArg('keywords'))) {
			$this->setKeywords($config->get('keywords'));
		}

		if ($config->get('description') and !empty($this->getArg('description'))) {
			$this->setDescription($config->get('description'));
		}

		if (!$this->getArg('robots')) {
			$this->setRobots($config->get('robots', true));
		}


		// TODO: This should be passed as a param, to be stored in $language, instead of storing a 'copy' on the language instance. Makes more sense!!!
		if (class_exists('\Snappy\Lib\Language')) {
			$this->setEncoding(\Snappy::locale()->getEncoding());
			$this->setLocale(\Snappy::locale()->getIso(2));
			$this->setDirection(\Snappy::locale()->getDirection());
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

		$this->addMeta('generator', 'Snappy ' . \Snappy::VERSION);

		if ($this->getDescription()) {
			$this->addMeta('description', $this->getDescription());
		}

		if ($this->getKeywords()) {
			$this->addMeta('keywords', $this->getKeywords());
		}

		$this->addMeta('robots', ($this->getIndex() ? 'index' : 'noindex') . ',' . ($this->getFollow() ? 'follow' : 'nofollow'));

		Event::exec('beforeDocumentGetHead');

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
			$this->meta = Filter::exec('document.meta', $this->meta);
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
			$this->links = Filter::exec('documentLinks', $this->links);

			$styles = array();
			foreach ($this->links['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$this->links[$hash]['href'] = Sanitize::forText($this->links[$hash]['href']);

					// Add meta..
					if (

						// If not a stylesheet..
						(!array_key_exists('rel', $this->links[$hash]) or $this->links[$hash]['rel'] != 'stylesheet')
						or (

							// or isn't in the Snappy url environment...
							strpos($this->links[$hash]['href'], SNAPPY_PATH) !== 0

							// nor the Snappy sys environment...
							and !file_exists(SNAPPY_PATH . DS . $this->links[$hash]['href'])
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
				$styles = Filter::exec('documentAssetCacheStyles', $styles);
				foreach ($styles as $media => $array) {
					$html .= $this->setCssAssetCache($array, $media);
				}
			}
		}

		if (count($this->styles['_'])) {
			ksort($this->styles['_']);
			$this->styles = Filter::exec('documentHeadStyles', $this->styles);
			foreach ($this->styles['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$html .= Element::html($this->styles[$hash], 'document.style', true) . $this->getEOL();
				}
			}
		}

		if (count($this->custom['_'])) {
			ksort($this->custom['_']);
			$this->custom = Filter::exec('documentHeadCustom', $this->custom);
			foreach ($this->custom['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$html .= $this->custom[$hash] . $this->getEOL();
				}
			}
		}

		// TODO: Store the head in memory, only clear storage on 'add' functions

		return Filter::exec('documentGetHead', $html);
	}

	/**
	 * @param array $files
	 */
	public function setCssAssetCache ($files = array(), $media = 'all') {
		if (($total = count($files)) !== 0) {
			$hash      = md5(serialize($files));
			$path      = SNAPPY_PUBLIC_ASSET_PATH . DS . 'cache';
			$path_file = $path . '/' . $hash . '.css';

			$content = '';

			if (SNAPPY_DEBUG === 0) {
				if (!file_exists($path_file)) {
					foreach ($files as $i => $file) {
						$content .= '/* (' . ($i + 1) . '/' . $total . ') ' . $file['href'] . " */\n";

						// Absolute path
						if (strpos($file['href'], SNAPPY_PATH) === 0) {
							$content .= file_get_contents($file['href']) . "\n\n";
						} // Relative to Snappy path
						elseif (file_exists(SNAPPY_PATH . DS . $file['href'])) {
							$content .= file_get_contents(SNAPPY_PATH . DS . $file['href']) . "\n\n";
						}
					}

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Snappy::error('Failed to write style cache file: %1$s', $path_file);
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
									array(dirname(SNAPPY_PUBLIC_ASSET_PATH), "\\"),
									array(SNAPPY_URL, '/'),
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
				if (strpos($file['href'], SNAPPY_PATH) === 0) {
					$path_file = $path . '/' . basename($file['href']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Snappy::error('Failed to write style cache file: %1$s', $path_file);
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
									array(dirname(SNAPPY_PATH), "\\"),
									array(SNAPPY_ASSET_URL, '/'),
									$file['href']
								) . '?' . time()
							), 'document.style')
						);
					}
				}

				// Relative to Snappy path
				elseif (file_exists(SNAPPY_PATH . DS . $file['href'])) {
					$path      = SNAPPY_PUBLIC_ASSET_PATH . DS;
					$path_file = $path . basename($file['href']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Snappy::error('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents(SNAPPY_PATH . DS . $file['href']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Element::html(array(
							'@tag'   => 'link',
							'@close' => false,
							'@props' => array(
								'media' => $media,
								'type'  => 'text/css',
								'rel'   => 'stylesheet',
								'href'  => SNAPPY_ASSET_URL . basename($file['href']) . '?' . time()
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
			$path      = SNAPPY_PUBLIC_ASSET_PATH . DS . 'cache';
			$path_file = $path . '/' . $hash . '.js';

			$content = '';

			if (SNAPPY_DEBUG === 0) {
				if (!file_exists($path_file)) {
					foreach ($scripts as $i => $script) {
						$content .= '/* (' . ($i + 1) . '/' . $total . ') ' . $script['@props']['src'] . " */\n";

						// Absolute path
						if (strpos($script['@props']['src'], SNAPPY_PATH) === 0) {
							$content .= file_get_contents($script['@props']['src']) . "\n\n";
						} // Relative to Snappy path
						elseif (file_exists(SNAPPY_PATH . DS . $script['@props']['src'])) {
							$content .= file_get_contents(SNAPPY_PATH . DS . $script['@props']['src']) . "\n\n";
						}
					}

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Snappy::error('Failed to write directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Snappy::error('Failed to write file: %1$s', $path_file);
					} else {
						file_put_contents($path_file, $content);
					}
				}

				return Element::html(array(
					'@tag'   => 'script',
					'@props' => array(
						'type' => 'text/javascript',
						'src'  => str_replace(
							array(dirname(SNAPPY_PUBLIC_ASSET_PATH), "\\"),
							array(SNAPPY_URL, '/'),
							$path_file
						)
					),
				), 'document.script') . $this->getEOL();
			}

			$return = array();
			foreach ($scripts as $i => $script) {
				$content .= '/* (' . ($i + 1) . '/' . $total . ') ' . $script['@props']['src'] . " */\n";

				// Absolute path
				if (strpos($script['@props']['src'], SNAPPY_PATH) === 0) {
					$path_file = $path . '/' . basename($script['@props']['src']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Snappy::error('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents($script['@props']['src']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Element::html(array(
							'@tag'   => 'script',
							'@props' => array(
								'type' => 'text/javascript',
								'src'  => str_replace(
									array(dirname(SNAPPY_PUBLIC_ASSET_PATH), "\\"),
									array(SNAPPY_URL, '/'),
									$script['@props']['src'] . '?' . time()
								)
							),
						), 'document.script');
					}
				}


				// Relative to Snappy path
				elseif (file_exists(SNAPPY_PATH . DS . $script['@props']['src'])) {
					$path      = SNAPPY_PUBLIC_ASSET_PATH;
					$path_file = $path . basename($script['@props']['src']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						\Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						\Snappy::error('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents(SNAPPY_PATH . DS . $script['@props']['src']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Element::html(array(
							'@tag'   => 'script',
							'@props' => array(
								'type' => 'text/javascript',
								'src'  => SNAPPY_ASSET_URL . basename($script['@props']['src']) . '?' . time()
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
	public function prependBody ($content = null) {
		array_unshift($this->body, $content);
	}

	/**
	 * @param null $content
	 */
	public function appendBody ($content = null) {
		array_push($this->body, $content);
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
	 * @return string
	 */
	public function getBody () {
		if (count($this->scripts['_'])) {
			ksort($this->scripts['_']);
			$this->scripts = Filter::exec('document.scripts', $this->scripts);

			$scripts = array();
			foreach ($this->scripts['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$this->links[$hash]['@props']['src'] = Sanitize::forText($this->scripts[$hash]['@props']['src']);

					// Add script..
					if (

						// If not javascript
						(array_key_exists('type', $this->scripts[$hash]['@props'])
						 and $this->scripts[$hash]['@props']['type'] != 'text/javascript')

						// Has inline markup..
						or (!empty($this->scripts[$hash]['@markup']))

						or (

							// or isn't in the Snappy url environment...
							strpos($this->scripts[$hash]['@props']['src'], SNAPPY_PATH) !== 0

							// nor the Snappy sys environment...
							and !file_exists(SNAPPY_PATH . DS . $this->scripts[$hash]['@props']['src'])
						)
					) {
						$this->appendBody(Element::html(
							[
								'@tag'    => 'script',
								'@markup' => $this->scripts[$hash]['@markup'],
								'@props'  => $this->scripts[$hash]['@props']
							],
							'document.script'
						) . $this->getEOL());
					} else {
						$scripts[] = $this->scripts[$hash];
					}
				}
			}

			if (count($scripts)) {
				$scripts = Filter::exec('documentAssetCacheScripts', $scripts);
				$this->appendBody($this->setJsAssetCache($scripts));
			}
		}

		// TODO: Store the head in memory, only clear storage on 'add' functions - perhaps an 'init' event?
		Event::exec('beforeDocumentGetBody');
		$return = Filter::exec('documentBody', implode($this->getEOL(), $this->body));
		Event::exec('afterDocumentGetBody');
		return $return;
	}

	/**
	 * @return bool
	 */
	public function isEmpty () {
		return !count($this->body);
	}

	/**
	 *
	 */
	static public function json($content) {
		header('Content-type: text/json');
		header('Content-length: ' . strlen($content));
		die($content);
	}

	/**
	 * @param null $scope
	 *
	 * @return mixed|string|void
	 */
	public function output($scope = null) {

		// Default template name
		if (!is_string($scope)) {
			$scope = Filter::exec('documentOutput.template', 'template.html5');
		}

		// Output
		$value = Filter::exec('documentOutput', \Snappy::capture($scope));

		// Ultimate document event
		Event::exec('afterDocumentContent');

		return $value;
	}
}