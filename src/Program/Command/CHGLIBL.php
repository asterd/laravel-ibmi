<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;

/**
 * Change Library List
 *
 * @author Cassiano Vailati <cassvail>
 */
class CHGLIBL extends Command
{

    /**
     * @param $libraries
     * @return mixed
     * @throws \Exception
     */
    public function execute($libraries)
    {
        if(is_array($libraries))
        {
            $libraries = implode(' ', $libraries);
        }

        //TODO create normalizeStringMethod
        /*if(PHP_SAPI == 'cli') {
            $libraries = str_replace('§', chr(167), $libraries);
            $libraries = str_replace(chr(63), chr(167), $libraries);
        }*/

        if(empty($libraries)) {
            throw new \RuntimeException('CHGLIBL expects non empty library list');
        }

        return $this->executeCommand("CHGLIBL LIBL({$libraries})");
    }
}
