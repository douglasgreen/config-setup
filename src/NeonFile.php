<?php

/**
 * This file defines class NeonFile.
 */

namespace DouglasGreen\ConfigSetup;

use Exception;
use Nette\Neon\Neon;

/**
 * This class saves and loads data in Neon format.
 */
class NeonFile
{
    /**
     * Set object properties.
     *
     * @param string $filename File name to save and load
     */
    public function __construct(
        protected readonly string $filename,
    ) {
    }

    /**
     * Load the Neon data from the file.
     *
     * @throws Exception if file not found
     * @return array<string, mixed>
     */
    public function load(): array
    {
        if (!file_exists($this->filename)) {
            throw new Exception('File not found: ' . $this->filename);
        }

        return Neon::decodeFile($this->filename);
    }

    /**
     * Save the data to the file in Neon format.
     *
     * @param array<string, mixed> $data Data to save
     *
     * @throws Exception if unable to save file
     */
    public function save(array $data): void
    {
        $neonContent = Neon::encode($data, Neon::BLOCK);
        if (file_put_contents($this->filename, $neonContent) === false) {
            throw new Exception('Unable to save file');
        }
    }
}
