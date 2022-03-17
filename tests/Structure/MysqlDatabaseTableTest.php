<?php

namespace LBIGroupDataBaseChecker\Test\Tests\Structure;

use PHPUnit\Framework\TestCase;
use LBIGroupDataBaseChecker\Test\Exception\TableHasNotColumnException;
use LBIGroupDataBaseChecker\Test\Exception\TablenameHasNotDefinedException;
use LBIGroupDataBaseChecker\Test\Structure\MysqlDatabaseColumn;
use LBIGroupDataBaseChecker\Test\Structure\MysqlDatabaseTable;

class MysqlDatabaseTableTest extends TestCase
{
    /**
     * @group structure
     * @group exception
     */
    public function testException(): void
    {
        $this->expectException(TablenameHasNotDefinedException::class);
        new MysqlDatabaseTable(null);
    }

    /**
     * @group structure
     */
    public function testColumnsCollection(): void
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $databaseTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $columns = $databaseTable->getColumns();
        $this->assertCount(1, $databaseTable->getColumns());
        $this->assertEquals('activites', $columns['id']->getTable());
        $databaseTable->removeColumn('id2');
        $this->assertCount(1, $databaseTable->getColumns());
        $databaseTable->removeColumn('id');
        $this->expectException(TableHasNotColumnException::class);
        $this->assertCount(0, $databaseTable->getColumns());
    }

    /**
     * @group structure
     */
    public function testIndexesCollection(): void
    {
        $databaseTable = new MysqlDatabaseTable('activites');

        $databaseTable->addIndex(['id']);
        $this->assertCount(1, $databaseTable->getIndexes());
        $idxIndex = $databaseTable->getIndex('IDX_' . md5('id'));
        $this->assertNotNull($idxIndex);
        $this->assertEquals(0, $idxIndex->isUnique());
        $this->assertEquals(0, $idxIndex->isPrimary());

        $databaseTable->addPrimary(['id']);
        $this->assertCount(2, $databaseTable->getIndexes());
        $primaryIndex = $databaseTable->getIndex('PRIMARY');
        $this->assertNotNull($primaryIndex);
        $this->assertEquals(1, $primaryIndex->isUnique());
        $this->assertEquals(1, $primaryIndex->isPrimary());

        $databaseTable->addUnique(['id']);
        $this->assertCount(3, $databaseTable->getIndexes());
        $uniqueIndex = $databaseTable->getIndex('UNI_' . md5('id'));
        $this->assertNotNull($primaryIndex);
        $this->assertEquals(1, $uniqueIndex->isUnique());
        $this->assertEquals(0, $uniqueIndex->isPrimary());
    }

    /**
     * @group structure
     */
    public function testCreateStatement(): void
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $databaseTable->addPrimary(['id']);
        $statements = $databaseTable->createStatement();
        $this->assertCount(1, $statements);
        $this->assertEquals('CREATE TABLE IF NOT EXISTS `activites`(`id` INT(255) NOT NULL AUTO_INCREMENT,PRIMARY KEY (`id`));', $statements[0]);
    }

    /**
     * @group structure
     * @group exception
     */
    public function testCreateStatementWithoutColumnException(): void
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $this->expectException(TableHasNotColumnException::class);
        $databaseTable->createStatement();
    }

    /**
     * @group structure
     * @group collate
     */
    public function testAlterStatementWithCollate(): void
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->setDatabase('test');
        $databaseTable->setCollate('latin1_swedish_ci');
        $statements = $databaseTable->alterStatement();
        $this->assertCount(1, $statements);
        $this->assertEquals('ALTER TABLE `activites` CONVERT TO CHARACTER SET latin1 COLLATE latin1_swedish_ci;', $statements[0]);
    }

    /**
     * @group structure
     * @group collate
     */
    public function testCollate(): void
    {
        //  COLLATE 'latin1_swedish_ci'
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->setCollate('latin1_swedish_ci');
        $column = new MysqlDatabaseColumn('id', 'CHAR', '255', false, null, 'auto_increment');
        $column->setCollate('utf8_general_ci');
        $databaseTable->addColumn($column);
        $databaseTable->addPrimary(['id']);
        $statements = $databaseTable->createStatement();
        $this->assertCount(1, $statements);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `activites`(`id` CHAR(255) NOT NULL AUTO_INCREMENT COLLATE 'utf8_general_ci',PRIMARY KEY (`id`))COLLATE='latin1_swedish_ci';", $statements[0]);
    }

    /**
     * @group structure
     * @group collate
     */
    public function testCollateWithoutDefineTableCollate(): void
    {
        //  COLLATE 'latin1_swedish_ci'
        $databaseTable = new MysqlDatabaseTable('activites');
        $column = new MysqlDatabaseColumn('id', 'CHAR', '255', false, null, 'auto_increment');
        $column->setCollate('utf8_general_ci');
        $databaseTable->addColumn($column);
        $databaseTable->addPrimary(['id']);
        $statements = $databaseTable->createStatement();
        $this->assertCount(1, $statements);
        $this->assertEquals('CREATE TABLE IF NOT EXISTS `activites`(`id` CHAR(255) NOT NULL AUTO_INCREMENT,PRIMARY KEY (`id`));', $statements[0]);
    }

    /**
     * @group structure
     * @group exception
     */
    public function testAccessUnknowIndex(): void
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $this->expectException('\RuntimeException');
        $databaseTable->getIndex('chips');
    }

    /**
     * @group structure
     * @group exception
     */
    public function testCreateIndexesOnNotExistingColumnException(): void
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $databaseTable->addIndex(['id']);
        $databaseTable->addIndex(['id2']);
        $this->assertCount(2, $databaseTable->getIndexes());
        $this->assertCount(1, $databaseTable->createStatement());
    }

    public function testToArray(): void
    {
        $table = new MysqlDatabaseTable('activite');
        $table->setCollate('utf8_general_ci');
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $table->addPrimary(['id']);
        $table->addUnique(['id']);
        $table->addIndex(['id'], 'caramel');
        $statements = $table->toArray();
        $expected = [
            'activite' => [
                'columns' => [
                    'id' => [
                        'type' => 'INT',
                        'length' => '255',
                        'extra' => 'AUTO_INCREMENT',
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
                    ['name' => 'UNI_b80bb7740288fda1f201890375a60c8f', 'columns' => ['id']],
                ],
                'collate' => 'utf8_general_ci',
            ],
        ];

        $this->assertEquals($expected, $statements);
    }

    public function testToArrayWithoutIndexes(): void
    {
        $table = new MysqlDatabaseTable('login');
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $table->addPrimary(['id']);
        $statements = $table->toArray();

        $expected = [
            'login' => [
                'columns' => [
                    'id' => [
                        'type' => 'INT',
                        'length' => '255',
                        'extra' => 'AUTO_INCREMENT',
                        'nullable' => false,
                        'defaultValue' => null,
                        'collate' => null,
                    ],
                ],
                'primary' => ['id'],
            ],
        ];
        $this->assertEquals($expected, $statements);
    }

    public function testOverideColumn(): void
    {
        $table = new MysqlDatabaseTable('login');
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '11', false, null, 'auto_increment'));
        $table->addColumn(new MysqlDatabaseColumn('id', 'varchar', '255', false, null, null));
        $statements = $table->toArray();

        $expected = [
            'login' => [
                'columns' => [
                    'id' => [
                        'type' => 'VARCHAR',
                        'length' => '255',
                        'extra' => '',
                        'nullable' => false,
                        'defaultValue' => null,
                        'collate' => null,
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $statements);
    }

    public function testDeleteStatement(): void
    {
        $table = new MysqlDatabaseTable('login');
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '11', false, null, 'auto_increment'));
        $table->removeColumn('id');
        $this->expectException('\LBIGroupDataBaseChecker\Test\Exception\TableHasNotColumnException');
        $this->assertCount(0, $table->toArray());
    }
}
