<?php

namespace Starkerxp\DatabaseChecker\Structure;


use Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException;
use Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException;
use Starkerxp\DatabaseChecker\LoggerTrait;

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
    private $columns = [];

    /**
     * @var MysqlDatabaseIndex[]
     */
    private $indexes = [];

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

    public function addColumn(MysqlDatabaseColumn $column)
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

    public function removeColumn($columnName)
    {
        unset($this->columns[$columnName]);
    }

    public function addIndex(array $columns, $indexName = '')
    {
        $this->addIndexType($indexName, 0, $columns);
    }

    /**
     * @param       $indexName
     * @param       $unique
     * @param array $columns
     */
    protected function addIndexType($indexName, $unique, array $columns)
    {
        if (empty($indexName)) {
            $indexName = ($unique ? 'UNI_' : 'IDX_') . md5(implode(',', $columns));
        }
        try {
            $index = new MysqlDatabaseIndex($indexName, $columns, $unique);
            $index->setTable($this->getTable());
            $this->indexes[$indexName] = $index;
        } catch (\Exception $e) {

        }
    }

    public function addPrimary(array $columnName)
    {
        $this->addIndexType('PRIMARY', 1, $columnName);
    }

    public function addUnique(array $columnName, $indexName = '')
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
            unset($arrayColumn['table']);
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
     * @return MysqlDatabaseColumn[]
     *
     * @throws TableHasNotColumnException
     */
    public function getColumns()
    {
        if (!count($this->columns)) {
            $this->critical('You need to define columns for this table.', ['table' => $this->getTable()]);
            throw new TableHasNotColumnException('');
        }

        return $this->columns;
    }

    public function getIndexes()
    {
        if (empty($this->indexes)) {
            $this->error("You don't have any index. Are you sure ?");
        }

        return $this->indexes;
    }

    /**
     * @return string
     */
    public function getCollate()
    {
        return $this->collate;
    }

    /**
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @param string $collate
     */
    public function setCollate($collate)
    {
        $this->collate = $collate;
    }

    /**
     * @param string $engine
     */
    public function setEngine($engine)
    {
        $this->engine = $engine;
    }

    /**
     * @return array
     *
     * @throws TableHasNotColumnException
     * @throws TablenameHasNotDefinedException
     */
    public function createStatement()
    {
        $modifications = [];
        $modifications[] = [sprintf('CREATE TABLE IF NOT EXISTS `%s`', $this->getTable())];
        $columns = $this->getColumns();
        foreach ($columns as $column) {

            if ($this->getCollate() == '') {
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
            } catch (\Exception $e) {
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
     * @return MysqlDatabaseIndex
     *
     * @throws \RuntimeException
     */
    public function getIndex($indexName)
    {

        if (empty($this->indexes[$indexName])) {
            $this->critical('You attempt to get undefined index name.', ['index' => $indexName]);
            throw new \RuntimeException('');
        }

        return $this->indexes[$indexName];
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

    private function formatCreateStatement(array $modifications)
    {
        if (!$finalStatement = array_shift($modifications)) {
            return [];
        }
        $tmp = [];
        foreach ($modifications as $modification) {
            $tmp[] = trim(str_replace(['ALTER TABLE `' . $this->getTable() . '` ADD COLUMN', 'ALTER TABLE `' . $this->getTable() . '` ADD ', ';',], '', $modification));
        }
        $collate = $this->getCollate() == '' ? '' : sprintf("COLLATE='%s'", $this->getCollate());

        return [$finalStatement . '(' . implode(',', $tmp) . ')' . $collate . ';'];
    }

    /**
     * @return array
     *
     * @throws \RuntimeException
     */
    public function alterStatement()
    {
        $modifications = [];
        $modifications = array_merge($modifications, $this->alterStatementCollate());
        $modifications = array_merge($modifications, $this->alterStatementEngine());

        return $modifications;
    }

    /**
     * @return array
     *
     * @throws \RuntimeException
     */
    private function alterStatementCollate()
    {
        if (empty($this->database)) {
            return [];
        }
        $collateTmp = $this->getCollate();
        $collate = $collateTmp == '' ? '' : sprintf('CONVERT TO CHARACTER SET %s COLLATE %s', explode('_', $collateTmp)[0], $collateTmp);
        if ($collate == '') {
            throw new \RuntimeException('Not implemented');
        }

        $modifications = [
            sprintf('ALTER DATABASE %s CHARACTER SET %s COLLATE %s;', $this->database, explode('_', $collateTmp)[0], $collateTmp),
            sprintf('ALTER TABLE `%s` %s;', $this->getTable(), $collate),
        ];

        return $modifications;
    }

    private function alterStatementEngine()
    {
        if (empty($this->engine)) {
            return [];
        }

        $modifications = [
            sprintf('ALTER TABLE `%s` ENGINE=%s;', $this->getTable(), $this->getEngine()),
        ];

        return $modifications;
    }

    public function setDatabase($database)
    {
        $this->database = $database;
    }

    public function deleteStatement()
    {
        // TODO: Implement deleteStatement() method.
    }


}
