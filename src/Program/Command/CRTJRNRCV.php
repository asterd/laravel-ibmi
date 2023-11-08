<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Create Journal Receiver
 *
 * @author Cassiano Vailati <cassvail>
 */
class CRTJRNRCV extends Command
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
            $journalReceiverName = 'QSQJRN0001';
        }

        if(empty($journalReceiverLibrary) || empty($journalReceiverName)) {
            throw new \RuntimeException('CRTJRNRCV expects a non empty library and name');
        }

        return $this->executeCommand("CRTJRNRCV JRNRCV($journalReceiverLibrary/$journalReceiverName)");
    }
}
