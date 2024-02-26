<?php

namespace Interpreter\Exceptions {
    // Define the code types as constants
    const SUCCESS = 0;
    const ERR_PARAMETER = 10;
    const ERR_INPUT = 11;
    const ERR_OUTPUT = 12;
    const ERR_XML_FORMAT = 31;
    const ERR_XML_STRUCT = 32;
    const ERR_SEMANTICS = 52;
    const ERR_TYPE = 53;
    const ERR_UNDEFINED = 54;
    const ERR_STACK = 55;
    const ERR_MISS = 56;
    const ERR_VALUE = 57;
    const ERR_STRING = 58;
    const ERR_INTERNAL = 99;

    // Define the exit method
    

    // Define the _Exception class as a separate class
    class _Exception extends \Exception {
        public function __construct($message, $code) {
            parent::__construct($message);
            $this->code = $code;
        }
        public function exit() {
            echo $this->message . "\n";
            exit($this->code);
        }
    }

    // Define other exception classes as separate classes
    class OptionError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_PARAMETER);
        }
    }

    class XMLFormatError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_XML_FORMAT);
        }
    }

    class XMLUnexpectedError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_XML_STRUCT);
        }
    }

    class SemanticError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_SEMANTICS);
        }
    }

    class TypeError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_TYPE);
        }
    }

    class VariableUndefinedError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_UNDEFINED);
        }
    }

    class FrameError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_STACK);
        }
    }

    class ValueUndefinedError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_MISS);
        }
    }

    class OperandValueError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_VALUE);
            $this->code = 57;
        }
    }

    class StringOperationError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_STRING);
        }
    }

    class InternalError extends _Exception {
        public function __construct($message) {
            parent::__construct($message, ERR_INTERNAL);
        }
    }
}

namespace Interpreter {
    use \Interpreter\Exceptions;
    use \Interpreter\Parser\Variable;
    use \Interpreter\Parser\Symb;
    use \Interpreter\Parser\Label;
    use \Interpreter\Parser\Type;
    
    function get_expected_types() {
        return [
            "MOVE" => [Variable::class, Symb::class],
            "CREATEFRAME" => null,
            "PUSHFRAME" => null,
            "POPFRAME" => null,
            "DEFVAR" => [Variable::class],
            "CALL" => [Label::class],
            "RETURN" => null,
            "PUSHS" => [Symb::class],
            "POPS" => [Variable::class],
            "ADD" => [Variable::class, Symb::class, Symb::class],
            "SUB" => [Variable::class, Symb::class, Symb::class],
            "MUL" => [Variable::class, Symb::class, Symb::class],
            "IDIV" => [Variable::class, Symb::class, Symb::class],
            "DIV" => [Variable::class, Symb::class, Symb::class],
            "LT" => [Variable::class, Symb::class, Symb::class],
            "GT" => [Variable::class, Symb::class, Symb::class],
            "EQ" => [Variable::class, Symb::class, Symb::class],
            "AND" => [Variable::class, Symb::class, Symb::class],
            "OR" => [Variable::class, Symb::class, Symb::class],
            "NOT" => [Variable::class, Symb::class],
            "CLEARS" => null,
            "ADDS" => null,
            "SUBS" => null,
            "MULS" => null,
            "DIVS" => null,
            "IDIVS" => null,
            "LTS" => null,
            "GTS" => null,
            "EQS" => null,
            "ANDS" => null,
            "ORS" => null,
            "NOTS" => null,
            "INT2CHARS" => null,
            "STRI2INTS" => null,
            "INT2FLOATS" => null,
            "FLOAT2INTS" => null,
            "JUMPIFEQS" => [Label::class],
            "JUMPIFNEQS" => [Label::class],
            "INT2CHAR" => [Variable::class, Symb::class],
            "STRI2INT" => [Variable::class, Symb::class, Symb::class],
            "INT2FLOAT" => [Variable::class, Symb::class],
            "FLOAT2INT" => [Variable::class, Symb::class],
            "READ" => [Variable::class, Type::class],
            "WRITE" => [Symb::class],
            "CONCAT" => [Variable::class, Symb::class, Symb::class],
            "STRLEN" => [Variable::class, Symb::class],
            "GETCHAR" => [Variable::class, Symb::class, Symb::class],
            "SETCHAR" => [Variable::class, Symb::class, Symb::class],
            "TYPE" => [Variable::class, Symb::class],
            "LABEL" => [Label::class],
            "JUMP" => [Label::class],
            "JUMPIFEQ" => [Label::class, Symb::class, Symb::class],
            "JUMPIFNEQ" => [Label::class, Symb::class, Symb::class],
            "EXIT" => [Symb::class],
            "DPRINT" => [Symb::class],
            "BREAK" => null,
        ];
    }

    // Define the FrameTypes enum as a separate class
    class FrameTypes {
        const TF = 1;
        const LF = 2;
        const GF = 3;
    }
    function deescape_str($s) {
        try {
            $res = $s;
            preg_match_all("/\\\\[\d]{3}/", $s, $matches);
            foreach ($matches[0] as $match) {
                $res = str_replace($match, chr(intval(substr($match, 1))), $res);
            }
            return $res;
        } catch (\Exception $e) {
            throw new Exceptions\StringOperationError($e->getMessage());
        }
    }

    class Instruction
    {
        public $opcode;
        public $order;
        public $operands;

