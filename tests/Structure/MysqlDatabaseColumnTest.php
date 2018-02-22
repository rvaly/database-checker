<?php

namespace Starkerxp\DatabaseChecker\Tests\Structure;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;

class MysqlDatabaseColumnTest extends TestCase
{

    public function testMutateurs()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment');
        $this->assertEquals('int', $databaseColumn->getType());
        $this->assertEquals('INT(255)', $databaseColumn->getColonneType());
    }

    public function testException()
    {
        $this->expectException("\RuntimeException");
        new MysqlDatabaseColumn('', 'INT', '255', false, null, 'auto_increment');
    }

    public function testCreateStatementException()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment');
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException");
        $databaseColumn->createStatement();
    }

    public function testAlterStatementException()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment');
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException");
        $databaseColumn->alterStatement();
    }

    public function testStatements()
    {
        $types = ['int', 'mediumint', 'tinyint', 'smallint', 'binary', 'varchar', 'bigint', 'char', 'float'];
        foreach ($types as $type) {
            $databaseColumn = new MysqlDatabaseColumn('id', $type, '255', false, null, null);
            $databaseColumn->setTable('activite');
            $statement = $databaseColumn->createStatement();
            $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `id` ' . strtoupper($type) . '(255) NOT NULL ;', $statement[0]);
            $statement = $databaseColumn->alterStatement();
            $this->assertEquals('ALTER TABLE `activite` CHANGE COLUMN `id` `id` ' .  strtoupper($type) . '(255) NOT NULL ;', $statement[0]);
        }

        $types = ['text', 'blob'];
        foreach ($types as $type) {
            $databaseColumn = new MysqlDatabaseColumn('id', $type, '255', false, null, null);
            $databaseColumn->setTable('activite');
            $statement = $databaseColumn->createStatement();
            $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `id` ' .  strtoupper($type) . ' NOT NULL ;', $statement[0]);
            $statement = $databaseColumn->alterStatement();
            $this->assertEquals('ALTER TABLE `activite` CHANGE COLUMN `id` `id` ' .  strtoupper($type) . ' NOT NULL ;', $statement[0]);
        }
    }

    public function testOptimizeBooleanEnum()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'ENUM(\'0\', \'1\')', '255', false, null, 'auto_increment');
        $databaseColumn->setTable('activite');
        $databaseColumn->optimizeType();
        $statement = $databaseColumn->createStatement();
        $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `id` TINYINT(1) NOT NULL auto_increment;', $statement[0]);
        $statement = $databaseColumn->alterStatement();
        $this->assertEquals('ALTER TABLE `activite` CHANGE COLUMN `id` `id` TINYINT(1) NOT NULL auto_increment;', $statement[0]);
    }
}
