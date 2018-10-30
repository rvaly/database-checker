<?php

namespace Starkerxp\DatabaseChecker\Tests\Structure;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseIndex;
use Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException;

class MysqlDatabaseIndexTest extends TestCase
{
    /**
     * @group structure
     * @group exception
     */
    public function testCreateObjectException()
    {
        $this->expectException(\RuntimeException::class);
        new MysqlDatabaseIndex('', ['id'], true);
    }

    /**
     * @group structure
     * @group exception
     */
    public function testCreateStatementException()
    {
        $databaseIndex = new MysqlDatabaseIndex('primary', ['id'], true);
        $this->expectException(TablenameHasNotDefinedException::class);
        $databaseIndex->createStatement();
    }

    /**
     * @group structure
     * @group exception
     */
    public function testAlterStatementException()
    {
        $databaseIndex = new MysqlDatabaseIndex('primary', ['id'], true);
        $this->expectException(TablenameHasNotDefinedException::class);
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
     * @group        structure
     * @group        exception
     *
     * @dataProvider dataProviderStatements
     *
     * @param string  $expected
     * @param string  $expectedAlter
     * @param string  $name
     * @param array   $columns
     * @param boolean $unique
     *
     * @throws \Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException
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
        $statement = $databaseIndex->deleteStatement();
        $this->assertEquals('ALTER TABLE `activite` DROP ' . $expectedAlter . ';', $statement);
    }

    /**
     * @group structure
     * @group collate
     */
    public function testToArray()
    {
        $databaseIndex = new MysqlDatabaseIndex('idx_primaire', ['id'], true);
        $databaseIndex->setTable('activite');
        $statement = $databaseIndex->toArray();
        $expected = [
            'table' => 'activite',
            'name' => 'idx_primaire',
            'unique' => true,
            'columns' => ['id'],
        ];
        $this->assertEquals($expected, $statement);

    }
}
