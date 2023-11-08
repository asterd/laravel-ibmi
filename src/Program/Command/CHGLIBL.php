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
     * @return array|bool
     * @throws \Exception
     */
    public function execute($libraries): bool|array
    {
        if(is_array($libraries))
        {
            $libraries = implode(' ', $libraries);
        }

        if(empty($libraries)) {
            throw new \RuntimeException('CHGLIBL expects non empty library list');
        }

        return $this->executeCommand("CHGLIBL LIBL({$libraries})");
    }
}
