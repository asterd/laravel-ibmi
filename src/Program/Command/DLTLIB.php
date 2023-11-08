<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Deletes a Library
 *
 * @author Guido Sangiovanni <gsangiov>
 */
class DLTLIB extends Command
{
    /**
     * @param string $libraryName
     * @return array|bool
     * @throws \Exception
     */
    public function execute(string $libraryName = ''): array|bool
    {
        if(empty($libraryName)) {
            throw new \RuntimeException('DLTLIB expects a not empty library name');
        }

        return $this->executeCommand("DLTLIB LIB($libraryName)");
    }
}
