<?hh // strict

namespace kilahm\Clio\Output;

use kilahm\Clio\Enum\BackgroundCode;
use kilahm\Clio\Enum\EffectCode;
use kilahm\Clio\Enum\ForegroundCode;

type ColorStyle = shape(
    'fg' => ForegroundCode,
    'bg' => BackgroundCode,
    'effect' => EffectCode,
);

final class CliColor
{
    public static function banner() : ColorStyle
    {
        return shape(
            'fg' => ForegroundCode::white,
            'bg' => BackgroundCode::green,
            'effect' => EffectCode::bold,
        );
    }

    public static function plain() : ColorStyle
    {
        return shape(
            'fg' => ForegroundCode::reset,
            'bg' => BackgroundCode::reset,
            'effect' => EffectCode::reset,
        );
    }

    public static function error() : ColorStyle
    {
        return shape(
            'fg' => ForegroundCode::white,
            'bg' => BackgroundCode::light_red,
            'effect' => EffectCode::bold,
        );
    }

    private ColorStyle $style;

    public static function make(string $text) : this
    {
        return new static($text);
    }

    public function withStyle(ColorStyle $style) : string
    {
        return $this
            ->fg($style['fg'])
            ->bg($style['bg'])
            ->effect($style['effect'])
            ->getResult();
    }

    public function __construct(private string $text = '')
    {
        $this->style = self::plain();
    }

    public function fg(ForegroundCode $fg) : this
    {
        $this->style['fg'] = $fg;
        return $this;
    }

    public function bg(BackgroundCode $bg) : this
    {
        $this->style['bg'] = $bg;
        return $this;
    }

    public function effect(EffectCode $effect) : this
    {
        $this->style['effect'] = $effect;
        return $this;
    }

    public function getResult() : string
    {
        return $this->apply() . $this->text . $this->reset();
    }

    private function reset() : string
    {
        if($this->style == self::plain()){
            return '';
        }
        return "\e[m";
    }

    private function apply() : string
    {
        if($this->style == self::plain()) {
            return '';
        }
        return sprintf("\e[%sm", array_reduce($this->style, ($r, $i) ==> {
            if($i != 0) {
                $r .= (strlen($r) > 0 ? ';' : '') . $i;
            }
            return $r;
        }));
    }
}
