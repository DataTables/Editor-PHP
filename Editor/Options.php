<?php

/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor.
 *
 * @author    SpryMedia
 * @copyright 2016 SpryMedia ( http://sprymedia.co.uk )
 * @license   http://editor.datatables.net/license DataTables Editor
 *
 * @see       http://editor.datatables.net
 */

namespace DataTables\Editor;

use DataTables\Database;
use DataTables\Database\Query;
use DataTables\Ext;

/**
 * The Options class provides a convenient method of specifying where Editor
 * should get the list of options for a `select`, `radio` or `checkbox` field.
 * This is normally from a table that is _left joined_ to the main table being
 * edited, and a list of the values available from the joined table is shown to
 * the end user to let them select from.
 *
 * `Options` instances are used with the {@see Field->options()} method.
 *
 * @example
 *   Get a list of options from the `sites` table
 *    ```php
 *    (new Field( 'users.site' ))
 *        ->options( (new Options())
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( 'name' )
 *        )
 *    ```
 * @example
 *   Get a list of options with custom ordering
 *    ```php
 *    (new Field( 'users.site' ))
 *        ->options( (new Options())
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( 'name' )
 *            ->order( 'name DESC' )
 *        )
 *    ```
 * @example
 *   Get a list of options showing the id and name in the label
 *    ```php
 *    (new Field( 'users.site' ))
 *        ->options( (new Options())
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( [ 'name', 'id' ] )
 *            ->render( function ( $row ) {
 *              return $row['name'].' ('.$row['id'].')';
 *            } )
 *        )
 *    ```
 */
class Options extends Ext
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private parameters
	 */

	/** @var bool Indicate if options should always be refreshed */
	private $_alwaysRefresh = true;

	/** @var callable|null */
	private $_customFn;

	/** @var string[] List of columns to include in the output */
	private $_includes = [];

	/** @var string[] Column names for the label(s) */
	private $_label = [];

	/** Information for left join */
	private $_leftJoin = [];

	/** @var int|null Row limit */
	private $_limit;

	/** @var array List of label / values to use if not database retrieved */
	private $_manualAdd = [];

	/** @var string|bool ORDER BY clause */
	private $_order = true;

	/** @var callable Callback function to do rendering of labels */
	private $_renderer;

	/** @var bool Indicate if options should get got for create/edit or on search only */
	private $_searchOnly = false;

	/** @var string Table to get the information from */
	private $_table;

	/** @var callable Callback function to add where conditions */
	private $_where;

	/** @var string Column name containing the value */
	private $_value;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Add extra options to the list, in addition to any obtained from the database.
	 *
	 * @param string      $label The label to use for the option
	 * @param string|null $value Value for the option. If not given, the label will be used
	 *
	 * @return $this
	 */
	public function add($label, $value = null)
	{
		if ($value === null) {
			$value = $label;
		}

		$this->_manualAdd[] = [
			'label' => $label,
			'value' => $value,
		];

		return $this;
	}

	/**
	 * Get / set the flag to indicate if the options should always be refreshed
	 * (i.e. on get, create and edit) or only on the initial data load (false).
	 *
	 * @param bool|null $_ Flag to set the always refresh set to, or null to
	 *                     get the current state.
	 *
	 * @return ($_ is null ? boolean : $this)
	 */
	public function alwaysRefresh($_ = null)
	{
		return $this->_getSet($this->_alwaysRefresh, $_);
	}

	/**
	 * Custom function to get the options, rather than using the built in DB.
	 *
	 * @param callable|null $_ Function that will be run to get the list of
	 *                         options.
	 *
	 * @return ($_ is null ? callable : $this)
	 */
	public function fn($_ = null)
	{
		return $this->_getSet($this->_customFn, $_);
	}

	/**
	 * Column names from `value()` and `label()` that should be included in the output object for
	 * each option, in addition to the value and label.
	 *
	 * @param string|string[] $inc The list of columns to include in the output
	 *
	 * @return ($inc is null ? string[] : $this)
	 */
	public function include($inc = null)
	{
		if ($inc === null) {
			return $this->_includes;
		}

		if (is_array($inc)) {
			$this->_includes = array_merge($this->_includes, $inc);
		} else {
			$this->_includes[] = $inc;
		}

		return $this;
	}

	/**
	 * Get / set the column(s) to use as the label value of the options.
	 *
	 * @param string|string[]|null $_ null to get the current value, string or
	 *                                array to get.
	 *
	 * @return ($_ is null ? string[] : $this)
	 */
	public function label($_ = null)
	{
		if ($_ === null) {
			return $this;
		} elseif (is_string($_)) {
			$this->_label = [$_];
		} else {
			$this->_label = $_;
		}

		return $this;
	}

	/**
	 * Set up a left join operation for the options.
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
	 * Get / set the LIMIT clause to limit the number of records returned.
	 *
	 * @param number|null $_ Number of rows to limit the result to
	 *
	 * @return ($_ is null ? string[] : $this)
	 */
	public function limit($_ = null)
	{
		return $this->_getSet($this->_limit, $_);
	}

	/**
	 * Get / set the ORDER BY clause to use in the SQL. If this option is `true`
	 * (which it is by default) the ordering will be based on the rendered output,
	 * either numerically or alphabetically based on the data returned by the
	 * renderer. If `false` no ordering will be performed and whatever is returned
	 * from the database will be used.
	 *
	 * @param string|bool|null $_ String to set, null to get current value
	 *
	 * @return ($_ is null ? string : $this)
	 */
	public function order($_ = null)
	{
		return $this->_getSet($this->_order, $_);
	}

	/**
	 * Get / set the label renderer. The renderer can be used to combine
	 * multiple database columns into a single string that is shown as the label
	 * to the end user in the list of options.
	 *
	 * @param callable(array): string|null $_ Function to set, null to get current value
	 *
	 * @return ($_ is null ? callable : $this)
	 */
	public function render($_ = null)
	{
		return $this->_getSet($this->_renderer, $_);
	}

	/**
	 * Get / set the flag to indicate if the options should always be refreshed
	 * (i.e. on get, create and edit) or only on the initial data load (false).
	 *
	 * @param bool|null $_ Flag to set the always refresh set to, or null to
	 *                     get the current state.
	 *
	 * @return ($_ is null ? boolean : $this)
	 */
	public function searchOnly($_ = null)
	{
		return $this->_getSet($this->_searchOnly, $_);
	}

	/**
	 * Get / set the database table from which to gather the options for the
	 * list.
	 *
	 * @param string|null $_ String to set, null to get current value
	 *
	 * @return ($_ is null ? string : $this)
	 */
	public function table($_ = null)
	{
		return $this->_getSet($this->_table, $_);
	}

	/**
	 * Get / set the column name to use for the value in the options list. This
	 * would normally be the primary key for the table.
	 *
	 * @param string|null $_ String to set, null to get current value
	 *
	 * @return ($_ is null ? string : $this)
	 */
	public function value($_ = null)
	{
		return $this->_getSet($this->_value, $_);
	}

	/**
	 * Get / set the method to use for a WHERE condition if it is to be
	 * applied to the query to get the options.
	 *
	 * @param \Closure(Query): void|null $_ Function to set, null to get current value
	 *
	 * @return ($_ is null ? callable : $this)
	 */
	public function where($_ = null)
	{
		return $this->_getSet($this->_where, $_);
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 */

	/**
	 * Execute the options (i.e. get them).
	 *
	 * @param Database    $db      Database connection
	 * @param bool        $refresh Indicate if this is a refresh or a full load
	 * @param string|null $search  Search term
	 *
	 * @return array|false List of options
	 *
	 * @internal
	 */
	public function exec($db, $refresh, $search = null, $find = null)
	{
		// If search only, and not a search action, then just return false
		if ($this->searchOnly() && $search === null && $find === null) {
			return false;
		}

		// Only get the options if doing a full load, or always is set
		if ($refresh === true && !$this->alwaysRefresh()) {
			return false;
		}

		$fn = $this->_customFn;
		if (is_callable($fn)) {
			return $fn($db, $search);
		}

		$label = $this->_label;
		$value = $this->_value;
		$formatter = $this->_renderer;

		// Create a list of the fields that we need to get from the db
		$fields = [];
		$fields[] = $value;
		$fields = array_merge($fields, $label);

		// We need a default formatter if one isn't provided
		if (!$formatter) {
			$formatter = static function ($row) use ($label) {
				$a = [];

				for ($i = 0, $ien = count($label); $i < $ien; ++$i) {
					$a[] = $row[$label[$i]];
				}

				return implode(' ', $a);
			};
		}

		// Get the data
		$q = $db
			->query('select')
			->distinct(true)
			->table($this->_table)
			->left_join($this->_leftJoin)
			->get($fields)
			->where($this->_where);

		if (is_array($find)) {
			$q->where_in($value, $find);
		}

		if (is_string($this->_order)) {
			// For cases where we are ordering by a field which isn't included in the list
			// of fields to display, we need to add the ordering field, due to the
			// select distinct.
			$orderFields = explode(',', $this->_order);

			for ($i = 0, $ien = count($orderFields); $i < $ien; ++$i) {
				$field = strtolower($orderFields[$i]);
				$field = str_replace(' asc', '', $field);
				$field = str_replace(' desc', '', $field);
				$field = trim($field);

				if (!in_array($field, $fields)) {
					$q->get($field);
				}
			}

			$q->order($this->_order);
		} elseif ($this->_order === true) {
			// Attempt to do a database order, needed for "limit()"ed results
			$q->order($this->_label[0]);
		}

		$rows = $q
			->exec()
			->fetchAll();

		// Create the output array
		$out = [];
		$max = $this->_limit;

		for ($i = 0, $ien = count($rows); $i < $ien; ++$i) {
			$rowLabel = $formatter($rows[$i]);
			$rowValue = $rows[$i][$value];

			// Apply the search to the rendered label. Need to do it here rather than in SQL since
			// the label is rendered in PHP.
			if ($search === null || $search === '' || stripos($rowLabel, $search) === 0) {
				$option = [
					'label' => $rowLabel,
					'value' => $rowValue,
				];

				// Add in any columns that are needed for extra data (includes)
				for ($j = 0; $j < count($this->_includes); ++$j) {
					$inc = $this->_includes[$j];

					if (isset($rows[$i][$inc])) {
						$option[$inc] = $rows[$i][$inc];
					}
				}

				$out[] = $option;
			}

			// Limit needs to be done in PHP to allow for the PHP based filtering above, and also
			// for when using a custom function.
			if ($max !== null && count($out) >= $max) {
				break;
			}
		}

		// Stick on any extra manually added options
		if (count($this->_manualAdd)) {
			$out = array_merge($out, $this->_manualAdd);
		}

		// Local sorting
		if ($this->_order === true) {
			usort($out, static function ($a, $b) {
				$aLabel = $a['label'];
				$bLabel = $b['label'];

				if ($aLabel === null) {
					$aLabel = '';
				}

				if ($bLabel === null) {
					$bLabel = '';
				}

				return is_numeric($aLabel) && is_numeric($bLabel) ?
					($aLabel * 1) - ($bLabel * 1) :
				strcmp($aLabel, $bLabel);
			});
		}

		return $out;
	}

	/**
	 * Get the objects for a set of values.
	 *
	 * @param Database $db  Database connection
	 * @param array    $ids IDs to get
	 *
	 * @return array|bool
	 */
	public function find($db, $ids)
	{
		return $this->exec($db, false, null, $ids);
	}

	/**
	 * Do a search for data on the source.
	 *
	 * @param Database $db   Database connection
	 * @param string   $term Search term
	 *
	 * @return array|bool
	 */
	public function search($db, $term)
	{
		return $this->exec($db, false, $term);
	}
}
