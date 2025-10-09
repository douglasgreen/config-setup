<?php

/**
 * Easy Coding Standard (ECS) configuration file.
 *
 * This file is used to configure the ECS PHP code style and quality tool.
 *
 * It's configured to be compatible with the use of PHP Coding Standards Fixer as a separate tool.
 *
 * Usage:
 * - The file paths for PHP files to analyze come from a file named 'php_paths' in the top-level
 * directory of the repository. This file should contain PHP files in the top-level directory as
 * well as directories that contain PHP files. It is shared with other PHP linting utilities so they
 * can all lint the same file list.
 *
 * For more information on configuring ECS, see
 * https://github.com/easy-coding-standard/easy-coding-standard.
 */

/*
 * The Symplify statements must precede the PhpCsFixer statements or PHPStan reports namespace
 * errors.
 */
use Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;
use PhpCsFixer\Fixer\Basic\BracesPositionFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassDefinitionFixer;
use PhpCsFixer\Fixer\ClassNotation\OrderedClassElementsFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionDeclarationFixer;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;

return function (ECSConfig $ecsConfig): void {
    // Dynamically determine paths from php_paths file
    $paths = file('php_paths', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($paths === false) {
        exit('PHP paths not found' . PHP_EOL);
    }

    $ecsConfig->paths($paths);

    // --- SECTIONS FOR RULE SETS ---
    $ecsConfig->sets([SetList::COMMON, SetList::PSR_12, SetList::STRICT, SetList::SYMPLIFY]);

    // --- CONFIGURE INDIVIDUAL RULES ---
    $ecsConfig->ruleWithConfiguration(ClassDefinitionFixer::class, [
        'single_line' => true,
    ]);

    $ecsConfig->ruleWithConfiguration(FunctionDeclarationFixer::class, [
        'closure_fn_spacing' => 'none',
    ]);

    $ecsConfig->ruleWithConfiguration(LineLengthFixer::class, [
        'line_length' => 100,
    ]);

    // --- SKIP RULES ---
    $ecsConfig->skip([
        // Allow one-line arrays.
        ArrayOpenerAndCloserNewlineFixer::class,

        // Don't fix braces because it's handled by PHP CS Fixer.
        BracesPositionFixer::class,

        // Do not enforce the declaration of strict types (`declare(strict_types=1);`).
        DeclareStrictTypesFixer::class,

        // Don't remove PHPDoc annotations because it's handled by PHP CS Fixer.
        GeneralPhpdocAnnotationRemoveFixer::class,

        // Do not automatically import global classes, functions, or constants with `use` statements.
        GlobalNamespaceImportFixer::class,

        // Do not add space after not operator.
        NotOperatorWithSuccessorSpaceFixer::class,

        // Don't order class elements because it's handled by PHP CS Fixer.
        OrderedClassElementsFixer::class,

        // Do not enforce a specific order for `use` statements because it's handled by PHP CS Fixer.
        OrderedImportsFixer::class,

        // Do not enforce vertical alignment of annotations in PHPDoc blocks.
        PhpdocAlignFixer::class,
    ]);

    // --- CACHE AND PARALLELIZATION ---
    $ecsConfig->cacheDirectory('var/cache/ecs');
    $ecsConfig->parallel();
};
