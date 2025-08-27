<?php

namespace DataTables\Editor;

/**
 * Column search methods for server-side processing
 */
class ColumnControl
{
	/**
	 * Apply conditions to a query for a ColumnControl search
	 *
	 * @param \DataTables\Editor $editor Host Editor instance
	 * @param \DataTables\Database\Query $query Query to add conditions to
	 * @param mixed $http Request object
	 * @return void
	 */
	public static function ssp(&$editor, &$query, $http)
	{
		for ($i = 0; $i < count($http['columns']); $i++) {
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
					} else if ($type === 'date') {
						self::_sspDate($query, $field, $value, $logic);
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
	 * Add conditions to a query for a ColumnControl date search
	 *
	 * @param \DataTables\Database\Query $query Query to add the conditions to
	 * @param \DataTables\Editor\Field $field Field for the column in question
	 * @param string $value Search term
	 * @param string $logic Search logic
	 * @return void
	 */
	private static function _sspDate(&$query, $field, $value, $logic)
	{
		if ($logic === 'empty') {
			$query->where($field->dbField(), null);
		} else if ($logic === 'notEmpty') {
			$query->where($field->dbField(), null, '!=');
		} else if ($value === '') {
			// Empty search value means no search for the other logic operators
			return;
		} else if ($logic === 'equal') {
			$query->where($field->dbField(), $value);
		} else if ($logic === 'notEqual') {
			$query->where($field->dbField(), $value, '!=');
		} else if ($logic === 'greater') {
			$query->where($field->dbField(), $value, '>');
		} else if ($logic === 'less') {
			$query->where($field->dbField(), $value, '<');
		}
	}

	/**
	 * Add conditions to a query for a ColumnControl number search
	 *
	 * @param \DataTables\Database\Query $query Query to add the conditions to
	 * @param \DataTables\Editor\Field $field Field for the column in question
	 * @param string $value Search term
	 * @param string $logic Search logic
	 * @return void
	 */
	private static function _sspNumber(&$query, $field, $value, $logic)
	{
		if ($logic === 'empty') {
			$query->where(function ($q) use ($field) {
				$q->where($field->dbField(), null);
				$q->or_where($field->dbField(), '');
			});
		} else if ($logic === 'notEmpty') {
			$query->where(function ($q) use ($field) {
				$q->where($field->dbField(), null, '!=');
				$q->where($field->dbField(), '', '!=');
			});
		} else if ($value === '') {
			// Empty search value means no search for the other logic operators
			return;
		} else if ($logic === 'equal') {
			$query->where($field->dbField(), $value);
		} else if ($logic === 'notEqual') {
			$query->where($field->dbField(), $value, '!=');
		} else if ($logic === 'greater') {
			$query->where($field->dbField(), $value, '>');
		} else if ($logic === 'greaterOrEqual') {
			$query->where($field->dbField(), $value, '>=');
		} else if ($logic === 'less') {
			$query->where($field->dbField(), $value, '<');
		} else if ($logic === 'lessOrEqual') {
			$query->where($field->dbField(), $value, '<=');
		}
	}

	/**
	 * Add conditions to a query for a ColumnControl text search
	 *
	 * @param \DataTables\Database\Query $query Query to add the conditions to
	 * @param \DataTables\Editor\Field $field Field for the column in question
	 * @param string $value Search term
	 * @param string $logic Search logic
	 * @return void
	 */
	private static function _sspText(&$query, $field, $value, $logic)
	{
		if ($logic === 'empty') {
			$query->where(function ($q) use ($field) {
				$q->where($field->dbField(), null);
				$q->or_where($field->dbField(), '');
			});
		} else if ($logic === 'notEmpty') {
			$query->where(function ($q) use ($field) {
				$q->where($field->dbField(), null, '!=');
				$q->where($field->dbField(), '', '!=');
			});
		} else if ($value === '') {
			// Empty search value means no search for the other logic operators
			return;
		} else if ($logic === 'equal') {
			$query->where($field->dbField(), $value);
		} else if ($logic === 'notEqual') {
			$query->where($field->dbField(), $value, '!=');
		} else if ($logic === 'contains') {
			$query->where($field->dbField(), '%' . $value . '%', 'like');
		} else if ($logic === 'notContains') {
			$query->where($field->dbField(), '%' . $value . '%', 'not like');
		} else if ($logic === 'starts') {
			$query->where($field->dbField(), $value . '%', 'like');
		} else if ($logic === 'ends') {
			$query->where($field->dbField(), '%' . $value, 'like');
		}
	}
}
