#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace DouglasGreen\ConfigSetup;

use DouglasGreen\OptParser\OptParser;
use DouglasGreen\Utility\FileSystem\Dir;
use DouglasGreen\Utility\FileSystem\Path;
use DouglasGreen\Utility\Program\Command;

class FileCopier
{
    /**
     * @var string
     */
    public $excludeFile;

    protected const array FILES_TO_COPY = [
        '.eslintignore',
        '.eslintrc.json',
        '.prettierignore',
        '.prettierrc.json',
        '.stylelintignore',
        '.stylelintrc.json',
        'commitlint.config.js',
        'ecs.php',
        'phpmd.xml',
        'phpstan.neon',
        'phpunit.xml',
        'rector.php',
    ];

    protected const array SCRIPTS_TO_COPY = [
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

    /**
     * @var string Add to .git/info/exclude to ignore without modifying .gitignore.
     */
    public const string GIT_EXCLUDE_FILE = '.git/info/exclude';

    /**
     * @var list<string>
     */
    protected array $filesToCopy;

    /**
     * @var array<string, int>
     */
    protected array $gitFiles;

    protected string $repoDir;

    protected bool $usePrePush;

    public function __construct(
    ) {
        $command = new Command('git');
        $gitFiles = $command->addArg('ls-files')
            ->exec();
        $this->gitFiles = array_flip($gitFiles);

        $this->repoDir = Dir::getCurrent();

        $this->filesToCopy = array_merge(
            self::FILES_TO_COPY,
            self::SCRIPTS_TO_COPY
        );

        $this->excludeFile = $this->repoDir . '/' . self::GIT_EXCLUDE_FILE;
    }

    public function copyFiles(): void
    {
        $excludeLines = [];
        $excludePath = new Path($this->excludeFile);
        if ($excludePath->exists()) {
            $excludeLines = $excludePath->loadLines();
        }

        $oldExcludeLines = $excludeLines;

        foreach ($this->filesToCopy as $file) {
            if ($this->usePrePush && $file === '.husky/pre-commit') {
                $file = '.husky/pre-push';
            }

            // Don't overwrite Git files in the repo.
            if (isset($this->gitFiles[$file])) {
                continue;
            }

            $source = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $file;
            $destination = $this->repoDir . '/' . $file;

            $destinationDir = dirname($destination);
            if (! is_dir($destinationDir)) {
                $dir = new Dir($destinationDir);
                $dir->make(0o777, Dir::RECURSIVE);
            }

            if (! in_array($file, $excludeLines, true)) {
                $excludeLines[] = $file . PHP_EOL;
            }

            // Skip copying of identical files.
            $sourcePath = new Path($source);
            $destPath = new Path($destination);
            if (
                $destPath->exists() &&
                $sourcePath->md5() === $destPath->md5()
            ) {
                continue;
            }

            $destPath = $sourcePath->copy($destination);
            echo sprintf(
                'Copied %s to %s.',
                $source,
                $destination
            ) . PHP_EOL;
            $mode = in_array(
                $file,
                self::SCRIPTS_TO_COPY,
                true
            ) ? 0o755 : 0o644;
            $destPath->changeMode($mode);
        }

        // Find top-level directories containing PHP files
        $phpDirectories = [];

        foreach (array_keys($this->gitFiles) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $topLevelDir = explode('/', $file)[0];
                $phpDirectories[$topLevelDir] = true;
            }
        }

        $phpDirectories = array_keys($phpDirectories);
        sort($phpDirectories);

        $pathFilename = $this->repoDir . '/php_paths';
        $pathFile = new Path($pathFilename);
        $oldPaths = $pathFile->exists() ? $pathFile->loadString() : '';
        $newPaths = implode(PHP_EOL, $phpDirectories) . PHP_EOL;

        // Write the list of directories to php_paths file
        if ($oldPaths !== $newPaths) {
            $pathFile->saveString($newPaths);
            echo 'php_paths file has been created.' . PHP_EOL;
        }

        if ($excludeLines !== $oldExcludeLines) {
            $excludePath->saveString(implode('', $excludeLines));
            echo $this->excludeFile . ' has been updated.' . PHP_EOL;
        }
    }

    protected function setArgs(): void
    {
        $optParser = new OptParser(
            'Config File Copier',
            'A program to copy standard config files to your repository'
        );

        $optParser->addFlag(
            ['pre-push', 'p'],
            'Use the husky pre-push event rather than pre-commit'
        )->addUsageAll();

        $input = $optParser->parse();

        $this->usePrePush = (bool) $input->get('pre-push');
    }
}
