<?php

namespace Starkerxp\DatabaseChecker\Tests\Factory;


use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Factory\JsonDatabaseFactory;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;

class JsonDatabaseFactoryTest extends TestCase
{

    /**
     * @group factory
     */
    public function testGenerateTableSinceJson()
    {
        $table = new MysqlDatabaseTable('activite');
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $table->addPrimary(['id']);
        $table->addUnique(['id']);
        $table->addIndex(['id'], 'caramel');

        $json = [
            'activite' => [
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
        ];
        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($json));
        $tableOut = $factoryJsonDatabase->generate();
        $this->assertEquals([$table], $tableOut);
    }

    /**
     * @group factory
     * @group exception
     */
    public function testGenerateEmptyTablenameSinceJsonException()
    {
        $json = [
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
        ];
        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($json));
        $this->expectException('\Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException');
        $factoryJsonDatabase->generate();
    }

    /**
     * @group factory
     * @group exception
     */
    public function testGenerateInvalidJsonException()
    {
        $factoryJsonDatabase = new JsonDatabaseFactory(null);
        $tableOut = $factoryJsonDatabase->generate();
        $this->assertEquals([], $tableOut);

        $factoryJsonDatabase = new JsonDatabaseFactory('"');
        $tableOut = $factoryJsonDatabase->generate();
        $this->assertEquals([], $tableOut);
    }

}
