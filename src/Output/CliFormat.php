<?hh // strict

namespace kilahm\CommandIO\Output;

<<__ConsistentConstruct>>
class CliFormat
{
    public static function defaults() : this
    {
        return new static(exec('tput cols'));
    }

    public function __construct(private int $width)
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
}
