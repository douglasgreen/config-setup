#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This script exists to copy the config file to your repo as a composer post-install-cmd.
 */

exec('git ls-files', $gitFiles, $rc);

if ($rc !== 0) {
    echo "Failed to get the list of git files.\n";
    exit(1);
}

$filesToCopy = [
    '.eslintignore',
    '.eslintrc.json',
    '.husky/commit-msg',
    '.husky/post-checkout',
    '.husky/post-merge',
    '.husky/pre-commit',
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
    'run_phpmd.sh',
    'run_phpstan.sh',
    'script/bootstrap',
    'script/lint',
    'script/setup',
    'script/test',
];

$gitFiles = array_flip($gitFiles);

foreach ($filesToCopy as $file) {
    if (! isset($gitFiles[$file])) {
        $source = __DIR__ . '/vendor/douglasgreen/config-setup/' . $file;
        $destination = __DIR__ . '/' . $file;

        $destinationDir = dirname($destination);
        if (! is_dir($destinationDir)) {
            mkdir($destinationDir, 0o777, true);
        }

        if (! copy($source, $destination)) {
            echo "Failed to copy {$source} to {$destination}.\n";
        } else {
            echo "Copied {$source} to {$destination}.\n";
        }
    }
}

// Find top-level directories containing PHP files
$phpDirectories = [];

foreach (array_keys($gitFiles) as $file) {
    if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
        $topLevelDir = explode('/', $file)[0];
        var_dump('tld', $topLevelDir);
        $phpDirectories[$topLevelDir] = true;
    }
}

$phpDirectories = array_keys($phpDirectories);
sort($phpDirectories);

// Write the list of directories to php_paths file
file_put_contents(
    __DIR__ . '/php_paths',
    implode(PHP_EOL, $phpDirectories) . PHP_EOL
);

echo "php_paths file has been created.\n";
