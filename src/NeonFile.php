<?php

namespace DouglasGreen\ConfigSetup;

use Exception;
use Nette\Neon\Neon;

class NeonFile
{
    public function __construct(
        protected readonly string $filename
    ) {}

    /**
     * @return array<string, mixed>
     * @throws Exception
     */
    public function load(): array
    {
        if (! file_exists($this->filename)) {
            throw new Exception('File not found: ' . $this->filename);
        }

        return Neon::decodeFile($this->filename);
    }

    /**
     * @param array<string, mixed> $data
     * @throws Exception
     */
    public function save(array $data): void
    {
        $neonContent = Neon::encode($data, Neon::BLOCK);
        if (file_put_contents($this->filename, $neonContent) === false) {
            throw new Exception('Unable to save file');
        }
    }
}
