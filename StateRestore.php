<?php

namespace DataTables;

use DataTables\Database\Query;

class StateRestore extends Ext
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */

	/** @var string */
	private $_columnDefault = 'defaultState';

	/** @var string */
	private $_columnId = 'id';

	/** @var string */
	private $_columnName = 'name';

	/** @var string */
	private $_columnPath = 'path';

	/** @var string */
	private $_columnShared = 'shared';

	/** @var string */
	private $_columnState = 'state';

	/** @var string */
	private $_columnTable = 'table';

	/** @var string */
	private $_columnUser = 'user';

	/** @var Database */
	private $_db;

	/** @var array */
	private $_result = [];

	/** @var array */
	private $_set = [];

	/** @var string */
	private $_table = '';

	/** @var string */
	private $_userId = '';

	/** @var array */
	private $_where = [];

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Get / set the column name for the default state flag.
	 *
	 * @param string $_ Column name
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function columnDefault($_ = null)
	{
		return $this->_getSet($this->_columnDefault, $_);
	}

	/**
	 * Get / set the column name for the table's primary key.
	 *
	 * @param string $_ Primary key column name
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function columnId($_ = null)
	{
		return $this->_getSet($this->_columnId, $_);
	}

	/**
	 * Get / set the column name for the state name.
	 *
	 * @param string $_ Column name
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function columnName($_ = null)
	{
		return $this->_getSet($this->_columnName, $_);
	}

	/**
	 * Get / set the column name for the URL (path) of where the state applies.
	 *
	 * @param string $_ Column name
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function columnPath($_ = null)
	{
		return $this->_getSet($this->_columnPath, $_);
	}

	/**
	 * Get / set the column name for the shared flag.
	 *
	 * @param string $_ Column name
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function columnShared($_ = null)
	{
		return $this->_getSet($this->_columnShared, $_);
	}

	/**
	 * Get / set the column name for where the state itself is stored.
	 *
	 * @param string $_ Column name
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function columnState($_ = null)
	{
		return $this->_getSet($this->_columnState, $_);
	}

	/**
	 * Get / set the column name for where the name of the host DataTable is
	 * stored.
	 *
	 * @param string $_ Column name
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function columnTable($_ = null)
	{
		return $this->_getSet($this->_columnTable, $_);
	}

	/**
	 * Get / set the column name for where the user identifier is stored.
	 *
	 * @param string $_ Column name
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function columnUser($_ = null)
	{
		return $this->_getSet($this->_columnUser, $_);
	}

	/**
	 * Get the data constructed in this instance.
	 *
	 * This will get the PHP array of data that has been constructed for the
	 * command that has been processed by this instance. Therefore only useful after
	 * process has been called.
	 *
	 * @return array Processed data array.
	 */
	public function data()
	{
		return $this->_result;
	}

	/**
	 * Get / set the database connection instance.
	 *
	 * @param Database $_ Database instance
	 *
	 * @return ($_ is null ? Database : $this) Database instance.
	 */
	public function db($_ = null)
	{
		return $this->_getSet($this->_db, $_);
	}

	/**
	 * Get the JSON for the data constructed in this instance.
	 *
	 * Basically the same as the {@see StateRestore->data()} method, but in this
	 * case we echo, or return the JSON string of the data.
	 *
	 * @param bool $print   Echo the JSON string out (true, default) or return it
	 *                      (false).
	 * @param int  $options JSON encode option
	 *                      https://www.php.net/manual/en/json.constants.php
	 *
	 * @return ($print is false ? string : $this) JSON representation of the
	 *                                            processed data if false is given as the first parameter.
	 */
	public function json($print = true, $options = 0)
	{
		if ($print) {
			$json = json_encode($this->_result, $options);

			header('Content-Type: application/json; charset=utf-8');

			if ($json !== false) {
				echo $json;
			} else {
				echo json_encode([
					'error' => 'JSON encoding error: ' . json_last_error_msg(),
				]);
			}

			return $this;
		}

		return json_encode($this->_result);
	}

	/**
	 * Process a request from the StateRestore client-side to get / set data.
	 *
	 * @param array $data Typically $_POST or $_GET as required by what is sent
	 *                    by StateRestore
	 *
	 * @return $this
	 */
	public function process($data)
	{
		if (!isset($data['action'])) {
			$this->_result = ['error' => 'Unknown action'];
		} elseif ($data['action'] === 'state-read') {
			$this->_result = $this->_read($data);
		} elseif ($data['action'] === 'state-create') {
			$this->_result = $this->_create($data);
		} elseif ($data['action'] === 'state-edit') {
			$this->_result = $this->_edit($data);
		} elseif ($data['action'] === 'state-remove') {
			$this->_result = $this->_remove($data);
		}

		return $this;
	}

	/**
	 * Get / set extra column name / value properties to store in the db. These
	 * are _set only_ - they are not read back from the db when loading a state.
	 * They can however be used for auditing information or for extra query
	 * conditions.
	 *
	 * @param array $_ Column name / value pairs
	 *
	 * @return ($_ is null ? array : $this) Column values
	 */
	public function set($_ = null)
	{
		return $this->_getSet($this->_set, $_);
	}

	/**
	 * Get / set the table name that will be used for state storage in the
	 * database.
	 *
	 * @param string $_ Table name
	 *
	 * @return ($_ is null ? string : $this) Table name.
	 */
	public function table($_ = null)
	{
		return $this->_getSet($this->_table, $_);
	}

	/**
	 * Get / set the value for the current user identifier. This is usually a
	 * user id, but it could be any other unique identifier.
	 *
	 * @param string $_ User id
	 *
	 * @return ($_ is null ? string : $this) Column name.
	 */
	public function user($_ = null)
	{
		return $this->_getSet($this->_userId, $_);
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
	 * closure function that has access to the database Query that Editor is
	 * using, so you can use the `where()`, `or_where()`, `and_where()` and
	 * `where_group()` methods as you require.
	 *
	 * Please be very careful when using this method! If an edit made by a user
	 * using Editor removes the row from the where condition, the result is
	 * undefined (since Editor expects the row to still be available, but the
	 * condition removes it from the result set).
	 *
	 * @param string|\Closure(Query): void $key   Single field name or a closure function
	 * @param string                       $value Single field value.
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

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	/**
	 * Constructor.
	 *
	 * @param Database $db    An instance of the DataTables Database class that we
	 *                        can use for the DB connection. Can be given here or with the 'db'
	 *                        method.
	 * @param string   $table The table name in the database to read and write
	 *                        information from and to. Can be given here or with the 'table' method.
	 * @param string   $pkey  Primary key column name in the table given in the
	 *                        $table parameter. Can be given here or with the 'pkey' method.
	 */
	public function __construct($db = null, $table = null, $pkey = null)
	{
		// Set constructor parameters using the API - note that the get/set will
		// ignore null values if they are used (i.e. not passed in)
		$this->db($db);
		$this->table($table);
		$this->columnId($pkey);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private methods
	 */

	/**
	 * Check a property is available in the data object.
	 *
	 * @param mixed $data Data object to check
	 * @param mixed $prop Property name
	 *
	 * @return bool true if valid, false otherwise.
	 */
	private function _assert($data, $prop)
	{
		return isset($data[$prop]);
	}

	/**
	 * Validate submitted state data.
	 *
	 * @param mixed $data Data to validate
	 *
	 * @return bool|array{error: string} `true` if valid, or an
	 */
	private function _assertStateData($data)
	{
		if (!$this->_assert($data, 'isDefault')) {
			return ['error' => 'Incomplete data - no default flag'];
		}

		if (!$this->_assert($data, 'isSharedOut')) {
			return ['error' => 'Incomplete data - no share flag'];
		}

		if (!$this->_assert($data, 'name') || !$data['name']) {
			return ['error' => 'Incomplete data - no name'];
		}

		if (!$this->_assert($data, 'state') || !json_validate($data['state'])) {
			return ['error' => 'Incomplete data - no valid state'];
		}

		$validate = $this->_assertStateHost($data);

		if ($validate !== true) {
			return $validate;
		}

		return true;
	}

	/**
	 * Check the parameters that are submitted for host table information.
	 *
	 * @param mixed $data Data to validate
	 *
	 * @return bool|array{error: string} `true` if valid, or an
	 */
	private function _assertStateHost($data)
	{
		if (!$this->_assert($data, 'path') || !$data['path']) {
			return ['error' => 'Incomplete data - no path'];
		}

		if (!$this->_assert($data, 'table') || !$data['table']) {
			return ['error' => 'Incomplete data - table'];
		}

		return true;
	}

	/**
	 * Add a new state to the database.
	 *
	 * @param mixed $data State information
	 *
	 * @return array
	 */
	private function _create($data)
	{
		$validate = $this->_assertStateData($data);

		if ($validate !== true) {
			return $validate;
		}

		$set = [];
		$set[$this->_columnDefault] = $this->_valueBoolean($data['isDefault']);
		$set[$this->_columnName] = $data['name'];
		$set[$this->_columnPath] = $data['path'];
		$set[$this->_columnShared] = $this->_valueBoolean($data['isSharedOut']);
		$set[$this->_columnState] = $data['state'];
		$set[$this->_columnTable] = $data['table'];

		if ($this->_userId) {
			$set[$this->_columnUser] = $this->_userId;
		}

		// Dev defined values (server-side)
		foreach ($this->_set as $key => $value) {
			$set[$key] = $value;
		}

		if ($set[$this->_columnDefault]) {
			$this->_removeDefault($data);
		}

		$res = $this->db()->insert($this->_table, $set, $this->_columnId);
		$id = $res->insertId();

		return $this->_read($data, $id);
	}

	/**
	 * Add a new state to the database.
	 *
	 * @param mixed $data State information
	 *
	 * @return array
	 */
	private function _edit($data)
	{
		$validate = $this->_assertStateData($data);

		if ($validate !== true) {
			return $validate;
		}

		if (!isset($data['id'])) {
			return [
				'error' => 'Incomplete data - no id',
			];
		}

		// Values to set
		$set = [];
		$set[$this->_columnDefault] = $this->_valueBoolean($data['isDefault']);
		$set[$this->_columnName] = $data['name'];
		$set[$this->_columnShared] = $this->_valueBoolean($data['isSharedOut']);
		$set[$this->_columnState] = $data['state'];

		// Dev defined values (server-side)
		foreach ($this->_set as $key => $value) {
			$set[$key] = $value;
		}

		// Conditions
		$where = [];
		$where[$this->_columnId] = $data['id'];
		$where[$this->_columnTable] = $data['table'];
		$where[$this->_columnPath] = $data['path'];

		if ($this->_userId) {
			$where[$this->_columnUser] = $this->_userId;
		}

		if ($set[$this->_columnDefault]) {
			$this->_removeDefault($data);
		}

		$this->db()->update($this->_table, $set, $where);

		return $this->_read($data, $data['id']);
	}

	/**
	 * Read the states from the db.
	 *
	 * @param array  $data Submitted data
	 * @param string $id   Limit the read to a specific ID
	 *
	 * @return array
	 */
	private function _read($data, $id = null)
	{
		$q = $this->db()
			->query('select')
			->table($this->_table)
			->get($this->_columnId . ' as id');

		if ($this->_columnDefault) {
			$q->get($this->_columnDefault . ' as isDefault');
		}

		if ($this->_columnName) {
			$q->get($this->_columnName . ' as name');
		}

		if ($this->_columnShared) {
			$q->get($this->_columnShared . ' as isSharedOut');
		}

		if ($this->_columnState) {
			$q->get($this->_columnState . ' as state');
		}

		if ($this->_columnUser) {
			$q->get($this->_columnUser . ' as user');
		}

		// Must have the table and path, otherwise all states would be returned!
		if (!isset($data['table']) || !isset($data['path'])) {
			return [
				'error' => 'Source table and path not set',
			];
		}

		// Conditions
		$q->where($this->_columnTable, $data['table']);
		$q->where($this->_columnPath, $data['path']);

		if ($id) {
			$q->where($this->_columnId, $id);
		}

		// The user id is optional, but there can't be any separation of user
		// states without it!
		if ($this->_userId) {
			$q->where(function ($r) {
				$r->where($this->_columnUser, $this->_userId);
				$r->or_where($this->_columnShared, 1);
			});
		}

		// Dev set conditions
		for ($i = 0; $i < count($this->_where); ++$i) {
			if ($this->_where[$i] instanceof \Closure) {
				$this->_where[$i]($q);
			} else {
				$q->where(
					$this->_where[$i]['key'],
					$this->_where[$i]['value'],
					$this->_where[$i]['op']
				);
			}
		}

		$res = $q->exec();
		$out = [];

		// Map to the JSON structure that StateRestore expects
		while ($row = $res->fetch()) {
			$out[] = [
				'id' => $row['id'],
				'isDefault' => $row['isDefault'],
				'isSharedIn' => $this->_userId && $row['user'] != $this->_userId ? true : false,
				'isSharedOut' => $row['isSharedOut'],
				'isStatic' => false,
				'name' => $row['name'],
				'state' => $row['state'],
			];
		}

		return [
			'data' => $out,
		];
	}

	/**
	 * Delete states.
	 *
	 * @param mixed $data
	 *
	 * @return array
	 */
	private function _remove($data)
	{
		$validate = $this->_assertStateHost($data);

		if ($validate !== true) {
			return $validate;
		}

		if (!isset($data['ids']) || !is_array($data['ids'])) {
			return [
				'error' => 'Invalid submitted data',
			];
		}

		$q = $this->_db
			->query('delete')
			->table($this->_table);

		$q->where($this->_columnTable, $data['table']);
		$q->where($this->_columnPath, $data['path']);

		if ($this->_userId) {
			$q->where($this->_columnUser, $this->_userId);
		}

		$q->where_in($this->_columnId, $data['ids']);
		$q->exec();

		return [
			'data' => [],
		];
	}

	/**
	 * If there is an existing default, remove it. The client-side will do this
	 * as well, so we don't need to worry about there being two default states
	 * shown, despite only returning a single record.
	 *
	 * @param mixed $data Submitted data
	 *
	 * @return array
	 */
	private function _removeDefault($data)
	{
		$validate = $this->_assertStateHost($data);

		if ($validate !== true) {
			return $validate;
		}

		// Values to set
		$set = [];
		$set[$this->_columnDefault] = 0;

		// Dev defined values (server-side)
		foreach ($this->_set as $key => $value) {
			$set[$key] = $value;
		}

		// Conditions
		$where = [];
		$where[$this->_columnDefault] = 1;
		$where[$this->_columnTable] = $data['table'];
		$where[$this->_columnPath] = $data['path'];

		if ($this->_userId) {
			$where[$this->_columnUser] = $this->_userId;
		}

		$this->db()->update($this->_table, $set, $where);

		return $this->_read($data, $data['id']);
	}

	/**
	 * Convert a boolean HTTP value to a PHP boolean.
	 *
	 * @param mixed $data
	 *
	 * @return int
	 */
	private function _valueBoolean($data)
	{
		if ($data === 'true' || $data === 't' || $data === '1' || $data === 1 || $data === true) {
			return 1;
		}

		return 0;
	}
}
