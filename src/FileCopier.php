#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace DouglasGreen\ConfigSetup;

use Exception;

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

    public function __construct(
        protected string $repoDir,
        protected bool $usePrePush
    ) {
        exec('git ls-files', $output);
        $this->gitFiles = array_flip($output);

        $this->filesToCopy = array_merge(
            self::FILES_TO_COPY,
            self::SCRIPTS_TO_COPY
        );

        $this->excludeFile = $this->repoDir . '/' . self::GIT_EXCLUDE_FILE;
    }

    /**
     * @throws Exception
     */
    public function copyFiles(): void
    {
        $excludeLines = [];
        if (file_exists($this->excludeFile)) {
            $excludeLines = file($this->excludeFile);
            if ($excludeLines === false) {
                throw new Exception('Unable to load Git exclude file');
            }
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
                mkdir($destinationDir, 0o777, true);
            }

            if (! in_array($file, $excludeLines, true)) {
                $excludeLines[] = $file . PHP_EOL;
            }

            // Skip copying of identical files.
            if (file_exists($destination) && md5_file($source) === md5_file(
                $destination
            )) {
                continue;
            }

            if (! copy($source, $destination)) {
                throw new Exception(sprintf(
                    'Failed to copy %s to %s.',
                    $source,
                    $destination
                ));
            }

            echo sprintf(
                'Copied %s to %s.',
                $source,
                $destination
            ) . PHP_EOL;
            if (in_array($file, self::SCRIPTS_TO_COPY, true)) {
                chmod($destination, 0o755);
            } else {
                chmod($destination, 0o644);
            }
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

        $pathFile = $this->repoDir . '/php_paths';
        $oldPaths = file_exists($pathFile) ? file_get_contents($pathFile) : '';
        $newPaths = implode(PHP_EOL, $phpDirectories) . PHP_EOL;

        // Write the list of directories to php_paths file
        if ($oldPaths !== $newPaths) {
            file_put_contents($pathFile, $newPaths);
            echo 'php_paths file has been created.' . PHP_EOL;
        }

        if ($excludeLines !== $oldExcludeLines) {
            file_put_contents($this->excludeFile, implode('', $excludeLines));
            echo $this->excludeFile . ' has been updated.' . PHP_EOL;
        }
    }
}
