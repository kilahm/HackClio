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

<<__ConsistentConstruct>>
class CliColor
{
    private ForegroundCode $fg = ForegroundCode::reset;
    private BackgroundCode $bg = BackgroundCode::reset;
    private EffectCode $effect = EffectCode::reset;

    public static function make(string $text) : this
    {
        return new static($text);
    }

    public function style(string $text, ColorStyle $style) : string
    {
        return $this->text($text)
            ->fg($style['fg'])
            ->bg($style['bg'])
            ->effect($style['effect'])
            ->result();
    }

    public function __construct(private string $text = '')
    {
    }

    public function text(string $text) : this
    {
        $this->text = $text;
        return $this;
    }

    public function fg(ForegroundCode $fg) : this
    {
        $this->fg = $fg;
        return $this;
    }

    public function bg(BackgroundCode $bg) : this
    {
        $this->bg = $bg;
        return $this;
    }

    public function effect(EffectCode $effect) : this
    {
        $this->effect = $effect;
        return $this;
    }

    public function result() : string
    {
        return $this->apply() . $this->text . $this->reset();
    }

    private function apply() : string
    {
        $out = '';

        if($this->fg !== ForegroundCode::reset) {
            $out .= $this->makeSequence($this->fg);
        }

        if($this->bg !== BackgroundCode::reset) {
            $out .= $this->makeSequence($this->bg);
        }

        if($this->effect !== EffectCode::reset) {
            $out .= $this->makeSequence($this->effect);
        }

        return $out;

    }

    private function reset() : string
    {
        $out = '';

        if($this->fg !== ForegroundCode::reset) {
            $out .= $this->makeSequence(ForegroundCode::reset);
        }

        if($this->bg !== BackgroundCode::reset) {
            $out .= $this->makeSequence(BackgroundCode::reset);
        }

        if($this->effect !== EffectCode::reset) {
            $out .= $this->makeSequence(EffectCode::reset);
        }

        return $out;
    }

    private function makeSequence(int $code) : string
    {
        return sprintf("\033[%dm", $code);
    }
}
