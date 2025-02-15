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

namespace DataTables\Database;

use DataTables\Database;

//
// This is a stub class that a driver must extend and complete
//

/**
 * Perform an individual query on the database.
 *
 * The typical pattern for using this class is through the {@see
 * Database->query()} method (and it's 'select', etc short-cuts).
 * Typically it would not be initialised directly.
 *
 * Note that this is a stub class that a driver will extend and complete as
 * required for individual database types. Individual drivers could add
 * additional methods, but this is discouraged to ensure that the API is the
 * same for all database types.
 */
abstract class Query
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Query instance constructor.
	 *
	 * Note that typically instances of this class will be automatically created
	 * through the {@see Database->query()} method.
	 *
	 * @param Database        $dbHost Database instance
	 * @param string          $type   Query type - 'select', 'insert', 'update' or 'delete'
	 * @param string|string[] $table  Tables to operate on - see {@see Query->table()}.
	 */
	public function __construct($dbHost, $type, $table = null)
	{
		$this->_dbHost = $dbHost;
		$this->_type = $type;
		$this->table($table);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */

	/**
	 * @var Database Database connection instance
	 *
	 * @internal
	 */
	protected $_dbHost;

	/**
	 * @var string Driver to use
	 *
	 * @internal
	 */
	protected $_type = '';

	/**
	 * @var array
	 *
	 * @internal
	 */
	protected $_table = [];

	/**
	 * @var array
	 *
	 * @internal
	 */
	protected $_field = [];

	/**
	 * @var array
	 *
	 * @internal
	 */
	protected $_bindings = [];

	/**
	 * @var array
	 *
	 * @internal
	 */
	protected $_where = [];

	/**
	 * @var array
	 *
	 * @internal
	 */
	protected $_join = [];

	/**
	 * @var array
	 *
	 * @internal
	 */
	protected $_order = [];

	/**
	 * @var array
	 *
	 * @internal
	 */
	protected $_noBind = [];

	/**
	 * @var int
	 *
	 * @internal
	 */
	protected $_limit;

	/**
	 * @var string
	 *
	 * @internal
	 */
	protected $_group_by;

	/**
	 * @var int
	 *
	 * @internal
	 */
	protected $_offset;

	/**
	 * @var bool
	 *
	 * @internal
	 */
	protected $_distinct = false;

	/**
	 * @var array{string, string}
	 *
	 * @internal
	 */
	protected $_identifier_limiter = ['`', '`'];

	/**
	 * @var string
	 *
	 * @internal
	 */
	protected $_field_quote = '\'';

	/**
	 * @var array
	 *
	 * @internal
	 */
	protected $_pkey;

	protected $_supportsAsAlias = true;

	protected $_whereInCnt = 1;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Static methods
	 */

	/**
	 * Commit a transaction.
	 *
	 * @param \PDO $dbh The Database handle (typically a PDO object, but not always).
	 */
	public static function commit($dbh)
	{
		$dbh->commit();
	}

	/**
	 * Database connection - override by the database driver.
	 *
	 * @param string|array $user User name or all parameters in an array
	 * @param string       $pass Password
	 * @param string       $host Host name
	 * @param string       $db   Database name
	 *
	 * @return \PDO
	 */
	public static function connect($user, $pass = '', $host = '', $port = '', $db = '', $dsn = '')
	{
		// noop - PHP <7 can't have an abstract static without causing
		// an error in strict mode. This should technically be an
		// abstract method however.
	}

	/**
	 * Start a database transaction.
	 *
	 * @param \PDO $dbh The Database handle (typically a PDO object, but not always).
	 */
	public static function transaction($dbh)
	{
		$dbh->beginTransaction();
	}

	/**
	 * Rollback the database state to the start of the transaction.
	 *
	 * @param \PDO $dbh The Database handle (typically a PDO object, but not always).
	 */
	public static function rollback($dbh)
	{
		$dbh->rollBack();
	}

	/**
	 * Common helper for the drivers to handle a PDO DSN postfix.
	 *
	 * @param string $dsn DSN postfix to use
	 *
	 * @return string
	 *
	 * @internal
	 */
	public static function dsnPostfix($dsn)
	{
		if (!$dsn) {
			return '';
		}

		// Add a DSN field separator if not given
		if (strpos($dsn, ';') !== 0) {
			return ';' . $dsn;
		}

		return $dsn;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Safely bind an input value to a parameter. This is evaluated when the
	 * query is executed. This allows user input to be safely executed without
	 * risk of an SQL injection attack.
	 *
	 * @param string $name  Parameter name. This should include a leading colon
	 * @param string $value Value to bind
	 * @param mixed  $type  Data type. See the PHP PDO documentation:
	 *                      http://php.net/manual/en/pdo.constants.php
	 *
	 * @return $this
	 */
	public function bind($name, $value, $type = null)
	{
		$this->_bindings[] = [
			'name' => $this->_safe_bind($name),
			'value' => $value,
			'type' => $type,
		];

		return $this;
	}

	/**
	 * Get the Database host for this query instance.
	 *
	 * @return Database Database class instance
	 */
	public function database()
	{
		return $this->_dbHost;
	}

	/**
	 * Set a distinct flag for a `select` query. Note that this has no effect on
	 * any of the other query types.
	 *
	 * @param bool $dis Optional
	 *
	 * @return $this
	 */
	public function distinct($dis)
	{
		$this->_distinct = $dis;

		return $this;
	}

	/**
	 * Execute the query.
	 *
	 * @param string $sql SQL string to execute (only if _type is 'raw').
	 *
	 * @return Result
	 */
	public function exec($sql = null)
	{
		$type = strtolower($this->_type);

		if ($type === 'select') {
			return $this->_select();
		} elseif ($type === 'insert') {
			return $this->_insert();
		} elseif ($type === 'update') {
			return $this->_update();
		} elseif ($type === 'delete') {
			return $this->_delete();
		} elseif ($type === 'count') {
			return $this->_count();
		} elseif ($type === 'raw') {
			return $this->_raw($sql);
		}

		throw new \Exception('Unknown database command or not supported: ' . $type, 1);
	}

	/**
	 * Get fields.
	 *
	 * @param string|string[] ...$get Fields to get - can be specified as
	 *                                individual fields or an array of fields.
	 *
	 * @return $this
	 */
	public function get($get)
	{
		$args = func_get_args();

		if ($get === null) {
			return $this;
		}

		for ($i = 0; $i < count($args); ++$i) {
			// If argument is an array then we loop over and add each using a
			// recursive call
			if (is_array($args[$i])) {
				for ($j = 0; $j < count($args[$i]); ++$j) {
					$this->get($args[$i][$j]);
				}
			} elseif (strpos($args[$i], ',') !== false && strpos($args[$i], '(') === false) {
				// Comma delimited set of fields - legacy. Recommended that fields be split into
				// an array on input
				$a = explode(',', $args[$i]);

				for ($j = 0; $j < count($a); ++$j) {
					$this->get($a[$j]);
				}
			} else {
				$this->_field[] = trim($args[$i]);
			}
		}

		return $this;
	}

	/**
	 * Perform a JOIN operation.
	 *
	 * @param string $table     Table name to do the JOIN on
	 * @param string $condition JOIN condition
	 * @param string $type      JOIN type
	 *
	 * @return $this
	 */
	public function join($table, $condition, $type = '', $bind = true)
	{
		// Tidy and check we know what the join type is
		if ($type !== '') {
			$type = strtoupper(trim($type));

			if (!in_array($type, ['LEFT', 'RIGHT', 'INNER', 'OUTER', 'LEFT OUTER', 'RIGHT OUTER'])) {
				$type = '';
			}
		}

		// Protect the identifiers
		if ($bind && preg_match('/([\w\.]+)([\W\s]+)(.+)/', $condition, $match)) {
			$match[1] = $this->_protect_identifiers($match[1]);
			$match[3] = $this->_protect_identifiers($match[3]);

			$condition = $match[1] . $match[2] . $match[3];
		}

		$this->_join[] = $type . ' JOIN ' . $this->_protect_identifiers($table) . ' ON ' . $condition . ' ';

		return $this;
	}

	/**
	 * Add a left join, with common logic for handling binding or not.
	 *
	 * @return $this
	 */
	public function left_join($joins)
	{
		// Allow a single associative array
		if ($this->_is_assoc($joins)) {
			$joins = [
				$joins,
			];
		}

		for ($i = 0, $ien = count($joins); $i < $ien; ++$i) {
			$join = $joins[$i];

			if ($join['field2'] === null && $join['operator'] === null) {
				$this->join(
					$join['table'],
					$join['field1'],
					'LEFT',
					false
				);
			} else {
				$this->join(
					$join['table'],
					$join['field1'] . ' ' . $join['operator'] . ' ' . $join['field2'],
					'LEFT'
				);
			}
		}

		return $this;
	}

	/**
	 * Limit the result set to a certain size.
	 *
	 * @param int $lim The number of records to limit the result to.
	 *
	 * @return $this
	 */
	public function limit($lim)
	{
		$this->_limit = $lim;

		return $this;
	}

	/**
	 * Group the results by the values in a field.
	 *
	 * @param string $group_by The field of which the values are to be grouped
	 *
	 * @return $this
	 */
	public function group_by($group_by)
	{
		$this->_group_by = $group_by;

		return $this;
	}

	/**
	 * Get / set the primary key column name(s) so they can be easily returned
	 * after an insert.
	 *
	 * @param string|string[] $pkey Primary keys
	 *
	 * @return ($pkey is null ? string[] : $this)
	 */
	public function pkey($pkey = null)
	{
		if ($pkey === null) {
			return $this->_pkey;
		}

		if (is_string($pkey)) {
			$this->_pkey = [$pkey];

			return $this;
		}

		$this->_pkey = $pkey;

		return $this;
	}

	/**
	 * Set table(s) to perform the query on.
	 *
	 * @param string|string[] ...$table Table(s) to use - can be specified as
	 *                                  individual names, an array of names, a string of comma separated
	 *                                  names or any combination of those.
	 *
	 * @return $this
	 */
	public function table($table)
	{
		if ($table === null) {
			return $this;
		}

		if (is_array($table)) {
			// Array so loop internally
			for ($i = 0; $i < count($table); ++$i) {
				$this->table($table[$i]);
			}
		} else {
			// String based, explode for multiple tables
			$tables = explode(',', $table);

			for ($i = 0; $i < count($tables); ++$i) {
				$this->_table[] = $this->_protect_identifiers(trim($tables[$i]));
			}
		}

		return $this;
	}

	/**
	 * Offset the return set by a given number of records (useful for paging).
	 *
	 * @param int $off The number of records to offset the result by.
	 *
	 * @return $this
	 */
	public function offset($off)
	{
		$this->_offset = $off;

		return $this;
	}

	/**
	 * Order by.
	 *
	 * @param string|string[] $order Columns and direction to order by - can
	 *                               be specified as individual names, an array of names, a string of comma
	 *                               separated names or any combination of those.
	 *
	 * @return $this
	 */
	public function order($order)
	{
		if ($order === null) {
			return $this;
		}

		if (!is_array($order)) {
			$order = preg_split('/\,(?![^\(]*\))/', $order);
		}

		for ($i = 0; $i < count($order); ++$i) {
			// Simplify the white-space
			$order[$i] = trim(preg_replace('/[\t ]+/', ' ', $order[$i]));

			// Find the identifier so we don't escape that
			if (strpos($order[$i], ' ') !== false) {
				$direction = strstr($order[$i], ' ');
				$identifier = substr($order[$i], 0, -strlen($direction));
			} else {
				$direction = '';
				$identifier = $order[$i];
			}

			$this->_order[] = $this->_protect_identifiers($identifier) . ' ' . $direction;
		}

		return $this;
	}

	/**
	 * Set fields to a given value.
	 *
	 * Can be used in two different ways, as set( field, value ) or as an array of
	 * fields to set: set( [ 'fieldName' => 'value', ...] );
	 *
	 * @param string|string[] $set  Can be given as a single string, when then $val
	 *                              must be set, or as an array of key/value pairs to be set.
	 * @param string          $val  When $set is given as a simple string, $set is the field
	 *                              name and this is the field's value.
	 * @param bool            $bind Should the value be bound or not
	 *
	 * @return $this
	 */
	public function set($set, $val = null, $bind = true)
	{
		if ($set === null) {
			return $this;
		}

		if (!is_array($set)) {
			$set = [$set => $val];
		}

		foreach ($set as $key => $value) {
			$this->_field[] = $key;

			if ($bind) {
				$this->bind(':' . $key, $value);
			} else {
				$this->_noBind[$key] = $value;
			}
		}

		return $this;
	}

	/**
	 * Where query - multiple conditions are bound as ANDs.
	 *
	 * Can be used in two different ways, as where( field, value ) or as an array of
	 * conditions to use: where( ['fieldName', ...], ['value', ...] );
	 *
	 * @param string|string[]|\Closure($this): void $key   Single field name, or an array of field names.
	 *                                                     If given as a function (i.e. a closure), the function is called, passing the
	 *                                                     query itself in as the only parameter, so the function can add extra conditions
	 *                                                     with parentheses around the additional parameters.
	 * @param string|int|string[]|int[]             $value Single field value, or an array of
	 *                                                     values. Can be null to search for `IS NULL` or `IS NOT NULL` (depending
	 *                                                     on the value of `$op` which should be `=` or `!=`.
	 * @param string                                $op    Condition operator: <, >, = etc
	 * @param bool                                  $bind  Escape the value (true, default) or not (false).
	 *
	 * @return $this
	 *
	 * @example
	 *     The following will produce
	 *     `'WHERE name='allan' AND ( location='Scotland' OR location='Canada' )`:
	 *
	 *     ```php
	 *       $query
	 *         ->where( 'name', 'allan' )
	 *         ->where( function ($q) {
	 *           $q->where( 'location', 'Scotland' );
	 *           $q->or_where( 'location', 'Canada' );
	 *         } );
	 *     ```
	 */
	public function where($key, $value = null, $op = '=', $bind = true)
	{
		if ($key === null) {
			return $this;
		} elseif ($key instanceof \Closure) {
			$this->_where_group(true, 'AND');
			$key($this);
			$this->_where_group(false, 'OR');
		} elseif (!is_array($key) && is_array($value)) {
			for ($i = 0; $i < count($value); ++$i) {
				$this->where($key, $value[$i], $op, $bind);
			}
		} else {
			$this->_where($key, $value, 'AND ', $op, $bind);
		}

		return $this;
	}

	/**
	 * Add addition where conditions to the query with an AND operator. An alias
	 * of `where` for naming consistency.
	 *
	 * Can be used in two different ways, as where( field, value ) or as an array of
	 * conditions to use: where( ['fieldName', ...], ['value', ...] );
	 *
	 * @param string|string[]|\Closure($this): void $key   Single field name, or an array of field names.
	 *                                                     If given as a function (i.e. a closure), the function is called, passing the
	 *                                                     query itself in as the only parameter, so the function can add extra conditions
	 *                                                     with parentheses around the additional parameters.
	 * @param string|int|string[]|int[]             $value Single field value, or an array of
	 *                                                     values. Can be null to search for `IS NULL` or `IS NOT NULL` (depending
	 *                                                     on the value of `$op` which should be `=` or `!=`.
	 * @param string                                $op    Condition operator: <, >, = etc
	 * @param bool                                  $bind  Escape the value (true, default) or not (false).
	 *
	 * @return $this
	 */
	public function and_where($key, $value = null, $op = '=', $bind = true)
	{
		return $this->where($key, $value, $op, $bind);
	}

	/**
	 * Add addition where conditions to the query with an OR operator.
	 *
	 * Can be used in two different ways, as where( field, value ) or as an array of
	 * conditions to use: where( ['fieldName', ...], ['value', ...] );
	 *
	 * @param string|string[]|\Closure($this): void $key   Single field name, or an array of field names.
	 *                                                     If given as a function (i.e. a closure), the function is called, passing the
	 *                                                     query itself in as the only parameter, so the function can add extra conditions
	 *                                                     with parentheses around the additional parameters.
	 * @param string|int|string[]|int[]             $value Single field value, or an array of
	 *                                                     values. Can be null to search for `IS NULL` or `IS NOT NULL` (depending
	 *                                                     on the value of `$op` which should be `=` or `!=`.
	 * @param string                                $op    Condition operator: <, >, = etc
	 * @param bool                                  $bind  Escape the value (true, default) or not (false).
	 *
	 * @return $this
	 */
	public function or_where($key, $value = null, $op = '=', $bind = true)
	{
		if ($key === null) {
			return $this;
		} elseif ($key instanceof \Closure) {
			$this->_where_group(true, 'OR');
			$key($this);
			$this->_where_group(false, 'OR');
		} else {
			if (!is_array($key) && is_array($value)) {
				for ($i = 0; $i < count($value); ++$i) {
					$this->or_where($key, $value[$i], $op, $bind);
				}

				return $this;
			}

			$this->_where($key, $value, 'OR ', $op, $bind);
		}

		return $this;
	}

	/**
	 * Provide grouping for WHERE conditions. Use it with a callback function to
	 * automatically group any conditions applied inside the method.
	 *
	 * For legacy reasons this method also provides the ability to explicitly
	 * define if a grouping bracket should be opened or closed in the query.
	 * This method is not prefer.
	 *
	 * @param bool|\Closure($this): void $inOut If callable it will create the group
	 *                                          automatically and pass the query into the called function. For
	 *                                          legacy operations use `true` to open brackets, `false` to close.
	 * @param string                     $op    Conditional operator to use to join to the
	 *                                          preceding condition. Default `AND`.
	 *
	 * @return $this
	 *
	 * @example
	 *     ```php
	 *     $query->where_group( function ($q) {
	 *       $q->where( 'location', 'Edinburgh' );
	 *       $q->where( 'position', 'Manager' );
	 *     } );
	 *     ```
	 */
	public function where_group($inOut, $op = 'AND')
	{
		if ($inOut instanceof \Closure) {
			$this->_where_group(true, $op);
			$inOut($this);
			$this->_where_group(false, $op);
		} else {
			$this->_where_group($inOut, $op);
		}

		return $this;
	}

	/**
	 * Provide a method that can be used to perform a `WHERE ... IN (...)` query
	 * with bound values and parameters.
	 *
	 * Note this is only suitable for local values, not a sub-query. For that use
	 * `->where()` with an unbound value.
	 *
	 * @param string $field    Field name
	 * @param array  $arr      Values
	 * @param string $operator Conditional operator to use to join to the
	 *                         preceding condition. Default `AND`.
	 *
	 * @return $this
	 */
	public function where_in($field, $arr, $operator = 'AND')
	{
		if (count($arr) === 0) {
			return $this;
		}

		$binders = [];
		$prefix = ':wherein';

		// Need to build an array of the binders (having bound the values) so
		// the query can be constructed
		for ($i = 0, $ien = count($arr); $i < $ien; ++$i) {
			$binder = $prefix . $this->_whereInCnt;

			$this->bind($binder, $arr[$i]);

			$binders[] = $binder;
			++$this->_whereInCnt;
		}

		$this->_where[] = [
			'operator' => $operator,
			'group' => null,
			'field' => $this->_protect_identifiers($field),
			'query' => $this->_protect_identifiers($field) . ' IN (' . implode(', ', $binders) . ')',
		];

		return $this;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Protected methods
	 */

	/**
	 * Create a comma separated field list.
	 *
	 * @param bool $addAlias Flag to add an alias
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_field($addAlias = false)
	{
		$a = [];
		$asAlias = $this->_supportsAsAlias ?
			' as ' :
			' ';

		for ($i = 0; $i < count($this->_field); ++$i) {
			$field = $this->_field[$i];

			// Keep the name when referring to a table
			if ($addAlias && $field !== '*' && strpos($field, '(') === false) {
				$split = preg_split('/ as (?![^\(]*\))/i', $field);

				if (count($split) > 1) {
					$a[] = $this->_protect_identifiers($split[0]) . $asAlias .
						$this->_field_quote . $split[1] . $this->_field_quote;
				} else {
					$a[] = $this->_protect_identifiers($field) . $asAlias .
						$this->_field_quote . $this->_escape_field($field) . $this->_field_quote;
				}
			} elseif ($addAlias && strpos($field, '(') !== false && !strpos($field, ' as ')) {
				$a[] = $this->_protect_identifiers($field) . $asAlias .
					$this->_field_quote . $this->_escape_field($field) . $this->_field_quote;
			} else {
				$a[] = $this->_protect_identifiers($field);
			}
		}

		return ' ' . implode(', ', $a) . ' ';
	}

	/**
	 * Create a JOIN statement list.
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_join()
	{
		return implode(' ', $this->_join);
	}

	/**
	 * Create the LIMIT / OFFSET string.
	 *
	 * MySQL and Postgres style - anything else can have the driver override
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_limit()
	{
		$out = '';
		$limit = (int) $this->_limit;
		$offset = (int) $this->_offset;

		if ($limit) {
			$out .= ' LIMIT ' . $limit;
		}

		if ($offset) {
			$out .= ' OFFSET ' . $offset;
		}

		return $out;
	}

	/**
	 * Create the GROUP BY string.
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_group_by()
	{
		$out = '';

		if ($this->_group_by) {
			$out .= ' GROUP BY ' . $this->_protect_identifiers($this->_group_by);
		}

		return $out;
	}

	/**
	 * Create the ORDER BY string.
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_order()
	{
		if (count($this->_order) > 0) {
			return ' ORDER BY ' . implode(', ', $this->_order) . ' ';
		}

		return '';
	}

	/**
	 * Create a set list.
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_set()
	{
		$a = [];

		for ($i = 0; $i < count($this->_field); ++$i) {
			$field = $this->_field[$i];

			if (isset($this->_noBind[$field])) {
				$a[] = $this->_protect_identifiers($field) . ' = ' . $this->_noBind[$field];
			} else {
				$a[] = $this->_protect_identifiers($field) . ' = :' . $this->_safe_bind($field);
			}
		}

		return ' ' . implode(', ', $a) . ' ';
	}

	/**
	 * Create the TABLE list.
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_table()
	{
		if ($this->_type === 'insert') {
			// insert, update and delete statements don't need or want aliases in the table name
			$a = [];

			for ($i = 0, $ien = count($this->_table); $i < $ien; ++$i) {
				$table = str_ireplace(' as ', ' ', $this->_table[$i]);
				$tableParts = explode(' ', $table);

				$a[] = $tableParts[0];
			}

			return ' ' . implode(', ', $a) . ' ';
		}

		return ' ' . implode(', ', $this->_table) . ' ';
	}

	/**
	 * Create a bind field value list.
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_value()
	{
		$a = [];

		for ($i = 0, $ien = count($this->_field); $i < $ien; ++$i) {
			$a[] = ' :' . $this->_safe_bind($this->_field[$i]);
		}

		return ' ' . implode(', ', $a) . ' ';
	}

	/**
	 * Create the WHERE statement.
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _build_where()
	{
		if (count($this->_where) === 0) {
			return '';
		}

		$condition = 'WHERE ';

		for ($i = 0; $i < count($this->_where); ++$i) {
			if ($i === 0) {
				// Nothing (simplifies the logic!)
			} elseif ($this->_where[$i]['group'] === ')') {
				// If a group has been used but no conditions were added inside
				// of, we don't want to end up with `()` in the SQL as that is
				// invalid, so add a 1.
				if ($this->_where[$i - 1]['group'] === '(') {
					$condition .= '1=1';
				}
			// else nothing reindent once https://github.com/PHP-CS-Fixer/PHP-CS-Fixer/issues/7497 is fixed
			} elseif ($this->_where[$i - 1]['group'] === '(') {
				// Nothing
			} else {
				$condition .= $this->_where[$i]['operator'] . ' ';
			}

			if ($this->_where[$i]['group'] !== null) {
				$condition .= $this->_where[$i]['group'];
			} else {
				$condition .= $this->_where[$i]['query'] . ' ';
			}
		}

		return $condition;
	}

	/**
	 * Create a DELETE statement.
	 *
	 * @return Result
	 *
	 * @internal
	 */
	protected function _delete()
	{
		$this->_prepare(
			'DELETE FROM '
			. $this->_build_table()
			. $this->_build_where()
		);

		return $this->_exec();
	}

	/**
	 * Escape quotes in a field identifier.
	 *
	 * @internal
	 */
	protected function _escape_field($field)
	{
		$quote = $this->_field_quote;

		return str_replace($quote, '\\' . $quote, $field);
	}

	/**
	 * Execute the query. Provided by the driver.
	 *
	 * @return Result
	 *
	 * @internal
	 */
	abstract protected function _exec();

	/**
	 * Create an INSERT statement.
	 *
	 * @return Result
	 *
	 * @internal
	 */
	protected function _insert()
	{
		$this->_prepare(
			'INSERT INTO '
				. $this->_build_table() . ' ('
					. $this->_build_field()
				. ') '
			. 'VALUES ('
				. $this->_build_value()
			. ')'
		);

		return $this->_exec();
	}

	/**
	 * Prepare the SQL query by populating the bound variables.
	 * Provided by the driver.
	 *
	 * @internal
	 */
	abstract protected function _prepare($sql);

	/**
	 * Protect field names.
	 *
	 * @param string $identifier String to be protected
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _protect_identifiers($identifier)
	{
		$idl = $this->_identifier_limiter;

		// No escaping character
		if (!$idl) {
			return $identifier;
		}

		$left = $idl[0];
		$right = $idl[1];

		// Dealing with a function or other expression? Just return immediately
		if (strpos($identifier, '(') !== false || strpos($identifier, '*') !== false) {
			return $identifier;
		}

		// Going to be operating on the spaces in strings, to simplify the white-space
		$identifier = preg_replace('/[\t ]+/', ' ', $identifier);
		$identifier = str_replace(' as ', ' ', $identifier);

		// If more that a single space, then return
		if (substr_count($identifier, ' ') > 1) {
			return $identifier;
		}

		// Find if our identifier has an alias, so we don't escape that
		if (strpos($identifier, ' ') !== false) {
			$alias = strstr($identifier, ' ');
			$identifier = substr($identifier, 0, -strlen($alias));
		} else {
			$alias = '';
		}

		$a = explode('.', $identifier);

		return $left . implode($right . '.' . $left, $a) . $right . $alias;
	}

	/**
	 * Passed in SQL statement.
	 *
	 * @return Result
	 *
	 * @internal
	 */
	protected function _raw($sql)
	{
		$this->_prepare($sql);

		return $this->_exec();
	}

	/**
	 * The characters that can be used for the PDO bindValue name are quite
	 * limited (`[a-zA-Z0-9_]+`). We need to abstract this out to allow slightly
	 * more complex expressions including dots for easy aliasing.
	 *
	 * @param string $name Field name
	 *
	 * @return string
	 *
	 * @internal
	 */
	protected function _safe_bind($name)
	{
		$name = str_replace('.', '_1_', $name);
		$name = str_replace('-', '_2_', $name);
		$name = str_replace('/', '_3_', $name);
		$name = str_replace('\\', '_4_', $name);
		$name = str_replace(' ', '_5_', $name);

		return $name;
	}

	/**
	 * Create a SELECT statement.
	 *
	 * @return Result
	 *
	 * @internal
	 */
	protected function _select()
	{
		$this->_prepare(
			'SELECT ' . ($this->_distinct ? 'DISTINCT ' : '')
			. $this->_build_field(true)
			. 'FROM ' . $this->_build_table()
			. $this->_build_join()
			. $this->_build_where()
			. $this->_build_group_by()
			. $this->_build_order()
			. $this->_build_limit()
		);

		return $this->_exec();
	}

	/**
	 * Create a SELECT COUNT statement.
	 *
	 * @return Result
	 *
	 * @internal
	 */
	protected function _count()
	{
		$select = $this->_supportsAsAlias ?
			'SELECT COUNT(' . $this->_build_field() . ') as ' . $this->_protect_identifiers('cnt') :
			'SELECT COUNT(' . $this->_build_field() . ') ' . $this->_protect_identifiers('cnt');

		$this->_prepare(
			$select
			. ' FROM ' . $this->_build_table()
			. $this->_build_join()
			. $this->_build_where()
			. $this->_build_limit()
		);

		return $this->_exec();
	}

	/**
	 * Create an UPDATE statement.
	 *
	 * @return Result
	 *
	 * @internal
	 */
	protected function _update()
	{
		$this->_prepare(
			'UPDATE '
			. $this->_build_table()
			. 'SET ' . $this->_build_set()
			. $this->_build_where()
		);

		return $this->_exec();
	}

	/**
	 * Add an individual where condition to the query.
	 *
	 * @internal
	 *
	 * @param string|array $where
	 * @param string|int   $value
	 * @param string       $type
	 * @param string       $op
	 * @param bool         $bind
	 */
	protected function _where($where, $value = null, $type = 'AND ', $op = '=', $bind = true)
	{
		if ($where === null) {
			return;
		} elseif (!is_array($where)) {
			$where = [$where => $value];
		}

		foreach ($where as $key => $value) {
			$i = count($this->_where);

			if ($value === null) {
				// Null query
				$this->_where[] = [
					'operator' => $type,
					'group' => null,
					'field' => $this->_protect_identifiers($key),
					'query' => $this->_protect_identifiers($key) . ($op === '=' ?
						' IS NULL' :
						' IS NOT NULL'),
				];
			} elseif ($bind) {
				// Binding condition (i.e. escape data)
				if ($this->_dbHost->type() === 'Postgres' && $op === 'like') {
					// Postgres specific a it needs a case for string searching non-text data
					$this->_where[] = [
						'operator' => $type,
						'group' => null,
						'field' => $this->_protect_identifiers($key),
						'query' => $this->_protect_identifiers($key) . '::text ilike ' . $this->_safe_bind(':where_' . $i),
					];
				} else {
					$this->_where[] = [
						'operator' => $type,
						'group' => null,
						'field' => $this->_protect_identifiers($key),
						'query' => $this->_protect_identifiers($key) . ' ' . $op . ' ' . $this->_safe_bind(':where_' . $i),
					];
				}
				$this->bind(':where_' . $i, $value);
			} else {
				// Non-binding condition
				$this->_where[] = [
					'operator' => $type,
					'group' => null,
					'field' => null,
					'query' => $this->_protect_identifiers($key) . ' ' . $op . ' ' . $this->_protect_identifiers($value),
				];
			}
		}
	}

	/**
	 * Add parentheses to a where condition.
	 *
	 * @internal
	 */
	protected function _where_group($inOut, $op)
	{
		$this->_where[] = [
			'group' => $inOut ? '(' : ')',
			'operator' => $op,
		];
	}

	/**
	 * Check if an array is associative or sequential.
	 */
	private function _is_assoc(array $arr)
	{
		if ($arr === []) {
			return false;
		}

		return array_keys($arr) !== range(0, count($arr) - 1);
	}
}
