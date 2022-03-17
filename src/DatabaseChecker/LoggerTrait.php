<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 28/02/2018
 * Time: 13:28.
 */

namespace LBIGroupDataBaseChecker\DatabaseChecker;

use Psr\Log\LoggerAwareTrait;

trait LoggerTrait
{
    use LoggerAwareTrait;
    use \Psr\Log\LoggerTrait;

    public function log($level, $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}
