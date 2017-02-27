<?hh // strict

namespace kilahm\Clio\Output;

use kilahm\Clio\Definition\BackgroundCode;
use kilahm\Clio\Definition\EffectCode;
use kilahm\Clio\Definition\UndoEffectCode;
use kilahm\Clio\Definition\ForegroundCode;

type ColorStyle = shape(
    'fg' => ForegroundCode,
    'bg' => BackgroundCode,
    'effects' => Vector<EffectCode>,
);

final class CliColor
{
    public static function banner() : ColorStyle
    {
        return shape(
            'fg' => ForegroundCode::white,
            'bg' => BackgroundCode::green,
            'effects' => Vector{EffectCode::bold},
        );
    }

    public static function plain() : ColorStyle
    {
        return shape(
            'fg' => ForegroundCode::normal,
            'bg' => BackgroundCode::normal,
            'effects' => Vector{},
        );
    }

    public static function error() : ColorStyle
    {
        return shape(
            'fg' => ForegroundCode::white,
            'bg' => BackgroundCode::light_red,
            'effects' => Vector{EffectCode::bold},
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
            ->setStyle($style)
            ->getResult();
    }

    public function setStyle(ColorStyle $style) : this
    {
        $this->style = $style;
        return $this;
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

    public function addEffects(Traversable<EffectCode> $effects) : this
    {
        $this->style['effects']->addAll($effects);
        return $this;
    }

    public function addEffect(EffectCode $effect) : this
    {
        $this->style['effects']->add($effect);
        return $this;
    }

    public function getResult() : string
    {
        $effectNames = EffectCode::getNames();
        $undoEffects = UndoEffectCode::getValues();

        $onCodes = Vector{};
        $offCodes = Vector{};
        if($this->style['fg'] !== ForegroundCode::normal) {
            $onCodes->add($this->style['fg']);
            $offCodes->add(ForegroundCode::normal);
        }
        if($this->style['bg'] !== BackgroundCode::normal) {
            $onCodes->add($this->style['bg']);
            $offCodes->add(BackgroundCode::normal);
        }
        foreach($this->style['effects'] as $effect) {
            $onCodes->add($effect);
            $offCodes->add($undoEffects[$effectNames[$effect]]);
        }
        if($onCodes->isEmpty()) {
            return $this->text;
        }
        return sprintf("\e[%sm%s\e[%sm", implode(';', $onCodes), $this->text, implode(';', $offCodes));
    }
}
