<?php
/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor, utilising PHP 5.3+.
 *
 *  @author    SpryMedia
 *  @copyright 2016 SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *
 *  @see      http://editor.datatables.net
 */

namespace DataTables\Editor;

use DataTables;

/**
 * The Options class provides a convenient method of specifying where Editor
 * should get the list of options for a `select`, `radio` or `checkbox` field.
 * This is normally from a table that is _left joined_ to the main table being
 * edited, and a list of the values available from the joined table is shown to
 * the end user to let them select from.
 *
 * `Options` instances are used with the {@see Field->options()} method.
 *
 *  @example
 *   Get a list of options from the `sites` table
 *    ```php
 *    Field::inst( 'users.site' )
 *        ->options( Options::inst()
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( 'name' )
 *        )
 *    ```
 *  @example
 *   Get a list of options with custom ordering
 *    ```php
 *    Field::inst( 'users.site' )
 *        ->options( Options::inst()
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( 'name' )
 *            ->order( 'name DESC' )
 *        )
 *    ```
 *  @example
 *   Get a list of options showing the id and name in the label
 *    ```php
 *    Field::inst( 'users.site' )
 *        ->options( Options::inst()
 *            ->table( 'sites' )
 *            ->value( 'id' )
 *            ->label( [ 'name', 'id' ] )
 *            ->render( function ( $row ) {
 *              return $row['name'].' ('.$row['id'].')';
 *            } )
 *        )
 *    ```
 */
