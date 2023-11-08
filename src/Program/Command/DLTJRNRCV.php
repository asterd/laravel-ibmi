<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Delete Journal Receivers
 *
 * @author Cassiano Vailati <cassvail>
 */
class DLTJRNRCV extends Command
{

    /**
     * @param string $journalReceiverLibrary
     * @param string $journalReceiverName
     * @return array|bool
     * @throws Exception
     */
    public function execute(string $journalReceiverLibrary = '', string $journalReceiverName = ''): array|bool
    {
        if(empty($journalReceiverName)) {
            $journalReceiverName = 'QSQJRN*';
        }

        if(empty($journalReceiverLibrary) || empty($journalReceiverName)) {
            throw new \RuntimeException('DLTJRNRCV expects a non empty journal receiver library and name');
        }

        return $this->executeCommand("DLTJRNRCV JRNRCV($journalReceiverLibrary/$journalReceiverName) DLTOPT(*IGNINQMSG)");
    }
}


