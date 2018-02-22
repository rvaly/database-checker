<?php

namespace Starkerxp\DatabaseChecker\Factory;

use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseColumn;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseTable;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Convert json data to array object.
 *
 * @package Starkerxp\DatabaseChecker\Factory
 */
class JsonDatabaseFactory
{

    /**
     * @var string
     */
    private $json;

    /**
     * JsonDatabaseFactory constructor.
     *
     * @param string $json
     */
    public function __construct($json)
    {
        $this->json = $json;
    }

    /**
     * @return MysqlDatabaseTable[]
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \LogicException
     * @throws \RuntimeException
     */
    public function generate()
    {
        if (empty($this->json)) {
            throw new \LogicException('');
        }
        if (!$data = json_decode($this->json, true)) {
            $data = [];
        }
        $tables = [];
        $dataTables = $this->resolve($data);
        foreach ($dataTables as $tableName => $dataTable) {
            try {
                $table = new MysqlDatabaseTable($tableName);
            } catch (\Exception $e) {
                continue;
            }
            foreach ((array)$dataTable['columns'] as $columnName => $row) {
                $table->addColumn(new MysqlDatabaseColumn($columnName, $row['type'], $row['length'], $row['nullable'], $row['defaultValue'], $row['extra']));
            }
            foreach ((array)$dataTable['indexes'] as $row) {
                $table->addIndex($row['columns'], $row['name']);
            }
            if (isset($dataTable['primary'])) {
                $table->addPrimary((array)$dataTable['primary']);
            }
            foreach ((array)$dataTable['uniques'] as $row) {
                $table->addUnique($row['columns'], $row['name']);
            }
            $tables[] = $table;
        }

        return $tables;
    }

    /**
     * Check syntax of array.
     *
     * @param array $data
     *
     * @return array
     *
     * @throws \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\OptionDefinitionException
     * @throws \Symfony\Component\OptionsResolver\Exception\MissingOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     * @throws \Symfony\Component\OptionsResolver\Exception\NoSuchOptionException
     * @throws \LogicException
     */
    protected function resolve(array $data)
    {
        if (!count($data)) {
            throw new \LogicException('json failed');
        }

        // On force les valeurs par d�faut.
        $resolverTable = new OptionsResolver();
        $resolverTable->setRequired(['columns']);
        $resolverTable->setDefaults(
            [
                'columns' => [],
                'indexes' => [],
                'primary' => null,
                'uniques' => [],
            ]
        );

        $resolverColumns = new OptionsResolver();
        $resolverColumns->setRequired(['type']);
        $resolverColumns->setDefaults(
            [
                'length' => null,
                'nullable' => false,
                'defaultValue' => null,
                'extra' => null,
            ]
        );

        $resolverIndex = new OptionsResolver();
        $resolverIndex->setDefaults(
            [
                'name' => '',
                'columns' => [],
            ]
        );
        $export = [];
        foreach ($data as $nomTable => $table) {
            $dataTable = $resolverTable->resolve($table);
            foreach ((array)$dataTable['columns'] as $columnName => $column) {
                $dataTable['columns'][$columnName] = $resolverColumns->resolve($column);
            }
            foreach (['indexes', 'uniques'] as $indexKey) {
                foreach ((array)$dataTable[$indexKey] as $keyIndex => $index) {
                    $dataTable[$indexKey][$keyIndex] = $resolverIndex->resolve($index);
                }
            }
            $export[$nomTable] = $dataTable;
        }

        return $export;
    }
}
