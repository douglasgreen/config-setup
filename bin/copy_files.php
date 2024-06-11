#!/usr/bin/env php
<?php

declare(strict_types=1);

/*
 * This script exists to copy the config file to your repo as a composer post-install-cmd.
 */

use DouglasGreen\ConfigSetup\FileCopier;
use DouglasGreen\OptParser\OptParser;
use DouglasGreen\Utility\FileSystem\Dir;

require_once Dir::getCurrent() . '/vendor/autoload.php';

$optParser = new OptParser(
    'Config File Copier',
    'A program to copy standard config files to your repository'
);

$optParser->addFlag(
    ['pre-push', 'p'],
    'Use the husky pre-push event rather than pre-commit'
)->addUsageAll();

$input = $optParser->parse();

$usePrePush = (bool) $input->get('pre-push');

$fileCopier = new FileCopier($usePrePush);
$fileCopier->copyFiles();
