<?php

/**
 * Validation Options for DataTables Editor.
 *
 * @see       http://editor.datatables.net
 */

namespace DataTables\Editor;

use DataTables\Ext;

/**
 * Common validation options that can be specified for all validation methods.
 */
class ValidateOptions extends Ext
{
	/** @var bool */
	private $_empty = true;

	/** @var string */
	private $_message = 'Input not valid';

	/** @var bool */
	private $_optional = true;

	/** @var string */
	private $_dependsField;

	/** @var mixed */
	private $_dependsValue;

	/** @var callable(mixed, array, array): boolean */
	private $_dependsFn;

	public function __construct($opts = null)
	{
		if ($opts) {
			if (isset($opts['empty'])) {
				$this->allowEmpty($opts['empty']);
			}
			if (isset($opts['message'])) {
				$this->message($opts['message']);
			}
			if (isset($opts['optional'])) {
				$this->optional($opts['optional']);
			}
		}
	}

	/**
	 * Apply a dependency for the validator.
	 *
	 * @param callable(mixed, array, array): boolean|string $field Function that performances a
	 *                                                             dependency check, or a field name that this validator depends upon
	 * @param mixed                                         $value If `$field` is given as a string, this can be a value, or an array of
	 *                                                             values that the field name given needs the value to match.
	 *
	 * @return $this Self for chaining
	 */
	public function dependsOn($field, $value = null)
	{
		if (is_callable($field)) {
			$this->_dependsFn = $field;
		} else {
			$this->_dependsField = $field;
			$this->_dependsValue = $value;
		}

		return $this;
	}

	/**
	 * Get / set the error message to use if validation fails.
	 *
	 * @param string $msg Error message to use. If not given, the currently
	 *                    set message will be returned.
	 *
	 * @return ($msg is null ? string : $this)
	 */
	public function message($msg = null)
	{
		if ($msg === null) {
			return $this->_message;
		}

		$this->_message = $msg;

		return $this;
	}

	/**
	 * Get / set the field empty option.
	 *
	 * @param bool $empty `false` if the field is not allowed to be
	 *                    empty. `true` if it can be.
	 *
	 * @return ($empty is null ? bool : $this)
	 */
	public function allowEmpty($empty = null)
	{
		if ($empty === null) {
			return $this->_empty;
		}

		$this->_empty = $empty;

		return $this;
	}

	/**
	 * Get / set the field optional option.
	 *
	 * @param bool $optional `false` if the field does not need to be
	 *                       submitted. `true` if it must be.
	 *
	 * @return ($optional is null ? bool : $this)
	 */
	public function optional($optional = null)
	{
		if ($optional === null) {
			return $this->_optional;
		}

		$this->_optional = $optional;

		return $this;
	}

	/**
	 * @internal
	 */
	public static function select($user)
	{
		if ($user) {
			return $user;
		}

		return new ValidateOptions();
	}

	/**
	 * Run the dependency check.
	 *
	 * @internal
	 *
	 * @param mixed $val  Field's value to validate
	 * @param array $data Row's submitted data
	 * @param array $host Host information
	 *
	 * @return bool `true` if there is no condition, or if there is one and the condition
	 *              matches, or `false` if there is a condition and it doesn't match.
	 */
	public function runDepends($val, $data, $host)
	{
		if ($this->_dependsFn !== null) {
			// External function - call it
			$fn = $this->_dependsFn;

			return $fn($val, $data, $host);
		} elseif ($this->_dependsField) {
			// Get the value that was submitted for the dependent field
			$depFieldVal = $this->_readProp($this->_dependsField, $data);

			if ($this->_dependsValue !== null) {
				// Field and value
				return is_array($this->_dependsValue)
					? in_array($depFieldVal, $this->_dependsValue)
					: ($depFieldVal === $this->_dependsValue);
			}

			// Just a field - check that the field has a value
			return $depFieldVal !== null && $depFieldVal !== '';
		}

		// Default is to apply the validator
		return true;
	}
}
