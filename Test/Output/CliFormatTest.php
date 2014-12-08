<?hh // strict

namespace kilahm\Clio\Test\Output;

use kilahm\Clio\Enum\BackgroundCode;
use kilahm\Clio\Enum\EffectCode;
use kilahm\Clio\Enum\ForegroundCode;
use kilahm\Clio\Enum\UndoEffectCode;
use kilahm\Clio\Output\CliFormat;
use kilahm\Clio\Test\ClioTestCase;

class CliFormatTest extends ClioTestCase
{
    public function testFormatterSplitsOnScreenWidth() : void
    {
        $text = '12345 6789';
        $expected = implode(PHP_EOL, ['1234', '5   ', '6789']);
        $f = CliFormat::make($text)->withScreenWidth(4);
        $this->expect($f->getResult())->toEqual($expected);
    }

    public function testPaddingAddedToFront() : void
    {
        $text = 'string';
        $expected = ' string';
        $f = CliFormat::make($text)->padLeft(1.0);
        $this->expect($f->getResult())->toEqual($expected);
    }

    public function testPaddingAddedToBack() : void
    {
        $text = 'string';
        $expected = 'string ';
        $f = CliFormat::make($text)->padRight(1.0);
        $this->expect($f->getResult())->toEqual($expected);
    }

    public function testPaddingAddedToFrontAndBack() : void
    {
        $text = 'string';
        $expected = ' string ';
        $f = CliFormat::make($text)->pad(1.0);
        $this->expect($f->getResult())->toEqual($expected);
    }

    public function testPaddingCanBeRelative() : void
    {
        $text = 'string';
        $expected = '  string  ';
        $f = CliFormat::make($text)->withScreenWidth(20)->pad(0.1);
        $this->expect($f->getResult())->toEqual($expected);
    }

    public function testColorIsAdded() : void
    {
        $text = 'string';
        $expected = sprintf("\e[%dmstring\e[%dm", ForegroundCode::green, ForegroundCode::normal);
        $f = CliFormat::make($text)->fg(ForegroundCode::green);
        $this->expect($f->getResult())->toEqual($expected);
    }

    public function testPaddingIsColored() : void
    {
        $text = 'string';
        $expected = sprintf("\e[%dm string \e[%dm", ForegroundCode::green, ForegroundCode::normal);
        $f = CliFormat::make($text)->fg(ForegroundCode::green)->pad(1.0);
        $this->expect($f->getResult())->toEqual($expected);
    }

    public function testMarginIsNotColored() : void
    {
        $text = 'string';
        $expected = sprintf(" \e[%dmstring\e[%dm", ForegroundCode::green, ForegroundCode::normal);
        $f = CliFormat::make($text)->fg(ForegroundCode::green)->indent(1.0);
        $this->expect($f->getResult())->toEqual($expected);
    }

    // TODO: test indentleft, indentright, indentright with small screen, push right, center
}
