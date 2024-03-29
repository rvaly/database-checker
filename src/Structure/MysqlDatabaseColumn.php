<?php

namespace LBIGroupDataBaseChecker\Structure;

use LBIGroupDataBaseChecker\Exception\TablenameHasNotDefinedException;
use LBIGroupDataBaseChecker\DatabaseChecker\LoggerTrait;

class MysqlDatabaseColumn implements DatabaseInterface
{
    use LoggerTrait;

    private $table;
    private $name;
    private string $type;
    private string $extra;
    private $collate;

    /**
     * DatabaseColumnStructure constructor.
     *
     * @param $name
     * @param $type
     * @param $length
     * @param $nullable
     * @param $defaultValue
     * @param $extra
     *
     * @throws \RuntimeException
     */
    public function __construct($name, $type, private $length, private $nullable, private $defaultValue, $extra)
    {
        if (empty($name)) {
            throw new \RuntimeException('');
        }
        $this->name = $name;
        $this->setType($type);
        $this->setExtra($extra);
    }

    private function setType($type): void
    {
        $type = strtolower((string) $type);
        $this->type = $type;
    }

    public function setExtra(mixed $extra): void
    {
        $this->extra = strtoupper((string) $extra);
    }

    public function optimizeType(): void
    {
        $isEnum = explode('enum', (string) $this->type);
        if (!empty($isEnum)) {
            $numberElements = substr_count(str_replace(['(', ')', "'"], '', $isEnum[1]), ',') + 1;
            if (2 == $numberElements) {
                $this->type = 'tinyint';
                $this->length = 1;
            }
        }
    }

    public function toArray()
    {
        $tmp = get_object_vars($this);
        unset($tmp['logger']);
        $tmp['type'] = strtoupper((string) $tmp['type']);

        return $tmp;
    }

    /**
     * @return array
     * @throws TablenameHasNotDefinedException
     *
     */
    public function createStatement()
    {
        $null = $this->getNullable() ? '' : 'NOT';
        $default = $this->formatDefaultValue();
        $collate = $this->formatCollate();
        $modification = sprintf('ALTER TABLE `%s` ADD COLUMN `%s` %s %s NULL %s %s %s;', $this->getTable(), $this->getName(), $this->getColonneType(), $null, $default, $this->getExtra(), $collate);

        return [str_replace(['   ', '  '], ' ', $modification)];
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

    /**
     * @return string
     */
    public function getCollate(): ?string
    {
        $type = $this->getType();
        if (!\in_array($type, ['char', 'varchar', 'enum', 'longtext', 'mediumtext', 'text', 'tinytext', 'varchar', 'float', 'decimal'], false)) {
            return '';
        }

        return $this->collate;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return mixed
     * @throws TablenameHasNotDefinedException
     *
     */
    public function getTable()
    {
        if (!$this->table) {
            $this->critical('You need to define name of your table');
            throw new TablenameHasNotDefinedException('table not defined');
        }

        return $this->table;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getColonneType()
    {
        $baseType = $this->type;
        $length = $this->length;
        $unsigned = '';
        if (str_contains((string) $this->length, 'unsigned')) {
            $explode = explode(' ', (string) $this->length);
            $length = $explode[0];
            $unsigned = !empty($explode[1]) ? ' UNSIGNED' : '';
        }
        if (\in_array($baseType, ['int', 'mediumint', 'tinyint', 'smallint', 'binary', 'varchar', 'bigint', 'char', 'float', 'decimal'], false)) {
            $baseType = $baseType . '(' . $length . ')' . $unsigned;
        }

        return strtoupper((string) $baseType);
    }

    /**
     * @return mixed
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param string $collate
     */
    public function setCollate($collate): void
    {
        $this->collate = $collate;
    }

    public function setTable(mixed $table): void
    {
        $this->table = $table;
    }

    /**
     * @return array
     * @throws TablenameHasNotDefinedException
     *
     */
    public function alterStatement()
    {
        $null = $this->getNullable() ? '' : 'NOT';
        $default = $this->formatDefaultValue();
        $collate = $this->formatCollate();
        $modification = sprintf('ALTER TABLE `%s` CHANGE COLUMN `%s` `%s` %s %s NULL %s %s %s;', $this->getTable(), $this->getName(), $this->getName(), $this->getColonneType(), $null, $default, $this->getExtra(), $collate);

        return [str_replace(['   ', '  '], ' ', $modification)];
    }

    public function deleteStatement(): string
    {
        return sprintf('ALTER TABLE `%s` DROP COLUMN `%s`;', $this->getTable(), $this->getName());
    }

    private function formatDefaultValue(): string
    {
        $default = $this->getDefaultValue();
        if (empty($default)) {
            return '';
        }

        if ('NULL' === $default) {
            return ' DEFAULT NULL';
        }

        return " DEFAULT '" . $default . "'";
    }

    private function formatCollate(): ?string
    {
        $collate = $this->getCollate();
        if (empty($collate)) {
            return '';
        }

        return sprintf("COLLATE '%s'", $collate);
    }

    public function getLength()
    {
        return $this->length;
    }
}
