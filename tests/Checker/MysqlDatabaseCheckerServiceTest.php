<?php

namespace Starkerxp\DatabaseChecker\Tests\Checker;

use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Checker\MysqlDatabaseCheckerService;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;

class MysqlDatabaseCheckerServiceTest extends TestCase
{

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
}
