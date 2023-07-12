<?php
/**
 * SQL Server driver for DataTables PHP libraries
 * BETA! Feedback welcome.
 *
 *  @author    SpryMedia
 *  @copyright 2013 SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *
 *  @see      http://editor.datatables.net
 */

namespace DataTables\Database\Driver;

use DataTables\Database\Result;

/**
 * SQL Server driver for DataTables Database Result class.
 *
 *  @internal
 */
class Db2Result extends Result
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
	private $_allRows;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	public function count()
	{
		$all = $this->_fetchAll();

		return count($all);
	}

	public function fetch($fetchType = \PDO::FETCH_ASSOC)
	{
		return db2_fetch_assoc($this->_stmt);
	}

	public function fetchAll($fetchType = \PDO::FETCH_ASSOC)
	{
		$all = $this->_fetchAll();

		return $all;
	}

	public function insertId()
	{
		return db2_last_insert_id($this->_dbh);
	}

	private function _fetchAll()
	{
		$a = array();
		while ($row = db2_fetch_assoc($this->_stmt)) {
			$a[] = $row;
		}

		return $a;
	}
}
