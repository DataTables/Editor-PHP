<?php

namespace DataTables\Editor;

/**
 * Column search methods for server-side processing.
 */
class ColumnControl
{
	/**
	 * Apply conditions to a query for a ColumnControl search.
	 *
	 * @param \DataTables\Editor         $editor Host Editor instance
	 * @param \DataTables\Database\Query $query  Query to add conditions to
	 * @param mixed                      $http   Request object
	 */
	public static function ssp(&$editor, &$query, $http)
	{
		for ($i = 0; $i < count($http['columns']); ++$i) {
			$column = $http['columns'][$i];

			if (isset($column['columnControl'])) {
				$field = $editor->field($column['data']);

				// `<input>` based searches
				if (isset($column['columnControl']['search'])) {
					$search = $column['columnControl']['search'];

					$value = $search['value'];
					$logic = $search['logic'];
					$type = $search['type'];

					if ($type === 'num') {
						self::_sspNumber($query, $field, $value, $logic);
					} elseif ($type === 'date') {
						self::_sspDate($query, $field, $value, $logic, $search['mask']);
					} else {
						self::_sspText($query, $field, $value, $logic);
					}
				}

				// SearchList
				if (isset($column['columnControl']['list'])) {
					$list = $column['columnControl']['list'];

					$query->where_in($field->dbField(), $list);
				}
			}
		}
	}

	/**
	 * Add conditions to a query for a ColumnControl date search.
	 *
	 * @param \DataTables\Database\Query $query Query to add the conditions to
	 * @param \DataTables\Editor\Field   $field Field for the column in question
	 * @param string                     $value Search term
	 * @param string                     $logic Search logic
	 * @param string                     $mask  Mask value
	 */
	private static function _sspDate(&$query, $field, $value, $logic, $mask)
	{
		$bindingName = $query->bindName();
		$dbField = $field->dbField();
		$search = $bindingName;

		// Only support date and time masks. This departs from the client side which allows
		// any component in the date/time to be masked out.
		if ($mask === 'YYYY-MM-DD') {
			$dbField = 'DATE(' . $dbField . ')';
			$search = 'DATE(' . $bindingName . ')';
		} elseif ($mask === 'hh:mm:ss') {
			$dbField = 'TIME(' . $dbField . ')';
			$search = 'TIME(' . $bindingName . ')';
		} else {
			$search = '(' . $bindingName . ')';
		}

		if ($logic === 'empty') {
			$query->where($field->dbField(), null);
		} elseif ($logic === 'notEmpty') {
			$query->where($field->dbField(), null, '!=');
		} elseif ($value === '') {
			// Empty search value means no search for the other logic operators
			return;
		} elseif ($logic === 'equal') {
			$query
				->where($dbField, $search, '=', false)
				->bind($bindingName, $value);
		} elseif ($logic === 'notEqual') {
			$query
				->where($dbField, $search, '!=', false)
				->bind($bindingName, $value);
		} elseif ($logic === 'greater') {
			$query
				->where($dbField, $search, '>', false)
				->bind($bindingName, $value);
		} elseif ($logic === 'less') {
			$query
				->where($dbField, $search, '<', false)
				->bind($bindingName, $value);
		}
	}

	/**
	 * Add conditions to a query for a ColumnControl number search.
	 *
	 * @param \DataTables\Database\Query $query Query to add the conditions to
	 * @param \DataTables\Editor\Field   $field Field for the column in question
	 * @param string                     $value Search term
	 * @param string                     $logic Search logic
	 */
	private static function _sspNumber(&$query, $field, $value, $logic)
	{
		if ($logic === 'empty') {
			$query->where(static function ($q) use ($field) {
				$q->where($field->dbField(), null);
				$q->or_where($field->dbField(), '');
			});
		} elseif ($logic === 'notEmpty') {
			$query->where(static function ($q) use ($field) {
				$q->where($field->dbField(), null, '!=');
				$q->where($field->dbField(), '', '!=');
			});
		} elseif ($value === '') {
			// Empty search value means no search for the other logic operators
			return;
		} elseif ($logic === 'equal') {
			$query->where($field->dbField(), $value);
		} elseif ($logic === 'notEqual') {
			$query->where($field->dbField(), $value, '!=');
		} elseif ($logic === 'greater') {
			$query->where($field->dbField(), $value, '>');
		} elseif ($logic === 'greaterOrEqual') {
			$query->where($field->dbField(), $value, '>=');
		} elseif ($logic === 'less') {
			$query->where($field->dbField(), $value, '<');
		} elseif ($logic === 'lessOrEqual') {
			$query->where($field->dbField(), $value, '<=');
		}
	}

	/**
	 * Add conditions to a query for a ColumnControl text search.
	 *
	 * @param \DataTables\Database\Query $query Query to add the conditions to
	 * @param \DataTables\Editor\Field   $field Field for the column in question
	 * @param string                     $value Search term
	 * @param string                     $logic Search logic
	 */
	private static function _sspText(&$query, $field, $value, $logic)
	{
		if ($logic === 'empty') {
			$query->where(static function ($q) use ($field) {
				$q->where($field->dbField(), null);
				$q->or_where($field->dbField(), '');
			});
		} elseif ($logic === 'notEmpty') {
			$query->where(static function ($q) use ($field) {
				$q->where($field->dbField(), null, '!=');
				$q->where($field->dbField(), '', '!=');
			});
		} elseif ($value === '') {
			// Empty search value means no search for the other logic operators
			return;
		} elseif ($logic === 'equal') {
			$query->where($field->dbField(), $value);
		} elseif ($logic === 'notEqual') {
			$query->where($field->dbField(), $value, '!=');
		} elseif ($logic === 'contains') {
			$query->where($field->dbField(), '%' . $value . '%', 'like');
		} elseif ($logic === 'notContains') {
			$query->where($field->dbField(), '%' . $value . '%', 'not like');
		} elseif ($logic === 'starts') {
			$query->where($field->dbField(), $value . '%', 'like');
		} elseif ($logic === 'ends') {
			$query->where($field->dbField(), '%' . $value, 'like');
		}
	}
}
