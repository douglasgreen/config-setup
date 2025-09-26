<?php

/**
 * Easy Coding Standard (ECS) configuration file.
 *
 * This file is used to configure the ECS PHP code style and quality tool.
 *
 * Usage:
 * - To perform risky changes, set the environment variable ECS_RISKY to true.
 *   This should be carefully reviewed to ensure it doesn't break anything.
 * - The file paths for PHP files to analyze come from a file named 'php_paths'
 *   in the top-level directory of the repository. This file should contain PHP
 *   files in the top-level directory as well as directories that contain PHP
 *   files. It is shared with other PHP linting utilities so they can all lint the
 *   same file list.
 * - The presence of PHPUnit, Symfony, and Doctrine in the composer.json file is
 *   automatically detected, and the relevant ECS rule sets are enabled or
 *   disabled accordingly based on the $hasPhpUnit, $hasSymfony, and $hasDoctrine
 *   variables.
 * - The PHP version in composer.json is detected and set as $phpVersion.
 * - Be cautious when configuring the list of annotations to remove using the
 *   GeneralPhpdocAnnotationRemoveFixer.  ECS removes both the tag and its
 *   contents, whereas in many cases, you may only want to remove or modify the
 *   tag itself without affecting its contents.
 *
 * For more information on configuring ECS, see
 * https://github.com/easy-coding-standard/easy-coding-standard.
 */

/*
 * The Symplify statements must precede the PhpCsFixer statements or PHPStan reports namespace errors.
 */
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;
use PhpCsFixer\Fixer\Import\GlobalNamespaceImportFixer;
use PhpCsFixer\Fixer\Import\OrderedImportsFixer;
use PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer;
use PhpCsFixer\Fixer\Phpdoc\GeneralPhpdocAnnotationRemoveFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocAlignFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocLineSpanFixer;
use PhpCsFixer\Fixer\Strict\DeclareStrictTypesFixer;

return function (ECSConfig $ecsConfig): void {
    // Dynamically determine paths from php_paths file
    $paths = file('php_paths', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($paths === false) {
        exit('PHP paths not found' . PHP_EOL);
    }

    $ecsConfig->paths($paths);

    // Get composer dependencies
    $hasPhpUnit = false;
    $hasSymfony = false;
    $hasDoctrine = false;
    $phpVersion = null;

    if (file_exists('composer.json')) {
        $composerContent = file_get_contents('composer.json');
        if ($composerContent !== false) {
            $composerData = json_decode($composerContent, true, 16, JSON_THROW_ON_ERROR);

            // Check for PHPUnit, Symfony, and Doctrine
            $requires = $composerData['require'] ?? [];
            $requiresDev = $composerData['require-dev'] ?? [];

            $allDependencies = array_merge($requires, $requiresDev);

            foreach ($allDependencies as $name => $value) {
                if (preg_match('#^phpunit/#', $name)) {
                    $hasPhpUnit = true;
                }

                if (preg_match('#^symfony/#', $name)) {
                    $hasSymfony = true;
                }

                if (preg_match('#^doctrine/#', $name)) {
                    $hasDoctrine = true;
                }

                if ($name !== 'php') {
                    continue;
                }

                if (!preg_match('/\d+\.\d+/', (string) $value, $match)) {
                    continue;
                }

                $phpVersion = $match[0];
            }
        }
    }

    // Enable risky rules via environment variable
    $isRisky = (bool) getenv('ECS_RISKY');

    // --- SECTIONS FOR RULE SETS ---
    $ecsConfig->sets([SetList::COMMON, SetList::PSR_12, SetList::STRICT, SetList::SYMPLIFY]);

    if ($hasDoctrine) {
        $ecsConfig->dynamicSets(['@DoctrineAnnotation']);
    }

    if ($hasSymfony) {
        $ecsConfig->dynamicSets(['@Symfony']);
        if ($isRisky) {
            $ecsConfig->dynamicSets(['@Symfony:risky']);
        }
    }

    // --- PHP MIGRATION SETS ---
    if ($phpVersion) {
        if ($phpVersion >= 8.3) {
            $ecsConfig->dynamicSets(['@PHP83Migration']);
            if ($isRisky) {
                $ecsConfig->dynamicSets(['@PHP83Migration:risky']);
            }
        } elseif ($phpVersion >= 8.2) {
            $ecsConfig->dynamicSets(['@PHP82Migration']);
            if ($isRisky) {
                $ecsConfig->dynamicSets(['@PHP82Migration:risky']);
            }
        } elseif ($phpVersion >= 8.1) {
            $ecsConfig->dynamicSets(['@PHP81Migration']);
            if ($isRisky) {
                $ecsConfig->dynamicSets(['@PHP81Migration:risky']);
            }
        }

        if ($isRisky && $hasPhpUnit) {
            $ecsConfig->dynamicSets(['@PHPUnit100Migration:risky']);
        }
    }


    // --- CONFIGURE INDIVIDUAL RULES ---
    $ecsConfig->ruleWithConfiguration(LineLengthFixer::class, [
        'line_length' => 100,
    ]);

    $ecsConfig->ruleWithConfiguration(PhpdocLineSpanFixer::class, [
        'const' => 'single',
        'property' => 'single',
    ]);

    // Be careful about this part of the config. ECS removes the tag and its
    // contents when what you often want to do is remove or modify the tag
    // only and not its contents.
    $ecsConfig->ruleWithConfiguration(GeneralPhpdocAnnotationRemoveFixer::class, [
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
    ]);

    // --- SKIP RULES ---
    $ecsConfig->skip([
        // Do not enforce the declaration of strict types (`declare(strict_types=1);`).
        DeclareStrictTypesFixer::class,

        // Do not automatically import global classes, functions, or constants with `use` statements.
        GlobalNamespaceImportFixer::class,

        // Do not add space after not operator.
        NotOperatorWithSuccessorSpaceFixer::class,

        // Do not enforce a specific order for `use` statements in the ecs.php file itself.
        OrderedImportsFixer::class => [__DIR__ . '/ecs.php'],

        // Do not enforce vertical alignment of annotations in PHPDoc blocks.
        PhpdocAlignFixer::class,
    ]);

    // --- CACHE AND PARALLELIZATION ---
    $ecsConfig->cacheDirectory('var/cache/ecs');
    $ecsConfig->parallel();
};
