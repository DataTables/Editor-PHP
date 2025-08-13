<?php

/**
 * Validation methods for DataTables Editor.
 *
 * @see       http://editor.datatables.net
 */

namespace DataTables\Editor;

/**
 * Validation methods for DataTables Editor fields.
 *
 * These methods will typically be applied through the {@see Field::validator()}
 * method and thus the arguments to be passed will be automatically resolved
 * by Editor.
 *
 * The validation methods in this class all take three parameters:
 *
 * 1. Data to be validated
 * 2. Full data from the form (this can be used with a custom validation method
 *    for dependent validation).
 * 3. Validation configuration options.
 *
 * When using the `Validate` class functions with the {@see Field::validator()}
 * method, the second parameter passed into {@see Field::validator()} is given
 * to the validation functions here as the third parameter. The first and
 * second parameters are automatically resolved by the {@see Field} class.
 *
 * The validation configuration options is an array of options that can be used
 * to customise the validation - for example defining a date format for date
 * validation. Each validation method has the option of defining its own
 * validation options, but all validation methods provide four common options:
 *
 * * `{boolean} optional` - Require the field to be submitted (`false`) or not
 *   (`true` - default). When set to `true` the field does not need to be
 *   included in the list of parameters sent by the client - if set to `false`
 *   then it must be included. This option can be be particularly used in Editor
 *   as Editor will not set a value for fields which have not been submitted -
 *   giving the ability to submit just a partial list of options.
 * * `{boolean} empty` - Allow a field to be empty, i.e. a zero length string -
 *   `''` (`true` - default) or require it to be non-zero length (`false`).
 * * `{boolean} required` - Short-cut for `optional=false` and `empty=false`.
 *   Note that if this option is set the `optional` and `empty` parameters are
 *   automatically set and cannot be overridden by passing in different values.
 * * `{string} message` - Error message shown should validation fail. This
 *   provides complete control over the message shown to the end user, including
 *   internationalisation (i.e. to provide a translation that is not in the
 *   English language).
 *
 * @example
 *    ```
 *      // Ensure that a non-empty value is given for a field
 *      (new Field( 'engine' ))->validator( Validate::required() )
 *    ```
 * @example
 *    ```
 *      // Don't require a field to be submitted, but if it is submitted, it
 *      // must be non-empty
 *      (new Field( 'reg_date' ))->validator( Validate::notEmpty() )
 *    ```
 * @example
 *    ```
 *      // Date validation
 *      (new Field( 'reg_date' ))->validator( Validate::dateFormat( 'D, d M y' ) )
 *    ```
 * @example
 *    ```
 *      // Date validation with a custom error message
 *      (new Field( 'reg_date' ))->validator( Validate::dateFormat( 'D, d M y',
 *          (new ValidateOptions())
 *              ->message( 'Invalid date' )
 *      ) )
 *    ```
 * @example
 *    ```
 *      // Require a non-empty e-mail address
 *      (new Field( 'reg_date' ))->validator( Validate::email( (new ValidateOptions())
 *        ->empty( false )
 *      ) )
 *    ```
 * @example
 *    ```
 *      // Custom validation - closure
 *      (new Field( 'engine' ))->validator( function($val, $data, $opts) {
 *         if ( ! preg_match( '/^1/', $val ) ) {
 *           return "Value <b>must</b> start with a 1";
 *         }
 *         return true;
 *      } )
 *    ```
 */