        public function __construct(string $opcode, string $order, array $operands)
        {
            $this->opcode = strtoupper($opcode);
            $this->order = (int)$order;
            $this->operands = [];

            
            $expectTypeList = (\Interpreter\get_expected_types())[$this->opcode] ?? [];

            if (count($operands) !== count($expectTypeList)) {
                throw new Exceptions\XMLUnexpectedError("Invalid number of arguments in {$this->opcode}");
            }

            try {
                $idx = 0;
                foreach ($operands as $child) {
                    if ($child->getName() !== "arg" . strval($idx + 1)) {
                        throw new Exceptions\XMLUnexpectedError("Invalid operand at " . $this->opcode . ", order=" . $this->order);
                    }
                    $typeParser = $expectTypeList[$idx];
                    $this->operands[] = (new $typeParser($child))->parse();
                    $idx++;
                }
            } catch (\Exception $e) {
                throw new Exceptions\XMLUnexpectedError("Too many arguments for " . $this->opcode);
            }
        }

        public function __toString()
        {
            return "(order={" . $this->order . ",instruction=" . $this->opcode . ",operands=" . $this->operands. ")";
        }
    }
}


namespace Interpreter\Types {
    use \Interpreter\FrameTypes;
    use \Interpreter\Exceptions;
    /**
     * Class, that implements all the typing dependencies and methods.
     *
     * Nested classes represent literal of specific types, class inheritance represents their subtype (or more specific literals).
     *
     * Classes:
     * - Symb
     * - Var
     * - Type
     * - Label
     */

    enum _SymbTypes : string {
        const INT = "int";
        const STRING = "string";
        const BOOL = "bool";
        const FLOAT = "float";
        const NIL = "nil";
        const UNDEF = "undef";
    }

    class Type {
        private $value;

        public function __construct(string $value)
        {
            $this->value = $value;
        }

        public function __toString()
        {
            return (string)$this->value;
        }

        public function __repr()
        {
            return "TYPE(" . $this->value . ")";
        }

        public function __eq(Type $other)
        {
            return $this->value == $other->value;
        }

        public function __ne(Type $other)
        {
            return $this->value != $other->value;
        }

        public static function Int()
        {
            return new Type(_SymbTypes::INT);
        }

        public static function Float()
        {
            return new Type(_SymbTypes::FLOAT);
        }

        public static function String()
        {
            return new Type(_SymbTypes::STRING);
        }

        public static function Bool()
        {
            return new Type(_SymbTypes::BOOL);
        }

        public static function Nil()
        {
            return new Type(_SymbTypes::NIL);
        }

        public static function Undef()
        {
            return new Type(_SymbTypes::UNDEF);
        }

        public function isInt()
        {
            return $this->value == _SymbTypes::INT;
        }

        public function isFloat()
        {
            return $this->value == _SymbTypes::FLOAT;
        }

        public function isString()
        {
            return $this->value == _SymbTypes::STRING;
        }

        public function isBool()
        {
            return $this->value == _SymbTypes::BOOL;
        }

        public function isNil()
        {
            return $this->value == _SymbTypes::NIL;
        }

        public function isUndef()
        {
            return $this->value == _SymbTypes::UNDEF;
        }

        public static function toType(string $type)
        {
            if ($type instanceof Type) {
                return $type;
            }        
            if ($type === "int") {
                return Type::Int();
            } elseif ($type === "string") {
                return Type::String();
            } elseif ($type === "bool") {
                return Type::Bool();
            } elseif ($type === "float") {
                return Type::Float();
            } else {
                return Type::Nil();
            }
        }
    }
    class Symb {
        public Type $type;
        public $value;

        public function __construct($value, $type) {
            $this->type = Type::toType($type);
            try {
                if ($this->type->isInt()) {
                    $this->value = intval($value);
                } elseif ($this->type->isBool()) {
                    if (is_bool($value)) {
                        $this->value = $value;
                    } else {
                        $this->value = strtolower($value) === "true" ? true : false;
                    }
                } elseif ($this->type->isFloat()) {
                    $this->value = is_string($value) ? floatval($value) : $value;
                } elseif ($this->type->isString()) {
                    $this->value = $value === null ? "" : $value;
                } else {
                    $this->value = $value;
                }
            } catch (\Exception $e) {
                throw new Exceptions\XMLUnexpectedError("{$value} is not a valid int");
            }
        }

        public static function Int($value) {
            return new self($value, Type::Int());
        }

        public static function Float($value) {
            return new self($value, Type::Float());
        }

        public static function String($value) {
            return new self($value, Type::String());
        }

        public static function Bool($value) {
            return new self($value, Type::Bool());
        }

        public static function Nil($value) {
            /**
             * Creates instance of Symb with type nil
             */
            return new self($value, Type::Nil());
        }

        public static function Undef($value) {
            /**
             * Creates instance of Symb with type nil
             */
            return new self($value, Type::Undef());
        }

