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

/**
 * Join table class for DataTables Editor.
 *
 * The Join class can be used with {@see Editor->join()} to allow Editor to
 * obtain joined information from the database.
 *
 * For an overview of how Join tables work, please refer to the
 * {@link https://editor.datatables.net/manual/php/ Editor manual} as it is
 * useful to understand how this class represents the links between tables,
 * before fully getting to grips with it's API.
 *
 * @example
 *    Join the parent table (the one specified in the {@see Editor->table()}
 *    method) with the table *access*, with a link table *user__access*, which
 *    allows multiple properties to be found for each row in the parent table.
 *    ```php
 *      (new Join( 'access', 'array' ))
 *          ->link( 'users.id', 'user_access.user_id' )
 *          ->link( 'access.id', 'user_access.access_id' )
 *          ->field(
 *              (new Field( 'id' ))->validator( 'Validate::required' ),
 *              new Field( 'name' )
 *          )
 *    ```
 * @example
 *    Single row join - here we join the parent table with a single row in
 *    the child table, without an intermediate link table. In this case the
 *    child table is called *extra* and the two fields give the columns that
 *    Editor will read from that table.
 *    ```php
 *        (new Join( 'extra', 'object' ))
 *            ->link( 'user.id', 'extra.user_id' )
 *            ->field(
 *                new Field( 'comments' ),
 *                new Field( 'review' )
 *            )
 *    ```
 */
