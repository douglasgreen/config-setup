#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This script exists to copy the config file to your repo as a composer post-install-cmd.
 */

exec('git ls-files', $gitFiles, $rc);

if ($rc !== 0) {
    echo 'Failed to get the list of git files.' . PHP_EOL;
    exit(1);
}

$filesToCopy = [
    '.eslintignore',
    '.eslintrc.json',
    '.prettierignore',
    '.prettierrc.json',
    '.stylelintignore',
    '.stylelintrc.json',
    'commitlint.config.js',
    'docs/setup_guide.md',
    'ecs.php',
    'phpmd.xml',
    'phpstan.neon',
    'phpunit.xml',
    'rector.php',
];

$scriptsToCopy = [
    '.husky/commit-msg',
    '.husky/post-checkout',
    '.husky/post-merge',
    '.husky/pre-commit',
    'run_phpmd.sh',
    'run_phpstan.sh',
    'script/bootstrap',
    'script/lint',
    'script/setup',
    'script/test',
];

$filesToCopy = array_merge($filesToCopy, $scriptsToCopy);

$gitFiles = array_flip($gitFiles);

$dir = getcwd();

// Add to .git/info/exclude to ignore without modifying .gitignore.
$excludeFile = $dir . '/.git/info/exclude';
$excludeLines = [];
if (file_exists($excludeFile)) {
    $result = file($excludeFile);
    if ($result !== false) {
        $excludeLines = $result;
    }
}

$oldExcludeLines = $excludeLines;

foreach ($filesToCopy as $file) {
    // Don't overwrite Git files in the repo.
    if (isset($gitFiles[$file])) {
        continue;
    }

    $source = $dir . '/vendor/douglasgreen/config-setup/' . $file;
    $destination = $dir . '/' . $file;

    $destinationDir = dirname($destination);
    if (! is_dir($destinationDir)) {
        mkdir($destinationDir, 0o777, true);
    }

    if (! in_array($destination, $excludeLines, true)) {
        $excludeLines[] = $destination . PHP_EOL;
    }

    // Skip copying of identical files.
    if (file_exists($destination) && md5_file($source) === md5_file(
        $destination
    )) {
        continue;
    }

    if (! copy($source, $destination)) {
        echo sprintf(
            'Failed to copy %s to %s.',
            $source,
            $destination
        ) . PHP_EOL;
    } else {
        echo sprintf('Copied %s to %s.', $source, $destination) . PHP_EOL;
        if (in_array($destination, $scriptsToCopy, true)) {
            chmod($destination, 0o755);
        }
    }
}

// Find top-level directories containing PHP files
$phpDirectories = [];

foreach (array_keys($gitFiles) as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $topLevelDir = explode('/', $file)[0];
        $phpDirectories[$topLevelDir] = true;
    }
}

$phpDirectories = array_keys($phpDirectories);
sort($phpDirectories);

$pathFile = $dir . '/php_paths';
$oldPaths = file_exists($pathFile) ? file_get_contents($pathFile) : '';
$newPaths = implode(PHP_EOL, $phpDirectories) . PHP_EOL;

// Write the list of directories to php_paths file
if ($oldPaths !== $newPaths) {
    file_put_contents($pathFile, $newPaths);
    echo 'php_paths file has been created.' . PHP_EOL;
}

if ($excludeLines !== $oldExcludeLines) {
    file_put_contents($excludeFile, implode('', $excludeLines));
    echo $excludeFile . ' has been updated.' . PHP_EOL;
}
