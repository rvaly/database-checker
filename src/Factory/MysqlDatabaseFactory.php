<?php

namespace Starkerxp\DatabaseChecker\Factory;

use Starkerxp\DatabaseChecker\LoggerTrait;
use Starkerxp\DatabaseChecker\Repository\StructureInterface;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabase;

/**
 * Transcris l'état de la base de données en version objet afin de pouvoir y appliquer les traitements.
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
     * @param StructureInterface $repositoryMysql
     * @param string             $databaseName
     */
    public function __construct(StructureInterface $repositoryMysql, $databaseName)
    {
        $this->repositoryMysql = $repositoryMysql;
        $this->databaseName = $databaseName;
    }

    public function enableCheckCollate(): void
    {
        $this->checkCollate = true;
    }

    /**
     * @throws \LogicException
     *
     * @return MysqlDatabase
     */
    public function generate(): MysqlDatabase
    {
        $export = [];
        $tables = $this->repositoryMysql->getTablesStructure($this->databaseName);
        foreach ($tables as $table) {
            $export['tables'][$table] = $this->getIndex($table);
            $export['tables'][$table]['columns'] = $this->getColumns($table);
        }
        $factoryJsonDatabase = new JsonDatabaseFactory(json_encode($export));

        try {
            $export = $factoryJsonDatabase->generate($this->databaseName);
        } catch (\Exception $e) {
            throw new \LogicException('Un expected error with json.' . $e->getMessage());
        }

        return $export;
    }

    protected function getIndex($table): array
    {
        if (!$results = $this->repositoryMysql->fetchIndexStructure($this->databaseName, $table)) {
            return [];
        }
        $export = [];
        foreach ($results as $row) {
            if ('PRIMARY' === $row['INDEX_NAME']) {
                $export['primary'] = array_filter(explode(',', $row['COLUMN_NAME']));
                continue;
            }
            $key = 'indexes';
            if (!$row['NON_UNIQUE']) {
                $key = 'uniques';
            }
            if (!$row['NON_FULLTEXT']) {
                $key = 'fulltexts';
            }
            $export[$key][] = array_filter(['name' => $row['INDEX_NAME'], 'columns' => explode(',', $row['COLUMN_NAME'])]);
        }

        return $export;
    }

    protected function getColumns($table): array
    {
        $export = [];
        $results = $this->repositoryMysql->fetchColumnsStructure($this->databaseName, $table);
        foreach ($results as $row) {
            $type = $row['DATA_TYPE'];
            $length = str_replace([$type, '(', ')'], '', $row['COLUMN_TYPE']);
            if ('enum' === $type) {
                $type = $row['COLUMN_TYPE'];
                $length = null;
            }
            $export[$row['COLUMN_NAME']] = array_filter([
                'type' => $type,
                'length' => $length,
                'nullable' => 'NO' !== $row['IS_NULLABLE'],
                'defaultValue' => 'NO' !== $row['IS_NULLABLE'] && empty($row['COLUMN_DEFAULT']) ? 'NULL' : $row['COLUMN_DEFAULT'],
                'extra' => $row['EXTRA'],
                'collate' => $row['COLLATION_NAME'],
            ]);
            if (!$this->checkCollate) {
                unset($export[$row['COLUMN_NAME']]['collate']);
            }
        }

        return $export;
    }

    public function exportStructure()
    {
        $export = [];
        $tables = $this->repositoryMysql->getTablesStructure($this->databaseName);
        foreach ($tables as $table) {
            $export[$table] = $this->getIndex($table);
            $export[$table]['columns'] = $this->getColumns($table);
        }

        return json_encode($export);
    }
}
