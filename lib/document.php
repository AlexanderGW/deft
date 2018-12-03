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

class Document {

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private static $args = array(
		'base'      => null,
		'encoding'  => 'utf-8',
		'locale'    => 'en',
		'direction' => 'ltr'
	);
	private static $errors = array();
	private static $robots = array(
		'index'  => true,
		'follow' => true
	);
	private static $custom = array(
		'_' => array()
	);
	private static $styles = array(
		'_' => array()
	);
	private static $meta = array(
		'_' => array()
	);
	private static $links = array(
		'_' => array()
	);
	private static $scripts = array(
		'_' => array()
	);
	private static $viewport = array(
		'height'  => null,
		'width'   => null,
		'initial' => null,
		'minimum' => null,
		'maximum' => null
	);
	private static $eol = "\r\n";
	private static $body = array();

	/**
	 * @name static function
	 */
	static function init () {
		if (self::$initialized) {
			return;
		}

		Event::exec('documentInit');
		self::$initialized = true;
	}

	/**
	 * @param null $message
	 * @param int $code
	 * @param null $stack
	 */
	static function setErrorMessage ($message = null, $code = 0, $stack = null) {
		$cfg   =& Snappy::getCfg();
		$limit = $cfg->get('document_max_errors', 30);
		if (count(self::$errors) >= $limit) {
			Snappy::error('Document error limit reached (%1%d)', $limit);
		}

		if (!is_string($message)) {
			return;
		}
		if (!is_string($stack)) {
			$stack = 'app';
		}
		$code = (int) $code;

		if (!array_key_exists($stack, self::$errors)) {
			self::$errors[$stack] = array();
		}
		self::$errors[$stack][] = array(
			'code'    => $code,
			'message' => $message
		);

		Document::prependBody(sprintf(
			Html::element(array(
				'tag' => 'div',
				'props' => array(
					'tabindex' => 0,
					'class' => array(
						'message',
						'error'
					),
					'role' => 'alert'
				),
				'markup' => array(
					array(
						'tag' => 'strong',
						'markup' => '%1$s'
					),
					array(
						'tag' => 'span',
						'markup' => '%2$s'
					)
				)
			), 'documentErrorTemplate'),
			__('Error: %1$s (%2$d)', $stack, $code),
			$message
		));
	}

	/**
	 * @param null $message
	 */
	static function addWarningMessage ($message = null) {
		if (!is_string($message)) {
			return;
		}

		Document::prependBody(sprintf(
			Html::element(array(
				'tag' => 'div',
				'props' => array(
					'tabindex' => 0,
					'class' => array(
						'message',
						'warning'
					),
					'role' => 'alert'
				),
				'markup' => array(
					array(
						'tag' => 'strong',
						'markup' => '%1$s'
					),
					array(
						'tag' => 'span',
						'markup' => '%2$s'
					)
				)
			), 'documentWarningTemplate'),
			__('Warning'),
			$message
		));
	}

