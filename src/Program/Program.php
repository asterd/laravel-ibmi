<?php

namespace Cooperl\IBMi\Program;

use Cooperl\IBMi\Facades\ToolkitService;
use Exception;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Executes IBMi Rpg, Ile, Cl Programs
 *
 * @author Cassiano Vailati <cassvail>
 */

class Program implements LoggerAwareInterface
{
    use XMLToolkitAwareTrait, LoggerAwareTrait;

    const IOTYPE_INPUT = "in";
    const IOTYPE_OUTPUT = "out";
    const IOTYPE_BOTH = "both";

    const TYPE_CHAR = "char";
    const TYPE_INT32 = "int32";
    const TYPE_DECIMAL = "decimal";
    const TYPE_ZONED = "zoned";
    const TYPE_FLOAT = "float";
    const TYPE_BIN = "bin";
    const TYPE_DATA_STRUCTURE = "dataStructure";

    protected $programName;
    protected $programLibrary = '';

    /**
     * @var array
     * @example
        $params = array(
            array(
                "name" => '$ARTI',
                "type" => IBMi_Toolkit::TYPE_CHAR,
                "length" => 15,
                "value" => trim('0102'),
                "io" => XMLToolkit::IOTYPE_BOTH
            ),
            array(
                "name" => '$PESO',
                "type" => IBMi_Toolkit::TYPE_DECIMAL,
                "length" => 7,
                "scale" => 2,
                "value" => 0,
                "io" => XMLToolkit::IOTYPE_BOTH
            )
        );
     */
    protected array $params = array();

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct (LoggerInterface $logger = null)
    {
        $this->setLogger($logger);
    }

    /**
     * @throws Exception
     */
    public function exec()
    {
        return $this->callProgram($this->getProgramName(), $this->getParams(), $this->getProgramLibrary());
    }

    /**
     * @param $programName
     * @param array $params
     * @param null $programLibrary
     * @param null $options
     * @return mixed
     * @throws Exception
     */
    public function callProgram($programName, array $params = array(), $programLibrary = null, $options = null): mixed
    {
        $programLibrary = !is_null($programLibrary) ? $programLibrary : $this->getProgramLibrary();

        $callParams = $this->prepareParams($params);

        $logger = $this->getLogger();
        try
        {
            $logger?->debug("Call $programLibrary/$programName");

            //PgmCall($Program, $Library, $Parameters, $Returnvalue, $options)
            $result = ToolkitService::PgmCall($programName, $programLibrary, $callParams, null, $options);
            if(!$result) {
                throw new \RuntimeException("Program $programLibrary/$programName exited with errors. Verify program parameters.");
            }
        }
        catch (Exception $e)
        {
            $logger?->error("Call $programLibrary/$programName");
            $logger?->error($e->getMessage());
            $logger?->error($e->getTraceAsString());

            throw $e;
        }

        $convertedIoParam = array();
        foreach($params as $param)
        {
            $name = $param["name"];
            $type = $param["type"];

            /**
             * @description
             * Quando nei parametri c'e una data structure il formato è diverso, la proprietà value non viene settata
             */
            $value = $result["io_param"][$name] ?? null;
            $value = $this->normalizeResult($value, $type);
            $convertedIoParam[$name] = $value;
        }

        $result["out"] = $convertedIoParam;

        return $result;
    }

    /**
     * @param $value
     * @param $type
     * @return float|int|mixed
     */
    protected function normalizeResult($value, $type): mixed
    {
        if($type === static::TYPE_CHAR)
        {

        }
        else {
            if ($type === static::TYPE_INT32) {
                $value = (int)$value;
            } //TODO TYPE_ZONED
            else {
                if ($type === static::TYPE_DECIMAL) {
                    $value = str_replace(',', '.', $value);
                    $value = (float)$value;
                } else {
                    if ($type === static::TYPE_FLOAT) {
                        $value = str_replace(',', '.', $value);
                        $value = (float)$value;
                    }
                }
            }
        }

        return $value;
    }

