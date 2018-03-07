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
    public function testGenerateAlterStatement()
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
    public function testCreateStatement()
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
        $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `aGeNcEs` INT(11) NOT NULL ;', $statements[0]);
        $this->assertCount(1, $statements);
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
        $statements = $service->diff([$table,], [$newTable,]);
        $this->assertCount(0, $statements);
    }

    /**
     * @group checker
     * @group collate
     */
    public function testEnableCollate()
    {
        $table = new MysqlDatabaseTable('activite');
        $table->setDatabase('test');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $column->setCollate('utf8_general_ci');
        $table->addColumn($column);

        $newTable = new MysqlDatabaseTable('activite');
        $newTable->setDatabase('test');
        $newTable->setCollate('latin1_iso_9777');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $column->setCollate('canardwc');
        $newTable->addColumn($column);

        $service = new MysqlDatabaseCheckerService();
        $service->enableCheckCollate();
        $statements = $service->diff([$table,], [$newTable,]);
        $this->assertEquals('ALTER DATABASE test CHARACTER SET latin1 COLLATE latin1_iso_9777;', $statements[0]);
        $this->assertEquals('ALTER TABLE `activite` CONVERT TO CHARACTER SET latin1 COLLATE latin1_iso_9777;', $statements[1]);
        $this->assertEquals("ALTER TABLE `activite` CHANGE COLUMN `nom` `nom` VARCHAR(11) NOT NULL COLLATE 'canardwc';", $statements[2]);
        $this->assertCount(3, $statements);
    }

    /**
     * @group checker
     * @group engine
     */
    public function testDisableEngine()
    {
        $table = new MysqlDatabaseTable('activite');
        $table->setEngine('INNODB');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $table->addColumn($column);

        $newTable = new MysqlDatabaseTable('activite');
        $table->setEngine('MEMORY');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $newTable->addColumn($column);

        $service = new MysqlDatabaseCheckerService();
        $statements = $service->diff([$table,], [$newTable,]);
        $this->assertCount(0, $statements);
    }


    /**
     * @group checker
     * @group engine
     */
    public function testEnableEngine()
    {
        $table = new MysqlDatabaseTable('activite');
        $table->setEngine('INNODB');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $table->addColumn($column);

        $newTable = new MysqlDatabaseTable('activite');
        $newTable->setEngine('MEMORY');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $newTable->addColumn($column);

        $service = new MysqlDatabaseCheckerService();
        $service->enableCheckEngine();
        $statements = $service->diff([$table,], [$newTable,]);
        $this->assertEquals('ALTER TABLE `activite` ENGINE=MEMORY;', $statements[0]);
        $this->assertCount(1, $statements);
    }

}
