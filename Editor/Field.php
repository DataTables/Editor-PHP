<?php

/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor.
 *
 * @author    SpryMedia
 * @copyright 2012 SpryMedia ( http://sprymedia.co.uk )
 * @license   http://editor.datatables.net/license DataTables Editor
 *
 * @see       http://editor.datatables.net
 */

namespace DataTables\Editor;

use DataTables\Database;
use DataTables\Database\Query;
use DataTables\Editor;
use DataTables\Ext;
use DataTables\HtmLawed\HtmLawedVanillaWrapper;

/**
 * Field definitions for the DataTables Editor.
 *
 * Each Database column that is used with Editor can be described with this
 * Field method (both for Editor and Join instances). It basically tells
 * Editor what table column to use, how to format the data and if you want
 * to read and/or write this column.
 *
 * Field instances are used with the {@see Editor->field()} and
 * {@see Join->field()} methods to describe what fields should be interacted
 * with by the editable table.
 *
 * @example
 *    Simply get a column with the name "city". No validation is performed.
 *    ```php
 *      new Field( 'city' )
 *    ```
 * @example
 *    Get a column with the name "first_name" - when edited a value must
 *    be given due to the "required" validation from the {@see Validate} class.
 *    ```php
 *      (new Field( 'first_name' ))->validator( 'Validate::required' )
 *    ```
 * @example
 *    Working with a date field, which is validated, and also has *get* and
 *    *set* formatters.
 *    ```php
 *      (new Field( 'registered_date' ))
 *          ->validator( 'Validate::dateFormat', 'D, d M y' )
 *          ->getFormatter( 'Format::date_sql_to_format', 'D, d M y' )
 *          ->setFormatter( 'Format::date_format_to_sql', 'D, d M y' )
 *    ```
 * @example
 *    Using an alias in the first parameter
 *    ```php
 *      new Field( 'name.first as first_name' )
 *    ```
 */
class Field extends Ext
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Statics
	 */

	/** Set option flag (`set()`) - do not set data */
	const SET_NONE = 'none';

	/** Set option flag (`set()`) - write to database on both create and edit */
	const SET_BOTH = 'both';

	/** Set option flag (`set()`) - write to database only on create */
	const SET_CREATE = 'create';

	/** Set option flag (`set()`) - write to database only on edit */
	const SET_EDIT = 'edit';

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Field instance constructor.
	 *
	 * @param string $dbField Name of the database column
	 * @param string $name    Name to use in the JSON output from Editor and the
	 *                        HTTP submit from the client-side when editing. If not given then the
	 *                        $dbField name is used.
	 */
	public function __construct($dbField = null, $name = null)
	{
		if ($dbField !== null && $name === null) {
			// Allow just a single parameter to be passed - each can be
			// overridden if needed later using the API.
			$this->name($dbField);
			$this->dbField($dbField);
		} else {
			$this->name($name);
			$this->dbField($dbField);
		}
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private parameters
	 */

	/** @var string */
	private $_dbField;

	/** @var bool */
	private $_get = true;

	/** @var mixed */
	private $_getFormatter;

	/** @var mixed */
	private $_getFormatterOpts;

	/** @var mixed */
	private $_getValue;

	/** @var Options|null */
	private $_opts;

	/** @var SearchPaneOptions|null */
	private $_spopts;

	/** @var SearchBuilderOptions|null */
	private $_sbopts;

	/** @var callable|null */
	private $_spoptsFn;

	/** @var callable|null */
	private $_sboptsFn;

	/** @var string */
	private $_name;

	/** @var string */
	private $_set = Field::SET_BOTH;

	/** @var mixed */
	private $_setFormatter;

	/** @var mixed */
	private $_setFormatterOpts;

	/** @var mixed */
	private $_setValue;

	/** @var array[] */
	private $_validator = [];

	/** @var Upload|null */
	private $_upload;

	/** @var callable */
	private $_xss;

	/** @var bool */
	private $_xssFormat = true;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Get / set the DB field name.
	 *
	 * Note that when used as a setter, an alias can be given for the field
	 * using the SQL `as` keyword - for example: `firstName as name`. In this
	 * situation the dbField is set to the field name before the `as`, and the
	 * field's name (`name()`) is set to the name after the ` as `.
	 *
	 * As a result of this, the following constructs have identical
	 * functionality:
	 *
	 *    new Field( 'firstName as name' );
	 *    new Field( 'firstName', 'name' );
	 *
	 * @param string $_ Value to set if using as a setter.
	 *
	 * @return ($_ is null ? string : $this) The name of the db field if no parameter is given.
	 */
	public function dbField($_ = null)
	{
		if ($_ === null) {
			return $this->_dbField;
		}

		// Don't split on an `as` inside paraenthesis
		$a = preg_split('/ as (?![^\(]*\))/i', $_);
		if (count($a) > 1) {
			$this->_dbField = trim($a[0]);
			$this->_name = trim($a[1]);
		} else {
			$this->_dbField = $_;
		}

		return $this;
	}

	/**
	 * Get / set the 'get' property of the field.
	 *
	 * A field can be marked as write only when setting the get property to false
	 * here.
	 *
	 * @param bool $_ Value to set if using as a setter.
	 *
	 * @return ($_ is null ? bool : $this) The get property if no parameter is given.
	 */
	public function get($_ = null)
	{
		return $this->_getSet($this->_get, $_);
	}

	/**
	 * Get formatter for the field's data.
	 *
	 * When the data has been retrieved from the server, it can be passed through
	 * a formatter here, which will manipulate (format) the data as required. This
	 * can be useful when, for example, working with dates and a particular format
	 * is required on the client-side.
	 *
	 * Editor has a number of formatters available with the {@see Format} class
	 * which can be used directly with this method.
	 *
	 * @param callable(mixed, array, array): string $_    Value to set if using as a setter. Can be given as
	 *                                                    a closure function or a string with a reference to a function that will
	 *                                                    be called with call_user_func().
	 * @param mixed                                 $opts Variable that is passed through to the get formatting
	 *                                                    function - can be useful for passing through extra information such as
	 *                                                    date formatting string, or a required flag. The actual options available
	 *                                                    depend upon the formatter used.
	 *
	 * @return ($_ is null ? callable|string : $this) The get formatter if no parameter is given.
	 */
	public function getFormatter($_ = null, $opts = null)
	{
		if ($opts !== null) {
			$this->_getFormatterOpts = $opts;
		}

		return $this->_getSet($this->_getFormatter, $_);
	}

	/**
	 * Get / set a get value. If given, then this value is used to send to the
	 * client-side, regardless of what value is held by the database.
	 *
	 * @param \Closure(): mixed|string|number $_ Value to set, or no value to use as a
	 *                                           getter
	 *
	 * @return ($_ is null ? callable|string : $this) Value if used as a getter.
	 */
	public function getValue($_ = null)
	{
		return $this->_getSet($this->_getValue, $_);
	}

	/**
	 * Get / set the 'name' property of the field.
	 *
	 * The name is typically the same as the dbField name, since it makes things
	 * less confusing(!), but it is possible to set a different name for the data
	 * which is used in the JSON returned to DataTables in a 'get' operation and
	 * the field name used in a 'set' operation.
	 *
	 * @param string $_ Value to set if using as a setter.
	 *
	 * @return ($_ is null ? string : $this) The name property if no parameter is given.
	 */
	public function name($_ = null)
	{
		return $this->_getSet($this->_name, $_);
	}

	/**
	 * Get a list of values that can be used for the options list in radio,
	 * select and checkbox inputs from the database for this field.
	 *
	 * Note that this is for simple 'label / value' pairs only. For more complex
	 * data, including pairs that require joins and where conditions, use a
	 * closure to provide a query
	 *
	 * @param Options|string|callable(Database, string): (false|array) $table     Database table name to use to get the
	 *                                                                            paired data from, or a closure function if providing a method
	 * @param string                                                   $value     Table column name that contains the pair's
	 *                                                                            value. Not used if the first parameter is given as a closure
	 * @param string                                                   $label     Table column name that contains the pair's
	 *                                                                            label. Not used if the first parameter is given as a closure
	 * @param \Closure(Query): void                                    $condition Function that will add `where`
	 *                                                                            conditions to the query
	 * @param callable(array): string                                  $format    Function will render each label
	 * @param string                                                   $order     SQL ordering
	 *
	 * @return ($table is null ? Options|null : $this)
	 */
	public function options($table = null, $value = null, $label = null, $condition = null, $format = null, $order = null)
	{
		if ($table === null) {
			return $this->_opts;
		}

		// Overloads for backwards compatibility
		if (is_a($table, 'DataTables\Editor\Options')) {
			// Options class
			$this->_opts = $table;
		} elseif ($table instanceof \Closure) {
			// Function
			$this->_opts = Options::inst()
				->fn($table);
		} else {
			$this->_opts = Options::inst()
				->table($table)
				->value($value)
				->label($label);

			if ($condition) {
				$this->_opts->where($condition);
			}

			if ($format) {
				$this->_opts->render($format);
			}

			if ($order) {
				$this->_opts->order($order);
			}
		}

		return $this;
	}

	/**
	 * Get a list of values that can be used for the options list in SearchPanes.
	 *
	 * @param SearchPaneOptions|callable(Database, Editor): (array|bool) $spInput SearchPaneOptions instance or a closure function if providing a method
	 *
	 * @return ($spInput is null ? SearchPaneOptions|null : $this)
	 */
	public function searchPaneOptions($spInput = null)
	{
		if ($spInput === null) {
			return $this->_spopts;
		}

		// Overloads for backwards compatibility
		if (is_a($spInput, 'DataTables\Editor\SearchPaneOptions')) {
			// Options class
			$this->_spoptsFn = null;
			$this->_spopts = $spInput;
		} elseif ($spInput instanceof \Closure) {
			// Function
			$this->_spopts = null;
			$this->_spoptsFn = $spInput;
		}

		return $this;
	}

	/**
	 * Get a list of values that can be used for the options list in SearchBuilder.
	 *
	 * @param SearchBuilderOptions|callable(Database, Editor): (array|bool) $sbInput SearchBuilderOptions instance or a closure function if providing a method
	 *
	 * @return ($sbInput is null ? SearchBuilderOptions|null : $this)
	 */
	public function searchBuilderOptions($sbInput = null)
	{
		if ($sbInput === null) {
			return $this->_sbopts;
		}

		// Overloads for backwards compatibility
		if (is_a($sbInput, 'DataTables\Editor\SearchBuilderOptions')) {
			// Options class
			$this->_sboptsFn = null;
			$this->_sbopts = $sbInput;
		} elseif ($sbInput instanceof \Closure) {
			// Function
			$this->_sbopts = null;
			$this->_sboptsFn = $sbInput;
		}

		return $this;
	}

	/**
	 * Get / set the 'set' property of the field.
	 *
	 * A field can be marked as read only using this option, to be set only
	 * during an create or edit action or to be set during both actions. This
	 * provides the ability to have fields that are only set when a new row is
	 * created (for example a "created" time stamp).
	 *
	 * @param string|bool $_ Value to set when the method is being used as a
	 *                       setter (leave as undefined to use as a getter). This can take the
	 *                       value of:
	 *
	 *    * `true`              - Same as `Field::SET_BOTH`
	 *    * `false`             - Same as `Field::SET_NONE`
	 *    * `Field::SET_BOTH`   - Set the database value on both create and edit commands
	 *    * `Field::SET_NONE`   - Never set the database value
	 *    * `Field::SET_CREATE` - Set the database value only on create
	 *    * `Field::SET_EDIT`   - Set the database value only on edit
	 *
	 * @return ($_ is null ? string : $this) The set property if no parameter is given.
	 */
	public function set($_ = null)
	{
		if ($_ === true) {
			$_ = Field::SET_BOTH;
		} elseif ($_ === false) {
			$_ = Field::SET_NONE;
		}

		return $this->_getSet($this->_set, $_);
	}

	/**
	 * Set formatter for the field's data.
	 *
	 * When the data has been retrieved from the server, it can be passed through
	 * a formatter here, which will manipulate (format) the data as required. This
	 * can be useful when, for example, working with dates and a particular format
	 * is required on the client-side.
	 *
	 * Editor has a number of formatters available with the {@see Format} class
	 * which can be used directly with this method.
	 *
	 * @param callable(mixed, array, array): string $_    Value to set if using as a setter. Can be given as
	 *                                                    a closure function or a string with a reference to a function that will
	 *                                                    be called with call_user_func().
	 * @param mixed                                 $opts Variable that is passed through to the get formatting
	 *                                                    function - can be useful for passing through extra information such as
	 *                                                    date formatting string, or a required flag. The actual options available
	 *                                                    depend upon the formatter used.
	 *
	 * @return ($_ is null ? callable|string : $this) The set formatter if no parameter is given.
	 */
	public function setFormatter($_ = null, $opts = null)
	{
		if ($opts !== null) {
			$this->_setFormatterOpts = $opts;
		}

		return $this->_getSet($this->_setFormatter, $_);
	}

	/**
	 * Get / set a set value. If given, then this value is used to write to the
	 * database regardless of what data is sent from the client-side.
	 *
	 * @param \Closure(): mixed|string|number $_ Value to set, or no value to use as a
	 *                                           getter
	 *
	 * @return ($_ is null ? callable|string : $this) Value if used as a getter.
	 */
	public function setValue($_ = null)
	{
		return $this->_getSet($this->_setValue, $_);
	}

	/**
	 * Get / set the upload class for this field.
	 *
	 * @param Upload $_ Upload class if used as a setter
	 *
	 * @return ($_ is null ? Upload|null : $this) Value if used as a getter.
	 */
	public function upload($_ = null)
	{
		return $this->_getSet($this->_upload, $_);
	}

	/**
	 * Get / set the 'validator' of the field.
	 *
	 * The validator can be used to check if any abstract piece of data is valid
	 * or not according to the given rules of the validation function used.
	 *
	 * Multiple validation options can be applied to a field instance by calling
	 * this method multiple times. For example, it would be possible to have a
	 * 'required' validation and a 'maxLength' validation with multiple calls.
	 *
	 * Editor has a number of validation available with the {@see Validate} class
	 * which can be used directly with this method.
	 *
	 * @param callable(mixed, array, $this, array): (true|string)|string $_    Value to set if using as the validation method.
	 *                                                                         Can be given as a closure function or a string with a reference to a
	 *                                                                         function that will be called with call_user_func().
	 * @param mixed                                                      $opts Variable that is passed through to the validation
	 *                                                                         function - can be useful for passing through extra information such as
	 *                                                                         date formatting string, or a required flag. The actual options available
	 *                                                                         depend upon the validation function used.
	 *
	 * @return ($_ is null ? array[] : $this) The validation method if no parameter is given.
	 */
	public function validator($_ = null, $opts = null)
	{
		if ($_ === null) {
			return $this->_validator;
		}

		$this->_validator[] = [
			'func' => $_,
			'opts' => $opts,
		];

		return $this;
	}

	/**
	 * Set a formatting method that will be used for XSS checking / removal.
	 * This should be a function that takes a single argument (the value to be
	 * cleaned) and returns the cleaned value.
	 *
	 * Editor will use HtmLawed by default for this operation, which is built
	 * into the software and no additional configuration is required, but a
	 * custom function can be used if you wish to use a different formatter such
	 * as HTMLPurifier.
	 *
	 * If you wish to disable this option (which you would only do if you are
	 * absolutely confident that your validation will pick up on any XSS inputs)
	 * simply provide a closure function that returns the value given to the
	 * function. This is _not_ recommended.
	 *
	 * @param callable(mixed): string|false $xssFormatter XSS cleaner function, use `false` or
	 *                                                    `null` to disable XSS cleaning.
	 *
	 * @return $this
	 */
	public function xss($xssFormatter)
	{
		if ($xssFormatter === true || $xssFormatter === false || $xssFormatter === null) {
			$this->_xssFormat = $xssFormatter;
		} else {
			$this->_xss = $xssFormatter;
		}

		return $this;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 * Used by the Editor class and not generally for public use
	 */

	/**
	 * Check to see if a field should be used for a particular action (get or set).
	 *
	 * Called by the Editor / Join class instances - not expected for general
	 * consumption - internal.
	 *
	 * @param string $action Direction that the data is travelling  - 'get' is
	 *                       reading DB data, `create` and `edit` for writing to the DB
	 * @param array  $data   Data submitted from the client-side when setting.
	 *
	 * @return bool true if the field should be used in the get / set.
	 *
	 * @internal
	 */
	public function apply($action, $data = null)
	{
		if ($action === 'get') {
			// Get action - can we get this field
			return $this->_get;
		}

		// Note that validation must be done on input data before we get here

		// Create or edit action, are we configured to use this field
		if (
			$action === 'create'
			&& ($this->_set === Field::SET_NONE || $this->_set === Field::SET_EDIT)
		) {
			return false;
		} elseif (
			$action === 'edit'
			&& ($this->_set === Field::SET_NONE || $this->_set === Field::SET_CREATE)
		) {
			return false;
		}

		// Check it was in the submitted data. If not, then not required
		// (validation would have failed if it was) and therefore we don't
		// set it. Check for a value as well, as it can format data from
		// some other source
		if ($this->_setValue === null && !$this->_inData($this->name(), $data)) {
			return false;
		}

		// In the data set, so use it
		return true;
	}

	/**
	 * Execute the spopts to get the list of options for SearchPanes to return
	 * to the client-side.
	 *
	 * @param self   $field    The field to retrieve the data from
	 * @param Editor $editor   The editor instance
	 * @param array  $http     The http request sent to the server
	 * @param self[] $fields   All of the fields
	 * @param array  $leftJoin Info for a leftJoin if required
	 *
	 * @return array|bool
	 *
	 * @internal
	 */
	public function searchPaneOptionsExec($field, $editor, $http, $fields, $leftJoin)
	{
		if ($this->_spoptsFn) {
			$fn = $this->_spoptsFn;

			return $fn($editor->db(), $editor);
		} elseif ($this->_spopts) {
			return $this->_spopts->exec($field, $editor, $http, $fields, $leftJoin);
		}

		return false;
	}

	/**
	 * Execute the spopts to get the list of options for SearchBuilder to return
	 * to the client-side.
	 *
	 * @param self   $field    The field to retrieve the data from
	 * @param Editor $editor   The editor instance
	 * @param array  $http     The http request sent to the server
	 * @param self[] $fields   All of the fields
	 * @param array  $leftJoin Info for a leftJoin if required
	 *
	 * @return array|bool
	 *
	 * @internal
	 */
	public function searchBuilderOptionsExec($field, $editor, $http, $fields, $leftJoin)
	{
		if ($this->_sboptsFn) {
			$fn = $this->_sboptsFn;

			return $fn($editor->db(), $editor);
		} elseif ($this->_sbopts) {
			return $this->_sbopts->exec($field, $editor, $http, $fields, $leftJoin);
		}

		return false;
	}

	/**
	 * Get the value of the field, taking into account if it is coming from the
	 * DB or from a POST. If formatting has been specified for this field, it
	 * will be applied here.
	 *
	 * Called by the Editor / Join class instances - not expected for general
	 * consumption - internal.
	 *
	 * @param string $direction Direction that the data is travelling  - 'get' is
	 *                          reading data, and 'set' is writing it to the DB.
	 * @param array  $data      Data submitted from the client-side when setting or the
	 *                          data for the row when getting data from the DB.
	 *
	 * @return string Value for the field
	 *
	 * @internal
	 */
	public function val($direction, $data)
	{
		if ($direction === 'get') {
			if ($this->_getValue !== null) {
				$val = $this->_getAssignedValue($this->_getValue);
			} else {
				// Getting data, so the db field name
				$val = isset($data[$this->_dbField]) ?
					$data[$this->_dbField] :
					null;
			}

			return $this->_format(
				$val,
				$data,
				$this->_getFormatter,
				$this->_getFormatterOpts
			);
		}

		// Sanity check that we aren't operating on a function
		if (strpos($this->dbField(), '(') !== false) {
			throw new \Exception('Cannot set the value for an SQL function field. These fields are read only: ' . $this->name());
		}

		// Setting data, so using from the payload (POST usually) and thus
		// use the 'name'
		$val = $this->_setValue !== null ?
			$this->_getAssignedValue($this->_setValue) :
			$this->_readProp($this->name(), $data);

		// XSS removal / checker
		if ($this->_xssFormat && $val) {
			$val = $this->xssSafety($val);
		}

		return $this->_format(
			$val,
			$data,
			$this->_setFormatter,
			$this->_setFormatterOpts
		);
	}

	/**
	 * Check the validity of the field based on the data submitted. Note that
	 * this validation is performed on the wire data - i.e. that which is
	 * submitted, before any setFormatter is run.
	 *
	 * Called by the Editor / Join class instances - not expected for general
	 * consumption - internal.
	 *
	 * @param array  $data   Data submitted from the client-side
	 * @param Editor $editor Editor instance
	 * @param mixed  $id     Row id that is being validated
	 *
	 * @return bool|string `true` if valid, string with error message if not
	 *
	 * @internal
	 */
	public function validate($data, $editor, $id = null)
	{
		// Three cases for the validator - closure, string or null
		if (!count($this->_validator)) {
			return true;
		}

		// Value could be from user data, or setValue might take priority
		$val = $this->_setValue !== null ?
			$this->_getAssignedValue($this->_setValue) :
			$this->_readProp($this->name(), $data);

		$processData = $editor->inData();
		$instances = [
			'action' => $processData['action'],
			'id' => $id,
			'field' => $this,
			'editor' => $editor,
			'db' => $editor->db(),
		];

		for ($i = 0, $ien = count($this->_validator); $i < $ien; ++$i) {
			$validator = $this->_validator[$i];

			// Backwards compatibility
			if (is_string($validator['func'])) {
				if (strpos($validator['func'], 'Validate::') === 0) {
					$a = explode('::', $validator['func']);

					// Validate class static methods - they have `Legacy` counter parts that
					// convert from the old style to the new so the old style options still work.
					if (method_exists('DataTables\\Editor\\' . $a[0], $a[1] . 'Legacy')) {
						$func = call_user_func('DataTables\\Editor\\' . $validator['func'] . 'Legacy', $validator['opts']);
						$res = call_user_func($func, $val, $data, $this, $instances);
					} else {
						// User style legacy function. Call it directly
						$func = 'DataTables\\Editor\\' . $validator['func'];
						$res = call_user_func($func, $val, $data, $this, $instances);
					}
				} else {
					// And for cases where a string was used to point to a function,
					// which was not in the Validate class
					$res = call_user_func($validator['func'], $val, $data, $validator['opts'], $instances);
				}
			} else {
				$func = $validator['func'];
				$res = $func($val, $data, $this, $instances);
			}

			// Check if there was a validation error and if so, return it
			if ($res !== true) {
				return $res;
			}
		}

		// Validation methods all run, must be valid
		return true;
	}

	/**
	 * Write the value for this field to the output array for a read operation.
	 *
	 * @param array $out     Row output data (to the JSON)
	 * @param mixed $srcData Row input data (raw, from the database)
	 *
	 * @internal
	 */
	public function write(&$out, $srcData)
	{
		$this->_writeProp($out, $this->name(), $this->val('get', $srcData));
	}

	/**
	 * Perform XSS prevention on an input.
	 *
	 * @param mixed $val Value to be escaped
	 *
	 * @return string Safe value
	 */
	public function xssSafety($val)
	{
		$xss = $this->_xss;

		if (is_array($val)) {
			$res = [];

			foreach ($val as $individual) {
				$res[] = $xss ?
					$xss($individual) :
					HtmLawedVanillaWrapper::filter($individual);
			}

			return $res;
		}

		return $xss ?
			$xss($val) :
			HtmLawedVanillaWrapper::filter($val);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private methods
	 */

	/**
	 * Apply a formatter to data. The caller will decide what formatter to apply
	 * (get or set).
	 *
	 * @param mixed                                 $val       Value to be formatted
	 * @param array                                 $data      Full row data
	 * @param callable(mixed, array, array): string $formatter Formatting function to be called
	 * @param array                                 $opts      Array of options to be passed to the formatter
	 *
	 * @return string Formatted value
	 */
	private function _format($val, $data, $formatter, $opts)
	{
		// Three cases for the formatter - closure, string or null
		if (!$formatter) {
			return $val;
		}

		if (!is_string($formatter)) {
			return $formatter($val, $data, $opts);
		}

		// Backwards compatibility - strings will not be supported in v2
		if (strpos($formatter, 'Format::') === 0) {
			$a = explode('::', $formatter);

			// Old style Editor formatter - use the legacy functions to
			// convert to the new style
			if (method_exists('DataTables\\Editor\\' . $a[0], $a[1] . 'Legacy')) {
				$func = call_user_func('DataTables\\Editor\\' . $formatter . 'Legacy', $opts);

				return $func($val, $data);
			}

			// User added old style methods
			return call_user_func('DataTables\\Editor\\' . $formatter, $val, $data, $opts);
		}

		// User function (string identifier)
		return call_user_func($formatter, $val, $data, $opts);
	}

	/**
	 * Get the value from `_[gs]etValue` - taking into account if it is callable
	 * function or not.
	 *
	 * @param mixed $val Value to be evaluated
	 *
	 * @return mixed Value assigned, or returned from the function
	 */
	private function _getAssignedValue($val)
	{
		return $val instanceof \Closure ?
			$val() :
			$val;
	}

	/**
	 * Check is a parameter is in the submitted data set. This is functionally
	 * the same as the `_readProp()` method, but in this case a binary value
	 * is required to indicate if the value is present or not.
	 *
	 * @param string $name Javascript dotted object name to write to
	 * @param array  $data Data source array to read from
	 *
	 * @return bool `true` if present, `false` otherwise
	 */
	private function _inData($name, $data)
	{
		if (strpos($name, '.') === false) {
			return isset($data[$name]) ?
				true :
				false;
		}

		$names = explode('.', $name);
		$inner = $data;

		for ($i = 0; $i < count($names) - 1; ++$i) {
			if (!isset($inner[$names[$i]])) {
				return false;
			}

			$inner = $inner[$names[$i]];
		}

		return isset($inner[$names[count($names) - 1]]) ?
			true :
			false;
	}
}
