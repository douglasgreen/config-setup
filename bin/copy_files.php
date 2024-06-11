#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This script exists to copy the config file to your repo as a composer post-install-cmd.
 */

use DouglasGreen\ConfigSetup\FileCopier;

$dir = getcwd();
if ($dir === false) {
    throw new Exception('Unable to get working dir');
}

// Run in current dir which is repository root dir.
require_once $dir . '/vendor/autoload.php';

$options = getopt('ap', ['airbnb', 'pre-push']);

$flags = 0;

// Use airbnb instead of eslint-config-standard for eslint rules.
if (isset($options['airbnb']) && isset($options['a'])) {
    $flags |= FileCopier::AIRBNB;
}

// Use pre-push event instead of pre-commit for fewer interruptions.
if (isset($options['pre-push']) && isset($options['p'])) {
    $flags |= FileCopier::PRE_PUSH;
}

$fileCopier = new FileCopier($dir, $flags);
$fileCopier->copyFiles();
