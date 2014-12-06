<?hh // strict

namespace kilahm\Clio\Input;

use kilahm\Clio\Clio;

class CliArg
{
    private string $value = '';
    public string $description = '';
    public bool $isPath = false;
    public bool $present = false;

    public function __construct(public string $name, private Clio $clio)
    {
    }

    public function describedAs(string $description) : this
    {
        $this->description = $description;
        return $this;
    }

    public function shouldBePath() : this
    {
        $this->isPath = true;
        return $this;
    }

    public function getVal() : string
    {
        $this->clio->parseInput();
        return $this->value;
    }

    public function setVal(string $val) : void
    {
        $this->present = true;
        $this->value = $val;
    }

    public function opt(string $name) : CliOption
    {
        return $this->clio->opt($name);
    }

    public function arg(string $name) : CliArg
    {
        return $this->clio->arg($name);
    }
}
