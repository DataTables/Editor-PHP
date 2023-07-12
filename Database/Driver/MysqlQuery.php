<?php
/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor, utilising PHP 5.3+.
 *
 *  @author    SpryMedia
 *  @copyright 2012 SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *
 *  @see      http://editor.datatables.net
 */

namespace DataTables\Database\Driver;

use DataTables\Database\Query;

/**
 * MySQL driver for DataTables Database Query class.
 *
 *  @internal
 */
class MysqlQuery extends Query
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */
	private $_stmt;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	public static function connect($user, $pass = '', $host = '', $port = '', $db = '', $dsn = '')
	{
		if (is_array($user)) {
			$opts = $user;
			$user = $opts['user'];
			$pass = $opts['pass'];
			$host = $opts['host'];
			$db = $opts['db'];
			$port = isset($opts['port']) ? $opts['port'] : '';
			$dsn = isset($opts['dsn']) ? $opts['dsn'] : '';
			$pdoAttr = isset($opts['pdoAttr']) ? $opts['pdoAttr'] : array();
		}

		if ($port !== '') {
			$port = "port={$port};";
		}

		try {
			$pdoAttr[\PDO::ATTR_ERRMODE] = \PDO::ERRMODE_EXCEPTION;

			$pdo = @new \PDO(
				"mysql:host={$host};{$port}dbname={$db}" . self::dsnPostfix($dsn),
				$user,
				$pass,
				$pdoAttr
			);
		} catch (\PDOException $e) {
			// If we can't establish a DB connection then we return a DataTables
			// error.
			echo json_encode(array(
				'error' => 'An error occurred while connecting to the database ' .
					"'{$db}'. The error reported by the server was: " . $e->getMessage(),
			));

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
		// $start = hrtime(true);

		try {
			$this->_stmt->execute();
		} catch (\PDOException $e) {
			throw new \Exception('An SQL error occurred: ' . $e->getMessage(), 0, $e);
		}

		// $this->database()->debugInfo( 'Execution complete - duration: '. (hrtime(true) - $start) . '  Time: '. microtime(true), [] );

		$resource = $this->database()->resource();

		return new MysqlResult($resource, $this->_stmt);
	}
}
