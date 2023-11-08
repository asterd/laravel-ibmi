<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Start journaling on a table
 *
 * @author Cassiano Vailati <cassvail>
 */
class STRJRNPF extends Command
{

    /**
     * @param string $library
     * @param string $table
     * @param string $journalLibrary
     * @param string $journalName
     * @return array|bool
     * @throws Exception
     */
    public function execute(string $library = '', string $table = '', string $journalLibrary = '', string $journalName = ''): array|bool
    {
        if(empty($journalName)) {
            $journalName = 'QSQJRN';
        }

        if(empty($library) || empty($table) || empty($journalLibrary) || empty($journalName)) {
            throw new \RuntimeException('STRJRNPF expects a non empty library, name and journal');
        }

        return $this->executeCommand("STRJRNPF FILE($library/$table) JRN($journalLibrary/$journalName)");
    }
}
