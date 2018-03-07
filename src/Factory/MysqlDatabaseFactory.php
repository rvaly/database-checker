<?php

namespace Starkerxp\DatabaseChecker\Factory;

use Starkerxp\DatabaseChecker\LoggerTrait;
use Starkerxp\DatabaseChecker\Repository\MysqlRepository;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;


/**
 * Transcris l'�tat de la base de donn�es en version objet afin de pouvoir y appliquer les traitements.
 *
 * @package Starkerxp\DatabaseChecker\Factory
 */
class MysqlDatabaseFactory
{

    use LoggerTrait;

    protected $databaseName;
    protected $repositoryMysql;
    private $checkCollate = false;

    /**
     * MysqlDatabaseFactory constructor.
     *
     * @param MysqlRepository $repositoryMysql
     * @param string          $databaseName
     */
    public function __construct(MysqlRepository $repositoryMysql, $databaseName)
    {
        $this->repositoryMysql = $repositoryMysql;
        $this->databaseName = $databaseName;
    }

    public function enableCheckCollate()
    {
        $this->checkCollate = true;
    }


    /**
     * @return MysqlDatabaseTable[]
     *
     * @throws \LogicException
     */
    public function generate()
    {
        $export = [];
        $tables = $this->repositoryMysql->getTablesStructure($this->databaseName);
        foreach ($tables as $table) {
            $export[$table] = $this->getIndex($table);
            $export[$table]['columns'] = $this->getColumns($table);
        }
        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($export));
        try {
            $export = $factoryJsonDatabase->generate();
        } catch (\Exception $e) {
            throw new \LogicException('Un expected error with json.' . $e->getMessage());
        }

        return $export;
    }


    protected function getIndex($table)
    {
        if (!$results = $this->repositoryMysql->fetchIndexStructure($this->databaseName, $table)) {
            return [];
        }
        $export = [];
        foreach ($results as $row) {
            if ($row['INDEX_NAME'] === 'PRIMARY') {
                $export['primary'] = array_filter(explode(',', $row['COLUMN_NAME']));
                continue;
            }
            $key = 'indexes';
            if(!$row['NON_UNIQUE']) {
                $key = 'uniques';
            }
            if(!$row['NON_FULLTEXT']) {
                $key = 'fulltexts';
            }
            $export[$key][] = array_filter(['name' => $row['INDEX_NAME'], 'columns' => explode(',', $row['COLUMN_NAME'])]);
        }

        return $export;
    }

    protected function getColumns($table)
    {
        $export = [];
        $results = $this->repositoryMysql->fetchColumnsStructure($this->databaseName, $table);
        foreach ($results as $row) {
            $type = $row['DATA_TYPE'];
            $length = str_replace([$type, '(', ')'], '', $row['COLUMN_TYPE']);
            if ($type === 'enum') {
                $type = $row['COLUMN_TYPE'];
                $length = null;
            }
            $export[$row['COLUMN_NAME']] = array_filter([
                'type' => $type,
                'length' => $length,
                'nullable' => $row['IS_NULLABLE'] !== 'NO',
                'defaultValue' => $row['IS_NULLABLE'] !== 'NO' && empty($row['COLUMN_DEFAULT']) ? 'NULL' : $row['COLUMN_DEFAULT'],
                'extra' => $row['EXTRA'],
                'collate' => $row['COLLATION_NAME'],
            ]);
            if (!$this->checkCollate) {
                unset($export[$row['COLUMN_NAME']]['collate']);
            }
        }

        return $export;
    }

}
