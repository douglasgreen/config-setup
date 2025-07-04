<?php

/**
 * Rector configuration file.
 *
 * This file is used to configure the Rector PHP code quality tool.
 *
 * Usage:
 * - The file paths for PHP files to analyze come from a file named 'php_paths'
 *   in the top-level directory of the repository. This file should contain PHP
 *   files in the top-level directory as well as directories that contain PHP
 *   files. It is shared with other PHP linting utilities so they can all lint the
 *   same file list.
 * - The presence of PHPUnit, Symfony, and Doctrine in the composer.json file is
 *   automatically detected, and the relevant Rector rule sets are enabled or
 *   disabled accordingly based on the $hasPhpUnit, $hasSymfony, and $hasDoctrine
 *   variables.
 * - The PHP version in composer.json is detected and set as $upToPhp for
 *   upgrades.
 *
 * For more information on configuring Rector, see https://getrector.com/
 */

use Rector\Caching\ValueObject\Storage\FileCacheStorage;
use Rector\Config\RectorConfig;
// Rector set lists
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;
// Rector rules
use Rector\CodeQuality\Rector\ClassMethod\LocallyCalledStaticMethodToNonStaticRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\Naming\Rector\Assign\RenameVariableToMatchMethodCallReturnTypeRector;
use Rector\Naming\Rector\ClassMethod\RenameVariableToMatchNewTypeRector;
use Rector\Naming\Rector\Foreach_\RenameForeachValueVariableToMatchExprVariableRector;
use Rector\Strict\Rector\Empty_\DisallowedEmptyRuleFixerRector;

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
            if (preg_match('#^phpunit/#', $name) === 1) {
                $hasPhpUnit = true;
            }

            if (preg_match('#^symfony/#', $name) === 1) {
                $hasSymfony = true;
            }

            if (preg_match('#^doctrine/#', $name) === 1) {
                $hasDoctrine = true;
            }

            if ($name !== 'php') {
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            if (preg_match('/\d+\.\d+/', $value, $match) !== 1) {
                continue;
            }

            $phpVersion = $match[0];
        }
    }
}

$upToPhp = match ($phpVersion) {
    '8.2' => LevelSetList::UP_TO_PHP_82,
    '8.3' => LevelSetList::UP_TO_PHP_83,
    default => LevelSetList::UP_TO_PHP_81,
};

$baseSets = [];

if ($hasPhpUnit) {
    $baseSets[] = PHPUnitSetList::PHPUNIT_100;
    $baseSets[] = PHPUnitSetList::PHPUNIT_CODE_QUALITY;
}

if ($hasSymfony) {
    $baseSets[] = SymfonySetList::SYMFONY_64;
    $baseSets[] = SymfonySetList::SYMFONY_CODE_QUALITY;
    $baseSets[] = SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION;
}

if ($hasDoctrine) {
    $baseSets[] = DoctrineSetList::DOCTRINE_CODE_QUALITY;
}

$paths = file('php_paths');
if ($paths === false) {
    exit('PHP paths not found' . PHP_EOL);
}

$paths = array_map(trim(...), $paths);

return RectorConfig::configure()
    ->withoutParallel()
    ->withCache(cacheDirectory: 'var/cache/rector', cacheClass: FileCacheStorage::class)
    ->withPaths($paths)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withPhpSets()
    ->withSets($baseSets)
    ->withAttributesSets(
        doctrine: $hasDoctrine,
        fosRest: $hasSymfony,
        gedmo: $hasDoctrine,
        jms: $hasDoctrine,
        mongoDb: $hasDoctrine,
        phpunit: $hasPhpUnit,
        sensiolabs: $hasSymfony,
        symfony: $hasSymfony
    )
    ->withPreparedSets(
        codeQuality: true,
        codingStyle: true,
        deadCode: true,
        earlyReturn: true,
        instanceOf: true,
        naming: true,
        privatization: true,
        typeDeclarations: true
    )
    ->withSkip([
        DisallowedEmptyRuleFixerRector::class,
        EncapsedStringsToSprintfRector::class,
        ExplicitBoolCompareRector::class,
        LocallyCalledStaticMethodToNonStaticRector::class,
        RenameForeachValueVariableToMatchExprVariableRector::class,
        RenameVariableToMatchMethodCallReturnTypeRector::class,
        RenameVariableToMatchNewTypeRector::class,
    ]);
