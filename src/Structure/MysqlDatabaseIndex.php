<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 19/02/2018
 * Time: 13:45
 */

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
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * @param bool $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     */
    public function setColumns(array $columns)
    {
        $this->columns = $columns;
    }

    public function toArray()
    {
        return get_object_vars($this);
    }

    public function isPrimary()
    {
        return strtolower($this->name) == 'primary';
    }

    public function getIndexType(){
        if($this->isPrimary()){
            return 'PRIMARY';
        }
        if($this->isUnique()){
            return 'UNIQUE';
        }
        return '';
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

        return [sprintf('ALTER TABLE `%s` ADD %s INDEX `%s` (%s);', $this->getTable(), $this->getIndexType() ,$this->getName(), '`' . implode('`, `', $this->getColumns()) . '`')];
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


}
