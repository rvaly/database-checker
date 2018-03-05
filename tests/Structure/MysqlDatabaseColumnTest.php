<?php

namespace Starkerxp\DatabaseChecker\Tests\Structure;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;

class MysqlDatabaseColumnTest extends TestCase
{

    /**
     * @group structure
     * @group mutator
     */
    public function testMutator()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment');
        $this->assertEquals('int', $databaseColumn->getType());
        $this->assertEquals('INT(255)', $databaseColumn->getColonneType());
    }

    /**
     * @group structure
     * @group exception
     */
    public function testException()
    {
        $this->expectException("\RuntimeException");
        new MysqlDatabaseColumn('', 'INT', '255', false, null, 'auto_increment');
    }

    /**
     * @group structure
     * @group exception
     */
    public function testCreateStatementException()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment');
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException");
        $databaseColumn->createStatement();
    }

    /**
     * @group structure
     * @group exception
     */
    public function testAlterStatementException()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment');
        $this->expectException("\Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException");
        $databaseColumn->alterStatement();
    }

    /**
     * @group structure
     * @group post
     */
    public function testStatements()
    {
        $types = ['int', 'mediumint', 'tinyint', 'smallint', 'binary', 'varchar', 'bigint', 'char', 'float'];
        foreach ($types as $type) {
            $databaseColumn = new MysqlDatabaseColumn('id', $type, '255', false, null, null);
            $databaseColumn->setTable('activite');
            $statement = $databaseColumn->createStatement();
            $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `id` ' . strtoupper($type) . '(255) NOT NULL ;', $statement[0]);
            $statement = $databaseColumn->alterStatement();
            $this->assertEquals('ALTER TABLE `activite` CHANGE COLUMN `id` `id` ' . strtoupper($type) . '(255) NOT NULL ;', $statement[0]);
        }

        $types = ['text', 'blob'];
        foreach ($types as $type) {
            $databaseColumn = new MysqlDatabaseColumn('id', $type, '255', false, null, null);
            $databaseColumn->setTable('activite');
            $statement = $databaseColumn->createStatement();
            $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `id` ' . strtoupper($type) . ' NOT NULL ;', $statement[0]);
            $statement = $databaseColumn->alterStatement();
            $this->assertEquals('ALTER TABLE `activite` CHANGE COLUMN `id` `id` ' . strtoupper($type) . ' NOT NULL ;', $statement[0]);
        }
    }

    /**
     * @group structure
     * @group optimize
     */
    public function testOptimizeBooleanEnum()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'ENUM(\'0\', \'1\')', '255', false, null, 'auto_increment');
        $databaseColumn->setTable('activite');
        $databaseColumn->optimizeType();
        $statement = $databaseColumn->createStatement();
        $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `id` TINYINT(1) NOT NULL AUTO_INCREMENT ;', $statement[0]);
        $statement = $databaseColumn->alterStatement();
        $this->assertEquals('ALTER TABLE `activite` CHANGE COLUMN `id` `id` TINYINT(1) NOT NULL AUTO_INCREMENT ;', $statement[0]);
    }

    /**
     * @group structure
     * @group collate
     */
    public function testCollate()
    {
        $types = ['int', 'mediumint', 'tinyint', 'smallint', 'binary', 'bigint', 'float'];
        foreach ($types as $type) {
            $databaseColumn = new MysqlDatabaseColumn('id', $type, '255', false, null, null);
            $databaseColumn->setTable('activite');
            $databaseColumn->setCollate('latin1_swedish_ci');
            $statement = $databaseColumn->createStatement();
            $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `id` ' . strtoupper($type) . '(255) NOT NULL ;', $statement[0]);
            $statement = $databaseColumn->alterStatement();
            $this->assertEquals('ALTER TABLE `activite` CHANGE COLUMN `id` `id` ' . strtoupper($type) . '(255) NOT NULL ;', $statement[0]);
        }

        $types = ['varchar', 'text', 'char',];
        foreach ($types as $type) {
            $databaseColumn = new MysqlDatabaseColumn('id', $type, '255', false, null, null);
            $databaseColumn->setTable('activite');
            $databaseColumn->setCollate('latin1_swedish_ci');
            $typeExpected = strtoupper($type) . ($type == 'text' ? '' : '(255)');
            $statement = $databaseColumn->createStatement();
            $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `id` ' . $typeExpected . ' NOT NULL COLLATE \'latin1_swedish_ci\';', $statement[0]);
            $statement = $databaseColumn->alterStatement();
            $this->assertEquals('ALTER TABLE `activite` CHANGE COLUMN `id` `id` ' . $typeExpected . ' NOT NULL COLLATE \'latin1_swedish_ci\';', $statement[0]);
        }
    }

    /**
     * @group structure
     * @group collate
     */
    public function testToArray()
    {
        $databaseColumn = new MysqlDatabaseColumn('id', 'int', '255', false, null, null);
        $databaseColumn->setTable('activite');
        $databaseColumn->setCollate('latin1_swedish_ci');
        $statement = $databaseColumn->toArray();
        $expected = [
            'type' => 'int',
            'length' => '255',
            'extra' => null,
            'table' => 'activite',
            'name' => 'id',
            'nullable' => false,
            'defaultValue' => null,
            'collate' => 'latin1_swedish_ci',
        ];
        $this->assertEquals($expected, $statement);

    }
}
