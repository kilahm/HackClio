<?hh // strict

namespace kilahm\Clio\Exception;

class UnknownOption extends \Exception
{
    public function __construct(string $name)
    {
        parent::__construct('Unknown option: ' . $name);
    }
}
