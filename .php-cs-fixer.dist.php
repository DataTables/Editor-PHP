<?php

$finder = PhpCsFixer\Finder::create()
	->in(array(__DIR__))
	->ignoreDotFiles(false)
	->exclude(array('.git', 'vendor'));

$config = new PhpCsFixer\Config();

return $config
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setRules(array(
		'@PhpCsFixer' => true,
		'@PHP74Migration' => true,

		// required by PSR-12
		'concat_space' => array(
			'spacing' => 'one',
		),

		// disable some too strict rules
		'phpdoc_types_order' => array(
			'null_adjustment' => 'always_last',
			'sort_algorithm' => 'none',
		),
		'single_line_throw' => false,
		'yoda_style' => array(
			'equal' => false,
			'identical' => false,
		),
		'native_constant_invocation' => true,
		'native_function_invocation' => false,
		'void_return' => false,
		'blank_line_before_statement' => array(
			'statements' => array('break', 'continue', 'declare', 'return', 'throw', 'exit'),
		),
		'combine_consecutive_issets' => false,
		'combine_consecutive_unsets' => false,
		'multiline_whitespace_before_semicolons' => false,
		'no_superfluous_elseif' => false,
		'ordered_class_elements' => false,
		'php_unit_internal_class' => false,
		'php_unit_test_class_requires_covers' => false,
		'phpdoc_add_missing_param_annotation' => false,
		'return_assignment' => false,
		'comment_to_phpdoc' => false,
		'general_phpdoc_annotation_remove' => array(
			'annotations' => array('author', 'copyright', 'throws'),
		),
		'nullable_type_declaration_for_default_null_value' => array(
			'use_nullable_type_declaration' => false,
		),

		// fn => without curly brackets is less readable,
		// also prevent bounding of unwanted variables for GC
		'use_arrow_functions' => false,

		// disable too destructive formating for now
		'escape_implicit_backslashes' => false,
		'heredoc_to_nowdoc' => false,
		'no_useless_else' => false,
		'single_line_comment_style' => false,

		'list_syntax' => array('syntax' => 'long'),
		'array_syntax' => array('syntax' => 'long'),
		'ternary_to_null_coalescing' => false, // needs PHP 7.0+

		// enable some safe rules from @PHP71Migration:risky
		'pow_to_exponentiation' => true,
		'modernize_types_casting' => true,
		'dir_constant' => true,
		'combine_nested_dirname' => true,
		'non_printable_character' => array(
			'use_escape_sequences_in_strings' => true,
		),

		// TODO
		'general_phpdoc_annotation_remove' => false,
		'phpdoc_annotation_without_dot' => false,
	))
	->setFinder($finder)
	->setCacheFile(sys_get_temp_dir() . '/php-cs-fixer.' . md5(__DIR__) . '.cache');
