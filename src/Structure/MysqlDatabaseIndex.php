<?php

namespace Starkerxp\DatabaseChecker\Structure;


use Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException;

class MysqlDatabaseIndex implements DatabaseInterface
{

    private $table;
    private $name;
    private $unique;
    private $columns;

    /**
     * DatabaseColumnStructure constructor.
     *
     * @param string  $name
     * @param boolean $unique
     * @param array   $columns
     *
     * @throws \RuntimeException
     */
    public function __construct($name, array $columns, $unique)
    {
        if (empty($name)) {
            throw new \RuntimeException('');
        }
        $this->name = $name;
        $this->unique = $unique;
        $this->columns = $columns;
    }


    /**
     * @return array
     *
     * @throws TableHasNotDefinedException
     */
    public function alterStatement()
    {
        if (!$this->getTable()) {
            throw new TableHasNotDefinedException('table not defined');
        }
        $modifications = [];
        if ($this->isPrimary()) {
            $modifications[] = sprintf('ALTER TABLE `%s` DROP PRIMARY KEY;', $this->getTable());
        } else {
            $modifications[] = sprintf('ALTER TABLE `%s` DROP INDEX `%s`;', $this->getTable(), $this->getName());
        }
        $modifications[] = $this->createStatement()[0];

        return $modifications;
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    public function isPrimary()
    {
        return strtolower($this->name) == 'primary';
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     *
     * @throws TableHasNotDefinedException
     */
    public function createStatement()
    {
        if (!$this->getTable()) {
            throw new TableHasNotDefinedException('table not defined');
        }
        if ($this->isPrimary()) {
            return [sprintf('ALTER TABLE `%s` ADD PRIMARY KEY (%s);', $this->getTable(), '`' . implode('`, `', $this->getColumns()) . '`')];
        }

        return [sprintf('ALTER TABLE `%s` ADD %s INDEX `%s` (%s);', $this->getTable(), $this->getIndexType(), $this->getName(), '`' . implode('`, `', $this->getColumns()) . '`')];
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    public function getIndexType()
    {
        if ($this->isUnique()) {
            return 'UNIQUE';
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }


}
