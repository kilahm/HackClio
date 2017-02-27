<?hh // strict

namespace kilahm\Clio\Test\Input;

use HackPack\HackUnit\Contract\Assert;
use kilahm\Clio\Exception\MissingOptionValue;
use kilahm\Clio\Exception\UnknownOption;
use kilahm\Clio\Exception\InvalidOptionValue;
use kilahm\Clio\Clio;
use kilahm\Clio\Input\CliOption;
use kilahm\Clio\Output\CliFormat;

class CliParamTest
{

    private resource $in;

    private resource $out;

    public function __construct()
    {
        $this->in = fopen('php://memory', 'w+');
        $this->out = fopen('php://memory', 'w+');
    }

    <<Test>>
    public function testClioRecognizesRequiredArgument(Assert $assert) : void
    {
        $first_argument = 'arg1';
        $first_argument_key = 'first';
        $clio = $this->makeClio(Vector{$first_argument});
        $arg = $clio->arg($first_argument_key);

        $assert->container($clio->getArgVals())->containsAll(Map{$first_argument_key => $first_argument});
        $assert->string($arg->getVal())->is($first_argument);
    }

    <<Test>>
    public function testClioRecognizesAdditionalArgument(Assert $assert) : void
    {
        $first_argument = 'arg1';
        $first_argument_key = 'first';
        $second_argument = 'arg2';
        $clio = $this->makeClio(Vector{$first_argument, $second_argument});
        $arg = $clio->arg($first_argument_key);

        $assert->container($clio->getArgVals())
            ->containsAll(
                Map{
                    $first_argument_key => $first_argument,
                    '1' => $second_argument
                }
            );
    }

    <<Test>>
    public function testValidOptionNames(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{});
        $valid = Vector{'a', 'b', 'long-opt', 'long'};

