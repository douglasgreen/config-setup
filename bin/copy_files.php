#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This script exists to copy the config file to your repo as a composer post-install-cmd.
 *
 * Note: Don't use the optparser or utility dependencies here because of conflicts.
 */

use DouglasGreen\ConfigSetup\FileCopier;

$dir = getcwd();
if ($dir === false) {
    throw new Exception('Unable to get working dir');
}

// Run in current dir which is repository root dir.
require_once $dir . '/vendor/autoload.php';

$options = getopt('cjpw:', ['cobertura', 'junit', 'pre-push', 'wrap:']);

$flags = 0;

// Use Cobertura for report format.
if (isset($options['cobertura']) || isset($options['c'])) {
    $flags |= FileCopier::COBERTURA;
}

// Use Junit for logging format.
if (isset($options['junit']) || isset($options['j'])) {
    $flags |= FileCopier::JUNIT;
}

// Use pre-push event instead of pre-commit for fewer interruptions.
if (isset($options['pre-push']) || isset($options['p'])) {
    $flags |= FileCopier::PRE_PUSH;
}

$wrapArg = $options['wrap'] ?? ($options['w'] ?? FileCopier::DEFAULT_WRAP);
$wrap = (int) $wrapArg;
if ($wrap === 0) {
    throw new Exception('Invalid wrap argument');
}

$fileCopier = new FileCopier($dir, $flags, $wrap);
$fileCopier->copyFiles();
