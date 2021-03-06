<?hh // strict

namespace kilahm\Clio\Test\Input;

use kilahm\Clio\Test\ClioTestCase;
use kilahm\Clio\Exception\MissingOptionValue;
use kilahm\Clio\Exception\UnknownOption;
use kilahm\Clio\Exception\InvalidOptionValue;
use kilahm\Clio\Output\CliFormat;

class CliParamTest extends ClioTestCase
{
    public function testClioRecognizesRequiredArgument() : void
    {
        $clio = $this->makeClio(Vector{'arg1'});
        $arg = $clio->arg('first');
        $this->expect($clio->getArgVals())->toEqual(Map{'first' => 'arg1'});
        $this->expect($arg->getVal())->toEqual('arg1');
    }

    public function testClioRecognizesAdditionalArgument() : void
    {
        $clio = $this->makeClio(Vector{'arg1', 'arg2'});
        $arg = $clio->arg('first');
        $this->expect($clio->getArgVals())->toEqual(Map{'first' => 'arg1', '1' => 'arg2'});
    }

    public function testOptionNameValidity() : void
    {
        $clio = $this->makeClio(Vector{});
        $valid = Vector{'a', 'b', 'long-opt', 'long'};
        $invalid = Vector{'-', '=', 'long--with--dashes', '--'};

        foreach($valid as $name) {
            $this->expectCallable( () ==> {
                $clio->opt($name);
            })->toNotThrow();
        }

        foreach($invalid as $name) {
            $this->expectCallable( () ==> {
                $clio->opt($name);
            })->toThrow(\InvalidArgumentException::class, $name . ' is not a valid name for an option.');
        }
    }

    public function testClioRecognizesShortOption() : void
    {
        $clio = $this->makeClio(Vector{'-a'});

        $this->expect(
            $clio->opt('a')->wasPresent()
        )->toEqual(true);
    }

    public function testClioRecognizesAllShortOptions() : void
    {
        $clio = $this->makeClio(Vector{'-abc', '--long', '-de', '-f'});

        $a = $clio->opt('a')->aka('b')->accumulates();
        $c = $clio->opt('c');
        $d = $clio->opt('d')->withValue();
        $e = $clio->opt('e');
        $f = $clio->opt('f');
        $l = $clio->opt('l');
        $long = $clio->opt('long');

        $this->expectCallable( () ==> {
            $clio->parseInput();
        })->toNotThrow();

        $this->expect($a->wasPresent())->toEqual(true);
        $this->expect($c->wasPresent())->toEqual(true);
        $this->expect($d->wasPresent())->toEqual(true);
        $this->expect($f->wasPresent())->toEqual(true);

        // -e is not present because it is the value to -d
        $this->expect($e->wasPresent())->toEqual(false);

        // -l is not present because it is the first letter of a long option
        $this->expect($l->wasPresent())->toEqual(false);
    }

    public function testClioDoesNotFindMissingOption() : void
    {
        $clio = $this->makeClio(Vector{});

        $this->expect(
            $clio->opt('b')->wasPresent()
        )->toEqual(false);
    }

    public function testClioRecognizesLogOption() : void
    {
        $clio = $this->makeClio(Vector{'--ab'});

        $this->expect(
            $clio->opt('ab')->wasPresent()
        )->toEqual(true);
    }

    public function testClioThrowsExceptionForMissingValue() : void
    {
        $clio = $this->makeClio(Vector{'-a'});

        $this->expectCallable( () ==> {
            $clio->opt('a')->withValue()->getVal();
        })->toThrow(MissingOptionValue::class);
    }

    public function testClioThrowsExceptionForUnknownOption() : void
    {
        $clio = $this->makeClio(Vector{'-a'});

        $this->expectCallable( () ==> {
            $clio->parseInput();
        })->toThrow(UnknownOption::class);
    }

    public function testClioDoesNotThrowForPresentValue() : void
    {
        $clio = $this->makeClio(Vector{'-aVal1', '--long="stuff"'});

        $this->expectCallable( () ==> {
            $clio->opt('a')->withValue()->opt('long')->withValue();
            $this->expect($clio->getOptVals())
                ->toEqual(Map{'a' => 'Val1', 'long' => '"stuff"'});
        })->toNotThrow();
    }

    public function testClioDoesNotThrowForMissingOptionalValue() : void
    {
        $clio = $this->makeClio(Vector{'-a'});

        $this->expectCallable( () ==> {
            $clio
                ->opt('a')->withValue('Val1')
                ->opt('long')->withValue('"stuff"');
            $this->expect($clio->getOptVals())
                ->toEqual(Map{'a' => 'Val1', 'long' => '"stuff"'});
        })->toNotThrow();
    }

    public function testClioFindsArgsAndOptionsInAnyOrder() : void
    {
        $clio = $this->makeClio(Vector{
            '-a',
                'valA',
                'arg1',
                '-b',
                'arg2',
        });

        $clio
            ->arg('one')
            ->arg('two');
        $a = $clio->opt('a')->withValue();
        $b = $clio->opt('b');

        $this->expectCallable(() ==> {
            $clio->parseInput();
        })->toNotThrow();

        $this->expect($a->getVal())->toEqual('valA');
        $this->expect($b->wasPresent())->toEqual(true);
        $this->expect($clio->getArgVals())->toEqual(Map{
            'one' => 'arg1',
            'two' => 'arg2',
        });
    }

    public function testClioAccumulatesForAllAliases() : void
    {
        $clio = $this->makeClio(Vector{'-abc', '--aLong', '-c'});
        $opt = $clio->opt('a')
            ->aka('b')->aka('c')->aka('aLong')
            ->accumulates();

        $this->expectCallable( () ==> {
            $clio->parseInput();
        })->toNotThrow();

        $this->expect($opt->getCount())->toEqual(5);
    }

    public function testInvalidOptionValueByPatternThrows() : void
    {
        $clio = $this->makeClio(Vector{'-vInvalid'});
        $opt = $clio->opt('v')->withValue()->mustMatchPattern('|\d+|');
        $this->expectCallable(() ==> {
            $clio->parseInput();
        })->toThrow(InvalidOptionValue::class, 'The value of -v does not match the regular expression |\d+|');
    }

    public function testValidOptionValueByPatternDoesNotThrow() : void
    {
        $clio = $this->makeClio(Vector{'-v valid'});
        $opt = $clio->opt('v')->withValue()->mustMatchPattern('|valid|');
        $this->expectCallable(() ==> {
            $clio->parseInput();
        })->toNotThrow();
    }

    public function testInvalidOptionValueByFunctionThrows() : void
    {
        $clio = $this->makeClio(Vector{'-vInvalid'});
        $clio
            ->opt('v')->withValue()
            ->validatedBy((string $in) ==> false);
        $this->expectCallable(() ==> {
            $clio->parseInput();
        })->toThrow(InvalidOptionValue::class, 'The value of -v is not valid.');
    }

    public function testValidOptionValueByFunctionDoesNotThrows() : void
    {
        $clio = $this->makeClio(Vector{'-vInvalid'});
        $clio
            ->opt('v')->withValue()
            ->validatedBy((string $in) ==> true);
        $this->expectCallable(() ==> {
            $clio->parseInput();
        })->toNotThrow();
    }

    public function testMultiValueOptionGathersAllValues() : void
    {
        $clio = $this->makeClio(Vector{'-aOne', '-a', 'Two'});
        $clio->opt('a')->withManyValues();
        $this->expectCallable(() ==> {
            $clio->parseInput();
        })->toNotThrow();
    }
}
