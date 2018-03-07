<?php

namespace Starkerxp\DatabaseChecker\Tests\Checker;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Checker\MysqlDatabaseCheckerService;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;

class MysqlDatabaseCheckerServiceTest extends TestCase
{

    /**
     * @group checker
     */
    public function testA()
    {
        $table = new MysqlDatabaseTable('activite');
        $table->setCollate("dd");
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $table->addColumn(new MysqlDatabaseColumn('idann', 'INT', '11', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('dateenr', 'DATETIME', '', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('agence', 'INT', '11', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('idnego', 'INT', '11', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('typeaction', 'VARCHAR', '255', false, '', null));
        $table->addColumn(new MysqlDatabaseColumn('valeur', 'TEXT', '', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('valeur2', 'TEXT', '', false, null, null));
        $table->addPrimary(['id']);

        $table2 = new MysqlDatabaseTable('activite23');

        $newTable = new MysqlDatabaseTable('activite');
        $newTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '11', false, null, 'auto_increment'));
        $newTable->addColumn(new MysqlDatabaseColumn('idann', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('dateenr', 'DATETIME', '', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('agence', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('idnego', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('typeaction', 'VARCHAR', '255', false, '', null));
        $newTable->addColumn(new MysqlDatabaseColumn('valeur', 'TEXT', '', false, null, null));
        $newTable->addPrimary(['id']);

        $service = new MysqlDatabaseCheckerService();
        $modifications = $service->diff([$table, $table2], [$newTable,]);
        $this->assertNotNull($modifications);
    }

    /**
     * @group checker
     */
    public function testB()
    {
        $newTable = new MysqlDatabaseTable('activite');
        $newTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '11', false, null, 'auto_increment'));
        $newTable->addColumn(new MysqlDatabaseColumn('idann', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('dateenr', 'DATETIME', '', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('agence', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('idnego', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('typeaction', 'VARCHAR', '255', false, '', null));
        $newTable->addColumn(new MysqlDatabaseColumn('valeur', 'TEXT', '', false, null, null));
        $newTable->addPrimary(['id']);

        $service = new MysqlDatabaseCheckerService();
        $modifications = $service->diff([], [$newTable,]);
        $this->assertNotNull($modifications);
    }

    /**
     * @group checker
     */
    public function testCheckCasse()
    {
        $table = new MysqlDatabaseTable('activite');
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '11', false, null, 'auto_increment'));
        $table->addColumn(new MysqlDatabaseColumn('idann', 'INT', '11', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('dateenr', 'DATETIME', '', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('agence', 'INT', '11', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('aGeNcEs', 'INT', '11', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('idnego', 'INT', '11', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('typeaction', 'VARCHAR', '255', false, '', null));
        $table->addColumn(new MysqlDatabaseColumn('valeur', 'TEXT', '', false, null, null));
        $table->addPrimary(['id']);

        $table2 = new MysqlDatabaseTable('activite23');

        $newTable = new MysqlDatabaseTable('activite');
        $newTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '11', false, null, 'auto_increment'));
        $newTable->addColumn(new MysqlDatabaseColumn('idAnN', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('dateenr', 'DATETIME', '', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('agence', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('idnego', 'INT', '11', false, null, null));
        $newTable->addColumn(new MysqlDatabaseColumn('typeaction', 'VARCHAR', '255', false, '', null));
        $newTable->addColumn(new MysqlDatabaseColumn('valeur', 'TEXT', '', false, null, null));
        $newTable->addPrimary(['id']);

        $service = new MysqlDatabaseCheckerService();
        $statements = $service->diff([$table, $table2], [$newTable,]);
        $this->assertCount(1, $statements);
        $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `aGeNcEs` INT(11) NOT NULL ;', $statements[0]);
    }

    /**
     * @group checker
     * @group collate
     */
    public function testDisableCollate()
    {
        $table = new MysqlDatabaseTable('activite');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $column->setCollate('utf8_general_ci');
        $table->addColumn($column);

        $newTable = new MysqlDatabaseTable('activite');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $column->setCollate('latin1_iso_9777');
        $newTable->addColumn($column);

        $service = new MysqlDatabaseCheckerService();
        $service->
        $statements = $service->diff([$table,], [$newTable,]);
        $this->assertCount(0, $statements);
    }

    /**
     * @group checker
     * @group collate
     */
    public function testEnableCollate()
    {

    }

    /**
     * @group checker
     * @group engine
     */
    public function testDisableEngine()
    {

    }

    /**
     * @group checker
     * @group engine
     */
    public function testEnableEngine()
    {

    }

}
