<?php
namespace Starkerxp\DatabaseChecker\Checker;


use Starkerxp\DatabaseChecker\Exception\ColumnNotExistException;
use Starkerxp\DatabaseChecker\Exception\NotCompareDifferentColumnException;
use Starkerxp\DatabaseChecker\Exception\NotCompareDifferentTableException;
use Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException;
use Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException;
use Starkerxp\DatabaseChecker\Exception\TableNotExistException;
use Starkerxp\DatabaseChecker\Structure\DatabaseInterface;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;

class MysqlDatabaseCheckerService
{

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
            } catch (TableNotExistException $exception) {
                //@todo Drop statement.
                //@todo config for generate create or drop.
                $modificationsBetweenTable[] = $this->createStatement($table);
                continue;
            } catch (\Exception $exception) {
                continue;
            }
        }

        foreach ($newTables as $newTable) {
            try {
                $this->getTable($newTable, $tables);
            } catch (TableNotExistException $exception) {
                $modificationsBetweenTable[] = $this->createStatement($newTable);
                continue;
            }
        }

        return $this->formatStatements($modificationsBetweenTable);
    }

    /**
     * @param MysqlDatabaseTable $table
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
        if ($table == $newTable) {
            return [];
        }

        $modificationsBetweenTable = [];
        $columns = $table->getColumns();
        $newColumns = $newTable->getColumns();

        foreach ($columns as $column) {
            try {
                $newColumn = $this->getColumn($column, $newColumns);
                $modificationsBetweenTable[$newColumn->getName()] = $this->checkColumn($column, $newColumn);
            } catch (ColumnNotExistException $exception) {
                $modificationsBetweenTable[$column->getName()] = $this->createStatement($column);
                continue;
            } catch (\Exception $exception) {
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
                    } catch (TableHasNotDefinedException $exception) {
                        continue;
                    }
                }
            }
        }

        return $this->formatStatements($modificationsBetweenTable);
    }

    /**
     * @param MysqlDatabaseColumn $column
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
     * @throws \Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException
     */
    private function checkColumn(MysqlDatabaseColumn $column, MysqlDatabaseColumn $newColumn)
    {
        if ($column->getName() != $newColumn->getName()) {
            throw new NotCompareDifferentColumnException('On ne compare pas deux colonnes avec un nom diff�rent');
        }
        if ($column == $newColumn) {
            return [];
        }

        try {
            $statements = $newColumn->alterStatement();
        } catch (\RuntimeException $exception) {
            return [];
        }

        return $statements;
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
        } catch (TableHasNotColumnException $exception) {
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
