<?php

namespace DataTables\Editor;

class ColumnSearch
{
	static function process($query, $field, $colIdx, $searchTerm, $http)
	{
		if (! isset($http['columnSearch'])) {
			return false;
		}

		if (! isset($http['columnSearch'][$colIdx])) {
			return false;
		}

		$colSearch = $http['columnSearch'][$colIdx];

		if ($colSearch['type'] === 'select') {
			// Absolute search term
			if ($searchTerm) {
				$query->where($field->dbField(), $searchTerm);
			}

			return true;
		} else if ($colSearch['type'] === 'dateRange') {
			if ($searchTerm) {
				$parts = explode('|', $searchTerm);

				if ($parts[0]) {
					$query->where($field->dbField(), $parts[0], '>=');
				}

				if ($parts[1]) {
					$query->where($field->dbField(), $parts[1], '<=');
				}
			}

			return true;
		}

		// Text fields can get the default handling
		return false;
	}
}
