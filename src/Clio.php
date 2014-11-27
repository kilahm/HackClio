<?hh // strict

namespace kilahm\Clio;

use Exception;
use kilahm\Clio\Exception\ClioException;
use kilahm\Clio\Exception\MissingOptionValue;
use kilahm\Clio\Exception\UnknownOption;
use kilahm\Clio\Input\CliOption;
use kilahm\Clio\Input\CliOptionType;
use kilahm\Clio\Input\CliArg;

class Clio
{
    private bool $suppressAutoHelp = false;
    private ?string $helptext = null;
    private int $argCount = 0;
    private bool $shouldCompile = true;

    private Map<string, CliOption> $definedOptions = Map{};
    private Vector<CliArg> $args = Vector{};

    private Map<string, CliOption> $presentOptions = Map{};

    private Vector<string> $argv;

    public function __construct(?Vector<string> $argv)
    {
        if($argv === null) {
            $argv = $this->getServerArgv();
        }
        $this->argv = $argv;

        set_exception_handler((Exception $e) ==> {
            // TODO: format and colorize this
            echo $e->getMessage();
            if( ! ($e instanceof ClioException)) {
                $this->help();
            }
        });
    }

    public function ensureCli() : void
    {
        if(substr(php_sapi_name(), 0, 3) !== 'cli'){
            http_response_code(404);
            exit();
        }
    }

    private function getServerArgv() : Vector<string>
    {
        // UNSAFE
        return Vector::fromItems($_SERVER['argv']);
    }

    public function arg(string $name) : CliArg
    {
        $this->shouldCompile = true;
        $arg = new CliArg($name, $this);
        $this->args->add($arg);
        return $arg;
    }

    public function getArgVals() : Map<string, string>
    {
        $this->parseInput();
        $out = Map{};
        foreach($this->args as $arg) {
            $out->add(Pair{$arg->name, $arg->getVal()});
        }
        return $out;
    }

    public function countArgs() : int
    {
        $this->parseInput();
        return $this->argCount;
    }

    public function getArg(string $name) : CliArg
    {
        foreach($this->args as $arg) {
            if($arg->name === $name) {
                return $arg;
            }
        }
        throw new \DomainException('Argument ' . $name . ' was not defined.');
    }

    public function opt(string $name) : CliOption
    {
        $this->shouldCompile = true;
        $opt = new CliOption($name, $this);
        $this->definedOptions->add(Pair{$name,$opt});
        return $opt;
    }

    public function getOpt(string $name) : CliOption
    {
        $this->parseInput();
        if($this->definedOptions->containsKey($name)) {
            return $this->definedOptions->at($name);
        }
        throw new \DomainException('Option ' . $name . ' was not defined.');
    }

    public function getOptVals() : Map<string, string>
    {
        $this->parseInput();
        return $this->definedOptions
            ->filter($out ==> $out->hasVal())
            ->map($opt ==> $opt->getVal());
    }

    public function disableHelp() : this
    {
        $this->suppressAutoHelp = true;
        return $this;
    }

    public function setHelp(string $text) : this
    {
        $this->helptext = $text;
        return $this;
    }

    public function help() : void
    {
        if($this->helptext !== null) {
            echo $this->helptext;
            return;
        }

        if($this->suppressAutoHelp) {
            return;
        }

        // Compile all arg and option names and descriptions
    }

    public function parseInput() : void
    {
        if($this->shouldCompile){
            $this->shouldCompile = false;
            $this->argCount = 0;
            $this->flattenOptions();
            $this->presentOptions->clear();

            $argv = $this->argv->toVector();
            $argv->reverse();
            while(! $argv->isEmpty()) {
                $argText = $argv->pop();
                if(substr($argText, 0, 2) === '--'){
                    $this->processLongOpt($argText, $argv);
                } elseif(substr($argText, 0, 1) === '-') {
                    $this->processShortOpt($argText, $argv);
                } else {
                    $this->processArg($argText);
                }
            }
        }
    }

    private function processArg(string $argText) : void
    {
        $arg = $this->args->get($this->argCount);
        if($arg === null) {
            $arg = new CliArg((string)$this->argCount, $this);
            $this->args->add($arg);
        }
        if($arg->isPath) {
            $rp = realpath($argText);
            if($rp === false) {
                throw new \Exception($argText . ' is not a valid path.');
            }
            $arg->setVal($rp);
        } else {
            $arg->setVal($argText);
        }
        $this->argCount += 1;
    }

    private function flattenOptions() : void
    {
        $add = Map{};
        foreach($this->definedOptions as $option) {
            foreach($option->getAliases() as $name) {
                $add->add(Pair{$name, $option});
            }
        }
        $this->definedOptions->setAll($add);
    }

    private function processLongOpt(string $argText, Vector<string> $argv) : void
    {
        $parts = Vector::fromItems(explode('=', $argText, 2));
        $name = substr($parts->get(0), 2);
        if($name === false) {
            return;
        }

        $this->assertOptionDefined($name);
        $this->processOpt($name, $parts->get(1), $argv);
    }

    private function processShortOpt(string $argText, Vector<string> $argv) : void
    {
        // Skip the leading dash
        for($i = 1; $i < strlen($argText); $i++) {
            $name = substr($argText, $i, 1);
            $this->assertOptionDefined($name);
            $opt = $this->definedOptions->at($name);
            if($opt->hasVal()) {
                $val = substr($argText, $i + 1);
                $val = $val === false ? null : $val;
                $this->processOpt($name, $val, $argv);
                break;
            } else {
                $this->processOpt($name, null, $argv);
            }
        }
    }

    private function processOpt(string $name, ?string $val, Vector<string> $argv) : void
    {
        $opt = $this->definedOptions->at($name);

        if($opt->hasVal()) {

            // Try to make the value non-null
            if($val === null) {
                if($opt->hasDefault()) {
                    // Defining the default sets it to the value
                    $val = $opt->getVal();

                } else {

                    // Try the next argument
                    if($argv->isEmpty()) {
                        throw new MissingOptionValue($name);
                    }
                    $val = $argv->pop();
                    if($val === '' || substr($val,0,1) === '-') {
                        throw new MissingOptionValue($name);
                    }
                }

                if($opt->getType() === CliOptionType::Path) {
                    $rp = realpath($val);
                    if($rp === false) {
                        throw new \Exception($val . ' is not a valid path.');
                    }
                    $val = $rp;
                }
            }

            $opt->setVal($val);
        }

        $opt->incrementCount();
        $this->presentOptions->add(Pair{$name, $opt});
    }

    private function assertOptionDefined(string $name) : void
    {
        if( ! $this->definedOptions->containsKey($name)) {
            throw new UnknownOption($name);
        }
    }

}
