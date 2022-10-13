<?php

namespace LBIGroupDataBaseChecker\Structure;

use LBIGroupDataBaseChecker\DatabaseChecker\LoggerTrait;
use LBIGroupDataBaseChecker\Exception\DatabaseHasNotDefinedException;

class MysqlDatabase implements DatabaseInterface
{
    use LoggerTrait;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $collate;

    /**
     * @var MysqlDatabaseTable[]
     */
    private $tables = [];

    /**
     * DatabaseTableStructure constructor.
     *
     * @param $database
     *
     * @throws DatabaseHasNotDefinedException
     */
    public function __construct($database)
    {
        if (empty($database)) {
            $this->critical('You need to define name of your database');
            throw new DatabaseHasNotDefinedException('');
        }
        $this->database = $database;
    }

    public function addTable(MysqlDatabaseTable $table): void
    {
        $table->setDatabase($this->getDatabase());
        if (!empty($this->getCollate())) {
            $table->setCollate($this->getCollate());
        }
        $this->tables[$table->getTable()] = $table;
    }

    /**
     * @return mixed
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @return string
     */
    public function getCollate(): ?string
    {
        return $this->collate;
    }

    /**
     * @param string $collate
     */
    public function setCollate($collate): void
    {
        $this->collate = $collate;
    }

    public function removeTable($tableName): void
    {
        unset($this->tables[$tableName]);
    }

    public function toArray()
    {
        $export = [];
        $export['tables'] = [];
        $tables = $this->getTables();
        foreach ($tables as $table) {
            $arrayTable = $table->toArray();
            unset($arrayTable['table']);
            $export['tables'] = array_merge($export['tables'], $arrayTable);
        }
        $export['collate'] = $this->getCollate();
        $export = array_filter($export);

        return $export;
    }

    /**
     * @return MysqlDatabaseTable[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }

    /**
     * @return array
     */
    public function createStatement()
    {
        $modifications = [sprintf('CREATE DATABASE IF NOT EXISTS `%s`;', $this->getDatabase())];

        return $modifications;
    }

    /**
     * @return array
     * @throws \RuntimeException
     *
     */
    public function alterStatement()
    {
        $collateTmp = $this->getCollate();
        $collate = '' == $collateTmp ? '' : sprintf('CONVERT TO CHARACTER SET %s COLLATE %s', explode('_', $collateTmp)[0], $collateTmp);
        if ('' == $collate) {
            return [];
        }

        return [
            sprintf('ALTER DATABASE %s CHARACTER SET %s COLLATE %s;', $this->database, explode('_', $collateTmp)[0], $collateTmp),
        ];
    }

    public function deleteStatement()
    {
        return [];
    }
}
