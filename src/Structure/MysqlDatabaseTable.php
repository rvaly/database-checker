<?php

namespace Starkerxp\DatabaseChecker\Structure;


//@todo Manage data sync by option.
use Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException;
use Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException;

class MysqlDatabaseTable implements DatabaseInterface
{

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
     * @throws TableHasNotDefinedException
     */
    public function __construct($table)
    {
        if (empty($table)) {
            throw new TableHasNotDefinedException('');
        }
        $this->table = $table;
    }

    /**
     * @return string
     */
    public function getCollate()
    {
        return $this->collate;
    }

    /**
     * @param string $collate
     */
    public function setCollate($collate)
    {
        $this->collate = $collate;
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
            throw new \RuntimeException('');
        }

        return $this->indexes[$indexName];
    }


    /**
     * @return MysqlDatabaseColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    public function getIndexes()
    {
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
        if (!count($columns)) {
            throw new TableHasNotColumnException('');
        }
        foreach ($columns as $column) {
            try {
                if ($this->getCollate() == '') {
                    $column->setCollate('');
                }
                $modifications[] = $column->createStatement();
            } catch (TableHasNotDefinedException $e) {
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
                continue;
            }
        }

        if (!$modifications = $this->formatStatements($modifications)) {
            return [];
        }

        return $this->formatCreateStatement($modifications);

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
