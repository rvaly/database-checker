<?php

namespace Starkerxp\DatabaseChecker\Structure;


use Starkerxp\DatabaseChecker\Exception\DatabaseHasNotDefinedException;
use Starkerxp\DatabaseChecker\LoggerTrait;

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
    private $engine;

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

    public function addTable(MysqlDatabaseTable $table)
    {
        $table->setDatabase($this->getDatabase());
        if (null !== $this->getCollate()) {
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

    public function removeTable($tableName)
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
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * @return array
     *
     */
    public function createStatement()
    {
        $modifications = [sprintf('CREATE DATABASE IF NOT EXISTS `%s`;', $this->getDatabase())];

        return $modifications;
    }

    /**
     * @return array
     *
     * @throws \RuntimeException
     */
    public function alterStatement()
    {
        $collateTmp = $this->getCollate();
        $collate = $collateTmp == '' ? '' : sprintf('CONVERT TO CHARACTER SET %s COLLATE %s', explode('_', $collateTmp)[0], $collateTmp);
        if ($collate == '') {
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
