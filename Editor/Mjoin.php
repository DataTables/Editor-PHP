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

namespace DataTables\Editor;

/**
 * The `Mjoin` class extends the `Join` class with the join data type set to
 * 'array', whereas the `Join` default is `object` which has been rendered
 * obsolete by the `Editor->leftJoin()` method. The API API is otherwise
 * identical.
 *
 * This class is recommended over the `Join` class.
 */
class Mjoin extends Join
{
	public function __construct($table = null)
	{
		parent::__construct($table, 'array');
	}
}
