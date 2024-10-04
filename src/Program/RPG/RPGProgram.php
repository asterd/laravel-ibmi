<?php
namespace Cooperl\IBMi\Program\RPG;

use Cooperl\IBMi\Facades\ToolkitService;
use Cooperl\IBMi\Program\Command\CHGLIBL;
use Cooperl\IBMi\Program\Program;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * This class extends the base Program class
 * to enable a set of utilities to fast-define an RPG
 * program call and an auto-configured code execution
 *
 * Class RPGProgram
 * @package BCSpa\IBMiToolkit\Rpg
 * @author Dario D'Urzo
 */
class RPGProgram extends Program
{
    private ?ParamBuilder $paramBuilder = null;
    protected string $pgmName = '';
    protected string $pgmLib = '';
    protected array $pgmOptions = [];

    private string $libl;

    public function __construct(String $libl, LoggerInterface $logger = null)
    {
        $this->libl = $libl;
        parent::__construct($logger);
    }

    /**
     * Must be called to initialize params
     * @param string $pgmName
     * @param string $pgmLib
     * @param string $dsText
     */
    public function initParams(string $pgmName, string $pgmLib, string $dsText): void
    {
        $this->setPgmLib($pgmLib);
        $this->setPgmName($pgmName);
        $this->buildParam($dsText);
    }

    /**
     * Expose internal builder setParamValue method
     * @param string $paramName
     * @param $value
     * @param string|null $dsName
     * @return RPGProgram
     */
    public function setParamValue(string $paramName, $value, string $dsName = null): RPGProgram {
        if (!$this->paramBuilder) {
            throw new \InvalidArgumentException('Param builder must be initialized!');
        }
        if (!$dsName) {
            $this->paramBuilder->setParamValue($paramName, $value);
        } else {
            $this->paramBuilder->setDSParamValue($dsName, $paramName, $value);
        }

        return $this;
    }

    /**
     * Create a DS Structure
     * @param string $dsName
     * @param string $dsText
     * @return RPGProgram
     */
    public function createDSStructure(string $dsName, string $dsText): RPGProgram {
        $pb = new ParamBuilder($dsText);
        $this->setParamValue($dsName, $pb);
        return $this;
    }

    /**
     * @throws Exception
     */
    public function call() {
        // 1. validate data structure
        if (!$this->paramBuilder || !$this->paramBuilder->isValid()) {
            throw new \InvalidArgumentException("Parameter must be valid");
        }

        // 2. change library list
        if ($this->libl !== '') {
            $chg = new CHGLIBL($this->logger);
            $chg->execute($this->libl);
        }

        // 3. prepare params and run
        return $this->callProgram($this->pgmName, [], $this->pgmLib, $this->pgmOptions);
    }

