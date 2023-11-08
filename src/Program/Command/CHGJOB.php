<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Change Job Options
 *
 * @author Cassiano Vailati <cassvail>
 */
class CHGJOB extends Command
{
    /**
     * @param $timeSlice
     * @return array|bool
     * @throws Exception
     */
    public function execute($timeSlice): array|bool
    {
        if(empty($timeSlice)) {
            throw new \RuntimeException('CHGJOB expects non empty time-slice');
        }

        return $this->executeCommand("CHGJOB TIMESLICE($timeSlice) PURGE(*YES)");
    }
}