    /**
     * @throws Exception
     */
    public function prepareParams($params=array())
    {
        $callParams = array();

        foreach($params as $param)
        {
            if(!array_key_exists("type", $param)) {
                throw new \RuntimeException("IBMi_Toolkit missing PARAM type");
            }
            if(!array_key_exists("io", $param)) {
                throw new \RuntimeException("IBMi_Toolkit missing PARAM io");
            }
            if(!array_key_exists("length", $param)) {
                throw new \RuntimeException("IBMi_Toolkit missing PARAM length");
            }
            if(!array_key_exists("desc", $param)) {
                $param["desc"] = '';
            }
            if(!array_key_exists("name", $param)) {
                throw new \RuntimeException("IBMi_Toolkit missing PARAM name");
            }
            if(!array_key_exists("value", $param)) {
                throw new \RuntimeException("IBMi_Toolkit missing PARAM value");
            }

            $io = $param["io"];
            $length = $param["length"];
            $comment = $param["desc"];
            $name = $param["name"];
            $value = $param["value"];
            $type = $param["type"];

            if($type === static::TYPE_CHAR)
            {
                $value = str_pad($value, $length, " ", STR_PAD_RIGHT);

                //AddParameterChar( $IOType, $Size, $Comment, $Name, $Value)
                $callParams[] = ToolkitService::AddParameterChar($io, $length , $comment, $name, $value);
            }
            else if ($type === static::TYPE_INT32) {
                $value = (int)$value . '';

                //AddParameterInt32 ($IOType, $Comment, $Name, $Value)
                $callParams[] = ToolkitService::AddParameterInt64($io, $comment, $name, $value);
            } else if ($type === static::TYPE_DECIMAL) {
                if (!array_key_exists("scale", $param)) {
                    throw new \RuntimeException("IBMi_Toolkit missing DECIMAL PARAM scale");
                }
                $scale = $param["scale"];

                $value = (float)$value . '';

                //AddParameterPackDec ($io, $length , $scale, $comment, $name, $value, $dimension=optional)
                $callParams[] = ToolkitService::AddParameterPackDec($io, $length, $scale, $comment, $name, $value);
            } else {
                if ($type === static::TYPE_ZONED) {
                    if (!array_key_exists("scale", $param)) {
                        throw new \RuntimeException("IBMi_Toolkit missing DECIMAL PARAM scale");
                    }
                    $scale = $param["scale"];

                    $value = (float)$value . '';

                    //AddParameterPackDec ($io, $length , $scale, $comment, $name, $value, $dimension=optional)
                    $callParams[] = ToolkitService::AddParameterZoned($io, $length, $scale, $comment, $name, $value);
                } else {
                    if ($type === static::TYPE_FLOAT) {
                        $value = (float)$value . '';

                        //AddParameterFloat ($IOType , $Size, $Comment, $Name, $Value)
                        $callParams[] = ToolkitService::AddParameterFloat($io, $length, $comment, $name, $value);
                    } else {
                        if ($type === static::TYPE_BIN) {
                            //AddParameterBin ($IOType, , $Size, $Comment, $Name, $Value)
                            $callParams[] = ToolkitService::AddParameterBin($io, $length, $comment, $name, $value);
                        } else {
                            throw new \RuntimeException("IBMi_Toolkit Unmanaged PARAM_TYPE: " . $type);
                        }
                    }
                }
            }

        }

        return $callParams;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return mixed
     */
    public function getProgramLibrary()
    {
        return $this->programLibrary;
    }

    /**
     * @param mixed $programLibrary
     */
    public function setProgramLibrary($programLibrary)
    {
        $this->programLibrary = $programLibrary;
    }

    /**
     * @return mixed
     */
    public function getProgramName()
    {
        return $this->programName;
    }

    /**
     * @param mixed $programName
     */
    public function setProgramName($programName)
    {
        $this->programName = $programName;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
