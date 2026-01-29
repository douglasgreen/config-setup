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
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SymfonySetList;

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
            if (preg_match('#^phpunit/#', (string) $name)) {
                $hasPhpUnit = true;
            }

            if (preg_match('#^symfony/#', (string) $name)) {
                $hasSymfony = true;
            }

            if (preg_match('#^doctrine/#', (string) $name)) {
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
        symfony: $hasSymfony,
        doctrine: $hasDoctrine,
        mongoDb: $hasDoctrine,
        gedmo: $hasDoctrine,
        phpunit: $hasPhpUnit,
        fosRest: $hasSymfony,
        jms: $hasDoctrine,
        sensiolabs: $hasSymfony
    )
    ->withPreparedSets(
        deadCode: true,
        typeDeclarations: true,
    );
