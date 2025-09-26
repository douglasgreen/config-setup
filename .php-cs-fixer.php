<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// Dynamically determine paths from php_paths file
$paths = file('php_paths', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($paths === false) {
    exit('PHP paths not found' . PHP_EOL);
}

// Only dirs are supported
$directories = array_filter($paths, 'is_dir');

$finder = Finder::create()
    ->in($directories);

return (new Config())
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/var/cache/php-cs-fixer/.php-cs-fixer.cache')
    ->setRiskyAllowed(false)
    ->setRules([
        //'@PER-CS' => true,
        //'@PSR12' => true,
        //'@Symfony' => true,
        //'@auto' => true,
        //'@autoPHPMigration' => true,

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
        'phpdoc_param_order' => true,
        'phpdoc_summary' => true,
        'phpdoc_order' => [
            'order' => [
                // Recommended Order of PHPDoc Tags (see phpdoc_order for implementation)

                // Warnings/Status (prominent for quick scanning)
                'deprecated',
                'final',

                // Metadata (file/class-level info)
                'author',
                'category',
                'copyright',
                'license',
                'package',
                'since',
                'subpackage',
                'version',

                // Visibility/Access
                'api',
                'global',
                'ignore',
                'internal',

                // Structural/Declaration
                'method',
                'property',
                'property-read',
                'property-write',
                'var',

                // Method/Function-Specific
                'param',
                'throws',
                'return',

                // Relational/References
                'link',
                'see',
                'uses',
                'used-by',

                // Examples/Source/Tasks (supplementary at the end)
                'example',
                'filesource',
                'source',
                'todo',
            ],
        ],
    ])
    ->setIndent('    ') // PSR-12: 4 spaces
    ->setLineEnding("\n") // PSR-12: LF
    ->setFinder($finder);