        public function kgt($other) {
            if ($other instanceof Variable || $other instanceof Symb) {
                if ($this->type->isNil() || $other->type->isNil()) {
                    throw new Exceptions\TypeError("GT doesn't support NIL");
                }
                if ($this->type != $other->type) {
                    throw new \Interpreter\Exceptions\TypeError("Incompatible types in LT");
                }
                if ($this->type->isString()) {
                    return Symb::Bool(\Interpreter\deescape_str($this->value) > \Interpreter\deescape_str($other->value));
                } else {
                    return Symb::Bool($this->value > $other->value);
                }
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function klt($other) {
            if ($other instanceof Variable || $other instanceof Symb) {
                if ($this->type->isNil() || $other->type->isNil()) {
                    throw new \Interpreter\Exceptions\TypeError("LT doesn't support NIL");
                }
                if ($this->type != $other->type) {
                    throw new \Interpreter\Exceptions\TypeError("Incompatible types in LT");
                }
                if ($this->type->isString()) {
                    return Symb::Bool(\Interpreter\deescape_str($this->value)) < \Interpreter\deescape_str($other->value);
                } else {
                    return Symb::Bool($this->value < $other->value);
                }
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function kbool() {
            return (bool)$this->value;
        }

        public function keq($other) {
            if ($other instanceof Variable || $other instanceof Symb) {
                if ($this->type != $other->type && !$this->type->isNil() && !$other->type->isNil()) {
                    throw new \Interpreter\Exceptions\TypeError("Operands must be of the same type");
                }
                if ($this->type->isString()) {
                    return Symb::Bool(\Interpreter\deescape_str(($this->value)) == \Interpreter\deescape_str(($other->value)));
                } else {
                    return Symb::Bool($this->value == $other->value);
                }
            }
            throw new Exceptions\InternalError("Not Implemented");
        }
        public function kinvert() {
            if (!$this->type->isBool()) {
                throw new Exceptions\TypeError("AND/OR/NOT supports bool only");
            }
            return Symb::Bool(!$this->value);
        }

        public function kand($other) {
            if ($other instanceof Types\Variable || $other instanceof Symb) {
                if (!$this->type->isBool() || !$other->type->isBool()) {
                    throw new Exceptions\TypeError("AND/OR/NOT supports bool only");
                }
                return Symb::Bool($this->value && $other->value);
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function kor($other) {
            if ($other instanceof Types\Variable || $other instanceof Symb) {
                if (!$this->type->isBool() || !$other->type->isBool()) {
                    throw new Exceptions\TypeError("AND/OR/NOT supports bool only");
                }
                return Symb::Bool($this->value || $other->value);
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function kadd($other) {
            if ($other instanceof Types\Variable || $other instanceof Symb) {
                if ($other->type->isNil() && $other instanceof Types\Variable) {
                    throw new Exceptions\ValueUndefinedError("Missing value");
                }
                if (!($this->type->isInt() && $other->type->isInt() || $this->type->isFloat() && $other->type->isFloat())) {
                    throw new Exceptions\TypeError("Incompatible types of " . $other . " and " . $this);
                }
                if ($this->type->isInt()) {
                    return Symb::Int(intval($this->value + $other->value));
                } else {
                    return Symb::Float($this->value + $other->value);
                }
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function ksub($other) {
            if ($other instanceof Types\Variable || $other instanceof Symb) {
                if ($other->type->isNil() && $other instanceof Types\Variable) {
                    throw new Exceptions\ValueUndefinedError("Missing value");
                }
                if (!($this->type->isInt() && $other->type->isInt() || $this->type->isFloat() && $other->type->isFloat())) {
                    throw new Exceptions\TypeError("Incompatible types of " . $other . " and " . $this);
                }
                if ($this->type->isInt()) {
                    return Symb::Int(intval($this->value - $other->value));
                } else {
                    return Symb::Float($this->value - $other->value);
                }
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function kmul($other) {
            if ($other instanceof Types\Variable || $other instanceof Symb) {
                if ($other->type->isNil() && $other instanceof Types\Variable) {
                    throw new Exceptions\ValueUndefinedError("Missing value");
                }
                if (!($this->type->isInt() && $other->type->isInt() || $this->type->isFloat() && $other->type->isFloat())) {
                    throw new Exceptions\TypeError("Incompatible types of " . $other . " and " . $this);
                }
                if ($this->type->isInt()) {
                    return Symb::Int(intval($this->value * $other->value));
                } else {
                    return Symb::Float($this->value * $other->value);
                }
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function kfloordiv($other) {
            if ($other instanceof Variable || $other instanceof Symb) {
                // Check for nil, type compatibility, and division by zero
                if ($other->value == 0) {
                    throw new Exceptions\OperandValueError("0 division is not allowed");
                }
                if ($this->type->isInt()) {
                    return Symb::Int(intval($this->value / $other->value));
                }
                return Symb::Int($this->value / $other->value);
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function kdiv($other) {
            if ($other instanceof Variable || $other instanceof Symb) {
                // Check for nil, type compatibility, and division by zero
                if ($other->value == 0) {
                    throw new Exceptions\OperandValueError("0 division is not allowed");
                }
                if ($this->type->isInt()) {
                    return Symb::Int(intval($this->value / $other->value));
                }
                return Symb::Float($this->value / $other->value);
            }
            throw new Exceptions\InternalError("Not Implemented");
        }

        public function getChar($arg) {
            if (!$arg->type->isInt()) {
                throw new \Interpreter\Exceptions\TypeError("Invalid index type");
            }
            if (!$this->type->isString()) {
                throw new \Interpreter\Exceptions\TypeError("Expected string");
            }
            if ($arg->value < 0) {
                throw new \Interpreter\Exceptions\StringOperationError("Index can't be negative");
            }
            if(strlen($this->value) <= (int)$arg->value) {
                throw new \Interpreter\Exceptions\StringOperationError("Index out of range");
            }
            return Symb::String(strval($this->value)[$arg->value]);
        }

        public function __str() {
            if ($this->type->isBool()) {
                return $this->value ? "true" : "false";
            }
            if ($this->type->isFloat()) {
                return floatval($this->value);
            }
            return $this->value;
        }

        public function __toString() {
            if ($this->type->isBool()) {
                return $this->value ? "true" : "false";
            }
            if ($this->type->isFloat()) {
                return strval($this->value);
            }
            return $this->value !== null && $this->value !== "" ? strval($this->value) : "";
        }

        public function __repr() {
            return "SYMB(" . $this->type . ", " . $this->value . ")";
        }
    }    

    class Variable extends Symb
    {
        public $name;
        public $scope;

        public function __construct($name, $scope)
        {
            parent::__construct(null, _SymbTypes::NIL);
            $this->name = $name;
            $this->scope = $this->toScope($scope);
        }

        public function setSymb($symb)
        {
            $this->type = $symb->type;
            $this->value = $symb->value;
        }

        public function toScope($scope)
        {
            switch ($scope) {
                case "TF":
                    return FrameTypes::TF;
                case "LF":
                    return FrameTypes::LF;
                case "GF":
                    return FrameTypes::GF;
                default:
                    throw new Exceptions\InternalError("Scope {$scope} is undefined");
            }
        }

        public function __toString()
        {
            return strval($this->value);//"VAR({$this->name}, {$this->value})";
        }
    }

    class Label {
        public $value;
    
        public function __construct($value) {
            $this->value = $value;
        }
    
        public function __toString() {
            return "LABEL(" . $this->value . ")";
        }
    }
}

namespace Interpreter {
    use \Interpreter\FrameTypes;
    use \Interpreter\Exceptions\ValueUndefinedError;
    use \Interpreter\Exceptions\StringOperationError;
    use \Interpreter\Exceptions\TypeError;
    use \Interpreter\Exceptions\SemanticError;
    use \Interpreter\Exceptions\FrameError;
    use \Interpreter\Exceptions\InternalError;
    use \Interpreter\Exceptions\VariableUndefinedError;
    use \Interpreter\Types\Variable;
    use \Interpreter\Types\Symb;
    
    class Frame
    {
        private $data = [];
    
        public function getVar($varName)
        {
            return $this->data[$varName] ?? null;
        }
    
        public function setVar(Variable $var)
        {
            if ($this->getVar($var->name) !== null) {
                throw new SemanticError("Variable {$var->name} is already defined at {$var->scope}");
            }
    
            $var->type = Types\Type::Undef();
            $this->data[$var->name] = $var;
        }
    
        public function __toString()
        {
            return print_r($this->data, true);
        }
    }
    
    class FrameManager
    {
        private $gframe;
        private $lframe;
        private $tframe;
    
        public function __construct()
        {
            $this->gframe = new Frame();
            $this->lframe = [];
            $this->tframe = null;
        }
    
        public function setVar(Variable $var)
        {
            switch ($var->scope) {
                case FrameTypes::GF:
                    $this->gframe->setVar($var);
                    break;
                case FrameTypes::TF:
                    if ($this->tframe === null) {
                        throw new FrameError("TF is not defined");
                    }
                    $this->tframe->setVar($var);
                    break;
                case FrameTypes::LF:
                    if (empty($this->lframe)) {
                        throw new FrameError("LF is not defined");
                    }
                    end($this->lframe)->setVar($var);
                    break;
                default:
                    throw new InternalError("Something went wrong. Checkout set_var() in FrameManager");
            }
        }
    
        public function getVar(Variable $var)
        {
            switch ($var->scope) {
                case FrameTypes::TF:
                    if ($this->tframe === null) {
                        throw new FrameError("Frame TF does not exist.");
                    }
                    $result = $this->tframe->getVar($var->name);
                    break;
                case FrameTypes::LF:
                    if (empty($this->lframe)) {
                        throw new FrameError("Frame LF does not exist.");
                    }
                    $result = end($this->lframe)->getVar($var->name);
                    break;
                case FrameTypes::GF:
                    $result = $this->gframe->getVar($var->name);
                    break;
                default:
                    throw new FrameError("Frame does not exist.");
            }
    
            if ($result === null) {
                throw new VariableUndefinedError("Variable '{$var->name}' is not defined at {$var->scope}");
            }
    
            return $result;
        }
    
        public function createFrame()
        {
            $this->tframe = new Frame();
        }
    
        public function pushFrame()
        {
            if ($this->tframe === null) {
                throw new FrameError("Unable to push undefined TF to LF");
            }
            $this->lframe[] = $this->tframe;
            $this->tframe = null;
        }
    
        public function popFrame()
        {
            if (empty($this->lframe)) {
                throw new FrameError("Unable to pop LF when it is empty");
            }
            $this->tframe = array_pop($this->lframe);
        }
    
        public function __toString()
        {
            return "GF: " . $this->gframe . "\nTF: " . $this->tframe . "\nLF: " . print_r($this->lframe, true);
        }
    }

    class StackManager {
        private $_data = [];
    
        public function push($symb) {
            $this->_data[] = $symb;
        }
    
        public function pop() {
            if ($this->is_empty()) {
                throw new ValueUndefinedError("Data stack is empty");
            }
            return array_pop($this->_data);
        }
    
        public function get_symb() {
            if (count($this->_data) < 1) {
                throw new ValueUndefinedError("Data stack is empty");
            }
            return end($this->_data);
        }
    
        public function get_symb_symb() {
            if (count($this->_data) < 2) {
                throw new ValueUndefinedError("Data stack is empty");
            }
            return [$this->_data[count($this->_data) - 2], end($this->_data)];
        }
    
        public function pop_symb_symb() {
            $symb2 = $this->pop();
            $symb1 = $this->pop();
            return [$symb1, $symb2];
        }
    
        public function clear() {
            $this->_data = [];
        }
    
        public function add() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->kadd($symb2));
        }
    
        public function sub() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->ksub($symb2));
        }
    
        public function mul() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->kmul($symb2));
        }
    
        public function div() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->kdiv($symb2));
        }
    
        public function idiv() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->kdiv($symb2));
        }

        public function lt() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->klt($symb2));
        }
    
        public function gt() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->kgt($symb2));
        }
    
        public function eq() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->keq($symb2));
        }
    
