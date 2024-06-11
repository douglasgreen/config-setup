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

$fileCopier = new FileCopier();
$fileCopier->copyFiles();
