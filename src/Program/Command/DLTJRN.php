<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Delete a Journal
 *
 * @author Cassiano Vailati <cassvail>
 */
class DLTJRN extends Command
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
            throw new \RuntimeException('DLTJRN expects a non empty journal, library and name');
        }

        return $this->executeCommand("DLTJRN JRN($journalLibrary/$journalName)");
    }
}
