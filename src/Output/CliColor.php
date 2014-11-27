<?hh // strict

namespace kilahm\CommandIO\Output;

type ColorStyle = shape(
    'fg' => ForegroundCode,
    'bg' => BackgroundCode,
    'effect' => EffectCode,
);

enum ForegroundCode : int as int
{
    reset      = 39;
    black      = 30;
    red        = 31;
    green      = 32;
    yellow     = 33;
    blue       = 34;
    magenta    = 35;
    cyan       = 36;
    light_gray = 37;

    dark_gray     = 90;
    light_red     = 91;
    light_green   = 92;
    light_yellow  = 93;
    light_blue    = 94;
    light_magenta = 95;
    light_cyan    = 96;
    white         = 97;

}

enum BackgroundCode : int as int
{

    reset      = 49;
    black      = 40;
    red        = 41;
    green      = 42;
    yellow     = 43;
    blue       = 44;
    magenta    = 45;
    cyan       = 46;
    light_gray = 47;

    dark_gray     = 100;
    light_red     = 101;
    light_green   = 102;
    light_yellow  = 103;
    light_blue    = 104;
    light_magenta = 105;
    light_cyan    = 106;
    white         = 107;
}

enum EffectCode : int as int
{
    reset     = 0;
    bold      = 1;
    dark      = 2;
    italic    = 3;
    underline = 4;
    blink     = 5;
    reverse   = 7;
    concealed = 8;
}

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

    public static function style(string $text, ColorStyle $style) : string
    {
        return self::make($text)
            ->fg($style['fg'])
            ->bg($style['bg'])
            ->effect($style['effect'])
            ->out();
    }

    public function __construct(private string $text = '')
    {
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

    public function out() : string
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