class SearchPaneOptions extends DataTables\Ext
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private parameters
	 */

	/** @var string Table to get the information from */
	private $_table;

	/** @var string Column name containing the value */
	private $_value;

	/** @var string[] Column names for the label(s) */
	private $_label = array();

	/** @var string[] Column names for left join */
	private $_leftJoin = array();

	/** @var callable Callback function to do rendering of labels */
	private $_renderer;

	/** @var callable Callback function to add where conditions */
	private $_where;

	/** @var string ORDER BY clause */
	private $_order;

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Public methods
	 */

	/**
	 * Get / set the column(s) to use as the label value of the options.
	 *
	 * @param string|string[]|null $_ null to get the current value, string or
	 *                                array to get.
	 *
	 * @return Options|string[] Self if setting for chaining, array of values if
	 *                          getting.
	 */
	public function label($_ = null)
	{
		if ($_ === null) {
			return $this;
		} elseif (is_string($_)) {
			$this->_label = array($_);
		} else {
			$this->_label = $_;
		}

		return $this;
	}

	/**
	 * Get / set the ORDER BY clause to use in the SQL. If this option is not
	 * provided the ordering will be based on the rendered output, either
	 * numerically or alphabetically based on the data returned by the renderer.
	 *
	 * @param string|null $_ String to set, null to get current value
	 *
	 * @return Options|string Self if setting for chaining, string if getting.
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
	 * @param callable|null $_ Function to set, null to get current value
	 *
	 * @return Options|callable Self if setting for chaining, callable if
	 *                          getting.
	 */
	public function render($_ = null)
	{
		return $this->_getSet($this->_renderer, $_);
	}

	/**
	 * Get / set the database table from which to gather the options for the
	 * list.
	 *
	 * @param string|null $_ String to set, null to get current value
	 *
	 * @return Options|string Self if setting for chaining, string if getting.
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
	 * @return Options|string Self if setting for chaining, string if getting.
	 */
	public function value($_ = null)
	{
		return $this->_getSet($this->_value, $_);
	}

	/**
	 * Get / set the method to use for a WHERE condition if it is to be
	 * applied to the query to get the options.
	 *
	 * @param callable|null $_ Function to set, null to get current value
	 *
	 * @return Options|callable Self if setting for chaining, callable if
	 *                          getting.
	 */
	public function where($_ = null)
	{
		return $this->_getSet($this->_where, $_);
	}

	/**
	 * Get / set the array values used for a leftJoin condition if it is to be
	 * applied to the query to get the options.
	 *
	 * @param string $table    to get the information from
	 * @param string $field1   the first field to get the information from
	 * @param string $operator the operation to perform on the two fields
	 * @param string $field2   the second field to get the information from
	 *
	 * @return self
	 */
	public function leftJoin($table, $field1, $operator, $field2)
	{
		$this->_leftJoin[] = array(
			'table' => $table,
			'field1' => $field1,
			'field2' => $field2,
			'operator' => $operator,
		);

		return $this;
	}

	/**
	 * Adds all of the where conditions to the desired query.
	 *
	 * @param string $query the query being built
	 *
	 * @return self
	 */
	private function _get_where($query)
	{
		for ($i = 0; $i < count($this->_where); ++$i) {
			if (is_callable($this->_where[$i])) {
				$this->_where[$i]($query);
			} else {
				$query->where(
					$this->_where[$i]['key'],
					$this->_where[$i]['value'],
					$this->_where[$i]['op']
				);
			}
		}

		return $this;
	}

	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Internal methods
	 */

	/**
	 * Execute the options (i.e. get them).
	 *
	 * @return array List of options
	 *
	 * @internal
	 */
	public function exec($field, $editor, $http, $fields, $leftJoinIn)
	{
		$db = $editor->db();
		$readTable = $editor->readTable();
		$filteringActive = isset($http['searchPanes']);
		$viewCount = isset($http['searchPanes_options'])
			? filter_var($http['searchPanes_options']['viewCount'], \FILTER_VALIDATE_BOOLEAN)
			: true;
		$viewTotal = isset($http['searchPanes_options'])
			? filter_var($http['searchPanes_options']['viewTotal'], \FILTER_VALIDATE_BOOLEAN)
			: false;
		$cascade = isset($http['searchPanes_options'])
			? filter_var($http['searchPanes_options']['cascade'], \FILTER_VALIDATE_BOOLEAN)
			: false;
		$entries = null;

		// If the value is not yet set then set the variable to be the field name
		$value = $this->_value == null
			? $field->dbField()
			: $this->_value;

		// If the table is not yet set then set the table variable to be the same as editor
		// This is not taking a value from the SearchPaneOptions instance as the table should be defined in value/label. This throws up errors if not.
		if ($this->_table !== null) {
			$table = $this->_table;
		} elseif (count($readTable) > 0) {
			$table = $readTable;
		} else {
			$table = $editor->table();
		}

		// If the label value has not yet been set then just set it to be the same as value
		$label = $this->_label == null
			? $value
			: $this->_label[0];

		$formatter = $this->_renderer ?: function ($str) {
			return $str;
		};

		// Set up the join variable so that it will fit nicely later
		$leftJoin = gettype($this->_leftJoin) === 'array' ?
			$this->_leftJoin :
			array($this->_leftJoin);

		foreach ($leftJoinIn as $lj) {
			$found = false;

			foreach ($leftJoin as $lje) {
				if ($lj['table'] === $lje['table']) {
					$found = true;
				}
			}

			if (!$found) {
				$leftJoin[] = $lj;
			}
		}

		// Get the data for the pane options
		$q = $db
			->query('select')
			->distinct(true)
			->table($table)
			->get($label . ' as label', $value . ' as value')
			->left_join($leftJoin)
			->group_by($value)
			->where($this->_where);

		// If not cascading, then the total and count must be the same
		if ($viewTotal) {
			$q->get('COUNT(*) as total');
		}

		if ($this->_order) {
			// For cases where we are ordering by a field which isn't included in the list
			// of fields to display, we need to add the ordering field, due to the
			// select distinct.
			$orderFields = explode(',', $this->_order);

			for ($i = 0, $ien = count($orderFields); $i < $ien; ++$i) {
				$orderField = strtolower($orderFields[$i]);
				$orderField = str_replace(' asc', '', $orderField);
				$orderField = str_replace(' desc', '', $orderField);
				$orderField = trim($orderField);

				if (!in_array($orderField, $fields)) {
					$q->get($orderField);
				}
			}

			$q->order($this->_order);
		}

		$rows = $q
			->exec()
			->fetchAll();

		// Remove any filtering entries that don't exist in the database (values might have changed)
		if (isset($http['searchPanes'][$field->name()])) {
			$values = array_column($rows, 'value');
			$selected = $http['searchPanes'][$field->name()];

			for ($i = 0; $i < count($selected); ++$i) {
				$idx = array_search($selected[$i], $values);

				if ($idx === false) {
					array_splice($http['searchPanes'][$field->name()], $i, 1);
				}
			}
		}

		// Apply filters to cascade tables
		if ($viewCount || $cascade) {
			$query = $db
				->query('select')
				->distinct(true)
				->table($table)
				->left_join($leftJoin);

			if ($field->apply('get') && $field->getValue() === null) {
				$query->get($value . ' as value');
				$query->group_by($value);

				// We viewTotal is enabled, we need to do a count to get the number of records,
				// If it isn't we still need to know it exists, but don't care about the cardinality
				if ($viewCount) {
					$query->get('COUNT(*) as count');
				} else {
					$query->get('(1) as count');
				}
			}

			// Construct the where queries based upon the options selected by the user
			foreach ($fields as $fieldOpt) {
				$add = false;
				$fieldName = $fieldOpt->name();

				// If there is a last value set then a slightly different set of results is required for cascade
				// That panes results are based off of the results when only considering the selections of all of the others
				if (isset($http['searchPanesLast']) && $field->name() === $http['searchPanesLast']) {
					if (isset($http['searchPanes'][$fieldName]) && $fieldName !== $http['searchPanesLast']) {
						$add = true;
					}
				} elseif (isset($http['searchPanes']) && isset($http['searchPanes'][$fieldName])) {
					$add = true;
				}

				if ($add) {
					$query->where(function ($q) use ($fieldOpt, $http, $fieldName) {
						for ($j = 0, $jen = count($http['searchPanes'][$fieldName]); $j < $jen; ++$j) {
							$q->or_where(
								$fieldOpt->dbField(),
								isset($http['searchPanes_null'][$fieldName][$j])
									? null
									: $http['searchPanes'][$fieldName][$j],
								'='
							);
						}
					});
				}
			}

			$entriesRows = $query
				->exec()
				->fetchAll();

			// Key by the value for fast lookup
			$entriesKeys = array_column($entriesRows, 'value');
			$entries = array_combine($entriesKeys, $entriesRows);
		}

		$out = array();

		for ($i = 0, $ien = count($rows); $i < $ien; ++$i) {
			$row = $rows[$i];
			$value = $row['value'];
			$total = isset($row['total']) ? $row['total'] : null;
			$count = $total;

			if ($entries !== null) {
				$count = isset($entries[$value]) && isset($entries[$value]['count'])
					? $entries[$value]['count']
					: 0;

				// For when viewCount is enabled and viewTotal is not
				// the total needs to be the same as the count!
				if ($total === null) {
					$total = $count;
				}
			}

			$out[] = array(
				'label' => $formatter($row['label']),
				'total' => $total,
				'value' => $value,
				'count' => $count,
			);
		}

		// Only sort if there was no SQL order field
		if (!$this->_order) {
			usort($out, function ($a, $b) {
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
}
