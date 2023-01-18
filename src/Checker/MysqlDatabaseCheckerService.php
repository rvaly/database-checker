<?php

namespace LBIGroupDataBaseChecker\Checker;

use LBIGroupDataBaseChecker\Exception\ColumnNotExistException;
use LBIGroupDataBaseChecker\Exception\IndexNotExistException;
use LBIGroupDataBaseChecker\Exception\TableHasNotColumnException;
use LBIGroupDataBaseChecker\Exception\TableNotExistException;
use LBIGroupDataBaseChecker\DatabaseChecker\LoggerTrait;
use LBIGroupDataBaseChecker\Structure\DatabaseInterface;
use LBIGroupDataBaseChecker\Structure\MysqlDatabase;
use LBIGroupDataBaseChecker\Structure\MysqlDatabaseColumn;
use LBIGroupDataBaseChecker\Structure\MysqlDatabaseIndex;
use LBIGroupDataBaseChecker\Structure\MysqlDatabaseTable;

class MysqlDatabaseCheckerService
{
    use LoggerTrait;

    private ?bool $checkCollate = null;

    private ?bool $checkEngine = null;

    private bool $dropStatement = false;

    /**
     *
     * @throws \LBIGroupDataBaseChecker\Exception\TablenameHasNotDefinedException
     *
     * @throws TableHasNotColumnException
     */
    public function diff(MysqlDatabase $database, MysqlDatabase $newDatabase): array
    {
        $modificationsBetweenTable = [];
        $tables = $database->getTables();
        $newTables = $newDatabase->getTables();

        if ($this->checkCollate && $database->getCollate() != $newDatabase->getCollate()) {
            $database->setCollate($newDatabase->getCollate());
            $modificationsBetweenTable[] = $database->alterStatement();
        }

        // check Table
        foreach ($tables as $table) {
            try {
                $newTable = $this->getTable($table, $newTables);
                $modificationsBetweenTable[] = $this->checkTable($table, $newTable);
            } catch (TableNotExistException) {
                //@todo Drop statement.
                //@todo config for generate create or drop.
                $modificationsBetweenTable[] = $this->createStatement($table);
                continue;
            }
        }

        foreach ($newTables as $newTable) {
            try {
                $this->getTable($newTable, $tables);
            } catch (TableNotExistException) {
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
     *
     */
    private function getTable($table, array $newTables)
    {
        foreach ($newTables as $newTable) {
            if (strtolower((string) $table->getTable()) == strtolower((string) $newTable->getTable())) {
                return $newTable;
            }
        }
        throw new TableNotExistException('');
    }

    /**
     *
     * @throws \LBIGroupDataBaseChecker\Exception\TablenameHasNotDefinedException
     *
     * @throws TableHasNotColumnException
     */
    private function checkTable(MysqlDatabaseTable $table, MysqlDatabaseTable $newTable): array
    {
        $this->prepareTable($table);
        $this->prepareTable($newTable);
        // Aucune différence
        if ($this->tableIsEquals($table, $newTable)) {
            return [];
        }

        $modificationsBetweenTable = $newTable->alterStatement();
        $columns = $table->getColumns();
        $newColumns = $newTable->getColumns();
        if ($columns) {
            foreach ($columns as $column) {
                try {
                    $newColumn = $this->getColumn($column, $newColumns);
                    $modificationsBetweenTable[$newColumn->getName()] = $this->checkColumn($column, $newColumn);
                } catch (ColumnNotExistException) {
                    if ($this->dropStatement) {
                        $modificationsBetweenTable[$column->getName()] = $column->deleteStatement();
                        continue;
                    }
                    // il ne passe jamais ici
//                $modificationsBetweenTable[$column->getName()] = $this->createStatement($column);
                    continue;
                } catch (\Exception) {
                    continue;
                }
            }
        }

        //add
        if ($newColumns) {
            foreach ($newColumns as $column) {
                try {
                    $this->getColumn($column, $columns);
                } catch (ColumnNotExistException) {
                    $modificationsBetweenTable[$column->getName()] = $this->createStatement($column);
                    continue;
                } catch (\Exception) {
                    continue;
                }
            }
        }

        $columnNeedAlter = array_unique(array_keys(array_filter($modificationsBetweenTable)));
        $newIndexes = $newTable->getIndexes();

        $modificationsIndexBetweenTable = [];
        $modificationsIndexRemoveBetweenTable = [];
        if ($indexes = $table->getIndexes()) {
            foreach ($indexes as $index) {
                try {
                    $newIndex = $this->getIndex($index, $newIndexes);
                    $modificationsIndexBetweenTable[$newColumn->getName()] = $this->checkIndex($index, $newIndex);
                } catch (IndexNotExistException) {
                    if ($this->dropStatement) {
                        $modificationsIndexRemoveBetweenTable[$index->getName()] = $index->deleteStatement();
                        continue;
                    }
                } catch (\Exception) {
                    continue;
                }
            }
        }

        // Generate new Indexes.
        if ($newIndexes) {
            foreach ($newIndexes as $index) {
                try {
                    $this->getIndex($index, $indexes);
                } catch (IndexNotExistException) {
                    $modificationsIndexBetweenTable[$index->getName()] = $index->createStatement();
                } catch (\Exception) {
                    continue;
                }
            }
        }


        $modificationsIndexBetweenTable = $this->formatStatements(array_filter($modificationsIndexBetweenTable));
        $modificationsIndexRemoveBetweenTable = $this->formatStatements(array_filter($modificationsIndexRemoveBetweenTable));

        $modificationsBetweenTable = $this->formatStatements($modificationsBetweenTable);

        $result = array_merge($modificationsIndexRemoveBetweenTable, $modificationsBetweenTable, $modificationsIndexBetweenTable);

        return $result;
    }

    private function prepareTable(MysqlDatabaseTable $table): void
    {
        if (!$this->checkCollate) {
            $this->disabledCollate($table);
        }
        if (!$this->checkEngine) {
            $table->setEngine('');
        }
    }

    /**
     * @throws TableHasNotColumnException
     */
    private function disabledCollate(MysqlDatabaseTable $table): void
    {
        $table->setCollate('');
        $columns = $table->getColumns();
        foreach ($columns as $column) {
            $column->setCollate('');
        }
    }

    private function tableIsEquals(MysqlDatabaseTable $table, MysqlDatabaseTable $newTable): bool
    {
        // Table is equals no need more check
        if ($table == $newTable) {
            return true;
        }

        return strtolower(json_encode($table->toArray(), JSON_THROW_ON_ERROR)) == strtolower(json_encode($newTable->toArray(), JSON_THROW_ON_ERROR));
    }

    /**
     * @param MysqlDatabaseColumn[] $newColumns
     *
     * @return mixed
     * @throws ColumnNotExistException
     *
     */
    private function getColumn(MysqlDatabaseColumn $column, array $newColumns)
    {
        foreach ($newColumns as $newColumn) {
            if (strtolower((string) $column->getName()) == strtolower((string) $newColumn->getName())) {
                return $newColumn;
            }
        }
        throw new ColumnNotExistException('');
    }

    /**
     * @throws \LBIGroupDataBaseChecker\Exception\TablenameHasNotDefinedException
     *
     */
    private function checkColumn(MysqlDatabaseColumn $column, MysqlDatabaseColumn $newColumn): array
    {
        if ($this->columnIsEquals($column, $newColumn)) {
            return [];
        }
        try {
            $statements = $newColumn->alterStatement();
        } catch (\RuntimeException) {
            return [];
        }

        return $statements;
    }

    private function columnIsEquals(MysqlDatabaseColumn $column, MysqlDatabaseColumn $newColumn): bool
    {
        // Column is equals no need more check
        if ($column == $newColumn) {
            return true;
        }
        $oldType = str_replace('unsigned', '', strtolower($column->getType()));
        $newType = str_replace('unsigned', '', strtolower($newColumn->getType()));
        if (null === $column->getLength() || false === $column->getLength() || ('int' === $oldType && 'int' === $newType) || ('mediumint' === $oldType && 'mediumint' === $newType) || ('tinyint' === $oldType && 'tinyint' === $newType) || ('smallint' === $oldType && 'smallint' === $newType)) {
            return true;
        }

        return strtolower(json_encode($column->toArray(), JSON_THROW_ON_ERROR)) == strtolower(json_encode($newColumn->toArray(), JSON_THROW_ON_ERROR));
    }

    private function getIndex(MysqlDatabaseIndex $index, array $newIndexes)
    {
        foreach ($newIndexes as $newIndex) {
            if (strtolower((string) $index->getName()) == strtolower((string) $newIndex->getName())) {
                return $newIndex;
            }
        }
        throw new IndexNotExistException('');
    }

    private function checkIndex(MysqlDatabaseIndex $index, MysqlDatabaseIndex $newIndex): array
    {
        if ($this->indexIsEquals($index, $newIndex)) {
            return [];
        }
        try {
            $statements = $newIndex->alterStatement();
        } catch (\RuntimeException) {
            return [];
        }

        return $statements;
    }

    private function indexIsEquals(MysqlDatabaseIndex $index, MysqlDatabaseIndex $newIndex): bool
    {
        // Column is equals no need more check
        if ($index == $newIndex) {
            return true;
        }

        if (strtolower(json_encode($index->toArray(), JSON_THROW_ON_ERROR)) == strtolower(json_encode($newIndex->toArray(), JSON_THROW_ON_ERROR))) {
            return true;
        }

        if (strtolower((string) $index->getTable()) === strtolower((string) $newIndex->getTable()) && $indexColumns = $index->getColumns() && $newIndexColumns = $newIndex->getColumns()) {
            if (is_array($newIndexColumns) && is_array($indexColumns)) {
                foreach ($indexColumns as $column) {
                    if (!in_array($column, $newIndexColumns)) {
                        return false;
                    }
                }
                foreach ($newIndexColumns as $column) {
                    if (!in_array($column, $indexColumns)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }

    /**
     * @param  $databaseInterface
     *
     * @return array
     */
    protected function createStatement(DatabaseInterface $databaseInterface): ?array
    {
        try {
            return $databaseInterface->createStatement();
        } catch (TableHasNotColumnException) {
            return [];
        }
    }

    private function formatStatements(array $modificationsBetweenTable): array
    {
        $statements = [];
        foreach ($modificationsBetweenTable as $modifications) {
            foreach ((array)$modifications as $modification) {
                $statements[] = $modification;
            }
        }

        return array_filter(array_unique($statements));
    }

    public function enableCheckCollate(): void
    {
        $this->checkCollate = true;
    }

    public function enableCheckEngine(): void
    {
        $this->checkEngine = true;
    }

    public function enableDropStatement(): void
    {
        $this->dropStatement = false;
    }

    public function getDropStatement(): bool
    {
        return $this->dropStatement;
    }
}
