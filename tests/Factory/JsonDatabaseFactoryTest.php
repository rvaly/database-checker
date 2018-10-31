<?php

namespace Starkerxp\DatabaseChecker\Tests\Factory;


use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Exception\JsonInvalidFormatException;
use Starkerxp\DatabaseChecker\Factory\JsonDatabaseFactory;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;
use Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException;

class JsonDatabaseFactoryTest extends TestCase
{

    /**
     * @group factory
     */
    public function testGenerateTableSinceJson(): void
    {
        $table = new MysqlDatabaseTable('activite');
        $column = new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment');
        $column->setCollate('utf8_general_ci');
        $table->addColumn($column);
        $table->addPrimary(['id']);
        $table->addUnique(['id']);
        $table->addIndex(['id'], 'caramel');
        $table->setCollate('utf8_general_ci');

        $json = [
            'tables' => [
                'activite' => [
                    'columns' => [
                        'id' => ['type' => 'INT', 'length' => '255', 'extra' => 'auto_increment', 'collate' => 'utf8_general_ci'],
                    ],
                    'indexes' => [
                        ['name' => 'caramel', 'columns' => ['id']],
                    ],
                    'primary' => ['id'],
                    'uniques' => [
                        ['columns' => ['id']],
                    ],
                    'collate' => 'utf8_general_ci',
                ],
            ],
        ];
        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($json));
        $databaseOut = $factoryJsonDatabase->generate('myTestDatabase');
        $this->assertEquals($table->toArray(), $databaseOut->toArray()['tables']);
    }

    /**
     * @group factory
     * @group exception
     */
    public function testGenerateEmptyTablenameSinceJsonException(): void
    {
        $json = [
            'tables' => [
                '' => [
                    'columns' => [
                        'id' => ['type' => 'INT', 'length' => '255', 'extra' => 'auto_increment',],
                    ],
                    'indexes' => [
                        ['name' => 'caramel', 'columns' => ['id']],
                    ],
                    'primary' => ['id'],
                    'uniques' => [
                        ['columns' => ['id']],
                    ],
                ],
            ],
        ];
        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($json));
        $this->expectException(TablenameHasNotDefinedException::class);
        $factoryJsonDatabase->generate('myTestDatabase');
    }

    /**
     * @group factory
     * @group exception
     */
    public function testGenerateNullJsonException(): void
    {
        $factoryJsonDatabase = new JsonDatabaseFactory(null);
        $this->expectException(JsonInvalidFormatException::class);
        $factoryJsonDatabase->generate('myTestDatabase');
    }

    /**
     * @group factory
     * @group exception
     */
    public function testGenerateInvalidJsonException(): void
    {
        $factoryJsonDatabase = new JsonDatabaseFactory('"');
        $this->expectException(JsonInvalidFormatException::class);
        $factoryJsonDatabase->generate('myTestDatabase');

    }

}
