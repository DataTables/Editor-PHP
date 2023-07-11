<?php
/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor, utilising PHP 5.3+.
 *
 *  @author    SpryMedia
 *  @copyright SpryMedia ( http://sprymedia.co.uk )
 *  @license   http://editor.datatables.net/license DataTables Editor
 *
 *  @see      http://editor.datatables.net
 */

namespace DataTables;

// ensure included from DataTables.php
// this file must not be included when installed using composer
if (!defined('DATATABLES')) {
	exit(1);
}

//
// Auto-loader
//   Automatically loads DataTables classes - they are psr-4 compliant
//
spl_autoload_register(function ($class) {
	$a = explode('\\', $class);

	// Are we working in the DataTables namespace
	if ($a[0] !== 'DataTables') {
		return;
	}

	array_shift($a);
	$className = array_pop($a);
	$path = count($a) ?
		implode('/', $a) . '/' :
		'';

	require __DIR__ . '/' . $path . $className . '.php';
});

//
// Configuration
//   Load the database connection configuration options
//
if (!isset($sql_details)) {
	include __DIR__ . '/config.php';
}

//
// Database connection
//   Database connection is globally available
//
$db = new Database($sql_details);
