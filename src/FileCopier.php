#!/usr/bin/env php
<?php

declare(strict_types=1);

namespace DouglasGreen\ConfigSetup;

use DOMDocument;
use Exception;
use SimpleXMLElement;

class FileCopier
{
    public const DEFAULT_WRAP = 80;

    public const PRE_PUSH = 1;

    /**
     * @var array<string, ?string> Names of files to copy if the project is installed
     */
    protected const COPY_FILES = [
        '.eslintignore' => 'eslint',
        '.eslintrc.json' => 'eslint',
        '.prettierignore' => 'prettier',
        '.prettierrc.json' => 'prettier',
        '.stylelintignore' => 'stylelint',
        '.stylelintrc.json' => 'stylelint',
        'commitlint.config.js' => 'commitlint',
        'ecs.php' => 'ecs',
        'phpmd.xml' => 'phpmd',
        'phpstan.neon' => 'phpstan',
        'phpunit.xml' => 'phpunit',
        'rector.php' => 'rector',
    ];

    /**
     * @var array<string, ?string> Names of scripts to copy if the project is installed
     */
    protected const COPY_SCRIPTS = [
        '.husky/commit-msg' => 'husky',
        '.husky/post-checkout' => 'husky',
        '.husky/post-merge' => 'husky',
        '.husky/pre-commit' => 'husky',
        'run_phpmd.sh' => 'phpmd',
        'run_phpstan.sh' => 'phpstan',
        'script/bootstrap' => null,
        'script/fix' => null,
        'script/lint' => null,
        'script/setup' => null,
        'script/test' => null,
    ];

    /**
     * @var array<string, ?string> Names of directories to make if the project is installed
     */
    protected const MAKE_DIRS = [
        '.husky' => 'husky',
        'script' => null,
        'var/cache/ecs' => 'ecs',
        'var/cache/eslint' => 'eslint',
        'var/cache/phpmd' => 'phpmd',
        'var/cache/phpstan' => 'phpstan',
        'var/cache/phpunit' => 'phpunit',
        'var/cache/rector' => 'rector',
        'var/report/phpunit' => 'phpunit',
    ];

    /**
     * @var array<string, string> Project name and its actual package name
     */
    protected const PACKAGE_NAMES = [
        'ecs' => 'symplify/easy-coding-standard',
        'phpmd' => 'phpmd/phpmd',
        'phpstan' => 'phpstan/phpstan',
        'phpunit' => 'phpunit/phpunit',
        'rector' => 'rector/rector',

        'commitlint' => '@commitlint/cli',
        'eslint' => 'eslint',
        'husky' => 'husky',
        'mocha' => 'mocha',
        'prettier' => 'prettier',
        'stylelint' => 'stylelint',
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
    protected array $composerPackages = [];

    protected string $excludeFile;

    /**
     * @var array<string, ?string>
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

    /**
     * @var list<string>
     */
    protected array $phpDirectories = [];

    protected string $phpVersion;

    protected bool $useCobertura;

    protected bool $useJunit;

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

        $this->filesToCopy = array_merge(self::COPY_FILES, self::COPY_SCRIPTS);
        ksort($this->filesToCopy);

        $this->excludeFile = $this->repoDir . '/' . self::GIT_EXCLUDE_FILE;

        $this->setComposerPackages();
        $this->setNpmPackages();
        $this->setPhpVersion();
        $this->updatePhpPaths();

        foreach (self::MAKE_DIRS as $dir => $requiredPackage) {
            // Don't make directories if their package isn't installed.
            if (! $this->hasPackage($requiredPackage)) {
                continue;
            }

            $this->makeDir($dir);
        }
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
        foreach ($this->filesToCopy as $fileToCopy => $requiredPackage) {
            // Don't overwrite Git files in the repo.
            if (isset($gitFiles[$fileToCopy])) {
                continue;
            }

            // Don't copy files if their package isn't installed.
            if (! $this->hasPackage($requiredPackage)) {
                continue;
            }

            $plainFile = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
            $source = $this->repoDir . '/vendor/douglasgreen/config-setup/var/' . $fileToCopy;
            if ($fileToCopy === 'ecs.php') {
                // Put temporary copy with correct "line_length" value in var dir.
                $this->makeEcs($plainFile, $source);
            } elseif ($fileToCopy === '.eslintrc.json') {
                // Put temporary copy with correct "extends" value in var dir.
                $this->makeEslintrc($plainFile, $source);
            } elseif ($fileToCopy === 'phpstan.neon') {
                // Put PHPStan temporary copy with PHP version in var dir.
                $this->makePhpStan($plainFile, $source);
            } elseif ($fileToCopy === 'phpunit.xml') {
                // Put PHPUnit temporary copy with PHP version in var dir.
                $this->makePhpunit($plainFile, $source);
            } elseif ($fileToCopy === '.prettierrc.json') {
                // Put Prettier temporary copy with new plugin list in var dir.
                $this->makePrettierrc($plainFile, $source);
            } else {
                // Use original, unmodified source.
                $source = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
            }

            // Overwrite target but not source file to copy to different name.
            if ($this->usePrePush && $fileToCopy === '.husky/pre-commit') {
                echo 'Using .husky/pre-push hook instead of .husky/pre-commit.' . PHP_EOL;
                $fileToCopy = '.husky/pre-push';
            }

            $destination = $this->repoDir . '/' . $fileToCopy;

            $destinationDir = dirname($destination);
            $this->makeDir($destinationDir);

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
            if (array_key_exists($fileToCopy, self::COPY_SCRIPTS)) {
                chmod($destination, 0o755);
            } else {
                chmod($destination, 0o644);
            }
        }

        if ($excludeLines === []) {
            return;
        }

        if ($excludeLines === $oldExcludeLines) {
            return;
        }

        if (
            file_put_contents($this->excludeFile, implode(PHP_EOL, $excludeLines) . PHP_EOL) ===
            false
        ) {
            echo 'Error updating ' . $this->excludeFile . PHP_EOL;
        } else {
            echo $this->excludeFile . ' has been updated.' . PHP_EOL;
        }
    }

