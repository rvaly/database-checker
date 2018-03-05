<?php
/**
 * Created by PhpStorm.
 * User: Guillaume
 * Date: 28/02/2018
 * Time: 13:28
 */

namespace Starkerxp\DatabaseChecker;


use Psr\Log\LoggerAwareTrait;

trait LoggerTrait
{
    use LoggerAwareTrait;
    use \Psr\Log\LoggerTrait;

    public function log($level, $message, array $context = [])
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }

}