        public function ands() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->kand($symb2));
        }
    
        public function ors() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push($symb1->kor($symb2));
        }
    
        public function nots() {
            $symb1 = $this->pop();
            $this->push($symb1->kinvert());
        }
    
        public function int2char() {
            $symb = $this->pop();
            if (!$symb->type->is_int()) {
                throw new TypeError("Invalid type in INT2CHARS");
            }
            try {
                $this->push(Types\Symb::String(chr($symb->value)));
            } catch (\Exception $e) {
                throw new StringOperationError("{$symb} is not valid unicode");
            }
        }
    
        public function stri2int() {
            list($symb1, $symb2) = $this->pop_symb_symb();
            $this->push(Types\Symb::Int(ord($symb1[$symb2]->value)));
        }
    
        public function int2float() {
            $symb1 = $this->pop();
            if ($symb1->type->isNil()) {
                throw new ValueUndefinedError("Cannot convert NIL to FLOAT");
            }
            if (!$symb1->type->isInt()) {
                throw new TypeError("Invalid type in convert");
            }
            $this->push(Types\Symb::Float($symb1->value));
        }
    
        public function float2int() {
            $symb1 = $this->pop();
            if ($symb1->type->isNil()) {
                throw new ValueUndefinedError("Cannot convert NIL to INT");
            }
            if (!$symb1->type->isFloat()) {
                throw new TypeError("Invalid type in convert");
            }
            $this->push(Types\Symb::Int((int)$symb1->value));
        }
    
        public function is_empty() {
            return empty($this->_data);
        }
    
        public function __toString() {
            return json_encode($this->_data);
        }
    }

    function read_input_generator($filename = null) {
        if ($filename === null) {
            $file = "php://stdin";
        } else {
            $file = fopen($filename, 'r');
        }
        try {
            while ($line = fgets($file)) {
                yield rtrim($line, "\n");
            }
        } finally {
            if ($file !== "php://stdin") {
                fclose($file);
            }
        }
    }
    
    function _write($symb, $file = "php://stdout") {
        $handle = fopen($file, "w");
        if ($symb->type->isString()) {
            fwrite($handle, deescape_str(strval($symb->value))); 
        } else {
            fwrite($handle, strval($symb));
        }
        fclose($handle);
    }
    
    class InstructionManager {
        private $instructions = [];
    
        public function insert($instruction) {
            $this->instructions[] = $instruction;
        }
    
        public function instructions() {
            usort($this->instructions, function($a, $b) {
                return $a->order - $b->order;
            });
            return $this->instructions;
        }
    }
    
    class CallStack {
        private $calls = [];
    
        public function push($label) {
            $this->calls[] = $label;
        }
    
        public function pop() {
            if ($this->isEmpty()) {
                throw new ValueUndefinedError("Call stack is empty");
            }
            return array_pop($this->calls);
        }
    
        public function isEmpty() {
            return empty($this->calls);
        }
    
        public function __toString() {
            return "Call stack: " . print_r($this->calls, true);
        }
    }
}

