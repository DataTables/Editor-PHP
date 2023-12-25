<?php

/**
 * DataTables PHP libraries.
 *
 * PHP libraries for DataTables and DataTables Editor.
 *
 * @author    SpryMedia
 * @copyright SpryMedia ( http://sprymedia.co.uk )
 * @license   http://editor.datatables.net/license DataTables Editor
 *
 * @see       http://editor.datatables.net
 */
define('DATATABLES', true);

//
// Error checking - check that we are PHP 5.4 or newer
//
if (version_compare(\PHP_VERSION, '5.4.0', '<')) {
	echo json_encode([
		'sError' => 'Editor PHP libraries required PHP 5.4 or newer. You are ' .
			'currently using ' . \PHP_VERSION . '. PHP 5.4 and newer have a lot of ' .
			'great new features that the Editor libraries take advantage of to ' .
			'present an easy to use and flexible API.',
	]);

	exit(1);
}

//
// Load the DataTables bootstrap core file and let it register the required
// handlers.
//
require __DIR__ . '/Bootstrap.php';
