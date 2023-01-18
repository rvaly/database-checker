<?php

namespace LBIGroupDataBaseChecker\Structure;

use LBIGroupDataBaseChecker\Exception\TablenameHasNotDefinedException;
use LBIGroupDataBaseChecker\DatabaseChecker\LoggerTrait;

class MysqlDatabaseIndex implements DatabaseInterface
{
    use LoggerTrait;

    private $table;
    private $name;

    /**
     * DatabaseColumnStructure constructor.
     *
     * @param string $name
     * @param bool   $unique
     *
     * @throws \RuntimeException
     */
    public function __construct($name, private readonly array $columns, private $unique)
    {
        if (empty($name)) {
            throw new \RuntimeException('');
        }
        $this->name = $name;
    }

    public function toArray()
    {
        $tmp = get_object_vars($this);
        unset($tmp['logger']);

        return $tmp;
    }

    /**
     * @throws TablenameHasNotDefinedException
     *
     * @return array
     */
    public function alterStatement()
    {
        $modifications = [];
        $modifications[] = $this->deleteStatement();
        $modifications[] = $this->createStatement()[0];

        return $modifications;
    }

    /**
     * @throws TablenameHasNotDefinedException
     *
     * @return string
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
        return 'primary' == strtolower($this->name);
    }

    /**
     * @throws \LBIGroupDataBaseChecker\Exception\TablenameHasNotDefinedException
     *
     * @return mixed
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
     * @throws TablenameHasNotDefinedException
     *
     * @return array
     */
    public function createStatement()
    {
        if ($this->isPrimary()) {
            return [sprintf('ALTER TABLE `%s` ADD PRIMARY KEY (%s);', $this->getTable(), '`' . implode('`, `', $this->getColumns()) . '`')];
        }

        return [sprintf('ALTER TABLE `%s` ADD %s INDEX `%s` (%s);', $this->getTable(), $this->getIndexType(), $this->getName(), '`' . implode('`, `', $this->getColumns()) . '`')];
    }

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

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setTable(mixed $table): void
    {
        $this->table = $table;
    }
}
