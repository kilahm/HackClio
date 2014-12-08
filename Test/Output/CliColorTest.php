<?hh // strict

namespace kilahm\Clio\Test\Output;

use kilahm\Clio\Enum\BackgroundCode;
use kilahm\Clio\Enum\EffectCode;
use kilahm\Clio\Enum\UndoEffectCode;
use kilahm\Clio\Enum\ForegroundCode;
use kilahm\Clio\Output\CliColor;
use kilahm\Clio\Test\ClioTestCase;

class CliColorTest extends ClioTestCase
{
    public function testPlain() : void
    {
        $color = new CliColor('string');
        $color->withStyle(CliColor::plain());
        $this->expect($color->getResult())
            ->toEqual('string');
    }
    public function testFgColor() : void
    {
        $color = new CliColor('string');
        $color->fg(ForegroundCode::red);
        $this->expect($color->getResult())
            ->toEqual(sprintf(
                "\e[%dmstring\e[%dm",
                ForegroundCode::red,
                ForegroundCode::normal,
            ));
    }

    public function testFgAndBgColor() : void
    {
        $color = new CliColor('string');
        $color->fg(ForegroundCode::red)
            ->bg(BackgroundCode::green);
        $this->expect($color->getResult())
            ->toEqual(sprintf(
                "\e[%d;%dmstring\e[%d;%dm",
                ForegroundCode::red,
                BackgroundCode::green,
                ForegroundCode::normal,
                BackgroundCode::normal,
            ));
    }

    public function testFgBgAndEffect() : void
    {
        $color = new CliColor('string');
        $color->fg(ForegroundCode::red)
            ->bg(BackgroundCode::green)
            ->addEffect(EffectCode::italic);
        $this->expect($color->getResult())
            ->toEqual(sprintf(
                "\e[%d;%d;%dmstring\e[%d;%d;%dm",
                ForegroundCode::red,
                BackgroundCode::green,
                EffectCode::italic,
                ForegroundCode::normal,
                BackgroundCode::normal,
                UndoEffectCode::italic,
            ));
    }
}
