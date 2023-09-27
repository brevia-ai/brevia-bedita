<?php
declare(strict_types=1);

/**
 * Chatlas BEdita plugin
 *
 * Copyright 2023 Atlas Srl
 */
namespace BEdita\Chatlas\Utility;

/**
 * Add CSV read methods
 */
trait ReadCSVTrait
{
    /**
     * CSV default options
     *
     * @var array
     */
    protected array $csvOptions = [
        'delimiter' => ',',
        'enclosure' => '"',
        'escape' => '\\',
    ];

    /**
     * CSV file keys read from header
     *
     * @var array
     */
    protected array $csvKeys = [];

    /**
     * CSV file data content organized as associative array with `key` => `value`
     *
     * @var array
     */
    protected array $csvData = [];

    /**
     * Read CSV file and populate `csvKeys` and `csvData` arrays
     *
     * @param string $filepath CSV file path
     * @param array $options CSV options overriding defaults
     * @return void
     */
    public function readCSVFile(string $filepath, ?array $options = []): void
    {
        $options = array_merge($this->csvOptions, (array)$options);
        $this->csvKeys = $this->csvData = [];

        $filecontent = file($filepath); // <= into an array
        if (empty($filecontent)) {
            return;
        }
        // read keys from first line
        $line = array_shift($filecontent);
        $this->csvKeys = str_getcsv($line, $options['delimiter'], $options['enclosure'], $options['escape']);
        $this->csvKeys = array_map('trim', $this->csvKeys);
        // read data using keys
        foreach ($filecontent as $line) {
            $values = str_getcsv($line, $options['delimiter'], $options['enclosure'], $options['escape']);
            if (count($values) != count($this->csvKeys)) {
                continue;
            }
            $this->csvData[] = array_combine($this->csvKeys, $values);
        }
    }
}
