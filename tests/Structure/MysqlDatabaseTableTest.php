<?php

namespace Starkerxp\DatabaseChecker\Tests\Structure;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;

class MysqlDatabaseTableTest extends TestCase
{
    /**
     * @group structure
     * @group exception
     */
    public function testException()
    {
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException");
        new MysqlDatabaseTable(null);
    }

    /**
     * @group structure
     */
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

    /**
     * @group structure
     */
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

    /**
     * @group structure
     */
    public function testCreateStatement()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $databaseTable->addPrimary(['id']);
        $statements = $databaseTable->createStatement();
        $this->assertCount(1, $statements);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `activites`(`id` INT(255) NOT NULL auto_increment,PRIMARY KEY (`id`));", $statements[0]);
    }

    /**
     * @group structure
     * @group exception
     */
    public function testCreateStatementWithoutColumnException()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException");
        $databaseTable->createStatement();
    }

    /**
     * @group structure
     * @group exception
     */
    public function testAlterStatementException()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $this->expectException("\RuntimeException");
        $databaseTable->alterStatement();
    }

    /**
     * @group structure
     * @group collate
     */
    public function testAlterStatementWithCollate()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->setCollate('latin1_swedish_ci');
        $statements = $databaseTable->alterStatement();
        $this->assertCount(1, $statements);
        $this->assertEquals("ALTER TABLE `activites` COLLATE='latin1_swedish_ci';", $statements[0]);
    }

    /**
     * @group structure
     * @group collate
     */
    public function testCollate()
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
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `activites`(`id` CHAR(255) NOT NULL auto_increment COLLATE 'utf8_general_ci',PRIMARY KEY (`id`))COLLATE='latin1_swedish_ci';", $statements[0]);
    }

    /**
     * @group structure
     * @group collate
     */
    public function testCollateWithoutDefineTableCollate()
    {
        //  COLLATE 'latin1_swedish_ci'
        $databaseTable = new MysqlDatabaseTable('activites');
        $column = new MysqlDatabaseColumn('id', 'CHAR', '255', false, null, 'auto_increment');
        $column->setCollate('utf8_general_ci');
        $databaseTable->addColumn($column);
        $databaseTable->addPrimary(['id']);
        $statements = $databaseTable->createStatement();
        $this->assertCount(1, $statements);
        $this->assertEquals("CREATE TABLE IF NOT EXISTS `activites`(`id` CHAR(255) NOT NULL auto_increment,PRIMARY KEY (`id`));", $statements[0]);
    }

    /**
     * @group structure
     * @group exception
     */
    public function testAccessUnknowIndex()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $this->expectException('\RuntimeException');
        $databaseTable->getIndex('chips');
    }

    /**
     * @group structure
     * @group exception
     */
    public function testCreateIndexesOnNotExistingColumnException()
    {
        $databaseTable = new MysqlDatabaseTable('activites');
        $databaseTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $databaseTable->addIndex(['id']);
        $databaseTable->addIndex(['id2']);
        $this->assertCount(2, $databaseTable->getIndexes());
        $this->assertCount(1, $databaseTable->createStatement());
    }

}
