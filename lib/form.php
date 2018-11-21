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

class Form extends Snappy_Concrete {
	private $id = null;
	private $attributes = array();
	private $fields = array();

	function __construct( $args = null ) {
		if( is_string( $args ) )
			$this->id = $args;
		elseif( is_array( $args ) )
			$this->id = array_shift( $args );

		parent::__construct( $this->id );
	}

	/**
	 * @return string
	 */
	public function getId() {
		if( is_null( $this->id ) )
			$this->id = 'sf_' . Helper::getRandomChars( 5 );
		return $this->id;
	}

	/**
	 * @param null $name
	 * @param null $value
	 *
	 * @return $this
	 */
	public function setAttribute( $name = null, $value = null ) {
		if( is_string( $name ) )
			$this->attributes[ $name ] = $value;
		return $this;
	}

	/**
	 * @param null $array
	 *
	 * @return $this
	 */
	public function setAttributes( $array = null ) {
		if( is_array( $array ) )
			$this->attributes = array_merge( $this->attributes, $array );
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this
	 */
	public function action( $value = null ) {
		if( is_null( $value ) )
			return $this->getAttribute( 'action' );
		$this->setAttribute( 'action', $value );
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this
	 */
	public function method( $value = null ) {
		if( is_null( $value ) )
			return $this->getAttribute( 'method' );
		$this->setAttribute( 'method', $value );
		return $this;
	}

	/**
	 * @param null $type
	 * @param null $name
	 * @param null $id
	 *
	 * @return mixed
	 */
	public function add( $type = null, $name = null, $id = null ) {
		if( is_string( $id ) )
			$key =& $id;
		else
			$key = 'sff_' . Helper::getRandomChars( 5 );
		$this->fields[ $key ] = new FormField( $type, $name, $id );
		return $this->fields[ $key ];
	}

	/**
	 * @param null $key
	 */
	public function get( $key = null ) {
		if( array_key_exists( $key, $this->fields ) )
			return $this->fields[ $key ];
		return;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		if( $this->getid() )
			$this->attributes['id'] = $this->getId();

		$this->attributes['html'] = '';
		foreach( $this->fields as $field ) {
			if( $field->getType() == FormField::File and !array_key_exists( 'enctype', $this->attributes ) )
				$this->attributes['enctype'] = 'multipart/form-data';
			$this->attributes['html'] .= $field->content();
		}

		if( array_key_exists( 'class', $this->attributes ) )
			$this->attributes['class'] = implode( ' ', $this->attributes['class'] );
		$return = Filter::exec( 'formContent', Html::element( 'form', $this->attributes, 'elementForm_' . $this->id ) );
		return $return;
	}
}

class FormField {
	const Hidden = 0;
	const Text = 1;
	const Password = 2;
	const Button = 3;
	const ButtonSubmit = 4;
	const InputButton = 5;
	const InputSubmit = 6;
	const Checkbox = 7;
	const Radio = 8;
	const Select = 9;
	const Textarea = 10;
	const File = 11;
	const Color = 100;
	const Date = 101;
	const Datetime = 102;
	const Email = 103;
	const Month = 104;
	const Number = 105;
	const Range = 106;
	const Search = 107;
	const Phone = 108;
	const Time = 109;
	const Url = 110;
	const Week = 111;

	/**
	 * @var null
	 */
	private $id = null;
	/**
	 * @var null
	 */
	private $name = null;
	/**
	 * @var int|null
	 */
	private $type = null;
	/**
	 * @var null
	 */
	private $label = null;
	/**
	 * @var null
	 */
	private $info = null;
	/**
	 * @var array
	 */
	private $attributes = array();
	/**
	 * @var array
	 */
	private $field_options = array();

	/**
	 * FormField constructor.
	 *
	 * @param null $type
	 * @param null $name
	 * @param null $id
	 */
	public function __construct( $type = null, $name = null, $id = null ) {
		if( is_null( $type ) )
			$type = self::Hidden;
		$this->type = $type;
		$this->name = $name;
		$this->id = $id;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|null
	 */
	public function label( $value = null ) {
		if( is_null( $value ) )
			return $this->label;
		$this->label = $value;
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|null
	 */
	public function info( $value = null ) {
		if( is_null( $value ) )
			return $this->info;
		$this->info = $value;
		return $this;
	}

	/**
	 * @return null
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getId() {
		return strtolower( $this->id );
	}

	/**
	 * @return int|null
	 */
	public function getType() {
		return $this->type;
	}

	/**
	 * @param null $name
	 */
	public function getAttribute( $name = null ) {
		if( !is_null( $name ) and array_key_exists( $name, $this->attributes ) )
			return $this->attributes[ $name ];
		return;
	}

	/**
	 * @param null $name
	 * @param null $value
	 *
	 * @return $this
	 */
	public function setAttribute( $name = null, $value = null ) {
		if( is_string( $name ) )
			$this->attributes[ $name ] = $value;
		return $this;
	}

	/**
	 * @param null $array
	 *
	 * @return $this
	 */
	public function setAttributes( $array = null ) {
		if( is_array( $array ) )
			$this->attributes = array_merge( $this->attributes, $array );
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function _handle( $name = null, $value = null ) {
		if( is_null( $value ) )
			return $this->getAttribute( $name );
		if( !is_null( $name ) )
			$this->setAttribute( $name, $value );
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function isAutoComplete( $value = null ) {
		return $this->_handle( 'autocomplete', ( (bool)$value ? 'on' : 'off' ) );
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function isChecked( $value = null ) {
		return $this->_handle( 'checked', (bool)$value );
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function isDisabled( $value = null ) {
		return $this->_handle( 'disabled', (bool)$value );
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function isReadOnly( $value = null ) {
		return $this->_handle( 'readonly', (bool)$value );
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function isRequired( $value = null ) {
		return $this->_handle( 'required', (bool)$value );
	}

	/**
	 * @param null $value
	 *
	 * @return mixed
	 */
	public function cols( $value = null ) {
		return $this->_handle( 'cols', (int)$value );
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function max( $value = null ) {
		return $this->_handle( 'max', $value );
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function maxLength( $value = null ) {
		return $this->_handle( 'maxlength', (int)$value );
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function min( $value = null ) {
		return $this->_handle( 'min', $value );
	}

	/**
	 * @param null $value
	 *
	 * @return $this|array
	 */
	public function options( $value = null ) {
		if( is_null( $value ) )
			return $this->field_options;
		$this->field_options = (array)$value;
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function pattern( $value = null ) {
		return $this->_handle( 'pattern', $value );
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function rows( $value = null ) {
		return $this->_handle( 'rows', (int)$value );
	}

	/**
	 * @param null $min
	 * @param null $step
	 * @param null $max
	 *
	 * @return $this|array
	 */
	public function scales( $min = null, $step = null, $max = null ) {
		if( is_null( $min ) )
			return array(
				$this->getAttribute( 'min' ),
				$this->getAttribute( 'step' ),
				$this->getAttribute( 'max' )
			);
		$this->setAttribute( 'min', $min );
		$this->setAttribute( 'step', $step );
		$this->setAttribute( 'max', $max );
		return $this;
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function size( $value = null ) {
		return $this->_handle( 'size', (int)$value );
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function step( $value = null ) {
		return $this->_handle( 'step', (float)$value );
	}

	/**
	 * @param null $value
	 *
	 * @return $this|void
	 */
	public function value( $value = null ) {
		return $this->_handle( 'value', $value );
	}

	/**
	 * @return array
	 */
	public function getClass() {
		return (array)$this->getAttribute( 'class' );
	}

	/**
	 * @param null $value
	 *
	 * @return $this
	 */
	public function setClass( $value = null ) {
		if( !is_null( $value ) ) {
			if( is_string( $value ) and strpos( $value, ' ' ) )
				$value = explode( ' ', trim( $value ) );
			$this->setAttribute( 'class', (array)$value );
		}
		return $this;
	}

	/**
	 * @param null $name
	 *
	 * @return bool|void
	 */
	public function hasClass( $name = null ) {
		if( is_string( $name ) )
			return in_array( $name, $this->getAttribute( 'class' ) );
		return;
	}

	/**
	 * Return rendered form field HTML
	 *
	 * @return null|string
	 */
	public function content() {
		$tag = 'input';
		$elements = $type = null;
		$self_close = true;
		switch( $this->type ) {
			case self::Hidden :
				$type = 'hidden';
				break;

			case self::Text :
				$type = 'text';
				break;

			case self::Password :
				$type = 'password';
				break;

			case self::InputSubmit :
				$type = 'submit';
				break;

			case self::Button :
				$tag = 'button';
				$type = 'button';
				$self_close = false;
				break;

			case self::ButtonSubmit :
				$tag = 'button';
				$type = 'submit';
				$self_close = false;
				break;

			case self::File :
				$type = 'file';
				break;

			case self::Color :
				$type = 'color';
				break;

			case self::Date :
				$type = 'date';
				break;

			case self::Datetime :
				$type = 'datetime';
				break;

			case self::Email :
				$type = 'email';
				break;

			case self::Month :
				$type = 'month';
				break;

			case self::Number :
				$type = 'number';
				break;

			case self::Range :
				$type = 'range';
				break;

			case self::Search :
				$type = 'search';
				break;

			case self::Phone :
				$type = 'phone';
				break;

			case self::Time :
				$type = 'time';
				break;

			case self::Url :
				$type = 'url';
				break;

			case self::Week :
				$type = 'week';
				break;

			case self::Checkbox :
				$type = 'checkbox';

				if( count( $this->field_options ) ) {
					$element_value = null;
					if( array_key_exists( 'value', $this->attributes ) ) {
						$element_value = $this->attributes['value'];
						unset( $this->attributes['value'] );
					}

					$elements = '';
					foreach( $this->field_options as $value => $label ) {
						$option_attributes = array( 'type' => $type, 'value' => $value, 'name' => $this->name . '[]' ) + $this->attributes;
						if( $element_value and $value == $element_value )
							$option_attributes['checked'] = true;

						$element = Html::element( 'input', $option_attributes, 'elementInput' );
						$template = Filter::exec( 'formFieldCheckboxOptionTemplate', '<label>%1$s<span>%2$s</span></label>' );
						if( !empty( $template ) )
							$elements .= sprintf( $template, $element, $label );
						else
							$elements .= $element;
					}
				}
				break;

			case self::Radio :
				$type = 'radio';

				if( count( $this->field_options ) ) {
					$element_value = null;
					if( array_key_exists( 'value', $this->attributes ) ) {
						$element_value = $this->attributes['value'];
						unset( $this->attributes['value'] );
					}

					$elements = '';
					foreach( $this->field_options as $value => $label ) {
						$option_attributes = array( 'type' => $type, 'value' => $value, 'name' => $this->name ) + $this->attributes;
						if( $element_value and $value == $element_value )
							$option_attributes['checked'] = true;

						$element = Html::element( 'input', $option_attributes, 'elementInput' );
						$template = Filter::exec( 'formFieldRadioOptionTemplate', '<label>%1$s<div class="radio"></div><span>%2$s</span></label>' );
						if( !empty( $template ) )
							$elements .= sprintf( $template, $element, $label );
						else
							$elements .= $element;
					}
				}
				break;

			case self::Select :
				$tag = 'select';
				$self_close = false;

				$attribute_value = null;
				if( array_key_exists( 'value', $this->attributes ) ) {
					$attribute_value = $this->attributes['value'];
					unset( $this->attributes['value'] );
				}

				$options = '';
				foreach( $this->field_options as $value => $label ) {
					$attributes = array( 'value' => $value, 'html' => $label );
					if( $attribute_value and $value == $attribute_value )
						$attributes['selected'] = true;
					$options .= Html::element( 'option', $attributes, 'elementOption' );
				}

				$this->attributes['html'] = $options;
				break;

			case self::Textarea :
				$tag = 'textarea';
				$self_close = false;

				if( array_key_exists( 'value', $this->attributes ) ) {
					$this->attributes['html'] = $this->attributes['value'];
					unset( $this->attributes['value'] );
				}
				break;
		}

		if( $this->getName() )
			$this->attributes['name'] = $this->getName();
		if( $this->getId() )
			$this->attributes['id'] = $this->getId();

		$class = null;
		if( array_key_exists( 'class', $this->attributes ) )
			$this->attributes['class'] = implode( ' ', $this->attributes['class'] );

		if( !is_null( $type ) )
			$this->attributes['type'] = $type;

		$info = '';
		if( $this->info() ) {
			$template = Filter::exec( 'formFieldInfoTemplate', '<p>%s</p>' );
			if( !empty( $template ) )
				$info = sprintf( $template, $this->info() );
		}

		$label = '';
		if( $this->label() ) {
			$template = Filter::exec( 'formFieldLabelTemplate', '<label>%s</label>' );
			if( !empty( $template ) )
				$label = sprintf( $template, $this->label() );
		}

		if( is_null( $elements ) )
			$elements = Html::element( $tag, $this->attributes, 'element' . ucfirst( $tag ) . ucfirst( $type ), $self_close );
		$template = Filter::exec( 'formFieldElementTemplate', '<div>%s</div>' );
		if( !empty( $template ) )
			$elements = sprintf( $template, $elements );

		$template = Filter::exec( 'formFieldWrapperTemplate', '<div>%1$s%2$s%3$s</div>' );
		if( !empty( $template ) )
			return sprintf( $template, $label, $info, $elements );
		else
			return $elements;
	}
}