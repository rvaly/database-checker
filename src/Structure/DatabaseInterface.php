<?php

namespace LBIGroupDataBaseChecker\Structure;


use LBIGroupDataBaseChecker\Exception\TableHasNotColumnException;

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

    public function deleteStatement();
}
