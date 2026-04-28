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
class SearchBuilderOptions extends Ext
{
	/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
	 * Private parameters
	 */

	/** @var string Table to get the information from */
	private $_table;

	/** @var string Column name containing the value */
	private $_value;

	/** @var string[] Column names for the label(s) */
	private $_label = [];

	/** @var string[] Column names for left join */
	private $_leftJoin = [];

	/** @var callable Callback function to do rendering of labels */
	private $_renderer;

	/** @var array Callback function to add where conditions */
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
	 * Get / set the ORDER BY clause to use in the SQL. If this option is not
	 * provided the ordering will be based on the rendered output, either
	 * numerically or alphabetically based on the data returned by the renderer.
	 *
	 * @param string|null $_ String to set, null to get current value
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
	 * @param callable(string): string|null $_ Function to set, null to get current value
	 *
	 * @return ($_ is null ? callable : $this)
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

	/**
	 * Get / set the array values used for a leftJoin condition if it is to be
	 * applied to the query to get the options.
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
	 * Adds all of the where conditions to the desired query.
	 *
	 * @param Query $query the query being built
	 *
	 * @return $this
	 */
	private function _get_where($query)
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
		// If the value is not yet set then set the variable to be the field name
		if ($this->_value == null) {
			$value = $field->dbField();
		} else {
			$value = $this->_value;
		}

		$readTable = $editor->readTable();

		// If the table is not yet set then set the table variable to be the same as editor
		// This is not taking a value from the SearchBuilderOptions instance as the table should be defined in value/label. This throws up errors if not.
		if ($this->_table !== null) {
			$table = $this->_table;
		} elseif (count($readTable) > 0) {
			$table = $readTable;
		} else {
			$table = $editor->table();
		}

		// If the label value has not yet been set then just set it to be the same as value
		if ($this->_label == null) {
			$label = $value;
		} else {
			$label = $this->_label[0];
		}

		// Set the database from editor
		$db = $editor->db();

		$formatter = $this->_renderer;

		// We need a default formatter if one isn't provided
		if (!$formatter) {
			$formatter = static function ($str) {
				return $str;
			};
		}

		// Set up the join variable so that it will fit nicely later
		$leftJoin = gettype($this->_leftJoin) === 'array'
			? $this->_leftJoin
			: [$this->_leftJoin];

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

		// Set the query to get the current counts for viewTotal
		$query = $db
			->query('select')
			->table($table)
			->left_join($leftJoin);

		if ($field->apply('get') && $field->getValue() === null) {
			$query->get($value . ' as value', $label . ' as label');
			$query->group_by($value);
		}

		$res = $query
			->exec()
			->fetchAll();

		// Create the output array
		$out = [];

		for ($j = 0; $j < count($res); ++$j) {
			$out[] = [
				'value' => $res[$j]['value'],
				'label' => $res[$j]['label'],
			];
		}

		// Only sort if there was no SQL order field
		if (!$this->_order) {
			usort($out, static function ($a, $b) {
				$aLabel = $a['label'];
				$bLabel = $b['label'];

				if ($aLabel === null) {
					$aLabel = '';
				}

				if ($bLabel === null) {
					$bLabel = '';
				}

				return is_numeric($aLabel) && is_numeric($bLabel)
					? ($aLabel * 1) - ($bLabel * 1)
					: strcmp($aLabel, $bLabel);
			});
		}

