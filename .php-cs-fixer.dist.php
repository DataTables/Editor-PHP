<?php

$finder = PhpCsFixer\Finder::create()
	->in([__DIR__])
	->ignoreDotFiles(false)
	->ignoreVCS(true)
	->exclude(['vendor', 'HtmLawed']);

$config = new PhpCsFixer\Config();

return $config
	->setRiskyAllowed(true)
	->setIndent("\t")
	->setRules([
		'@PhpCsFixer' => true,
		'@PhpCsFixer:risky' => true,
		'@PHP74Migration' => true,
		'@PHP74Migration:risky' => true,

		// required by PSR-12
		'concat_space' => [
			'spacing' => 'one',
		],

		// disable some too strict rules
		'phpdoc_types_order' => [
			'null_adjustment' => 'always_last',
			'sort_algorithm' => 'none',
		],
		'single_line_throw' => false,
		'yoda_style' => [
			'equal' => false,
			'identical' => false,
		],
		'native_constant_invocation' => true,
		'native_function_invocation' => false,
		'void_return' => false,
		'blank_line_before_statement' => [
			'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'exit'],
		],
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
		'general_phpdoc_annotation_remove' => [
			'annotations' => [/* 'author', 'copyright', */ 'throws'],
		],
		'nullable_type_declaration_for_default_null_value' => [
			'use_nullable_type_declaration' => false,
		],

		// fn => without curly brackets is less readable,
		// also prevent bounding of unwanted variables for GC
		'use_arrow_functions' => false,

		// disable too destructive formating for now
		'ternary_to_null_coalescing' => false, // needs PHP 7.0+
		'single_line_comment_style' => false,
		'phpdoc_annotation_without_dot' => false,
		'declare_strict_types' => false,
		'static_lambda' => false, // needs PHP 5.4+
		'strict_comparison' => false,
		'strict_param' => false, // TODO
		'final_internal_class' => false,
		'function_to_constant' => false, // needs PHP 5.5+
		'self_accessor' => false, // TODO some should be converted to static:: probably
		'visibility_required' => ['elements' => ['property', 'method']], // needs PHP 7.1+
	])
	->setFinder($finder)
	->setCacheFile(sys_get_temp_dir() . '/php-cs-fixer.' . md5(__DIR__) . '.cache');
