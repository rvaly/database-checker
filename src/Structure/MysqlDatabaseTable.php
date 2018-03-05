<?php

namespace Starkerxp\DatabaseChecker\Structure;


//@todo Manage data sync by option.
use Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException;
use Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException;
use Starkerxp\DatabaseChecker\LoggerTrait;

class MysqlDatabaseTable implements DatabaseInterface
{

    use LoggerTrait;

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
            $export['columns'][] = $column->toArray();
        }

        $export['indexes'] = [];
        $indexes = $this->getIndexes();
        foreach ($indexes as $index) {
            $export['indexes'][] = $index->toArray();
        }

        return $export;
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
     * @return array
     *
     * @throws TableHasNotColumnException
     */
    public function createStatement()
    {
        $modifications = [];
        $modifications[] = [sprintf('CREATE TABLE IF NOT EXISTS `%s`', $this->getTable())];
        $columns = $this->getColumns();
        foreach ($columns as $column) {
            try {
                if ($this->getCollate() == '') {
                    $column->setCollate('');
                }
                $modifications[] = $column->createStatement();
            } catch (TablenameHasNotDefinedException $e) {
                continue;
            }
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
     * @return string
     */
    public function getCollate()
    {
        return $this->collate;
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
     * @param string $collate
     */
    public function setCollate($collate)
    {
        $this->collate = $collate;
    }

    /**
     *
     * @throws \RuntimeException
     */
    public function alterStatement()
    {
        $collate = $this->getCollate() == '' ? '' : sprintf("COLLATE='%s'", $this->getCollate());
        if ($collate == '') {
            throw new \RuntimeException('Not implemented');
        }

        return [sprintf('ALTER TABLE `%s` %s;', $this->getTable(), $collate)];
    }

}
