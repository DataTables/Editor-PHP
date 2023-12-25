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

namespace DataTables\Database\Driver;

use DataTables\Database\Query;

/**
 * Postgres driver for DataTables Database Query class.
 *
 * @internal
 */
class PostgresQuery extends Query
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */
	private $_stmt;

	protected $_identifier_limiter = ['"', '"'];

	protected $_field_quote = '"';

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	public static function connect($user, $pass = '', $host = '', $port = '', $db = '', $dsn = '')
	{
		if (is_array($user)) {
			$opts = $user;
			$user = $opts['user'];
			$pass = $opts['pass'];
			$port = $opts['port'];
			$host = $opts['host'];
			$db = $opts['db'];
			$dsn = isset($opts['dsn']) ? $opts['dsn'] : '';
			$pdoAttr = isset($opts['pdoAttr']) ? $opts['pdoAttr'] : [];
		}

		if ($port !== '') {
			$port = "port={$port};";
		}

		try {
			$pdoAttr[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

			$pdo = @new \PDO(
				"pgsql:host={$host};{$port}dbname={$db}" . self::dsnPostfix($dsn),
				$user,
				$pass,
				$pdoAttr
			);
		} catch (\PDOException $e) {
			// If we can't establish a DB connection then we return a DataTables
			// error.
			echo json_encode([
				'error' => 'An error occurred while connecting to the database ' .
					"'{$db}'. The error reported by the server was: " . $e->getMessage(),
			]);

			exit(1);
		}

		return $pdo;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Protected methods
	 */

	protected function _prepare($sql)
	{
		$this->database()->debugInfo($sql, $this->_bindings);

		$resource = $this->database()->resource();
		$pkey = $this->pkey();

		// Add a RETURNING command to postgres insert queries so we can get the
		// pkey value from the query reliably
		if ($this->_type === 'insert') {
			$table = explode(' as ', $this->_table[0]);

			// Get the pkey field name
			$pkRes = $resource->prepare(
				'SELECT a.attname
				FROM   pg_index i
				JOIN   pg_attribute a ON a.attrelid = i.indrelid
									 AND a.attnum = ANY(i.indkey)
				WHERE  i.indrelid = (:tableName)::regclass
				AND    i.indisprimary'
			);
			$pkRes->bindValue('tableName', $table[0]);
			$pkRes->execute();
			$row = $pkRes->fetch();

			if ($row && isset($row['attname'])) {
				$sql .= ' RETURNING ' . $row['attname'] . ' as dt_pkey';
			}
		}

		$this->_stmt = $resource->prepare($sql);

		// bind values
		for ($i = 0; $i < count($this->_bindings); ++$i) {
			$binding = $this->_bindings[$i];

			$this->_stmt->bindValue(
				$binding['name'],
				$binding['value'],
				$binding['type'] ?: \PDO::PARAM_STR
			);
		}
	}

	protected function _exec()
	{
		try {
			$this->_stmt->execute();
		} catch (\PDOException $e) {
			throw new \Exception('An SQL error occurred: ' . $e->getMessage(), 0, $e);
		}

		$resource = $this->database()->resource();

		return new PostgresResult($resource, $this->_stmt);
	}
}
