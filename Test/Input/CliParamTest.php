<?hh // strict

namespace kilahm\Clio\Test;

use HackPack\HackUnit\Core\TestCase;
use kilahm\Clio\Clio;
use kilahm\Clio\Exception\MissingOptionValue;
use kilahm\Clio\Exception\UnknownOption;

class CliParamTest extends TestCase
{
    public function testClioRecognizesRequiredArgument() : void
    {
        $inputs = Vector{'arg1'};
        $clio = new Clio($inputs);
        $arg = $clio->arg('first');
        $this->expect($clio->getArgVals())->toEqual(Map{'first' => 'arg1'});
        $this->expect($arg->getVal())->toEqual('arg1');
    }

    public function testClioRecognizesAdditionalArgument() : void
    {
        $inputs = Vector{'arg1', 'arg2'};
        $clio = new Clio($inputs);
        $arg = $clio->arg('first');
        $this->expect($clio->getArgVals())->toEqual(Map{'first' => 'arg1', 'Argument 1' => 'arg2'});
    }

    public function testOptionNameValidity() : void
    {
        $clio = new Clio(Vector{});
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
        $clio = new Clio(Vector{'-a'});

        $this->expect(
            $clio->opt('a')->isFlag()->wasPresent()
        )->toEqual(true);
    }

    public function testClioRecognizesAllShortOptions() : void
    {
        $clio = new Clio(Vector{'-abc', '--long', '-de', '-f'});

        $a = $clio->opt('a')->aka('b')->accumulates();
        $c = $clio->opt('c')->isFlag();
        $d = $clio->opt('d');
        $e = $clio->opt('e')->isFlag();
        $f = $clio->opt('f')->isFlag();
        $l = $clio->opt('l')->isFlag();
        $long = $clio->opt('long')->isFlag();

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
        $clio = new Clio(Vector{});

        $this->expect(
            $clio->opt('b')->isFlag()->wasPresent()
        )->toEqual(false);
    }

    public function testClioRecognizesLogOption() : void
    {
        $clio = new Clio(Vector{'--ab'});

        $this->expect(
            $clio->opt('ab')->isFlag()->wasPresent()
        )->toEqual(true);
    }

    public function testClioThrowsExceptionForMissingValue() : void
    {
        $clio = new Clio(Vector{'-a'});

        $this->expectCallable( () ==> {
            $clio->opt('a')->getVal();
        })->toThrow(MissingOptionValue::class);
    }

    public function testClioThrowsExceptionForUnknownOption() : void
    {
        $clio = new Clio(Vector{'-a'});

        $this->expectCallable( () ==> {
            $clio->parseInput();
        })->toThrow(UnknownOption::class);
    }

    public function testClioDoesNotThrowForPresentValue() : void
    {
        $clio = new Clio(Vector{'-aVal1', '--long="stuff"'});

        $this->expectCallable( () ==> {
            $clio->opt('a')->opt('long');
            $this->expect($clio->getOptVals())
                ->toEqual(Map{'a' => 'Val1', 'long' => '"stuff"'});
        })->toNotThrow();
    }

    public function testClioDoesNotThrowForMissingOptionalValue() : void
    {
        $clio = new Clio(Vector{'-a'});

        $this->expectCallable( () ==> {
            $clio
                ->opt('a')->withDefault('Val1')
                ->opt('long')->withDefault('"stuff"');
            $this->expect($clio->getOptVals())
                ->toEqual(Map{'a' => 'Val1', 'long' => '"stuff"'});
        })->toNotThrow();
    }

    public function testClioFindsArgsAndOptionsInAnyOrder() : void
    {
        $clio = new Clio(Vector{
            '-a',
                'valA',
                'arg1',
                '-b',
                'arg2',
        });

        $clio
            ->arg('one')
            ->arg('two');
        $a = $clio->opt('a');
        $b = $clio->opt('b')->isFlag();

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
        $clio = new Clio(Vector{'-abc', '--aLong', '-c'});
        $opt = $clio->opt('a')
            ->aka('b')->aka('c')->aka('aLong')
            ->accumulates();

        $this->expectCallable( () ==> {
            $clio->parseInput();
        })->toNotThrow();

        $this->expect($opt->getCount())->toEqual(5);
    }
}