		return $out;
	}

	/**
	 * Apply SearchBuilder conditions to a sever-side processing request query.
	 *
	 * @param Query $query Database query object
	 * @param mixed $data  SearchBuilder condition parameters
	 *
	 * @internal For use when processing an SSP request to add the require
	 * conditions.
	 */
	public static function ssp($query, $data)
	{
		$first = true;

		if (!isset($data['criteria'])) {
			return;
		}

		// Iterate over every group or criteria in the current group
		foreach ($data['criteria'] as $crit) {
			// If criteria is defined then this must be a group
			if (isset($crit['criteria'])) {
				// Check if this is the first, or if it is and logic
				if ($data['logic'] === 'AND' || $first) {
					// Call the function for the next group
					$query->where_group(static function ($q) use ($crit) {
						self::ssp($q, $crit);
					});
					// Set first to false so that in future only the logic is checked
					$first = false;
				} else {
					$query->where_group(static function ($q) use ($crit) {
						self::ssp($q, $crit);
					}, 'OR');
				}
			} elseif (isset($crit['condition']) && (isset($crit['value1']) || $crit['condition'] === 'null' || $crit['condition'] === '!null')) {
				// Sometimes the structure of the object that is passed across is named in a strange way.
				// This conditional assignment solves that issue
				$val1 = isset($crit['value1']) ? $crit['value1'] : '';
				$val2 = isset($crit['value2']) ? $crit['value2'] : '';

				if ($val1 == '' && $crit['condition'] !== 'null' && $crit['condition'] !== '!null') {
					continue;
				}

				if ($val2 == '' && ($crit['condition'] === 'between' || $crit['condition'] === '!between')) {
					continue;
				}

				// Switch on the condition that has been passed in
				switch ($crit['condition']) {
					case '=':
						// Check if this is the first, or if it is and logic
						if ($data['logic'] === 'AND' || $first) {
							// Call the where function for this condition
							$query->where($crit['origData'], $val1, '=');
							// Set first to false so that in future only the logic is checked
							$first = false;
						} else {
							// Call the or_where function - has to be or logic in this block
							$query->or_where($crit['origData'], $val1, '=');
						}

						break;
					case '!=':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], $val1, '<>');
							$first = false;
						} else {
							$query->or_where($crit['origData'], $val1, '<>');
						}

						break;
					case 'contains':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], '%' . $val1 . '%', 'LIKE');
							$first = false;
						} else {
							$query->or_where($crit['origData'], '%' . $val1 . '%', 'LIKE');
						}

						break;
					case '!contains':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], '%' . $val1 . '%', 'NOT LIKE');
							$first = false;
						} else {
							$query->or_where($crit['origData'], '%' . $val1 . '%', 'NOT LIKE');
						}

						break;
					case 'starts':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], $val1 . '%', 'LIKE');
							$first = false;
						} else {
							$query->or_where($crit['origData'], $val1 . '%', 'LIKE');
						}

						break;
					case '!starts':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], $val1 . '%', 'NOT LIKE');
							$first = false;
						} else {
							$query->or_where($crit['origData'], $val1 . '%', 'NOT LIKE');
						}

						break;
					case 'ends':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], '%' . $val1, 'LIKE');
							$first = false;
						} else {
							$query->or_where($crit['origData'], '%' . $val1, 'LIKE');
						}

						break;
					case '!ends':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], '%' . $val1, 'NOT LIKE');
							$first = false;
						} else {
							$query->or_where($crit['origData'], '%' . $val1, 'NOT LIKE');
						}

						break;
					case '<':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], $val1, '<');
							$first = false;
						} else {
							$query->or_where($crit['origData'], $val1, '<');
						}

						break;
					case '<=':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], $val1, '<=');
							$first = false;
						} else {
							$query->or_where($crit['origData'], $val1, '<=');
						}

						break;
					case '>=':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], $val1, '>=');
							$first = false;
						} else {
							$query->or_where($crit['origData'], $val1, '>=');
						}

						break;
					case '>':
						if ($data['logic'] === 'AND' || $first) {
							$query->where($crit['origData'], $val1, '>');
							$first = false;
						} else {
							$query->or_where($crit['origData'], $val1, '>');
						}

						break;
					case 'between':
						if ($data['logic'] === 'AND' || $first) {
							$query->where_group(static function ($q) use ($crit, $val1, $val2) {
								$q
									->where($crit['origData'], is_numeric($val1) ? (int) $val1 : $val1, '>=')
									->where($crit['origData'], is_numeric($val2) ? (int) $val2 : $val2, '<=');
							});
							$first = false;
						} else {
							$query
								->or_where($crit['origData'], is_numeric($val1) ? (int) $val1 : $val1, '>=')
								->where($crit['origData'], is_numeric($val2) ? (int) $val2 : $val2, '<=');
						}

						break;
					case '!between':
						if ($data['logic'] === 'AND' || $first) {
							$query->where_group(static function ($q) use ($crit, $val1, $val2) {
								$q->where($crit['origData'], is_numeric($val1) ? (int) $val1 : $val1, '<')->or_where($crit['origData'], is_numeric($val2) ? (int) $val2 : $val2, '>');
							});
							$first = false;
						} else {
							$query->or_where($crit['origData'], is_numeric($val1) ? (int) $val1 : $val1, '<')->or_where($crit['origData'], is_numeric($val2) ? (int) $val2 : $val2, '>');
						}

						break;
					case 'null':
						if ($data['logic'] === 'AND' || $first) {
							$query->where_group(static function ($q) use ($crit) {
								$q->where($crit['origData'], null, '=');
								if (strpos($crit['type'], 'date') === false && strpos($crit['type'], 'moment') === false && strpos($crit['type'], 'luxon') === false) {
									$q->or_where($crit['origData'], '', '=');
								}
							});
							$first = false;
						} else {
							$query->where_group(static function ($q) use ($crit) {
								$q->where($crit['origData'], null, '=');
								if (strpos($crit['type'], 'date') === false && strpos($crit['type'], 'moment') === false && strpos($crit['type'], 'luxon') === false) {
									$q->or_where($crit['origData'], '', '=');
								}
							}, 'OR');
						}

						break;
					case '!null':
						if ($data['logic'] === 'AND' || $first) {
							$query->where_group(static function ($q) use ($crit) {
								$q->where($crit['origData'], null, '!=');
								if (strpos($crit['type'], 'date') === false && strpos($crit['type'], 'moment') === false && strpos($crit['type'], 'luxon') === false) {
									$q->where($crit['origData'], '', '!=');
								}
							});
							$first = false;
						} else {
							$query->where_group(static function ($q) use ($crit) {
								$q->where($crit['origData'], null, '!=');
								if (strpos($crit['type'], 'date') === false && strpos($crit['type'], 'moment') === false && strpos($crit['type'], 'luxon') === false) {
									$q->where($crit['origData'], '', '!=');
								}
							}, 'OR');
						}

						break;
					default:
						break;
				}
			}
		}

		return $query;
	}
}
