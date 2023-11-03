<?php

namespace Cooperl\IBMi\Program\RPG;

use Cooperl\IBMi\Program\Program;

/**
 * This class provide a very basic
 * RPG or DS parameter description parser
 * to auto-bind a description to a positional array of parameters.
 *
 * It also enable a fluent-api to populate the necessary fields with
 * correct values.
 *
 * Class ParamBuilder
 * @package BCSpa\IBMiToolkit\Rpg
 * @author Dario D'Urzo
 */
class ParamBuilder
{
    private array $ds = [];

    public function __construct(string $dsText = '') {
        if ($dsText !== '') {
            $this->addFromDSText($dsText);
        }
    }

    /**
     * Get the DS as RPG Param
     * @return array
     */
    public function getRPGParam(): array
    {
        usort($this->ds, static function($a, $b) { return $a->getPosition() > $b->getPosition(); });
        return array_map(static function($o) {
            return $o->getRPGParam();
        }, $this->ds);
    }

    /**
     * Get list of params in RPGParam format
     * @return array
     */
    public function getParams(): array
    {
        usort($this->ds, static function($a, $b) { return $a->getPosition() > $b->getPosition(); });
        return $this->ds;
    }

    /**
     * Check if parameter structure is valid
     * The only check is on DataStructure field
     * to check if is empty or not
     * @return bool
     */
    public function isValid(): bool {
        foreach ($this->ds as $data) {
            if ($data->getType() === "DS" && $data->getValue() === null) {
                return false;
            }
        }
        return true;
    }

    /**
     * To String override
     */
    public function __toString() {
        usort($this->ds, static function($a, $b) { return $a->getPosition() > $b->getPosition(); });
        return implode("\n", $this->ds);
    }

    /**
     * Return next position
     * @return int
     */
    private function getNextPosition(): int {
        $max = -1;
        foreach ($this->ds as $d) {
            if ($d->getPosition() > $max) {
                $max = $d->getPosition();
            }
        }
        return $max + 1;
    }

    /**
     * Set value on specific param
     * @param string $paramName
     * @param $value
     * @return ParamBuilder
     */
    public function setParamValue(string $paramName, $value): ParamBuilder
    {
        foreach($this->ds as $param) {
            if (strtoupper($param->getName()) ===  strtoupper($paramName)) {
                $param->setValue($value);
            }
        }
        return $this;
    }

    /**
     * Set value on specific param
     * @param string $dsName
     * @param string $paramName
     * @param $value
     * @return ParamBuilder
     */
    public function setDSParamValue(string $dsName, string $paramName, $value): ParamBuilder
    {
        foreach($this->ds as $param) {
            if (strtoupper($param->getName()) ===  strtoupper($dsName)) {
                $param->getValue($value)->setParamValue($paramName, $value);
            }
        }
        return $this;
    }

    /**
     * Add a parameter value to an array
     * @param string $paramName
     * @param $value
     * @return $this
     */
    public function addArrayParamValue(string $paramName, $value): ParamBuilder
    {
        foreach($this->ds as $param) {
            if ($param->getName() === $paramName) {
                $param->addValue($value);
            }
        }
        return $this;
    }

    /**
     * Add a DS param
     * @param int $position
     * @param string $io
     * @param string $name
     * @param int $length
     * @param string $type
     * @param int $scale
     * @param string $description
     * @return $this
     */
    public function addParam(
        int $position, string $io, string $name, int $length,
        string $type, int $scale = 0, string $description = '',
        int $arrayDimension = 0
    ): ParamBuilder
    {
        $this->ds[] = new RPGParam($position, $io, $name, $length, $type, $scale, $description, $arrayDimension);
        return $this;
    }

