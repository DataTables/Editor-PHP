<?php

/**
 * PHP libraries for DataTables and its extensions.
 *
 * @author    SpryMedia
 * @copyright SpryMedia ( http://sprymedia.co.uk )
 * @license   https://datatables.net/license
 */

namespace DataTables;

use DataTables\Database\Query;
use DataTables\DataTable\Column;
use DataTables\Editor\Join;

/**
 * This class let's you define the structure of a database, in order for it to
 * be read and the data returned to DataTables.
 *
 * Typically you will:
 *
 * * Create the instance
 * * Define the columns
 * * Process the request
 * * Return JSON to the client-side
 *
 * You may also wish to add query conditions, or provide extra pre-column
 * options for features such as ColumnControl.
 *
 * @example
 * A very basic example of using the DataTable class to get data from a database
 * and return it to the client, for DataTables to process and display.
 *
 * ```php
 *   new DataTable( $db, 'browsers' )
 *       ->columns(
 *           new Column('first_name'),
 *           new Column('last_name'),
 *           new Column('country'),
 *           new Column('details')
 *       )
 *       ->process( $_POST )
 *       ->json();
 * ```
 */
class DataTable extends Ext
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Constructor.
	 *
	 * @param Database     $db    An instance of the DataTables Database class that we can
	 *                            use for the DB connection. Can be given here or with the 'db' method.
	 * @param string|array $table The table name in the database to read and write
	 *                            information from and to. Can be given here or with the 'table' method.
	 * @param string|array $pkey  Primary key column name in the table given in
	 *                            the $table parameter. Can be given here or with the 'pkey' method.
	 */
	public function __construct($db = null, $table = null, $pkey = null)
	{
		$this->_editor = new Editor($db, $table, $pkey);
		$this->_editor->write(false);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public properties
	 */

	/** @var string */
	public $version = Editor::VERSION;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */

	/** @var Editor */
	private $_editor;

	/** @var Column[] */
	private $_columns = [];

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Get the data constructed in this instance.
	 *
	 * This will get the PHP array of data that has been constructed for the
	 * command that has been processed by this instance. Therefore only useful
	 * after process has been called.
	 *
	 * @return array Processed data array.
	 */
	public function data()
	{
		return $this->_editor->data();
	}

	/**
	 * Get / set the DB connection instance.
	 *
	 * @param Database $_ DataTable's Database class instance to use for database
	 *                    connectivity. If not given, then used as a getter.
	 *
	 * @return ($_ is null ? Database : $this) The Database connection instance if no parameter
	 *                                         is given.
	 */
	public function db($_ = null)
	{
		return $this->_proxy($this->_editor, 'db', func_get_args());
	}

	/**
	 * Get / set debug mode and set a debug message.
	 *
	 * It can be useful to see the SQL statements that Editor is using. This
	 * method enables that ability. Information about the queries used is
	 * automatically added to the output data array / JSON under the property
	 * name `debugSql`.
	 *
	 * This method can also be called with a string parameter, which will be
	 * added to the debug information sent back to the client-side. This can be
	 * useful when debugging event listeners, etc.
	 *
	 * @param bool|mixed $_    Debug mode state. If not given, then used as a
	 *                         getter. If given as anything other than a
	 *                         boolean, it will be added to the debug
	 *                         information sent back to the client.
	 * @param string     $path Set an output path to log debug information
	 *
	 * @return ($_ is null ? bool : $this) Debug mode state if no parameter is
	 *                                     given.
	 */
	public function debug($_ = null, $path = null)
	{
		return $this->_proxy($this->_editor, 'debug', func_get_args());
	}

	/**
	 * Get / set a column instance.
	 *
	 * @param Column|string $_ The name of the column to get the instance of, or
	 *                         the column instance to add.
	 *
	 * @return ($_ is string ? Column : $this) Column, or self for chaining if
	 *                                         used as a setter.
	 */
	public function column($_ = null)
	{
		if (is_string($_)) {
			for ($i = 0, $ien = count($this->_columns); $i < $ien; ++$i) {
				if ($this->_columns[$i]->name() === $_) {
					return $this->_columns[$i];
				}
			}

			throw new \Exception('Unknown column: ' . $_);
		}

		return $this->columns($_);
	}

	/**
	 * Get / set the columns for the table.
	 *
	 * @param Column|Column[] ...$_ Instances of the {@see Column} class, given
	 *                              as a single instance of {@see Column}, an
	 *                              array of {@see Column} instances, or multiple
	 *                              {@see Column} instance parameters for the
	 *                              function.
	 *
	 * @return ($_ is null ? Column[] : $this) Array of columns.
	 *
	 * @see {@see Column} for column documentation.
	 */
	public function columns($_ = null)
	{
		if ($_ === null) {
			return $this->_columns;
		}

		$args = func_get_args();

		if (!is_array($_)) {
			$_ = $args;
		}

		for ($i = 0; $i < count($_); ++$i) {
			$this->_columns[] = $_[$i];
			$this->_editor->field($_[$i]->field());
		}

		return $this;
	}

	/**
	 * Get / set the DOM prefix.
	 *
	 * Typically primary keys are numeric and this is not a valid ID value in an
	 * HTML document - is also increases the likelihood of an ID clash if
	 * multiple tables are used on a single page. As such, a prefix is assigned
	 * to the primary key value for each row, and this is used as the DOM ID.
	 *
	 * @param string $_ Primary key's name. If not given, then used as a getter.
	 *
	 * @return ($_ is null ? string : $this) Primary key value if no parameter
	 *                                       is given.
	 */
	public function idPrefix($_ = null)
	{
		return $this->_proxy($this->_editor, 'idPrefix', func_get_args());
	}

	/**
	 * Get / set join instances. Note that for the majority of use cases you
	 * will want to use the `leftJoin()` method. It is significantly easier
	 * to use if you are just doing a simple left join!
	 *
	 * @param Join ...$_ Instances of the {@see Join} class, given as a
	 *                   single instance of {@see Join}, an array of {@see Join} instances,
	 *                   or multiple {@see Join} instance parameters for the function.
	 *
	 * @return ($_ is null ? Join[] : $this) Array of joins.
	 */
	public function join($_ = null)
	{
		return $this->_proxy($this->_editor, 'join', func_get_args());
	}

	/**
	 * Get the JSON for the data constructed in this instance.
	 *
	 * Basically the same as the {@see Editor->data()} method, but in this case we echo, or
	 * return the JSON string of the data.
	 *
	 * @param bool $print   Echo the JSON string out (true, default) or return it
	 *                      (false).
	 * @param int  $options JSON encode option https://www.php.net/manual/en/json.constants.php
	 *
	 * @return ($print is false ? string : $this) JSON representation of the processed data if
	 *                                            false is given as the first parameter.
	 */
	public function json($print = true, $options = 0)
	{
		return $this->_editor->json($print, $options);
	}

	/**
	 * Add a left join condition to the Editor instance, allowing it to operate
	 * over multiple tables. Multiple `leftJoin()` calls can be made for a
	 * single Editor instance to join multiple tables.
	 *
	 * @param string $table    Table name to do a join onto
	 * @param string $field1   Field from the parent table to use as the join
	 * @param string $operator Join condition (`=`, '<`, etc)
	 * @param string $field2   Field from the child table to use as the join
	 *
	 * @return $this
	 */
	public function leftJoin($table, $field1, $operator = null, $field2 = null)
	{
		$this->_editor->leftJoin($table, $field1, $operator, $field2);

		return $this;
	}

	/**
	 * Get / set the primary key.
	 *
	 * The primary key must be known uniquely identify each row.
	 *
	 * @param string|string[] $_ Primary key's name. If not given, then used as
	 *                           a getter. An array of column names can be given
	 *                           to allow composite keys to be used.
	 *
	 * @return ($_ is null ? string[] : $this) Primary key value if no parameter
	 *                                         is given.
	 */
	public function pkey($_ = null)
	{
		return $this->_proxy($this->_editor, 'pkey', func_get_args());
	}

	/**
	 * Process a request from the client-side to get / set data.
	 *
	 * @param array $data Typically $_POST or $_GET if used with server-side
	 *                    processing mode, but is not required (client-side processing).
	 *
	 * @return $this
	 */
	public function process($data = [])
	{
		$this->_editor->process($data);

		return $this;
	}

	/**
	 * Get / set the table name.
	 *
	 * The table name designated which DB table will be used as its data source
	 * for working with the database. Table names can be given with an alias,
	 * which can be used to simplify larger table names. The field names would
	 * also need to reflect the alias, just like an SQL query. For example:
	 * `users as a`.
	 *
	 * @param string|array ...$_ Table names given as a single string, an array
	 *                           of strings or multiple string parameters for
	 *                           the function.
	 *
	 * @return ($_ is null ? string[] : $this) Array of tables names.
	 */
	public function table($_ = null)
	{
		return $this->_proxy($this->_editor, 'table', func_get_args());
	}

	/**
	 * Where condition to add to the query used to get data from the database.
	 *
	 * Can be used in two different ways:
	 *
	 * * Simple case: `where( field, value, operator )`
	 * * Complex: `where( fn )`
	 *
	 * The simple case is fairly self explanatory, a condition is applied to the
	 * data that looks like `field operator value` (e.g. `name = 'Allan'`). The
	 * complex case allows full control over the query conditions by providing a
	 * closure function that has access to the database Query that is being
	 * using, so you can use the `where()`, `or_where()`, `and_where()` and
	 * `where_group()` methods as you require.
	 *
	 * @param string|\Closure(Query): void $key   Single field name or a closure function
	 * @param string                       $value Single field value.
	 * @param string                       $op    Condition operator: <, >, = etc
	 *
	 * @return ($key is null ? string[] : $this) Where condition array.
	 */
	public function where($key = null, $value = null, $op = '=')
	{
		return $this->_proxy($this->_editor, 'where', func_get_args());
	}
}
