<?hh // strict

namespace kilahm\Clio\Exception;

class InvalidOptionDefault extends ClioException
{
    public function __construct(string $path)
    {
        parent::__construct($path . ' was set as a default and is not a valid file or directory.');
    }
}