	/**
	 * @param null $message
	 */
	static function addInfoMessage ($message = null) {
		if (!is_string($message)) {
			return;
		}

		Document::prependBody(sprintf(
			Html::element(array(
				'tag' => 'div',
				'props' => array(
					'tabindex' => 0,
					'class' => array(
						'message',
						'info'
					),
					'role' => 'alert'
				),
				'markup' => array(
					array(
						'tag' => 'strong',
						'markup' => '%1$s'
					),
					array(
						'tag' => 'span',
						'markup' => '%2$s'
					)
				)
			), 'documentInfoTemplate'),
			__('Information'),
			$message
		));
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	static function setArg ($arg = null, $value = null) {
		if (is_null($arg)) {
			return false;
		}
		self::$args[$arg] = array($value);

		return true;
	}

	/**
	 * @param null $arg
	 * @param null $value
	 *
	 * @return bool
	 */
	static function prependArg ($arg = null, $value = null) {
		if (is_null($arg)) {
			return false;
		}
		if (is_string(self::$args[$arg])) {
			self::$args[$arg] = $value . self::$args[$arg];
		} else {
			if (!is_array(self::$args[$arg])) {
				self::$args[$arg] = array();
			}

			if (is_string($value)) {
				array_unshift(self::$args[$arg], $value);
			} else {
				array_merge($value, self::$args[$arg]);
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
	static function appendArg ($arg = null, $value = null) {
		if (is_null($arg)) {
			return false;
		}
		if (is_string(self::$args[$arg])) {
			self::$args[$arg] .= $value;
		} else {
			if (!is_array(self::$args[$arg])) {
				self::$args[$arg] = array();
			}

			if (is_string($value)) {
				array_push(self::$args[$arg], $value);
			} else {
				array_merge(self::$args[$arg], $value);
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
	static function getArg ($arg = null, $filter = null, $seperator = ' ') {
		$return = null;
		if (is_string(self::$args[$arg])) {
			$return = self::$args[$arg];
		} elseif (is_array(self::$args[$arg])) {
			$return = implode($seperator, self::$args[$arg]);
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
	static function setTitle ($value = null) {
		return self::setArg('title', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	static function prependTitle ($value = null) {
		return self::prependArg('title', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	static function appendTitle ($value = null) {
		return self::appendArg('title', $value);
	}

	/**
	 * @return mixed|string|void
	 */
	static function getTitle () {
		return self::getArg('title', 'documentTitle', self::getTitleSeparator());
	}

	/**
	 * @param null $value
	 */
	static function setTitleSeparator ($value = null) {
		self::$args['title_separator'] = $value;
	}

	/**
	 * @return mixed
	 */
	static function getTitleSeparator () {
		return self::$args['title_separator'];
	}

	/**
	 * @param null $value
	 */
	static function setDescription ($value = null) {
		self::$args['description'] = $value;
	}

	/**
	 * @param null $value
	 */
	static function prependDescription ($value = null) {
		self::$args['description'] = $value . self::$args['description'];
	}

	/**
	 * @param null $value
	 */
	static function appendDescription ($value = null) {
		self::$args['description'] .= $value;
	}

	/**
	 * @return mixed|void
	 */
	static function getDescription () {
		return Filter::exec('documentDescription', self::$args['description']);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	static function setKeywords ($value = null) {
		return self::setArg('keywords', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	static function prependKeywords ($value = null) {
		return self::prependArg('keywords', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return bool
	 */
	static function appendKeywords ($value = null) {
		return self::appendArg('keywords', $value);
	}

	/**
	 * @return mixed|string|void
	 */
	static function getKeywords () {
		return self::getArg('keywords', 'documentKeywords', ', ');
	}

	/**
	 * @param null $value
	 */
	static function setBaseUrl ($value = null) {
		self::$args['base'] = $value;
	}

	/**
	 * @return mixed
	 */
	static function getBaseUrl () {
		return self::$args['base'];
	}

	/**
	 * @param bool|true $bool
	 */
	static function setIndex ($bool = true) {
		self::$robots['index'] = (bool) $bool;
	}

	/**
	 * @return mixed
	 */
	static function getIndex () {
		return self::$robots['index'];
	}

	/**
	 * @param bool|true $bool
	 */
	static function setFollow ($bool = true) {
		self::$robots['follow'] = (bool) $bool;
	}

	/**
	 * @return mixed
	 */
	static function getFollow () {
		return self::$robots['follow'];
	}

	/**
	 * @param bool|true $bool
	 */
	static function setRobots ($bool = true) {
		self::setIndex($bool);
		self::setFollow($bool);
	}

	/**
	 * @param null $value
	 */
	static function setEncoding ($value = null) {
		self::$args['encoding'] = $value;
	}

	/**
	 * @return mixed
	 */
	static function getEncoding () {
		return self::$args['encoding'];
	}

	/**
	 * @param null $value
	 */
	static function setLocale ($value = null) {
		self::$args['locale'] = $value;
	}

	/**
	 * @return mixed
	 */
	static function getLocale () {
		return self::$args['locale'];
	}

	/**
	 * @param null $value
	 */
	static function setDirection ($value = null) {
		self::$args['direction'] = $value;
	}

	/**
	 * @return mixed
	 */
	static function getDirection () {
		return self::$args['direction'];
	}

	/**
	 * @param int $value
	 */
	static function setVpHeight ($value = 0) {
		self::$viewport['height'] = ($value === 0 ? 'device-height' : $value);
	}

	/**
	 * @param int $value
	 */
	static function setVpWidth ($value = 0) {
		self::$viewport['width'] = ($value === 0 ? 'device-width' : $value);
	}

	/**
	 * @param null $value
	 */
	static function setVpInitial ($value = null) {
		self::$viewport['initial'] = round($value, 2);
	}

	/**
	 * @param null $value
	 */
	static function setVpMinimum ($value = null) {
		self::$viewport['minimum'] = round($value, 2);
	}

	/**
	 * @param null $value
	 */
	static function setVpMaximum ($value = null) {
		self::$viewport['maximum'] = round($value, 2);
	}

	/**
	 * @param null $content
	 * @param int $priority
	 *
	 * @return string|void
	 */
	static function addHeadCustom ($content = null, $priority = 5) {
		if (is_null($content)) {
			return;
		}

		$hash                           = Helper::getRandomHash();
		self::$custom[$hash]            = $content;
		self::$custom['_'][$priority][] = $hash;

		return $hash;
	}

	/**
	 * @param null $hash
	 *
	 * @return bool|void
	 */
	static function removeHeadCustom ($hash = null) {
		if (is_null($hash) or strlen($hash) <> 32) {
			return;
		}

		if (array_key_exists($hash, self::$custom)) {
			unset(self::$custom[$hash]);

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
	static function addLink ($props = array(), $priority = 5) {
		if (!count($props) or !array_key_exists('rel', $props) or !array_key_exists('href', $props)) {
			return;
		}

		$hash                          = md5($props['rel'] . '-' . $props['href']);
		self::$links[$hash]            = $props;
		self::$links['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param array $attributes
	 *
	 * @return bool|void
	 */
	static function removeLink ($props = array()) {
		if (!count($props) or !array_key_exists('rel', $props) or !array_key_exists('href', $props)) {
			return;
		}

		$hash = md5($props['rel'] . '-' . $props['href']);
		if (array_key_exists($hash, self::$links)) {
			unset(self::$links[$hash]);

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
	static function addMeta ($name = null, $content = '', $priority = 5) {
		if (!is_string($name) or !is_string($content)) {
			return;
		}

		$hash              = md5($name);
		self::$meta[$hash] = array(
			'name'    => $name,
			'content' => $content
		);

		self::$meta[$hash]            = Filter::exec('documentAddMeta', self::$meta[$hash]);
		self::$meta['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param null $name
	 *
	 * @return bool|void
	 */
	static function removeMeta ($name = null) {
		if (!is_string($name)) {
			return;
		}

		$hash = md5($name);
		if (array_key_exists($hash, self::$meta)) {
			unset(self::$meta[$hash]);

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
	static function addStyleContent ($content = null, $priority = 5, $media = 'all') {
		if (!is_string($content)) {
			return;
		}

		$hash                = md5($content);
		self::$styles[$hash] = array(
			'tag'    => 'style',
			'markup' => $content,
			'props'  => array(
				'type'  => 'text/css',
				'media' => $media,
			)
		);

		self::$styles['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param null $path
	 * @param int $priority
	 * @param string $media
	 *
	 * @return bool|void
	 */
	static function addStyleInline ($path = null, $priority = 5, $media = 'all') {
		if (!is_string($path)) {
			return;
		}

		if (strpos($path, SNAPPY_PATH) !== 0) {
			$path = SNAPPY_PATH . $path;
		}

		if (!file_exists($path)) {
			return false;
		}

		$content = file_get_contents($path);
		if ($content) {
			return self::addStyleContent($content, $priority, $media);
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
	static function addStyle ($path = null, $priority = 5, $media = 'all') {
		if (!is_string($path)) {
			return;
		}

		return self::addLink(array(
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
	static function removeStyle ($path = null) {
		if (!is_string($path)) {
			return;
		}

		return self::removeLink(array(
			'href' => $path,
			'rel'  => 'stylesheet'
		));
	}

	/**
	 * @param null $content
	 * @param int $priority
	 * @param string $type
	 *
	 * @return bool|void
	 */
	static function addScriptContent ($content = null, $priority = 5, $type = 'text/javascript') {
		if (!is_string($content)) {
			return;
		}

		$hash                 = md5($content);
		self::$scripts[$hash] = array(
			'tag'    => 'script',
			'markup' => $content,
			'props'  => array(
				'type' => $type
			)
		);

		self::$scripts['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param null $path
	 * @param int $priority
	 * @param string $type
	 *
	 * @return bool|void
	 */
	static function addScriptInline ($path = null, $priority = 5, $type = 'text/javascript') {
		if (!is_string($path)) {
			return;
		}

		if (strpos($path, SNAPPY_PATH) !== 0) {
			$path = SNAPPY_PATH . $path;
		}

		if (!file_exists($path)) {
			return false;
		}

		$content = file_get_contents($path);
		if ($content) {
			return self::addScriptContent($content, $priority, $type);
		}

		return false;
	}

	/**
	 * @param null $path
	 * @param int $priority
	 * @param string $type
	 * @param array $attributes
	 *
	 * @return bool|void
	 */
	static function addScript ($path = null, $priority = 5, $type = 'text/javascript', $attributes = array()) {
		if (!is_string($path)) {
			return;
		}

		$hash                 = md5($path);
		self::$scripts[$hash] = array(
			'tag'   => 'script',
			'props' => array_merge(array(
				'type' => $type,
				'src'  => $path,
			), (array) $attributes)
		);

		self::$scripts[$hash]            = Filter::exec('documentAddScript', self::$scripts[$hash]);
		self::$scripts['_'][$priority][] = $hash;

		return true;
	}

	/**
	 * @param null $path
	 *
	 * @return bool|void
	 */
	static function removeScript ($path = null) {
		if (!is_string($path)) {
			return;
		}

		$hash = md5($path);
		unset(self::$scripts[$hash]);

		return true;
	}

	/**
	 * @return mixed|void
	 */
	static function getHead () {
		$cfg =& Snappy::getCfg('document');

		if ($cfg->get('title') and !empty(self::getArg('title'))) {
			self::setTitle($cfg->get('title'));
		}

		if (!self::$args['title_separator']) {
			self::setTitleSeparator($cfg->get('title_separator', ', '));
		}

		if ($cfg->get('keywords') and !empty(self::getArg('keywords'))) {
			self::setKeywords($cfg->get('keywords'));
		}

		if ($cfg->get('description') and !empty(self::getArg('description'))) {
			self::setDescription($cfg->get('description'));
		}

		if (!self::getArg('robots')) {
			self::setRobots($cfg->get('robots', true));
		}

		if (class_exists('Language')) {
			self::setEncoding(Language::getEncoding());
			self::setLocale(Language::getIso(2));
			self::setDirection(Language::getDirection());
		}

		self::addMeta('charset', self::getEncoding());

		$html = '';
		if (self::getBaseUrl()) {
			$html .= Html::element(array(
					'tag'   => 'base',
					'props' => array(
						'href' => self::getBaseUrl()
					)
				), 'documentBase', true) . self::getEOL();
		}

		self::addMeta('generator', 'Snappy ' . Snappy::VERSION);

		if (self::getDescription()) {
			self::addMeta('description', self::getDescription());
		}

		if (self::getKeywords()) {
			self::addMeta('keywords', self::getKeywords());
		}

		self::addMeta('robots', (self::getIndex() ? 'index' : 'noindex') . ',' . (self::getFollow() ? 'follow' : 'nofollow'));

		Event::exec('beforeDocumentGetHead');

		$viewport = array();
		if (!is_null(self::$viewport['width'])) {
			$viewport[] = 'width=' . self::$viewport['width'];
		}

		if (!is_null(self::$viewport['height'])) {
			$viewport[] = 'height=' . self::$viewport['height'];
		}

		if (is_null(self::$viewport['initial']) and is_null(self::$viewport['minimum']) and is_null(self::$viewport['maximum'])) {
			$viewport[] = 'user-scalable=yes';
		} else {
			if (!is_null(self::$viewport['minimum'])) {
				$viewport[] = 'minimum-scale=' . self::$viewport['minimum'];
			}

			if (!is_null(self::$viewport['initial'])) {
				$viewport[] = 'initial-scale=' . self::$viewport['initial'];
			}

			if (!is_null(self::$viewport['maximum'])) {
				$viewport[] = 'maximum-scale=' . self::$viewport['maximum'];
			}
		}

		self::addMeta('viewport', implode(',', $viewport));
		unset($viewport);

		if (count(self::$meta['_'])) {
			ksort(self::$meta['_']);
			self::$meta = Filter::exec('documentMeta', self::$meta);
			foreach (self::$meta['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$html .= Html::element(array(
							'tag'   => 'meta',
							'close' => false,
							'props' => self::$meta[$hash]
						), 'documentMeta', true) . self::getEOL();
				}
			}
		}

		if (self::getArg('title')) {
			$html .= Html::element(array(
					'tag'    => 'title',
					'markup' => self::getTitle()
				), 'documentTitle') . self::getEOL();
		}

		if (count(self::$links['_'])) {
			ksort(self::$links['_']);
			self::$links = Filter::exec('documentLinks', self::$links);

			$styles = array();
			foreach (self::$links['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					self::$links[$hash]['href'] = Helper::trimAllCtrlChars(self::$links[$hash]['href']);

					// Add meta..
					if (

						// If not a stylesheet..
						(!array_key_exists('rel', self::$links[$hash]) or self::$links[$hash]['rel'] != 'stylesheet')
						or (

							// or isn't in the Snappy url dataironment...
							strpos(self::$links[$hash]['href'], SNAPPY_PATH) !== 0

							// nor the Snappy sys dataironment...
							and !file_exists(SNAPPY_PATH . self::$links[$hash]['href'])
						)
					) {
						$html .= Html::element(array(
								'tag'   => 'link',
								'close' => false,
								'props' => self::$links[$hash]
							), 'documentLink', true) . self::getEOL();
					} else {
						if (!array_key_exists(self::$links[$hash]['media'], $styles)) {
							$styles[self::$links[$hash]['media']] = array();
						}
						$styles[self::$links[$hash]['media']][] = self::$links[$hash];
					}
				}
			}

			if (count($styles)) {
				$styles = Filter::exec('documentAssetCacheStyles', $styles);
				foreach ($styles as $media => $array) {
					$html .= self::setCssAssetCache($array, $media);
				}
			}
		}

		if (count(self::$styles['_'])) {
			ksort(self::$styles['_']);
			self::$styles = Filter::exec('documentHeadStyles', self::$styles);
			foreach (self::$styles['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$html .= Html::element(self::$styles[$hash], 'documentStyle', true) . self::getEOL();
				}
			}
		}

		if (count(self::$custom['_'])) {
			ksort(self::$custom['_']);
			self::$custom = Filter::exec('documentHeadCustom', self::$custom);
			foreach (self::$custom['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					$html .= self::$custom[$hash] . self::getEOL();
				}
			}
		}

		// TODO: Store the head in memory, only clear storage on 'add' functions

		return Filter::exec('documentGetHead', $html);
	}

	/**
	 * @param array $files
	 */
	static function setCssAssetCache ($files = array(), $media = 'all') {
		if (($total = count($files)) !== 0) {
			$hash      = md5(serialize($files));
			$path      = SNAPPY_PUBLIC_ASSET_PATH . 'cache';
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
						elseif (file_exists(SNAPPY_PATH . $file['href'])) {
							$content .= file_get_contents(SNAPPY_PATH . $file['href']) . "\n\n";
						}
					}

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						Snappy::error('Failed to write style cache file: %1$s', $path_file);
					} else {
						file_put_contents($path_file, $content);
						return Html::element(array(
							'tag'   => 'link',
							'close' => false,
							'props' => array(
								'media' => $media,
								'type'  => 'text/css',
								'rel'   => 'stylesheet',
								'href'  => str_replace(
									array(dirname(SNAPPY_PUBLIC_ASSET_PATH), "\\"),
									array(SNAPPY_URL, '/'),
									$path_file
								)
							)
						), 'documentStyle') . self::getEOL();
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
						Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						Snappy::error('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents($file['href']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Html::element(array(
							'tag'   => 'link',
							'close' => false,
							'props' => array(
								'media' => $media,
								'type'  => 'text/css',
								'rel'   => 'stylesheet',
								'href'  => str_replace(
									array(dirname(SNAPPY_PATH), "\\"),
									array(SNAPPY_ASSET_URL, '/'),
									$file['href']
								) . '?' . time()
							), 'documentStyle')
						);
					}
				}

				// Relative to Snappy path
				elseif (file_exists(SNAPPY_PATH . $file['href'])) {
					$path      = SNAPPY_PUBLIC_ASSET_PATH;
					$path_file = $path . basename($file['href']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						Snappy::error('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents(SNAPPY_PATH . $file['href']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Html::element(array(
							'tag'   => 'link',
							'close' => false,
							'props' => array(
								'media' => $media,
								'type'  => 'text/css',
								'rel'   => 'stylesheet',
								'href'  => SNAPPY_ASSET_URL . basename($file['href']) . '?' . time()
							)
						), 'documentStyle');
					}
				}
			}

			return implode(self::getEOL(), $return);
		}

		return null;
	}

	/**
	 * @param array $files
	 */
	static function setJsAssetCache ($scripts = array()) {
		if (($total = count($scripts)) !== 0) {
			$hash      = md5(serialize($scripts));
			$path      = SNAPPY_PUBLIC_ASSET_PATH . 'cache';
			$path_file = $path . '/' . $hash . '.js';

			$content = '';

			if (SNAPPY_DEBUG === 0) {
				if (!file_exists($path_file)) {
					foreach ($scripts as $i => $script) {
						$content .= '/* (' . ($i + 1) . '/' . $total . ') ' . $script['props']['src'] . " */\n";

						// Absolute path
						if (strpos($script['props']['src'], SNAPPY_PATH) === 0) {
							$content .= file_get_contents($script['props']['src']) . "\n\n";
						} // Relative to Snappy path
						elseif (file_exists(SNAPPY_PATH . $script['props']['src'])) {
							$content .= file_get_contents(SNAPPY_PATH . $script['props']['src']) . "\n\n";
						}
					}

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						Snappy::error('Failed to write directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						Snappy::error('Failed to write file: %1$s', $path_file);
					} else {
						file_put_contents($path_file, $content);
					}
				}

				return Html::element(array(
					'tag'   => 'script',
					'props' => array(
						'type' => 'text/javascript',
						'src'  => str_replace(
							array(dirname(SNAPPY_PUBLIC_ASSET_PATH), "\\"),
							array(SNAPPY_URL, '/'),
							$path_file
						)
					),
				), 'documentScript') . self::getEOL();
			}

			$return = array();
			foreach ($scripts as $i => $script) {
				$content .= '/* (' . ($i + 1) . '/' . $total . ') ' . $script['props']['src'] . " */\n";

				// Absolute path
				if (strpos($script['props']['src'], SNAPPY_PATH) === 0) {
					$path_file = $path . '/' . basename($script['props']['src']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						Snappy::error('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents($script['props']['src']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Html::element(array(
							'tag'   => 'script',
							'props' => array(
								'type' => 'text/javascript',
								'src'  => str_replace(
									array(dirname(SNAPPY_PUBLIC_ASSET_PATH), "\\"),
									array(SNAPPY_URL, '/'),
									$script['props']['src'] . '?' . time()
								)
							),
						), 'documentScript');
					}
				}


				// Relative to Snappy path
				elseif (file_exists(SNAPPY_PATH . $script['props']['src'])) {
					$path      = SNAPPY_PUBLIC_ASSET_PATH;
					$path_file = $path . basename($script['props']['src']);

					// Attempt to create
					if (!is_dir($path)) {
						@mkdir($path);
						@chmod($path, 0755);
					}
					touch($path_file);

					if (!is_writable($path)) {
						Snappy::error('Failed to write style cache directory: %1$s', $path);
					} elseif (!is_writable($path_file)) {
						Snappy::error('Failed to write style cache file: %1$s', $path_file);
					} else {
						$content = file_get_contents(SNAPPY_PATH . $script['props']['src']) . "\n\n";
						file_put_contents($path_file, $content);
						$return[] = Html::element(array(
							'tag'   => 'script',
							'props' => array(
								'type' => 'text/javascript',
								'src'  => SNAPPY_ASSET_URL . basename($script['props']['src']) . '?' . time()
							),
						), 'documentScript');
					}
				}
			}

			return implode(self::getEOL(), $return);
		}

		return null;
	}

	/**
	 * @param null $content
	 */
	static function setBody ($content = null) {
		self::$body = array($content);
	}

	/**
	 * @param null $content
	 */
	static function prependBody ($content = null) {
		array_unshift(self::$body, $content);
	}

	/**
	 * @param null $content
	 */
	static function appendBody ($content = null) {
		array_push(self::$body, $content);
	}

	/**
	 * @param $value
	 */
	static function setEOL ($value) {
		self::$eol = $value;
	}

	/**
	 * @return string
	 */
	static function getEOL () {
		return self::$eol;
	}

	/**
	 * @return string
	 */
	static function getBody () {
		if (count(self::$scripts['_'])) {
			ksort(self::$scripts['_']);
			self::$scripts = Filter::exec('documentScripts', self::$scripts);

			$scripts = array();
			foreach (self::$scripts['_'] as $priority => $hashes) {
				foreach ($hashes as $hash) {
					self::$links[$hash]['props']['src'] = Helper::trimAllCtrlChars(self::$scripts[$hash]['props']['src']);

					// Add script..
					if (

						// If not javascript
						(array_key_exists('type', self::$scripts[$hash]['props']) and self::$scripts[$hash]['props']['type'] != 'text/javascript')

						// Has inline markup..
						or (!empty(self::$scripts[$hash]['markup']))

						or (

							// or isn't in the Snappy url dataironment...
							strpos(self::$scripts[$hash]['props']['src'], SNAPPY_PATH) !== 0

							// nor the Snappy sys dataironment...
							and !file_exists(SNAPPY_PATH . self::$scripts[$hash]['props']['src'])
						)
					) {
						self::appendBody(Html::element(array(
								'tag'    => 'script',
								'markup' => self::$scripts[$hash]['markup'],
								'props'  => self::$scripts[$hash]['props']
							), 'documentScript') . self::getEOL());
					} else {
						$scripts[] = self::$scripts[$hash];
					}
				}
			}

			if (count($scripts)) {
				$scripts = Filter::exec('documentAssetCacheScripts', $scripts);
				self::appendBody(self::setJsAssetCache($scripts));
			}
		}

		// TODO: Store the head in memory, only clear storage on 'add' functions - perhaps an 'init' event?
		Event::exec('beforeDocumentGetBody');
		$return = Filter::exec('documentBody', implode(self::getEOL(), self::$body));
		Event::exec('afterDocumentGetBody');
		return $return;
	}

	/**
	 * @return bool
	 */
	static function isEmpty () {
		return !count(self::$body);
	}

	/**
	 * @param null $scope
	 *
	 * @return mixed|string|void
	 */
	static function content ($scope = null) {

		// Default template name
		if (!is_string($scope)) {
			$scope = Filter::exec('documentContentTemplate', 'template.html5');
		}

		// Output
		$return = Filter::exec('documentContent', Snappy::capture($scope));

		// Ultimate document event
		Event::exec('afterDocumentContent');

		return $return;
	}
}

Event::add('init', array('Document', 'init'));
//Event::add('init', array('Document', 'content'), 9999);