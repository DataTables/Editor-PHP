<?php

namespace DataTables\DataTable;

use DataTables\Database;
use DataTables\Editor;
use DataTables\Editor\Field;
use DataTables\Ext;

/**
 * Column configuration object. This is used to define how a column should be
 * read from a database table.
 *
 * This class is largely a proxy to `\DataTables\Editor\Field`, exposing only
 * the read aspects of the class, and not being writable.
 */
class Column extends Ext
{
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
		$this->_field = new Field($dbField, $name);
		$this->_field->set(false);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */

	/** @var Field */
	private $_field;

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
		return $this->_proxy($this->_field, 'dbField', func_get_args());
	}

	/**
	 * Options for ColumnControl's searchList content type.
	 *
	 * @param Options $_ Options
	 *
	 * @return ($_ is null ? Options|null : $this)
	 */
	public function columnControl($_ = null)
	{
		return $this->_proxy($this->_field, 'columnControl', func_get_args());
	}

	/**
	 * Get formatter for the columns's data.
	 *
	 * When the data has been retrieved from the server, it can be passed through
	 * a formatter here, which will manipulate (format) the data as required. This
	 * can be useful when, for example, working with dates and a particular format
	 * is required on the client-side.
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
		return $this->_proxy($this->_field, 'getFormatter', func_get_args());
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
		return $this->_proxy($this->_field, 'getValue', func_get_args());
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
		return $this->_proxy($this->_field, 'name', func_get_args());
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
		return $this->_proxy($this->_field, 'searchPaneOptions', func_get_args());
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
		return $this->_proxy($this->_field, 'searchBuilderOptions', func_get_args());
	}

	/**
	 * Get the field instance associated with this column.
	 *
	 * @return Editor\Field
	 *
	 * @internal For use internally only
	 */
	public function field()
	{
		return $this->_field;
	}
}
