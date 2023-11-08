<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;

/**
 * Change Auth to a Path
 *
 * @author Cassiano Vailati <cassvail>
 */
class CHGDTAARA extends Command
{
    /**
     * @param $dataAreaName
     * @param $value
     * @param string $dataAreaLibrary
     * @return array|bool
     * @throws Exception
     */
    public function execute($dataAreaName, $value, string $dataAreaLibrary='*LIBL'): bool|array
    {
        if(empty($dataAreaName)) {
            throw new \RuntimeException('CHGDTAARA expects a non empty DataArea name');
        }

        return $this->executeCommand("CHGDTAARA DTAARA($dataAreaLibrary/$dataAreaName *ALL) VALUE($value)");
    }
}
