# DataTables PHP server-side libraries

This is a PHP library to provide easy server-side support for [DataTables](https://datatables.net/) - _the_ Javascript table library.

These libraries provide support for:

* Server-side processing - work with millions of rows
* [Editor](https://editor.datatables.net) - CRUD UI for DataTables
* ColumnControl - Column search controls for DataTables
* SearchBuilder - Complex search logic UI

The library is framework-agnostic and can be used in any web framework, including Laravel, Symfony and CodeIgniter. It is dependency free, other than PHP core with PDO, and uses a database abstraction layer to operate with MySQL, Postgres, SQLite and SQLServer, using the same API.


## Installation

Available on [Packagist](https://packagist.org/packages/datatables.net/editor-php), this package can be installed with:

```sh
composer require datatables.net/editor-php
```

Then add:

```php
require 'vendor/autoload.php'
```

to your files that use the DataTables classes. The required classes will be automatically included as you use them.

If you prefer not to use composer and include the PHP files directly, download them and require the `DataTables.php` file, which will load the auto loader for the classes:

```php
require( $_SERVER['DOCUMENT_ROOT'] . 'lib/DataTables.php' );
```


### Database connection

You must create a database connection using the `Database` class. This can be done automatically by defining the array `$sql_details` in `config.php`, or creating a `new Database()` class instance directly:

```php
$db = new Database( [
	'type' => '',  // Database type: "Mysql", "Postgres", "Sqlserver", "Sqlite"
	'user' => '',  // Database user name
	'pass' => '',  // Database password
	'host' => '',  // Database host
	'port' => '',  // Database connection port (can be left empty for default)
	'db' => ''    // Database name
]);
```

There are two primary entry point classes in the library:

* `DataTable` - for read only tables
* `Editor` - for read / write tables, with [Editor](https://editor.datatables.net/)

There are also a number of supporting classes such as `Options`, `Mjoin` and more.


## Quick Start

Once a database connection is configured, you can use the library to read and write data on a database - e.g. the following is a simple PHP file that acts as a CRUD end point for for a DataTables / Editor client-side:

```php
<?php

// DataTables PHP library
include( "../lib/DataTables.php" );

// Alias the namespaces so the classes are easy to use
use
	DataTables\Editor,
	DataTables\Editor\Field,
	DataTables\Editor\Format,
	DataTables\Editor\Mjoin,
	DataTables\Editor\Options,
	DataTables\Editor\Upload,
	DataTables\Editor\Validate,
	DataTables\Editor\ValidateOptions;

// Build our Editor instance and process the data coming from $_POST
new Editor( $db, 'datatables_demo' )
	->fields(
		new Field( 'first_name' )
			->validator( Validate::notEmpty( new ValidateOptions()
				->message( 'A first name is required' )	
			) ),
		new Field( 'last_name' )
			->validator( Validate::notEmpty( new ValidateOptions()
				->message( 'A last name is required' )	
			) ),
		new Field( 'position' ),
		new Field( 'email' )
			->validator( Validate::email( new ValidateOptions()
				->message( 'Please enter an e-mail address' )	
			) ),
		new Field( 'office' ),
		new Field( 'extn' ),
		new Field( 'age' )
			->validator( Validate::numeric() )
			->setFormatter( Format::ifEmpty(null) ),
		new Field( 'salary' )
			->validator( Validate::numeric() )
			->setFormatter( Format::ifEmpty(null) ),
		new Field( 'start_date' )
			->validator( Validate::dateFormat( 'Y-m-d' ) )
			->getFormatter( Format::dateSqlToFormat( 'Y-m-d' ) )
			->setFormatter( Format::dateFormatToSql('Y-m-d' ) )
	)
	->process( $_POST )
	->json();
```

Note that the above uses PHP 8.4 syntax to chain a constructor without parentheses. If you are using an older version of PHP you can use either:

* `(new Editor($db, 'name'))->fields(...)`
* `Editor:inst($db, 'name')->fields(...)`


Similarly, if your table is readonly, the `DataTable` and `Column` classes can be used (this will support DataTables' client-side or server-side processing modes):

```php
<?php

include( "../lib/DataTables.php" );

use
	DataTables\DataTable,
	DataTables\DataTable\Column;

new DataTable( $db, 'datatables_demo' )
	->columns(
		new Column( 'first_name' ),
		new Column( 'last_name' ),
		new Column( 'position' ),
		new Column( 'email' ),
		new Column( 'office' )
	)
	->process( $_POST )
	->json();
```


## Documentation

For full documentation, [please refer to the DataTables site](https://datatables.net/manual/php).


## License

MIT — see [LICENSE](License.txt) for full text.
