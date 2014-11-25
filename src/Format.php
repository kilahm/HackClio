<?hh // strict

namespace kilahm\CommandIO;

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

    public function center(string $text) : string
    {
        return '';
    }
}
