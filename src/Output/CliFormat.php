<?hh // strict

namespace kilahm\Clio\Output;

use kilahm\Clio\Enum\BackgroundCode;
use kilahm\Clio\Enum\EffectCode;
use kilahm\Clio\Enum\ForegroundCode;

<<__ConsistentConstruct>>
class CliFormat
{
    public ColorStyle $bannerStyle = shape(
        'fg' => ForegroundCode::white,
        'bg' => BackgroundCode::green,
        'effect' => EffectCode::reset,
    );

    public static function makeWithDefaults() : this
    {
        return new static((int)exec('tput cols'), new CliColor());
    }

    public function __construct(private int $width, private CliColor $color)
    {
    }

    public function screenWidth() : int
    {
        return $this->width;
    }

    public function center(string $text, float $padding = 0.05) : string
    {
        $textWidth = 2 * floor((1 - $padding) * $this->width);
        return implode(
            PHP_EOL,
            $this->wrapToVector($text, (int)$textWidth)
            ->map($line ==> str_pad($line, $this->width, STR_PAD_BOTH))
        );
    }

    public function wrapToVector(string $text, int $width) : Vector<string>
    {
        return Vector::fromItems(
            explode(
                PHP_EOL,
                wordwrap($text, $width, PHP_EOL, true)
            )
        );
    }

    public function banner(string $text) : string
    {
        return $this->color->style(str_pad($text, $this->width), $this->bannerStyle);
    }

    public function indent(string $text, float $padding = 0.05) : string
    {
        if($padding > 1) {
            // They probably meant spaces, not percent
            $spaces = (int)$padding;
        } else {
            $spaces = (int)ceil($this->width * $padding);
        }

        $textWidth = $this->width - $spaces;
        return implode(
            PHP_EOL,
            $this->wrapToVector($text, $textWidth)
            ->map($line ==> str_repeat(' ', $spaces) . $line)
        );
    }
}
