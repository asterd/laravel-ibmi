<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * End journaling on a table
 *
 * @author Cassiano Vailati <cassvail>
 */
class ENDJRNPF extends Command
{

    /**
     * @param string $library
     * @param string $table
     * @param string $journalLibrary
     * @param string $journalName
     * @return array|bool
     * @throws \Exception
     */
    public function execute(string $library = '', string $table = '', string $journalLibrary = '', string $journalName = ''): array|bool
    {
        if(empty($journalName)) {
            $journalName = 'QSQJRN';
        }

        if(empty($table) || empty($journalLibrary) || empty($journalName)) {
            throw new \RuntimeException('ENDJRNPF expects a non empty library, name and journal');
        }

        if ($table === '*ALL') {
            $library = '';
        }

        if ($library === '') {
            $commandString = "ENDJRNPF FILE($table) JRN($journalLibrary/$journalName)";
        } else {
            $commandString = "ENDJRNPF FILE($library/$table) JRN($journalLibrary/$journalName)";
        }

        return $this->executeCommand($commandString);
    }
}
