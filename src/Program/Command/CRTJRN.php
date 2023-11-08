<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Create a Journal
 *
 * @author Cassiano Vailati <cassvail>
 */
class CRTJRN extends Command
{

    /**
     * @param string $journalLibrary
     * @param string $journalName
     * @param string $journalReceiverLibrary
     * @param string $journalReceiverName
     * @return array|bool
     * @throws Exception
     */
    public function execute(string $journalLibrary = '', string $journalName = '', string $journalReceiverLibrary = '', string $journalReceiverName = ''): array|bool
    {
        if(empty($journalName)) {
            $journalName = 'QSQJRN';
        }
        if(empty($journalReceiverName)) {
            $journalReceiverName = 'QSQJRN0001';
        }

        if(empty($journalLibrary) || empty($journalName) || empty($journalReceiverLibrary) || empty($journalReceiverName)) {
            throw new \RuntimeException('CRTJRN expects a non empty journal and receiver, library and name');
        }

        return $this->executeCommand("CRTJRN JRN($journalLibrary/$journalName) JRNRCV($journalReceiverLibrary/$journalReceiverName) DLTRCV(*YES)");
    }
}
