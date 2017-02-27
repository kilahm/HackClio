<?hh // strict

namespace kilahm\Clio\Test\Output;

use HackPack\HackUnit\Contract\Assert;
use kilahm\Clio\Output\CliFormat;
use kilahm\Clio\Definition\ForegroundCode;

class CliFormatTest
{
    <<Test>>
    public function testFormatterSplitsOnScreenWidth(Assert $assert) : void
    {
	$text = '12345 6789';
	$expected = implode(PHP_EOL, ['1234', '5   ', '6789']);
	$f = CliFormat::make($text)->withScreenWidth(4);
        $assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testPaddingAddedToFront(Assert $assert) : void
    {
	$text = 'string';
	$expected = ' string';
	$f = CliFormat::make($text)->padLeft(1.0);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testPaddingAddedToBack(Assert $assert) : void
    {
	$text = 'string';
	$expected = 'string ';
	$f = CliFormat::make($text)->padRight(1.0);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testPaddingAddedToFrontAndBack(Assert $assert) : void
    {
	$text = 'string';
	$expected = ' string ';
	$f = CliFormat::make($text)->pad(1.0);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testPaddingCanBeRelative(Assert $assert) : void
    {
	$text = 'string';
	$expected = '  string  ';
	$f = CliFormat::make($text)->withScreenWidth(20)->pad(0.1);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testColorIsAdded(Assert $assert) : void
    {
	$text = 'string';
	$expected = sprintf("\e[%dmstring\e[%dm", ForegroundCode::green, ForegroundCode::normal);
	$f = CliFormat::make($text)->fg(ForegroundCode::green);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testPaddingIsColored(Assert $assert) : void
    {
	$text = 'string';
	$expected = sprintf("\e[%dm string \e[%dm", ForegroundCode::green, ForegroundCode::normal);
	$f = CliFormat::make($text)->fg(ForegroundCode::green)->pad(1.0);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testMarginIsNotColored(Assert $assert) : void
    {
	$text = 'string';
	$expected = sprintf(" \e[%dmstring\e[%dm", ForegroundCode::green, ForegroundCode::normal);
	$f = CliFormat::make($text)->fg(ForegroundCode::green)->indent(1.0);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testIndentLeftAddsSpaces(Assert $assert) : void
    {
	$text = 'string';
	$expected = '  string';
	$f = CliFormat::make($text)->indentLeft(2.0);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testIndentRightAddsNewlineForSmallScreens(Assert $assert) : void
    {
	$text = 'strings';
	$expected = 'str' . PHP_EOL . 'ing' . PHP_EOL . 's  ';
	$f = CliFormat::make($text)->withScreenWidth(5)->indentRight(2.0);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testIndentRightAddsNothingForLargeScreens(Assert $assert) : void
    {
	$text = 'string';
	$f = CliFormat::make($text)->withScreenWidth(30)->indentRight(2.0);
	$assert->string($f->getResult())->is($text);
    }

    <<Test>>
    public function testIndentCanBeRelative(Assert $assert) : void
    {
	$text = 'string';
	$expected = '  string';
	$f = CliFormat::make($text)->withScreenWidth(20)->indent(0.1);
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testCenter(Assert $assert) : void
    {
	$text = 'string';
	$expected = '      string      ';
	$f = CliFormat::make($text)->withScreenWidth(18)->center();
	$assert->string($f->getResult())->is($expected);
    }

    <<Test>>
    public function testPushRight(Assert $assert) : void
    {
	$text = 'string';
	$expected = str_repeat(' ', 10) . $text;
	$width = strlen($text) + 10;
	$f = CliFormat::make($text)->withScreenWidth($width)->pushRight();
	$assert->string($f->getResult())->is($expected);
    }
}
