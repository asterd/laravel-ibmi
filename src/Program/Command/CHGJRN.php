<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Creates a new Journal Receiver
 *
 * @author Cassiano Vailati <cassvail>
 */
class CHGJRN extends Command
{
    /**
     * @param string $journalLibrary
     * @param string $journalName
     * @return array|bool
     * @throws Exception
     */
    public function execute(string $journalLibrary = '', string $journalName = ''): array|bool
    {
        if(empty($journalName)) {
            $journalName = 'QSQJRN';
        }

        if(empty($journalLibrary) || empty($journalName)) {
            throw new \RuntimeException('CHGJRN expects a non empty journal library and name');
        }

        return $this->executeCommand("CHGJRN JRN($journalLibrary/$journalName) JRNRCV(*GEN)");
    }

}
