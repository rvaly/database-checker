<?php

namespace Starkerxp\DatabaseChecker\Structure;


use Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException;
use Starkerxp\DatabaseChecker\LoggerTrait;

class MysqlDatabaseIndex implements DatabaseInterface
{
    use LoggerTrait;

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

    public function toArray()
    {
        $tmp = get_object_vars($this);
        unset($tmp['logger']);
        return $tmp;
    }

    /**
     * @return array
     *
     * @throws TablenameHasNotDefinedException
     */
    public function alterStatement()
    {
        $modifications = [];
        $modifications[] = $this->deleteStatement();
        $modifications[] = $this->createStatement()[0];

        return $modifications;
    }

    /**
     * @return string
     *
     * @throws TablenameHasNotDefinedException
     */
    public function deleteStatement()
    {
        if ($this->isPrimary()) {
            return sprintf('ALTER TABLE `%s` DROP PRIMARY KEY;', $this->getTable());
        }

        return sprintf('ALTER TABLE `%s` DROP INDEX `%s`;', $this->getTable(), $this->getName());
    }


    public function isPrimary(): bool
    {
        return strtolower($this->name) == 'primary';
    }

    /**
     * @return mixed
     *
     * @throws \Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException
     */
    public function getTable()
    {
        if (!$this->table) {
            $this->critical('You need to define name of your table');
            throw new TablenameHasNotDefinedException('table not defined');
        }

        return $this->table;
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
     * @throws TablenameHasNotDefinedException
     */
    public function createStatement()
    {
        if ($this->isPrimary()) {
            return [sprintf('ALTER TABLE `%s` ADD PRIMARY KEY (%s);', $this->getTable(), '`' . implode('`, `', $this->getColumns()) . '`')];
        }

        return [sprintf('ALTER TABLE `%s` ADD %s INDEX `%s` (%s);', $this->getTable(), $this->getIndexType(), $this->getName(), '`' . implode('`, `', $this->getColumns()) . '`')];
    }

    /**
     * @return array
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getIndexType(): string
    {
        if ($this->isUnique()) {
            return 'UNIQUE';
        }

        return '';
    }

    /**
     * @return bool
     */
    public function isUnique(): bool
    {
        return $this->unique;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table): void
    {
        $this->table = $table;
    }


}