    /**
     * Add new RPGParam parsing a simple DS Line
     * it assume that $io is input and $position is progressive (max + 1)
     * You can eventually specify these params
     *
     * The input string must be in RPG Form:
     * BGPQUANTI     15P 3
     *
     * @param string $dsLine
     * @param string $io
     * @param int $position
     * @return ParamBuilder
     */
    public function addFromDSLine(string $dsLine, string $io = '', int $position = -1): ParamBuilder
    {
        // 1. data initialization and validation
        $name = '';
        $type = '';
        $description = '';
        $scale = 0;
        $len = 0;
        if ($position === -1) {
            $position = $this->getNextPosition();
        }
        if ($io === '') {
            $io = Program::IOTYPE_BOTH;
        }

        // 2. cleanup and validate unwanted data
        $dsLine = trim($dsLine);
        // is a comment: return
        if (str_starts_with($dsLine, '*')) {
            return $this;
        }
        // is valid line: remove check and continue
        if (str_starts_with($dsLine, 'A') || str_starts_with($dsLine, 'D')) {
            $dsLine = trim(substr($dsLine, 1));
        }
        // is an header: return
        if (str_starts_with($dsLine, 'R')) {
            return $this;
        }
        // remove ALIAS line (if exist)
        $dsLine = preg_replace('/ALIAS\\(.*\\)/', '', $dsLine, -1);
        // get COLHDG if exist and put it in description
        $matches = null;
        $returnValue = preg_match('/COLHDG\\(.*\\)/', $dsLine, $matches);
        if ($returnValue && $matches && count($matches) > 0) {
            $description = str_replace(["COLHDG('", "')"], '', $matches[0]);
            $dsLine = preg_replace('/COLHDG\\(.*\\)/', '', $dsLine, -1);
        }
        // if description is still null, try to get from LIKEDS (if is a likeds line)
        if ($description === '') {
            $returnValue = preg_match('/LIKEDS\\([a-zA-Z0-9]*\\)/', $dsLine, $matches);
            if ($returnValue && $matches && count($matches) > 0) {
                $description = str_replace(["LIKEDS(", ")"], '', $matches[0]);
            }
        }

        // 3. revalidate line
        if (trim($dsLine) === '') {
            return $this;
        }

        // 4. run tokenizer
        $items = preg_split("/\s+/", $dsLine, -1, PREG_SPLIT_NO_EMPTY);
        if ($items && count($items) >= 2) {
            $name = $items[0];

            // 1. check line is valid and the type
            // if is a definition line, return
            if ($items[1] === 'PR' || $items[1] === 'PI') {
                return $this;
            }
            // if is a DS line, read ds data and save
            if (str_starts_with($items[1], "LIKEDS")) {
                $type = "DS";
                $scale = 0;
                $len = 0;
                // try to understand if is an array and witch dimension is
                if ((count($items) > 2) && str_starts_with($items[2], 'DIM')) {
                    $items[2] = str_replace(["DIM(", ")"], "", $items[2]);
                    $len = (int) $items[2];
                }
                $this->addParam($position, $io, $name, $len, $type, $scale, $description);
            } else {
                // otherwise read standard data
                $len = (int)substr($items[1], 0, -1);
                $type = substr($items[1], -1);
                $arrayDimension = 0;
                if (count($items) > 2) {
                    $dimIndex = 0;
                    // DIM can be in second or third position. must be calculated
                    if (str_starts_with($items[2], 'DIM')) {
                        $dimIndex = 2;
                    }
                    if (count($items) > 3 && str_starts_with($items[3], 'DIM')) {
                        $dimIndex = 3;
                    }

                    if ($dimIndex > 0) {
                        $items[$dimIndex] = str_replace(["DIM(", ")"], "", $items[$dimIndex]);
                        $arrayDimension = (int) $items[$dimIndex];
                    } else {
                        $scale = (int)$items[2];
                    }
                }
                // add standard param
                $this->addParam($position, $io, $name, $len, $type, $scale, $description, $arrayDimension);
            }
        }
        // 5. return
        return $this;
    }

    /**
     * Build a DS Description from
     * a multiline text like this:
     *  BGPFBBUNI     15P 3
     *  BGPQUANTI     15P 3
     *  BGPQTAACQ     15P 3
     * @param string $dsText
     * @return ParamBuilder
     */
    public function addFromDSText(string $dsText): ParamBuilder
    {
        $lineArray = explode("\n", $dsText);
        foreach ($lineArray as $line) {
            if (trim($line) !== '') {
                $this->addFromDSLine($line);
            }
        }
        return $this;
    }
}
