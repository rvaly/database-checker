<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 07/03/2018
 * Time: 13:34
 */

namespace Starkerxp\DatabaseChecker\Repository;


interface StructureInterface
{

    /**
     * @param $database
     *
     * @return array
     */
    public function getSchemaCollation($database);

    /**
     * @param $database
     *
     * @return array
     */
    public function getTablesStructure($database);

    /**
     * @param $database
     * @param $table
     *
     * @return array
     */
    public function getTablesCollation($database, $table);

    /**
     * @param $database
     * @param $table
     *
     * @return array
     */
    public function fetchIndexStructure($database, $table);

    /**
     * @param $database
     * @param $table
     *
     * @return array
     */
    public function fetchColumnsStructure($database, $table);
}
