<?php

namespace Starkerxp\DatabaseChecker\Tests\Checker;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Checker\MysqlDatabaseCheckerService;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabase;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;

class MysqlDatabaseCheckerServiceTest extends TestCase
{
    /**
     * @group checker
     */
    public function testGenerateAlterStatement(): void
    {
        $table = new MysqlDatabaseTable('activite');
        $table->setCollate('dd');
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

        $database = new MysqlDatabase('actual');
        $database->addTable($table);
        $database->addTable($table2);

        $newDatabase = new MysqlDatabase('referal');
        $newDatabase->addTable($newTable);

        $service = new MysqlDatabaseCheckerService();
        $statements = $service->diff($database, $newDatabase);
        $this->assertNotNull($statements);
    }

    /**
     * @group checker
     */
    public function testCreateStatement(): void
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

        $database = new MysqlDatabase('actual');
        $newDatabase = new MysqlDatabase('referal');
        $newDatabase->addTable($newTable);

        $statements = $service->diff($database, $newDatabase);
        $this->assertNotNull($statements);
    }

    /**
     * @group checker
     */
    public function testCheckCasse(): void
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

        $database = new MysqlDatabase('actual');
        $database->addTable($table);
        $database->addTable($table2);
        $newDatabase = new MysqlDatabase('referal');
        $newDatabase->addTable($newTable);
        $service = new MysqlDatabaseCheckerService();
        $statements = $service->diff($database, $newDatabase);

        $this->assertEquals('ALTER TABLE `activite` ADD COLUMN `aGeNcEs` INT(11) NOT NULL ;', $statements[0]);
        $this->assertCount(1, $statements);
    }

    /**
     * @group checker
     * @group collate
     */
    public function testDisableCollate(): void
    {
        $table = new MysqlDatabaseTable('activite');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $column->setCollate('utf8_general_ci');
        $table->addColumn($column);

        $newTable = new MysqlDatabaseTable('activite');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $column->setCollate('latin1_iso_9777');
        $newTable->addColumn($column);
        $database = new MysqlDatabase('actual');
        $database->addTable($table);
        $newDatabase = new MysqlDatabase('referal');
        $newDatabase->addTable($newTable);
        $service = new MysqlDatabaseCheckerService();
        $statements = $service->diff($database, $newDatabase);

        $this->assertCount(0, $statements);
    }

    /**
     * @group checker
     * @group collate
     */
    public function testEnableCollate(): void
    {
        $table = new MysqlDatabaseTable('activite');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $column->setCollate('utf8_general_ci');
        $table->addColumn($column);
        $database = new MysqlDatabase('actual');
        $database->addTable($table);

        $newTable = new MysqlDatabaseTable('activite');
        $newTable->setCollate('latin1_iso_9777');
        $newColumn = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $newColumn->setCollate('canardwc');
        $newTable->addColumn($newColumn);

        $newDatabase = new MysqlDatabase('referal');
        $newDatabase->setCollate('latin1_iso_9777');
        $newDatabase->addTable($newTable);

        $service = new MysqlDatabaseCheckerService();
        $service->enableCheckCollate();
        $statements = $service->diff($database, $newDatabase);
        $this->assertEquals('ALTER DATABASE actual CHARACTER SET latin1 COLLATE latin1_iso_9777;', $statements[0]);
        $this->assertEquals('ALTER TABLE `activite` CONVERT TO CHARACTER SET latin1 COLLATE latin1_iso_9777;', $statements[1]);
        $this->assertEquals("ALTER TABLE `activite` CHANGE COLUMN `nom` `nom` VARCHAR(11) NOT NULL COLLATE 'canardwc';", $statements[2]);
        $this->assertCount(3, $statements);
    }

    /**
     * @group checker
     * @group engine
     */
    public function testDisableEngine(): void
    {
        $table = new MysqlDatabaseTable('activite');
        $table->setEngine('INNODB');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $table->addColumn($column);

        $newTable = new MysqlDatabaseTable('activite');
        $table->setEngine('MEMORY');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $newTable->addColumn($column);

        $database = new MysqlDatabase('actual');
        $database->addTable($table);
        $newDatabase = new MysqlDatabase('referal');
        $newDatabase->addTable($newTable);
        $service = new MysqlDatabaseCheckerService();
        $statements = $service->diff($database, $newDatabase);
        $this->assertCount(0, $statements);
    }

    /**
     * @group checker
     * @group engine
     */
    public function testEnableEngine(): void
    {
        $table = new MysqlDatabaseTable('activite');
        $table->setEngine('INNODB');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $table->addColumn($column);

        $newTable = new MysqlDatabaseTable('activite');
        $newTable->setEngine('MEMORY');
        $column = new MysqlDatabaseColumn('nom', 'varchar', '11', false, null, '');
        $newTable->addColumn($column);

        $database = new MysqlDatabase('actual');
        $database->addTable($table);
        $newDatabase = new MysqlDatabase('referal');
        $newDatabase->addTable($newTable);
        $service = new MysqlDatabaseCheckerService();
        $service->enableCheckEngine();
        $statements = $service->diff($database, $newDatabase);
        $this->assertEquals('ALTER TABLE `activite` ENGINE=MEMORY;', $statements[0]);
        $this->assertCount(1, $statements);
    }

    /**
     * @group checker
     * @group drop
     */
    public function testEnableDropColumn(): void
    {
        $database = new MysqlDatabase('actual');
        $table = new MysqlDatabaseTable('activite');
        $table->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $table->addColumn(new MysqlDatabaseColumn('valeur', 'TEXT', '', false, null, null));
        $table->addColumn(new MysqlDatabaseColumn('valeur2', 'TEXT', '', false, null, null));
        $table->addPrimary(['id']);
        $database->addTable($table);

        $newDatabase = new MysqlDatabase('referal');
        $newTable = new MysqlDatabaseTable('activite');
        $newTable->addColumn(new MysqlDatabaseColumn('id', 'INT', '255', false, null, 'auto_increment'));
        $newTable->addColumn(new MysqlDatabaseColumn('valeur', 'TEXT', '', false, null, null));
        $newDatabase->addTable($newTable);

        $service = new MysqlDatabaseCheckerService();
        $service->enableDropStatement();
        $statements = $service->diff($database, $newDatabase);

        $this->assertCount(2, $statements);
        $this->assertEquals('ALTER TABLE `activite` DROP PRIMARY KEY;', $statements[0]);
        $this->assertEquals('ALTER TABLE `activite` DROP COLUMN `valeur2`;', $statements[1]);
    }
}
