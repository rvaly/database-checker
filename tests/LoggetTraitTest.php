<?php

namespace Starkerxp\DatabaseChecker\Tests;

use Apix\Log\Logger\Runtime;
use PHPUnit\Framework\TestCase;
use Starkerxp\DatabaseChecker\Exception\TablenameHasNotDefinedException;
use Starkerxp\DatabaseChecker\Structure\MysqlDatabaseIndex;

class LoggetTraitTest extends TestCase
{
    /**
     * @group logger
     * @group exception
     */
    public function testLogExceptionValid(): void
    {
        $logger = new Runtime();
        $logger->setMinLevel('debug');   // catch logs >= to `critical`
        $index = new MysqlDatabaseIndex('unq_id', ['id'], true);
        $index->setLogger($logger);

        try {
            $index->getTable();
        } catch (TablenameHasNotDefinedException $e) {
            $this->assertCount(1, $logger->getItems());
        }

    }

    /**
     * @group logger
     * @group exception
     */
    public function testLogGreatherThanExceptionInvalid(): void
    {
        $logger = new Runtime();
        $logger->setMinLevel('emergency')
            ->setCascading(false)
            ->setDeferred(true);
        $index = new MysqlDatabaseIndex('unq_id', ['id'], true);
        $index->setLogger($logger);

        try {
            $index->getTable();
        } catch (TablenameHasNotDefinedException $e) {
            $this->assertCount(0, $logger->getItems());
        }

    }
}