namespace Interpreter\Parser {
    use \Interpreter\Exceptions\XMLUnexpectedError;
    use \Interpreter\Exceptions\TypeError;

    class Instruction {
        private $element;
        private $opcode;
        private $order;
        private $operands;

        public function __construct($element) {
            $this->element = $element;
            $this->opcode = $element->attributes()->opcode;
            $this->order = $element->attributes()->order;
            $this->operands = iterator_to_array($element->children());
        }
    
        public function parse() {
            if (!$this->is_valid()) {
                throw new XMLUnexpectedError("Instruction is not valid");
            }
            return new \Interpreter\Instruction($this->opcode, $this->order, $this->operands);
        }
    
        public function is_valid() {
            return ($this->opcode !== null
                && $this->order !== null
                && array_key_exists(strtoupper($this->opcode), \Interpreter\get_expected_types())
                && (int)$this->order > 0
                && $this->element->getName() === 'instruction');
        }
    }

    abstract class _GenericParseType {
        protected $element;
        protected $attr;
        protected $text;

        public function __construct($element) {
            $this->element = $element;
            $this->attr = strval($element->attributes()->type);
            $this->text = trim((string)$element);
        }

        abstract public function parse();
        abstract public function is_valid();
    }

    class Symb extends _GenericParseType {
        public function __construct($element) {
            parent::__construct($element);
        }

        public function parse() {
            if (!$this->is_valid()) {
                return (new Variable($this->element))->parse();
            }
            return new \Interpreter\Types\Symb($this->text, strtolower($this->attr));
        }

        public function is_valid() {
            if (!preg_match("/^(int|bool|string|float|nil)$/", $this->attr)) {
                return false;
            }
            return true;
        }
    }    

    class Variable extends _GenericParseType {
        public function __construct($element) {
            parent::__construct($element);
        }

