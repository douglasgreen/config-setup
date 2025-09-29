<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

// Enable risky rules via environment variable
$isRisky = (bool) getenv('PHP_CS_FIXER_RISKY');

// Dynamically determine paths from php_paths file
$paths = file('php_paths', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($paths === false) {
    exit('PHP paths not found' . PHP_EOL);
}

// Only dirs are supported
$directories = array_filter($paths, 'is_dir');

// Detect composer package installations to determine what rulesets to use.
$hasPhpUnit = false;
$hasSymfony = false;
$hasDoctrine = false;

if (file_exists('composer.json')) {
    $composerContent = file_get_contents('composer.json');
    if ($composerContent !== false) {
        $composerData = json_decode($composerContent, true, 16, JSON_THROW_ON_ERROR);

        // Check for PHPUnit, Symfony, and Doctrine
        $requires = $composerData['require'] ?? [];
        $requiresDev = $composerData['require-dev'] ?? [];

        $allDependencies = array_merge($requires, $requiresDev);

        foreach (array_keys($allDependencies) as $name) {
            if (preg_match('#^phpunit/#', (string) $name)) {
                $hasPhpUnit = true;
            }

            if (preg_match('#^symfony/#', (string) $name)) {
                $hasSymfony = true;
            }

            if (preg_match('#^doctrine/#', (string) $name)) {
                $hasDoctrine = true;
            }
        }
    }
}

$rulesets = [
    '@autoPHPMigration' => true,
    '@auto' => true,
    '@PER-CS' => true,
    '@PhpCsFixer' => true,
    '@PSR12' => true,
];

if ($isRisky) {
    if ($hasPhpUnit) {
        $rulesets['@autoPHPUnitMigration:risky'] = true;
    }

    $rulesets['@auto:risky'] = true;
    $rulesets['@PER-CS:risky'] = true;
    $rulesets['@PhpCsFixer:risky'] = true;
    $rulesets['@PSR12:risky'] = true;
    $rulesets['@Symfony:risky'] = true;
}

if ($hasDoctrine) {
    $rulesets['@DoctrineAnnotation'] = true;
}

if ($hasSymfony) {
    $rulesets['@Symfony'] = true;
}

$rules = [
    // operators
    'operator_linebreak' => [
        'position' => 'beginning',
    ],
    'yoda_style' => [
        'equal' => false,
        'identical' => false,
    ],

    // braces
    'braces_position' => [
        'allow_single_line_anonymous_functions' => true,
        'allow_single_line_empty_anonymous_classes' => true,
        'anonymous_classes_opening_brace' => 'same_line',
        'anonymous_functions_opening_brace' => 'same_line',
        'classes_opening_brace' => 'next_line_unless_newline_at_signature_end',
        'control_structures_opening_brace' => 'same_line',
        'functions_opening_brace' => 'next_line_unless_newline_at_signature_end',
    ],

    // spacing and line-break hygiene
    'binary_operator_spaces' => [
        'default' => 'single_space',
        'operators' => [
            '=>' => 'single_space',
        ],
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
    'not_operator_with_successor_space' => false,
    'multiline_promoted_properties' => [
        'keep_blank_lines' => true,
    ],
    'multiline_whitespace_before_semicolons' => false,

    // imports
    'no_unused_imports' => true,
    'global_namespace_import' => [
        'import_classes' => false,
        'import_constants' => false,
        'import_functions' => false,
    ],
    'ordered_imports' => [
        'imports_order' => ['class', 'function', 'const'],
        'sort_algorithm' => 'alpha',
    ],

    // attributes
    'attribute_empty_parentheses' => [
        'use_parentheses' => false,
    ],
    'ordered_attributes' => [
        'sort_algorithm' => 'alpha',
    ],

    // arrays and lists
    'array_syntax' => [
        'syntax' => 'short',
    ],
    'list_syntax' => [
        'syntax' => 'short',
    ],

    // strings and concatenation
    'single_quote' => true,
    'concat_space' => [
        'spacing' => 'one',
    ],

    // integers
    'numeric_literal_separator' => [
        'strategy' => 'use_separator',
    ],

    // types
    'return_type_declaration' => [
        'space_before' => 'none',
    ],
    'return_to_yield_from' => true,
    'simplified_null_return' => true,
    'simplified_if_return' => true,

    // element order
    'ordered_class_elements' => [
        'sort_algorithm' => 'alpha',
        'order' => [
            'use_trait',
            'case',
            'constant_public',
            'constant_protected',
            'constant_private',
            'property_public_static',
            'property_protected_static',
            'property_private_static',
            'property_public_readonly',
            'property_protected_readonly',
            'property_private_readonly',
            'property_public',
            'property_protected',
            'property_private',
            'construct',
            'destruct',
            'magic',
            'phpunit',
            'method_public_abstract_static',
            'method_protected_abstract_static',
            'method_public_static',
            'method_protected_static',
            'method_private_static',
            'method_public_abstract',
            'method_protected_abstract',
            'method_public',
            'method_protected',
            'method_private',
        ],
    ],

    // PHPDoc
    'phpdoc_param_order' => true,
    'phpdoc_summary' => true,
    'phpdoc_line_span' => [
        'const' => 'multi',
        'method' => 'multi',
        'property' => 'multi',
    ],
    'phpdoc_align' => [
        'align' => 'left',
    ],
    'phpdoc_order' => [
        // Use recommended order of PHPDoc tags.
        'order' => [
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
    'general_phpdoc_annotation_remove' => [
        // Be careful about this part of the config; it removes the tag and its contents when
        // what you often want to do is remove or modify the tag only and not its contents.
        'annotations' => [
            // Use abstract keyword instead
            'abstract',

            // Use public, protected, or private keyword instead
            'access',

            // Use version history instead
            'author',

            // Use namespaces instead
            'category',

            // Use class keyword instead
            'class',

            // Use @var tag or const keyword instead
            'const',

            // Use constructor keyword instead
            'constructor',

            // Use license file instead
            'copyright',

            // First comment is automatically file comment
            'file',

            // Use final keyword instead
            'final',

            // Use dependency injection instead of globals
            'global',

            // Use @inheritdoc instead
            'inherit',

            // Use license file instead
            'license',

            // Use never return type instead
            'noreturn',

            // Use namespaces instead
            'package',

            // Use @param instead
            'parm',

            // Use private keyword instead
            'private',

            // Use protected keyword instead
            'protected',

            // Use public keyword instead
            'public',

            // Use readonly keyword instead
            'readonly',

            // Use @uses tag instead
            'requires',

            // Use static keyword instead
            'static',

            // Use namespaces instead
            'subpackage',

            // Use type declaration or @var tag instead.
            'type',

            // Use type declaration or @var tag instead.
            'typedef',

            // Use version history instead
            'updated',

            // Use @uses on the other code instead
            'usedby',
        ],
    ],
];

$finder = Finder::create()
    ->in($directories);

return (new Config())
    ->setUsingCache(true)
    ->setCacheFile(__DIR__ . '/var/cache/php-cs-fixer/.php-cs-fixer.cache')
    ->setRiskyAllowed($isRisky)
    ->setRules(array_merge($rulesets, $rules))
    ->setIndent('    ') // PSR-12: 4 spaces
    ->setLineEnding("\n") // PSR-12: LF
    ->setFinder($finder);
