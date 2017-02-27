<?hh // strict

namespace kilahm\Clio\Output;

use kilahm\Clio\Definition\BackgroundCode;
use kilahm\Clio\Definition\CliTextAlign;
use kilahm\Clio\Definition\EffectCode;
use kilahm\Clio\Definition\ForegroundCode;

final class CliFormat
{
    private CliTextAlign $alignment = CliTextAlign::Left;
    private ColorStyle $colors;
    private int $screenWidth;
    private int $leftPad = 0;
    private int $rightPad = 0;
    private int $leftMargin = 0;
    private int $rightMargin = 0;
    private int $textWidth;
    private int $maxWidth;
    private int $paddedTextWidth = 0;
    private bool $vPad = false;
    private Vector<string> $lines;
    private int $baseLineWidth;

    <<__Memoize>>
    private static function getScreenWidth() : int
    {
        return (int)exec('tput cols');
    }

    public static function make(string $text) : this
    {
        return new static($text);
    }
    public function __construct(private string $text)
    {
        $this->lines = Vector::fromItems(explode(PHP_EOL, $text));
        $this->baseLineWidth = max($this->lines->map($l ==> strlen($l)));
        $this->screenWidth = self::getScreenWidth();
        $this->textWidth = $this->screenWidth;
        $this->maxWidth = $this->screenWidth;
        $this->colors = CliColor::plain();
    }

    public function screenWidth() : int
    {
        return $this->screenWidth;
    }

    public function withScreenWidth(int $int) : this
    {
        $this->screenWidth = $int;
        return $this;
    }

    public function splitLines(int $width) : Vector<string>
    {
        return Vector::fromItems(
            explode(
                PHP_EOL,
                wordwrap($this->text, $width, PHP_EOL, true)
            )
        );
    }

    public function lineCount() : int
    {
        $this->adjustSpacing();
        $count = $this->splitLines($this->textWidth)->count();
        return $this->vPad ? $count + 2 : $count;
    }

    public function maxWidth(float $width) : this
    {
        if($width > 1.0) {
            $this->maxWidth = (int)floor($width);
        } else {
            $this->maxWidth = (int)floor($width * $this->screenWidth);
        }
        return $this;
    }

    public function center() : this
    {
        $this->alignment = CliTextAlign::Center;
        return $this;
    }

    public function pushRight() : this
    {
        $this->alignment = CliTextAlign::Right;
        return $this;
    }

    public function vPad() : this
    {
        $this->vPad = true;
        return $this;
    }

    public function pad(float $padding = 0.05) : this
    {
        $this->padRight($padding);
        $this->padLeft($padding);
        return $this;
    }

    public function padRight(float $padding = 0.05) : this
    {
        if($padding >= 1.0) {
            $this->rightPad = (int)$padding;
        } else {
            $this->rightPad = (int)ceil($this->screenWidth * $padding);
        }
        return $this;
    }

    public function padLeft(float $padding = 0.05) : this
    {
        if($padding >= 1.0) {
            $this->leftPad = (int)$padding;
        } else {
            $this->leftPad = (int)ceil($this->screenWidth * $padding);
        }
        return $this;
    }

    public function indent(float $margin = 0.05) : this
    {
        $this->indentLeft($margin);
        $this->indentRight($margin);
        return $this;
    }

    public function indentRight(float $margin = 0.05) : this
    {
        if($margin >= 1.0) {
            $this->rightMargin = (int)$margin;
        } else {
            $this->rightMargin = (int)ceil($this->screenWidth * $margin);
        }
        return $this;
    }

    public function indentLeft(float $margin = 0.05) : this
    {
        if($margin >= 1.0) {
            $this->leftMargin = (int)$margin;
        } else {
            $this->leftMargin = (int)ceil($this->screenWidth * $margin);
        }
        return $this;
    }

    public function fg(ForegroundCode $fg) : this
    {
        $this->colors['fg'] = $fg;
        return $this;
    }

    public function bg(BackgroundCode $bg) : this
    {
        $this->colors['bg'] = $bg;
        return $this;
    }

    public function withEffects(Traversable<EffectCode> $effects) : this
    {
        $this->colors['effects']->addAll($effects);
        return $this;
    }

    public function withEffect(EffectCode $effect) : this
    {
        $this->colors['effects']->add($effect);
        return $this;
    }

    public function withColors(ColorStyle $style) : this
    {
        $this->colors = $style;
        return $this;
    }

    public function asBanner(?ColorStyle $style = null) : string
    {
        if($style === null) {
            $style = CliColor::banner();
        }

        $this->vPad = true;
        $this->rightMargin = $this->leftMargin = $this->leftPad = 2;
        $this->rightPad = max(( $this->screenWidth - strlen($this->text) ) - 8, 2);
        $this->colors = $style;
        return $this->getResult();
    }

    public function asError(?ColorStyle $style = null) : string
    {
        if($style === null) {
            $style = CliColor::error();
        }

        $this->leftMargin = $this->leftPad = $this->rightPad = 2;
        $this->colors = $style;
        return $this->getResult();
    }

    public function __toString() : string
    {
        return $this->getResult();
    }

    public function getResult() : string
    {
        $this->adjustSpacing();
        $lines = $this->splitLines($this->textWidth)->map($line ==> $this->formatLine($line));
        if($this->vPad) {
            $lines->add($this->makeBlankLine());
            $lines->reverse();
            $lines->add($this->makeBlankLine());
            $lines->reverse();
        }

        return implode(PHP_EOL, $lines);
    }

    private function adjustSpacing() : void
    {
        $maxWidth = $this->screenWidth
            - $this->leftPad - $this->rightPad
            - $this->leftMargin - $this->rightMargin;

        while($maxWidth < 0) {

            // Reduce padding first
            if($this->leftPad > 0 || $this->rightPad > 0) {

                // Reduce right pad before left pad
                if($this->leftPad > $this->rightPad) {
                    $this->leftPad -= 1;
                } else {
                    $this->rightPad -= 1;
                }
            } elseif($this->leftMargin > 0 || $this->rightMargin > 0) {

                // Reduce right margin before left margin
                if($this->leftMargin > $this->rightMargin) {
                    $this->leftMargin -= 1;
                } else {
                    $this->rightMargin -= 1;
                }

            // Reduce padding second
            } else {
                throw new \Exception('Screen width for terminal output is 0 characters wide.');
            }

            $this->textWidth += 1;
        }

        $this->textWidth = min($maxWidth, $this->baseLineWidth, $this->maxWidth);

        $this->paddedTextWidth = $this->textWidth + $this->leftPad + $this->rightPad;
    }

    private function formatLine(string $line) : string
    {
        $base = str_repeat(' ', $this->leftMargin)
            . CliColor::make(
                str_pad(str_repeat(' ', $this->leftPad) . $line, $this->paddedTextWidth)
            )->withStyle($this->colors)
            ;
        switch($this->alignment) {
        case CliTextAlign::Left:
            return $base;
            break;
        case CliTextAlign::Center:
            return str_pad($base, $this->screenWidth, ' ', STR_PAD_BOTH);
            break;
        case CliTextAlign::Right:
            return str_pad($base, $this->screenWidth, ' ', STR_PAD_LEFT);
            break;
        }
    }

    private function makeBlankLine() : string
    {
        return str_repeat(' ', $this->leftMargin) . CliColor::make(str_repeat(' ', $this->paddedTextWidth))->withStyle($this->colors);
    }
}