        public function parse() {
            if (!$this->is_valid()) {
                throw new TypeError("Unexpected type");
            }
            list($scope, $name) = explode('@', $this->text);
            return new \Interpreter\Types\Variable($name, $scope);
        }

        public function is_valid() {
            return ($this->attr === "var"
                && preg_match("/^(GF|LF|TF)@[a-zA-Z_\-$&%*!?][\w\-$&%*!?]*$/", $this->text));
        }
    }

    class Type extends _GenericParseType {
        public function __construct($element) {
            parent::__construct($element);
        }

        public function parse() {
            if (!$this->is_valid()) {
                throw new TypeError("Unexpected type");
            }

            return \Interpreter\Types\Type::toType(strtolower($this->text));
        }

        public function is_valid() {
            return ($this->attr === "type"
                && preg_match("/^(int|bool|string|float|nil)$/", $this->text));
        }
    }

    class Label extends _GenericParseType {
        public function __construct($element) {
            parent::__construct($element);
        }

        public function parse() {
            if (!$this->is_valid()) {
                throw new TypeError("Unexpected type");
            }

            return new \Interpreter\Types\Label($this->text);
        }

        public function is_valid() {
            return ($this->attr === "label"
                && preg_match("/^[a-zA-Z_\-$&%*!?][\w\-$&%*!?]*$/", $this->text));
        }
    }
}

namespace Interpreter {
    use \Interpreter\Exceptions\OptionError;
    use \Interpreter\Exceptions\XMLUnexpectedError;
    use \Interpreter\Exceptions\SemanticError;

