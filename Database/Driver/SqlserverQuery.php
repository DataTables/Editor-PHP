<?php

/**
 * SQL Server driver for DataTables PHP libraries.
 *
 * @author    SpryMedia
 * @copyright 2013 SpryMedia ( http://sprymedia.co.uk )
 * @license   http://editor.datatables.net/license DataTables Editor
 *
 * @see       http://editor.datatables.net
 */

namespace DataTables\Database\Driver;

use DataTables\Database\Query;

/**
 * SQL Server driver for DataTables Database Query class.
 *
 * @internal
 */
class SqlserverQuery extends Query
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */
	private $_stmt;

	protected $_identifier_limiter = ['[', ']'];

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

		try {
			$pdoAttr[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

			if (in_array('sqlsrv', \PDO::getAvailableDrivers())) {
				// Windows
				if ($port !== '') {
					$port = ",{$port}";
				}

				$pdo = new \PDO(
					"sqlsrv:Server={$host}{$port};Database={$db}" . self::dsnPostfix($dsn),
					$user,
					$pass,
					$pdoAttr
				);
			} else {
				// Linux
				if ($port !== '') {
					$port = ":{$port}";
				}

				$pdo = new \PDO(
					"dblib:host={$host}{$port};dbname={$db}" . self::dsnPostfix($dsn),
					$user,
					$pass,
					$pdoAttr
				);
			}
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

		return new SqlserverResult($resource, $this->_stmt);
	}

	// SQL Server 2012+ only
	protected function _build_limit()
	{
		$out = '';

		if ($this->_offset) {
			$out .= ' OFFSET ' . $this->_offset . ' ROWS';
		}

		if ($this->_limit) {
			if (!$this->_offset) {
				$out .= ' OFFSET 0 ROWS';
			}
			$out .= ' FETCH NEXT ' . $this->_limit . ' ROWS ONLY';
		}

		return $out;
	}
}
