<?php
namespace Cooperl\IBMi\Program\RPG;

use Cooperl\IBMi\Program\Program;

/**
 * This class remap an RPG (pgm or ds) parameter
 * and expose utility methods to correctly manage and describe
 * these params.
 *
 * Class RPGParam
 * @package BCSpa\IBMiToolkit\Rpg
 * @author Dario D'Urzo
 */
class RPGParam
{
    private static array $supportedTypes = [
        'A' => Program::TYPE_CHAR, // Alphanumeric character
        'B' => Program::TYPE_BIN, // Binary numeric
        'C' => Program::TYPE_CHAR, // UCS-2 character
        'P' => Program::TYPE_DECIMAL,
        'S' => Program::TYPE_ZONED,
        'F' => Program::TYPE_FLOAT,
        'I' => Program::TYPE_INT32,
        'DS' => Program::TYPE_DATA_STRUCTURE,
    ];
    private static array $supportedIn = [
        Program::IOTYPE_INPUT, Program::IOTYPE_OUTPUT, Program::IOTYPE_BOTH
    ];

    private int $position;
    public string $type;
    public string $io;
    public int $length;
    public int $scale;
    public string $name;
    public string $description;
    public $value;

    /**
     * RPGParam constructor.
     * @param int $position
     * @param string $io
     * @param string $name
     * @param int $length
     * @param string $type
     * @param int $scale
     * @param string $description
     * @param int $arrayDimension
     */
    public function __construct(
        int $position, string $io, string $name, int $length,
        string $type, int $scale = 0, string $description = '',
        int $arrayDimension = 0
    ) {
        $this->initField($position, $io, $name, $length, $type, $scale, $description, $arrayDimension);
    }

    /**
     * Initialize field with default values
     *
     * @param int $position
     * @param string $io
     * @param string $name
     * @param int $length
     * @param string $type
     * @param int $scale
     * @param string $description
     * @param int $arrayDimension
     */
    public function initField(
        int $position, string $io, string $name, int $length,
        string $type, int $scale = 0, string $description = '',
        int $arrayDimension = 0
    ): void
    {
        // validations
        if (!array_key_exists(strtoupper($type), self::$supportedTypes)) {
            throw new \InvalidArgumentException("Type {$type} is not a supported type");
        }
        if (!in_array(strtolower($io), self::$supportedIn, true)) {
            throw new \InvalidArgumentException("In/Out {$io} is not a supported In/Out Type");
        }
        if ($length <= 0 && strtoupper($type) !== "DS") {
            throw new \InvalidArgumentException("Field length must be greater than 0");
        }

        // save data
        $this->position = $position;
        $this->io = $io;
        $this->name = strtoupper($name);
        $this->length = $length;
        $this->type = strtoupper($type);
        $this->scale = $scale;
        $this->description = $description !== '' ? $description : $this->name;
        $this->arrayDimension = $arrayDimension;
    }

    /**
     * Return the default value by type
     * @return mixed
     */
    private function getDefaultValue() {
        return match (self::$supportedTypes[$this->type]) {
            Program::TYPE_ZONED, Program::TYPE_FLOAT, Program::TYPE_INT32, Program::TYPE_DECIMAL => 0,
            Program::TYPE_DATA_STRUCTURE => null,
            default => '',
        };
    }

    /**
     * @return mixed
     */
    public function getDataType() {
        return self::$supportedTypes[$this->type];
    }

    /**
     * Get the
     * @return array
     */
    public function getRPGParam(): array
    {
        if (self::$supportedTypes[$this->type] === Program::TYPE_DATA_STRUCTURE) {
            return [
                "name" => $this->name,
                "type" => self::$supportedTypes[$this->type],
                "isArray" => $this->length > 0 ? "TRUE ({$this->length})": "FALSE",
                "value" => $this->getValue() ? $this->getValue()->getRPGParam() : null,
                "io" => $this->io,
                "description" => $this->description
            ];
        }

        // this is the default structure supported by Program and XMLToolkit classes
        return [
            "name" => $this->name,
            "type" => self::$supportedTypes[$this->type],
            "length" => $this->length,
            "scale" => $this->scale,
            "value" => $this->getValue(),
            // "isArray" => $this->arrayDimension > 0 ? "TRUE ({$this->arrayDimension})": "FALSE",
            "io" => $this->io,
            "description" => $this->description,
            "comment" => $this->description,
            "var" => '',
            "data" => $this->getValue(),
            "varying" => 'off',
            "dim" => $this->arrayDimension
        ];
    }

    /**
     * Override the toString method
     * @return string
     */
    public function __toString()
    {
        if (self::$supportedTypes[$this->type] === Program::TYPE_DATA_STRUCTURE) {
            $desc = "{$this->position}: {$this->io} {$this->name} ({$this->type} :: {$this->description}) ";
            $desc .= $this->length > 0 ? "DIM({$this->length})" : "";
            $desc .= $this->getValue();
            return $desc;
        }
        return "{$this->position}: {$this->io} {$this->name} {$this->length}{$this->type} {$this->scale} // {$this->description} :: {$this->value}";
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return \App\Shared\Rpg\RPGParam
     */
    public function setPosition(int $position): RPGParam
    {
        $this->position = $position;
        return $this;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return RPGParam
     */
    public function setType(string $type): RPGParam
    {
        $this->type = strtoupper($type);
        return $this;
    }

    /**
     * @return string
     */
    public function getIo(): string
    {
        return $this->io;
    }

    /**
     * @param string $io
     * @return RPGParam
     */
    public function setIo(string $io): RPGParam
    {
        $this->io = strtolower($io);
        return $this;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param int $length
     * @return RPGParam
     */
    public function setLength(int $length): RPGParam
    {
        $this->length = $length;
        return $this;
    }

    /**
     * @return int
     */
    public function getScale(): int
    {
        return $this->scale;
    }

    /**
     * @param int $scale
     * @return RPGParam
     */
    public function setScale(int $scale): RPGParam
    {
        $this->scale = $scale;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return RPGParam
     */
    public function setName(string $name): RPGParam
    {
        $this->name = strtoupper($name);
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return RPGParam
     */
    public function setDescription(string $description): RPGParam
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue()
    {
        if ($this->value === null) {
            $this->value = $this->getDefaultValue();
        }
        return $this->value;
    }

    /**
     * @param mixed $value
     * @return RPGParam
     */
    public function setValue($value): RPGParam
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @param mixed $value
     * @return RPGParam
     */
    public function addValue($value): RPGParam
    {
        if ($this->type === "DS" && $this->length > 0) {
            if ($this->value === null) {
                $this->value = [];
            }
            $this->value = array_merge($this->value, $value->getParams());
        }
        return $this;
    }
}
