<?php

declare(strict_types=1);

namespace DouglasGreen\ConfigSetup;

use DouglasGreen\Utility\FileSystem\FileException;
use DouglasGreen\Utility\FileSystem\PathUtil;
use Nette\Neon\Neon;

class NeonFile
{
    public function __construct(
        protected readonly string $filename
    ) {}

    /**
     * @return array<string, mixed>
     * @throws FileException
     */
    public function load(): array
    {
        if (! file_exists($this->filename)) {
            throw new FileException('File not found: ' . $this->filename);
        }

        return Neon::decodeFile($this->filename);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function save(array $data): void
    {
        $neonContent = Neon::encode($data, Neon::BLOCK);
        PathUtil::saveString($this->filename, $neonContent);
    }
}