class Validate
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal functions
	 */

	/**
	 * "Magic" method to automatically apply the required check to any of the
	 * static methods simply by adding '_required' or '_empty' to the end of the
	 * method's name, depending on if you need the field to be submitted or not.
	 *
	 * This is retained for backwards compatibility, but the validation options
	 * are now the recommended way of indicating that a field is required.
	 *
	 * @internal
	 *
	 * @param string $name      Function name
	 * @param array  $arguments Function arguments
	 *
	 * @return mixed|string
	 */
	public static function __callStatic($name, $arguments)
	{
		if (preg_match('/_required$/', $name)) {
			if ($arguments[0] === null || $arguments[0] === '') {
				return 'This field is required';
			}

			return call_user_func_array(
				__NAMESPACE__ . '\Validate::' . str_replace('_required', '', $name),
				$arguments
			);
		}
	}

	/**
	 * During validation, check if the validator is conditional.
	 *
	 * @internal
	 *
	 * @param mixed                $val  Field's value to validate
	 * @param ValidateOptions|null $opts Validation options
	 * @param array                $data Row's submitted data
	 * @param array                $host Host information
	 *
	 * @return bool `true` if there is no condition, or if there is one and the condition
	 *              matches, or `false` if there is a condition and it doesn't match.
	 */
	public static function _conditional($val, $opts, $data, $host)
	{
		if ($opts === null) {
			// No options, so there can be no condition
			return true;
		}

		// Otherwise, let the options dependency runner return the value
		return $opts->runDepends($val, $data, $host);
	}

	/**
	 * Extend the options from the user function and the validation function
	 * with core defaults.
	 *
	 * @internal
	 */
	public static function _extend($userOpts, $prop, $fnOpts)
	{
		$cfg = [
			'message' => 'Input not valid',
			'required' => false,
			'empty' => true,
			'optional' => true,
		];

		if (!is_array($userOpts)) {
			if ($prop) {
				$cfg[$prop] = $userOpts;
			}

			// Not an array, but the non-array case has been handled above, so
			// just an empty array
			$userOpts = [];
		}

		// Merge into a single array - first array gets priority if there is a
		// key clash, so user first, then function commands and finally the
		// global options
		$cfg = $userOpts + $fnOpts + $cfg;

		return $cfg;
	}

	/**
	 * Perform common validation using the configuration parameters.
	 *
	 * @internal
	 */
	public static function _common($val, $opts, $data, $host)
	{
		// Check if the validator should be applied. If not, then it will pass (i.e. as if
		// there was no validator). If the validator should apply, fall through to the actual
		// validator function.
		if (Validate::_conditional($val, $opts, $data, $host) === false) {
			return true;
		}

		$optional = $opts->optional();
		$empty = $opts->allowEmpty();

		// Error state tests
		if (!$optional && $val === null) {
			// Value must be given
			return false;
		} elseif ($empty === false && $val === '') {
			// Value must not be empty
			return false;
		}

		// Validation passed states
		if ($optional && $val === null) {
			return true;
		} elseif ($empty === true && $val === '') {
			return true;
		}

		// Have the specific validation function perform its tests
		return null;
	}

	/**
	 * Convert the old style validation parameters into ValidateOptions.
	 *
	 * @internal
	 */
	public static function _commonLegacy($cfg)
	{
		$opts = new ValidateOptions();

		if (is_array($cfg)) {
			// `required` is a legacy shortcut for optional=false, empty=false
			if (isset($cfg['required'])) {
				$opts->optional(false);
				$opts->allowEmpty(false);
			}

			if (isset($cfg['empty'])) {
				$opts->allowEmpty($cfg['empty']);
			}

			if (isset($cfg['message'])) {
				$opts->message($cfg['message']);
			}

			if (isset($cfg['optional'])) {
				$opts->optional($cfg['optional']);
			}
		}

		return $opts;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Basic validation
	 */

	/**
	 * No validation - all inputs are valid.
	 *
	 * @return callable Validation function
	 */
	public static function none()
	{
		return static function ($val, $data, $field, $host) {
			return true;
		};
	}

	/**
	 * Basic validation - this is used to perform the validation provided by the
	 * validation options only. If the validation options pass (e.g. `required`,
	 * `empty` and `optional`) then the validation will pass regardless of the
	 * actual value.
	 *
	 * Note that there are two helper short-cut methods that provide the same
	 * function as this method, but are slightly shorter:
	 *
	 * ```
	 * // Required:
	 * Validate::required()
	 *
	 * // is the same as
	 * Validate::basic( $val, $data, [
	 *   "required" => true
	 * ];
	 * ```
	 *
	 * ```
	 * // Optional, but not empty if given:
	 * Validate::notEmpty()
	 *
	 * // is the same as
	 * Validate::basic( $val, $data, [
	 *   "empty" => false
	 * ];
	 * ```
	 *
	 * @callback-param string   $val  The value to check for validity
	 * @callback-param string[] $data The full data set submitted
	 * @callback-param array    $opts Validation options. No additional options are
	 *                                available or required for this validation method.
	 * @callback-param array    $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function basic($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			return $common === false ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Required field - there must be a value and it must be a non-empty value.
	 *
	 * This is a helper short-cut method which is the same as:
	 *
	 * ```
	 * Validate::basic( $val, $data, [
	 *   "required" => true
	 * ];
	 * ```
	 *
	 * @callback-param string   $val  The value to check for validity
	 * @callback-param string[] $data The full data set submitted
	 * @callback-param array    $opts Validation options. No additional options are
	 *                                available or required for this validation method.
	 * @callback-param array    $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function required($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);
		$opts->allowEmpty(false);
		$opts->optional(false);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			return $common === false ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Optional field, but if given there must be a non-empty value.
	 *
	 * This is a helper short-cut method which is the same as:
	 *
	 * ```
	 * Validate::basic( $val, $data, [
	 *   "empty" => false
	 * ];
	 * ```
	 *
	 * @param ValidateOptions $cfg Validation options
	 *
	 * @return callable Validation function
	 */
	public static function notEmpty($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);
		$opts->allowEmpty(false);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			return $common === false ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Validate an input as a boolean value.
	 *
	 * @callback-param string   $val  The value to check for validity
	 * @callback-param string[] $data The full data set submitted
	 * @callback-param array    $opts Validation options. No additional options are
	 *                                available or required for this validation method.
	 * @callback-param array    $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function boolean($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			if (filter_var($val, \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE) === null) {
				return $opts->message();
			}

			return true;
		};
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Number validation methods
	 */

	/**
	 * Check that any input is numeric.
	 *
	 * @callback-param string   $val  The value to check for validity
	 * @callback-param string[] $data The full data set submitted
	 * @callback-param array    $opts Validation options. Additional options:
	 *                                * `decimal`: is available to indicate what character should be used
	 *                                as the decimal
	 * @callback-param array    $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function numeric($decimal = '.', $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts, $decimal) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			if ($decimal !== '.') {
				$val = str_replace($decimal, '.', $val);
			}

			return !is_numeric($val) ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Check for a numeric input and that it is greater than a given value.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. Additional options:
	 *                                 * `min`: indicate the minimum value. If only the default validation
	 *                                 options are required, this parameter can be given as an integer
	 *                                 value, which will be used as the minimum value.
	 *                                 * `decimal`: is available to indicate what character should be used
	 *                                 as the decimal
	 *                                 separator (default '.').
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function minNum($min, $decimal = '.', $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts, $min, $decimal) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			$fn = Validate::numeric($decimal, $opts);
			$numeric = $fn($val, $data, $field, $host);

			if ($numeric !== true) {
				return $numeric;
			}

			if ($decimal !== '.') {
				$val = str_replace($decimal, '.', $val);
			}

			return $val < $min ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Check for a numeric input and that it is less than a given value.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options.
	 *                                 * `max`: indicate the maximum value. If only the default validation
	 *                                 options are required, this parameter can be given as an integer
	 *                                 value, which will be used as the maximum value.
	 *                                 * `decimal`: is available to indicate what character should be used
	 *                                 as the decimal
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function maxNum($max, $decimal = '.', $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts, $max, $decimal) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			$fn = Validate::numeric($decimal, $opts);
			$numeric = $fn($val, $data, $field, $host);

			if ($numeric !== true) {
				return $numeric;
			}

			if ($decimal !== '.') {
				$val = str_replace($decimal, '.', $val);
			}

			return $val > $max ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Check for a numeric input and that it is both greater and smaller than
	 * given numbers.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. Additional options:
	 *                                 * `min`: indicate the minimum value.
	 *                                 * `max`: indicate the maximum value.
	 *                                 * `decimal`: is available to indicate what character should be used
	 *                                 as the decimal
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function minMaxNum($min, $max, $decimal = '.', $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts, $min, $max, $decimal) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			$fn = Validate::numeric($decimal, $opts);
			$numeric = $fn($val, $data, $field, $host);

			if ($numeric !== true) {
				return $numeric;
			}

			$fn = Validate::minNum($min, $decimal, $opts);
			$numeric = $fn($val, $data, $field, $host);

			if ($numeric !== true) {
				return $numeric;
			}

			$fn = Validate::maxNum($max, $decimal, $opts);
			$numeric = $fn($val, $data, $field, $host);

			if ($numeric !== true) {
				return $numeric;
			}

			return true;
		};
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * String validation methods
	 */

	/**
	 * Validate an input as an e-mail address.
	 *
	 * @callback-param string   $val  The value to check for validity
	 * @callback-param string[] $data The full data set submitted
	 * @callback-param array    $opts Validation options. No additional options are
	 *                                available or required for this validation method.
	 * @callback-param array    $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function email($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			return filter_var($val, \FILTER_VALIDATE_EMAIL) !== false ?
				true :
				$opts->message();
		};
	}

	/**
	 * Validate a string has a minimum length.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. The additional option of
	 *                                 `min` is available for this method to indicate the minimum string
	 *                                 length. If only the default validation options are required, this
	 *                                 parameter can be given as an integer value, which will be used as the
	 *                                 minimum string length.
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function minLen($min, $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($min, $opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			$strlen = function_exists('mb_strlen') ?
				'mb_strlen' :
				'strlen';

			return $strlen($val) < $min ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Validate a string does not exceed a maximum length.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. The additional option of
	 *                                 `max` is available for this method to indicate the maximum string
	 *                                 length. If only the default validation options are required, this
	 *                                 parameter can be given as an integer value, which will be used as the
	 *                                 maximum string length.
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function maxLen($max, $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($max, $opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			$strlen = function_exists('mb_strlen') ?
				'mb_strlen' :
				'strlen';

			return $strlen($val) > $max ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Require a string with a certain minimum or maximum number of characters.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. The additional options of
	 *                                 `min` and `max` are available for this method to indicate the minimum
	 *                                 and maximum string lengths, respectively.
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function minMaxLen($min, $max, $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts, $min, $max) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			$fn = Validate::minLen($min, $opts);
			$res = $fn($val, $data, $field, $host);

			if ($res !== true) {
				return $res;
			}

			$fn = Validate::maxLen($max, $opts);
			$res = $fn($val, $data, $field, $host);

			if ($res !== true) {
				return $res;
			}

			return true;
		};
	}

	/**
	 * Validate as an IP address.
	 *
	 * @callback-param string   $val  The value to check for validity
	 * @callback-param string[] $data The full data set submitted
	 * @callback-param array    $opts Validation options. No additional options are
	 *                                available or required for this validation method.
	 * @callback-param array    $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function ip($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			return filter_var($val, \FILTER_VALIDATE_IP) !== false ?
				true :
				$opts->message();
		};
	}

	/**
	 * Validate as an URL address.
	 *
	 * @callback-param string   $val  The value to check for validity
	 * @callback-param string[] $data The full data set submitted
	 * @callback-param array    $opts Validation options. No additional options are
	 *                                available or required for this validation method.
	 * @callback-param array    $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function url($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			return filter_var($val, \FILTER_VALIDATE_URL) !== false ?
				true :
				$opts->message();
		};
	}

	/**
	 * Check if string could contain an XSS attack string.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. The additional options of
	 *                                 `db` - database connection object, `table` - database table to use and
	 *                                 `column` - the column to check this value against as value, are also
	 *                                 available. These options are not required and if not given are
	 *                                 automatically derived from the Editor and Field instances.
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function xss($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			return $field->xssSafety($val) != $val ?
				$opts->message() :
				true;
		};
	}

	/**
	 * Confirm that the value submitted is in a list of allowable values.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. The additional options of
	 *                                 `db` - database connection object, `table` - database table to use and
	 *                                 `column` - the column to check this value against as value, are also
	 *                                 available. These options are not required and if not given are
	 *                                 automatically derived from the Editor and Field instances.
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function values($values, $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($values, $opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			return in_array($val, $values) ?
				true :
				$opts->message();
		};
	}

	/**
	 * Check if there are any tags in the submitted value.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. The additional options of
	 *                                 `db` - database connection object, `table` - database table to use and
	 *                                 `column` - the column to check this value against as value, are also
	 *                                 available. These options are not required and if not given are
	 *                                 automatically derived from the Editor and Field instances.
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function noTags($cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			return strip_tags($val) != $val ?
				$opts->message() :
				true;
		};
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Date validation methods
	 */

	/**
	 * Check that a valid date input is given.
	 *
	 * @callback-param string       $val  The value to check for validity
	 * @callback-param string[]     $data The full data set submitted
	 * @callback-param array|string $opts If given as a string, then $opts is the date
	 *                                    format to check the validity of. If given as an array, then the
	 *                                    date format is in the 'format' parameter, and the return error
	 *                                    message in the 'message' parameter.
	 * @callback-param array        $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function dateFormat($format, $cfg = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($format, $opts) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			$formatCreate = substr($format, 0, 1) !== '!' ?
				'!' . $format :
				$format;

			$date = \DateTime::createFromFormat($formatCreate, $val);

			return $date && $date->format($format) === $val ?
				true :
				$opts->message();
		};
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Database validation methods
	 */

	/**
	 * Check that the given value is unique in the database.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. The additional options of
	 *                                 `db` - database connection object, `table` - database table to use and
	 *                                 `column` - the column to check this value against as value, are also
	 *                                 available. These options are not required and if not given are
	 *                                 automatically derived from the Editor and Field instances.
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function unique($cfg = null, $column = null, $table = null, $db = null)
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts, $column, $table, $db) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			$editor = $host['editor'];

			if (!$db) {
				$db = $host['db'];
			}

			if (!$table) {
				$table = $editor->table(); // Returns an array, but `select` will take an array
			}

			if (!$column) {
				$column = $field->dbField();
			}

			$query = $db
				->query('select', $table)
				->get($column)
				->where($column, $val);

			// If doing an edit, then we need to also discount the current row,
			// since it is of course already validly unique
			if ($host['action'] === 'edit') {
				$cond = $editor->pkeyToArray($host['id'], true);
				$query->where($cond, null, '!=');
			}

			$res = $query->exec();

			return $res->count() === 0 ?
				true :
				$opts->message();
		};
	}

	/**
	 * Check that the given value is a value that is available in a database -
	 * i.e. a join primary key. This will attempt to automatically use the table
	 * name and value column from the field's `options` method (under the
	 * assumption that it will typically be used with a joined field), but the
	 * table and field can also be specified via the options.
	 *
	 * @callback-param string    $val  The value to check for validity
	 * @callback-param string[]  $data The full data set submitted
	 * @callback-param int|array $opts Validation options. The additional options of
	 *                                 `db` - database connection object, `table` - database table to use and
	 *                                 `column` - the column to check this value against as value, are also
	 *                                 available. These options are not required and if not given are
	 *                                 automatically derived from the Editor and Field instances.
	 * @callback-param array     $host Host information
	 *
	 * @return callable Validation function
	 */
	public static function dbValues($cfg = null, $column = null, $table = null, $db = null, $values = [])
	{
		$opts = ValidateOptions::select($cfg);

		return static function ($val, $data, $field, $host) use ($opts, $column, $table, $db, $values) {
			$common = Validate::_common($val, $opts, $data, $host);

			if ($common !== null) {
				return $common === false ?
					$opts->message() :
					$common;
			}

			// Allow local values to be defined - for example null
			if (in_array($val, $values)) {
				return true;
			}

			$editor = $host['editor'];
			$options = $field->options();

			if (!$db) {
				$db = $host['db'];
			}

			if (!$table) {
				$table = $options->table(); // Returns an array, but `select` will take an array
			}

			if (!$column) {
				$column = $options->value();
			}

			if (!$table) {
				throw new \Exception('Table for database value check is not defined for field ' . $field->name());
			}

			if (!$column) {
				throw new \Exception('Value column for database value check is not defined for field ' . $field->name());
			}

			// Try / catch in case the submitted value can't be represented as the
			// database type (e.g. an empty string as an integer)
			try {
				$count = $db
					->query('select', $table)
					->get($column)
					->where($column, $val)
					->exec()
					->count();

				return $count === 0 ?
					$opts->message() :
					true;
			} catch (\Exception $e) {
				return $opts->message();
			}
		};
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	* File validation methods
	*/
	public static function fileExtensions($extensions, $msg = 'This file type cannot be uploaded.')
	{
		return static function ($file) use ($extensions, $msg) {
			$extn = pathinfo($file['name'], \PATHINFO_EXTENSION);

			for ($i = 0, $ien = count($extensions); $i < $ien; ++$i) {
				if (strtolower($extn) === strtolower($extensions[$i])) {
					return true;
				}
			}

			return $msg;
		};
	}

	public static function fileSize($size, $msg = 'Uploaded file is too large.')
	{
		return static function ($file) use ($size, $msg) {
			return $file['size'] > $size ?
				$msg :
				true;
		};
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	* Mjoin validation methods
	*/
	public static function mjoinMinCount($count, $msg = 'Too few items.')
	{
		return static function ($editor, $action, $values) use ($count, $msg) {
			if ($action === 'create' || $action === 'edit') {
				return count($values) < $count ?
					$msg :
					true;
			}

			return true;
		};
	}

	public static function mjoinMaxCount($count, $msg = 'Too many items.')
	{
		return static function ($editor, $action, $values) use ($count, $msg) {
			if ($action === 'create' || $action === 'edit') {
				return count($values) > $count ?
					$msg :
					true;
			}

			return true;
		};
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal functions
	 * These legacy methods are for backwards compatibility with the old way of
	 * using the validation methods. They basically do argument swapping.
	 */

	/**
	 * @internal
	 */
	public static function noneLegacy($legacyOpts)
	{
		return Validate::none();
	}

	/**
	 * @internal
	 */
	public static function basicLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, []);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::required($opts);
	}

	/**
	 * @internal
	 */
	public static function requiredLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'This field is required.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::required($opts);
	}

	/**
	 * @internal
	 */
	public static function notEmptyLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'This field is required.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::notEmpty($opts);
	}

	/**
	 * @internal
	 */
	public static function booleanLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'Please enter true or false.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::notEmpty($opts);
	}

	/**
	 * @internal
	 */
	public static function numericLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'This input must be given as a number.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return isset($legacyOpts['decimal']) ?
			Validate::numeric($legacyOpts['decimal'], $opts) :
			Validate::numeric('.', $opts);
	}

	/**
	 * @internal
	 */
	public static function minNumLegacy($legacyOpts)
	{
		$min = is_array($legacyOpts) ? $legacyOpts['min'] : $legacyOpts;
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'Number is too small, must be ' . $min . ' or larger.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return isset($legacyOpts['decimal']) ?
			Validate::minNum($min, $legacyOpts['decimal'], $opts) :
			Validate::minNum($min, '.', $opts);
	}

	/**
	 * @internal
	 */
	public static function maxNumLegacy($legacyOpts)
	{
		$max = is_array($legacyOpts) ? $legacyOpts['max'] : $legacyOpts;
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'Number is too large, must be ' . $max . ' or smaller.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return isset($legacyOpts['decimal']) ?
			Validate::maxNum($max, $legacyOpts['decimal'], $opts) :
			Validate::maxNum($max, '.', $opts);
	}

	/**
	 * @internal
	 */
	public static function minMaxNumLegacy($legacyOpts)
	{
		$min = $legacyOpts['min'];
		$max = $legacyOpts['max'];
		$cfg = Validate::_extend($legacyOpts, null, []);
		$opts = Validate::_commonLegacy($cfg);

		return isset($legacyOpts['decimal']) ?
			Validate::minMaxNum($min, $max, $legacyOpts['decimal'], $opts) :
			Validate::minMaxNum($min, $max, '.', $opts);
	}

	/**
	 * @internal
	 */
	public static function emailLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'Please enter a valid e-mail address.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::email($opts);
	}

	/**
	 * @internal
	 */
	public static function minLenLegacy($legacyOpts)
	{
		$min = is_array($legacyOpts) ? $legacyOpts['min'] : $legacyOpts;
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'The input is too short. ' . $min . ' characters required.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::minLen($min, $opts);
	}

	/**
	 * @internal
	 */
	public static function maxLenLegacy($legacyOpts)
	{
		$max = is_array($legacyOpts) ? $legacyOpts['max'] : $legacyOpts;
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'The input is too long. ' . $max . ' characters maximum.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::maxLen($max, $opts);
	}

	/**
	 * @internal
	 */
	public static function minMaxLenLegacy($legacyOpts)
	{
		$min = $legacyOpts['min'];
		$max = $legacyOpts['max'];
		$cfg = Validate::_extend($legacyOpts, null, []);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::minMaxLen($min, $max, $opts);
	}

	/**
	 * @internal
	 */
	public static function ipLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'Please enter a valid IP address.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::ip($opts);
	}

	/**
	 * @internal
	 */
	public static function urlLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'Please enter a valid URL.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::url($opts);
	}

	/**
	 * @internal
	 */
	public static function xssLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'This field contains potentially unsafe data.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::xss($opts);
	}

	/**
	 * @internal
	 */
	public static function valuesLegacy($legacyOpts)
	{
		$values = isset($legacyOpts['valid']) ? $legacyOpts['valid'] : $legacyOpts;
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'This value is not valid.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::values($values, $opts);
	}

	/**
	 * @internal
	 */
	public static function noTagsLegacy($legacyOpts)
	{
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'This field may not contain HTML.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::noTags($opts);
	}

	/**
	 * @internal
	 */
	public static function dateFormatLegacy($legacyOpts)
	{
		$format = is_array($legacyOpts) ? $legacyOpts['format'] : $legacyOpts;
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'Date is not in the expected format.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::dateFormat($format, $opts);
	}

	/**
	 * @internal
	 */
	public static function uniqueLegacy($legacyOpts)
	{
		$table = isset($legacyOpts['table']) ? $legacyOpts['table'] : null;
		$column = isset($legacyOpts['column']) ? $legacyOpts['column'] : null;
		$db = isset($legacyOpts['db']) ? $legacyOpts['db'] : null;
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'This field must have a unique value.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::unique($opts, $column, $table, $db);
	}

	/**
	 * @internal
	 */
	public static function dbValuesLegacy($legacyOpts)
	{
		$table = isset($legacyOpts['table']) ? $legacyOpts['table'] : null;
		$column = isset($legacyOpts['column']) ? $legacyOpts['column'] : null;
		$db = isset($legacyOpts['db']) ? $legacyOpts['db'] : null;
		$values = isset($legacyOpts['values']) ? $legacyOpts['values'] : [];
		$cfg = Validate::_extend($legacyOpts, null, [
			'message' => 'This value is not valid.',
		]);
		$opts = Validate::_commonLegacy($cfg);

		return Validate::dbValues($opts, $column, $table, $db, $values);
	}
}
