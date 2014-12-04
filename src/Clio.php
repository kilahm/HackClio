<?hh // strict

namespace kilahm\Clio;

use Exception;
use InvalidArgumentException;
use kilahm\Clio\Enum\BackgroundCode;
use kilahm\Clio\Enum\EffectCode;
use kilahm\Clio\Enum\ForegroundCode;
use kilahm\Clio\Exception\ClioException;
use kilahm\Clio\Exception\MissingOptionValue;
use kilahm\Clio\Exception\UnknownOption;
use kilahm\Clio\Input\CliOption;
use kilahm\Clio\Input\CliOptionType;
use kilahm\Clio\Input\CliArg;
use kilahm\Clio\Input\CliQuestion;
use kilahm\Clio\Output\CliFormat;

<<__ConsistentConstruct>>
final class Clio
{
    private bool $autoHelp = true;
    private bool $throwOnParseError = false;
    private ?string $helptext = null;
    private ?string $useText = null;
    private int $argCount = 0;
    private bool $shouldCompile = true;

    private Map<string, CliOption> $definedOptions = Map{};
    private Vector<CliArg> $args = Vector{};

    private Map<string, CliOption> $flatOptions = Map{};

    private static function getServerArgv() : Vector<string>
    {
        // UNSAFE
        return Vector::fromItems($_SERVER['argv']);
    }

    public static function fromCli() : this
    {
        $clio = self::fromCliWithoutHelp();
        $clio->opt('help')->aka('h')
            ->describedAs('Show this help');
        return $clio;
    }

    public static function fromCliWithoutHelp() : this
    {
        $argv = self::getServerArgv();
        $scriptname = basename($argv[0]);
        $argv->removeKey(0);
        return new static($scriptname, $argv, STDIN, STDOUT);
    }

    public function __construct(
        private string $scriptname,
        private Vector<string> $argv,
        private resource $stdin,
        private resource $stdout
    )
    {
        $this->testStream('input', $stdin);
        $this->testStream('output', $stdout);
    }

    private function testStream(string $name, resource $stream) : void
    {
        if(get_resource_type($stream) !== 'stream') {
            throw new InvalidArgumentException('The resource given for ' . $name . ' is not a stream.');
        }
    }

    public function ensureCli() : void
    {
        if(substr(php_sapi_name(), 0, 3) !== 'cli'){
            http_response_code(404);
            exit();
        }
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
        if($this->flatOptions->containsKey($name)) {
            return $this->flatOptions->at($name);
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

    public function throwOnParseError() : this
    {
        $this->throwOnParseError = true;
        return $this;
    }

    public function setHelp(string $text) : this
    {
        $this->helptext = $text;
        return $this;
    }

    public function setUseText(string $text) : this
    {
        $this->useText = $text;
        return $this;
    }

    public function help() : void
    {
        if($this->helptext !== null) {
            echo $this->helptext;
            return;
        }

        $indent = str_repeat(' ', 4);

        $help = $this->scriptname;
        $description = '';

        if( ! $this->args->isEmpty()) {
            $description .= PHP_EOL . $this->format('Arguments')
                ->fg(ForegroundCode::white)
                ->bg(BackgroundCode::dark_gray)
                ->effect(EffectCode::bold)
                ->indentLeft(2.0)
                ->pad(1.0)
                ;
        }
        foreach($this->args as $arg){
            $help .= ' ' . $arg->name;
            $description .= $this->formatNameAndDescription($arg->name, $arg->description);
        }

        if( ! $this->definedOptions->isEmpty()) {
            $description .= PHP_EOL . $this->format('Options')
                ->fg(ForegroundCode::white)
                ->bg(BackgroundCode::dark_gray)
                ->effect(EffectCode::bold)
                ->indentLeft(2.0)
                ->pad(1.0)
                ;
        }

        foreach($this->definedOptions as $opt) {
            $name = $opt->getAllNames();
            if($opt->hasVal()) {
                $name .= ' <Value>';
            }
            $description .= $this->formatNameAndDescription($name, $opt->description);
        }

        //$useText = $this->format('Use:')->fg(ForegroundCode::blue)->effect(EffectCode::underline);
        $helpText = $this->format("Use:\n$help")->indentLeft(0.0)->padLeft(3.0)->vPad()
            ->fg(ForegroundCode::white)->bg(BackgroundCode::dark_gray);//->effect(EffectCode::bold);

        echo  PHP_EOL . $helpText
            . PHP_EOL . $description;
    }

    private function formatUseText() : string
    {
        if($this->useText !== null) {
            return $this->useText;
        }
        return (string)$this->format("Use:\n" . implode(' ', $this->args->map($a ==> $a->name)))
            ->padLeft(3.0)->vPad()
            ->fg(ForegroundCode::white)
            ->bg(BackgroundCode::dark_gray)
            //->effect(EffectCode::bold)
            ;
    }

    private function formatNameAndDescription(string $name, string $description) : string
    {
        $out = PHP_EOL . $this->format($name)->indentLeft(0.03)->getResult() . PHP_EOL;
        if($description !== '') {
            $out .= $this->format($description)->indent(0.05)->getResult() . PHP_EOL;
        }
        return $out;
    }

    public function parseInput() : void
    {
        if( ! $this->shouldCompile){
            return;
        }

        $this->shouldCompile = false;
        $this->argCount = 0;

        try{
            $this->flatOptions->clear();
            $this->flattenOptions();

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

            $this->checkForHelp();
        } catch (ClioException $e) {
            if($this->throwOnParseError) {
                throw $e;
            }
            echo PHP_EOL . $this->format($e->getMessage())->asError() . PHP_EOL;
            $this->help();
            exit();
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
        foreach($this->definedOptions as $firstName => $option) {
            $this->flatOptions->add(Pair{$firstName, $option});
            foreach($option->getAliases() as $name) {
                $this->flatOptions->add(Pair{$name, $option});
            }
        }
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
                $opt = $this->flatOptions->at($name);
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
        $opt = $this->flatOptions->at($name);

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
    }

    private function assertOptionDefined(string $name) : void
    {
        if( ! $this->flatOptions->containsKey($name)) {
            throw new UnknownOption($name);
        }
    }

    public function format(string $text) : CliFormat
    {
        return new CliFormat($text);
    }

    public function ask(string $question) : CliQuestion
    {
        return new CliQuestion($question, $this);
    }

    public function getLine() : string
    {
        return trim(fgets($this->stdin));
    }

    public function out(string $out) : void
    {
        fputs($this->stdout, $out);
    }

    public function supressAutoHelp() : this
    {
        $this->autoHelp = false;
        return $this;
    }

    private function checkForHelp() : void
    {
        if($this->autoHelp && $this->flatOptions->get('help')?->wasPresent()) {
            $this->help();
            exit();
        }
    }
}
