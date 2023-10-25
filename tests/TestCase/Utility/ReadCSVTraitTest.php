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
                ['Column 1', 'Column 2', 'Column 3'],
                [
                    ['Column 1' => 'Name', 'Column 2' => 'Surname', 'Column 3' => '123,23'],
                    ['Column 1' => 'Another', 'Column 2' => 'Value', 'Column 3' => '35,99'],
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
