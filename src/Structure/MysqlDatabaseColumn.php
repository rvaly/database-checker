<?php

namespace Starkerxp\DatabaseChecker\Structure;


use Starkerxp\DatabaseChecker\Exception\TableHasNotDefinedException;

class MysqlDatabaseColumn implements DatabaseInterface
{

    private $table;
    private $name;
    private $type;
    private $length;
    private $nullable;
    private $defaultValue;
    private $extra;
    private $collate;

    /**
     * DatabaseColumnStructure constructor.
     *
     * @param $name
     *
     * @param $type
     * @param $length
     * @param $nullable
     * @param $defaultValue
     * @param $extra
     *
     * @throws \RuntimeException
     */
    public function __construct($name, $type, $length, $nullable, $defaultValue, $extra)
    {
        if (empty($name)) {
            throw new \RuntimeException('');
        }
        $this->name = $name;
        $this->setType($type);
        $this->length = $length;
        $this->nullable = $nullable;
        $this->defaultValue = $defaultValue;
        $this->extra = $extra;
    }

    /**
     * @return string
     */
    public function getCollate()
    {
        $type = $this->getType();
        if (!in_array($type, ['char', 'varchar', 'enum', 'longtext', 'mediumtext', 'text', 'tinytext', 'varchar'], false)) {
            return '';
        }

        return $this->collate;
    }

    /**
     * @param string $collate
     */
    public function setCollate($collate)
    {
        $this->collate = $collate;
    }

    private function setType($type)
    {
        $type = strtolower($type);
        $this->type = $type;
    }

    public function optimizeType()
    {
        $isEnum = explode('enum', $this->type);
        if (!empty($isEnum)) {
            $numberElements = substr_count(str_replace(['(', ')', "'",], '', $isEnum[1]), ',') + 1;
            if ($numberElements == 2) {
                $this->type = 'tinyint';
                $this->length = 1;
            }
        }
    }


    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
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
        $null = $this->getNullable() ? '' : 'NOT';
        $default = $this->getDefaultValue() == false ? '' : ' DEFAULT ' . $this->getDefaultValue();
        $collate = $this->getCollate() == '' ? '' : sprintf("COLLATE '%s'", $this->getCollate());
        $modification = sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s %s NULL %s %s %s;', $this->getTable(), $this->getName(), $this->getColonneType(), $null, $default, $this->getExtra(), $collate);

        return [str_replace(['   ', '  ',], ' ', $modification)];
    }

    /**
     * @return mixed
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * @return mixed
     */
    public function getNullable()
    {
        return $this->nullable;
    }

    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getColonneType()
    {
        $baseType = $this->type;
        if (in_array($baseType, ['int', 'mediumint', 'tinyint', 'smallint', 'binary', 'varchar', 'bigint', 'char', 'float'], false)) {
            $baseType = $baseType . '(' . $this->length . ')';
        }

        return strtoupper($baseType);
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param mixed $table
     */
    public function setTable($table)
    {
        $this->table = $table;
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
        $null = $this->getNullable() ? '' : 'NOT';
        $default = $this->getDefaultValue() == false ? '' : ' DEFAULT ' . $this->getDefaultValue();
        $columnName = '`' . $this->getName() . '`';
        $collate = $this->getCollate() == '' ? '' : sprintf("COLLATE '%s'", $this->getCollate());
        $modification = sprintf('ALTER TABLE `%s` CHANGE COLUMN %s %s %s %s NULL %s %s %s;', $this->getTable(), $columnName, $columnName, $this->getColonneType(), $null, $default, $this->getExtra(), $collate);

        return [str_replace(['   ', '  ',], ' ', $modification)];
    }
}
