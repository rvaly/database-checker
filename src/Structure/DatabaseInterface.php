<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 19/02/2018
 * Time: 13:44
 */

namespace Starkerxp\DatabaseChecker\Structure;


use Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException;

interface DatabaseInterface
{
    public function toArray();

    /**
     * @return mixed
     *
     * @throws TableHasNotColumnException
     */
    public function createStatement();

    public function alterStatement();
}
