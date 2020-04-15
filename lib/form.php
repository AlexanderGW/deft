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

use Snappy\Lib\Random;

class Form extends \Snappy_Concrete {
	private $element = array();
	private $fields = array();

	function __construct ($value = null) {
		$this->element['@tag'] = 'form';
		$this->element['@props']['method'] = 'POST';
		if (is_null($value)) {
			$this->prop('id', 'sf_' . Random::getChars(5));
		}
		if (is_string($value)) {
			$this->prop('id', $value);
		} elseif (is_array($value)) {
			$this->element = $value;
		}

		parent::__construct($this->prop('id'), __CLASS__);
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function route ($value = null) {
		//$this->prop('action','');
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function validate ($form = null, $state = null) {
		if (\Snappy::request()->isPost()) {
//			var_dump($form, $state);
//			die('validate form');
		}
	}

	/**
	 * @param null $value
	 *
	 * @return $this|null
	 */
	public function post ($value = null) {
		if (is_null($value)) {
			return $this->props('method') === 'POST';
		}
		$this->prop('method', (bool) $value === true ? 'POST' : 'GET');

		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function props ($value = null) {
		if (is_null($value)) {
			return $this->element['@props'];
		}

		$this->element['@props'] = array(
			                          'id' => $this->element['@props']['id']
		                          ) + (array) $value;

		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function prop ($name = null, $value = null) {
		if (!is_null($name)) {
			if (is_null($value)) {
				return $this->element['@props'][$name];
			}
			if (!is_null($name)) {
				$this->element['@props'][$name] = $value;
			}
		}

		return $this;
	}

	/**
	 * @param null $scope
	 *
	 * @return object
	 */
	public function field ($scope = null) {
		if (is_null($scope)) {
			$tag  = 'input';
			$type = 'hidden';
		}

		if (!array_key_exists($scope, $this->fields)) {
			if (strpos($scope, 'select') === 0 or strpos($scope, 'textarea') === 0) {
				list($tag, $name, $id) = explode('.', $scope);
				$type = null;
			} else {
				list($tag, $type, $name, $id) = explode('.', $scope);
			}
			if (empty($id)) {
				$id = 'syf__' . ($name ? $name : ($type ? $tag . '_' . $type : $tag));
			}
			$this->fields[$scope] = new formField($tag, $type, $name, $id);
		}

		return $this->fields[$scope];
	}

	/**
	 * @return string
	 */
	public function __toString () {
		foreach ($this->fields as $field) {
			if ($field->prop('type') == 'file' and !$this->prop('enctype')) {
				$this->prop('enctype', 'multipart/form-data');
			}
			$this->element['@markup'] .= $field->content();
		}

		return Element::html($this->element, $this->prop('id') ? 'element.form.' . $this->prop('id') : 'element.form');
	}

	public function save () {
		$config = \Snappy::config('config.form.' . $this->prop('id'));

		$array = array();
		foreach ($this->fields as $field) {
			array_push($array, array(
				'@tag'         => $field->tag(),
				'label'       => $field->label(),
				'description' => $field->description(),
				'options'     => $field->options(),
				'@props'       => $field->props()
			));
		}

		$config->set($array);

		return $config->save();
	}
}

class formField {

	/**
	 * @var null
	 */
	private $tag = null;
	/**
	 * @var null
	 */
	private $label = null;
	/**
	 * @var null
	 */
	private $description = null;
	/**
	 * @var null
	 */
	private $markup = null;
	/**
	 * @var array
	 */
	private $props = array();
	/**
	 * @var array
	 */
	private $options = array();

	/**
	 * form.field. constructor.
	 *
	 * @param null $tag
	 * @param null $type
	 * @param null $name
	 * @param null $id
	 */
	public function __construct ($tag = null, $type = null, $name = null, $id = null) {
		if (is_null($tag)) {
			$tag = 'input';
		}
		$this->tag = $tag;
		if (is_null($type) and $tag == 'input') {
			$type = 'hidden';
		}
		$this->props['type'] = $type;
		$this->props['name'] = $name;
		$this->props['id']   = str_replace('-', '_', $id);
	}

	/**
	 * @param null $value
	 *
	 * @return $this|null
	 */
	public function label ($value = null) {
		if (is_null($value)) {
			return $this->label;
		}
		$this->label = $value;

		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|null
	 */
	public function description ($value = null) {
		if (is_null($value)) {
			return $this->description;
		}
		$this->description = $value;

		return $this;
	}

	/**
	 * @return int|null
	 */
	public function tag ($value = null) {
		if (is_null($value)) {
			return $this->tag;
		}
		$this->tag = $value;

		return $this;
	}

	/**
	 * @return null
	 */
	public function name ($value = null) {
		return $this->prop('name', $value);
	}

	/**
	 * @return string
	 */
	public function id ($value = null) {
		return $this->prop('id', $value);
	}

	/**
	 * @return int|null
	 */
	public function type ($value = null) {
		return $this->prop('type', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function props ($value = null) {
		if (is_null($value)) {
			return $this->props;
		}
		$this->props = (array) $value;

		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function prop ($name = null, $value = null) {
		if (is_null($value)) {
			return $this->props[$name];
		}
		if (!is_null($name)) {
			$this->props[$name] = $value;
		}

		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function autoComplete ($value = null) {
		return $this->prop('autocomplete', ((bool) $value ? 'on' : 'off'));
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function checked ($value = null) {
		return $this->prop('checked', (bool) $value);
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function disabled ($value = null) {
		return $this->prop('disabled', (bool) $value);
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function readOnly ($value = null) {
		return $this->prop('readonly', (bool) $value);
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function required ($value = null) {
		return $this->prop('required', (bool) $value);
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function cols ($value = null) {
		return $this->prop('cols', (int) $value);
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function max ($value = null) {
		return $this->prop('max', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function maxLength ($value = null) {
		return $this->prop('maxlength', (int) $value);
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function min ($value = null) {
		return $this->prop('min', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return $this|array
	 */
	public function options ($value = null) {
		if (is_null($value)) {
			return $this->options;
		}
		$this->options = (array) $value;

		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function pattern ($value = null) {
		return $this->prop('pattern', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function rows ($value = null) {
		return $this->prop('rows', (int) $value);
	}

	/**
	 * @param null $min
	 * @param null $step
	 * @param null $max
	 *
	 * @return $this|array
	 */
	public function scales ($min = null, $step = null, $max = null) {
		if (is_null($min)) {
			return array(
				$this->prop('min'),
				$this->prop('step'),
				$this->prop('max')
			);
		}
		$this->prop('min', $min);
		$this->prop('step', $step);
		$this->prop('max', $max);

		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function size ($value = null) {
		return $this->prop('size', (int) $value);
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function step ($value = null) {
		return $this->prop('step', (float) $value);
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function value ($value = null) {
		return $this->prop('value', $value);
	}

	/**
	 * @param null $value
	 *
	 * @return $this
	 */
	public function classes ($value = null) {
		if (!is_null($value)) {
			if (is_string($value) and strpos($value, ' ')) {
				$value = explode(' ', trim($value));
			}
			$this->prop('class', (array) $value);
		}

		return $this;
	}

	/**
	 * @param null $name
	 *
	 * @return bool|void
	 */
	public function hasClass ($value = null) {
		if (($classes = $this->prop('class'))) {
			if (is_string($classes)) {
				return (strpos($classes, $value) !== false);
			} elseif (is_array($classes)) {
				return in_array($value, $classes);
			}
		}

		return;
	}

	/**
	 * Return rendered form field HTML
	 *
	 * @return null|string
	 */
	public function content () {
		$elements = null;

		// TODO: Check if in cache, return if we have a value

		if ($this->label() && !array_key_exists('aria-labelledby', $this->props)) {
			$this->props['aria-labelledby'] = $this->id() .'_label';
		}

		if ($this->description() && !array_key_exists('aria-describedby', $this->props)) {
			$this->props = array_merge(array(
				'aria-describedby' => $this->id() . '_description'
			), $this->props);
			$this->props['aria-describedby'] = $this->id() .'_description';
		}

		switch ($this->tag) {

			/**
			 * Input controls
			 */
			case 'input' :
				switch ($this->prop('type')) {

					/**
					 * Checkbox control
					 */
					case 'checkbox' :
						if (count($this->options)) {

							// Remove value prop
							$prop_value = null;
							if ($prop_value = $this->prop('value')) {
								unset($this->props['value']);
							}

							// Build options
							$elements = '';
							$i = 0;
							foreach ($this->options as $value => $label) {
								$option_id = $this->id();
								if ($i) {
									$option_id .= '_' . $i;
								}

								$option_label_id = $option_id . '_label';

								// Build props
								$props = array(
							         'type'            => 'checkbox',
							         'value'           => $value,
							         'name'            => $this->prop('name') . '[]',
							         'id'              => $option_id,
							         'aria-labelledby' => $option_label_id
						         ) + $this->props;

								// Is option the current state value?
								if ($prop_value and $value == $prop_value) {
									$props['checked'] = true;
								}

								// Control element
								$element = Element::html(array(
									'@tag'   => 'input',
									'@close' => false,
									'@props' => $props
								), 'form.field.control.input.checkbox');

								// Control element template
								$template = Element::html(array(
									'@tag'    => 'div',
									'@markup' => array(
										'%1$s',
										array(
											'@tag'    => 'label',
											'@props'  => array(
												'for' => $option_id,
												'id' => $option_label_id
											),
											'@markup' => '%2$s'
										)
									),
									'@props'  => array(
										'class' => 'option'
									)
								), 'form.field.control.template.input.checkbox');
								if (!empty($template)) {
									$elements .= sprintf($template, $element, $label);
								} else {
									$elements .= $element;
								}

								$i++;
							}
						}
						break;

					/**
					 * Radio control
					 */
					case 'radio' :
						if (count($this->options)) {

							// Remove value prop
							$prop_value = null;
							if (array_key_exists('value', $this->props)) {
								$prop_value = $this->prop('value');
								unset($this->props['value']);
							}

							// Build options
							$elements = '';
							$i = 0;
							foreach ($this->options as $value => $label) {
								$option_id = $this->id();
								if ($i) {
									$option_id .= '_' . $i;
								}

								$option_label_id = $option_id . '_label';

								// Build props
								$props = array(
									         'type'            => 'radio',
									         'value'           => $value,
									         'name'            => $this->prop('name'),
									         'id'              => $option_id,
									         'aria-labelledby' => $option_label_id
								         ) + $this->props;

								// Is option the current state value?
								if ($prop_value and $value == $prop_value) {
									$props['checked'] = true;
								}

								// Control element
								$element = Element::html(array(
									'@tag'   => 'input',
									'@close' => false,
									'@props' => $props
								), 'form.field.control.input.radio');

								// Control element template
								$template = Element::html(array(
									'@tag'    => 'div',
									'@markup' => array(
										'%1$s',
										array(
											'@tag'    => 'label',
											'@props'  => array(
												'for' => $option_id,
												'id' => $option_label_id
											),
											'@markup' => '%2$s'
										)
									),
									'@props'  => array(
										'class' => 'option'
									)
								), 'form.field.control.template.input.radio');
								if (!empty($template)) {
									$elements .= sprintf($template, $element, $label);
								} else {
									$elements .= $element;
								}

								$i++;
							}
						}
						break;
				}
				break;

			/**
			 * Select control
			 */
			case 'select' :

				// Remove value prop
				$prop_value = null;
				if ($prop_value = $this->prop('value')) {
					unset($this->props['value']);
				}

				// Build options
				foreach ($this->options as $value => $label) {

					// Build props
					$props = array(
						'value' => $value,
					);

					// Is option the current state value?
					if ($prop_value and $value == $prop_value) {
						$props['selected'] = true;
					}

					// Control elements
					$this->markup .= Element::html(array(
						'@tag'    => 'option',
						'@markup' => $label,
						'@props'  => $props
					), 'form.field.control.select.option');
				}

				$elements = Element::html(array(
					'@tag'    => 'select',
					'@markup' => $this->markup,
					'@props'  => $this->props
				));
				break;

			/**
			 * Textarea control
			 */
			case 'textarea' :

				// Remove value prop, set as markup
				if ($prop_value = $this->prop('value')) {
					$this->markup = Sanitize::forHtml($prop_value);
					unset($this->props['value']);
				}

				$elements = Element::html(array(
					'@tag'    => 'textarea',
					'@markup' => $this->markup,
					'@props'  => $this->props
				));
				break;
		}

		// Field label
		$label = '';
		if ($this->label()) {
			$label = Element::html(array(
				'@tag'    => 'label',
				'@markup' => $this->label(),
				'@props'  => array(
					'id' => $this->id() . '_label'
				)
			), 'form.field.label.template');
		}

		// Field description
		$description = '';
		if ($this->description()) {
			$description = Element::html(array(
				'@tag'    => 'p',
				'@markup' => $this->description(),
				'@props'  => array(
					'id' => $this->id() . '_description'
				)
			), 'form.field.description.template');
		}

		// Field control
		if (is_null($elements)) {
			$elements = Element::html(array(
				'@tag'    => $this->tag,
				'@close'  => ($this->tag == 'input'),
				'@markup' => $this->markup,
				'@props'  => $this->props
			), strtolower('element.' . $this->tag). '.' . $this->prop('type') . ($this->prop('name') ? '.' . $this->prop('name') : ''));
		}

		// Field control template
		$template = Element::html(array(
			'@tag'    => 'div',
			'@props'  => array(
				'class' => array(
					'control'
				),
			),
			'@markup' => '%s'
		), 'form.field.control.template');
		if (!empty($template)) {
			$elements = sprintf($template, $elements);
		}

		// Field template
		$template = Element::html(array(
			'@tag'    => 'div',
			'@markup' => '%1$s%2$s%3$s',
			'@props'  => array(
				'class' => array(
					'sy-field',
					$this->prop('type'),
					$this->prop('name')
				)
			)
		), 'form.field..template');
		if (!empty($template)) {
			return sprintf($template, $label, $description, $elements);
		} else {
			return $elements;
		}
	}
}