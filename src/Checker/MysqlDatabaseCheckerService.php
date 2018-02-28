<?php

namespace Starkerxp\DatabaseChecker\Checker;


use Starkerxp\DatabaseChecker\Exception\ColumnNotExistException;
use Starkerxp\DatabaseChecker\Exception\NotCompareDifferentColumnException;
use Starkerxp\DatabaseChecker\Exception\NotCompareDifferentTableException;
use Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException;
use Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException;
use Starkerxp\DatabaseChecker\Exception\TableNotExistException;
use Starkerxp\DatabaseChecker\LoggerTrait;
use Starkerxp\DatabaseChecker\Structure\DatabaseInterface;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;

class MysqlDatabaseCheckerService
{
    use LoggerTrait;
    /**
     * @var boolean
     */
    private $checkCollate;

    /**
     * @var boolean
     */
    private $checkCasse;

    /**
     * @param MysqlDatabaseTable[] $tables
     * @param MysqlDatabaseTable[] $newTables
     *
     * @return array
     */
    public function diff(array $tables, array $newTables)
    {
        $modificationsBetweenTable = [];

        // check Table
        foreach ($tables as $table) {
            try {
                $newTable = $this->getTable($table, $newTables);
                $modificationsBetweenTable[] = $this->checkTable($table, $newTable);
            } catch (TableNotExistException $e) {
                //@todo Drop statement.
                //@todo config for generate create or drop.
                $modificationsBetweenTable[] = $this->createStatement($table);
                continue;
            } catch (NotCompareDifferentTableException $e) {
                continue;
            }
        }

        foreach ($newTables as $newTable) {
            try {
                $this->getTable($newTable, $tables);
            } catch (TableNotExistException $e) {
                $modificationsBetweenTable[] = $this->createStatement($newTable);
                continue;
            }
        }

        return $this->formatStatements($modificationsBetweenTable);
    }

    /**
     * @param MysqlDatabaseTable   $table
     * @param MysqlDatabaseTable[] $newTables
     *
     * @return mixed
     * @throws TableNotExistException
     */
    private function getTable($table, array $newTables)
    {
        foreach ($newTables as $newTable) {
            if ($table->getTable() == $newTable->getTable()) {
                return $newTable;
            }
        }
        throw new TableNotExistException('');
    }

    /**
     * @param MysqlDatabaseTable $table
     * @param MysqlDatabaseTable $newTable
     *
     * @return array
     *
     * @throws NotCompareDifferentTableException
     */
    private function checkTable(MysqlDatabaseTable $table, MysqlDatabaseTable $newTable)
    {
        if ($table->getTable() != $newTable->getTable()) {
            throw new NotCompareDifferentTableException('On ne compare pas deux table avec un nom diff�rent');
        }
        // Aucune différence
        if ($this->tableIsEquals($table, $newTable)) {
            return [];
        }

        $modificationsBetweenTable = [];
        $columns = $table->getColumns();
        $newColumns = $newTable->getColumns();

        foreach ($columns as $column) {
            try {
                $newColumn = $this->getColumn($column, $newColumns);
                $modificationsBetweenTable[$newColumn->getName()] = $this->checkColumn($column, $newColumn);
            } catch (ColumnNotExistException $e) {
                $modificationsBetweenTable[$column->getName()] = $this->createStatement($column);
                continue;
            } catch (\Exception $e) {
                continue;
            }
        }

        $columnNeedAlter = array_unique(array_keys($modificationsBetweenTable));
        $indexes = $newTable->getIndexes();
        foreach ($indexes as $indexName => $index) {
            foreach ($columnNeedAlter as $colonne) {
                if (in_array($colonne, $index->getColumns(), false)) {
                    try {
                        $modificationsBetweenTable[] = $index->alterStatement();
                    } catch (TablenameHasNotDefinedException $e) {
                        continue;
                    }
                }
            }
        }

        return $this->formatStatements($modificationsBetweenTable);
    }

    private function tableIsEquals(MysqlDatabaseTable $table, MysqlDatabaseTable $newTable)
    {
        // Table is equals no need more check
        if ($table == $newTable) {
            return true;
        }

        if (!$this->checkCollate) {
            $this->disabledCollate($table);
            $this->disabledCollate($newTable);
        }

        return $table == $newTable;
    }

    /**
     * @param MysqlDatabaseTable $table
     */
    private function disabledCollate(MysqlDatabaseTable $table)
    {
        $table->setCollate('');
        $columns = $table->getColumns();
        foreach ($columns as $column) {
            $column->setCollate('');
        }
    }

    /**
     * @param MysqlDatabaseColumn   $column
     * @param MysqlDatabaseColumn[] $newColumns
     *
     * @return mixed
     *
     * @throws ColumnNotExistException
     */
    private function getColumn(MysqlDatabaseColumn $column, array $newColumns)
    {
        foreach ($newColumns as $newColumn) {
            if ($column->getName() == $newColumn->getName()) {
                return $newColumn;
            }
        }
        throw new ColumnNotExistException('');
    }

    /**
     * @param MysqlDatabaseColumn $column
     * @param MysqlDatabaseColumn $newColumn
     *
     * @return array
     *
     * @throws NotCompareDifferentColumnException
     * @throws \Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException
     */
    private function checkColumn(MysqlDatabaseColumn $column, MysqlDatabaseColumn $newColumn)
    {
        if ($column->getName() != $newColumn->getName()) {
            throw new NotCompareDifferentColumnException('On ne compare pas deux colonnes avec un nom diff�rent');
        }
        if ($this->columnIsEquals($column, $newColumn)) {
            return [];
        }

        try {
            $statements = $newColumn->alterStatement();
        } catch (\RuntimeException $e) {
            return [];
        }

        return $statements;
    }

    private function columnIsEquals(MysqlDatabaseColumn $column, MysqlDatabaseColumn $newColumn)
    {
        // Column is equals no need more check
        if ($column == $newColumn) {
            return true;
        }

        if (!$this->checkCollate) {
            $column->setCollate('');
            $newColumn->setCollate('');
        }

        return $column == $newColumn;
    }

    /**
     * @param  $databaseInterface
     *
     * @return array
     */
    protected function createStatement(DatabaseInterface $databaseInterface)
    {
        try {
            return $databaseInterface->createStatement();
        } catch (TableHasNotColumnException $e) {
            return [];
        }
    }

    /**
     * @param array $modificationsBetweenTable
     *
     * @return array
     */
    private function formatStatements(array $modificationsBetweenTable)
    {
        $statements = [];
        foreach ($modificationsBetweenTable as $modifications) {
            foreach ((array)$modifications as $modification) {
                $statements[] = $modification;
            }
        }

        return array_filter(array_unique($statements));
    }

}
