<?php

namespace Starkerxp\DatabaseChecker\Tests\Structure;

use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseIndex;
use PHPUnit\Framework\TestCase;

class MysqlDatabaseIndexTest extends TestCase
{

    public function testCreateStatementException()
    {
        $databaseIndex = new MysqlDatabaseIndex('primary', ['id'], true);
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException");
        $databaseIndex->createStatement();
    }

    public function testAlterStatementException()
    {
        $databaseIndex = new MysqlDatabaseIndex('primary', ['id'], true);
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException");
        $databaseIndex->alterStatement();
    }

    public function dataProviderStatements()
    {
        $combinaisons = [
            'primaryOneColumn' => [
                'expected' => 'PRIMARY KEY (`id`)',
                'expectedAlter' => 'PRIMARY KEY',
                'name' => 'primary',
                'columns' => ['id'],
                'unique' => false,
            ],
            'primaryTwoColumn' => [
                'expected' => 'PRIMARY KEY (`id`, `id2`)',
                'expectedAlter' => 'PRIMARY KEY',
                'name' => 'primary',
                'columns' => ['id', 'id2'],
                'unique' => true,
            ],
            'oneColumn' => [
                'expected' => ' INDEX `chips` (`id`)',
                'expectedAlter' => 'INDEX `chips`',
                'name' => 'chips',
                'columns' => ['id'],
                'unique' => false,
            ],
            'twoColumn' => [
                'expected' => ' INDEX `chips` (`id`, `id2`)',
                'expectedAlter' => 'INDEX `chips`',
                'name' => 'chips',
                'columns' => ['id', 'id2'],
                'unique' => false,
            ],
            'oneColumnUnique' => [
                'expected' => 'UNIQUE INDEX `chips` (`id`)',
                'expectedAlter' => 'INDEX `chips`',
                'name' => 'chips',
                'columns' => ['id'],
                'unique' => true,
            ],
            'twoColumnUnique' => [
                'expected' => 'UNIQUE INDEX `chips` (`id`, `id2`)',
                'expectedAlter' => 'INDEX `chips`',
                'name' => 'chips',
                'columns' => ['id', 'id2'],
                'unique' => true,
            ],
        ];

        return $combinaisons;
    }

    /**
     * @dataProvider dataProviderStatements
     *
     * @param string $expected
     * @param string $expectedAlter
     * @param string $name
     * @param array $columns
     * @param boolean $unique
     *
     * @throws \Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException
     */
    public function testStatements($expected, $expectedAlter, $name, array $columns, $unique)
    {
        $createStatementExpected = 'ALTER TABLE `activite` ADD ' . $expected . ';';
        $databaseIndex = new MysqlDatabaseIndex($name, $columns, $unique);
        $databaseIndex->setTable('activite');
        $statements = $databaseIndex->createStatement();
        $this->assertEquals($createStatementExpected, $statements[0]);
        $statements = $databaseIndex->alterStatement();
        $this->assertCount(2, $statements);
        $this->assertEquals('ALTER TABLE `activite` DROP ' . $expectedAlter . ';', $statements[0]);
        $this->assertEquals($createStatementExpected, $statements[1]);
    }

}
