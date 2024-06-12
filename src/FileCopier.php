#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace DouglasGreen\ConfigSetup;

use Exception;

class FileCopier
{
    public const DEFAULT_WRAP = 80;

    public const PRE_PUSH = 1;

    /**
     * @var string
     */
    public $excludeFile;

    protected const FILES_TO_COPY = [
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

    protected const SCRIPTS_TO_COPY = [
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
    public const GIT_EXCLUDE_FILE = '.git/info/exclude';

    /**
     * @var array<string, mixed>
     */
    protected array $composerJson;

    /**
     * @var list<string>
     */
    protected array $filesToCopy;

    /**
     * @var list<string>
     */
    protected array $gitFiles;

    /**
     * @var list<string>
     */
    protected array $npmPackages = [];

    /**
     * @var array<string, mixed>
     */
    protected array $packageJson;

    protected string $phpVersion;

    protected bool $usePrePush;

    /**
     * @throws Exception
     */
    public function __construct(
        protected string $repoDir,
        protected int $flags = 0,
        protected int $wrap = self::DEFAULT_WRAP
    ) {
        $this->usePrePush = (bool) ($this->flags & self::PRE_PUSH);

        $this->loadGitFiles();
        $this->loadComposerJson();
        $this->loadPackageJson();

        $this->filesToCopy = array_merge(self::FILES_TO_COPY, self::SCRIPTS_TO_COPY);

        $this->excludeFile = $this->repoDir . '/' . self::GIT_EXCLUDE_FILE;

        $this->setNpmPackages();
        $this->setPhpVersion();
    }

    /**
     * @throws Exception
     */
    public function copyFiles(): void
    {
        $excludeLines = [];
        if (file_exists($this->excludeFile)) {
            $excludeLines = file($this->excludeFile, FILE_IGNORE_NEW_LINES);
            if ($excludeLines === false) {
                throw new Exception('Unable to load Git exclude file');
            }
        }

        $oldExcludeLines = $excludeLines;

        $gitFiles = array_flip($this->gitFiles);
        foreach ($this->filesToCopy as $fileToCopy) {
            // Don't overwrite Git files in the repo.
            if (isset($gitFiles[$fileToCopy])) {
                continue;
            }

            if ($fileToCopy === 'ecs.php') {
                // Put temporary copy with correct "line_length" value in var dir.
                $plainFile = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
                $source = $this->repoDir . '/vendor/douglasgreen/config-setup/var/' . $fileToCopy;

                $this->makeEcs($plainFile, $source);
            } elseif ($fileToCopy === '.eslintrc.json') {
                // Put temporary copy with correct "extends" value in var dir.
                $plainFile = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
                $source = $this->repoDir . '/vendor/douglasgreen/config-setup/var/' . $fileToCopy;

                $this->makeEslintrc($plainFile, $source);
            } elseif ($fileToCopy === 'phpstan.neon') {
                // Put PHPStan temporary copy with PHP version in var dir.
                $plainFile = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
                $source = $this->repoDir . '/vendor/douglasgreen/config-setup/var/' . $fileToCopy;

                $this->makePhpStan($plainFile, $source);
            } elseif ($fileToCopy === '.prettierrc.json') {
                // Put Prettier temporary copy with new plugin list in var dir.
                $plainFile = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
                $source = $this->repoDir . '/vendor/douglasgreen/config-setup/var/' . $fileToCopy;

                $this->makePrettierrc($plainFile, $source);
            } else {
                $source = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
            }

            // Overwrite target but not source file to copy to different name.
            if ($this->usePrePush && $fileToCopy === '.husky/pre-commit') {
                echo 'Using .husky/pre-push hook instead of .husky/pre-commit.' . PHP_EOL;
                $fileToCopy = '.husky/pre-push';
            }

            $destination = $this->repoDir . '/' . $fileToCopy;

            $destinationDir = dirname($destination);
            if (! is_dir($destinationDir)) {
                mkdir($destinationDir, 0o777, true);
            }

            if (! in_array($fileToCopy, $excludeLines, true)) {
                $excludeLines[] = $fileToCopy;
            }

            // Skip copying of identical files.
            if (file_exists($destination) && md5_file($source) === md5_file($destination)) {
                continue;
            }

            if (! copy($source, $destination)) {
                throw new Exception(sprintf('Failed to copy %s to %s.', $source, $destination));
            }

            echo sprintf('Copied %s to %s.', $source, $destination) . PHP_EOL;
            if (in_array($fileToCopy, self::SCRIPTS_TO_COPY, true)) {
                chmod($destination, 0o755);
            } else {
                chmod($destination, 0o644);
            }
        }

        $this->updatePhpPaths();

        if ($excludeLines !== $oldExcludeLines) {
            file_put_contents($this->excludeFile, implode(PHP_EOL, $excludeLines) . PHP_EOL);
            echo $this->excludeFile . ' has been updated.' . PHP_EOL;
        }
    }

    /**
     * @throws Exception
     */
    protected function loadComposerJson(): void
    {
        $composerJsonString = file_get_contents('composer.json');
        if ($composerJsonString === false) {
            throw new Exception('Unable to read composer.json file.');
        }

        $this->composerJson = json_decode($composerJsonString, true, 16, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws Exception
     */
    protected function loadGitFiles(): void
    {
        exec('git ls-files', $output);
        $this->gitFiles = $output;
    }

    /**
     * @throws Exception
     */
    protected function loadPackageJson(): void
    {
        $packageJsonString = file_get_contents('package.json');
        if ($packageJsonString === false) {
            return;
        }

        $this->packageJson = json_decode($packageJsonString, true, 16, JSON_THROW_ON_ERROR);
    }

    /**
     * @throws Exception
     */
    protected function makeEcs(string $source, string $destination): void
    {
        $lines = file($source);
        if ($lines === false) {
            throw new Exception('Unable to load ECS config');
        }

        $newLines = [];
        foreach ($lines as $line) {
            if (str_contains($line, 'line_length')) {
                $line = preg_replace('/\b100\b/', (string) $this->wrap, $line);
                if ($line === null) {
                    throw new Exception('Unable to replace line wrap');
                }
            }

            $newLines[] = $line;
        }

        $newString = implode('', $newLines);
        $result = file_put_contents($destination, $newString);
        if ($result === false) {
            throw new Exception('Unable to save ECS config to var dir');
        }
    }

    /**
     * @throws Exception
     */
    protected function makeEslintrc(string $source, string $destination): void
    {
        $eslintJsonString = file_get_contents($source);
        if ($eslintJsonString === false) {
            throw new Exception('Unable to load Eslint config');
        }

        // Decode the JSON string into a PHP array
        $eslintJson = json_decode($eslintJsonString, true, 16, JSON_THROW_ON_ERROR);

        $extension = null;

        if (in_array('eslint-config-airbnb-base', $this->npmPackages, true)) {
            $extension = 'airbnb-base';
        } elseif (in_array('eslint-config-standard', $this->npmPackages, true)) {
            $extension = 'standard';
        }

        // Add the "extends" field
        if ($extension !== null) {
            $eslintJson['extends'] = $extension;
        }

        // Encode the array back to a JSON string
        $eslintJsonString = json_encode(
            $eslintJson,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        $result = file_put_contents($destination, $eslintJsonString);
        if ($result === false) {
            throw new Exception('Unable to save Eslint config to var dir');
        }
    }

    /**
     * @throws Exception
     */
    protected function makePhpStan(string $source, string $destination): void
    {
        [$major, $minor] = explode('.', $this->phpVersion);
        $phpStanVersion = sprintf('%d0%d00', $major, $minor);

        // Load phpstan.neon
        if (! file_exists($source)) {
            throw new Exception('phpstan.neon file not found.');
        }

        $phpStanConfig = file_get_contents($source);
        if ($phpStanConfig === false) {
            throw new Exception('Unable to load PHPStan config');
        }

        // Update phpVersion entry with project version.
        $phpStanConfig = preg_replace(
            '/phpVersion: \d+/',
            'phpVersion: ' . $phpStanVersion,
            $phpStanConfig
        );
        if (file_put_contents($destination, $phpStanConfig) === false) {
            throw new Exception('Unable to write PHPStan config file to var');
        }
    }

    /**
     * @throws Exception
     */
    protected function makePrettierrc(string $source, string $destination): void
    {
        // Load .prettierrc.json
        $prettierJsonString = file_get_contents($source);
        if ($prettierJsonString === false) {
            throw new Exception('Unable to read .prettierrc.json file.');
        }

        $prettierJson = json_decode($prettierJsonString, true, 16, JSON_THROW_ON_ERROR);

        // Update the print width.
        $prettierJson['printWidth'] = $this->wrap;

        // Find the plugins.
        if (! isset($prettierJson['plugins'])) {
            throw new Exception('Plugins not specified in .prettierrc.json.');
        }

        $plugins = [];

        if ($this->npmPackages !== []) {
            foreach ($this->npmPackages as $npmPackage) {
                if (preg_match('#prettier[/-]plugin#', $npmPackage)) {
                    $plugins[] = $npmPackage;

                    // Only set phpVersion if PHP plugin is included.
                    if ($npmPackage === '@prettier/plugin-php') {
                        $prettierJson['phpVersion'] = $this->phpVersion;
                    }
                }
            }

            $prettierJson['plugins'] = $plugins;
            // Encode the array back to a JSON string
            $prettierJsonString = json_encode(
                $prettierJson,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        }

        if (file_put_contents($destination, $prettierJsonString) === false) {
            throw new Exception('Unable to write Prettier config file to var');
        }
    }

    /**
     * @throws Exception
     */
    protected function setNpmPackages(): void
    {
        // Find the plugins.
        if (! isset($this->packageJson['devDependencies'])) {
            return;
        }

        $packageList = [];
        foreach (array_keys($this->packageJson['devDependencies']) as $package) {
            if (is_string($package)) {
                $packageList[] = $package;
            }
        }

        $this->npmPackages = $packageList;
    }

    protected function setPhpVersion(): void
    {
        // Find the PHP version in the require section
        if (! isset($this->composerJson['require']['php'])) {
            throw new Exception('PHP version not specified in composer.json.');
        }

        $phpVersionConstraint = $this->composerJson['require']['php'];

        // Extract the PHP version number
        if (preg_match('/\d+\.\d+/', (string) $phpVersionConstraint, $match) === 0) {
            throw new Exception('Unable to extract PHP version from composer.json.');
        }

        $this->phpVersion = $match[0];
    }

    protected function updatePhpPaths(): void
    {
        // Find top-level directories containing PHP files
        $phpPaths = [];

        foreach ($this->gitFiles as $gitFile) {
            if (pathinfo($gitFile, PATHINFO_EXTENSION) === 'php') {
                $topLevelDir = explode('/', $gitFile)[0];
                $phpPaths[$topLevelDir] = true;
            }
        }

        $phpPaths = array_keys($phpPaths);
        sort($phpPaths);

        $pathFile = $this->repoDir . '/php_paths';
        $oldPaths = file_exists($pathFile) ? file($pathFile, FILE_IGNORE_NEW_LINES) : [];

        // Write the list of directories to php_paths file
        if ($oldPaths !== $phpPaths) {
            file_put_contents($pathFile, implode(PHP_EOL, $phpPaths) . PHP_EOL);
            echo 'php_paths file has been created.' . PHP_EOL;
        }
    }
}
