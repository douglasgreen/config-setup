#!/usr/bin/env php
<?php

// Define the path to the composer.json file
$composerJsonPath = 'composer.json';

// Check if the file exists
if (! file_exists($composerJsonPath)) {
    die('Error: composer.json file not found.');
}

// Read the content of the composer.json file
$composerJsonContent = file_get_contents($composerJsonPath);
if ($composerJsonContent === false) {
    die("Unable to read composer.json file: {$composerJsonPath}\n");
}

// Decode the JSON content into a PHP array
$composerData = json_decode($composerJsonContent, true);

// Check if the JSON decoding was successful
if (json_last_error() !== JSON_ERROR_NONE) {
    die('Error: Failed to decode JSON. ' . json_last_error_msg());
}

$projectName = $composerData['name'];

$envPackages = ['ext-bcmath', 'ext-json', 'ext-mbstring', 'ext-pdo', 'ext-simplexml', 'php'];

$packagesRequired = [];
if (isset($composerData['require']) && is_array($composerData['require'])) {
    $requirements = array_keys($composerData['require']);
    foreach ($requirements as $requirement) {
        if (! in_array($requirement, $envPackages, true)) {
            $packagesRequired[] = $requirement;
        }
    }
}

if (! $packagesRequired) {
    die('No package requirements found');
}

$composerPaths = [];
exec('find vendor/ -maxdepth 3 -name composer.json -print', $composerPaths);
if ($composerPaths === []) {
    die('Vendor files not found' . PHP_EOL);
}

$projectNamespaces = [];
foreach ($composerPaths as $composerJsonPath) {
    // Read the content of the composer.json file
    $composerJsonContent = file_get_contents($composerJsonPath);
    if ($composerJsonContent === false) {
        die("Unable to read composer.json file: {$composerJsonPath}\n");
    }

    // Decode the JSON content into a PHP array
    $composerData = json_decode($composerJsonContent, true);

    // Check if the JSON decoding was successful
    if (json_last_error() !== JSON_ERROR_NONE) {
        die('Error: Failed to decode JSON. ' . json_last_error_msg());
    }

    // Check if the autoload and psr-4 keys exist
    if (isset($composerData['autoload']['psr-4']) && is_array($composerData['autoload']['psr-4'])) {
        $projectName = $composerData['name'];

        // Extract the namespaces
        $projectNamespaces[$projectName] = array_keys($composerData['autoload']['psr-4']);
    }
}

// Run the git ls-files command
$files = shell_exec('git ls-files');
$fileList = [];
if ($files) {
    $fileList = explode("\n", trim($files));
}

$phpFiles = [];

// Filter files with .php extension or hashbang line containing php
foreach ($fileList as $file) {
    if (preg_match('/\.php$/', $file)) {
        $phpFiles[] = $file;
    } else {
        $handle = fopen($file, 'r');
        if (! $handle) {
            die("Unable to open file: {$file}\n");
        }

        $firstLine = fgets($handle);
        if ($firstLine === false) {
            continue;
        }

        if (str_contains($firstLine, 'php')) {
            $phpFiles[] = $file;
        }
    }
}

$useStatements = [];

// Load each file and find use statements
foreach ($phpFiles as $file) {
    $content = file_get_contents($file);
    if ($content === false) {
        continue;
    }

    // Match use statements
    preg_match_all('/^use\s+([^;]+);/m', $content, $matches);
    foreach ($matches[1] as $match) {
        $match = trim($match);

        // Skip global names.
        if (preg_match('/^\w+$/', $match)) {
            continue;
        }

        $match = preg_replace('/^function\s+/', '', $match);
        $match = preg_replace('#^\\\\#', '', (string) $match);
        $match = preg_replace('#\\\\$#', '', (string) $match);
        $match = preg_replace('#\\\\+#', '\\', (string) $match);

        // Store all possible base paths.
        do {
            $newMatch = preg_replace('#\\\\\w+$#', '', (string) $match);
            $useStatements[$newMatch . '\\'] = true;
            $modified = $match !== $newMatch;
            $match = $newMatch;
        } while ($modified);
    }
}

ksort($projectNamespaces);
$allFound = true;
foreach ($projectNamespaces as $projectName => $namespaces) {
    if (! in_array($projectName, $packagesRequired, true)) {
        continue;
    }

    $found = false;
    foreach ($namespaces as $namespace) {
        if (isset($useStatements[$namespace])) {
            $found = true;
            break;
        }
    }

    if (! $found) {
        echo 'Composer namespaces are not used: ' . $projectName . PHP_EOL;
        $allFound = false;
    }
}

if ($allFound) {
    echo 'All composer namespaces are used' . PHP_EOL;
}
