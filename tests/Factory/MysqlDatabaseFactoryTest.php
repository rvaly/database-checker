<?php

namespace Starkerxp\DatabaseChecker\Tests\Factory;

use Starkerxp\DatabaseChecker\Factory\JsonDatabaseFactory;
use Starkerxp\DatabaseChecker\Factory\MysqlDatabaseFactory;
use PHPUnit\Framework\TestCase;

class MysqlDatabaseFactoryTest extends TestCase
{

    public function testGenerateTableSinceJson()
    {
        $factoryMysqlDatabase = new MysqlDatabaseFactory($this->mockMysqlRepository(), 'hektor2');
        $export = $factoryMysqlDatabase->generate();
        $expectedJson = [
            'activite' => [
                'indexes' => [
                    ['name' => 'dateenr', 'columns' => ['dateenr',],],
                    ['name' => 'idclient', 'columns' => ['agence',],],
                    ['name' => 'idlca', 'columns' => ['idann',],],
                    ['name' => 'idnego', 'columns' => ['idnego',],],
                    ['name' => 'typeaction', 'columns' => ['typeaction',],],
                ],
                'primary' => ['id',],
                'columns' => [
                    'id' => ['type' => 'int', 'length' => '255', 'extra' => 'auto_increment',],
                    'idann' => ['type' => 'int', 'length' => '11',],
                    'dateenr' => ['type' => 'datetime',],
                    'agence' => ['type' => 'int', 'length' => '11',],
                    'idnego' => ['type' => 'int', 'length' => '11',],
                    'typeaction' => ['type' => 'varchar', 'length' => '255',],
                    'valeur' => ['type' => 'text',],
                ],
            ],
        ];

        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($expectedJson));
        $expected = $factoryJsonDatabase->generate();
        $this->assertEquals($expected, $export);
    }

    private function mockMysqlRepository()
    {
        $oMock = $this->createMock('\Starkerxp\DatabaseChecker\Repository\MysqlRepository');
        $oMock->expects($this->any())
            ->method('getTablesStructure')
            ->with('hektor2')
            ->willReturn(['activite']);

        $oMock->expects($this->any())
            ->method('fetchColumnsStructure')
            ->with('hektor2', 'activite')
            ->willReturn([
                ['COLUMN_NAME' => 'id', 'DATA_TYPE' => 'int', 'COLUMN_TYPE' => 'int(255)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => 'auto_increment',],
                ['COLUMN_NAME' => 'idann', 'DATA_TYPE' => 'int', 'COLUMN_TYPE' => 'int(11)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '',],
                ['COLUMN_NAME' => 'dateenr', 'DATA_TYPE' => 'datetime', 'COLUMN_TYPE' => 'datetime', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '',],
                ['COLUMN_NAME' => 'agence', 'DATA_TYPE' => 'int', 'COLUMN_TYPE' => 'int(11)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '',],
                ['COLUMN_NAME' => 'idnego', 'DATA_TYPE' => 'int', 'COLUMN_TYPE' => 'int(11)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '',],
                ['COLUMN_NAME' => 'typeaction', 'DATA_TYPE' => 'varchar', 'COLUMN_TYPE' => 'varchar(255)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '',],
                ['COLUMN_NAME' => 'valeur', 'DATA_TYPE' => 'text', 'COLUMN_TYPE' => 'text', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '',],
            ]);

        $oMock->expects($this->any())
            ->method('fetchIndexStructure')
            ->with('hektor2', 'activite')
            ->willReturn([
                ['INDEX_NAME' => 'dateenr', 'COLUMN_NAME' => 'dateenr', 'NON_UNIQUE' => 1,],
                ['INDEX_NAME' => 'idclient', 'COLUMN_NAME' => 'agence', 'NON_UNIQUE' => 1,],
                ['INDEX_NAME' => 'idlca', 'COLUMN_NAME' => 'idann', 'NON_UNIQUE' => 1,],
                ['INDEX_NAME' => 'idnego', 'COLUMN_NAME' => 'idnego', 'NON_UNIQUE' => 1,],
                ['INDEX_NAME' => 'PRIMARY', 'COLUMN_NAME' => 'id', 'NON_UNIQUE' => 0,],
                ['INDEX_NAME' => 'typeaction', 'COLUMN_NAME' => 'typeaction', 'NON_UNIQUE' => 1,],
            ]);

        return $oMock;
    }
}