    protected function hasCodeCoverageDriver(): bool
    {
        exec('php -m | grep -E "xdebug|pcov"', $output, $returnCode);
        if ($returnCode !== 0 && $returnCode !== 1) {
            throw new Exception('Unable to determine if code coverage driver is available');
        }

        return ! empty($output);
    }

    /**
     * Check if the repository has the required package, either in Composer or NPM.
     */
    protected function hasPackage(?string $requiredPackage): bool
    {
        // If there are no requirements, they can't fail.
        if ($requiredPackage === null) {
            return true;
        }

        $packageName = self::PACKAGE_NAMES[$requiredPackage];
        return in_array($packageName, $this->composerPackages, true) ||
            in_array($packageName, $this->npmPackages, true);
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
     * Make a directory if it doesn't exist.
     *
     * @throws Exception
     */
    protected function makeDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (mkdir($dir, 0o777, true)) {
            return;
        }

        throw new Exception(sprintf('Unable to make directory: "%s"', $dir));
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

    protected function makePhpunit(string $source, string $destination): void
    {
        // Load the XML file.
        $xmlSource = file_get_contents($source);
        if ($xmlSource === false) {
            throw new Exception('Unable to load phpunit.xml');
        }

        $xml = new SimpleXMLElement($xmlSource);

        // Add source files.
        $source = $xml->addChild('source');
        $include = $source->addChild('include');

        // Add each PHP directory to the include section.
        foreach ($this->phpDirectories as $phpDirectory) {
            // Don't provide coverage of the unit tests directory.
            if ($phpDirectory === 'tests') {
                continue;
            }

            $directory = $include->addChild('directory', $phpDirectory);
            $directory->addAttribute('suffix', '.php');
        }

        // Add coverage if a code coverage driver is available.
        if ($this->hasCodeCoverageDriver()) {
            $coverage = $xml->addChild('coverage');
            $coverage->addAttribute('cacheDirectory', 'var/report/phpunit/cache/');

            $report = $coverage->addChild('report');
            $report
                ->addChild('cobertura')
                ->addAttribute('outputFile', 'var/report/phpunit/cobertura.xml');
            $report->addChild('html')
                ->addAttribute('outputDirectory', 'var/report/phpunit/html');
            $report->addChild('text')
                ->addAttribute('outputFile', 'php://stdout');
        }

        // Save the modified XML to the new file with pretty print.
        $domDocument = new DOMDocument('1.0');
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;
        $xmlOutput = $xml->asXML();
        if ($xmlOutput === false) {
            throw new Exception('Unable to make PHPUnit XML');
        }

        $domDocument->loadXML($xmlOutput);
        $domDocument->save($destination);
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
                        // @prettier/plugin-php 0.22 doesn't support PHP 8.3 yet.
                        // @todo Update this when the new PHP Prettier plugin version arrives.
                        $prettierJson['phpVersion'] = min($this->phpVersion, '8.2');
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
    protected function setComposerPackages(): void
    {
        // Find the plugins.
        if (! isset($this->composerJson['require-dev'])) {
            return;
        }

        $packageList = [];
        foreach (array_keys($this->composerJson['require-dev']) as $package) {
            if (is_string($package)) {
                $packageList[] = $package;
            }
        }

        $this->composerPackages = $packageList;
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
                if (is_dir($topLevelDir)) {
                    $phpPaths[$topLevelDir] = true;
                }
            }
        }

        $this->phpDirectories = array_keys($phpPaths);
        sort($this->phpDirectories);

        $pathFile = $this->repoDir . '/php_paths';
        $oldPaths = file_exists($pathFile) ? file($pathFile, FILE_IGNORE_NEW_LINES) : [];

        // Write the list of directories to php_paths file
        if ($oldPaths !== $this->phpDirectories) {
            file_put_contents($pathFile, implode(PHP_EOL, $this->phpDirectories) . PHP_EOL);
            echo 'php_paths file has been created.' . PHP_EOL;
        }
    }
}
