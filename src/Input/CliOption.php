<?hh // strict

namespace kilahm\Clio\Input;

use \LogicException;
use \InvalidArgumentException;
use kilahm\Clio\Clio;
use kilahm\Clio\Enum\CliOptionType;
use kilahm\Clio\Exception\ClioException;
use kilahm\Clio\Exception\InvalidOptionValue;
use kilahm\Clio\Exception\MissingOptionValue;

<<__ConsistentConstruct>>
class CliOption
{
    private Set<string> $aliases = Set{};
    private string $value = '';
    private bool $hasDefault = false;
    private int $count = 0;
    private CliOptionType $type = CliOptionType::Flag;
    private ?string $regex = null;
    private ?(function(string):bool) $validator = null;
    private ?string $invalidMessage = null;
    private Vector<string> $valueList = Vector{};
    private ?Vector<string> $defaultValueList = null;

    public string $description = '';

    public function __construct(public string $name, private Clio $clio)
    {
        if( ! $this->isValidName($name)) {
            throw new InvalidArgumentException($name . ' is not a valid name for an option.');
        }
    }

    public function aka(string $name) : this
    {
        if( ! $this->isValidName($name)) {
            throw new InvalidArgumentException($name . ' is not a valid name for an option.');
        }
        $this->aliases->add($name);
        return $this;
    }

    public function describedAs(string $description) : this
    {
        $this->description = $description;
        return $this;
    }

    private function isValidName(string $name) : bool
    {
        if(preg_match('|--|', $name)) {
            return false;
        }
        return (bool)preg_match('|^[a-zA-Z][a-zA-Z_\-]*$|', $name);
    }

    public function getAliases() : Set<string>
    {
        return $this->aliases->toSet();
    }

    public function getAllNames() : string
    {
        $val = $this->hasVal() ? '<Value>' : '';
        $format = (string $in) ==> {
            $long = strlen($in) > 1;
            return ($long ? '--' : '-')
                . $in
                . ($long && $this->hasVal() ? ' ' : '')
                . $val
                ;
        };
        $out = $format($this->name);
        if(!$this->aliases->isEmpty()) {
            $out .= PHP_EOL
                . implode(PHP_EOL, $this->aliases->map($format));
        }
        return $out;
    }

    public function accumulates() : this
    {
        $this->type = CliOptionType::Accumulator;
        return $this;
    }

    public function isPath(?string $default = null) : this
    {
        $this->type = CliOptionType::Path;
        if($default !== null) {
            $this->hasDefault = true;
            $this->value = $default;
        }
        return $this;
    }

    public function withValue(?string $default = null) : this
    {
        $this->type = CliOptionType::Value;
        if($default !== null) {
            $this->hasDefault = true;
            $this->value = $default;
        }
        return $this;
    }

    public function withManyValues(?Traversable<string> $default = null) : this
    {
        $this->type = CliOptionType::MultiValued;
        if($default !== null) {
            $this->defaultValueList = Vector::fromItems($default);
        }
        return $this;
    }

    public function getType() : CliOptionType
    {
        return $this->type;
    }

    public function incrementCount() : void
    {
        $this->count += 1;
    }

    public function wasPresent() : bool
    {
        $this->clio->parseInput();
        return $this->count > 0;
    }

    public function getCount() : int
    {
        $this->clio->parseInput();
        return $this->count;
    }

    public function getVal() : string
    {
        $this->clio->parseInput();
        if($this->hasVal()){
            return $this->value;
        }
        throw new LogicException($this->type . ' type options do not have values.');
    }

    public function getValueList() : Vector<string>
    {
        switch($this->type) {
        case CliOptionType::Value:
        case CliOptionType::Path:
            throw new LogicException('Value and Path type options only have one value.');
            break;
        case CliOptionType::MultiValued:
            if($this->wasPresent()){
                return $this->valueList;
            } elseif($this->defaultValueList !== null) {
                return $this->defaultValueList;
            } else {
                throw new MissingOptionValue($this->name);
            }
        case CliOptionType::Flag:
        case CliOptionType::Accumulator:
            throw new LogicException($this->type . ' type options do not have values.');
        }
    }

    public function setVal(?string $value, string $alias) : void
    {
        if($value === null) {
            if($this->hasDefault){
                // The val is already the default
                return;
            }
            throw new MissingOptionValue($alias);
        }
        $this->value = $value;
        if($this->type === CliOptionType::MultiValued) {
            $this->valueList->add($value);
        }
    }

    public function opt(string $name) : CliOption
    {
        return $this->clio->opt($name);
    }

    public function arg(string $name) : CliArg
    {
        return $this->clio->arg($name);
    }

    public function hasVal() : bool
    {
        switch($this->type) {
        case CliOptionType::Path:
        case CliOptionType::Value:
        case CliOptionType::MultiValued:
            return true;
            break;
        case CliOptionType::Flag:
        case CliOptionType::Accumulator:
            return false;
        }
    }

    public function hasDefault() : bool
    {
        return $this->hasDefault;
    }

    public function validatedBy((function(string) : bool) $validator) : this
    {
        $this->validator = $validator;
        return $this;
    }

    public function mustMatchPattern(string $regex) : this
    {
        $this->regex = $regex;
        return $this;
    }

    public function withInvalidMessage(string $message) : this
    {
        $this->invalidMessage = $message;
        return $this;
    }

    public function validate(string $thisname) : void
    {
        if( ! $this->hasVal()) {
            return;
        }

        if($this->type === CliOptionType::Path) {
            $rp = realpath($this->value);
            if($rp === false) {
                throw new \Exception($this->value . ' is not a valid path.');
            }
            $this->value = $rp;
            $this->validateImpl($thisname, $this->value);
        }

        if($this->type === CliOptionType::MultiValued) {
            foreach($this->getValueList() as $val) {
                $this->validateImpl($thisname, $val);
            }
        } else {
            $this->validateImpl($thisname, $this->value);
        }
    }

    private function validateImpl(string $thisname, string $val) : void
    {
        if($this->regex !== null) {
            /* HH_FIXME[4118] */
            if(preg_match($this->regex, '') === false) {
                throw new InvalidOptionValue('Pattern ' . $this->regex . ' is not a valid regular expression.');
            }
            if(! (bool) preg_match($this->regex, $val)) {
                if($this->invalidMessage === null) {
                    $this->invalidMessage = 'The value of '
                        . (strlen($thisname) > 1 ? '--' : '-')
                        . $thisname
                        . ' does not match the regular expression '
                        . $this->regex
                        ;
                }
                throw new InvalidOptionValue($this->invalidMessage);
            }
        }

        $v = $this->validator;
        if($v !== null && ! $v($val)) {
            if($this->invalidMessage === null) {
                $this->invalidMessage = 'The value of '
                    . (strlen($thisname) > 1 ? '--' : '-')
                    . $thisname
                    . ' is not valid.'
                    ;
            }
            throw new InvalidOptionValue($this->invalidMessage);
        }
    }
}
