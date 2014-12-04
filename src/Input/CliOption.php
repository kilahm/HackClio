<?hh // strict

namespace kilahm\Clio\Input;

use \LogicException;
use \InvalidArgumentException;
use kilahm\Clio\Clio;
use kilahm\Clio\Exception\ClioException;
use kilahm\Clio\Exception\InvalidOptionDefault;

enum CliOptionType : string as string
{
    Value = 'Value';
    Accumulator = 'Accumulator';
    Flag = 'Flag (no value allowed)';
    Path = 'Path';
}

<<__ConsistentConstruct>>
class CliOption
{
    private Set<string> $aliases = Set{};
    private string $value = '';
    private bool $hasDefault = false;
    private int $count = 0;
    private CliOptionType $type = CliOptionType::Flag;

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
        $val = $this->hasVal() ? '' : '<Value>';
        $out = strlen($this->name) === 1 ? '-' . $this->name : '--' . $this->name;
        foreach($this->aliases as $alias) {
            $long = strlen($alias) > 1;
            $out .= PHP_EOL
                . ($long ? '--' : '-')
                . $alias
                . ($long && $this->hasVal() ? ' ' : '')
                . $val
                ;
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

    public function setVal(string $value) : void
    {
        $this->value = $value;
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
        return $this->type === CliOptionType::Value || $this->type === CliOptionType::Path;
    }

    public function hasDefault() : bool
    {
        return $this->hasDefault;
    }
}