    /**
     * @param array $inputParams
     * @return array
     * @throws Exception
     */
    public function prepareParams($inputParams = []): array
    {
        // init params
        $params = [];
        $dataParams = count($inputParams) === 0 ? $this->paramBuilder->getParams() : $inputParams;
        foreach ($dataParams as $param) {
            switch ($param->getDataType()) {
                case Program::TYPE_CHAR:
                    $value = str_pad($param->value, $param->length, " ", STR_PAD_RIGHT);
                    $params[] = ToolkitService::AddParameterChar($param->io, $param->length , $param->description, $param->name, $value, 'off', $param->arrayDimension, '', $param->arrayDimension > 0);
                    break;
                case Program::TYPE_INT32:
                    // $value = (int)$param->value .'';
                    // $toolkitServiceObj::AddParameterIntGeneric($param->length.'i0', $param->io, $param->description, $param->name, $value,  0, $param->arrayDimension > 0);
                    if ($param->length < 5) {
                        $params[] = ToolkitService::AddParameterInt8($param->io, $param->description, $param->name, $param->value, 0);
                    } else if ($param->length < 10) {
                        $params[] = ToolkitService::AddParameterInt16($param->io, $param->description, $param->name, $param->value, 0);
                    } else if ($param->length < 20) {
                        $params[] = ToolkitService::AddParameterInt32($param->io, $param->description, $param->name, $param->value, 0);
                    } else {
                        $params[] = ToolkitService::AddParameterInt64($param->io, $param->description, $param->name, $param->value, 0);
                    }
                    break;
                case Program::TYPE_DECIMAL:
                    if(!$this->propertyOrKeyExists($param, "scale")) {
                        throw new \RuntimeException("IBMi_Toolkit missing DECIMAL PARAM scale");
                    }
                    $value = (float)$param->value .'';
                    $params[] = ToolkitService::AddParameterPackDec($param->io, $param->length, $param->scale, $param->description, $param->name, $value, $param->arrayDimension);
                    break;
                case Program::TYPE_ZONED:
                    if(!$this->propertyOrKeyExists($param, "scale")) {
                        throw new \RuntimeException("IBMi_Toolkit missing DECIMAL PARAM scale");
                    }
                    $value = (float)$param->value .'';
                    $params[] = ToolkitService::AddParameterZoned($param->io, $param->length, $param->scale, $param->description, $param->name, $value, $param->arrayDimension);
                    break;
                case Program::TYPE_FLOAT:
                    $value = (float)$param->value .'';
                    $params[] = ToolkitService::AddParameterFloat($param->io, $param->length, $param->description, $param->name, $value, $param->arrayDimension);
                    break;
                case Program::TYPE_BIN:
                    $params[] = ToolkitService::AddParameterFloat($param->io, $param->length, $param->description, $param->name, $param->value, $param->arrayDimension);
                    break;
                case Program::TYPE_DATA_STRUCTURE:
                    $callParams = $this->prepareParams(is_array($param->getValue())?$param->getValue():$param->getValue()->getParams());
                    $arrayDim = is_array($param->getValue()) ? 0 : ($param->arrayDimension ?? 0);
                    $params[] = ToolkitService::AddDataStruct($callParams, $param->name, $arrayDim, '',($arrayDim > 0), null, $param->description, $param->io);
            }
        }

        return $params;
    }

    /**
     * @param array|RPGParam $input
     * @param string $property
     * @return bool
     */
    private function propertyOrKeyExists(array|RPGParam $input, string $property): bool {
        if (is_array($input)) {
            return array_key_exists("scale", $input);
        }
        return property_exists($input, $property);
    }

    /**
     * @return ParamBuilder
     */
    public function getBuildParam(): ParamBuilder
    {
        return $this->paramBuilder;
    }

    /**
     * @param string $dsText
     * @return RPGProgram
     */
    public function buildParam(string $dsText): RPGProgram
    {
        $this->paramBuilder = new ParamBuilder($dsText);
        return $this;
    }

    /**
     * @return string
     */
    public function getPgmName(): string
    {
        return $this->pgmName;
    }

    /**
     * @param string $pgmName
     * @return RPGProgram
     */
    public function setPgmName(string $pgmName): RPGProgram
    {
        $this->pgmName = $pgmName;
        return $this;
    }

    /**
     * @return string
     */
    public function getPgmLib(): string
    {
        return $this->pgmLib;
    }

    /**
     * @param string $pgmLib
     * @return RPGProgram
     */
    public function setPgmLib(string $pgmLib): RPGProgram
    {
        $this->pgmLib = $pgmLib;
        return $this;
    }

    /**
     * Override the LIBL definition
     * @param $libl
     */
    public function setLibl($libl): RPGProgram {
        $this->libl = $libl;
        return $this;
    }

    /**
     * Return the current LIBL
     * @return string
     */
    public function getLibl(): string {
        return $this->libl;
    }

    /**
     * @return array
     */
    public function getPgmOptions(): array
    {
        return $this->pgmOptions;
    }

    /**
     * @param array $pgmOptions
     */
    public function setPgmOptions(array $pgmOptions): void
    {
        $this->pgmOptions = $pgmOptions;
    }
}
