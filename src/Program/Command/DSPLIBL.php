<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Print libl to spool file
 * Spool file read is not implemented yet (WRKSPLF [USERNAME])
 *
 * @author Cassiano Vailati <cassvail>
 */
class DSPLIBL extends Command
{
    /**
     * @return array|bool
     * @throws Exception
     */
    public function execute(): bool|array
    {
        return $this->executeCommand("DSPLIBL OUTPUT(*PRINT)"); //['RTNVAR'];
    }

}