    $input = null;

    
    try {
        $opts = getopt("hi", ["help", "input:"]);
    
        foreach ($opts as $opt => $arg) {
            switch ($opt) {
                case 'h':
                case 'help':
                    if (count($opts) > 1) {
                        throw new OptionError("Option --help(-h) can only be used without any other options)");
                    }
                    echo $USAGE;
                    exit(0);
                case 'i':
                case 'input':
                    $input = $arg;
                    break;
            }
        }
        
        if ($input === null) {
            $input = 'php://stdin';
        }

        try {
            $tree = simplexml_load_file($input);
        } catch (\Exception $e) {
            throw new OptionError($input . ": No such file or directory");
        }
    
        if ($tree->getName() != "program") {
            throw new XMLUnexpectedError("Expected <program> element");
        }
        
        $IManager = new InstructionManager();
   
        foreach ($tree->instruction as $child) {
            $IManager->insert((new Parser\Instruction($child))->parse());
        }
    
        $instructions = $IManager->instructions();
    
        // Duplicate order check
        $orders = array_map(function ($i) {
            return $i->order;
        }, $instructions);
        if (count($orders) != count(array_unique($orders))) {
            throw new XMLUnexpectedError("Duplicate order");
        }
    
        $FManager = new FrameManager();
        $SManager = new StackManager();
        $CStack = new CallStack();
        $labels = [];
    
        foreach ($instructions as $idx => $i) {
            if ($i->opcode == "LABEL") {
                [$label] = $i->operands;
                if (!isset($labels[$label->value])) {
                    $labels[$label->value] = $idx;
                } else {
                    throw new SemanticError("Label {$label->value} is already defined");
                }
            }
        }
    
        $idx = 0;

    
        while ($idx < count($instructions)) {
            $instruction = $instructions[$idx];
    
            if ($instruction->opcode == "CREATEFRAME") {
                $FManager->createFrame();
            } elseif ($instruction->opcode == "PUSHFRAME") {
                $FManager->pushFrame();
            } elseif ($instruction->opcode == "POPFRAME") {
                $FManager->popFrame();
            } elseif ($instruction->opcode == "DEFVAR") {
                list($var) = $instruction->operands;
                $FManager->setVar($var);
            } elseif ($instruction->opcode == "WRITE") {
                list($symb) = $instruction->operands;
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                    if ($symb->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                _write($symb);
            } elseif ($instruction->opcode == "DPRINT") {
                list($symb) = $instruction->operands;
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                }
                _write($symb, $file = STDERR);
            } elseif ($instruction->opcode == "BREAK") {
                fwrite(STDERR, $instruction->order);
                fwrite(STDERR, $FManager);
                fwrite(STDERR, $SManager);
                fwrite(STDERR, $CStack);
            } elseif ($instruction->opcode == "READ") {
                list($var, $_type) = $instruction->operands;
                try {
                    $var = $FManager->getVar($var);
                    $value = readline("Input:");
                    if ($_type->isInt()) {
                        intval($value);
                    }
                    
                    $var->setSymb(new Types\Symb($value, $_type));
                } catch (\Exception $e) {
                    $var->setSymb(Types\Symb::Nil(null));
                }
            } elseif ($instruction->opcode == "MOVE") {
                list($var, $symb) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                    if ($symb->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                $var->setSymb($symb);
            } elseif ($instruction->opcode == "ADD") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb1->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb2->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                $var->setSymb($symb1->kadd($symb2));
            } else if ($instruction->opcode == "SUB") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb1->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb2->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                $var->setSymb($symb1->ksub($symb2));
            } elseif ($instruction->opcode == "MUL") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb1->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb2->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                $var->setSymb($symb1->kmul($symb2));
            } elseif ($instruction->opcode == "IDIV") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb1->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb2->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                $var->setSymb($symb1->kdiv($symb2));
            } elseif ($instruction->opcode == "DIV") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb1->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb2->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                $var->setSymb($symb1->kdiv($symb2));
            } elseif ($instruction->opcode == "EXIT") {
                list($symb) = $instruction->operands;
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                    if ($symb->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if (!$symb->type->isInt()) {
                    throw new Exceptions\TypeError("EXIT accepts only integer");
                }
                if ($symb->value < 0 || $symb->value > 49) {
                    throw new Exceptions\OperandValueError("EXIT code must be in interval <0, 49>");
                }
                exit($symb->value);
            } elseif ($instruction->opcode == "TYPE") {
                list($var, $symb) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                }
                if ($symb->type->isUndef()) {
                    $var->setSymb(Types\Symb::Nil(null));
                } else {
                    $var->setSymb(Types\Symb::String(strval($symb->type)));
                }
            } elseif ($instruction->opcode == "LT") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                $var->setSymb($symb1->klt($symb2));
            } elseif ($instruction->opcode == "GT") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                $var->setSymb($symb1->kgt($symb2));
            } elseif ($instruction->opcode == "EQ") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                $var->setSymb($symb1->ket($symb2));
            } elseif ($instruction->opcode == "AND") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb1->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb2->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                $var->setSymb($symb1->kand($symb2));
            } elseif ($instruction->opcode == "OR") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb1->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb2->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Missing value");
                    }
                }
                $var->setSymb($symb1->kor($symb2));
            } elseif ($instruction->opcode == "NOT") {
                list($var, $symb1) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb1->type->isNil()) {
                        throw new Exceptions\TypeError("Missing value");
                    }
                }
                $var->setSymb($symb1->kinvert());
            } elseif ($instruction->opcode == "INT2CHAR") {
                list($var, $symb) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                    if ($symb->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if (!$symb->type->isInt()) {
                    throw new Exceptions\TypeError("Invalid type in INT2CHAR");
                }
                try {
                    $var->setSymb(Types\Symb::String(chr($symb->value)));
                } catch (\Exception $e) {
                    throw new Exceptions\StringOperationError($e);
                }
            } elseif ($instruction->opcode == "STRI2INT") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 =$FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                $var->setSymb(Types\Symb::Int(ord($symb1[$symb2]->value)));
            } elseif ($instruction->opcode == "INT2FLOAT") {
                list($var, $symb) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                    if ($symb->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Cannot convert NIL to FLOAT");
                    }
                }
                if (!$symb->type->isInt()) {
                    throw new Exceptions\TypeError("Invalid type in convert");
                }
                $var->setSymb(Types\Symb::Float(floatval($symb->value)));
            } elseif ($instruction->opcode == "FLOAT2INT") {
                list($var, $symb) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                    if ($symb->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                    if ($symb->type->isNil()) {
                        throw new Exceptions\ValueUndefinedError("Cannot convert NIL to INT");
                    }
                }
                if (!$symb->type->isFloat()) {
                    throw new Exceptions\TypeError("Invalid type in convert");
                }
                $var->setSymb(Types\Symb::Int(intval($symb->value)));
            } elseif ($instruction->opcode == "LABEL") {

            } elseif ($instruction->opcode == "CLEARS") {
                $SManager->clear();
            } elseif ($instruction->opcode == "ADDS") {
                $SManager->add();
            } elseif ($instruction->opcode == "SUBS") {
                $SManager->sub();
            } elseif ($instruction->opcode == "MULS") {
                $SManager->mul();
            } elseif ($instruction->opcode == "DIVS") {
                $SManager->div();
            } elseif ($instruction->opcode == "IDIVS") {
                $SManager->idiv();
            } elseif ($instruction->opcode == "LTS") {
                $SManager->lt();
            } elseif ($instruction->opcode == "GTS") {
                $SManager->gt();
            } elseif ($instruction->opcode == "EQS") {
                $SManager->eq();
            } elseif ($instruction->opcode == "ANDS") {
                $SManager->ands();
            } elseif ($instruction->opcode == "ORS") {
                $SManager->ors();
            } elseif ($instruction->opcode == "NOTS") {
                $SManager->nots();
            } elseif ($instruction->opcode == "INT2CHARS") {
                $SManager->int2char();
            } elseif ($instruction->opcode == "STRI2INTS") {
                $SManager->stri2int();
            } elseif ($instruction->opcode == "FLOAT2INTS") {
                $SManager->float2int();
            } elseif ($instruction->opcode == "INT2FLOATS") {
                $SManager->int2float();
            } if ($instruction->opcode == "PUSHS") {
                $symb = $instruction->operands[0];
                if ($symb instanceof Types\Variable) {
                    $symb = $FManager->getVar($symb);
                    if ($symb->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                $SManager->push($symb);
            } elseif ($instruction->opcode == "POPS") {
                $var = $instruction->operands[0];
                $var = $FManager->getVar($var);
                $var->setSymb($SManager->pop());
            } elseif ($instruction->opcode == "CONCAT") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if (!$symb1->type->isString() || !$symb2->type->isString()) {
                    throw new Exceptions\TypeError("Both operands must be of type string in CONCAT");
                }
                $var->setSymb(Types\Symb::String($symb1->value . $symb2->value));
            } elseif ($instruction->opcode == "STRLEN") {
                list($var, $symb1) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if (!$symb1->type->isString()) {
                    throw new Exceptions\TypeError("Operand must be of type string in STRLEN");
                }
                $var->setSymb(Types\Symb::Int(strlen($symb1->value)));
            } elseif ($instruction->opcode == "GETCHAR") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if (!$symb1->type->isString()) {
                    throw new Exceptions\TypeError("Second operand in GETCHAR must be string");
                }
                if (!$symb2->type->isInt()) {
                    throw new Exceptions\TypeError("Third operand in GETCHAR must be integer");
                }
                if ($symb2->value < 0) {
                    throw new Exceptions\StringOperationError("Second operand in GETCHAR must be >= 0");
                }
                $var->setSymb(Types\Symb::String($symb1)->getChar($symb2));
            } elseif ($instruction->opcode == "SETCHAR") {
                list($var, $symb1, $symb2) = $instruction->operands;
                $var = $FManager->getVar($var);
                if ($var->type->isUndef()) {
                    throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                }
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if (!$var->type->isString()) {
                    throw new Exceptions\TypeError("First operand in SETCHAR must be string");
                }
                if (!$symb1->type->isInt()) {
                    throw new Exceptions\TypeError("Second operand in SETCHAR must be integer");
                }
                if ($symb1->value < 0) {
                    throw new Exceptions\StringOperationError("Second operand in SETCHAR must be >= 0");
                }
                if (!$symb2->type->isString()) {
                    throw new Exceptions\TypeError("Third operand in SETCHAR must be string");
                }
                try {
                    $val1 = deescape_str($var->value);
                    $val2 = deescape_str($symb2->value);
                    $listValue = str_split($val1);
                    $listValue[$symb1->value] = $val2[0];
                    $var->setSymb(Types\Symb::String(implode("", $listValue)));
                } catch (\Exception $e) {
                    throw new Exceptions\StringOperationError($e);
                }
            } elseif ($instruction->opcode == "CALL") {
                $label = $instruction->operands[0];
                $jump_to = $labels[$label->value] ?? null;
                if ($jump_to === null) {
                    throw new Exceptions\SemanticError("Label " . $label->value . " is undefined");
                }
                $CStack->push($idx);
                $idx = $jump_to;
            } elseif ($instruction->opcode == "RETURN") {
                $jump_to = $CStack->pop();
                if ($jump_to === null) {
                    throw new Exceptions\SemanticError("Label " . $label->value . " is undefined");
                }
                $idx = $jump_to;
            } elseif ($instruction->opcode == "JUMP") {
                $label = $instruction->operands[0];
                $jump_to = $labels[$label->value] ?? null;
                if ($jump_to === null) {
                    throw new Exceptions\SemanticError("Label " . $label->value . " is undefined");
                }
                $idx = $jump_to;
            } elseif ($instruction->opcode == "JUMPIFEQ") {
                list($label, $symb1, $symb2) = $instruction->operands;
                $jump_to = $labels[$label->value] ?? null;
                if ($jump_to === null) {
                    throw new Exceptions\SemanticError("Label " . $label->value . " is undefined");
                }
                if (!$symb1->type->isNil() && !$symb2->type->isNil()) {
                    if ($symb1->type != $symb2->type) {
                        throw new Exceptions\TypeError("Incompatible types in JUMPIFEQ");
                    }
                }
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb1->keq($symb2)->value) {
                    $idx = $jump_to;
                }
            }  elseif ($instruction->opcode == "JUMPIFNEQ") {
                list($label, $symb1, $symb2) = $instruction->operands;
                $jump_to = $labels[$label->value] ?? null;
                if ($jump_to === null) {
                    throw new Exceptions\SemanticError("Label " . $label->value . " is undefined");
                }
                if (!$symb1->type->isNil() && !$symb2->type->isNil()) {
                    if ($symb1->type != $symb2->type) {
                        throw new Exceptions\TypeError("Incompatible types in JUMPIFNEQ");
                    }
                }
                if ($symb1 instanceof Types\Variable) {
                    $symb1 = $FManager->getVar($symb1);
                    if ($symb1->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if ($symb2 instanceof Types\Variable) {
                    $symb2 = $FManager->getVar($symb2);
                    if ($symb2->type->isUndef()) {
                        throw new Exceptions\ValueUndefinedError("Var's value is undefined");
                    }
                }
                if (!$symb1->keq($symb2)->value) {
                    $idx = $jump_to;
                }
            } elseif ($instruction->opcode == "JUMPIFEQS") {
                list($label) = $instruction->operands;
                $jump_to = $labels[$label->value] ?? null;
                if ($jump_to === null) {
                    throw new Exceptions\SemanticError("Label " . $label->value . " is undefined");
                }
                list($symb1, $symb2) = $SManager->get_symb_symb();
                if (!$symb1->type->isNil() && !$symb2->type->isNil()) {
                    if ($symb1->type != $symb2->type) {
                        throw new Exceptions\TypeError("Incompatible types in JUMPIFEQS");
                    }
                }
                if ($symb1->keq($symb2)->value) {
                    $idx = $jump_to;
                }
            } elseif ($instruction->opcode == "JUMPIFNEQS") {
                list($label) = $instruction->operands;
                $jump_to = $labels[$label->value] ?? null;
                if ($jump_to === null) {
                    throw new Exceptions\SemanticError("Label " . $label->value . " is undefined");
                }
                list($symb1, $symb2) = $SManager->get_symb_symb();
                if (!$symb1->type->isNil() && !$symb2->type->isNil()) {
                    if ($symb1->type != $symb2->type) {
                        throw new Exceptions\TypeError("Incompatible types in JUMPIFEQS");
                    }
                }
                if (!$symb1->keq($symb2)->value) {
                    $idx = $jump_to;
                }
            }
            
            $idx++;
        }
    } catch (\Exception $e) {
        echo $e->getMessage();
        exit($e->getCode());
    }
}