class Join extends Ext
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Join instance constructor.
	 *
	 * @param string $table Table name to get the joined data from.
	 * @param string $type  Work with a single result ('object') or an array of
	 *                      results ('array') for the join.
	 */
	public function __construct($table = null, $type = 'object')
	{
		$this->table($table);
		$this->type($type);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */

	/** @var Field[] */
	private $_fields = [];

	/** @var array */
	private $_join = [
		'parent' => null,
		'child' => null,
		'table' => null,
	];

	/** @var array */
	private $_leftJoin = [];

	/** @var string */
	private $_table;

	/** @var string */
	private $_type;

	/** @var string */
	private $_name;

	/** @var bool */
	private $_get = true;

	/** @var bool */
	private $_set = true;

	/** @var string */
	private $_aliasParentTable;

	/** @var array */
	private $_where = [];

	/** @var bool */
	private $_whereSet = false;

	/** @var array */
	private $_links = [];

	/** @var string */
	private $_customOrder;

	/** @var array */
	private $_validators = [];

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Get / set parent table alias.
	 *
	 * When working with a self referencing table (i.e. a column in the table contains
	 * a primary key value from its own table) it can be useful to set an alias on the
	 * parent table's name, allowing a self referencing Join. For example:
	 *   ```
	 *   SELECT p2.publisher
	 *   FROM   publisher as p2
	 *   JOIN   publisher on (publisher.idPublisher = p2.idPublisher)
	 *   ```
	 * Where, in this case, `publisher` is the table that is used by the Editor instance,
	 * and you want to use `Join` to link back to the table (resolving a name for example).
	 * This method allows that alias to be set. Fields can then use standard SQL notation
	 * to select a field, for example `p2.publisher` or `publisher.publisher`.
	 *
	 * @param string $_ Table alias to use
	 *
	 * @return ($_ is null ? string : $this) Table alias set (which is null by default).
	 */
	public function aliasParentTable($_ = null)
	{
		return $this->_getSet($this->_aliasParentTable, $_);
	}

	/**
	 * Get / set field instances.
	 *
	 * The list of fields designates which columns in the table that will be read
	 * from the joined table.
	 *
	 * @param Field ...$_ Instances of the {@see Field} class, given as a single
	 *                    instance of {@see Field}, an array of {@see Field} instances, or multiple
	 *                    {@see Field} instance parameters for the function.
	 *
	 * @return ($_ is null ? Field[] : $this) Array of fields.
	 *
	 * @see {@see Field} for field documentation.
	 */
	public function field($_ = null)
	{
		$args = func_get_args();

		if ($_ !== null && !is_array($_)) {
			$_ = $args;
		}

		return $this->_getSet($this->_fields, $_, true);
	}

	/**
	 * Get / set field instances.
	 *
	 * An alias of {@see field}, for convenience.
	 *
	 * @param Field ...$_ Instances of the {@see Field} class, given as a single
	 *                    instance of {@see Field}, an array of {@see Field} instances, or multiple
	 *                    {@see Field} instance parameters for the function.
	 *
	 * @return ($_ is null ? Field[] : $this) Array of fields.
	 *
	 * @see {@see Field} for field documentation.
	 */
	public function fields($_ = null)
	{
		$args = func_get_args();

		if ($_ !== null && !is_array($_)) {
			$_ = $args;
		}

		return $this->_getSet($this->_fields, $_, true);
	}

	/**
	 * Get / set get attribute.
	 *
	 * When set to false no read operations will occur on the join tables.
	 *
	 * @param bool $_ Value
	 *
	 * @return ($_ is null ? bool : $this) Name
	 */
	public function get($_ = null)
	{
		return $this->_getSet($this->_get, $_);
	}

	/**
	 * Get / set join properties.
	 *
	 * Define how the SQL will be performed, on what columns. There are
	 * basically two types of join that are supported by Editor here, a direct
	 * foreign key reference in the join table to the parent table's primary
	 * key, or a link table that contains just primary keys for the parent and
	 * child tables (this approach is usually used with a {@see Join->type()} of
	 * 'array' since you can often have multiple links between the two tables,
	 * while a direct foreign key reference will typically use a type of
	 * 'object' (i.e. a single entry).
	 *
	 * @param string|string[] $parent Parent table's primary key names. If used
	 *                                with a link table (i.e. third parameter to this method is given, then
	 *                                an array should be used, with the first element being the pkey's name
	 *                                in the parent table, and the second element being the key's name in
	 *                                the link table.
	 * @param string|string[] $child  Child table's primary key names. If used
	 *                                with a link table (i.e. third parameter to this method is given, then
	 *                                an array should be used, with the first element being the pkey's name
	 *                                in the child table, and the second element being the key's name in the
	 *                                link table.
	 * @param string          $table  Join table name, if using a link table
	 *
	 * @return ($parent is null ? array : $this)
	 *
	 * @deprecated 1.5 Please use the {@see Join->link()} method rather than this
	 *                                method now.
	 */
	public function join($parent = null, $child = null, $table = null)
	{
		if ($parent === null && $child === null) {
			return $this->_join;
		}

		$this->_join['parent'] = $parent;
		$this->_join['child'] = $child;
		$this->_join['table'] = $table;

		return $this;
	}

	/**
	 * Set up a left join operation for the Mjoined data.
	 *
	 * @param string $table    to get the information from
	 * @param string $field1   the first field to get the information from
	 * @param string $operator the operation to perform on the two fields
	 * @param string $field2   the second field to get the information from
	 *
	 * @return $this
	 */
	public function leftJoin($table, $field1, $operator, $field2)
	{
		$this->_leftJoin[] = [
			'table' => $table,
			'field1' => $field1,
			'field2' => $field2,
			'operator' => $operator,
		];

		return $this;
	}

	/**
	 * Create a join link between two tables. The order of the fields does not
	 * matter, but each field must contain the table name as well as the field
	 * name.
	 *
	 * This method can be called a maximum of two times for an Mjoin instance:
	 *
	 * * First time, creates a link between the Editor host table and a join
	 *   table
	 * * Second time creates the links required for a link table.
	 *
	 * Please refer to the Editor Mjoin documentation for further details:
	 * https://editor.datatables.net/manual/php
	 *
	 * @param string $field1 Table and field name
	 * @param string $field2 Table and field name
	 *
	 * @return $this
	 */
	public function link($field1, $field2)
	{
		if (strpos($field1, '.') === false || strpos($field2, '.') === false) {
			throw new \Exception('Link fields must contain both the table name and the column name');
		}

		if (count($this->_links) >= 4) {
			throw new \Exception('Link method cannot be called more than twice for a single instance');
		}

		$this->_links[] = $field1;
		$this->_links[] = $field2;

		return $this;
	}

	/**
	 * Specify the property that the data will be sorted by.
	 *
	 * @param string $_ SQL column name to order the data by
	 *
	 * @return ($_ is null ? Join : $this)
	 */
	public function order($_ = null)
	{
		// How this works is by setting the SQL order by clause, and since the
		// join that is done in PHP is always done sequentially, the order is
		// retained.
		return $this->_getSet($this->_customOrder, $_);
	}

	/**
	 * Get / set name.
	 *
	 * The `name` of the Join is the JSON property key that is used when
	 * 'getting' the data, and the HTTP property key (in a JSON style) when
	 * 'setting' data. Typically the name of the db table will be used here,
	 * but this method allows that to be overridden.
	 *
	 * @param string $_ Field name
	 *
	 * @return ($_ is null ? string : $this) Name
	 */
	public function name($_ = null)
	{
		return $this->_getSet($this->_name, $_);
	}

	/**
	 * Get / set set attribute.
	 *
	 * When set to false no write operations will occur on the join tables.
	 * This can be useful when you want to display information which is joined,
	 * but want to only perform write operations on the parent table.
	 *
	 * @param bool $_ Value
	 *
	 * @return ($_ is null ? bool : $this) Name
	 */
	public function set($_ = null)
	{
		return $this->_getSet($this->_set, $_);
	}

	/**
	 * Get / set join table name.
	 *
	 * Please note that this will also set the {@see Join->name()} used by the Join
	 * as well. This is for convenience as the JSON output / HTTP input will
	 * typically use the same name as the database name. If you want to set a
	 * custom name, the {@see Join->name()} method must be called ***after*** this one.
	 *
	 * @param string $_ Name of the table to read the join data from
	 *
	 * @return ($_ is null ? string : $this) Name of the join table.
	 */
	public function table($_ = null)
	{
		if ($_ !== null) {
			$this->_name = $_;
		}

		return $this->_getSet($this->_table, $_);
	}

	/**
	 * Get / set the join type.
	 *
	 * The join type allows the data that is returned from the join to be given
	 * as an array (i.e. working with multiple possibly results for each record from
	 * the parent table), or as an object (i.e. working which one and only one result
	 * for each record form the parent table).
	 *
	 * @param string $_ Join type ('object') or an array of
	 *                  results ('array') for the join.
	 *
	 * @return ($_ is null ? string : $this) Join type.
	 */
	public function type($_ = null)
	{
		return $this->_getSet($this->_type, $_);
	}

	/**
	 * Set a validator for the array of data (not on a field basis).
	 *
	 * @param string                                    $fieldName Name of the field that any error should be shown
	 *                                                             against on the client-side
	 * @param callable(Editor, string, string): ?string $fn        Callback function for validation
	 *
	 * @return $this
	 */
	public function validator($fieldName, $fn)
	{
		$this->_validators[] = [
			'fieldName' => $fieldName,
			'fn' => $fn,
		];

		return $this;
	}

	/**
	 * Where condition to add to the query used to get data from the database.
	 * Note that this is applied to the child table.
	 *
	 * Can be used in two different ways:
	 *
	 * * Simple case: `where( field, value, operator )`
	 * * Complex: `where( fn )`
	 *
	 * @param string|\Closure(Query): void $key   Single field name or a closure function
	 * @param string|string[]              $value Single field value, or an array of values.
	 * @param string                       $op    Condition operator: <, >, = etc
	 *
	 * @return ($key is null ? string[] : $this) Where condition array.
	 */
	public function where($key = null, $value = null, $op = '=')
	{
		if ($key === null) {
			return $this->_where;
		}

		if ($key instanceof \Closure) {
			$this->_where[] = $key;
		} else {
			$this->_where[] = [
				'key' => $key,
				'value' => $value,
				'op' => $op,
			];
		}

		return $this;
	}

	/**
	 * Get / set if the WHERE conditions should be included in the create and
	 * edit actions.
	 *
	 * This means that the fields which have been used as part of the 'get'
	 * WHERE condition (using the `where()` method) will be set as the values
	 * given.
	 *
	 * This is default false (i.e. they are not included).
	 *
	 * @param bool $_ Include (`true`), or not (`false`)
	 *
	 * @return ($_ is null ? bool : $this) Current value
	 */
	public function whereSet($_ = null)
	{
		return $this->_getSet($this->_whereSet, $_);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 */

	/**
	 * Get data.
	 *
	 * @param Editor  $editor Host Editor instance
	 * @param mixed[] $data   Data from the parent table's get and were we need
	 *                        to add out output.
	 *
	 * @internal
	 */
	public function data($editor, &$data)
	{
		if (!$this->_get) {
			return;
		}

		$this->_prep($editor);
		$db = $editor->db();
		$dteTable = $editor->table();
		$pkey = $editor->pkey();
		$idPrefix = $editor->idPrefix();

		$dteTable = $dteTable[0];
		if (strpos($dteTable, ' as ') !== false) {
			$arr = explode(' as ', $dteTable);
			$dteTable = $arr[0];
			$this->_aliasParentTable = $arr[1];
		}

		$dteTableLocal = $this->_aliasParentTable ?: $dteTable;  // Can be aliased to allow a self join

		$joinField = isset($this->_join['table']) ?
			$this->_join['parent'][0] :
			$this->_join['parent'];

		// This is something that will likely come in a future version, but it
		// is a relatively low use feature. Please get in touch if this is
		// something you require.
		if (count($pkey) > 1) {
			throw new \Exception('MJoin is not currently supported with a compound primary key for the main table', 1);
		}

		if (count($data) > 0) {
			$pkey = $pkey[0];
			$pkeyIsJoin = $pkey === $joinField || $pkey === $dteTableLocal . '.' . $joinField;

			// Set up the JOIN query
			$stmt = $db
				->query('select')
				->distinct(true)
				->get($dteTableLocal . '.' . $joinField . ' as dteditor_pkey')
				->get($this->_fields('get'))
				->table($dteTable . ' as ' . $dteTableLocal);

			if ($this->order()) {
				$stmt->order($this->order());
			}

			$stmt->left_join($this->_leftJoin);
			$this->_apply_where($stmt);

			if (isset($this->_join['table'])) {
				// Working with a link table
				$stmt
					->join(
						$this->_join['table'],
						$dteTableLocal . '.' . $this->_join['parent'][0] . ' = ' . $this->_join['table'] . '.' . $this->_join['parent'][1]
					)
					->join(
						$this->_table,
						$this->_table . '.' . $this->_join['child'][0] . ' = ' . $this->_join['table'] . '.' . $this->_join['child'][1]
					);
			} else {
				// No link table in the middle
				$stmt
					->join(
						$this->_table,
						$this->_table . '.' . $this->_join['child'] . ' = ' . $dteTableLocal . '.' . $this->_join['parent']
					);
			}

			// Check that the joining field is available.  The joining key can
			// come from the Editor instance's primary key, or any other field,
			// including a nested value (from a left join). If the instance's
			// pkey, then we've got that in the DT_RowId parameter, so we can
			// use that. Otherwise, the key must be in the field list.
			if ($this->_propExists($dteTable . '.' . $joinField, $data[0])) {
				$readField = $dteTable . '.' . $joinField;
			} elseif ($this->_propExists($joinField, $data[0])) {
				$readField = $joinField;
			} elseif (!$pkeyIsJoin) {
				echo json_encode([
					'sError' => "Join was performed on the field '{$joinField}' which was not "
						. 'included in the Editor field list. The join field must be included '
						. 'as a regular field in the Editor instance.',
				]);

				exit(1);
			}

			// Get list of pkey values and apply as a WHERE IN condition
			// This is primarily useful in server-side processing mode and when filtering
			// the table as it means only a sub-set will be selected
			// This is only applied for "sensible" data sets. It will just complicate
			// matters for really large data sets:
			// https://stackoverflow.com/questions/21178390/in-clause-limitation-in-sql-server
			if (count($data) < 1000) {
				$whereIn = [];

				for ($i = 0; $i < count($data); ++$i) {
					$whereIn[] = $pkeyIsJoin ?
						str_replace($idPrefix, '', $data[$i]['DT_RowId']) :
						$this->_readProp($readField, $data[$i]);
				}

				$stmt->where_in($dteTableLocal . '.' . $joinField, $whereIn);
			}

			// Go!
			$res = $stmt->exec();
			if (!$res) {
				return;
			}

			// Map to primary key for fast lookup
			$join = [];
			while ($row = $res->fetch()) {
				$inner = [];

				for ($j = 0; $j < count($this->_fields); ++$j) {
					$field = $this->_fields[$j];
					if ($field->apply('get')) {
						$field->write($inner, $row);
					}
				}

				if ($this->_type === 'object') {
					$join[$row['dteditor_pkey']] = $inner;
				} else {
					if (!isset($join[$row['dteditor_pkey']])) {
						$join[$row['dteditor_pkey']] = [];
					}
					$join[$row['dteditor_pkey']][] = $inner;
				}
			}

			// Loop over the data and do a join based on the data available
			for ($i = 0; $i < count($data); ++$i) {
				$rowPKey = $pkeyIsJoin ?
					str_replace($idPrefix, '', $data[$i]['DT_RowId']) :
					$this->_readProp($readField, $data[$i]);

				if (isset($join[$rowPKey])) {
					$data[$i][$this->_name] = $join[$rowPKey];
				} else {
					$data[$i][$this->_name] = ($this->_type === 'object') ?
						(object) [] : [];
				}
			}
		}
	}

	/**
	 * Create a row.
	 *
	 * @param Editor $editor   Host Editor instance
	 * @param int    $parentId Parent row's primary key value
	 * @param array  $data     Data to be set for the join
	 *
	 * @internal
	 */
	public function create($editor, $parentId, $data)
	{
		// If not settable, or the many count for the join was not submitted
		// there we do nothing
		if (
			!$this->_set
			|| !isset($data[$this->_name])
			|| !isset($data[$this->_name . '-many-count'])
		) {
			return;
		}

		$this->_prep($editor);
		$db = $editor->db();

		if ($this->_type === 'object') {
			$this->_insert($db, $parentId, $data[$this->_name]);
		} else {
			for ($i = 0; $i < count($data[$this->_name]); ++$i) {
				$this->_insert($db, $parentId, $data[$this->_name][$i]);
			}
		}
	}

	/**
	 * Get options for the fields in this join.
	 *
	 * @param array    $options Array to write the read options into
	 * @param Database $db      Database connection object
	 * @param bool     $refresh Refresh indication flag
	 *
	 * @internal
	 */
	public function options(&$options, $db, $refresh)
	{
		// Field options
		foreach ($this->_fields as $field) {
			$optsInst = $field->options();

			if ($optsInst) {
				$opts = $optsInst->exec($db, $refresh);

				if ($opts !== false) {
					if ($this->_type === 'object') {
						$name = $this->name() . '.' . $field->name();
					} else {
						$name = $this->name() . '[].' . $field->name();
					}

					$options[$name] = $opts;
				}
			}
		}
	}

	/**
	 * Update a row.
	 *
	 * @param Editor   $editor   Host Editor instance
	 * @param int      $parentId Parent row's primary key value
	 * @param string[] $data     Data to be set for the join
	 *
	 * @internal
	 */
	public function update($editor, $parentId, $data)
	{
		// If not settable, or the many count for the join was not submitted
		// there we do nothing
		if (!$this->_set || !isset($data[$this->_name . '-many-count'])) {
			return;
		}

		$this->_prep($editor);
		$db = $editor->db();

		if ($this->_type === 'object') {
			// update or insert
			$this->_update_row($db, $parentId, $data[$this->_name]);
		} else {
			// WARNING - this will remove rows and then readd them. Any
			// data not in the field list WILL BE LOST
			// todo - is there a better way of doing this?
			$this->remove($editor, [$parentId]);
			$this->create($editor, $parentId, $data);
		}
	}

	/**
	 * Delete rows.
	 *
	 * @param Editor $editor Host Editor instance
	 * @param int[]  $ids    Parent row IDs to delete
	 *
	 * @internal
	 */
	public function remove($editor, $ids)
	{
		if (!$this->_set) {
			return;
		}

		$this->_prep($editor);
		$db = $editor->db();

		if (isset($this->_join['table'])) {
			$stmt = $db
				->query('delete')
				->table($this->_join['table'])
				->or_where($this->_join['parent'][1], $ids)
				->exec();
		} else {
			$stmt = $db
				->query('delete')
				->table($this->_table)
				->where_group(function ($q) use ($ids) {
					$q->or_where($this->_join['child'], $ids);
				});

			$this->_apply_where($stmt);

			$stmt->exec();
		}
	}

	/**
	 * Validate input data.
	 *
	 * @param array    $errors Errors array
	 * @param Editor   $editor Editor instance
	 * @param string[] $data   Data to validate
	 * @param string   $action `create` or `edit`
	 *
	 * @internal
	 */
	public function validate(&$errors, $editor, $data, $action)
	{
		if (!$this->_set && !isset($data[$this->_name . '-many-count'])) {
			return;
		}

		$this->_prep($editor);

		$joinData = isset($data[$this->_name]) ?
			$data[$this->_name] :
			[];

		for ($i = 0; $i < count($this->_validators); ++$i) {
			$validator = $this->_validators[$i];
			$fn = $validator['fn'];
			$res = $fn($editor, $action, $joinData);

			if (is_string($res)) {
				$errors[] = [
					'name' => $validator['fieldName'],
					'status' => $res,
				];
			}
		}

		if ($this->_type === 'object') {
			$this->_validateFields($errors, $editor, $joinData, $this->_name . '.');
		} else {
			for ($i = 0; $i < count($joinData); ++$i) {
				$this->_validateFields($errors, $editor, $joinData[$i], $this->_name . '[].');
			}
		}
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private methods
	 */

	/**
	 * Add local WHERE condition to query.
	 *
	 * @param Query $query Query instance to apply the WHERE conditions to
	 */
	private function _apply_where($query)
	{
		for ($i = 0; $i < count($this->_where); ++$i) {
			if ($this->_where[$i] instanceof \Closure) {
				$this->_where[$i]($query);
			} else {
				$query->where(
					$this->_where[$i]['key'],
					$this->_where[$i]['value'],
					$this->_where[$i]['op']
				);
			}
		}
	}

	/**
	 * Create a row.
	 *
	 * @param Database $db       Database reference to use
	 * @param int      $parentId Parent row's primary key value
	 * @param string[] $data     Data to be set for the join
	 */
	private function _insert($db, $parentId, $data)
	{
		if (isset($this->_join['table'])) {
			// Insert keys into the join table
			$stmt = $db
				->query('insert')
				->table($this->_join['table'])
				->set($this->_join['parent'][1], $parentId)
				->set($this->_join['child'][1], $data[$this->_join['child'][0]])
				->exec();
		} else {
			// Insert values into the target table
			$stmt = $db
				->query('insert')
				->table($this->_table)
				->set($this->_join['child'], $parentId);

			for ($i = 0; $i < count($this->_fields); ++$i) {
				$field = $this->_fields[$i];

				if ($field->apply('set', $data)) { // TODO should be create or edit
					$stmt->set($field->dbField(), $field->val('set', $data));
				}
			}

			// If the where condition variables should also be added to the database
			// Note that `whereSet` is now deprecated
			if ($this->_whereSet) {
				for ($i = 0, $ien = count($this->_where); $i < $ien; ++$i) {
					if (!$this->_where[$i] instanceof \Closure) {
						$stmt->set($this->_where[$i]['key'], $this->_where[$i]['value']);
					}
				}
			}

			$stmt->exec();
		}
	}

	/**
	 * Prepare the instance to be run.
	 *
	 * @param Editor $editor Editor instance
	 */
	private function _prep($editor)
	{
		$links = $this->_links;

		// Were links used to configure this instance - if so, we need to
		// back them onto the join array
		if ($this->_join['parent'] === null && count($links)) {
			$editorTable = $editor->table();
			$editorTable = $editorTable[0];
			$joinTable = $this->table();
			if (strpos($editorTable, ' as ') !== false) {
				$arr = explode(' as ', $editorTable);
				$editorTable = $arr[0];
				$this->_aliasParentTable = $arr[1];
			}

			if ($this->_aliasParentTable) {
				$editorTable = $this->_aliasParentTable;
			}

			if (count($links) === 2) {
				// No link table
				$f1 = explode('.', $links[0]);
				$f2 = explode('.', $links[1]);

				if ($f1[0] === $editorTable) {
					$this->_join['parent'] = $f1[1];
					$this->_join['child'] = $f2[1];
				} else {
					$this->_join['parent'] = $f2[1];
					$this->_join['child'] = $f1[1];
				}
			} else {
				// Link table
				$f1 = explode('.', $links[0]);
				$f2 = explode('.', $links[1]);
				$f3 = explode('.', $links[2]);
				$f4 = explode('.', $links[3]);

				// Discover the name of the link table
				if ($f1[0] !== $editorTable && $f1[0] !== $joinTable) {
					$this->_join['table'] = $f1[0];
				} elseif ($f2[0] !== $editorTable && $f2[0] !== $joinTable) {
					$this->_join['table'] = $f2[0];
				} elseif ($f3[0] !== $editorTable && $f3[0] !== $joinTable) {
					$this->_join['table'] = $f3[0];
				} else {
					$this->_join['table'] = $f4[0];
				}

				$this->_join['parent'] = [$f1[1], $f2[1]];
				$this->_join['child'] = [$f3[1], $f4[1]];
			}
		}
	}

	/**
	 * Update a row.
	 *
	 * @param Database $db       Database reference to use
	 * @param int      $parentId Parent row's primary key value
	 * @param string[] $data     Data to be set for the join
	 */
	private function _update_row($db, $parentId, $data)
	{
		if (isset($this->_join['table'])) {
			// Got a link table, just insert the pkey references
			$db->push(
				$this->_join['table'],
				[
					$this->_join['parent'][1] => $parentId,
					$this->_join['child'][1] => $data[$this->_join['child'][0]],
				],
				[
					$this->_join['parent'][1] => $parentId,
				]
			);
		} else {
			// No link table, just a direct reference
			$set = [
				$this->_join['child'] => $parentId,
			];

			for ($i = 0; $i < count($this->_fields); ++$i) {
				$field = $this->_fields[$i];

				if ($field->apply('set', $data)) {
					$set[$field->dbField()] = $field->val('set', $data);
				}
			}

			// Add WHERE conditions
			$where = [$this->_join['child'] => $parentId];
			for ($i = 0, $ien = count($this->_where); $i < $ien; ++$i) {
				$where[$this->_where[$i]['key']] = $this->_where[$i]['value'];

				// Is there any point in this? Is there any harm?
				// Note that `whereSet` is now deprecated
				if ($this->_whereSet) {
					if (!$this->_where[$i] instanceof \Closure) {
						$set[$this->_where[$i]['key']] = $this->_where[$i]['value'];
					}
				}
			}

			$db->push($this->_table, $set, $where);
		}
	}

	/**
	 * Create an SQL string from the fields that this instance knows about for
	 * using in queries.
	 *
	 * @param string $direction Direction: 'get' or 'set'.
	 *
	 * @return array Fields to include
	 */
	private function _fields($direction)
	{
		$fields = [];

		for ($i = 0; $i < count($this->_fields); ++$i) {
			$field = $this->_fields[$i];

			if ($field->apply($direction, null)) {
				if (strpos($field->dbField(), '.') === false) {
					$fields[] = $this->_table . '.' . $field->dbField() . ' as ' . $field->dbField();
				} else {
					$fields[] = $field->dbField(); // ." as ".$field->dbField();
				}
			}
		}

		return $fields;
	}

	/**
	 * Validate input data.
	 *
	 * @param array    $errors Errors array
	 * @param Editor   $editor Editor instance
	 * @param string[] $data   Data to validate
	 * @param string   $prefix Field error prefix for client-side to show the
	 *                         error message on the appropriate field
	 *
	 * @internal
	 */
	private function _validateFields(&$errors, $editor, $data, $prefix)
	{
		for ($i = 0; $i < count($this->_fields); ++$i) {
			$field = $this->_fields[$i];
			$validation = $field->validate($data, $editor);

			if ($validation !== true) {
				$errors[] = [
					'name' => $prefix . $field->name(),
					'status' => $validation,
				];
			}
		}
	}
}
