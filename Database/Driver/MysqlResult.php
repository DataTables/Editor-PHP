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

use DataTables\Database\Result;

/**
 * MySQL driver for DataTables Database Result class.
 *
 * @internal
 */
class MysqlResult extends Result
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Constructor
	 */

	public function __construct($dbh, $stmt)
	{
		$this->_dbh = $dbh;
		$this->_stmt = $stmt;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private properties
	 */

	private $_stmt;
	private $_dbh;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	public function count()
	{
		return count($this->fetchAll());
	}

	public function fetch($fetchType = \PDO::FETCH_ASSOC)
	{
		return $this->_stmt->fetch($fetchType);
	}

	public function fetchAll($fetchType = \PDO::FETCH_ASSOC)
	{
		return $this->_stmt->fetchAll($fetchType);
	}

	public function insertId()
	{
		return $this->_dbh->lastInsertId();
	}
}
