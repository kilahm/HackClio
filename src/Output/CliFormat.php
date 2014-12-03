<?hh // strict

namespace kilahm\Clio\Output;

use kilahm\Clio\Enum\BackgroundCode;
use kilahm\Clio\Enum\CliTextAlign;
use kilahm\Clio\Enum\EffectCode;
use kilahm\Clio\Enum\ForegroundCode;

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
        $this->screenWidth = self::getScreenWidth();
        $this->textWidth = $this->screenWidth;
        $this->maxWidth = $this->screenWidth;
        $this->colors = CliColor::plain();
    }

    public function screenWidth() : int
    {
        return $this->screenWidth;
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

    public function center(float $padding = 0.0) : this
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

    public function effect(EffectCode $effect) : this
    {
        $this->colors['effect'] = $effect;
        return $this;
    }

    public function withColors(ColorStyle $style) : this
    {
        $this->colors = $style;
        return $this;
    }

    public function asBanner(ColorStyle $style = CliColor::banner()) : string
    {
        $this->vPad = true;
        $this->rightMargin = $this->leftMargin = $this->leftPad = 2;
        $this->rightPad = max(( $this->screenWidth - strlen($this->text) ) - 8, 2);
        $this->colors = $style;
        return $this->getResult();
    }

    public function asError(ColorStyle $style = CliColor::error()) : string
    {
        $this->leftMargin = $this->leftPad = $this->rightPad = 2;
        $this->colors = $style;
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

        while($maxWidth < 4) {

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
                // Who has a terminal only 4 characters wide?!
                break;
            }

            $this->textWidth += 1;
        }

        $this->textWidth = min($maxWidth, strlen($this->text), $this->maxWidth);

        $this->paddedTextWidth = $this->textWidth + $this->leftPad + $this->rightPad;
    }

    private function formatLine(string $line) : string
    {
        return str_repeat(' ', $this->leftMargin)
            . CliColor::make(
                str_pad(str_repeat(' ', $this->leftPad) . $line, $this->paddedTextWidth)
            )->withStyle($this->colors)
            . str_repeat(' ', $this->rightMargin);
    }

    private function makeBlankLine() : string
    {
        return str_repeat(' ', $this->leftMargin) . CliColor::make(str_repeat(' ', $this->paddedTextWidth))->withStyle($this->colors);
    }
}
