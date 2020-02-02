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

define('SNAPPY_LOCALE_PATH', SNAPPY_PATH . 'locale' . DS);

class Language {
	const DEFAULT_LOCALE = 'en-GB';

	/**
	 * Set TRUE once init() executes.
	 *
	 * @var array
	 */
	private static $initialized = false;

	private static $args = array();

	private static $phrases = array();

	/**
	 * @param null $args
	 */
	public static function init () {
		if (self::$initialized) {
	 		return;
		}

		$config     =& \Snappy::config();
		$locales = $config->get('locales', array());

		$string = \Snappy::request()->post('locale');
		if (!$string) {
			$string = \Snappy::request()->query('locale');
		}
		if (!$string) {
			$string = Token::get('locale');
		}
		if (!$string) {
			$string = Route::getParam('locale');
		}
		if (!$string) {
			$string = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}

		if ($string !== $_SERVER['HTTP_ACCEPT_LANGUAGE']) {
			$locale = self::negotiate($string);
		} else {
			preg_match_all(
				"/([[:alpha:]]{1,8})(-([[:alpha:]|-]{1,8}))?(\s*;\s*q\s*=\s*(1\.0{0,3}|0\.\d{0,3}))?\s*(,|$)/i",
				$string,
				$matches,
				PREG_SET_ORDER
			);

			$locale = null;
			if (count($matches)) {
				$bestq = 0;
				foreach ($matches as $value) {
					$language = strtolower($value[1]);

					if (!empty($value[3])) {
						$region = $value[3];
						$label  = $language . '-' . $region;
					} else {
						$label = $language;
					}

					$q = 1.0;
					if (!empty($value[5])) {
						$q = floatval($value[5]);
					}

					if (in_array($label, $locales) && ($q > $bestq)) {
						$locale = $label;
						$bestq  = $q;
					} elseif (in_array($language, $locales) && (($q * 0.9) > $bestq)) {
						$locale = $language;
						$bestq  = ($q * 0.9);
					}
				}
			}
		}

		if (is_null($locale)) {
			$locale = $config->get('locale_default', self::DEFAULT_LOCALE);
		}

		if ($locale != self::DEFAULT_LOCALE) {
			$config = \Snappy::config('locale.' . strtolower($locale));
			if (!$config->isEmpty()) {
				$args = $config->get(0);
				if ($args) {
					self::$args = $args;
				}

				$phrases = $config->get(1);
				if ($phrases) {
					self::$phrases = $phrases;
				}
			}
		}

		if (!count(self::$args)) {
			self::$args = array(
				'direction' => 'ltr',
				'encoding'  => 'utf-8',
				'iso2'      => 'en',
				'iso3'      => 'eng',
				'locale'    => 'en-GB'
			);
		}

		setlocale(LC_ALL, $locale . '.utf8');
		Event::exec('initLanguage');
		self::$initialized = true;
	}

	/**
	 * @return string
	 */
	public static function negotiate ($locale = null) {
		if (is_string($locale) and (strlen($locale) == 2 or strlen($locale) == 5)) {
			$config     =& \Snappy::config();
			$locales = $config->get('locales', array());

			if (strlen($locale) == 5) {
				if (in_array($locale, $locales)) {
					return $locale;
				}
				$locale = substr($locale, 0, 2);
			}
			if (strlen($locale) == 2 and in_array($locale, $locales)) {
				return $locale;
			}
		}

		return self::DEFAULT_LOCALE;
	}

	/**
	 * @return string
	 */
	public static function getEncoding () {
		if (!self::$initialized) {
			self::init();
		}

		return self::$args['encoding'];
	}

	/**
	 * @param int $length
	 *
	 * @return string
	 */
	public static function getISO ($length = 2) {
		if ($length < 2 or $length > 3) {
			return null;
		}

		if (!self::$initialized) {
			self::init();
		}

		return self::$args['iso' . $length];
	}

	/**
	 * @return string
	 */
	public static function getLocale () {
		if (!self::$initialized) {
			self::init();
		}

		return self::$args['locale'];
	}

	/**
	 * @return string
	 */
	public static function getDirection () {
		if (!self::$initialized) {
			self::init();
		}

		return self::$args['direction'];
	}

	/**
	 * @return bool
	 */
	public static function isRTL () {
		if (!self::$initialized) {
			self::init();
		}

		return (self::$args['direction'] == 'rtl' ? true : false);
	}

	/**
	 * @return bool
	 */
	public static function isDefault () {
		return (self::getLocale() == self::DEFAULT_LOCALE);
	}

	/**
	 * @param null $string
	 *
	 * @return null
	 */
	public static function getPhrase ($string = null) {
		if (!self::$initialized) {
			self::init();
		}

		if (!self::isDefault()) {
			if (array_key_exists($string, self::$phrases)) {
				return self::$phrases[$string];
			}
		}

		return $string;
	}
}