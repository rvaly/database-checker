<?php

namespace Starkerxp\DatabaseChecker\Structure;


use Starkerxp\DatabaseChecker\Exception\TableHasNotColumnException;

interface DatabaseInterface
{

    /**
     * @return mixed
     *
     * @throws TableHasNotColumnException
     */
    public function createStatement();

    public function alterStatement();
}
