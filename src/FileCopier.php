<?php

declare(strict_types=1);

namespace DouglasGreen\ConfigSetup;

use DOMDocument;
use DouglasGreen\Utility\Data\ValueException;
use DouglasGreen\Utility\Data\XmlException;
use DouglasGreen\Utility\FileSystem\DirUtil;
use DouglasGreen\Utility\FileSystem\PathUtil;
use DouglasGreen\Utility\Program\Command;
use DouglasGreen\Utility\Program\CommandException;
use DouglasGreen\Utility\Regex\Regex;
use SimpleXMLElement;

class FileCopier
{
    public const DEFAULT_WRAP = 100;

    public const PRE_COMMIT = 1;

    public const PRE_PUSH = 2;

    public const USE_WOOCOMMERCE = 4;

    public const USE_WORDPRESS = 8;

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
        'phpstan.neon' => 'phpstan',
        'phpunit.xml' => 'phpunit',
        'rector.php' => 'rector',
        'script/functions.py' => null,
        'stubs/wordpress.php' => null,
    ];

    /**
     * @var array<string, ?string> Names of scripts to copy if the project is installed
     */
    protected const COPY_SCRIPTS = [
        '.husky/commit-msg' => 'husky',
        '.husky/post-checkout' => 'husky',
        '.husky/post-merge' => 'husky',
        '.husky/post-rewrite' => 'husky',
        '.husky/pre-commit' => 'husky',
        'script/bootstrap' => null,
        'script/fix' => null,
        'script/lint' => null,
        'script/setup' => null,
        'script/test' => null,
        'script/update' => null,
    ];

    /**
     * There is no cache for rector because it was having too many errors.
     *
     * @var array<string, ?string> Names of directories to make if the project is installed
     */
    protected const MAKE_DIRS = [
        '.husky' => 'husky',
        'script' => null,
        'stubs' => null,
        'var/cache/ecs' => 'ecs',
        'var/cache/eslint' => 'eslint',
        'var/cache/pdepend' => 'pdepend',
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
        'pdepend' => 'pdepend/pdepend',
        'phpstan' => 'phpstan/phpstan',
        'phpunit' => 'phpunit/phpunit',
        'rector' => 'rector/rector',

        'commitlint' => '@commitlint/cli',
        'eslint' => 'eslint',
        'husky' => 'husky',
        'prettier' => 'prettier',
        'stylelint' => 'stylelint',
    ];

    /**
     * @var array<string, mixed>
     */
    protected readonly array $composerJson;

    /**
     * @var ?list<string>
     */
    protected readonly ?array $composerPackages;

    /**
     * @var array<string, ?string>
     */
    protected readonly array $filesToCopy;

    /**
     * @var list<string>
     */
    protected readonly array $gitFiles;

    /**
     * @var ?list<string>
     */
    protected readonly ?array $npmPackages;

    /**
     * @var ?array<string, mixed>
     */
    protected readonly ?array $packageJson;

    /**
     * @var list<string>
     */
    protected readonly array $phpPaths;

    protected readonly string $excludeFile;

    protected readonly string $phpVersion;

    protected readonly bool $usePreCommit;

    protected readonly bool $usePrePush;

    protected readonly bool $useWoocommerce;

    protected readonly bool $useWordpress;

    public function __construct(
        protected readonly string $repoDir,
        protected readonly int $flags = 0,
        protected readonly int $wrap = self::DEFAULT_WRAP
    ) {
        $this->usePreCommit = (bool) ($this->flags & self::PRE_COMMIT);
        $this->usePrePush = (bool) ($this->flags & self::PRE_PUSH);
        $this->useWoocommerce = (bool) ($this->flags & self::USE_WOOCOMMERCE);
        $this->useWordpress = (bool) ($this->flags & self::USE_WORDPRESS);

        $this->gitFiles = self::loadGitFiles();
        $this->composerJson = self::loadComposerJson();
        $this->packageJson = self::loadPackageJson();
        $this->phpPaths = $this->getPhpPaths();

        $filesToCopy = array_merge(self::COPY_FILES, self::COPY_SCRIPTS);
        ksort($filesToCopy);
        $this->filesToCopy = $filesToCopy;

        // Add to .git/info/exclude to ignore without modifying .gitignore.
        $this->excludeFile = $this->repoDir . '/.git/info/exclude';

        $this->composerPackages = $this->getComposerPackages();
        $this->npmPackages = $this->getNpmPackages();
        $this->phpVersion = $this->getPhpVersion();

        foreach (self::MAKE_DIRS as $dir => $requiredPackage) {
            // Don't make directories if their package isn't installed.
            if (! $this->hasPackage($requiredPackage)) {
                continue;
            }

            // Check if the stubs are needed.
            if ($dir === 'stubs' && ! $this->useWordpress) {
                continue;
            }

            self::makeDir($dir);
        }
    }

    public function copyFiles(): void
    {
        $excludeLines = [];
        if (file_exists($this->excludeFile)) {
            $excludeLines = PathUtil::loadLines($this->excludeFile, PathUtil::IGNORE_NEW_LINES);
        }

        $oldExcludeLines = $excludeLines;

        if ($this->updatePhpPaths()) {
            $excludeLines[] = 'php_paths';
        }

        if ($this->wrap !== self::DEFAULT_WRAP) {
            printf('Setting line wrap to %d characters.' . PHP_EOL, $this->wrap);
        }

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

            // Skip WordPress if not requested.
            if (! $this->useWordpress && $fileToCopy === 'stubs/wordpress.php') {
                continue;
            }

            $plainFile = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
            $target = $this->repoDir . '/vendor/douglasgreen/config-setup/var/' . $fileToCopy;
            if ($fileToCopy === 'ecs.php') {
                // Put temporary copy with correct "line_length" value in var dir.
                $this->makeEcs($plainFile, $target);
            } elseif ($fileToCopy === '.eslintrc.json') {
                // Put temporary copy with correct "extends" value in var dir.
                $this->makeEslintrc($plainFile, $target);
            } elseif ($fileToCopy === 'phpstan.neon') {
                // Put PHPStan temporary copy with PHP version in var dir.
                $this->makePhpStan($plainFile, $target);
            } elseif ($fileToCopy === 'phpunit.xml') {
                // Put PHPUnit temporary copy with directory list and coverage options in var dir.
                $this->makePhpUnit($target);
            } elseif ($fileToCopy === '.prettierrc.json') {
                // Put Prettier temporary copy with new plugin list in var dir.
                $this->makePrettierrc($plainFile, $target);
            } elseif ($fileToCopy === '.husky/pre-commit') {
                // Use original pre-commit file as target in either case.
                $target = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;

                // Install either pre-commit, or pre-push, or none.
                if ($this->usePrePush) {
                    // Pre-push symlink points to pre-commit script.
                    $fileToCopy = '.husky/pre-push';
                } elseif (! $this->usePreCommit) {
                    // No symlink was requested.
                    continue;
                }
            } else {
                // Use original, unmodified source.
                $target = $this->repoDir . '/vendor/douglasgreen/config-setup/' . $fileToCopy;
            }

            $symlink = $this->repoDir . '/' . $fileToCopy;

            $symlinkDir = dirname($symlink);
            self::makeDir($symlinkDir);

            if (! in_array($fileToCopy, $excludeLines, true)) {
                $excludeLines[] = $fileToCopy;
            }

            // Check if link already exists.
            if (is_link($symlink)) {
                // Check if link is pointing to the right target.
                $actualTarget = PathUtil::getLinkTarget($symlink);
                if ($actualTarget === $target) {
                    continue;
                }

                PathUtil::delete($symlink);
            }

            // Check if the destination exists and is a file, then delete it
            if (is_file($symlink)) {
                PathUtil::delete($symlink);
            }

            // Create a soft link instead of copying the file
            PathUtil::makeSymlink($target, $symlink);
            printf('Created symlink %s.' . PHP_EOL, PathUtil::removeBase($this->repoDir, $symlink));
        }

        if ($excludeLines === []) {
            return;
        }

        if ($excludeLines === $oldExcludeLines) {
            return;
        }

        $output = implode(PHP_EOL, $excludeLines) . PHP_EOL;
        if (is_dir(dirname($this->excludeFile))) {
            PathUtil::saveString($this->excludeFile, $output);
            printf(
                '%s has been updated.' . PHP_EOL,
                PathUtil::removeBase($this->repoDir, $this->excludeFile)
            );
        }
    }

    /**
     * @throws CommandException
     */
    protected static function hasCodeCoverageDriver(): bool
    {
        $command = new Command('php -m');
        $command->addSubcommand('|', 'grep -E', ['xdebug|pcov']);

        $output = $command->run();
        $returnCode = $command->getReturnCode();
        if ($returnCode !== 0 && $returnCode !== 1) {
            throw new CommandException('Unable to determine if code coverage driver is available');
        }

        return $output !== [];
    }

    /**
     * @return array<string, mixed>
     */
    protected static function loadComposerJson(): array
    {
        $composerJsonString = PathUtil::loadString('composer.json');
        return json_decode($composerJsonString, true, 16, JSON_THROW_ON_ERROR);
    }

    /**
     * @return list<string>
     */
    protected static function loadGitFiles(): array
    {
        $command = new Command('git ls-files');
        return $command->run();
    }

    /**
     * @return ?array<string, mixed>
     */
    protected static function loadPackageJson(): ?array
    {
        if (! file_exists('package.json')) {
            echo 'File package.json not found.' . PHP_EOL;
            return null;
        }

        $packageJsonString = PathUtil::loadString('package.json');
        return json_decode($packageJsonString, true, 16, JSON_THROW_ON_ERROR);
    }

    /**
     * Make a directory if it doesn't exist.
     */
    protected static function makeDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        DirUtil::makeRecursive($dir);
    }

    /**
     * @return ?list<string>
     */
    protected function getComposerPackages(): ?array
    {
        // Find the plugins.
        if (! isset($this->composerJson['require-dev'])) {
            return null;
        }

        $packageList = [];
        foreach (array_keys($this->composerJson['require-dev']) as $package) {
            if (is_string($package)) {
                $packageList[] = $package;
            }
        }

        return $packageList;
    }

    /**
     * @return ?list<string>
     */
    protected function getNpmPackages(): ?array
    {
        // Find the plugins.
        if (! isset($this->packageJson['devDependencies'])) {
            return null;
        }

        $packageList = [];
        foreach (array_keys($this->packageJson['devDependencies']) as $package) {
            if (is_string($package)) {
                $packageList[] = $package;
            }
        }

        return $packageList;
    }

    /**
     * @return list<string>
     */
    protected function getPhpPaths(): array
    {
        // Find top-level directories containing PHP files
        $phpPaths = [];

        foreach ($this->gitFiles as $gitFile) {
            if (Regex::hasMatch('/\.php$/', $gitFile)) {
                // Extract the top-level directory for files with PHP extension
                $topLevelDir = explode('/', $gitFile)[0];
                $phpPaths[$topLevelDir] = true;
            } elseif (PathUtil::getFileType($gitFile) === 'php') {
                // Store the entire path for other files to be sure they are recognized
                $phpPaths[$gitFile] = true;
            }
        }

        $phpPaths = array_keys($phpPaths);
        sort($phpPaths);
        return $phpPaths;
    }

    /**
     * @throws ValueException
     */
    protected function getPhpVersion(): string
    {
        // Find the PHP version in the require section
        if (! isset($this->composerJson['require']['php'])) {
            throw new ValueException('PHP version not specified in composer.json');
        }

        $phpVersionConstraint = $this->composerJson['require']['php'];

        // Extract the PHP version number
        $match = Regex::match('/\d+\.\d+/', (string) $phpVersionConstraint);
        if ($match === []) {
            throw new ValueException('Unable to extract PHP version from composer.json');
        }

        return $match[0];
    }

    /**
     * Check if the repository has the required package, either in Composer or NPM.
     */
    protected function hasPackage(?string $requiredPackage): bool
    {
        // If there are no requirements, it can't fail.
        if ($requiredPackage === null) {
            return true;
        }

        $packageName = self::PACKAGE_NAMES[$requiredPackage];

        if (
            $this->composerPackages !== null &&
            in_array($packageName, $this->composerPackages, true)
        ) {
            return true;
        }

        return $this->npmPackages !== null && in_array($packageName, $this->npmPackages, true);
    }

    protected function makeEcs(string $source, string $destination): void
    {
        $lines = PathUtil::loadLines($source);
        $newLines = [];
        foreach ($lines as $line) {
            if (str_contains($line, 'line_length')) {
                $line = Regex::replace('/\b100\b/', (string) $this->wrap, $line);
            }

            $newLines[] = $line;
        }

        $newString = implode('', $newLines);
        PathUtil::saveString($destination, $newString);
    }

    /**
     * @throws XmlException
     */
    protected function makeEslintrc(string $source, string $destination): void
    {
        if ($this->npmPackages === null) {
            return;
        }

        // Decode the JSON string into a PHP array
        $eslintJsonString = PathUtil::loadString($source);
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

        PathUtil::saveString($destination, $eslintJsonString);
    }

    protected function makePhpStan(string $source, string $destination): void
    {
        [$major, $minor] = explode('.', $this->phpVersion);
        $phpStanVersion = sprintf('%d0%d00', $major, $minor);

        // Load phpstan.neon
        $sourceFile = new NeonFile($source);
        $phpStanConfig = $sourceFile->load();

        // Update phpVersion entry with project version.
        $phpStanConfig['parameters']['phpVersion'] = (int) $phpStanVersion;

        // Add bootstrap file if exists at usual location.
        $bootstrapFiles = [];
        if (file_exists($this->repoDir . '/phpstan-bootstrap.php')) {
            $bootstrapFiles[] = 'phpstan-bootstrap.php';
        }

        // Add the PHP paths to process.
        $phpPaths = $this->phpPaths;

        if ($this->useWordpress) {
            // Include WordPress extensions without needing the PHPStan extension installer.
            $phpStanConfig['includes'] = ['vendor/szepeviktor/phpstan-wordpress/extension.neon'];
            if ($this->useWoocommerce) {
                $bootstrapFiles[] = 'vendor/php-stubs/woocommerce-stubs/woocommerce-stubs.php';
            }

            // Add the stubs directory if we are installing the WordPress stub.
            if (! in_array('stubs', $phpPaths, true)) {
                $phpPaths[] = 'stubs';
            }
        }

        $phpStanConfig['parameters']['paths'] = $phpPaths;

        if ($bootstrapFiles !== []) {
            $phpStanConfig['parameters']['bootstrapFiles'] = $bootstrapFiles;
        }

        $destFile = new NeonFile($destination);
        $destFile->save($phpStanConfig);
    }

    protected function makePhpUnit(string $destination): void
    {
        // Initialize the XML structure with the necessary attributes because SimpleXML doesn't
        // support namespaces directly.
        $xmlString = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
                xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.5/phpunit.xsd" 
                bootstrap="{$this->repoDir}/vendor/autoload.php" 
                cacheDirectory="{$this->repoDir}/var/cache/phpunit" 
                cacheResult="true" 
                colors="true" 
                executionOrder="random" 
                failOnIncomplete="false" 
                failOnNotice="true" 
                failOnRisky="false" 
                failOnWarning="true" 
                stopOnFailure="false">
                <testsuites>
                    <testsuite name="Project Test Suite">
                        <directory>{$this->repoDir}/tests</directory>
                    </testsuite>
                </testsuites>
                <logging>
                    <junit outputFile="{$this->repoDir}/var/report/phpunit/junit.xml"/>
                </logging>
            </phpunit>
            XML;

        // Load the XML string into SimpleXMLElement.
        $xml = new SimpleXMLElement($xmlString);

        // Add source files.
        $source = $xml->addChild('source');
        $include = $source->addChild('include');

        // Add each PHP directory to the include section.
        foreach ($this->phpPaths as $phpPath) {
            // Don't provide coverage of the unit tests directory.
            if ($phpPath === 'tests') {
                continue;
            }

            $directory = $include->addChild('directory', $this->repoDir . '/' . $phpPath);
            $directory->addAttribute('suffix', '.php');
        }

        // Add coverage if a code coverage driver is available.
        if (self::hasCodeCoverageDriver()) {
            $php = $xml->addChild('php');
            $env = $php->addChild('env');
            $env->addAttribute('name', 'XDEBUG_MODE');
            $env->addAttribute('value', 'coverage');

            $coverage = $xml->addChild('coverage');
            $coverage->addAttribute(
                'cacheDirectory',
                $this->repoDir . '/var/report/phpunit/cache/'
            );

            $report = $coverage->addChild('report');
            $report
                ->addChild('cobertura')
                ->addAttribute('outputFile', $this->repoDir . '/var/report/phpunit/cobertura.xml');
            $report
                ->addChild('html')
                ->addAttribute('outputDirectory', $this->repoDir . '/var/report/phpunit/html');
            $report
                ->addChild('text')
                ->addAttribute('outputFile', $this->repoDir . '/var/report/phpunit/text');
        }

        // Save the modified XML to the new file with pretty print.
        $domDocument = new DOMDocument('1.0');
        $domDocument->preserveWhiteSpace = false;
        $domDocument->formatOutput = true;
        $xmlOutput = $xml->asXML();
        if ($xmlOutput === false) {
            throw new XmlException('Unable to make PHPUnit XML');
        }

        $domDocument->loadXML($xmlOutput);
        $domDocument->save($destination);
    }

    /**
     * @throws ValueException
     */
    protected function makePrettierrc(string $source, string $destination): void
    {
        if ($this->npmPackages === null) {
            return;
        }

        // Load .prettierrc.json
        $prettierJsonString = PathUtil::loadString($source);
        $prettierJson = json_decode($prettierJsonString, true, 16, JSON_THROW_ON_ERROR);

        // Update the print width.
        $prettierJson['printWidth'] = $this->wrap;

        // Find the plugins.
        if (! isset($prettierJson['plugins'])) {
            throw new ValueException('Plugins not specified in .prettierrc.json');
        }

        $plugins = [];

        if ($this->npmPackages !== []) {
            foreach ($this->npmPackages as $npmPackage) {
                if (Regex::hasMatch('#prettier[/-]plugin#', $npmPackage)) {
                    $plugins[] = $npmPackage;
                }
            }

            $prettierJson['plugins'] = $plugins;
            // Encode the array back to a JSON string
            $prettierJsonString = json_encode(
                $prettierJson,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
            );
        }

        PathUtil::saveString($destination, $prettierJsonString);
    }

    protected function updatePhpPaths(): bool
    {
        $pathFile = $this->repoDir . '/php_paths';
        $oldPaths = file_exists($pathFile) ? PathUtil::loadLines(
            $pathFile,
            PathUtil::IGNORE_NEW_LINES
        ) : [];

        // Write the list of directories to php_paths file
        if ($oldPaths !== $this->phpPaths) {
            PathUtil::saveString($pathFile, implode(PHP_EOL, $this->phpPaths) . PHP_EOL);
            echo 'Created php_paths file.' . PHP_EOL;
            return true;
        }

        return false;
    }
}
