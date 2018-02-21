<?php

namespace Starkerxp\DatabaseChecker\Tests\Structure;

use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;
use PHPUnit\Framework\TestCase;

class MysqlDatabaseTableTest extends TestCase
{

    public function testException()
    {

        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException");
        new MysqlDatabaseTable(null);
    }

    public function testColumnsCollection()
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
        $this->assertCount(0, $databaseTable->getColumns());
    }

    public function testIndexesCollection()
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

    public function testCreateStatement()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $databaseTable->addPrimary(['id']);
        $statements = $databaseTable->createStatement();
        $this->assertCount(3, $statements);
    }

    public function testCreateStatementWithoutColumn()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException");
        $statements = $databaseTable->createStatement();
    }


    public function testAlterStatement()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $this->expectException("\RuntimeException");
        $databaseTable->alterStatement();
    }

}
