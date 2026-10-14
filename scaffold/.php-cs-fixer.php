<?php

// Project code style: PSR-12 rules but indented with a single tab (not spaces),
// so PHP matches the tab convention used across the Vue/JS side. Kept close to
// Laravel Pint's "laravel" preset; the notable divergence is tab indentation,
// which Pint cannot express.

$finder = PhpCsFixer\Finder::create()
	->in([
		__DIR__.'/app',
		__DIR__.'/config',
		__DIR__.'/database',
		__DIR__.'/routes',
		__DIR__.'/tests',
	])
	->name('*.php')
	->notName('*.blade.php')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

return (new PhpCsFixer\Config)
	->setRules([
		'@PSR12' => true,
		'array_syntax' => ['syntax' => 'short'],
		'ordered_imports' => ['sort_algorithm' => 'alpha'],
		'no_unused_imports' => true,
		'not_operator_with_successor_space' => false,
		'trailing_comma_in_multiline' => ['elements' => ['arrays']],
		'phpdoc_scalar' => true,
		'unary_operator_spaces' => true,
		'binary_operator_spaces' => true,
		'blank_line_before_statement' => ['statements' => ['return']],
		'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
		'single_trait_insert_per_statement' => true,
		// Match Laravel/Pint style: no parens on parameterless `new`, and keep
		// the anonymous-class-migration brace on its own line.
		'new_with_parentheses' => false,
		'braces_position' => [
			'anonymous_classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
		],
		// Keep `) {}` compact (e.g. constructor property promotion with no body).
		'single_line_empty_body' => true,
	])
	->setIndent("\t")
	->setLineEnding("\n")
	->setFinder($finder);