        foreach($valid as $name) {
            $assert->mixed($clio->opt($name))->isTypeOf(CliOption::class);
        }
    }

    <<Test>>
    public function testInValidOptionNames(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{});
        $invalid = Vector{'-', '=', 'long--with--dashes', '--'};

        foreach($invalid as $name) {
            $assert
                ->whenCalled(() ==> {
                    $clio->opt($name);
                })
                ->willThrowClassWithMessage(
                    \InvalidArgumentException::class,
                    sprintf('%s is not a valid name for an option.', $name)
                );
        }
    }

    <<Test>>
    public function testClioRecognizesShortOption(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-a'});

        $assert->bool($clio->opt('a')->wasPresent())->is(true);
    }

    <<Test>>
    public function testClioRecognizesAllShortOptions(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-abc', '--long', '-de', '-f'});

        $a = $clio->opt('a')->aka('b')->accumulates();
        $c = $clio->opt('c');
        $d = $clio->opt('d')->withValue();
        $e = $clio->opt('e');
        $f = $clio->opt('f');
        $l = $clio->opt('l');
        $long = $clio->opt('long');

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willNotThrow();

        $assert->bool($a->wasPresent())->is(true);
        $assert->bool($c->wasPresent())->is(true);
        $assert->bool($d->wasPresent())->is(true);
        $assert->bool($f->wasPresent())->is(true);

        // -e is not present because it is the value to -d
        $assert->bool($e->wasPresent())->is(false);

        // -l is not present because it is the first letter of a long option
        $assert->bool($l->wasPresent())->is(false);
    }

    <<Test>>
    public function testClioDoesNotFindMissingOption(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{});

        $assert->bool($clio->opt('b')->wasPresent())->is(false);
    }

    <<Test>>
    public function testClioRecognizesLogOption(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'--ab'});

        $assert->bool($clio->opt('ab')->wasPresent())->is(true);
    }

    <<Test>>
    public function testClioThrowsExceptionForMissingValue(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-a'});

        $assert
            ->whenCalled(() ==> {
                $clio->opt('a')->withValue()->getVal();
            })
            ->willThrowClass(
                MissingOptionValue::class
            );
    }

    <<Test>>
    public function testClioThrowsExceptionForUnknownOption(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-a'});

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willThrowClass(
                UnknownOption::class
            );
    }

    <<Test>>
    public function testClioDoesNotThrowForPresentValue(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-aVal1', '--long="stuff"'});

        $assert
            ->whenCalled(() ==> {
                $clio->opt('a')->withValue()->opt('long')->withValue();

                $assert->container($clio->getOptVals())
                    ->containsAll(
                        Map{'a' => 'Val1', 'long' => '"stuff"'}
                    );
            })
            ->willNotThrow();
    }

    <<Test>>
    public function testClioDoesNotThrowForMissingOptionalValue(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-a'});

        $assert
            ->whenCalled(() ==> {
                $clio
                    ->opt('a')->withValue('Val1')
                    ->opt('long')->withValue('"stuff"');
                $assert->container($clio->getOptVals())
                    ->containsAll(
                        Map{'a' => 'Val1', 'long' => '"stuff"'}
                    );
            })
            ->willNotThrow();
    }

    <<Test>>
    public function testClioFindsArgsAndOptionsInAnyOrder(Assert $assert) : void
    {
        $val_a = 'valA';
        $clio = $this->makeClio(Vector{
            '-a',
                $val_a,
                'arg1',
                '-b',
                'arg2',
        });

        $clio
            ->arg('one')
            ->arg('two');
        $a = $clio->opt('a')->withValue();
        $b = $clio->opt('b');

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willNotThrow();

        $assert->string($a->getVal())->is($val_a);
        $assert->bool($b->wasPresent())->is(true);
        $assert->container($clio->getArgVals())
            ->containsAll(
                Map{
                    'one' => 'arg1',
                    'two' => 'arg2',
                }
            );
    }

    <<Test>>
    public function testClioAccumulatesForAllAliases(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-abc', '--aLong', '-c'});
        $opt = $clio->opt('a')
            ->aka('b')->aka('c')->aka('aLong')
            ->accumulates();

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willNotThrow();

        $assert->int($opt->getCount())->eq(5);
    }

    <<Test>>
    public function testInvalidOptionValueByPatternThrows(Assert $assert) : void
    {
        $pattern = '|\d+|';
        $clio = $this->makeClio(Vector{'-vInvalid'});
        $opt = $clio->opt('v')->withValue()->mustMatchPattern($pattern);

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willThrowClassWithMessage(
                InvalidOptionValue::class,
                sprintf('The value of -v does not match the regular expression %s', $pattern)
            );
    }

    <<Test>>
    public function testValidOptionValueByPatternDoesNotThrow(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-v valid'});
        $opt = $clio->opt('v')->withValue()->mustMatchPattern('|valid|');

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willNotThrow();
    }

    <<Test>>
    public function testInvalidOptionValueByFunctionThrows(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-vInvalid'});
        $clio
            ->opt('v')->withValue()
            ->validatedBy((string $in) ==> false);

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willThrowClassWithMessage(
                InvalidOptionValue::class,
                'The value of -v is not valid.'
            );
    }

    <<Test>>
    public function testValidOptionValueByFunctionDoesNotThrows(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-vInvalid'});
        $clio
            ->opt('v')->withValue()
            ->validatedBy((string $in) ==> true);

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willNotThrow();
    }

    <<Test>>
    public function testMultiValueOptionGathersAllValues(Assert $assert) : void
    {
        $clio = $this->makeClio(Vector{'-aOne', '-a', 'Two'});
        $clio->opt('a')->withManyValues();

        $assert
            ->whenCalled(() ==> {
                $clio->parseInput();
            })
            ->willNotThrow();
    }

    <<TearDown>>
    public function tearDown() : void
    {
        rewind($this->in);
        ftruncate($this->in, 0);
        rewind($this->out);
        ftruncate($this->out, 0);
    }

    private function makeClio(Vector<string> $argv = Vector{}) : Clio
    {
        $clio = new Clio('unit', $argv, $this->in, $this->out);
        $clio->throwOnParseError();
        return $clio;
    }
}
