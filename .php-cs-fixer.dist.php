<?php

$finder = PhpCsFixer\Finder::create()
	->in(array(__DIR__))
	->ignoreDotFiles(false)
	->ignoreVCS(true)
	->exclude(array('vendor'));

$config = new PhpCsFixer\Config();

return $config
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setRules(array(
		'@PhpCsFixer' => true,
		'@PhpCsFixer:risky' => true,
		'@PHP74Migration' => true,
		'@PHP74Migration:risky' => true,

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
			'annotations' => array(/* 'author', 'copyright', */ 'throws'),
		),
		'nullable_type_declaration_for_default_null_value' => array(
			'use_nullable_type_declaration' => false,
		),

		// fn => without curly brackets is less readable,
		// also prevent bounding of unwanted variables for GC
		'use_arrow_functions' => false,

		// disable too destructive formating for now
		'list_syntax' => array('syntax' => 'long'), // needs PHP 5.4+
		'array_syntax' => array('syntax' => 'long'), // needs PHP 5.4+
		'ternary_to_null_coalescing' => false, // needs PHP 7.0+
		'single_line_comment_style' => false,
		'phpdoc_annotation_without_dot' => false,
		'declare_strict_types' => false,
		'strict_comparison' => false,
		'strict_param' => false, // TODO
		'final_internal_class' => false,
		'function_to_constant' => false, // needs PHP 5.5+
		'self_accessor' => false, // TODO some should be converted to static:: probably
		'visibility_required' => array('elements' => array('property', 'method')), // needs PHP 7.1+
	))
	->setFinder($finder)
	->setCacheFile(sys_get_temp_dir() . '/php-cs-fixer.' . md5(__DIR__) . '.cache');
