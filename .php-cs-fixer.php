<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// @todo Update paths
$finder = Finder::create()
    ->in(__DIR__ . '/src')
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/var/.php-cs-fixer.cache')
    ->setRiskyAllowed(false)
    ->setRules([
        '@PSR12' => true,
        '@PHP73Migration' => true,

        // put operators at line start
        'operator_linebreak' => ['position' => 'beginning'],

        // spacing and line-break hygiene
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => ['=>' => 'single_space'], // avoid alignment; PSR-12 friendly
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => false,
        ],
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['return', 'throw'],
        ],
        'no_trailing_whitespace' => true,
        'no_whitespace_in_blank_line' => true,

        // arrays and lists
        'array_syntax' => ['syntax' => 'short'],
        'list_syntax' => ['syntax' => 'short'],

        // imports
        'no_unused_imports' => true,
        'ordered_imports' => [
            'imports_order' => ['class', 'function', 'const'],
            'sort_algorithm' => 'alpha',
        ],

        // strings and concatenation
        'single_quote' => true,
        'concat_space' => ['spacing' => 'one'],

        // types
        'return_type_declaration' => ['space_before' => 'none'],

        // PHPDoc polish (style-only)
        'phpdoc_order' => true,
        'phpdoc_summary' => true,
    ])
    ->setIndent('    ') // PSR-12: 4 spaces
    ->setLineEnding("\n") // PSR-12: LF
    ->setFinder($finder);
