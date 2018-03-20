<?php

namespace Starkerxp\DatabaseChecker\Tests\Structure;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Exception\DatabaseHasNotDefinedException;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabase;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;

class MysqlDatabaseTest extends TestCase
{
    /**
     * @group structure
     * @group exception
     */
    public function testException()
    {
        $this->expectException(DatabaseHasNotDefinedException::class);
        new MysqlDatabase(null);
    }

    /**
     * @group structure
     */
    public function testCreateStatement()
    {
        $database = new MysqlDatabase('activites');
        $statements = $database->createStatement();
        $this->assertCount(1, $statements);
        $this->assertEquals('CREATE DATABASE IF NOT EXISTS `activites`;', $statements[0]);
    }


    /**
     * @group structure
     * @group exception
     */
    public function testAlterStatement()
    {
        $databaseTable = new MysqlDatabase('activites');
        $statements = $databaseTable->alterStatement();
        $this->assertCount(0, $statements);
    }

    public function testToArray()
    {
        $database = new MysqlDatabase('hektor');
        $database->setCollate('utf8_general_ci');
        $table = new MysqlDatabaseTable('activite');
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $table->addPrimary(['id']);
        $table->addUnique(['id']);
        $table->addIndex(['id'], 'caramel');
        $database->addTable($table);
        $statements = $database->toArray();

        $expected = [
            'tables' => [
                'activite' => [
                    'columns' => [
                        'id' => [
                            'type' => 'INT',
                            'length' => '255',
                            'extra' => 'AUTO_INCREMENT',
                            'name' => 'id',
                            'nullable' => false,
                            'defaultValue' => null,
                            'collate' => null,
                        ],
                    ],
                    'indexes' => [
                        ['name' => 'caramel', 'columns' => ['id']],
                    ],
                    'primary' => ['id'],
                    'uniques' => [
                        ['name' => 'UNI_b80bb7740288fda1f201890375a60c8f', 'columns' => ['id'],],
                    ],
                    'collate' => 'utf8_general_ci',
                ],
            ],
            'collate' => 'utf8_general_ci',
        ];
        $this->assertEquals($expected, $statements);
    }

}
