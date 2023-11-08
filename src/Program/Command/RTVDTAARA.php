<?php

namespace Cooperl\IBMi\Program\Command;

use Cooperl\IBMi\Program\Command;
use Exception;
use RuntimeException;

/**
 * Change Auth to a Path
 *
 * @author Cassiano Vailati <cassvail>
 */
class RTVDTAARA extends Command
{
    /**
     * @param $dataAreaName
     * @param string $dataAreaLibrary
     * @return mixed
     * @throws Exception
     */
    public function execute($dataAreaName, string $dataAreaLibrary = '*LIBL'): mixed
    {
        if(empty($dataAreaName)) {
            throw new RuntimeException('RTVDTAARA expects a non empty DataArea name');
        }

        $result =  $this->executeCommand("RTVDTAARA DTAARA($dataAreaLibrary/$dataAreaName *ALL) RTNVAR(?)", true);

        return $result['RTNVAR'];
    }
}
