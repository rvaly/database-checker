<?php

namespace LBIGroupDataBaseChecker\Structure;

use LBIGroupDataBaseChecker\Exception\TableHasNotColumnException;
use LBIGroupDataBaseChecker\Exception\TablenameHasNotDefinedException;
use LBIGroupDataBaseChecker\DatabaseChecker\LoggerTrait;

class MysqlDatabaseTable implements DatabaseInterface
{
    use LoggerTrait;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $engine;

    /**
     * @var string
     */
    private $table;

    /**
     * @var MysqlDatabaseColumn[]
     */
    private array $columns = [];

    /**
     * @var MysqlDatabaseIndex[]
     */
    private array $indexes = [];

    /**
     * @var string
     */
    private $collate;

    /**
     * DatabaseTableStructure constructor.
     *
     * @param $table
     *
     * @throws TablenameHasNotDefinedException
     */
    public function __construct($table)
    {
        if (empty($table)) {
            $this->critical('You need to define name of your table');
            throw new TablenameHasNotDefinedException('');
        }
        $this->table = $table;
    }

    public function addColumn(MysqlDatabaseColumn $column): void
    {
        $column->setTable($this->getTable());
        $this->columns[$column->getName()] = $column;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    public function removeColumn($columnName): void
    {
        unset($this->columns[$columnName]);
    }

    public function addIndex(array $columns, $indexName = ''): void
    {
        $this->addIndexType($indexName, 0, $columns);
    }

    /**
     * @param       $indexName
     * @param       $unique
     */
    protected function addIndexType($indexName, $unique, array $columns): void
    {
        if (empty($indexName)) {
            $indexName = ($unique ? 'UNI_' : 'IDX_') . md5(implode(',', $columns));
        }
        try {
            $index = new MysqlDatabaseIndex($indexName, $columns, $unique);
            $index->setTable($this->getTable());
            $this->indexes[$indexName] = $index;
        } catch (\Exception) {
        }
    }

    public function addPrimary(array $columnName): void
    {
        $this->addIndexType('PRIMARY', 1, $columnName);
    }

    public function addUnique(array $columnName, $indexName = ''): void
    {
        $this->addIndexType($indexName, 1, $columnName);
    }

    public function toArray()
    {
        $export = [];
        $export['columns'] = [];
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            $arrayColumn = $column->toArray();
            unset($arrayColumn['table'], $arrayColumn['name']);
            $export['columns'][$column->getName()] = $arrayColumn;
        }

        $export['indexes'] = [];
        $export['uniques'] = [];
        $indexes = $this->getIndexes();
        foreach ($indexes as $index) {
            $arrayIndex = $index->toArray();
            if ($index->isPrimary()) {
                $export['primary'] = $index->getColumns();
                continue;
            }
            if ($index->isUnique()) {
                unset($arrayIndex['table'], $arrayIndex['unique']);
                $export['uniques'][] = $arrayIndex;
                continue;
            }
            unset($arrayIndex['table'], $arrayIndex['unique']);
            $export['indexes'][] = $arrayIndex;
        }
        $export['collate'] = $this->getCollate();
        $export['engine'] = $this->getEngine();
        $export = array_filter($export);

        return [$this->getTable() => $export];
    }

    /**
     * @throws TableHasNotColumnException
     *
     * @return MysqlDatabaseColumn[]
     */
    public function getColumns(): array
    {
        if (!count($this->columns)) {
            $this->critical('You need to define columns for this table.', ['table' => $this->getTable()]);
            throw new TableHasNotColumnException('');
        }

        return $this->columns;
    }

    public function getIndexes(): array
    {
        if (empty($this->indexes)) {
            $this->error("You don't have any index. Are you sure ?");
        }

        return $this->indexes;
    }

    /**
     * @return string
     */
    public function getCollate(): ?string
    {
        return $this->collate;
    }

    /**
     * @return string
     */
    public function getEngine(): ?string
    {
        return $this->engine;
    }

    /**
     * @param string $collate
     */
    public function setCollate($collate): void
    {
        $this->collate = $collate;
    }

    /**
     * @param string $engine
     */
    public function setEngine($engine): void
    {
        $this->engine = $engine;
    }

    /**
     * @throws TableHasNotColumnException
     * @throws TablenameHasNotDefinedException
     *
     * @return array
     */
    public function createStatement()
    {
        $modifications = [];
        $modifications[] = [sprintf('CREATE TABLE IF NOT EXISTS `%s`', $this->getTable())];
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            if ('' == $this->getCollate()) {
                $column->setCollate('');
            }
            $modifications[] = $column->createStatement();
        }
        $indexes = $this->getIndexes();
        foreach ($indexes as $index) {
            try {
                if (!$this->getIndex($index->getName())) {
                    continue;
                }
                $modifications[] = $index->createStatement();
            } catch (\Exception) {
                $this->critical('Unexpected error are throw.', ['table' => $this->getTable(), 'index' => $index->getName()]);
                continue;
            }
        }

        if (!$modifications = $this->formatStatements($modifications)) {
            return [];
        }

        return $this->formatCreateStatement($modifications);
    }

    /**
     * @param $indexName
     *
     * @throws \RuntimeException
     */
    public function getIndex($indexName): MysqlDatabaseIndex
    {
        if (empty($this->indexes[$indexName])) {
            $this->critical('You attempt to get undefined index name.', ['index' => $indexName]);
            throw new \RuntimeException('');
        }

        return $this->indexes[$indexName];
    }

    private function formatStatements(array $modificationsBetweenTable): array
    {
        $statements = [];
        foreach ($modificationsBetweenTable as $modifications) {
            foreach ((array) $modifications as $modification) {
                $statements[] = $modification;
            }
        }

        return array_filter(array_unique($statements));
    }

    private function formatCreateStatement(array $modifications): array
    {
        if (!$finalStatement = array_shift($modifications)) {
            return [];
        }
        $tmp = [];
        foreach ($modifications as $modification) {
            $tmp[] = trim(str_replace(['ALTER TABLE `' . $this->getTable() . '` ADD COLUMN', 'ALTER TABLE `' . $this->getTable() . '` ADD ', ';'], '', (string) $modification));
        }
        $collate = '' == $this->getCollate() ? '' : sprintf("COLLATE='%s'", $this->getCollate());

        return [$finalStatement . '(' . implode(',', $tmp) . ')' . $collate . ';'];
    }

    /**
     * @throws \RuntimeException
     *
     * @return array
     */
    public function alterStatement()
    {
        $modifications = [];
        $modifications = array_merge($modifications, $this->alterStatementCollate());
        $modifications = array_merge($modifications, $this->alterStatementEngine());

        return $modifications;
    }

    private function alterStatementCollate(): array
    {
        if (empty($this->database)) {
            return [];
        }
        $collateTmp = $this->getCollate();
        $collate = '' == $collateTmp ? '' : sprintf('CONVERT TO CHARACTER SET %s COLLATE %s', explode('_', $collateTmp)[0], $collateTmp);
        if ('' == $collate) {
            return [];
        }

        $modifications = [
            sprintf('ALTER TABLE `%s` %s;', $this->getTable(), $collate),
        ];

        return $modifications;
    }

    private function alterStatementEngine(): array
    {
        if (empty($this->engine)) {
            return [];
        }

        $modifications = [
            sprintf('ALTER TABLE `%s` ENGINE=%s;', $this->getTable(), $this->getEngine()),
        ];

        return $modifications;
    }

    public function setDatabase($database): void
    {
        $this->database = $database;
    }

    public function deleteStatement()
    {
        // TODO: Implement deleteStatement() method.
    }
}
