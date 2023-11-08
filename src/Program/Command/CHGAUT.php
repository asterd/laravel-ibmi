<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Change Auth to a Path
 *
 * @author Cassiano Vailati <cassvail>
 */
class CHGAUT extends Command
{

    /**
     * @param string $path
     * @param string $user
     * @param string $auth
     * @return array|bool
     * @throws Exception
     */
    public function execute(string $path='/usr/local/ESC', string $user='QTMHHTTP', string $auth='*RWX'): array|bool
    {
        if(empty($path)) {
            throw new \RuntimeException('CHGAUT expects a non empty path');
        }

        return $this->executeCommand("CHGAUT OBJ('$path') USER($user) DTAAUT($auth) OBJAUT(*ALL) SUBTREE(*ALL)");
    }

}
