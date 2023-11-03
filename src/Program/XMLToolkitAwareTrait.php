<?php
declare(strict_types=1);

namespace Cooperl\IBMi\Program;

trait XMLToolkitAwareTrait
{
    /**
     * @var XMLToolkit
     */
    protected $xmlToolkit;

    /**
     * @return XMLToolkit
     */
    public function getXmlToolkit(): XMLToolkit
    {
        return $this->xmlToolkit;
    }

    /**
     * @param XMLToolkit $xmlToolkit
     */
    public function setXmlToolkit(XMLToolkit $xmlToolkit):void
    {
        $this->xmlToolkit = $xmlToolkit;
    }
}
