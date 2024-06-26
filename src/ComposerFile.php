<?php

declare(strict_types=1);

namespace DouglasGreen\ConfigSetup;

use DouglasGreen\Utility\FileSystem\PathUtil;
use DouglasGreen\Utility\Regex\Regex;

class ComposerFile
{
    protected readonly bool $hasDoctrine;

    protected readonly bool $hasPhpUnit;

    protected readonly bool $hasSymfony;

    protected readonly ?string $phpVersion;

    public function __construct(
        protected readonly string $composerFilePath = 'composer.json'
    ) {
        $hasPhpUnit = false;
        $hasSymfony = false;
        $hasDoctrine = false;
        $phpVersion = null;

        $composerContent = PathUtil::loadString($this->composerFilePath);
        $composerData = json_decode($composerContent, true, 16, JSON_THROW_ON_ERROR);

        // Check for PHPUnit, Symfony, and Doctrine
        $requires = $composerData['require'] ?? [];
        $requiresDev = $composerData['require-dev'] ?? [];

        $allDependencies = array_merge($requires, $requiresDev);

        foreach ($allDependencies as $name => $value) {
            if (Regex::hasMatch('#^phpunit/#', $name)) {
                $hasPhpUnit = true;
            }

            if (Regex::hasMatch('#^symfony/#', $name)) {
                $hasSymfony = true;
            }

            if (Regex::hasMatch('#^doctrine/#', $name)) {
                $hasDoctrine = true;
            }

            if ($name !== 'php') {
                continue;
            }

            if (! is_string($value)) {
                continue;
            }

            $match = Regex::match('/\d+\.\d+/', $value);
            if ($match === []) {
                continue;
            }

            $phpVersion = $match[0];
        }

        $this->hasDoctrine = $hasDoctrine;
        $this->hasPhpUnit = $hasPhpUnit;
        $this->hasSymfony = $hasSymfony;
        $this->phpVersion = $phpVersion;
    }

    public function getPhpVersion(): ?string
    {
        return $this->phpVersion;
    }

    public function hasDoctrine(): bool
    {
        return $this->hasDoctrine;
    }

    public function hasPhpUnit(): bool
    {
        return $this->hasPhpUnit;
    }

    public function hasSymfony(): bool
    {
        return $this->hasSymfony;
    }
}
