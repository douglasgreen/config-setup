#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace DouglasGreen\ConfigSetup;

use Exception;

class FileCopier
{
    public const AIRBNB = 1;

    public const PRE_PUSH = 2;

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
     * @var list<string>
     */
    protected array $filesToCopy;

    /**
     * @var array<string, int>
     */
    protected array $gitFiles;

    protected bool $useAirbnb;

    protected bool $usePrePush;

    /**
     * @throws Exception
     */
    public function __construct(
        protected string $repoDir,
        protected int $flags = 0
    ) {
        $this->useAirbnb = (bool) ($this->flags & self::AIRBNB);
        $this->usePrePush = (bool) ($this->flags & self::PRE_PUSH);

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
            $excludeLines = file($this->excludeFile, FILE_IGNORE_NEW_LINES);
            if ($excludeLines === false) {
                throw new Exception('Unable to load Git exclude file');
            }
        }

        $oldExcludeLines = $excludeLines;

        foreach ($this->filesToCopy as $fileToCopy) {
            // Don't overwrite Git files in the repo.
            if (isset($this->gitFiles[$fileToCopy])) {
                continue;
            }

            if ($this->useAirbnb && $fileToCopy === '.eslintrc.json') {
                // Put Airbnb temporary copy in var dir.
                $standardFile = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
                $source = $this->repoDir . '/vendor/douglasgreen/config-setup/var/' . $fileToCopy;

                $this->makeAirbnb($standardFile, $source);
                echo 'Using Airbnb config for eslint' . PHP_EOL;
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
            if (file_exists($destination) &&
                md5_file($source) === md5_file($destination)
            ) {
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
            if (in_array($fileToCopy, self::SCRIPTS_TO_COPY, true)) {
                chmod($destination, 0o755);
            } else {
                chmod($destination, 0o644);
            }
        }

        $this->updatePhpPaths();

        if ($excludeLines !== $oldExcludeLines) {
            file_put_contents(
                $this->excludeFile,
                implode(PHP_EOL, $excludeLines) . PHP_EOL
            );
            echo $this->excludeFile . ' has been updated.' . PHP_EOL;
        }
    }

    /**
     * @return list<string>
     * @throws Exception
     */
    protected function getPackageList(): array
    {
        // Load package.json
        $packageJson = file_get_contents('package.json');
        if ($packageJson === false) {
            return [];
        }

        $packageJson = json_decode(
            $packageJson,
            true,
            16,
            JSON_THROW_ON_ERROR
        );

        // Find the plugins.
        if (! isset($packageJson['devDependencies'])) {
            return [];
        }

        $packageList = [];
        foreach (array_keys($packageJson['devDependencies']) as $package) {
            if (is_string($package)) {
                $packageList[] = $package;
            }
        }

        return $packageList;
    }

    /**
     * @throws Exception
     */
    protected function makeAirbnb(string $source, string $destination): void
    {
        $standardConfig = file_get_contents($source);
        if ($standardConfig === false) {
            throw new Exception('Unable to load Eslint config');
        }

        $airbnbConfig = $this->updateJsonExtendsField($standardConfig);

        $result = file_put_contents($destination, $airbnbConfig);
        if ($result === false) {
            throw new Exception('Unable to save Eslint config to var dir');
        }
    }

    /**
     * @throws Exception
     */
    protected function makePhpStan(string $source, string $destination): void
    {
        // Load composer.json
        $composerJsonFile = file_get_contents('composer.json');
        if ($composerJsonFile === false) {
            throw new Exception('Unable to read composer.json file.');
        }

        $composerJson = json_decode(
            $composerJsonFile,
            true,
            16,
            JSON_THROW_ON_ERROR
        );

        // Find the PHP version in the require section
        if (! isset($composerJson['require']['php'])) {
            throw new Exception('PHP version not specified in composer.json.');
        }

        $phpVersionConstraint = $composerJson['require']['php'];

        // Extract the PHP version number
        if (preg_match(
            '/(\d+)\.(\d+)/',
            (string) $phpVersionConstraint,
            $matches
        ) === 0) {
            throw new Exception(
                'Unable to extract PHP version from composer.json.'
            );
        }

        $major = $matches[1];
        $minor = $matches[2];
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

        $prettierJson = json_decode(
            $prettierJsonString,
            true,
            16,
            JSON_THROW_ON_ERROR
        );

        // Find the plugins.
        if (! isset($prettierJson['plugins'])) {
            throw new Exception('Plugins not specified in .prettierrc.json.');
        }

        $plugins = [];

        $packages = $this->getPackageList();

        if ($packages !== []) {
            foreach ($packages as $package) {
                if (preg_match('#prettier[/-]plugin#', $package)) {
                    $plugins[] = $package;
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

    protected function updateJsonExtendsField(string $jsonString): string
    {
        // Decode the JSON string into a PHP array
        $data = json_decode($jsonString, true, 16, JSON_THROW_ON_ERROR);

        // Update the "extends" field
        $data['extends'] = 'airbnb-base';

        // Encode the array back to a JSON string
        $updatedJsonString = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        );

        return $updatedJsonString;
    }

    protected function updatePhpPaths(): void
    {
        // Find top-level directories containing PHP files
        $phpPaths = [];

        foreach (array_keys($this->gitFiles) as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
                $topLevelDir = explode('/', $file)[0];
                $phpPaths[$topLevelDir] = true;
            }
        }

        $phpPaths = array_keys($phpPaths);
        sort($phpPaths);

        $pathFile = $this->repoDir . '/php_paths';
        $oldPaths = file_exists($pathFile) ? file(
            $pathFile,
            FILE_IGNORE_NEW_LINES
        ) : [];

        // Write the list of directories to php_paths file
        if ($oldPaths !== $phpPaths) {
            file_put_contents($pathFile, implode(PHP_EOL, $phpPaths) . PHP_EOL);
            echo 'php_paths file has been created.' . PHP_EOL;
        }
    }
}
