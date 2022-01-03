<?php

namespace Starkerxp\DatabaseChecker\Tests\Factory;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Factory\JsonDatabaseFactory;
use Starkerxp\DatabaseChecker\Factory\MysqlDatabaseFactory;

class MysqlDatabaseFactoryTest extends TestCase
{
    /**
     * @group factory
     */
    public function testGenerateTableSinceJson(): void
    {
        $factoryMysqlDatabase = new MysqlDatabaseFactory($this->mockMysqlRepository(), 'myTestDatabase');
        $export = $factoryMysqlDatabase->generate();
        $expectedJson = [
            'tables' => [
                'activite' => [
                    'indexes' => [
                        ['name' => 'dateenr', 'columns' => ['dateenr']],
                        ['name' => 'idclient', 'columns' => ['agence']],
                        ['name' => 'idlca', 'columns' => ['idann']],
                        ['name' => 'typeaction', 'columns' => ['typeaction']],
                    ],
                    'primary' => ['id'],
                    'fulltexts' => [
                        ['name' => 'fulltext_valeur', 'columns' => ['valeur']],
                    ],
                    'uniques' => [
                        ['name' => 'unq_idnego', 'columns' => ['idnego']],
                    ],
                    'columns' => [
                        'id' => ['type' => 'int', 'length' => '255', 'extra' => 'auto_increment'],
                        'idann' => ['type' => 'int', 'length' => '11'],
                        'dateenr' => ['type' => 'datetime'],
                        'agence' => ['type' => 'int', 'length' => '11'],
                        'idnego' => ['type' => 'int', 'length' => '11'],
                        'typeaction' => ['type' => 'varchar', 'length' => '255'],
                        'valeur' => ['type' => 'text'],
                    ],
                ],
            ],
        ];

        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($expectedJson));
        $expected = $factoryJsonDatabase->generate('myTestDatabase');
        $this->assertEquals($expected, $export);
    }

    private function mockMysqlRepository()
    {
        $oMock = $this->createMock('\Starkerxp\DatabaseChecker\Repository\MysqlRepository');
        $oMock->expects($this->any())
            ->method('getTablesStructure')
            ->with('myTestDatabase')
            ->willReturn(['activite']);

        $oMock->expects($this->any())
            ->method('fetchColumnsStructure')
            ->with('myTestDatabase', 'activite')
            ->willReturn([
                ['COLUMN_NAME' => 'id', 'DATA_TYPE' => 'int', 'COLUMN_TYPE' => 'int(255)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => 'auto_increment', 'COLLATION_NAME' => null],
                ['COLUMN_NAME' => 'idann', 'DATA_TYPE' => 'int', 'COLUMN_TYPE' => 'int(11)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '', 'COLLATION_NAME' => null],
                ['COLUMN_NAME' => 'dateenr', 'DATA_TYPE' => 'datetime', 'COLUMN_TYPE' => 'datetime', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '', 'COLLATION_NAME' => null],
                ['COLUMN_NAME' => 'agence', 'DATA_TYPE' => 'int', 'COLUMN_TYPE' => 'int(11)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '', 'COLLATION_NAME' => null],
                ['COLUMN_NAME' => 'typeaction', 'DATA_TYPE' => 'varchar', 'COLUMN_TYPE' => 'varchar(255)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '', 'COLLATION_NAME' => 'latin1_swedish_ci'],
                ['COLUMN_NAME' => 'valeur', 'DATA_TYPE' => 'text', 'COLUMN_TYPE' => 'text', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '', 'COLLATION_NAME' => 'latin1_swedish_ci'],
                ['COLUMN_NAME' => 'idnego', 'DATA_TYPE' => 'int', 'COLUMN_TYPE' => 'int(11)', 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => '', 'COLLATION_NAME' => null],
            ]);

        $oMock->expects($this->any())
            ->method('fetchIndexStructure')
            ->with('myTestDatabase', 'activite')
            ->willReturn([
                ['INDEX_NAME' => 'dateenr', 'COLUMN_NAME' => 'dateenr', 'NON_UNIQUE' => 1, 'NON_FULLTEXT' => 1],
                ['INDEX_NAME' => 'idclient', 'COLUMN_NAME' => 'agence', 'NON_UNIQUE' => 1, 'NON_FULLTEXT' => 1],
                ['INDEX_NAME' => 'idlca', 'COLUMN_NAME' => 'idann', 'NON_UNIQUE' => 1, 'NON_FULLTEXT' => 1],
                ['INDEX_NAME' => 'unq_idnego', 'COLUMN_NAME' => 'idnego', 'NON_UNIQUE' => 0, 'NON_FULLTEXT' => 1],
                ['INDEX_NAME' => 'PRIMARY', 'COLUMN_NAME' => 'id', 'NON_UNIQUE' => 0, 'NON_FULLTEXT' => 1],
                ['INDEX_NAME' => 'typeaction', 'COLUMN_NAME' => 'typeaction', 'NON_UNIQUE' => 1, 'NON_FULLTEXT' => 1],
                ['INDEX_NAME' => 'fulltext_valeur', 'COLUMN_NAME' => 'valeur', 'NON_UNIQUE' => 1, 'NON_FULLTEXT' => 0],
            ]);

        return $oMock;
    }

    /**
     * @group collate
     * @group factory
     */
    public function testCheckCollate(): void
    {
        $factoryMysqlDatabase = new MysqlDatabaseFactory($this->mockMysqlRepository(), 'myTestDatabase');
        $factoryMysqlDatabase->enableCheckCollate();
        $export = $factoryMysqlDatabase->generate();

        $expectedJson = [
            'tables' => [
                'activite' => [
                    'indexes' => [
                        ['name' => 'dateenr', 'columns' => ['dateenr']],
                        ['name' => 'idclient', 'columns' => ['agence']],
                        ['name' => 'idlca', 'columns' => ['idann']],
                        ['name' => 'typeaction', 'columns' => ['typeaction']],
                    ],
                    'fulltexts' => [
                        ['name' => 'fulltext_valeur', 'columns' => ['valeur']],
                    ],
                    'uniques' => [
                        ['name' => 'unq_idnego', 'columns' => ['idnego']],
                    ],
                    'primary' => ['id'],
                    'columns' => [
                        'id' => ['type' => 'int', 'length' => '255', 'extra' => 'auto_increment'],
                        'idann' => ['type' => 'int', 'length' => '11'],
                        'dateenr' => ['type' => 'datetime'],
                        'agence' => ['type' => 'int', 'length' => '11'],
                        'typeaction' => ['type' => 'varchar', 'length' => '255', 'collate' => 'latin1_swedish_ci'],
                        'valeur' => ['type' => 'text', 'collate' => 'latin1_swedish_ci'],
                        'idnego' => ['type' => 'int', 'length' => '11'],
                    ],
                ],
            ],
        ];

        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($expectedJson));
        $expected = $factoryJsonDatabase->generate('myTestDatabase');
        $this->assertEquals($expected->toArray(), $export->toArray());
    }

    /**
     * @group collate
     * @group factory
     */
    public function testCheckCollateDisable(): void
    {
        $factoryMysqlDatabase = new MysqlDatabaseFactory($this->mockMysqlRepository(), 'myTestDatabase');
        $export = $factoryMysqlDatabase->generate();

        $expectedJson = [
            'tables' => [
                'activite' => [
                    'indexes' => [
                        ['name' => 'dateenr', 'columns' => ['dateenr']],
                        ['name' => 'idclient', 'columns' => ['agence']],
                        ['name' => 'idlca', 'columns' => ['idann']],
                        ['name' => 'typeaction', 'columns' => ['typeaction']],
                    ],
                    'primary' => ['id'],
                    'fulltexts' => [
                        ['name' => 'fulltext_valeur', 'columns' => ['valeur']],
                    ],
                    'uniques' => [
                        ['name' => 'unq_idnego', 'columns' => ['idnego']],
                    ],
                    'columns' => [
                        'id' => ['type' => 'int', 'length' => '255', 'extra' => 'auto_increment'],
                        'idann' => ['type' => 'int', 'length' => '11'],
                        'dateenr' => ['type' => 'datetime'],
                        'agence' => ['type' => 'int', 'length' => '11'],
                        'typeaction' => ['type' => 'varchar', 'length' => '255'],
                        'valeur' => ['type' => 'text'],
                        'idnego' => ['type' => 'int', 'length' => '11'],
                    ],
                ],
            ],
        ];

        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($expectedJson));
        $expected = $factoryJsonDatabase->generate('myTestDatabase');

        $this->assertEquals($expected->toArray(), $export->toArray());
    }

    /**
     * @group factory
     */
    public function testGenerateEnumTableSinceJson(): void
    {
        $factoryMysqlDatabase = new MysqlDatabaseFactory($this->mockMysqlRepositoryForEnum(), 'myTestDatabase');
        $export = $factoryMysqlDatabase->generate();
        $expectedJson = [
            'tables' => [
                'activite' => [
                    'indexes' => [],
                    'columns' => [
                        'id' => ['type' => "enum('0','1')", 'length' => null, 'extra' => null],
                    ],
                ],
            ],
        ];

        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($expectedJson));
        $expected = $factoryJsonDatabase->generate('myTestDatabase');

        $this->assertEquals($expected->toArray(), $export->toArray());
    }

    private function mockMysqlRepositoryForEnum()
    {
        $oMock = $this->createMock('\Starkerxp\DatabaseChecker\Repository\MysqlRepository');
        $oMock->expects($this->any())
            ->method('getTablesStructure')
            ->with('myTestDatabase')
            ->willReturn(['activite']);

        $oMock->expects($this->any())
            ->method('fetchColumnsStructure')
            ->with('myTestDatabase', 'activite')
            ->willReturn([
                ['COLUMN_NAME' => 'id', 'DATA_TYPE' => 'enum', 'COLUMN_TYPE' => "enum('0','1')", 'IS_NULLABLE' => 'NO', 'COLUMN_DEFAULT' => null, 'EXTRA' => null, 'COLLATION_NAME' => null],
            ]);

        $oMock->expects($this->any())
            ->method('fetchIndexStructure')
            ->with('myTestDatabase', 'activite')
            ->willReturn([]);

        return $oMock;
    }
}
