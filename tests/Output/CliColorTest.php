<?hh // partial

namespace kilahm\Clio\Test\Output;

use HackPack\HackUnit\Contract\Assert;
use kilahm\Clio\Definition\BackgroundCode;
use kilahm\Clio\Definition\EffectCode;
use kilahm\Clio\Definition\UndoEffectCode;
use kilahm\Clio\Definition\ForegroundCode;
use kilahm\Clio\Output\CliColor;

class CliColorTest
{
    <<Test>>
    public function testPlain(Assert $assert) : void
    {
        $data = 'string';
        $color = new CliColor($data);
        $color->withStyle(CliColor::plain());

        $assert->string($color->getResult())->is($data);
    }

    <<Test>>
    public function testFgColor(Assert $assert) : void
    {
        $data = 'string';
        $expected = sprintf(
            "\e[%dm%s\e[%dm",
            ForegroundCode::red,
            $data,
            ForegroundCode::normal
        );
        $color = new CliColor($data);
        $color->fg(ForegroundCode::red);

        $assert->string($color->getResult())->is($expected);
    }

    <<Test>>
    public function testFgAndBgColor(Assert $assert) : void
    {
        $data = 'string';
        $expected = sprintf(
            "\e[%d;%dm%s\e[%d;%dm",
            ForegroundCode::red,
            BackgroundCode::green,
            $data,
            ForegroundCode::normal,
            BackgroundCode::normal,
        );
        $color = new CliColor($data);
        $color->fg(ForegroundCode::red)
            ->bg(BackgroundCode::green);

        $assert->string($color->getResult())->is($expected);
    }

    <<Test>>
    public function testFgBgAndEffect(Assert $assert) : void
    {
        $data = 'string';
        $expected = sprintf(
            "\e[%d;%d;%dm%s\e[%d;%d;%dm",
            ForegroundCode::red,
            BackgroundCode::green,
            EffectCode::italic,
            $data,
            ForegroundCode::normal,
            BackgroundCode::normal,
            UndoEffectCode::italic,
        );
        $color = new CliColor($data);
        $color->fg(ForegroundCode::red)
            ->bg(BackgroundCode::green)
            ->addEffect(EffectCode::italic);
        $assert->string($color->getResult())->is($expected);
    }
}
