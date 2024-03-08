<?php
declare(strict_types=1);

namespace Brevia\BEdita\Test\TestCase\Core\Filter;

use Brevia\BEdita\Utility\ReadCSVTrait;
use Cake\TestSuite\TestCase;

/**
 * {@see \Brevia\BEdita\Utility\ReadCSVTrait} Test Case
 *
 * @coversDefaultClass \Brevia\BEdita\Utility\ReadCSVTrait
 */
class ReadCSVTraitTest extends TestCase
{
    use ReadCSVTrait;

    /**
     * Provider for testReadCSVFile
     *
     * @return array
     */
    public function readCSVFileProvider(): array
    {
        return [
            'empty' => [
                [],
                [],
                getcwd() . '/tests/files/empty.csv',
            ],
            'import' => [
                ['name', 'surname', 'value', 'extra'],
                [
                    ['name' => 'Name', 'surname' => 'Surname', 'value' => '123,23', 'extra' => '{"key":"value"}'],
                    ['name' => 'Another', 'surname' => 'Value', 'value' => '35,99', 'extra' => '{"key2":"value2"}'],
                ],
                getcwd() . '/tests/files/import.csv',
            ],
        ];
    }

    /**
     * Test `readCSVFile` method
     *
     * @param array $keys Expected keys
     * @param array $data Expected data
     * @param string $filepath CSV File path
     * @param array $options CSV options
     * @dataProvider readCSVFileProvider
     * @covers ::readCSVFile()
     * @return void
     */
    public function testReadCSVFile(array $keys, array $data, string $filepath, array $options = []): void
    {
        $this->readCSVFile($filepath, $options);
        static::assertEquals($keys, $this->csvKeys);
        static::assertEquals($data, $this->csvData);
    }
}
