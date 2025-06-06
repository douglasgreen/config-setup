#!/usr/bin/env php
<?php

/*
 * This script exists to copy the config file to your repo as a composer post-install-cmd.
 */

use DouglasGreen\ConfigSetup\FileCopier;

$currentDir = getcwd();
if ($currentDir === false) {
    throw new Exception('Unable to get working dir');
}

// Run in current dir which is repository root dir.
require_once $currentDir . '/vendor/autoload.php';

$options = getopt('w:', ['woocommerce', 'wordpress', 'wrap:']);

$flags = 0;

// Specify a custom line wrap length.
$wrapArg = $options['wrap'] ?? ($options['w'] ?? FileCopier::DEFAULT_WRAP);
$wrap = (int) $wrapArg;
if ($wrap === 0) {
    throw new Exception('Invalid wrap argument');
}

// Install the WordPress stubs for PHPStan.
if (isset($options['wordpress'])) {
    $flags |= FileCopier::USE_WORDPRESS;

    // Install the WooCommerce stubs for PHPStan.
    if (isset($options['woocommerce'])) {
        $flags |= FileCopier::USE_WOOCOMMERCE;
    }
}

$fileCopier = new FileCopier($currentDir, $flags, $wrap);
$fileCopier->copyFiles();
